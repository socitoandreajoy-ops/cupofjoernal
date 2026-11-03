<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$id = (int)($_POST['id'] ?? 0);
$data = ['customer_name' => trim($_POST['customer_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''),
         'phone' => trim($_POST['phone'] ?? ''), 'loyalty_points' => trim($_POST['loyalty_points'] ?? '')];

$errors = validate_customer($conn, $data, $id);
if ($errors) back_with_errors($errors, $data, "index.php?edit=$id");

$em = $data['email'] ?: null;
$ph = $data['phone'] ?: null;
$pts = (int)$data['loyalty_points'];
$ok = mysqli_execute_query($conn, "UPDATE customers SET customer_name=?,email=?,phone=?,loyalty_points=? WHERE customer_id=?",
    [$data['customer_name'], $em, $ph, $pts, $id]);
if ($ok) {
    $_SESSION['success'] = 'Customer updated.';
    redirect('index.php');
}
back_with_errors(['Database error: ' . mysqli_error($conn)], $data, "index.php?edit=$id");
