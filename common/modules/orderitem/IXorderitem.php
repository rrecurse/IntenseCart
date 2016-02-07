<?
class IXorderitem extends IXmodule {
  function getName() {
    return 'Order Item';
  }
  function isReady() {
    return true;
  }
  function getWeight() {
    return NULL;
  }
  function initItem($data) {
    return false;
  }
  function loadFromRow($itm,$refs,&$order) {
    $item=IXmodule::init($itm['item_class'],'orderitem');
    if (!isset($item)) return NULL;
    $item->itemid=$itm['orders_items_id'];
    $item->orderid=$itm['orders_id'];
    $item->itemtype=$itm['item_type'];
    $item->qty=$itm['item_quantity'];
    $item->amount=$itm['item_amount'];
    $item->refid=$itm['item_refid'];
    $item->refdata=isset($itm['item_refdata'])?unserialize($itm['item_refdata']):NULL;
    $item->itemname=$itm['item_name'];
    $item->fulfill=$itm['item_fulfill'];
    $item->refs=Array();
    foreach ($refs AS $ref) {
      $item->refs[]=Array(
        'id'=>$ref['orders_items_refs_id'],
        'type'=>$ref['ref_type'],
        'key'=>$ref['ref_key'],
        'value'=>$ref['ref_value'],
        'amount'=>$ref['ref_amount'],
        'qty'=>$ref['ref_quantity'],
	'refid'=>$ref['ref_refid'],
	'itemid'=>$ref['ref_item_id'],
      );
    }
    return $item;
  }
  function saveItem(&$order) {
    $idata=Array(
      'item_class'=>get_class($this),
      'orders_id'=>$order->orderid,
      'item_type'=>$this->itemtype,
      'item_quantity'=>$this->qty,
      'item_amount'=>$this->amount,
      'item_refid'=>$this->refid,
      'item_refdata'=>(isset($this->refdata)?serialize($this->refdata):NULL),
      'item_name'=>$this->itemname
    );
    if (isset($this->itemid)) $idata['item_parent_id']=$this->itemid;
    $this->itemid=IXdb::store('insert','orders_items',$idata);
    if (!$this->itemid) return NULL;
    if (isset($this->fulfill)) IXdb::query("UPDATE orders_items SET item_fulfill=IF(orders_items_id='{$this->itemid}',".IXdb::quote($this->fulfill).",NULL) WHERE orders_items_id IN ('{$this->itemid}','{$idata['item_parent_id']}')");
    foreach ($this->refs AS $ridx=>$ref) $this->saveRef($ridx,true);
  }
  function saveRef($ridx,$new=false) {
    if (!isset($this->refs[$ridx])) return false;
    $ref=&$this->refs[$ridx];
    $rdata=Array(
      'orders_id'=>$order->orderid,
      'orders_items_id'=>$this->itemid,
      'ref_type'=>$ref['type'],
      'ref_key'=>$ref['key'],
      'ref_value'=>$ref['value'],
      'ref_quantity'=>$ref['qty'],
      'ref_amount'=>$ref['amount'],
      'ref_refid'=>$ref['refid'],
      'ref_item_id'=>(isset($ref['item'])?$ref['item']->itemid:$ref['itemid'])
    );
    if ($new || !$ref['id']) $ref['id']=IXdb::store('insert','orders_items_refs',$rdata);
    else IXdb::store('update','orders_items_refs',$rdata,"orders_items_refs_id='{$ref['id']}'");
  }
  function getTotalAmount() {
    return $this->amount*$this->quantity;
  }
  function getChargeAmount() {
    return 0;
  }
  function getOrderTotal() {
    return Array();
  }
  function getRefAmount() {
    $ra=0;
    $r=0;
    foreach ($this->refs AS $ridx=>$ref) {
      if (isset($ref['amount'])) {
        $r+=$ref['amount']*$ref['qty'];
	$ra+=$ref['amount']*$ref['qty']*(isset($ref['item'])?$ref['item']->getChargeRatio(get_class($this)):1);
      } else {
	$ra+=$ref['amount']*$ref['qty']*(isset($ref['item'])?$ref['item']->getChargeRatio(get_class($this)):1);
      }
    }
  }
  function checkItem(&$items,$idx,&$order) {
    return NULL;
  }
  function applyItem(&$items,&$order) {
    return NULL;
  }
  function joinItem(&$items) {
    $w=$this->getWeight();
    for ($i=0;isset($items[$i]);$i++) if ($items[$i]->getWeight()>$w) break;
    $th=Array(&$this);
    array_splice($items,$i,0,$th);
  }
  function setFulfill($status) {
  }
  function updateFulfill($ff) {
    if (!$this->itemid) return NULL;
    if ($ff==$this->fulfill) return true;
    if (IXdb::query("UPDATE orders_items SET item_fulfill=".IXdb::quote($ff)." WHERE orders_items_id='{$this->itemid}' AND item_fulfill".(isset($this->fulfill)?'='.IXdb::quote($this->fulfill):" IS NULL"))==0) return false;
    $this->fulfill=$ff;
    return true;
  }
}
?>