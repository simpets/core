<?php
session_start();
require_once "includes/db.php";

// Admin check
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT usergroup FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$usergroup = $stmt->fetchColumn();
if ($usergroup !== 'Admin') { die("Access denied. Admins only."); }

// Handle Add

if (isset($_POST['add_type'])) {
    $type = trim($_POST['type'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $cost = intval($_POST['cost'] ?? 0);
    $available = isset($_POST['available']) ? 1 : 0;
    if ($type && $image) {
        $pdo->prepare("INSERT INTO adopts (type, name, image, cost, available) VALUES (?, ?, ?, ?, ?)")
            ->execute([$type, $name, $image, $cost, $available]);
    }
}

// Handle Edit (from previous code, if you want to keep editing on this page)
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $type = trim($_POST['type'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $cost = intval($_POST['cost'] ?? 0);
    $available = isset($_POST['available']) ? 1 : 0;
    if ($type && $image) {
        $pdo->prepare("UPDATE adopts SET type = ?, name = ?, image = ?, cost = ?, available = ? WHERE id = ?")
            ->execute([$type, $name, $image, $cost, $available, $edit_id]);
    }
}

// Fetch all pet types
$types = $pdo->query("SELECT * FROM adopts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Add/Edit Pet Types</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container { max-width: 980px; margin: 34px auto; background: #fffdf8; border-radius: 16px; box-shadow: 0 7px 22px #bfa64da9; padding: 32px 26px; }
        h2 { color: #b0751d; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th, td { border: 1px solid #eed392; padding:9px; }
        th { background: #fff3c5; }
        img { max-width: 70px; max-height: 70px; }
        .admin-btn { background: #e4ba5e; color: #533800; border-radius: 5px; border:none; padding:5px 14px; font-weight:bold; }
        .admin-btn:hover { background: #d4a23c; color:#fff; }
        form.inline { display:inline; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>🐾 Add or Edit Pet Types</h2>
    <a href="admin.php">⬅️ Back to Admin Panel</a>
    <h3>Add New Pet Type</h3>
    <form method="post" style="margin-bottom: 20px;">
        <input type="text" name="type" placeholder="Type" required style="width:110px;">
        
        <input type="text" name="name" placeholder="Type Name" required style="width:110px;">
        
        
        <input type="text" name="image" placeholder="Image Path (e.g. images/lion.png)" required style="width:160px;">
        <input type="number" name="cost" placeholder="Cost" required style="width:80px;">
        <label style="font-size:0.95em;"><input type="checkbox" name="available" value="1" checked> Available?</label>
        <button type="submit" name="add_type" class="admin-btn">Add Pet Type</button>
    </form>
    <table>
        <tr>
            <th>ID</th><th>Type</th><th>Name</th><th>Image</th><th>Cost</th><th>Available?</th><th>Edit</th>
        </tr>
        <?php foreach ($types as $type): ?>
            <tr>
                <form class="inline" method="post">
                <td><?= $type['id'] ?></td>
                <td><input type="text" name="type" value="<?= htmlspecialchars($type['type']) ?>" style="width:110px;"></td>
                
                <td><input name="text" name="name" value="<?= htmlspecialchars($name['name']) ?>" style="width:110px;"></td>
                
                
                <td>
                    <input type="text" name="image" value="<?= htmlspecialchars($type['image']) ?>" style="width:150px;">
                    <?php if ($type['image']): ?>
                        <br><img src="<?= htmlspecialchars($type['image']) ?>">
                    <?php endif; ?>
                </td>
                <td><input type="number" name="cost" value="<?= (int)$type['cost'] ?>" style="width:80px;"></td>
                <td><input type="checkbox" name="available" value="1" <?= $type['available'] ? 'checked' : '' ?>></td>
                <td>
                    <input type="hidden" name="edit_id" value="<?= $type['id'] ?>">
                    <button type="submit" class="admin-btn">Edit</button>
                </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>