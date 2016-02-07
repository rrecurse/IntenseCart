<?php
class IXproduct extends IXmodule {

  function load($pid) {
    $row=tep_db_fetch_array(tep_db_query("SELECT * FROM products WHERE products_id='".addslashes($pid)."'"));
    return IXproduct::loadFromRow($row);
  }
  function loadFromRow($row) {
//    if (!$row) return NULL;
    $class=$row['products_class'];
    if (!$class) $class='product_default';
    $obj=tep_module($class,'product');
    if ($obj) {
      $obj->pid=$row['products_id'];
      $obj->product=$row;
      $obj->desc=Array();
    }
    return $obj;
  }
  function findBySKU($sku) {
    $row=IXdb::read("SELECT * FROM products WHERE products_sku='".addslashes($sku)."'");
    if (!$row) return NULL;
    return IXproduct::loadFromRow($row);
  }
  function findByUPC($upc) {
    return IXproduct::findBySKU($upc);
  }
  function getID() {
    return $this->pid;
  }
  
  function initStock() {
    if (isset($this->stock)) return true;
    if (!$this->product) return false;
    $lnks=tep_db_read("SELECT p.products_id,p.stock_class,p.products_quantity FROM products_xsell x,products p WHERE x.products_id='".$this->pid."' AND x.xsell_channel='stock' AND x.xsell_id=p.products_id",'products_id');
    if (!$lnks) $lnks=Array(($this->pid)=>$this->product);
    $this->stock=Array();
    $stkcl=Array();
    foreach ($lnks AS $lnk) {
      $cl=$lnk['stock_class'];
      if (isset($stkcl[$cl]) && $stkcl[$cl]->joinRow($lnk)) continue;
      $stk=tep_module($cl,'stock');
      if (!isset($stk)) $stk=tep_module('stock_default','stock');
      if (isset($stk)) {
        $this->stock[]=$stk;
	$stkl=&$this->stock[sizeof($this->stock)-1];
	$stkl->setRow($lnk);
        $stkcl[$cl]=&$stkl;
      }
    }
  }
  
  function getStock($force=0) {
    $dep=Array();
    return $this->getStockDep($dep,$force);
  }
  function getStockDep(&$dep,$force=0) {
    $this->initStock();
    $s=NULL;
    foreach ($this->stock AS $sidx=>$stk) $s=max($s,-$this->stock[$sidx]->getStock($dep,$force));
    return -$s;
  }
  function checkStock($qty,$force=0) {
    $dep=Array();
    return $this->checkStockDep($dep,$qty,$force);
  }
  function checkStockDep(&$dep,$qty,$force=0) {
    $this->initStock();
    foreach ($this->stock AS $sidx=>$stk) if (!$this->stock[$sidx]->checkStock($dep,$qty,$force)) return false;
    return true;
  }
  function adjustStock($sh) {
    $this->initStock();
    foreach ($this->stock AS $sidx=>$stk) $adj=$this->stock[$sidx]->adjustStock($sh);
    return $adj;
  }

  function getExtraField($fld) {
    return isset($this->pid)?tep_get_product_extra($this->pid,$fld):NULL;
  }

  function approvePurchase($qty,$op,&$order) {
    return NULL;
  }
  function getPurchaseInfo($op,&$order) {
    return Array();
  }
  function getProductFields() {
    return Array();
  }
  function getModelFields() {
    return $this->getProductFields();
  }
  function disableModelFields() {
    return Array();
  }
  function productEditSectionAllowed($sec) {
    return true;
  }
}
?>