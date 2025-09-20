<?php
session_start();
require_once "includes/db.php";

if (!isset($_GET['id'])) {
    die("No shop selected.");
}

$shop_id = (int) $_GET['id'];

// Get the shop name
$stmt = $pdo->prepare("SELECT name FROM shops WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

if (!$shop) {
    die("Shop not found.");
}

// Get items in this shop
$stmt = $pdo->prepare("SELECT i.id, i.name, i.image, i.price 
                       FROM items i 
                       WHERE i.shop_id = ?");
$stmt->execute([$shop_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($shop['name']) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .shop-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
    }
    .shop-item {
      width: 120px;
      text-align: center;
    }
    .shop-item img {
      width: 100px;
      height: 100px;
      border: 1px solid #ccc;
    }
    .notice {
      color: green;
      font-weight: bold;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1><?= htmlspecialchars($shop['name']) ?></h1>

  <?php if (isset($_GET['bought']) && $_GET['bought'] == 1 && isset($_GET['name'], $_GET['price'])): ?>
    <p class="notice">
      ✅ You bought 1x <?= htmlspecialchars($_GET['name']) ?> for <?= (int)$_GET['price'] ?> Simbucks!
    </p>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <p>This shop doesn't have any items yet.</p>
  <?php else: ?>
    <div class="shop-grid">
      <?php foreach ($items as $item): ?>
        <div class="shop-item">
          <img src="assets/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"><br>
          <strong><?= htmlspecialchars($item['name']) ?></strong><br>
          <span><?= $item['price'] ?> Simbucks</span><br>
          <form method="post" action="buy_item.php">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <button type="submit">Buy</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>