<?php
// product/validate.php - shared by store.php and update.php. Returns a list of error messages.
function validate_product($conn, array $f, int $id = 0): array {
    $errors = [];
    if ($f['product_name'] === '') $errors[] = 'Product name is required.';
    elseif (strlen($f['product_name']) > 100) $errors[] = 'Product name is too long (max 100).';
    elseif (!preg_match("/^[\p{L}\p{N}][\p{L}\p{N} '&.,()\-]*$/u", $f['product_name'])) $errors[] = 'Product name has invalid characters.';
    if ($f['product_name'] !== '') {
        $d = mysqli_execute_query($conn, "SELECT 1 FROM products WHERE LOWER(product_name)=LOWER(?) AND product_id<>?", [$f['product_name'], $id]);
        if (mysqli_fetch_row($d)) $errors[] = 'A product with that name already exists.';
    }
    if ($f['price'] === '') $errors[] = 'Price is required.';
    elseif (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $f['price']) || (float)$f['price'] <= 0) $errors[] = 'Price must be a number greater than 0 (max 2 decimals, up to 999999.99).';
    if ($f['stock_quantity'] === '') $errors[] = 'Stock is required.';
    elseif (!ctype_digit($f['stock_quantity']) || strlen($f['stock_quantity']) > 6) $errors[] = 'Stock must be a whole number from 0 to 999999.';
    $c = mysqli_execute_query($conn, "SELECT 1 FROM categories WHERE category_id=?", [(int)$f['category_id']]);
    if (!mysqli_fetch_row($c)) $errors[] = 'Please choose a category.';
    foreach (files_to_list($_FILES['photos'] ?? []) as $file) {
        if ($pe = validate_image($file)) $errors[] = $file['name'] . ': ' . $pe;
    }
    return $errors;
}

// Saves every chosen photo (input name="photos[]") for product $pid. Returns a list of error messages.
function save_photos($conn, int $pid): array {
    $errors = [];
    $dir = __DIR__ . '/../uploads/products/';
    foreach (files_to_list($_FILES['photos'] ?? []) as $file) {
        $f = save_image($file, $dir, "p{$pid}_", $err);
        if ($f === null) { $errors[] = $file['name'] . ': ' . ($err ?: 'could not be saved.'); continue; }
        mysqli_execute_query($conn, "INSERT INTO product_photos(product_id,filename) VALUES(?,?)", [$pid, $f]);
    }
    return $errors;
}
