<?php
  class ot_total extends IXmodule {
    function calculateTotal($totals,&$order) {
      if (!$totals) $totals=Array(Array('title' => $this->title . ':'));
      $totals[0]['value']=$order->info['total'];
      $totals[0]['text']=NULL;
      return $totals;
    }
  }
?>