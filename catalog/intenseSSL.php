<?php 
header("Content-type: image/png"); 

$date = date("M Y", time());
$hostname = $_SERVER['SERVER_NAME']; 
$hostname = str_replace('www.', '', $hostname);
//imagecolorallocate($image, R, G, B) in HEX values

$font = '/usr/share/IXcore/catalog/includes/fonts/arial.ttf';

if ($_GET['seal']==NULL) {
}

if ($_GET['seal']==1) {
$image = imagecreatefrompng("/usr/share/IXcore/catalog/Security-Verified-Seal.png");
$font_white = imagecolorallocate($image, 255, 255, 255);
$font_black = imagecolorallocate($image, 2, 1, 8);
//($image, fontsize, angle, rightindent, downindent, txtcolour, data)
imagettftext($image, 8, 0, 48, 10, $font_black, $font, $date);
$bbox=imagettfbbox(8,0,$font,$hostname);
imagettftext($image, 8, 0, $bbox[0]+(imagesx($image)-$bbox[4])/2, 55, $font_white, $font, $hostname);
imagealphablending($image, false);
imagesavealpha($image, true);
}

elseif ($_GET['seal']==2) {
$image = imagecreatefrompng("/usr/share/IXcore/catalog/Privacy-Verified-Seal.png");
$font_white = imagecolorallocate($image, 255, 255, 255);
$font_black = imagecolorallocate($image, 2, 1, 8);
imagettftext($image, 8, 0, 48, 10, $font_black, $font, $date);
$bbox=imagettfbbox(8,0,$font,$hostname);
imagettftext($image, 8, 0, $bbox[0]+(imagesx($image)-$bbox[4])/2, 55, $font_white, $font, $hostname);
imagealphablending($image, false);
imagesavealpha($image, true);

}

elseif ($_GET['seal']==3){
$image = imagecreatefrompng("/usr/share/IXcore/catalog/Biz-Verified-Seal.png");
$font_white = imagecolorallocate($image, 255, 255, 255);
$font_black = imagecolorallocate($image, 2, 1, 8);
imagettftext($image, 8, 0, 48, 10, $font_black, $font, $date);
$bbox=imagettfbbox(8,0,$font,$hostname);
imagettftext($image, 8, 0, $bbox[0]+(imagesx($image)-$bbox[4])/2, 55, $font_white, $font, $hostname);
imagealphablending($image, false);
imagesavealpha($image, true);

}

elseif ($_GET['seal']==4){
$image = imagecreatefrompng("/usr/share/IXcore/catalog/Verified-Seal.png");
$font_white = imagecolorallocate($image, 255, 255, 255);
$font_black = imagecolorallocate($image, 2, 1, 8);
imagettftext($image, 8, 0, 48, 10, $font_black, $font, $date);
$bbox=imagettfbbox(8,0,$font,$hostname);
imagettftext($image, 8, 0, $bbox[0]+(imagesx($image)-$bbox[4])/2, 55, $font_white, $font, $hostname);
imagealphablending($image, false);
imagesavealpha($image, true);
}

imagepng($image); 
imagedestroy($image); 

?>
