<?php
// Reports navigation page.
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'manager']);
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Reports & Analytics</h1>
<div class="kpi-grid" style="margin-top: 16px;">
    <div class="kpi card">
        <div class="kpi-label">Sales Report</div>
        <div class="kpi-sub" style="margin: 10px 0;">View completed transactions, date range filters, and total revenue.</div>
        <a class="btn small" href="<?= BASE_URL ?>/reports/sales.php">Open Report</a>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Stock & Inventory Report</div>
        <div class="kpi-sub" style="margin: 10px 0;">Track current batch quantities, storage levels, and stock valuations.</div>
        <a class="btn small" href="<?= BASE_URL ?>/inventory/stock.php">View Stock</a>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Expiry Alerts Report</div>
        <div class="kpi-sub" style="margin: 10px 0;">Monitor expired items and upcoming warning periods to minimize losses.</div>
        <a class="btn small" href="<?= BASE_URL ?>/inventory/expiry.php">View Expiries</a>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Low Stock Alerts</div>
        <div class="kpi-sub" style="margin: 10px 0;">Medicines currently at or below minimum reorder thresholds.</div>
        <a class="btn small" href="<?= BASE_URL ?>/inventory/low_stock.php">View Reorders</a>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Purchase Orders Report</div>
        <div class="kpi-sub" style="margin: 10px 0;">History of supplier purchase orders, quantities ordered, and fulfillment.</div>
        <a class="btn small" href="<?= BASE_URL ?>/purchases/list.php">View Purchases</a>
    </div>
    <div class="kpi card">
        <div class="kpi-label">Returns Report</div>
        <div class="kpi-sub" style="margin: 10px 0;">Customer return items, refunded amounts, and restocked batches.</div>
        <a class="btn small" href="<?= BASE_URL ?>/returns/list.php">View Returns</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
