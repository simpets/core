<?php
session_start();
require_once "includes/db.php";

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();

$preview_image = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_id'], $_POST['base_file'])) {
    $pet_id = $_POST['pet_id'];
    $base_file = $_POST['base_file'];

    $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    $pet = $stmt->fetch();

    if (!$pet) die("Pet not found.");

    $new_base = "images/base/{$pet['type']}/{$base_file}";
    if (!file_exists($new_base)) die("Base not found.");

    // Create final image path and copy base
    $filename = "images/generated/pet_" . time() . ".png";
    copy($new_base, $filename);

    $stmt = $pdo->prepare("UPDATE user_pets SET pet_image = ?, base = ? WHERE id = ?");
    $stmt->execute([$filename, $base_file, $pet_id]);

    header("Location: pet_profile.php?id=" . $pet_id);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Base Swapper</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .base-option { display: inline-block; margin: 10px; text-align: center; }
        .base-option img { width: 150px; height: 150px; border: 2px solid #ccc; }
        .preview { margin-top: 20px; text-align: center; }
        .preview img { width: 300px; height: 300px; }
    </style>
    <script>
    function showPreview(imgPath) {
        document.getElementById("preview").src = imgPath;
    }
    </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Base Swapper</h1>
    <form method="post">
        <label>Select a pet:</label>
        <select name="pet_id" onchange="this.form.submit()">
            <option value="">Choose your pet</option>
            <?php foreach ($pets as $pet): ?>
                <option value="<?= $pet['id'] ?>" <?= ($_POST['pet_id'] ?? '') == $pet['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pet['pet_name']) ?> (<?= $pet['type'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php
    if (isset($_POST['pet_id'])):
        $selected_pet_id = $_POST['pet_id'];
        $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
        $stmt->execute([$selected_pet_id, $user_id]);
        $selected_pet = $stmt->fetch();
        $type = $selected_pet['type'];
        $base_dir = "images/base/{$type}/";
        $files = glob($base_dir . "*.png");
    ?>

    <form method="post">
        <input type="hidden" name="pet_id" value="<?= $selected_pet_id ?>">
        <h3>Select a base:</h3>
        <?php foreach ($files as $file): $base_name = basename($file); ?>
            <div class="base-option">
                <label>
                    <input type="radio" name="base_file" value="<?= $base_name ?>" onclick="showPreview('<?= $file ?>')">
                    <br>
                    <img src="<?= $file ?>" alt="<?= $base_name ?>">
                    <br><?= $base_name ?>
                </label>
            </div>
        <?php endforeach; ?>
        <br><br>
        <div class="preview">
            <h3>Preview</h3>
            <img id="preview" src="" alt="Live preview will appear here">
        </div>
        <br>
        <button type="submit">Apply Selected Base - NOTE:  This Is PERMANENT And Erases ALL Markings!</button>
    </form>
    <?php endif; ?>
</div>
</body>

 <?php include 'footer.php'; ?>

</html>
