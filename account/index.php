<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
include('../includes/adminHeader.php');
include('helpers.php');

$roles  = ['admin', 'cashier', 'customer'];
$me     = (int)$_SESSION['user_id'];
$editId = (int)($_GET['edit'] ?? 0);
$search = trim($_GET['q'] ?? '');

$editing = $editId ? get_user($conn, $editId) : null;
$form    = $editing ?: ['full_name' => '', 'email' => '', 'role' => 'customer'];
$form    = array_merge($form, take_old());     // what was typed if the save failed

$like = '%' . $search . '%';
$list = mysqli_execute_query($conn, "SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY is_active DESC, user_id", [$like, $like]);
?>
<h2>User Accounts</h2>
<?php include('../includes/alert.php'); ?>

<div class="panel"><b><?= $editing ? 'Edit user' : 'Add new user' ?></b>
<form class="f" method="post" action="<?= $editing ? 'update.php' : 'store.php' ?>" enctype="multipart/form-data" novalidate>
<input type="hidden" name="id" value="<?= $editing ? (int)$editing['user_id'] : 0 ?>">
<div><label>Full name</label><input type="text" name="full_name" value="<?= h($form['full_name'] ?? '') ?>"></div>
<div><label>Email (used to log in)</label><input type="text" name="email" value="<?= h($form['email'] ?? '') ?>"></div>
<div><label>Role</label><select name="role"><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= ($form['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
<div><label><?= $editing ? 'New password (leave blank to keep)' : 'Password' ?></label><input type="password" name="password" autocomplete="new-password"></div>
<div><label>Photo (JPG, PNG or WEBP, max 2MB)</label><input type="file" name="photo"></div>
<div style="align-self:end"><button type="submit"><?= $editing ? 'Save changes' : 'Add user' ?></button>
<?php if ($editing): ?> <a class="btn alt" href="index.php">Cancel</a><?php endif; ?></div>
</form>
<?php if ($editing && $editing['photo']): ?><div style="margin-top:10px"><?= avatar_html($editing['photo'], 64) ?></div><?php endif; ?>
</div>

<div class="panel">
<form method="get" style="display:flex;gap:10px;margin-bottom:14px"><input type="text" name="q" value="<?= h($search) ?>" placeholder="Search name or email"><button>Search</button></form>
<table><tr><th>Photo</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
<?php while ($r = mysqli_fetch_assoc($list)): $active = (int)$r['is_active'] === 1; $self = (int)$r['user_id'] === $me; ?><tr>
<td><?= avatar_html($r['photo'], 36) ?></td>
<td><?= h($r['full_name'] ?: $r['username']) ?><?= $self ? ' <span class="pill">you</span>' : '' ?></td>
<td><?= h($r['email']) ?></td>
<td>
  <?php if ($active && !$self): ?>
  <form method="post" action="role.php" style="display:flex;gap:6px"><input type="hidden" name="id" value="<?= (int)$r['user_id'] ?>">
    <select name="role" style="width:auto"><?php foreach ($roles as $ro): ?><option value="<?= $ro ?>" <?= $r['role'] === $ro ? 'selected' : '' ?>><?= ucfirst($ro) ?></option><?php endforeach; ?></select>
    <button class="btn sm alt">Update</button></form>
  <?php else: ?><span class="pill"><?= h(ucfirst($r['role'])) ?></span><?php endif; ?>
</td>
<td><span class="pill"><?= $active ? 'Active' : 'Deactivated' ?></span></td>
<td style="white-space:nowrap"><a class="btn sm alt" href="index.php?edit=<?= (int)$r['user_id'] ?>">Edit</a>
<?php if (!$self): ?>
<form method="post" action="toggle.php" style="display:inline" onsubmit="return confirm('<?= $active ? 'Deactivate' : 'Activate' ?> this user?')"><input type="hidden" name="id" value="<?= (int)$r['user_id'] ?>"><button class="btn sm alt"><?= $active ? 'Deactivate' : 'Activate' ?></button></form>
<form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this user permanently?')"><input type="hidden" name="id" value="<?= (int)$r['user_id'] ?>"><button class="btn sm del">Delete</button></form>
<?php endif; ?></td>
</tr><?php endwhile; ?></table></div>
<?php include('../includes/footer.php'); ?>
