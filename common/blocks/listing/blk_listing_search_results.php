<?
IXblock::loadBlock('IXblockListing');
class blk_listing_search_results extends IXblockListingSQL {

  function getListingSQL() {
    global $languages_id;
//    echo $GLOBALS['advSearchSQL'];
    return $GLOBALS['advSearchSQL'];
  }

  function setListingElement(&$row) {
    $this->product_row=&$row;
  }

  function getSortModes() {
    return Array(
      'price'=>Array('title'=>'Price','sql'=>'p.products_price'),
      'name'=>Array('title'=>'Name','sql'=>'pd.products_name'),
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
  function requireContext() {
    return Array('root','category');
  }
}
?>
