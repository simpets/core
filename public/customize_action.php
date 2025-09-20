<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pet_name = trim($_POST['pet_name'] ?? '');
$color = $_POST['marking_color'] ?? '#000000';
$user_id = $_SESSION['user_id'];

if (!$pet_name || !$color) {
    header("Location: customize.php?error=1");
    exit;
}

list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");

$base_img = imagecreatefrompng("assets/base_pet.png");
$width = imagesx($base_img);
$height = imagesy($base_img);

$marking = imagecreatetruecolor($width, $height);
imagealphablending($marking, false);
imagesavealpha($marking, true);
$transparent = imagecolorallocatealpha($marking, 0, 0, 0, 127);
imagefill($marking, 0, 0, $transparent);

$marking_color = imagecolorallocatealpha($marking, $r, $g, $b, 60);
imagefilledellipse($marking, $width / 2, $height / 2, $width / 2, $height / 2, $marking_color);

imagecopy($base_img, $marking, 0, 0, 0, 0, $width, $height);
$filename = "assets/pets/custom_" . uniqid() . ".png";
imagepng($base_img, $filename);
imagedestroy($base_img);
imagedestroy($marking);

$stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_name, pet_image) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $pet_name, $filename]);

header("Location: dashboard.php?custom=1");
exit;