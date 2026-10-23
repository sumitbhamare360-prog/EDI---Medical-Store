<?php
// Process a return for one invoice item.
// Cannot return more than (sold - already returned). Reason required.
// Restock choice: restock to original batch (if not expired) or do not restock.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$invoice_id = (int)($_GET['invoice_id'] ?? 0);
$item_id = (int)($_GET['item_id'] ?? 0);
$error = '';

$stmt = $pdo->prepare("
    SELECT ii.*, i.invoice_no, i.customer_name
    FROM invoice_items ii
    JOIN invoices i ON i.invoice_id = ii.invoice_id
    WHERE ii.item_id = ? AND ii.invoice_id = ?
");
$stmt->execute([$item_id, $invoice_id]);
$item = $stmt->fetch();
if (!$item) {
    flash('error', 'Invoice item not found.');
    redirect('lookup.php');
}

// Already returned qty
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(ri.qty),0) AS returned_qty
    FROM return_items ri
    JOIN returns r ON r.return_id = ri.return_id
    WHERE ri.invoice_item_id = ?
");
$stmt->execute([$item_id]);
$returned = (int)$stmt->fetchColumn();
$returnable = (int)$item['qty'] - $returned;

// Original batch (from sale_allocations) for restock option
$stmt = $pdo->prepare("
    SELECT sa.batch_id, b.batch_no, b.expiry_date, b.available_qty
    FROM sale_allocations sa
    JOIN batches b ON b.batch_id = sa.batch_id
    WHERE sa.invoice_id = ? AND sa.batch_id IN (
        SELECT sa2.batch_id FROM sale_allocations sa2 WHERE sa2.invoice_id = ?
    )
");
$stmt->execute([$invoice_id, $invoice_id]);
$batches = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $qty = (int)($_POST['qty'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $restock = isset($_POST['restock']) ? 1 : 0;
        if ($qty <= 0 || $qty > $returnable) {
            $error = 'Return qty must be between 1 and ' . $returnable . '.';
        } elseif ($reason === '') {
            $error = 'Reason is required.';
        } else {
            try {
                $pdo->beginTransaction();
                $date_prefix = date('Ymd');
                $s = $pdo->prepare("SELECT return_no FROM returns WHERE return_no LIKE ? ORDER BY return_no DESC LIMIT 1");
                $s->execute(['RET-' . $date_prefix . '-%']);
                $last = $s->fetchColumn();
                $seq = 1;
                if ($last) { $parts = explode('-', $last); $seq = (int)end($parts) + 1; }
                $return_no = 'RET-' . $date_prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

                $unit_price = (float)$item['unit_price'];
                $total_amount = $qty * $unit_price;

                $ins_ret = $pdo->prepare("INSERT INTO returns (invoice_id, return_no, reason, total_amount, restocked) VALUES (?, ?, ?, ?, ?)");
                $ins_ret->execute([$invoice_id, $return_no, $reason, $total_amount, $restock]);
                $return_id = (int)$pdo->lastInsertId();

                $ins_item = $pdo->prepare("INSERT INTO return_items (return_id, invoice_item_id, qty, restock_batch_id) VALUES (?, ?, ?, ?)");
                $restock_batch_id = null;
                if ($restock && !empty($batches)) {
                    // Use first usable non-expired original batch
                    foreach ($batches as $b) {
                        if ($b['expiry_date'] > date('Y-m-d')) {
                            $restock_batch_id = (int)$b['batch_id'];
                            $pdo->prepare("UPDATE batches SET available_qty = available_qty + ? WHERE batch_id = ?")->execute([$qty, $restock_batch_id]);
                            break;
                        }
                    }
                }
                $ins_item->execute([$return_id, $item_id, $qty, $restock_batch_id]);

                // Ledger entry (return, negative amount)
                $pdo->prepare("INSERT INTO ledger_entries (entry_type, reference_id, medicine_id, qty_change, amount, note, created_at)
                    VALUES ('return', ?, ?, ?, ?, ?, NOW())")
                    ->execute([$return_id, (int)$item['medicine_id'], $qty, -$total_amount, 'Return ' . $return_no . ' for ' . $item['invoice_no']]);

                audit_log($_SESSION['user_id'], 'return', 'returns', $return_id, 'Return ' . $return_no . ' qty ' . $qty . ' restocked=' . $restock);
                $pdo->commit();
                flash('success', 'Return processed: ' . e($return_no));
                redirect('list.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Return failed (' . e($e->getMessage()) . ').';
            }
        }
    }
}
?>
<h1>Process Return</h1>
<p><strong>Invoice:</strong> <?php echo e($item['invoice_no']); ?> &nbsp;|&nbsp; <strong>Customer:</strong> <?php echo e($item['customer_name']); ?><br>
<strong>Medicine:</strong> <?php echo e($item['medicine_name']); ?> &nbsp;|&nbsp; <strong>Unit Price:</strong> <?php echo e(number_format($item['unit_price'], 2)); ?></p>
<p><strong>Sold:</strong> <?php echo (int)$item['qty']; ?> &nbsp;|&nbsp; <strong>Already Returned:</strong> <?php echo $returned; ?> &nbsp;|&nbsp; <strong>Returnable:</strong> <?php echo $returnable; ?></p>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<form method="post" action="process.php?invoice_id=<?php echo $invoice_id; ?>&item_id=<?php echo $item_id; ?>">
    <?php echo csrf_field(); ?>
    <table class="form-table">
        <tr><th>Return Qty <span class="req">*</span></th><td>
            <input type="number" name="qty" min="1" max="<?php echo $returnable; ?>" value="<?php echo $returnable; ?>" required>
        </td></tr>
        <tr><th>Reason <span class="req">*</span></th><td>
            <textarea name="reason" required placeholder="e.g. damaged, expired, wrong item"><?php echo e($_POST['reason'] ?? ''); ?></textarea>
        </td></tr>
        <tr><th>Restock to original batch?</th><td>
            <label><input type="checkbox" name="restock" value="1"> Yes, add back to original batch (only if not expired)</label><br>
            <small>If unchecked, item is treated as damaged/expired and not restocked.</small>
        </td></tr>
        <tr><td></td><td>
            <button type="submit" class="btn">Process Return</button>
            <a class="btn ghost" href="lookup.php">Cancel</a>
        </td></tr>
    </table>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
