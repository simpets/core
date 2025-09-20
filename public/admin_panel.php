<?php
session_start();
require_once "includes/db.php";

if ($_SESSION['usergroup'] !== 'Admin') {
    die("Access denied.");
}

// Fetch users
$users = $pdo->query("SELECT id, username, nickname, usergroup FROM users")->fetchAll();

// Fetch pets
$pets = $pdo->query("SELECT id, pet_name, type, user_id FROM user_pets")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel - Simpets</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Admin Panel</h1>

  <h2>Manage Users</h2>
  <table border="1" cellpadding="6" style="width:100%; background:white;">
    <tr>
      <th>ID</th><th>Username</th><th>Nickname</th><th>User Group</th>
    </tr>
    <?php foreach ($users as $user): ?>
    <tr>
      <td><?= $user['id'] ?></td>
      <td><?= htmlspecialchars($user['username']) ?></td>
      <td><?= htmlspecialchars($user['nickname']) ?></td>
      <td><?= htmlspecialchars($user['usergroup']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h2>Manage Pets</h2>
  <table border="1" cellpadding="6" style="width:100%; background:white;">
    <tr>
      <th>ID</th><th>Name</th><th>Type</th><th>User ID</th>
    </tr>
    <?php foreach ($pets as $pet): ?>
    <tr>
      <td><?= $pet['id'] ?></td>
      <td><?= htmlspecialchars($pet['pet_name']) ?></td>
      <td><?= htmlspecialchars($pet['type']) ?></td>
      <td><?= $pet['user_id'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
