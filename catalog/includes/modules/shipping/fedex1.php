<?php

  class fedex1 {
    var $code, $title, $description, $sort_order, $icon, $tax_class, $enabled, $meter, $intl;
    var $cart_total, $cart_total_per_one, $cart_weight, $cart_weight_per_one, $cart_shipping_num_boxes, $cart_qty;

// class constructor
    function fedex1() {
	global $order; //zone change
	
      $this->code = 'fedex1';
      $this->title = MODULE_SHIPPING_FEDEX1_TEXT_TITLE;
      $this->description = MODULE_SHIPPING_FEDEX1_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_SHIPPING_FEDEX1_SORT_ORDER;
      $this->icon = DIR_WS_ICONS . 'shipping_fedex.gif';
      $this->tax_class = MODULE_SHIPPING_FEDEX1_TAX_CLASS;
      $this->enabled = ((MODULE_SHIPPING_FEDEX1_STATUS == 'True') ? true : false);
      $this->meter = MODULE_SHIPPING_FEDEX1_METER;
	  
	  //zone change(s)
	        if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_FEDEX1_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_FEDEX1_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->delivery['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
	  //end

// You can comment out any methods you do not wish to quote by placing a // at the beginning of that line
// If you comment out 92 in either domestic or international, be
// sure and remove the trailing comma on the last non-commented line
      $this->domestic_types = array(
             '01' => 'Priority (by 10:30AM, later for rural)',
             '03' => '2 Day Air',
             '05' => 'Standard Overnight (by 3PM, later for rural)',
             '06' => 'First Overnight', 
             '20' => 'Express Saver (3 Day)',
             '90' => 'Home Delivery',
             '92' => 'Ground Service'
             );

      $this->international_types = array(
           //  '01' => 'International Priority (1-3 Days)',
             '03' => 'International Economy (4-5 Days)',
           //  '06' => 'International First',
             '90' => 'Home Delivery',
             '92' => 'Ground Service'
             );
    }

// class methods
    function quote($method = '') {
      global $shipping_weight, $shipping_num_boxes, $cart, $order;

      if (tep_not_null($method)) {
        $this->_setService($method);
      }
      if (defined("SHIPPING_ORIGIN_COUNTRY")) {
        $countries_array = tep_get_countries(SHIPPING_ORIGIN_COUNTRY, true);
        $this->country = $countries_array['countries_iso_code_2'];
      } else {
        $this->country = STORE_ORIGIN_COUNTRY;
      }
      

/////////////////////////////////////////////////////////
//    Was modified 21-04-2007 by
//    Dimon Gubanov aka dadsim,
//    simeon75@mail.ru 
/////////////////////////////////////////////////////////
 
//    Process items with disabled Ship-Separately's flag 
/*
      $this->cart_total = 0;
      $this->cart_total_per_one = 0;
      $this->cart_weight = 0;
      $this->cart_weight_per_one = 0;
      $this->cart_shipping_num_boxes = 0;
      $this->cart_qty = 0;
*/

      $this->cart_total = 0;
      $this->cart_total_per_one = 0;
      $this->cart_weight = $GLOBALS['shipping_weight'];
      $this->cart_weight_per_one = $GLOBALS['shipping_weight'];
      $this->cart_shipping_num_boxes = $GLOBALS['shipping_num_boxes'];
      $this->cart_qty = 1;

//      reset($cart->contents);
//      while (list($products_id, ) = each($cart->contents)){
//        $this->_CartCount($products_id);
//      }

      if ($this->cart_qty > 0){  
        if (SHIPPING_BOX_WEIGHT >= $this->cart_weight*SHIPPING_BOX_PADDING/100) {
          $this->cart_weight = $this->cart_weight+SHIPPING_BOX_WEIGHT;
        }
        else {
          $this->cart_weight = $this->cart_weight + ($this->cart_weight*SHIPPING_BOX_PADDING/100);
        }

        if ($this->cart_weight > SHIPPING_MAX_WEIGHT) { // Split into many boxes
          $this->cart_shipping_num_boxes = ceil($this->cart_weight/SHIPPING_MAX_WEIGHT);
          $cart_weight = $this->cart_weight/$this->cart_shipping_num_boxes;
        }
        else {
          $this->cart_shipping_num_boxes = 1;
          $cart_weight = $this->cart_weight;
        }

        if (MODULE_SHIPPING_FEDEX1_ENVELOPE == 'True') {
          if (($cart_weight <= .5 && MODULE_SHIPPING_FEDEX1_WEIGHT == 'LBS') ||
              ($cart_weight <= .2 && MODULE_SHIPPING_FEDEX1_WEIGHT == 'KGS')) {
            $this->_setPackageType('06');
          }
          else {
              $this->_setPackageType('01');
          }
        }
        else {
          $this->_setPackageType('01');
        }

        if ($this->packageType == '01' && $cart_weight < 1) {
          $this->_setWeight(1);
        } 
        else {
          $this->_setWeight($cart_weight);
        }

        $this->_setInsuranceValue($this->cart_total / $this->cart_shipping_num_boxes);

        $fedexQuote = $this->_getQuote();
      
        if (is_array($fedexQuote)) {
          if (isset($fedexQuote['error'])) {
            $this->quotes = array('module' => $this->title,
                                  'error' => $fedexQuote['error']);
          }
          else {
            $methods = array();
            foreach ($fedexQuote as $type => $cost) {
              $skip = FALSE;
              $this->surcharge = 0;
              if ($this->intl === FALSE) {
                if (strlen($type) > 2 && MODULE_SHIPPING_FEDEX1_TRANSIT == 'True') {
                  $service_descr = $this->domestic_types[substr($type,0,2)] . ' (' . substr($type,2,1) . ' days)';
                } 
                else {
                  $service_descr = $this->domestic_types[substr($type,0,2)];
                }
                switch (substr($type,0,2)) {
                  case 90:
                    if ($order->delivery['company'] != '') {
                      $skip = TRUE;
                    }
                    break;
                  case 92:
                    if ($this->country == "CA") {
                      if ($order->delivery['company'] == '') {
                        $this->surcharge = MODULE_SHIPPING_FEDEX1_RESIDENTIAL;
                      }
                    } else {
                      if ($order->delivery['company'] == '') {
                        $skip = TRUE;
                      }
                    }
                    break;
                  default:
                    if ($this->country != "CA" && substr($type,0,2) < "90" && $order->delivery['company'] == '') {
                      $this->surcharge = MODULE_SHIPPING_FEDEX1_RESIDENTIAL;
                    }
                    break;
                }
             } else {
                if (strlen($type) > 2 && MODULE_SHIPPING_FEDEX1_TRANSIT == 'True') {
                  $service_descr = $this->international_types[substr($type,0,2)] . ' (' . substr($type,2,1) . ' days)';
                } else {
                  $service_descr = $this->international_types[substr($type,0,2)];
                }
              }
              if ($method) {
                if (substr($type,0,2) != $method) $skip = TRUE;
              }
              if (!$skip) {
                $this->quotes['methods'][] = array('id' => substr($type,0,2),
                                                   'title' => $service_descr,
                                                   'cost' => (SHIPPING_HANDLING + MODULE_SHIPPING_FEDEX1_SURCHARGE + $this->surcharge + $cost) * $this->cart_shipping_num_boxes);
              }
            }

            if ($this->tax_class > 0) {
              $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
            }
          }
        } else {
          $this->quotes = array('module' => $this->title,
                                'error' => 'An error occured with the fedex shipping calculations.<br>Fedex may not deliver to your country, or your postal code may be wrong.');
        }
      }

//    Process items with enabled Ship-Separately's flag

      $total_qty = $this->cart_qty;
      reset($cart->contents);
      while (list($products_id, ) = each($cart->contents)){
        $this->cart_total_per_one = 0;
        $this->cart_weight_per_one = 0;
        $this->cart_qty = 0;
        $this->_CartCount($products_id,'1');
      
        if ($this->cart_qty > 0){
          $total_qty += $this->cart_qty;

          if (SHIPPING_BOX_WEIGHT >= $this->cart_weight_per_one*SHIPPING_BOX_PADDING/100) {
            $this->cart_weight_per_one = $this->cart_weight_per_one+SHIPPING_BOX_WEIGHT;
          }
          else {
            $this->cart_weight_per_one = $this->cart_weight_per_one + ($this->cart_weight_per_one*SHIPPING_BOX_PADDING/100);
          }

          $this->cart_shipping_num_boxes += $this->cart_qty;

          if (MODULE_SHIPPING_FEDEX1_ENVELOPE == 'True') {
            if (($this->cart_weight_per_one <= .5 && MODULE_SHIPPING_FEDEX1_WEIGHT == 'LBS') ||
                ($this->cart_weight_per_one <= .2 && MODULE_SHIPPING_FEDEX1_WEIGHT == 'KGS')) {
              $this->_setPackageType('06');
            }   
            else {
              $this->_setPackageType('01');
            }
          }
          else {
            $this->_setPackageType('01');
          }
        
          if ($this->packageType == '01' && $this->cart_weight_per_one < 1) {
            $this->_setWeight(1);
          } 
          else {
            $this->_setWeight($this->cart_weight_per_one);
          }
        
          $this->_setInsuranceValue($this->cart_total_per_one);
      
          $fedexQuote = $this->_getQuote();
                
          if (is_array($fedexQuote)) {
            if (isset($fedexQuote['error'])) {
              $this->quotes = array('module' => $this->title,
                                    'error' => $fedexQuote['error']);
            }
            else {
              foreach ($fedexQuote as $type => $cost) {
                $skip = FALSE;
                $this->surcharge = 0;
                if ($this->intl === FALSE) {
                  if (strlen($type) > 2 && MODULE_SHIPPING_FEDEX1_TRANSIT == 'True') {
                    $service_descr = $this->domestic_types[substr($type,0,2)] . ' (' . substr($type,2,1) . ' days)';
                  } 
                  else {
                    $service_descr = $this->domestic_types[substr($type,0,2)];
                  }
                  switch (substr($type,0,2)) {
                    case 90:
                      if ($order->delivery['company'] != '') {
                        $skip = TRUE;
                      }
                      break;
                    case 92:
                      if ($this->country == "CA") {
                        if ($order->delivery['company'] == '') {
                          $this->surcharge = MODULE_SHIPPING_FEDEX1_RESIDENTIAL;
                        }
                      }
                      else {
                        if ($order->delivery['company'] == '') {
                          $skip = TRUE;
                        }
                      }
                      break;
                    default:
                      if ($this->country != "CA" && substr($type,0,2) < "90" && $order->delivery['company'] == '') {
                        $this->surcharge = MODULE_SHIPPING_FEDEX1_RESIDENTIAL;
                      }
                      break;
                  }
                }
                else {
                  if (strlen($type) > 2 && MODULE_SHIPPING_FEDEX1_TRANSIT == 'True') {
                    $service_descr = $this->international_types[substr($type,0,2)] . ' (' . substr($type,2,1) . ' days)';
                  }
                  else {
                    $service_descr = $this->international_types[substr($type,0,2)];
                  }
                }
                if ($method) {
                  if (substr($type,0,2) != $method)
                  $skip = TRUE;
                }
                if (!$skip) {
                  $new_method_flag = true;
                  if(is_array($this->quotes['methods'])){
                    foreach($this->quotes['methods'] as $key => $value){
                      if($this->quotes['methods'][$key]['id'] == substr($type,0,2)){
                        $this->quotes['methods'][$key]['cost'] += (SHIPPING_HANDLING + MODULE_SHIPPING_FEDEX1_SURCHARGE + $this->surcharge + $cost) * $this->cart_qty;
                        $new_method_flag = false;
                      }
                    }
                  }
                  if($new_method_flag){
                    $this->quotes['methods'][] = array('id' => substr($type,0,2),
                                                       'title' => $service_descr,
                                                       'cost' => (SHIPPING_HANDLING + MODULE_SHIPPING_FEDEX1_SURCHARGE + $this->surcharge + $cost) * $this->cart_qty);
                  }
                }             
              }             
            }             
          }           
          else {
            $this->quotes = array('module' => $this->title,
                                  'error' => 'An error occured with the fedex shipping calculations.<br>Fedex may not deliver to your country, or your postal code may be wrong.');
          }
        }
      } 
      
      if ((MODULE_SHIPPING_FEDEX1_MAX_WEIGHT != 'NONE' && MODULE_SHIPPING_FEDEX1_MAX_WEIGHT != '' && $this->cart_weight > MODULE_SHIPPING_FEDEX1_MAX_WEIGHT) || (MODULE_SHIPPING_FEDEX1_MAX_QUANTITY_OF_SHIP_BOXES != 'NONE' && MODULE_SHIPPING_FEDEX1_MAX_QUANTITY_OF_SHIP_BOXES != '' && $this->cart_shipping_num_boxes > MODULE_SHIPPING_FEDEX1_MAX_QUANTITY_OF_SHIP_BOXES))
        return false;


      if(ctype_digit(MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_WEIGHT) && ctype_digit(MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_WEIGHT) && (ctype_digit(MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_PERCENTAGE) || ctype_digit(MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_PERCENTAGE)) && is_array($this->quotes['methods'])){
        foreach($this->quotes['methods'] as $key => $value){
          if($this->cart_weight > MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_WEIGHT && $this->cart_weight < MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_WEIGHT && ctype_digit(MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_PERCENTAGE) && $order->delivery['company'] != ''){
            $this->quotes['methods'][$key]['cost'] = $this->quotes['methods'][$key]['cost']*(1 - MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_PERCENTAGE / 100);
          }
          elseif($this->cart_weight > MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_WEIGHT && ctype_digit(MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_PERCENTAGE) && $order->delivery['company'] != ''){
            $this->quotes['methods'][$key]['cost'] = $this->quotes['methods'][$key]['cost']*(1 - MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_PERCENTAGE / 100);
          }
        }
      }
      
      $this->quotes['id'] = $this->code;
// Begin truncate to 2 decimals //
	  $c_e = explode(".",$this->cart_weight);
      $this->quotes['module'] = $this->title . " (Total items: " . $total_qty . ' pcs. Total weight: '.$c_e[0].((isset($c_e[1]) == FALSE)? "" : ".".substr($c_e[1],0,2)) .' '.strtolower(MODULE_SHIPPING_FEDEX1_WEIGHT).')';
// End truncate to 2 decimals //		
/////////////////////////////////////////////////////
//    End of dadsim's modification
///////////////////////////////////////////////////// 

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      return $this->quotes;
    }

    function check() {
      if (!isset($this->_check)) {
        $this->_check=true;
//        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_FEDEX1_STATUS'");
//        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Fedex Shipping', 'MODULE_SHIPPING_FEDEX1_STATUS', 'True', 'Do you want to offer Fedex shipping?', '6', '10', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Transit Times', 'MODULE_SHIPPING_FEDEX1_TRANSIT', 'True', 'Do you want to show transit times for ground or home delivery rates?', '6', '10', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Your Fedex Account Number', 'MODULE_SHIPPING_FEDEX1_ACCOUNT', 'NONE', 'Enter the fedex Account Number assigned to you, required', '6', '11', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Your Fedex Meter ID', 'MODULE_SHIPPING_FEDEX1_METER', 'NONE', 'Enter the Fedex MeterID assigned to you, set to NONE to obtain a new meter number', '6', '12', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('cURL Path', 'MODULE_SHIPPING_FEDEX1_CURL', 'NONE', 'Enter the path to the cURL program, normally, leave this set to NONE to execute cURL using PHP', '6', '12', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Debug Mode', 'MODULE_SHIPPING_FEDEX1_DEBUG', 'False', 'Turn on Debug', '6', '19', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Weight Units', 'MODULE_SHIPPING_FEDEX1_WEIGHT', 'LBS', 'Weight Units:', '6', '19', 'tep_cfg_select_option(array(\'LBS\', \'KGS\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('First line of street address', 'MODULE_SHIPPING_FEDEX1_ADDRESS_1', 'NONE', 'Enter the first line of your ship from street address, required', '6', '13', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Second line of street address', 'MODULE_SHIPPING_FEDEX1_ADDRESS_2', 'NONE', 'Enter the second line of your ship from street address, leave set to NONE if you do not need to specify a second line', '6', '14', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('City name', 'MODULE_SHIPPING_FEDEX1_CITY', 'NONE', 'Enter the city name for the ship from street address, required', '6', '15', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('State or Province name', 'MODULE_SHIPPING_FEDEX1_STATE', 'NONE', 'Enter the 2 letter state or province name for the ship from street address, required for Canada and US', '6', '16', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Postal code', 'MODULE_SHIPPING_FEDEX1_POSTAL', 'NONE', 'Enter the postal code for the ship from street address, required', '6', '17', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Phone number', 'MODULE_SHIPPING_FEDEX1_PHONE', 'NONE', 'Enter a contact phone number for your company, required', '6', '18', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Which server to use', 'MODULE_SHIPPING_FEDEX1_SERVER', 'production', 'You must have an account with Fedex', '6', '19', 'tep_cfg_select_option(array(\'test\', \'production\'), ', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Drop off type', 'MODULE_SHIPPING_FEDEX1_DROPOFF', '1', 'Dropoff type (1 = Regular pickup, 2 = request courier, 3 = drop box, 4 = drop at BSC, 5 = drop at station)?', '6', '20', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fedex surcharge?', 'MODULE_SHIPPING_FEDEX1_SURCHARGE', '0', 'Surcharge amount to add to shipping charge?', '6', '21', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Show List Rates?', 'MODULE_SHIPPING_FEDEX1_LIST_RATES', 'False', 'Show LIST Rates?', '6', '21', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Residential surcharge?', 'MODULE_SHIPPING_FEDEX1_RESIDENTIAL', '0', 'Residential Surcharge (in addition to other surcharge) for Express packages within US, or ground packages within Canada?', '6', '21', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Insurance?', 'MODULE_SHIPPING_FEDEX1_INSURE', 'NONE', 'Insure packages over what dollar amount?', '6', '22', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Envelope Rates?', 'MODULE_SHIPPING_FEDEX1_ENVELOPE', 'False', 'Do you want to offer Fedex Envelope rates? All items under 1/2 LB (.23KG) will quote using the envelope rate if True.', '6', '10', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sort rates: ', 'MODULE_SHIPPING_FEDEX1_WEIGHT_SORT', 'High to Low', 'Sort rates top to bottom: ', '6', '19', 'tep_cfg_select_option(array(\'High to Low\', \'Low to High\'), ', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Timeout in Seconds', 'MODULE_SHIPPING_FEDEX1_TIMEOUT', 'NONE', 'Enter the maximum time in seconds you would wait for a rate request from Fedex? Leave NONE for default timeout.', '6', '22', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_FEDEX1_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '23', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_FEDEX1_SORT_ORDER', '0', 'Sort order of display.', '6', '24', now())");
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Zone', 'MODULE_SHIPPING_FEDEX1_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())"); //zone change

/////////////////////////////////////////////////////////
//    Was modified 25-05-2007 by
//    Dimon Gubanov aka dadsim,
//    simeon75@mail.ru 
/////////////////////////////////////////////////////////

        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Max. weight', 'MODULE_SHIPPING_FEDEX1_MAX_WEIGHT', 'NONE', 'Enter the maximum weight when module becomes disabled.', '6', '22', now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Max. quantity of ship boxes', 'MODULE_SHIPPING_FEDEX1_MAX_QUANTITY_OF_SHIP_BOXES', 'NONE', 'Enter the maximum quantity of ship boxes when module becomes disabled.', '6', '22', now())");
 	    tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Tier 1 Discount Weight', 'MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_WEIGHT', 'NONE', 'Enter the weight threshold for Tier 1 Ground MultiWeight Discount.', '6', '24', now())");
 	    tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Tier 2 Discount Weight', 'MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_WEIGHT', 'NONE', 'Enter the weight threshold for Tier 2 Ground MultiWeight Discount.', '6', '25', now())");
 	    tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Tier 1 Discount Percent', 'MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_PERCENTAGE', 'NONE', 'Enter the integer number of discount percentage for Tier 1 Ground MultiWeight Discount.', '6', '26', now())");
 	    tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Tier 2 Discount Percent', 'MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_PERCENTAGE', 'NONE', 'Enter the integer number of discount percentage for Tier 2 Ground MultiWeight Discount.', '6', '27', now())");
 	    tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Saturday Shipment', 'MODULE_SHIPPING_FEDEX1_SATURDAY_SHIPMENT', 'True', 'Saturday Shipment enabled or disabled?', '6', '28', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Insurance Currency Type', 'MODULE_SHIPPING_FEDEX1_INSURANCE_CURRENCY', 'USD', 'Enter USD if you are shipping your products from the US or CAD if you are shipping your products from Canada', '6', '29', now())");      


/////////////////////////////////////////////////////
//    End of dadsim's modification
///////////////////////////////////////////////////// 

    }

    function remove() {
      tep_db_query("delete from " . TABLE_CORE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {

/////////////////////////////////////////////////////////
//    Was modified 25-05-2007 by
//    Dimon Gubanov aka dadsim,
//    simeon75@mail.ru 
/////////////////////////////////////////////////////////

      return array('MODULE_SHIPPING_FEDEX1_STATUS', 'MODULE_SHIPPING_FEDEX1_ACCOUNT', 'MODULE_SHIPPING_FEDEX1_METER', 'MODULE_SHIPPING_FEDEX1_CURL', 'MODULE_SHIPPING_FEDEX1_DEBUG', 'MODULE_SHIPPING_FEDEX1_WEIGHT', 'MODULE_SHIPPING_FEDEX1_SERVER', 'MODULE_SHIPPING_FEDEX1_ADDRESS_1', 'MODULE_SHIPPING_FEDEX1_ADDRESS_2', 'MODULE_SHIPPING_FEDEX1_CITY', 'MODULE_SHIPPING_FEDEX1_STATE', 'MODULE_SHIPPING_FEDEX1_POSTAL', 'MODULE_SHIPPING_FEDEX1_PHONE', 'MODULE_SHIPPING_FEDEX1_DROPOFF', 'MODULE_SHIPPING_FEDEX1_TRANSIT', 'MODULE_SHIPPING_FEDEX1_SURCHARGE', 'MODULE_SHIPPING_FEDEX1_LIST_RATES', 'MODULE_SHIPPING_FEDEX1_INSURE', 'MODULE_SHIPPING_FEDEX1_RESIDENTIAL', 'MODULE_SHIPPING_FEDEX1_ENVELOPE', 'MODULE_SHIPPING_FEDEX1_WEIGHT_SORT', 'MODULE_SHIPPING_FEDEX1_TIMEOUT', 'MODULE_SHIPPING_FEDEX1_MAX_WEIGHT','MODULE_SHIPPING_FEDEX1_MAX_QUANTITY_OF_SHIP_BOXES','MODULE_SHIPPING_FEDEX1_TAX_CLASS', 'MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_WEIGHT', 'MODULE_SHIPPING_FEDEX1_TIER1_DISCOUNT_PERCENTAGE', 'MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_WEIGHT', 'MODULE_SHIPPING_FEDEX1_TIER2_DISCOUNT_PERCENTAGE', 'MODULE_SHIPPING_FEDEX1_SATURDAY_SHIPMENT', 'MODULE_SHIPPING_FEDEX1_SORT_ORDER','MODULE_SHIPPING_FEDEX1_INSURANCE_CURRENCY', 'MODULE_SHIPPING_FEDEX1_ZONE'); //zone change

/////////////////////////////////////////////////////
//    End of dadsim's modification
///////////////////////////////////////////////////// 

    }

    function _setService($service) {
      $this->service = $service;
    }

    function _setWeight($pounds) {
      $this->pounds = sprintf("%01.1f", $pounds);
    }

    function _setPackageType($type) {
      $this->packageType = $type;
    }

    function _setInsuranceValue($order_amount) {
      if ($order_amount > MODULE_SHIPPING_FEDEX1_INSURE) {
        $this->insurance = sprintf("%01.2f",$order_amount);
      } else {
        $this->insurance = 0;
      }
    }

    function _AccessFedex($data) {

      if (MODULE_SHIPPING_FEDEX1_SERVER == 'production') {
        $this->server = 'gateway.fedex.com/GatewayDC';
      } else {
        $this->server = 'gatewaybeta.fedex.com/GatewayDC';
      }
      if (MODULE_SHIPPING_FEDEX1_CURL == "NONE") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->server);
        if (MODULE_SHIPPING_FEDEX1_TIMEOUT != 'NONE') curl_setopt($ch, CURLOPT_TIMEOUT, MODULE_SHIPPING_FEDEX1_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
///// Begin: If you are hosting with Godaddy.com: you need to un-remark the following 3 lines: /////

//		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
//		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
//		curl_setopt($ch, CURLOPT_PROXY, "64.202.165.130:3128");

///// End Godaddy.com settings /////
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Referer: " . STORE_NAME,
                                                   "Host: " . $this->server,
                                                   "Accept: image/gif,image/jpeg,image/pjpeg,text/plain,text/html,*/*",
                                                   "Pragma:",
                                                   "Content-Type:image/gif"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $reply = curl_exec($ch);
        curl_close ($ch);
      } else {
        $this->command_line = MODULE_SHIPPING_FEDEX1_CURL . " " . (MODULE_SHIPPING_FEDEX1_TIMEOUT == 'NONE' ? '' : '-m ' . MODULE_SHIPPING_FEDEX1_TIMEOUT) . " -s -e '" . STORE_NAME . "' --url https://" . $this->server . " -H 'Host: " . $this->server . "' -H 'Accept: image/gif,image/jpeg,image/pjpeg,text/plain,text/html,*/*' -H 'Pragma:' -H 'Content-Type:image/gif' -d '" . $data . "' 'https://" . $this->server . "'";
        exec($this->command_line, $this->reply);
        $reply = $this->reply[0];
      }
        return $reply;
    }

    function _getMeter() {
      $data = '0,"211"'; // Transaction Code, required
      $data .= '10,"' . MODULE_SHIPPING_FEDEX1_ACCOUNT . '"'; // Sender Fedex account number
      $data .= '4003,"' . STORE_OWNER . '"'; // Subscriber contact name
      $data .= '4007,"' . STORE_NAME . '"'; // Subscriber company name
      $data .= '4008,"' . MODULE_SHIPPING_FEDEX1_ADDRESS_1 . '"'; // Subscriber Address line 1
      if (MODULE_SHIPPING_FEDEX1_ADDRESS_2 != 'NONE') {
        $data .= '4009,"' . MODULE_SHIPPING_FEDEX1_ADDRESS_2 . '"'; // Subscriber Address Line 2
      }
      $data .= '4011,"' . MODULE_SHIPPING_FEDEX1_CITY . '"'; // Subscriber City Name
      if (MODULE_SHIPPING_FEDEX1_STATE != 'NONE') {
        $data .= '4012,"' . MODULE_SHIPPING_FEDEX1_STATE . '"'; // Subscriber State code
      }
      $data .= '4013,"' . MODULE_SHIPPING_FEDEX1_POSTAL . '"'; // Subscriber Postal Code
      $data .= '4014,"' . $this->country . '"'; // Subscriber Country Code
      $data .= '4015,"' . MODULE_SHIPPING_FEDEX1_PHONE . '"'; // Subscriber phone number
      $data .= '99,""'; // End of Record, required
      if (MODULE_SHIPPING_FEDEX1_DEBUG == 'True') echo "Data sent to Fedex for Meter: " . $data . "<br>";
      $fedexData = $this->_AccessFedex($data);
      if (MODULE_SHIPPING_FEDEX1_DEBUG == 'True') echo "Data returned from Fedex for Meter: " . $fedexData . "<br>";
      $meterStart = strpos($fedexData,'"498,"');

      if ($meterStart === FALSE) {
        if (strlen($fedexData) == 0) {
          $this->error_message = 'No response to CURL from Fedex server, check CURL availability, or maybe timeout was set too low, or maybe the Fedex site is down';
        } else {
          $fedexData = $this->_ParseFedex($fedexData);
          $this->error_message = 'No meter number was obtained, check configuration. Error ' . $fedexData['2'] . ' : ' . $fedexData['3'];
        }
        return false;
      }
    
      $meterStart += 6;
      $meterEnd = strpos($fedexData, '"', $meterStart);
      $this->meter = substr($fedexData, $meterStart, $meterEnd - $meterStart);
      $meter_sql = "UPDATE configuration SET configuration_value=\"" . $this->meter . "\" where configuration_key=\"MODULE_SHIPPING_FEDEX1_METER\"";
      tep_db_query($meter_sql);

      return true;
    }

    function _ParseFedex($data) {
      $current = 0;
      $length = strlen($data);
      $resultArray = array();
      while ($current < $length) {
        $endpos = strpos($data, ',', $current);
        if ($endpos === FALSE) { break; }
        $index = substr($data, $current, $endpos - $current);
        $current = $endpos + 2;
        $endpos = strpos($data, '"', $current);
        $resultArray[$index] = substr($data, $current, $endpos - $current);
        $current = $endpos + 1;
      }
    return $resultArray;
    }
     
    function _getQuote() {
      global $order, $customer_id, $sendto;

      if (MODULE_SHIPPING_FEDEX1_ACCOUNT == "NONE" || strlen(MODULE_SHIPPING_FEDEX1_ACCOUNT) == 0) {
        return array('error' => 'You forgot to set up your Fedex account number, this can be set up in Admin -> Modules -> Shipping');
      }
      if (MODULE_SHIPPING_FEDEX1_ADDRESS_1 == "NONE" || strlen(MODULE_SHIPPING_FEDEX1_ADDRESS_1) == 0) {
        return array('error' => 'You forgot to set up your ship from street address line 1, this can be set up in Admin -> Modules -> Shipping');
      }
      if (MODULE_SHIPPING_FEDEX1_CITY == "NONE" || strlen(MODULE_SHIPPING_FEDEX1_CITY) == 0) {
        return array('error' => 'You forgot to set up your ship from City, this can be set up in Admin -> Modules -> Shipping');
      }
      if (MODULE_SHIPPING_FEDEX1_POSTAL == "NONE" || strlen(MODULE_SHIPPING_FEDEX1_POSTAL) == 0) {
        return array('error' => 'You forgot to set up your ship from postal code, this can be set up in Admin -> Modules -> Shipping');
      }
      if (MODULE_SHIPPING_FEDEX1_PHONE == "NONE" || strlen(MODULE_SHIPPING_FEDEX1_PHONE) == 0) {
        return array('error' => 'You forgot to set up your ship from phone number, this can be set up in Admin -> Modules -> Shipping');
      }
      if (MODULE_SHIPPING_FEDEX1_METER == "NONE") { 
        if ($this->_getMeter() === false) return array('error' => $this->error_message);
      }

      $data = '0,"25"'; // TransactionCode
      $data .= '10,"' . MODULE_SHIPPING_FEDEX1_ACCOUNT . '"'; // Sender fedex account number
      $data .= '498,"' . $this->meter . '"'; // Meter number
      $data .= '8,"' . MODULE_SHIPPING_FEDEX1_STATE . '"'; // Sender state code
      $orig_zip = str_replace(array(' ', '-'), '', MODULE_SHIPPING_FEDEX1_POSTAL);
      $data .= '9,"' . $orig_zip . '"'; // Origin postal code
      $data .= '117,"' . $this->country . '"'; // Origin country
      $dest_zip = str_replace(array(' ', '-'), '', $order->delivery['postcode']);
      $data .= '17,"' . $dest_zip . '"'; // Recipient zip code
      if ($order->delivery['country']['iso_code_2'] == "US" || $order->delivery['country']['iso_code_2'] == "CA" || $order->delivery['country']['iso_code_2'] == "PR") {
        $state .= tep_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], ''); // Recipient state
        if ($state == "QC") $state = "PQ";
        $data .= '16,"' . $state . '"'; // Recipient state
      }
      $data .= '50,"' . $order->delivery['country']['iso_code_2'] . '"'; // Recipient country
