<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Fetch available pet types from adopts
$types = $pdo->query("SELECT DISTINCT type FROM adopts")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pet_name = trim($_POST['pet_name']);
    $type = $_POST['type'];
    $gender = $_POST['gender'];
    $marking = $_POST['marking'];
    $color = $_POST['color'];

    if (!$pet_name || !$type || !$gender || !$marking) {
        $error = "All fields are required.";
    } else {
        $base_image = "images/levels/" . $type . ".png";
        $marking_image = "images/markings/" . $type . "/" . $marking;

        if (!file_exists($base_image) || !file_exists($marking_image)) {
            $error = "Base image or marking not found.";
        } else {
            $base = imagecreatefrompng($base_image);
            $mark = imagecreatefrompng($marking_image);

            list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
            imagefilter($mark, IMG_FILTER_COLORIZE, $r, $g, $b);

            imagealphablending($base, true);
            imagesavealpha($base, true);
            imagecopy($base, $mark, 0, 0, 0, 0, imagesx($mark), imagesy($mark));

            $filename = "images/customs/pet_" . uniqid() . ".png";
            imagepng($base, $filename);
            imagedestroy($base);
            imagedestroy($mark);

            $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_name, type, gender, level, pet_image) VALUES (?, ?, ?, ?, 3, ?)");
            $stmt->execute([$user_id, $pet_name, $type, $gender, $filename]);
            $success = "Custom pet created successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Custom Maker</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Create a Custom Pet</h1>
    <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>

    <form method="post">
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
            <label>Marking:
                <select name="marking" required>
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
            </label><br><br>
            <label>Marking Color: <input type="color" name="color" value="#000000" required></label><br><br>
        <?php endif; ?>

        <label>Gender:
            <select name="gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </label><br><br>

        <input type="submit" value="Create Pet">
    </form>
</div>
</body>
</html>
