<?php
// /forum/reply.php — reply + legacy columns + NO-CDN WYSIWYG
require_once __DIR__ . '/_common.php';
require_member();

/* ---------- tiny sanitizer (fallback) ---------- */
if (!function_exists('sanitize_html_basic')) {
  function sanitize_html_basic(string $html): string {
    $allowed = '<b><strong><i><em><u><p><br><ul><ol><li><a><blockquote><hr><code><pre><table><thead><tbody><tr><th><td><h1><h2><h3>';
    $html = strip_tags($html, $allowed);
    // links: only http(s), add target/rel, strip JS handlers
    if (preg_match_all('#<a\s[^>]*href=("|\')(.*?)\1[^>]*>#i', $html, $m, PREG_SET_ORDER)) {
      foreach ($m as $a) {
        $full = $a[0]; $url = $a[2];
        if (!preg_match('#^https?://#i', $url)) {
          $html = str_replace($full, '', $html);
        } else {
          $safe = preg_replace('/\s(on\w+)=("|\')[^"\']*\2/i', '', $full);
          if (!preg_match('/\brel=/i',   $safe)) $safe = preg_replace('#<a\s#i', '<a rel="noopener noreferrer nofollow" ', $safe, 1);
          if (!preg_match('/\btarget=/i',$safe)) $safe = preg_replace('#<a\s#i', '<a target="_blank" ', $safe, 1);
          $html = str_replace($full, $safe, $html);
        }
      }
    }
    return $html;
  }
}

/* ---------- thread lookup & access ---------- */
$thread_id = (int)($_GET['thread_id'] ?? 0);
if ($thread_id <= 0) { http_response_code(400); exit('Invalid thread'); }