/////////////////////////////////////////////////////////
//    Was modified 25-05-2007 by
//    Dimon Gubanov aka dadsim,
//    simeon75@mail.ru 
/////////////////////////////////////////////////////////
      if(MODULE_SHIPPING_FEDEX1_SATURDAY_SHIPMENT == 'False'){
        $dataInfo = tep_db_fetch_array(tep_db_query("select case when weekday(now()) = '5' then date_format(date_add(now(), interval 2 day),'%Y%m%d') else date_format(now( ),'%Y%m%d') end as data"));
        $data .= '24,"'.$dataInfo['data'].'"'; // Ship Date
      }
/////////////////////////////////////////////////////
//    End of dadsim's modification
///////////////////////////////////////////////////// 
      $data .= '75,"' . MODULE_SHIPPING_FEDEX1_WEIGHT . '"'; // Weight units
      if (MODULE_SHIPPING_FEDEX1_WEIGHT == "KGS") {
        $data .= '1116,"C"'; // Dimension units
      } else {
        $data .= '1116,"I"'; // Dimension units
      }
      $data .= '1401,"' . $this->pounds . '"'; // Total weight
      $data .= '1529,"1"'; // Quote discounted rates
      if ($this->insurance > 0) {
        $data .= '1415,"' . $this->insurance . '"'; // Insurance value
        $data .= '68,"' . MODULE_SHIPPING_FEDEX1_INSURANCE_CURRENCY . '"'; // Insurance value currency
      }
      if ($order->delivery['company'] == '' && MODULE_SHIPPING_FEDEX1_RESIDENTIAL == 0) {
        $data .= '440,"Y"'; // Residential address
      }else {
        $data .= '440,"N"'; // Business address, use if adding a residential surcharge
      }
      $data .= '1273,"' . $this->packageType . '"'; // Package type
      $data .= '1333,"' . MODULE_SHIPPING_FEDEX1_DROPOFF . '"'; // Drop of drop off or pickup
      if (MODULE_SHIPPING_FEDEX1_LIST_RATES == 'True') {
        $data .= '1529,"2"'; // Also return list rates
      }
      $data .= '99,""'; // End of record
      if (MODULE_SHIPPING_FEDEX1_DEBUG == 'True') echo "Data sent to Fedex for Rating: " . $data . "<br>";
      $fedexData = $this->_AccessFedex($data);
      if (MODULE_SHIPPING_FEDEX1_DEBUG == 'True') echo "Data returned from Fedex for Rating: " . $fedexData . "<br>";
      if (strlen($fedexData) == 0) {
        $this->error_message = 'No data returned from Fedex, perhaps the Fedex site is down';
        return array('error' => $this->error_message);
      }
      $fedexData = $this->_ParseFedex($fedexData);
      $i = 1;
