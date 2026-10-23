<?php
// AJAX medicine search for POS. Returns JSON.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'manager', 'cashier']);
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$pdo = db();
$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT m.medicine_id, m.name, m.generic_name, m.selling_price, m.requires_prescription,
               COALESCE(SUM(CASE WHEN b.available_qty > 0 AND b.expiry_date >= CURDATE()
                                 THEN b.available_qty ELSE 0 END),0) AS usable_stock
        FROM medicines m
        LEFT JOIN batches b ON b.medicine_id = m.medicine_id
        WHERE m.status='active' AND (m.name LIKE ? OR m.generic_name LIKE ?)
        GROUP BY m.medicine_id
        ORDER BY m.name
        LIMIT 20
    ");
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
}
echo json_encode($results);
