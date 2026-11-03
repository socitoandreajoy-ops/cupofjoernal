<?php
// product/show.php - product details: photo gallery (big photo + thumbnails), order button, customer reviews
// (reviews are saved by review_store.php / review_delete.php)
session_start();
include('../includes/config.php');
require_login();
mysqli_report(MYSQLI_REPORT_OFF);

$pid  = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];
$cid  = (int)($_SESSION['customer_id'] ?? 0);

$q = mysqli_execute_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id WHERE p.product_id=?", [$pid]);
$p = mysqli_fetch_assoc($q);
if (!$p) { flash('err', 'Product not found.'); redirect(home_for($role)); }

// Has this customer ordered (availed) this product? (cancelled orders do not count)
$canReview = false;
if ($role === 'Customer' && $cid > 0) {
    $q = mysqli_execute_query($conn, "SELECT 1 FROM orders o JOIN order_items oi ON oi.order_id=o.order_id
                                      WHERE o.customer_id=? AND oi.product_id=? AND o.status<>'Cancelled' LIMIT 1", [$cid, $pid]);
    $canReview = (bool)mysqli_fetch_row($q);
}

$mine = null;
if ($cid) $mine = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM reviews WHERE product_id=? AND customer_id=?", [$pid, $cid]));
$form = ['rating' => $mine['rating'] ?? '', 'comment' => $mine['comment'] ?? '', 'rating_error' => '', 'comment_error' => ''];
$form = array_merge($form, take_old());     // what was typed (and the messages) if the review failed validation

// photos (first one is the cover)
$photos = array_column(mysqli_fetch_all(mysqli_execute_query($conn, "SELECT filename FROM product_photos WHERE product_id=? ORDER BY photo_id", [$pid]), MYSQLI_ASSOC), 'filename');
$photos = array_values(array_filter($photos, fn($f) => is_file(__DIR__ . '/../uploads/products/' . $f)));

// reviews (newest first) + average + how many of each star
$reviews = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT r.*, c.customer_name, u.photo
                            FROM reviews r JOIN customers c ON c.customer_id=r.customer_id
                            LEFT JOIN users u ON u.customer_id=r.customer_id
                            WHERE r.product_id=? ORDER BY COALESCE(r.updated_at, r.created_at) DESC", [$pid]), MYSQLI_ASSOC);
$avg  = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) { $k = (int)$r['rating']; if (isset($dist[$k])) $dist[$k]++; }
$stars = fn($n) => str_repeat('★', (int)$n) . str_repeat('☆', 5 - (int)$n);

$stock    = (int)$p['stock_quantity'];
$stockCls = $stock <= 0 ? 'out' : ($stock <= 10 ? 'low' : 'ok');
$stockTxt = $stock <= 0 ? 'Sold out' : ($stock <= 10 ? "Only $stock left" : "In stock: $stock");
$photoUrl = fn($f) => BASE_URL . '/uploads/products/' . rawurlencode($f);

$back  = $role === 'Admin' ? url('/product/index.php') : home_for($role);
$title = $p['product_name'] . ' – Cup of Joe-rnal';
include('../includes/header.php');
?>
<div class="top-bar">
  <div class="brand"><img src="<?= BASE_URL ?>/assets/logo-sm.png" alt=""><span>Cup of Joe-rnal</span></div>
  <div><a class="btn alt" href="<?= BASE_URL ?>/user/profile.php">My Profile</a> <a class="btn alt" href="<?= BASE_URL ?>/user/logout.php">Logout</a></div>
</div>

<a class="back-pill" href="<?= $back ?>">← Back to <?= $role === 'Admin' ? 'products' : 'menu' ?></a>

<?php include('../includes/alert.php'); ?>

