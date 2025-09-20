<?php
session_start();
require_once "includes/db.php";

// Restrict to admins only!
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Fetch the user's group (adjust if your admin group is called something else)
$stmt = $pdo->prepare("SELECT usergroup FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usergroup = $stmt->fetchColumn();

if ($usergroup !== 'Admin') {
    die("Access denied. Admins only.");
}

// Handle news post
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['message'] ?? '');
    if ($title && $body) {
        $stmt = $pdo->prepare("INSERT INTO news (title, message) VALUES (?, ?)");
        $stmt->execute([$title, $body]);
        $message = "✅ News posted successfully!";
    } else {
        $message = "Please enter both a title and a message.";
    }
}

// Fetch last 10 news posts
$news_stmt = $pdo->query("SELECT * FROM news ORDER BY id DESC LIMIT 10");
$news_list = $news_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin: Post News</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .newsform { max-width: 500px; margin: 24px auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 0 20px #eee; }
        .newsform input[type='text'] { width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #ccc; font-size: 1.1em; }
        .newsform textarea { width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #ccc; font-size: 1.1em; min-height: 120px; }
        .newsform button { padding: 8px 18px; border-radius: 8px; background: #563b9c; color: #fff; border: none; font-weight: bold; cursor: pointer; font-size: 1em;}
        .newsform button:hover { background: #321f56; }
        .newslist { max-width: 600px; margin: 36px auto 0 auto;}
        .newsitem { background: #f7f6fb; border-radius: 10px; margin-bottom: 16px; padding: 18px 22px;}
        .newsitem h3 { margin: 0 0 8px 0; font-size: 1.2em; }
        .newsitem small { color: #888; }
        .success { color: #269d2a; margin-bottom: 12px; }
    </style>
</head>
<body>
<?php include 'menu.php'; ?>

<div class="newsform">
    <h2>Post a New Announcement</h2>
    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>
            <strong>Title:</strong><br>
            <input type="text" name="title" maxlength="255" required>
        </label>
        <br>
        <label>
            <strong>Message:</strong><br>
            <textarea name="message" maxlength="3000" required></textarea>
        </label>
        <br>
        <button type="submit">Post News</button>
    </form>
</div>

<div class="newslist">
    <h2>Recent News Posts</h2>
    <?php foreach ($news_list as $n): ?>
        <div class="newsitem">
            <h3><?= htmlspecialchars($n['title']) ?></h3>
            <small>Posted on <?= htmlspecialchars($n['date_posted']) ?></small>
            <p><?= nl2br(htmlspecialchars($n['message'])) ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>