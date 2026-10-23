<?php
// Suppliers list.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$search = $_GET['search'] ?? '';

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_name LIKE ? OR contact_person LIKE ? ORDER BY supplier_name");
    $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $pdo->prepare("SELECT * FROM suppliers ORDER BY supplier_name");
    $stmt->execute();
}
$rows = $stmt->fetchAll();
?>
<h1>Suppliers</h1>
<div class="bar">
    <form method="get" action="list.php" style="display:inline;">
        <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search supplier...">
        <button type="submit" class="btn">Search</button>
    </form>
    <a class="btn" href="add.php">+ Add Supplier</a>
</div>
<table class="data-table">
    <thead><tr>
        <th>#</th><th>Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Status</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo (int)$r['supplier_id']; ?></td>
            <td><?php echo e($r['supplier_name']); ?></td>
            <td><?php echo e($r['contact_person'] ?? ''); ?></td>
            <td><?php echo e($r['phone'] ?? ''); ?></td>
            <td><?php echo e($r['email'] ?? ''); ?></td>
            <td><span class="badge status-<?php echo e($r['status']); ?>"><?php echo e(ucfirst($r['status'])); ?></span></td>
            <td>
                <a class="btn small" href="edit.php?id=<?php echo (int)$r['supplier_id']; ?>">Edit</a>
                <a class="btn small ghost" href="toggle.php?id=<?php echo (int)$r['supplier_id']; ?>&status=<?php echo e($r['status'] === 'active' ? 'inactive' : 'active'); ?>" onclick="return confirm('Toggle status?')">
                    <?php echo e($r['status'] === 'active' ? 'Deactivate' : 'Activate'); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="7">No suppliers found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
