<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "includes/db.php";

echo "<h2>Testing Level Image Lookup</h2>";

$user_id = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT id, pet_name, type, level FROM user_pets WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$pet = $stmt->fetch();

if (!$pet) {
    echo "No pet found for user ID {$user_id}";
    exit;
}

echo "<p>Pet Name: " . htmlspecialchars($pet['pet_name'] ?? '') . "</p>";
echo "<p>Type: " . htmlspecialchars($pet['type'] ?? '') . "</p>";
echo "<p>Level: " . htmlspecialchars($pet['level'] ?? '') . "</p>";

$stmt2 = $pdo->prepare("SELECT image FROM levels WHERE pet_type = ? AND level = ?");
$stmt2->execute([$pet['type'], $pet['level']]);
$image = $stmt2->fetchColumn();

if ($image) {
    echo "<p>Image Path: $image</p>";
    echo '<img src="' . $image . '" width="300">';
} else {
    echo "<p style='color:red;'>No image found in levels table for this type and level.</p>";
}
?>
