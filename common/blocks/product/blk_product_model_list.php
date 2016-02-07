<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class blk_product_model_list extends IXblock {

  function render(&$body) {
    global $languages_id;
    $pid=$this->pid=$this->context['product']->getProductField('products_id');
    $max=100;
    $this->mdls = tep_db_read("SELECT pd.*,p.*, m.manufacturers_name from ".TABLE_PRODUCTS." p LEFT JOIN products_description pd ON (pd.products_id=p.master_products_id AND pd.language_id='$languages_id') LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id WHERE p.master_products_id='$pid'",'products_id');
    if (!$this->mdls) return;
    if (sizeof($this->mdls)>1) unset($this->mdls[$pid]);
    $qry = tep_db_query("SELECT pa.*,ov.* FROM products_attributes pa LEFT JOIN products_options_values ov ON (pa.options_values_id=ov.products_options_values_id AND ov.language_id='$languages_id') WHERE pa.products_id IN (".join(',',array_keys($this->mdls)).")");
    while ($row=tep_db_fetch_array($qry)) {
      $this->mdls[$row['products_id']]['attrs'][]=$row['products_options_values_name'];
    }
    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'content':
      case 'table':
        return $this->mdls && true;
      case 'nocontent':
        return !$this->mdls;
      default: return true;
    }
  }
  
  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'table':
        $this->renderListing($this->mdls,$this->args['cols'],$this->args['max'],$body);
	break;
      default: $this->renderBody($body);
    }
  }
    
  function renderListing($lst,$wd,$max,&$body) {
    $idx=0;
?>
<table border="0" cellspacing="0" cellpadding="0">
<?php
    foreach ($lst AS $cell) {
      if ($idx >= $max && $max > 0) break;
      if (!($idx%$wd)) echo '<tr>';
      $this->product_row=$cell;
      echo '<td>';
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
<?php
  }
  
  function getNumSlots() {
    return 4;
  }
  function exportContext() {
    $ctxt=$this->context;
    $this->product_obj=$this->block('blk_product_main');
    $this->product_obj->setContext($this->context,Array());
    $this->product_obj->setData($this->product_row);
    $ctxt['main_product']=&$ctxt['product'];
    $ctxt['product']=&$this->product_obj;
    $ctxt['product_model']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'attributes':
        if (!$this->product_row['attrs']) return '';
        return join(isset($args['sep'])?$args['sep']:',',$this->product_row['attrs']);

	case 'products_price':

	$product_check = tep_db_query("SELECT products_price FROM " . TABLE_PRODUCTS . " WHERE products_id = '". (int)$this->pid ."'");
	$prod = tep_db_fetch_array($product_check);

		if($prod['products_price'] > 0) { 
			return '<span id="'.$this->jsMakeField('price').'"></span><time style="display:none;" itemprop="priceValidUntil" datetime="'.date('Y-m-d', strtotime('+1 year')).'">'. date('Y-m-d', strtotime('+1 year')).'</time>';
		} else {
			return '<span>-</span>';
		}
      default:
        if (!isset($this->product_obj)) return NULL;
        return $this->product_obj->getVar($var,$args);
    }
  }
}
?>
