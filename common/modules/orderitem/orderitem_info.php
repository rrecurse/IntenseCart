<?
class orderitem_info extends IXorderitem {
  function getName() {
    return 'Extra Info';
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
    if (!isset($idx)) {
      $this->joinItem($items);
      return 1;
    }
    return NULL;
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
  
// Internal for orderitem_inventory
  function getProduct() {
    if ($this->refid) return IXproduct::load($this->refid);
    return NULL;
  }
}
?>