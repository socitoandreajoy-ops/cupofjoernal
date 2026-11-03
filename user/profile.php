<?php
// user/profile.php - any logged-in user (Admin, Cashier, Customer) edits their own profile, photo and password (saved by update.php)
session_start();
include('../includes/config.php');
require_login();

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$u = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM users WHERE user_id=?", [$uid]));

$phone = ''; $points = 0; $orders = 0;
if ($u['customer_id']) {
    $row = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT phone, loyalty_points FROM customers WHERE customer_id=?", [$u['customer_id']]));
    $phone  = (string)($row['phone'] ?? '');
    $points = (int)($row['loyalty_points'] ?? 0);
    $orders = (int)(mysqli_fetch_row(mysqli_execute_query($conn, "SELECT COUNT(*) FROM orders WHERE customer_id=?", [$u['customer_id']]))[0] ?? 0);
}
$form = ['full_name' => $u['full_name'], 'email' => $u['email'], 'phone' => $phone];
$form = array_merge($form, take_old());     // what they typed if the last save failed

$hasPhoto = $u['photo'] && is_file(__DIR__ . '/../uploads/users/' . $u['photo']);
$words = preg_split('/\s+/u', trim((string)($u['full_name'] ?: $u['username'])), -1, PREG_SPLIT_NO_EMPTY);
$initials = mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
$roleLabel = ucfirst(strtolower($u['role']));
$roleNote  = ['Admin' => 'Full access to the shop', 'Cashier' => 'Takes and manages orders', 'Customer' => 'Earns loyalty points on every order'][$roleLabel] ?? '';

$title = 'My Profile – Cup of Joe-rnal';
if (in_array($role, ['Admin', 'Cashier'], true)) {
    include('../includes/adminHeader.php');          // staff: sidebar layout
} else {
    $back = home_for($role);
    include('../includes/header.php');               // customers: simple top bar
    ?>
<div class="top-bar">
  <div class="brand"><img src="<?= BASE_URL ?>/assets/logo-sm.png" alt=""><span>Cup of Joe-rnal</span></div>
  <div><a class="btn alt" href="<?= $back ?>">← Back to menu</a> <a class="btn alt" href="logout.php">Logout</a></div>
</div>
<?php } ?>

<h2>My Profile</h2>
<?php include('../includes/alert.php'); ?>

<form class="pf" method="post" action="update.php" enctype="multipart/form-data" novalidate>

  <!-- identity card -->
  <section class="pf-hero">
    <div class="pf-cover"></div>
    <div class="pf-id">
      <div class="pf-avatar">
        <div class="pf-ava-lg" id="pfAva" data-initials="<?= h($initials) ?>">
          <?php if ($hasPhoto): ?><img src="<?= BASE_URL ?>/uploads/users/<?= h($u['photo']) ?>" alt="Your photo"><?php else: ?><span><?= h($initials) ?></span><?php endif; ?>
        </div>
        <label class="pf-cam" for="photo" title="Change photo">📷<span class="sr">Change photo</span></label>
      </div>
      <div class="pf-name">
        <div class="pf-title"><?= h($u['full_name'] ?: $u['username']) ?></div>
        <div class="pf-meta"><span class="pill"><?= h($roleLabel) ?></span> <span><?= h($u['email']) ?></span></div>
        <div class="pf-note"><?= h($roleNote) ?></div>
      </div>
      <?php if ($u['customer_id']): ?>
      <div class="pf-stats">
        <div><b><?= $points ?></b><small>Loyalty points</small></div>
        <div><b><?= $orders ?></b><small>Orders</small></div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <div class="pf-grid">
    <div class="pf-col">
      <section class="panel pf-card">
        <b>Personal information</b>
        <div class="pf-fields">
          <div class="pf-field"><label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" autocomplete="name" value="<?= h($form['full_name']) ?>"></div>
          <div class="pf-field"><label for="email">Email <small>(used to log in)</small></label>
            <input type="text" id="email" name="email" maxlength="100" autocomplete="email" value="<?= h($form['email']) ?>"></div>
          <?php if ($u['customer_id']): ?>
          <div class="pf-field"><label for="phone">Contact number</label>
            <input type="text" id="phone" name="phone" maxlength="20" autocomplete="tel" placeholder="09123456789" value="<?= h($form['phone']) ?>"></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel pf-card">
        <b>Change password <small class="sub">· leave blank to keep your current one</small></b>
        <div class="pf-fields">
          <div class="pf-field"><label for="current_password">Current password</label>
            <div class="pf-pw"><input type="password" id="current_password" name="current_password" autocomplete="current-password"><button type="button" class="pf-eye" data-for="current_password">Show</button></div></div>
          <div class="pf-field"><label for="password">New password</label>
            <div class="pf-pw"><input type="password" id="password" name="password" autocomplete="new-password"><button type="button" class="pf-eye" data-for="password">Show</button></div>
            <div class="pf-meter"><i id="pfBar"></i></div><small class="sub" id="pfStrength">At least 8 characters.</small></div>
          <div class="pf-field"><label for="confirm">Confirm new password</label>
            <div class="pf-pw"><input type="password" id="confirm" name="confirm" autocomplete="new-password"><button type="button" class="pf-eye" data-for="confirm">Show</button></div>
            <small id="pfMatch" class="sub"></small></div>
        </div>
      </section>
    </div>

    <div class="pf-col">
      <section class="panel pf-card">
        <b>Profile photo</b>
        <div class="pf-photo-box">
          <p class="sub" style="margin-bottom:12px">JPG, PNG or WEBP, up to 2&nbsp;MB. Square photos look best.</p>
          <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" class="pf-file">
          <label for="photo" class="btn alt pf-pick">Choose a new photo</label>
          <span class="sub" id="pfFileName">No file chosen</span>
          <?php if ($hasPhoto): ?>
          <label class="pf-remove"><input type="checkbox" name="remove_photo" value="1" id="rmPhoto"> Remove my current photo</label>
          <?php endif; ?>
          <p class="pf-err" id="pfPhotoErr" role="alert" hidden></p>
        </div>
      </section>

      <section class="panel pf-card pf-tip">
        <b>Good to know</b>
        <ul>
          <li>Your email is what you use to log in<?= $u['customer_id'] ? ' and where order receipts are sent' : '' ?>.</li>
          <li>Changing your password signs you out of other browsers that were set to stay logged in.</li>
        </ul>
      </section>
    </div>
  </div>

  <div class="pf-save">
    <a class="btn alt" href="<?= h(home_for($role)) ?>">Cancel</a>
    <button type="submit">Save changes</button>
  </div>
