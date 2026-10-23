<?php
// Serve a prescription file. Requires login. Path stored outside direct web access.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'manager', 'cashier']);
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT file_path FROM prescriptions WHERE prescription_id = ?");
$stmt->execute([$id]);
$path = $stmt->fetchColumn();
if (!$path) {
    http_response_code(404);
    echo 'Prescription file not found.';
    exit;
}

$full = __DIR__ . '/../assets/uploads/' . $path;
if (!is_file($full)) {
    http_response_code(404);
    echo 'File not found on disk.';
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $full);
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($full) . '"');
header('Content-Length: ' . filesize($full));
header('X-Content-Type-Options: nosniff');
readfile($full);
exit;
