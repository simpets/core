<?php
session_start();
require_once "includes/db.php";

define('CURRENCY_NAME', 'simbucks'); // Change to your site's currency name

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get level 3+ pets and their last gem hunt
$stmt = $pdo->prepare("SELECT id, pet_name, level, skill, last_quest FROM user_pets WHERE user_id = ? AND level >= 3");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// GEM TYPES!
$gem_types = [
    "Shimmering Sapphire", "Radiant Ruby", "Gleaming Emerald", "Opal Spark", "Twilight Amethyst",
    "Mystic Topaz", "Moonstone", "Crimson Garnet", "Celestial Quartz", "Azure Aquamarine"
];

if (!isset($_SESSION['gem_quest'])) {
    $_SESSION['gem_quest'] = [
        'stage' => 0,
        'pet_id' => null,
        'pet_name' => null,
        'pet_skill' => null,
        'log' => [],
        'blocked' => false
    ];
}
$quest = &$_SESSION['gem_quest'];

$quest_steps = [
    [
        "Your pet finds a fork in the trail. Do they search the shady cave or climb the rocky hill?",
        ['cave' => "Explore the cave", 'hill' => "Climb the hill"]
    ],
    [
        "A babbling brook glimmers nearby. Does your pet dig in the soft mud or look beneath the water?",
        ['dig' => "Dig in the mud", 'water' => "Look under the water"]
    ],
    [
        "A flock of birds scatters near a sparkling patch. Does your pet sniff the area or follow the birds?",
        ['sniff' => "Sniff the sparkles", 'birds' => "Follow the birds"]
    ],
    [
        "A large boulder sits alone. Will your pet look behind it or try digging beside it?",
        ['behind' => "Look behind the boulder", 'beside' => "Dig beside it"]
    ],
    [
        "The path ends at a glade where the sun shines bright. Does your pet search the grass or rest in the sun?",
        ['grass' => "Search the grass", 'sun' => "Rest in the sun"]
    ]
];

function quest_success($skill) {
    $luck = rand(1, 80);
    return ($skill * 2 + $luck) >= 60;
}

// Handle quest start (pet select)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_id'])) {
    $selected_pet = null;
    foreach ($pets as $pet) {
        if ($pet['id'] == $_POST['pet_id']) {
            $selected_pet = $pet;
            break;
        }
    }
    if ($selected_pet) {
        // Check last_quest to see if pet already quested today
        $already_quested = false;
        if (!empty($selected_pet['last_quest'])) {
            $last = strtotime($selected_pet['last_quest']);
            $midnight = strtotime("today", time());
            if ($last > $midnight) {
                $already_quested = true;
            }
        }

        if ($already_quested) {
            $quest['blocked'] = true;
            $quest['pet_id'] = null;
            $quest['pet_name'] = $selected_pet['pet_name'];
            $quest['log'] = [
                "Sorry, <b>{$selected_pet['pet_name']}</b> has already completed a Gem Hunt today. Try again tomorrow!"
            ];
        } else {
            $quest['blocked'] = false;
            $quest['pet_id'] = $selected_pet['id'];
            $quest['pet_name'] = $selected_pet['pet_name'];
            $quest['pet_skill'] = (int)$selected_pet['skill'];
            $quest['stage'] = 0;
            $quest['log'] = [
                "Your pet <b>{$selected_pet['pet_name']}</b> (Skill: {$selected_pet['skill']}) sets off on a dazzling Gem Hunt!"
            ];
        }
    }
}

