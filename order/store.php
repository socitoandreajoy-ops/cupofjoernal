<?php
// order/store.php - saves an order placed by staff on order/create.php
session_start();
include('../includes/config.php');
require_role(['Admin', 'Cashier']);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['place_order'])) redirect('create.php');

$mode    = in_array($_POST['cust_mode'] ?? '', ['new', 'guest'], true) ? $_POST['cust_mode'] : 'existing';
$cust_id = (int)($_POST['customer_id'] ?? 0);
$emp_id  = (int)($_SESSION['employee_id'] ?? 0);   // set at login for cashiers, 0 for admin
$qtys    = array_filter(array_map('intval', $_POST['qty'] ?? []), fn($q) => $q > 0);
$nc_name  = trim($_POST['new_name'] ?? '');
$nc_phone = trim($_POST['new_phone'] ?? '');
$nc_email = trim($_POST['new_email'] ?? '');
$g_email  = trim($_POST['guest_email'] ?? '');
$old = ['cust_mode' => $mode, 'guest_email' => $g_email, 'customer_id' => $cust_id, 'new_name' => $nc_name, 'new_phone' => $nc_phone, 'new_email' => $nc_email];
$errors = [];

if ($mode === 'new') {
    if ($nc_name === '')            $errors[] = 'Enter the new customer\'s name.';
    elseif (strlen($nc_name) > 100) $errors[] = 'Customer name is too long (max 100).';
    if ($nc_phone !== '' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $nc_phone))
        $errors[] = 'Phone must look like 09123456789 or +639123456789.';
    if ($nc_email !== '' && !filter_var($nc_email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Email format is invalid.';
    if ($nc_email !== '' && !$errors) {
        $q = mysqli_execute_query($conn, "SELECT 1 FROM customers WHERE email=?", [$nc_email]);
        if (mysqli_fetch_row($q)) $errors[] = 'That email is already used by another customer.';
    }
} elseif ($mode === 'guest') {
    if ($g_email !== '' && !filter_var($g_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Guest email format is invalid.';
} elseif ($cust_id < 1) {
    $errors[] = 'Please select a customer (or choose "New customer").';
}
if (!$qtys) $errors[] = 'Select at least one product.';
[$otype, $oaddr, $ophone, $oerrs] = read_order_type($_POST);
$errors = array_merge($errors, $oerrs);
$old += ['order_type' => $otype, 'delivery_address' => (string)$oaddr, 'contact_phone' => (string)$ophone];
if ($errors) back_with_errors($errors, $old, 'create.php');

mysqli_begin_transaction($conn);
try {
    if ($mode === 'new') {
        $em = $nc_email !== '' ? $nc_email : null;
        $ph = $nc_phone !== '' ? $nc_phone : null;
        mysqli_execute_query($conn, "INSERT INTO customers (customer_name, email, phone, loyalty_points) VALUES (?, ?, ?, 0)", [$nc_name, $em, $ph]);
        $cust_id = mysqli_insert_id($conn);
    }
    if ($mode === 'guest') {
        $gid = guest_id($conn);
        $nc_name = 'Guest ' . $gid;
        mysqli_execute_query($conn, "INSERT INTO customers (customer_name, email, phone, loyalty_points) VALUES (?, ?, ?, 0)", [$nc_name, $g_email !== '' ? $g_email : null, $ophone]);
        $cust_id = mysqli_insert_id($conn);
    }
    // session user_id comes from the users table, not employees,
    // so only keep it if a matching employee row exists (else NULL)
    $emp_ref = mysqli_fetch_row(mysqli_execute_query($conn, "SELECT employee_id FROM employees WHERE employee_id = ?", [$emp_id]))[0] ?? null;

    $placed_by = $_SESSION['username'] ?? 'Admin';
    mysqli_execute_query($conn, "INSERT INTO orders (customer_id, employee_id, placed_by_name, order_type, delivery_address, contact_phone, total_amount) VALUES (?, ?, ?, ?, ?, ?, 0)", [$cust_id, $emp_ref, $placed_by, $otype, $oaddr, $ophone]);
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
            throw new Exception("Only {$r['stock_quantity']} left of {$r['product_name']}.");
        }

        $price = (float)$r['price'];
        $total += $price * $q;

        mysqli_stmt_bind_param($ins, 'iiid', $oid, $pid, $q, $price);
        mysqli_stmt_execute($ins);

        mysqli_stmt_bind_param($upd, 'ii', $q, $pid);
        mysqli_stmt_execute($upd);
    }

    mysqli_execute_query($conn, "UPDATE orders SET total_amount=? WHERE order_id=?", [$total, $oid]);

    mysqli_commit($conn);
    $sent = mail_order_slip($conn, $oid);       // slip -> customer email (if any) + shop copy
    $msg = "Order #$oid ($otype) created successfully! Total: ₱" . number_format($total, 2);
    if ($mode === 'new') $msg .= " (new customer \"$nc_name\" added)";
    if ($mode === 'guest') $msg .= " Guest ID: $gid.";
    if ($sent) $msg .= ' Order slip emailed.';
    $_SESSION['success'] = $msg;
    $_SESSION['success_slip'] = $oid;       // alert.php adds the "Print Slip" button for this order
    redirect('create.php');
} catch (Throwable $ex) {
    mysqli_rollback($conn);
    back_with_errors([$ex->getMessage()], $old, 'create.php');
}
