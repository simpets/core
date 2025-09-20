<?php
session_start();
require_once "includes/db.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $msg_id = intval($_POST['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND recipient_id = ?");
    $stmt->execute([$msg_id, $user_id]);
}

// Fetch messages for the current user
$stmt = $pdo->prepare("SELECT * FROM messages WHERE recipient_id = ? ORDER BY sent_at DESC");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all messages as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = ?")->execute([$user_id]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Messages</title>
    <style>
        body { background: #fff8ef; font-family: Arial, sans-serif; }
        .msg-container { max-width: 750px; margin: 30px auto; background: #fffefb; border-radius: 12px; box-shadow: 0 7px 22px #edd8b0a9; padding: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid #e0d4c4; padding: 10px; }
        th { background: #f6e3b9; }
        tr.unread { background: #f7f0e3; font-weight: bold; }
        .subject { color: #5b3d09; }
        .sender { color: #b7872f; }
        .delete-btn {
            background: #ed4747;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 12px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1em;
            transition: background .18s;
        }
        .delete-btn:hover {
            background: #b92e2e;
        }
    </style>
</head>
<body>
    
    <?php include "menu.php"; ?>
<div class="msg-container">
    <h2>Your Messages</h2>
    <?php if (empty($messages)): ?>
        <p>You have no messages.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>From</th>
            <th>Subject</th>
            <th>Date</th>
            <th>Status</th>
            <th>Delete</th>
        </tr>
        <?php foreach ($messages as $msg): ?>
        <tr class="<?= $msg['is_read'] ? '' : 'unread' ?>">
            <td class="sender">
                <?php
                // Get sender username
                $sender = ($msg['sender_id'] == 0) ? 'SYSTEM' : '';
                if (!$sender) {
                    $stmt2 = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt2->execute([$msg['sender_id']]);
                    $sender = $stmt2->fetchColumn();
                }
                echo htmlspecialchars($sender);
                ?>
            </td>
            <td class="subject"><?= htmlspecialchars($msg['subject']) ?></td>
            <td><?= htmlspecialchars($msg['sent_at']) ?></td>
            <td><?= $msg['is_read'] ? 'Read' : 'Unread' ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                    <button type="submit" class="delete-btn" onclick="return confirm('Delete this message?');">Delete</button>
                </form>
            </td>
        </tr>
        <tr>
            <td colspan="5" style="font-size:1em; color:#523c17;">
                <?= nl2br(htmlspecialchars($msg['body'])) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
</body>
<?php include "footer.php"; ?>

</html>