<?php
// includes/mailer.php - tiny SMTP mailer (no Composer needed) + HTML email templates.
// Usage:  send_mail('someone@example.com', 'Someone', 'Subject', '<p>HTML body</p>');   // true / false
// It never throws: if the mail server is down or not configured, the site carries on and the reason is
// available from mail_last_error() (and written to the PHP error log).
require_once __DIR__ . '/mail_config.php';

function mail_enabled(): bool { return MAIL_USERNAME !== '' && MAIL_HOST !== ''; }

function mail_last_error(?string $set = null): string {
    static $err = '';
    if ($set !== null) $err = $set;
    return $err;
}

// Reads one (possibly multi-line) SMTP reply. Returns [code, text].
function smtp_read($fp): array {
    $text = ''; $code = 0;
    while (($line = fgets($fp, 1024)) !== false) {
        $text .= $line;
        $code = (int)substr($line, 0, 3);
        if (strlen($line) < 4 || $line[3] !== '-') break;       // "250-..." means more lines follow, "250 ..." is the last
    }
    return [$code, trim($text)];
}

// Sends one command and checks that the reply code is one of $ok. Throws RuntimeException otherwise.
function smtp_cmd($fp, string $cmd, array $ok, ?string $shown = null): string {
    if ($cmd !== '') fwrite($fp, $cmd . "\r\n");
    [$code, $text] = smtp_read($fp);
    if (!in_array($code, $ok, true)) {
        throw new RuntimeException('SMTP error after "' . ($shown ?? $cmd) . '": ' . ($text !== '' ? $text : 'no reply (timeout?)'));
    }
    return $text;
}

function mail_clean(string $s): string { return trim(preg_replace('/[\r\n]+/', ' ', $s)); }      // blocks header injection

function mail_encode_header(string $s): string {
    $s = mail_clean($s);
    return preg_match('/^[\x20-\x7e]*$/', $s) ? $s : '=?UTF-8?B?' . base64_encode($s) . '?=';
}

function send_mail(string $to, string $toName, string $subject, string $html, string $text = ''): bool {
    mail_last_error('');
    if (!mail_enabled()) { mail_last_error('Mail is not set up yet (add your Mailtrap login in includes/mail_config.php).'); return false; }
    $to = mail_clean($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { mail_last_error('Invalid recipient address.'); return false; }
    if ($text === '') $text = trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/tr|\/h\d)\b[^>]*>/i', "\n", $html)), ENT_QUOTES, 'UTF-8'));

    $fp = null;
    try {
        $remote = (MAIL_ENCRYPTION === 'ssl' ? 'ssl://' : 'tcp://') . MAIL_HOST . ':' . MAIL_PORT;
        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => MAIL_VERIFY_TLS, 'verify_peer_name' => MAIL_VERIFY_TLS, 'allow_self_signed' => !MAIL_VERIFY_TLS,
        ]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, MAIL_TIMEOUT, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) throw new RuntimeException("Could not reach the mail server ($errstr).");
        stream_set_timeout($fp, MAIL_TIMEOUT);

        smtp_cmd($fp, '', [220]);                                         // greeting
        $me = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? '') ?: 'localhost';
        smtp_cmd($fp, "EHLO $me", [250]);
        if (MAIL_ENCRYPTION === 'tls') {
            smtp_cmd($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('Could not start TLS with the mail server.');
            smtp_cmd($fp, "EHLO $me", [250]);
        }
        if (MAIL_USERNAME !== '') {
            smtp_cmd($fp, 'AUTH LOGIN', [334]);
            smtp_cmd($fp, base64_encode(MAIL_USERNAME), [334], '(username)');
            smtp_cmd($fp, base64_encode(MAIL_PASSWORD), [235], '(password)');
        }
        smtp_cmd($fp, 'MAIL FROM:<' . mail_clean(MAIL_FROM) . '>', [250]);
        smtp_cmd($fp, "RCPT TO:<$to>", [250, 251]);
        smtp_cmd($fp, 'DATA', [354]);

        $boundary = 'coj_' . bin2hex(random_bytes(8));
        $toHeader = ($toName !== '' ? mail_encode_header($toName) . ' ' : '') . "<$to>";
        $msg  = 'Date: ' . date('r') . "\r\n";
        $msg .= 'From: ' . mail_encode_header(MAIL_FROM_NAME) . ' <' . mail_clean(MAIL_FROM) . ">\r\n";
        $msg .= "To: $toHeader\r\n";
        $msg .= 'Subject: ' . mail_encode_header($subject) . "\r\n";
        $msg .= 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $me . ">\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
        $msg .= "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text)) . "\r\n";
        $msg .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n";
        $msg .= "--$boundary--\r\n";
        fwrite($fp, $msg . "\r\n.\r\n");                                  // base64 lines can never start with "." so no dot-stuffing needed
        smtp_cmd($fp, '', [250]);
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    } catch (Throwable $e) {
        if (is_resource($fp)) @fclose($fp);
        mail_last_error($e->getMessage());
        error_log('[Cup of Joe-rnal mail] ' . $e->getMessage());
        return false;
    }
}

