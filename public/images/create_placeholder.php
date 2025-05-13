<?php
// Create a simple QR code placeholder image
$width = 150;
$height = 150;

// Create image
$image = imagecreatetruecolor($width, $height);

// Define colors
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$gray = imagecolorallocate($image, 200, 200, 200);

// Fill background with white
imagefill($image, 0, 0, $white);

// Create QR code-like pattern
$blockSize = 10;
for ($x = 0; $x < $width; $x += $blockSize) {
    for ($y = 0; $y < $height; $y += $blockSize) {
        // Add some random blocks
        if (mt_rand(0, 2) === 0) {
            imagefilledrectangle($image, $x, $y, $x + $blockSize - 1, $y + $blockSize - 1, $black);
        }
    }
}

// Add position detection patterns (QR code corners)
// Top left
imagefilledrectangle($image, 0, 0, 30, 30, $black);
imagefilledrectangle($image, 5, 5, 25, 25, $white);
imagefilledrectangle($image, 10, 10, 20, 20, $black);

// Top right
imagefilledrectangle($image, $width - 30, 0, $width, 30, $black);
imagefilledrectangle($image, $width - 25, 5, $width - 5, 25, $white);
imagefilledrectangle($image, $width - 20, 10, $width - 10, 20, $black);

// Bottom left
imagefilledrectangle($image, 0, $height - 30, 30, $height, $black);
imagefilledrectangle($image, 5, $height - 25, 25, $height - 5, $white);
imagefilledrectangle($image, 10, $height - 20, 20, $height - 10, $black);

// Add text
$text = "QR Placeholder";
$font = 5; // Built-in font
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;
imagestring($image, $font, $x, $y, $text, $black);

// Create the file
imagepng($image, __DIR__ . '/qr-placeholder.png');
imagedestroy($image);

echo "QR placeholder image created successfully!"; 