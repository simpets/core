<?php
// /forum/edit_post.php — edit a post (owner/admin) with NO-CDN WYSIWYG, image URLs, and column-aware update
require_once __DIR__ . '/_common.php';
require_member();

/* ---------- tiny sanitizer (safe subset + IMG) ---------- */
if (!function_exists('sanitize_html_basic')) {
  function sanitize_html_basic(string $html): string {
    // allow a safe subset of tags/attrs (includes <img>)
    $allowed = '<b><strong><i><em><u><p><br><ul><ol><li><a><blockquote><hr><code><pre><table><thead><tbody><tr><th><td><img>';
    $html = strip_tags($html, $allowed);

    // keep only http(s) links; force rel/target; strip JS handlers
    if (preg_match_all('#<a\s[^>]*href=("|\')(.*?)\1[^>]*>#i', $html, $m, PREG_SET_ORDER)) {
      foreach ($m as $a) {
        $full = $a[0]; $url = $a[2];
        if (!preg_match('#^https?://#i', $url)) {
          $html = str_replace($full, '', $html);
        } else {
          $safe = preg_replace('/\s(on\w+)=("|\')[^"\']*\2/i', '', $full);
          if (!preg_match('/\brel=/i',   $safe)) $safe = preg_replace('#<a\s#i', '<a rel="nofollow noopener" ', $safe, 1);
          if (!preg_match('/\btarget=/i',$safe)) $safe = preg_replace('#<a\s#i', '<a target="_blank" ', $safe, 1);
          $html = str_replace($full, $safe, $html);
        }
      }
    }

    // validate <img> src; remove JS/style; rebuild with whitelisted attrs
    if (preg_match_all('#<img\s[^>]*src=("|\')(.*?)\1[^>]*>#i', $html, $m, PREG_SET_ORDER)) {
      foreach ($m as $img) {
        $full = $img[0]; $src = $img[2];
        if (!preg_match('#^https?://#i', $src)) {
          $html = str_replace($full, '', $html);
        } else {
          $safe = preg_replace('/\s(on\w+|style)=("|\')[^"\']*\2/i', '', $full);
          // pull alt/title/width/height
          $alt=''; $title=''; $width=''; $height='';
          if (preg_match('/\balt=("|\')(.*?)\1/i',   $full, $m2)) $alt = htmlspecialchars($m2[2], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
          if (preg_match('/\btitle=("|\')(.*?)\1/i', $full, $m2)) $title = htmlspecialchars($m2[2], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
          if (preg_match('/\bwidth=("|\')(\d{1,4})\1/i',  $full, $m2)) $width = (string)intval($m2[2], 10);
          if (preg_match('/\bheight=("|\')(\d{1,4})\1/i', $full, $m2)) $height = (string)intval($m2[2], 10);
          $attrs = ' src="'.htmlspecialchars($src, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8').'" loading="lazy"';
          if ($alt    !== '') $attrs .= ' alt="'.$alt.'"';
          if ($title  !== '') $attrs .= ' title="'.$title.'"';
          if ($width  !== '') $attrs .= ' width="'.$width.'"';
          if ($height !== '') $attrs .= ' height="'.$height.'"';
          $html = str_replace($full, '<img'.$attrs.'>', $html);
        }
      }
    }

    // collapse empty paragraphs
    $html = preg_replace('#(<p>\s*</p>)+#i', '<p><br></p>', $html);
    return $html;
  }
}

/* ---------- schema helpers ---------- */
function table_cols(PDO $pdo, string $table): array {
  $cols = [];
  $q = $pdo->query("SHOW COLUMNS FROM {$table}");
  if ($q) foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) $cols[strtolower($r['Field'])] = true;
  return $cols;
}
function has_col(array $cols, string $name): bool { return isset($cols[strtolower($name)]); }
function user_is_owner(int $ownerId): bool { return (int)($_SESSION['user_id'] ?? 0) === $ownerId; }

/* ---------- load post ---------- */
$post_id = (int)($_GET['id'] ?? 0);
if ($post_id <= 0) { http_response_code(400); exit('Invalid post id'); }

$pcols = table_cols($pdo,'posts');

$bodyCandidates   = ['body','content','message','post_text','text'];
$authorIdCols     = ['user_id','author_id','poster_id'];

/* Select ALL possible body columns so we can detect which one actually holds this post */
$select = ["p.id"];
if (has_col($pcols,'thread_id')) { $select[] = "p.thread_id"; }
elseif (has_col($pcols,'topic_id')) { $select[] = "p.topic_id AS thread_id"; }
foreach ($authorIdCols as $c) if (has_col($pcols,$c)) $select[] = "p.$c AS user_id";

$bodySelectMap = []; // alias => real column
foreach ($bodyCandidates as $c) {
  if (has_col($pcols,$c)) {
    $alias = "body__" . $c;
    $bodySelectMap[$alias] = $c;
    $select[] = "p.$c AS $alias";
  }
}

$selectList = implode(', ', $select);
$ps = $pdo->prepare("SELECT $selectList FROM posts p WHERE p.id = ?");
$ps->execute([$post_id]);
$post = $ps->fetch(PDO::FETCH_ASSOC);
if (!$post) { http_response_code(404); exit('Post not found'); }
$thread_id = (int)$post['thread_id'];
$ownerId   = (int)($post['user_id'] ?? 0);

/* Detect which body column is used */
$currentHtml = '';
$bodyColUsed = null;
foreach ($bodySelectMap as $alias => $real) {
  if (isset($post[$alias]) && $post[$alias] !== null && $post[$alias] !== '') {
    $currentHtml = (string)$post[$alias];
    $bodyColUsed = $real;
    break;
  }
}
if (!$bodyColUsed) {
  foreach ($bodyCandidates as $c) {
    if (has_col($pcols,$c)) { $bodyColUsed = $c; break; }
  }
}
if (!$bodyColUsed) { http_response_code(500); exit('No editable text column found in posts table.'); }

/* ---------- permissions via thread/forum ---------- */
$st = $pdo->prepare("SELECT t.id, t.forum_id, t.is_locked, f.admin_only FROM threads t JOIN forums f ON f.id=t.forum_id WHERE t.id=?");
$st->execute([$thread_id]);
$thread = $st->fetch(PDO::FETCH_ASSOC);
if (!$thread) { http_response_code(404); exit('Thread not found'); }

require_forum_access($thread);
if (!is_admin() && !user_is_owner($ownerId)) { http_response_code(403); exit('You cannot edit this post.'); }
if (!empty($thread['is_locked']) && !is_admin()) { http_response_code(403); exit('Thread is locked.'); }

/* ---------- handle submit ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $errors[] = "Security token invalid. Please reload.";
  } else {
    $raw  = (string)($_POST['body'] ?? '');
    $html = sanitize_html_basic($raw);
    if (trim(strip_tags($html)) === '') $errors[] = "Message cannot be empty.";

    if (!$errors) {
      try {
        $sql = "UPDATE posts SET {$bodyColUsed} = ?";
        if (has_col($pcols,'updated_at')) $sql .= ", updated_at = NOW()";
        $sql .= " WHERE id = ?";

        $upd = $pdo->prepare($sql);
        $upd->execute([$html, $post_id]);

        header('Location: /forum/thread.php?id='.(int)$thread_id);
        exit;
      } catch (Throwable $e) {
        $errors[] = "Failed to edit post: " . $e->getMessage();
      }
    }
  }
}

/* Prefill editor with sanitized HTML (avoid echoing raw DB HTML) */
$prefill = sanitize_html_basic($currentHtml);

forum_header('Edit Post');
?>
<style>
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
  .btn{display:inline-block;padding:8px 12px;border:1px solid #888;border-radius:8px;background:#f3f3f3;color:#111;text-decoration:none}
  .field{margin:8px 0}
  .editor-wrap{border:1px solid #ccc;border-radius:8px}
  .toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:8px;background:#f7f7f7;border-bottom:1px solid #ddd;border-radius:8px 8px 0 0}
  .toolbar button{padding:6px 10px;border:1px solid #bbb;background:#fff;border-radius:6px;cursor:pointer}
  .wys{min-height:320px;padding:10px;border:1px solid #ccc;border-radius:0 0 8px 8px;outline:none}
  .wys:empty:before{content:'Edit your message…'; color:#888}
  input[type=text]{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
</style>

<div class="card" style="max-width:940px;">
  <?php foreach ($errors as $e): ?>
    <div style="color:#a33;margin-bottom:8px;"><?= e($e) ?></div>
  <?php endforeach; ?>

  <form id="editForm" method="post" autocomplete="off">
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
          <button type="button" id="mkImg">Image URL</button>
          <button type="button" id="mkBr">↵</button>
        </div>
        <div id="editor" class="wys" contenteditable="true"><?php echo $prefill; ?></div>
      </div>
      <!-- Hidden field sent to PHP -->
      <textarea name="body" id="bodyField" hidden></textarea>
    </div>
    <div class="field">
      <button class="btn" type="submit">Save</button>
      <a class="btn" href="/forum/thread.php?id=<?= (int)$thread_id ?>">Cancel</a>
    </div>
  </form>
</div>

<script>
(function(){
  const ed = document.getElementById('editor');
  const bodyField = document.getElementById('bodyField');
  const form = document.getElementById('editForm');

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

  // Insert image by URL (http/https), optional alt/size
  document.getElementById('mkImg').addEventListener('click', () => {
    let url = prompt('Image URL (must start with http:// or https://):', 'https://');
    if (!url) return;
    if (!/^https?:\/\//i.test(url)) { alert('URL must start with http:// or https://'); return; }
    const alt = prompt('Alt text (optional):', '') || '';
    const w = prompt('Width (px, optional):', '') || '';
    const h = prompt('Height (px, optional):', '') || '';
    const attrs = [
      'src="'+escapeHtml(url)+'"',
      'loading="lazy"'
    ];
    if (alt) attrs.push('alt="'+escapeHtml(alt)+'"');
    if (/^\d{1,4}$/.test(w)) attrs.push('width="'+w+'"');
    if (/^\d{1,4}$/.test(h)) attrs.push('height="'+h+'"');
    document.execCommand('insertHTML', false, '<img '+attrs.join(' ')+' />');
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

  function escapeHtml(s){
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>

<?php forum_footer(); ?>