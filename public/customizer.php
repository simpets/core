<?php
require_once "includes/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


$error = "";
$success = "";




// Check for Custom Token
$stmt = $pdo->prepare("SELECT quantity FROM user_items JOIN items ON user_items.item_id = items.id WHERE user_id = ? AND items.name = 'Deluxe Custom Token'");
$stmt->execute([$user_id]);
$hasToken = $stmt->fetchColumn();

if (!$hasToken) die("You need a Deluxe Custom Token to use this.");









// Get user's pets
$stmt = $pdo->prepare("SELECT id, pet_name, pet_image, type FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Collect form input
$selected_pet_id = $_POST['pet_id'] ?? null;
$selected_base = $_POST['base'] ?? null;
$selected_marking1 = $_POST['marking1'] ?? '';
$selected_color1 = $_POST['color1'] ?? '#000000';
$selected_marking2 = $_POST['marking2'] ?? '';
$selected_color2 = $_POST['color2'] ?? '#000000';
$success = false;

// Find type
$type = $user_pets[0]['type'] ?? null;
if ($selected_pet_id) {
    foreach ($user_pets as $p) {
        if ($p['id'] == $selected_pet_id) {
            $type = $p['type'];
            break;
        }
    }
}

// Load bases for type
$bases = [];
if ($type) {
    $bases_dir = "images/bases/$type";
    if (is_dir($bases_dir)) {
        foreach (glob("$bases_dir/*.png") as $b) {
            $bases[] = basename($b);
        }
    }
}

// Load markings for type
$markings = [];
if ($type) {
    $markings_dir = "images/markings/$type";
    if (is_dir($markings_dir)) {
        foreach (glob("$markings_dir/*.png") as $m) {
            $markings[] = basename($m);
        }
    }
}

// Handle the submit/save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_pet_id && $selected_base) {
    $pet_stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $pet_stmt->execute([$selected_pet_id, $user_id]);
    $pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);

    if ($pet) {
        $base_path = "images/bases/{$type}/" . basename($selected_base);
        if (!file_exists($base_path)) die("Could not find base.");

        $image = imagecreatefrompng($base_path);
        imagesavealpha($image, true);

        // Marking 1
        if ($selected_marking1 && file_exists("images/markings/{$type}/" . $selected_marking1)) {
            $mark1 = imagecreatefrompng("images/markings/{$type}/" . $selected_marking1);
            imagefilter($mark1, IMG_FILTER_COLORIZE,
                hexdec(substr($selected_color1, 1, 2)),
                hexdec(substr($selected_color1, 3, 2)),
                hexdec(substr($selected_color1, 5, 2))
            );
            imagecopy($image, $mark1, 0, 0, 0, 0, imagesx($mark1), imagesy($mark1));
            imagedestroy($mark1);
        }

        // Marking 2
        if ($selected_marking2 && file_exists("images/markings/{$type}/" . $selected_marking2)) {
            $mark2 = imagecreatefrompng("images/markings/{$type}/" . $selected_marking2);
            imagefilter($mark2, IMG_FILTER_COLORIZE,
                hexdec(substr($selected_color2, 1, 2)),
                hexdec(substr($selected_color2, 3, 2)),
                hexdec(substr($selected_color2, 5, 2))
            );
            imagecopy($image, $mark2, 0, 0, 0, 0, imagesx($mark2), imagesy($mark2));
            imagedestroy($mark2);
        }

        $filename = uniqid("pet_", true) . ".png";
        $save_path = "images/customs/" . $filename;
        imagepng($image, $save_path);
        imagedestroy($image);

       $update = $pdo->prepare("UPDATE user_pets SET pet_image = ? WHERE id = ? AND user_id = ?");
$update->execute(["images/customs/" . $filename, $selected_pet_id, $user_id]);


            // Consume the token
            $pdo->prepare("UPDATE user_items JOIN items ON user_items.item_id = items.id SET quantity = quantity - 1 WHERE user_id = ? AND items.name = 'Deluxe Custom Token'")->execute([$user_id]);










        $success = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Custom Maker: 2 Markings + Base</title>
    <style>
        .preview-box { width: 200px; height: 200px; background: #eee; }
        canvas { background: transparent; }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
<h1>Custom Maker: 2 Markings + Base</h1>
<?php if ($success): ?>
    <p style="color:green;">Custom pet updated! View on your dashboard.</p>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data" oninput="updatePreview()">
    <label for="pet_id">Step 1. Choose Your Pet:</label>
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
    <form method="POST" enctype="multipart/form-data" oninput="updatePreview()">
        <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <p>
            <strong>Base:</strong>
            <select name="base" id="base" required onchange="updatePreview()">
                <option value="">-- Select Base --</option>
                <?php foreach ($bases as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= ($selected_base==$b)?'selected':'' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <strong>Marking 1:</strong>
            <select name="marking1" id="marking1" onchange="updatePreview()">
                <option value="">-- None --</option>
                <?php foreach ($markings as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($selected_marking1==$m)?'selected':'' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="color" name="color1" id="color1" value="<?= htmlspecialchars($selected_color1) ?>">
        </p>
        <p>
            <strong>Marking 2:</strong>
            <select name="marking2" id="marking2" onchange="updatePreview()">
                <option value="">-- None --</option>
                <?php foreach ($markings as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($selected_marking2==$m)?'selected':'' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="color" name="color2" id="color2" value="<?= htmlspecialchars($selected_color2) ?>">
        </p>
        <button type="submit">Apply This *PERMANENT* Customization!</button>
        <div class="preview-box" style="margin-top:20px;">
            <canvas id="preview_canvas" width="200" height="200"></canvas>
        </div>
        <script>
        // Utility: tint PNG using Canvas
        function drawTintedMarking(ctx, img, hex, cb) {
            let tempCanvas = document.createElement('canvas');
            tempCanvas.width = img.width;
            tempCanvas.height = img.height;
            let tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(img, 0, 0);
            tempCtx.globalCompositeOperation = "source-atop";
            tempCtx.fillStyle = hex;
            tempCtx.globalAlpha = 0.8;
            tempCtx.fillRect(0, 0, img.width, img.height);
            tempCtx.globalAlpha = 1.0;
            ctx.drawImage(tempCanvas, 0, 0, 200, 200);
            cb && cb();
        }
        function updatePreview() {
            var type = <?= json_encode($type) ?>;
            var base = document.getElementById('base').value;
            var m1 = document.getElementById('marking1').value;
            var c1 = document.getElementById('color1').value;
            var m2 = document.getElementById('marking2').value;
            var c2 = document.getElementById('color2').value;

            var canvas = document.getElementById('preview_canvas');
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,200,200);

            // Draw base
            if (base) {
                let imgBase = new Image();
                imgBase.onload = function() {
                    ctx.drawImage(imgBase, 0, 0, 200, 200);

                    // Draw marking1
                    if (m1) {
                        let imgM1 = new Image();
                        imgM1.crossOrigin = "";
                        imgM1.onload = function() {
                            drawTintedMarking(ctx, imgM1, c1, function() {
                                // Draw marking2
                                if (m2) {
                                    let imgM2 = new Image();
                                    imgM2.crossOrigin = "";
                                    imgM2.onload = function() {
                                        drawTintedMarking(ctx, imgM2, c2);
                                    }
                                    imgM2.src = "images/markings/" + type + "/" + m2;
                                }
                            });
                        }
                        imgM1.src = "images/markings/" + type + "/" + m1;
                    } else if (m2) {
                        // If only marking2
                        let imgM2 = new Image();
                        imgM2.crossOrigin = "";
                        imgM2.onload = function() {
                            drawTintedMarking(ctx, imgM2, c2);
                        }
                        imgM2.src = "images/markings/" + type + "/" + m2;
                    }
                }
                imgBase.src = "images/bases/" + type + "/" + base;
            }
        }
        window.onload = updatePreview;
        </script>
    </form>
<?php endif; ?>
</body>

 <?php include 'footer.php'; ?>

</html>