<?php

  class shipping {
    var $modules;

// # class constructor
    function __construct($module = '') {
      global $language, $PHP_SELF;

      if (defined('MODULE_SHIPPING_INSTALLED') && tep_not_null(MODULE_SHIPPING_INSTALLED)) {
      
// BOF Separate Pricing Per Customer, next line original code
     //   $this->modules = explode(';', MODULE_SHIPPING_INSTALLED);
     global $sppc_customer_group_id, $customer_id;
     if(!tep_session_is_registered('sppc_customer_group_id')) {
     $customer_group_id = '0';
     } else {
      $customer_group_id = $sppc_customer_group_id;
     }
   $customer_shipment_query = tep_db_query("select IF(c.customers_shipment_allowed <> '', c.customers_shipment_allowed, cg.group_shipment_allowed) as shipment_allowed from " . TABLE_CUSTOMERS . " c, " . TABLE_CUSTOMERS_GROUPS . " cg where c.customers_id = '" . $customer_id . "' and cg.customers_group_id =  '" . $customer_group_id . "'");


   if ($customer_shipment = tep_db_fetch_array($customer_shipment_query)  ) {
	   if (tep_not_null($customer_shipment['shipment_allowed']) ) {
	  $temp_shipment_array = explode(';', $customer_shipment['shipment_allowed']);
	  $installed_modules = explode(';', MODULE_SHIPPING_INSTALLED);
	  for ($n = 0; $n < sizeof($installed_modules) ; $n++) {
		  // check to see if a shipping module is not de-installed
		  if ( in_array($installed_modules[$n], $temp_shipment_array ) ) {
			  $shipment_array[] = $installed_modules[$n];
		  }
	  } // end for loop
	  $this->modules = $shipment_array;
   } else {
	   $this->modules = explode(';', MODULE_SHIPPING_INSTALLED);
   }
   } else { // default
	   $this->modules = explode(';', MODULE_SHIPPING_INSTALLED);
   }
     // EOF Separate Pricing Per Customer

        $include_modules = array();

        if ( (tep_not_null($module)) && (in_array(substr($module['id'], 0, strpos($module['id'], '_')) . '.' . substr($PHP_SELF, (strrpos($PHP_SELF, '.')+1)), $this->modules)) ) {
          $include_modules[] = array('class' => substr($module['id'], 0, strpos($module['id'], '_')), 'file' => substr($module['id'], 0, strpos($module['id'], '_')) . '.' . substr($PHP_SELF, (strrpos($PHP_SELF, '.')+1)));
        } else {
          reset($this->modules);
          while (list(, $value) = each($this->modules)) {
            $class = substr($value, 0, strrpos($value, '.'));
            $include_modules[] = array('class' => $class, 'file' => $value);
          }
        }

        for ($i=0, $n=sizeof($include_modules); $i<$n; $i++) {
          include(DIR_WS_LANGUAGES . $language . '/modules/shipping/' . $include_modules[$i]['file']);
          include(DIR_WS_MODULES . 'shipping/' . $include_modules[$i]['file']);

          $GLOBALS[$include_modules[$i]['class']] = new $include_modules[$i]['class'];
        }
      }
    }

    function quote($method='', $module='', $options='', $use_negotiated_rates='') {
      global $total_weight, $shipping_weight, $shipping_quoted, $shipping_num_boxes;
      
	$this->weight_list = array();

      if (is_array($this->modules)) {

        $include_quotes = array();
        reset($this->modules);
        while (list(, $value) = each($this->modules)) {
          $class = substr($value, 0, strrpos($value, '.'));
          if (tep_not_null($module)) {
            if ( ($module == $class) && ($GLOBALS[$class]->enabled) ) {
              $include_quotes[] = $class;
            }
          } elseif ($GLOBALS[$class]->enabled) {
            $include_quotes[] = $class;
          }
        }
	
		// # multipackage shipping
        foreach (preg_split('/,/',$total_weight) AS $weight) {
          $shipping_quoted = '';
          $shipping_num_boxes = 1;
	  $qx_match=Array();
	  if (preg_match('/(\d+)x(.+)/',$weight,$qx_match)) {
	    $shipping_num_boxes=$qx_match[1];
	    $weight=$qx_match[2];
	  }
          $shipping_weight = $weight+0;

          if (SHIPPING_BOX_WEIGHT >= ($shipping_weight * SHIPPING_BOX_PADDING) / 100) {
            $shipping_weight = $shipping_weight;
          } else {
            $shipping_weight = $shipping_weight + ($shipping_weight*SHIPPING_BOX_PADDING/100);
          }

          if ($shipping_weight > SHIPPING_MAX_WEIGHT) { // Split into many boxes
            $shipping_num_boxes *= ceil($shipping_weight/SHIPPING_MAX_WEIGHT);
            $shipping_weight = $shipping_weight/$shipping_num_boxes;
          }
	  $this->weight_list[]=Array(qty=>$shipping_num_boxes,weight=>$shipping_weight);

    	  $quotes_array = array();
          $size = sizeof($include_quotes);
          for ($i=0; $i<$size; $i++) {
		if(is_array($options)) {
            $quotes = $GLOBALS[$include_quotes[$i]]->quote($method, $options, $use_negotiated_rates);
		} else {
			 $quotes = $GLOBALS[$include_quotes[$i]]->quote($method, $use_negotiated_rates);
		}
            if (is_array($quotes)) $quotes_array[] = $quotes;
          }
	  $quotes_merged = isset($quotes_merged) ? $this->merge_quote_list($quotes_merged,$quotes_array) : $quotes_array;
        }
      }
      return $quotes_merged;
    }
    
// By MegaJim
    function merge_quote($q1,$q2) {
      $q=Array();
      if ($q1['id']==$q2['id']) {
        $meth1=Array();
        $meth2=Array();
        foreach ($q1 AS $k => $v) {
          $q[$k]=$v;
        }
        foreach ($q2 AS $k => $v) {
          if (!isset($q[$k])) $q[$k]=$v;
        }
	$q['methods']=Array();
	if (is_array($q1['methods'])) {
	  foreach ($q1['methods'] AS $m) {
	    $meth1[$m['id']]=$m;
	  }
	}
	if (is_array($q2['methods'])) {
	  foreach ($q2['methods'] AS $m) {
	    $meth2[$m['id']]=$m;
	  }
	}
	foreach ($meth1 AS $mk =>$mv) {
	  if (isset($meth2[$mk])) {
	    $meth=Array();
	    foreach ($mv AS $k=>$v) {
	      $meth[$k]=$v;
	    }
	    foreach ($meth2[$mk] AS $k=>$v) {
	      if ($k=='cost') $meth[$k]+=$v;
	        else if (!isset($meth[$k])) $meth[$k]=$v;
	    }
	    $q['methods'][]=$meth;
	  }
	}
      }
      return $q;
    }

// By MegaJim
    function merge_quote_list($ql1,$ql2) {
      $ql=Array();
      foreach ($ql1 AS $k=>$q1) {
        if (is_array($ql2[$k])) $ql[]=$this->merge_quote($q1,$ql2[$k]);
      }
      return $ql;
    }


    function cheapest() {
      if (is_array($this->modules)) {
        $rates = array();

        reset($this->modules);
        while (list(, $value) = each($this->modules)) {
          $class = substr($value, 0, strrpos($value, '.'));
          if ($GLOBALS[$class]->enabled) {
            $quotes = $GLOBALS[$class]->quotes;
            for ($i=0, $n=sizeof($quotes['methods']); $i<$n; $i++) {
              if (isset($quotes['methods'][$i]['cost']) && tep_not_null($quotes['methods'][$i]['cost'])) {
                $rates[] = array('id' => $quotes['id'] . '_' . $quotes['methods'][$i]['id'],
                                 'title' => $quotes['module'] . ' (' . $quotes['methods'][$i]['title'] . ')',
                                 'cost' => $quotes['methods'][$i]['cost']);
              }
            }
          }
        }

        $cheapest = false;
        for ($i=0, $n=sizeof($rates); $i<$n; $i++) {
          if (is_array($cheapest)) {
            if ($rates[$i]['cost'] < $cheapest['cost']) {
              $cheapest = $rates[$i];
            }
          } else {
            $cheapest = $rates[$i];
          }
        }

        return $cheapest;
      }
    }
  }
?>
