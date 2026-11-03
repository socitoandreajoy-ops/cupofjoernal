<?php
session_start();
include('../includes/config.php');

$old = take_old();
$title = 'Sign up – Cup of Joe-rnal';
include('../includes/authHeader.php');
?>
<div class="auth">

    <div class="hero">
        <img src="<?= BASE_URL ?>/assets/logo.png" alt="Cup of Joe-rnal coffee shop logo">
        <h1>Join the Cup of Joe-rnal family</h1>
        <p>Sign up and start earning loyalty points.</p>
    </div>

    <main class="card" id="main">
        <h2 id="t">Create your account</h2>

        <?php $alert_auth = true; include('../includes/alert.php'); ?>

        <form method="post" action="store.php" id="f" aria-labelledby="t" enctype="multipart/form-data">

            <label for="name">Full name</label>
            <input type="text" id="name" name="name" autocomplete="name" required value="<?= h($old['name'] ?? '') ?>">

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" autocomplete="email" required value="<?= h($old['email'] ?? '') ?>">

            <label for="phone">Contact number</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel" value="<?= h($old['phone'] ?? '') ?>">

            <label for="photo">Profile photo (optional)</label>
            <input type="file" id="photo" name="photo">

            <label for="password">Password</label>
            <div class="pw">
                <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="h">
                <button type="button" data-toggle="password" aria-pressed="false" aria-label="Show password">Show</button>
            </div>
            <p class="hint" id="h">At least 8 characters.</p>

            <label for="confirm">Confirm password</label>
            <div class="pw">
                <input type="password" id="confirm" name="confirm" autocomplete="new-password" required>
                <button type="button" data-toggle="confirm" aria-pressed="false" aria-label="Show confirm password">Show</button>
            </div>

            <button class="submit" type="submit">Sign up</button>
        </form>

        <p class="swap">Already have an account? <a href="login.php">Log in</a></p>
    </main>

</div>

<script>
// Show / hide password (works for both password boxes)
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.onclick = function () {
        var input = document.getElementById(btn.dataset.toggle);
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + (input.id === 'confirm' ? 'confirm password' : 'password'));
    };
});
</script>
<?php include('../includes/authFooter.php'); ?>
