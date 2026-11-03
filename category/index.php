<?php
session_start();
include('../includes/config.php');
include('../includes/adminHeader.php');   // login + role check + sidebar

$old = take_old();
$edit_id = 0;
$edit_name = "";
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = mysqli_execute_query($conn, "SELECT * FROM categories WHERE category_id = ?", [$edit_id]);
    if ($row = mysqli_fetch_assoc($res)) {
        $edit_name = $row['category_name'];
    } else {
        $edit_id = 0;
    }
}
if (isset($old['category_name'])) $edit_name = $old['category_name'];   // what they typed if the save failed
?>

<h2>Categories Management</h2>
<?php include('../includes/alert.php'); ?>

<form method="POST" action="<?= $edit_id > 0 ? 'update.php' : 'store.php' ?>" style="margin-bottom: 20px;">
    <input type="hidden" name="category_id" value="<?php echo $edit_id; ?>">
    <input type="text" name="category_name" placeholder="Category Name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
    <button type="submit" name="save_category"><?php echo $edit_id > 0 ? "Update Category" : "Add Category"; ?></button>
    <?php if ($edit_id > 0): ?>
        <a href="index.php" style="margin-left: 10px;">Cancel</a>
    <?php endif; ?>
</form>

<?php search_box('#catTbl', 'Search categories…'); ?>
<table id="catTbl">
    <thead>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $result = mysqli_query($conn, "SELECT * FROM categories");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['category_id']}</td>";
            echo "<td>" . h($row['category_name']) . "</td>";
            echo "<td>
                    <a href='index.php?edit={$row['category_id']}'>Edit</a> | 
                    <a href='delete.php?id={$row['category_id']}' onclick='return confirm(\"Delete this category?\")'>Delete</a>
                  </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>
<?php include('../includes/footer.php'); ?>
