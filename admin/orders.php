<?php
session_start();
include('../includes/config.php');
include('../includes/adminHeader.php');   // login + role check + sidebar

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

$src   = $_GET['src'] ?? 'all';
$where = $src === 'self'  ? 'WHERE o.employee_id IS NULL AND o.placed_by_name IS NULL'
       : ($src === 'staff' ? 'WHERE (o.employee_id IS NOT NULL OR o.placed_by_name IS NOT NULL)' : '');

// search: order #, customer, items, order type, address, phone, date, who placed it
$q = trim($_GET['q'] ?? '');
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where .= ($where === '' ? 'WHERE ' : ' AND ') . "(CAST(o.order_id AS CHAR) LIKE ? OR c.customer_name LIKE ? OR o.order_type LIKE ? OR o.delivery_address LIKE ? OR o.contact_phone LIKE ?
        OR DATE_FORMAT(o.order_date, '%Y-%m-%d %h:%i %p %b %e') LIKE ? OR COALESCE(e.employee_name, o.placed_by_name, 'Self-order') LIKE ?
        OR EXISTS (SELECT 1 FROM order_items oi2 JOIN products p2 ON p2.product_id = oi2.product_id WHERE oi2.order_id = o.order_id AND p2.product_name LIKE ?))";
    $params = array_fill(0, 8, $like);
}
$qs = $q !== '' ? '&q=' . urlencode($q) : '';

$res = mysqli_execute_query($conn, "SELECT o.order_id, o.order_date, o.order_type, o.delivery_address, o.contact_phone, o.total_amount,
        c.customer_name,
        COALESCE(e.employee_name, o.placed_by_name, 'Self-order') AS placed_via,        (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_name) SEPARATOR ', ')
           FROM order_items oi JOIN products p ON p.product_id = oi.product_id
          WHERE oi.order_id = o.order_id) AS items
    FROM orders o
    JOIN customers c ON c.customer_id = o.customer_id
    LEFT JOIN employees e ON e.employee_id = o.employee_id
    $where
    ORDER BY o.order_date DESC, o.order_id DESC
    LIMIT 100", $params);
?>
<?php if ($q === ''): ?><meta http-equiv="refresh" content="30"><!-- auto-refresh every 30s so new orders show up (paused while searching) --><?php endif; ?>

<?php include('../includes/alert.php'); ?>
<div class="sub" style="margin-bottom:6px;">Incoming &amp; recent orders</div>
<h2>Customer Orders</h2>

<form method="get" class="tsearch">
  <input type="hidden" name="src" value="<?= $e($src) ?>">
  <input type="search" name="q" value="<?= $e($q) ?>" placeholder="🔍 Search order #, customer, item, type, address, date…" autocomplete="off">
  <button type="submit">Search</button>
  <?php if ($q !== ''): ?><a class="btn alt" href="orders.php?src=<?= $e($src) ?>">Clear</a><?php endif; ?>
</form>

<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
  <a class="btn <?= $src === 'all'   ? '' : 'alt' ?> sm" href="orders.php?src=all<?= $e($qs) ?>">All</a>
  <a class="btn <?= $src === 'self'  ? '' : 'alt' ?> sm" href="orders.php?src=self<?= $e($qs) ?>">Customer self-orders</a>
  <a class="btn <?= $src === 'staff' ? '' : 'alt' ?> sm" href="orders.php?src=staff<?= $e($qs) ?>">Cashier orders</a>
</div>

<div class="panel">
  <b>🧾 <?= $q !== '' ? 'Search results (up to 100)' : 'Latest 100 orders' ?></b>
  <table>
    <thead><tr><th>Order</th><th>Date &amp; Time</th><th>Customer</th><th>Type</th><th>Order</th><th>Placed Via</th><th>Total</th><th>Slip</th></tr></thead>
    <tbody>
    <?php if (mysqli_num_rows($res) > 0): while ($r = mysqli_fetch_assoc($res)): ?>
      <tr>
        <td>#<?= (int)$r['order_id'] ?></td>
        <td><?= $e(date('M j, Y h:i A', strtotime($r['order_date']))) ?></td>
        <td><?= $e($r['customer_name']) ?></td>
        <td><span class="tag-type <?= $r['order_type'] === 'Deliver' ? 'deliver' : '' ?>"><?= $e($r['order_type']) ?></span>
          <?php if ($r['order_type'] === 'Deliver'): ?><br><small><?= $e($r['delivery_address']) ?><?= $r['contact_phone'] ? ' · ' . $e($r['contact_phone']) : '' ?></small><?php endif; ?></td>
        <td><?= $e($r['items']) ?></td>
        <td><?= $r['placed_via'] === 'Self-order' ? '<span class="pill">Self-order</span>' : $e($r['placed_via']) ?></td>
        <td>₱<?= number_format($r['total_amount'], 2) ?></td>
        <td><a class="btn sm alt" href="../order/slip.php?id=<?= (int)$r['order_id'] ?>" target="_blank">📄 Slip</a></td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="8" class="empty"><?= $q !== '' ? 'No orders match your search.' : 'No orders yet.' ?></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include('../includes/footer.php'); ?>