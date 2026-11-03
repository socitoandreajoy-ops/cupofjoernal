<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$id   = (int)($_POST['employee_id'] ?? 0);
$form = ['employee_name' => trim($_POST['employee_name'] ?? ''), 'role' => $_POST['role'] ?? '', 'username' => trim($_POST['username'] ?? '')];
$pw   = $_POST['password'] ?? '';

$errors = validate_employee($conn, $form, $pw, $id);
if ($errors) back_with_errors($errors, $form, "index.php?edit=$id");

$uname = $form['username'] !== '' ? $form['username'] : null;
if ($pw !== '') {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $ok = mysqli_execute_query($conn, "UPDATE employees SET employee_name=?, role=?, username=?, password=? WHERE employee_id=?",
        [$form['employee_name'], $form['role'], $uname, $hash, $id]);
} else {
    $ok = mysqli_execute_query($conn, "UPDATE employees SET employee_name=?, role=?, username=? WHERE employee_id=?",
        [$form['employee_name'], $form['role'], $uname, $id]);
}
if ($ok) {
    $_SESSION['success'] = 'Employee updated.';
    redirect('index.php');
}
back_with_errors(['Could not save: ' . mysqli_error($conn)], $form, "index.php?edit=$id");