/* ---------- links + templates ---------- */

// Full web address (http://localhost/cupofjoernal/user/reset.php) for use inside emails
function absolute_url(string $path): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) $host = 'localhost';          // ignore a forged Host header
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https://' : 'http://') . $host . BASE_URL . $path;
}

// Wraps content in the coffee-shop email layout. $button = ['Label', 'https://…'] or null.
function email_layout(string $heading, string $bodyHtml, ?array $button = null): string {
    $btn = '';
    if ($button) {
        $btn = '<p style="margin:26px 0 8px"><a href="' . h($button[1]) . '" style="background:#6b2a08;color:#ffffff;text-decoration:none;font-weight:600;padding:13px 26px;border-radius:12px;display:inline-block">' . h($button[0]) . '</a></p>';
    }
    return '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#ebdcd0;font-family:Arial,Helvetica,sans-serif;color:#5a2e16">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
        . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden">'
        . '<tr><td style="background:#6b2a08;color:#ffffff;padding:18px 28px;font-size:18px;font-weight:bold">&#9749; Cup of Joe-rnal</td></tr>'
        . '<tr><td style="padding:28px;font-size:15px;line-height:1.6">'
        . '<h2 style="margin:0 0 14px;color:#6b2a08;font-size:22px">' . h($heading) . '</h2>' . $bodyHtml . $btn
        . '</td></tr>'
        . '<tr><td style="padding:16px 28px;background:#fbf1ea;color:#9e8476;font-size:12px">This is an automated message from Cup of Joe-rnal. Every cup tells a story.</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function mail_welcome(string $email, string $name): bool {
    $html = email_layout('Welcome, ' . $name . '!',
        '<p>Your Cup of Joe-rnal account is ready. Every &#8369;50 you spend earns you a loyalty point, so start sipping!</p>',
        ['Log in and order', absolute_url('/user/login.php')]);
    return send_mail($email, $name, 'Welcome to Cup of Joe-rnal', $html);
}

function mail_password_reset(string $email, string $name, string $link): bool {
    $html = email_layout('Reset your password',
        '<p>Hi ' . h($name) . ', we received a request to reset your password. This link works for 60 minutes and can be used once.</p>'
        . '<p style="color:#9e8476;font-size:13px">If you did not ask for this, you can safely ignore this email - your password stays the same.</p>',
        ['Choose a new password', $link]);
    return send_mail($email, $name, 'Reset your Cup of Joe-rnal password', $html);
}

function mail_password_changed(string $email, string $name): bool {
    $html = email_layout('Your password was changed',
        '<p>Hi ' . h($name) . ', this is a confirmation that the password on your Cup of Joe-rnal account was just changed.</p>'
        . '<p style="color:#9e8476;font-size:13px">If this was not you, reset your password right away or contact the shop.</p>',
        ['Go to login', absolute_url('/user/login.php')]);
    return send_mail($email, $name, 'Your password was changed', $html);
}

