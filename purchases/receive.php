<?php
// Receive stock for a purchase order. Creates batches (stock increases only here).
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) {
    flash('error', 'Purchase order not found.');
    redirect('list.php');
}
if ($po['status'] !== 'pending') {
    flash('error', 'This purchase order is already ' . e($po['status']) . '.');
    redirect('view.php?id=' . $id);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $batch_nos = $_POST['batch_no'] ?? [];
        $expiries = $_POST['expiry_date'] ?? [];
        $rec_qtys = $_POST['received_qty'] ?? [];
        $all_valid = true;
        $today = date('Y-m-d');
        foreach ($items as $it) {
            $i = $it['item_id'];
            $bn = trim($batch_nos[$i] ?? '');
            $ex = trim($expiries[$i] ?? '');
            $rq = (int)($rec_qtys[$i] ?? 0);
            if ($bn === '' || $ex === '' || $rq <= 0) {
                $error = 'All lines need batch no, future expiry date, and received qty.';
                $all_valid = false; break;
            }
            if ($ex <= $today) {
                $error = 'Expiry date must be in the future.';
                $all_valid = false; break;
            }
        }
        if ($all_valid) {
            try {
                $pdo->beginTransaction();
                $upd_item = $pdo->prepare("UPDATE purchase_items SET qty_received = ? WHERE item_id = ?");
                $ins_batch = $pdo->prepare("
                    INSERT INTO batches (medicine_id, batch_no, purchase_price, available_qty, expiry_date)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($items as $it) {
                    $i = $it['item_id'];
                    $rq = (int)($rec_qtys[$i] ?? 0);
                    $upd_item->execute([$rq, $i]);
                    $ins_batch->execute([$it['medicine_id'], trim($batch_nos[$i]), $it['purchase_price'], $rq, trim($expiries[$i])]);
                }
                // If all items fully received, mark PO received
                $all_received = true;
                foreach ($items as $it) {
                    if ((int)($rec_qtys[$it['item_id']] ?? 0) < (int)$it['qty_ordered']) {
                        $all_received = false;
                    }
                }
                if ($all_received) {
                    $pdo->prepare("UPDATE purchase_orders SET status='received', received_at=NOW() WHERE po_id = ?")->execute([$id]);
                }
                audit_log($_SESSION['user_id'], 'receive', 'purchase_orders', $id, 'Received stock for PO ' . e($po['po_no']));
                $pdo->commit();
                flash('success', 'Stock received. Batches created.');
                redirect('view.php?id=' . $id);
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Could not receive stock (' . e($e->getMessage()) . ').';
            }
        }
    }
}
?>
<h1>Receive Stock - <?php echo e($po['po_no']); ?></h1>
<p><strong>Supplier:</strong> <?php echo e($po['supplier_name']); ?> &nbsp;|&nbsp; <strong>Status:</strong> <?php echo e(ucfirst($po['status'])); ?></p>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="receive.php?id=<?php echo $id; ?>">
    <?php echo csrf_field(); ?>
    <table class="data-table">
        <thead><tr>
            <th>Medicine</th><th>Qty Ordered</th><th>Batch No</th><th>Expiry Date</th><th>Received Qty</th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?php echo e($it['medicine_name']); ?></td>
                <td><?php echo (int)$it['qty_ordered']; ?></td>
                <td><input type="text" name="batch_no[<?php echo $it['item_id']; ?>]" required></td>
                <td><input type="date" name="expiry_date[<?php echo $it['item_id']; ?>]" required></td>
                <td><input type="number" name="received_qty[<?php echo $it['item_id']; ?>]" min="1" value="<?php echo (int)$it['qty_ordered']; ?>" required></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
            <tr><td colspan="5">No items.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div style="margin-top:12px;">
        <button type="submit" class="btn">Confirm Receive</button>
        <a class="btn ghost" href="view.php?id=<?php echo $id; ?>">Cancel</a>
    </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
