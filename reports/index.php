<?php
// Reports navigation page.
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'manager']);
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1><ul>
<?php foreach (['sales'=>'Sales','stock'=>'Stock','expiry'=>'Expiry','low_stock'=>'Low Stock','purchases'=>'Purchases','returns'=>'Returns','ledger'=>'Ledger','audit'=>'Audit'] as $file=>$label): ?>
<li><a href="<?= BASE_URL ?>/reports/<?= e($file) ?>.php"><?= e($label) ?></a></li>
<?php endforeach; ?></ul>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
