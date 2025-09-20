<?php
session_start();
require_once "includes/db.php";
require_once "classes/class_privatemessage.php";

function getUserIdByUsername($username, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Validation
    if (!$username || !$email || !$password || !$confirm) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($username) < 3 || strlen($username) > 32) {
        $message = "Username must be 3-32 characters.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $message = "That username or email is already in use.";
        } else {
            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $new_user_id = $pdo->lastInsertId();

            // Optionally auto-login after register:
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;

            // Admin username - change if needed
            $admin_username = 'Admin';
            $admin_id = getUserIdByUsername($admin_username, $pdo);
            $safe_username = htmlentities(addslashes(trim($username)));

            // Notify admin
            $pm = new PrivateMessage();
            $pm->setsender(0); // SYSTEM or 0
            $pm->setrecipient($admin_id);
            $pm->setmessage("New User Registered", "A new user has registered! Their username is {$safe_username}.");
            $pm->post();

            // Welcome PM to new member
            $pm = new PrivateMessage();
            $pm->setsender($admin_id);
            $pm->setrecipient($new_user_id);
            $pm->setmessage(
                "Welcome to Simpets!",
                "Hi there and welcome to Simpets! We hope you enjoy your time here. Feel free to ask questions anytime. For more fun and info, check out our FAQ and please join up at the Forums .. lots of important updates there, and a place to share with other members! Please make sure you read and follow the rules and Terms of Service!<br><br>Have a wonderful day!<br><br>~" . $admin_username
            );
            $pm->post();

            header("Location: dashboard.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Simpets</title>
    <style>
        body {
            background: #fff8ef;
            font-family: 'Segoe UI', 'Arial', sans-serif;
        }
        .register-container {
            max-width: 430px;
            margin: 30px auto;
            padding: 32px 28px;
            background: #fcf4e0;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(80,60,30,0.12);
            text-align: center;
        }
        .welcome-sign {
            display: block;
            margin: 0 auto 14px auto;
            width: 270px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 3px 9px rgba(60,40,10,0.10);
        }
        h2 {
            color: #aa7733;
            letter-spacing: 1.5px;
            margin-top: 6px;
            font-size: 2.1em;
        }
        label {
            display: block;
            margin: 15px 0 6px 0;
            font-weight: bold;
            color: #865b20;
            text-align: left;
        }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 98%;
            padding: 10px 8px;
            border-radius: 6px;
            border: 1px solid #c8b098;
            background: #fdfaf3;
            font-size: 1.06em;
        }
        button, input[type="submit"] {
            background: #aa7733;
            color: #fff8ef;
            border: none;
            border-radius: 6px;
            padding: 10px 30px;
            font-size: 1.11em;
            font-weight: bold;
            margin: 18px 0 0 0;
            cursor: pointer;
            transition: background .22s;
        }
        button:hover, input[type="submit"]:hover {
            background: #e4a13b;
            color: #fff9ec;
        }
        .small-link {
            font-size: 0.98em;
            color: #756d4b;
            margin-top: 18px;
            display: block;
        }
        .form-row {
            margin-bottom: 12px;
        }
        .message {
            color: #a32626;
            font-weight: bold;
            margin-bottom: 16px;
            padding: 6px 0;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <img src="https://pet-sim.online/Petsim/images/Welcome.jpeg" class="welcome-sign" alt="Welcome Please!" />
        <h2>Welcome to Simpets!</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <div class="form-row">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" maxlength="32" required>
            </div>
            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" maxlength="60" required>
            </div>
            <div class="form-row">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" minlength="6" maxlength="32" required>
            </div>
            <div class="form-row">
                <label for="confirm">Confirm Password:</label>
                <input type="password" id="confirm" name="confirm" minlength="6" maxlength="32" required>
            </div>
            <input type="submit" value="Create Account">
        </form>
        <a href="login.php" class="small-link">Already have an account? Log in here!</a>
    </div>
</body>
</html>