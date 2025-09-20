<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, pet_name FROM user_pets WHERE user_id = ? AND id NOT IN (SELECT pet_id FROM pet_market)");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Sell a Pet</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Sell a Pet</h1></header>
<div class="container">
  <h2>List Your Pet for Sale</h2>
  <form method="post" action="sell_pet_action.php">
    <label>Select Pet:
      <select name="pet_id" required>
        <?php foreach ($pets as $pet): ?>
          <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label><br><br>
    <label>Price (Simbucks): <input type="number" name="price" min="1" required></label><br><br>
    <input type="submit" value="List Pet for Sale">
  </form>
</div>
</body>
</html>