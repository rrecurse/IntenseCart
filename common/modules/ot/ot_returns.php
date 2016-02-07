<?php
  class ot_returns extends IXmodule {
    function calculateTotal($totals,&$order) {
      global $currencies;
      $rs=Array();
      foreach ($order->returns AS $rt) {
        if ($rt['refund_amount']>0) $rs[]=Array(
          'title' => 'Return Refund (RMA '.$rt['rma'].'):',
	  'value'=>-$rt['refund_amount'],
          'text'=>NULL,
	);
        if ($rt['exchange_amount']!=0) $rs[]=Array(
          'title' => 'Exchange '.($rt['exchange_amount']>0?'Surcharge':'Refund').' (RMA '.$rt['rma'].'):',
	  'value'=>$rt['exchange_amount'],
          'text'=>NULL,
	);
      }
      foreach ($rs AS $idx=>$rr) if (isset($totals[$idx]) && isset($totals[$idx]['orders_totals_id'])) $rs[$idx]['orders_totals_id']=$totals[$idx]['orders_totals_id'];
      return $rs;
    }
  }
?>
