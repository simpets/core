<?php
session_start();
require_once "includes/db.php";

ini_set("display_errors", 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];
$id1 = $_POST['pet1'] ?? null;
$id2 = $_POST['pet2'] ?? null;
$offspring_name = trim($_POST['offspring_name'] ?? '');

if (!$id1 || !$id2 || !$offspring_name) {
    die("Missing required fields. (id1: $id1, id2: $id2, name: $offspring_name)");
}

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

// Load parent images safely
$img1 = @imagecreatefrompng($p1['pet_image']);
$img2 = @imagecreatefrompng($p2['pet_image']);
if (!$img1 || !$img2) {
    die("Failed to load one or both parent images.");
}

imagealphablending($img1, true);
imagealphablending($img2, true);
imagesavealpha($img1, true);
imagesavealpha($img2, true);

// Create transparent canvas 300x300
$merged = imagecreatetruecolor(300, 300);
imagealphablending($merged, false);
imagesavealpha($merged, true);
$transparent = imagecolorallocatealpha($merged, 0, 0, 0, 127);
imagefill($merged, 0, 0, $transparent);

// Copy both images (preserve transparency)
imagecopy($merged, $img1, 0, 0, 0, 0, 300, 300);
imagecopy($merged, $img2, 0, 0, 0, 0, 300, 300);

// Save output
$filename = "images/generated/pet_" . time() . ".png";
if (!imagepng($merged, $filename)) {
    die("Failed to save merged image.");
}
imagedestroy($img1);
imagedestroy($img2);
imagedestroy($merged);

$petImage = $filename;

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

$pdo->prepare("UPDATE user_pets SET offspring = offspring + 1 WHERE id IN (?, ?)")->execute([$id1, $id2]);

header("Location: dashboard.php?bred=1");
exit;
?>