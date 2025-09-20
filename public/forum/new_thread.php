<?php
// /forum/new_thread.php — robust + legacy columns + NO-CDN WYSIWYG
require_once __DIR__ . '/_common.php';
require_member();

/* ---------- tiny sanitizer (fallback) ---------- */
if (!function_exists('sanitize_html_basic')) {
  function sanitize_html_basic(string $html): string {
    $allowed = '<b><strong><i><em><u><p><br><ul><ol><li><a><blockquote><hr>'
             . '<code><pre><table><thead><tbody><tr><th><td><h1><h2><h3>';
    $html = strip_tags($html, $allowed);
    if (preg_match_all('#<a\s[^>]*href=("|\')(.*?)\1[^>]*>#i', $html, $m, PREG_SET_ORDER)) {
      foreach ($m as $a) {
        $url = $a[2];
        if (!preg_match('#^https?://#i', $url)) {
          $html = str_replace($a[0], '', $html);
        } else {
          $safe = preg_replace('#<a\s#i', '<a target="_blank" rel="noopener noreferrer" ', $a[0], 1);
          $html = str_replace($a[0], $safe, $html);
        }
      }
    }
    return $html;
  }
}

/* ---- Input & forum lookup ---- */
$forum_id = (int)($_GET['forum_id'] ?? 0);
if ($forum_id <= 0) { http_response_code(400); exit('Invalid forum'); }

$st = $pdo->prepare("SELECT id, name, is_container, admin_only FROM forums WHERE id = ?");
$st->execute([$forum_id]);
$forum = $st->fetch(PDO::FETCH_ASSOC);
if (!$forum) { http_response_code(404); exit('Forum not found'); }

require_forum_access($forum);
if (!empty($forum['is_container'])) { http_response_code(400); exit('Cannot post in a category. Choose a subforum.'); }

/* ---- schema helpers ---- */
function colmap(PDO $pdo, string $table): array {
  $out = [];
  $q = $pdo->query("SHOW COLUMNS FROM {$table}");
  if ($q) foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $out[strtolower($r['Field'])] = $r;
  return $out;
}
function has(array $m, string $c): bool { return isset($m[strtolower($c)]); }
function required(array $m, string $c): bool {
  $c = strtolower($c);
  if (!isset($m[$c])) return false;
  return (strtoupper((string)$m[$c]['Null']) === 'NO') && ($m[$c]['Default'] === null);
}
function u_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function u_name(): string { return (string)($_SESSION['username'] ?? ''); }

/* ---- form handling ---- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $errors[] = "Security token invalid. Please reload.";
  } else {
    $title = trim((string)($_POST['title'] ?? ''));
    // Hidden textarea receives HTML from the contenteditable editor:
    $body  = trim((string)($_POST['body'] ?? ''));

    if ($body !== '') $body = sanitize_html_basic($body);

    if ($title === '' || $body === '') $errors[] = "Title and message are required.";
    if (mb_strlen($title) > 200) $errors[] = "Title is too long (max 200).";

    if (!$errors) {
      try {
        $pdo->beginTransaction();

        /* Insert thread (minimal) */
        $tcols = colmap($pdo,'threads');
        $thCols   = ['forum_id','user_id','title'];
        $thParams = [$forum_id, u_id(), $title];

        $sql = "INSERT INTO threads (" . implode(',', $thCols);
        if (has($tcols,'created_at'))   $sql .= ",created_at";
        if (has($tcols,'last_post_at')) $sql .= ",last_post_at";
        $sql .= ") VALUES (" . rtrim(str_repeat('?,', count($thParams)), ',');
        if (has($tcols,'created_at'))   $sql .= ",NOW()";
        if (has($tcols,'last_post_at')) $sql .= ",NOW()";
        $sql .= ")";

        $insT = $pdo->prepare($sql);
        $insT->execute($thParams);
        $thread_id = (int)$pdo->lastInsertId();
        if ($thread_id <= 0) throw new RuntimeException("No thread id returned");

        /* Insert first post — fill ALL required legacy cols */
        $pmap   = colmap($pdo,'posts');
        $cols   = [];
        $params = [];

        if (has($pmap,'thread_id')) { $cols[]='thread_id'; $params[]=$thread_id; }
        if (has($pmap,'topic_id'))  { $cols[]='topic_id';  $params[]=$thread_id; }

        $uidValue = u_id();
        foreach (['user_id','author_id','poster_id'] as $c) {
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$uidValue; }
        }
        if (!array_intersect(['user_id','author_id','poster_id'], array_map('strtolower',$cols))) {
          foreach (['user_id','author_id','poster_id'] as $c) {
            if (has($pmap,$c)) { $cols[]=$c; $params[]=$uidValue; break; }
          }
        }

        $uname = u_name();
        $authorSet = false;
        foreach (['author','poster','username'] as $c) {
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$uname; $authorSet = true; }
        }
        if (!$authorSet) {
          foreach (['author','poster','username'] as $c) {
            if (has($pmap,$c) && !in_array($c, $cols, true)) { $cols[]=$c; $params[]=$uname; break; }
          }
        }

        $subjSet = false;
        foreach (['subject','post_title'] as $c) {
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$title; $subjSet = true; }
        }
        if (!$subjSet) {
          foreach (['subject','post_title'] as $c) {
            if (has($pmap,$c) && !in_array($c, $cols, true)) { $cols[]=$c; $params[]=$title; break; }
          }
        }

        $bodyCols = ['body','content','message','post_text','text'];
        $anyRequiredBody = false;
        foreach ($bodyCols as $c) {
          if (has($pmap,$c) && required($pmap,$c)) { $cols[]=$c; $params[]=$body; $anyRequiredBody = true; }
        }
        if (!$anyRequiredBody) {
          foreach ($bodyCols as $c) {
            if (has($pmap,$c) && !in_array($c, $cols, true)) { $cols[]=$c; $params[]=$body; break; }
          }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (has($pmap,'ip'))         { $cols[]='ip';         $params[]=$ip; }
        if (has($pmap,'ip_address')) { $cols[]='ip_address'; $params[]=$ip; }
        if (has($pmap,'user_agent')) { $cols[]='user_agent'; $params[]=(string)($_SERVER['HTTP_USER_AGENT'] ?? ''); }

        $nowCols = [];
        foreach (['created_at','post_time','posted_at','created'] as $ts) if (has($pmap,$ts)) $nowCols[]=$ts;

        $sqlCols = implode(',', $cols) . (empty($nowCols) ? '' : ',' . implode(',', $nowCols));
        $place   = rtrim(str_repeat('?,', count($params)), ',');
        $sqlVals = $place . (empty($nowCols) ? '' : ',' . implode(',', array_fill(0, count($nowCols), 'NOW()')));

        $insP = $pdo->prepare("INSERT INTO posts ($sqlCols) VALUES ($sqlVals)");
        $insP->execute($params);

        if (has($tcols,'last_post_at')) {
          $pdo->prepare("UPDATE threads SET last_post_at = NOW() WHERE id = ?")->execute([$thread_id]);
        }

        $pdo->commit();
        header('Location: /forum/thread.php?id=' . $thread_id);
        exit;
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = "Failed to create thread: " . $e->getMessage();
      }
    }
  }
}

