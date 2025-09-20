<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    die("Not authorized.");
}

$user_id = $_SESSION['user_id'];
$item_id = $_POST['item_id'] ?? null;
$pet_id = $_POST['pet_id'] ?? null;
$type = $_POST['type'] ?? '';

if (!$item_id || !$pet_id || !$type) {
    die("Missing data.");
}

if ($type == 'background') {
    $stmt = $pdo->prepare("UPDATE user_pets SET background = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $pet_id, $user_id]);
    echo "Background equipped!";
} else {
    $stmt = $pdo->prepare("UPDATE user_pets SET accessory = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $pet_id, $user_id]);
    echo "Accessory equipped!";
}
?>