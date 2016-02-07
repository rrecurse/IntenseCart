<?
IXblock::loadBlock('IXblockListing');
class blk_listing_specials extends IXblockListingSQL {

  function getListingSQL() {
    global $languages_id, $cPath;
    $cat_id=end(tep_parse_category_path($cPath));
    if (is_numeric($cat_id)) {
     $dbres=tep_db_query("select ptc.products_id from products_to_categories ptc where ptc.categories_id='$cat_id'"); //for some reason I can't use a subquery 'where p.products_id in (select ...)'
     while ($row=tep_db_fetch_array($dbres))
      $products[]=$row['products_id'];
    }
    if (count($products)>0) return "select pd.*,p.* from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_SPECIALS . " s where p.products_id in (".implode(",", $products).") and p.products_status = '1' and s.products_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and s.status = '1'";
//    else return "select 1"; //derp
    else return "select pd.*,p.* from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_SPECIALS . " s where p.products_status = '1' and s.products_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and s.status = '1'";
  }

  function setListingElement(&$row) {
    $this->product_row=&$row;
  }

  function getSortModes() {
    return Array(
      'specials_date'=>Array('title'=>'Date Added','sql'=>'s.specials_date_added'),
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