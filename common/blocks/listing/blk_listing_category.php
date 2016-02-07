<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

IXblock::loadBlock('IXblockListing');

class blk_listing_category extends IXblockListingSQL {

  function getListingSQL() {
    global $languages_id;


	// # Detect current pricing group
	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');


	$mfr_id = '';
    if($this->context['manufacturer']) {
		$mfr_id .=" AND p.manufacturers_id='{$this->context['manufacturer']->mid}' ";
	}

    $qry = "SELECT DISTINCT pd.*, p.*, m.manufacturers_name
			FROM products p 
			LEFT JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON (pd.products_id = p.master_products_id AND pd.language_id = '" . (int)$languages_id . "')
			LEFT JOIN ". TABLE_PRODUCTS_TO_CATEGORIES ." p2c ON p2c.products_id = pd.products_id
			LEFT JOIN ". TABLE_MANUFACTURERS ." m ON m.manufacturers_id = p.manufacturers_id
			LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
			WHERE p.products_status = '1'
			AND (pg.customers_group_price > 0 OR p.products_price > 0)
			AND p2c.categories_id = '".$this->category->cid."'
			".$mfr_id."
			GROUP BY p.master_products_id
		   "; 
			// # IXblocklisting.php needs GROUP BY removed for pagination / leave here to avoid duplicate listings.

	$qry = preg_replace('/\s+/', ' ', $qry);

    return $qry;

  }

	function setListingElement(&$row) {
    	$this->product_row=&$row;
	}

	function getSortModes() {
		return array(
	      'price' => array('title'=>'Price','sql'=>'products_price'),
    	  'name' => array('title'=>'Name','sql'=>'products_name'),
	      'random' => array('title'=>'Random','sql'=>'RAND(%s)'),
    	  'sort_order' => array('title'=>'Default','sql'=>'products_sort_order'),
	    );
	}


	function exportContext() {
		$ctxt = $this->context;
		if(isset($this->product_row)) {
			//$this->product_obj = IXproduct::load($this->product_row);
			//$ctxt['product'] = &$this->product_obj;
			$this->product_obj = IXblock::block('blk_product_main');
			$this->product_obj->setData($this->product_row);
			$ctxt['product'] = &$this->product_obj;
		}
		
		$ctxt['listing']=&$this;

		return $ctxt;
	}

  function requireContext() {
    return Array('root','category');
  }


	function getVar($var,$args) {

		// # Detect current pricing group
		$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

		switch ($var) {

			case 'products_price':

				// # if pricing group is greater then Pending or Retail, reference the pricing_groups table for special pricing to show links

				$product_check = tep_db_query("SELECT customers_group_price AS products_price FROM products_groups WHERE products_id = '". (int)$this->pid ."' AND customers_group_id = '". $customer_group_id ."' GROUP BY products_id");

				$prod = tep_db_fetch_array($product_check);

				tep_db_free_result($product_check);

				if($prod['products_price'] > 0) { 
					return '<span id="'.$this->jsMakeField('price').'"></span>';
				} else {
					return '<span>-</span>';
				}
	
			case 'mfr_select':

				$lst = array(array('id' => $this->root->pageUrl(array('mfr_id'=>'')), 'text' => 'ALL'));

/*
				for($qry = IXdb::query("SELECT m.* from products_to_categories p2c, products p, manufacturers m where p.products_status = '1' AND p.products_price > 0 and p.products_id = p2c.products_id and m.manufacturers_id = p.manufacturers_id AND p2c.categories_id = '".$this->category->cid."' GROUP BY m.manufacturers_id"); $mf = IXdb::fetch($qry);) { 
*/
				
				$qry = tep_db_query("SELECT m.manufacturers_id, m.manufacturers_name
									 FROM ". TABLE_MANUFACTURERS ." m
									 LEFT JOIN ". TABLE_PRODUCTS ." p ON p.manufacturers_id = m.manufacturers_id 
									 LEFT JOIN products_to_categories p2c ON p2c.products_id = p.products_id
									 LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
									 WHERE p.products_status = '1' 
									 AND pg.customers_group_price > 0
									 AND p2c.categories_id = '".$this->category->cid."' 
									 GROUP BY m.manufacturers_id
									");

				while($mf = tep_db_fetch_array($qry)) { 

					$lst[] = array('id'=>$this->root->pageUrl(array('mfr_id'=>$mf['manufacturers_id'])),'text'=>$mf['manufacturers_name']);
				
				}

				tep_db_free_result($qry);
				
			return tep_draw_pull_down_menu('mfr_select',$lst,$this->root->pageUrl(),'onChange="document.location=this.value"');
   		}
		
		return NULL;
	}
}
?>