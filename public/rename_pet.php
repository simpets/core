<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get pet ID from URL
$pet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pet_id) {
    die("No pet selected.");
}

// Fetch the pet belonging to the current user
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch();

if (!$pet) {
    die("Pet not found or you do not own this pet.");
}

// Handle rename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
    $new_name = trim($_POST['new_name']);
    if ($new_name !== "" && strlen($new_name) <= 30) {
        $stmt = $pdo->prepare("UPDATE user_pets SET pet_name = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_name, $pet_id, $user_id]);
        header("Location: pet_profile.php?id=" . $pet_id);
        exit;
    } else {
        $error = "Name cannot be empty and must be 30 characters or less.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rename Pet</title>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Rename <?= htmlspecialchars($pet['pet_name']) ?></h1>
    <?php if (!empty($error)) echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>'; ?>
    <form method="post">
        <label>New Name: <input type="text" name="new_name" maxlength="30" value="<?= htmlspecialchars($pet['pet_name']) ?>" required></label>
        <input type="submit" value="Rename">
    </form>
    <p><a href="pet_profile.php?id=<?= $pet_id ?>">Back to Pet Profile</a></p>
</div>
</body>

 <?php include 'footer.php'; ?>

</html>