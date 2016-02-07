<?php

IXblock::loadBlock('IXblockListing');
class blk_listing_xsell_cart extends IXblockListingSQL {

  function getListingSQL() {

	global $languages_id;
	$pids = array();

	if(!$_SESSION['cart']) {
		return false;
	} else {
		foreach($_SESSION['cart']->get_products() AS $prod) { 
			$pids[] = $prod['id'];
		}
	}

    $pidl = "'".join("','",$pids)."'";

    $ch = $this->args['channel'];

    if (!$ch) $ch='default';

	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

	if ($customer_group_id > '1') {

		$qry = "SELECT pd.*, p.*, pg.customers_group_price
				FROM ".TABLE_PRODUCTS." mp," . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p
				LEFT JOIN " . TABLE_PRODUCTS_GROUPS . " pg ON (p.products_id = pg.products_id AND pg.customers_group_id = '".$customer_group_id."'), " . TABLE_PRODUCTS_DESCRIPTION . " pd 
				WHERE mp.products_id IN($pidl) 
				AND xp.products_id = mp.products_id 
				AND xp.xsell_id = p.products_id 
				AND p.master_products_id = pd.products_id 
				AND pd.language_id = '". $languages_id ."' 
				AND p.products_status = '1' 
				AND (p.products_price > 0 OR pg.customers_group_price > 0)
				AND xsell_channel = '". $ch ."' 
				AND p.products_id NOT IN($pidl) 
				GROUP BY p.master_products_id";

	} else {

		$qry = "SELECT pd.*, p.* 
				FROM ".TABLE_PRODUCTS." mp," . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd 
				WHERE mp.products_id IN($pidl) 
				AND xp.products_id = mp.products_id 
				AND xp.xsell_id = p.products_id 
				AND p.master_products_id = pd.products_id 
				AND pd.language_id = '". $languages_id ."' 
				AND p.products_status = '1' 
				AND p.products_price > 0 
				AND xsell_channel = '". $ch ."' 
				AND p.products_id NOT IN($pidl) 
				GROUP BY p.master_products_id";
	}

	$qry = preg_replace('/\s+/', ' ', $qry);

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
  function getVar($var,$args) {
  }
  function getListingCount() {
    return sizeof($this->getListingRows());
  }
}
?>
