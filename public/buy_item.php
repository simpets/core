<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$item_id = $_POST['item_id'] ?? null;

if (!$item_id) {
    die("No item selected.");
}

// Fetch item from database
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    die("Item not found.");
}

$price = (int) $item['price'];

// Check user currency
$stmt = $pdo->prepare("SELECT simbucks FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_currency = $stmt->fetchColumn();

if ($user_currency < $price) {
    die("You don't have enough Simbucks.");
}


// Deduct currency
$stmt = $pdo->prepare("UPDATE users SET simbucks = simbucks - ? WHERE id = ?");
$stmt->execute([$price, $user_id]);

// Add item to user_items
$stmt = $pdo->prepare("SELECT id FROM user_items WHERE user_id = ? AND item_id = ?");
$stmt->execute([$user_id, $item_id]);
$user_item_id = $stmt->fetchColumn();

if ($user_item_id) {
    $pdo->prepare("UPDATE user_items SET quantity = quantity + 1 WHERE id = ?")->execute([$user_item_id]);
} else {
    $pdo->prepare("INSERT INTO user_items (user_id, item_id, quantity) VALUES (?, ?, 1)")
        ->execute([$user_id, $item_id]);
}

// Fetch shop_id to redirect properly
$stmt = $pdo->prepare("SELECT shop_id FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$shop_id = $stmt->fetchColumn();

// Final redirect with confirmation
header("Location: view_shop.php?id={$shop_id}&bought=1&name=" . urlencode($item['name']) . "&price=" . $price);
exit;
?>