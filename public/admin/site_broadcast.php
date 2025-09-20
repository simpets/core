<?php
// /admin/site_broadcast.php
session_start();
require_once __DIR__ . '/../includes/db.php';

/* ------------ Access control (admin only) ------------ */
$group = strtolower($_SESSION['usergroup'] ?? '');
if (empty($_SESSION['user_id']) || !in_array($group, ['admin','admins'])) {
  http_response_code(403);
  exit('Admins only.');
}
$ADMIN_ID = (int)$_SESSION['user_id'];

/* ------------ CSRF ------------ */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

/* ------------ Ensure messages table (only if missing) ------------ */
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS messages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      sender_id INT NOT NULL,
      recipient_id INT NOT NULL,
      subject VARCHAR(255) NOT NULL,
      body MEDIUMTEXT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      read_at DATETIME NULL,
      INDEX idx_recipient (recipient_id),
      INDEX idx_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
} catch (Throwable $e) {
  // If your schema already has a different messages table, you can ignore this.
}

/* ------------ Stats + recipient list ------------ */
$totalMembers = 0;
$eligibleMembers = 0;
$recipients = [];

try {
  $totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  // Exclude the admin themselves from the global send; test mode will target admin only.
  $stmt = $pdo->query("SELECT id FROM users WHERE id <> " . (int)$ADMIN_ID);
  $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
  $eligibleMembers = count($recipients);
} catch (Throwable $e) {
  $recipients = [];
}

$err = ''; $ok = '';

/* ------------ Handle POST ------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $err = 'Security token invalid. Please reload.';
  } else {
    $subject = trim((string)($_POST['subject'] ?? ''));
    $body    = trim((string)($_POST['body'] ?? ''));
    $test    = !empty($_POST['test_mode']);   // test = just to admin

    if ($subject === '' || $body === '') {
      $err = 'Subject and message are required.';
    } else {
      // determine target list
      $targetIds = $test ? [$ADMIN_ID] : $recipients;

      if (empty($targetIds)) {
        $err = 'No recipients found.';
      } else {
        try {
          // Insert messages in batches for performance
          $BATCH = 500; // tune as needed
          $insert = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
          $pdo->beginTransaction();
          $inserted = 0;

          $chunk = [];
          foreach ($targetIds as $uid) {
            $insert->execute([$ADMIN_ID, (int)$uid, $subject, $body]);
            $inserted++;
            // (single-row inserts; batching is handled by transaction)
          }

          $pdo->commit();

          if ($inserted > 0) {
            $ok = $test
              ? "Test message sent to your inbox."
              : "Broadcast posted to {$inserted} member inbox(es).";
          } else {
            $err = 'No messages were created.';
          }
        } catch (Throwable $e) {
          if ($pdo->inTransaction()) $pdo->rollBack();
          $err = 'Failed to post messages: ' . $e->getMessage();
        }
      }
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Site Broadcast — Simpets</title>
  <link rel="stylesheet" href="/assets/styles.css">
  <style>
    .wrap{max-width:760px;margin:30px auto;padding:0 16px}
    .card{border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0;background:#fff}
    .row{margin-bottom:12px}
    label{display:block;margin:8px 0 6px}
    input[type=text]{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
    textarea{width:100%;min-height:220px;padding:10px;border:1px solid #ccc;border-radius:8px}
    .btn{padding:10px 14px;border-radius:8px;border:1px solid #888;background:#f3f3f3;cursor:pointer}
    .muted{color:#666}
    .alert{padding:10px;border-radius:8px;margin:10px 0}
    .ok{background:#eaffea;border:1px solid #3a5}
    .err{background:#ffeaea;border:1px solid #a33}
    .stats{display:flex;gap:14px;font-size:14px}
    .stats span{background:#fafafa;border:1px solid #ddd;border-radius:8px;padding:6px 10px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Admin Site Broadcast</h1>
    <div class="stats">
      <span>Total members: <strong><?= number_format($totalMembers) ?></strong></span>
      <span>Recipients this send: <strong><?= number_format($eligibleMembers) ?></strong></span>
    </div>

    <?php if (!empty($err)): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if (!empty($ok)):  ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <div class="card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
        <div class="row">
          <label>Subject</label>
          <input type="text" name="subject" required>
        </div>
        <div class="row">
          <label>Message (shown in member inbox)</label>
          <textarea name="body" placeholder="Write your announcement for all members..."></textarea>
        </div>
        <div class="row">
          <label>
            <input type="checkbox" name="test_mode" value="1" checked>
            Send TEST to me first (recommended)
          </label>
        </div>
        <div class="row">
          <button class="btn" type="submit">Post to Inboxes</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Notes</h3>
      <ul>
        <li>This posts messages directly into each member’s inbox.</li>
        <li>Use <strong>Test</strong> first—it will only post to your own inbox.</li>
        <li>Recipients currently include <em>all users except you</em>. We can filter further (e.g., active only, certain groups) if you want.</li>
      </ul>
    </div>
  </div>
</body>
</html>