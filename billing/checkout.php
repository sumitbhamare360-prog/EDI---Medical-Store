<?php
// FEFO atomic checkout.
// Re-validates cart, locks batches in expiry order, allocates stock,
// creates invoice + allocations + ledger in one transaction.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'manager', 'cashier']);
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid request.');
    header('Location: pos.php');
    exit;
}

$cart_json = $_POST['cart_json'] ?? '';
$queue_id = (int)($_POST['queue_id'] ?? 0);
$rx_ref = trim($_POST['rx_ref'] ?? '');

$cart = json_decode($cart_json, true);
if (!is_array($cart) || empty($cart)) {
    flash('error', 'Cart is empty.');
    header('Location: pos.php');
    exit;
}

// Server-side validation: re-read prices + stock requirements from DB
$med_ids = array_column($cart, 'medicine_id');
$placeholders = implode(',', array_fill(0, count($med_ids), '?'));
$stmt = $pdo->prepare("SELECT medicine_id, name, selling_price, requires_prescription FROM medicines WHERE medicine_id IN ($placeholders) AND status='active'");
$stmt->execute($med_ids);
$db_meds = [];
foreach ($stmt->fetchAll() as $medicine) { $db_meds[(int)$medicine['medicine_id']] = $medicine; }

$lines = [];
foreach ($cart as $line) {
    $mid = (int)$line['medicine_id'];
    $qty = (int)($line['qty'] ?? 0);
    if ($qty <= 0) continue;
    if (!isset($db_meds[$mid])) {
        $error = 'Medicine #' . $mid . ' is no longer available.';
        break;
    }
    $m = $db_meds[$mid];
    // Prescription check
    if ((int)$m['requires_prescription'] === 1 && $rx_ref === '') {
        $error = 'Prescription reference required for ' . e($m['name']) . '.';
        break;
    }
    $lines[] = [
        'medicine_id' => $mid,
        'name' => $m['name'],
        'selling_price' => (float)$m['selling_price'],
        'qty' => $qty,
        'requires_prescription' => (int)$m['requires_prescription'],
    ];
}

if ($error === '' && empty($lines)) {
    $error = 'Cart is empty.';
}

