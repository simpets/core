<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$earned = rand(1, 5);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE users SET canicash = canicash + ? WHERE id = ?");
$stmt->execute([$earned, $user_id]);

?>
<!DOCTYPE html>
<html>
<head><title>Click Game - Simpets</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<header><h1>Canis-Club</h1></header>
<div class="container">
    <h2>Click Game</h2>
    <p>You clicked the shiny button and earned <strong><?= $earned ?></strong> Canicash!</p>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>