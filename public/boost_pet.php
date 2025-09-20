<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}
$user_id = $_SESSION['user_id'];

$pet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pet_id) die("No pet selected.");

// Fetch the pet (any pet can be boosted)
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch();
if (!$pet) die("Pet not found.");

// Optional: Prevent user from boosting their own pet
// if ($pet['user_id'] == $user_id) die("You cannot boost your own pet.");

$error = '';
$success = '';

try {
    // Only one boost per hour per user per pet
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_boosts WHERE pet_id = ? AND user_id = ? AND timestamp > (NOW() - INTERVAL 1 HOUR)");
    $stmt->execute([$pet_id, $user_id]);
    $recent = $stmt->fetchColumn();

    if ($recent > 0) {
        $error = "You can only boost this pet once per hour.";
    } else {
        // Add boost
        $stmt = $pdo->prepare("INSERT INTO pet_boosts (pet_id, user_id, timestamp) VALUES (?, ?, NOW())");
        $stmt->execute([$pet_id, $user_id]);
        $stmt = $pdo->prepare("UPDATE user_pets SET boosts = boosts + 1 WHERE id = ?");
        $stmt->execute([$pet_id]);
        // Instead of immediate redirect, show a confirmation
        $success = "Pet boosted successfully!";
    }
} catch (Exception $e) {
    $error = "DB error: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Boost Pet</title>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Boost <?= htmlspecialchars($pet['pet_name']) ?></h1>
    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
        <meta http-equiv="refresh" content="2;url=pet_profile.php?id=<?= $pet_id ?>">
    <?php elseif ($error): ?>
        <p style="color:red;"><?= $error ?></p>
        <p><a href="pet_profile.php?id=<?= $pet_id ?>">Back to Pet Profile</a></p>
    <?php endif; ?>
    <?php if (!$success && !$error): ?>
        <form method="post">
            <input type="hidden" name="boost" value="1">
            <button type="submit">Boost this pet!</button>
        </form>
        <p><a href="pet_profile.php?id=<?= $pet_id ?>">Back to Pet Profile</a></p>
    <?php endif; ?>
</div>
</body>
 <?php include 'footer.php'; ?>


</html>