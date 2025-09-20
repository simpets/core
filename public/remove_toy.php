<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = intval($_GET['pet_id'] ?? 0);
$slot = $_GET['slot'] ?? '';

if ($pet_id <= 0 || !in_array($slot, ['toy1', 'toy2', 'toy3', 'deco'])) {
    die("Invalid parameters.");
}

// Verify pet ownership and get the filename in that slot
$stmt = $pdo->prepare("SELECT {$slot}, user_id FROM user_pets WHERE id = ?");
$stmt->execute([$pet_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['user_id'] !== $user_id) {
    die("You do not own this pet or pet not found.");
}

$image = $row[$slot];
if (empty($image)) {
    die("No item equipped in that slot.");
}

// Determine the item_id by image filename
$itemStmt = $pdo->prepare("SELECT id FROM items WHERE image = ? LIMIT 1");
$itemStmt->execute([$image]);
$item_id = $itemStmt->fetchColumn();

if (!$item_id) {
    die("Item record not found.");
}

// Remove the item from the pet (set slot to NULL/empty)
$updatePet = $pdo->prepare("UPDATE user_pets SET {$slot} = NULL WHERE id = ? AND user_id = ?");
$updatePet->execute([$pet_id, $user_id]);

// Return the item to inventory: check if user_item exists
$userItemStmt = $pdo->prepare("SELECT quantity FROM user_items WHERE user_id = ? AND item_id = ?");
$userItemStmt->execute([$user_id, $item_id]);
$userItem = $userItemStmt->fetch(PDO::FETCH_ASSOC);

if ($userItem) {
    // Increment quantity
    $updateInventory = $pdo->prepare("UPDATE user_items SET quantity = quantity + 1 WHERE user_id = ? AND item_id = ?");
    $updateInventory->execute([$user_id, $item_id]);
} else {
    // Insert new row with quantity 1
    $insertInventory = $pdo->prepare("INSERT INTO user_items (user_id, item_id, quantity) VALUES (?, ?, 1)");
    $insertInventory->execute([$user_id, $item_id]);
}

// Redirect back to profile
header("Location: pet_profile.php?id={$pet_id}");
exit;
?>