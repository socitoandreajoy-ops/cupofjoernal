<?php
// includes/alert.php - shows (once) whatever is waiting in the session:
//   $_SESSION['success'], $_SESSION['message'] (one error), $_SESSION['errors'] (list of errors)
// Set $alert_auth = true before including it on the login / sign-up pages (they use the .msg style).
$alert_auth = !empty($alert_auth);
$slip_id = (int)($_SESSION['success_slip'] ?? 0);

if (isset($_SESSION['success'])) {
    if ($alert_auth) {
        echo '<div class="msg ok" role="alert">' . h($_SESSION['success']) . '</div>';
    } else {
        echo '<div class="alert ok">' . h($_SESSION['success']);
        if ($slip_id) echo ' &nbsp;<a class="btn sm alt" href="' . BASE_URL . '/order/slip.php?id=' . $slip_id . '" target="_blank">🖨️ Print Slip</a>';
        echo '</div>';
    }
    unset($_SESSION['success'], $_SESSION['success_slip']);
}

if (isset($_SESSION['message'])) {
    echo '<div class="' . ($alert_auth ? 'msg err' : 'alert err') . '"' . ($alert_auth ? ' role="alert"' : '') . '>' . h($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}

if (!empty($_SESSION['errors'])) {
    if ($alert_auth) {
        echo '<div class="msg err" role="alert">' . implode('<br>', array_map('h', $_SESSION['errors'])) . '</div>';
    } else {
        foreach ($_SESSION['errors'] as $er) echo '<div class="alert err">' . h($er) . '</div>';
    }
}
unset($_SESSION['errors']);
