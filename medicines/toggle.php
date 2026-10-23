<?php
// Toggle medicine status (active/inactive).
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'manager']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$new_status = $_GET['status'] ?? 'inactive';
if (!in_array($new_status, ['active', 'inactive'], true)) {
    $new_status = 'inactive';
}

$stmt = $pdo->prepare("SELECT name FROM medicines WHERE medicine_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if ($row) {
    $pdo->prepare("UPDATE medicines SET status = ? WHERE medicine_id = ?")->execute([$new_status, $id]);
    audit_log($_SESSION['user_id'], 'update_status', 'medicines', $id, 'Set medicine status to ' . e($new_status));
    flash('success', 'Medicine status updated.');
} else {
    flash('error', 'Medicine not found.');
}
redirect('list.php');
