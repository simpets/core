<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_id = $_POST['topic_id'];
    $content = trim($_POST['content']);
    $username = $_SESSION['username'];

    if (!empty($topic_id) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (topic_id, author, content) VALUES (?, ?, ?)");
        $stmt->execute([$topic_id, $username, $content]);

        header("Location: view_topic.php?id=" . $topic_id);
        exit;
    } else {
        echo "Post content is required.";
    }
} else {
    echo "Invalid request.";
}
?>
