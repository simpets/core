<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to buy a pet.");
}

$buyer_id = $_SESSION['user_id'];
$pet_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($pet_id <= 0) {
    die("Invalid pet ID.");
}

// Get pet info
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();

if (!$pet || $pet['price'] <= 0) {
    die("This pet is not for sale.");
}

$seller_id = $pet['user_id'];
$price = (int) $pet['price'];

// Prevent self-purchase
if ($seller_id == $buyer_id) {
    die("You can't buy your own pet.");
}

// Get buyer's Simbucks
$stmt = $pdo->prepare("SELECT simbucks FROM users WHERE id = ?");
$stmt->execute([$buyer_id]);
$buyer_funds = $stmt->fetchColumn();

if ($buyer_funds < $price) {
    die("You don't have enough Simbucks.");
}

try {
    $pdo->beginTransaction();

    // Deduct Simbucks from buyer
    $stmt = $pdo->prepare("UPDATE users SET simbucks = simbucks - ? WHERE id = ?");
    $stmt->execute([$price, $buyer_id]);

    // Add Simbucks to seller
    $stmt = $pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?");
    $stmt->execute([$price, $seller_id]);

    // Transfer ownership and mark not for sale
    $stmt = $pdo->prepare("UPDATE user_pets SET user_id = ?, price = 0 WHERE id = ?");
    $stmt->execute([$buyer_id, $pet_id]);

    // Optional sale log
    $stmt = $pdo->prepare("INSERT INTO pet_sales (pet_id, seller_id, buyer_id, price, date_sold)
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$pet_id, $seller_id, $buyer_id, $price]);

    $pdo->commit();

    header("Location: pet_profile.php?id=$pet_id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Transaction failed: " . $e->getMessage());
}
?>