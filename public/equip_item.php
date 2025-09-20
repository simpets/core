<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit("Unauthorized.");
}

$user_id = $_SESSION['user_id'];
$item_id = (int) $_POST['id'];
$function_type = $_POST['type'] ?? '';
$pet_id = (int) $_POST['pet_id'] ?? 0;
$pos_x = (int) $_POST['x'];
$pos_y = (int) $_POST['y'];

// Validate slot type
$slot_map = [
    'add_toy1' => 'toy1',
    'add_toy2' => 'toy2',
    'add_toy3' => 'toy3',
    'add_deco' => 'deco'
];
if (!array_key_exists($function_type, $slot_map)) {
    exit("Invalid function type.");
}
$slot = $slot_map[$function_type];
$slot_x = $slot . "_x";
$slot_y = $slot . "_y";

// Get item
$stmt = $pdo->prepare("SELECT ui.id AS user_item_id, i.image FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.id = ? AND ui.user_id = ?");
$stmt->execute([$item_id, $user_id]);
$item = $stmt->fetch();

if (!$item) {
    exit("Item not found.");
}

$image = $item['image'];

// Equip to pet and store position
$update = $pdo->prepare("UPDATE user_pets SET {$slot} = ?, {$slot_x} = ?, {$slot_y} = ? WHERE id = ? AND user_id = ?");
$update->execute([$image, $pos_x, $pos_y, $pet_id, $user_id]);

// Remove from inventory
$stmt = $pdo->prepare("SELECT quantity FROM user_items WHERE id = ?");
$stmt->execute([$item_id]);
$qty = $stmt->fetchColumn();

if ($qty > 1) {
    $pdo->prepare("UPDATE user_items SET quantity = quantity - 1 WHERE id = ?")->execute([$item_id]);
} else {
    $pdo->prepare("DELETE FROM user_items WHERE id = ?")->execute([$item_id]);
}

echo "Equipped at ({$pos_x}, {$pos_y}).";