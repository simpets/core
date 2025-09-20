<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

ini_set("display_errors", 1);
error_reporting(E_ALL);

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
  <h1>Breed Your Pets (Level 3 Only)</h1>
  <?php if (count($pets) < 2): ?>
    <p>You need at least two Level 3 pets to breed.</p>
  <?php else: ?>
    <form method="post" action="breed_action.php">
      <label>Select Pet 1:</label>
      <select name="pet1" required>
        <?php foreach ($pets as $pet): ?>
          <?php
            $image = $pet['pet_image'];
            if (empty($image)) {
                $stmt = $pdo->prepare("SELECT image FROM levels WHERE pet_type = ? AND level = ?");
                $stmt->execute([$pet['type'], $pet['level']]);
                $image = $stmt->fetchColumn();
            }
          ?>
          <option value="<?= $pet['id'] ?>"><?= $pet['pet_name'] ?> (<?= $pet['type'] ?>)</option>
        <?php endforeach; ?>
      </select><br><br>

      <label>Select Pet 2:</label>
      <select name="pet2" required>
        <?php foreach ($pets as $pet): ?>
          <option value="<?= $pet['id'] ?>"><?= $pet['pet_name'] ?> (<?= $pet['type'] ?>)</option>
        <?php endforeach; ?>
      </select><br><br>

      <label for="offspring_name">Offspring Name:</label>
      <input type="text" id="offspring_name" name="offspring_name" placeholder="Enter a name for your baby pet" required><br><br>

      <input type="submit" value="Breed">
    </form>
  <?php endif; ?>
</div>
</body>
</html>