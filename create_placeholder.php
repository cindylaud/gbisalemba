<?php
// Create a placeholder image for pelayanan
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Define colors
$blue = imagecolorallocate($image, 30, 58, 95);
$light_blue = imagecolorallocate($image, 234, 242, 249);
$white = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 100, 100, 100);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $light_blue);

// Add border
imagerectangle($image, 5, 5, $width-5, $height-5, $blue);

// Add text
$text = "Foto Pelayanan";
$font_size = 5;
$text_bbox = imagettfbbox($font_size, 0, __DIR__ . '/arial.ttf', $text);
$text_width = $text_bbox[2] - $text_bbox[0];
$text_x = ($width - $text_width) / 2;
$text_y = ($height / 2) + 10;

imagestring($image, 3, $text_x, $text_y - 20, "Foto Pelayanan", $text_color);

// Save image
imagepng($image, '../uploads/pelayanan/placeholder-pelayanan.png');
imagedestroy($image);

echo "Placeholder image created!";
?>
