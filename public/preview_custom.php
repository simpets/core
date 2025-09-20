<?php
// preview_custom.php

// Grab GET params safely
function val($k) { return isset($_GET[$k]) ? $_GET[$k] : ''; }

$type      = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower(val('type')));
$base      = basename(val('base'));
$marking1  = basename(val('marking1'));
$color1    = val('color1');
$marking2  = basename(val('marking2'));
$color2    = val('color2');
$marking3  = basename(val('marking3'));
$color3    = val('color3');

$size = 200;
$ok = false;

// Output PNG header only after we're sure
ob_start();

// Start transparent image
$canvas = imagecreatetruecolor($size, $size);
imagesavealpha($canvas, true);
$trans = imagecolorallocatealpha($canvas, 0,0,0,127);
imagefill($canvas, 0, 0, $trans);

// Try loading the base
if ($type && $base) {
    $base_path = "images/bases/$type/$base";
    if (file_exists($base_path)) {
        $base_img = imagecreatefrompng($base_path);
        imagecopyresampled($canvas, $base_img, 0,0,0,0,$size,$size,imagesx($base_img),imagesy($base_img));
        imagedestroy($base_img);
        $ok = true;
    }
}

// Helper: apply one marking
function apply_marking($canvas, $path, $hexcolor, $size) {
    if (!$path || !file_exists($path)) return false;
    $img = imagecreatefrompng($path);

    // Parse color as #RRGGBB or fallback
    if (!$hexcolor || !preg_match('/^#[0-9A-Fa-f]{6}$/', $hexcolor)) $hexcolor = '#000000';
    $r = hexdec(substr($hexcolor,1,2));
    $g = hexdec(substr($hexcolor,3,2));
    $b = hexdec(substr($hexcolor,5,2));
    imagefilter($img, IMG_FILTER_COLORIZE, $r, $g, $b);
    imagecopyresampled($canvas, $img, 0,0,0,0,$size,$size,imagesx($img),imagesy($img));
    imagedestroy($img);
    return true;
}

// Markings
$did_marking = false;
if ($type && $marking1) $did_marking |= apply_marking($canvas, "images/markings/$type/$marking1", $color1, $size);
if ($type && $marking2) $did_marking |= apply_marking($canvas, "images/markings/$type/$marking2", $color2, $size);
if ($type && $marking3) $did_marking |= apply_marking($canvas, "images/markings/$type/$marking3", $color3, $size);

$ok |= $did_marking;

// If nothing loaded, draw a "red X" error image
if (!$ok) {
    $red = imagecolorallocate($canvas, 255,0,0);
    imagesetthickness($canvas, 8);
    imageline($canvas, 0,0, $size,$size, $red);
    imageline($canvas, $size,0, 0,$size, $red);
}

// Only now set PNG header!
header('Content-Type: image/png');
imagepng($canvas);
imagedestroy($canvas);
ob_end_flush();
exit;