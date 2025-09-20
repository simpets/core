<?php
session_start();
require_once "includes/db.php";

if (!isset($_GET['id'])) {
    die("No pet selected.");
}

$root_id = (int) $_GET['id'];

// Fetch pet by ID or name
function getPet($pdo, $value, $byName = false) {
    if (!$value) return null;

    if ($byName) {
        $stmt = $pdo->prepare("SELECT id, pet_name, pet_image, mother, father FROM user_pets WHERE pet_name = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, pet_name, pet_image, mother, father FROM user_pets WHERE id = ?");
    }

    $stmt->execute([$value]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Build the tree recursively
function buildTree($pdo, $pet, $generation = 1, $maxGen = 3) {
    if (!$pet || $generation > $maxGen) return '';

    $html = "<div class='gen gen$generation'>";
    $html .= "<a href='public_pet_profile.php?id={$pet['id']}'>";
    $html .= "<img src='" . htmlspecialchars($pet['pet_image']) . "' width='150' height='150'><br>";
    $html .= htmlspecialchars($pet['pet_name']);
    $html .= "</a>";

    if ($generation < $maxGen) {
        $mother = getPet($pdo, $pet['mother'], true);
        $father = getPet($pdo, $pet['father'], true);

        $html .= "<div class='parents'>";
        $html .= "<div class='mother'>" . buildTree($pdo, $mother, $generation + 1, $maxGen) . "</div>";
        $html .= "<div class='father'>" . buildTree($pdo, $father, $generation + 1, $maxGen) . "</div>";
        $html .= "</div>";
    }

    $html .= "</div>";
    return $html;
}

$current_pet = getPet($pdo, $root_id);
if (!$current_pet) die("Pet not found.");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pedigree - <?= htmlspecialchars($current_pet['pet_name']) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .tree { display: flex; flex-direction: column; align-items: center; }
    .gen { text-align: center; margin: 20px; position: relative; }
    .parents { display: flex; justify-content: center; gap: 40px; margin-top: 20px; }
    .mother, .father { display: flex; flex-direction: column; align-items: center; }
    img { border: 2px solid #ccc; border-radius: 8px; }
  </style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="tree">
  <h1>Pedigree for <?= htmlspecialchars($current_pet['pet_name']) ?></h1>
  <?= buildTree($pdo, $current_pet, 1, 3) ?>
</div>
</body>
  <?php include 'footer.php'; ?>


</html>
