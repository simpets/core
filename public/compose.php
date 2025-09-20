<?php
session_start();
require_once "includes/db.php";
require_once "classes/class_privatemessage.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = "";

// Fetch all users except sender (for the dropdown)
$stmt = $pdo->prepare("SELECT username FROM users WHERE id != ? ORDER BY username ASC");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_user = trim($_POST['to_user'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if (!$to_user || !$subject || !$body) {
        $message = "All fields are required.";
    } else {
        // Get recipient's user_id
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$to_user]);
        $recipient_id = $stmt->fetchColumn();
        if (!$recipient_id) {
            $message = "User not found.";
        } else {
            // Send PM
            $pm = new PrivateMessage();
            $pm->setsender($user_id);
            $pm->setrecipient($recipient_id);
            $pm->setmessage($subject, $body);
            $pm->post();
            $message = "Message sent to $to_user!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compose Message - Simpets</title>
    <style>
        body { background: #fff8ef; font-family: Arial, sans-serif; }
        .compose-container {
            max-width: 460px; margin: 32px auto; background: #f7f2e4;
            border-radius: 12px; box-shadow: 0 7px 22px #edd8b0a9; padding: 24px;
        }
        label { display: block; margin: 14px 0 6px; font-weight: bold; color: #784a10; }
        input[type="text"], textarea, select {
            width: 99%; border-radius: 6px; padding: 8px; border: 1px solid #d2b87a; font-size: 1.06em; background: #fcf9ef;
        }
        textarea { min-height: 100px; resize: vertical; }
        button { margin-top: 16px; padding: 9px 30px; border-radius: 6px; border: none; background: #a97e31; color: #fff8e6; font-weight: bold; font-size: 1.08em; cursor: pointer; }
        button:hover { background: #c9a13a; }
        .success { color: #237025; margin: 13px 0 7px; font-weight: bold; }
        .error { color: #a02020; margin: 13px 0 7px; font-weight: bold; }
    </style>
</head>
<body>
        <?php include "menu.php"; ?>
<div class="compose-container">
    <h2>📬 Compose Message</h2>
    <?php if ($message): ?>
        <div class="<?= strpos($message, 'sent to') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <label for="to_user">To Username:</label>
        <select id="to_user" name="to_user" required>
            <option value="">-- Select User --</option>
            <?php foreach ($all_users as $user): ?>
                <option value="<?= htmlspecialchars($user) ?>" <?= (isset($_POST['to_user']) && $_POST['to_user'] == $user) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="subject">Subject:</label>
        <input type="text" id="subject" name="subject" maxlength="100" required value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">

        <label for="body">Message:</label>
        <textarea id="body" name="body" maxlength="1000" required><?= isset($_POST['body']) ? htmlspecialchars($_POST['body']) : '' ?></textarea>

        <button type="submit">Send Message</button>
    </form>
</div>
</body>
    <?php include "footer.php"; ?>

</html>