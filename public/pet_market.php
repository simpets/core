<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_id'])) {
    $buy_id = $_POST['buy_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT id, user_id, pet_name, price FROM user_pets WHERE id = ? AND price IS NOT NULL");
    $stmt->execute([$buy_id]);
    $pet = $stmt->fetch();

    if ($pet && $pet['user_id'] != $user_id) {
        $stmt = $pdo->prepare("SELECT simbucks FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $buyerFunds = $stmt->fetchColumn();

        if ($buyerFunds >= $pet['price']) {
            $pdo->prepare("UPDATE users SET simbucks = simbucks - ? WHERE id = ?")->execute([$pet['price'], $user_id]);
            $pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?")->execute([$pet['price'], $pet['user_id']]);
            $pdo->prepare("UPDATE user_pets SET user_id = ?, price = NULL WHERE id = ?")->execute([$user_id, $buy_id]);

            header("Location: dashboard.php?bought=1");
            exit;
        } else {
            $error = "Not enough Simbucks!";
        }
    } else {
        $error = "Invalid pet or already sold.";
    }
}

// Fetch pets for sale with parent IDs and their names
$pets = $pdo->query("SELECT p.id, p.pet_name, p.pet_image, p.price, p.type, p.gender, p.level, p.boosts, p.offspring,
    p.mother, p.father,
    m.pet_name AS mother_name,
    f.pet_name AS father_name,
    u.username AS seller
    FROM user_pets p
    LEFT JOIN user_pets m ON p.mother = m.id
    LEFT JOIN user_pets f ON p.father = f.id
    JOIN users u ON p.user_id = u.id
    WHERE p.price IS NOT NULL")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pet Market</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Pet Market</h1>

  <?php if (!empty($error)): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if ($pets): ?>
    <div style="display:flex; flex-wrap:wrap; gap:20px;">
    <?php foreach ($pets as $pet): ?>
      <div style="border:1px solid #ccc; padding:10px; text-align:center; width:220px;">
        <img src="<?= htmlspecialchars($pet['pet_image']) ?>" width="150"><br>
        <strong><?= htmlspecialchars($pet['pet_name']) ?></strong><br>

        <?php if (empty($pet['mother']) && empty($pet['father'])): ?>
          Generation: Gen One<br>
        <?php else: ?>
          Generation: Standard<br>
          
        <?php endif; ?>

        Type: <?= htmlspecialchars($pet['type']) ?><br>
        Gender: <?= htmlspecialchars($pet['gender']) ?><br>
        
        Father: <?= $pet['father'] ?><br>
        Mother: <?= $pet['mother'] ?><br>
        Level: <?= $pet['level'] ?><br>
        Boosts: <?= $pet['boosts'] ?><br>
        Offspring: <?= $pet['offspring'] ?><br>
        Seller: <?= htmlspecialchars($pet['seller']) ?><br><br>

        <strong>Price: <?= $pet['price'] ?> Simbucks</strong>
        <form method="post">
          <input type="hidden" name="buy_id" value="<?= $pet['id'] ?>"><br>
          <input type="submit" value="Buy Pet">
        </form>
      </div>
    <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No pets are currently for sale.</p>
  <?php endif; ?>
</div>
</body>
  <?php include 'footer.php'; ?>


</html>
