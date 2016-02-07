<?php

class blk_xsell extends IXblock {

  function jsObjectName() {
    return 'XSell_'.$this->makeID();
  }

  function render($body) {
    global $languages_id;
    $pid=$this->context['productset']->master_pid;
    $max=100;

	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

	if ($customer_group_id > '1') {

      $xsell_query = tep_db_query("SELECT mp.products_id AS ref_pid,p.products_id, 
										  p.products_image, 
										  pd.products_info, 
										  pd.products_info_alt, 
										  pd.products_name, 
										  p.products_tax_class_id, 
										  p.products_price 
								  FROM ".TABLE_PRODUCTS." mp, " . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd
								  LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON (pg.products_id = p.products_id AND customers_group_id = '". $customer_group_id . "')
								  WHERE mp.master_products_id = '". $pid ."' 
								  AND xp.products_id = mp.products_id 
								  AND xp.xsell_id = p.products_id
								  AND p.master_products_id = pd.products_id 
								  AND pd.language_id = '". $languages_id ."' 
								  AND p.products_status = '1'
								  AND (p.products_price > 0 OR pg.customers_group_price > 0)
								  GROUP BY p.products_id 
								  ORDER BY sort_order ASC
								  LIMIT " .$max);

	} else {

      $xsell_query = tep_db_query("SELECT mp.products_id AS ref_pid,p.products_id, 
										  p.products_image, 
										  pd.products_info, 
										  pd.products_info_alt, 
										  pd.products_name, 
										  p.products_tax_class_id, 
										  p.products_price 
								  FROM ".TABLE_PRODUCTS." mp, " . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd 
								  WHERE mp.master_products_id = '". $pid ."' 
								  AND xp.products_id = mp.products_id 
								  AND xp.xsell_id = p.products_id
								  AND p.master_products_id = pd.products_id 
								  AND pd.language_id = '". $languages_id ."' 
								  AND p.products_status = '1'
								  AND p.products_price > 0  
								  ORDER BY sort_order ASC
								  LIMIT " .$max);
	}

    $xs = array();
    $xd = array();
    
    while ($row=tep_db_fetch_array($xsell_query)) {
      if ($row['ref_pid']==$pid) $xs[]=Array(''=>$row); else {
        for ($i=0;;$i++) {
	  if (!isset($xd[$i])) $xd[$i]=Array();
	  if (!isset($xd[$i][$row['ref_pid']])) {
	    $xd[$i][$row['ref_pid']]=$row;
	    break;
	  }
	}
      }
    }
    $xds=Array();
    foreach ($xd AS $idx=>$xdr) {
      $xdt=Array();
      $xpr=Array();
      $xds[$idx]=Array();
      foreach ($xdr AS $rid=>$row) {
        if (!isset($xpr[$row['products_id']])) {
	  $xpr[$row['products_id']]=$rid;
	  $xdt[$rid]=Array($rid);
	} else $xdt[$xpr[$row['products_id']]][]=$rid;
      }
      foreach ($xpr AS $pid=>$rid) $xds[$idx][join('_',$xdt[$rid])]=$xd[$idx][$rid];
    }
    if ($xs || $xds) {
?>
<div class="xSellprodListing_masterTitle"><?=XSELL_PRODUCTS_MAINTITLE?></div>
<?
      $this->renderListing(array_merge($xs,$xds),$this->args['cols'],$this->args['max'],$body);
    }
  }
    
  function renderListing($lst,$wd,$max,&$body) {
    $idx=0;
?>
<table border="0" cellspacing="0" cellpadding="0">
<?
    foreach ($lst AS $cell) {
      if ($idx>=$max && $max>0) break;
      if (!($idx%$wd)) echo '<tr>';
      echo '<td id="'.$this->jsObjectName().'_'.$idx.'">';
      foreach ($cell AS $k=>$cswp) {
	$this->context['datarow']=$cswp;
	if ($k!='') {
?>
<div id="<?=$this->jsObjectName()?>_<?=$idx?>_<?=$k?>" style="display:none;"><?
	  $this->renderBody($body);
?></div>
<?
        } else $this->renderBody($body);
      }
      $idx++;
      echo '</td>';
      if (!($idx%$wd)) echo '</tr>';
    }
    if ($idx%$wd) {
      for (;$idx%$wd;$idx++) echo '<td>&nbsp;</td>';
      echo '</tr>';
    }
?>
</table>
<script type="text/javascript">
  window.<?=$this->jsObjectName()?>={
    id:'<?=$this->jsObjectName()?>',
    productSwap:function(pid) {
      var b;
      for (var i=0;b=$(this.id+'_'+i);i++) {
	var regx=RegExp('^'+this.id+'_'+i+'_(.*_)?'+pid+'(_|$)');
        for (var e=b.firstChild;e;e=e.nextSibling) if (e.id) e.style.display=(e.id.match(regx)?'':'none');
      }
    }
  };
  <?=$this->context['productset']->jsObjectName()?>.addProductSwap(<?=$this->jsObjectName()?>);
</script>
<?
  }
  
  function getNumSlots() {
    return 4;
  }
}
?>