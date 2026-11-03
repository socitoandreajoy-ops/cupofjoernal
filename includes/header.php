<?php
// includes/header.php - page head for the customer-facing pages (order page, product details, my profile)
// Set $title (and optionally $head_extra) before including this file.
$title = $title ?? 'Cup of Joe-rnal';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="<?= BASE_URL ?>/assets/logo-sm.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/includes/style/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/includes/style/menu.css?v=<?= @filemtime(__DIR__ . '/style/menu.css') ?>">
<?= $head_extra ?? '' ?></head><body>
<div class="app"><main class="content">
