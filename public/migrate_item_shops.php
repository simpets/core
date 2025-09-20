<?php
require_once "includes/db.php";

echo "<h2>Migrating items.shop → items.shop_id</h2>";

// Fetch all shops and normalize names
$stmt = $pdo->query("SELECT id, name FROM shops");
$shops = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $normalizedName = strtolower(trim($row['name']));
    $shops[$normalizedName] = $row['id'];
}

$updated = 0;
$skipped = 0;

// Fetch all items with a shop string but no shop_id
$stmt = $pdo->query("SELECT id, name, shop FROM items WHERE shop_id IS NULL");
$items = $stmt->fetchAll();

foreach ($items as $item) {
    $rawName = $item['shop'];
    $normalizedShop = strtolower(trim($rawName));

    if (isset($shops[$normalizedShop])) {
        $shop_id = $shops[$normalizedShop];
        $update = $pdo->prepare("UPDATE items SET shop_id = ? WHERE id = ?");
        $update->execute([$shop_id, $item['id']]);
        echo "✔ Updated '{$item['name']}' to shop_id $shop_id ({$rawName})<br>";
        $updated++;
    } else {
        echo "⚠ Skipped '{$item['name']}' — no matching shop '{$rawName}' found<br>";
        $skipped++;
    }
}

echo "<br><strong>Done:</strong> $updated updated, $skipped skipped.";