<?php
// user/forgot.php - "Forgot password?": emails a one-time reset link (valid 60 minutes). Open the email in Mailtrap.
session_start();
include('../includes/config.php');

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) redirect(home_for($_SESSION['role']));

$errors = [];
$email  = '';
$sent   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '')                                  $errors[] = 'Email is required.';
    elseif (strlen($email) > 100)                       $errors[] = 'Email is too long (max 100 characters).';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

    if (!$errors) {
        try {
            $q = mysqli_execute_query($conn, "SELECT user_id, username, full_name, email FROM users WHERE LOWER(email)=LOWER(?) AND is_active=1 LIMIT 1", [$email]);
            $u = mysqli_fetch_assoc($q);
            if ($u) {
                // at most one email per minute for the same account
                $q = mysqli_execute_query($conn, "SELECT 1 FROM password_resets WHERE user_id=? AND created_at > (NOW() - INTERVAL 1 MINUTE) LIMIT 1", [$u['user_id']]);
                if (!mysqli_fetch_row($q)) {
                    mysqli_execute_query($conn, "DELETE FROM password_resets WHERE user_id=? OR expires_at < NOW()", [$u['user_id']]);
                    $token = bin2hex(random_bytes(32));             // the link carries this; only its hash is stored
                    mysqli_execute_query($conn, "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))",
                        [$u['user_id'], hash('sha256', $token)]);
                    mail_password_reset($u['email'], $u['full_name'] ?: $u['username'], absolute_url('/user/reset.php?token=' . $token));
                }
            }
            $sent = true;                                            // same answer whether or not the email exists
        } catch (Throwable $ex) {
            $errors[] = 'Password reset is not set up yet. Import database/update_v2.sql in phpMyAdmin first.';
        }
    }
    if ($errors) $_SESSION['errors'] = $errors;
}

$title = 'Forgot password – Cup of Joe-rnal';
include('../includes/authHeader.php');
?>
<div class="auth">

    <div class="hero">
        <img src="<?= BASE_URL ?>/assets/logo.png" alt="Cup of Joe-rnal coffee shop logo">
        <h1>No worries, it happens</h1>
        <p>We'll email you a link to pick a new password.</p>
    </div>

    <main class="card" id="main">
        <h2 id="t">Forgot your password?</h2>

        <?php $alert_auth = true; include('../includes/alert.php'); ?>

        <?php if ($sent): ?>
            <div class="msg ok" role="status">If that email belongs to an account, a reset link is on its way. It is valid for 60 minutes.</div>
            <?php if (!mail_enabled()): ?>
                <div class="msg err" role="alert">Email is not set up yet, so nothing was sent. Add your Mailtrap login in <b>includes/mail_config.php</b>.</div>
            <?php endif; ?>
            <p class="back-link"><a href="login.php">← Back to log in</a></p>
        <?php else: ?>
            <p class="lead">Enter the email you signed up with.</p>
            <form method="post" id="f" aria-labelledby="t" novalidate>
                <label for="email">Email address</label>
                <input type="text" id="email" name="email" autocomplete="email" autofocus value="<?= h($email) ?>">
                <button class="submit" type="submit">Send reset link</button>
            </form>
            <p class="back-link"><a href="login.php">← Back to log in</a></p>
        <?php endif; ?>
    </main>

</div>
<?php include('../includes/authFooter.php'); ?>
