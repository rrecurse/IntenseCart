<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


IXblock::loadBlock('IXblockListing');
class blk_listing_manufacturer_products extends IXblockListingSQL {

	function getListingSQL() {
		global $languages_id;

	// # Detect current pricing group
	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

	// # if pricing group is greater then Pending or Retail, reference the pricing_groups table for special pricing to show links
	if($customer_group_id > 1) { 
		$special_pricing_join = "LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')";
		$special_pricing = "AND pg.customers_group_price > 0";
	} else {
		$special_pricing_join = '';
		$special_pricing = 'AND p.products_price > 0';
	}

		$qry = "SELECT pd.*, p.* 
				FROM " . TABLE_PRODUCTS_DESCRIPTION . " pd 
				LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = pd.products_id
				" . $special_pricing_join . "
				WHERE p.products_status = '1' 
				" . $special_pricing . "
				AND pd.language_id = '" . (int)$languages_id . "'
				AND p.manufacturers_id = '".$this->manufacturer->mid."'
			   ";

		$qry = 	preg_replace("/\s+/", ' ', $qry);

    return $qry;
  }

  function setListingElement(&$row) {
    $this->product_row=&$row;
  }

  function getSortModes() {
    return Array(
      'price'=>Array('title'=>'Price','sql'=>'products_price'),
      'name'=>Array('title'=>'Name','sql'=>'products_name'),
      'random'=>Array('title'=>'Random','sql'=>'RAND(%s)'),
      'sort_order'=>Array('title'=>'Default','sql'=>'products_sort_order'),
    );
  }

  function exportContext() {
    $ctxt=$this->context;
    if (isset($this->product_row)) {
//      $this->product_obj=IXproduct::load($this->product_row);
//      $ctxt['product']=&$this->product_obj;
      $this->product_obj=IXblock::block('blk_product_main');
      $this->product_obj->setData($this->product_row);
      $ctxt['product']=&$this->product_obj;
    }
    $ctxt['listing']=&$this;
    return $ctxt;
  }
  function requireContext() {
    return Array('root','manufacturer');
  }
}
?>