<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    if ($name && $image) {
        $stmt = $pdo->prepare("INSERT INTO pets_available (name, image) VALUES (?, ?)");
        $stmt->execute([$name, $image]);
    }
}

$stmt = $pdo->query("SELECT * FROM pets_available ORDER BY id DESC");
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Manage Pets</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Simpets Admin</h1></header>
<div class="container">
    <h2>Available Pets</h2>
    <?php foreach ($pets as $pet): ?>
        <div class="card">
            <strong><?= htmlspecialchars($pet['name']) ?></strong><br>
            <img src="<?= htmlspecialchars($pet['image']) ?>" width="100">
        </div>
    <?php endforeach; ?>

    <h3>Add New Pet</h3>
    <form method="post">
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Image URL: <input type="text" name="image" required></label><br>
        <input type="submit" value="Add Pet">
    </form>
</div>
</body>
</html>