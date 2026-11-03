<?php
session_start();
include('../includes/config.php');
remember_forget();                     // also forget the "Keep me logged in" cookie of this browser
$_SESSION = [];
session_destroy();
session_start();                       // fresh session just to carry the message
session_regenerate_id(true);
$_SESSION['success'] = 'You have been logged out.';
header('Location: login.php');
exit();
