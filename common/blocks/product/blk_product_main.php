<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class blk_product_main extends IXblock {

  function setData($row) {
    global $languages_id;

    if (is_array($row)) {

      $this->product_row = $row;

    } else {

		$this->product_row_query = tep_db_query("SELECT pd.*, p.*, m.manufacturers_name, GROUP_CONCAT(spg.products_msrp separator ',') AS products_msrp
											  FROM products p 
											  LEFT JOIN products_description pd ON (pd.products_id=p.master_products_id AND pd.language_id = '".$languages_id."')
											  LEFT JOIN suppliers_products_groups spg ON p.products_id = spg.products_id
											  LEFT JOIN " . TABLE_MANUFACTURERS." m ON m.manufacturers_id = p.manufacturers_id
											  WHERE p.products_id = '".(int)$row."'
											 ");

		$this->product_row = tep_db_fetch_array($this->product_row_query);

		tep_db_free_result($this->product_row_query);

    }

    $this->pid=$this->product_row['products_id'];
  }

  function initContext() {
    if ($this->args['pid']) $this->setData($this->args['pid']);
    return true;
  }
  function getVar($var,$args) {
    global $currencies;
    switch ($var) {
      case 'products_image':
        return tep_image(DIR_WS_IMAGES.$this->product_row['products_image'],$this->product_row['products_name'],$args['width'],$args['height'],'itemprop="image"');
      case 'products_price':
        $pf=new PriceFormatter;
		$pf->loadProduct($this->product_row['products_id']);
        return $pf->displayPrice();
		//return $currencies->display_price($this->product_row['products_price'], tep_get_tax_rate($this->product_row['products_tax_class_id']));
     

case 'products_href':
	$argls = array();
	if ($args) foreach ($args AS $k=>$v) $argls[]=urlencode($k).'='.urlencode($v);
	if ($argls) {
		return htmlspecialchars(HTTP_SERVER.DIR_WS_CATALOG.'index.php?products_id='.$this->product_row['products_id'].'&'.join('&',$argls));
	}
	return tep_href_link('index.php',(isset($this->cPath)?'cPath='.join('_',$this->cPath).'&':'').'products_id='.$this->product_row['products_id'].($argls?'&'.join('&',$argls):''));

case 'products_oldprice': 
return $this->getOldPrice();


case 'products_msrp':

	// # strip any added html tags to the currency class (like structured data spans and meta tags).
	$theMSRP = $currencies->format($this->product_row['products_msrp']);
	$theMSRP = strip_tags(trim($theMSRP));

	if (isset($this->product_row['products_msrp']) && ($this->product_row['products_msrp'] > $this->product_row['products_price'] && $this->product_row['products_msrp'] > 0)) { 
		return	' <span class="products_msrpTitle">MSRP: </span> <span class="products_msrp">'.  $theMSRP .'</span>'; 
	} else { 
		return ''; 
	}

case 'manufacturers_name':
	if (isset($this->product_row['manufacturers_name'])) { 
		return	' <span class="products_manufacturersTitle">Manufacturer:</span> <span class="manufacturers_name" itemprop="brand">'.  $this->product_row['manufacturers_name'].'</span>'; 
	} else { 
		return ''; 
	}
      default:
        if (isset($this->product_row[$var])) return $this->product_row[$var];
    }
    return NULL;
  }
  function getOldPrice() {
    $pf = new PriceFormatter;
    $pf->loadProduct($this->product_row['products_id']);
    if (!$pf->hasSpecialPrice) return NULL;
    return $pf->displayPrice(NULL,true);
  }
  function preRenderSection($sec,&$body,$args) {
    if (preg_match('/^if_(.*)/',$sec,$scp)) return isset($this->product_row[$scp[1]]) && $this->product_row[$scp[1]];
    switch ($sec) {
      case 'oldprice': return $this->getOldPrice()!='';
      default: return NULL;
    }
  }
  function getProductField($fld) {
    return $this->product_row[$fld];
  }
  function getPath() {
    return $GLOBALS['cPath']=='0'?Array():preg_split('/_/',$GLOBALS['cPath']);
  }
  function exportContext() {
    $ctxt=$this->context;
    $ctxt['product']=&$this;
    return $ctxt;
  }
  function requireContext() {
    return array();
  }
  function getAjaxContext() {
    return array('pid'=>$this->pid);
  }
}
?>
