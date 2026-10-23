<?php
// Medicines list with search and total usable stock per medicine.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$role = $_SESSION['user_role'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT m.*, c.category_name,
           (SELECT COALESCE(SUM(b.available_qty),0)
            FROM batches b
            WHERE b.medicine_id = m.medicine_id
              AND b.available_qty > 0
              AND b.expiry_date >= CURDATE()
             ) AS usable_stock
        FROM medicines m
        LEFT JOIN categories c ON c.category_id = m.category_id";
$params = [];
if ($search !== '') {
    $sql .= " WHERE m.name LIKE ? OR m.generic_name LIKE ?";
    $params = ['%' . $search . '%', '%' . $search . '%'];
}
$sql .= " ORDER BY m.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<h1>Medicines</h1>
<div class="bar">
    <form method="get" action="list.php" style="display:inline;">
        <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search name or generic name...">
        <button type="submit" class="btn">Search</button>
    </form>
    <?php if (in_array($role, ['admin','manager'], true)): ?>
        <a class="btn" href="add.php">+ Add Medicine</a>
    <?php endif; ?>
</div>
<table class="data-table">
    <thead><tr>
        <th>#</th><th>Name</th><th>Generic Name</th><th>Category</th><th>Rx</th><th>Price</th><th>Usable Stock</th><th>Reorder</th><th>Status</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo (int)$r['medicine_id']; ?></td>
            <td><?php echo e($r['name']); ?></td>
            <td><?php echo e($r['generic_name'] ?? ''); ?></td>
            <td><?php echo e($r['category_name'] ?? ''); ?></td>
            <td><?php echo e((int)$r['requires_prescription'] ? 'Yes' : 'No'); ?></td>
            <td><?php echo e(number_format($r['selling_price'], 2)); ?></td>
            <td><?php echo (int)$r['usable_stock']; ?></td>
            <td><?php echo (int)$r['reorder_level']; ?></td>
            <td><span class="badge status-<?php echo e($r['status']); ?>"><?php echo e(ucfirst($r['status'])); ?></span></td>
            <td>
                <?php if (in_array($role, ['admin','manager'], true)): ?>
                    <a class="btn small" href="edit.php?id=<?php echo (int)$r['medicine_id']; ?>">Edit</a>
                <?php endif; ?>
                <a class="btn small ghost" href="toggle.php?id=<?php echo (int)$r['medicine_id']; ?>&status=<?php echo e($r['status'] === 'active' ? 'inactive' : 'active'); ?>" onclick="return confirm('Toggle status?')">
                    <?php echo e($r['status'] === 'active' ? 'Deactivate' : 'Activate'); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="10">No medicines found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
