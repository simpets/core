<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "includes/db.php";

$currentTime = time();
$cutoff = $currentTime - (10 * 60); // 10 minutes


$stmt = $pdo->prepare("SELECT DISTINCT username FROM online WHERE time >= ?");




$stmt->execute([$cutoff]);
$usersOnline = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Who's Online</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Who's Online</h1>

  <?php if ($usersOnline): ?>
    <ul>
      <?php foreach ($usersOnline as $user): ?>
        <li><a href="profile.php?user=<?= urlencode($user['username']) ?>">
          <?= htmlspecialchars($user['username']) ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No users are currently online.</p>
  <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>