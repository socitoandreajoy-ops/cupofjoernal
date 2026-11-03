<?php
// includes/functions.php - shared helpers: login guards, flash messages, image upload, bad-word filter
if (session_status() === PHP_SESSION_NONE) session_start();

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES); }

function url(string $path): string { return BASE_URL . $path; }

function redirect(string $url): void { header('Location: ' . $url); exit; }

/* ---------- messages (shown once by includes/alert.php, e.g. after a redirect) ---------- */
function flash(string $type, string $msg): void {
    if ($type === 'ok') $_SESSION['success'] = $msg;
    else                $_SESSION['message'] = $msg;
}

// Send the person back to a form with the error list and what they typed
function back_with_errors(array $errors, array $old, string $to): void {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $old;
    redirect($to);
}

// Values typed into a form that failed validation (read once)
function take_old(): array {
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $old;
}

/* ---------- roles ---------- */
function home_for(string $role): string {
    if ($role === 'Admin')    return url('/admin/index.php');
    if ($role === 'Cashier')  return url('/order/create.php');
    if ($role === 'Customer') return url('/index.php');
    return url('/user/login.php');
}

// Must be logged in (and the account must still be active), otherwise go to login with a message
function require_login(): void {
    global $conn;
    if (empty($_SESSION['user_id'])) {
        flash('err', 'Please log in to access that page.');
        redirect(url('/user/login.php'));
    }
    $uid = (int)$_SESSION['user_id'];
    $st = mysqli_prepare($conn, "SELECT is_active FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($st, 'i', $uid);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    if (!$row || (int)$row['is_active'] !== 1) {
        $_SESSION = [];
        flash('err', 'Your account is deactivated. Please contact the administrator.');
        redirect(url('/user/login.php'));
    }
}

// Must be logged in AND have one of the given roles ('Admin', 'Cashier', 'Customer')
function require_role(array $roles): void {
    require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        flash('err', 'Access denied. You do not have permission to open that page.');
        redirect(home_for($role));
    }
}

/* ---------- image upload helpers ---------- */
// Turns $_FILES['photos'] (multiple) into a list of single-file arrays; skips empty slots
function files_to_list(array $f): array {
    $out = [];
    if (!isset($f['name']) || !is_array($f['name'])) return $out;
    foreach ($f['name'] as $i => $n) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i],
                  'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

// Returns an error message, or null if the file is fine (or no file was chosen)
function validate_image(array $file): ?string {
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) return null;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return 'Photo is too large.';
    if ($err !== UPLOAD_ERR_OK) return 'Photo upload failed.';
    if ($file['size'] > 2 * 1024 * 1024) return 'Photo must be 2MB or smaller.';
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true))
        return 'Photo must be a JPG, PNG or WEBP image.';
    return null;
}

// Validates + moves the file into $dir. Returns the new filename, or null (and sets $err) on failure/no file
function save_image(array $file, string $dir, string $prefix, ?string &$err = null): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $err = validate_image($file);
    if ($err) return null;
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][getimagesize($file['tmp_name'])[2]];
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $name = $prefix . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name)) {
        $err = 'Could not save the uploaded photo.';
        return null;
    }
    return $name;
}

// Small round avatar (user photo or a default icon)
function avatar_html(?string $file, int $size = 32): string {
    $s = (int)$size;
    if ($file && is_file(__DIR__ . '/../uploads/users/' . $file))
        return '<img class="ava" style="width:' . $s . 'px;height:' . $s . 'px" src="' . BASE_URL . '/uploads/users/' . h($file) . '" alt="">';
    return '<span class="ava ava-def" style="width:' . $s . 'px;height:' . $s . 'px;line-height:' . $s . 'px">👤</span>';
}

/* ---------- bad word filter (regex) ---------- */
// The word list lives in the `bad_words` table and is edited by the Admin (Admin > Bad Words).
// If that table is missing (database not updated yet) the built-in starter list below is used instead.
function bad_words_default(): array {
    return ['fuck', 'shit', 'bitch', 'asshole', 'ass', 'bastard', 'damn', 'crap', 'dick', 'piss',
            'gago', 'tangina', 'putangina', 'bobo', 'tanga', 'ulol', 'pakyu', 'pakshet', 'leche',
            'lintik', 'tarantado', 'buwisit', 'siraulo'];
}

function bad_words_list(bool $refresh = false): array {
    global $conn;
    static $list = null;
    if ($list !== null && !$refresh) return $list;
    try {
        $res  = mysqli_query($conn, "SELECT word FROM bad_words ORDER BY word");
        $list = $res ? array_column(mysqli_fetch_all($res, MYSQLI_ASSOC), 'word') : bad_words_default();
    } catch (Throwable $e) {
        $list = bad_words_default();
    }
    return $list;
}

