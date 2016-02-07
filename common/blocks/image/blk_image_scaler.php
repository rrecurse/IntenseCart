<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class blk_image_scaler extends IXblock {

  function render(&$body) {
    $this->scaler='imgScaler_'.$this->makeID();

  
    $this->renderBody($body);
    if (!isset($this->imgobj)) return;
    
    $imgs=$this->context['imageset']->getImages();

    $imgjs=Array();
    $pimgjs=Array();
    foreach ($imgs AS $img) {
      if (isset($imgjs[$img])) continue;
      if (!isset($img0)) $img0=$img;
      $imgjs[$img]=Array();
      $sz=tep_get_image_info($img);
      $zoom=max($sz['width']/$this->width,$sz['height']/$this->height,1);
      if ($zoom<2) $zoom*=2;
      $nstp=floor(log($zoom)/log(2)+0.999);
      $stp=$nstp>0?exp(log($zoom)/$nstp):1;
      for ($i=0,$z=1;$i<=$nstp;$i++) {
	$imgjs[$img][]=Array('x1'=>0,'x2'=>1,'y1'=>0,'y2'=>1,'zoom'=>floor($z*1000+.5)/1000,'src'=>tep_image_src($img,floor($this->width*$z),floor($this->height*$z)));
	$z*=$stp;
      }
      if (isset($this->ptrobj)) $pimgjs[$img]=tep_image_src($img,$this->pwidth,$this->pheight);
    }
    $maximgs=$this->context['imageset']->getNumSlots();
?>
<script type="text/javascript">

window.<?=$this->scaler?>=new imageScalerObj({
  div:$('<?=$this->imgobj?>'),
  width:<?=$this->width?>,
  height:<?=$this->height?>,
<?  if (isset($this->ptrobj)) { ?>
  ptrdiv:$('<?=$this->ptrobj?>'),
  pwidth:<?=$this->pwidth?>,
  pheight:<?=$this->pheight?>,
<?  } ?>
  ptrSlot:[<? $sls=Array(); if (isset($this->slotobjs)) foreach ($this->slotobjs AS $sl) $sls[]="$('$sl')"; echo join(',',$sls); ?>],
  images:<?=$this->_js_quote($imgjs)?>,
  ptrImages:<?=tep_js_quote($pimgjs)?>,
  currImage:<?=tep_js_quote($img0)?>
});

<?=$this->scaler?>.imageSwap([<?=tep_js_quote($img0)?>]);
<?=$this->context['imageset']->jsObjectName()?>.addImageSwap(<?=$this->scaler?>);

</script>
<?
  }

  function HTMLParamsSection($sec,$htargs) {
    $srate=15;
    switch ($sec) {
      case 'zoom_in':
        $htargs['onClick']='onClick="'.$this->scaler.'.goScaleUp('.$srate.');"';
        if (!isset($htargs['class'])) $htargs['class']='class="image_scaler_up"';
	break;
      case 'zoom_out':
        $htargs['onClick']='onClick="'.$this->scaler.'.goScaleDown('.$srate.');"';
        if (!isset($htargs['class'])) $htargs['class']='class="image_scaler_down"';
	break;
      case 'zoom_reset':
        $htargs['onClick']='onClick="'.$this->scaler.'.goScale(1,'.$srate.');"';
        if (!isset($htargs['class'])) $htargs['class']='class="image_scaler_reset"';
	break;
      default:
    }
    return $htargs;
  }

  function renderSection($sec,&$body,$args) {
    $scaler='imgScaler_'.$this->makeID();
    switch ($sec) {
      case 'image':
        $this->width=$args['width'];
        $this->height=$args['height'];
	if (!$this->width) $this->width=160;
	if (!$this->height) $this->height=120;

?>
<div style="position:relative; overflow:hidden; width:<?=$this->width?>px; height:<?=$this->height?>px; padding:0;">
<div style="position:absolute; overflow:visible; padding:0; left:0; top:0; ">
<div id="img_<?=$scaler?>" onMouseMove="<?=$scaler?>.mouseMove(event,1); return false;" onMouseDown="<?=$scaler?>.mouseDown(event,1); return false;" onMouseUp="<?=$scaler?>.mouseUp(event,1); return false;" style="position:relative; overflow:visible; padding:0; margin:0;">
</div></div></div>
<?php

	$this->imgobj='img_'.$scaler;
	break;

case 'thumbs':
	$this->pwidth=$args['width'];
	$this->pheight=$args['height'];
	if (!$this->pwidth) $this->pwidth = 40;
	if (!$this->pheight) $this->pheight = 32;
	$ncells = $args['cols'];
	if (!$ncells) $ncells=4;
	$maximgs=$this->context['imageset']->getNumSlots();
	if (isset($args['max'])) $maximgs=min($maximgs,$args['max']);
	$this->slotobjs=Array();

?>
<table border="0" cellspacing="0" cellpadding="0" align="left">
<?php for ($i=0;$i<$maximgs;) { ?>
<tr>
<?php for ($j=0;$j<1 || $j<$ncells;$j++,$i++) { ?>
<td>
<?php if ($i<$maximgs) { ?>
<div id="<?=($this->slotobjs[]='slot_'.$scaler.'_'.$i)?>" onMouseDown="<?=$scaler?>.mouseDownPtr(event,<?=$i?>); return false;" onMouseMove="<?=$scaler?>.mouseMove(event,2); return false;" onMouseUp="<?=$scaler?>.mouseUp(event,2); return false;" style="position:relative; overflow:visible; width:<?=$this->pwidth?>px; height:<?=$this->pheight?>px; padding:0 2px 0 2px; z-index:0; left:0px;top:0px;" class="image_scaler_th_img">
<?	  if ($i==0) { ?>
<div id="<?=($this->ptrobj='ptr_'.$scaler)?>" onMouseMove="<?=$scaler?>.mouseMove(event,2); return false;" onMouseDown="<?=$scaler?>.mouseDown(event,2); return true;" onMouseUp="<?=$scaler?>.mouseUp(event,2); return false;" class="image_scaler_th_border" style="position:absolute; padding:0 2px 0 2px; margin:0; z-index:2; opacity:0.5; filter:alpha(opacity=50); background:#FFFFFF;"></div>
<?	  } ?>
</div>

<?      } else echo '&nbsp;' ?>
</td>
<?   } ?>
</tr>
<? } ?>
</table>
<?

        break;
      default:
        $this->renderBody($body);
    }
  }

  function renderOnce() {

	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
                echo '<script type="text/javascript" src="'. CDN_CONTENT .'/js/blocks/blk_image_scaler.js"></script>';
	} else {
		echo '<script type="text/javascript" src="/js/blocks/blk_image_scaler.js"></script>';
	}
  }


  function _js_quote($s) {
    if (!isset($s)) return 'null';
    if (is_array($s)) {
      $j=Array();
      foreach ($s AS $k=>$v) $j[]=$this->_js_quote($k).':'.$this->_js_quote($v);
      return '{'.join(',',$j).'}';
    }
    else if (is_int($s) || is_float($s)) return $s;
    return "'".preg_replace('|\r?\n|','\n',preg_replace("|\'|","\\'",preg_replace("|\\\\|","\\\\",$s)))."'";
  }
}
?>
