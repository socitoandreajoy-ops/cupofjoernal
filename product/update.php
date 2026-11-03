<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$id   = (int)($_POST['id'] ?? 0);
$form = ['product_name' => trim($_POST['product_name'] ?? ''), 'price' => trim($_POST['price'] ?? ''),
         'stock_quantity' => trim($_POST['stock_quantity'] ?? ''), 'category_id' => trim($_POST['category_id'] ?? '')];

$errors = validate_product($conn, $form, $id);
if ($errors) back_with_errors($errors, $form, "index.php?edit=$id");

$price = (float)$form['price']; $stock = (int)$form['stock_quantity']; $cid = (int)$form['category_id'];
if (!mysqli_execute_query($conn, "UPDATE products SET product_name=?,price=?,stock_quantity=?,category_id=? WHERE product_id=?",
        [$form['product_name'], $price, $stock, $cid, $id])) {
    back_with_errors(['Database error: ' . mysqli_error($conn)], $form, "index.php?edit=$id");
}

$errors = save_photos($conn, $id);
if ($errors) back_with_errors($errors, [], "index.php?edit=$id");

$_SESSION['success'] = 'Product updated.';
redirect('index.php');
