<?php
// Add an account without exposing password hashes.
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';
$roles = db()->query('SELECT role_id,role_name FROM roles ORDER BY role_id')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = db()->prepare('INSERT INTO users(role_id,username,password,full_name) VALUES(?,?,?,?)');
        $stmt->execute([(int)$_POST['role_id'],trim($_POST['username']),password_hash($_POST['password'], PASSWORD_DEFAULT),trim($_POST['full_name'])]);
        audit_log((int)$_SESSION['user_id'],'create','users',(int)db()->lastInsertId(),'Created user');
        flash('success','User added.'); redirect('/admin/users.php');
    } catch (Throwable $e) { error_log($e->getMessage()); flash('error','Could not add user.'); }
}
?>
<h1>Add user</h1><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Name <input name="full_name" required></label><label>Username <input name="username" required></label><label>Password <input type="password" name="password" required></label><label>Role <select name="role_id"><?php foreach($roles as $r): ?><option value="<?= (int)$r['role_id'] ?>"><?= e($r['role_name']) ?></option><?php endforeach ?></select></label><button class="btn">Save</button></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
