<?php
session_start();
include('../includes/config.php');
require_role(['Admin']);
include('../includes/adminHeader.php');

$editId = (int)($_GET['edit'] ?? 0);

$editing = null;
if ($editId) {
    $editing = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT * FROM products WHERE product_id=?", [$editId]));
}
$form = $editing ?: ['product_name' => '', 'price' => '', 'stock_quantity' => '', 'category_id' => ''];
$form = array_merge($form, take_old());     // what was typed if the save failed

$cats = mysqli_fetch_all(mysqli_query($conn, "SELECT category_id,category_name FROM categories ORDER BY category_name"), MYSQLI_ASSOC);
$list = mysqli_query($conn, "SELECT p.*,c.category_name,(SELECT filename FROM product_photos WHERE product_id=p.product_id ORDER BY photo_id LIMIT 1) thumb FROM products p LEFT JOIN categories c USING(category_id) ORDER BY p.product_id");
?>
<h2>Products Management</h2>
<?php include('../includes/alert.php'); ?>

<div class="panel"><b><?= $editing ? 'Edit product' : 'Add new product' ?></b>
<form class="f" method="post" action="<?= $editing ? 'update.php' : 'store.php' ?>" enctype="multipart/form-data" novalidate>
<input type="hidden" name="id" value="<?= $editing ? (int)$editing['product_id'] : 0 ?>">
<div><label>Product name</label><input type="text" name="product_name" value="<?= h($form['product_name']) ?>"></div>
<div><label>Price (₱)</label><input type="text" name="price" value="<?= h($form['price']) ?>"></div>
<div><label>Stock</label><input type="text" name="stock_quantity" value="<?= h($form['stock_quantity']) ?>"></div>
<div><label>Category</label><select name="category_id"><option value="">Select category</option>
<?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (string)$form['category_id'] === (string)$c['category_id'] ? 'selected' : '' ?>><?= h($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div style="grid-column:1/-1"><label>Photos (JPG, PNG or WEBP, max 2MB each - pick as many as you like; the first one is the cover)</label>
  <input type="file" name="photos[]" id="photoPick" accept="image/jpeg,image/png,image/webp" multiple>
  <div class="pick-prev" id="pickPrev"></div></div>
<div style="align-self:end"><button type="submit"><?= $editing ? 'Save changes' : 'Add product' ?></button>
<?php if ($editing): ?> <a class="btn alt" href="index.php">Cancel</a><?php endif; ?></div>
</form>
<?php if ($editing): $eid = (int)$editing['product_id'];
  $gal = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT photo_id,filename FROM product_photos WHERE product_id=? ORDER BY photo_id", [$eid]), MYSQLI_ASSOC); ?>
<div class="gallery" style="margin-top:14px">
<?php foreach ($gal as $g): ?>
  <div class="g-item"><img src="<?= BASE_URL ?>/uploads/products/<?= h($g['filename']) ?>" alt="">
  <form method="post" action="delete_photo.php" onsubmit="return confirm('Remove this photo?')"><input type="hidden" name="id" value="<?= $eid ?>"><input type="hidden" name="photo_id" value="<?= (int)$g['photo_id'] ?>"><button class="btn sm del">Remove</button></form></div>
<?php endforeach; if (!$gal): ?><span class="sub">No photos yet.</span><?php endif; ?>
</div>
<?php endif; ?>
</div>

<div class="panel"><?php search_box('#prodTbl', 'Search products by name, category or price…'); ?>
<table id="prodTbl"><tr><th>ID</th><th>Photo</th><th>Product</th><th>Price</th><th>Stock</th><th>Category</th><th>Actions</th></tr>
<?php while ($r = mysqli_fetch_assoc($list)): ?><tr>
<td><?= (int)$r['product_id'] ?></td>
<td><?php if ($r['thumb']): ?><img class="thumb" src="<?= BASE_URL ?>/uploads/products/<?= h($r['thumb']) ?>" alt=""><?php endif; ?></td>
<td><?= h($r['product_name']) ?></td>
<td>₱<?= number_format($r['price'], 2) ?></td><td><?= (int)$r['stock_quantity'] ?></td><td><span class="pill"><?= h($r['category_name']) ?></span></td>
<td style="white-space:nowrap"><a class="btn sm alt" href="show.php?id=<?= (int)$r['product_id'] ?>">View</a> <a class="btn sm alt" href="index.php?edit=<?= (int)$r['product_id'] ?>">Edit</a>
<form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="id" value="<?= (int)$r['product_id'] ?>"><button class="btn sm del">Delete</button></form></td>
</tr><?php endwhile; ?></table></div>
<script>
// preview the photos chosen in the form before uploading
(function () {
  var inp = document.getElementById('photoPick'), box = document.getElementById('pickPrev');
  if (!inp) return;
  inp.addEventListener('change', function () {
    box.innerHTML = '';
    [].slice.call(inp.files).forEach(function (f, i) {
      if (f.type.indexOf('image/') !== 0) return;
      var fig = document.createElement('figure'), im = new Image(), cap = document.createElement('figcaption');
      im.alt = f.name; im.title = f.name + (f.size > 2 * 1024 * 1024 ? ' (over 2MB - will be rejected)' : '');
      if (f.size > 2 * 1024 * 1024) im.style.borderColor = '#c4161c';
      im.src = URL.createObjectURL(f);
      cap.textContent = i + 1;
      fig.appendChild(im); fig.appendChild(cap); box.appendChild(fig);
    });
  });
})();
</script>
<?php include('../includes/footer.php'); ?>
