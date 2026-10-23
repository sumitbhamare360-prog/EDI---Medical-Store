<?php
// MediStore dashboard - KPIs per SRS 14.1
// Accessible to admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

$pdo = db();
$today = date('Y-m-d');

// KPI: today's sales amount and invoice count
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS inv_count, COALESCE(SUM(total_amount),0) AS total_sales
     FROM invoices WHERE DATE(created_at) = ? AND status='completed'"
);
$stmt->execute([$today]);
$today_sales = $stmt->fetch();

// KPI: low stock count (medicines where total non-expired usable qty <= reorder_level)
$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT m.medicine_id) AS low_count
     FROM medicines m
     LEFT JOIN batches b ON b.medicine_id = m.medicine_id AND b.available_qty > 0 AND b.expiry_date >= CURDATE()
     WHERE m.status='active'
     GROUP BY m.medicine_id
     HAVING COALESCE(SUM(b.available_qty),0) <= m.reorder_level"
);
$stmt->execute();
$low_stock_count = $stmt->rowCount();

// KPI: expired batch count
$stmt = $pdo->prepare("SELECT COUNT(*) AS exp_count FROM batches WHERE expiry_date < CURDATE()");
$stmt->execute();
$expired_count = (int)$stmt->fetchColumn();

// KPI: near-expiry count (within settings.expiry_warning_days)
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS near_count FROM batches
    WHERE expiry_date >= CURDATE()
    AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key='expiry_warning_days') DAY)
");
$stmt->execute();
$near_expiry_count = (int)$stmt->fetchColumn();

// KPI: pending POs
$stmt = $pdo->prepare("SELECT COUNT(*) AS po_count FROM purchase_orders WHERE status='pending'");
$stmt->execute();
$pending_po_count = (int)$stmt->fetchColumn();

// KPI: queue length (waiting) and current serving token
$stmt = $pdo->prepare("SELECT COUNT(*) AS wait_count FROM queue_entries WHERE status='waiting'");
$stmt->execute();
$queue_waiting = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT token_no FROM queue_entries WHERE status='serving' ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$current_token = $stmt->fetchColumn();

// KPI: 10 recent transactions (sales/returns/receive)
$stmt = $pdo->prepare("
    SELECT 'sale' AS type, invoice_no AS ref, total_amount AS amount, created_at
    FROM invoices WHERE status='completed'
    UNION ALL
    SELECT 'return' AS type, return_no AS ref, total_amount AS amount, created_at
    FROM returns
    ORDER BY created_at DESC LIMIT 10
");
$stmt->execute();
$recent = $stmt->fetchAll();
?>
<h1>Dashboard</h1>
<div class="kpi-grid">
    <div class="kpi card">
        <div class="kpi-label">Today's Sales</div>
        <div class="kpi-value"><?php echo e(number_format($today_sales['total_sales'], 2)); ?></div>
        <div class="kpi-sub"><?php echo (int)$today_sales['inv_count']; ?> invoices</div>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Low Stock</div>
        <div class="kpi-value"><?php echo $low_stock_count; ?></div>
        <div class="kpi-sub">medicines at/below reorder level</div>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Expired Batches</div>
        <div class="kpi-value"><?php echo $expired_count; ?></div>
        <div class="kpi-sub">past expiry date</div>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Near Expiry</div>
        <div class="kpi-value"><?php echo $near_expiry_count; ?></div>
        <div class="kpi-sub">within warning days</div>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Pending POs</div>
        <div class="kpi-value"><?php echo $pending_po_count; ?></div>
        <div class="kpi-sub">purchase orders awaiting receive</div>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Queue Waiting</div>
        <div class="kpi-value"><?php echo $queue_waiting; ?></div>
        <div class="kpi-sub">current: <?php echo $current_token ? (int)$current_token : '-'; ?></div>
    </div>
</div>

<h2>Recent Transactions</h2>
<table class="data-table">
    <thead><tr>
        <th>Date</th><th>Type</th><th>Reference</th><th>Amount</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recent as $r): ?>
        <tr>
            <td><?php echo e(date('Y-m-d H:i', strtotime($r['created_at']))); ?></td>
            <td><?php echo e(ucfirst($r['type'])); ?></td>
            <td><?php echo e($r['ref']); ?></td>
            <td><?php echo e(number_format($r['amount'], 2)); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($recent)): ?>
        <tr><td colspan="4">No transactions yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/footer.php'; ?>
