<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "includes/db.php";

if (isset($_SESSION['username'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("REPLACE INTO online (username, time, ip) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['username'], time(), $ip]);
}