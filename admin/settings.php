<?php
// Edit store-wide settings.
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    $stmt = db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    foreach (['store_name','store_address','currency_symbol','expiry_warning_days'] as $key) $stmt->execute([$key,trim((string)($_POST[$key] ?? ''))]);
    audit_log((int)$_SESSION['user_id'],'update','settings',null,'Updated store settings'); flash('success','Settings saved.'); redirect('/admin/settings.php');
}
$values=[];foreach(db()->query('SELECT setting_key,setting_value FROM settings')->fetchAll() as $row)$values[$row['setting_key']]=$row['setting_value'];
?>
<h1>Store settings</h1><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php foreach(['store_name'=>'Store name','store_address'=>'Address','currency_symbol'=>'Currency symbol','expiry_warning_days'=>'Expiry warning days'] as $key=>$label): ?><label><?= e($label) ?> <input name="<?= e($key) ?>" value="<?= e($values[$key]??'') ?>" required></label><br><?php endforeach ?><button class="btn">Save</button></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
