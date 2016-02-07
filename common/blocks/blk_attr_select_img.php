<?

class blk_attr_select_img extends IXblock {

  function render($body) {
    $this->optn=$this->args['optn'];
    $attrinfo=$this->context['models']->getAttrInfo($this->optn);
    if (!$attrinfo) return;
    $imgct=0;
    foreach ($attrinfo AS $attr=>$ainfo) if ($ainfo['image']) $imgct++;
    if (!$imgct) return;
    $prodjs=$this->context['models']->jsObjectName();
    $obj='attrSelector_'.$this->makeID();
    $selclass='attrSelector';
    $width=6;
?>

<table border="0" class="<?=$selclass?>" cellpadding="0" cellspacing="0">
<?
   $idx=0;
   foreach ($attrinfo AS $attr=>$info) {
     if (!($idx%$width)) echo '<tr>';
?>
<td><div id="<?=$obj?>_<?=$attr?>" class="inactive" onClick="<?=$prodjs?>.selectAttr(<?=tep_js_quote($this->optn)?>,<?=tep_js_quote($attr)?>); return false;" onMouseOver="<?=$prodjs?>.previewAttr(<?=tep_js_quote($this->optn)?>,<?=tep_js_quote($attr)?>);" onMouseOut="<?=$prodjs?>.previewAttr(<?=tep_js_quote($this->optn)?>,null);"><?=$info['image']?tep_image(DIR_WS_IMAGES.$info['image'],$info['name']):$info['name']?></div></td>
<?
     if (!(++$idx%$width)) echo '</tr>';
   }
   if ($idx%$width) {
     if ($idx>$width) for (;$idx%$width;$idx++) echo '<td>&nbsp;</td>';
     echo '</tr>';
   }
?>
</table>

<script type="text/javascript">
window.<?=$obj?>={
  prod:<?=$prodjs?>,
  optn:<?=tep_js_quote($this->optn)?>,
  attrs:<?=tep_js_quote($attrinfo)?>,
  selectionChanged:function(curr,avl,avm,offstk) {
//    var sl={};
//    var av=this.prod.getAvailableAttrs(this.optn);
    var av=avl[this.optn];
    for (var a in this.attrs) $('<?=$obj?>_'+a).className=(av[a]?'available':(avm && avm[this.optn][a]?'halfactive':'inactive'))+(offstk && offstk[this.optn][a]?' offstock':'')+(curr[this.optn]==a?' selected':'');
  }
};

<?=$prodjs?>.addAttrSelector(<?=$obj?>);
  
</script>

<?
  }

}
?>