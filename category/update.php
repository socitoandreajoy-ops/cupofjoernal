<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);

$id   = (int)($_POST['category_id'] ?? 0);
$name = trim($_POST['category_name'] ?? '');

if ($name === '') {
    back_with_errors(['Category name is required.'], ['category_name' => $name], "index.php?edit=$id");
}
if (mb_strlen($name) > 50) {
    back_with_errors(['Category name is too long (max 50).'], ['category_name' => $name], "index.php?edit=$id");
}

mysqli_execute_query($conn, "UPDATE categories SET category_name = ? WHERE category_id = ?", [$name, $id]);
header("Location: index.php");
exit();
