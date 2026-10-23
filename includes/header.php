<?php
// MediStore shared header layout
// Role-based navigation; includes flash messages.

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
start_session();
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediStore - Medical Store Management</title>
<base href="<?= BASE_URL ?>/">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <div class="brand">MediStore</div>
        <div class="user-info">
            <?php echo e($_SESSION['username'] ?? 'User'); ?> (
            <?php echo e($_SESSION['user_role'] ?? '?'); ?>
            )
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="btn ghost">Logout</a>
    </div>
</header>

<nav class="sidebar">
    <div class="nav-title">Menu</div>
    <ul>
        <?php
        $role = $_SESSION['user_role'] ?? '';
        // Dashboard - all roles
        echo '<li><a href="' . BASE_URL . '/dashboard.php">Dashboard</a></li>';
        if (in_array($role, ['admin', 'manager'], true)) {
            foreach (['medicines/list.php'=>'Medicines','categories/list.php'=>'Categories','suppliers/list.php'=>'Suppliers','purchases/list.php'=>'Purchases','inventory/stock.php'=>'Inventory','inventory/expiry.php'=>'Expiry','inventory/low_stock.php'=>'Low Stock','reports/index.php'=>'Reports'] as $path=>$label) echo '<li><a href="' . BASE_URL . '/' . $path . '">' . $label . '</a></li>';
        }
        if (in_array($role, ['admin', 'manager', 'cashier'], true)) {
            foreach (['billing/queue.php'=>'Queue','billing/pos.php'=>'POS / Billing','prescriptions/add.php'=>'Prescriptions','returns/lookup.php'=>'Returns'] as $path=>$label) echo '<li><a href="' . BASE_URL . '/' . $path . '">' . $label . '</a></li>';
        }
        if ($role === 'admin') foreach (['admin/users.php'=>'Users','admin/settings.php'=>'Settings'] as $path=>$label) echo '<li><a href="' . BASE_URL . '/' . $path . '">' . $label . '</a></li>';
        ?>
    </ul>
</nav>

<main class="content">
    <div class="container">
        <?php
        $error = get_flash('error');
        $success = get_flash('success');
        if ($error): ?>
            <div class="flash error"><?php echo e($error); ?></div>
        <?php endif;
        if ($success): ?>
            <div class="flash success"><?php echo e($success); ?></div>
        <?php endif;
        ?>
