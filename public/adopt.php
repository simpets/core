<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adopt_id = $_POST['adopt_id'] ?? null;
    $pet_name = trim($_POST['pet_name'] ?? '');
    $gender = $_POST['gender'] ?? '';

    if ($adopt_id && $pet_name && in_array($gender, ['Male', 'Female'])) {
        $stmt = $pdo->prepare("SELECT type, image FROM adopts WHERE id = ?");
        $stmt->execute([$adopt_id]);
        $adopt = $stmt->fetch();

        if ($adopt) {
            $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_name, type, level, pet_image, gender, boosts, offspring)
                VALUES (?, ?, ?, 3, ?, ?, 0, 0)");
            $stmt->execute([
                $_SESSION['user_id'],
                $pet_name,
                $adopt['type'],
                $adopt['image'],
                $gender
            ]);
            header("Location: dashboard.php?adopted=1");
            exit;
        }
    }
}

$adoptables = $pdo->query("SELECT id, type, image FROM adopts")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Adopt a Pet</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
    .adopt-card {
        display: inline-block;
        width: 220px;
        margin: 20px;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 10px;
        background: #fff;
        text-align: center;
        box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
    }
    .adopt-card img {
        width: 150px;
        height: auto;
    }
    </style>
</head>
<body>
    
    
    <?php include "menu.php"; ?>
    
    
    
    
    
    
    
<div class="container">
<h1>Adopt a New Pet</h1>
<div style="display: flex; flex-wrap: wrap; justify-content: center;">
<?php foreach ($adoptables as $adopt): ?>
    <div class="adopt-card">
        <form method="post">
            <input type="hidden" name="adopt_id" value="<?= $adopt['id'] ?>">
            <h3><?= htmlspecialchars($adopt['type']) ?></h3>
            <img src="<?= htmlspecialchars($adopt['image']) ?>" alt="<?= htmlspecialchars($adopt['type']) ?>">
            <p>
                <label>Name:<br>
                <input type="text" name="pet_name" required></label>
            </p>
            <p>
                <label>Gender:<br>
                <select name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select></label>
            </p>
            <input type="submit" value="Adopt" style="margin-top: 10px;">
        </form>
    </div>
<?php endforeach; ?>
</div>
</div>
</body>
</html>
