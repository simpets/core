<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM shop_items WHERE type = 'background'");
$stmt->execute();
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Background Bin</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Background Bin</h1></header>
<div class="container">
  <h2>Buy Backgrounds</h2>
  <?php foreach ($items as $item): ?>
    <div class="card">
      <strong><?= htmlspecialchars($item['name']) ?> - <?= $item['price'] ?> Simbucks</strong><br>
      <img src="<?= $item['image'] ?>" width="100"><br>
      <form method="post" action="buy_item.php">
        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
        <input type="number" name="quantity" value="1" min="1">
        <input type="submit" value="Buy">
      </form>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>