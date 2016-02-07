<?
IXblock::loadBlock('IXblockListing');
class blk_listing_bestsellers extends IXblockListingSQL {


  function getListingSQL() {
    global $languages_id;
    return "select pd.*,p.* from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p where p.products_ordered>0 AND p.products_status = '1' AND pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'";
  }

  function setListingElement(&$row) {
    $this->product_row=&$row;
  }

  function getSortModes() {
    return Array(
      ''=>Array('sql'=>'p.products_ordered'),
    );
  }
  
  function getSortSQL($sort) {
    return "p.products_ordered DESC";
  }
  
  function exportContext() {
    $ctxt=$this->context;
    if (isset($this->product_row)) {
      $this->product_obj=IXproduct::load($this->product_row);
      $ctxt['product']=&$this->product_obj;
    }
    $ctxt['listing']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
  }
}
?>