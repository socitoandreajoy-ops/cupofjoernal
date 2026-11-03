<?php
// badword/restore.php - Admin puts the starter words back (words the admin added stay)
session_start();
include('../includes/config.php');
require_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$added = 0;
try {
    foreach (bad_words_default() as $w) {
        mysqli_execute_query($conn, "INSERT IGNORE INTO bad_words (word) VALUES (?)", [$w]);
        $added += max(0, mysqli_affected_rows($conn));
    }
    $_SESSION['success'] = $added ? "Restored $added starter word" . ($added === 1 ? '' : 's') . '.' : 'All starter words are already in the list.';
} catch (Throwable $ex) {
    $_SESSION['message'] = 'Could not restore the starter words.';
}
redirect('index.php');
