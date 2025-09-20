<?php
session_start();
require_once "includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["usergroup"] = $user["usergroup"];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="container">
    <h1>Login</h1>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Username: <input type="text" name="username" required></label><br><br>
        <label>Password: <input type="password" name="password" required></label><br><br>
        <input type="submit" value="Login">
    </form>
    <p><a href="register.php">Don't have an account? Register</a></p>
    
      <p><a href="forgot_password.php">Don't remember password or want to reset? Reset Password</a></p>
    
</div>
</body>
 <?php include 'footer.php'; ?>


</html>
