<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) die("Unauthorized.");
$buyer_id = $_SESSION['user_id'];
$listing_id = $_POST['listing_id'] ?? null;

if (!$listing_id) die("No listing selected.");

// Get listing
$stmt = $pdo->prepare("SELECT * FROM user_shop_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing || $listing['seller_id'] == $buyer_id || $listing['quantity'] < 1) die("Invalid listing.");

// Check buyer currency
$stmt = $pdo->prepare("SELECT simbucks FROM users WHERE id = ?");
$stmt->execute([$buyer_id]);
$canicash = $stmt->fetchColumn();

if ($canicash < $listing['price']) die("Not enough currency.");

// Do the transaction
$pdo->beginTransaction();
// Deduct buyer currency
$pdo->prepare("UPDATE users SET simbucks = simbucks - ? WHERE id = ?")->execute([$listing['price'], $buyer_id]);
// Add to seller
$pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?")->execute([$listing['price'], $listing['seller_id']]);
// Add to buyer's inventory (insert or update)
$stmt = $pdo->prepare("SELECT id FROM user_items WHERE user_id = ? AND item_id = ?");
$stmt->execute([$buyer_id, $listing['item_id']]);
$user_item_id = $stmt->fetchColumn();
if ($user_item_id) {
    $pdo->prepare("UPDATE user_items SET quantity = quantity + 1 WHERE id = ?")->execute([$user_item_id]);
} else {
    $pdo->prepare("INSERT INTO user_items (user_id, item_id, quantity) VALUES (?, ?, 1)")->execute([$buyer_id, $listing['item_id']]);
}
// Reduce listing quantity
$pdo->prepare("UPDATE user_shop_listings SET quantity = quantity - 1 WHERE id = ?")->execute([$listing_id]);
// If sold out, delete listing
$pdo->prepare("DELETE FROM user_shop_listings WHERE id = ? AND quantity <= 0")->execute([$listing_id]);
$pdo->commit();

header("Location: member_shop.php?id={$listing['seller_id']}&bought=1");
exit;
?>