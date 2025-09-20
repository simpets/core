<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$pet_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verify pet ownership
$stmt = $pdo->prepare("SELECT id, pet_name FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch();

if (!$pet) {
    die("You do not own this pet.");
}

// Fetch all pets except the selected one (any level, any user)
$stmt = $pdo->prepare("SELECT id, pet_name, type FROM user_pets WHERE id != ? ORDER BY pet_name");
$stmt->execute([$pet_id]);
$mates = $stmt->fetchAll();

// Handle mate assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mate_id'])) {
    $mate_id = intval($_POST['mate_id']);

    // Get mate name
    $stmt = $pdo->prepare("SELECT pet_name FROM user_pets WHERE id = ?");
    $stmt->execute([$mate_id]);
    $mate_name = $stmt->fetchColumn();

    if ($mate_name) {
        $stmt = $pdo->prepare("UPDATE user_pets SET mate_id = ?, mate = ? WHERE id = ?");
        $stmt->execute([$mate_id, $mate_name, $pet_id]);
    }

    header("Location: pet_profile.php?id=$pet_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add A Mate</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Select a Mate for <?= htmlspecialchars($pet['pet_name']) ?></h1>
  <?php if ($mates): ?>
  <form method="post">
    <label for="mate_id">Choose a mate:</label>
    <select name="mate_id" id="mate_id" required>
      <?php foreach ($mates as $mate): ?>
        <option value="<?= $mate['id'] ?>"><?= htmlspecialchars($mate['pet_name']) ?> (<?= $mate['type'] ?>)</option>
      <?php endforeach; ?>
    </select><br><br>
    <input type="submit" value="Assign Mate">
  </form>
  <?php else: ?>
    <p>No pets available to assign as a mate.</p>
  <?php endif; ?>
  <br>
  <a href="pet_profile.php?id=<?= $pet_id ?>" class="button">Cancel</a>
</div>
</body>
</html>