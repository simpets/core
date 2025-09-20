<?php
// /forum/thread.php — view one thread (schema-adaptive, safe HTML render + image URL auto-embed)
require_once __DIR__ . '/_common.php';
require_member();

$thread_id = (int)($_GET['id'] ?? 0);
if ($thread_id <= 0) { http_response_code(400); exit('Invalid thread id'); }

/* Load thread + forum (for permissions) */
$st = $pdo->prepare("SELECT t.id, t.forum_id, t.title, t.is_locked, f.name AS forum_name, f.is_container, f.admin_only
                     FROM threads t
                     JOIN forums f ON f.id = t.forum_id
                     WHERE t.id = ?");
$st->execute([$thread_id]);
$thread = $st->fetch(PDO::FETCH_ASSOC);
if (!$thread) { http_response_code(404); exit('Thread not found'); }
require_forum_access($thread);
if (!empty($thread['is_container'])) { http_response_code(400); exit('This is a category, not a thread.'); }

/* pagination */
$perPage = max(1, min(100, (int)($_GET['per'] ?? 20)));
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

/* helper: get posts columns */
function posts_table_cols(PDO $pdo): array {
  $cols = [];
  $q = $pdo->query("SHOW COLUMNS FROM posts");
  if ($q) foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;
  return $cols;
}
$pcols = posts_table_cols($pdo);

/* count posts — prefer thread_id but accept topic_id */
if (!empty($pcols['thread_id'])) {
  $cnt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE thread_id = ?");
  $cnt->execute([$thread_id]);
} else {
  $cnt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ?");
  $cnt->execute([$thread_id]);
}
$total = (int)$cnt->fetchColumn();

/* build SELECT with unique aliases */
$timeCols = [];
foreach (['created_at','post_time','posted_at','created'] as $c) if (!empty($pcols[$c])) $timeCols[] = $c;
$timeExpr = !empty($timeCols) ? implode(', ', array_map(fn($c)=>"p.$c",$timeCols)) : '';

$bodyCandidates   = ['body','content','message','post_text','text'];
$authorCandidates = ['author','poster','username'];
$authorIdCols     = ['user_id','author_id','poster_id'];

$selectParts = ['p.id'];
foreach ($authorIdCols as $c) if (!empty($pcols[$c])) $selectParts[] = "p.$c AS user_id";
foreach ($authorCandidates as $c) if (!empty($pcols[$c])) $selectParts[] = "p.$c AS author_name";

/* body columns with UNIQUE aliases */
$bodyAliases = [];
foreach ($bodyCandidates as $c) {
  if (!empty($pcols[$c])) {
    $alias = "body__{$c}";
    $bodyAliases[] = $alias;
    $selectParts[] = "p.$c AS $alias";
  }
}
if ($timeExpr) $selectParts[] = $timeExpr;

$selectList = implode(', ', $selectParts);

/* fetch posts (inline LIMIT/OFFSET due to MySQL prepared stmts limitations) */
if (!empty($pcols['thread_id'])) {
  $sql = "SELECT $selectList FROM posts p WHERE p.thread_id = ? ORDER BY p.id ASC LIMIT $perPage OFFSET $offset";
} else {
  $sql = "SELECT $selectList FROM posts p WHERE p.topic_id = ? ORDER BY p.id ASC LIMIT $perPage OFFSET $offset";
}
$ps = $pdo->prepare($sql);
$ps->execute([$thread_id]);
$posts = $ps->fetchAll(PDO::FETCH_ASSOC);

$lastPage = max(1, (int)ceil($total / $perPage));

/* ---- Render helper: auto-embed image URLs safely ----
   - If the content is already HTML (sanitized on save), keep it but
     convert <a href="...image.ext">...</a> into <img src="...">.
   - If it's plain text, escape it and:
       * convert image URLs to <img>
       * convert other URLs to <a>
       * keep line breaks
*/
if (!function_exists('render_post_html')) {
  function render_post_html(string $raw): string {
    // quick check: looks like HTML?
    $looksHtml = (strpos($raw, '<') !== false);

    if ($looksHtml) {
      // Upgrade image links to <img>
      $out = preg_replace_callback(
        '#<a\s[^>]*href=("|\')(https?://[^"\']+\.(?:png|jpe?g|gif|webp|svg))\1[^>]*>.*?</a>#i',
        function($m){
          $src = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          return '<img src="'.$src.'" loading="lazy">';
        },
        $raw
      );
      return $out;
    }

    // Plain text path
    $t = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // image URLs -> <img>
    $t = preg_replace_callback(
      '~\bhttps?://[^\s<>"\']+\.(?:png|jpe?g|gif|webp|svg)\b~i',
      function($m){
        $src = htmlspecialchars($m[0], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<img src="'.$src.'" loading="lazy">';
      },
      $t
    );

    // other URLs -> <a>
    $t = preg_replace(
      '~\bhttps?://[^\s<>"\']+~i',
      '<a href="$0" target="_blank" rel="nofollow ugc noopener">$0</a>',
      $t
    );

    // keep line breaks
    return nl2br($t);
  }
}

forum_header(e($thread['title']));
?>
<style>
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
  .muted{color:#666}
  .btn{display:inline-block;padding:8px 12px;border:1px solid #888;border-radius:8px;background:#f3f3f3;color:#111;text-decoration:none}
  .post{border-top:1px solid #eee;padding:12px 0}
  .post:first-child{border-top:0}
  .right{float:right}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #aaa;background:#fafafa;font-size:12px}
  .post img{max-width:100%;height:auto;border-radius:6px;display:block;margin:8px 0}
  .post table{border-collapse:collapse;max-width:100%}
  .post table, .post th, .post td{border:1px solid #ddd}
  .post th, .post td{padding:6px}
</style>

<p class="muted">
  Forum: <a href="/forum/forum.php?id=<?= (int)$thread['forum_id'] ?>"><?= e($thread['forum_name']) ?></a>
  <?php if (!empty($thread['is_locked'])): ?> &nbsp; <span class="pill">Locked</span><?php endif; ?>
</p>

<div class="card">
  <?php if (empty($posts)): ?>
    <p>No posts yet.</p>
  <?php else: ?>
    <?php foreach ($posts as $p): ?>
      <?php
        // choose the first non-empty body among the aliases
        $rawBody = '';
        foreach ($bodyAliases as $alias) {
          if (!empty($p[$alias])) { $rawBody = (string)$p[$alias]; break; }
        }
        // author display
        $display = '';
        if (!empty($p['user_id'])) {
          $display = user_display($pdo, (int)$p['user_id']);
        } elseif (!empty($p['author_name'])) {
          $display = e($p['author_name']);
        } else {
          $display = 'Member';
        }
        // time display
        $ts = '';
        foreach (['created_at','post_time','posted_at','created'] as $tcol) {
          if (!empty($p[$tcol])) { $ts = $p[$tcol]; break; }
        }
        // render
        $rendered = render_post_html($rawBody);
      ?>
      <div class="post">
        <div>
          <strong><?= $display ?></strong>
          <?php if ($ts): ?><span class="muted"> • <?= e($ts) ?></span><?php endif; ?>
          <?php if (!empty($_SESSION['user_id']) && ( (int)$p['user_id'] === (int)($_SESSION['user_id']) || is_admin() )): ?>
            <a class="btn right" href="/forum/edit_post.php?id=<?= (int)$p['id'] ?>">Edit</a>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px;">
          <?= $rendered /* sanitized on save; here we safely auto-embed image URLs */ ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($lastPage > 1): ?>
  <div style="text-align:center;margin:14px 0;">
    <?php if ($page > 1): ?><a class="btn" href="?id=<?= (int)$thread_id ?>&page=<?= $page-1 ?>&per=<?= $perPage ?>">« Prev</a><?php endif; ?>
    <span class="pill">Page <?= $page ?> / <?= $lastPage ?></span>
    <?php if ($page < $lastPage): ?><a class="btn" href="?id=<?= (int)$thread_id ?>&page=<?= $page+1 ?>&per=<?= $perPage ?>">Next »</a><?php endif; ?>
  </div>
<?php endif; ?>

<div>
  <?php if (!empty($thread['is_locked'])): ?>
    <span class="pill">Thread locked</span>
  <?php else: ?>
    <a class="btn" href="/forum/reply.php?thread_id=<?= (int)$thread_id ?>">Reply</a>
  <?php endif; ?>
  &nbsp; <a class="btn" href="/forum/forum.php?id=<?= (int)$thread['forum_id'] ?>">Back to forum</a>
</div>

<?php forum_footer(); ?>