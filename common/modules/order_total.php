<?
class order_total extends IXmoduleSet {
  function getAllModules() {
    return tep_list_modules('ot');
  }
  function getModules() {
    $modlist=Array('ot_subtotal','ot_shipping','ot_coupon','ot_gv','ot_returns','ot_tax','ot_total');
    $mods=Array();    
    foreach ($modlist AS $m) {
      $mod=tep_module($m,'ot');
      if (isset($mod)) $mods[$m]=$mod;
    }
    return $mods;
  }
  function calculateTotal($totals,&$order) {
    global $currencies;
    if (!isset($currencies)) {
      include_once(DIR_FS_CLASSES.'currencies.php');
      $currencies=new currencies();
    }
    $tc=Array();
    $mods=$this->getModules();
    foreach ($totals AS $t) {
      if (isset($mods[$t['class']])) foreach ($mods AS $cl=>$mod) {
	if ($cl==$t['class']) break;
        if (!isset($tc[$cl])) $tc[$cl]=Array();
      }
      if (!isset($tc[$t['class']])) $tc[$t['class']]=Array();
      $tc[$t['class']][]=$t;
    }
    $rs=Array();
    $order->info['total']=0.0;
    foreach ($tc AS $cl=>$totals) {
      if (isset($mods[$cl])) {
        $totals=$mods[$cl]->calculateTotal($totals,$order);
	foreach ($totals AS $tidx=>$t) {
	  $totals[$tidx]['class']=$cl;
	  if (!$t['text']) $totals[$tidx]['text']=$currencies->format($totals[$tidx]['value'],true,$order->info['currency'],$order->info['currency_value']);
	}
      }
      if ($cl!='ot_total') foreach ($totals AS $t) $order->info['total']+=$t['value'];
      $rs=array_merge($rs,$totals);
    }
    return $rs;
  }
}
?>
