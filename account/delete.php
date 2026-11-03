<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('helpers.php');

$me = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$u  = get_user($conn, $id);

if (!$u) $_SESSION['message'] = 'User not found.';
elseif ($id === $me) $_SESSION['message'] = 'You cannot delete your own account.';
elseif ($u['role'] === 'admin' && (int)$u['is_active'] === 1 && other_active_admins($conn, $id) < 1) $_SESSION['message'] = 'There must be at least one active admin.';
elseif (mysqli_execute_query($conn, "DELETE FROM users WHERE user_id=?", [$id])) {
    if ($u['photo']) @unlink(__DIR__ . '/../uploads/users/' . $u['photo']);
    $_SESSION['success'] = 'User deleted.';
} else {
    $_SESSION['message'] = 'Could not delete this user.';
}
redirect('index.php');
