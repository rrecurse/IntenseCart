<?php

class blk_image_telescope extends IXblock {

  function render(&$body) {
    $this->scaler='imgScaler_'.$this->makeID();

  
    $this->renderBody($body);
    if (!isset($this->imgobj)) return;

    $this->fadeRate=$this->args['faderate']+0;
    if (!$this->fadeRate) $this->fadeRate=0.2;
    
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
<script type="text/javascript" src="/js/blocks/blk_image_telescope.js"></script>
<script type="text/javascript">

window.<?php echo $this->scaler?>=new imageScalerObj({
  div:$('<?php echo $this->imgobj?>'),
  width:<?php echo $this->width?>,
  height:<?php echo $this->height?>,
  fadeRate:<?php echo $this->fadeRate+0?>,
  telFrameOffs:2,
<?php  if (isset($this->ptrobj)) { ?>
  ptrdiv:$('<?php echo $this->ptrobj?>'),
  pwidth:<?php echo $this->pwidth?>,
  pheight:<?php echo $this->pheight?>,
<?php  } ?>
  ptrSlot:[<?php $sls=Array(); if (isset($this->slotobjs)) foreach ($this->slotobjs AS $sl) $sls[]="$('$sl')"; echo join(',',$sls); ?>],
  images:<?php echo $this->_js_quote($imgjs)?>,
  ptrImages:<?php echo tep_js_quote($pimgjs)?>,
  currImage:<?php echo tep_js_quote($img0)?>
});

<?php
	if ($this->telDiv) { ?>
  <?php echo $this->scaler?>.telDiv=$('<?php echo $this->telDiv?>');
  <?php echo $this->scaler?>.telMask=$('mask_imgScaler_<?php echo $this->makeID()?>');
  <?php echo $this->scaler?>.telDivW=$(<?php echo $this->telDivW+0?>);
  <?php echo $this->scaler?>.telDivH=$(<?php echo $this->telDivH+0?>);
<?php if (isset($this->telFrameMin)) { ?>
  <?php echo $this->scaler?>.telFrameMin=<?php echo $this->telFrameMin+0?>;
<?php } ?>

<?php } ?>

<?php echo $this->scaler?>.imageSwap([<?php echo tep_js_quote($img0)?>]);
<?php echo $this->context['imageset']->jsObjectName()?>.addImageSwap(<?php echo $this->scaler?>);

</script>
<?php
  }

