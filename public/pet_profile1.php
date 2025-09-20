<?php
session_start();
require_once "includes/db.php";

if (!isset($_GET['id'])) {
    die("Pet not specified.");
}

$pet_id = $_GET['id'];
$user_id = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if (!$pet) {
    die("Pet not found.");
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$pet['user_id']]);
$owner = $stmt->fetchColumn();

$is_owner = ($user_id == $pet['user_id']);
$generation = ($pet['mother'] || $pet['father']) ? "Child of {$pet['mother']} & {$pet['father']}" : "Gen 1";

$stmt = $pdo->prepare("SELECT image FROM levels WHERE pet_type = ? AND level = ?");
$stmt->execute([$pet['type'], $pet['level']]);
$levelImage = $stmt->fetchColumn();

if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc = substr(trim($_POST['description']), 0, 500);
    $stmt = $pdo->prepare("UPDATE user_pets SET description = ? WHERE id = ?");
    $stmt->execute([$desc, $pet_id]);
    $pet['description'] = $desc;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($pet['pet_name']) ?>'s Profile</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1><?= htmlspecialchars($pet['pet_name']) ?>'s Profile</h1>

  <div style="position:relative; width:350px; height:350px; margin:auto; background:#f5f5f5;">
    <?php if (!empty($pet['background_url'])): ?>
      <img src="<?= htmlspecialchars($pet['background_url']) ?>" style="position:absolute; width:100%; height:100%; z-index:0;">
    <?php endif; ?>
    <?php if (!empty($levelImage)): ?>
      <img src="<?= htmlspecialchars($levelImage) ?>" style="position:absolute; width:200px; height:200px; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1;">
    <?php endif; ?>
    <?php if (!empty($pet['deco'])): ?>
      <img src="<?= htmlspecialchars($pet['deco']) ?>" style="position:absolute; width:100%; height:100%; z-index:2;">
    <?php endif; ?>
    <?php if (!empty($pet['toy1'])): ?>
      <img src="<?= htmlspecialchars($pet['toy1']) ?>" style="position:absolute; width:100%; height:100%; z-index:3;">
    <?php endif; ?>
    <?php if (!empty($pet['toy2'])): ?>
      <img src="<?= htmlspecialchars($pet['toy2']) ?>" style="position:absolute; width:100%; height:100%; z-index:4;">
    <?php endif; ?>
    <?php if (!empty($pet['toy3'])): ?>
      <img src="<?= htmlspecialchars($pet['toy3']) ?>" style="position:absolute; width:100%; height:100%; z-index:5;">
    <?php endif; ?>
  </div>

  <ul>
    <li><strong>Name:</strong> <?= htmlspecialchars($pet['pet_name']) ?></li>
    <li><strong>Type:</strong> <?= htmlspecialchars($pet['type']) ?></li>
    <li><strong>Gender:</strong> <?= htmlspecialchars($pet['gender']) ?></li>
    <li><strong>Level:</strong> <?= $pet['level'] ?></li>
    <li><strong>Boosts:</strong> <?= $pet['boost'] ?></li>
    <li><strong>Owner:</strong> <?= htmlspecialchars($owner) ?></li>
    <li><strong>Mother:</strong> <?= $pet['mother'] ?: "None" ?></li>
    <li><strong>Father:</strong> <?= $pet['father'] ?: "None" ?></li>
    <li><strong>Generation:</strong> <?= $generation ?></li>
    <li><strong>Offspring:</strong> <?= $pet['offspring'] ?></li>
    <li><strong>Adoption Date:</strong> <?= $pet['created_at'] ?></li>
    <li><strong>Pet ID:</strong> <?= $pet['id'] ?></li>
    <li><strong>Sale Price:</strong> <?= $pet['price'] ? $pet['price'] . " Simbucks" : "Not for sale" ?></li>
    <li><strong>Custom Image:</strong> <?= ($pet['level'] == 3 && !empty($pet['pet_image'])) ? "Yes" : "No" ?></li>
  </ul>

  <h3>Description</h3>
  <?php if ($is_owner): ?>
    <form method="post">
      <textarea name="description" rows="4" cols="60" maxlength="500"><?= htmlspecialchars($pet['description']) ?></textarea><br>
      <input type="submit" value="Update Description">
    </form>
  <?php else: ?>
    <p><?= nl2br(htmlspecialchars($pet['description'])) ?></p>
  <?php endif; ?>
</div>
</body>
</html>
