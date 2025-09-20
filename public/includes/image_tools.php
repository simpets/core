<?php
function overlayImage($baseImagePath, $overlayPath, $x = 0, $y = 0, $opacity = 100) {
    $base = imagecreatefrompng($baseImagePath);
    $overlay = imagecreatefrompng($overlayPath);
    imagealphablending($base, true);
    imagesavealpha($base, true);
    imagecopymerge($base, $overlay, $x, $y, 0, 0, imagesx($overlay), imagesy($overlay), $opacity);
    return $base;
}

function saveImage($image, $destination) {
    imagepng($image, $destination);
    imagedestroy($image);
}

function createTransparentCanvas($width, $height) {
    $canvas = imagecreatetruecolor($width, $height);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);
    return $canvas;
}
?>
