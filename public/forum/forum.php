<?php
require_once __DIR__ . '/_common.php';
require_member();

$forum_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($forum_id <= 0) {
  header('Location: /forum/index.php'); exit;
}

$st = $pdo->prepare("SELECT id, name, parent_id, is_container, admin_only, description FROM forums WHERE id = ?");
$st->execute([$forum_id]);
$forum = $st->fetch(PDO::FETCH_ASSOC);
if (!$forum) { http_response_code(404); exit('Forum not found'); }

require_forum_access($forum);

forum_header('Forum: ' . $forum['name']);
?>
<style>
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
  .muted{color:#666}
  .btn{display:inline-block;padding:8px 12px;border:1px solid #888;border-radius:8px;background:#f3f3f3;color:#111;text-decoration:none}
  table.forum{width:100%;border-collapse:collapse}
  table.forum th, table.forum td{padding:10px;border-bottom:1px solid #eee;text-align:left}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #aaa;background:#fafafa;font-size:12px}
</style>

<p class="muted"><?= !empty($forum['description']) ? e($forum['description']) : '' ?></p>

<?php
/* If this is a category, list subforums (no threads) */
if (!empty($forum['is_container'])) {
  $st = $pdo->prepare("SELECT id, name, admin_only FROM forums WHERE parent_id = ? ORDER BY name ASC");
  $st->execute([$forum['id']]);
  $subs = $st->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <div class="card">
    <h3>Subforums</h3>
    <?php if (empty($subs)): ?>
      <div class="muted">No subforums yet.</div>
    <?php else: ?>
      <ul style="margin:0;padding-left:18px;">
        <?php foreach ($subs as $sf): if (!can_view_forum($sf)) continue; ?>
          <li>
            <a href="/forum/forum.php?id=<?= (int)$sf['id'] ?>"><?= e($sf['name']) ?></a>
            <?php if (!empty($sf['admin_only'])): ?><span class="pill">Staff</span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <p><a class="btn" href="/forum/index.php">« All forums</a></p>
  <?php forum_footer(); exit;
}

/* Normal forum: threads list */
$perPage = max(1, min(100, (int)($_GET['per'] ?? 20)));
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$st = $pdo->prepare("SELECT COUNT(*) FROM threads WHERE forum_id = ?");
$st->execute([$forum_id]);
$total = (int)$st->fetchColumn();

$sql = "SELECT id, title, is_locked, created_at, last_post_at
        FROM threads
        WHERE forum_id = ?
        ORDER BY COALESCE(last_post_at, created_at) DESC
        LIMIT $perPage OFFSET $offset";
$st = $pdo->prepare($sql);
$st->execute([$forum_id]);
$threads = $st->fetchAll(PDO::FETCH_ASSOC);
$lastPage = max(1, (int)ceil($total / $perPage));
?>

<div style="margin:12px 0;">
  <?php if (empty($forum['admin_only']) || in_array(strtolower($_SESSION['usergroup'] ?? ''), ['admin','admins'])): ?>
    <a class="btn" href="/forum/new_thread.php?forum_id=<?= (int)$forum_id ?>">+ New Thread</a>
  <?php else: ?>
    <span class="pill">Staff</span>
  <?php endif; ?>
  &nbsp; <a class="btn" href="/forum/index.php">All Forums</a>
</div>

<div class="card">
  <?php if ($total === 0): ?>
    <p>No threads yet.</p>
  <?php else: ?>
    <table class="forum">
      <tr>
        <th>Title</th>
        <th style="width:200px;">Last Activity</th>
        <th style="width:90px;">Status</th>
      </tr>
      <?php foreach ($threads as $t): ?>
        <tr>
          <td><a href="/forum/thread.php?id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a></td>
          <td class="muted"><?= e($t['last_post_at'] ?? $t['created_at'] ?? '') ?></td>
          <td><?= !empty($t['is_locked']) ? 'Locked' : 'Open' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php if ($lastPage > 1): ?>
      <div style="text-align:center;margin:14px 0;">
        <?php if ($page > 1): ?><a class="btn" href="?id=<?= (int)$forum_id ?>&page=<?= $page-1 ?>&per=<?= $perPage ?>">« Prev</a><?php endif; ?>
        <span class="pill">Page <?= $page ?> / <?= $lastPage ?></span>
        <?php if ($page < $lastPage): ?><a class="btn" href="?id=<?= (int)$forum_id ?>&page=<?= $page+1 ?>&per=<?= $perPage ?>">Next »</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php forum_footer(); ?>