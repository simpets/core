<?php
require_once "includes/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's pets
$stmt = $pdo->prepare("SELECT id, pet_name, pet_image, type FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_pet_id = $_POST['pet_id'] ?? null;
$marking1 = $_POST['marking1'] ?? null;
$color1 = $_POST['color1'] ?? "#000000";
$marking2 = $_POST['marking2'] ?? null;
$color2 = $_POST['color2'] ?? "#000000";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_pet_id && $marking1 && $marking2) {
    // Get pet info
    $pet_stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $pet_stmt->execute([$selected_pet_id, $user_id]);
    $pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);

    if ($pet) {
        $base_path = "images/customs/" . basename($pet['pet_image']);
        if (!file_exists($base_path)) {
            $base_path = "images/generated/" . basename($pet['pet_image']);
        }
        if (!file_exists($base_path)) {
            die("Could not find the pet image.");
        }

        $base = imagecreatefrompng($base_path);
        imagesavealpha($base, true);

        // Marking 1
        $marking_path1 = "images/markings/{$pet['type']}/" . basename($marking1);
        if (file_exists($marking_path1)) {
            $mark1 = imagecreatefrompng($marking_path1);
            imagefilter($mark1, IMG_FILTER_COLORIZE,
                hexdec(substr($color1, 1, 2)),
                hexdec(substr($color1, 3, 2)),
                hexdec(substr($color1, 5, 2)));
            imagecopy($base, $mark1, 0, 0, 0, 0, imagesx($mark1), imagesy($mark1));
            imagedestroy($mark1);
        }

        // Marking 2
        $marking_path2 = "images/markings/{$pet['type']}/" . basename($marking2);
        if (file_exists($marking_path2)) {
            $mark2 = imagecreatefrompng($marking_path2);
            imagefilter($mark2, IMG_FILTER_COLORIZE,
                hexdec(substr($color2, 1, 2)),
                hexdec(substr($color2, 3, 2)),
                hexdec(substr($color2, 5, 2)));
            imagecopy($base, $mark2, 0, 0, 0, 0, imagesx($mark2), imagesy($mark2));
            imagedestroy($mark2);
        }

        $filename = uniqid("pet_", true) . ".png";
        $save_path = "images/customs/" . $filename;
        imagepng($base, $save_path);
        imagedestroy($base);

        $update = $pdo->prepare("UPDATE user_pets SET pet_image = ? WHERE id = ? AND user_id = ?");
        $update->execute([$filename, $selected_pet_id, $user_id]);
        $success = true;
    }
}

$type = $user_pets[0]['type'] ?? null;
if ($selected_pet_id) {
    foreach ($user_pets as $p) {
        if ($p['id'] == $selected_pet_id) {
            $type = $p['type'];
            break;
        }
    }
}
$markings = [];
if ($type) {
    $markings_dir = "images/markings/$type";
    if (is_dir($markings_dir)) {
        foreach (glob("$markings_dir/*.png") as $m) {
            $markings[] = basename($m);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Make A Custom - Two Markings</title>
</head>
<body>
    <h1>Make A Custom - Add Two Markings</h1>
    <?php if ($success): ?>
        <p style="color:green;">Two markings applied! View your pet on your dashboard.</p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="pet_id">Step 1. Choose your Pet:</label>
        <select name="pet_id" id="pet_id" required onchange="this.form.submit()">
            <option value="">-- Select --</option>
            <?php foreach ($user_pets as $pet): ?>
                <option value="<?= htmlspecialchars($pet['id']) ?>" <?= ($selected_pet_id==$pet['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($pet['pet_name']) ?> (<?= htmlspecialchars($pet['type']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($selected_pet_id): ?>
        <form method="POST">
            <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">
            <p>
                <img src="images/customs/<?= htmlspecialchars($pet['pet_image']) ?>" alt="Pet Image" width="200" style="background:#eee;">
            </p>
            <label for="marking1">Step 2. Choose First Marking:</label>
            <select name="marking1" id="marking1" required>
                <option value="">-- Select Marking --</option>
                <?php foreach ($markings as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($marking1==$m)?'selected':'' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="color1">Color:</label>
            <input type="color" name="color1" id="color1" value="<?= htmlspecialchars($color1) ?>"><br>
            <label for="marking2">Step 3. Choose Second Marking:</label>
            <select name="marking2" id="marking2" required>
                <option value="">-- Select Marking --</option>
                <?php foreach ($markings as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($marking2==$m)?'selected':'' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="color2">Color:</label>
            <input type="color" name="color2" id="color2" value="<?= htmlspecialchars($color2) ?>"><br>
            <button type="submit">Apply Markings!</button>
        </form>
    <?php endif; ?>
</body>
</html>