<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);
include('validate.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$form = ['product_name' => trim($_POST['product_name'] ?? ''), 'price' => trim($_POST['price'] ?? ''),
         'stock_quantity' => trim($_POST['stock_quantity'] ?? ''), 'category_id' => trim($_POST['category_id'] ?? '')];

$errors = validate_product($conn, $form, 0);
if ($errors) back_with_errors($errors, $form, 'index.php');

$price = (float)$form['price']; $stock = (int)$form['stock_quantity']; $cid = (int)$form['category_id'];
if (!mysqli_execute_query($conn, "INSERT INTO products(product_name,price,stock_quantity,category_id) VALUES(?,?,?,?)",
        [$form['product_name'], $price, $stock, $cid])) {
    back_with_errors(['Database error: ' . mysqli_error($conn)], $form, 'index.php');
}
$pid = mysqli_insert_id($conn);

$errors = save_photos($conn, $pid);
if ($errors) back_with_errors($errors, [], "index.php?edit=$pid");

$_SESSION['success'] = 'Product added.';
redirect('index.php');
