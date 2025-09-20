<?php
session_start();
require_once "includes/db.php";


if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Check for Custom Token 2
$stmt = $pdo->prepare("SELECT quantity FROM user_items JOIN items ON user_items.item_id = items.id WHERE user_id = ? AND items.name = 'Custom Token 2'");
$stmt->execute([$user_id]);
$hasToken = $stmt->fetchColumn();

if (!$hasToken) die("You need a Custom Token 2 to use this.");

// Get available types
$types = $pdo->query("SELECT DISTINCT type FROM adopts")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pet_name = trim($_POST['pet_name']);
    $type = $_POST['type'];
    $gender = $_POST['gender'];
    $marking1 = $_POST['marking1'];
    $color1 = $_POST['color1'];
    $marking2 = $_POST['marking2'];
    $color2 = $_POST['color2'];

    if (!$pet_name || !$type || !$gender || !$marking1 || !$marking2) {
        $error = "All fields are required.";
    } else {
        $base_image = "images/levels/{$type}.png";
        $marking_image1 = "images/markings/{$type}/{$marking1}";
        $marking_image2 = "images/markings/{$type}/{$marking2}";

        if (!file_exists($base_image) || !file_exists($marking_image1) || !file_exists($marking_image2)) {
            $error = "One or more images could not be found.";
        } else {
            $base = imagecreatefrompng($base_image);
            imagesavealpha($base, true);
            imagealphablending($base, true);

            // Apply first marking
            $mark1 = imagecreatefrompng($marking_image1);
            list($r1, $g1, $b1) = sscanf($color1, "#%02x%02x%02x");
            imagefilter($mark1, IMG_FILTER_COLORIZE, $r1, $g1, $b1);
            imagecopy($base, $mark1, 0, 0, 0, 0, imagesx($mark1), imagesy($mark1));
            imagedestroy($mark1);

            // Apply second marking
            $mark2 = imagecreatefrompng($marking_image2);
            list($r2, $g2, $b2) = sscanf($color2, "#%02x%02x%02x");
            imagefilter($mark2, IMG_FILTER_COLORIZE, $r2, $g2, $b2);
            imagecopy($base, $mark2, 0, 0, 0, 0, imagesx($mark2), imagesy($mark2));
            imagedestroy($mark2);

            // Save and insert
            $filename = "images/customs/pet_" . uniqid() . ".png";
            imagepng($base, $filename);
            imagedestroy($base);

            $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_name, type, gender, level, pet_image) VALUES (?, ?, ?, ?, 3, ?)");
$stmt->execute([
    $user_id,
    $pet_name,
    $type,
    $gender,
    $filename,
    
]);




            // Consume the token
            $pdo->prepare("UPDATE user_items JOIN items ON user_items.item_id = items.id SET quantity = quantity - 1 WHERE user_id = ? AND items.name = 'Custom Token 2'")->execute([$user_id]);

            $success = "Custom pet created successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>2-Mark Custom Pet</title>
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
        const type = document.querySelector("select[name='type']").value;
        const mark1 = document.querySelector("select[name='marking1']").value;
        const mark2 = document.querySelector("select[name='marking2']").value;
        const color1 = document.querySelector("input[name='color1']").value;
        const color2 = document.querySelector("input[name='color2']").value;

        const baseImg = document.getElementById("base");
        const overlay1 = document.getElementById("marking1");
        const overlay2 = document.getElementById("marking2");

        baseImg.src = "images/levels/" + type + ".png";
        overlay1.src = mark1 ? `colorize_marking.php?type=${type}&marking=${mark1}&color=${color1.substring(1)}` : "";
        overlay2.src = mark2 ? `colorize_marking.php?type=${type}&marking=${mark2}&color=${color2.substring(1)}` : "";
    }
  </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Custom Pet Maker (2 Markings)</h1>
  <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
  <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>

  <form method="post" oninput="updatePreview()">
    <label>Pet Name: <input type="text" name="pet_name" required></label><br><br>
    <label>Type:
        <select name="type" required onchange="this.form.submit()">
            <option value="">-- Select Type --</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= $t ?>" <?= isset($_POST['type']) && $_POST['type'] === $t ? "selected" : "" ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <?php if (!empty($_POST['type'])): ?>
        <label>Marking 1:
            <select name="marking1" onchange="updatePreview()" required>
                <option value="">-- Select Marking --</option>
                <?php
                $dir = "images/markings/" . $_POST['type'];
                foreach (scandir($dir) as $file) {
                    if ($file !== "." && $file !== "..") {
                        echo "<option value='$file'>$file</option>";
                    }
                }
                ?>
            </select>
        </label>
        <label>Color 1: <input type="color" name="color1" value="#000000" onchange="updatePreview()" required></label><br><br>

        <label>Marking 2:
            <select name="marking2" onchange="updatePreview()" required>
                <option value="">-- Select Marking --</option>
                <?php
                foreach (scandir($dir) as $file) {
                    if ($file !== "." && $file !== "..") {
                        echo "<option value='$file'>$file</option>";
                    }
                }
                ?>
            </select>
        </label>
        <label>Color 2: <input type="color" name="color2" value="#000000" onchange="updatePreview()" required></label><br><br>
    <?php endif; ?>

    <label>Gender:
        <select name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </label><br><br>

    <div id="preview">
        <img id="base" src="" alt="">
        <img id="marking1" src="" alt="">
        <img id="marking2" src="" alt="">
    </div>

    <input type="submit" value="Create Pet">
  </form>
</div>

<?php include 'footer.php'; ?>
</body>

</html>