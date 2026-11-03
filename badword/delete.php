<?php
// badword/delete.php - Admin removes a word from the list
session_start();
include('../includes/config.php');
require_role(['Admin']);

$id = filter_input(INPUT_POST, 'word_id', FILTER_VALIDATE_INT);
if ($id) {
    try {
        mysqli_execute_query($conn, "DELETE FROM bad_words WHERE word_id=?", [$id]);
        $_SESSION['success'] = 'Word removed.';
    } catch (Throwable $ex) {
        $_SESSION['message'] = 'Could not remove that word.';
    }
}
redirect('index.php');
