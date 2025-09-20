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
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Pets</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .pet-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
    }
    .pet-entry {
      width: 140px;
      text-align: center;
    }
    .pet-entry img {
      width: 100px;
      height: 100px;
      border: 1px solid #999;
    }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>My Pets</h1>

  <?php if (empty($pets)): ?>
    <p>You don't have any pets yet.</p>
  <?php else: ?>
    <div class="pet-grid">
      <?php foreach ($pets as $pet): ?>
        <?php
          $level = $pet['level'];
          $pet_image = $pet['pet_image'];
          $type = $pet['type'];

          if ($level < 3) {
              $image_path = "images/levels/{$type}_Egg.png";
          } elseif (!empty($pet_image)) {
              $image_path = $pet_image;
          } else {
              $image_path = "images/levels/{$type}.png"; // fallback adult
          }
        ?>
        <div class="pet-entry">
          <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($pet['pet_name']) ?>"><br>
          <?= htmlspecialchars($pet['pet_name']) ?><br>
          <small>(<?= htmlspecialchars($pet['type']) ?>)</small><br>
          <a href="pet_profile.php?id=<?= $pet['id'] ?>">View</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>

 <?php include 'footer.php'; ?>
</html>