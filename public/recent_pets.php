<?php
// /recent_pets.php — List the most recent pets (Level 3+)
session_start();
require_once "includes/db.php";

// Fetch latest level 3+ pets by ID
$stmt = $pdo->prepare("
    SELECT up.id, up.pet_name, up.pet_image, up.type, up.level, u.username
    FROM user_pets up
    JOIN users u ON up.user_id = u.id
    WHERE up.level >= 3
    ORDER BY up.id DESC
    LIMIT 20
");
$stmt->execute();
$pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Recent Pets</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .pets-grid { display:flex; flex-wrap:wrap; gap:20px; }
    .pet-card { border:1px solid #ccc; padding:12px; border-radius:10px; text-align:center; background:#fff; width:180px; }
    .pet-card img { max-width:150px; max-height:150px; display:block; margin:0 auto 8px; }
    .muted { color:#666; font-size:0.9em; }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Recent Pets (Level 3+)</h1>

  <?php if (empty($pets)): ?>
    <p class="muted">No pets yet.</p>
  <?php else: ?>
    <div class="pets-grid">
      <?php foreach ($pets as $pet): ?>
        <div class="pet-card">
          <img src="<?= htmlspecialchars($pet['pet_image']) ?>" alt="Pet">
          <div><strong><?= htmlspecialchars($pet['pet_name']) ?></strong></div>
          <div class="muted">Type: <?= htmlspecialchars($pet['type']) ?></div>
          <div class="muted">Level: <?= (int)$pet['level'] ?></div>
          <div class="muted">Owner: <a href="profile.php?user=<?= urlencode($pet['username']) ?>"><?= htmlspecialchars($pet['username']) ?></a></div>
          <div><a href="public_pet_profile.php?id=<?= (int)$pet['id'] ?>">View Profile</a></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>