<?php
// MediStore database configuration
// Edit these values to match your XAMPP setup.
// Runs on XAMPP: Apache + PHP 8 + MySQL/MariaDB.

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'medistore');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a PDO connection to the database.
 * One place to change credentials. Uses prepared statements everywhere.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

require_once __DIR__ . '/app.php';
