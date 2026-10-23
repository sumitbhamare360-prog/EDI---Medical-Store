<?php
// Create a purchase order with line items.
// Roles: admin, manager.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$error = '';
$suppliers = $pdo->query("SELECT * FROM suppliers WHERE status='active' ORDER BY supplier_name")->fetchAll();
$medicines = $pdo->query("SELECT * FROM medicines WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $po_no = trim($_POST['po_no'] ?? '');
        $med_ids = $_POST['medicine_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $prices = $_POST['price'] ?? [];
        if ($supplier_id === 0 || $po_no === '' || empty($med_ids)) {
            $error = 'Supplier, PO number and at least one line are required.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, po_no, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$supplier_id, $po_no]);
                $po_id = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare("INSERT INTO purchase_items (po_id, medicine_id, qty_ordered, qty_received, purchase_price) VALUES (?, ?, ?, 0, ?)");
                foreach ($med_ids as $i => $mid) {
                    $qty = (int)($qtys[$i] ?? 0);
                    $price = (float)($prices[$i] ?? 0);
                    if ($qty > 0) {
                        $ins->execute([$po_id, (int)$mid, $qty, $price]);
                    }
                }
                audit_log($_SESSION['user_id'], 'create', 'purchase_orders', $po_id, 'Created PO ' . e($po_no));
                $pdo->commit();
                flash('success', 'Purchase order created.');
                redirect('view.php?id=' . $po_id);
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Could not create purchase order (' . e($e->getMessage()) . ').';
            }
        }
    }
}
?>
<h1>Create Purchase Order</h1>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="create.php">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Supplier <span class="req">*</span></th><td>
            <select name="supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?php echo (int)$s['supplier_id']; ?>"><?php echo e($s['supplier_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th>PO Number <span class="req">*</span></th><td><input type="text" name="po_no" value="PO-<?php echo date('Ymd'); ?>-<?php echo rand(1000,9999); ?>" required></td></tr>
    </table>

    <h3>Line Items</h3>
    <table id="lines" class="data-table">
        <thead><tr>
            <th>Medicine</th><th>Qty</th><th>Purchase Price</th><th>Action</th>
        </tr></thead>
        <tbody>
            <tr class="line-row">
                <td><select name="medicine_id[]" required>
                    <option value="">Select</option>
                    <?php foreach ($medicines as $m): ?>
                        <option value="<?php echo (int)$m['medicine_id']; ?>"><?php echo e($m['name']); ?></option>
                    <?php endforeach; ?>
                </select></td>
                <td><input type="number" name="qty[]" min="1" required></td>
                <td><input type="number" step="0.01" min="0" name="price[]" required></td>
                <td><button type="button" class="btn small" onclick="removeRow(this)">Remove</button></td>
            </tr>
        </tbody>
    </table>
    <button type="button" class="btn" onclick="addRow()">+ Add Line</button>
    <div style="margin-top:12px;">
        <button type="submit" class="btn">Create PO</button>
        <a class="btn ghost" href="list.php">Cancel</a>
    </div>
</form>
<script>
function addRow(){
    var t=document.getElementById('lines').getElementsByTagName('tbody')[0];
    var r=t.insertRow(-1);
    var html='<td><select name="medicine_id[]" required><option value="">Select</option>';
    <?php foreach ($medicines as $m): ?>
        html+='<option value="<?php echo (int)$m['medicine_id']; ?>"><?php echo e($m['name']); ?></option>';
    <?php endforeach; ?>
    html+='</select></td><td><input type="number" name="qty[]" min="1" required></td><td><input type="number" step="0.01" min="0" name="price[]" required></td><td><button type="button" class="btn small" onclick="removeRow(this)">Remove</button></td>';
    r.innerHTML=html;
}
function removeRow(btn){
    var row=btn.closest('tr');
    if(document.getElementById('lines').getElementsByTagName('tbody')[0].rows.length>1) row.remove();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
