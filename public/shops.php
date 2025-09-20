<?php
require_once "includes/db.php";


$stmt = $pdo->query("SELECT id, name FROM shops WHERE is_active = 1 ORDER BY name");
$shops = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html>
<head>
  <title>Shops</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Available Shops</h1>
  <ul>
    <?php foreach ($shops as $shop): ?>
      <li><a href="view_shop.php?id=<?= $shop['id'] ?>"><?= htmlspecialchars($shop['name']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</div>
</body>

  <?php include 'footer.php'; ?>

</html>