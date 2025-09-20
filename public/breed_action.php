<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id'])) die("Unauthorized access.");

$user_id = $_SESSION['user_id'];
$id1 = $_POST['parent1'] ?? null;
$id2 = $_POST['parent2'] ?? null;
$offspring_name = trim($_POST['offspring_name'] ?? '');

if (!$id1 || !$id2 || !$offspring_name) die("Missing required fields.");

// Get both parents
$stmt1 = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt1->execute([$id1, $user_id]);
$p1 = $stmt1->fetch();

$stmt2 = $pdo->prepare("SELECT * FROM user_pets WHERE id = ? AND user_id = ?");
$stmt2->execute([$id2, $user_id]);
$p2 = $stmt2->fetch();

if (!$p1 || !$p2 || $p1['type'] !== $p2['type']) {
    die("Invalid parents selected or mismatched type.");
}

$type = $p1['type'];

function loadImage($path) {
    if (!file_exists($path)) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match($ext) {
        'png' => imagecreatefrompng($path),
        'gif' => imagecreatefromgif($path),
        default => imagecreatefromjpeg($path),
    };
}

$base1_path = (!empty($p1['pet_image']) && file_exists($p1['pet_image']))
    ? $p1['pet_image']
    : "images/bases/" . basename($p1['base']);

$base2_path = (!empty($p2['pet_image']) && file_exists($p2['pet_image']))
    ? $p2['pet_image']
    : "images/bases/" . basename($p2['base']);

$base1 = loadImage($base1_path);
$base2 = loadImage($base2_path);
if (!$base1 || !$base2) die("Error loading base images.");

// Transparent canvas setup
$canvas = imagecreatetruecolor(200, 200);
imagealphablending($canvas, false);
imagesavealpha($canvas, true);
$transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
imagefill($canvas, 0, 0, $transparent);

// Random visual merge mode
$merge_mode = rand(1,2);
switch ($merge_mode) {
   
        
   case 1: // Soft opacity blend with fully transparent background
    $scaled1 = imagescale($base1, 200, 200);
    $scaled2 = imagescale($base2, 200, 200);

    // First, copy base1 normally
    imagecopy($canvas, $scaled1, 0, 0, 0, 0, 200, 200);

    // Then manually blend base2 at 50% opacity preserving alpha
    for ($x = 0; $x < 200; $x++) {
        for ($y = 0; $y < 200; $y++) {
            $rgb1 = imagecolorsforindex($canvas, imagecolorat($canvas, $x, $y));
            $rgb2 = imagecolorsforindex($scaled2, imagecolorat($scaled2, $x, $y));

            // Alpha blending math
            $r = ($rgb1['red'] + $rgb2['red']) / 2;
            $g = ($rgb1['green'] + $rgb2['green']) / 2;
            $b = ($rgb1['blue'] + $rgb2['blue']) / 2;
            $a = min($rgb1['alpha'], $rgb2['alpha']); // keep least transparent pixel

            $color = imagecolorallocatealpha($canvas, $r, $g, $b, $a);
            imagesetpixel($canvas, $x, $y, $color);
        }
    }

    // Cleanup
    imagedestroy($scaled1);
    imagedestroy($scaled2);
    break;
        
        
        
    case 2: // One parent with slight variation
        $primary = rand(0, 1) ? $base1 : $base2;
        imagecopy($canvas, imagescale($primary, 200, 200), 0, 0, 0, 0, 200, 200);
        break;
}

// Combine markings
$all_markings = array_filter([
    $p1['marking1'], $p1['marking2'], $p1['marking3'],
    $p2['marking1'], $p2['marking2'], $p2['marking3']
]);
shuffle($all_markings);
$selected_markings = array_slice($all_markings, 0, 3);

// Apply markings with optional mutation
$marking_paths = [];
foreach ($selected_markings as $marking) {
    $path = "images/markings/{$type}/{$marking}";
    if (file_exists($path)) {
        $mark_img = imagecreatefrompng($path);
        $mark_img = imagescale($mark_img, 200, 200);

        // Mutation: 25% chance to apply with transparency or tint
        $mutation = rand(1, 4) == 1;
        if ($mutation) {
            imagefilter($mark_img, IMG_FILTER_COLORIZE, rand(0,50), rand(0,50), rand(0,50), rand(30,70));
        }

        imagecopy($canvas, $mark_img, 0, 0, 0, 0, 200, 200);
        $marking_paths[] = $marking;
    }
}

// Save image
$filename = "images/generated/pet_" . time() . ".png";
imagepng($canvas, $filename);
imagedestroy($canvas);



$stmt = $pdo->prepare("INSERT INTO user_pets (
    user_id, pet_name, type, level, pet_image, gender, father, mother,
    base, marking1, marking2, marking3,
    boosts, offspring, background_url, toy1, toy2, toy3, deco, description, mate,mate_id 
    
    
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $user_id,
    $offspring_name,
    $type,
    1, // level
    $filename,
    rand(0, 1) ? 'Male' : 'Female',
    $p1['pet_name'],
    $p2['pet_name'],
    basename($p1['base']),
    $marking_paths[0] ?? '',
    $marking_paths[1] ?? '',
    $marking_paths[2] ?? '',
    0,    // boosts
    0,    // offspring
    '',   // background_url
    '',   // toy1
    '',   // toy2
    '',   // toy3
    '',   // deco
    '',   // description
    
    '', // mate
    
    0, // mate id
    
]);










$pdo->prepare("UPDATE user_pets SET offspring = offspring + 1 WHERE id IN (?, ?)")->execute([$id1, $id2]);

header("Location: dashboard.php?bred=1");
exit;
?>