/*      if ($this->country == $order->delivery['country']['iso_code_2']) {
        $this->intl = FALSE;
      } else {
        $this->intl = TRUE;
      } */
// Begin add a check for US territories and switch to international shipping labels 
	  if ($state == "VI" || $state == "GU" || $state == "PR" || $state == "MH") {
        $this->intl = TRUE;
//        } elseif ($this->country == $order->delivery['country']['iso_code_2']) {
//        $this->intl = FALSE;
        } else {
        $this->intl = TRUE;
        }
	
// End add a check for US territories and switch to international shipping labels 
      $rates = NULL;
      while (isset($fedexData['1274-' . $i])) {
        if ($this->intl) {
          if (isset($this->international_types[$fedexData['1274-' . $i]])) {
            if (MODULE_SHIPPING_FEDEX1_LIST_RATES == 'False') {
              if (isset($fedexData['3058-' . $i])) {
                $rates[$fedexData['1274-' . $i] . $fedexData['3058-' . $i]] = $fedexData['1419-' . $i];
              } else {
                $rates[$fedexData['1274-' . $i]] = $fedexData['1419-' . $i];
              }
            } else {
              if (isset($fedexData['3058-' . $i])) {
                $rates[$fedexData['1274-' . $i] . $fedexData['3058-' . $i]] = $fedexData['1528-' . $i];
              } else {
                $rates[$fedexData['1274-' . $i]] = $fedexData['1528-' . $i];
              }
            }
          }
        } else {
          if (isset($this->domestic_types[$fedexData['1274-' . $i]])) {
            if (MODULE_SHIPPING_FEDEX1_LIST_RATES == 'False') {
              if (isset($fedexData['3058-' . $i])) {
                $rates[$fedexData['1274-' . $i] . $fedexData['3058-' . $i]] = $fedexData['1419-' . $i];
              } else {
                $rates[$fedexData['1274-' . $i]] = $fedexData['1419-' . $i];
              }
            } else {
              if (isset($fedexData['3058-' . $i])) {
                $rates[$fedexData['1274-' . $i] . $fedexData['3058-' . $i]] = $fedexData['1528-' . $i];
              } else {
                $rates[$fedexData['1274-' . $i]] = $fedexData['1528-' . $i];
              }
            }
          }
        }
        $i++;
      }

      if (is_array($rates)) {
        if (MODULE_SHIPPING_FEDEX1_WEIGHT_SORT == 'Low to High') {
          arsort($rates);
        } else {
          asort($rates);
        }
      } else {
        $this->error_message = 'No Rates Returned, ' . $fedexData['2'] . ' : ' . $fedexData['3']; 
        return array('error' => $this->error_message);
      }

      return ((sizeof($rates) > 0) ? $rates : false);
    }
    
