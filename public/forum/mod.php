<?php
require_once __DIR__ . '/_common.php';
require_member();
if (!is_admin()) { http_response_code(403); exit('Admins only.'); }

$forum_id = (int)($_GET['forum_id'] ?? 0);
$thread   = (int)($_GET['thread'] ?? 0);

// Actions: lock/unlock forum, lock/unlock thread, rename thread, delete post
if (isset($_GET['lock_forum'])) {
  $pdo->prepare("UPDATE forums SET is_locked = 1 WHERE id = ?")->execute([(int)$_GET['lock_forum']]);
  header('Location: /forum/forum.php?id='.(int)$_GET['lock_forum']); exit;
}
if (isset($_GET['unlock_forum'])) {
  $pdo->prepare("UPDATE forums SET is_locked = 0 WHERE id = ?")->execute([(int)$_GET['unlock_forum']]);
  header('Location: /forum/forum.php?id='.(int)$_GET['unlock_forum']); exit;
}
if (isset($_GET['lock_thread'])) {
  $pdo->prepare("UPDATE threads SET is_locked = 1 WHERE id = ?")->execute([(int)$_GET['lock_thread']]);
  header('Location: /forum/thread.php?id='.(int)$_GET['lock_thread']); exit;
}
if (isset($_GET['unlock_thread'])) {
  $pdo->prepare("UPDATE threads SET is_locked = 0 WHERE id = ?")->execute([(int)$_GET['unlock_thread']]);
  header('Location: /forum/thread.php?id='.(int)$_GET['unlock_thread']); exit;
}
if (isset($_GET['delete_post'])) {
  $pid = (int)$_GET['delete_post'];
  $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$pid]);
  $redir = (int)($_GET['thread'] ?? 0);
  header('Location: /forum/thread.php?id='.$redir); exit;
}

forum_header('Moderation');

if ($forum_id) {
  // Quick toggles for forum
  $f = $pdo->prepare("SELECT id, name, is_locked FROM forums WHERE id = ?");
  $f->execute([$forum_id]);
  $forum = $f->fetch();
  if ($forum): ?>
    <p>Forum: <strong><?= e($forum['name']) ?></strong></p>
    <?php if ($forum['is_locked']): ?>
      <p><a href="?unlock_forum=<?= (int)$forum['id'] ?>">Unlock forum</a></p>
    <?php else: ?>
      <p><a href="?lock_forum=<?= (int)$forum['id'] ?>">Lock forum</a></p>
    <?php endif; ?>
  <?php endif;
}

if ($thread) {
  $t = $pdo->prepare("SELECT id, title, is_locked FROM threads WHERE id = ?");
  $t->execute([$thread]);
  $th = $t->fetch();
  if ($th): ?>
    <p>Thread: <strong><?= e($th['title']) ?></strong></p>
    <?php if ($th['is_locked']): ?>
      <p><a href="?unlock_thread=<?= (int)$th['id'] ?>">Unlock thread</a></p>
    <?php else: ?>
      <p><a href="?lock_thread=<?= (int)$th['id'] ?>">Lock thread</a></p>
    <?php endif; ?>
    <form method="post" style="margin-top:10px;">
      <?php csrf_input(); ?>
      <input type="hidden" name="rename_thread" value="<?= (int)$th['id'] ?>">
      <label>Rename thread</label>
      <input type="text" name="new_title" value="<?= e($th['title']) ?>" style="padding:8px;">
      <button type="submit">Rename</button>
    </form>
  <?php endif;
}

// Handle rename POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_thread'])) {
  if (!csrf_ok($_POST['csrf'] ?? '')) { echo '<p style="color:#a33;">CSRF error.</p>'; }
  else {
    $tid = (int)$_POST['rename_thread'];
    $title = trim($_POST['new_title'] ?? '');
    if ($title !== '') {
      $pdo->prepare("UPDATE threads SET title = ?, updated_at = NOW() WHERE id = ?")->execute([$title, $tid]);
      echo '<p>Renamed.</p><p><a href="/forum/thread.php?id='.$tid.'">Back to thread</a></p>';
    }
  }
}

forum_footer();