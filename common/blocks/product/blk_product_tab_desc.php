<?php
class blk_product_tab_desc extends IXblock {
	function renderOnce() {
?>
<script type="text/javascript">
	function tabDescSwitch(td) {
		var tr=td.parentNode;
		var idx=null;
		var i=0;
		for (var t=tr.firstChild;t;t=t.nextSibling) if (t.tagName==td.tagName) {
			t.className=(t==td && ((idx=i) || true))?'tabOpen':'tabClosed';
		    i++;
		}
		for (var d=tr;d;d=d.parentNode) if (d.tagName=='DIV') break;
		i=0;
		if (d) for (var e=d.firstChild;e;e=e.nextSibling) if (e.tagName=='DIV') {
			e.style.display=i==idx?'':'none';
		    i++;
		}
	}
</script>
<?php
	}

	function render(&$body) {
    	$fld=isset($this->args['field'])?$this->args['field']:'products_description';
	    $val=$this->context['product']->getProductField($fld);
    	if (preg_match_all('|<newtab>(.*?)</newtab>|s',$val,$ts)) {
?>
<table cellpadding="0" cellspacing="0" border="0" class="productDesc_tabs">
	<tr>
<?php
      foreach ($ts[1] AS $idx=>$tsd) {
        preg_match('|<tabname>(.*?)</tabname>|s',$tsd,$tname);
?>
<td class="<?=$idx?'tabClosed':'tabOpen'?>" onClick="tabDescSwitch(this); return false;"><?php echo $tname[1]?></td>
<?php
      }
      $this->display='tabs';
      $this->renderBody($body);
?>
</tr></table>
<?php
      foreach ($ts[1] AS $idx=>$tsd) {
        preg_match('|<tabtext>(.*?)</tabtext>|s',$tsd,$tcont);
?>
<div style="<?php echo ($idx ? 'display:none' : '')?>" class="tabContent"><?php echo $this->renderBody($this->parse($tcont[1]))?></div>
<?php
      }
    } else echo $val;
    $this->display='content';
    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'tab':
        if ($this->display=='tabs') {
?>
<td class="tabClosed" onClick="tabDescSwitch(this); return false;"><?
	  $this->renderBody($body);
	  echo '</td>';
	}
	return false;
      case 'content':
        return $this->display=='content';
      default: return false;
    }
  }
}
?>