<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* 0) Fetch member display info + last_news_seen in one go */
$u = $pdo->prepare("SELECT username, avatar, last_news_seen FROM users WHERE id = ?");
$u->execute([$user_id]);
$userRow = $u->fetch(PDO::FETCH_ASSOC);
$username = $userRow['username'] ?? 'Member';
$avatar   = $userRow['avatar']   ?? 'images/default-avatar.png';
$last_news_seen = (int)($userRow['last_news_seen'] ?? 0);

/* 1) Fetch user's pets */
$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 2) Latest news */
$news_stmt = $pdo->query("SELECT id, title, message FROM news ORDER BY id DESC LIMIT 1");
$news = $news_stmt->fetch(PDO::FETCH_ASSOC);

/* 3) Show popup if there is newer news than the last seen id */
$show_news = ($news && (int)$news['id'] > $last_news_seen);

/* 4) Dismiss news */
if (isset($_POST['dismiss_news']) && $news) {
    $upd = $pdo->prepare("UPDATE users SET last_news_seen = ? WHERE id = ?");
    $upd->execute([(int)$news['id'], $user_id]);
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .page-wrap { max-width: 1100px; margin: 0 auto; padding: 20px; }

    /* Header: avatar + welcome line */
    .dash-header {
      display: flex; align-items: center; gap: 14px;
      margin: 10px 0 18px 0;
    }
    .dash-avatar {
      width: 64px; height: 64px; border-radius: 12px; object-fit: cover;
      border: 1px solid #ddd; background: #fff;
    }
    .dash-title {
      font-size: 1.8rem; margin: 0; line-height: 1.2;
    }
    .dash-subtle { color: #666; margin: 2px 0 0 0; }

    /* News popup (unchanged, just namespaced) */
    #news-popup {
      position: fixed; inset: 0; background: rgba(0,0,0,0.7);
      display: flex; align-items: center; justify-content: center; z-index: 9999;
    }
    #news-popup-content {
      background: #fff; padding: 32px; border-radius: 18px;
      max-width: 520px; box-shadow: 0 0 24px #333; text-align: center;
      animation: popin .3s;
    }
    #news-popup-content h2 { margin: 0 0 10px; font-size: 1.8rem; color: #563b9c; }
    #news-popup-content button {
      padding: 8px 22px; border-radius: 8px; border: none;
      background: #2ecc40; color: #fff; font-weight: 700; margin-top: 20px; cursor: pointer;
    }
    #news-popup-content button:hover { background: #179f2b; }
    @keyframes popin { from { transform: scale(.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    /* Pets grid */
    .pets-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 16px;
      margin-top: 10px;
    }
    .pet-card {
      border: 1px solid #ddd; border-radius: 12px; background: #fff;
      padding: 12px; text-align: center;
    }

    /* 200 x 200 canvas */
    .pet-canvas {
      position: relative;
      width: 200px; height: 200px;
      margin: 0 auto 8px auto;
      border: 1px solid #e5e7eb; border-radius: 10px;
      background: #fafafa;
      overflow: hidden;
    }
    .pet-canvas img { position: absolute; user-select: none; }

    /* Layers */
    .pet-bg   { top:0; left:0; width:100%; height:100%; z-index:0; object-fit: cover; }
    .pet-img  { z-index:1; width:80%; height:auto; top:10%; left:10%; transition: transform .2s; }
    .pet-glow {
      filter: drop-shadow(0 0 8px #fff) drop-shadow(0 0 16px #aef)
              drop-shadow(0 0 24px #99f) brightness(1.15);
    }
    .pet-rainbow-glow {
      filter: drop-shadow(0 0 6px #ff3232) drop-shadow(0 0 12px #ff9932)
              drop-shadow(0 0 18px #ffe132) drop-shadow(0 0 24px #49ff32)
              drop-shadow(0 0 30px #32fff3) drop-shadow(0 0 36px #3257ff)
              drop-shadow(0 0 42px #a832ff) drop-shadow(0 0 48px #ff32a8)
              brightness(1.13);
    }
    .pet-mini { width: 27% !important; top: 36.5%; left: 36.5%; }

    /* Toys + deco scaled for 200px canvas */
    .pet-toy { z-index:3; bottom: 8px; width: 48px; height: auto; }
    .pet-toy.slot1 { left: 10px; }
    .pet-toy.slot2 { left: 76px; }
    .pet-toy.slot3 { left: 142px; }
    .pet-deco { z-index:3; bottom: 90px; left: 100px; width: 52px; height: auto; transform: translateX(-50%); }

    .pet-name { font-weight: 700; margin: 4px 0 6px; }
    .muted { color:#666; }
    .release-note { color:#d9534f; font-weight:bold; }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>

<?php if ($show_news): ?>
  <div id="news-popup">
    <div id="news-popup-content">
      <h2><?= htmlspecialchars($news['title']) ?></h2>
      <p><?= nl2br(htmlspecialchars($news['message'])) ?></p>
      <form method="post">
        <button type="submit" name="dismiss_news">Okay!</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="page-wrap">
  <!-- Header -->
  <div class="dash-header">
    <img class="dash-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
    <div>
      <h1 class="dash-title">Welcome to your Dashboard, <?= htmlspecialchars($username) ?>!</h1>
      <p class="dash-subtle">Here are your pets and shortcuts.</p>
    </div>
  </div>

  <?php if (isset($_GET['released'], $_GET['name'])): ?>
    <p class="release-note">You have released <?= htmlspecialchars($_GET['name']) ?>.</p>
  <?php endif; ?>

  <!-- Pets -->
  <?php if (empty($pets)): ?>
    <p class="muted">You don't have any pets yet.</p>
  <?php else: ?>
    <div class="pets-grid">
      <?php foreach ($pets as $pet): ?>
        <div class="pet-card">
          <div class="pet-canvas">
            <?php
              $level = (int)($pet['level'] ?? 1);
              $type  = $pet['type'] ?? 'Unknown';
              $custom_image = $pet['pet_image'] ?? '';
              $bg    = $pet['background_url'] ?? '';
              $deco  = $pet['deco'] ?? '';
              $toy1  = $pet['toy1'] ?? '';
              $toy2  = $pet['toy2'] ?? '';
              $toy3  = $pet['toy3'] ?? '';

              if (!empty($bg)) {
                  echo "<img class='pet-bg' src='assets/backgrounds/".htmlspecialchars($bg, ENT_QUOTES)."' alt='Background'>";
              }

              if ($level < 3) {
                  $image_path = "images/levels/".htmlspecialchars($type, ENT_QUOTES)."_Egg.png";
              } elseif (!empty($custom_image)) {
                  $image_path = htmlspecialchars($custom_image, ENT_QUOTES);
              } else {
                  $image_path = "images/levels/".htmlspecialchars($type, ENT_QUOTES).".png";
              }

              $glowClass    = (!empty($pet['appearance_glow'])) ? 'pet-glow' : '';
              $rainbowClass = (!empty($pet['appearance_effect']) && $pet['appearance_effect'] === 'rainbow') ? 'pet-rainbow-glow' : '';
              $miniClass    = (!empty($pet['appearance_size']) && $pet['appearance_size'] === 'mini') ? 'pet-mini' : '';

              echo "<img class='pet-img {$glowClass} {$rainbowClass} {$miniClass}' src='{$image_path}' alt='Pet'>";
              if (!empty($toy1)) echo "<img class='pet-toy slot1' src='assets/toys/".htmlspecialchars($toy1, ENT_QUOTES)."' alt='Toy1'>";
              if (!empty($toy2)) echo "<img class='pet-toy slot2' src='assets/toys/".htmlspecialchars($toy2, ENT_QUOTES)."' alt='Toy2'>";
              if (!empty($toy3)) echo "<img class='pet-toy slot3' src='assets/toys/".htmlspecialchars($toy3, ENT_QUOTES)."' alt='Toy3'>";
              if (!empty($deco)) echo "<img class='pet-deco' src='assets/decorations/".htmlspecialchars($deco, ENT_QUOTES)."' alt='Decoration'>";
            ?>
          </div>
          <div class="pet-name"><?= htmlspecialchars($pet['pet_name'] ?? 'Unnamed', ENT_QUOTES) ?></div>
          <a href="pet_profile.php?id=<?= (int)$pet['id'] ?>">View Profile</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>