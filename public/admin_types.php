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

// Handle Delete
if (isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $pdo->prepare("DELETE FROM adopts WHERE id = ?")->execute([$del_id]);
}


// Handle Edit
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $type = trim($_POST['type'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $cost = intval($_POST['cost'] ?? 0);
    $available = isset($_POST['available']) ? 1 : 0;
    if ($type && $image) {
        $pdo->prepare("UPDATE adopts SET type = ?, image = ?, cost = ?, available = ? WHERE id = ?")
            ->execute([$type, $image, $cost, $available, $edit_id]);
    }
}

// Fetch pet types
$types = $pdo->query("SELECT * FROM adopts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Pet Types</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container { max-width: 960px; margin: 34px auto; background: #fffdf8; border-radius: 16px; box-shadow: 0 7px 22px #bfa64da9; padding: 32px 26px; }
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
    <h2>🐾 Edit or Delete Pet Types</h2>
    <a href="admin.php">⬅️ Back to Admin Panel</a>
    <table>
        <tr>
            <th>ID</th><th>Type</th><th>Image</th><th>Cost</th><th>Available?</th><th>Edit</th><th>Delete</th>
        </tr>
        <?php foreach ($types as $type): ?>
            <tr>
                <form class="inline" method="post">
                <td><?= $type['id'] ?></td>
                <td><input type="text" name="type" value="<?= htmlspecialchars($type['type']) ?>" style="width:110px;"></td>
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
                <td>
                    <form class="inline" method="post">
                        <input type="hidden" name="delete_id" value="<?= $type['id'] ?>">
                        <button type="submit" class="admin-btn" onclick="return confirm('Delete this pet type?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>