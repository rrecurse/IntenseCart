<?php
// Copyright (c) 2008 IntenseCart e-commerce

umask(0002);

// # added for cookieless static content
// # consider needing wildcard or seperate SSL cert to use subdomain securely.

if(defined('IMAGE_SUBDOMAIN') && IMAGE_SUBDOMAIN != '') {

	$srcPath = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:').str_replace(array('https:','http:'),'',IMAGE_SUBDOMAIN).'/';
	$dstPath = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:').str_replace(array('https:','http:'),'',IMAGE_SUBDOMAIN).'/cache/';

} else { 

	$srcPath=$_SERVER['DOCUMENT_ROOT'].'/images/';
	$dstPath=$_SERVER['DOCUMENT_ROOT'].'/images/cache/';
}

$lockFile=$dstPath.'.lock';

// FagSoft: Removed old and unused createDir() function + tempFile variable.

function imgDone() {
  virtual($_SERVER['REQUEST_URI']);
  exit();
}

//if (!preg_match('|^/((\d+)x(\d+)/(.*))|',$_SERVER['PATH_INFO'],$pp)) imgNotFound();
$imgFile=$pp[4];
$dstWidth=$pp[2];
$dstHeight=$pp[3];
$dstFile=$pp[1];

$lfd=fopen($lockFile,'w');
fputs($lfd,rand());
fclose($lfd);

unlink($dstPath.$dstFile);

list($srcWidth,$srcHeight,$srcType) = getimagesize($srcPath.$imgFile);

$img=NULL;
$tc=false;
$dstType=$srcType;

$wd2=$dstWidth?$dstWidth:($dstHeight?floor($dstHeight*$srcWidth/$srcHeight+0.5):$srcWidth);
$ht2=$dstHeight?$dstHeight:floor($dstWidth*$srcHeight/$srcWidth+0.5);

if ($wd2==$srcWidth && $ht2==$srcHeight) {
	// # Changed to copy image if of same size.
	if (!is_file ($dstPath.$dstFile)) {
		copy ($srcPath.$imgFile, $dstPath.$dstFile);
	}

	imgDone();
}

switch ($srcType) {
  case IMAGETYPE_JPEG:
    $tc=true;
    $img=imagecreatefromjpeg($srcPath.$imgFile);
    break;
  case IMAGETYPE_GIF:
    $img=imagecreatefromgif($srcPath.$imgFile);
    if (!function_exists('imagegif')) $dstType=IMAGETYPE_PNG;
    break;
  case IMAGETYPE_PNG:
    $img=imagecreatefrompng($srcPath.$imgFile);
    $tc=imagecolorstotal($img)>256;
    $tc=true;
    break;
}

if (!$img) imgDone();
$img2=NULL;

$sc=min($wd2/$srcWidth,$ht2/$srcHeight);
$wd2t=floor($srcWidth*$sc+0.5);
$ht2t=floor($srcHeight*$sc+0.5);
$x2t=floor(($wd2-$wd2t)/2);
$y2t=floor(($ht2-$ht2t)/2);
$img2=$tc?imagecreatetruecolor($wd2,$ht2):imagecreate($wd2,$ht2);
imagealphablending($img2,false);
if ($wd2>$wd2t || $ht2>$ht2t) {
  $bg=imagecolorallocate($img2,255,255,255);
  if (!$tc) imagecolortransparent($img2,$bg);
  if ($x2t>0) imagefilledrectangle($img2,0,0,$x2t-1,$ht2-1,$bg);
  if ($y2t>0) imagefilledrectangle($img2,0,0,$wd2-1,$y2t-1,$bg);
  if ($wd2>$wd2t) imagefilledrectangle($img2,$wd2-$wd2t-$x2t,0,$wd2-1,$ht2-1,$bg);
  if ($ht2>$ht2t) imagefilledrectangle($img2,0,$ht2-$ht2t-$y2t,$wd2-1,$ht2-1,$bg);
}
if ($tc && function_exists('imagecopyresampled')) {
  imagecopyresampled($img2,$img,$x2t,$y2t,0,0,$wd2t,$ht2t,$srcWidth,$srcHeight);
} else {
  imagecopyresized($img2,$img,$x2t,$y2t,0,0,$wd2t,$ht2t,$srcWidth,$srcHeight);
}

// # Changed to write to resized file directly, instead of temp file.
switch ($dstType) {
  case IMAGETYPE_GIF:
    imagegif($img2,$dstPath.$dstFile);
    break;
  case IMAGETYPE_PNG:
    imagesavealpha($img2,true);
    imagepng($img2,$dstPath.$dstFile);
    break;
  default:
    imagejpeg($img2,$dstPath.$dstFile,80);
    break;
}

// # Removed file renaming and deletion of temp file.

imgDone();

?>
