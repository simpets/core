<?php
session_start();
require_once "includes/db.php";

if (!isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$pet_id  = intval($_GET['id']);

// 1) Fetch the pet (including all necessary columns)
$stmt = $pdo->prepare("
    SELECT id, pet_name, type, pet_image, background_url,
           gender, level, boosts, sparring_wins, offspring,
           mother, father,
           base, marking1, marking2, marking3,
           price, description,
           toy1, toy2, toy3, deco,
           user_id, mate,
           appearance_glow, appearance_effect, appearance_size
      FROM user_pets
     WHERE id = ?
");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    die("Pet not found.");
}

// Fetch owner’s username
$ownerStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$ownerStmt->execute([$pet['user_id']]);
$ownerName = $ownerStmt->fetchColumn() ?: 'Unknown';

// Determine if the current user is the owner
$is_owner = isset($_SESSION['user_id']) && ($_SESSION['user_id'] === $pet['user_id']);

// Assign variables for convenience
$type  = $pet['type'];
$level = $pet['level'];

// Determine which image to use for the pet (custom or fallback)
if ($pet['level'] < 3) {
    $petImage = "images/levels/{$pet['type']}_Egg.png";
} elseif (!empty($pet['pet_image'])) {
    $petImage = $pet['pet_image'];
} else {
    $petImage = "images/levels/{$pet['type']}.png";
}

// Background (if any) - prefix assets/backgrounds/ if needed
if (!empty($pet['background_url'])) {
    if (strpos($pet['background_url'], '/') === false) {
        $background = "assets/backgrounds/" . $pet['background_url'];
    } else {
        $background = $pet['background_url'];
    }
} else {
    $background = null;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($pet['pet_name'], ENT_QUOTES) ?>’s Profile</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    /* Pet canvas and layering */
    .pet-canvas {
      position: relative;
      width: 400px;
      height: 400px;
      border: 1px solid #ccc;
      margin-bottom: 20px;
    }
    .pet-canvas img {
      position: absolute;
      user-select: none;
    }
    .pet-bg {
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }
    .pet-img {
      z-index: 1;
      width: 80%;
      height: auto;
      top: 10%;
      left: 10%;
      transition: all 0.25s;
    }
    /* GLOW EFFECT */
    .pet-glow {
      filter: drop-shadow(0 0 8px #fff) drop-shadow(0 0 16px #aef)
              drop-shadow(0 0 24px #99f) brightness(1.15);
    }
    /* RAINBOW GLOW EFFECT */
       .pet-rainbow-glow {
  /* Stronger, balanced rainbow effect with more visible color separation */
  filter:
    drop-shadow(0 0 6px #ff3232)
    drop-shadow(0 0 12px #ff9932)
    drop-shadow(0 0 18px #ffe132)
    drop-shadow(0 0 24px #49ff32)
    drop-shadow(0 0 30px #32fff3)
    drop-shadow(0 0 36px #3257ff)
    drop-shadow(0 0 42px #a832ff)
    drop-shadow(0 0 48px #ff32a8)
    brightness(1.13);
}
    
    /* MINI SIZE EFFECT */
    .pet-mini {
      width: 27% !important;
      top: 36.5%;
      left: 36.5%;
    }
    /* Toy positioning */
    .pet-toy {
      z-index: 2;
      bottom: 10px;
      width: 100px;
      height: auto;
    }
    .pet-toy.slot1 { left: 10px; }
    .pet-toy.slot2 { left: 160px; }
    .pet-toy.slot3 { left: 300px; }
    /* Decoration positioning */
    .pet-deco {
      z-index: 2;
      bottom: 110px;
      left: 160px;
      width: 80px;
      height: auto;
    }
    /* Pet details and buttons */
    .pet-details {
      margin-top: 20px;
      line-height: 1.5em;
    }
    .button-stack a {
      margin-right: 10px;
      text-decoration: none;
      padding: 6px 12px;
      background: #007bff;
      color: white;
      border-radius: 4px;
    }
    .button-stack a:hover {
      background: #0056b3;
    }
    /* List styling for removal */
    .remove-list {
      margin-top: 20px;
    }
    .remove-list li {
      margin-bottom: 8px;
    }
    .remove-list a {
      color: #d9534f;
      text-decoration: none;
      margin-left: 10px;
    }
    .remove-list a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1><?= htmlspecialchars($pet['pet_name'], ENT_QUOTES) ?>’s Profile</h1>

  <!-- Pet image & background with layered toys/deco -->
  <div class="pet-canvas">
    <?php if ($background): ?>
      <img src="<?= htmlspecialchars($background, ENT_QUOTES) ?>" class="pet-bg" alt="Pet Background">
    <?php endif; ?>
    <?php
      // Stack all pet image effects!
      $glowClass = (!empty($pet['appearance_glow']) && $pet['appearance_glow']) ? 'pet-glow' : '';
      $rainbowClass = (!empty($pet['appearance_effect']) && $pet['appearance_effect'] === 'rainbow') ? 'pet-rainbow-glow' : '';
      $miniClass = (!empty($pet['appearance_size']) && $pet['appearance_size'] === 'mini') ? 'pet-mini' : '';
      echo '<img src="' . htmlspecialchars($petImage, ENT_QUOTES) . '" class="pet-img ' . $glowClass . ' ' . $rainbowClass . ' ' . $miniClass . '" alt="Pet Image">';
    ?>
    <?php for ($i = 1; $i <= 3; $i++): ?>
      <?php $slotName = 'toy' . $i; ?>
      <?php if (!empty($pet[$slotName])): ?>
        <img src="assets/toys/<?= htmlspecialchars($pet[$slotName], ENT_QUOTES) ?>" alt="Toy <?= $i ?>" class="pet-toy slot<?= $i ?>">
      <?php endif; ?>
    <?php endfor; ?>
    <?php if (!empty($pet['deco'])): ?>
      <img src="assets/decorations/<?= htmlspecialchars($pet['deco'], ENT_QUOTES) ?>" alt="Decoration" class="pet-deco">
    <?php endif; ?>
  </div>

  <!-- Removal options for owner -->
  <?php if ($is_owner): ?>
    <div class="remove-list">
      <h3>Remove Equipped Items</h3>
      <ul>
        <?php
          // Toy removal items
          for ($i = 1; $i <= 3; $i++) {
            $slotName = 'toy' . $i;
            if (!empty($pet[$slotName])) {
              $itemStmt = $pdo->prepare("SELECT name FROM items WHERE image = ? LIMIT 1");
              $itemStmt->execute([$pet[$slotName]]);
              $itemName = $itemStmt->fetchColumn() ?: 'Unknown Toy';
        ?>
              <li>
                Toy: <?= htmlspecialchars($itemName, ENT_QUOTES) ?>
                <a href="remove_toy.php?pet_id=<?= $pet_id ?>&slot=<?= $slotName ?>"
                   onclick="return confirm('Remove <?= htmlspecialchars($itemName, ENT_QUOTES) ?>?');">
                   Remove
                </a>
              </li>
        <?php
            }
          }
          // Decoration removal
          if (!empty($pet['deco'])) {
            $itemStmt = $pdo->prepare("SELECT name FROM items WHERE image = ? LIMIT 1");
            $itemStmt->execute([$pet['deco']]);
            $itemName = $itemStmt->fetchColumn() ?: 'Unknown Decoration';
        ?>
            <li>
              Decoration: <?= htmlspecialchars($itemName, ENT_QUOTES) ?>
              <a href="remove_toy.php?pet_id=<?= $pet_id ?>&slot=deco"
                 onclick="return confirm('Remove <?= htmlspecialchars($itemName, ENT_QUOTES) ?>?');">
                 Remove
              </a>
            </li>
        <?php
          }
          if (empty($pet['toy1']) && empty($pet['toy2']) && empty($pet['toy3']) && empty($pet['deco'])) {
        ?>
          <li>No items equipped.</li>
        <?php } ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Owner-only action links -->
  
  
  
  
  
  <br> <br>
  
  
  
  
  
  
  <?php if ($is_owner): ?>
    <div class="button-stack">
        
        
        
        <?php if ($is_owner): ?>
  <a href="release_pet.php?id=<?= $pet_id ?>" style="background:#d9534f;" onclick="return confirm('Are you sure you want to release this pet? This is PERMANENT and cannot be undone!');">
    RELEASE PET
  </a>
<?php endif; ?>
  
  <br> <br>
        
        
        
        
        
      <a href="change_gender.php?pet_id=<?= $pet_id ?>">Change Gender</a>
      <a href="boost_pet.php?id=<?= $pet_id ?>">Boost Pet</a>
      <a href="rename_pet.php?id=<?= $pet_id ?>">Rename Pet</a>
      <a href="sell_pet.php?id=<?= $pet_id ?>">Sell Pet</a>
      <a href="breed.php?pet_id=<?= $pet_id ?>">Breed Pet</a>
      <a href="pedigree.php?id=<?= $pet_id ?>">Pedigree Pet</a>
      <a href="pedigree_5_generations.php?id=<?= $pet_id ?>">Extended Pedigree Pet</a><br><br>
      <a href="add_mate.php?id=<?= $pet['id'] ?>" class="button">Add A Mate</a>
    </div>
  <?php endif; ?>










  <!-- Pet details block -->
  <div class="pet-details">
    <p><strong>Owner:</strong> <?= htmlspecialchars($ownerName, ENT_QUOTES) ?></p>
    <strong>Type:</strong> <?= htmlspecialchars($type, ENT_QUOTES) ?><br>
    <strong>Gender:</strong> <?= htmlspecialchars($pet['gender'], ENT_QUOTES) ?><br>
    <strong>Level:</strong> <?= htmlspecialchars($level, ENT_QUOTES) ?><br>
    <strong>Mate:</strong> <?= htmlspecialchars($pet['mate'], ENT_QUOTES) ?><br>
    <strong>Boosts:</strong> <?= htmlspecialchars($pet['boosts'], ENT_QUOTES) ?><br>
    <strong>Sparring Wins:</strong> <?= htmlspecialchars($pet['sparring_wins'], ENT_QUOTES) ?><br>
    <strong>Offspring:</strong> <?= htmlspecialchars($pet['offspring'], ENT_QUOTES) ?><br>
    <?php if (empty($pet['mother']) && empty($pet['father'])): ?>
      <strong>Generation:</strong> Gen One<br>
    <?php else: ?>
      <strong>First Parent:</strong> <?= htmlspecialchars($pet['father'] ?: 'Unknown', ENT_QUOTES) ?><br>
      <strong>Second Parent:</strong> <?= htmlspecialchars($pet['mother'] ?: 'Unknown', ENT_QUOTES) ?><br>
    <?php endif; ?>
    <strong>Base:</strong> <?= htmlspecialchars($pet['base'], ENT_QUOTES) ?><br>
    <strong>Background:</strong> <?= htmlspecialchars($pet['background_url'], ENT_QUOTES) ?><br>
    <strong>Sale Status:</strong> <?= 
      $pet['price'] > 0 
        ? 'For Sale: ' . htmlspecialchars($pet['price'], ENT_QUOTES) . ' Canicash' 
        : 'Not for sale' 
    ?><br><br>
    <strong>Description:</strong><br>
    <?php if ($is_owner): ?>
      <form method="post" action="update_description.php">
        <textarea name="description" maxlength="500" rows="5" cols="40"><?= 
            htmlspecialchars($pet['description'], ENT_QUOTES) 
        ?></textarea><br>
        <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
        <input type="submit" value="Update Description">
      </form>
    <?php else: ?>
      <p><?= nl2br(htmlspecialchars($pet['description'], ENT_QUOTES)) ?></p>
    <?php endif; ?>
  </div>
</div>
</body>
<?php include 'footer.php'; ?>
</html>