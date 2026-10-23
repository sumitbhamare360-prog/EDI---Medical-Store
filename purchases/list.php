<?php
// Purchase orders list.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$stmt = $pdo->prepare("
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON s.supplier_id = po.supplier_id
    ORDER BY po.created_at DESC
");
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<h1>Purchase Orders</h1>
<div class="bar">
    <a class="btn" href="create.php">+ Create Purchase Order</a>
</div>
<table class="data-table">
    <thead><tr>
        <th>PO No</th><th>Supplier</th><th>Status</th><th>Created</th><th>Received</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo e($r['po_no']); ?></td>
            <td><?php echo e($r['supplier_name']); ?></td>
            <td><span class="badge status-<?php echo e($r['status']); ?>"><?php echo e(ucfirst($r['status'])); ?></span></td>
            <td><?php echo e(date('Y-m-d', strtotime($r['created_at']))); ?></td>
            <td><?php echo $r['received_at'] ? e(date('Y-m-d', strtotime($r['received_at']))) : '-'; ?></td>
            <td>
                <a class="btn small" href="view.php?id=<?php echo (int)$r['po_id']; ?>">View</a>
                <?php if ($r['status'] === 'pending' && in_array($_SESSION['user_role'] ?? '', ['admin','manager'], true)): ?>
                    <a class="btn small" href="receive.php?id=<?php echo (int)$r['po_id']; ?>">Receive</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="6">No purchase orders found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
