<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$form = ['employee_name' => trim($_POST['employee_name'] ?? ''), 'role' => $_POST['role'] ?? '', 'username' => trim($_POST['username'] ?? '')];
$pw   = $_POST['password'] ?? '';

$errors = validate_employee($conn, $form, $pw, 0);
if ($errors) back_with_errors($errors, $form, 'index.php');

$uname = $form['username'] !== '' ? $form['username'] : null;
$hash  = $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null;
$ok = mysqli_execute_query($conn, "INSERT INTO employees (employee_name, role, username, password) VALUES (?, ?, ?, ?)",
    [$form['employee_name'], $form['role'], $uname, $hash]);
if ($ok) {
    $_SESSION['success'] = 'Employee added.';
    redirect('index.php');
}
back_with_errors(['Could not save: ' . mysqli_error($conn)], $form, 'index.php');
