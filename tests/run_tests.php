<?php
// CLI smoke checks; never connects to the live database.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
if (!extension_loaded('pdo_mysql')) { fwrite(STDERR, "FAIL: pdo_mysql extension is required\n"); exit(1); }
try { $pdo = new PDO('mysql:host=127.0.0.1;dbname=medistore_test;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); }
catch (Throwable $e) { fwrite(STDERR, "FAIL: create/import medistore_test first\n"); exit(1); }
$failed = 0;
$users = $pdo->query("SELECT username,password FROM users WHERE username IN ('admin','manager','cashier')")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach (['admin'=>'admin123','manager'=>'manager123','cashier'=>'cashier123'] as $name=>$password) {
    $ok = isset($users[$name]) && password_verify($password, $users[$name]);
    echo ($ok ? 'PASS' : 'FAIL') . ": seeded password $name\n";
    if (!$ok) $failed++;
}
exit($failed ? 1 : 0);
