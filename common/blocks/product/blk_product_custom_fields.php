<?

class blk_product_custom_fields extends IXblock {

  function render($body) {
    global $attrSelPullDnOptns;
    if (!isset($attrSelPullDnOptns)) $attrSelPullDnOptns=Array();
    $optns=$this->context['models']->getAttrInfo();
    $this->optns=Array();
    foreach ($optns AS $optn=>$opinfo) if (isset($this->args['optns'])?isset($this->args['optns'][$optn]):!isset($attrSelPullDnOptns[$optn])) $this->optns[$optn]=$optn;
    if (!$this->optns) return;
    foreach ($this->optns AS $optn=>$ov) $attrSelPullDnOptns[$optn]=1;
    $prodjs=$this->context['models']->jsObjectName();
    $obj='attrSelector_'.$this->makeID();
    $selclass='attrPullDown';
?>
<table border="0" class="<?=$selclass?>">
<?
    foreach ($optns AS $optn=>$opinfo) if ($this->optns[$optn]) {
?>
<tr><td><?=$opinfo['name']?></td><td><select id="<?=$obj?>_<?=$optn?>" onChange="<?=$prodjs?>.selectAttr(<?=tep_js_quote($optn)?>,this.value);">
<option value="">--Select--</option>
<?   foreach ($opinfo['values'] AS $attr=>$atinfo) { ?><option value="<?=$attr?>"><?=$atinfo['name']?></option>
<?   } ?>
</select>
</td></tr>
<? } ?>
</table>

<script language="javascript">
window.<?=$obj?>={
  prod:<?=$prodjs?>,
  optns:<?=tep_js_quote($this->optns)?>,
  opSave:{},
  selectionChanged:function(curr,avl,avm,offstk) {
    for (var op in this.optns) {
//      var av=this.prod.getAvailableAttrs(op);
      var av=avl[op];
      var sel=$('<?=$obj?>_'+op);

// Fuck Bill Gates!
/*
      if (!this.opSave[op]) this.opSave[op]={};
      var oidx=1;
      for (var attr in this.prod.optns[op].values) {
	if (sel.options[oidx] && sel.options[oidx].value==attr) {
	  if (!av[attr]) sel.removeChild(this.opSave[op][attr]=sel.options[oidx]);
	  else oidx++;
	} else if (av[attr] && this.opSave[op][attr]) {
	  sel.insertBefore(this.opSave[op][attr],sel.options[oidx++]);
	  delete(this.opSave[op][attr]);
	}
      }
*/

      var f=false;
      for (var i=1;sel.options[i];i++) {
	sel.options[i].className=(av[sel.options[i].value]?'available':'inactive')+(offstk && offstk[op][sel.options[i].value]?' offstock':'');
//	sel.options[i].style.display=av[sel.options[i].value]?'':'none';
	if (sel.options[i].value==curr[op]) f=sel.options[i].selected=true;
      }
      if (!f) sel.options[0].selected=true;
    }
  }
};

<?=$prodjs?>.addAttrSelector(<?=$obj?>);
  
</script>

<?
  }


}
?>