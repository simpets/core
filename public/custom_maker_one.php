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

// Select pet and marking
$selected_pet_id = $_POST['pet_id'] ?? ($_GET['pet_id'] ?? null);
$selected_marking = $_POST['marking'] ?? null;
$selected_color = $_POST['color'] ?? "#000000";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_pet_id && $selected_marking) {
    // Get pet info
    $pet_stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $pet_stmt->execute([$selected_pet_id, $user_id]);
    $pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);











    if ($pet) {
    $base_path = "";
    if (!empty($pet['pet_image']) && file_exists("images/generated/" . basename($pet['pet_image']))) {
        $base_path = "images/generated/" . basename($pet['pet_image']);
    } elseif (!empty($pet['pet_image']) && file_exists("images/customs/" . basename($pet['pet_image']))) {
        $base_path = "images/customs/" . basename($pet['pet_image']);
    } elseif (file_exists("images/levels/{$pet['type']}/3.png")) {
        $base_path = "images/levels/{$pet['type']}/3.png";
    } else {
        die("Could not find the pet image.");
    }
}








        // Prepare image
        $base = imagecreatefrompng($base_path);
        imagesavealpha($base, true);

        // Load and color marking
        $marking_path = "images/markings/{$pet['type']}/" . basename($selected_marking);
        if (!file_exists($marking_path)) die("Could not find marking.");
        $mark = imagecreatefrompng($marking_path);
        imagesavealpha($mark, true);

        // Apply color
        imagefilter($mark, IMG_FILTER_COLORIZE,
            hexdec(substr($selected_color, 1, 2)),
            hexdec(substr($selected_color, 3, 2)),
            hexdec(substr($selected_color, 5, 2)));

        // Overlay marking
        imagecopy($base, $mark, 0, 0, 0, 0, imagesx($mark), imagesy($mark));
        imagedestroy($mark);

        // Save new image to images/generated/
        $filename = uniqid("pet_", true) . ".png";
        $save_path = "images/generated/" . $filename;
        imagepng($base, $save_path);
        imagedestroy($base);

        // Update pet record
        $update = $pdo->prepare("UPDATE user_pets SET pet_image = ? WHERE id = ? AND user_id = ?");
        $update->execute([$filename, $selected_pet_id, $user_id]);
        $success = true;

        // Update $pet variable for preview after submit
        $pet['pet_image'] = $filename;
    }


// Find all markings for chosen type (first pet by default)
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

// Find selected pet image for preview
$preview_pet = null;
foreach ($user_pets as $p) {
    if ($p['id'] == $selected_pet_id) {
        $preview_pet = $p;
        break;
    }
}
// If just saved, use updated image
if (isset($pet) && $success) $preview_pet = $pet;

// Try images/generated for preview
$preview_img = "";
if ($preview_pet && $preview_pet['pet_image']) {
    if (file_exists("images/generated/" . $preview_pet['pet_image'])) {
        $preview_img = "images/generated/" . $preview_pet['pet_image'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make A Custom - One Marking</title>
    <style>
    .preview-area {
        background: #eee;
        display: inline-block;
        width: 300px; height: 300px;
        position: relative;
    }
    .preview-area img {
        position: absolute; left: 0; top: 0;
        width: 300px; height: 300px;
        pointer-events: none;
    }
    </style>
</head>
<body>
    <h1>Make A Custom - Add One Marking</h1>
    <?php if ($success): ?>
        <p style="color:green;">Marking applied! View your pet on your dashboard.</p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" id="petselectform">
        <label for="pet_id">Step 1. Choose your Pet:</label>
        <select name="pet_id" id="pet_id" required onchange="document.getElementById('petselectform').submit()">
            <option value="">-- Select --</option>
            <?php foreach ($user_pets as $pet): ?>
                <option value="<?= htmlspecialchars($pet['id']) ?>" <?= ($selected_pet_id==$pet['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($pet['pet_name']) ?> (<?= htmlspecialchars($pet['type']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_pet_id): ?>
        <form method="POST" id="markingform">
            <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">
            <div class="preview-area" id="preview-area">
                <?php if ($preview_img): ?>
                    <img id="baseimg" src="<?= htmlspecialchars($preview_img) ?>?t=<?= time() ?>" alt="Pet Image">
                <?php endif; ?>
                <img id="markpreview" style="display:none;" />
            </div>
            <br>
            <label for="marking">Step 2. Choose Marking:</label>
            <select name="marking" id="marking" required>
                <option value="">-- Select Marking --</option>
                <?php foreach ($markings as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($selected_marking==$m)?'selected':'' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <label for="color">Marking Color:</label>
            <input type="color" name="color" id="color" value="<?= htmlspecialchars($selected_color) ?>">
            <br>
            <button type="submit">Apply Marking!</button>
        </form>

        <!-- LIVE PREVIEW SCRIPT -->
        <script>
        const markingsDir = <?= json_encode("images/markings/$type/") ?>;
        const previewArea = document.getElementById('preview-area');
        const markPreview = document.getElementById('markpreview');
        const markingSelect = document.getElementById('marking');
        const colorInput = document.getElementById('color');

        function updatePreview() {
            const selectedMark = markingSelect.value;
            if (!selectedMark) {
                markPreview.style.display = 'none';
                markPreview.src = '';
                return;
            }
            markPreview.style.display = '';
            markPreview.src = markingsDir + selectedMark + '?c=' + colorInput.value;

            // Try using CSS filter for color (for PNGs)
            const color = colorInput.value;
            markPreview.style.filter = 'drop-shadow(0 0 0 ' + color + ')';
        }

        markingSelect.addEventListener('change', updatePreview);
        colorInput.addEventListener('input', updatePreview);

        // On page load, try preview
        updatePreview();
        </script>
    <?php endif; ?>
</body>
</html>