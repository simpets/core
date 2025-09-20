<?php
// /forum/_common.php
session_start();
require_once __DIR__ . '/../includes/db.php';

function current_user_id() { return $_SESSION['user_id'] ?? null; }
function current_username(){ return $_SESSION['username'] ?? null; }
function is_admin() {
  $g = strtolower($_SESSION['usergroup'] ?? '');
  return !empty($_SESSION['user_id']) && in_array($g, ['admin','admins'], true);
}
function require_member() {
  if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
  }
}

/* ---- CSRF ---- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_input(){ echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8').'">'; }
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

/* ---- Escaping + mini formatting (legacy BBCode helper) ---- */
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
function format_post($text){
  $t = trim((string)$text);
  $t = e($t);
  $patterns = [
    '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
    '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
    '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
    '/\bhttps?:\/\/[^\s<]+/i' => '<a href="$0" target="_blank" rel="nofollow ugc noopener">$0</a>',
  ];
  foreach ($patterns as $re => $rep) $t = preg_replace($re, $rep, $t);
  return nl2br($t);
}

/* ---- Shared sanitizer for WYSIWYG HTML ---- */
if (!function_exists('sanitize_html_basic')) {
  function sanitize_html_basic(string $html): string {
    // Allow a safe subset (headings/tables/links/code; image allowed)
    $allowed = '<b><strong><i><em><u><p><br><ul><ol><li><a><blockquote><hr>'
             . '<code><pre><table><thead><tbody><tr><th><td><h1><h2><h3><img>';
    $html = strip_tags($html, $allowed);

    // A) Links: http(s) only; strip JS/style; enforce target+rel
    $html = preg_replace_callback('#<a\s[^>]*href=("|\')(.*?)\1[^>]*>#i', function($m){
      $full = $m[0]; $url = $m[2];
      if (!preg_match('#^https?://#i', $url)) return ''; // drop non-http(s)
      $safe = preg_replace('/\s(on\w+|style)=("|\')[^"\']*\2/i', '', $full);
      if (!preg_match('/\btarget=/i',$safe)) $safe = preg_replace('#<a\s#i', '<a target="_blank" ', $safe, 1);
      if (!preg_match('/\brel=/i',   $safe)) $safe = preg_replace('#<a\s#i', '<a rel="noopener noreferrer nofollow" ', $safe, 1);
      return $safe;
    }, $html);

    // B) Images: http(s) only; rebuild with whitelisted attrs
    $html = preg_replace_callback('#<img\s[^>]*src=("|\')(.*?)\1[^>]*>#i', function($m){
      $src = $m[2];
      if (!preg_match('#^https?://#i', $src)) return '';
      $attr = ' src="'.htmlspecialchars($src, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8').'" loading="lazy"';
      $alt=''; $title=''; $w=''; $h='';
      if (preg_match('/\balt=("|\')(.*?)\1/i',   $m[0], $mm)) $alt = htmlspecialchars($mm[2], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
      if (preg_match('/\btitle=("|\')(.*?)\1/i', $m[0], $mm)) $title = htmlspecialchars($mm[2], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
      if (preg_match('/\bwidth=("|\')(\d{1,4})\1/i',  $m[0], $mm)) $w = (string)intval($mm[2],10);
      if (preg_match('/\bheight=("|\')(\d{1,4})\1/i', $m[0], $mm)) $h = (string)intval($mm[2],10);
      if ($alt   !== '') $attr .= ' alt="'.$alt.'"';
      if ($title !== '') $attr .= ' title="'.$title.'"';
      if ($w     !== '') $attr .= ' width="'.$w.'"';
      if ($h     !== '') $attr .= ' height="'.$h.'"';
      return '<img'.$attr.'>';
    }, $html);

    // Collapse empty paragraphs
    $html = preg_replace('#(<p>\s*</p>)+#i', '<p><br></p>', $html);
    return $html;
  }
}

/* ---- User display helper ---- */
function user_display($pdo, $id){
  $st = $pdo->prepare("SELECT username, nickname FROM users WHERE id = ?");
  $st->execute([$id]);
  $u = $st->fetch(PDO::FETCH_ASSOC);
  if (!$u) return "User#".$id;
  return $u['nickname'] ? $u['nickname'].' ('.$u['username'].')' : $u['username'];
}

/* ---- Layout ---- */
function forum_header($title='Forum'){
  include __DIR__ . '/../menu.php';
  echo '<div class="container" style="max-width:1000px;margin:24px auto;">';
  echo '<h1>'.e($title).'</h1>';
}
function forum_footer(){
  echo '</div>';
  include __DIR__ . '/../footer.php';
}