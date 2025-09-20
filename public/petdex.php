<?php
session_start();
require_once "includes/db.php";

$typeFilter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT name, image FROM pets_available WHERE 1";
$params = [];

if ($typeFilter) {
    $query .= " AND name = ?";
    $params[] = $typeFilter;
}
if ($search) {
    $query .= " AND name LIKE ?";
    $params[] = "%" . $search . "%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pets = $stmt->fetchAll();

$typeStmt = $pdo->query("SELECT DISTINCT name FROM pets_available ORDER BY name ASC");
$types = $typeStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Simpets - Petdex</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .petdex-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }
    .petdex-card {
      background: #fffdf9;
      border: 1px solid #d4b895;
      border-radius: 8px;
      padding: 12px;
      text-align: center;
      width: 200px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    .petdex-card img {
      width: 150px;
      height: 150px;
      object-fit: contain;
    }
  </style>
</head>
<body>
<header><h1>Simpets</h1></header>
<nav>
  <a href="dashboard.php">Dashboard</a> |
  <a href="petdex.php">Petdex</a> |
  <a href="adopt.php">Adopt</a> |
  <a href="logout.php">Logout</a>
</nav>
<div class="container">
  <h2>Pet Encyclopedia</h2>
  <form method="get" style="margin-bottom:20px;">
    <input type="text" name="search" placeholder="Search pet name..." value="<?= htmlspecialchars($search) ?>">
    <select name="type">
      <option value="">All Types</option>
      <?php foreach ($types as $type): ?>
        <option value="<?= htmlspecialchars($type) ?>" <?= $type === $typeFilter ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="submit" value="Filter">
  </form>

  <div class="petdex-grid">
    <?php foreach ($pets as $pet): ?>
      <div class="petdex-card">
        <strong><?= htmlspecialchars($pet['name']) ?></strong><br>
        <img src="<?= htmlspecialchars($pet['image']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>"><br>
        <?php if (isset($_SESSION['user_id'])): ?>
          <form method="post" action="adopt_action.php">
            <input type="hidden" name="pet_id" value="<?= htmlspecialchars($pet['name']) ?>">
            <input type="text" name="pet_name" placeholder="Pet Name" required>
            <input type="submit" value="Adopt Now">
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>