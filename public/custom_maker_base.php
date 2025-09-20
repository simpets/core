<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/image_tools.php';

if (!isset($_SESSION['user_id'])) die('Not logged in');
$user_id = $_SESSION['user_id'];

// Check for Base Token in inventory
$stmt = $pdo->prepare("SELECT quantity FROM user_items JOIN items ON user_items.item_id = items.id WHERE user_id = ? AND items.name = 'Base Token'");
$stmt->execute([$user_id]);
$hasToken = $stmt->fetchColumn();

if (!$hasToken) die('You need a Base Token to use this.');

$petTypes = ['Iron', 'Steel', 'Platinum', 'Diamond', 'Aether', 'Brass'];
$selectedType = $_POST['pet_type'] ?? '';
$selectedBase = $_POST['base'] ?? '';
$petName = $_POST['pet_name'] ?? '';


function getBases($type) {
    $folder = "base/$type";
    if (!is_dir($folder)) return [];
    return array_filter(scandir($folder), fn($f) => preg_match('/\.png$/', $f));
}

// Create pet and consume token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedType && $selectedBase && $petName) {
    $imagePath = "base/$selectedType/$selectedBase";
    $newFile = "images/customs/pet_" . time() . ".png";
    copy($imagePath, $newFile);

    // Insert pet
    $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_name, type, level, pet_image, base) VALUES (?, ?, ?, 3, ?, ?)");
    $stmt->execute([$user_id, $petName, $selectedType, $newFile, $selectedBase]);

    // Consume token
    $stmt = $pdo->prepare("UPDATE user_items SET quantity = quantity - 1 WHERE user_id = ? AND item_id = (SELECT id FROM items WHERE name = 'Base Token')");
    $stmt->execute([$user_id]);

    echo "<p>Custom pet created successfully!</p>";
    echo "<img src='$newFile' width='300'>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Base Custom Maker</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Create a Base Custom Pet</h1>
  <form method="post">
    <label>Select Pet Type:</label>
    <select name="pet_type" onchange="this.form.submit()">
      <option value="">--Choose--</option>
      <?php foreach ($petTypes as $type): ?>
        <option value="<?= $type ?>" <?= $type == $selectedType ? 'selected' : '' ?>><?= $type ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($selectedType): ?>
    <form method="post">
      <input type="hidden" name="pet_type" value="<?= htmlspecialchars($selectedType) ?>">
      <label>Name your pet:</label><br>
      <input type="text" name="pet_name" required><br><br>
      <label>Select a Base:</label><br>
      <?php foreach (getBases($selectedType) as $base): ?>
        <label>
          <input type="radio" name="base" value="<?= $base ?>" required>
          <img src="base/<?= $selectedType ?>/<?= $base ?>" width="100">
        </label>
      <?php endforeach; ?>
      <br><br>
      <input type="submit" value="Create Pet">
    </form>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>


</html>
