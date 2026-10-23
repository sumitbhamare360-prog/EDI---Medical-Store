<?php
// MediStore logout
// Destroys the session fully and logs the action.

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
start_session();

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'unknown';

// Log logout then destroy everything
audit_log($user_id, 'logout', 'users', $user_id, 'User logged out: ' . e($username));

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: login.php?logged_out=1');
exit;
