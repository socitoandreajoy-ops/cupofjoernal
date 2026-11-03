<?php
// user/reset.php - the page the emailed link opens: choose a new password
session_start();
include('../includes/config.php');

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) redirect(home_for($_SESSION['role']));

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;
if (strlen($token) === 64 && ctype_xdigit($token)) {
    try {
        $q = mysqli_execute_query($conn, "SELECT r.reset_id, r.user_id, u.email, u.username, u.full_name
                                          FROM password_resets r JOIN users u ON u.user_id = r.user_id
                                          WHERE r.token_hash = ? AND r.expires_at > NOW() AND u.is_active = 1", [hash('sha256', $token)]);
        $reset = mysqli_fetch_assoc($q) ?: null;
    } catch (Throwable $ex) { $reset = null; }
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['confirm'] ?? '';
    $errors = [];
    if (strlen($pw) < 8)  $errors[] = 'Password must be at least 8 characters.';
    elseif ($pw !== $pw2) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $uid = (int)$reset['user_id'];
        mysqli_execute_query($conn, "UPDATE users SET password=? WHERE user_id=?", [password_hash($pw, PASSWORD_DEFAULT), $uid]);
        mysqli_execute_query($conn, "DELETE FROM password_resets WHERE user_id=?", [$uid]);     // the link only works once
        remember_forget($uid);                                                                  // sign out every "kept logged in" browser
        mail_password_changed($reset['email'], $reset['full_name'] ?: $reset['username']);
        flash('ok', 'Your password was changed. Please log in with the new one.');
        redirect('login.php');
    }
    $_SESSION['errors'] = $errors;
}

$title = 'Reset password – Cup of Joe-rnal';
include('../includes/authHeader.php');
?>
<div class="auth">

    <div class="hero">
        <img src="<?= BASE_URL ?>/assets/logo.png" alt="Cup of Joe-rnal coffee shop logo">
        <h1>Fresh start</h1>
        <p>Pick a new password and get back to brewing.</p>
    </div>

    <main class="card" id="main">
        <h2 id="t">Choose a new password</h2>

        <?php $alert_auth = true; include('../includes/alert.php'); ?>

        <?php if (!$reset): ?>
            <div class="msg err" role="alert">This reset link is invalid or has expired. Please request a new one.</div>
            <p class="back-link"><a href="forgot.php">Send me a new link</a></p>
        <?php else: ?>
            <form method="post" id="f" aria-labelledby="t" novalidate>
                <input type="hidden" name="token" value="<?= h($token) ?>">

                <label for="password">New password</label>
                <div class="pw">
                    <input type="password" id="password" name="password" autocomplete="new-password" autofocus aria-describedby="h">
                    <button type="button" data-toggle="password" aria-pressed="false" aria-label="Show password">Show</button>
                </div>
                <div class="meter" aria-hidden="true"><i id="bar"></i></div>
                <p class="hint" id="h">At least 8 characters. <span id="strength"></span></p>

                <label for="confirm">Confirm new password</label>
                <div class="pw">
                    <input type="password" id="confirm" name="confirm" autocomplete="new-password">
                    <button type="button" data-toggle="confirm" aria-pressed="false" aria-label="Show confirm password">Show</button>
                </div>

                <button class="submit" type="submit">Save new password</button>
            </form>
            <p class="back-link"><a href="login.php">← Back to log in</a></p>
        <?php endif; ?>
    </main>

</div>

<script>
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.onclick = function () {
        var input = document.getElementById(btn.dataset.toggle);
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', show);
    };
});
var pw = document.getElementById('password');
if (pw) pw.addEventListener('input', function () {
    var v = pw.value, score = 0;
    if (v.length >= 8) score++;
    if (v.length >= 12) score++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
    if (/\d/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var label = ['Too short', 'Weak', 'Okay', 'Good', 'Strong', 'Very strong'][v.length ? score : 0];
    if (v.length && v.length < 8) { score = 0; label = 'Too short'; }
    document.getElementById('bar').style.width = (v.length ? Math.max(score, 1) * 20 : 0) + '%';
    document.getElementById('bar').style.background = score <= 1 ? '#a3281a' : (score <= 3 ? '#d98a00' : '#2d6a3e');
    document.getElementById('strength').textContent = v.length ? '· ' + label : '';
});
</script>
<?php include('../includes/authFooter.php'); ?>
