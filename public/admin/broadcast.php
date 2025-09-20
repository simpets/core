<?php
// /admin/broadcast.php
session_start();
require_once __DIR__ . '/../includes/db.php';

// ---- Access control (case-insensitive) ----
$group = strtolower($_SESSION['usergroup'] ?? '');
if (empty($_SESSION['user_id']) || !in_array($group, ['admin','admins'])) {
  http_response_code(403);
  exit('Admins only.');
}

// ---- CSRF ----
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

// ---- Does unsubscribe_emails column exist? ----
$hasUnsub = false;
try {
  $chkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'unsubscribe_emails'");
  $hasUnsub = (bool)$chkCol->fetch();
} catch (Throwable $e) { /* assume false */ }

// ---- Counts ----
$totalWithEmail = 0;
try {
  $c = $pdo->query("SELECT COUNT(*) FROM users WHERE email IS NOT NULL AND email <> ''");
  $totalWithEmail = (int)$c->fetchColumn();
} catch (Throwable $e) { $totalWithEmail = 0; }

$eligibleCount = 0;
try {
  if ($hasUnsub) {
    $c2 = $pdo->query("SELECT COUNT(*) FROM users WHERE email IS NOT NULL AND email <> '' AND (unsubscribe_emails = 0 OR unsubscribe_emails IS NULL)");
  } else {
    $c2 = $pdo->query("SELECT COUNT(*) FROM users WHERE email IS NOT NULL AND email <> ''");
  }
  $eligibleCount = (int)$c2->fetchColumn();
} catch (Throwable $e) { $eligibleCount = 0; }

