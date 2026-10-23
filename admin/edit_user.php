<?php
// Change user role or activation state; an administrator cannot disable self.
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin']);
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';
$id=(int)($_GET['id']??0);$q=db()->prepare('SELECT * FROM users WHERE user_id=?');$q->execute([$id]);$user=$q->fetch();
if(!$user){http_response_code(404);echo 'User not found';require_once __DIR__ . '/../includes/footer.php';exit;}
$roles=db()->query('SELECT role_id,role_name FROM roles ORDER BY role_id')->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'&&csrf_verify($_POST['csrf_token']??'')){
    if($id===(int)$_SESSION['user_id']&&($_POST['status']??'')==='inactive'){flash('error','You cannot deactivate your own account.');}
    else{$q=db()->prepare('UPDATE users SET role_id=?,status=? WHERE user_id=?');$q->execute([(int)$_POST['role_id'],$_POST['status'],$id]);audit_log((int)$_SESSION['user_id'],'update','users',$id,'Changed role or status');flash('success','User updated.');redirect('/admin/users.php');}
}
?>
<h1>Edit user <?= e($user['username']) ?></h1><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Role <select name="role_id"><?php foreach($roles as $r): ?><option value="<?= (int)$r['role_id'] ?>" <?= (int)$r['role_id']===(int)$user['role_id']?'selected':'' ?>><?= e($r['role_name']) ?></option><?php endforeach ?></select></label><label>Status <select name="status"><option value="active">Active</option><option value="inactive" <?= $user['status']==='inactive'?'selected':'' ?>>Inactive</option></select></label><button class="btn">Save</button></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
