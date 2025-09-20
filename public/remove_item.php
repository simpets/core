<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit("Unauthorized.");
}

$user_id = $_SESSION['user_id'];
$slot = $_POST['slot'] ?? '';
$pet_id = (int) $_POST['pet_id'] ?? 0;

$valid_slots = ['toy1', 'toy2', 'toy3', 'deco'];
if (!in_array($slot, $valid_slots)) {
    exit("Invalid slot.");
}

// Get current image in that slot
$stmt = $pdo->prepare("SELECT {$slot} FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$image = $stmt->fetchColumn();

if (!$image) {
    exit("No item equipped in this slot.");
}

// Find item ID by image
$item_stmt = $pdo->prepare("SELECT id FROM items WHERE image = ? LIMIT 1");
$item_stmt->execute([$image]);
$item_id = $item_stmt->fetchColumn();

if ($item_id) {
    // Check if already in user's inventory
    $inv_check = $pdo->prepare("SELECT id, quantity FROM user_items WHERE user_id = ? AND item_id = ?");
    $inv_check->execute([$user_id, $item_id]);
    $owned = $inv_check->fetch();

    if ($owned) {
        $pdo->prepare("UPDATE user_items SET quantity = quantity + 1 WHERE id = ?")
            ->execute([$owned['id']]);
    } else {
        $pdo->prepare("INSERT INTO user_items (user_id, item_id, quantity) VALUES (?, ?, 1)")
            ->execute([$user_id, $item_id]);
    }
}

// Clear the slot in the pet
$pdo->prepare("UPDATE user_pets SET {$slot} = NULL WHERE id = ? AND user_id = ?")
    ->execute([$pet_id, $user_id]);

echo "Removed.";