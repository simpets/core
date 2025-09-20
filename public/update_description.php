<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pet_id = (int) $_POST['pet_id'];
    $description = trim($_POST['description']);

    if (strlen($description) > 500) {
        die("Description is too long. Max 500 characters.");
    }

    // Check if this pet belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $_SESSION['user_id']]);
    $pet = $stmt->fetch();

    if (!$pet) {
        die("You do not own this pet.");
    }

    // Update the description
    $update = $pdo->prepare("UPDATE user_pets SET description = ? WHERE id = ?");
    $update->execute([$description, $pet_id]);

    header("Location: pet_profile.php?id=" . $pet_id);
    exit;
} else {
    die("Invalid request.");
}