$st = $pdo->prepare("SELECT t.id, t.forum_id, t.is_locked, f.admin_only
                     FROM threads t JOIN forums f ON f.id = t.forum_id
                     WHERE t.id = ?");
$st->execute([$thread_id]);
$thread = $st->fetch(PDO::FETCH_ASSOC);
if (!$thread) { http_response_code(404); exit('Thread not found'); }
require_forum_access($thread);
if (!empty($thread['is_locked'])) { http_response_code(403); exit('Thread is locked.'); }

/* ---------- schema helpers ---------- */
function colmap(PDO $pdo, string $table): array {
  $out=[]; $q=$pdo->query("SHOW COLUMNS FROM {$table}");
  if ($q) foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $out[strtolower($r['Field'])]=$r;
  return $out;
}
function has(array $m,string $c):bool{ return isset($m[strtolower($c)]); }
function required(array $m,string $c):bool{
  $c=strtolower($c);
  return isset($m[$c]) && strtoupper((string)$m[$c]['Null'])==='NO' && $m[$c]['Default']===null;
}
function u_id():int{ return (int)($_SESSION['user_id'] ?? 0); }
function u_name():string{ return (string)($_SESSION['username'] ?? ''); }

/* ---------- handle submit ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $errors[] = "Security token invalid. Please reload.";
  } else {
    // Hidden textarea gets HTML from the contenteditable editor
    $body = trim((string)($_POST['body'] ?? ''));
    if ($body !== '') $body = sanitize_html_basic($body);
    if ($body === '' || trim(strip_tags($body)) === '') $errors[] = "Message cannot be empty.";

    if (!$errors) {
      try {
        $pdo->beginTransaction();

        $pmap   = colmap($pdo,'posts');
        $cols   = [];
        $params = [];

        // Linkage
        if (has($pmap,'thread_id')) { $cols[]='thread_id'; $params[]=$thread_id; }
        if (has($pmap,'topic_id'))  { $cols[]='topic_id';  $params[]=$thread_id; }

        // User id variants (fill all required, else first available)
        $uid = u_id();
        foreach (['user_id','author_id','poster_id'] as $c)
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$uid; }
        if (!array_intersect(['user_id','author_id','poster_id'], array_map('strtolower',$cols))) {
          foreach (['user_id','author_id','poster_id'] as $c)
            if (has($pmap,$c)) { $cols[]=$c; $params[]=$uid; break; }
        }

        // Author display
        $uname = u_name();
        $authorReq = false;
        foreach (['author','poster','username'] as $c)
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$uname; $authorReq=true; }
        if (!$authorReq) {
          foreach (['author','poster','username'] as $c)
            if (has($pmap,$c)) { $cols[]=$c; $params[]=$uname; break; }
        }

        // Body/content — fill ALL required, else first available
        $bodyCols = ['body','content','message','post_text','text'];
        $bodyReq = false;
        foreach ($bodyCols as $c)
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$body; $bodyReq=true; }
        if (!$bodyReq) {
          foreach ($bodyCols as $c)
            if (has($pmap,$c)) { $cols[]=$c; $params[]=$body; break; }
        }

        // Optional metadata
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (has($pmap,'ip'))         { $cols[]='ip';         $params[]=$ip; }
        if (has($pmap,'ip_address')) { $cols[]='ip_address'; $params[]=$ip; }
        if (has($pmap,'user_agent')) { $cols[]='user_agent'; $params[]=(string)($_SERVER['HTTP_USER_AGENT'] ?? ''); }

        // Timestamps
        $nowCols = [];
        foreach (['created_at','post_time','posted_at','created'] as $ts) if (has($pmap,$ts)) $nowCols[]=$ts;

        $sqlCols = implode(',', $cols) . (empty($nowCols) ? '' : ',' . implode(',', $nowCols));
        $place   = rtrim(str_repeat('?,', count($params)), ',');
        $sqlVals = $place . (empty($nowCols) ? '' : ',' . implode(',', array_fill(0, count($nowCols), 'NOW()')));

        $ins = $pdo->prepare("INSERT INTO posts ($sqlCols) VALUES ($sqlVals)");
        $ins->execute($params);

        // touch thread last_post_at if exists
        $tmap = colmap($pdo,'threads');
        if (has($tmap,'last_post_at')) {
          $pdo->prepare("UPDATE threads SET last_post_at = NOW() WHERE id = ?")->execute([$thread_id]);
        }

        $pdo->commit();
        header('Location: /forum/thread.php?id=' . $thread_id);
        exit;
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = "Failed to post reply: " . $e->getMessage();
      }
    }
  }
}

forum_header('Reply');
?>
<style>
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
  .btn{display:inline-block;padding:8px 12px;border:1px solid #888;border-radius:8px;background:#f3f3f3;color:#111;text-decoration:none}
  .field{margin:8px 0}
  .editor-wrap{border:1px solid #ccc;border-radius:8px}
  .toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:8px;background:#f7f7f7;border-bottom:1px solid #ddd;border-radius:8px 8px 0 0}
  .toolbar button{padding:6px 10px;border:1px solid #bbb;background:#fff;border-radius:6px;cursor:pointer}
  .wys{min-height:220px;padding:10px;border:1px solid #ccc;border-radius:0 0 8px 8px;outline:none}
  .wys:empty:before{content:'Write your reply…'; color:#888}
</style>

<div class="card" style="max-width:840px;">
  <?php foreach ($errors as $e): ?>
    <div style="color:#a33;margin-bottom:8px;"><?= e($e) ?></div>
  <?php endforeach; ?>

  <form id="replyForm" method="post" autocomplete="off">
    <?php csrf_input(); ?>
    <div class="field">
      <label>Message</label>
      <div class="editor-wrap">
        <div class="toolbar" id="tb">
          <button type="button" data-cmd="bold"><b>B</b></button>
          <button type="button" data-cmd="italic"><i>I</i></button>
          <button type="button" data-cmd="underline"><u>U</u></button>
          <button type="button" data-cmd="insertUnorderedList">• List</button>
          <button type="button" data-cmd="insertOrderedList">1. List</button>
          <button type="button" id="mkLink">Link</button>
          <button type="button" id="mkCode">Code</button>
          <button type="button" id="mkTable">Table</button>
          <button type="button" id="mkBr">↵</button>
        </div>
        <div id="editor" class="wys" contenteditable="true"></div>
      </div>
      <!-- Hidden field sent to PHP -->
      <textarea name="body" id="bodyField" hidden></textarea>
    </div>
    <div class="field">
      <button class="btn" type="submit">Post Reply</button>
      <a class="btn" href="/forum/thread.php?id=<?= (int)$thread_id ?>">Cancel</a>
    </div>
  </form>
</div>

<script>
(function(){
  const ed = document.getElementById('editor');
  const bodyField = document.getElementById('bodyField');
  const form = document.getElementById('replyForm');

  // Toolbar actions
  document.getElementById('tb').addEventListener('click', function(e){
    const btn = e.target.closest('button');
    if (!btn) return;
    const cmd = btn.dataset.cmd;
    if (cmd) document.execCommand(cmd, false, null);
  });

  // Insert <br>
  document.getElementById('mkBr').addEventListener('click', () => {
    document.execCommand('insertLineBreak');
  });

  // Link (forces http/https)
  document.getElementById('mkLink').addEventListener('click', () => {
    let url = prompt('Enter URL (must start with http:// or https://):', 'https://');
    if (!url) return;
    if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
    document.execCommand('createLink', false, url);
    const sel = window.getSelection();
    if (sel && sel.anchorNode) {
      const a = (sel.anchorNode.parentElement && sel.anchorNode.parentElement.closest('a')) || null;
      if (a) { a.setAttribute('target','_blank'); a.setAttribute('rel','noopener noreferrer nofollow'); }
    }
  });

  // Inline code tag
  document.getElementById('mkCode').addEventListener('click', () => {
    document.execCommand('insertHTML', false, '<code></code>');
    const code = ed.querySelector('code:last-of-type');
    if (!code) return;
    const range = document.createRange();
    range.selectNodeContents(code);
    range.collapse(true);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  });

  // Simple 2x2 table
  document.getElementById('mkTable').addEventListener('click', () => {
    const html = '<table><thead><tr><th>H1</th><th>H2</th></tr></thead>'
               + '<tbody><tr><td>Cell</td><td>Cell</td></tr>'
               + '<tr><td>Cell</td><td>Cell</td></tr></tbody></table><p><br></p>';
    document.execCommand('insertHTML', false, html);
  });

  // Paste as plain text
  ed.addEventListener('paste', (e) => {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
    const html = text.replace(/\n/g, '<br>');
    document.execCommand('insertHTML', false, html);
  });

  // On submit, move HTML into hidden textarea
  form.addEventListener('submit', () => {
    bodyField.value = ed.innerHTML;
  });
})();
</script>

<?php forum_footer(); ?>