<?php
// account/helpers.php - small helpers shared by the account pages

// how many OTHER active admins exist (so the last admin can't be removed)
function other_active_admins($conn, int $id): int {
    $q = mysqli_execute_query($conn, "SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1 AND user_id<>?", [$id]);
    return (int)(mysqli_fetch_row($q)[0] ?? 0);
}

function get_user($conn, int $id) {
    return mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM users WHERE user_id=?", [$id]));
}

// customers need a row in `customers` so they can order / review
function ensure_customer($conn, int $uid): void {
    $u = get_user($conn, $uid);
    if (!$u || $u['role'] !== 'customer' || $u['customer_id']) return;
    $n = $u['full_name'] ?: $u['username']; $em = $u['email'];
    mysqli_execute_query($conn, "INSERT INTO customers (customer_name, email) VALUES (?, ?)", [$n, $em]);
    $cid = mysqli_insert_id($conn);
    mysqli_execute_query($conn, "UPDATE users SET customer_id=? WHERE user_id=?", [$cid, $uid]);
}

// Checks the add / edit form. $cur = the existing user (or null for a new one). Returns a list of error messages.
function validate_account($conn, array $f, string $pw, int $id, ?array $cur, int $me): array {
    $roles  = ['admin', 'cashier', 'customer'];
    $errors = [];
    if ($id && !$cur) $errors[] = 'User not found.';
    if ($f['full_name'] === '') $errors[] = 'Full name is required.';
    elseif (strlen($f['full_name']) > 100) $errors[] = 'Full name is too long (max 100).';
    if ($f['email'] === '') $errors[] = 'Email is required.';
    elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 100) $errors[] = 'Enter a valid email address.';
    else {
        $q = mysqli_execute_query($conn, "SELECT 1 FROM users WHERE (LOWER(email)=LOWER(?) OR LOWER(username)=LOWER(?)) AND user_id<>?", [$f['email'], $f['email'], $id]);
        if (mysqli_fetch_row($q)) $errors[] = 'That email is already used by another account.';
    }
    if (!in_array($f['role'], $roles, true)) $errors[] = 'Please choose a role.';
    if (!$id && $pw === '') $errors[] = 'Password is required for a new user.';
    if ($pw !== '' && strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    // role rules for an existing user
    if ($cur && $cur['role'] !== $f['role']) {
        if (!(int)$cur['is_active']) $errors[] = 'Only an active user\'s role can be changed. Activate the user first.';
        if ($id === $me) $errors[] = 'You cannot change your own role.';
        elseif ($cur['role'] === 'admin' && other_active_admins($conn, $id) < 1) $errors[] = 'There must be at least one active admin.';
    }
    if ($pe = validate_image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE])) $errors[] = $pe;
    return $errors;
}
