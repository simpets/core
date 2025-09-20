<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ---------- helpers ---------- */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function is_token_name($name): bool {
    return str_contains(strtolower((string)$name), 'token');
}

/* ---------- data ---------- */

// Get user pets
$petstmt = $pdo->prepare("SELECT id, pet_name FROM user_pets WHERE user_id = ?");
$petstmt->execute([$user_id]);
$pets = $petstmt->fetchAll(PDO::FETCH_ASSOC);

// Get user items
$stmt = $pdo->prepare("
    SELECT ui.id, ui.quantity, i.id AS item_id, i.name, i.image, i.function_type
    FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.user_id = ?
    ORDER BY i.name ASC, ui.id ASC
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Inventory</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .item-block { display: inline-block; width: 180px; text-align: center; margin: 10px; vertical-align: top; }
    .item-block img { width: 64px; height: 64px; object-fit: contain; }
    select { margin-top: 6px; }
    .success { color: green; font-weight: bold; margin-bottom: 10px; }
    .muted { color:#666; font-size: 12px; }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Your Inventory</h1>

  <?php if (isset($_GET['used'], $_GET['item'], $_GET['pet'])): ?>
    <p class="success">✅ You have used a <?= e($_GET['item']) ?> on <?= e($_GET['pet']) ?>!</p>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <p>You don't have any items.</p>
  <?php else: ?>
    <div class="inventory">
      <?php foreach ($items as $item): ?>
        <div class="item-block">
          <img src="assets/<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>"><br>
          <strong><?= e($item['name']) ?></strong><br>
          Quantity: <?= (int)$item['quantity'] ?><br>

          <?php
            // SHOW "Use on..." for all items EXCEPT those whose name contains "token"
            $showUse = (count($pets) > 0) && !is_token_name($item['name']);
          ?>

          <?php if ($showUse): ?>
            <form method="get" action="use_item.php">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <select name="pet_id" required onchange="this.form.submit()">
                <option value="">Use on...</option>
                <?php foreach ($pets as $pet): ?>
                  <option value="<?= (int)$pet['id'] ?>"><?= e($pet['pet_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          <?php else: ?>
            <div class="muted">Token - This Will Enable A Custom!</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p><a href="dashboard.php">Return to Dashboard</a></p>
</div>
</body>

<?php include 'footer.php'; ?>
</html>