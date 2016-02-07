<?
IXblock::loadBlock('IXblockListing');
class blk_listing_cart_attributes extends IXblockListing {

  function getListingRows($sort,$start=0,$count=NULL) {
    $attrs=Array();
    foreach ($this->cart->getCartAttributes() AS $op=>$val) $attrs[]=Array('name'=>$op,'value'=>$val);
    return isset($count)?array_slice($attrs,$start,$count):$attrs;
  }
  function getListingCount() {
    return count($this->cart->getCartAttributes());
  }
  function setListingElement(&$row) {
    $this->cart_attr_row=&$row;
  }

  function getSortModes() {
    return Array(
      ''=>Array('title'=>'Default'),
    );
  }

  function exportContext() {
    $ctxt=$this->context;
    $ctxt['listing']=$ctxt['cart_attributes']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'attr_name':
        return $this->cart_attr_row['name'];
      case 'attr_value':
        return $this->cart_attr_row['value'];
    }
    return NULL;
  }
  
  function requireContext() {
    return Array('root','cart');
  }
}
?>