<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$topic_id = $_GET['id'] ?? null;
if (!$topic_id) {
    header("Location: forum.php");
    exit;
}

$stmt = $pdo->prepare("SELECT forum_topics.*, users.username FROM forum_topics JOIN users ON forum_topics.user_id = users.id WHERE forum_topics.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();
if (!$topic) {
    echo "Topic not found."; exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO forum_posts (topic_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$topic_id, $_SESSION['user_id'], $content]);
        header("Location: topic.php?id=" . $topic_id);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT forum_posts.*, users.username FROM forum_posts JOIN users ON forum_posts.user_id = users.id WHERE forum_posts.topic_id = ? ORDER BY posted_at ASC");
$stmt->execute([$topic_id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title><?= htmlspecialchars($topic['title']) ?></title><link rel="stylesheet" href="assets/styles.css"></head>
<body>
<h2><?= htmlspecialchars($topic['title']) ?></h2>
<p>By <?= htmlspecialchars($topic['username']) ?> on <?= $topic['created_at'] ?></p>
<hr>
<?php foreach ($posts as $post): ?>
    <div style="margin-bottom:10px;">
        <strong><?= htmlspecialchars($post['username']) ?>:</strong><br>
        <?= nl2br(htmlspecialchars($post['content'])) ?><br>
        <small><?= $post['posted_at'] ?></small>
    </div>
<?php endforeach; ?>

<h3>Reply</h3>
<form method="post">
    <textarea name="content" rows="4" cols="50" required></textarea><br>
    <input type="submit" value="Post Reply">
</form>
<p><a href="forum.php">Back to Forum</a></p>
</body>
</html>