/////////////////////////////////////////////////////////
//    Was modified 23-11-2006 by
//    Dimon Gubanov aka dadsim,
//    simeon75@mail.ru 
/////////////////////////////////////////////////////////

    function _CartCount($products_id, $ship_sep='0'){
      global $cart;
      // products price
      $qty = $cart->contents[$products_id]['qty'];
//      $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, products_weight from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "' and products_ship_sep = '".$ship_sep."'");
      $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, products_weight from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
      if ($product = tep_db_fetch_array($product_query)) {
        $prid = $product['products_id'];
        $products_tax = tep_get_tax_rate($product['products_tax_class_id']);
        $products_price = $product['products_price'];
        $products_weight = $product['products_weight'];
        
        $specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$prid . "' and status = '1'");
        if (tep_db_num_rows ($specials_query)) {
          $specials = tep_db_fetch_array($specials_query);
          $products_price = $specials['specials_new_products_price'];
        }
          
        $this->cart_qty += $qty;
        $this->cart_total += tep_add_tax($products_price, $products_tax) * $qty;
        $this->cart_total_per_one += tep_add_tax($products_price, $products_tax);
        $this->cart_weight += ($qty * $products_weight);
        $this->cart_weight_per_one += $products_weight;
      }
          
      // attributes price
      if (isset($cart->contents[$products_id]['attributes'])) {
        reset($cart->contents[$products_id]['attributes']);
        while (list($option, $value) = each($cart->contents[$products_id]['attributes'])) {
          $attribute_price_query = tep_db_query("select options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int)$prid . "' and options_id = '" . (int)$option . "' and options_values_id = '" . (int)$value . "'");
          $attribute_price = tep_db_fetch_array($attribute_price_query);
          if ($attribute_price['price_prefix'] == '+') {
            $this->cart_total += $qty * tep_add_tax($attribute_price['options_values_price'], $products_tax);
            $this->cart_total_per_one += tep_add_tax($attribute_price['options_values_price'], $products_tax);
          }
          else {
            $this->cart_total -= $qty * tep_add_tax($attribute_price['options_values_price'], $products_tax);
            $this->cart_total_per_one -= tep_add_tax($attribute_price['options_values_price'], $products_tax);
          }
        }
      }      
    }
  }

/////////////////////////////////////////////////////
//    End of dadsim's modification
///////////////////////////////////////////////////// 

?>
