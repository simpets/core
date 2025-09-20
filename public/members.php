<?php
session_start();
require_once "includes/db.php";

// --- pagination setup ---
$perPage = max(1, min(100, (int)($_GET['per'] ?? 50)));
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Count total users
$total = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$lastPage = max(1, ceil($total / $perPage));

// Clamp page within range
if ($page > $lastPage) {
  $page = $lastPage;
  $offset = ($page - 1) * $perPage;
}

// Fetch current page
$stmt = $pdo->prepare("SELECT username, usergroup FROM users ORDER BY id ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function build_query(array $overrides = []): string {
  $params = $_GET;
  foreach ($overrides as $k=>$v) {
    if ($v === null) unset($params[$k]); else $params[$k] = $v;
  }
  return http_build_query($params);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Members</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .admin-star { color: gold; margin-left: 5px; }
    .pager { display:flex; gap:8px; margin-top:16px; }
    .pager a { padding:6px 10px; border:1px solid #ccc; border-radius:6px; text-decoration:none; color:#333; }
    .pager span { padding:6px 10px; }
  </style>
</head>

<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Members List</h1>
  <p>Total members: <strong><?= number_format($total) ?></strong></p>
  <ul>
    <?php foreach ($users as $user): ?>
      <li>
        <a href="profile.php?user=<?= urlencode($user['username']) ?>">
          <?= e($user['username']) ?>
        </a>
        <?php if ($user['usergroup'] === 'Admin'): ?>
          <span class="admin-star">★</span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($lastPage > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a href="?<?= e(build_query(['page'=>1])) ?>">« First</a>
        <a href="?<?= e(build_query(['page'=>$page-1])) ?>">‹ Prev</a>
      <?php endif; ?>
      <span>Page <?= $page ?> / <?= $lastPage ?></span>
      <?php if ($page < $lastPage): ?>
        <a href="?<?= e(build_query(['page'=>$page+1])) ?>">Next ›</a>
        <a href="?<?= e(build_query(['page'=>$lastPage])) ?>">Last »</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>

<?php include 'footer.php'; ?>
</html>