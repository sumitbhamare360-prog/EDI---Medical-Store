<?php
// Add a prescription with file upload (jpg/png/pdf, max 2MB, random filename).
// Stored outside direct web access (.htaccess denies). Served via view.php with auth check.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$error = '';
$success = '';
$patient = '';
$ref = '';

$upload_dir = __DIR__ . '/../assets/uploads/prescriptions/';
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $patient = trim($_POST['patient_name'] ?? '');
        $ref = trim($_POST['ref_no'] ?? '');
        if ($patient === '' || $ref === '') {
            $error = 'Patient name and reference number are required.';
        } elseif (!isset($_FILES['prescription_file']) || $_FILES['prescription_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload a prescription file (jpg, png, pdf, max 2MB).';
        } else {
            $file = $_FILES['prescription_file'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!isset($allowed[$mime])) {
                $error = 'Only JPG, PNG and PDF files are allowed.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'File too large (max 2MB).';
            } else {
                $ext = $allowed[$mime];
                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_name, ref_no, file_path, issued_by_cashier) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$patient, $ref, 'prescriptions/' . $filename, $_SESSION['user_id']]);
                        $pid = (int)$pdo->lastInsertId();
                        audit_log($_SESSION['user_id'], 'create', 'prescriptions', $pid, 'Prescription ' . e($ref) . ' for ' . e($patient));
                        flash('success', 'Prescription recorded.');
                        redirect('view.php?id=' . $pid);
                    } catch (Throwable $e) {
                        @unlink($dest);
                        $error = 'Reference number may already exist.';
                    }
                } else {
                    $error = 'Failed to save uploaded file.';
                }
            }
        }
    }
}
?>
<h1>Add Prescription</h1>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<?php if ($success): echo '<div class="flash success">' . e($success) . '</div>'; endif; ?>
<form method="post" action="add.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Patient Name <span class="req">*</span></th><td><input type="text" name="patient_name" value="<?php echo e($patient); ?>" required></td></tr>
        <tr><th>Reference No. <span class="req">*</span></th><td><input type="text" name="ref_no" value="<?php echo e($ref); ?>" required></td></tr>
        <tr><th>Prescription File <span class="req">*</span></th><td>
            <input type="file" name="prescription_file" accept=".jpg,.jpeg,.png,.pdf" required>
            <small class="hint">JPG, PNG or PDF. Max 2MB.</small>
        </td></tr>
        <tr><td></td><td><button type="submit" class="btn">Save</button> <a class="btn ghost" href="../billing/pos.php">Cancel</a></td></tr>
    </table>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
