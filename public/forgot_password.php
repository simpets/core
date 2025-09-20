<?php
session_start();
require_once "includes/db.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        $link = "https://your_domain_goes_here/reset_password.php?token=$token";
        $message = "Reset link: <a href='$link'>$link</a>";
    } else {
        $message = "If that email exists, a reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Forgot Password</title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
<h1>Forgot Password</h1>
<form method="post">
  <label for="email">Enter your email address:</label><br>
  <input type="email" name="email" id="email" required><br><br>
  <input type="submit" value="Send Reset Link">
</form>
<?php if ($message): ?><p><?= $message ?></p><?php endif; ?>
</div>
</body>
</html>