// $items = [['name' => 'Latte', 'qty' => 2, 'subtotal' => 240.0], ...]
function mail_order_placed(string $email, string $name, int $orderId, array $items, float $total): bool {
    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #ebd8cb">' . (int)$it['qty'] . ' &times; ' . h($it['name']) . '</td>'
               . '<td align="right" style="padding:8px 0;border-bottom:1px solid #ebd8cb">&#8369;' . number_format((float)$it['subtotal'], 2) . '</td></tr>';
    }
    $body = '<p>Thanks, ' . h($name) . '! Your order <b>#' . $orderId . '</b> has been placed. Please show your order slip to the cashier to pay.</p>'
          . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">' . $rows
          . '<tr><td style="padding:12px 0;font-weight:bold">Total due</td><td align="right" style="padding:12px 0;font-weight:bold;color:#ff6a13">&#8369;' . number_format($total, 2) . '</td></tr></table>';
    return send_mail($email, $name, "Order #$orderId received - Cup of Joe-rnal", email_layout('Order received', $body, ['View my orders', absolute_url('/index.php')]));
}


// Emails the order slip for order $oid to the customer (if they have an email) and a copy to the shop (MAIL_SHOP_TO).
// Never throws - the order is already saved. Returns the number of emails sent.
function mail_order_slip(mysqli $conn, int $oid): int {
    try {
        $o = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT o.*, c.customer_name, c.email, c.phone FROM orders o JOIN customers c ON c.customer_id=o.customer_id WHERE o.order_id=?", [$oid]));
        if (!$o) return 0;
        $its = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT oi.quantity, oi.unit_price, p.product_name FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=?", [$oid]), MYSQLI_ASSOC);

        $rows = '';
        foreach ($its as $it) {
            $rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #ebd8cb">' . (int)$it['quantity'] . ' &times; ' . h($it['product_name']) . '</td>'
                   . '<td align="right" style="padding:8px 0;border-bottom:1px solid #ebd8cb">&#8369;' . number_format($it['quantity'] * $it['unit_price'], 2) . '</td></tr>';
        }
        $type = $o['order_type'] ?? 'Take Out';
        $meta = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-bottom:14px">'
              . '<tr><td style="padding:3px 0;color:#9e8476">Order</td><td align="right"><b>#' . $oid . '</b></td></tr>'
              . '<tr><td style="padding:3px 0;color:#9e8476">Date</td><td align="right">' . h($o['order_date']) . '</td></tr>'
              . '<tr><td style="padding:3px 0;color:#9e8476">Customer</td><td align="right">' . h($o['customer_name']) . '</td></tr>'
              . '<tr><td style="padding:3px 0;color:#9e8476">Order type</td><td align="right"><b>' . h($type) . '</b></td></tr>';
        if ($type === 'Deliver') {
            $meta .= '<tr><td style="padding:3px 0;color:#9e8476">Deliver to</td><td align="right">' . h($o['delivery_address']) . '</td></tr>';
        }
        $ph = $o['contact_phone'] ?: $o['phone'];
        if ($ph) $meta .= '<tr><td style="padding:3px 0;color:#9e8476">Contact</td><td align="right">' . h($ph) . '</td></tr>';
        $meta .= '</table>';

        $totalRow = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">' . $rows
                  . '<tr><td style="padding:12px 0;font-weight:bold">Total due</td><td align="right" style="padding:12px 0;font-weight:bold;color:#ff6a13">&#8369;' . number_format((float)$o['total_amount'], 2) . '</td></tr></table>';
        $note = $type === 'Deliver'
              ? '<p style="color:#9e8476;font-size:13px">We\'ll bring your order to the address above. Payment is collected on delivery.</p>'
              : '<p style="color:#9e8476;font-size:13px">Please show this slip to the cashier to pay and collect your order (' . h($type) . ').</p>';

        $sent = 0;
        if (!empty($o['email'])) {
            $html = email_layout('Your order slip', '<p>Thanks, ' . h($o['customer_name']) . '! Here is your order slip.</p>' . $meta . $totalRow . $note, ['View my orders', absolute_url('/index.php')]);
            if (send_mail($o['email'], $o['customer_name'], "Order slip #$oid ($type) - Cup of Joe-rnal", $html)) $sent++;
        }
        if (MAIL_SHOP_TO !== '') {
            $html = email_layout("New $type order #$oid", $meta . $totalRow, ['Open orders', absolute_url('/admin/orders.php')]);
            if (send_mail(MAIL_SHOP_TO, 'Cup of Joe-rnal Orders', "[$type] New order #$oid - " . $o['customer_name'], $html)) $sent++;
        }
        return $sent;
    } catch (Throwable $e) {
        error_log('[Cup of Joe-rnal mail] order slip: ' . $e->getMessage());
        return 0;
    }
}
