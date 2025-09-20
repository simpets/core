<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's pets
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Custom Maker (Inventory)</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .preview-img { width: 100px; height: 100px; border: 1px solid #999; }
    .form-section { margin-bottom: 20px; }
  </style>
  <script>
    function updatePreview() {
      const base = document.getElementById('base').value;
      const mark = document.getElementById('marking').value;

      document.getElementById('basePreview').src = base ? 'assets/' + base : '';
      document.getElementById('markPreview').src = mark ? 'assets/' + mark : '';
    }
  </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Custom Maker (from Owned Items)</h1>

  <?php if (empty($pets)): ?>
    <p>You don’t own any pets to customize.</p>
  <?php else: ?>
    <form method="POST" action="custom_maker_inventory.php">
      <div class="form-section">
        <label>Select Pet:</label>
        <select name="pet_id" required onchange="this.form.submit()">
          <option value="">Choose a pet</option>
          <?php foreach ($pets as $pet): ?>
            <option value="<?= $pet['id'] ?>" <?= isset($_POST['pet_id']) && $_POST['pet_id'] == $pet['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($pet['pet_name']) ?> (<?= $pet['type'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php
    if (isset($_POST['pet_id'])):
      $selected_pet_id = (int) $_POST['pet_id'];
      $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
      $stmt->execute([$selected_pet_id, $user_id]);
      $pet = $stmt->fetch();
      if (!$pet) {
          echo "<p>Invalid pet selected.</p>";
      } else {
          $type = $pet['type'];

          // Get base items
          $bases = $pdo->prepare("SELECT ui.id, i.name, i.image FROM user_items ui
            JOIN items i ON ui.item_id = i.id
            WHERE ui.user_id = ? AND i.function_type = 'set_base' AND i.name LIKE ? ");
          $bases->execute([$user_id, "$type%"]);
          $baseItems = $bases->fetchAll();

          // Get marking items
          $marks = $pdo->prepare("SELECT ui.id, i.name, i.image FROM user_items ui
            JOIN items i ON ui.item_id = i.id
            WHERE ui.user_id = ? AND i.function_type = 'add_marking' AND i.name LIKE ? ");
          $marks->execute([$user_id, "$type%"]);
          $markingItems = $marks->fetchAll();
    ?>
    <form method="POST" action="custom_maker_apply.php">
      <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">

      <div class="form-section">
        <label>Choose Base:</label>
        <select name="base_image" id="base" onchange="updatePreview()">
          <option value="">None</option>
          <?php foreach ($baseItems as $item): ?>
            <option value="<?= $item['image'] ?>"><?= htmlspecialchars($item['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <img id="basePreview" class="preview-img" src="">
      </div>

      <div class="form-section">
        <label>Choose Marking:</label>
        <select name="marking_image" id="marking" onchange="updatePreview()">
          <option value="">None</option>
          <?php foreach ($markingItems as $item): ?>
            <option value="<?= $item['image'] ?>"><?= htmlspecialchars($item['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <img id="markPreview" class="preview-img" src="">
      </div>

      <input type="submit" value="Apply to Pet">
    </form>
    <?php } endif; ?>
  <?php endif; ?>
</div>
</body>
</html>