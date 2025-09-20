<?php
session_start();
require_once "includes/db.php";

$token = $_GET['token'] ?? '';
$message = "";

if (!$token) {
    die("Invalid reset link.");
}

$stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || strtotime($user['reset_expires']) < time()) {
    die("This reset link is invalid or expired.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = $_POST['password'] ?? '';
    $hashed = password_hash($newpass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$hashed, $user['id']]);

    $message = "Password has been reset. <a href='login.php'>Login here</a>.";
}
?>
<!DOCTYPE html>
<html>
<head><title>Reset Password</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
<h1>Reset Password</h1>
<?php if ($message): ?>
  <p><?= $message ?></p>
<?php else: ?>
  <form method="post">
    <label for="password">Enter a new password:</label><br>
    <input type="password" name="password" id="password" required><br><br>
    <input type="submit" value="Reset Password">
  </form>
<?php endif; ?>
</div>
</body>
</html>
