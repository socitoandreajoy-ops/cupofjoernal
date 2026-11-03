# Cup of Joe-rnal

Coffee-shop ordering system (PHP + MySQL/MariaDB) - organized like `itim211-2a-shop`.

## How to run (XAMPP)
1. Copy this folder into `htdocs` (any folder name works, e.g. `htdocs/cupofjoernal`).
2. Start **Apache** and **MySQL** in XAMPP.
3. Open **phpMyAdmin** -> **Import** -> choose `database/cupofjoernal.sql` -> **Go**
   (it creates the `cupofjoernal` database for you).
4. Open `http://localhost/cupofjoernal/` and log in.

> **Already imported the older database?** Don't re-import (you'd lose data). Import `database/update_v2.sql` instead -
> it only adds the 3 new tables (bad words, password resets, "keep me logged in") and is safe to run twice.

| Role     | Email                    | Password     |
|----------|--------------------------|--------------|
| Admin    | admin@cupofjoernal.com   | Admin@123    |
| Cashier  | clowie@cupofjoernal.com  | (the password set in Employees) |
| Cashier  | andrea@cupofjoernal.com  | (the password set in Employees) |
| Customer | customer1@example.com    | password123  |

DB settings (host / user / password / name) are in `includes/config.php`.

## Email trap (Mailtrap)
The site sends a **welcome email** (sign-up), an **order confirmation** (checkout), a **password-reset link** (Forgot password) and a
**password-changed notice**. They all go to [Mailtrap](https://mailtrap.io), so nothing reaches a real inbox - you read them in Mailtrap.
1. Make a free Mailtrap account -> **Email Testing -> Inboxes -> your inbox -> SMTP Settings**.
2. Paste the **Username** and **Password** into `includes/mail_config.php`.
3. Sign up a new customer / place an order / click *Forgot password?* on the login page, then open your Mailtrap inbox.

Until the username is filled in, emails are skipped quietly and everything else works as normal. If an email does not arrive, the
reason is written to PHP's error log (XAMPP: `xampp/apache/logs/error.log`).

## What's new in v2
| Feature | Where |
|---|---|
| Product page with **big photo + clickable thumbnails** (arrows / keyboard / swipe) and an *Order now* button | `product/show.php` |
| Admin can pick **several photos** at once, with a live preview | Products -> add / edit |
| Nicer **profile editing** for Admin, Cashier and Customer (photo preview, remove photo, password strength, current-password check) | `user/profile.php` |
| **Forgot password** by email + **Keep me logged in** (30 days) on the login page | `user/forgot.php`, `user/reset.php`, `user/login.php` |
| **Latest customer reviews** on the customer homepage | `index.php` |
| **Clickable star rating**; submitting with no stars shows a message under the stars | `product/show.php` |
| Admin-editable **bad words list** (add / remove / restore / test) - used to hide foul words in reviews | Admin sidebar -> Bad Words (`badword/`) |


## Folders
```
index.php, checkout.php   customer page (menu + "Your order") and placing the order
includes/                 config, functions, mail_config + mailer, headers, footer, alert, shared menu, style/ (css)
user/                     login, register, store, logout, forgot, reset, profile, update
admin/                    dashboard (index), orders, reports
category/ product/ customer/ employee/ account/ inventory/ badword/
                          index (list + form) + store / update / delete
order/                    create (staff new order), store, slip (print)
database/                 cupofjoernal.sql  (import in phpMyAdmin)
assets/  uploads/         logo/background, uploaded photos
```
