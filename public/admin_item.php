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
    $pdo->prepare("DELETE FROM items WHERE id = ?")->execute([$del_id]);
}

// Handle Edit
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    $available = isset($_POST['available']) ? 1 : 0;
    if ($name && $image) {
        $pdo->prepare("UPDATE items SET name = ?, image = ?, price = ?, available = ? WHERE id = ?")
            ->execute([$name, $image, $price, $available, $edit_id]);
    }
}

// Handle Add
if (isset($_POST['add_item'])) {
    $name = trim($_POST['name'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    $available = isset($_POST['available']) ? 1 : 0;
    if ($name && $image) {
        $pdo->prepare("INSERT INTO items (name, image, price, available) VALUES (?, ?, ?, ?)")
            ->execute([$name, $image, $price, $available]);
    }
}

// Fetch all items
$items = $pdo->query("SELECT * FROM items ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Items</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container { max-width: 980px; margin: 34px auto; background: #fffdf8; border-radius: 16px; box-shadow: 0 7px 22px #bfa64da9; padding: 32px 26px; }
        h2 { color: #b0751d; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th, td { border: 1px solid #eed392; padding:9px; }
        th { background: #fff3c5; }
        img { max-width: 90px; max-height: 70px; }
        .admin-btn { background: #e4ba5e; color: #533800; border-radius: 5px; border:none; padding:5px 14px; font-weight:bold; }
        .admin-btn:hover { background: #d4a23c; color:#fff; }
        form.inline { display:inline; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>🪙 Add or Edit Items</h2>
    <a href="admin.php">⬅️ Back to Admin Panel</a>
    <h3>Add New Item</h3>
    <form method="post" style="margin-bottom: 20px;">
        <input type="text" name="name" placeholder="Item Name" required style="width:110px;">
        <input type="text" name="image" placeholder="Image path (e.g. images/apple.png)" required style="width:160px;">
        <input type="number" name="price" placeholder="Price" required style="width:80px;">
        <label style="font-size:0.95em;"><input type="checkbox" name="available" value="1" checked> Available?</label>
        <button type="submit" name="add_item" class="admin-btn">Add Item</button>
    </form>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Image</th><th>Price</th><th>Available?</th><th>Edit</th><th>Delete</th>
        </tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <form class="inline" method="post">
                <td><?= $item['id'] ?></td>
                <td><input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" style="width:110px;"></td>
                <td>
                    <input type="text" name="image" value="<?= htmlspecialchars($item['image']) ?>" style="width:150px;">
                    <?php if ($item['image']): ?>
                        <br><img src="<?= htmlspecialchars($item['image']) ?>">
                    <?php endif; ?>
                </td>
                <td><input type="number" name="price" value="<?= (int)$item['price'] ?>" style="width:80px;"></td>
                <td><input type="checkbox" name="available" value="1" <?= $item['available'] ? 'checked' : '' ?>></td>
                <td>
                    <input type="hidden" name="edit_id" value="<?= $item['id'] ?>">
                    <button type="submit" class="admin-btn">Edit</button>
                </td>
                </form>
                <td>
                    <form class="inline" method="post">
                        <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="admin-btn" onclick="return confirm('Delete this item?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>