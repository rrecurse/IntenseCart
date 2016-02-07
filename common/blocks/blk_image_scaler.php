<?


class blk_image_scaler extends IXblock {

  function render($body) {
    $scaler='imgScaler_'.$this->makeID();

    $width=$this->args['width'];
    $height=$this->args['height'];
    $pwidth=40;
    $pheight=50;

    $imgs=$this->context['imageset']->getImages();

    $imgjs=Array();
    $pimgjs=Array();
    foreach ($imgs AS $img) {
      if (isset($imgjs[$img])) continue;
      if (!isset($img0)) $img0=$img;
      $imgjs[$img]=Array();
      $sz=tep_get_image_info($img);
      $zoom=max($sz['width']/$width,$sz['height']/$height,1);
      if ($zoom<2) $zoom*=2;
      $nstp=floor(log($zoom)/log(2)+0.999);
      $stp=$nstp>0?exp(log($zoom)/$nstp):1;
      for ($i=0,$z=1;$i<=$nstp;$i++) {
	$imgjs[$img][]=Array('x1'=>0,'x2'=>1,'y1'=>0,'y2'=>1,'zoom'=>sprintf("%.3f",$z),'src'=>tep_image_src($img,floor($width*$z),floor($height*$z)));
	$z*=$stp;
      }
      $pimgjs[$img]=tep_image_src($img,$pwidth,$pheight);
    }
    $maximgs=$this->context['imageset']->getNumSlots();
    $ncells=4;
?>

<div style="position:relative; overflow:hidden; width:<?=$width?>px; height:<?=$height?>px; padding:0;">
<div style="position:absolute; overflow:visible; padding:0; left:0; top:0; ">
<div id="<?=$scaler?>" onMouseMove="<?=$scaler?>.mouseMove(event,1); return false;" onMouseDown="<?=$scaler?>.mouseDown(event,1); return false;" onMouseUp="<?=$scaler?>.mouseUp(event,1); return false;" style="position:relative; overflow:visible; padding:0; margin:0;">

</div></div></div>

<table cellpadding="0" cellspacing="0" class="image_scaler_table">
<tr>
<td style="padding-left:10px; padding-top:2px;"><a href="javascript:popupWindow('/index.php?products_id=<?=$this->context['product']->pid?>&popup=1,<?=LARGE_IMAGE_WIDTH+ULT_THUMB_IMAGE_WIDTH+POPUP_ADJUST_WIDTH?>,<?=LARGE_IMAGE_HEIGHT+POPUP_ADJUST_HEIGHT?>');" title="click to view enlarged photos of <?=$this->context['product']->products_name?>"><img src="/images/items/magglass_icon.gif" width="15" height="15" border="0" alt=""> &nbsp;<span style="line-height:20px; font-size:11px;">Enlarge Image</span></a></td>
<td><div class="image_scaler_up" onClick="<?=$scaler?>.goScaleUp(15);"></div></td>
<td><div class="image_scaler_down" onClick="<?=$scaler?>.goScaleDown(15);"></div></td>
<td><div class="image_scaler_reset" onClick="<?=$scaler?>.goScale(1,15);"></div></td></tr></table>


<table border="0" cellspacing="2" cellpadding="2" align="left">
<? for ($i=0;$i<$maximgs;) { ?>
<tr>
<?   for ($j=0;$j<1 || $j<$ncells;$j++,$i++) { ?>
<td>
<?      if ($i<$maximgs) { ?>
<div id="slot_<?=$scaler?>_<?=$i?>" onMouseDown="<?=$scaler?>.mouseDownPtr(event,<?=$i?>); return false;" onMouseMove="<?=$scaler?>.mouseMove(event,2); return false;" onMouseUp="<?=$scaler?>.mouseUp(event,2); return false;" style="position:relative; overflow:visible; width:<?=$pwidth?>px; height:<?=$pheight?>px; padding:0; z-index:0;">
<?	  if ($i==0) { ?>
<div id="ptr_<?=$scaler?>" onMouseMove="<?=$scaler?>.mouseMove(event,2); return false;" onMouseDown="<?=$scaler?>.mouseDown(event,2); return true;" onMouseUp="<?=$scaler?>.mouseUp(event,2); return false;" class="image_scaler_th_border" style="position:absolute; padding:0px; margin:0; z-index:2; opacity:0.5; filter:alpha(opacity=50); background:#FFFFFF;"></div>
<?	  } ?>
</div>
<?      } else echo '&nbsp;' ?>
</td>
<?   } ?>
</tr>
<? } ?>
</table>

<script type="text/javascript">

window.<?=$scaler?>={
  div:$('<?=$scaler?>'),
  ptrdiv:$('ptr_<?=$scaler?>'),
  width:<?=$width?>,
  height:<?=$height?>,
  pwidth:<?=$pwidth?>,
  pheight:<?=$pheight?>,
  x:0.5,
  y:0.5,
  zoom:1.0,
  targetZoom:1.0,
  images:<?=tep_js_quote($imgjs)?>,
  ptrImages:<?=tep_js_quote($pimgjs)?>,
  ptrElements:{0:{}},
  currImage:<?=tep_js_quote($img0)?>,
  currSlot:0,
  slotImages:[],
  moveTo:function(dx,dy,zoom,zr) {
  if (zoom==null) zoom=this.zoom; else this.zoom=zoom;
  if (!zr) zr=1;
  var rzoom=zoom/zr;
  if (rzoom<1) rzoom=1;
  if (zoom>1) {
    this.x+=dx/this.width/(zoom-1);
    this.y+=dy/this.height/(zoom-1);
  }
  if (this.x>1) this.x=1;
  if (this.y>1) this.y=1;
  if (this.x<0) this.x=0;
  if (this.y<0) this.y=0;
  var x=Math.floor(this.x*this.width*(zoom-1));
  var y=Math.floor(this.y*this.height*(zoom-1));
  var im;
  for (var i=0;im=this.images[this.currImage][i];i++) {
    if (im.zoom<=rzoom) {
      var x1=Math.floor(this.width*im.x1*zoom);
      var x2=Math.floor(this.width*im.x2*zoom);
      var y1=Math.floor(this.height*im.y1*zoom);
      var y2=Math.floor(this.height*im.y2*zoom);
      if ((x2>x || x1<x+this.width) && (y2>y || y1<y+this.height)) {
        if (!im.element) {
  	  im.element=document.createElement('img');
	  im.element.style.position='absolute';
	  im.element.src=im.src;
	  im.element.style.zInsex=im.zoom;
	  this.div.insertBefore(im.element,null);
	}
	im.element.width=x2-x1;
	im.element.height=y2-y1;
	im.element.style.left=x1+'px';
	im.element.style.top=y1+'px';
	im.element.style.display='';
      }
    } else if (im.element) im.element.style.display='none';
  }
  this.div.style.left=-x+'px';
  this.div.style.top=-y+'px';
  if (this.ptrdiv && this.ptrImages[this.currImage]) {
    this.ptrdiv.style.left=Math.floor(x/this.width/zoom*this.pwidth)+'px';
    this.ptrdiv.style.top=Math.floor(y/this.height/zoom*this.pheight)+'px';
    this.ptrdiv.style.width=Math.floor(this.pwidth/zoom)+'px';
    this.ptrdiv.style.height=Math.floor(this.pheight/zoom)+'px';
  }
},

  mouseDown:function(event,wh) {
    this.drag=wh;
    this.lastX=event.screenX;
    this.lastY=event.screenY;
    event.cancelBubble=true;
//    event.stopPropagation();
},

  mouseMove:function(event,wh) {
    if (this.drag!=wh) return;
    switch (wh) {
    case 1:
      this.moveTo(this.lastX-event.screenX,this.lastY-event.screenY);
      break;
    case 2:
      this.moveTo((event.screenX-this.lastX)*this.width/this.pwidth*this.zoom,(event.screenY-this.lastY)*this.height/this.pheight*this.zoom);
      break;
    }
    this.lastX=event.screenX;
    this.lastY=event.screenY;
},

  mouseUp:function(event,wh) {
    this.drag=0;
},

  mouseDownPtr:function(event,sl) {
    if (!this.slotSwap(sl)) return false;
    var x=event.offsetX!=null?event.offsetX:event.layerX;
    var y=event.offsetY!=null?event.offsetY:event.layerY;
    if (this.zoom>1) {
      this.x=0.5+(x-this.pwidth/2)*this.zoom/(this.zoom-1)/this.pwidth;
      this.y=0.5+(y-this.pheight/2)*this.zoom/(this.zoom-1)/this.pheight;
    }
    this.moveTo(0,0);
    this.mouseDown(event,2);
},

  goScale:function(zoom,steps) {
    this.targetZoom=zoom;
    var i=0;
    var sc=this;
    var intv=window.setInterval(function() {if (i<steps) sc.moveTo(0,0,sc.zoom*Math.exp(1/(steps-i++)*Math.log(zoom/sc.zoom)),3); else { sc.moveTo(0,0,zoom); clearInterval(intv);} },30);
},

  goScaleUp:function(steps) {
    var z=null;
    for (var i=0;this.images[this.currImage][i];i++) if (this.images[this.currImage][i].zoom>this.zoom+0.1 && (!z || this.images[this.currImage][i].zoom<z)) z=this.images[this.currImage][i].zoom;
    if (z) this.goScale(z,steps);
},

  goScaleDown:function(steps) {
    var z=null;
    for (var i=0;this.images[this.currImage][i];i++) if (this.images[this.currImage][i].zoom<this.zoom-0.1 && (!z || this.images[this.currImage][i].zoom>z)) z=this.images[this.currImage][i].zoom;
    if (z) this.goScale(z,steps);
},

  slotSwap:function(sl) {
    if (sl==this.currSlot) return true;
    if (this.slotImages[sl] && this.ptrdiv) {
      var blk=$('slot_<?=$scaler?>_'+sl);
      if (!blk) return false;
      blk.insertBefore(this.ptrdiv,null);
      this.zoom=this.targetZoom=1;
      this.flushImages();
      this.imageSelect(this.slotImages[sl]);
      this.currSlot=sl;
      if (!this.ptrElements[sl]) this.ptrElements[sl]={};
      return true;
    }
    return false;
},

  imageSelect:function(img) {
    if (!this.images[img]) return;
    for (var im in this.images) if (im!=img) {
      for (var i=0;this.images[im][i];i++) if (this.images[im][i].element) this.images[im][i].element.style.display='none';
    }
    this.currImage=img;
    var zoom=this.targetZoom;
    var lz=Math.log(zoom);
    var zdev=10;
    for (var i=0;this.images[img][i];i++) {
      var zd=Math.abs(Math.log(this.images[img][i].zoom)-lz);
      if (zd<zdev) {
        zdev=zd;
        zoom=this.images[img][i].zoom;
      }
    }
    this.moveTo(0,0,zoom);
  },

  flushImages:function() {
    for (var im in this.images) for (var i=0;this.images[im][i];i++) if (this.images[im][i].zoom>1 && this.images[im][i].element) this.images[im][i].element=(this.images[im][i].element.parentNode.removeChild(this.images[im][i].element),null);
  },

  imageSwap:function(imgs) {
    for (var i=0;$('slot_<?=$scaler?>_'+i);i++) {
      this.slotImages[i]=imgs[i];
      if (!this.ptrElements[i]) this.ptrElements[i]={};
      for (var im in this.images) if (im!=imgs[i]) {
        if (this.ptrElements[i][im]) this.ptrElements[i][im].style.display='none';
      }
      if (imgs[i] && this.images[imgs[i]]) {
        if (!this.ptrElements[i][imgs[i]]) {
          e=document.createElement('img');
          e.style.position='absolute';
          e.src=this.ptrImages[imgs[i]];
          e.style.zIndex=1;
          e.style.top=e.style.left='0px';
          e.width=this.pwidth;
          e.height=this.pheight;
          $('slot_<?=$scaler?>_'+i).insertBefore(e,null);
          this.ptrElements[i][imgs[i]]=e;
        }
        this.ptrElements[i][imgs[i]].style.display='';
      }
    }
    this.imageSelect(this.slotImages[this.currSlot]);
  }

};

<?=$scaler?>.imageSwap([<?=tep_js_quote($img0)?>]);

<?=$this->context['imageset']->jsObjectName()?>.addImageSwap(<?=$scaler?>);

</script>


<?
  }


}
?>
