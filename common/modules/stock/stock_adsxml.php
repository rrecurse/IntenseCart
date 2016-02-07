<?
class stock_adsxml extends IXmodule {
  function getName() {
    return 'ADS XML Stock Feed';
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
    if (!isset($GLOBALS['StockADSXMLLvl'])) $GLOBALS['StockADSXMLLvl']=Array();
    if ($upc && !array_key_exists($upc,$GLOBALS['StockADSXMLLvl'])) $GLOBALS['StockADSXMLLvl'][$upc]=false;
  }
  function requestFeed() {
    $upcs=Array();
    $stklvl=Array();
//    foreach ($this->stock AS $sidx=>$stk) if ($stk['products_sku']) $upcs[$stk['products_sku']]=$stk['products_id'];
//    foreach ($this->stock AS $sidx=>$stk) if ($stk['products_sku']) $GLOBALS['StockADSXMLLvl'][$stk['products_sku']]=false;
    foreach ($GLOBALS['StockADSXMLLvl'] AS $upc=>$lvl) if ($lvl===false) $upcs[]=$upc;
    if ($upcs) {
      $feed='<msg><CMP_CODE>'.htmlspecialchars($this->getConf('cmp_code')).'</CMP_CODE>';
      if ($this->getConf('whs_code')!='') $feed.='<WHS_CODE>'.htmlspecialchars($this->getConf('whs_code')).'</WHS_CODE>';
      foreach ($upcs AS $upc) $feed.='<UPC>'.htmlspecialchars($upc).'</UPC>';
      $feed.='</msg>';
      $post='xmlrequest='.urlencode('METHOD STKLVL '.$feed);
      $url=$this->getConf('xml_url');
      $urlp=parse_url($url);
      $sk=@fsockopen($urlp['host'],$urlp['port']?$urlp['port']:9080,$errno,$errstr,15);
      $rsp='';
      if ($sk) {
        stream_set_timeout($sk,15);
        fwrite($sk,"POST $url HTTP/1.0\r\n");
        fwrite($sk,"Content-Type: application/www-form-urlencoded\r\n");
        fwrite($sk,"Content-Length: ".strlen($post)."\r\n");
        fwrite($sk,"\r\n");
        fwrite($sk,$post);
        while (preg_match('/^\w/',fgets($sk,1024)));
        while (($l=@fread($sk,65535))!='') $rsp.=$l;
        fclose($sk);
      }
//echo htmlspecialchars($rsp);
      foreach ($upcs AS $upc) $GLOBALS['StockADSXMLLvl'][$upc]=NULL;
      if (preg_match_all('|<ITEM>(.*?)</ITEM>|',$rsp,$rp)) foreach ($rp[1] AS $r) {
        preg_match('|<UPC>(.*?)</UPC>|',$r,$r_upc);
        preg_match('|<STOCK>(.*?)</STOCK>|',$r,$r_stk);
        if ($upc=$r_upc[1]) $GLOBALS['StockADSXMLLvl'][$upc]=$r_stk[1];
      }
    }
    foreach ($this->stock AS $sidx=>$stk) if (isset($GLOBALS['StockADSXMLLvl'][$stk['products_sku']])) {
      $q=$GLOBALS['StockADSXMLLvl'][$stk['products_sku']];
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
      'xml_url'=>Array('title'=>'XML Feed URL'),
      'cmp_code'=>Array('title'=>'Company Code','default'=>'0000'),
      'whs_code'=>Array('title'=>'Warehouse Code','default'=>''),
    );
  }
}
?>
