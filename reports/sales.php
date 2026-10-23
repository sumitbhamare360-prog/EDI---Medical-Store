<?php
// Sales report - date range filter, table, totals, print button.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT i.*, u.username AS cashier_name
    FROM invoices i
    JOIN users u ON u.user_id = i.cashier_id
    WHERE i.status='completed' AND DATE(i.created_at) BETWEEN ? AND ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();
$total = 0; foreach ($rows as $r) { $total += (float)$r['total_amount']; }
?>
<h1>Sales Report</h1>
<form method="get" action="sales.php" style="display:inline; margin-bottom:12px;">
    From <input type="date" name="from" value="<?php echo e($from); ?>">
    To <input type="date" name="to" value="<?php echo e($to); ?>">
    <button type="submit" class="btn">Filter</button>
    <button class="btn" onclick="window.print()">Print / Save as PDF</button>
</form>
<table class="data-table">
    <thead><tr>
        <th>Invoice No</th><th>Date</th><th>Cashier</th><th>Customer</th><th>Amount</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo e($r['invoice_no']); ?></td>
            <td><?php echo e(date('Y-m-d H:i', strtotime($r['created_at']))); ?></td>
            <td><?php echo e($r['cashier_name']); ?></td>
            <td><?php echo e($r['customer_name'] ?? 'Walk-in'); ?></td>
            <td><?php echo e(number_format($r['total_amount'], 2)); ?></td>
        </tr>
    <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="4"><strong>Total</strong></td>
            <td><strong><?php echo e(number_format($total, 2)); ?></strong></td>
        </tr>
    <?php if (empty($rows)): ?>
        <tr><td colspan="5">No sales in this range.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
