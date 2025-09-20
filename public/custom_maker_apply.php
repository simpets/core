<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

$user_id = $_SESSION['user_id'];
$pet_id = (int)($_POST['pet_id'] ?? 0);
$base_image = $_POST['base_image'] ?? '';
$markings = [
    $_POST['marking1'] ?? '',
    $_POST['marking2'] ?? '',
    $_POST['marking3'] ?? ''
];

// Filter out empty markings
$markings = array_filter($markings);

if (!$pet_id || (!$base_image && empty($markings))) {
    die("Missing required fields.");
}

// Fetch the pet
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch();

if (!$pet) {
    die("Pet not found or not owned by you.");
}

// Get list of owned item images
$allowedImages = [];
$imageToUserItemID = [];

$stmt = $pdo->prepare("SELECT ui.id AS user_item_id, i.image FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.user_id = ? AND i.function_type IN ('set_base', 'add_marking')");
$stmt->execute([$user_id]);

foreach ($stmt as $row) {
    $img = $row['image'];
    $allowedImages[] = $img;
    if (!isset($imageToUserItemID[$img])) {
        $imageToUserItemID[$img] = $row['user_item_id'];
    }
}

if ($base_image && !in_array($base_image, $allowedImages)) {
    die("You do not own the selected base.");
}
foreach ($markings as $marking_image) {
    if (!in_array($marking_image, $allowedImages)) {
        die("You do not own one of the selected markings.");
    }
}

// Image processing
$finalWidth = 300;
$finalHeight = 300;

$merged = imagecreatetruecolor($finalWidth, $finalHeight);
imagealphablending($merged, false);
imagesavealpha($merged, true);
$transparent = imagecolorallocatealpha($merged, 0, 0, 0, 127);
imagefill($merged, 0, 0, $transparent);

// Apply base
if ($base_image && file_exists("assets/{$base_image}")) {
    $base = imagecreatefrompng("assets/{$base_image}");
    imagecopyresampled($merged, $base, 0, 0, 0, 0, $finalWidth, $finalHeight, imagesx($base), imagesy($base));
    imagedestroy($base);
}

// Apply markings
foreach ($markings as $marking_image) {
    if (file_exists("assets/{$marking_image}")) {
        $mark = imagecreatefrompng("assets/{$marking_image}");
        imagecopyresampled($merged, $mark, 0, 0, 0, 0, $finalWidth, $finalHeight, imagesx($mark), imagesy($mark));
        imagedestroy($mark);
    }
}

$finalImagePath = "images/generated/pet_" . time() . "_custom.png";
imagepng($merged, $finalImagePath);
imagedestroy($merged);

// Update pet with new image
$update = $pdo->prepare("UPDATE user_pets SET pet_image = ? WHERE id = ? AND user_id = ?");
$update->execute([$finalImagePath, $pet_id, $user_id]);

// Consume used items (base + markings)
$toConsume = array_merge([$base_image], $markings);
foreach ($toConsume as $img) {
    $user_item_id = $imageToUserItemID[$img] ?? null;
    if ($user_item_id) {
        $stmt = $pdo->prepare("SELECT quantity FROM user_items WHERE id = ?");
        $stmt->execute([$user_item_id]);
        $qty = $stmt->fetchColumn();
        if ($qty > 1) {
            $pdo->prepare("UPDATE user_items SET quantity = quantity - 1 WHERE id = ?")->execute([$user_item_id]);
        } else {
            $pdo->prepare("DELETE FROM user_items WHERE id = ?")->execute([$user_item_id]);
        }
    }
}

// Confirmation screen
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pet Updated</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Customization Applied!</h1>
    <p>Your pet has been updated with the selected base and markings.</p>
    <img src="<?= $finalImagePath ?>" width="300" height="300" alt="Updated Pet"><br><br>
    <a href="my_pets.php">Return to My Pets</a>
</div>
</body>
</html>