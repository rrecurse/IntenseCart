<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class blk_product_category extends IXblock {

	function jsObjectName() {
		return 'prodListing_'.$this->makeID();
	}

	function render(&$body) {
	    global $languages_id;
	$max = $this->args['max'];
	$catid = isset($this->args['cid']) ? $this->args['cid'] : $this->context['category']->cid;
	$sort = $GLOBALS['HTTP_GET_VARS']['sort'];

	switch ($sort) {
		case 'price': 
			$order="products_price"; 
		break;

		case 'price-': 
			$order="products_price DESC"; 
		break;

		case 'name': 
			$order="products_name"; 
		break;

		case 'name-': 
			$order="products_name DESC"; 
		break;

		default: 
			$order="p.products_sort_order, p.products_id"; 
		break;
	}

	$this->listing_sql = "SELECT pd.*, 
								 p.products_image, 
								 p.products_id, 
								 p.manufacturers_id, 
								 p.products_price, 
								 p.products_tax_class_id, 
								 IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, 
								 IF(s.status, s.specials_new_products_price, p.products_price) as final_price, 
								 m.manufacturers_name 
						   FROM " . TABLE_PRODUCTS . " p 
						   LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd (ON pd.products_id = p.products_id AND pd.language_id = '" . (int)$languages_id . "')
						   LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id 
						   LEFT JOIN " . TABLE_SPECIALS_RETAIL_PRICES . " s ON s.products_id = p.products_id
						   LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id = p.products_id
						   WHERE p.products_status = '1'
						   AND pd.language_id = '" . (int)$languages_id . "
						   AND p2c.categories_id = '" . (int)$catid . "' 
						   ORDER BY $order";

    $this->listing_split = new splitPageResults($this->listing_sql, $max, 'p.products_id');
    $this->renderBody($body);
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'table':
        $this->renderListing(tep_db_read($this->listing_sql,Array(NULL)),$body,$this->args['cols'],$this->args['max']);
	break;
      case 'count':
        echo $this->listing_split->display_count(TEXT_DISPLAY_NUMBER_OF_PRODUCTS);
	break;
      case 'pages':
	echo TEXT_RESULT_PAGE . ' ' . $this->listing_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))).'<span style="prodListing_topPageCount_showall">&nbsp;<a href="'.tep_href_link(basename($PHP_SELF),tep_get_all_get_params(array('page', 'info', 'x', 'y')).($listing_split->current_page_number?'page=all':'')).'" class="prodListing_topPageCount_showall">'.($this->listing_split->current_page_number?'View All':'Show Pages');
	break;
      default: $this->renderBody($body);
    }
  }
  
  function setListingElement(&$row) {
    $this->product_row=&$row;
  }
  
  function renderListing($lst,&$body,$cols=NULL,$max=NULL) {
    $idx=0;
?>
<table border="0" cellspacing="0" cellpadding="0">
<?php
    foreach ($lst AS $cell) {
      if ($max && $idx>=$max) break;
      if (!($cols?($idx%$cols):$idx)) echo '<tr>';
      echo '<td id="'.$this->jsObjectName().'_'.$idx.'">';
      $this->setListingElement($cell);
      $this->renderBody($body);
      $idx++;
      echo '</td>';
      if ($cols && !($idx%$cols)) echo '</tr>';
    }
    if ($idx && (!$cols || $idx%$cols)) {
      if ($cols) for (;$idx%$cols;$idx++) echo '<td>&nbsp;</td>';
      echo '</tr>';
    }
?>
</table>
<?php
  }
  
  function exportContext() {
    $ctxt=$this->context;
    if (isset($this->product_row)) $ctxt['product']=&$this;
    return $ctxt;
  }
  function preRenderSection($sec,$body,$args) {
    switch ($sec) {
      case 'specialprice':
        $pf=new PriceFormatter;
	$pf->loadProduct($this->product_row['products_id']);
	return $pf->hasSpecialPrice;
      case 'nospecialprice':
        $pf=new PriceFormatter;
	$pf->loadProduct($this->product_row['products_id']);
	return !$pf->hasSpecialPrice;
      default: return true;
    }
  }

	function getVar($var,$args) {
		global $currencies;
		switch ($var) {

			case 'products_image':
			return tep_image(DIR_WS_IMAGES.$this->product_row['products_image'],$this->product_row['products_name'],$args['width'],$args['height']);

			case 'products_price':
				$pf = new PriceFormatter;
				$pf->loadProduct($this->product_row['products_id']);

				return $currencies->display_price($args['type']=='special'?$pf->getSpecialPrice():$pf->getPrice(), tep_get_tax_rate($this->product['products_tax_class_id']));
				//return $currencies->display_price($this->product_row['products_price'], tep_get_tax_rate($this->product_row['products_tax_class_id']));

			case 'products_href':
				return tep_href_link('index.php','products_id='.$this->product_row['products_id']);
			default:
				if(isset($this->product_row[$var])) return $this->product_row[$var];
		}
		
		return NULL;
	}
	
	function getProductField($fld) {
		return $this->product_row[$fld];
	}
}
?>