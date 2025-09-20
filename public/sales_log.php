<?php
session_start();
require_once "includes/db.php";
$stmt = $pdo->query("
  SELECT s.*, p.pet_name, u1.username AS seller_name, u2.username AS buyer_name 
  FROM sales_log s
  JOIN user_pets p ON s.pet_id = p.id
  JOIN users u1 ON s.seller_id = u1.id
  JOIN users u2 ON s.buyer_id = u2.id
  ORDER BY s.sold_at DESC
");
$sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Sales Log</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Sales History</h1></header>
<div class="container">
  <h2>Completed Pet Sales</h2>
  <?php foreach ($sales as $sale): ?>
    <div class="card">
      <strong><?= htmlspecialchars($sale['pet_name']) ?></strong><br>
      Sold by <em><?= htmlspecialchars($sale['seller_name']) ?></em> to <em><?= htmlspecialchars($sale['buyer_name']) ?></em><br>
      For <strong><?= $sale['price'] ?> Simbucks</strong> on <?= $sale['sold_at'] ?>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>