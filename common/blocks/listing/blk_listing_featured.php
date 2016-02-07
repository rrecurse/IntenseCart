<?php
IXblock::loadBlock('IXblockListing');
class blk_listing_featured extends IXblockListingSQL {

  function getListingSQL() {

    global $languages_id;

    $featured_products_category_id = ($this->context['category'] ? ($this->context['category']->cid + 0) : 0);

    $featured_cats = array($featured_products_category_id => $featured_products_category_id);

    $subcats = tep_get_subcats_info($featured_products_category_id);

    foreach($subcats AS $subcat) {
		$featured_cats[$subcat['id']] = $subcat['id'];
	}

	// # Detect current pricing group
	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

	$query = "SELECT pd.*,p.*, pg.customers_group_price
			  FROM ". TABLE_FEATURED ." f
			  LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_id = f.products_id
			  LEFT JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON (pd.products_id = p.products_id AND pd.language_id = '". (int)$languages_id ."')
			  LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
			  WHERE f.status = 1 
			  AND p.products_status = 1
			  AND (p.products_price > 0 OR pg.customers_group_price > 0)
			  AND (SELECT COUNT(0) FROM products_to_categories p2c 
				   LEFT JOIN ". TABLE_FEATURED ." ft ON ft.products_id = p2c.products_id
				   WHERE p2c.categories_id IN('".join("','",$featured_cats)."')
				   ) > 0
			 ";

	$query = preg_replace('/\s+/', ' ', $query);

    return $query;

  }

  function setListingElement(&$row) {
    $this->product_row=&$row;
  }

  function getSortModes() {

    return array(
      'sort_order' => array('title' => 'Featured','sql' => 'f.sort_order'),
      'price' => array('title' => 'Price','sql' => 'p.products_price'),
      'name' => array('title' => 'Name','sql' => 'pd.products_name'),
      'random' => array('title' => 'Random','sql' => 'RAND(%s)'),
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
    return Array('root','category');
  }
}
?>