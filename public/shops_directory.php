<?php
session_start();
require_once "includes/db.php";

// Fetch all shops and join with usernames
$stmt = $pdo->query("
    SELECT s.user_id, s.shop_name, s.shop_image, s.shop_description, u.username
    FROM user_shops s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.shop_name
");
$shops = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
   <center> <title>All Member Shops</title>
    <style>
    .shop-card {
        display: inline-block;
        width: 410px;
        vertical-align: top;
        border: 1px solid #bbb;
        margin: 15px;
        padding: 8px;
        text-align: center;
        background: #faf7f0;
        border-radius: 10px;
    }
    .shop-card img { max-width: 400px; max-height: 400px; border-radius: 8px; }
    </style>
</head>
<body>
    
        <?php include "menu.php"; ?>
    <h1>Member Shops Directory</h1>
    <?php if ($shops): ?>
        <?php foreach ($shops as $shop): ?>
            <div class="shop-card">
                <?php if ($shop['shop_image']): ?>
                    <img src="<?= htmlspecialchars($shop['shop_image']) ?>" alt="Shop Image"><br>
                <?php endif; ?>
                <h2><?= htmlspecialchars($shop['shop_name']) ?></h2>
                <strong>Owner:</strong> <?= htmlspecialchars($shop['username']) ?><br>
                <?php if ($shop['shop_description']): ?>
                    <div style="font-size: 0.95em; color: #444; margin: 8px 0;"><?= nl2br(htmlspecialchars($shop['shop_description'])) ?></div>
                <?php endif; ?>
                <a href="member_shop.php?id=<?= $shop['user_id'] ?>">Visit Shop</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No shops have been created yet!</p>
    <?php endif; ?>
</body>
</html>