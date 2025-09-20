<?php
session_start();
require_once "includes/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$game_name = 'simpet_quiz';
$today = date('Y-m-d');

// Check if already played today
$stmt = $pdo->prepare("SELECT 1 FROM quiz_log WHERE user_id = ? AND game_name = ? AND played_on = ?");
$stmt->execute([$user_id, $game_name, $today]);
$already_played = $stmt->fetchColumn();

$answered = false;
$earned = 0;

// Quiz bank
$quizzes = [
    [
        "question" => "What do Simpets usually love to do?",
        "options" => ["Be Companions", "Play Pingpong", "Dance", "Make Lemonade"],
        "correct" => "Be Companions"
    ],
    [
        "question" => "What's the Simpet that looks like a little curled up Fox?",
        "options" => ["Farra", "Squeenix", "PomPom", "Feenee"],
        "correct" => "Feenee"
    ],
    [
        "question" => "What color is the Farra's main fur?",
        "options" => ["White", "Green", "Red", "Black"],
        "correct" => "Black"
    ],
    [
        "question" => "Name another Pet Game that ends in Pets and is very well known?",
        "options" => ["Mweor", "Aywas", "Neopets", "Chicken Smoothie"],
        "correct" => "Neopets"
    ],
];

$quiz = $quizzes[array_rand($quizzes)]; // Pick random quiz

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_played) {
    $answer = $_POST['answer'] ?? '';
    if ($answer === $_POST['correct']) {
        $earned = 1000;
        $stmt = $pdo->prepare("UPDATE users SET simbucks = simbucks + ? WHERE id = ?");
        $stmt->execute([$earned, $user_id]);
    }

    // Log the attempt
    $stmt = $pdo->prepare("INSERT INTO quiz_log (user_id, game_name, played_on) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $game_name, $today]);

    $answered = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quiz Game - Simpets!</title>
    
    
    
    
    
    
    
    
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<header><h1>The Simpet Quiz</h1></header>

<img src="assets/images/simpet_quiz_banner.jpg" alt="Simpet Quiz" width="400">







<div class="container">
    <h2>Quiz Game</h2>

    <?php if ($already_played): ?>
        <p>You’ve already played today. Come back tomorrow!</p>
        <p><a href="dashboard.php">Return to Dashboard</a></p>
    <?php elseif (!$answered): ?>
        <form method="post">
            <p><strong><?= $quiz['question'] ?></strong></p>
            <?php foreach ($quiz['options'] as $opt): ?>
                <label><input type="radio" name="answer" value="<?= $opt ?>" required> <?= $opt ?></label><br>
            <?php endforeach; ?>
            <input type="hidden" name="correct" value="<?= $quiz['correct'] ?>">
            <br>
            <input type="submit" value="Submit Answer">
        </form>
    <?php else: ?>
        <?php if ($earned > 0): ?>
            <p>Correct! You earned <strong><?= $earned ?></strong> Simbucks!</p>
        <?php else: ?>
            <p>Oops! That wasn’t right. Better luck next time.</p>
        <?php endif; ?>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>