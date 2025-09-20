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
    $pdo->prepare("DELETE FROM user_pets WHERE id = ?")->execute([$del_id]);
}

// Handle Edit
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $pet_name = trim($_POST['pet_name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $level = intval($_POST['level'] ?? 1);
    $gender = trim($_POST['gender'] ?? '');
    $owner_id = intval($_POST['owner_id'] ?? 0);
    if ($pet_name && $type && $owner_id) {
        $pdo->prepare("UPDATE user_pets SET pet_name = ?, type = ?, level = ?, gender = ?, user_id = ? WHERE id = ?")
            ->execute([$pet_name, $type, $level, $gender, $owner_id, $edit_id]);
    }
}

// Fetch owned pets, join to users for owner username
$pets = $pdo->query(
    "SELECT up.*, u.username as owner 
     FROM user_pets up
     LEFT JOIN users u ON up.user_id = u.id
     ORDER BY up.id ASC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Owned Pets</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container { max-width: 1200px; margin: 34px auto; background: #fffdf8; border-radius: 16px; box-shadow: 0 7px 22px #bfa64da9; padding: 32px 20px; }
        h2 { color: #b0751d; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th, td { border: 1px solid #eed392; padding:8px; }
        th { background: #fff3c5; }
        img { max-width: 90px; max-height: 90px; }
        .admin-btn { background: #e4ba5e; color: #533800; border-radius: 5px; border:none; padding:5px 14px; font-weight:bold; }
        .admin-btn:hover { background: #d4a23c; color:#fff; }
        form.inline { display:inline; }
        select, input[type='text'], input[type='number'] { width: 80px; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>🐾 Edit or Delete Owned Pets</h2>
    <a href="admin.php">⬅️ Back to Admin Panel</a>
    <table>
        <tr>
            <th>ID</th>
            <th>Pet Name</th>
            <th>Type</th>
            <th>Level</th>
            <th>Gender</th>
            <th>Owner</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
        <?php foreach ($pets as $pet): ?>
            <tr>
                <form class="inline" method="post">
                <td><?= $pet['id'] ?></td>
                <td><input type="text" name="pet_name" value="<?= htmlspecialchars($pet['pet_name']) ?>"></td>
                <td><input type="text" name="type" value="<?= htmlspecialchars($pet['type']) ?>"></td>
                <td><input type="number" name="level" min="1" max="10" value="<?= (int)$pet['level'] ?>"></td>
                <td>
                    <select name="gender">
                        <option value="Male" <?= $pet['gender'] === 'Male' ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= $pet['gender'] === 'Female' ? "selected" : "" ?>>Female</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="owner_id" value="<?= (int)$pet['user_id'] ?>" min="1" style="width:50px;">
                    <br>
                    <span style="font-size:0.96em; color:#777;"><?= htmlspecialchars($pet['owner']) ?></span>
                </td>
                <td>
                    <input type="hidden" name="edit_id" value="<?= $pet['id'] ?>">
                    <button type="submit" class="admin-btn">Edit</button>
                </td>
                </form>
                <td>
                    <form class="inline" method="post">
                        <input type="hidden" name="delete_id" value="<?= $pet['id'] ?>">
                        <button type="submit" class="admin-btn" onclick="return confirm('Delete this pet?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>