<div class="pd">
  <!-- photos -->
  <div class="pd-gallery">
    <div class="pd-main" id="pdBox">
      <?php if ($photos): ?>
        <img id="pdMain" src="<?= $photoUrl($photos[0]) ?>" alt="<?= h($p['product_name']) ?>">
        <?php if (count($photos) > 1): ?>
          <button type="button" class="pd-nav prev" id="pdPrev" aria-label="Previous photo">‹</button>
          <button type="button" class="pd-nav next" id="pdNext" aria-label="Next photo">›</button>
          <span class="pd-count" id="pdCount">1 / <?= count($photos) ?></span>
        <?php endif; ?>
      <?php else: ?>
        <div class="pd-noimg" aria-hidden="true">☕</div>
      <?php endif; ?>
    </div>
    <?php if (count($photos) > 1): ?>
    <div class="pd-thumbs">
      <?php foreach ($photos as $i => $f): ?>
        <button type="button" class="pd-thumb<?= $i === 0 ? ' active' : '' ?>" data-full="<?= $photoUrl($f) ?>" aria-label="Show photo <?= $i + 1 ?> of <?= count($photos) ?>"><img src="<?= $photoUrl($f) ?>" alt="" loading="lazy"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- details -->
  <div class="pd-info">
    <h1 class="pd-name"><?= h($p['product_name']) ?></h1>
    <div class="pd-sub">
      <span><?= h($p['category_name'] ?: 'Other') ?></span>
      <?php if ($reviews): ?>
        <span>·</span><a href="#reviews"><span class="stars"><?= $stars(round($avg)) ?></span> <?= number_format($avg, 1) ?> (<?= count($reviews) ?>)</a>
      <?php else: ?>
        <span>·</span><a href="#reviews">No reviews yet</a>
      <?php endif; ?>
    </div>

    <div class="pd-price">₱<?= number_format($p['price'], 2) ?></div>
    <div class="pd-stock <?= $stockCls ?>"><?= h($stockTxt) ?></div>

    <?php if ($role === 'Customer' && $stock > 0): ?>
      <form class="pd-buy" method="post" action="<?= BASE_URL ?>/checkout.php" novalidate>
        <input type="hidden" name="place_order" value="1">
        <div class="step">
          <button type="button" onclick="pdQty(-1)" aria-label="Less">−</button>
          <input type="number" id="pdQty" name="qty[<?= $pid ?>]" min="1" max="<?= $stock ?>" value="1" aria-label="Quantity">
          <button type="button" onclick="pdQty(1)" aria-label="More">+</button>
        </div>
        <button type="submit" class="pd-order">Order now</button>
      </form>
      <p class="pd-hint">Your order slip appears right after. Show it to the cashier to pay. Want more than one item? Pick them all from the <a href="<?= $back ?>">menu</a>.</p>
    <?php elseif ($role === 'Customer'): ?>
      <p class="pd-hint">This item is sold out for now. Please check back soon!</p>
    <?php elseif ($role === 'Admin'): ?>
      <a class="btn" href="<?= BASE_URL ?>/product/index.php?edit=<?= $pid ?>">Edit this product</a>
    <?php endif; ?>
  </div>
</div>

<div class="panel" id="reviews"><b>Customer reviews</b>
<?php if ($reviews): ?>
  <div class="rv-sum">
    <div class="rv-big"><b><?= number_format($avg, 1) ?></b><span class="stars"><?= $stars(round($avg)) ?></span><small><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></small></div>
    <div class="rv-bars">
      <?php foreach ($dist as $star => $cnt): ?>
        <div class="rv-bar"><span><?= $star ?> ★</span><i><u style="width:<?= round($cnt / count($reviews) * 100) ?>%"></u></i><span><?= $cnt ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php foreach ($reviews as $r): $own = $cid && (int)$r['customer_id'] === $cid; ?>
  <div class="review">
    <?= avatar_html($r['photo'], 38) ?>
    <div>
      <div><span class="stars"><?= $stars($r['rating']) ?></span> <b><?= h($r['customer_name']) ?></b>
        <small class="sub"> · <?= h(date('M j, Y', strtotime($r['updated_at'] ?: $r['created_at']))) ?><?= $r['updated_at'] ? ' (edited)' : '' ?></small></div>
      <p><?= nl2br(h(mask_bad_words($r['comment']))) ?></p>
      <?php if ($own || $role === 'Admin'): ?>
        <form method="post" action="review_delete.php" style="display:inline" onsubmit="return confirm('Delete this review?')"><input type="hidden" name="product_id" value="<?= $pid ?>"><input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>"><button class="btn sm del">Delete</button></form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; if (!$reviews): ?><p class="sub">Be the first to review this product.</p><?php endif; ?>
</div>

<?php if ($role === 'Customer'): ?>
<div class="panel" id="write-review"><b><?= $mine ? 'Update your review' : 'Write a review' ?></b>
<?php if ($canReview): ?>
  <form method="post" action="review_store.php" id="rvForm" novalidate>
    <input type="hidden" name="product_id" value="<?= $pid ?>">

    <label style="font-weight:600;font-size:.84rem;color:var(--brown)">Your rating</label>
    <div class="star-row">
      <div class="star-wrap<?= $form['rating_error'] ? ' invalid' : '' ?>" id="starWrap">
        <div class="star-input" role="radiogroup" aria-label="Rating from 1 to 5 stars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= (string)$form['rating'] === (string)$i ? 'checked' : '' ?>>
            <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>"><span class="sr"><?= $i ?> star<?= $i === 1 ? '' : 's' ?></span>★</label>
          <?php endfor; ?>
        </div>
      </div>
      <span class="star-text" id="starText" aria-live="polite"></span>
    </div>
    <p class="field-err" id="ratingErr" role="alert" <?= $form['rating_error'] ? '' : 'hidden' ?>><?= h($form['rating_error'] ?: 'Please select a star rating before submitting.') ?></p>

    <label for="comment" style="margin-top:14px;font-weight:600;font-size:.84rem;color:var(--brown);display:block">Your review</label>
    <textarea name="comment" id="comment" rows="4" maxlength="500" class="<?= $form['comment_error'] ? 'invalid' : '' ?>"><?= h($form['comment']) ?></textarea>
    <p class="field-err" id="commentErr" role="alert" <?= $form['comment_error'] ? '' : 'hidden' ?>><?= h($form['comment_error']) ?></p>
    <p class="sub" style="margin:8px 0 14px">Please keep it respectful - foul words are hidden automatically. <span id="charCount"></span></p>
    <button type="submit"><?= $mine ? 'Update review' : 'Post review' ?></button>
  </form>
