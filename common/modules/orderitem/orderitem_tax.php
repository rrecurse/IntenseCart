<?
class orderitem_tax extends IXorderitem {
  function getName() {
    return 'Tax Entry';
  }
  function getWeight() {
    return 100;
  }
  function initItem($data) {
    $this->item
    if (!$data['id']) return false;
    $this->item=Array('item_refid'=>$data['id'],'item_quantity'=>isset($data['quantity'])?$data['quantity']:1);
    $this->refs=Array();
    if ($data['attributes']) foreach ($data['attributes'] AS $k=>$v) {
      $this->refs[]=Array('ref_type'=>'attr','ref_key'=>$k,'ref_value'=>$v);
    }
    $pr=$this->getProduct();
    $this->item['item_amount']=isset($data['price'])?$data['price']:$pr->getPrice();
    $this->item['item_name']=$pr->getName();
    return true;
  }
  function getOrderTotal() {
    return Array(
      Array('class'=>'ot_tax',
            'class_ref'=>NULL,
	    'title'=>$this->name,
	    'value'=>$this->getAmount(),
	  ),
    );
  }
  function checkItem(&$items,$idx,&$order) {
    return 0;
  }
  function applyItem(&$items,&$order) {
    foreach ($items AS $idx=>$item) if (get_class($items[$idx])==get_class($this) && $items[$idx]->refid==$this->refid) {
      foreach ($this->refs AS $ridx=>$ref) $items[$idx]->setTaxRef($this->refs[$ridx]);
      return 2;
    }
    return $this->joinItem($items,$order);
  }
  function getChargeAmount() {
  }
  
// Internal for orderitem_tax
  function setTaxRef(&$ref) {
    foreach ($this->refs AS $ridx=>$ref) if ($ref['item']->isEqual($this->refs[$ridx]['item'])) {
      $this->refs[$ridx]=&$ref;
      return;
    }
    $this->refs[]=&$ref;
  }
}
?>