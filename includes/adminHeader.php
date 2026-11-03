<?php
// includes/adminHeader.php - staff layout (sidebar). Used by every Admin / Cashier page.
require_login();                     // not logged in -> login page with a message

$role = $_SESSION['role'] ?? '';
$cur  = CURRENT_PAGE;                // e.g. "admin/orders.php"

// Only Admin and Cashier may use these pages (ordinary users = Customer are sent back)
if (!in_array($role, ['Admin', 'Cashier'], true)) {
    flash('err', 'Access denied. That page is for staff only.');
    redirect(home_for($role));
}

// Cashiers can only open New Order, Customer Orders and their own profile
if ($role === 'Cashier' && !in_array($cur, ['order/create.php', 'admin/orders.php', 'user/profile.php'], true)) {
    flash('err', 'Access denied. Cashiers can only open New Order and Customer Orders.');
    redirect(url('/order/create.php'));
}

if ($role === 'Cashier') {
    $menu = ['OPERATIONS' => ['order/create.php' => '🛒 New Order', 'admin/orders.php' => '🧾 Customer Orders']];
} else {
    $menu = ['OPERATIONS' => ['admin/orders.php' => '🧾 Customer Orders'],
     'INSIGHTS' => ['admin/index.php' => '📊 Dashboard', 'admin/reports.php' => '📈 Reports'],
     'CATALOG'  => ['category/index.php' => '🗂 Categories', 'product/index.php' => '☕ Products', 'inventory/index.php' => '📦 Inventory'],
     'MODERATION' => ['badword/index.php' => '🚫 Bad Words'],
     'PEOPLE'   => ['customer/index.php' => '👥 Customers', 'employee/index.php' => '🧑‍🍳 Employees', 'account/index.php' => '🔑 User Accounts']];
}

// a menu link is "active" on its own page and on the other pages of the same folder
function menu_active(string $link, string $cur): bool {
    if ($link === $cur) return true;
    return dirname($link) !== 'admin' && dirname($link) === dirname($cur);
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cup of Joe-rnal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="<?= BASE_URL ?>/assets/logo-sm.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/includes/style/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/includes/style/menu.css?v=<?= @filemtime(__DIR__ . '/style/menu.css') ?>"></head><body>
<div class="app">
<aside class="side">
  <div class="brand"><img src="<?= BASE_URL ?>/assets/logo-sm.png" alt="Cup of Joe-rnal logo"><span>Cup of<br>Joe-rnal</span></div>
  <?php foreach ($menu as $group => $items): ?>
    <small><?= $group ?></small>
    <?php foreach ($items as $f => $l): ?><a href="<?= BASE_URL . '/' . $f ?>" class="<?= menu_active($f, $cur) ? 'active' : '' ?>"><?= $l ?></a><?php endforeach; ?>
  <?php endforeach; ?>
  <div class="user"><?= avatar_html($_SESSION['photo'] ?? null, 28) ?> <?= h($_SESSION['username'] ?? 'admin') ?> (<?= h($_SESSION['role'] ?? 'Admin') ?>)<br><a href="<?= BASE_URL ?>/user/profile.php">My Profile</a> <a href="<?= BASE_URL ?>/user/logout.php">Logout</a></div>
</aside>
<main class="content">
