<?php
// checkout.php - a customer places an order from the menu on index.php (same idea as 2a-shop's checkout.php)
session_start();
include('includes/config.php');
require_role(['Customer']);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_POST['place_order'])) redirect('index.php');

$cid    = (int)($_SESSION['customer_id'] ?? 0);
$qtys   = array_filter(array_map('intval', $_POST['qty'] ?? []), fn($q) => $q > 0);
$errors = [];
if ($cid < 1) {
    $errors[] = 'Your account is not linked to a customer record. Please ask staff for assistance.';
}
if (!$qtys) {
    $errors[] = 'Add at least one item to your order.';
}
[$otype, $oaddr, $ophone, $oerrs] = read_order_type($_POST);
$errors = array_merge($errors, $oerrs);
if ($errors) back_with_errors($errors, [], 'index.php');

mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_id, employee_id, order_type, delivery_address, contact_phone) VALUES (?, NULL, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isss', $cid, $otype, $oaddr, $ophone);
    mysqli_stmt_execute($stmt);
    $oid = mysqli_insert_id($conn);
    $total = 0;

    $get = mysqli_prepare($conn, "SELECT price, stock_quantity, product_name FROM products WHERE product_id=? FOR UPDATE");
    $ins = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?,?,?,?)");
    $upd = mysqli_prepare($conn, "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id=?");

    foreach ($qtys as $pid => $q) {
        $pid = (int)$pid;
        mysqli_stmt_bind_param($get, 'i', $pid);
        mysqli_stmt_execute($get);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($get));

        if (!$r) {
            throw new Exception('One of the items is no longer available.');
        }
        if ($q > (int)$r['stock_quantity']) {
            throw new Exception("Sorry, only {$r['stock_quantity']} left of {$r['product_name']}.");
        }

        $price = (float)$r['price'];
        $total += $price * $q;

        mysqli_stmt_bind_param($ins, 'iiid', $oid, $pid, $q, $price);
        mysqli_stmt_execute($ins);

        // deduct stock
        mysqli_stmt_bind_param($upd, 'ii', $q, $pid);
        mysqli_stmt_execute($upd);
    }

    mysqli_execute_query($conn, "UPDATE orders SET total_amount=? WHERE order_id=?", [$total, $oid]);

    $points = (int)floor($total / 50);
    mysqli_execute_query($conn, "UPDATE customers SET loyalty_points = loyalty_points + ? WHERE customer_id=?", [$points, $cid]);

    mysqli_commit($conn);

    // email the order slip to the customer + a copy to the shop (never breaks the order)
    mail_order_slip($conn, $oid);
    $hasEmail = (bool)(mysqli_fetch_row(mysqli_execute_query($conn, "SELECT email FROM customers WHERE customer_id=?", [$cid]))[0] ?? '');

    $_SESSION['new_order_id'] = $oid;       // index.php shows the order slip pop-up for this order
    $_SESSION['success'] = "Order #$oid ($otype) placed! " . ($otype === 'Deliver' ? 'We will bring it to your address.' : 'Please present your Order Slip to the cashier to pay.') . ($hasEmail ? ' Your order slip was sent to your email.' : ' Add an email in My Profile to receive order slips.');
    redirect('index.php');
} catch (Throwable $ex) {
    mysqli_rollback($conn);
    back_with_errors([$ex->getMessage()], [], 'index.php');
}
