<?php
// account/role.php - change the role of an active user
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('helpers.php');

$roles = ['admin', 'cashier', 'customer'];
$me  = (int)$_SESSION['user_id'];
$id  = (int)($_POST['id'] ?? 0);
$new = $_POST['role'] ?? '';
$u   = get_user($conn, $id);

if (!$u) $_SESSION['message'] = 'User not found.';
elseif (!in_array($new, $roles, true)) $_SESSION['message'] = 'Please choose a valid role.';
elseif (!(int)$u['is_active']) $_SESSION['message'] = 'Only an active user\'s role can be changed.';
elseif ($id === $me) $_SESSION['message'] = 'You cannot change your own role.';
elseif ($u['role'] === 'admin' && $new !== 'admin' && other_active_admins($conn, $id) < 1) $_SESSION['message'] = 'There must be at least one active admin.';
else {
    mysqli_execute_query($conn, "UPDATE users SET role=? WHERE user_id=?", [$new, $id]);
    ensure_customer($conn, $id);
    $_SESSION['success'] = 'Role updated.';
}
redirect('index.php');
