<?php
// inventory/restock.php - adds stock to a product (Quick Restock form on inventory/index.php)
session_start();
include('../includes/config.php');
require_role(['Admin']);

$product_id   = (int)($_POST['product_id'] ?? 0);
$add_quantity = (int)($_POST['add_quantity'] ?? 0);

if ($product_id > 0 && $add_quantity > 0) {
    mysqli_execute_query($conn, "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?", [$add_quantity, $product_id]);
}
header("Location: index.php");
exit();
