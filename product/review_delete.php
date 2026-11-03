<?php
// product/review_delete.php - admin can remove any review, customers only their own
session_start();
include('../includes/config.php');
require_login();
mysqli_report(MYSQLI_REPORT_OFF);

$pid  = (int)($_POST['product_id'] ?? 0);
$rid  = (int)($_POST['review_id'] ?? 0);
$role = $_SESSION['role'];
$cid  = (int)($_SESSION['customer_id'] ?? 0);

if ($role === 'Admin') {
    mysqli_execute_query($conn, "DELETE FROM reviews WHERE review_id=? AND product_id=?", [$rid, $pid]);
} else {
    mysqli_execute_query($conn, "DELETE FROM reviews WHERE review_id=? AND product_id=? AND customer_id=?", [$rid, $pid, $cid]);
}
$_SESSION['success'] = 'Review deleted.';
redirect("show.php?id=$pid#reviews");
