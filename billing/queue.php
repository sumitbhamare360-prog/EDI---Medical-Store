<?php
// Customer queue - add customers, call next (FIFO), complete/cancel tokens.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$error = '';
$success = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            $pdo->beginTransaction();
            if ($action === 'add') {
                $name = trim($_POST['customer_name'] ?? '');
                if ($name === '') {
                    $error = 'Customer name is required.';
                } else {
                    $stmt = $pdo->prepare("SELECT COALESCE(MAX(token_no),0) + 1 AS next_token FROM queue_entries WHERE DATE(created_at) = ?");
                    $stmt->execute([$today]);
                    $token = (int)$stmt->fetchColumn();
                    $pdo->prepare("INSERT INTO queue_entries (token_no, customer_name, status, created_at) VALUES (?, ?, 'waiting', NOW())")
                        ->execute([$token, $name]);
                    audit_log($_SESSION['user_id'], 'create', 'queue_entries', (int)$pdo->lastInsertId(), 'Added queue token #' . $token . ' for ' . e($name));
                    $success = 'Token #' . $token . ' added for ' . e($name);
                }
            } elseif ($action === 'call_next') {
                $stmt = $pdo->prepare("
                    SELECT queue_id, token_no FROM queue_entries
                    WHERE status='waiting'
                    ORDER BY token_no ASC, created_at ASC LIMIT 1 FOR UPDATE
                ");
                $stmt->execute();
                $next = $stmt->fetch();
                if ($next) {
                    $pdo->prepare("UPDATE queue_entries SET status='serving', served_at=NOW() WHERE queue_id = ?")->execute([$next['queue_id']]);
                    audit_log($_SESSION['user_id'], 'call_next', 'queue_entries', (int)$next['queue_id'], 'Called token #' . (int)$next['token_no']);
                    $success = 'Now serving token #' . (int)$next['token_no'];
                } else {
                    $error = 'No waiting customers.';
                }
            } elseif ($action === 'complete') {
                $qid = (int)($_POST['queue_id'] ?? 0);
                $pdo->prepare("UPDATE queue_entries SET status='completed', completed_at=NOW() WHERE queue_id = ? AND status='serving'")->execute([$qid]);
                audit_log($_SESSION['user_id'], 'complete', 'queue_entries', $qid, 'Completed queue entry');
                $success = 'Token completed.';
            } elseif ($action === 'cancel') {
                $qid = (int)($_POST['queue_id'] ?? 0);
                $pdo->prepare("UPDATE queue_entries SET status='cancelled' WHERE queue_id = ? AND status IN ('waiting','serving')")->execute([$qid]);
                audit_log($_SESSION['user_id'], 'cancel', 'queue_entries', $qid, 'Cancelled queue entry');
                $success = 'Token cancelled.';
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Queue action failed (' . e($e->getMessage()) . ').';
        }
    }
}

// Fetch queue: today's entries, waiting first then serving then completed/cancelled
$stmt = $pdo->prepare("
    SELECT * FROM queue_entries
    WHERE DATE(created_at) = ?
    ORDER BY
        CASE status WHEN 'waiting' THEN 1 WHEN 'serving' THEN 2 WHEN 'completed' THEN 3 WHEN 'cancelled' THEN 4 END,
        token_no ASC
");
$stmt->execute([$today]);
$queue = $stmt->fetchAll();

// Current serving token
$stmt = $pdo->prepare("SELECT token_no FROM queue_entries WHERE status='serving' ORDER BY served_at DESC LIMIT 1");
$stmt->execute();
$serving = $stmt->fetchColumn();
?>
<h1>Customer Queue</h1>
<div class="bar">
    <form method="post" action="queue.php" style="display:inline;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add">
        <input type="text" name="customer_name" placeholder="Customer name" required>
        <button type="submit" class="btn">+ Add Customer</button>
    </form>
    <?php if (in_array($_SESSION['user_role'] ?? '', ['admin','manager','cashier'], true)): ?>
        <form method="post" action="queue.php" style="display:inline;margin-left:8px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="call_next">
            <button type="submit" class="btn">Call Next</button>
        </form>
    <?php endif; ?>
    <span class="kpi-value" style="font-size:2rem; margin-left:16px; color:#b00;">Serving: <?php echo $serving ? (int)$serving : '-'; ?></span>
</div>
<?php if ($error): echo '<div class="flash error">' . e($error) . '</div>'; endif; ?>
<?php if ($success): echo '<div class="flash success">' . e($success) . '</div>'; endif; ?>
<table class="data-table">
    <thead><tr>
        <th>Token</th><th>Customer</th><th>Status</th><th>Added</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($queue as $q): ?>
        <tr class="<?php echo e($q['status']); ?>">
            <td><strong>#<?php echo (int)$q['token_no']; ?></strong></td>
            <td><?php echo e($q['customer_name']); ?></td>
            <td><span class="badge status-<?php echo e($q['status']); ?>"><?php echo e(ucfirst($q['status'])); ?></span></td>
            <td><?php echo e(date('H:i', strtotime($q['created_at']))); ?></td>
            <td>
                <?php if ($q['status'] === 'serving'): ?>
                    <form method="post" action="queue.php" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="complete">
                        <input type="hidden" name="queue_id" value="<?php echo (int)$q['queue_id']; ?>">
                        <button type="submit" class="btn small" onclick="return confirm('Mark completed?')">Complete</button>
                    </form>
                <?php elseif ($q['status'] === 'waiting'): ?>
                    <form method="post" action="queue.php" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="queue_id" value="<?php echo (int)$q['queue_id']; ?>">
                        <button type="submit" class="btn small ghost" onclick="return confirm('Cancel?')">Cancel</button>
                    </form>
                <?php endif; ?>
                <a class="btn small" href="../billing/pos.php?queue=<?php echo (int)$q['queue_id']; ?>">Sell</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($queue)): ?>
        <tr><td colspan="5">No queue entries today.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
