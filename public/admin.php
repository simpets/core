<?php
session_start();
require_once "includes/db.php";

// Admin Access Only
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT usergroup FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$usergroup = $stmt->fetchColumn();

if ($usergroup !== 'Admin') {
    die("Access denied. Admins only.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simpets Admin Control Panel</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container {
            max-width: 680px; margin: 40px auto; background: #fffdf8; border-radius: 16px;
            box-shadow: 0 7px 22px #bfa64da9; padding: 36px 40px; text-align: center;
        }
        h2 { color: #b0751d; }
        ul.admin-links { list-style: none; padding: 0; }
        ul.admin-links li { margin: 20px 0; }
        ul.admin-links a {
            font-size: 1.21em; color: #634e23; text-decoration: none;
            padding: 11px 30px; border-radius: 7px; background: #ffe2a6; transition: background .18s;
            display: inline-block;
        }
        ul.admin-links a:hover { background: #eebd60; color: #322310; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>🐾 Simpets Admin Control Panel 🐾</h2>
    <ul class="admin-links">
        <li><a href="admin_members.php">Edit or Delete Members</a></li>
        <li><a href="admin_types.php">Edit or Delete Available Pet Types</a></li>
        <li><a href="admin_ownedpets.php">Edit or Delete Owned Pets</a></li>
        <li><a href="admin_item.php">Add or Edit Items (set price, make available)</a></li>
        <li><a href="admin_types_manage.php">Add or Edit Pet Types (set price, make available)</a></li>
        <li><a href="admin_levels.php">Add, Edit, or Delete Levels (with image preview)</a></li>
        
        <?php if (($_SESSION['usergroup'] ?? '') === 'admin'): ?>
  <li><a href="/admin/broadcast.php">Broadcast email</a></li>
<?php endif; ?>
        
        
    </ul>
</div>
</body>
</html>