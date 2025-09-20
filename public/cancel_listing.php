<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$market_id = $_POST['market_id'] ?? null;

$stmt = $pdo->prepare("DELETE FROM pet_market WHERE id = ? AND seller_id = ?");
$stmt->execute([$market_id, $user_id]);

header("Location: my_listings.php");
exit;
?>