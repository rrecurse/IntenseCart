<?php

	IXblock::loadBlock('IXblockListing');

class blk_listing_subcats extends IXblockListingSQL {

	function getListingSQL() {
    	
		global $languages_id;
		
		$qry = "SELECT cd.*, c.* 
				FROM ". TABLE_CATEGORIES_DESCRIPTION ." cd 
				LEFT JOIN " . TABLE_CATEGORIES . " c ON (c.categories_id = cd.categories_id AND cd.language_id = '". (int)$languages_id ."') 
				WHERE c.categories_status = '1' 
				AND c.parent_id = '". $this->category->cid ."' 
				AND c.products_class='product_default'
			   ";

		$qry = preg_replace('/\s+/', ' ', $qry);

		return $qry;
  }


  function setListingElement(&$row) {
    $this->product_row=&$row;
  }

  function getSortModes() {
    return array(
	   //'price'=>Array('title'=>'Price','sql'=>'products_price'),
      'name'=>Array('title'=>'Name','sql'=>'categories_name'),
      'sort_order'=>Array('title'=>'Default','sql'=>'sort_order'),
      'random'=>Array('title'=>'Random','sql'=>'RAND(%s)'),
    );
  }

  function exportContext() {
    $ctxt=$this->context;
    if (isset($this->product_row)) {
		//$this->product_obj=IXproduct::load($this->product_row);
		//$ctxt['product']=&$this->product_obj;
		$this->product_obj=IXblock::block('blk_category_main');
		$this->product_obj->setContext($this->context,Array());
		$this->product_obj->setData($this->product_row);
		//print_r($this->product_row);
		$ctxt['category']=&$this->product_obj;
	}

    $ctxt['listing']=&$this;
    return $ctxt;
  }

  function requireContext() {
    return Array('root','category');
  }

}
?>