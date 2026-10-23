<?php
// Toggle category status (active/inactive).
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

$stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if ($row) {
    $pdo->prepare("UPDATE categories SET status = ? WHERE category_id = ?")->execute([$new_status, $id]);
    audit_log($_SESSION['user_id'], 'update_status', 'categories', $id, 'Set category status to ' . e($new_status));
    flash('success', 'Category status updated.');
} else {
    flash('error', 'Category not found.');
}
redirect('list.php');
