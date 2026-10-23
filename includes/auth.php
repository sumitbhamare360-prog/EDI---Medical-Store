<?php
// MediStore authentication helpers
// Session start, login requirement, role-based access control.

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Returns the current user id from the session, or null if not logged in.
 */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Returns the current user role name from the session, or null if not logged in.
 */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Require that the user is logged in.
 * Redirects to login.php if not.
 */
function require_login(): void {
    start_session();
    if (empty($_SESSION['user_id'])) {
        flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
}

/**
 * Require that the logged-in user has one of the allowed roles.
 * Logs access denial to audit_logs and shows a 403 page.
 * allowed_roles: array of role names, e.g. ['admin','manager']
 */
function require_role(array $allowed_roles): void {
    start_session();
    if (empty($_SESSION['user_id'])) {
        flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
    $role = $_SESSION['user_role'] ?? null;
    if (!in_array($role, $allowed_roles, true)) {
        // Log the access denial
        try {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES (?, 'access_denied', 'page', NULL, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? null, 'User tried to access a page not allowed for role: ' . ($role ?? 'unknown')]);
        } catch (Throwable $e) {
            // If logging fails, continue anyway
        }
        http_response_code(403);
        require_once __DIR__ . '/header.php';
        echo '<div class="container"><h2>403 - Forbidden</h2><p>Your role is not allowed to access this page.</p><a href="' . BASE_URL . '/dashboard.php" class="btn">Back to Dashboard</a></div>';
        require_once __DIR__ . '/footer.php';
        exit;
    }
}

/**
 * Log a successful login in the session and audit log.
 */
function set_logged_in(int $user_id, string $username, string $role): void {
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['user_role'] = $role;
    audit_log($user_id, 'login_success', 'users', $user_id, 'User logged in: ' . htmlspecialchars($username));
}
