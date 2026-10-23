<?php
// Edit a category.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Category not found.');
    redirect('list.php');
}

$name = $row['category_name'];
$desc = $row['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = trim($_POST['category_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
                $stmt->execute([$name, $desc, $id]);
                audit_log($_SESSION['user_id'], 'update', 'categories', $id, 'Updated category: ' . e($name));
                flash('success', 'Category updated.');
                redirect('list.php');
            } catch (Throwable $e) {
                $error = 'Category name may already exist.';
            }
        }
    }
}
?>
<h1>Edit Category</h1>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="edit.php?id=<?php echo $id; ?>">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Category Name <span class="req">*</span></th><td><input type="text" name="category_name" value="<?php echo e($name); ?>" required></td></tr>
        <tr><th>Description</th><td><input type="text" name="description" value="<?php echo e($desc); ?>"></td></tr>
        <tr><td></td><td><button type="submit" class="btn">Save</button> <a class="btn ghost" href="list.php">Cancel</a></td></tr>
    </table>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
