<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    mysqli_execute_query($conn, "DELETE FROM categories WHERE category_id = ?", [$id]);
} catch (Throwable $error) {
    $_SESSION['message'] = 'Cannot delete this category because products are using it.';
}
header("Location: index.php");
exit();
