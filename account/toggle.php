<?php
// account/toggle.php - deactivate / activate a user
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('helpers.php');

$me = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$u  = get_user($conn, $id);

if (!$u) $_SESSION['message'] = 'User not found.';
elseif ($id === $me) $_SESSION['message'] = 'You cannot deactivate your own account.';
elseif ((int)$u['is_active'] === 1 && $u['role'] === 'admin' && other_active_admins($conn, $id) < 1) $_SESSION['message'] = 'There must be at least one active admin.';
else {
    $new = (int)$u['is_active'] === 1 ? 0 : 1;
    mysqli_execute_query($conn, "UPDATE users SET is_active=? WHERE user_id=?", [$new, $id]);
    $_SESSION['success'] = $new ? 'User activated.' : 'User deactivated. They can no longer log in.';
}
redirect('index.php');
