<?php
// Low stock report - medicines where usable non-expired stock <= reorder level.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$stmt = $pdo->prepare("
    SELECT m.medicine_id, m.name, m.generic_name, c.category_name, m.reorder_level,
           COALESCE(SUM(CASE WHEN b.available_qty > 0 AND b.expiry_date >= CURDATE()
                             THEN b.available_qty ELSE 0 END),0) AS usable_stock
    FROM medicines m
    LEFT JOIN categories c ON c.category_id = m.category_id
    LEFT JOIN batches b ON b.medicine_id = m.medicine_id
    WHERE m.status='active'
    GROUP BY m.medicine_id
    HAVING usable_stock <= m.reorder_level
    ORDER BY m.name
");
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<h1>Low Stock Alerts</h1>
<p>Medicines whose usable (non-expired) stock is at or below the reorder level.</p>
<table class="data-table">
    <thead><tr>
        <th>#</th><th>Name</th><th>Generic Name</th><th>Category</th><th>Usable Stock</th><th>Reorder Level</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="low">
            <td><?php echo (int)$r['medicine_id']; ?></td>
            <td><?php echo e($r['name']); ?></td>
            <td><?php echo e($r['generic_name'] ?? ''); ?></td>
            <td><?php echo e($r['category_name'] ?? ''); ?></td>
            <td><?php echo (int)$r['usable_stock']; ?></td>
            <td><?php echo (int)$r['reorder_level']; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="6">All medicines are above reorder level.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<p class="print-hide"><button class="btn" onclick="window.print()">Print / Save as PDF</button></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
