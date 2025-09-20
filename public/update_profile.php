<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$bio = trim($_POST['bio'] ?? '');

$avatarPath = null;
if (!empty($_FILES['avatar']['name'])) {
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $avatarPath = "assets/avatars/avatar_" . $user_id . "." . $ext;
    move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
}

if ($avatarPath) {
    $stmt = $pdo->prepare("UPDATE users SET bio = ?, avatar = ? WHERE id = ?");
    $stmt->execute([$bio, $avatarPath, $user_id]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->execute([$bio, $user_id]);
}

header("Location: profile.php");
exit;