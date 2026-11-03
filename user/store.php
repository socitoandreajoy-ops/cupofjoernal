<?php
// user/store.php - saves a new sign-up (customer account), like 2a-shop's user/store.php
session_start();
include('../includes/config.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('register.php');

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$pw      = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$role    = 'customer';
$old     = ['name' => $name, 'email' => $email, 'phone' => $phone];
$file    = $_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE];

// Validate the form (shows the first problem only)
$err = '';
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Enter your name and a valid email.';
} elseif (strlen($pw) < 8) {
    $err = 'Password must be at least 8 characters.';
} elseif ($pw !== $confirm) {
    $err = 'Passwords do not match.';
} elseif ($perr = validate_image($file)) {
    $err = $perr;
} else {
    // Check if the email is already used
    $dup = mysqli_execute_query($conn, "SELECT 1 FROM users WHERE email = ?", [$email]);
    if (mysqli_fetch_row($dup)) $err = 'That email is already registered.';
}
if ($err) back_with_errors([$err], $old, 'register.php');

$photo = save_image($file, __DIR__ . '/../uploads/users/', 'u');
try {
    // Save both records together, or neither
    mysqli_begin_transaction($conn);

    $ph = $phone !== '' ? $phone : null;
    mysqli_execute_query($conn, "INSERT INTO customers (customer_name, phone, email) VALUES (?, ?, ?)", [$name, $ph, $email]);
    $customer_id = mysqli_insert_id($conn);

    $hash = password_hash($pw, PASSWORD_DEFAULT);
    mysqli_execute_query($conn, "INSERT INTO users (username, full_name, email, password, role, customer_id, photo)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)", [$email, $name, $email, $hash, $role, $customer_id, $photo]);

    mysqli_commit($conn);
    mail_welcome($email, $name);                       // welcome email (shows up in Mailtrap); a failure never blocks sign-up
    $_SESSION['success'] = 'Account created! Please log in.';
    redirect('login.php');
} catch (Throwable $ex) {
    mysqli_rollback($conn);
    if ($photo) @unlink(__DIR__ . '/../uploads/users/' . $photo);
    back_with_errors(['Sorry, we could not create your account. Please try again.'], $old, 'register.php');
}
