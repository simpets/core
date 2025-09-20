<?php
session_start();
require_once "includes/db.php";
$user_id = $_GET['id'] ?? $_SESSION['user_id']; // allows viewing others’ shops

// Load shop info
$stmt = $pdo->prepare("SELECT * FROM user_shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

// Load listings
$stmt = $pdo->prepare(
    "SELECT l.*, i.name AS item_name, i.image AS item_image 
     FROM user_shop_listings l
     JOIN items i ON l.item_id = i.id
     WHERE l.seller_id = ?"
);
$stmt->execute([$user_id]);
$listings = $stmt->fetchAll();

?>
 <title>Member Shop</title>
    <?php include "menu.php"; ?>
<div style="border:1px solid #aaa;padding:12px;text-align:center;">
    <h1><?= htmlspecialchars($shop['shop_name'] ?? 'My Shop') ?></h1>
    <?php if (!empty($shop['shop_image'])): ?>
        <img src="<?= htmlspecialchars($shop['shop_image']) ?>" width="400" height="400"><br>
    <?php endif; ?>
    <div><?= nl2br(htmlspecialchars($shop['shop_description'] ?? '')) ?></div>
</div>
<h2>Items for Sale</h2>
<table>
<tr><th>Image</th><th>Name</th><th>Qty</th><th>Price</th><th>Buy</th></tr>
<?php foreach ($listings as $l): ?>
<tr>
    <td><img src="<?= htmlspecialchars($l['item_image']) ?>" width="60"></td>
    <td><?= htmlspecialchars($l['item_name']) ?></td>
    <td><?= (int)$l['quantity'] ?></td>
    <td><?= (int)$l['price'] ?> <?= htmlspecialchars($l['currency']) ?></td>
    <td>
        <form method="post" action="buy_user_item.php">
            <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
            <button type="submit">Buy</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>