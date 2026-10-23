<?php
// MediStore helper functions
// e() escaping, flash messages, redirect, audit_log().

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Escape output for HTML to prevent XSS.
 * Use this whenever echoing user-provided or DB data.
 */
function e(string $value = ''): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Set a flash message (shown once on the next page load).
 * type: 'success' or 'error'
 */
function flash(string $type, string $message): void {
    start_session();
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and remove a flash message of the given type.
 */
function get_flash(string $type): ?string {
    start_session();
    $val = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $val;
}

/**
 * Redirect to a URL and stop execution.
 */
function redirect(string $url): void {
    start_session();
    $path = str_starts_with($url, '/') ? $url : (str_contains($url, '/') ? '/' . $url : rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/' . $url);
    header("Location: " . BASE_URL . $path);
    exit;
}

/**
 * Write an audit log entry.
 * Called on: login success/fail, logout, sale, return, stock receive,
 * user create/role change, medicine create/edit, access denied.
 */
function audit_log(?int $user_id, string $action, ?string $entity_type = null, ?int $entity_id = null, ?string $details = null): void {
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $action, $entity_type, $entity_id, $details]);
    } catch (Throwable $e) {
        // Never let audit logging break the main flow
    }
}

/**
 * Start session (wrapper for header.php usage).
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }
}
