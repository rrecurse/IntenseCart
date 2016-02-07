<?php
  class ot_shipping extends IXmodule {
    function calculateTotal($totals,&$order) {
      global $currencies;
      
      if ($totals) {
        $rs=Array();
	foreach ($totals AS $t) if ($t['value']>=0) {
	  $rs[]=$t;
	  $cost+=$t['value'];
	}
	$rf=$order->getShippingRefund($cost);
	if ($rf>0) $rs[]=Array('value'=>-$rf,'title'=>'Shipping Refund');
        return $rs;
      }

      if (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') {
        switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
          case 'national':
            if ($order->delivery['country_id'] == STORE_COUNTRY) $pass = true; break;
          case 'international':
            if ($order->delivery['country_id'] != STORE_COUNTRY) $pass = true; break;
          case 'both':
            $pass = true; break;
          default:
            $pass = false; break;
        }

        if ( ($pass == true) && ( ($order->info['total'] - $order->info['shipping_cost']) >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) ) {
          $order->info['shipping_method'] = $this->title;
//          $order->info['total'] -= $order->info['shipping_cost'];
          $order->info['shipping_cost'] = 0;
        }
      }

      $module = substr($GLOBALS['shipping']['id'], 0, strpos($GLOBALS['shipping']['id'], '_'));

      if (tep_not_null($order->info['shipping_method'])) {
        if ($GLOBALS[$module]->tax_class > 0) {
          $shipping_tax = tep_get_tax_rate($GLOBALS[$module]->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
          $shipping_tax_description = tep_get_tax_description($GLOBALS[$module]->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);

          $order->info['tax'] += tep_calculate_tax($order->info['shipping_cost'], $shipping_tax);
          $order->info['tax_groups']["$shipping_tax_description"] += tep_calculate_tax($order->info['shipping_cost'], $shipping_tax);
          $order->info['total'] += tep_calculate_tax($order->info['shipping_cost'], $shipping_tax);

          if (DISPLAY_PRICE_WITH_TAX == 'true') $order->info['shipping_cost'] += tep_calculate_tax($order->info['shipping_cost'], $shipping_tax);
        }

	$totals=Array(Array());
        $totals[0]['title']= $order->info['shipping_method'] . ':';
//        $totals[0]['value']= $order->info['shipping_cost'];
        $totals[0]['value']= $order->info['shipping_cost'];
        $totals[0]['text']= NULL;
      }
      return $totals;
    }
  }
?>
