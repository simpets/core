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
    // Prevent self-delete!
    if ($del_id != $user_id) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
    }
}

// Handle Edit
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $new_email = trim($_POST['email'] ?? '');
    $new_group = trim($_POST['usergroup'] ?? '');
    if ($new_email && $new_group) {
        $pdo->prepare("UPDATE users SET email = ?, usergroup = ? WHERE id = ?")
            ->execute([$new_email, $new_group, $edit_id]);
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, username, email, usergroup FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Members</title>
    <style>
        body { background: #fffbee; font-family: Arial, sans-serif; }
        .admin-container { max-width: 900px; margin: 34px auto; background: #fffdf8; border-radius: 16px; box-shadow: 0 7px 22px #bfa64da9; padding: 32px 26px; }
        h2 { color: #b0751d; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th, td { border: 1px solid #eed392; padding:9px; }
        th { background: #fff3c5; }
        form.inline { display:inline; }
        .edit-row { background: #f2f5e2; }
        .admin-btn { background: #e4ba5e; color: #533800; border-radius: 5px; border:none; padding:5px 14px; font-weight:bold; }
        .admin-btn:hover { background: #d4a23c; color:#fff; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>👤 Edit or Delete Members</h2>
    <a href="admin.php">⬅️ Back to Admin Panel</a>
    <table>
        <tr>
            <th>ID</th><th>Username</th><th>Email</th><th>Group</th><th>Edit</th><th>Delete</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <form class="inline" method="post">
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" style="width:180px;">
                </td>
                <td>
                    <select name="usergroup">
                        <option value="member" <?= $user['usergroup'] === 'member' ? "selected" : "" ?>>member</option>
                        <option value="admin" <?= $user['usergroup'] === 'admin' ? "selected" : "" ?>>admin</option>
                    </select>
                </td>
                <td>
                    <input type="hidden" name="edit_id" value="<?= $user['id'] ?>">
                    <button type="submit" class="admin-btn">Edit</button>
                </td>
                <td>
                    <?php if ($user['id'] != $user_id): ?>
                    <form class="inline" method="post">
                        <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="admin-btn" onclick="return confirm('Delete this user?');">Delete</button>
                    </form>
                    <?php else: ?>
                        (You)
                    <?php endif; ?>
                </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>