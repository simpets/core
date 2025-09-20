<?php
// /forum/_common.php — self-contained common includes for the forum
session_start();
require_once __DIR__ . '/../includes/db.php'; // forum uses site DB, but not menu.php

function current_user_id() { return $_SESSION['user_id'] ?? null; }
function current_username(){ return $_SESSION['username'] ?? null; }
function is_admin() {
  $g = strtolower($_SESSION['usergroup'] ?? '');
  return !empty($_SESSION['user_id']) && in_array($g, ['admin','admins']);
}
function require_member() {
  if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
  }
}

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_input(){ echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; }
function csrf_ok($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }

// Escaping + lite formatting for posts
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function format_post($text){
  $t = e((string)$text);
  $t = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $t);
  $t = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $t);
  $t = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $t);
  $t = preg_replace('/\bhttps?:\/\/[^\s<]+/i', '<a href="$0" target="_blank" rel="nofollow ugc noopener">$0</a>', $t);
  return nl2br($t);
}

function user_display(PDO $pdo, int $id){
  $st = $pdo->prepare("SELECT username, nickname FROM users WHERE id = ?");
  $st->execute([$id]);
  $u = $st->fetch();
  if (!$u) return "User#".$id;
  return $u['nickname'] ? $u['nickname'].' ('.$u['username'].')' : $u['username'];
}



function can_view_forum(array $f): bool {
  // admin_only forums visible to admins only
  if (!empty($f['admin_only'])) {
    $g = strtolower($_SESSION['usergroup'] ?? '');
    return !empty($_SESSION['user_id']) && in_array($g, ['admin','admins']);
  }
  // all members can see others
  return !empty($_SESSION['user_id']);
}

function require_forum_access(array $f) {
  if (!can_view_forum($f)) {
    http_response_code(403);
    exit('You do not have access to this forum.');
  }
}





/* Self-contained header/footer (no menu.php) */
function forum_header($title='Forum'){
  ?>
  <!doctype html>
  <html>
  <head>
    <meta charset="utf-8">
    <title><?= e($title) ?> — The Simpets Forum</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/assets/styles.css"><!-- optional -->
    <style>
      body{margin:0;background:#fafafa;color:#222;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
      .topbar{display:flex;align-items:center;gap:14px;padding:12px 16px;background:#1f2937;color:#fff}
      .topbar a{color:#fff;text-decoration:none}
      .brand{font-weight:700}
      .container{max-width:1000px;margin:24px auto;padding:0 16px}
      .button{display:inline-block;padding:8px 12px;border:1px solid #888;border-radius:8px;background:#f3f3f3;color:#111;text-decoration:none}
      .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;margin:16px 0}
    </style>
  </head>
  <body>
      
      
    <div class="topbar">
      <div class="brand"><a href="/forum/index.php">The Simpets Forums</a></div>
      <div style="flex:1"></div>
      <a href="/">← Back to site</a>
      <?php if (!empty($_SESSION['username'])): ?>
        <span style="opacity:.85">Hi, <?= e($_SESSION['username']) ?></span>
        <a class="button" href="/logout.php">Logout</a>
      <?php else: ?>
        <a class="button" href="/login.php">Login</a>
      <?php endif; ?>
    </div>
    <div class="container">
      <h1><?= e($title) ?></h1>
  <?php
}

function forum_footer(){
  ?>
    </div><!-- .container -->
  </body>
  </html>
  <?php
}