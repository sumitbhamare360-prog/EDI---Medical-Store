<?php
// Expiry report - expired and soon-to-expire batches, colour-coded.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$warning_days = (int)($pdo->query("SELECT CAST(setting_value AS UNSIGNED) AS v FROM settings WHERE setting_key='expiry_warning_days'")->fetchColumn() ?: 60);

$stmt = $pdo->prepare("
    SELECT b.*, m.name AS medicine_name
    FROM batches b
    JOIN medicines m ON m.medicine_id = b.medicine_id
    WHERE b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY b.expiry_date ASC
");
$stmt->execute([$warning_days]);
$rows = $stmt->fetchAll();
?>
<h1>Expiry Alerts</h1>
<p>Showing batches expired or expiring within <strong><?php echo $warning_days; ?></strong> days. Red = expired, Orange = expiring soon.</p>
<table class="data-table">
    <thead><tr>
        <th>Batch No</th><th>Medicine</th><th>Available Qty</th><th>Expiry Date</th><th>Status</th><th>Days to Expiry</th>
    </tr></thead>
    <tbody>
    <?php $today = new DateTime(); foreach ($rows as $r): 
        $expiry = new DateTime($r['expiry_date']);
        $days = (int)$today->diff($expiry)->format('%r%a');
        $rowClass = $days < 0 ? 'expired' : 'soon';
        $status = $days < 0 ? 'expired' : ($days <= 30 ? 'critical' : 'warning');
    ?>
        <tr class="<?php echo e($rowClass); ?>">
            <td><?php echo e($r['batch_no']); ?></td>
            <td><?php echo e($r['medicine_name']); ?></td>
            <td><?php echo (int)$r['available_qty']; ?></td>
            <td><?php echo e($r['expiry_date']); ?></td>
            <td><span class="badge status-<?php echo e($status); ?>"><?php echo e(ucfirst($status)); ?></span></td>
            <td><?php echo $days; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="6">No expiring or expired batches.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<p class="print-hide"><button class="btn" onclick="window.print()">Print / Save as PDF</button></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
