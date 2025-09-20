<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id      = $_SESSION['user_id'];
$user_item_id = $_GET['id'] ?? null;

if (!$user_item_id) {
    die("No item selected.");
}

// 1) Fetch the item details
$stmt = $pdo->prepare("
    SELECT 
        ui.id AS user_item_id,
        ui.quantity,
        i.id   AS item_id,
        i.name,
        i.function_type,
        i.image
    FROM user_items ui
    JOIN items i ON ui.item_id = i.id
    WHERE ui.id = ? 
      AND ui.user_id = ?
");
$stmt->execute([$user_item_id, $user_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Item not found.");
}

// 2) Fetch all of this user's pets (for the dropdown)
$stmt = $pdo->prepare("
    SELECT id, pet_name, level, gender 
      FROM user_pets 
     WHERE user_id = ?
");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3) If form submitted, apply the item to the selected pet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = intval($_POST['pet_id'] ?? 0);
    if ($pet_id <= 0) {
        die("No pet selected.");
    }

    // Determine which function to run
    $func = $item['function_type'];

    if ($func === 'level3') {
        $update = $pdo->prepare("UPDATE user_pets SET level = 3 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }
    elseif ($func === 'level1') {
        $update = $pdo->prepare("UPDATE user_pets SET level = 1 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }
    elseif ($func === 'flip_gender') {
        $update = $pdo->prepare("
            UPDATE user_pets
               SET gender = CASE 
                              WHEN gender = 'Male' THEN 'Female' 
                              ELSE 'Male' 
                            END
             WHERE id = ? AND user_id = ?
        ");
        $update->execute([$pet_id, $user_id]);
    }
    elseif ($func === 'set_background') {
        $update = $pdo->prepare("
            UPDATE user_pets 
               SET background_url = ? 
             WHERE id = ? AND user_id = ?
        ");
        $update->execute([$item['image'], $pet_id, $user_id]);
    }

    // --- Fun Potion Effects! ---
    // Rainbow Fur
    elseif ($func === 'rainbow_fur') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_effect = 'rainbow' WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'normal_fur') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_effect = NULL WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Mini Size
    elseif ($func === 'mini_size') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_size = 'mini' WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'normal_size') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_size = NULL WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Wings
    elseif ($func === 'add_wings') {
        $update = $pdo->prepare("UPDATE user_pets SET has_wings = 1 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'remove_wings') {
        $update = $pdo->prepare("UPDATE user_pets SET has_wings = 0 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Glow
    elseif ($func === 'glow') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_glow = 1 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'remove_glow') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_glow = 0 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Patterns
    elseif ($func === 'add_pattern') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_pattern = 'special' WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'remove_pattern') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_pattern = NULL WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Shapeshifting
    elseif ($func === 'shapeshift') {
        $update = $pdo->prepare("UPDATE user_pets SET temp_species = 'alternate' WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'revert_shape') {
        $update = $pdo->prepare("UPDATE user_pets SET temp_species = NULL WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Extra Fluff
    elseif ($func === 'fluff') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_fluff = 1 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'de_fluff') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_fluff = 0 WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Neon
    elseif ($func === 'neon') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_effect = 'neon' WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'de_neon') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_effect = NULL WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Elemental
    elseif ($func === 'set_elemental') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_elemental = 'elemental' WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    } elseif ($func === 'remove_elemental') {
        $update = $pdo->prepare("UPDATE user_pets SET appearance_elemental = NULL WHERE id = ? AND user_id = ?");
        $update->execute([$pet_id, $user_id]);
    }

    // Handle toy slots: matches 'toy1','toy2','toy3' at end of function_type
    elseif (preg_match('/toy([123])$/', $func, $matches)) {
        $query = $pdo->prepare("SELECT toy1, toy2, toy3 FROM user_pets WHERE id = :pet_id AND user_id = :user_id");
        $query->execute([
            ':pet_id' => $pet_id,
            ':user_id' => $user_id
        ]);
        $pet = $query->fetch(PDO::FETCH_ASSOC);

        $slot = null;
        if (empty($pet['toy1'])) {
            $slot = 'toy1';
        } elseif (empty($pet['toy2'])) {
            $slot = 'toy2';
        } elseif (empty($pet['toy3'])) {
            $slot = 'toy3';
        }

        if ($slot) {
            $sql  = "
                UPDATE user_pets
                   SET {$slot} = :image_filename
                 WHERE id = :pet_id
                   AND user_id = :user_id
            ";
            $update = $pdo->prepare($sql);
            $update->execute([
                ':image_filename' => $item['image'],
                ':pet_id'         => $pet_id,
                ':user_id'        => $user_id,
            ]);
        } else {
            echo "All toy slots are full!";
        }
    }

    // Handle decoration: function_type 'add_deco' or 'adddeco'
    elseif (preg_match('/^add[_]?deco$/', $func)) {
        $update = $pdo->prepare("
            UPDATE user_pets 
               SET deco = :image_filename 
             WHERE id = :pet_id 
               AND user_id = :user_id
        ");
        $update->execute([
            ':image_filename' => $item['image'],
            ':pet_id'         => $pet_id,
            ':user_id'        => $user_id,
        ]);
    }
    // If none of the above, no action is taken for this item’s function_type

    // 4) Decrement or remove the item from inventory
    if ($item['quantity'] > 1) {
        $pdo->prepare("
            UPDATE user_items 
               SET quantity = quantity - 1 
             WHERE id = ?
        ")->execute([$user_item_id]);
    } else {
        $pdo->prepare("
            DELETE FROM user_items 
             WHERE id = ?
        ")->execute([$user_item_id]);
    }

    // 5) Fetch the pet's name for the redirect
    $stmt = $pdo->prepare("
        SELECT pet_name 
          FROM user_pets 
         WHERE id = ? 
           AND user_id = ?
    ");
    $stmt->execute([$pet_id, $user_id]);
    $pet_name = $stmt->fetchColumn() ?: '';

    // 6) Redirect back to inventory with a success message
    header(
        "Location: inventory.php?used=1"
        . "&item=" . urlencode($item['name'])
        . "&pet="  . urlencode($pet_name)
    );
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Use <?= htmlspecialchars($item['name'], ENT_QUOTES) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <h1>Use <?= htmlspecialchars($item['name'], ENT_QUOTES) ?></h1>
  <form method="post">
    <label for="pet_id">
      <strong>Select a Pet:</strong><br>
      <select id="pet_id" name="pet_id" required>
        <option value="">-- Choose a Pet --</option>
        <?php foreach ($pets as $pet): ?>
          <option value="<?= htmlspecialchars($pet['id'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($pet['pet_name'], ENT_QUOTES) ?>
            (Level <?= htmlspecialchars($pet['level'], ENT_QUOTES) ?>, 
             <?= htmlspecialchars($pet['gender'], ENT_QUOTES) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <br><br>
    <input type="submit" value="Use Item">
  </form>
</div>
</body>
</html>