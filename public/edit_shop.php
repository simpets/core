<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) die("Unauthorized.");

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? 'My Shop');
    $shop_description = trim($_POST['shop_description'] ?? '');
    $shop_image = null;

    // Handle image upload
    if (!empty($_FILES['shop_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['shop_image']['name'], PATHINFO_EXTENSION));
        $target = "images/shop_images/shop_{$user_id}." . $ext;
        if (move_uploaded_file($_FILES['shop_image']['tmp_name'], $target)) {
            $shop_image = $target;
        }
    }

    // Upsert shop row
    $stmt = $pdo->prepare("INSERT INTO user_shops (user_id, shop_name, shop_image, shop_description)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE shop_name=VALUES(shop_name), shop_image=IFNULL(VALUES(shop_image), shop_image), shop_description=VALUES(shop_description)");
    $stmt->execute([$user_id, $shop_name, $shop_image, $shop_description]);

    header("Location: my_shop.php");
    exit;
}

// Load current shop settings
$stmt = $pdo->prepare("SELECT * FROM user_shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

?>
<form method="post" enctype="multipart/form-data">
    <label>Shop Name: <input type="text" name="shop_name" maxlength="100" value="<?= htmlspecialchars($shop['shop_name'] ?? 'My Shop') ?>"></label><br>
    <label>Description: <br><textarea name="shop_description" rows="4" cols="50"><?= htmlspecialchars($shop['shop_description'] ?? '') ?></textarea></label><br>
    <label>Shop Image (400x400): <input type="file" name="shop_image"></label><br>
    <?php if (!empty($shop['shop_image'])): ?>
        <img src="<?= htmlspecialchars($shop['shop_image']) ?>" width="200"><br>
    <?php endif; ?>
    <button type="submit">Save Shop Settings</button>
</form>