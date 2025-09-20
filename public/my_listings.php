<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
  SELECT m.id as market_id, m.price, p.pet_name, p.pet_image
  FROM pet_market m
  JOIN user_pets p ON m.pet_id = p.id
  WHERE m.seller_id = ?
");
$stmt->execute([$user_id]);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>My Listings</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>My Listings</h1></header>

 <?php include 'menu.php'; ?>
<div class="container">
  <h2>Your Pets Listed for Sale</h2>
  <?php foreach ($listings as $listing): ?>
    <div class="card">
      <strong><?= htmlspecialchars($listing['pet_name']) ?> - <?= $listing['price'] ?> Canicash</strong><br>
      <img src="<?= htmlspecialchars($listing['pet_image']) ?>" width="150"><br>
      <form method="post" action="cancel_listing.php">
        <input type="hidden" name="market_id" value="<?= $listing['market_id'] ?>">
        <input type="submit" value="Cancel Listing">
      </form>
    </div>
  <?php endforeach; ?>
</div>
</body>
 <?php include 'footer.php'; ?>

</html>