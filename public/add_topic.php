<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$usergroup = $_SESSION['usergroup'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forum_id = $_POST['forum_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO topics (forum_id, title, author) VALUES (?, ?, ?)");
        $stmt->execute([$forum_id, $title, $username]);

        $topic_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO posts (topic_id, author, content) VALUES (?, ?, ?)");
        $stmt->execute([$topic_id, $username, $content]);

        header("Location: view_topic.php?id=" . $topic_id);
        exit;
    } else {
        $error = "Please fill out all fields.";
    }
}

$forums = $pdo->query("SELECT * FROM forums")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add New Topic</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Create a New Topic</h1>

  <?php if (!empty($error)): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Choose Forum:<br>
      <select name="forum_id">
        <?php foreach ($forums as $forum): ?>
          <?php if ($forum['access_level'] === 'All' || ($forum['access_level'] === 'Admin' && $usergroup === 'Admin')): ?>
            <option value="<?= $forum['id'] ?>"><?= htmlspecialchars($forum['name']) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </label><br><br>

    <label>Topic Title:<br>
      <input type="text" name="title" style="width:100%;">
    </label><br><br>

    <label>First Post:<br>
      <textarea name="content" rows="6" style="width:100%;"></textarea>
    </label><br><br>

    <input type="submit" value="Create Topic">
  </form>
</div>
</body>
</html>
