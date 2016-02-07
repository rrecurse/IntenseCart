<?

class blk_product_xsell_list extends IXblock {

  function render(&$body) {
    global $languages_id;
    $pid=$this->pid=$this->context['product']->getProductField('products_id');
    $max=100;
    
    $ch=$this->args['channel'];
    if (!$ch) $ch='default';

    $this->xlist = tep_db_read("select p.*, pd.products_info, pd.products_info_alt, pd.products_name FROM " . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where xp.products_id = '$pid' and xp.xsell_id = p.products_id and p.master_products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_status = '1' AND xsell_channel='$ch' order by sort_order asc limit $max",'products_id');
    
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
      if (!($idx%$wd)) echo '<tr>';
      echo '<td>';
      $this->product_row=$cell;
      $this->renderBody($body);
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
<?
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
    global $currencies;
    switch ($var) {
      case 'products_image':
        if ($this->product_obj) $img=tep_db_read("SELECT image_file FROM products_images WHERE products_id='".$this->pid."' AND ref_id='".$this->product_obj->getProductField('products_id')."' AND image_group='linked' ORDER BY sort_order LIMIT 1",NULL,'image_file');
	if ($img) return tep_image(DIR_WS_CATALOG_IMAGES.$img,'',$args['width'],$args['height']);
	break;
      case 'xsell_price':
        $pf=new PriceFormatter;
	$pf->loadProduct($this->product_obj->getProductField('products_id'),$languages_id,NULL,$this->pid);
        $pf0=new PriceFormatter;
	$pf0->loadProduct($this->pid);
	return $currencies->format($pf->computePrice()+$pf0->computePrice());
      default: break;
    }
    if (!isset($this->product_obj)) return NULL;
    return $this->product_obj->getVar($var,$args);
  }
  function getXSellRef($pid) {
    return $this->pid;
  }
}
?>