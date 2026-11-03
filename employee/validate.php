<?php
// employee/validate.php - shared by store.php and update.php. Returns a list of error messages.
function validate_employee($conn, array $f, string $pw, int $id = 0): array {
    $errors = [];
    $roles = ['Barista', 'Cashier', 'Manager'];

    if ($f['employee_name'] === '')            $errors[] = 'Employee name is required.';
    elseif (strlen($f['employee_name']) > 100) $errors[] = 'Employee name is too long (max 100).';
    if (!in_array($f['role'], $roles, true))   $errors[] = 'Please select a role.';

    $isCashier = $f['role'] === 'Cashier';

    // does this employee already have a password?
    $hasPw = false;
    if ($id > 0) {
        $cur   = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT password FROM employees WHERE employee_id = ?", [$id]));
        $hasPw = !empty($cur['password'] ?? '');
    }

    if ($isCashier || $f['username'] !== '') {
        if (!preg_match('/^[A-Za-z0-9._@-]{4,50}$/', $f['username'])) {
            $errors[] = 'Username must be 4-50 characters (letters, numbers, . _ @ - only).';
        } else {
            $q = mysqli_execute_query($conn, "SELECT 1 FROM employees WHERE LOWER(username) = LOWER(?) AND employee_id <> ?", [$f['username'], $id]);
            if (mysqli_fetch_row($q)) $errors[] = 'That username is already taken.';
        }
    }
    if ($pw !== '' && strlen($pw) < 6)  $errors[] = 'Password must be at least 6 characters.';
    if ($isCashier && $pw === '' && !$hasPw) $errors[] = 'Set a password so this cashier can log in.';
    return $errors;
}
