<?php
// order/create.php - staff (Admin / Cashier) place a new order for a customer; saved by order/store.php
session_start();
include('../includes/config.php');
require_role(['Admin', 'Cashier']);
include('../includes/adminHeader.php');

$e   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$old  = take_old();                       // what was typed if the last order failed
$mode = $old['cust_mode'] ?? 'existing';

// Fetch Customers and Products
$customers = mysqli_query($conn, "SELECT customer_id, customer_name FROM customers ORDER BY customer_name");
$prods = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id = p.category_id ORDER BY c.category_name, p.product_name");
?>

<h2>Place New Order</h2>

<?php include('../includes/alert.php'); ?>

<form method="post" action="store.php" class="pos" novalidate>
  <section>
    <?php include '../includes/menu_items.php'; ?>
  </section>

  <aside class="panel bill">
    <h3>Customer & Order</h3>
    
    <div class="cust-switch">
      <button type="button" id="tabExisting" class="active" onclick="custMode('existing')">Existing customer</button>
      <button type="button" id="tabNew" onclick="custMode('new')">＋ New customer</button>
      <button type="button" id="tabGuest" onclick="custMode('guest')">👤 Guest</button>
    </div>
    <input type="hidden" name="cust_mode" id="cust_mode" value="<?= $e(in_array($mode, ['new', 'guest'], true) ? $mode : 'existing') ?>">

    <div id="boxExisting">
      <label class="lbl">Customer</label>
      <select name="customer_id">
        <option value="">-- Select Customer --</option>
        <?php while ($c = mysqli_fetch_assoc($customers)): ?>
          <option value="<?= (int)$c['customer_id'] ?>" <?= ((int)($old['customer_id'] ?? 0) === (int)$c['customer_id']) ? 'selected' : '' ?>><?= $e($c['customer_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div id="boxNew" style="display:none">
      <label class="lbl">Full name *</label>
      <input type="text" name="new_name" value="<?= $e($old['new_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz">
      <label class="lbl">Phone <small>(optional)</small></label>
      <input type="text" name="new_phone" value="<?= $e($old['new_phone'] ?? '') ?>" placeholder="09123456789">
      <label class="lbl">Email <small>(optional)</small></label>
      <input type="text" name="new_email" value="<?= $e($old['new_email'] ?? '') ?>" placeholder="name@email.com">
      <div class="sub" style="margin-top:8px">They'll be saved in Customers when you place the order.</div>
    </div>

    <div id="boxGuest" style="display:none">
      <div class="sub">Walk-in without an account. A Guest ID is created automatically:</div>
      <div style="font-weight:700;font-size:1.05rem;margin:6px 0 10px;">Guest <span id="guestPreview">—</span></div>
      <label class="lbl">Email <small>(optional - to send the order slip)</small></label>
      <input type="text" name="guest_email" value="<?= $e($old['guest_email'] ?? '') ?>" placeholder="name@email.com">
    </div>

    <?php include('../includes/order_type_box.php'); ?>

    <h4 style="margin:16px 0 8px;">Order Summary</h4>
    <div id="lines"><div class="empty">No items selected</div></div>
    
    <div class="tot" style="display:flex;justify-content:space-between;font-weight:bold;font-size:1.15rem;margin:16px 0;">
      <span>Total</span><span id="total">₱0.00</span>
    </div>
    
    <button class="btn" style="width:100%;" type="submit" name="place_order">Place Order</button>
  </aside>
</form>

<script>
const peso = n => '₱' + n.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

function bump(id, d) {
    const i = document.getElementById('q' + id);
    i.value = Math.max(0, Math.min(+i.max, (+i.value || 0) + d));
    render();
}

function render() {
    let html = '', total = 0;
    document.querySelectorAll('.step input').forEach(i => {
        const q = +i.value || 0;
        if (q < 1) return;
        const sub = q * +i.dataset.price;
        total += sub;
        
        const nameText = document.createTextNode(i.dataset.name);
        const div = document.createElement('div');
        div.appendChild(nameText);
        
        html += `<div class="row" style="padding:6px 0;font-size:0.86rem;">
                    <div>${div.innerHTML}<br><small>x ${q} · ${peso(+i.dataset.price)}</small></div>
                    <b>${peso(sub)}</b>
                 </div>`;
    });
    document.getElementById('lines').innerHTML = html || '<div class="empty">No items selected</div>';
    document.getElementById('total').textContent = peso(total);
    if (window.markSelected) markSelected();
}

function custMode(m) {
    document.getElementById('cust_mode').value = m;
    document.getElementById('boxExisting').style.display = m === 'existing' ? '' : 'none';
    document.getElementById('boxNew').style.display      = m === 'new' ? '' : 'none';
    document.getElementById('boxGuest').style.display    = m === 'guest' ? '' : 'none';
    document.getElementById('tabExisting').classList.toggle('active', m === 'existing');
    document.getElementById('tabNew').classList.toggle('active', m === 'new');
    document.getElementById('tabGuest').classList.toggle('active', m === 'guest');
}
// preview of the Guest ID: yyyyddmm-HHmmss (the server makes the final one when the order is placed)
(function () {
    const p = n => String(n).padStart(2, '0'), d = new Date();
    document.getElementById('guestPreview').textContent = d.getFullYear() + p(d.getDate()) + p(d.getMonth() + 1) + '-' + p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds());
})();
custMode(document.getElementById('cust_mode').value);

render();
</script>
<?php include('../includes/footer.php'); ?>