<?php
// includes/mail_config.php - email settings. Emails go to Mailtrap (an "email trap"): nothing reaches a real
// inbox, you read every message inside your Mailtrap dashboard. Perfect for testing sign-up / reset / order emails.
//
// 1. Sign up free at https://mailtrap.io -> Email Testing -> Inboxes -> your inbox -> "SMTP Settings".
// 2. Copy the Username and Password into the two lines below.
// 3. Done. While MAIL_USERNAME is empty, emails are skipped quietly and the site keeps working normally.
//
// (Don't upload your real Mailtrap password to a public repo.)

define('MAIL_HOST',       'sandbox.smtp.mailtrap.io');   // Mailtrap sandbox server (live.smtp.mailtrap.io is the real-sending one)
define('MAIL_PORT',       2525);                         // 2525, 587 or 25 (use 465 with MAIL_ENCRYPTION = 'ssl')
define('MAIL_ENCRYPTION', 'tls');                        // 'tls' = STARTTLS, 'ssl' = implicit TLS, '' = none
define('MAIL_USERNAME',   '');                           // <-- paste your Mailtrap username here
define('MAIL_PASSWORD',   '');                           // <-- paste your Mailtrap password here
define('MAIL_FROM',       'no-reply@cupofjoernal.test');
define('MAIL_FROM_NAME',  'Cup of Joe-rnal');
define('MAIL_VERIFY_TLS', false);                       // false = don't check the server certificate (fine for the Mailtrap sandbox; XAMPP often has no CA list). Use true with a real mail server.
define('MAIL_TIMEOUT',    8);                            // seconds to wait for the mail server before giving up

// Every order slip is also emailed to the shop so staff can see new Take Out / To Go / Deliver orders. Leave '' to turn the shop copy off.
define('MAIL_SHOP_TO',    'orders@cupofjoernal.test');
