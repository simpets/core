<?php
session_start();
require_once "includes/db.php";

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch();

if (!$pet) {
    echo "<p>Pet not found or you do not own this pet.</p>";
    exit;
}

// Determine image source
$petImage = !empty($pet['pet_image'])
    ? htmlspecialchars($pet['pet_image'])
    : "images/levels/" . htmlspecialchars($pet['type']) . "_level" . $pet['level'] . ".png";

$background = $pet['background_url'] ? "assets/" . htmlspecialchars($pet['background_url']) : null;
$toys = [];
foreach (['toy1', 'toy2', 'toy3'] as $toy) {
    if (!empty($pet[$toy])) {
        $toys[] = "assets/" . htmlspecialchars($pet[$toy]);
    }
}
$deco = !empty($pet['deco']) ? "assets/" . htmlspecialchars($pet['deco']) : null;
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($pet['pet_name']) ?> - Pet Profile</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .stats-table td { padding: 4px 10px; }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1><?= htmlspecialchars($pet['pet_name']) ?> (<?= htmlspecialchars($pet['type']) ?>)</h1>

  <div style="position: relative; width: 400px; height: 400px; margin-bottom: 20px;">
    <?php if ($background): ?>
      <img src="<?= $background ?>" alt="Background" style="position: absolute; width: 400px; height: 400px; z-index: 0;">
    <?php endif; ?>

    <img src="<?= $petImage ?>" alt="Pet" style="position: absolute; width: 200px; height: 200px; top: 100px; left: 100px; z-index: 1;">

    <?php foreach ($toys as $toyImg): ?>
      <img src="<?= $toyImg ?>" alt="Toy" style="position: absolute; width: 200px; height: 200px; top: 100px; left: 100px; z-index: 2;">
    <?php endforeach; ?>

    <?php if ($deco): ?>
      <img src="<?= $deco ?>" alt="Decoration" style="position: absolute; width: 200px; height: 200px; top: 100px; left: 100px; z-index: 3;">
    <?php endif; ?>
  </div>

  <table class="stats-table">
    <tr><td><strong>Level:</strong></td><td><?= $pet['level'] ?></td></tr>
    <tr><td><strong>Gender:</strong></td><td><?= $pet['gender'] ?></td></tr>
    <tr><td><strong>Boosts (Clicks):</strong></td><td><?= $pet['boosts'] ?></td></tr>
    <tr><td><strong>Offspring:</strong></td><td><?= $pet['offspring'] ?></td></tr>
    <tr><td><strong>Adopted At:</strong></td><td><?= $pet['adopted_at'] ?></td></tr>
    <tr><td><strong>Generation:</strong></td><td><?= ($pet['mother'] || $pet['father']) ? 'Bred' : 'Gen One' ?></td></tr>
    <tr><td><strong>Mother:</strong></td><td><?= $pet['mother'] ?: '—' ?></td></tr>
    <tr><td><strong>Father:</strong></td><td><?= $pet['father'] ?: '—' ?></td></tr>
    <tr><td><strong>Price (if for sale):</strong></td><td><?= $pet['price'] !== null ? $pet['price'] . " Simbucks" : 'Not for sale' ?></td></tr>
    <tr><td><strong>Background:</strong></td><td><?= $pet['background_url'] ?: 'None' ?></td></tr>
    <tr><td><strong>Deco:</strong></td><td><?= $pet['deco'] ?: 'None' ?></td></tr>
    <tr><td><strong>Toy 1:</strong></td><td><?= $pet['toy1'] ?: 'None' ?></td></tr>
    <tr><td><strong>Toy 2:</strong></td><td><?= $pet['toy2'] ?: 'None' ?></td></tr>
    <tr><td><strong>Toy 3:</strong></td><td><?= $pet['toy3'] ?: 'None' ?></td></tr>
  </table>

  <?php if (!empty($pet['description'])): ?>
    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($pet['description'])) ?></p>
  <?php endif; ?>

  <?php if ($pet['marking1'] || $pet['marking2'] || $pet['marking3']): ?>
    <h3>Applied Markings:</h3>
    <ul>
      <?php foreach (['marking1', 'marking2', 'marking3'] as $field): ?>
        <?php if (!empty($pet[$field])): ?>
          <li><img src="assets/<?= htmlspecialchars($pet[$field]) ?>" width="50"> <?= htmlspecialchars($pet[$field]) ?></li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <br>
  <a href="sell_pet.php?id=<?= $pet['id'] ?>">Sell this Pet</a> |
  <a href="breed.php?pet_id=<?= $pet['id'] ?>">Breed this Pet</a> |
  
  <?php if ($pet['user_id'] == $_SESSION['user_id']): ?>
  <a href="equip_items.php?id=<?= $pet['id'] ?>" class="button">🎒 Equip Items</a> |
<?php endif; ?>
  
  <a href="my_pets.php">Back to My Pets</a>
</div>
</body>
</html>