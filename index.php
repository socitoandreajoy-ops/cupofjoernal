<?php
// index.php - the customer's page: menu, "Your order" bill, recent orders (placing the order is handled by checkout.php)
session_start();
include('includes/config.php');

// this is the customers' page: visitors who are not logged in go to the login page, staff go to their own page
if (empty($_SESSION['user_id'])) redirect(url('/user/login.php'));
if (($_SESSION['role'] ?? '') !== 'Customer') redirect(home_for($_SESSION['role'] ?? ''));
require_role(['Customer']);

$cid = (int)($_SESSION['customer_id'] ?? 0);

// ---- Order slip pop-up for the order that was just placed (set by checkout.php) ----
$new_order_data = null;
if (!empty($_SESSION['new_order_id'])) {
    $oid = (int)$_SESSION['new_order_id'];
    unset($_SESSION['new_order_id']);
    $o = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT order_id, order_date, order_type, delivery_address, total_amount FROM orders WHERE order_id=? AND customer_id=?", [$oid, $cid]));
    if ($o) {
        $its = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT oi.quantity, oi.unit_price, p.product_name FROM order_items oi
                                JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=?", [$oid]), MYSQLI_ASSOC);
        $new_order_data = [
            'order_id' => $o['order_id'],
            'date'     => $o['order_date'],
            'type'     => $o['order_type'],
            'address'  => $o['delivery_address'],
            'customer' => $_SESSION['username'] ?? 'Customer',
            'items'    => array_map(fn($i) => ['name' => $i['product_name'], 'qty' => $i['quantity'], 'subtotal' => $i['quantity'] * $i['unit_price']], $its),
            'total'    => $o['total_amount'],
        ];
    }
}

// ---- Fetch Data ----
$points = 0;
if ($cid) {
    $res = mysqli_execute_query($conn, "SELECT loyalty_points FROM customers WHERE customer_id=?", [$cid]);
    $points = (int)(mysqli_fetch_row($res)[0] ?? 0);
}

$prods = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id = p.category_id ORDER BY c.category_name, p.product_name");

