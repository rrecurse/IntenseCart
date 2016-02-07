<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class blk_image_swap extends IXblock {

  function render(&$body) {
    $imgs=$this->context['imageset']->getImages();
    $width=$this->args['width']+0;
    $height=$this->args['height']+0;

    $images=Array();
    foreach ($imgs AS $img) {
      if (isset($images[$img])) continue;
      if (!isset($img0)) $img0=$img;
      $images[$img]=tep_image_src($img,$width,$height);
    }
?>
<img src="<?=$images[$img0]?>" width="<?=$width?>" height="<?=$height?>" id="<?=$this->jsObjectName()?>" border="0" alt="" itemprop="image">
<script language="javascript" type="text/javascript">
<?=$this->context['imageset']->jsObjectName()?>.addImageSwap({
    images:<?=tep_js_quote($images)?>,
    imageSwap:function(imgs) {
      if (this.images[imgs[0]]) $('<?=$this->jsObjectName()?>').src=this.images[imgs[0]];
    }
  });
</script>
<?
  }
  function jsObjectName() {
    return 'imageSwap_'.$this->makeID();
  }
}
?>