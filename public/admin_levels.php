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
if (isset($_POST['add_level'])) {
    $type = trim($_POST['type'] ?? '');
    $level_num = intval($_POST['level_num'] ?? 1);
    $image = trim($_POST['image'] ?? '');
    if ($type && $level_num && $image) {
        $pdo->prepare("INSERT INTO levels (type, level, image) VALUES (?, ?, ?)")
            ->execute([$type, $level_num, $image]);
    }
}

// Handle Edit
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $type = trim($_POST['type'] ?? '');
    $level_num = intval($_POST['level_num'] ?? 1);
    $image = trim($_POST['image'] ?? '');
    if ($type && $level_num && $image) {
        $pdo->prepare("UPDATE levels SET type = ?, level = ?, image = ? WHERE id = ?")
            ->execute([$type, $level_num, $image, $edit_id]);
    }
}

// Handle Delete
if (isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $pdo->prepare("DELETE FROM levels WHERE id = ?")->execute([$del_id]);
}

// Fetch all levels
$levels = $pdo->query("SELECT * FROM levels ORDER BY pet_type ASC, level ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Levels</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container { max-width: 1060px; margin: 34px auto; background: #fffdf8; border-radius: 16px; box-shadow: 0 7px 22px #bfa64da9; padding: 32px 26px; }
        h2 { color: #b0751d; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th, td { border: 1px solid #eed392; padding:9px; }
        th { background: #fff3c5; }
        img { max-width: 90px; max-height: 90px; }
        .admin-btn { background: #e4ba5e; color: #533800; border-radius: 5px; border:none; padding:5px 14px; font-weight:bold; }
        .admin-btn:hover { background: #d4a23c; color:#fff; }
        form.inline { display:inline; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>🎚️ Add, Edit, or Delete Pet Levels</h2>
    <a href="admin.php">⬅️ Back to Admin Panel</a>
    <h3>Add New Level</h3>
    <form method="post" style="margin-bottom: 20px;">
        <input type="text" name="type" placeholder="Pet Type" required style="width:110px;">
        <input type="number" name="level_num" placeholder="Level #" required min="1" max="10" style="width:65px;">
        <input type="text" name="image" placeholder="Image path (e.g. images/levels/cat3.png)" required style="width:170px;">
        <button type="submit" name="add_level" class="admin-btn">Add Level</button>
    </form>
    <table>
        <tr>
            <th>ID</th><th>Type</th><th>Level #</th><th>Image Path</th><th>Preview</th><th>Edit</th><th>Delete</th>
        </tr>
        <?php foreach ($levels as $level): ?>
            <tr>
                <form class="inline" method="post">
                <td><?= $level['id'] ?></td>
                <td><input type="text" name="type" value="<?= htmlspecialchars($level['type']) ?>" style="width:100px;"></td>
                <td><input type="number" name="level_num" value="<?= (int)$level['level'] ?>" min="1" max="10" style="width:60px;"></td>
                <td><input type="text" name="image" value="<?= htmlspecialchars($level['image']) ?>" style="width:170px;"></td>
                <td>
                    <?php if ($level['image']): ?>
                        <img src="<?= htmlspecialchars($level['image']) ?>">
                    <?php endif; ?>
                </td>
                <td>
                    <input type="hidden" name="edit_id" value="<?= $level['id'] ?>">
                    <button type="submit" class="admin-btn">Edit</button>
                </td>
                </form>
                <td>
                    <form class="inline" method="post">
                        <input type="hidden" name="delete_id" value="<?= $level['id'] ?>">
                        <button type="submit" class="admin-btn" onclick="return confirm('Delete this level?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>