<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT si.id, si.name, si.image, si.type, ui.quantity FROM user_items ui JOIN shop_items si ON ui.item_id = si.id WHERE ui.user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, pet_name, pet_image FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Drag & Drop Inventory</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .item { display:inline-block; margin:10px; padding:10px; border:1px solid #ccc; background:#fff; cursor:grab; }
    .pet-drop { border:2px dashed #ccc; padding:20px; margin:10px; width:180px; height:200px; display:inline-block; vertical-align:top; background:#fdfdfd; }
    .pet-drop.hover { background:#cce5ff; }
  </style>
</head>
<body>
<header><h1>Simpets</h1></header>
<div class="container">
  <h2>Drag and Drop to Equip Items</h2>
  <h3>Your Items</h3>
  <div id="items">
    <?php foreach ($items as $item): ?>
      <div class="item" draggable="true" data-item-id="<?= $item['id'] ?>" data-type="<?= $item['type'] ?>">
        <img src="<?= htmlspecialchars($item['image']) ?>" width="60"><br>
        <?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)
      </div>
    <?php endforeach; ?>
  </div>

  <h3>Your Pets</h3>
  <div id="pets">
    <?php foreach ($pets as $pet): ?>
      <div class="pet-drop" data-pet-id="<?= $pet['id'] ?>">
        <strong><?= htmlspecialchars($pet['pet_name']) ?></strong><br>
        <img src="<?= htmlspecialchars($pet['pet_image']) ?>" width="100"><br>
        <em>Drop item here</em>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.querySelectorAll('.item').forEach(item => {
  item.addEventListener('dragstart', e => {
    e.dataTransfer.setData('text/plain', JSON.stringify({
      item_id: item.dataset.itemId,
      type: item.dataset.type
    }));
  });
});

document.querySelectorAll('.pet-drop').forEach(drop => {
  drop.addEventListener('dragover', e => {
    e.preventDefault();
    drop.classList.add('hover');
  });

  drop.addEventListener('dragleave', () => drop.classList.remove('hover'));

  drop.addEventListener('drop', e => {
    e.preventDefault();
    drop.classList.remove('hover');
    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
    const petId = drop.dataset.petId;

    fetch('equip_drag.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `item_id=${data.item_id}&pet_id=${petId}&type=${data.type}`
    }).then(res => res.text()).then(txt => {
      alert(txt);
      location.reload();
    });
  });
});
</script>
</body>
</html>