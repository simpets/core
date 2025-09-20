<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Simpets - Dashboard</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header>
  <h1>Simpets</h1>
</header>
<nav>
  <a href="dashboard.php">Dashboard</a> |
  <a href="adopt.php">Adopt</a> |
  <a href="breed.php">Breed</a> |
  <a href="customize.php">Customize</a> |
  <a href="shop.php">Shop</a> |
  <a href="forum.php">Forum</a> |
  <a href="members.php">Members</a> |
  <a href="profile.php">My Profile</a>
  <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
    | <a href="admin.php">Admin</a>
    | <a href="admin_pets.php">Manage Pets</a>
    | <a href="admin_items.php">Manage Items</a>
  <?php endif; ?>
  | <a href="logout.php">Logout</a>
</nav>
<div class="container">
  <h2>Dashboard</h2>
  Welcome to your dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!
</div>
</body>
</html>