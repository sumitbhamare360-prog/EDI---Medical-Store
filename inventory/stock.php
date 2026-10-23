<?php
// Inventory stock - all batches with medicine, qty, expiry, status.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$pdo = db();
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT b.*, m.name AS medicine_name, CASE WHEN b.expiry_date < CURDATE() THEN 'expired' WHEN b.available_qty=0 THEN 'depleted' ELSE 'active' END AS status
        FROM batches b
        JOIN medicines m ON m.medicine_id = b.medicine_id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND m.name LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($status === 'expired') $sql .= ' AND b.expiry_date < CURDATE()';
elseif ($status === 'depleted') $sql .= ' AND b.available_qty = 0';
elseif ($status === 'active') $sql .= ' AND b.available_qty > 0 AND b.expiry_date >= CURDATE()';
$sql .= " ORDER BY b.expiry_date ASC, b.batch_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<h1>Inventory Stock</h1>
<div class="bar">
    <form method="get" action="stock.php" style="display:inline;">
        <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search medicine...">
        <select name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo $status==='active'?'selected':''; ?>>Active</option>
            <option value="expired" <?php echo $status==='expired'?'selected':''; ?>>Expired</option>
            <option value="depleted" <?php echo $status==='depleted'?'selected':''; ?>>Depleted</option>
        </select>
        <button type="submit" class="btn">Filter</button>
        <a class="btn" href="stock.php">Clear</a>
    </form>
</div>
<table class="data-table">
    <thead><tr>
        <th>Batch No</th><th>Medicine</th><th>Available Qty</th><th>Expiry Date</th><th>Status</th><th>Days to Expiry</th>
    </tr></thead>
    <tbody>
    <?php $today = new DateTime(); foreach ($rows as $r): 
        $expiry = new DateTime($r['expiry_date']);
        $days = (int)$today->diff($expiry)->format('%r%a');
        $badgeClass = $r['status'];
        if ($days < 0) $badgeClass = 'expired';
        elseif ($days <= 30) $badgeClass = 'near-expiry';
    ?>
        <tr class="<?php echo e($badgeClass); ?>">
            <td><?php echo e($r['batch_no']); ?></td>
            <td><?php echo e($r['medicine_name']); ?></td>
            <td><?php echo (int)$r['available_qty']; ?></td>
            <td><?php echo e($r['expiry_date']); ?></td>
            <td><span class="badge status-<?php echo e($r['status']); ?>"><?php echo e(ucfirst($r['status'])); ?></span></td>
            <td><?php echo $days; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="6">No batches found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
