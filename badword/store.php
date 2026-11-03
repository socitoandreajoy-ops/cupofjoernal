<?php
// badword/store.php - Admin adds one or more words (comma separated)
session_start();
include('../includes/config.php');
require_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$raw   = (string)($_POST['words'] ?? '');
$parts = preg_split('/[,;\n]+/u', $raw);
$added = 0; $dupes = 0; $errors = [];
$seen  = [];

try {
    foreach ($parts as $p) {
        $w = mb_strtolower(preg_replace('/\s+/u', ' ', trim($p)));
        if ($w === '' || isset($seen[$w])) continue;
        $seen[$w] = true;
        $len = mb_strlen($w);
        if ($len < 2 || $len > 30)                          { $errors[] = "“$w” must be 2 to 30 characters."; continue; }
        if (!preg_match('/^[\p{L}\p{N}]+( [\p{L}\p{N}]+)*$/u', $w)) { $errors[] = "“$w” can only have letters, numbers and spaces."; continue; }
        mysqli_execute_query($conn, "INSERT IGNORE INTO bad_words (word) VALUES (?)", [$w]);
        if (mysqli_affected_rows($conn) > 0) $added++; else $dupes++;
    }
} catch (Throwable $ex) {
    back_with_errors(['Could not save. Import database/update_v2.sql in phpMyAdmin first.'], ['words' => $raw], 'index.php');
}

if (!$seen) $errors[] = 'Type at least one word to add.';
if ($added)  $_SESSION['success'] = "Added $added word" . ($added === 1 ? '' : 's') . '.' . ($dupes ? " ($dupes already in the list.)" : '');
elseif ($dupes && !$errors) $_SESSION['message'] = 'Those words are already in the list.';
if ($errors) { $_SESSION['errors'] = $errors; $_SESSION['old'] = ['words' => $raw]; }
redirect('index.php');
