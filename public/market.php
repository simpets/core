<?php
session_start();
require_once "includes/db.php";

$stmt = $pdo->query("SELECT up.*, u.username FROM user_pets up JOIN users u ON up.user_id = u.id WHERE up.price > 0");
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pet Market</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Pet Market</h1>
  <?php if (!$pets): ?>
    <p>No pets are currently listed for sale.</p>
  <?php else: ?>
    <?php foreach ($pets as $pet): ?>
      <?php
        $image = $pet['pet_image'];
        if (empty($image)) {
            $stmt = $pdo->prepare("SELECT image FROM levels WHERE pet_type = ? AND level = ?");
            $stmt->execute([$pet['type'], $pet['level']]);
            $image = $stmt->fetchColumn();
        }
      ?>
      <div class="card" style="margin-bottom:20px;">
        <h3><?= htmlspecialchars($pet['pet_name']) ?> (<?= $pet['type'] ?>)</h3>
        <img src="<?= $image ?>" width="200"><br>
        <p><strong>Seller:</strong> <?= htmlspecialchars($pet['username']) ?></p>
        <p><strong>Level:</strong> <?= $pet['level'] ?> | <strong>Gender:</strong> <?= $pet['gender'] ?> | <strong>Price:</strong> <?= $pet['price'] ?> Simbucks</p>
        <form method="post" action="buy_pet.php">
          <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
          <input type="submit" value="Buy Pet">
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>
