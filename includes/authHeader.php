<?php
// includes/authHeader.php - page head for the login / sign-up pages. Set $title before including.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= h($title ?? 'Cup of Joe-rnal') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/includes/style/auth.css">
</head>
<body>

<a class="skip" href="#main">Skip to form</a>

