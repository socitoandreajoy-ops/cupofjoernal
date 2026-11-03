<?php
// customer/validate.php - shared by store.php and update.php. Returns a list of error messages.
function validate_customer($conn, array $d, int $id = 0): array {
    $errors = [];
    if ($d['customer_name'] === '') $errors[] = 'Customer name is required.';
    elseif (strlen($d['customer_name']) > 100) $errors[] = 'Customer name is too long (max 100).';
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email format is invalid.';
    if ($d['email'] !== '') {
        $q = mysqli_execute_query($conn, "SELECT 1 FROM customers WHERE email=? AND customer_id<>?", [$d['email'], $id]);
        if (mysqli_fetch_row($q)) $errors[] = 'That email is already used by another customer.';
    }
    if ($d['phone'] !== '' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $d['phone'])) $errors[] = 'Phone must look like 09123456789 or +639123456789.';
    if ($d['loyalty_points'] === '' || !ctype_digit($d['loyalty_points'])) $errors[] = 'Loyalty points must be a whole number (0 or more).';
    return $errors;
}
