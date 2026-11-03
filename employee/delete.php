<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);

$id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

if (mysqli_execute_query($conn, "DELETE FROM employees WHERE employee_id = ?", [$id])) {
    $_SESSION['success'] = 'Employee deleted.';
} else {
    $_SESSION['message'] = "Can't delete this employee because they already have orders on record. Edit them instead (change role or username).";
}
redirect('index.php');
