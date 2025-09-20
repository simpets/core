<?php
session_start();
require_once "includes/db.php";
$user_id = $_SESSION['user_id'];




// Fetch user's items
$stmt = $pdo->prepare("SELECT ui.*, i.name AS item_name, i.image AS item_image 
    FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.user_id = ? AND ui.quantity > 0");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $qty = max(1, (int)$_POST['quantity']);
    $price = max(1, (int)$_POST['price']);
    $currency = $_POST['currency'] ?? 'canicash';




    // Check ownership and enough qty
    foreach ($items as $it) {
        if ($it['item_id'] == $item_id && $it['quantity'] >= $qty) {
            // Remove from user_items
            $pdo->prepare("UPDATE user_items SET quantity = quantity - ? WHERE user_id = ? AND item_id = ?")
                ->execute([$qty, $user_id, $item_id]);
            // Add to listings
            $pdo->prepare("INSERT INTO user_shop_listings (seller_id, item_id, quantity, price, currency) VALUES (?, ?, ?, ?, ?)")
                ->execute([$user_id, $item_id, $qty, $price, $currency]);
            header("Location: my_shop.php");
            exit;
        }
    }
    die("Invalid item or quantity.");
}





?>

<?php include "menu.php"; ?>

<form method="post">
    <label>Item:
        <select name="item_id">
            <?php foreach ($items as $it): ?>
                <option value="<?= $it['item_id'] ?>">
                    <?= htmlspecialchars($it['item_name']) ?> (You have <?= (int)$it['quantity'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <label>Quantity: <input type="number" name="quantity" min="1" value="1"></label><br>
    <label>Price per item: <input type="number" name="price" min="1" value="1"></label><br>
    <label>Currency: <input type="text" name="currency" value="canicash"></label><br>
    <button type="submit">List for Sale</button>
</form>


        