  function HTMLParamsSection($sec,$htargs) {
    $srate=15;
    switch ($sec) {
      case 'telescope':
        $htargs['id']='id="tel_imgScaler_'.$this->makeID().'"';
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
<div style="position:relative; overflow:hidden; width:<?php echo $this->width?>px; height:<?php echo $this->height?>px; left:0px; top:0px; padding:0;" onMouseMove="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseMove(event,1); return false;" onMouseDown="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseDown(event,1); return false;" onMouseUp="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseUp(event,1); return false;" onMouseOut="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseOut(event); return false;">
<img id="img_<?php echo $scaler?>"  width="<?php echo $this->width?>" height="<?php echo $this->height?>" style="position:absolute;left:0px;top:0px;z-index:0" onMouseMove="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseMove(event,1); return false;" onMouseDown="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseDown(event,1); return false;" onMouseUp="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseUp(event,1); return false;" onMouseOut="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseOut(event); return false;">
<img src="/images/pixel_trans.gif" width="<?php echo $this->width?>" height="<?php echo $this->height?>" style="position:absolute;left:0;top:0;z-index:2;" onMouseMove="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseMove(event,1); return false;" onMouseDown="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseDown(event,1); return false;" onMouseUp="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseUp(event,1); return false;" onMouseOut="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseOut(event); return false;">

<div id="mask_<?php echo $scaler?>" style="position:absolute; padding:0; left:0; top:0; display:none; overflow:visible; z-index:1;" onMouseDown="return false;" onMouseMove="return false;">
<table cellspacing="0" cellpadding="0" border="0">
<tr>
  <td align="center"><div class="telescopeMask telescopeMask_tl" style="width:<?php echo $this->width?>px; height:<?php echo $this->height?>px;"></div></td>
  <td align="center"><div class="telescopeMask telescopeMask_t" style="width:10px; height:<?php echo $this->height?>px;"></div></td>
  <td align="center"><div class="telescopeMask telescopeMask_tr" style="width:<?php echo $this->width?>px; height:<?php echo $this->height?>px;"></div></td>
</tr>
<tr>
  <td align="center"><div class="telescopeMask telescopeMask_l" style="width:<?php echo $this->width?>px; height:1px;"></div></td>
  <td align="center"><div class="telescopeFrame" style="width:10px; height:1px;"></div></td>
  <td align="center"><div class="telescopeMask telescopeMask_r" style="width:<?php echo $this->width?>px; height:1px;"></div></td>
</tr>
<tr>
  <td align="center"><div class="telescopeMask telescopeMask_bl" style="width:<?php echo $this->width?>px; height:<?php echo $this->height?>px;"></div></td>
  <td align="center"><div class="telescopeMask telescopeMask_b" style="width:10px; height:<?php echo $this->height?>px;"></div></td>
  <td align="center"><div class="telescopeMask telescopeMask_br" style="width:<?php echo $this->width?>px; height:<?php echo $this->height?>px;"></div></td>
</tr>
</table>
</div>

</div>
<?php
      
        $this->imgobj='img_'.$scaler;
        break;
      case 'thumbs':
        $this->pwidth=$args['width'];
        $this->pheight=$args['height'];
	if (!$this->pwidth) $this->pwidth=40;
	if (!$this->pheight) $this->pheight=32;
	$ncells=$args['cols'];
	if (!$ncells) $ncells=4;
	$maximgs=$this->context['imageset']->getNumSlots();
	if (isset($args['max'])) $maximgs=min($maximgs,$args['max']);
	$this->slotobjs=Array();

?>
<table border="0" cellspacing="2" cellpadding="2" align="left">
<?php for ($i=0;$i<$maximgs;) { ?>
<tr>
<?php   for ($j=0;$j<1 || $j<$ncells;$j++,$i++) { ?>
<td>
<?php      if ($i<$maximgs) { ?>
<div id="<?php echo ($this->slotobjs[]='slot_'.$scaler.'_'.$i)?>" onMouseDown="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseDownPtr(event,<?php echo $i?>); return false;" onMouseUp="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseUp(event,2); return false;" style="position:relative; overflow:visible; width:<?php echo $this->pwidth?>px; height:<?php echo $this->pheight?>px; padding:0; z-index:0; left:0px;top:0px;">
<?	  if ($i==0) { ?>
<div id="<?php echo ($this->ptrobj='ptr_'.$scaler)?>" onMouseDown="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseDown(event,2); return true;" onMouseUp="if (window.<?php echo $scaler?>) <?php echo $scaler?>.mouseUp(event,2); return false;" class="image_scaler_th_border" style="position:absolute; padding:0px; margin:0; z-index:2; opacity:0.5; filter:alpha(opacity=50); background:#FFFFFF;"></div>
<?	  } ?>
</div>
<?php      } else echo '&nbsp;' ?>
</td>
<?php   } ?>
</tr>
<?php } ?>
</table>
<?php

        break;
      case 'telescope':
        $this->telDivW=$args['width'];
        $this->telDivH=$args['height'];
	$this->telFrameMin=$args['min'];
	$this->telDiv='tel_'.$scaler;
?>
<div style="position:relative; top:0px;left:0px;width:<?php echo $this->telDivW?>px; height:<?php echo $this->telDivH?>px; overflow:hidden">
<img style="position:absolute;">
</div>
<?php
	break;
      default:
        $this->renderBody($body);
    }
  }

  function renderOnce() {
?>

<?php
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