forum_header('New Thread in ' . $forum['name']);
?>
<style>
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
  .btn{display:inline-block;padding:8px 12px;border:1px solid #888;border-radius:8px;background:#f3f3f3;color:#111;text-decoration:none}
  .muted{color:#666}
  .field{margin:8px 0}
  input[type=text]{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
  .editor-wrap{border:1px solid #ccc;border-radius:8px}
  .toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:8px;background:#f7f7f7;border-bottom:1px solid #ddd;border-radius:8px 8px 0 0}
  .toolbar button{padding:6px 10px;border:1px solid #bbb;background:#fff;border-radius:6px;cursor:pointer}
  .wys{min-height:220px;padding:10px;outline:none}
  .wys:empty:before{content:'Write your message…'; color:#888}
</style>

<div class="card" style="max-width:840px;">
  <?php foreach ($errors as $e): ?>
    <div style="color:#a33;margin-bottom:8px;"><?= e($e) ?></div>
  <?php endforeach; ?>

  <form id="threadForm" method="post" autocomplete="off">
    <?php csrf_input(); ?>
    <div class="field">
      <label>Title</label>
      <input type="text" name="title" maxlength="200" required>
    </div>

    <div class="field">
      <label>Message</label>

      <!-- Toolbar + contenteditable editor -->
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
      <div class="muted">Tip: Use the buttons for formatting. Links must be http(s)://</div>
    </div>

    <div class="field">
      <button class="btn" type="submit">Post Thread</button>
      <a class="btn" href="/forum/forum.php?id=<?= (int)$forum_id ?>">Cancel</a>
    </div>
  </form>
</div>

<script>
(function(){
  const ed = document.getElementById('editor');
  const bodyField = document.getElementById('bodyField');
  const form = document.getElementById('threadForm');

  // Basic toolbar actions with execCommand (widely supported)
  document.getElementById('tb').addEventListener('click', function(e){
    const btn = e.target.closest('button');
    if (!btn) return;
    const cmd = btn.dataset.cmd;
    if (cmd) {
      document.execCommand(cmd, false, null);
    }
  });

  // Insert <br>
  document.getElementById('mkBr').addEventListener('click', () => {
    document.execCommand('insertLineBreak');
  });

  // Make link (forces https:// if missing)
  document.getElementById('mkLink').addEventListener('click', () => {
    let url = prompt('Enter URL (must start with http:// or https://):', 'https://');
    if (!url) return;
    if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
    document.execCommand('createLink', false, url);
    // add rel/target on created <a>
    const sel = window.getSelection();
    if (sel && sel.anchorNode) {
      const a = (sel.anchorNode.parentElement && sel.anchorNode.parentElement.closest('a')) || null;
      if (a) { a.setAttribute('target','_blank'); a.setAttribute('rel','noopener noreferrer'); }
    }
  });

  // Inline code wrapper
  document.getElementById('mkCode').addEventListener('click', () => {
    document.execCommand('insertHTML', false, '<code></code>');
    // place caret inside the <code>
    const code = ed.querySelector('code:last-of-type');
    if (!code) return;
    const range = document.createRange();
    range.selectNodeContents(code);
    range.collapse(true);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  });

  // Insert a simple 2x2 table (allowed by sanitizer)
  document.getElementById('mkTable').addEventListener('click', () => {
    const html = '<table><thead><tr><th>H1</th><th>H2</th></tr></thead>'
               + '<tbody><tr><td>Cell</td><td>Cell</td></tr>'
               + '<tr><td>Cell</td><td>Cell</td></tr></tbody></table><p><br></p>';
    document.execCommand('insertHTML', false, html);
  });

  // Paste as plain text to avoid weird styles; line breaks preserved as <br>
  ed.addEventListener('paste', (e) => {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
    const html = text.replace(/\n/g, '<br>');
    document.execCommand('insertHTML', false, html);
  });

  // On submit, move HTML into hidden textarea
  form.addEventListener('submit', () => {
    // Normalize <div> newlines to <p> blocks for consistency
    // (optional; server still sanitizes)
    bodyField.value = ed.innerHTML;
  });
})();
</script>

<?php forum_footer(); ?>