<?php
// Print-friendly invoice page.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT i.*, u.username AS cashier_name
    FROM invoices i
    JOIN users u ON u.user_id = i.cashier_id
    WHERE i.invoice_id = ?
");
$stmt->execute([$id]);
$inv = $stmt->fetch();
if (!$inv) {
    flash('error', 'Invoice not found.');
    redirect('billing/queue.php');
}

$stmt = $pdo->prepare("
    SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Batches used for this invoice
$stmt = $pdo->prepare("
    SELECT sa.batch_id, b.batch_no, sa.qty
    FROM sale_allocations sa
    JOIN batches b ON b.batch_id = sa.batch_id
    WHERE sa.invoice_id = ?
    ORDER BY sa.allocation_id
");
$stmt->execute([$id]);
$allocs = $stmt->fetchAll();
$batch_map = [];
foreach ($allocs as $a) {
    $batch_map[(int)$a['batch_id']] = $a;
}

$settings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
?>
<h1>Invoice</h1>
<div class="invoice">
    <div class="inv-header">
        <div class="inv-store">
            <h2><?php echo e($settings['store_name'] ?? 'MediStore'); ?></h2>
            <div><?php echo e($settings['store_address'] ?? ''); ?></div>
        </div>
        <div class="inv-meta">
            <strong>Invoice No:</strong> <?php echo e($inv['invoice_no']); ?><br>
            <strong>Date:</strong> <?php echo e(date('Y-m-d H:i', strtotime($inv['created_at']))); ?><br>
            <strong>Cashier:</strong> <?php echo e($inv['cashier_name']); ?><br>
            <strong>Customer:</strong> <?php echo e($inv['customer_name'] ?? 'Walk-in'); ?>
        </div>
    </div>
    <table class="inv-table">
        <thead><tr>
            <th>Medicine</th><th>Qty</th><th>Unit Price</th><th>Line Total</th>
        </tr></thead>
        <tbody>
        <?php $grand=0; foreach ($items as $it): 
            $grand += $it['line_total'];
        ?>
            <tr>
                <td><?php echo e($it['medicine_name']); ?></td>
                <td><?php echo (int)$it['qty']; ?></td>
                <td><?php echo e(number_format($it['unit_price'], 2)); ?></td>
                <td><?php echo e(number_format($it['line_total'], 2)); ?></td>
            </tr>
        <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="2"></td>
                <td><strong>Total</strong></td>
                <td><strong><?php echo e(number_format($grand, 2)); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php if (!empty($allocs)): ?>
    <div style="margin:12px 0; font-size:13px; color:#555;">
        <strong>Batches dispensed:</strong> 
        <?php foreach ($allocs as $a): ?>
            <?php echo e($a['batch_no']); ?> x <?php echo (int)$a['qty']; ?><?php echo end($allocs) === $a ? '' : ', '; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="inv-footer">
        Thank you for your purchase! Prescription medicines require a valid prescription.
    </div>
</div>
<p class="print-hide"><button class="btn" onclick="window.print()">Print / Save as PDF</button> <a class="btn ghost" href="queue.php">Back to Queue</a></p>
<style>
.invoice { max-width: 720px; margin: 24px auto; font-family: Arial, sans-serif; }
.inv-header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
.inv-store h2 { margin: 0; font-size: 22px; letter-spacing: 2px; }
.inv-store div { font-size: 13px; color: #555; }
.inv-meta { font-size: 14px; line-height: 1.6; }
.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.inv-table th, .inv-table td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
.inv-table th { background: #eee; }
.inv-table td:nth-child(3), .inv-table td:nth-child(4), .inv-table td:nth-child(5) { text-align: right; }
.inv-table .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 15px; }
.inv-footer { font-size: 12px; color: #666; border-top: 1px solid #ccc; padding-top: 10px; }
@media print {
    .topbar, .sidebar, .print-hide, .footer { display: none !important; }
    .content, .container { margin: 0; padding: 0; width: 100%; }
    body { background: #fff; }
}
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
