<?php
session_start();
include('../includes/config.php');

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) redirect(home_for($_SESSION['role']));

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

    // ---- PHP validation (no HTML5 validation on this form) ----
    if ($email === '')                                   $errors[] = 'Email is required.';
    elseif (strlen($email) > 100)                        $errors[] = 'Email is too long (max 100 characters).';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Please enter a valid email address.';
    if ($pw === '')                                      $errors[] = 'Password is required.';

    if (!$errors) {
        $sql = "SELECT user_id, username, full_name, email, password, role, customer_id, is_active, photo
                FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1";
        $result = mysqli_execute_query($conn, $sql, [$email]);
        $u = mysqli_fetch_assoc($result);

        $ok = false;
        if ($u) {
            if (password_verify($pw, $u['password'])) {
                $ok = true;
            } elseif (password_get_info($u['password'])['algoName'] === 'unknown' && hash_equals($u['password'], $pw)) {
                // old plain-text password: accept it once and store a proper hash
                $ok   = true;
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                mysqli_execute_query($conn, "UPDATE users SET password = ? WHERE user_id = ?", [$hash, $u['user_id']]);
            }
        }

        if (!$ok) {
            $errors[] = 'Incorrect email or password.';
        } elseif ((int)$u['is_active'] !== 1) {
            $errors[] = 'Your account has been deactivated. Please contact the administrator.';
        } else {
            $role = login_user($u);                           // fills the session (see includes/functions.php)
            if (!empty($_POST['remember'])) remember_issue((int)$u['user_id']);   // "Keep me logged in" ticked
            redirect(home_for($role));
        }
    }
    $_SESSION['errors'] = $errors;
}

$title = 'Log in – Cup of Joe-rnal';
include('../includes/authHeader.php');
?>
<div class="auth">

    <div class="hero">
        <img src="<?= BASE_URL ?>/assets/logo.png" alt="Cup of Joe-rnal coffee shop logo">
        <h1>Every cup tells a story</h1>
        <p>Log in and keep brewing.</p>
    </div>

    <main class="card" id="main">
        <h2 id="t">Log in to your account</h2>

        <?php $alert_auth = true; include('../includes/alert.php'); ?>

        <form method="post" id="f" aria-labelledby="t" novalidate>

            <label for="email">Email address</label>
            <input type="text" id="email" name="email" autocomplete="username" value="<?= h($email) ?>">

            <label for="password">Password</label>
            <div class="pw">
                <input type="password" id="password" name="password" autocomplete="current-password">
                <button type="button" data-toggle="password" aria-pressed="false" aria-label="Show password">Show</button>
            </div>

            <div class="login-opts">
                <label class="check"><input type="checkbox" name="remember" value="1"<?= !empty($_POST['remember']) ? ' checked' : '' ?>> Keep me logged in</label>
                <a href="forgot.php">Forgot password?</a>
            </div>

            <button class="submit" type="submit">Log in</button>
        </form>

        <p class="swap">Don't have an account? <a href="register.php">Sign up</a></p>
    </main>

</div>

<script>
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.onclick = function () {
        var input = document.getElementById('password');
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + 'password');
    };
});
</script>
<?php include('../includes/authFooter.php'); ?>
