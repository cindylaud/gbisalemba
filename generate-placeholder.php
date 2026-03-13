<?php
// Create simple placeholder image
// This creates a basic gradient placeholder image

// Set headers for image
header('Content-Type: image/png');

// Create image 400x300
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 234, 242, 249);  // Light blue
$blue_primary = imagecolorallocate($image, 30, 58, 95); // Dark blue
$text_color = imagecolorallocate($image, 100, 120, 150); // Gray-blue

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add border
imagerectangle($image, 0, 0, $width-1, $height-1, $blue_primary);
imagerectangle($image, 2, 2, $width-3, $height-3, $text_color);

// Add diagonal lines
imageline($image, 0, 0, $width, $height, $text_color);
imageline($image, $width, 0, 0, $height, $text_color);

// Add text
$text = "Foto Pelayanan";
$font = 4; // Built-in font
$x = ($width - strlen($text) * imagefontwidth($font)) / 2;
$y = ($height - imagefontheight($font)) / 2;

imagestring($image, $font, $x, $y, $text, $blue_primary);

// Output image
imagepng($image);

// Free up memory
imagedestroy($image);
?>