<?php else: ?>
  <p class="sub">Only customers who have ordered this product can leave a review. Place an order and come back!</p>
<?php endif; ?>
</div>
<?php endif; ?>

<script>
// ---------- photo gallery ----------
(function () {
  var main = document.getElementById('pdMain'), thumbs = [].slice.call(document.querySelectorAll('.pd-thumb'));
  if (!main || thumbs.length < 2) return;
  var n = thumbs.length, cur = 0, count = document.getElementById('pdCount');
  thumbs.forEach(function (t) { new Image().src = t.dataset.full; });          // preload so switching is instant
  function go(k) {
    cur = (k + n) % n;
    main.src = thumbs[cur].dataset.full;
    thumbs.forEach(function (b, j) { b.classList.toggle('active', j === cur); });
    if (count) count.textContent = (cur + 1) + ' / ' + n;
  }
  thumbs.forEach(function (b, j) { b.addEventListener('click', function () { go(j); }); });
  document.getElementById('pdPrev').addEventListener('click', function () { go(cur - 1); });
  document.getElementById('pdNext').addEventListener('click', function () { go(cur + 1); });
  document.addEventListener('keydown', function (e) {
    if (e.target.closest && e.target.closest('input,textarea,select')) return;
    if (e.key === 'ArrowLeft') go(cur - 1); else if (e.key === 'ArrowRight') go(cur + 1);
  });
  var x0 = null, box = document.getElementById('pdBox');                        // swipe on phones
  box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, {passive: true});
  box.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0; x0 = null;
    if (Math.abs(dx) > 40) go(cur + (dx < 0 ? 1 : -1));
  });
})();

// ---------- quantity buttons ----------
function pdQty(d) {
  var i = document.getElementById('pdQty');
  i.value = Math.max(+i.min || 1, Math.min(+i.max, (+i.value || 1) + d));
}

// ---------- star rating + review form checks ----------
(function () {
  var form = document.getElementById('rvForm');
  if (!form) return;
  var words = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'],
      wrap = document.getElementById('starWrap'), txt = document.getElementById('starText'),
      rErr = document.getElementById('ratingErr'), cErr = document.getElementById('commentErr'),
      box = document.getElementById('comment'), count = document.getElementById('charCount');

  function chosen() { var c = form.querySelector('input[name=rating]:checked'); return c ? +c.value : 0; }
  function showRating() { var v = chosen(); txt.textContent = v ? v + ' / 5 · ' + words[v] : 'Tap a star'; }
  function shake(el) { el.classList.remove('shake'); void el.offsetWidth; el.classList.add('shake'); }

  form.querySelectorAll('input[name=rating]').forEach(function (r) {
    r.addEventListener('change', function () { showRating(); wrap.classList.remove('invalid'); rErr.hidden = true; });
  });
  box.addEventListener('input', function () {
    count.textContent = box.value.length + '/500';
    if (box.value.trim().length >= 5) { cErr.hidden = true; box.classList.remove('invalid'); }
  });
  showRating(); count.textContent = box.value.length + '/500';

  form.addEventListener('submit', function (e) {
    var bad = null;
    if (!chosen()) {                                                           // nothing selected -> show a message instead of sending
      rErr.textContent = 'Please select a star rating before submitting.';
      rErr.hidden = false; wrap.classList.add('invalid'); shake(wrap); bad = wrap;
    }
    var len = box.value.trim().length;
    if (len < 5) {
      cErr.textContent = 'Your review must be at least 5 characters.';
      cErr.hidden = false; box.classList.add('invalid'); bad = bad || box;
    }
    if (bad) { e.preventDefault(); bad.scrollIntoView({behavior: 'smooth', block: 'center'}); }
  });
})();
</script>
<?php include('../includes/footer.php'); ?>
