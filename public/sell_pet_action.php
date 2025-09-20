<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Check that data is submitted correctly
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pet_id']) || !isset($_POST['price'])) {
    die("Invalid request.");
}

$pet_id = (int) $_POST['pet_id'];
$price = (int) $_POST['price'];

if ($price < 0) {
    die("Invalid price.");
}

// Confirm the pet belongs to the current user
$stmt = $pdo->prepare("SELECT id FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
if (!$stmt->fetch()) {
    die("This pet does not belong to you.");
}

// Update the pet's price
$stmt = $pdo->prepare("UPDATE user_pets SET price = ? WHERE id = ?");
$stmt->execute([$price, $pet_id]);

header("Location: pet_profile.php?id=" . $pet_id);
exit;
?>