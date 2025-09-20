<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pet_id = $_GET['id'] ?? null;
if (!$pet_id) {
    echo "No pet selected."; exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, u.username,
        (SELECT image FROM shop_items WHERE id = p.background) AS background_img,
        (SELECT image FROM shop_items WHERE id = p.accessory) AS accessory_img
    FROM user_pets p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if (!$pet) {
    echo "Pet not found."; exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($pet['pet_name']) ?> - Profile</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header><h1>Simpets</h1></header>
<div class="container">
  <h2><?= htmlspecialchars($pet['pet_name']) ?>'s Profile</h2>
  <div style="position:relative; width:300px; height:300px;">
    <?php if ($pet['background_img']): ?>
      <img src="<?= $pet['background_img'] ?>" style="position:absolute; z-index:0;" width="300">
    <?php endif; ?>
    <img src="<?= $pet['pet_image'] ?>" style="position:absolute; z-index:1;" width="300">
    <?php if ($pet['accessory_img']): ?>
      <img src="<?= $pet['accessory_img'] ?>" style="position:absolute; z-index:2;" width="300">
    <?php endif; ?>
  </div>
  <ul>
    <li><strong>Owner:</strong> <?= htmlspecialchars($pet['username']) ?></li>
    <li><strong>Type:</strong> <?= htmlspecialchars($pet['pet_name']) ?></li>
    <li><strong>Gender:</strong> <?= htmlspecialchars($pet['gender']) ?></li>
    <li><strong>Mother:</strong> <?= htmlspecialchars($pet['mother']) ?: 'Unknown' ?></li>
    <li><strong>Father:</strong> <?= htmlspecialchars($pet['father']) ?: 'Unknown' ?></li>
    <li><strong>Offspring:</strong> <?= intval($pet['offspring']) ?></li>
    <li><strong>Level:</strong> <?= $pet['level'] ?? 1 ?></li>
    <li><strong>Boosts:</strong> <?= $pet['boosts'] ?? 0 ?></li>
  </ul>
  <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>