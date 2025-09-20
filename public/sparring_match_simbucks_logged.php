<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT pet_name, type FROM user_pets WHERE user_id = ? ORDER BY RAND() LIMIT 1");
$stmt->execute([$user_id]);
$pet = $stmt->fetch();

$pet_name = $pet ? $pet['pet_name'] : 'Your Pet';
$pet_type = $pet ? $pet['type'] : 'Unknown Creature';
$opponent_name = 'Wild ' . $pet_type;
$result = "";
$reward = 0;

$date = date("Y-m-d");
$check = $pdo->prepare("SELECT COUNT(*) FROM sparring_logs WHERE user_id = ? AND game = ? AND DATE(battle_time) = ?");
$check->execute([$user_id, 'sparring_battle_simpets', $date]);
$spar_count = $check->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($spar_count >= 2) {
        $result = "You’ve already sparred twice today. Come back tomorrow!";
    } else {
        $won = rand(0, 1);
        $reward = $won ? rand(5, 20) : 0;

        if ($won) {
            $stmt = $pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?");
            $stmt->execute([$reward, $user_id]);
            $result = "<strong>{\$pet_name}</strong> won the sparring match against <strong>{\$opponent_name}</strong> and earned <strong>{\$reward} Simbucks!</strong>";
        } else {
            $result = "<strong>{\$pet_name}</strong> sparred with <strong>{\$opponent_name}</strong> but lost this round.";
        }

        $log = $pdo->prepare("INSERT INTO sparring_logs (user_id, pet_name, game, won, reward, battle_time) VALUES (?, ?, ?, ?, ?, NOW())");
        $log->execute([$user_id, $pet_name, 'sparring_battle_simpets', $won, $reward]);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Sparring Match - Simbucks</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .battle-box {
      background: #fff;
      padding: 20px;
      border: 2px solid #ccc;
      max-width: 500px;
      margin: 40px auto;
      text-align: center;
      border-radius: 12px;
      box-shadow: 2px 2px 10px #aaa;
    }
  </style>
</head>
<body>
<?php include "menu.php"; ?>
<div class="battle-box">
  <h1>Sparring Match - Simbucks</h1>
  <p><strong><?= $pet_name ?></strong> faces off against a <strong><?= $opponent_name ?></strong> in a sparring match!</p>
  <?php if ($result): ?>
    <p><?= $result ?></p>
    <form method="post">
      <button type="submit">Spar Again</button>
    </form>
  <?php else: ?>
    <form method="post">
      <button type="submit">Start Sparring!</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
