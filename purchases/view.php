<?php
// View a purchase order and its items.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON s.supplier_id = po.supplier_id
    WHERE po.po_id = ?
");
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) {
    flash('error', 'Purchase order not found.');
    redirect('list.php');
}

$stmt = $pdo->prepare("
    SELECT pi.*, m.name AS medicine_name
    FROM purchase_items pi
    JOIN medicines m ON m.medicine_id = pi.medicine_id
    WHERE pi.po_id = ?
    ORDER BY pi.item_id
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<h1>Purchase Order: <?php echo e($po['po_no']); ?></h1>
<p><strong>Supplier:</strong> <?php echo e($po['supplier_name']); ?> &nbsp;|&nbsp; <strong>Status:</strong> <span class="badge status-<?php echo e($po['status']); ?>"><?php echo e(ucfirst($po['status'])); ?></span> &nbsp;|&nbsp; <strong>Created:</strong> <?php echo e(date('Y-m-d H:i', strtotime($po['created_at']))); ?></p>

<h3>Items</h3>
<table class="data-table">
    <thead><tr>
        <th>Medicine</th><th>Qty Ordered</th><th>Qty Received</th><th>Purchase Price</th><th>Line Total</th>
    </tr></thead>
    <tbody>
    <?php $grand=0; foreach ($items as $it): 
        $total=$it['qty_ordered']*$it['purchase_price']; $grand+=$total;
    ?>
        <tr>
            <td><?php echo e($it['medicine_name']); ?></td>
            <td><?php echo (int)$it['qty_ordered']; ?></td>
            <td><?php echo (int)$it['qty_received']; ?></td>
            <td><?php echo e(number_format($it['purchase_price'],2)); ?></td>
            <td><?php echo e(number_format($total,2)); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
        <tr><td colspan="5">No items.</td></tr>
    <?php else: ?>
        <tr><td colspan="4" style="text-align:right"><strong>Grand Total</strong></td><td><strong><?php echo e(number_format($grand,2)); ?></strong></td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php if ($po['status'] === 'pending' && in_array($_SESSION['user_role'] ?? '', ['admin','manager'], true)): ?>
    <p><a class="btn" href="receive.php?id=<?php echo $id; ?>">Receive Stock</a></p>
<?php endif; ?>
<p><a class="btn ghost" href="list.php">Back to PO List</a></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
