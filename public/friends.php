<?php
// friends.php — manage friend requests in one place
session_start();
require_once "includes/db.php";

// require login
if (empty($_SESSION['user_id'])) {
  header("Location: login.php?next=" . urlencode($_SERVER['REQUEST_URI']));
  exit;
}
$me = (int)$_SESSION['user_id'];

// minimal csrf
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

// ensure table exists (safe to keep)
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS user_friends (
      id INT AUTO_INCREMENT PRIMARY KEY,
      requester_id INT NOT NULL,
      addressee_id INT NOT NULL,
      status ENUM('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_pair (requester_id, addressee_id),
      KEY idx_addressee_status (addressee_id, status),
      KEY idx_requester_status (requester_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
} catch (Throwable $e) {}

// actions
$errors = []; $success = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && csrf_ok($_POST['csrf'] ?? '')) {
  $act = $_POST['action'] ?? '';
  try {
    if ($act === 'accept') {
      $rid = (int)($_POST['requester_id'] ?? 0);
      $st = $pdo->prepare("UPDATE user_friends SET status='accepted'
                           WHERE requester_id=? AND addressee_id=? AND status='pending'");
      $st->execute([$rid,$me]);
      if ($st->rowCount()) $success[]="Friend request accepted.";
      else $errors[]="Nothing to accept.";
    } elseif ($act === 'decline') {
      $rid = (int)($_POST['requester_id'] ?? 0);
      $st = $pdo->prepare("DELETE FROM user_friends
                           WHERE requester_id=? AND addressee_id=? AND status='pending'");
      $st->execute([$rid,$me]);
      if ($st->rowCount()) $success[]="Friend request declined.";
      else $errors[]="Nothing to decline.";
    } elseif ($act === 'cancel') {
      $aid = (int)($_POST['addressee_id'] ?? 0);
      $st = $pdo->prepare("DELETE FROM user_friends
                           WHERE requester_id=? AND addressee_id=? AND status='pending'");
      $st->execute([$me,$aid]);
      if ($st->rowCount()) $success[]="Friend request canceled.";
      else $errors[]="Nothing to cancel.";
    } elseif ($act === 'unfriend') {
      $uid = (int)($_POST['friend_id'] ?? 0);
      $st = $pdo->prepare("DELETE FROM user_friends
                           WHERE (requester_id=? AND addressee_id=?)
                              OR (requester_id=? AND addressee_id=?)");
      $st->execute([$me,$uid,$uid,$me]);
      if ($st->rowCount()) $success[]="Removed from friends.";
      else $errors[]="Not friends.";
    } elseif ($act === 'request') {
      $target = trim((string)($_POST['username'] ?? ''));
      if ($target==='') { $errors[]="Enter a username."; }
      else {
        $q = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $q->execute([$target]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) $errors[]="User not found.";
        else {
          $to = (int)$row['id'];
          if ($to === $me) $errors[]="You can’t friend yourself.";
          else {
            // ensure not existing
            $chk = $pdo->prepare("SELECT 1 FROM user_friends
                                  WHERE (requester_id=? AND addressee_id=?)
                                     OR (requester_id=? AND addressee_id=?) LIMIT 1");
            $chk->execute([$me,$to,$to,$me]);
            if ($chk->fetch()) $errors[]="Already friends or pending.";
            else {
              $ins = $pdo->prepare("INSERT INTO user_friends (requester_id, addressee_id, status)
                                    VALUES (?,?,'pending')");
              $ins->execute([$me,$to]);
              $success[]="Friend request sent to $target.";
            }
          }
        }
      }
    }
  } catch (Throwable $e) { $errors[]="Action failed."; }
}

// fetch lists
$incoming = $pdo->prepare("SELECT uf.requester_id, u.username, u.avatar
                           FROM user_friends uf
                           JOIN users u ON u.id=uf.requester_id
                           WHERE uf.addressee_id=? AND uf.status='pending'
                           ORDER BY uf.id DESC");
$incoming->execute([$me]);
$incoming = $incoming->fetchAll(PDO::FETCH_ASSOC);

$outgoing = $pdo->prepare("SELECT uf.addressee_id, u.username, u.avatar
                           FROM user_friends uf
                           JOIN users u ON u.id=uf.addressee_id
                           WHERE uf.requester_id=? AND uf.status='pending'
                           ORDER BY uf.id DESC");
$outgoing->execute([$me]);
$outgoing = $outgoing->fetchAll(PDO::FETCH_ASSOC);

$friends = $pdo->prepare("
  SELECT CASE WHEN uf.requester_id=? THEN uf.addressee_id ELSE uf.requester_id END AS fid,
         u.username, u.avatar, u.nickname
  FROM user_friends uf
  JOIN users u ON u.id = CASE WHEN uf.requester_id=? THEN uf.addressee_id ELSE uf.requester_id END
  WHERE uf.status='accepted' AND (uf.requester_id=? OR uf.addressee_id=?)
  ORDER BY uf.id DESC
");
$friends->execute([$me,$me,$me,$me]);
$friends = $friends->fetchAll(PDO::FETCH_ASSOC);

// viewer username for greeting
$meRow = $pdo->prepare("SELECT username FROM users WHERE id=?");
$meRow->execute([$me]);
$my_username = $meRow->fetchColumn();
?>
<!doctype html>
<html>
<head>
  <title>Friends — <?= htmlspecialchars($my_username) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .container{max-width:900px;margin:24px auto;}
    .card{border:1px solid #ddd;border-radius:12px;padding:14px;background:#fff;margin-bottom:16px;}
    .row{display:flex;align-items:center;gap:10px;margin:8px 0;}
    .avatar{width:36px;height:36px;border-radius:999px;border:1px solid #ccc;object-fit:cover;}
    .button{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #888;background:#f3f3f3;cursor:pointer;}
    .muted{color:#666;}
    .ok{background:#ebf9f1;border:1px solid #a8e5c7;color:#145a32;padding:8px 10px;border-radius:8px;margin:8px 0;}
    .err{background:#fde8e8;border:1px solid #f5b5b5;color:#8a1f1f;padding:8px 10px;border-radius:8px;margin:8px 0;}
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Friends</h1>

  <?php foreach ($success as $s): ?><div class="ok"><?= htmlspecialchars($s) ?></div><?php endforeach; ?>
  <?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

  <div class="card">
    <h3>Send a Friend Request</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="request">
      <input type="text" name="username" placeholder="Enter a username" required>
      <button class="button" type="submit">Send</button>
    </form>
  </div>

  <div class="card" id="incoming">
    <h3>Incoming Requests</h3>
    <?php if (!$incoming): ?>
      <p class="muted">No incoming requests.</p>
    <?php else: foreach ($incoming as $r): ?>
      <div class="row">
        <img class="avatar" src="<?= htmlspecialchars($r['avatar'] ?? 'images/default-avatar.png') ?>">
        <a href="profile.php?user=<?= urlencode($r['username']) ?>"><?= htmlspecialchars($r['username']) ?></a>
        <form method="post" style="margin-left:auto;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
          <input type="hidden" name="action" value="accept">
          <input type="hidden" name="requester_id" value="<?= (int)$r['requester_id'] ?>">
          <button class="button" type="submit">Accept</button>
        </form>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
          <input type="hidden" name="action" value="decline">
          <input type="hidden" name="requester_id" value="<?= (int)$r['requester_id'] ?>">
          <button class="button" type="submit">Decline</button>
        </form>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card" id="outgoing">
    <h3>Outgoing Requests</h3>
    <?php if (!$outgoing): ?>
      <p class="muted">No outgoing requests.</p>
    <?php else: foreach ($outgoing as $r): ?>
      <div class="row">
        <img class="avatar" src="<?= htmlspecialchars($r['avatar'] ?? 'images/default-avatar.png') ?>">
        <a href="profile.php?user=<?= urlencode($r['username']) ?>"><?= htmlspecialchars($r['username']) ?></a>
        <form method="post" style="margin-left:auto;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
          <input type="hidden" name="action" value="cancel">
          <input type="hidden" name="addressee_id" value="<?= (int)$r['addressee_id'] ?>">
          <button class="button" type="submit">Cancel</button>
        </form>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <h3>Your Friends</h3>
    <?php if (!$friends): ?>
      <p class="muted">No friends yet.</p>
    <?php else: foreach ($friends as $f): ?>
      <div class="row">
        <img class="avatar" src="<?= htmlspecialchars($f['avatar'] ?? 'images/default-avatar.png') ?>">
        <a href="profile.php?user=<?= urlencode($f['username']) ?>"><?= htmlspecialchars($f['username']) ?></a>
        <?php if (!empty($f['nickname'])): ?>
          <span class="muted"> (<?= htmlspecialchars($f['nickname']) ?>)</span>
        <?php endif; ?>
        <form method="post" style="margin-left:auto;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
          <input type="hidden" name="action" value="unfriend">
          <input type="hidden" name="friend_id" value="<?= (int)$f['fid'] ?>">
          <button class="button" type="submit">Unfriend</button>
        </form>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
<?php include 'footer.php'; ?>
</body>
</html>