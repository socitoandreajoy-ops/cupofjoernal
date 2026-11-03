<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
include('../includes/adminHeader.php');

$roles  = ['Barista', 'Cashier', 'Manager'];
$editId = (int)($_GET['edit'] ?? 0);
$form   = ['employee_name' => '', 'role' => '', 'username' => ''];

// Load employee into the form when clicking Edit
if ($editId > 0) {
    $row = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT employee_name, role, username FROM employees WHERE employee_id = ?", [$editId]));
    if ($row) {
        $form = ['employee_name' => $row['employee_name'], 'role' => $row['role'], 'username' => (string)$row['username']];
    } else {
        $editId = 0;
    }
}
$form = array_merge($form, take_old());     // what was typed if the save failed

$list = mysqli_query($conn, "SELECT employee_id, employee_name, role, username,
                                    (password IS NOT NULL AND password <> '') AS has_pw
                             FROM employees ORDER BY employee_id");
?>
<h2>Employees Management</h2>
<?php include('../includes/alert.php'); ?>

<div class="panel"><b><?= $editId > 0 ? 'Edit employee' : 'Add new employee' ?></b>
<form class="f" method="post" action="<?= $editId > 0 ? 'update.php' : 'store.php' ?>" autocomplete="off" novalidate>
  <input type="hidden" name="employee_id" value="<?= $editId ?>">

  <div><label>Full name</label>
    <input type="text" name="employee_name" value="<?= h($form['employee_name']) ?>" placeholder="e.g. Cardo Dalisay"></div>

  <div><label>Role</label>
    <select name="role">
      <option value="">-- Select role --</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r ?>" <?= $form['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select></div>

  <div><label>Login username <small>(cashiers)</small></label>
    <input type="text" name="username" value="<?= h($form['username']) ?>" placeholder="e.g. cardo" autocomplete="off"></div>

  <div><label>Password <small>(cashiers)</small></label>
    <input type="password" name="password" autocomplete="new-password"
           placeholder="<?= $editId > 0 ? 'Leave blank to keep current' : 'At least 6 characters' ?>"></div>

  <div class="sub" style="grid-column:1/-1">Only employees with the <b>Cashier</b> role can log in. They sign in on the login page by choosing "Cashier".</div>

  <div style="align-self:end">
    <button type="submit"><?= $editId > 0 ? 'Save changes' : 'Add employee' ?></button>
    <?php if ($editId > 0): ?> <a class="btn alt" href="index.php">Cancel</a><?php endif; ?>
  </div>
</form>
</div>

<div class="panel">
  <?php search_box('#empTbl', 'Search employees by name, role or username…'); ?>
  <table id="empTbl">
    <thead><tr><th>ID</th><th>Employee Name</th><th>Role</th><th>Username</th><th>Login</th><th>Action</th></tr></thead>
    <tbody>
    <?php while ($r = mysqli_fetch_assoc($list)):
        $canLogin = $r['role'] === 'Cashier' && !empty($r['username']) && (int)$r['has_pw'] === 1; ?>
      <tr>
        <td><?= (int)$r['employee_id'] ?></td>
        <td><?= h($r['employee_name']) ?></td>
        <td><span class="pill"><?= h($r['role']) ?></span></td>
        <td><?= h($r['username'] ?? '') ?></td>
        <td><?= $canLogin ? '<span class="pill">✅ Can log in</span>' : '<span class="sub">-</span>' ?></td>
        <td style="white-space:nowrap">
          <a class="btn sm alt" href="index.php?edit=<?= (int)$r['employee_id'] ?>">Edit</a>
          <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this employee?')">
            <input type="hidden" name="employee_id" value="<?= (int)$r['employee_id'] ?>">
            <button class="btn sm del" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php include('../includes/footer.php'); ?>