// ---- Fetch recipients list (used when sending) ----
try {
  if ($hasUnsub) {
    $stmt = $pdo->query("SELECT id, email FROM users
                         WHERE email IS NOT NULL AND email <> ''
                         AND (unsubscribe_emails = 0 OR unsubscribe_emails IS NULL)");
  } else {
    $stmt = $pdo->query("SELECT id, email FROM users
                         WHERE email IS NOT NULL AND email <> ''");
  }
  $allUsers = $stmt->fetchAll();
} catch (Throwable $e) {
  $allUsers = [];
}

// ---- Optional: create broadcast_log table if missing ----
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS broadcast_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    sent_count INT NOT NULL DEFAULT 0,
    method VARCHAR(32) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Throwable $e) {}

// ---- Sending configuration ----
$BATCH_SIZE = 50;
$FROM_EMAIL = 'no-reply@simpets.site'; // <-- set this
$FROM_NAME  = 'Simpets System';
$USE_PHPMAILER = class_exists('PHPMailer\\PHPMailer\\PHPMailer');

$err = ''; $ok = '';

// ---- Handle POST send ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $err = 'Security token invalid. Please reload.';
  } else {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');
    $isHtml  = !empty($_POST['is_html']);
    $test    = !empty($_POST['test_mode']);

    if ($subject === '' || $body === '') {
      $err = 'Subject and message are required.';
    } else {
      // recipient list
      $recipients = $allUsers;
      if ($test) {
        $adminStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $adminStmt->execute([$_SESSION['user_id']]);
        $adminEmail = $adminStmt->fetchColumn();
        if (!$adminEmail) { $err = 'Your admin account has no email on file.'; }
        else { $recipients = [['id'=>$_SESSION['user_id'], 'email'=>$adminEmail]]; }
      }

      if (!$err) {
        $sent = 0;
        $method = $USE_PHPMAILER ? 'phpmailer' : 'mail()';

        // require_once __DIR__ . '/../vendor/autoload.php'; // if using PHPMailer via Composer

        $chunks = array_chunk($recipients, $BATCH_SIZE);
        foreach ($chunks as $chunk) {
          if ($USE_PHPMAILER) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
              $mail->isSMTP();
              $mail->Host       = 'smtp.yourdomain.com';
              $mail->SMTPAuth   = true;
              $mail->Username   = 'smtp-user';
              $mail->Password   = 'smtp-pass';
              $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
              $mail->Port       = 587;

              $mail->setFrom($FROM_EMAIL, $FROM_NAME);
              $mail->addAddress($FROM_EMAIL, $FROM_NAME); // visible To
              foreach ($chunk as $u) {
                if (filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $mail->addBCC($u['email']);
              }
              $mail->Subject = $subject;
              if ($isHtml) { $mail->isHTML(true); $mail->Body = $body; $mail->AltBody = strip_tags($body); }
              else { $mail->Body = $body; }

              $mail->send();
              $sent += count($chunk);
            } catch (Throwable $e) { $err = 'Sending failed: '.$e->getMessage(); break; }
          } else {
            // fallback mail()
            $to = $FROM_EMAIL;
            $headers = ["From: {$FROM_NAME} <{$FROM_EMAIL}>", "Reply-To: {$FROM_EMAIL}"];
            if ($isHtml) { $headers[] = "MIME-Version: 1.0"; $headers[] = "Content-Type: text/html; charset=UTF-8"; }
            $bcc = [];
            foreach ($chunk as $u) if (filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $bcc[] = $u['email'];
            if ($bcc) $headers[] = "Bcc: " . implode(',', $bcc);
            $okMail = @mail($to, $subject, $body, implode("\r\n", $headers));
            if ($okMail) $sent += count($chunk); else { $err = 'mail() failed on a batch.'; break; }
          }
        }

        if ($sent > 0 && !$err) {
          $ok = $test ? "Test message sent to your admin email." : "Broadcast sent to {$sent} member(s).";
          $ins = $pdo->prepare("INSERT INTO broadcast_log (admin_id, subject, body, sent_count, method) VALUES (?, ?, ?, ?, ?)");
          $ins->execute([$_SESSION['user_id'], $subject, $body, $sent, $method]);
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
  <title>Admin Broadcast — Simpets</title>
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
    <h1>Admin Broadcast</h1>
    <div class="stats">
      <span>Total with email: <strong><?= number_format($totalWithEmail) ?></strong></span>
      <span>Eligible (not unsubscribed): <strong><?= number_format($eligibleCount) ?></strong></span>
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
          <label>Message</label>
          <textarea name="body" placeholder="Write your announcement... You can paste HTML if 'Send as HTML' is checked."></textarea>
          <label style="margin-top:6px">
            <input type="checkbox" name="is_html" value="1"> Send as HTML
          </label>
        </div>
        <div class="row">
          <label>
            <input type="checkbox" name="test_mode" value="1" checked>
            Send TEST to me first (recommended)
          </label>
        </div>
        <div class="row">
          <button class="btn" type="submit">Send Broadcast</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Notes</h3>
      <ul>
        <li>“Eligible” excludes members who checked <em>Unsubscribe from admin emails</em>.</li>
        <li>Set your real sender in the code: <code>$FROM_EMAIL</code>, <code>$FROM_NAME</code>.</li>
        <li>For best deliverability, use SMTP (PHPMailer) with SPF/DKIM on your domain.</li>
      </ul>
    </div>
  </div>
</body>
</html>
<body>
  <div class="wrap">
    <h1>Admin Broadcast</h1>
    <p class="muted">Total members with email: <?= count($allUsers) ?></p>

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
          <label>Message</label>
          <textarea name="body" placeholder="Write your announcement... You can paste HTML if 'Send as HTML' is checked."></textarea>
          <label style="margin-top:6px">
            <input type="checkbox" name="is_html" value="1"> Send as HTML
          </label>
        </div>
        <div class="row">
          <label>
            <input type="checkbox" name="test_mode" value="1" checked>
            Send TEST to me first (recommended)
          </label>
        </div>
        <div class="row">
          <button class="btn" type="submit">Send Broadcast</button>
        </div>
      </form>
    </div>
<p class="muted">Only members who have NOT unsubscribed will receive this broadcast.</p>

    <div class="card">
      <h3>Tips</h3>
      <ul>
        <li>Use a real SMTP sender with SPF/DKIM for best deliverability (PHPMailer section in code).</li>
        <li>We send in BCC batches of <?= (int)$BATCH_SIZE ?> to reduce spam filters.</li>
        <li>Consider adding an “unsubscribe” preference per user to be compliant.</li>
      </ul>
    </div>
  </div>
</body>
</html>
