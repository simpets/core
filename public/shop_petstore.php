<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->query("SELECT id, name, image FROM pets_available");
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Petstore</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Petstore</h1></header>
<div class="container">
  <h2>Adopt a Pet</h2>
  <?php foreach ($pets as $pet): ?>
    <div class="card">
      <strong><?= htmlspecialchars($pet['name']) ?></strong><br>
      <img src="<?= $pet['image'] ?>" width="120"><br>
      <form method="post" action="adopt_action.php">
        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
        <input type="text" name="pet_name" placeholder="Your Pet Name" required>
        <input type="submit" value="Adopt">
      </form>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>