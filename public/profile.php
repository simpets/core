<?php
session_start();
require_once "includes/db.php";

/* ------------------ helpers (CSRF + password verify) ------------------ */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_check($token) {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}
function verify_password_row(array $row, string $plain): bool {
    if (!empty($row['password'])) return password_verify($plain, $row['password']);
    if (!empty($row['password_hash'])) return password_verify($plain, $row['password_hash']);
    return false;
}

/* ------------------ ensure unsubscribe_emails column exists (optional) ------------------ */
try { $pdo->exec("ALTER TABLE users ADD COLUMN unsubscribe_emails TINYINT(1) NOT NULL DEFAULT 0"); } catch (Throwable $e) {}

/* ------------------ (NEW) ensure friends table exists ------------------ */
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
          KEY idx_requester_status (requester_id, status),
          CONSTRAINT fk_uf_req FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
          CONSTRAINT fk_uf_add FOREIGN KEY (addressee_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Throwable $e) {
    // don't block profile if DDL fails
}

/* ------------------ fetch viewer/subject ------------------ */
$viewing_user = $_GET['user'] ?? ($_SESSION['username'] ?? '');

$stmt = $pdo->prepare("SELECT id, username, nickname, avatar, usergroup, simbucks,
                              profile_theme, custom_background, email, unsubscribe_emails
                       FROM users WHERE username = ?");
$stmt->execute([$viewing_user]);
$user = $stmt->fetch();
if (!$user) { die("User not found."); }

$isOwner = ($viewing_user === ($_SESSION['username'] ?? null));
$my_id   = $_SESSION['user_id'] ?? null;

/* also load password column for verification if owner */
$pwdRow = null;
if ($isOwner && $my_id) {
    $p = $pdo->prepare("SELECT id, username, email, password FROM users WHERE id = ?");
    $p->execute([$my_id]);
    $pwdRow = $p->fetch();
}

/* ------------------ theme map ------------------ */
$themes = [
    'theme-default' => 'Default',
    'theme-forest'  => 'Forest',
    'theme-ocean'   => 'Ocean',
    'theme-moon'    => 'Moonlight',
    'theme-rainbow' => 'Rainbow',
    'theme-rose'    => 'Rose',
    'theme-custom'  => 'Custom (User Background)',
];

/* ------------------ messages ------------------ */
$errors  = [];
$success = [];

/* ------------------ FRIENDS: helpers ------------------ */
function friends_get_status(PDO $pdo, int $a, int $b): array {
    // returns ['state'=>..., 'row'=>row|null, 'direction'=>'out'|'in'|null]
    $q = $pdo->prepare("SELECT * FROM user_friends
                        WHERE (requester_id = ? AND addressee_id = ?)
                           OR (requester_id = ? AND addressee_id = ?)
                        LIMIT 1");
    $q->execute([$a,$b,$b,$a]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['state'=>'none','row'=>null,'direction'=>null];
    $dir = ((int)$row['requester_id'] === $a && (int)$row['addressee_id'] === $b) ? 'out' : 'in';
    return ['state'=>$row['status'],'row'=>$row,'direction'=>$dir];
}
function friends_count_for_user(PDO $pdo, int $uid): int {
    $q = $pdo->prepare("SELECT COUNT(*) FROM user_friends
                        WHERE status='accepted' AND (requester_id = ? OR addressee_id = ?)");
    $q->execute([$uid,$uid]);
    return (int)$q->fetchColumn();
}
function friends_list_for_user(PDO $pdo, int $uid, int $limit=24, int $offset=0): array {
    $q = $pdo->prepare("
        SELECT CASE WHEN requester_id = :uid THEN addressee_id ELSE requester_id END AS friend_id
        FROM user_friends
        WHERE status='accepted' AND (requester_id = :uid OR addressee_id = :uid)
        ORDER BY id DESC
        LIMIT :lim OFFSET :off
    ");
    $q->bindValue(':uid', $uid, PDO::PARAM_INT);
    $q->bindValue(':lim', $limit, PDO::PARAM_INT);
    $q->bindValue(':off', $offset, PDO::PARAM_INT);
    $q->execute();
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);

    if (!$ids) return [];
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $s   = $pdo->prepare("SELECT id, username, nickname, avatar FROM users WHERE id IN ($in)");
    $s->execute($ids);
    $map = [];
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) $map[$r['id']] = $r;
    $out = [];
    foreach ($ids as $id) if (isset($map[$id])) $out[] = $map[$id];
    return $out;
}
function friends_pending_incoming(PDO $pdo, int $uid, int $limit=20): array {
    $q = $pdo->prepare("
        SELECT uf.id, uf.requester_id AS from_id, u.username, u.nickname, u.avatar
        FROM user_friends uf
        JOIN users u ON u.id = uf.requester_id
        WHERE uf.addressee_id = ? AND uf.status='pending'
        ORDER BY uf.id DESC
        LIMIT ?
    ");
    $q->execute([$uid, $limit]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

/* ------------------ POST handler (profile edits + friends) ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Friends actions
    if (in_array($action, ['friend_request','friend_cancel','friend_accept','friend_decline','friend_unfriend'], true)) {
        if (empty($my_id)) {
            $errors[] = "Please log in to manage friends.";
        } elseif (!csrf_check($_POST['csrf'] ?? '')) {
            $errors[] = "Security check failed. Please reload and try again.";
        } elseif ($my_id === (int)$user['id']) {
            $errors[] = "You can’t friend yourself.";
        } else {
            $status = friends_get_status($pdo, (int)$my_id, (int)$user['id']);
            try {
                if ($action === 'friend_request') {
                    if ($status['state'] === 'none') {
                        $ins = $pdo->prepare("INSERT INTO user_friends (requester_id, addressee_id, status) VALUES (?, ?, 'pending')");
                        $ins->execute([$my_id, $user['id']]);
                        $success[] = "Friend request sent!";
                    } else {
                        $errors[] = "A friendship or request already exists.";
                    }
                } elseif ($action === 'friend_cancel') {
                    if ($status['state'] === 'pending' && $status['direction'] === 'out') {
                        $del = $pdo->prepare("DELETE FROM user_friends WHERE requester_id=? AND addressee_id=? AND status='pending'");
                        $del->execute([$my_id, $user['id']]);
                        $success[] = "Friend request canceled.";
                    } else {
                        $errors[] = "No outgoing request to cancel.";
                    }
                } elseif ($action === 'friend_accept' || $action === 'friend_decline') {
                    $from_id = isset($_POST['from_id']) ? (int)$_POST['from_id'] : (int)$user['id']; // fallback to viewed profile
                    $st2 = friends_get_status($pdo, (int)$my_id, $from_id);
                    if ($st2['state'] === 'pending' && $st2['direction'] === 'in') {
                        if ($action === 'friend_accept') {
                            $upd = $pdo->prepare("UPDATE user_friends SET status='accepted' WHERE requester_id=? AND addressee_id=? AND status='pending'");
                            $upd->execute([$from_id, $my_id]);
                            $success[] = "Friend request accepted!";
                        } else {
                            $del = $pdo->prepare("DELETE FROM user_friends WHERE requester_id=? AND addressee_id=? AND status='pending'");
                            $del->execute([$from_id, $my_id]);
                            $success[] = "Friend request declined.";
                        }
                    } else {
                        $errors[] = "No incoming request to handle.";
                    }
                } elseif ($action === 'friend_unfriend') {
                    if ($status['state'] === 'accepted') {
                        $del = $pdo->prepare("DELETE FROM user_friends
                                              WHERE (requester_id=? AND addressee_id=?)
                                                 OR (requester_id=? AND addressee_id=?)");
                        $del->execute([$my_id, $user['id'], $user['id'], $my_id]);
                        $success[] = "You are no longer friends.";
                    } else {
                        $errors[] = "You are not friends.";
                    }
                }
            } catch (Throwable $e) {
                $errors[] = "Friend action failed.";
            }
        }
    }

    // Owner-only profile actions
    if ($isOwner && !in_array($action, ['friend_request','friend_cancel','friend_accept','friend_decline','friend_unfriend'], true)) {
        if ($action === 'save_profile') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors[] = "Security check failed. Please reload and try again.";
            } else {
                $nickname  = trim($_POST['nickname'] ?? '');
                $avatar    = trim($_POST['avatar'] ?? '');
                $theme     = $_POST['profile_theme'] ?? 'theme-default';
                $custom_bg = trim($_POST['custom_background'] ?? '');
                $stmt = $pdo->prepare("UPDATE users SET nickname = ?, avatar = ?, profile_theme = ?, custom_background = ? WHERE id = ?");
                $stmt->execute([$nickname, $avatar, $theme, $custom_bg, $my_id]);
                header("Location: profile.php?user=" . urlencode($viewing_user));
                exit;
            }
        } elseif ($action === 'change_email') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors[] = "Security check failed. Please reload and try again.";
            } else {
                $new_email = trim(strtolower((string)($_POST['new_email'] ?? '')));
                $curr_pass = (string)($_POST['current_password'] ?? '');
                if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Please enter a valid email address.";
                } elseif (!$pwdRow || !verify_password_row($pwdRow, $curr_pass)) {
                    $errors[] = "Your current password is incorrect.";
                } else {
                    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
                    $chk->execute([$new_email, $my_id]);
                    if ($chk->fetch()) {
                        $errors[] = "That email is already in use by another account.";
                    } else {
                        $upd = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $upd->execute([$new_email, $my_id]);
                        $success[] = "Email updated.";
                        $stmt = $pdo->prepare("SELECT id, username, nickname, avatar, usergroup, simbucks,
                                                      profile_theme, custom_background, email, unsubscribe_emails
                                               FROM users WHERE username = ?");
                        $stmt->execute([$viewing_user]);
                        $user = $stmt->fetch();
                    }
                }
            }
        } elseif ($action === 'change_password') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors[] = "Security check failed. Please reload and try again.";
            } else {
                $curr_pass   = (string)($_POST['current_password'] ?? '');
                $new_pass    = (string)($_POST['new_password'] ?? '');
                $confirm_new = (string)($_POST['confirm_password'] ?? '');
                if (!$pwdRow || !verify_password_row($pwdRow, $curr_pass)) {
                    $errors[] = "Your current password is incorrect.";
                } elseif (strlen($new_pass) < 8) {
                    $errors[] = "New password must be at least 8 characters.";
                } elseif ($new_pass !== $confirm_new) {
                    $errors[] = "New password confirmation does not match.";
                } else {
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $upd  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->execute([$hash, $my_id]);
                    $success[] = "Password updated.";
                }
            }
        } elseif ($action === 'toggle_emails') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors[] = "Security check failed. Please reload and try again.";
            } else {
                $val = !empty($_POST['unsubscribe_emails']) ? 1 : 0;
                $upd = $pdo->prepare("UPDATE users SET unsubscribe_emails = ? WHERE id = ?");
                $upd->execute([$val, $my_id]);
                $success[] = $val ? "You have unsubscribed from admin emails." : "You are subscribed to admin emails.";
                $stmt = $pdo->prepare("SELECT id, username, nickname, avatar, usergroup, simbucks,
                                              profile_theme, custom_background, email, unsubscribe_emails
                                       FROM users WHERE username = ?");
                $stmt->execute([$viewing_user]);
                $user = $stmt->fetch();
            }
        }
    }
}

/* ------------------ fetch pets ------------------ */
$stmt = $pdo->prepare("SELECT id, pet_name, pet_image, type, level FROM user_pets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$pets = $stmt->fetchAll();

/* ------------------ friends state for viewer vs profile subject ------------------ */
$friendState = null;
if (!empty($my_id) && (int)$my_id !== (int)$user['id']) {
    $friendState = friends_get_status($pdo, (int)$my_id, (int)$user['id']);
}

/* ------------------ view helpers ------------------ */
$themeClass = htmlspecialchars($user['profile_theme'] ?? 'theme-default');
$customBgStyle = ($themeClass === 'theme-custom' && !empty($user['custom_background']))
    ? "background: url('" . htmlspecialchars($user['custom_background']) . "') center/cover no-repeat fixed;"
    : "";
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($viewing_user) ?>'s Profile</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link rel="stylesheet" href="assets/themes.css">
  <style>
    body.theme-custom { <?= $customBgStyle ?> color: #333; }
    .center-card { max-width: 720px; margin: 0 auto; }
    .alert { padding:10px; border-radius:8px; margin:10px 0; }
    .alert.ok { background:#1f2d1f; border:1px solid #3a5; color:#e8ffe8; }
    .alert.err{ background:#3b1d1d; border:1px solid #a33; color:#ffeaea; }
    .form-card { border:1px solid #ccc; border-radius:12px; padding:16px; margin-top:20px; }
    .form-card label { display:block; margin:10px 0 6px; }
    .form-card input[type=text],
    .form-card input[type=email],
    .form-card input[type=password] { width:100%; padding:10px; border-radius:8px; border:1px solid #bbb; }
    .button { display:inline-block; margin-top:10px; padding:10px 14px; border-radius:8px; border:1px solid #888; background:#f3f3f3; cursor:pointer; }
    .pill { display:inline-block; padding:2px 8px; border-radius:999px; background:#f1f5f9; border:1px solid #cbd5e1; font-size:12px; margin-left:6px }
    .friends-grid { display:flex; flex-wrap:wrap; gap:14px; }
    .friend-card { border:1px solid #ddd; border-radius:10px; padding:10px; width:160px; text-align:center; background:#fff; }
    .friend-card img { width:72px; height:72px; border-radius:999px; object-fit:cover; border:1px solid #ccc; }
    .muted { color:#666; }
  </style>
</head>
<body class="<?= $themeClass ?>">
<?php include 'menu.php'; ?>
<div class="container">

  <h1>
    <?= htmlspecialchars($viewing_user) ?>'s Profile
    <?php if (!$isOwner && $friendState): ?>
      <?php if ($friendState['state'] === 'accepted'): ?>
        <span class="pill">Friends</span>
      <?php elseif ($friendState['state'] === 'pending' && $friendState['direction'] === 'out'): ?>
        <span class="pill">Request sent</span>
      <?php elseif ($friendState['state'] === 'pending' && $friendState['direction'] === 'in'): ?>
        <span class="pill">Requested you</span>
      <?php endif; ?>
    <?php endif; ?>
  </h1>

  <?php
  // Owner-only pending count banner (PLACED OUTSIDE <h1/> so the anchor works cleanly)
  $pending_count = 0;
  if ($isOwner) {
    $pc = $pdo->prepare("SELECT COUNT(*) FROM user_friends WHERE addressee_id = ? AND status = 'pending'");
    $pc->execute([$user['id']]);
    $pending_count = (int)$pc->fetchColumn();
  }
  ?>
  
  
  
  
  <?php if ($isOwner && $pending_count > 0): ?>
  <div class="alert ok center-card" style="background:#fff8e1; border:1px solid #f6d365; color:#7a5b00;">
    You have <strong><?= $pending_count ?></strong> pending friend request<?= $pending_count===1?'':'s' ?>.
    <a href="friends.php" style="margin-left:6px; text-decoration:underline;">Review</a>
  </div>
<?php endif; ?>
    
    
    

  <?php foreach ($errors as $e): ?>
    <div class="alert err center-card"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>
  <?php foreach ($success as $s): ?>
    <div class="alert ok center-card"><?= htmlspecialchars($s) ?></div>
  <?php endforeach; ?>

  <div class="center-card" style="display:flex; gap:30px; align-items:center;">
    <div>
      <img src="<?= htmlspecialchars($user['avatar'] ?? 'images/default-avatar.png') ?>" alt="Avatar" width="150" height="150" style="border-radius:12px; border:1px solid #ccc;">
    </div>
    <div>
      <p><strong>Nickname:</strong> <?= htmlspecialchars($user['nickname'] ?? 'None') ?></p>
      <p><strong>User Group:</strong> <?= htmlspecialchars($user['usergroup']) ?></p>
      <?php if ($isOwner): ?>
        <p><strong>Your Simbucks:</strong> <?= $user['simbucks'] ?></p>
        <?php if (!empty($user['email'])): ?>
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <?php endif; ?>
      <?php endif; ?>

      <!-- FRIEND BUTTONS -->
      <?php if (!empty($my_id) && !$isOwner && $friendState): ?>
        <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
          <?php if ($friendState['state'] === 'none'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="friend_request">
              <button class="button" type="submit">Add Friend</button>
            </form>

          <?php elseif ($friendState['state'] === 'pending' && $friendState['direction'] === 'out'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="friend_cancel">
              <button class="button" type="submit">Cancel Request</button>
            </form>

          <?php elseif ($friendState['state'] === 'pending' && $friendState['direction'] === 'in'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="friend_accept">
              <input type="hidden" name="from_id" value="<?= (int)$user['id'] ?>">
              <button class="button" type="submit">Accept</button>
            </form>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="friend_decline">
              <input type="hidden" name="from_id" value="<?= (int)$user['id'] ?>">
              <button class="button" type="submit">Decline</button>
            </form>

          <?php elseif ($friendState['state'] === 'accepted'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="friend_unfriend">
              <button class="button" type="submit">Unfriend</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <!-- /FRIEND BUTTONS -->

    </div>
  </div>

  <?php if ($isOwner): ?>
  <div class="center-card form-card">
    <h3>Edit Profile</h3>
    <form method="post">
      <input type="hidden" name="action" value="save_profile">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
      <label>Nickname:<br>
        <input type="text" name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>">
      </label>
      <label>Avatar Image URL:<br>
        <input type="text" name="avatar" value="<?= htmlspecialchars($user['avatar']) ?>">
      </label>
      <label>Choose Theme:<br>
        <select name="profile_theme" onchange="document.getElementById('customBgField').style.display = (this.value === 'theme-custom') ? 'block' : 'none';">
          <?php foreach ($themes as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($user['profile_theme'] === $value) ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div id="customBgField" style="display: <?= ($user['profile_theme'] === 'theme-custom') ? 'block' : 'none' ?>;">
        <label>Custom Background Image URL:<br>
          <input type="text" name="custom_background" value="<?= htmlspecialchars($user['custom_background']) ?>">
        </label>
      </div>
      <button class="button" type="submit">Save Changes</button>
    </form>
  </div>

  <!-- Email Preferences -->
  <div class="center-card form-card">
    <h3>Email Preferences</h3>
    <form method="post">
      <input type="hidden" name="action" value="toggle_emails">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
      <label>
        <input type="checkbox" name="unsubscribe_emails" value="1" <?= !empty($user['unsubscribe_emails']) ? 'checked' : '' ?>>
        Unsubscribe from admin emails
      </label>
      <button class="button" type="submit">Save Preference</button>
    </form>
  </div>

  <!-- Change Email -->
  <div class="center-card form-card">
    <h3>Change Email</h3>
    <form method="post" autocomplete="on">
      <input type="hidden" name="action" value="change_email">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
      <label>New email</label>
      <input type="email" name="new_email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
      <label>Current password</label>
      <input type="password" name="current_password" required>
      <button class="button" type="submit">Update Email</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="center-card form-card">
    <h3>Change Password</h3>
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
      <label>Current password</label>
      <input type="password" name="current_password" required>
      <label>New password (min 8 characters)</label>
      <input type="password" name="new_password" minlength="8" required>
      <label>Confirm new password</label>
      <input type="password" name="confirm_password" minlength="8" required>
      <button class="button" type="submit">Update Password</button>
    </form>
  </div>

  <!-- Pending friend requests (owner only) -->
  <?php $incoming = friends_pending_incoming($pdo, (int)$user['id'], 20); ?>
  <div class="center-card form-card" id="pending-requests">
    <h3>Pending Friend Requests</h3>
    <?php if (empty($incoming)): ?>
      <p class="muted" style="margin:0;">No pending requests.</p>
    <?php else: ?>
      <?php foreach ($incoming as $req): ?>
        <div style="display:flex; align-items:center; gap:10px; margin:8px 0;">
          <img src="<?= htmlspecialchars($req['avatar'] ?? 'images/default-avatar.png') ?>" width="36" height="36" style="border-radius:999px; border:1px solid #ccc;">
          <a href="profile.php?user=<?= urlencode($req['username']) ?>"><?= htmlspecialchars($req['username']) ?></a>
          <form method="post" style="margin-left:auto;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="friend_accept">
            <input type="hidden" name="from_id" value="<?= (int)$req['from_id'] ?>">
            <button class="button" type="submit">Accept</button>
          </form>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="friend_decline">
            <input type="hidden" name="from_id" value="<?= (int)$req['from_id'] ?>">
            <button class="button" type="submit">Decline</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Friends list on this profile -->
  <div class="center-card form-card">
    <h3><?= $isOwner ? 'Your Friends' : htmlspecialchars($user['username'])."'s Friends" ?></h3>
    <?php
      $friendTotal = friends_count_for_user($pdo, (int)$user['id']);
      $friends = friends_list_for_user($pdo, (int)$user['id'], 24, 0);
    ?>
    <?php if ($friendTotal === 0): ?>
      <p class="muted" style="margin:0;">No friends yet.</p>
    <?php else: ?>
      <div class="friends-grid">
        <?php foreach ($friends as $f): ?>
          <div class="friend-card">
            <a href="profile.php?user=<?= urlencode($f['username']) ?>">
              <img src="<?= htmlspecialchars($f['avatar'] ?? 'images/default-avatar.png') ?>" alt="">
              <div style="margin-top:6px; font-weight:600;"><?= htmlspecialchars($f['username']) ?></div>
              <?php if (!empty($f['nickname'])): ?>
                <div class="muted" style="font-size:12px;"><?= htmlspecialchars($f['nickname']) ?></div>
              <?php endif; ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($friendTotal > count($friends)): ?>
        <p class="muted" style="margin-top:8px;">Showing <?= count($friends) ?> of <?= $friendTotal ?>.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>



<?php
// FRIENDS PREVIEW ON PROFILE
$friendsPrev = $pdo->prepare("
  SELECT CASE WHEN uf.requester_id=? THEN uf.addressee_id ELSE uf.requester_id END AS fid,
         u.username, u.avatar, u.nickname
  FROM user_friends uf
  JOIN users u ON u.id = CASE WHEN uf.requester_id=? THEN uf.addressee_id ELSE uf.requester_id END
  WHERE uf.status='accepted' AND (uf.requester_id=? OR uf.addressee_id=?)
  ORDER BY uf.id DESC
  LIMIT 12
");
$profileUid = (int)$user['id'];
$friendsPrev->execute([$profileUid,$profileUid,$profileUid,$profileUid]);
$friendsPrev = $friendsPrev->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="center-card form-card">
  <h3><?= $isOwner ? 'Your Friends' : htmlspecialchars($user['username'])."'s Friends" ?></h3>
  <?php if (!$friendsPrev): ?>
    <p class="muted" style="margin:0;">No friends yet.</p>
  <?php else: ?>
    <div class="friends-grid">
      <?php foreach ($friendsPrev as $f): ?>
        <div class="friend-card">
          <a href="profile.php?user=<?= urlencode($f['username']) ?>">
            <img src="<?= htmlspecialchars($f['avatar'] ?? 'images/default-avatar.png') ?>" alt="">
            <div style="margin-top:6px; font-weight:600;"><?= htmlspecialchars($f['username']) ?></div>
            <?php if (!empty($f['nickname'])): ?>
              <div class="muted" style="font-size:12px;"><?= htmlspecialchars($f['nickname']) ?></div>
            <?php endif; ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($isOwner): ?>
      <p class="muted" style="margin-top:8px;">
        Manage friends here: <a href="friends.php">Friends page</a>
      </p>
    <?php endif; ?>
  <?php endif; ?>
</div>



  <h2 class="center-card">Pets Owned</h2>
  <?php if ($pets): ?>
    <div class="center-card" style="display:flex; flex-wrap:wrap; gap:20px;">
    <?php foreach ($pets as $pet): ?>
      <div style="border:1px solid #ccc; padding:10px; text-align:center;">
        <?php
          if ($pet['level'] == 1 || $pet['level'] == 2) {
              $img_src = "images/levels/" . htmlspecialchars($pet['type']) . "_Egg.png";
          } else {
              if (strpos($pet['pet_image'], 'generated/') !== false) {
                  $img_src = "images/generated/" . basename($pet['pet_image']);
              } else {
                  $img_src = "images/levels/" . htmlspecialchars($pet['type']) . ".png";
              }
          }
        ?>
        <img src="<?= $img_src ?>" width="150"><br>
        <?= htmlspecialchars($pet['pet_name']) ?><br>
        <a href="public_pet_profile.php?id=<?= $pet['id'] ?>">View Profile</a>
      </div>
    <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="center-card">This user has no pets yet.</p>
  <?php endif; ?>

</div>
</body>
<?php include 'footer.php'; ?>
</html>