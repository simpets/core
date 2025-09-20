<?php
session_start();
require_once __DIR__ . "/includes/db.php";

/* ---------- helpers ---------- */
function is_admin(): bool {
    $g = strtolower($_SESSION['usergroup'] ?? '');
    return !empty($_SESSION['user_id']) && in_array($g, ['admin','admins']);
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function niceDate($dt){ if(!$dt) return ''; $ts=strtotime($dt); return date('M j, Y g:ia', $ts); }
function excerpt($html,$len=220){ $text=trim(strip_tags($html)); return (mb_strlen($text)<=$len)?$text:mb_substr($text,0,$len-1).'…'; }

/* ---------- discover actual column names ---------- */
$cols = [];
try {
  $c = $pdo->query("SHOW COLUMNS FROM news");
  while ($r = $c->fetch(PDO::FETCH_ASSOC)) $cols[strtolower($r['Field'])] = true;
} catch (Throwable $e) { /* if table missing, we'll try to create */ }

$hasTable = !empty($cols);

/* Optional: create a standard table if none exists at all */
if (!$hasTable) {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS news (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      body MEDIUMTEXT NOT NULL,
      author_id INT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    // reload columns
    $c = $pdo->query("SHOW COLUMNS FROM news");
    while ($r = $c->fetch(PDO::FETCH_ASSOC)) $cols[strtolower($r['Field'])] = true;
    $hasTable = true;
  } catch (Throwable $e) {}
}

/* Pick best-match column names and build SELECT aliases */
function pickCol($cols, $candidates, $default = null) {
  foreach ($candidates as $name) {
    if (isset($cols[strtolower($name)])) return $name;
  }
  return $default; // may be null
}

$titleCol   = pickCol($cols, ['title','subject','headline','name']);
$bodyCol    = pickCol($cols, ['body','content','message','text','description']);
$createdCol = pickCol($cols, ['created_at','created','date','posted','published_at','time','timestamp']);
$authorCol  = pickCol($cols, ['author_id','author','user_id','posted_by']);

$hasAuthor  = $authorCol !== null;
$hasCreated = $createdCol !== null;

/* ---------- handle admin create (uses whatever columns exist) ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
  if (($_POST['action'] ?? '') === 'create_news') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
      $errors[] = "Security token invalid. Please reload.";
    } else {
      $inTitle = trim($_POST['title'] ?? '');
      $inBody  = trim($_POST['body']  ?? '');
      if ($inTitle === '' || $inBody === '') {
        $errors[] = "Title and body are required.";
      } else {
        try {
          if ($titleCol && $bodyCol) {
            if ($hasAuthor) {
              $sql = "INSERT INTO news ($titleCol, $bodyCol, $authorCol) VALUES (?, ?, ?)";
              $st  = $pdo->prepare($sql);
              $st->execute([$inTitle, $inBody, ($_SESSION['user_id'] ?? null)]);
            } else {
              $sql = "INSERT INTO news ($titleCol, $bodyCol) VALUES (?, ?)";
              $st  = $pdo->prepare($sql);
              $st->execute([$inTitle, $inBody]);
            }
          } else {
            // Fallback if the legacy table truly has unknown schema: create the standard table alongside
            $pdo->exec("CREATE TABLE IF NOT EXISTS news (
              id INT AUTO_INCREMENT PRIMARY KEY,
              title VARCHAR(255) NOT NULL,
              body MEDIUMTEXT NOT NULL,
              author_id INT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NULL,
              INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $pdo->prepare("INSERT INTO news (title, body, author_id) VALUES (?, ?, ?)")
                ->execute([$inTitle, $inBody, ($_SESSION['user_id'] ?? null)]);
          }
          header("Location: news.php");
          exit;
        } catch (Throwable $e) {
          $errors[] = "Failed to publish: " . $e->getMessage();
        }
      }
    }
  }
}

/* ---------- single view or list ---------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isSingle = $id > 0;

/* Build dynamic SELECT for list/single */
function buildSelect($titleCol,$bodyCol,$createdCol,$authorCol,&$join){
  $parts = ["n.id"];
  if ($titleCol)   $parts[] = "n.$titleCol   AS title";
  if ($bodyCol)    $parts[] = "n.$bodyCol    AS body";
  if ($createdCol) $parts[] = "n.$createdCol AS created_at";
  $join = "";
  if ($authorCol) {
    $parts[] = "u.username AS author_name";
    $join = " LEFT JOIN users u ON u.id = n.$authorCol ";
  }
  return "SELECT ".implode(", ", $parts)." FROM news n";
}

if ($isSingle) {
  $join = "";
  $sql  = buildSelect($titleCol,$bodyCol,$createdCol,$authorCol,$join) . " $join WHERE n.id = ?";
  $st   = $pdo->prepare($sql);
  $st->execute([$id]);
  $article = $st->fetch();
  if (!$article) { http_response_code(404); $singleError = "News item not found."; }
} else {
  $perPage = 10;
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $perPage;
  $total  = 0;
  try { $total = (int)$pdo->query("SELECT COUNT(*) FROM news")->fetchColumn(); } catch (Throwable $e) {}

  $orderCol = $hasCreated ? "n.$createdCol" : "n.id";
  $join = "";
  $sql  = buildSelect($titleCol,$bodyCol,$createdCol,$authorCol,$join) . " $join ORDER BY $orderCol DESC LIMIT :lim OFFSET :off";
  $st   = $pdo->prepare($sql);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll();
  $lastPage = max(1, (int)ceil(($total ?: 0) / $perPage));
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= $isSingle ? esc($article['title'] ?? 'News') : 'News & Updates' ?> — Simpets</title>
  <link rel="stylesheet" href="/assets/styles.css">
  <style>
    .wrap{max-width:900px;margin:28px auto;padding:0 16px}
    .page-title{margin:0 0 12px}
    .muted{color:#666}
    .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
    .news-title{margin:0}
    .news-meta{font-size:13px;color:#667}
    .news-body{margin-top:8px;line-height:1.6}
    .news-list-item{display:block;text-decoration:none;color:inherit}
    .news-list-item:hover .news-title{text-decoration:underline}
    .alert{padding:10px;border-radius:8px;margin:10px 0}
    .err{background:#ffeaea;border:1px solid #a33}
    .ok{background:#eaffea;border:1px solid #3a5}
    .pager{display:flex;gap:10px;justify-content:center;margin:20px 0}
    .pager a,.pager span{padding:8px 12px;border:1px solid #ccc;border-radius:8px;background:#fafafa}
    .admin-form label{display:block;margin:8px 0 6px}
    .admin-form input[type=text]{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
    .admin-form textarea{width:100%;min-height:220px;padding:10px;border:1px solid #ccc;border-radius:8px}
    .btn{padding:10px 14px;border:1px solid #888;background:#f3f3f3;cursor:pointer}
  </style>
</head>
<body>
<?php include __DIR__ . '/menu.php'; ?>
<div class="wrap">

<?php if (!$isSingle): ?>
  <h1 class="page-title">News & Updates</h1>
  <?php foreach ($errors as $e): ?><div class="alert err"><?= esc($e) ?></div><?php endforeach; ?>

  <?php if (is_admin()): ?>
    <div class="card">
      <h3>Post a new update (Admins)</h3>
      <form method="post" class="admin-form">
        <input type="hidden" name="action" value="create_news">
        <input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">
        <label>Title</label>
        <input type="text" name="title" required>
        <label>Message (HTML allowed)</label>
        <textarea name="body" placeholder="Write your update..."></textarea>
        <button class="btn" type="submit">Publish</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <div class="card">No news yet.</div>
  <?php else: ?>
    <?php foreach ($rows as $n): ?>
      <a class="news-list-item" href="/news.php?id=<?= (int)$n['id'] ?>">
        <div class="card">
          <h3 class="news-title"><?= esc($n['title'] ?? ('News #'.$n['id'])) ?></h3>
          <div class="news-meta">
            Posted <?= esc(isset($n['created_at']) ? niceDate($n['created_at']) : ('ID '.$n['id'])) ?>
            <?php if (!empty($n['author_name'])): ?> by <?= esc($n['author_name']) ?><?php endif; ?>
          </div>
          <div class="news-body"><?= esc(excerpt($n['body'] ?? '')) ?></div>
        </div>
      </a>
    <?php endforeach; ?>

    <?php if (($lastPage ?? 1) > 1): ?>
      <div class="pager">
        <?php if (($page ?? 1) > 1): ?><a href="/news.php?page=<?= $page-1 ?>">« Prev</a><?php else:?><span>« Prev</span><?php endif; ?>
        <span>Page <?= $page ?> / <?= $lastPage ?></span>
        <?php if ($page < $lastPage): ?><a href="/news.php?page=<?= $page+1 ?>">Next »</a><?php else:?><span>Next »</span><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

<?php else: ?>
  <?php if (!empty($singleError)): ?>
    <div class="card"><?= esc($singleError) ?></div>
  <?php else: ?>
    <h1 class="page-title"><?= esc($article['title'] ?? ('News #'.$article['id'])) ?></h1>
    <div class="news-meta">
      Posted <?= esc(isset($article['created_at']) ? niceDate($article['created_at']) : ('ID '.$article['id'])) ?>
      <?php if (!empty($article['author_name'])): ?> by <?= esc($article['author_name']) ?><?php endif; ?>
    </div>
    <div class="card news-body">
      <?php
        // Show body if we found one; otherwise inform admin subtly
        if (!empty($article['body'])) {
          echo $article['body'];
        } else {
          echo "<em>No body/content column found for this entry.</em>";
        }
      ?>
    </div>
    <p><a href="/news.php">« Back to all news</a></p>
  <?php endif; ?>
<?php endif; ?>

</div>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>