<?

$ImageParameters=Array(
 large => Array(
  width => LARGE_IMAGE_WIDTH,
  height => LARGE_IMAGE_HEIGHT,
 ),
 medium => Array(
  width => MEDIUM_IMAGE_WIDTH,
  height => MEDIUM_IMAGE_HEIGHT,
 ),
 small => Array(
  width => SMALL_IMAGE_WIDTH,
  height => SMALL_IMAGE_HEIGHT,
 ),
 
);

$ImageSlots=Array(
 1 => Array(
  images => Array('large','medium','small'),
 ),
);



function UploadImageSlots() {
 global $ImageSlots;
 global $ImageParameters;
 foreach($ImageSlots AS $sl=>$slot) {
  $img;
  if (isset($_FILES['upload_image_slot_'.$sl]['tmp_name'])) {
   $img=imagecreatefromjpeg($_FILES['upload_image_slot_'.$sl]['tmp_name']);
  } elseif (isset($_FILES['url_image_slot_'.$sl]) && ($_FILES['url_image_slot_'.$sl]!='')) {
   $img=imagecreatefromjpeg($_FILES['url_image_slot_'.$sl]);
  }
  if ($img) {
   $wd=imagesx($img);
   $ht=imagesy($img);
   foreach($slot['images'] AS $ikey) {
    $ipar=$ImageParameters[$ikey];
    $wd2=$ipar['width'];
    $ht2=$ipar['height'];
    $sc=min($wd2/$wd,$ht2/$ht);
    $img2=imagecreatetruecolor($wd2,$ht2);
    imagecopyresized($img2,$img,int(($wd2-$wd*$sc+1)/2),int(($ht2-$ht*$sc+1)/2),0,0,int($wd*$sc+0.5),int($ht*$sc+0.5),$wd,$ht);
    $dir=DIR_FS_CATALOG_IMAGES .$ikey.'/';
    mkdir($dir,0755);
    imagejpeg($img2,$dir.$img_filename,isset($ipar['quality'])?$ipar['quality']:75);
   }
  }
 }
}


function DisplayImageSlots() {
 global $ImageSlots;
 global $ImageParameters;
?>
<TABLE>
<?
 foreach($ImageSlots AS $sl=>$slot) {
?>
<TR><TD>
<?
  if (isset($slot['preview'])) {
?>
<IMG src="">
<?
  }
?>
</TD><TD>Upload Image: <INPUT type="file" name="upload_image_slot_<?=$sl?>"><BR>or load from url:<INPUT type="text" name="url_image_slot_<?=$sl?>"></TD>
</TR>
<?
 }
?>
</TABLE>
<?
}



?>
