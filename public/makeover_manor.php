<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) die("Unauthorized");
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// --- STEP 1: Choose Pet ---
$pet_id = $_GET['pet_id'] ?? $_POST['pet_id'] ?? null;
if (!$pet_id) {
    // List owned pets
    $pets = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
    $pets->execute([$user_id]);
    $pets = $pets->fetchAll();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Makeover Manor - Pick a Pet</title>
        <link rel="stylesheet" href="assets/styles.css">
    </head>
    <body>
    <?php include 'menu.php'; ?>
    <div class="container">
        <h1>Makeover Manor: Pick a Pet</h1>
        <?php if (empty($pets)): ?>
            <p>You don’t own any pets.</p>
        <?php else: ?>
            <form method="get">
                <label>Choose a Pet:
                    <select name="pet_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($pets as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['pet_name']) ?> (<?= htmlspecialchars($p['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <input type="submit" value="Next">
            </form>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php exit;
}

// --- STEP 2: Choose Marking & Color for THIS pet type ---
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch();
if (!$pet) die("Pet not found or not yours.");
$type = $pet['type'];
$level = $pet['level'];

// Find pet image
$pet_img = !empty($pet['pet_image'])
    ? $pet['pet_image']
    : "images/levels/{$type}_{$level}.png";

// Marking slots
$slots = ['marking1', 'marking2', 'marking3', 'marking4', 'marking5'];
$next_slot = null;
foreach ($slots as $s) {
    if (empty($pet[$s])) {
        $next_slot = $s;
        break;
    }
}

// Only show markings for this type that the user owns
$stmt = $pdo->prepare("SELECT ui.id AS user_item_id, i.name, i.image, ui.quantity
    FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.user_id = ? AND (i.function_type = 'add_marking' OR i.function_type LIKE 'add_marking%') AND ui.quantity > 0");
$stmt->execute([$user_id]);
$markings = $stmt->fetchAll();

$available_markings = [];
foreach ($markings as $m) {
    if (file_exists("assets/markings/$type/" . $m['image'])) {
        $m['imgpath'] = "assets/markings/$type/" . $m['image'];
        $available_markings[] = $m;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['marking'], $_POST['color'])) {
    $marking_id = $_POST['marking'];
    $color = $_POST['color'];

    // Find marking info in available_markings
    $chosen_marking = null;
    foreach ($available_markings as $m) {
        if ($m['user_item_id'] == $marking_id) {
            $chosen_marking = $m;
            break;
        }
    }
    if (!$chosen_marking) {
        $error = "Invalid marking.";
    } elseif (!$next_slot) {
        $error = "All marking slots are full.";
    } else {
        // Compose new image (just like custom maker)
        $base_image = $pet_img;
        $marking_image = $chosen_marking['imgpath'];
        if (!file_exists($base_image) || !file_exists($marking_image)) {
            $error = "Image file not found.";
        } else {
            $base = imagecreatefrompng($base_image);
            imagesavealpha($base, true);
            imagealphablending($base, true);

            $mark = imagecreatefrompng($marking_image);
            list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
            imagefilter($mark, IMG_FILTER_COLORIZE, $r, $g, $b);
            imagecopy($base, $mark, 0, 0, 0, 0, imagesx($mark), imagesy($mark));
            imagedestroy($mark);

            // Save composite
            $filename = "images/customs/pet_" . $pet['id'] . "_" . time() . ".png";
            imagepng($base, $filename);
            imagedestroy($base);

            // Update pet (add marking slot + new pet_image)
            $stmt = $pdo->prepare("UPDATE user_pets SET pet_image = ?, $next_slot = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$filename, $chosen_marking['image'], $pet['id'], $user_id]);

            // Remove one marking item from inventory
            if ($chosen_marking['quantity'] > 1) {
                $pdo->prepare("UPDATE user_items SET quantity = quantity - 1 WHERE id = ?")->execute([$marking_id]);
            } else {
                $pdo->prepare("DELETE FROM user_items WHERE id = ?")->execute([$marking_id]);
            }

            $success = "Marking applied successfully! <a href='pet_profile.php?id={$pet['id']}'>View Pet</a>";
            // Don't show form again
            $available_markings = [];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Makeover Manor</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    #preview {
        position: relative;
        width: 300px;
        height: 300px;
        margin: 20px auto;
    }
    #preview img {
        position: absolute;
        width: 100%;
        height: auto;
        top: 0;
        left: 0;
    }
  </style>
  <script>
    function updatePreview() {
        const markSel = document.querySelector("select[name='marking']");
        const colorSel = document.querySelector("input[name='color']");
        const overlay = document.getElementById("marking");
        const base = document.getElementById("base");
        if (markSel && overlay) {
            const marking = markSel.options[markSel.selectedIndex]?.getAttribute('data-img') || "";
            const color = colorSel.value.replace('#', '');
            overlay.src = marking ? `colorize_marking.php?type=<?= $type ?>&marking=${encodeURIComponent(marking.split('/').pop())}&color=${color}` : "";
        }
    }
    window.addEventListener('DOMContentLoaded', updatePreview);
  </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Makeover Manor</h1>
    <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>

    <?php if (count($available_markings) === 0 && !$success): ?>
        <p>You do not own any markings compatible with this pet type (<?= htmlspecialchars($type) ?>).</p>
    <?php elseif (!$success): ?>
        <form method="post" oninput="updatePreview()">
            <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
            <div id="preview">
                <img id="base" src="<?= htmlspecialchars($pet_img) ?>" alt="Base">
                <img id="marking" src="" alt="Marking">
            </div>
            <label>Marking:
                <select name="marking" required onchange="updatePreview()">
                    <option value="">-- Select Marking --</option>
                    <?php foreach ($available_markings as $m): ?>
                        <option value="<?= $m['user_item_id'] ?>" data-img="<?= $m['imgpath'] ?>">
                            <?= htmlspecialchars($m['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Color: <input type="color" name="color" value="#000000" onchange="updatePreview()" required></label><br><br>
            <input type="submit" value="Apply Marking">
        </form>
        <p><a href="makeover_manor.php">Pick a different pet</a></p>
    <?php endif; ?>
    <p><a href="dashboard.php">Return to Dashboard</a></p>
</div>
<?php include 'footer.php'; ?>

</body>
</html>