// Turns a list of words into one regex. Catches upper/lower case, repeated letters (fuuuck) and simple
// look-alikes (sh1t, @ss). Spaces inside a phrase match any amount of whitespace.
function bad_words_pattern(array $words): ?string {
    $look = ['a' => '[a@4]', 'e' => '[e3]', 'i' => '[i1!]', 'o' => '[o0]', 's' => '[s5$]', 't' => '[t7]', 'l' => '[l1]'];
    usort($words, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    $parts = [];
    foreach ($words as $w) {
        $w = mb_strtolower(trim((string)$w));
        if ($w === '') continue;
        $rx = '';
        foreach (preg_split('//u', $w, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $rx .= ($ch === ' ') ? '\s+' : ($look[$ch] ?? preg_quote($ch, '/')) . '+';
        }
        $parts[] = $rx;
    }
    if (!$parts) return null;
    return '/(?<![\p{L}\p{N}])(?:' . implode('|', $parts) . ')(?![\p{L}\p{N}])/iu';
}

// Masks bad words with asterisks. Because it reads the current list every time it is shown, words the
// Admin adds (or removes) apply to every review straight away.
function mask_bad_words(string $text): string {
    static $cache = [];
    $words = bad_words_list();
    $key = md5(implode("\n", $words));
    if (!array_key_exists($key, $cache)) $cache[$key] = bad_words_pattern($words);
    if ($cache[$key] === null) return $text;
    return preg_replace_callback($cache[$key], fn($m) => str_repeat('*', mb_strlen($m[0])), $text) ?? $text;
}

/* ---------- login session + "keep me logged in" ---------- */
const REMEMBER_COOKIE = 'coj_remember';
const REMEMBER_DAYS   = 30;

// Puts a users-table row into the session (used by the login form and by the remember-me cookie). Returns the role.
function login_user(array $u): string {
    global $conn;
    $role = ucfirst(strtolower($u['role']));            // Admin / Cashier / Customer

    // Cashiers are also rows in `employees` (matched by username) - used when saving orders
    $empId = null;
    if ($role === 'Cashier') {
        $q = mysqli_execute_query($conn, "SELECT employee_id FROM employees WHERE username = ? LIMIT 1", [$u['username']]);
        $empId = mysqli_fetch_row($q)[0] ?? null;
    }

    @session_regenerate_id(true);
    $_SESSION['user_id']     = (int)$u['user_id'];
    $_SESSION['username']    = $u['full_name'] ?: $u['username'];
    $_SESSION['email']       = $u['email'];
    $_SESSION['role']        = $role;
    $_SESSION['customer_id'] = $u['customer_id'];
    $_SESSION['employee_id'] = $empId;
    $_SESSION['photo']       = $u['photo'];
    return $role;
}

function remember_cookie_set(string $value, int $expires): void {
    @setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => BASE_URL !== '' ? BASE_URL : '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Called after a successful login when "Keep me logged in" is ticked
function remember_issue(int $uid): void {
    global $conn;
    $selector  = bin2hex(random_bytes(12));              // 24 hex chars, finds the row
    $validator = bin2hex(random_bytes(32));              // secret half, only its hash is stored
    $exp       = time() + REMEMBER_DAYS * 86400;
    try {
        mysqli_execute_query($conn, "DELETE FROM remember_tokens WHERE expires_at < NOW()");
        mysqli_execute_query($conn, "INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?,?,?,FROM_UNIXTIME(?))",
            [$uid, $selector, hash('sha256', $validator), $exp]);
        remember_cookie_set($selector . ':' . $validator, $exp);
    } catch (Throwable $e) { /* table not created yet: simply no remember-me */ }
}

// Logout / password change: forget this browser's cookie (and, with $uid, every remembered browser of that user)
function remember_forget(?int $uid = null): void {
    global $conn;
    try {
        if ($uid) mysqli_execute_query($conn, "DELETE FROM remember_tokens WHERE user_id=?", [$uid]);
        if (!empty($_COOKIE[REMEMBER_COOKIE])) {
            $sel = explode(':', (string)$_COOKIE[REMEMBER_COOKIE])[0];
            mysqli_execute_query($conn, "DELETE FROM remember_tokens WHERE selector=?", [$sel]);
        }
    } catch (Throwable $e) {}
    if (!empty($_COOKIE[REMEMBER_COOKIE])) remember_cookie_set('', time() - 3600);
}

// Runs on every page: no session but a valid remember-me cookie -> log the person back in
function remember_restore(): void {
    global $conn;
    if (!empty($_SESSION['user_id']) || empty($_COOKIE[REMEMBER_COOKIE])) return;
    $parts = explode(':', (string)$_COOKIE[REMEMBER_COOKIE]);
    if (count($parts) !== 2 || !ctype_xdigit($parts[0]) || !ctype_xdigit($parts[1])) { remember_cookie_set('', time() - 3600); return; }
    try {
        $q = mysqli_execute_query($conn, "SELECT t.token_id, t.validator_hash, u.* FROM remember_tokens t
                                          JOIN users u ON u.user_id = t.user_id
                                          WHERE t.selector = ? AND t.expires_at > NOW()", [$parts[0]]);
        $row = mysqli_fetch_assoc($q);
        if (!$row) { remember_cookie_set('', time() - 3600); return; }
        if (!hash_equals($row['validator_hash'], hash('sha256', $parts[1])) || (int)$row['is_active'] !== 1) {
            mysqli_execute_query($conn, "DELETE FROM remember_tokens WHERE token_id=?", [$row['token_id']]);
            remember_cookie_set('', time() - 3600);
            return;
        }
        // use a fresh secret every time the cookie is used
        $validator = bin2hex(random_bytes(32));
        $exp = time() + REMEMBER_DAYS * 86400;
        mysqli_execute_query($conn, "UPDATE remember_tokens SET validator_hash=?, expires_at=FROM_UNIXTIME(?) WHERE token_id=?",
            [hash('sha256', $validator), $exp, $row['token_id']]);
        remember_cookie_set($parts[0] . ':' . $validator, $exp);
        login_user($row);
    } catch (Throwable $e) { /* table not created yet */ }
}

remember_restore();

/* ---------- order type (Take Out / To Go / Deliver) ---------- */

function order_types(): array {
    return ['Take Out' => 'Customer picks up and leaves', 'To Go' => 'Packed to carry away', 'Deliver' => 'Brought to the customer\'s address'];
}

// Reads + checks order_type / delivery_address / contact_phone from a POST array.
// Returns [type, address|null, phone|null, errors[]]
function read_order_type(array $post): array {
    $errors = [];
    $type = (string)($post['order_type'] ?? '');
    if (!array_key_exists($type, order_types())) { $errors[] = 'Please choose Take Out, To Go or Deliver.'; $type = 'Take Out'; }
    $addr  = trim((string)($post['delivery_address'] ?? ''));
    $phone = trim((string)($post['contact_phone'] ?? ''));
    if ($type === 'Deliver') {
        if ($addr === '') $errors[] = 'Enter the delivery address.';
        elseif (mb_strlen($addr) > 255) $errors[] = 'Delivery address is too long (max 255).';
        if ($phone === '') $errors[] = 'Enter a contact number for the delivery.';
    }
    if ($phone !== '' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) $errors[] = 'Contact number must look like 09123456789 or +639123456789.';
    if ($type !== 'Deliver') $addr = '';
    return [$type, $addr !== '' ? $addr : null, $phone !== '' ? $phone : null, $errors];
}



/* ---------- guest customers ---------- */
// Guest ID = yyyyddmm-HHmmss (year, day, month - 24-hour time), e.g. 20260311-143005 for 3 Nov 2026 at 2:30:05 PM.
// If two guests are created in the same second, -2, -3 ... is added so every ID stays unique.
function guest_id(mysqli $conn): string {
    $base = date('Ydm-His');
    $id = $base; $n = 1;
    while (mysqli_fetch_row(mysqli_execute_query($conn, "SELECT 1 FROM customers WHERE customer_name=?", ['Guest ' . $id]))) {
        $n++; $id = $base . '-' . $n;
    }
    return $id;
}

/* ---------- admin list search (live filter, no page reload) ---------- */
// Prints a search box that hides table rows that don't match what's typed.
// $tables = CSS selector of the table(s) to filter, e.g. '#catTbl' or '#lowTbl, #allTbl'
function search_box(string $tables, string $placeholder = 'Search…'): void {
    static $n = 0; $n++;
    $id = 'tsearch' . $n;
    echo '<div class="tsearch"><input type="search" id="' . $id . '" placeholder="🔍 ' . h($placeholder) . '" autocomplete="off" aria-label="' . h($placeholder) . '">'
       . '<span class="tsearch-count" id="' . $id . 'n"></span></div>';
    echo '<script>(function(){var inp=document.getElementById(' . json_encode($id) . '),cnt=document.getElementById(' . json_encode($id . 'n') . '),sel=' . json_encode($tables) . ';'
       . 'function run(){var t=inp.value.trim().toLowerCase(),shown=0,total=0;'
       . 'document.querySelectorAll(sel).forEach(function(tb){var none=tb.querySelector("tr.tsearch-none");if(none)none.remove();var vis=0,rows=0;'
       . 'tb.querySelectorAll("tr").forEach(function(r){if(r.querySelector("th")||r.classList.contains("tsearch-none")||r.querySelector("td.empty"))return;rows++;'
       . 'var ok=t===""||r.textContent.toLowerCase().indexOf(t)!==-1;r.style.display=ok?"":"none";if(ok)vis++;});'
       . 'shown+=vis;total+=rows;if(t!==""&&rows>0&&vis===0){var cols=(tb.querySelector("tr")||{children:[]}).children.length||1;var tr=document.createElement("tr");tr.className="tsearch-none";tr.innerHTML="<td class=\"empty\" colspan=\""+cols+"\">No matches found.</td>";(tb.tBodies[0]||tb).appendChild(tr);}});'
       . 'cnt.textContent=t===""?"":shown+" of "+total+" shown";}'
       . 'inp.addEventListener("input",run);})();</script>';
}

require_once __DIR__ . '/mailer.php';
