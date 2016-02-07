<?
IXblock::loadBlock('IXblockListing');
class blk_listing_cart extends IXblockListing {

  function getListingRows($sort,$start=0,$count=NULL) {
    $prods=$_SESSION['cart']->get_products();
    return isset($count)?array_slice($prods,$start,$count):$prods;
  }
  function getListingCount() {
    return count($_SESSION['cart']->get_products());
  }

  function setListingElement(&$row) {
    $this->cart_row=&$row;
  }

  function getSortModes() {
    return Array(
      ''=>Array('title'=>'Default'),
    );
  }


// Context Cart specific  
  function getCartAttributes() {
    return isset($this->cart_row['attributes'])?$this->cart_row['attributes']:Array();
  }

  function exportContext() {
    $ctxt=$this->context;
    if (isset($this->cart_row)) {
//      $this->product_obj=IXproduct::load($this->product_row);
//      $ctxt['product']=&$this->product_obj;
      $this->product_obj=IXblock::block('blk_product_main');
      $this->product_obj->setData($this->cart_row['id']);
      $ctxt['product']=&$this->product_obj;
    }
    $ctxt['listing']=$ctxt['cart']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'cart_subtotal':
        return $_SESSION['cart']->show_total();
      case 'cart_id':
        return $this->cart_row['cart_id'];
      case 'cart_quantity':
        return $this->cart_row['quantity'];
      case 'cart_price':
        return $this->cart_row['price'];
    }
    return NULL;
  }
  
  
  
}
?>