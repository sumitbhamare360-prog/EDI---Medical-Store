<?php
// Returns list.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$stmt = $pdo->prepare("
    SELECT r.*, i.invoice_no
    FROM returns r
    JOIN invoices i ON i.invoice_id = r.invoice_id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<h1>Returns</h1>
<table class="data-table">
    <thead><tr>
        <th>Return No</th><th>Invoice</th><th>Reason</th><th>Amount</th><th>Restocked</th><th>Date</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo e($r['return_no']); ?></td>
            <td><?php echo e($r['invoice_no']); ?></td>
            <td><?php echo e($r['reason']); ?></td>
            <td><?php echo e(number_format($r['total_amount'], 2)); ?></td>
            <td><?php echo (int)$r['restocked'] ? 'Yes' : 'No'; ?></td>
            <td><?php echo e(date('Y-m-d H:i', strtotime($r['created_at']))); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="6">No returns found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
