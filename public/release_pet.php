<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = intval($_GET['id'] ?? 0);

if ($pet_id <= 0) {
    die("No pet selected.");
}

// Double-check this pet belongs to the user!
$stmt = $pdo->prepare("SELECT pet_name FROM user_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet_name = $stmt->fetchColumn();

if (!$pet_name) {
    die("Pet not found or you do not own this pet.");
}

// Delete the pet
$delete = $pdo->prepare("DELETE FROM user_pets WHERE id = ? AND user_id = ?");
$delete->execute([$pet_id, $user_id]);

// Optionally: delete related data (offspring, boosts, etc) here if needed

// Redirect to dashboard with a success message
header("Location: dashboard.php?released=1&name=" . urlencode($pet_name));
exit;
?>
3. Show Success Message on Dashboard
Add this to your dashboard page, after <h1>Welcome to Your Dashboard!</h1>:

php
Copy
Edit
<?php if (isset($_GET['released'], $_GET['name'])): ?>
  <p style="color:#d9534f; font-weight:bold;">
    You have released <?= htmlspecialchars($_GET['name']) ?>.
  </p>
<?php endif; ?>