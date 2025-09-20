<?php
session_start();
require_once "includes/db.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT usergroup, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['usergroup'] != 'admin') {
    die("Access denied. You must be an admin to view this page.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Control Panel</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
    <h1>Welcome to the Admin Control Panel</h1>
    <p>Hello, <?= htmlspecialchars($user['username']) ?>! Use the links below to manage your site.</p>
    <ul>
        <li><a href="admin_settings.php">Site Settings</a></li>
        <li><a href="admin_manage_users.php">Manage Users</a></li>
        <li><a href="admin_news.php">Manage News</a></li>
        <li><a href="admin_items.php">Manage Items & Markings</a></li>
        <!-- Add more admin links here -->
    </ul>
</div>
<?php include 'footer.php'; ?>
</body>
</html>