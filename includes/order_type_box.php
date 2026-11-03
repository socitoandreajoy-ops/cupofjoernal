<?php
// includes/order_type_box.php - Take Out / To Go / Deliver picker (+ address & phone when Deliver).
// Optional: $old = ['order_type'=>..., 'delivery_address'=>..., 'contact_phone'=>...] to re-fill after an error.
$_ot  = $old['order_type'] ?? 'Take Out';
$_oa  = $old['delivery_address'] ?? '';
$_op  = $old['contact_phone'] ?? '';
$_ico = ['Take Out' => '🛍️', 'To Go' => '🥡', 'Deliver' => '🛵'];
?>
<h4 style="margin:6px 0 8px;">Order type</h4>
<div class="otype">
  <?php foreach (order_types() as $t => $desc): ?>
    <label class="otype-opt" title="<?= h($desc) ?>">
      <input type="radio" name="order_type" value="<?= h($t) ?>" <?= $_ot === $t ? 'checked' : '' ?>>
      <span><?= $_ico[$t] ?> <?= h($t) ?></span>
    </label>
  <?php endforeach; ?>
</div>
<div id="deliverBox" style="display:none">
  <label class="lbl">Delivery address *</label>
  <textarea name="delivery_address" rows="2" maxlength="255" placeholder="House no., street, barangay, city"><?= h($_oa) ?></textarea>
  <label class="lbl">Contact number *</label>
  <input type="text" name="contact_phone" value="<?= h($_op) ?>" placeholder="09123456789">
</div>
<script>
(function () {
  var radios = document.querySelectorAll('input[name="order_type"]'), box = document.getElementById('deliverBox');
  function sync() {
    var v = document.querySelector('input[name="order_type"]:checked');
    box.style.display = (v && v.value === 'Deliver') ? '' : 'none';
    radios.forEach(function (r) { r.closest('.otype-opt').classList.toggle('on', r.checked); });
  }
  radios.forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
