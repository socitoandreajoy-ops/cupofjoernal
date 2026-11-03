<?php
// product/review_store.php - a customer posts (or updates) their review of a product
session_start();
include('../includes/config.php');
require_role(['Customer']);
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('/index.php'));

$pid  = (int)($_POST['product_id'] ?? 0);
$cid  = (int)($_SESSION['customer_id'] ?? 0);
$back = "show.php?id=$pid";

$mine = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM reviews WHERE product_id=? AND customer_id=?", [$pid, $cid]));
$canReview = false;
if ($cid > 0) {
    $q = mysqli_execute_query($conn, "SELECT 1 FROM orders o JOIN order_items oi ON oi.order_id=o.order_id
                                      WHERE o.customer_id=? AND oi.product_id=? AND o.status<>'Cancelled' LIMIT 1", [$cid, $pid]);
    $canReview = (bool)mysqli_fetch_row($q);
}

$form = ['rating' => trim($_POST['rating'] ?? ''), 'comment' => trim($_POST['comment'] ?? '')];

// Problems with the rating / comment are shown right under those fields on the product page;
// anything else (like "you have not ordered this") goes in the red box at the top.
$top = [];
$old = $form + ['rating_error' => '', 'comment_error' => ''];
if (!$canReview) $top[] = 'Only customers who ordered this product can review it.';
if ($form['rating'] === '')                          $old['rating_error'] = 'Please select a star rating before submitting.';
elseif (!preg_match('/^[1-5]$/', $form['rating']))   $old['rating_error'] = 'Please choose a rating from 1 to 5 stars.';
$len = mb_strlen($form['comment']);
if ($len < 5)        $old['comment_error'] = 'Your review must be at least 5 characters.';
elseif ($len > 500)  $old['comment_error'] = 'Your review is too long (max 500 characters).';
if ($top || $old['rating_error'] !== '' || $old['comment_error'] !== '') {
    $_SESSION['errors'] = $top;
    $_SESSION['old']    = $old;
    redirect($back . '#write-review');
}

$rating = (int)$form['rating'];
// The review is saved exactly as typed. Bad words are hidden each time it is displayed (see mask_bad_words()),
// so words the Admin adds to the list later are covered too.
$text = $form['comment'];
if ($mine) {
    $ok = mysqli_execute_query($conn, "UPDATE reviews SET rating=?, comment=? WHERE review_id=? AND customer_id=?", [$rating, $text, $mine['review_id'], $cid]);
} else {
    $ok = mysqli_execute_query($conn, "INSERT INTO reviews (product_id, customer_id, rating, comment) VALUES (?,?,?,?)", [$pid, $cid, $rating, $text]);
}
if ($ok) {
    $masked = mask_bad_words($text) !== $text ? ' (Some words are hidden.)' : '';
    $_SESSION['success'] = ($mine ? 'Your review was updated.' : 'Thanks! Your review was posted.') . $masked;
    redirect($back . '#reviews');
}
back_with_errors(['Database error: ' . mysqli_error($conn)], $form, $back . '#write-review');
