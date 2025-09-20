<?php
session_start();
require_once "includes/db.php";
?>
<!DOCTYPE html>
<html>
<head>
  <title>Top Pets - Simpets</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ccc;
      text-align: center;
    }
    th {
      background-color: #f2f2f2;
    }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Top Pets on Simpets</h1>
  <table>
    <tr>
      <th>Rank</th>
      <th>Pet Name</th>
      <th>Owner</th>
      <th>Type</th>
      <th>Level</th>
      <th>Boosts</th>
      <th>Offspring</th>
      <th>Gen</th>
    </tr>
<?php
$stmt = $pdo->query("SELECT user_pets.*, users.username 
                     FROM user_pets 
                     JOIN users ON user_pets.user_id = users.id 
                     ORDER BY boosts DESC 
                     LIMIT 25");
$rank = 1;
while ($pet = $stmt->fetch()) {
    $gen = (empty($pet['mother']) && empty($pet['father'])) ? '1' : '2+';
    echo "<tr>
            <td>{$rank}</td>
            <td>" . htmlspecialchars($pet['pet_name']) . "</td>
            <td>" . htmlspecialchars($pet['username']) . "</td>
            <td>{$pet['type']}</td>
            <td>{$pet['level']}</td>
            <td>{$pet['boosts']}</td>
            <td>{$pet['offspring']}</td>
            <td>{$gen}</td>
          </tr>";
    $rank++;
}
?>
  </table>
</div>
</body>
</html>
