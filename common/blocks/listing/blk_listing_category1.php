<?
IXblock::loadBlock('IXblockListing');
class blk_listing_category1 extends IXblockListing {

  function getSubcatCount() {
    if (!isset($this->subcatCount)) {
      $this->subcatCount=IXdb::read("SELECT COUNT(0) AS ct FROM categories c WHERE c.parent_id='".$this->category->cid."' AND c.categories_status = '1'",NULL,'ct');
    }
    return $this->subcatCount;
  }

  function getProductCount() {
    if (!isset($this->productCount)) {
      $this->productCount=IXdb::read("SELECT COUNT(0) AS ct FROM products_to_categories p2c,products p WHERE p2c.categories_id='".$this->category->cid."' AND p2c.products_id=p.products_id AND p.products_status = '1'",NULL,'ct');
    }
    return $this->productCount;
  }
  
  function getListingTypesCount() {
    return Array('category'=>$this->getSubcatCount(),'product'=>$this->getProductCount());
  }
  
  function getListingRows($sort,$start=0,$count=NULL) {
    global $languages_id;
    $cct=$this->getSubcatCount();
    if ($cct>$start) {
      $lst=IXdb::read("select cd.*,c.*,'category' AS item_type FROM categories c LEFT JOIN categories_description cd ON (c.categories_id=cd.categories_id AND cd.language_id='" . (int)$languages_id . "') WHERE c.parent_id='".$this->category->cid."' AND c.categories_status=1".($count?" LIMIT $start,$count":""),Array(NULL));
      if ($count && $count<=count($lst)) return $lst;
      $start=0;
    } else {
      $start-=$cct;
      $lst=Array();
    }
    $lst=array_merge($lst,IXdb::read("select pd.*,p.*,'product' AS item_type from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_status = '1' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '".$this->category->cid."'".($count?" LIMIT $start,$count":""),Array(NULL)));
    return ($lst);
  }

  function setListingElement(&$row) {
    unset($this->product_row);
    unset($this->category_row);
    if ($row['item_type']=='product') $this->product_row=&$row;
    else if ($row['item_type']=='category') $this->category_row=&$row;
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
    } else if (isset($this->category_row)) {
      $this->cat_obj=IXblock::block('blk_category_main');
      $this->cat_obj->setData($this->category_row);
      $ctxt['category']=&$this->cat_obj;
    }
    $ctxt['listing']=&$this;
    return $ctxt;
  }
  function requireContext() {
    return Array('root','category');
  }
  
}
?>