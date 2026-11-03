<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
include('../includes/adminHeader.php');

$editId = (int)($_GET['edit'] ?? 0);
$search = trim($_GET['q'] ?? '');

// Load the customer into the form when clicking Edit (or what was typed if the save failed)
$editing = null;
if ($editId) {
    $q = mysqli_execute_query($conn, "SELECT * FROM customers WHERE customer_id=?", [$editId]);
    $editing = mysqli_fetch_assoc($q);
}
$form = $editing ?: ['customer_name' => '', 'email' => '', 'phone' => '', 'loyalty_points' => '0'];
$form = array_merge($form, take_old());

$like = '%' . $search . '%';
$list = mysqli_execute_query($conn, "SELECT * FROM customers WHERE customer_name LIKE ? OR email LIKE ? ORDER BY customer_id DESC", [$like, $like]);
?>
<h2>Customers</h2>
<?php include('../includes/alert.php'); ?>

<div class="panel"><b><?= $editing ? 'Edit customer' : 'Add new customer' ?></b>
<form class="f" method="post" action="<?= $editing ? 'update.php' : 'store.php' ?>" novalidate>
<input type="hidden" name="id" value="<?= $editing ? (int)$editing['customer_id'] : 0 ?>">
<div><label>Full name</label><input type="text" name="customer_name" value="<?= h($form['customer_name']) ?>"></div>
<div><label>Email</label><input type="text" name="email" value="<?= h($form['email'] ?? '') ?>"></div>
<div><label>Phone</label><input type="text" name="phone" value="<?= h($form['phone'] ?? '') ?>" placeholder="09123456789"></div>
<div><label>Loyalty points</label><input type="text" name="loyalty_points" value="<?= h($form['loyalty_points']) ?>"></div>
<div style="align-self:end"><button type="submit"><?= $editing ? 'Save changes' : 'Add customer' ?></button>
<?php if ($editing): ?> <a class="btn alt" href="index.php">Cancel</a><?php endif; ?></div>
</form>
</div>

<div class="panel">
<form method="get" style="display:flex;gap:10px;margin-bottom:14px"><input type="text" name="q" value="<?= h($search) ?>" placeholder="Search name or email"><button>Search</button></form>
<table><tr><th>ID</th><th>Customer</th><th>Email</th><th>Phone</th><th>Points</th><th>Actions</th></tr>
<?php while ($r = mysqli_fetch_assoc($list)): ?><tr>
<td><?= (int)$r['customer_id'] ?></td>
<td><?= h($r['customer_name']) ?><?= strpos($r['customer_name'], 'Guest ') === 0 ? ' <span class="pill">Guest</span>' : '' ?></td>
<td><?= h($r['email'] ?? '') ?></td><td><?= h($r['phone'] ?? '') ?></td><td><span class="pill"><?= (int)$r['loyalty_points'] ?> pts</span></td>
<td style="white-space:nowrap"><a class="btn sm alt" href="index.php?edit=<?= (int)$r['customer_id'] ?>">Edit</a>
<form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this customer?')"><input type="hidden" name="id" value="<?= (int)$r['customer_id'] ?>"><button class="btn sm del">Delete</button></form></td>
</tr><?php endwhile; ?></table></div>
<?php include('../includes/footer.php'); ?>
