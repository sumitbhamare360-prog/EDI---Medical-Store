<?php
// Add a category.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$error = '';
$name = '';
$desc = '';

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
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, description, status) VALUES (?, ?, 'active')");
                $stmt->execute([$name, $desc]);
                $id = (int)$pdo->lastInsertId();
                audit_log($_SESSION['user_id'], 'create', 'categories', $id, 'Created category: ' . e($name));
                flash('success', 'Category added.');
                redirect('list.php');
            } catch (Throwable $e) {
                $error = 'Category name may already exist.';
            }
        }
    }
}
?>
<h1>Add Category</h1>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="add.php">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Category Name <span class="req">*</span></th><td><input type="text" name="category_name" value="<?php echo e($name); ?>" required></td></tr>
        <tr><th>Description</th><td><input type="text" name="description" value="<?php echo e($desc); ?>"></td></tr>
        <tr><td></td><td><button type="submit" class="btn">Save</button> <a class="btn ghost" href="list.php">Cancel</a></td></tr>
    </table>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
