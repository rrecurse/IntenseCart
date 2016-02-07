<?

class blk_product_attr_select extends IXblock {

  function render(&$body) {
    $optns=$this->context['models']->getAttrInfo();
    $this->optns=Array();
    $oplst=isset($this->args['optns'])?preg_split('/:/',$this->args['optns']):NULL;
    foreach ($optns AS $optn=>$opinfo) if ($oplst?in_array($optn,$oplst):(!$this->args['norepeat']||!isset($this->context['models']->attrSelPullDnOptns[$optn]))) $this->optns[$optn]=$optn;
    if (!$this->optns) {
      $this->renderBody($body);
      return;
    }
    foreach ($this->optns AS $optn=>$ov) $this->context['models']->attrSelPullDnOptns[$optn]=1;
    $this->prodjs=$this->context['models']->jsObjectName();
    $this->obj='attrSelector_'.$this->makeID();
    $selclass='attrPullDown';
    $this->init_js=Array();
    $this->secidx=1;
    foreach ($optns AS $this->optn=>$this->opinfo) if (isset($this->optns[$this->optn])) $this->renderBody($body);
    if ($this->init_js) {
?>
<script type="text/javascript">
<?    foreach ($this->init_js AS $js) { ?>
  <?=$this->prodjs?>.addAttrSelector(<?=$js?>);
<?    } ?>
</script>
<?
    }
  }
  
  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'pulldn':
      case 'name':
      case 'swatch':
      case 'content':
        return isset($this->optn);
      case 'nocontent':
        return !isset($this->optn);
      default: return true;
    }
  }
  
  function renderSection($sec,&$body,$args) {
    $secid=$this->obj.'_'.($this->secidx++);
    switch ($sec) {
      case 'pulldn':
        $nulltext=isset($args['nulltext'])?$args['nulltext']:'--Select--';
	$selclass=isset($args['selclass'])?$args['selclass']:'attrPullDown';
?>
<select id="<?=$secid?>" onChange="<?=$this->prodjs?>.selectAttr(<?=tep_js_quote($this->optn)?>,this.value);">
<option value=""><?=$nulltext?></option>
<?   foreach ($this->opinfo['values'] AS $attr=>$atinfo) { ?><option value="<?=$attr?>"><?=htmlspecialchars($atinfo['name'])?></option>
<?   } ?>
</select>
<?
	$this->init_js[]="new prodAttrSelectPullDn(".tep_js_quote($secid).",".tep_js_quote($this->optn).")";
	break;
      case 'name':
	$selclass=isset($args['selclass'])?$args['selclass']:'attrName';
?>
<?   foreach ($this->opinfo['values'] AS $attr=>$atinfo) { ?><span id="<?=$secid?>_<?=$attr?>"><?=htmlspecialchars($atinfo['name'])?></span>
<?   } ?>
<?
	$this->init_js[]="new prodAttrSelectName(".tep_js_quote($secid).",".tep_js_quote($this->optn).",".tep_js_quote($this->opinfo['values']).")";
	break;
      case 'swatch':
        $width=$args['cols']+0;
	$selclass=isset($args['selclass'])?$args['selclass']:'attrSelector';
?>
<table border="0" class="<?=$selclass?>" cellpadding="0" cellspacing="0">
<?
   $idx=0;
   foreach ($this->opinfo['values'] AS $attr=>$info) {
     if ($width?!($idx%$width):!$idx) echo '<tr>';
?>
<td><div id="<?=$secid?>_<?=$attr?>" class="inactive" onClick="<?=$this->prodjs?>.selectAttr(<?=tep_js_quote($this->optn)?>,<?=tep_js_quote($attr)?>); return false;" onMouseOver="if (window.<?=$this->prodjs?>) <?=$this->prodjs?>.previewAttr(<?=tep_js_quote($this->optn)?>,<?=tep_js_quote($attr)?>); this.className+=' hover';" onMouseOut="if (window.<?=$this->prodjs?>) <?=$this->prodjs?>.previewAttr(<?=tep_js_quote($this->optn)?>,null);"><?=$info['image']?tep_image(DIR_WS_IMAGES.$info['image'],$info['name'],$args['width'],$args['height']):$info['name']?></div></td>
<?
     $idx++;
     if ($width && !($idx%$width)) echo '</tr>';
   }
   if (!$width || $idx%$width) {
     if ($width && $idx>$width) for (;$idx%$width;$idx++) echo '<td>&nbsp;</td>';
     echo '</tr>';
   }
?>
</table>
<?
	$this->init_js[]="new prodAttrSelectSwatch(".tep_js_quote($secid).",".tep_js_quote($this->optn).",".tep_js_quote($this->opinfo['values']).")";
        break;      
      default: $this->renderBody($body);

   }
  }
  
  function renderOnce() {
?>
<script type="text/javascript">
window.prodAttrSelectPullDn=function(obj,optn) {
  return {
  obj:obj,
  optn:optn,
  selectionChanged:function(curr,avl,avm,offstk) {
    var av=avl[this.optn];
    var sel=$(this.obj);
    var f=false;
    for (var i=1;sel.options[i];i++) {
      sel.options[i].className=(av[sel.options[i].value]?'available':'inactive')+(offstk && offstk[this.optn][sel.options[i].value]?' offstock':'');
      if (sel.options[i].value==curr[this.optn]) f=sel.options[i].selected=true;
    }
    if (!f) sel.options[0].selected=true;
  }
  };
}
window.prodAttrSelectSwatch=function(obj,optn,attrs) {
  return {
  obj:obj,
  optn:optn,
  attrs:attrs,
  selectionChanged:function(curr,avl,avm,offstk) {
    var av=avl[this.optn];
    for (var a in this.attrs) $(this.obj+'_'+a).className=(av[a]?'available':(avm && avm[this.optn][a]?'halfactive':'inactive'))+(offstk && offstk[this.optn][a]?' offstock':'')+(curr[this.optn]==a?' selected':'');
  }
  };
}
window.prodAttrSelectName=function(obj,optn,attrs) {
  return {
  obj:obj,
  optn:optn,
  attrs:attrs,
  selectionChanged:function(curr,avl,avm,offstk) {
//  alert(curr);
    for (var a in this.attrs) $(this.obj+'_'+a).style.display=(curr[this.optn]==a?'':'none');
  }
  };
}
</script>
<?
  }
  function requireContext() {
    return Array('models');
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'attr_name': return $this->opinfo['name'];
      default: return NULL;
    }
  }
}
?>
