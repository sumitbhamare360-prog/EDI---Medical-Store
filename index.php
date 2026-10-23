<?php
// MediStore entry point - redirects to login or dashboard.

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
start_session();

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
