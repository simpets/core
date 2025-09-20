<?php
$type = $_GET['type'] ?? '';
$marking = $_GET['marking'] ?? '';
$hex = $_GET['color'] ?? '000000';

$path = "images/markings/$type/$marking";

if (!file_exists($path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

header('Content-Type: image/png');

list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");

$img = imagecreatefrompng($path);
imagefilter($img, IMG_FILTER_COLORIZE, $r, $g, $b);
imagesavealpha($img, true);
imagealphablending($img, false);
imagepng($img);
imagedestroy($img);
?>
