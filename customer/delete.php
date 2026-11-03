<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

$old = mysqli_fetch_row(mysqli_execute_query($conn, "SELECT photo FROM customers WHERE customer_id=?", [$id]))[0] ?? null;

mysqli_begin_transaction($conn);
try {
    mysqli_execute_query($conn, "DELETE FROM users WHERE customer_id=?", [$id]);
    mysqli_execute_query($conn, "DELETE FROM customers WHERE customer_id=?", [$id]);
    mysqli_commit($conn);
    if ($old) @unlink(__DIR__ . '/../uploads/customers/' . $old);
    $_SESSION['success'] = 'Customer deleted.';
} catch (Throwable $error) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'Cannot delete this customer because they have existing orders.';
}
redirect('index.php');
