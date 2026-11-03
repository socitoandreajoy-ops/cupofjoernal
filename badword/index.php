<?php
// badword/index.php - Admin: edit the list of words that are masked (***) in customer reviews
session_start();
include('../includes/config.php');
require_role(['Admin']);
include('../includes/adminHeader.php');   // login + role check + sidebar

$words = null;                            // null = the bad_words table does not exist yet
try {
    $words = array_column(mysqli_fetch_all(mysqli_query($conn, "SELECT word_id, word FROM bad_words ORDER BY word"), MYSQLI_ASSOC), 'word', 'word_id');
} catch (Throwable $ex) { $words = null; }

// "Try the filter" box
$test = null; $testOut = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test'])) {
    $test    = mb_substr((string)$_POST['test'], 0, 500);
    $testOut = mask_bad_words($test);
}
$old = take_old();
?>
<h2>Bad Words</h2>
<p class="sub" style="margin-bottom:18px">Words in this list are hidden as <b>****</b> in customer reviews. Changes apply immediately, to old reviews too.</p>
<?php include('../includes/alert.php'); ?>

<?php if ($words === null): ?>
  <div class="alert err">The <b>bad_words</b> table is missing. Import <b>database/update_v2.sql</b> in phpMyAdmin, then refresh this page.</div>
<?php else: ?>

<div class="panel"><b>Add words</b>
  <form method="post" action="store.php" class="inline-add" novalidate>
    <input type="text" name="words" maxlength="300" placeholder="e.g. stupid, idiot, hindot" value="<?= h($old['words'] ?? '') ?>" autocomplete="off">
    <button type="submit">Add to list</button>
  </form>
  <p class="sub" style="margin-top:8px">Separate several words with commas. Letters, numbers and spaces only (2-30 characters each).
     Sneaky spellings like <i>sh1t</i> or <i>fuuuck</i> are caught automatically, so you only add the plain word.</p>
</div>

<div class="panel">
  <b>Current list <span class="pill"><?= count($words) ?></span></b>
  <div class="chips-bar">
    <input type="text" id="wfilter" placeholder="🔍 Search the list…" autocomplete="off">
    <form method="post" action="restore.php" onsubmit="return confirm('Add the starter words back? Your own words stay.')"><button class="btn sm alt" type="submit">Restore starter words</button></form>
  </div>
  <div class="chips" id="chips">
    <?php foreach ($words as $id => $w): ?>
      <form method="post" action="delete.php" class="chip" data-w="<?= h($w) ?>">
        <input type="hidden" name="word_id" value="<?= (int)$id ?>">
        <span><?= h($w) ?></span>
        <button type="submit" aria-label="Remove <?= h($w) ?>" title="Remove" onclick="return confirm('Remove “<?= h(addslashes($w)) ?>” from the list?')">✕</button>
      </form>
    <?php endforeach; ?>
  </div>
  <p class="sub" id="chipsEmpty" style="<?= $words ? 'display:none' : '' ?>">No words yet. Nothing is being masked - add some above.</p>
</div>

<div class="panel"><b>Try the filter</b>
  <form method="post" novalidate>
    <textarea name="test" rows="2" maxlength="500" placeholder="Type a sentence to see what customers would see…"><?= h($test ?? '') ?></textarea>
    <div style="margin-top:10px"><button type="submit">Test it</button></div>
  </form>
  <?php if ($test !== null): ?>
    <p style="margin:14px 0 0"><span class="sub">Shown to customers as:</span><br><b><?= $test === '' ? '(empty)' : nl2br(h($testOut)) ?></b></p>
  <?php endif; ?>
</div>

<script>
(function () {
  var box = document.getElementById('wfilter');
  if (!box) return;
  box.addEventListener('input', function () {
    var t = box.value.trim().toLowerCase(), shown = 0;
    document.querySelectorAll('#chips .chip').forEach(function (c) {
      var ok = c.dataset.w.indexOf(t) !== -1;
      c.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    document.getElementById('chipsEmpty').style.display = shown ? 'none' : '';
    document.getElementById('chipsEmpty').textContent = t ? 'No words match your search.' : 'No words yet.';
  });
})();
</script>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
