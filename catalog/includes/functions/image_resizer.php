<?php

// # Resizes the jpeg image $imgf (without path!) to $wd2 x $ht2
// # caches it and returns the path of the cached image

function ImageResizer($imgf,$wd2,$ht2) {

	$src = DIR_FS_CATALOG_IMAGES.$imgf;
	$srcw = DIR_WS_CATALOG_IMAGES.$imgf;

	$frm = sprintf('%dx%d',$wd2,$ht2);
	$dst = DIR_FS_CATALOG_IMAGES_CACHE."$frm/$imgf";
	$dstw = DIR_WS_CATALOG_IMAGES_CACHE."$frm/$imgf";

 if (!file_exists(DIR_FS_CATALOG_IMAGES_CACHE)) mkdir(DIR_FS_CATALOG_IMAGES_CACHE,0775);


 $sstat=@stat($src);
 if (!$sstat) return $srcw;
 if ($dstat=@stat($dst)) {
  if ($dstat[9]>=$sstat[9]) return $dstw;
 }


 $quality=80;
 $extm=Array();
 preg_match('/.*\.(.*)/',$imgf,$extm);
 $ext=strtolower($extm[1]);
 $img;
 $tc=false;
 switch ($ext) {
 case 'jpeg':
 case 'jpg':
  $tc=true;
  $img=imagecreatefromjpeg($src);
  break;
 case 'gif':
  $img=@imagecreatefromgif($src);
  if (!function_exists('imagegif')) $dst.='.'.($ext='jpg');
  break;
 case 'png':
  $img=@imagecreatefrompng($src);
  break;
 }
 if (!$img) return $srcw;
 $wd=imagesx($img);
 $ht=imagesy($img);
 $ipar=$ImageParameters[$frm];
 $img2;
 if ($wd2==0) $wd2=floor($ht2*$wd/$ht+0.5);
 if ($ht2==0) $ht2=floor($wd2*$ht/$wd+0.5);

	if(($wd!=$wd2) || ($ht!=$ht2)) {
		$sc = min($wd2/$wd,$ht2/$ht);

  $img2 = ($tc ? imagecreatetruecolor($wd2,$ht2) : imagecreate($wd2,$ht2));

  $x2t=floor(($wd2-$wd*$sc+1)/2);
  $y2t=floor(($ht2-$ht*$sc+1)/2);
  $wd2t=floor($wd*$sc+0.5);
  $ht2t=floor($ht*$sc+0.5);
  $bg=imagecolorallocate($img2,255,255,255);
  imagefilledrectangle($img2,0,0,$wd2-1,$ht2-1,$bg);
  if (!$tc) imagecolortransparent($img2,$bg);
  if ($tc && function_exists('imagecopyresampled')) {
   imagecopyresampled($img2,$img,$x2t,$y2t,0,0,$wd2t,$ht2t,$wd,$ht);
  } else {
   imagecopyresized($img2,$img,$x2t,$y2t,0,0,$wd2t,$ht2t,$wd,$ht);
  }
 } else {
	$img2=$img;
 }

if(!file_exists(DIR_FS_CATALOG_IMAGES_CACHE .$frm)) mkdir(DIR_FS_CATALOG_IMAGES_CACHE.$frm,0777);

	switch ($ext) {
		case 'jpeg':
		case 'jpg':
			imagejpeg($img2,$dst,$quality);
		break;

		case 'gif':
			imagegif($img2,$dst);
		break;
	
		case 'png':
			imagepng($img2,$dst);
		break;
	}

	CleanupImageCache($frm);

	return $dstw;
}


function CleanupImageCache($frm) {
	$d = DIR_FS_CATALOG_IMAGES_CACHE.$frm;

	if(($clst=@stat($d.'/.cleanup')) && ($clst[9]>time()-86400)) return 1;

	$dir = opendir($d);

	if(!$dir) return 0;

	while($f=readdir($dir)) {
		if($f[0]=='.') continue;
		$st = stat("$d/$f");
		if ($st && ($st[8]<time()-86400*15)) {
			unlink("$d/$f");
		}
	}

	closedir($dir);
	
	$cl = fopen($d.'/.cleanup','w');
	fclose($cl);
}

?>
