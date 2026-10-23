<?php
// Add a medicine.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$error = '';
$name = $generic = $price = $reorder = '';
$requires_rx = '0';

// Load active categories for dropdown
$cats = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $generic = trim($_POST['generic_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = trim($_POST['selling_price'] ?? '');
        $reorder = trim($_POST['reorder_level'] ?? '');
        $requires_rx = isset($_POST['requires_prescription']) ? '1' : '0';
        if ($name === '' || $category_id === 0 || $price === '') {
            $error = 'Name, category and selling price are required.';
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Selling price must be a valid number.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO medicines (category_id, name, generic_name, requires_prescription, selling_price, reorder_level, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$category_id, $name, $generic, $requires_rx, $price, $reorder === '' ? 0 : (int)$reorder]);
                $id = (int)$pdo->lastInsertId();
                audit_log($_SESSION['user_id'], 'create', 'medicines', $id, 'Created medicine: ' . e($name));
                flash('success', 'Medicine added.');
                redirect('list.php');
            } catch (Throwable $e) {
                $error = 'Could not add medicine.';
            }
        }
    }
}
?>
<h1>Add Medicine</h1>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="add.php">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Category <span class="req">*</span></th><td>
            <select name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?php echo (int)$c['category_id']; ?>"><?php echo e($c['category_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th>Name <span class="req">*</span></th><td><input type="text" name="name" value="<?php echo e($name); ?>" required></td></tr>
        <tr><th>Generic Name</th><td><input type="text" name="generic_name" value="<?php echo e($generic); ?>"></td></tr>
        <tr><th>Selling Price <span class="req">*</span></th><td><input type="number" step="0.01" min="0" name="selling_price" value="<?php echo e($price); ?>" required></td></tr>
        <tr><th>Reorder Level</th><td><input type="number" min="0" name="reorder_level" value="<?php echo e($reorder); ?>"></td></tr>
        <tr><th>Requires Prescription</th><td><label><input type="checkbox" name="requires_prescription" value="1" <?php echo $requires_rx === '1' ? 'checked' : ''; ?>> Yes</label></td></tr>
        <tr><td></td><td><button type="submit" class="btn">Save</button> <a class="btn ghost" href="list.php">Cancel</a></td></tr>
    </table>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
