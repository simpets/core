<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to change a pet's gender.");
}

$user_id = $_SESSION['user_id'];
$message = "";

if (isset($_GET['pet_id'])) {
    $pet_id = (int) $_GET['pet_id'];
    
    
    

    // Check pet ownership
    $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    $pet = $stmt->fetch();

    if (!$pet) {
        $message = "You do not own this pet.";
    } else {
        $current_gender = $pet['gender'];
        $new_gender = ($current_gender === 'Male') ? 'Female' : 'Male';

        $update = $pdo->prepare("UPDATE user_pets SET gender = ? WHERE id = ?");
        $update->execute([$new_gender, $pet_id]);

        $message = "Gender changed successfully! {$pet['pet_name']} is now {$new_gender}.";
    }
} else {
    $message = "No pet selected.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Pet Gender</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Change Pet Gender</h1>
    <p><?= htmlspecialchars($message) ?></p>
    <a href="dashboard.php">Return to Dashboard</a>
</div>
</body>
</html>