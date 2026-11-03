<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('helpers.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$me   = (int)$_SESSION['user_id'];
$id   = (int)($_POST['id'] ?? 0);
$cur  = get_user($conn, $id);
$form = ['full_name' => trim($_POST['full_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'role' => $_POST['role'] ?? ''];
$pw   = $_POST['password'] ?? '';

$errors = validate_account($conn, $form, $pw, $id, $cur, $me);
if ($errors) back_with_errors($errors, $form, "index.php?edit=$id");

$dir   = __DIR__ . '/../uploads/users/';
$photo = save_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $dir, 'u');
$photoVal = $photo ?: $cur['photo'];

// keep username in sync when it was the email
$uname = ($cur['username'] === $cur['email']) ? $form['email'] : $cur['username'];
if ($pw !== '') {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $ok = mysqli_execute_query($conn, "UPDATE users SET username=?, full_name=?, email=?, role=?, photo=?, password=? WHERE user_id=?",
        [$uname, $form['full_name'], $form['email'], $form['role'], $photoVal, $hash, $id]);
} else {
    $ok = mysqli_execute_query($conn, "UPDATE users SET username=?, full_name=?, email=?, role=?, photo=? WHERE user_id=?",
        [$uname, $form['full_name'], $form['email'], $form['role'], $photoVal, $id]);
}
if ($ok) {
    if ($photo && $cur['photo']) @unlink($dir . $cur['photo']);
    ensure_customer($conn, $id);
    if ($id === $me) { $_SESSION['username'] = $form['full_name']; if ($photo) $_SESSION['photo'] = $photo; }
    $_SESSION['success'] = 'User updated.';
    redirect('index.php');
}
$dberr = mysqli_error($conn);
if ($photo) @unlink($dir . $photo);
back_with_errors(['Database error: ' . $dberr], $form, "index.php?edit=$id");
