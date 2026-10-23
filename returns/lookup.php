<?php
// Returns lookup - find an invoice by number to start a return.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$invoice_no = trim($_GET['invoice_no'] ?? '');
$inv = null;
$items = [];
$error = '';

if ($invoice_no !== '') {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE invoice_no = ? AND status='completed'");
    $stmt->execute([$invoice_no]);
    $inv = $stmt->fetch();
    if (!$inv) {
        $error = 'No completed invoice found with number ' . e($invoice_no);
    } else {
        $stmt = $pdo->prepare("
            SELECT ii.*, 
                   COALESCE(SUM(ri.qty),0) AS returned_qty
            FROM invoice_items ii
            LEFT JOIN return_items ri ON ri.invoice_item_id = ii.item_id
            LEFT JOIN returns r ON r.return_id = ri.return_id
            WHERE ii.invoice_id = ?
            GROUP BY ii.item_id
        ");
        $stmt->execute([$inv['invoice_id']]);
        $items = $stmt->fetchAll();
    }
}
?>
<h1>Returns - Lookup Invoice</h1>
<form method="get" action="lookup.php" style="display:inline;">
    <?php echo csrf_field(); ?>
    <input type="text" name="invoice_no" value="<?php echo e($invoice_no); ?>" placeholder="Invoice number (e.g. INV-20260101-0001)" required>
    <button type="submit" class="btn">Lookup</button>
</form>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<?php if ($inv): ?>
    <h3>Invoice: <?php echo e($inv['invoice_no']); ?> &nbsp;<small>(<?php echo e(date('Y-m-d', strtotime($inv['created_at']))); ?>, Customer: <?php echo e($inv['customer_name']); ?>)</small></h3>
    <table class="data-table">
        <thead><tr>
            <th>Medicine</th><th>Qty Sold</th><th>Already Returned</th><th>Returnable</th><th>Action</th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $it): 
            $returnable = (int)$it['qty'] - (int)$it['returned_qty'];
        ?>
            <tr>
                <td><?php echo e($it['medicine_name']); ?></td>
                <td><?php echo (int)$it['qty']; ?></td>
                <td><?php echo (int)$it['returned_qty']; ?></td>
                <td><?php echo $returnable; ?></td>
                <td>
                    <?php if ($returnable > 0): ?>
                        <a class="btn small" href="process.php?invoice_id=<?php echo (int)$inv['invoice_id']; ?>&item_id=<?php echo (int)$it['item_id']; ?>">Return</a>
                    <?php else: ?>
                        <span class="badge" style="background:#ccc;">Fully returned</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
            <tr><td colspan="5">No items.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
