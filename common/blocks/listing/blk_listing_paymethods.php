<?
IXblock::loadBlock('IXblockListing');
class blk_listing_paymethods extends IXblockListing {

  function getListingRows($sort=NULL,$start=0,$count=NULL) {
    if (!isset($this->paymods)) {
      $payset=IXmodule::module('checkout');
      $this->paymods=$payset->getModulesCustomer($customer_id);
    }
    $rows=Array();
    foreach ($this->paymods AS $idx=>$mod) {
      $rows[]=Array(
      'module'=>&$this->paymods[$idx],
      'item_id'=>$mod->getClass(),
      'item_type'=>($mod->getExternalCheckoutButton()?'external':'internal')
      );
    }
    return isset($count)?array_slice($rows,$start,$count):$rows;
  }
  function getListingCount() {
    return count($this->getListingRows());
  }

  function setListingElement(&$row) {
    $this->listing_row=&$row;
  }

  function getSortModes() {
    return Array(
      ''=>Array('title'=>'Default'),
    );
  }


  function exportContext() {
    $ctxt=$this->context;
    $ctxt['listing']=$ctxt['paymethod']=&$this;
    return $ctxt;
  }
  function getVar($var,$args) {
    switch ($var) {
      case 'id':
        return $this->listing_row['item_id'];
      case 'paymentbox':
        return $this->listing_row['module']->paymentBox();
      case 'paymethod_name': case 'name':
        return $this->listing_row['module']->getTitle();
    }
    return NULL;
  }
  
  
  
}
?>