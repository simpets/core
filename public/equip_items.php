<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = (int) ($_GET['pet_id'] ?? 0); // ✅ Corrected from 'id' to 'pet_id'

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if (!$pet || $pet['user_id'] != $user_id) {
    echo "<p>You do not own this pet.</p>";
    exit;
}

$inv = $pdo->prepare("SELECT ui.id, i.name, i.image, i.function_type FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.user_id = ? AND i.function_type IN ('add_toy1', 'add_toy2', 'add_toy3', 'add_deco')");
$inv->execute([$user_id]);
$inventory = $inv->fetchAll();

$slots = ['toy1', 'toy2', 'toy3', 'deco'];
$petImage = !empty($pet['pet_image'])
    ? htmlspecialchars($pet['pet_image'])
    : "images/levels/" . htmlspecialchars($pet['type']) . "_level" . $pet['level'] . ".png";
$background = $pet['background_url'] ? "assets/" . htmlspecialchars($pet['background_url']) : null;
?>
<!DOCTYPE html>
<html>
<head>
  <title>Equip Items (Drag-to-Place)</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .inventory img { width: 64px; height: 64px; margin: 5px; cursor: grab; }
    .pet-canvas {
      position: relative; width: 400px; height: 400px;
      border: 1px solid #999; margin-bottom: 20px;
      background-color: #f8f8f8;
    }
    .pet-canvas img.item {
      position: absolute; width: 64px; height: 64px;
    }
    .pet-canvas img.pet-core {
      width: 200px; height: 200px; position: absolute; top: 100px; left: 100px;
      z-index: 2;
    }
  </style>
  <script>
    let draggedItem = null;

    function dragStart(ev) {
      draggedItem = {
        id: ev.target.dataset.id,
        type: ev.target.dataset.type,
        image: ev.target.dataset.image
      };
    }

    function allowDrop(ev) {
      ev.preventDefault();
    }

    function dropItem(ev) {
      ev.preventDefault();
      if (!draggedItem) return;

      const rect = ev.currentTarget.getBoundingClientRect();
      const x = Math.floor(ev.clientX - rect.left);
      const y = Math.floor(ev.clientY - rect.top);

      const params = new URLSearchParams();
      params.append('id', draggedItem.id);
      params.append('type', draggedItem.type);
      params.append('pet_id', <?= $pet['id'] ?>);
      params.append('x', x);
      params.append('y', y);

      fetch('equip_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      }).then(() => location.reload());
    }

    function removeItem(slot, petId) {
      fetch("remove_item.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `slot=${slot}&pet_id=${petId}`
      }).then(() => location.reload());
    }
  </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Equip Items for <?= htmlspecialchars($pet['pet_name']) ?></h1>

  <div class="pet-canvas" ondrop="dropItem(event)" ondragover="allowDrop(event)">
    <?php if ($background): ?>
      <img src="<?= $background ?>" style="position:absolute; width: 400px; height: 400px; top: 0; left: 0; z-index: 0;">
    <?php endif; ?>

    <img src="<?= $petImage ?>" class="pet-core">

    <?php foreach ($slots as $index => $slot):
      $img = $pet[$slot];
      $x = $pet[$slot . '_x'];
      $y = $pet[$slot . '_y'];
      if ($img): ?>
        <img src="assets/<?= htmlspecialchars($img) ?>" class="item" style="top: <?= $y ?>px; left: <?= $x ?>px; z-index: <?= 3 + $index ?>;">
        <button onclick="removeItem('<?= $slot ?>', <?= $pet['id'] ?>)"
          style="position:absolute; top:<?= 10 + $index * 30 ?>px; right:10px; z-index:99;">Remove <?= $slot ?></button>
      <?php endif;
    endforeach; ?>
  </div>

  <h3>Your Inventory (Toys & Deco)</h3>
  <div class="inventory">
    <?php foreach ($inventory as $item): ?>
      <img src="assets/<?= htmlspecialchars($item['image']) ?>"
           data-id="<?= $item['id'] ?>"
           data-type="<?= $item['function_type'] ?>"
           data-image="<?= $item['image'] ?>"
           draggable="true"
           ondragstart="dragStart(event)"
           title="<?= htmlspecialchars($item['name']) ?>">
    <?php endforeach; ?>
  </div>

  <p><a href="pet_profile.php?id=<?= $pet['id'] ?>">Back to Pet</a></p>
</div>
</body>
</html>