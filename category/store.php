<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);

$name = trim($_POST['category_name'] ?? '');

if ($name === '') {
    back_with_errors(['Category name is required.'], ['category_name' => $name], 'index.php');
}
if (mb_strlen($name) > 50) {
    back_with_errors(['Category name is too long (max 50).'], ['category_name' => $name], 'index.php');
}

mysqli_execute_query($conn, "INSERT INTO categories (category_name) VALUES (?)", [$name]);
header("Location: index.php");
exit();
