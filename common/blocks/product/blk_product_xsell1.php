<?

class blk_product_xsell1 extends IXblock {

  function jsObjectName() {
    return 'XSell_'.$this->makeID();
  }

  function render(&$body) {
    global $languages_id;
    $pid=$this->pid=isset($this->context['models'])?$this->context['models']->master_pid:$this->context['product']->getProductField('products_id');
    $max=100;
    if(!tep_session_is_registered('sppc_customer_group_id')) {
      $customer_group_id = 0;
    } else {
      $customer_group_id = $_SESSION['sppc_customer_group_id'];
    }
    
    $ch=$this->args['channel'];
    if (!$ch) $ch='default';

    if ($customer_group_id != 0) {
      $xsell_query = tep_db_query("select mp.products_id AS ref_pid,p.*, pd.products_info, pd.products_info_alt, pd.products_name, IF(pg.customers_group_price IS NOT NULL, pg.customers_group_price, p.products_price) as products_price from ".TABLE_PRODUCTS." mp," . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_GROUPS . " pg using(products_id), " . TABLE_PRODUCTS_DESCRIPTION . " pd where mp.master_products_id='$pid' AND xp.products_id = mp.products_id and xp.xsell_id = p.products_id and p.master_products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_status = '1' and pg.customers_group_id = '".$customer_group_id."' AND xsell_channel='$ch' order by sort_order asc limit $max");
    } else {
      $xsell_query = tep_db_query("select mp.products_id AS ref_pid,p.*, pd.products_info, pd.products_info_alt, pd.products_name from ".TABLE_PRODUCTS." mp," . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where mp.master_products_id='$pid' AND xp.products_id=mp.products_id and xp.xsell_id = p.products_id and p.master_products_id = pd.products_id and pd.language_id = '$languages_id' and p.products_status = '1' AND xsell_channel='$ch' order by sort_order asc limit $max");
    }
    
    $xs=Array();
    $xd=Array();
    
    $this->xrefs=Array();
    while ($row=tep_db_fetch_array($xsell_query)) {
//      print_r($row);
      $this->xrefs[$row['products_id']][]=$row['ref_pid'];
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
    $this->xlist=array_merge($xs,$xds);
//    print_r($this->xlist);
    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'content':
      case 'table':
        return $this->xlist && true;
      case 'nocontent':
        return !$this->xlist;
      default: return true;
    }
  }
  
  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'table':
        $this->renderListing($this->xlist,$this->args['cols'],$this->args['max'],$body);
	break;
      default: $this->renderBody($body);
    }
  }
    
  function renderListing($lst,$wd,$max,&$body) {
    $idx=0;
?>
<table border="0" cellspacing="0" cellpadding="0">
<?
    foreach ($lst AS $cell) {
      if ($idx>=$max && $max>0) break;
      if (!($wd?$idx%$wd:$idx)) echo '<tr>';
      echo '<td id="'.$this->jsObjectName().'_'.$idx.'">';
      foreach ($cell AS $k=>$cswp) {
	$this->product_row=$cswp;
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
      if ($wd && !($idx%$wd)) echo '</tr>';
    }
    if (!$wd || $idx%$wd) {
      if ($wd) for (;$idx%$wd;$idx++) echo '<td>&nbsp;</td>';
      echo '</tr>';
    }
?>
</table>
<?   if (isset($this->context['models'])) { ?>
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
  <?=$this->context['models']->jsObjectName()?>.addProductSwap(<?=$this->jsObjectName()?>);
</script>
<?
    }
    $this->product_row=NULL;
  }
  
  function getNumSlots() {
    return 4;
  }
  function exportContext() {
    $ctxt=$this->context;
    if (isset($this->product_row)) {
      $this->product_obj=$this->block('blk_product_main');
      $this->product_obj->setContext($this->context,Array());
      $this->product_obj->setData($this->product_row);
      $ctxt['main_product']=&$ctxt['product'];
      $ctxt['product']=&$this->product_obj;
    }
    $ctxt['xsell']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'products_image':
        $img=tep_db_read("SELECT image_file FROM products_images WHERE products_id='".$this->pid."' AND ref_id='".$this->product_obj->getProductField('products_id')."' AND image_group='linked' ORDER BY sort_order LIMIT 1",NULL,'image_file');
	if ($img) return tep_image($img,'',$args['width'],$args['height']);
      default:
        if (!isset($this->product_obj)) return NULL;
        return $this->product_obj->getVar($var,$args);
    }
  }
  function getXSellRef($pid) {
    return $this->xrefs[$pid];
//    echo $this->product_row['ref_pid'];
//    return $this->product_row['ref_pid'];
  }
}
?>