<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT si.name, si.image, ui.quantity FROM user_items ui JOIN shop_items si ON ui.item_id = si.id WHERE ui.user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simpets Dashboard</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header><h1>Simpets</h1></header>
<nav>
  <a href="dashboard.php">Dashboard</a> |
  <a href="adopt.php">Adopt</a> |
  <a href="breed.php">Breed</a> |
  <a href="customize.php">Customize</a> |
  <a href="shop.php">Shop</a> |
  <a href="inventory.php">Inventory</a> |
  <a href="forum.php">Forum</a> |
  <a href="members.php">Members</a> |
  <a href="profile.php">My Profile</a>
  <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
    | <a href="admin.php">Admin</a>
  <?php endif; ?>
  | <a href="logout.php">Logout</a>
</nav>
<div class="container">
  <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>

  <h3>Your Pets</h3>
  <?php foreach ($pets as $pet): ?>
    <div class="card">
      <h4><?= htmlspecialchars($pet['pet_name']) ?></h4>
      <div style="position:relative; width:150px; height:150px;">
        <?php
        if ($pet['background']) {
            $stmt = $pdo->prepare("SELECT image FROM shop_items WHERE id = ?");
            $stmt->execute([$pet['background']]);
            $bg = $stmt->fetchColumn();
            echo "<img src='$bg' width='150' style='position:absolute;z-index:0;'>";
        }
        echo "<img src='{$pet['pet_image']}' width='150' style='position:absolute;z-index:1;'>";
        if ($pet['accessory']) {
            $stmt = $pdo->prepare("SELECT image FROM shop_items WHERE id = ?");
            $stmt->execute([$pet['accessory']]);
            $acc = $stmt->fetchColumn();
            echo "<img src='$acc' width='150' style='position:absolute;z-index:2;'>";
        }
        ?>
      </div>
    </div>
  <?php endforeach; ?>

  <h3>Your Items</h3>
  <?php foreach ($items as $item): ?>
    <div class="card">
      <strong><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</strong><br>
      <?php if ($item['image']): ?>
        <img src="<?= htmlspecialchars($item['image']) ?>" width="80">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>