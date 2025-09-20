<?php
session_start();
require_once "includes/db.php";

if (!isset($_GET['id'])) {
    die("Topic not specified.");
}

$topic_id = $_GET['id'];

// Fetch topic info
$stmt = $pdo->prepare("SELECT t.title, u.username FROM forum_topics t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) {
    die("Topic not found.");
}

// Fetch posts
$stmt = $pdo->prepare("SELECT p.content, p.created_at, u.username FROM forum_posts p JOIN users u ON p.user_id = u.id WHERE p.topic_id = ? ORDER BY p.created_at ASC");
$stmt->execute([$topic_id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($topic['title']) ?> - The Forum</title>
  <link rel="stylesheet" href="assets/forum.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1><?= htmlspecialchars($topic['title']) ?></h1>
  <p><strong>Started by:</strong> <?= htmlspecialchars($topic['username']) ?></p>
  <hr>
  <?php foreach ($posts as $post): ?>
    <div class="forum-section" style="margin-bottom:15px;">
      <p><strong><?= htmlspecialchars($post['username']) ?></strong> <em><?= $post['created_at'] ?></em></p>
      <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
    </div>
  <?php endforeach; ?>
  <div class="forum-buttons">
    <a href="add_post.php?topic_id=<?= $topic_id ?>">Add Reply</a>
  </div>
</div>
</body>
</html>