$history = mysqli_execute_query($conn, "SELECT o.order_id, o.order_date, o.order_type, o.total_amount,
        (SELECT GROUP_CONCAT(CONCAT(oi.quantity,' x ',p.product_name) SEPARATOR ', ')
           FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=o.order_id) items
        FROM orders o WHERE o.customer_id=? ORDER BY o.order_id DESC LIMIT 10", [$cid]);

// latest customer reviews for the "What our customers are saying" section (bad words are masked when shown)
$rvStat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n, AVG(rating) a FROM reviews"));
$latestReviews = mysqli_fetch_all(mysqli_query($conn, "SELECT r.rating, r.comment, r.product_id, COALESCE(r.updated_at, r.created_at) reviewed_at,
        c.customer_name, p.product_name, u.photo
        FROM reviews r
        JOIN customers c ON c.customer_id = r.customer_id
        JOIN products p  ON p.product_id  = r.product_id
        LEFT JOIN users u ON u.customer_id = c.customer_id
        ORDER BY reviewed_at DESC LIMIT 6"), MYSQLI_ASSOC);
$starStr = fn($n) => str_repeat('★', (int)$n) . str_repeat('☆', 5 - (int)$n);

$title = 'Order – Cup of Joe-rnal';
$head_extra = <<<'CSS'
<style>
    .top{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:22px}
    .top .brand{padding:0;font-size:1.3rem;font-weight:700;color:var(--orange)}
    .who{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    
    .bill{position:sticky;top:20px}
    .line{display:flex;justify-content:space-between;gap:8px;padding:8px 0;font-size:.86rem;border-bottom:1px solid var(--line)}
    .line small{color:var(--muted)}
    .tot{display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;padding:12px 0}
    .bill button.go{width:100%;padding:14px;font-size:1rem;cursor:pointer}
    .empty{color:var(--muted);font-size:.85rem;padding:14px 0;text-align:center}

    /* Modal Styling */
    .modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
    .slip-card { background: #fff; width: 100%; max-width: 380px; border-radius: 20px; padding: 24px; font-family: 'Courier New', Courier, monospace; color: #333; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .slip-header { text-align: center; border-bottom: 2px dashed #aaa; padding-bottom: 12px; margin-bottom: 12px; }
    .slip-header h3 { margin: 0; font-family: 'Poppins', sans-serif; font-weight: 700; color: #4a2c2a; }
    .slip-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; }
    .slip-items { border-bottom: 2px dashed #aaa; padding-bottom: 12px; margin-bottom: 12px; }
    .slip-total { font-weight: bold; font-size: 1.1rem; display: flex; justify-content: space-between; margin-bottom: 16px; }
    .slip-actions { display: flex; gap: 10px; }
    .slip-actions button, .slip-actions a { flex: 1; text-align: center; font-family: 'Poppins', sans-serif; text-decoration: none; }

    @media print {
        body * { visibility: hidden; }
        .slip-card, .slip-card * { visibility: visible; }
        .slip-card { position: absolute; left: 0; top: 0; width: 100%; max-width: 100%; box-shadow: none; }
        .slip-actions { display: none !important; }
    }
    </style>
CSS;
include('includes/header.php');
?>

<div class="top">
  <div class="brand"><img src="<?= BASE_URL ?>/assets/logo-sm.png" alt="Cup of Joe-rnal logo"><span>Cup of Joe-rnal</span></div>
  <div class="who">
    <?= avatar_html($_SESSION['photo'] ?? null, 32) ?><span>Hi, <b><?= h($_SESSION['username'] ?? 'there') ?></b></span>
    <span class="pill">⭐ <?= $points ?> loyalty pts</span>
    <a class="btn alt" href="user/profile.php">My Profile</a>
    <a class="btn alt" href="user/logout.php">Logout</a>
  </div>
</div>

<h2>What would you like today?</h2>
<?php include('includes/alert.php'); ?>

<form method="post" action="checkout.php" class="pos" novalidate>
  <section>
    <?php include 'includes/menu_items.php'; ?>
  </section>

  <aside class="panel bill">
    <h3>Your order</h3>
    <div id="lines"><div class="empty">No items yet</div></div>
    <?php $old = []; include('includes/order_type_box.php'); ?>
    <div class="tot"><span>Total</span><span id="total">₱0.00</span></div>
    <button class="go" type="submit" name="place_order">Place Order</button>
  </aside>
</form>

<div class="panel" id="customer-reviews" style="margin-top:24px">
  <div class="rv-head">
    <h3>What our customers are saying</h3>
    <?php if ((int)$rvStat['n'] > 0): ?>
      <div class="rv-avg"><span class="stars"><?= $starStr(round((float)$rvStat['a'])) ?></span> <b><?= number_format((float)$rvStat['a'], 1) ?></b> from <?= (int)$rvStat['n'] ?> review<?= (int)$rvStat['n'] === 1 ? '' : 's' ?></div>
    <?php endif; ?>
  </div>
  <?php if ($latestReviews): ?>
  <div class="rv-grid">
    <?php foreach ($latestReviews as $rv):
        $txt = mask_bad_words((string)$rv['comment']);
        $txt = mb_strlen($txt) > 150 ? rtrim(mb_substr($txt, 0, 150)) . '…' : $txt; ?>
      <div class="rv-card">
        <span class="stars" aria-label="<?= (int)$rv['rating'] ?> out of 5 stars"><?= $starStr($rv['rating']) ?></span>
        <p class="rv-text">“<?= h($txt) ?>”</p>
        <div class="rv-by">
          <?= avatar_html($rv['photo'], 34) ?>
          <div><b><?= h($rv['customer_name']) ?></b>
            <a href="<?= BASE_URL ?>/product/show.php?id=<?= (int)$rv['product_id'] ?>#reviews">on <?= h($rv['product_name']) ?> · <?= h(date('M j, Y', strtotime($rv['reviewed_at']))) ?></a></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="sub">No reviews yet. Order something, then tap it on the menu to be the first to leave one!</p>
  <?php endif; ?>
</div>

<div class="panel">
  <b>My recent orders</b>
  <table>
    <tr><th>Order</th><th>Date</th><th>Type</th><th>Items</th><th>Total</th><th>Slip</th></tr>
    <?php $n = 0; while ($hrow = mysqli_fetch_assoc($history)): $n++; ?>
      <tr>
        <td>#<?= (int)$hrow['order_id'] ?></td>
        <td><?= h($hrow['order_date']) ?></td>
        <td><span class="tag-type <?= $hrow['order_type'] === 'Deliver' ? 'deliver' : '' ?>"><?= h($hrow['order_type']) ?></span></td>
        <td><?= h($hrow['items']) ?></td>
        <td>₱<?= number_format($hrow['total_amount'], 2) ?></td>
        <td>
          <a class="btn sm alt" href="order/slip.php?id=<?= (int)$hrow['order_id'] ?>" target="_blank">📄 Print Slip</a>
        </td>
      </tr>
    <?php endwhile; if (!$n): ?>
      <tr><td colspan="6" class="empty">No orders yet — pick something above!</td></tr>
    <?php endif; ?>
  </table>
</div>

<!-- Order Slip Modal -->
<?php if ($new_order_data): ?>
<div class="modal-bg" id="slipModal">
  <div class="slip-card">
    <div class="slip-header">
      <img class="slip-logo" src="<?= BASE_URL ?>/assets/logo-sm.png" alt="">
      <h3>Cup of Joe-rnal</h3>
      <p style="margin:4px 0;font-size:0.75rem;">ORDER SLIP - UNPAID</p>
      <p style="margin:0;font-size:0.75rem;">Show this slip to the cashier to pay</p>
    </div>
    
    <div class="slip-row"><span>Order ID:</span><b>#<?= $new_order_data['order_id'] ?></b></div>
    <div class="slip-row"><span>Date:</span><span><?= $new_order_data['date'] ?></span></div>
    <div class="slip-row"><span>Customer:</span><span><?= h($new_order_data['customer']) ?></span></div>
    <div class="slip-row"><span>Order type:</span><b><?= h(strtoupper($new_order_data['type'])) ?></b></div>
    <?php if ($new_order_data['type'] === 'Deliver'): ?><div class="slip-row"><span>Deliver to:</span><span style="text-align:right;max-width:200px"><?= h($new_order_data['address']) ?></span></div><?php endif; ?>
    <br>
    
    <div class="slip-items">
      <?php foreach ($new_order_data['items'] as $it): ?>
        <div class="slip-row">
          <span><?= $it['qty'] ?>x <?= h($it['name']) ?></span>
          <span>₱<?= number_format($it['subtotal'], 2) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="slip-total">
      <span>TOTAL DUE:</span>
      <span>₱<?= number_format($new_order_data['total'], 2) ?></span>
    </div>

    <div class="slip-actions">
      <button class="btn" onclick="window.print()">🖨️ Print</button>
      <button class="btn alt" onclick="document.getElementById('slipModal').remove()">Close</button>
    </div>
  </div>
</div>
<?php endif; ?>


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
        
        html += `<div class="line"><div>${div.innerHTML}<br><small>x ${q} · ${peso(+i.dataset.price)}</small></div><b>${peso(sub)}</b></div>`;
    });
    document.getElementById('lines').innerHTML = html || '<div class="empty">No items yet</div>';
    document.getElementById('total').textContent = peso(total);
    if (window.markSelected) markSelected();
}

render();
</script>
<?php include('includes/footer.php'); ?>
