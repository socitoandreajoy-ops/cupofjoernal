<?php
// product/delete_photo.php - removes one photo from a product's gallery
session_start();
include('../includes/config.php');
require_role(['Admin']);
mysqli_report(MYSQLI_REPORT_OFF);

$pid = (int)($_POST['id'] ?? 0);
$ph  = (int)($_POST['photo_id'] ?? 0);

$r = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT filename FROM product_photos WHERE photo_id=? AND product_id=?", [$ph, $pid]));
if ($r) {
    @unlink(__DIR__ . '/../uploads/products/' . $r['filename']);
    mysqli_execute_query($conn, "DELETE FROM product_photos WHERE photo_id=?", [$ph]);
}
redirect("index.php?edit=$pid");
