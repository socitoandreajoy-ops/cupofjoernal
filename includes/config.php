<?php
// includes/config.php - database connection + site settings (same idea as 2a-shop's config.php)
$db_host     = "localhost";
$db_username = "root";
$db_passwd   = "";
$db_name     = "cupofjoernal";

$conn = mysqli_connect($db_host, $db_username, $db_passwd) or die("Could not connect!\n");

// echo "Connection established.\n";
mysqli_select_db($conn, $db_name) or die("Could not select the database $db_name!\n" . mysqli_error($conn));
mysqli_set_charset($conn, 'utf8mb4');

// BASE_URL = the web path of this project (e.g. "/cupofjoernal"), worked out automatically so the
// project runs from any folder name. CURRENT_PAGE = the page being viewed, e.g. "admin/orders.php".
if (!defined('BASE_URL')) {
    $root   = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
    $script = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $rel    = (stripos($script, $root) === 0) ? ltrim(substr($script, strlen($root)), '/') : basename($script);
    $name   = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base   = ($rel !== '' && substr($name, -strlen($rel)) === $rel) ? rtrim(substr($name, 0, -strlen($rel)), '/') : '';
    define('BASE_URL', $base);       // if the CSS ever fails to load, set this by hand: define('BASE_URL', '/cupofjoernal');
    define('CURRENT_PAGE', $rel);
}

require_once __DIR__ . '/functions.php';
