<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    if ($name && $image && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO shop_items (name, image, price) VALUES (?, ?, ?)");
        $stmt->execute([$name, $image, $price]);
    }
}

$stmt = $pdo->query("SELECT * FROM shop_items ORDER BY id DESC");
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Manage Items</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Simpets Admin</h1></header>
<div class="container">
    <h2>Shop Items</h2>
    <?php foreach ($items as $item): ?>
        <div class="card">
            <strong><?= htmlspecialchars($item['name']) ?> (<?= $item['price'] ?> coins)</strong><br>
            <img src="<?= htmlspecialchars($item['image']) ?>" width="100">
        </div>
    <?php endforeach; ?>

    <h3>Add New Item</h3>
    <form method="post">
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Image URL: <input type="text" name="image" required></label><br>
        <label>Price: <input type="number" name="price" min="1" required></label><br>
        <input type="submit" value="Add Item">
    </form>
</div>
</body>
</html>