// Handle step choices
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice']) && $quest['pet_id'] && empty($quest['blocked'])) {
    $stage = $quest['stage'];
    $pet_skill = $quest['pet_skill'];
    $choice = $_POST['choice'];
    $step_text = $quest_steps[$stage][0];
    $choice_text = $quest_steps[$stage][1][$choice];
    $success = quest_success($pet_skill);

    if ($success) {
        $result = [
            "Sparkle! <b>{$quest['pet_name']}</b> chose to <b>$choice_text</b> and discovered something interesting!"
        ];
    } else {
        $result = [
            "Oh no! <b>{$quest['pet_name']}</b> chose to <b>$choice_text</b> but found only dirt and dust this time."
        ];
    }
    $quest['log'][] = "<br><b>Step " . ($stage+1) . "</b>: $step_text<br><i>Choice:</i> $choice_text<br>" . implode(" ", $result);
    $quest['stage']++;

    // Finish quest after 5 steps
    if ($quest['stage'] >= count($quest_steps)) {
        $num_success = substr_count(implode(" ", $quest['log']), "discovered something");
        $reward = rand(50 + $num_success*100, 5000);

        // Pick a random gem (better gem if more successes)
        $gem_index = min(count($gem_types)-1, $num_success + rand(0,2));
        $gem_found = $gem_types[$gem_index];

        // Update currency
        $pdo->prepare("UPDATE users SET " . CURRENCY_NAME . " = " . CURRENCY_NAME . " + ? WHERE id = ?")->execute([$reward, $user_id]);

        $quest['log'][] = "<br><b>Gem Hunt complete!</b><br>{$quest['pet_name']} brings back a <b>$gem_found</b> and <b>$reward</b> " . CURRENCY_NAME . "!";

        // Award skill improvement!
        $skill_gain = rand(1, 4);
        $stmt = $pdo->prepare("UPDATE user_pets SET skill = skill + ?, last_quest = ? WHERE id = ? AND user_id = ?");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$skill_gain, $now, $quest['pet_id'], $user_id]);

        // Fetch new skill for display
        $stmt = $pdo->prepare("SELECT skill FROM user_pets WHERE id = ? AND user_id = ?");
        $stmt->execute([$quest['pet_id'], $user_id]);
        $new_skill = $stmt->fetchColumn();

        $quest['log'][] = "<b>{$quest['pet_name']}</b> gained <b>+$skill_gain skill</b>! Their skill is now <b>$new_skill</b>.";

        // Reset for next quest
        $show_end = true;
        $log = $quest['log'];
        unset($_SESSION['gem_quest']);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gem Hunt Quest</title>
    <style>
        body { background: #fffaf8; font-family: 'Segoe UI', Arial, sans-serif; }
        .quest-container {
            max-width: 550px; margin: 32px auto; background: #f7f1fa; padding: 30px 26px;
            border-radius: 14px; box-shadow: 0 7px 32px rgba(40,60,130,0.07);
        }
        h2 { color: #6f2ba1; letter-spacing: 1.1px; }
        .pet-select, .quest-step, .quest-log { margin: 30px 0; }
        label, .quest-step label { font-weight: bold; color: #34204d; }
        .choices { margin: 20px 0; }
        button, input[type=submit] {
            background: #f8b3fc; color: #612c83; border: none; border-radius: 6px;
            padding: 9px 32px; font-size: 1.11em; font-weight: bold; cursor: pointer;
            margin-top: 12px; transition: background .19s;
        }
        button:hover, input[type=submit]:hover { background: #bc77e1; color: #fff8ec; }
        .quest-log { background: #eadaf8; border-radius: 9px; padding: 18px; }
    </style>
</head>
<body>
    
    <?php include 'menu.php'; ?>
<div class="quest-container">
    <h2>💎 Gem Hunt Quest 💎</h2>
    <div class="quest-log">
        <?php
        // Print quest log
        if (isset($show_end)) {
            foreach ($log as $l) echo $l . "<br>";
            echo '<form method="post"><button type="submit">Start Another Gem Hunt</button></form>';
        } else {
            foreach ($quest['log'] as $l) echo $l . "<br>";
        }
        ?>
    </div>

    <?php if (!isset($show_end)): ?>
        <?php if (!$quest['pet_id']): ?>
            <div class="pet-select">
                <form method="post">
                    <label>Select a pet for the hunt (Level 3+ only):</label>
                    <select name="pet_id" required>
                        <option value="">-- Select Pet --</option>
                        <?php foreach ($pets as $pet): ?>
                            <?php
                            $already_quested = false;
                            if (!empty($pet['last_quest'])) {
                                $last = strtotime($pet['last_quest']);
                                $midnight = strtotime("today", time());
                                if ($last > $midnight) {
                                    $already_quested = true;
                                }
                            }
                            ?>
                            <option value="<?= $pet['id'] ?>" <?= $already_quested ? "disabled" : "" ?>>
                                <?= htmlspecialchars($pet['pet_name']) ?> (Skill: <?= $pet['skill'] ?>)
                                <?= $already_quested ? " - Already hunted today" : "" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Begin Gem Hunt!</button>
                </form>
            </div>
        <?php elseif ($quest['stage'] < count($quest_steps) && empty($quest['blocked'])): ?>
            <div class="quest-step">
                <form method="post">
                    <label><?= $quest_steps[$quest['stage']][0] ?></label><br>
                    <div class="choices">
                        <?php foreach ($quest_steps[$quest['stage']][1] as $val => $label): ?>
                            <button type="submit" name="choice" value="<?= $val ?>"><?= $label ?></button>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
<?php include 'footer.php'; ?>


</html>