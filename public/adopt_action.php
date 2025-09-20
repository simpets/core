<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = $_POST['pet_id'] ?? null;
$pet_name = trim($_POST['pet_name'] ?? '');
$gender = $_POST['gender'] ?? '';

if (!$pet_id || !$pet_name || !$gender) {
    header("Location: adopt.php");
    exit;
}

// Lookup pet template
$stmt = $pdo->prepare("SELECT name, image FROM pets_available WHERE id = ?");
$stmt->execute([$pet_id]);
$template = $stmt->fetch();

if (!$template) {
    echo "Invalid pet type."; exit;
}

// Clone pet into user's collection
$stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_name, pet_image, gender) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $pet_name, $template['image'], $gender]);

header("Location: dashboard.php?adopted=1");
exit;
?>