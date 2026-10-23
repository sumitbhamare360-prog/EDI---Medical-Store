<?php
// Add a supplier.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$error = '';
$name = $contact = $phone = $email = $address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = trim($_POST['supplier_name'] ?? '');
        $contact = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name === '') {
            $error = 'Supplier name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$name, $contact, $phone, $email, $address]);
                $id = (int)$pdo->lastInsertId();
                audit_log($_SESSION['user_id'], 'create', 'suppliers', $id, 'Created supplier: ' . e($name));
                flash('success', 'Supplier added.');
                redirect('list.php');
            } catch (Throwable $e) {
                $error = 'Supplier name may already exist.';
            }
        }
    }
}
?>
<h1>Add Supplier</h1>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="add.php">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Supplier Name <span class="req">*</span></th><td><input type="text" name="supplier_name" value="<?php echo e($name); ?>" required></td></tr>
        <tr><th>Contact Person</th><td><input type="text" name="contact_person" value="<?php echo e($contact); ?>"></td></tr>
        <tr><th>Phone</th><td><input type="text" name="phone" value="<?php echo e($phone); ?>"></td></tr>
        <tr><th>Email</th><td><input type="email" name="email" value="<?php echo e($email); ?>"></td></tr>
        <tr><th>Address</th><td><input type="text" name="address" value="<?php echo e($address); ?>"></td></tr>
        <tr><td></td><td><button type="submit" class="btn">Save</button> <a class="btn ghost" href="list.php">Cancel</a></td></tr>
    </table>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
