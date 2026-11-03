<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('helpers.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$me   = (int)$_SESSION['user_id'];
$form = ['full_name' => trim($_POST['full_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'role' => $_POST['role'] ?? ''];
$pw   = $_POST['password'] ?? '';

$errors = validate_account($conn, $form, $pw, 0, null, $me);
if ($errors) back_with_errors($errors, $form, 'index.php');

$dir   = __DIR__ . '/../uploads/users/';
$photo = save_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $dir, 'u');
$hash  = password_hash($pw, PASSWORD_DEFAULT);

$ok = mysqli_execute_query($conn, "INSERT INTO users (username, full_name, email, password, role, photo, is_active) VALUES (?,?,?,?,?,?,1)",
    [$form['email'], $form['full_name'], $form['email'], $hash, $form['role'], $photo]);
if ($ok) {
    ensure_customer($conn, mysqli_insert_id($conn));
    $_SESSION['success'] = 'User added.';
    redirect('index.php');
}
$dberr = mysqli_error($conn);
if ($photo) @unlink($dir . $photo);
back_with_errors(['Database error: ' . $dberr], $form, 'index.php');
