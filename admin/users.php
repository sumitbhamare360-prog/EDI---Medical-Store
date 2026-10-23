<?php
// Administrator user list.
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/header.php';
$users = db()->query('SELECT u.user_id,u.username,u.full_name,u.status,r.role_name FROM users u JOIN roles r ON r.role_id=u.role_id ORDER BY u.user_id')->fetchAll();
?>
<h1>Users</h1><p><a class="btn" href="<?= BASE_URL ?>/admin/add_user.php">Add user</a></p>
<table><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr><?php foreach ($users as $u): ?><tr><td><?= e($u['full_name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['role_name']) ?></td><td><?= e($u['status']) ?></td><td><a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= (int)$u['user_id'] ?>">Edit</a></td></tr><?php endforeach ?></table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
