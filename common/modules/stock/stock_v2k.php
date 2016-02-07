<?
class stock_v2k extends IXmodule {
  function stock_v2k() {
    $this->orderfeed=IXmodule::module('orderfeed_v2k');
  }
  function getName() {
    return 'Visual2k Stock Feed';
  }
 function setRow($row) {
    $this->stock=Array($row);
    $this->addToFeed($row);
  }
  function joinRow($row) {
    $this->stock[]=$row;
    $this->addToFeed($row);
    return true;
  }
  function addToFeed(&$row) {
    $upc=$row['products_sku'];
    if (!isset($GLOBALS['StockV2kXMLLvl'])) $GLOBALS['StockV2kXMLLvl']=Array();
    if ($upc && !array_key_exists($upc,$GLOBALS['StockV2kXMLLvl'])) $GLOBALS['StockV2kXMLLvl'][$upc]=false;
  }
  function requestFeed() {

    $ipcs=Array();
    foreach ($GLOBALS['StockV2kXMLLvl'] AS $upc=>$lvl) if ($lvl===false) {
      $GLOBALS['StockV2kXMLLvl'][$upc]=NULL;
      $ipcs[]=$upc;
    }
    if ($ipcs) {
    
      $rs=$this->orderfeed->SoapCall('GetProductsATS',Array('IPCCodes'=>Array('string'=>$ipcs)));

/*
	  echo htmlspecialchars($this->orderfeed->soap->debug_str);
print_r($rs);
	  exit;
*/

      if ($rs['GetProductsATSResult']['DataObjects']['ProductATSByIPC']) {
        foreach ($rs['GetProductsATSResult']['DataObjects']['ProductATSByIPC'] AS $r) {
          foreach ($r['ProductATS']['WebErpProductATSData'] AS $d) if ($d['WarehouseID']==$this->orderfeed->getConf('warehouse_id')) {
//	  $GLOBALS['StockV2kXMLLvl'][$d['IPCCode']]=$d['ATSell'];
	    if ($GLOBALS['StockV2kXMLLvl'][$r['IPCCode']]<$d['ATSell']) $GLOBALS['StockV2kXMLLvl'][$r['IPCCode']]=$d['ATSell'];
//	  print_r($rs);
//	  exit;
	  }
        }
//      print_r($GLOBALS['StockV2kXMLLvl']);
      } else {

        foreach ($GLOBALS['StockV2kXMLLvl'] AS $upc=>$lvl) if ($lvl===false) {
          $GLOBALS['StockV2kXMLLvl'][$upc]=NULL;
          $rs=$this->orderfeed->SoapCall('GetProductATS',Array('IPCCode'=>$upc));
          if ($rs['GetProductATSResult']['DataObjects']['WebErpProductATSData']) {
	    foreach ($rs['GetProductATSResult']['DataObjects']['WebErpProductATSData'] AS $d) if ($d['WarehouseID']==$this->orderfeed->getConf('warehouse_id')) {
	      if ($GLOBALS['StockV2kXMLLvl'][$upc]<$d['ATSell']) $GLOBALS['StockV2kXMLLvl'][$upc]=$d['ATSell'];
	    }
          }
        }

      }
    }
    
    foreach ($this->stock AS $sidx=>$stk) if (isset($GLOBALS['StockV2kXMLLvl'][$stk['products_sku']])) {
      $q=$GLOBALS['StockV2kXMLLvl'][$stk['products_sku']];
      if ($stk['products_quantity']!=$q && $stk['products_id']) IXdb::query("UPDATE products SET products_quantity='".($q+0)."' WHERE products_id='{$stk['products_id']}'");
      $this->stock[$sidx]['products_quantity']=$q;
    }
    return $stklvl;
  }
  function getStock(&$dep,$force=0) {
    $this->requestFeed();
    $s=NULL;
    foreach ($this->stock AS $sidx=>$stk) $s=max($s,-($stk['products_quantity']-$dep[$stk['products_id']]));
    return -$s;
  }
  function checkStock(&$dep,$qty,$force=0) {
    $this->requestFeed();
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
    }
    tep_db_query("UPDATE products SET products_quantity=products_quantity+($sh) WHERE products_id IN (".join(',',$pids).")");
    return $sh;
  }
  function listConf() {
    return Array(
    );
  }
}
?>
