<?php
// POS / Billing screen. Left: medicine search + results. Right: cart + checkout.
// Roles: admin, manager, cashier.

declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';

$queue_id = (int)($_GET['queue'] ?? 0);
$queue_name = '';
if ($queue_id) {
    $stmt = db()->prepare("SELECT customer_name FROM queue_entries WHERE queue_id = ?");
    $stmt->execute([$queue_id]);
    $queue_name = $stmt->fetchColumn() ?: '';
}
?>
<h1>Point of Sale</h1>
<div class="pos-wrap">
    <div class="pos-panel left">
        <h3>Search Medicine</h3>
        <input type="text" id="med-search" placeholder="Type medicine name..." autocomplete="off">
        <div id="search-results" class="search-results"></div>
    </div>
    <div class="pos-panel right">
        <h3>Cart</h3>
        <table id="cart-table" class="data-table">
            <thead><tr><th>Medicine</th><th>Price</th><th>Qty</th><th>Line Total</th><th>Rx</th><th>Action</th></tr></thead>
            <tbody id="cart-body"></tbody>
        </table>
        <div class="cart-total"><strong>Total:</strong> <span id="cart-total">0.00</span></div>
        <?php if ($queue_id): ?>
            <p style="font-size:0.9em;color:#666;">Queue: #<?php echo $queue_id; ?> - <?php echo e($queue_name); ?></p>
        <?php endif; ?>
        <div id="rx-note" class="rx-note" style="display:none;">
            <small>Prescription required. <input type="text" id="rx-ref" placeholder="Prescription ref no."> <a href="../prescriptions/add.php?target=pos" class="btn small">Upload Prescription</a></small>
        </div>
        <div style="margin-top:12px;">
            <button id="checkout-btn" class="btn" disabled>Checkout</button>
            <a class="btn ghost" href="queue.php">Back to Queue</a>
        </div>
        <form id="checkout-form" method="post" action="checkout.php" style="display:none;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cart_json" id="cart-json">
            <input type="hidden" name="queue_id" value="<?php echo $queue_id; ?>">
            <input type="hidden" name="rx_ref" id="checkout-rx-ref">
        </form>
    </div>
</div>
<script src="../assets/js/pos.js"></script>
<script>
document.getElementById('checkout-btn').addEventListener('click', function(){
    var form=document.getElementById('checkout-form');
    document.getElementById('cart-json').value=window.cartJSON();
    document.getElementById('checkout-rx-ref').value=document.getElementById('rx-ref').value||'';
    form.submit();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
