<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$reward = 0;
$result = null;
$pet = null;

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ? AND level = 3");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['reset'])) {
    $pet_id = $_POST['pet_id'];
    $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    $pet = $stmt->fetch();

    if ($pet) {
        $win = rand(0, 1);
        if ($win) {
            $reward = rand(5, 15);
            $pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?")->execute([$reward, $user_id]);
            $pdo->prepare("UPDATE user_pets SET boosts = boosts + 1 WHERE id = ?")->execute([$pet_id]);
            $result = "win";
        } else {
            $result = "lose";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Monster Battle - Simpets</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .battle-scene {
      position: relative;
      width: 400px;
      height: 300px;
      background-image: url('battle_assets/battle_bg.png');
      background-size: cover;
      border: 2px solid #333;
      margin: 20px auto;
    }
    .pet-img, .monster-img, .overlay {
      position: absolute;
      width: 150px;
      height: 150px;
    }
    .pet-img { left: 20px; bottom: 0; }
    .monster-img { right: 20px; bottom: 0; }
    .overlay {
      width: 100%;
      height: 100%;
      top: 0; left: 0;
    }
    .message {
      text-align: center;
      font-size: 20px;
      font-weight: bold;
      margin-top: 20px;
    }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Monster Battle</h1>

  <?php if (!$result): ?>
    <form method="post">
      <label>Select your pet:</label>
      <select name="pet_id" required>
        <?php foreach ($pets as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['pet_name']) ?> (<?= $p['type'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Fight!</button>
    </form>
  <?php else: ?>
    <div class="battle-scene">
      <img class="pet-img" src="<?= htmlspecialchars($pet['pet_image']) ?>" alt="Pet">
      <img class="monster-img" src="battle_assets/monster<?= rand(1,2) ?>.png" alt="Monster">
      <?php if ($result === "win"): ?>
        <img class="overlay" src="battle_assets/victory_overlay.png" alt="Victory">
      <?php else: ?>
        <img class="overlay" src="battle_assets/defeat_overlay.png" alt="Defeat">
      <?php endif; ?>
    </div>
    <div class="message">
      <?php if ($result === "win"): ?>
        <?= htmlspecialchars($pet['pet_name']) ?> defeated the monster and earned <strong><?= $reward ?> Simbucks!</strong>
      <?php else: ?>
        <?= htmlspecialchars($pet['pet_name']) ?> was defeated. Try again tomorrow!
      <?php endif; ?>
    </div>
    <form method="post">
      <button type="submit" name="reset" value="1">Battle Again</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
