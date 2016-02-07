<?php
  class ot_tax extends IXmodule {
    function calculateTotal($totals,&$order) {
      global $currencies;
      reset($order->info['tax_groups']);
      $out=Array();
      while (list($key, $value) = each($order->info['tax_groups'])) {
        if ($value > 0) {
          $out[] = array('title' => $key . ':',
                                  'text' => $currencies->format($value, true, $order->info['currency'], $order->info['currency_value']),
                                  'value' => round($value,2));
        }
      }
      if (!$out) $out[]=Array('title'=>'Tax:','value'=>0,'text'=>NULL);
      for ($i=0;isset($out[$i])&&isset($totals[$i]);$i++) if (isset($totals[$i]['id'])) $out[$i]['id']=$totals[$i]['id'];
      return $out;
    }
  }
?>
