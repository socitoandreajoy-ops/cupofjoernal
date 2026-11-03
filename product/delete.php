<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

$files = array_column(mysqli_fetch_all(mysqli_execute_query($conn, "SELECT filename FROM product_photos WHERE product_id=?", [$id]), MYSQLI_ASSOC), 'filename');

if (mysqli_execute_query($conn, "DELETE FROM products WHERE product_id=?", [$id])) {
    foreach ($files as $f) @unlink(__DIR__ . '/../uploads/products/' . $f);
    $_SESSION['success'] = 'Product deleted.';
} else {
    $_SESSION['message'] = 'Cannot delete this product because it is used in existing orders.';
}
redirect('index.php');
