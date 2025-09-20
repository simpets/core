<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {{
    die("You must be logged in to spar.");
}}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {{
    $pet_id = (int) $_POST['pet_id'];

    $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    $pet = $stmt->fetch();

    if (!$pet) die("Pet not found.");

    // Pick a random opponent of the same type
    $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE type = ? AND user_id != ? ORDER BY RAND() LIMIT 1");
    $stmt->execute([$pet['type'], $user_id]);
    $opponent = $stmt->fetch();

    if (!$opponent) {{
        echo "<p>No opponents available at this time.</p>";
    }} else {{
        $winner = rand(0, 1) ? $pet : $opponent;
        $isUserWin = ($winner['id'] == $pet['id']);

        // Log win
        $pdo->prepare("UPDATE user_pets SET sparring_wins = sparring_wins + 1 WHERE id = ?")->execute([$winner['id']]);

        echo "<p>You sparred against <strong>" . htmlspecialchars($opponent['pet_name']) . "</strong>!</p>";
        echo $isUserWin 
            ? "<p><strong>You won!</strong> Your pet <strong>" . htmlspecialchars($pet['pet_name']) . "</strong> now has more sparring wins!</p>"
            : "<p><strong>You lost!</strong> Better luck next time.</p>";
    }}
    echo "<p><a href='sparring_match_simpets.php'>Spar Again</a></p>";
    exit;
}}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Sparring Match</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Sparring Match</h1>
  <form method="post">
    <label for="pet_id">Choose your pet:</label>
    <select name="pet_id" id="pet_id" required>
      <?php foreach ($pets as $pet): ?>
        <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?> (<?= $pet['type'] ?>)</option>
      <?php endforeach; ?>
    </select>
    <br><br>
    <button type="submit">Start Spar</button>
  </form>
</div>
</body>
</html>
