<?php
// View a prescription and download its file (served via download.php with auth check).
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, u.username AS cashier_name
    FROM prescriptions p
    LEFT JOIN users u ON u.user_id = p.issued_by_cashier
    WHERE p.prescription_id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    flash('error', 'Prescription not found.');
    redirect('../billing/pos.php');
}
?>
<h1>Prescription</h1>
<table class="form-table">
    <tr><th>Reference No.</th><td><?php echo e($p['ref_no']); ?></td></tr>
    <tr><th>Patient Name</th><td><?php echo e($p['patient_name']); ?></td></tr>
    <tr><th>Issued By</th><td><?php echo e($p['cashier_name'] ?? '-'); ?></td></tr>
    <tr><th>Created</th><td><?php echo e(date('Y-m-d H:i', strtotime($p['created_at']))); ?></td></tr>
</table>
<?php if ($p['file_path']): ?>
    <p><a class="btn" href="download.php?id=<?php echo (int)$p['prescription_id']; ?>">Download Prescription File</a></p>
<?php endif; ?>
<p><a class="btn ghost" href="../billing/pos.php">Back to POS</a></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
