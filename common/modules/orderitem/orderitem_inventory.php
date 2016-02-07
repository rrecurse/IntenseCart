<?
class orderitem_inventory extends IXorderitem {
  function getName() {
    return 'Inventory Item';
  }
  function getWeight() {
    return 0;
  }
  function initItem($data) {
    if (!$data['id']) return false;
    $this->refid=$data['id'];
    $this->quantity=isset($data['quantity'])?$data['quantity']:1;
    $this->refs=Array();
    if ($data['attributes']) foreach ($data['attributes'] AS $k=>$v) {
      $this->refs[]=Array('type'=>'attr','key'=>$k,'value'=>$v);
    }
    $pr=$this->getProduct();
    $this->amount=isset($data['price'])?$data['price']:$pr->getPrice();
    $this->itemname=$pr->getName();
    return true;
  }
  function getOrderTotal() {
    return Array(
      Array('class'=>'ot_subtotal',
            'class_ref'=>NULL,
	    'title'=>'Subtotal',
	    'value'=>$this->getAmount(),
	  ),
    );
  }
  function checkItem(&$items,$idx,&$order) {
    return 0;
  }
  function applyItem(&$items,&$order) {
    $this->joinItem($items);
    foreach ($this->getTaxGroups() AS $grp=>$tax) {
      $tx=IXmodule::module('orderitem_tax');
      if (!isset($tx)) break;
      $tx->initItem(Array('group'=>$grp,'amount'=>$tax));
      $tx->applyItem($items,$order);
    }
    return 2;
  }
  function setFulfill($status) {
    $pr=$this->getProduct();
    if ($pr) {
      $q=$status?$this->quantity:0;
      $diff=$this->fullfill-$q;
      if ($diff) {
        $adj=$pr->adjustStock($diff);
	$this->updateFulfill($this->fulfill-$adj);
      }
    }
  }
  function getChargeAmount() {
    return $this->amount*$this->fulfill;
  }
  
// Internal for orderitem_inventory
  function getProduct() {
    if (!isset($this->theProduct)) $this->theProduct=IXproduct::load($this->refid);
    return $this->theProduct;
  }
  function getTaxGroups() {
    return Array(0=>1.23);
  }
}
?>