</form>

<script>
(function () {
  // ---- show / hide password ----
  document.querySelectorAll('.pf-eye').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = document.getElementById(b.dataset.for), show = i.type === 'password';
      i.type = show ? 'text' : 'password'; b.textContent = show ? 'Hide' : 'Show';
    });
  });

  // ---- password strength + match ----
  var pw = document.getElementById('password'), cf = document.getElementById('confirm');
  function strength() {
    var v = pw.value, s = 0;
    if (v.length >= 8) s++; if (v.length >= 12) s++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
    var bar = document.getElementById('pfBar'), txt = document.getElementById('pfStrength');
    if (!v) { bar.style.width = '0'; txt.textContent = 'At least 8 characters.'; return; }
    if (v.length < 8) { s = 0; txt.textContent = 'Too short - at least 8 characters.'; }
    else txt.textContent = ['', 'Weak', 'Okay', 'Good', 'Strong', 'Very strong'][Math.max(s, 1)];
    bar.style.width = Math.max(s, 1) * 20 + '%';
    bar.style.background = s <= 1 ? '#c4161c' : (s <= 3 ? '#d98a00' : '#2d6a3e');
  }
  function match() {
    var m = document.getElementById('pfMatch');
    if (!cf.value) { m.textContent = ''; return; }
    m.textContent = cf.value === pw.value ? '✓ Passwords match' : 'Passwords do not match yet';
    m.style.color = cf.value === pw.value ? '#2d6a3e' : '#c4161c';
  }
  pw.addEventListener('input', function () { strength(); match(); });
  cf.addEventListener('input', match);

  // ---- photo preview ----
  var file = document.getElementById('photo'), ava = document.getElementById('pfAva'),
      err = document.getElementById('pfPhotoErr'), rm = document.getElementById('rmPhoto'),
      original = ava.innerHTML;
  function showInitials() { ava.innerHTML = ''; var s = document.createElement('span'); s.textContent = ava.dataset.initials; ava.appendChild(s); }
  file.addEventListener('change', function () {
    err.hidden = true;
    var f = file.files[0];
    document.getElementById('pfFileName').textContent = f ? f.name : 'No file chosen';
    if (!f) { ava.innerHTML = original; return; }
    if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { err.textContent = 'Photo must be a JPG, PNG or WEBP image.'; err.hidden = false; file.value = ''; ava.innerHTML = original; return; }
    if (f.size > 2 * 1024 * 1024) { err.textContent = 'Photo must be 2MB or smaller.'; err.hidden = false; file.value = ''; ava.innerHTML = original; return; }
    var r = new FileReader();
    r.onload = function (e) { ava.innerHTML = ''; var im = new Image(); im.src = e.target.result; im.alt = 'New photo preview'; ava.appendChild(im); };
    r.readAsDataURL(f);
    if (rm) rm.checked = false;
  });
  if (rm) rm.addEventListener('change', function () {
    if (rm.checked) { showInitials(); file.value = ''; document.getElementById('pfFileName').textContent = 'No file chosen'; }
    else ava.innerHTML = original;
  });
})();
</script>
<?php include('../includes/footer.php'); ?>
