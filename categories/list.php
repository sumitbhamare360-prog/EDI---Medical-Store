<?php
// Categories list - view all categories with search, add/edit links, toggle status.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$search = $_GET['search'] ?? '';

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_name LIKE ? ORDER BY category_name");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY category_name");
    $stmt->execute();
}
$rows = $stmt->fetchAll();
?>
<h1>Categories</h1>
<div class="bar">
    <form method="get" action="list.php" style="display:inline;">
        <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search category name...">
        <button type="submit" class="btn">Search</button>
    </form>
    <a class="btn" href="add.php">+ Add Category</a>
</div>
<table class="data-table">
    <thead><tr>
        <th>#</th><th>Name</th><th>Description</th><th>Status</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo (int)$r['category_id']; ?></td>
            <td><?php echo e($r['category_name']); ?></td>
            <td><?php echo e($r['description'] ?? ''); ?></td>
            <td>
                <span class="badge status-<?php echo e($r['status']); ?>">
                    <?php echo e(ucfirst($r['status'])); ?>
                </span>
            </td>
            <td>
                <a class="btn small" href="edit.php?id=<?php echo (int)$r['category_id']; ?>">Edit</a>
                <a class="btn small ghost" href="toggle.php?id=<?php echo (int)$r['category_id']; ?>&status=<?php echo e($r['status'] === 'active' ? 'inactive' : 'active'); ?>" onclick="return confirm('Toggle status?')">
                    <?php echo e($r['status'] === 'active' ? 'Deactivate' : 'Activate'); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="5">No categories found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
