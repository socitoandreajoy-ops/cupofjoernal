<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$data = ['customer_name' => trim($_POST['customer_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''),
         'phone' => trim($_POST['phone'] ?? ''), 'loyalty_points' => trim($_POST['loyalty_points'] ?? '')];

$errors = validate_customer($conn, $data, 0);
if ($errors) back_with_errors($errors, $data, 'index.php');

$em = $data['email'] ?: null;
$ph = $data['phone'] ?: null;
$pts = (int)$data['loyalty_points'];
$ok = mysqli_execute_query($conn, "INSERT INTO customers(customer_name,email,phone,loyalty_points) VALUES(?,?,?,?)",
    [$data['customer_name'], $em, $ph, $pts]);
if ($ok) {
    $_SESSION['success'] = 'Customer added.';
    redirect('index.php');
}
back_with_errors(['Database error: ' . mysqli_error($conn)], $data, 'index.php');
