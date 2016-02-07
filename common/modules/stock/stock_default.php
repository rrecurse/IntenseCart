<?php
class stock_default extends IXmodule {
  function getName() {
    return 'Manual Stock Control';
  }
  function setRow($row) {
    $this->stock=Array($row);
  }
  function joinRow($row) {
    $this->stock[]=$row;
    return true;
  }
  function getStock(&$dep,$force=0) {
    $s=NULL;
    foreach ($this->stock AS $sidx=>$stk) $s=max($s,-($stk['products_quantity']-$dep[$stk['products_id']]));
    return -$s;
  }
  function checkStock(&$dep,$qty,$force=0) {
    $f=true;
    foreach ($this->stock AS $sidx=>$stk) if ($stk['products_quantity']<($dep[$stk['products_id']]+=$qty)) $f=false;
    return $f;
  }
  function adjustStock($sh) {
    $sh+=0;
    $pids=Array();
    foreach ($this->stock AS $sidx=>$stk) {
      $stk['products_quantity']+=$sh;
      $pids[]=$stk['products_id'];

//error_log('/common/modules/stock/stock_default.php - referrer - ' | products_quantity - ' . $stk['products_quantity'] . ' | products_ids - ' . print_r($pids,1));
    }
    tep_db_query("UPDATE products 
				  SET products_quantity = products_quantity+($sh),
				  last_stock_change = NOW()
				  WHERE products_id IN (".join(',',$pids).")");
    return $sh;
  }
}
?>