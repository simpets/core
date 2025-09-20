<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ? AND level = 3");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Breed Pets</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Breed Your Pets</h1>

  <?php if (count($pets) < 2): ?>
    <p>You need at least two Level 3 pets to breed.</p>
  <?php else: ?>
    <form method="post" action="breed_action.php">
      <label for="parent1">Select Pet 1:</label><br>
      <select name="parent1" required>
        <?php foreach ($pets as $pet): ?>
          <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?> (<?= $pet['type'] ?>)</option>
        <?php endforeach; ?>
      </select><br><br>

      <label for="parent2">Select Pet 2:</label><br>
      <select name="parent2" required>
        <?php foreach ($pets as $pet): ?>
          <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?> (<?= $pet['type'] ?>)</option>
        <?php endforeach; ?>
      </select><br><br>

      <label for="offspring_name">Offspring Name:</label><br>
      <input type="text" name="offspring_name" maxlength="100" required><br><br>

      <input type="submit" value="Breed Now">
    </form>
  <?php endif; ?>
</div>
</body>
</html>
