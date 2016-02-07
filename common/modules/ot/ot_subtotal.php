<?php

  class ot_subtotal extends IXmodule {
    function calculateTotal($totals,&$order) {
      if (!$totals) $totals=Array(Array('title'=>'Sub-Total:'));
      $totals[0]['value']=$order->getSubTotal();
      $totals[0]['text']=NULL;
      return $totals;
    }
  }
  
?>
