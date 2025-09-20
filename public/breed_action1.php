<?php
session_start();
ob_start(); // Prevent header issues from any warning output
require_once "includes/db.php";

// Optional: hide non-critical warnings
ini_set("display_errors", 0);
error_reporting(E_ALL & ~E_WARNING);

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Accept both naming conventions
$id1 = $_POST['parent1'] ?? $_POST['pet1'] ?? null;
$id2 = $_POST['parent2'] ?? $_POST['pet2'] ?? null;
$offspring_name = trim($_POST['offspring_name'] ?? '');

if (!$id1 || !$id2 || !$offspring_name) {
    die("Missing required fields. (id1: $id1, id2: $id2, name: $offspring_name)");
}

// Get parent pets
$stmt1 = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt1->execute([$id1, $user_id]);
$p1 = $stmt1->fetch();

$stmt2 = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt2->execute([$id2, $user_id]);
$p2 = $stmt2->fetch();

if (!$p1 || !$p2) {
    die("Invalid pets selected.");
}

if ($p1['type'] !== $p2['type']) {
    die("Pets must be the same type to breed.");
}

// New offspring starts at level 1 → get egg image
$stmt = $pdo->prepare("SELECT image FROM levels WHERE pet_type = ? AND level = 1 LIMIT 1");
$stmt->execute([$p1['type']]);
$petImage = $stmt->fetchColumn();

// Fallback if egg image not found
if (empty($petImage)) {
    $petImage = 'images/default_egg.png'; // Use your default egg image here
}

// Insert new pet
$stmt = $pdo->prepare("INSERT INTO user_pets (
    user_id, pet_name, type, level, pet_image, gender, mother, father, boosts, offspring,
    background_url, toy1, toy2, toy3, deco, description, price
) VALUES (?, ?, ?, 1, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $user_id,
    $offspring_name,
    $p1['type'],
    $petImage,
    rand(0, 1) ? 'Male' : 'Female',
    $p1['pet_name'],
    $p2['pet_name'],
    '', '', '', '', '', '', 0
]);

// Update parents' offspring count
$pdo->prepare("UPDATE user_pets SET offspring = offspring + 1 WHERE id IN (?, ?)")->execute([$id1, $id2]);

// Done!
header("Location: dashboard.php?bred=1");
exit;
?>