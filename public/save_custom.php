<?php
session_start();
require_once "includes/db.php";

$pet_id = $_POST['pet_id'] ?? null;
$imgData = $_POST['final_image'] ?? null;

if (!$pet_id || !$imgData || !isset($_SESSION['user_id'])) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $_SESSION['user_id']]);
$pet = $stmt->fetch();

if (!$pet) {
    die("Pet not found.");
}

$data = explode(',', $imgData);
$decoded = base64_decode($data[1]);

$filename = "images/generated/pet_" . $pet_id . "_" . time() . ".png";
file_put_contents("/home/petsimon/public_html/Simpets/" . $filename, $decoded);

$update = $pdo->prepare("UPDATE user_pets SET image = ?, level = 3 WHERE id = ?");
$update->execute([$filename, $pet_id]);

header("Location: pet_profile.php?id=" . $pet_id);
?>
