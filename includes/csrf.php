<?php
// MediStore CSRF protection
// Generates and verifies tokens for every POST form.

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Generate a CSRF token and store it in the session.
 */
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for forms.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the submitted CSRF token. Returns true if valid.
 */
function csrf_verify(string $submitted): bool {
    start_session();
    $expected = $_SESSION['csrf_token'] ?? '';
    if (empty($expected) || empty($submitted)) {
        return false;
    }
    return hash_equals($expected, $submitted);
}
