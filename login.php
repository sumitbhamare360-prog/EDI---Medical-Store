<?php
// MediStore login page
// No authentication required to view. Generic failure message.

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
start_session();

// Already logged in -> dashboard
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($csrf)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        try {
            $pdo = db();
            $stmt = $pdo->prepare(
                "SELECT u.user_id, u.username, u.password, u.status, r.role_name
                 FROM users u
                 JOIN roles r ON r.role_id = u.role_id
                 WHERE u.username = ? LIMIT 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
                require_once __DIR__ . '/auth.php';
                set_logged_in((int)$user['user_id'], $user['username'], $user['role_name']);
                redirect('dashboard.php');
            } else {
                // Log failed attempt
                audit_log(null, 'login_fail', 'users', null, 'Failed login attempt for username: ' . e($username));
                $error = 'Invalid username or password.';
            }
        } catch (Throwable $e) {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediStore - Login</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.login-box { max-width: 360px; margin: 80px auto; padding: 24px; border: 1px solid #ddd; border-radius: 8px; }
.login-box h2 { margin-top: 0; text-align: center; }
.login-box label { display: block; margin: 12px 0 6px; font-weight: 600; }
.login-box input[type="text"], .login-box input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
.login-box .btn { width: 100%; margin-top: 16px; }
.flash.error { color: #b00; background: #fde; border: 1px solid #b00; padding: 10px; border-radius: 4px; margin-bottom: 14px; }
.credits { text-align: center; color: #666; font-size: 12px; margin-top: 14px; }
</style>
</head>
<body>
<div class="login-box">
    <h2>MediStore Login</h2>
    <?php if ($error): ?>
        <div class="flash error"><?php echo e($error); ?></div>
    <?php endif;
    $success = get_flash('success');
    if ($success): ?>
        <div class="flash success"><?php echo e($success); ?></div>
    <?php endif;
    ?>
    <form method="post" action="login.php">
        <?php echo csrf_field(); ?>
        <label>Username</label>
        <input type="text" name="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn">Sign In</button>
    </form>
    <div class="credits">
        Default logins:<br>
        admin / admin123<br>
        manager / manager123<br>
        cashier / cashier123
    </div>
</div>
</body>
</html>