if ($error !== '') {
    $_SESSION['flash']['error'] = $error;
    header('Location: pos.php' . ($queue_id ? '?queue=' . $queue_id : ''));
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock batches per medicine in expiry order (FEFO)
    $allocations = []; // batch_id => qty
    $line_details = []; // for invoice items
    $total_amount = 0.0;

    foreach ($lines as $line) {
        $mid = $line['medicine_id'];
        $remaining = $line['qty'];
        $usable_total = 0;

        // Get all usable batches for this medicine, locked, earliest expiry first
        $stmt = $pdo->prepare("
            SELECT batch_id, available_qty, expiry_date
            FROM batches
            WHERE medicine_id = ? AND available_qty > 0 AND expiry_date >= CURDATE()
            ORDER BY expiry_date ASC, batch_id ASC
            FOR UPDATE
        ");
        $stmt->execute([$mid]);
        $batches = $stmt->fetchAll();

        foreach ($batches as $b) {
            $usable_total += (int)$b['available_qty'];
            if ($remaining <= 0) break;
            $take = min((int)$b['available_qty'], $remaining);
            $allocations[$mid][$b['batch_id']] = (int)($allocations[$mid][$b['batch_id']] ?? 0) + $take;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            $pdo->rollBack();
            $_SESSION['flash']['error'] = 'Only ' . ($usable_total) . ' usable unit(s) of ' . e($line['name']) . ' available (you requested ' . $line['qty'] . ').';
            header('Location: pos.php' . ($queue_id ? '?queue=' . $queue_id : ''));
            exit;
        }

        $line_details[] = [
            'medicine_id' => $mid,
            'name' => $line['name'],
            'qty' => $line['qty'],
            'unit_price' => $line['selling_price'],
            'line_total' => $line['qty'] * $line['selling_price'],
        ];
        $total_amount += $line['qty'] * $line['selling_price'];
    }

    // Generate invoice number INV-YYYYMMDD-0001
    $date_prefix = date('Ymd');
    $stmt = $pdo->prepare("
        SELECT invoice_no FROM invoices
        WHERE invoice_no LIKE ?
        ORDER BY invoice_no DESC LIMIT 1
    ");
    $stmt->execute(['INV-' . $date_prefix . '-%']);
    $last = $stmt->fetchColumn();
    $seq = 1;
    if ($last) {
        $parts = explode('-', $last);
        $seq = (int)end($parts) + 1;
    }
    $invoice_no = 'INV-' . $date_prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

    // Insert invoice
    $stmt = $pdo->prepare("
        INSERT INTO invoices (invoice_no, queue_id, cashier_id, customer_name, total_amount, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'completed', NOW())
    ");
    $customer_name = $queue_id ? null : 'Walk-in Customer';
    // Try to get queue customer name
    if ($queue_id) {
        $qstmt = $pdo->prepare("SELECT customer_name FROM queue_entries WHERE queue_id = ?");
        $qstmt->execute([$queue_id]);
        $customer_name = $qstmt->fetchColumn() ?: 'Walk-in Customer';
    }
    $stmt->execute([$invoice_no, $queue_id ?: null, $_SESSION['user_id'], $customer_name, $total_amount]);
    $invoice_id = (int)$pdo->lastInsertId();

    // Insert invoice items, allocations, update batches, ledger
    $ins_item = $pdo->prepare("INSERT INTO invoice_items (invoice_id, medicine_id, medicine_name, qty, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
    $ins_alloc = $pdo->prepare("INSERT INTO sale_allocations (invoice_id, invoice_item_id, batch_id, qty) VALUES (?, ?, ?, ?)");
    $upd_batch = $pdo->prepare("UPDATE batches SET available_qty = available_qty - ? WHERE batch_id = ?");
    $ins_ledger = $pdo->prepare("INSERT INTO ledger_entries (entry_type, reference_id, medicine_id, qty_change, amount, note, created_at) VALUES ('sale', ?, ?, ?, ?, ?, NOW())");

    // Re-fetch batches to allocate precisely (we already computed allocations map)
    // Allocations map: batch_id => qty. We need to deduct from each batch.
    foreach ($allocations as $medicine_id => $batch_allocations) {
        foreach ($batch_allocations as $batch_id => $qty) { $upd_batch->execute([$qty, $batch_id]); }
    }

    foreach ($line_details as $ld) {
        $ins_item->execute([$invoice_id, $ld['medicine_id'], $ld['name'], $ld['qty'], $ld['unit_price'], $ld['line_total']]);
        $invoice_item_id = (int)$pdo->lastInsertId();
        foreach (($allocations[$ld['medicine_id']] ?? []) as $batch_id => $qty) { $ins_alloc->execute([$invoice_id, $invoice_item_id, $batch_id, $qty]); }
        $ins_ledger->execute([$invoice_id, $ld['medicine_id'], -$ld['qty'], $ld['line_total'], 'Sale ' . $invoice_no . ' ' . $ld['name']]);
    }

    // Mark queue completed
    if ($queue_id) {
        $pdo->prepare("UPDATE queue_entries SET status='completed', completed_at=NOW() WHERE queue_id = ?")->execute([$queue_id]);
    }

    audit_log($_SESSION['user_id'], 'sale', 'invoices', $invoice_id, 'Sale ' . $invoice_no . ' total ' . number_format($total_amount, 2));

    $pdo->commit();
    header('Location: invoice.php?id=' . $invoice_id);
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    $_SESSION['flash']['error'] = 'Sale could not be completed. No stock was changed.';
    header('Location: pos.php' . ($queue_id ? '?queue=' . $queue_id : ''));
    exit;
}
