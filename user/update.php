<?php
// user/update.php - saves the profile form from profile.php
session_start();
include('../includes/config.php');
require_login();
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('profile.php');

$dir = __DIR__ . '/../uploads/users/';
$uid = (int)$_SESSION['user_id'];
$u = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM users WHERE user_id=?", [$uid]));

$form = ['full_name' => trim($_POST['full_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')];
$cur = $_POST['current_password'] ?? ''; $pw = $_POST['password'] ?? ''; $pw2 = $_POST['confirm'] ?? '';
$removePhoto = !empty($_POST['remove_photo']);
$errors = [];

if ($form['full_name'] === '') $errors[] = 'Full name is required.';
elseif (mb_strlen($form['full_name']) > 100) $errors[] = 'Full name is too long (max 100).';
if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 100) $errors[] = 'Enter a valid email address.';
else {
    $q = mysqli_execute_query($conn, "SELECT 1 FROM users WHERE (LOWER(email)=LOWER(?) OR LOWER(username)=LOWER(?)) AND user_id<>?", [$form['email'], $form['email'], $uid]);
    if (mysqli_fetch_row($q)) $errors[] = 'That email is already used by another account.';
}
if ($form['phone'] !== '' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $form['phone'])) $errors[] = 'Phone must look like 09123456789 or +639123456789.';
if ($pw !== '' || $pw2 !== '' || $cur !== '') {
    $curOk = password_verify($cur, $u['password'])
          || (password_get_info($u['password'])['algoName'] === 'unknown' && hash_equals($u['password'], $cur));    // old plain-text password
    if ($pw === '')              $errors[] = 'Type the new password you want.';
    elseif (strlen($pw) < 8)     $errors[] = 'New password must be at least 8 characters.';
    elseif ($pw !== $pw2)        $errors[] = 'New passwords do not match.';
    if ($cur === '')             $errors[] = 'Enter your current password to change it.';
    elseif (!$curOk)             $errors[] = 'Your current password is not correct.';
}
if ($pe = validate_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE])) $errors[] = $pe;

if ($errors) back_with_errors($errors, $form, 'profile.php');

$photo = save_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $dir, 'u');
if ($photo)                $photoVal = $photo;                 // a new photo was chosen
elseif ($removePhoto)      $photoVal = null;                   // "Remove my photo" ticked
else                       $photoVal = $u['photo'];            // keep the current one
$uname = ($u['username'] === $u['email']) ? $form['email'] : $u['username'];
if ($pw !== '') {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $ok = mysqli_execute_query($conn, "UPDATE users SET username=?, full_name=?, email=?, photo=?, password=? WHERE user_id=?",
        [$uname, $form['full_name'], $form['email'], $photoVal, $hash, $uid]);
} else {
    $ok = mysqli_execute_query($conn, "UPDATE users SET username=?, full_name=?, email=?, photo=? WHERE user_id=?",
        [$uname, $form['full_name'], $form['email'], $photoVal, $uid]);
}
if ($ok) {
    if ($u['photo'] && $u['photo'] !== $photoVal) @unlink($dir . $u['photo']);       // old photo is no longer used
    if ($u['customer_id']) {
        $ph = $form['phone'] !== '' ? $form['phone'] : null;
        mysqli_execute_query($conn, "UPDATE customers SET customer_name=?, email=?, phone=? WHERE customer_id=?",
            [$form['full_name'], $form['email'], $ph, $u['customer_id']]);
    }
    $_SESSION['username'] = $form['full_name']; $_SESSION['email'] = $form['email']; $_SESSION['photo'] = $photoVal;
    $msg = 'Profile updated.';
    if ($pw !== '') {
        remember_forget($uid);                                                       // other "kept logged in" browsers must log in again
        mail_password_changed($form['email'], $form['full_name']);                   // confirmation email (Mailtrap)
        $msg = 'Profile and password updated.';
    }
    $_SESSION['success'] = $msg;
    redirect('profile.php');
}
if ($photo) @unlink($dir . $photo);
back_with_errors(['Database error: ' . mysqli_error($conn)], $form, 'profile.php');
