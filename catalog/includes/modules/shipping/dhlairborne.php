<?php

  class dhlairborne {
    var $code, $title, $quote_title, $description, $icon, $enabled, $debug, $types, $allowed_methods;
    
    // DHL/Airborne Vars
    var $service;
    var $container;
    var $shipping_day;
    var $weight;
    var $dimensions;
    var $length;
    var $width;
    var $height;
    var $destination_street_address;
    var $destination_city;
    var $destination_state;
    var $destination_postal;
    var $destination_country;
    var $additionalProtection;

    function dhlairborne() {
      global $order;

      $this->code = 'dhlairborne';
      $this->title = MODULE_SHIPPING_AIRBORNE_TEXT_TITLE;
      $this->quote_title = MODULE_SHIPPING_AIRBORNE_TEXT_QUOTE_TITLE;
      $this->description = MODULE_SHIPPING_AIRBORNE_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_SHIPPING_AIRBORNE_SORT_ORDER;
/*    $this->icon = DIR_WS_ICONS . MODULE_SHIPPING_AIRBORNE_ICON; */
      $this->icon = DIR_WS_ICONS . 'shipping_dhl.gif';
      $this->tax_class = MODULE_SHIPPING_AIRBORNE_TAX_CLASS;
      $this->debug = ((MODULE_SHIPPING_AIRBORNE_DEBUG == 'True') ? true : false);
      $this->enabled = ((MODULE_SHIPPING_AIRBORNE_STATUS == 'True') ? true : false);


      if (($this->enabled == true) && ((int)MODULE_SHIPPING_AIRBORNE_ZONE > 0)) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . 
            " where geo_zone_id = '" . MODULE_SHIPPING_AIRBORNE_ZONE . "' and zone_country_id = '" . 
            $order->delivery['country']['id'] . "' order by zone_id");
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

      $this->types = array('G' => MODULE_SHIPPING_AIRBORNE_TEXT_GROUND,
                           'S' => MODULE_SHIPPING_AIRBORNE_TEXT_SECOND_DAY,
                           'N' => MODULE_SHIPPING_AIRBORNE_TEXT_NEXT_AFTERNOON,
                           'E' => MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS,
                           'E 10:30AM' => MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS_1030,
                           'E SAT' => MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS_SAT,
                           'IE' => MODULE_SHIPPING_AIRBORNE_TEXT_INTERNATIONAL_EXPRESS);
    }

// class methods
    function quote($method = '') {
      global $order, $shipping_weight, $shipping_num_boxes;
      
      
      if ((tep_not_null($method)) && (isset($this->types[$method]))) {
        $this->_setService($method);
      }
 
      $this->_setMethods(MODULE_SHIPPING_AIRBORNE_TYPES);	  
      $this->_setDestination($order->delivery['street_address'], $order->delivery['city'], $order->delivery['state'], $order->delivery['postcode'], $order->delivery['country']['iso_code_2']);
      $this->_setContainer(MODULE_SHIPPING_AIRBORNE_PACKAGE);
      $this->_setWeight($shipping_weight);
      $this->_setShippingDay(MODULE_SHIPPING_AIRBORNE_DAYS_TO_SHIP, MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY);
      if (MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_WEIGHT == 'true') $this->_setDimensions(MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_EXCLUSIVE);
      if (MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION == 'true') $this->_setAdditionalProtection(MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_VALUE);

      $dhlAirborneQuotes = $this->_getQuote();

      if (is_array($dhlAirborneQuotes)) {
        if (MODULE_SHIPPING_AIRBORNE_SHIP_WEIGHT=='true') {
          // Wi-Gear Changed in v2.2 - Made shipping weight to title optional
          $module = $this->quote_title . ' (' . $shipping_num_boxes . ' x ' . $this->weight . 'lbs)';
        } else {
          $module = $this->quote_title;
        }
        if (isset($dhlAirborneQuotes['error'])) {
          $this->quotes = array('module' => $module,
                                'error' => $dhlAirborneQuotes['error']);
        } else {
          $this->quotes = array('id' => $this->code,
                                'module' => $module);

          $methods = array();
          foreach ($dhlAirborneQuotes as $dhlAirborneQuote) {
            list($type, $cost) = each($dhlAirborneQuote);
if($cost!='0.00') {
            $methods[] = array('id' => $type,
                               'title' => ((isset($this->types[$type])) ? $this->types[$type] : $type) . $dhlAirborneQuote['description'],
                               'cost' => ($cost * $shipping_num_boxes) + MODULE_SHIPPING_AIRBORNE_HANDLING);
          }
}

          $this->quotes['methods'] = $methods;

          if ($this->tax_class > 0) {
            $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
          }
        }
      } else {
        $this->quotes = array('module' => $this->quote_title,
                              'error' => MODULE_SHIPPING_AIRBORNE_TEXT_ERROR);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->quote_title);

      return $this->quotes;
    }
    
    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CORE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_AIRBORNE_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
//
// Add in config variable for International Shipping key
//
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable DHL/Airborne Shipping', 'MODULE_SHIPPING_AIRBORNE_STATUS', 'True', 'Do you want to offer DHL/Airborne shipping?<p><a onClick=\"javascript:window.open(\'dhlairborne_docs.html\',\'dhlairborne_docs\',\'height=375,width=550,toolbar=no,statusbar=no,scrollbars=yes,screenX=150,screenY=150,top=150,left=150\');\"><u>Help</u> [?]</a>', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('DHL/Airborne system ID', 'MODULE_SHIPPING_AIRBORNE_SYSTEMID', '', 'Enter your DHL/Airborne system ID.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('DHL/Airborne password', 'MODULE_SHIPPING_AIRBORNE_PASS', '', 'Enter your DHL/Airborne password.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('DHL/Airborne domestic shipping key', 'MODULE_SHIPPING_AIRBORNE_SHIP_KEY', '', 'Enter the DHL/Airborne domestic shipping key assigned to you.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('DHL/Airborne international shipping key', 'MODULE_SHIPPING_AIRBORNE_SHIP_KEY_INTL', '', 'Enter the DHL/Airborne international shipping key assigned to you.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('DHL/Airborne account number', 'MODULE_SHIPPING_AIRBORNE_ACCT_NBR', '', 'Enter your DHL/Airborne customer/account number.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Which server to use', 'MODULE_SHIPPING_AIRBORNE_SERVER', 'test', 'An account with DHL/Airborne is needed to use the Production server', '6', '0', 'tep_cfg_select_option(array(\'test\', \'production\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ( 'Shipping Methods', 'MODULE_SHIPPING_AIRBORNE_TYPES', 'G, S, N, E, IE', 'Select the DHL/Airborne shipping services to be offered.<p>Available Methods:<br>G - Ground<br>S - Second Day Service<br>N - Next Afternoon<br>E - Express<br>E 10:30AM - Express 10:30 AM<br>E SAT - Express Saturday<br>IE - International Express', '6', '0', '_selectOptions3254(array(\'G\',\'S\', \'N\', \'E\', \'E 10:30AM\', \'E SAT\', \'IE\'), ', now() )");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Estimated Delivery Time', 'MODULE_SHIPPING_AIRBORNE_EST_DELIVERY', 'true', 'Display the estimated delivery time beside the shipment method in checkout?', '6', '0', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
// Wi-Gear Added in v2.2 - Append SHIP_WEIGHT
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Show shipping Weight ', 'MODULE_SHIPPING_AIRBORNE_SHIP_WEIGHT', 'true', 'Display the Shipping Total weight', '6', '0', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Shipment Type', 'MODULE_SHIPPING_AIRBORNE_PACKAGE', 'P', 'Select the type of shipment:<br>P - Package<br>L - Letter', '6', '0', 'tep_cfg_select_option(array(\'P\', \'L\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Shipment Type', 'MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE', 'Ship in x number of days', 'Select whether you usually ship on a certain day (ex: every Monday) or usually in x number of days (ship out in 2 days).  Then set either the x number of days or set day below.', '6', '0', 'tep_cfg_select_option(array(\'Ship in x number of days\', \'Ship on certain day\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Days to Shipment', 'MODULE_SHIPPING_AIRBORNE_DAYS_TO_SHIP', '2', 'If using \"Ship in x number of days,\" how many days do you estimate it will be from when a customers orders until you ship the packages? (0 = ship same day, 1 = ship the following day, etc)', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Shipment Day', 'MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY', 'Monday', 'Select the day you ship on if using \"Ship on certain day\"', '6', '0', 'tep_cfg_select_option(array(\'Monday\', \'Tuesday\', \'Wednesday\', \'Thursday\', \'Friday\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Override Express Saturday Shipping', 'MODULE_SHIPPING_AIRBORNE_OVERRIDE_EXP_SAT', 'false', 'If you want to enable shipping an Express Saturday shipment on a day that is not Friday, use this override to generate the shipping quote.', '6', '0', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Dimensional Weight', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_WEIGHT', 'false', 'Do you want to use dimensions in the rate request? (dimensions set below)', '6', '0', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Average Dimensions', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_TABLE', '1:12x8x2,3:24x10x3', 'Setup your average dimensions as follows: <em>number of products ordered<b>:</b>length<b>x</b>width<b>x</b>height</em> with a comma seperating each entry.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Dimensional Exclusive Option', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_EXCLUSIVE', 'false', 'Do you want the dimensions to be exclusive the number of products in the cart?<p>Example: If this is true, and you have a dimensional table setup for 1 product and 3 products, only dimensions will be used if 1 product or 3 products are ordered.', '6', '0', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
// Wi-Gear Added in v2.2 - Added DUTIABLE, and DUTY_PAYMENT_TYPE
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Dutiable', 'MODULE_SHIPPING_AIRBORNE_DUTIABLE', 'Yes', 'Indicates the Shipment is Dutiable/non-dutiable', '6', '0', 'tep_cfg_select_option(array(\'Yes\', \'No\'), ', now())"); 	 
	  tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ( 'Duty Payment Type', 'MODULE_SHIPPING_AIRBORNE_DUTY_PAYMENT_TYPE', 'R', 'Select the Duty Payment Type:<br>S - Sender<br>R - Receiver<br>3 - Third Party<br>', '6', '0', 'tep_cfg_select_option(array(\'S\', \'R\', \'3\'), ', now() )");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Package Description', 'MODULE_SHIPPING_AIRBORNE_CONTENTS_DESCRIPTION', 'Merchandise', 'What will you be shipping?.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Additional Protection', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION', 'false', 'Do you want to quote the rate with additional protection against potential loss or damage?', '6', '0', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Additional Protection Type', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_TYPE', 'NR', 'If you have additional protection enabled, select the type of additional protection you want to use.<p>AP - Shipment Value Protection<br>NR - No Additional Protection', '6', '0', 'tep_cfg_select_option(array(\'AP\', \'NR\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Additional Protection Value', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_VALUE', '', 'If you have additional protection enabled, by default the cart subtotal is used as the protection value.  Use this to add additional protection value on top of cart subtotal.<p>Example:<br>10 - adds $10 to cart subtotal<br>10% - adds 10% to cart subtotal', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Handling Fee', 'MODULE_SHIPPING_AIRBORNE_HANDLING', '0', 'Handling fee for this shipping method.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_AIRBORNE_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Shipping Zone', 'MODULE_SHIPPING_AIRBORNE_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_AIRBORNE_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Module Debugging', 'MODULE_SHIPPING_AIRBORNE_DEBUG', 'False', 'Do you want to debug the domestic DHL/Airborne shipping module (will save the XML request and response to a file in the directory specified below)?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Debug Method', 'MODULE_SHIPPING_AIRBORNE_DEBUG_METHOD', 'Print to screen', 'Method of debugging', '6', '0', 'tep_cfg_select_option(array(\'Print to screen\', \'Save to file\'), ', now())");
      tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Debug Directory', 'MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY', '', 'Absolute path to the directory where to save XML requests and responses when in debug mode of \"Save to file\"<br /><em>Note:  Directory must be CHMOD to 777</em>', '6', '0', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
//
// Add in International Shipping Key
//
      return array('MODULE_SHIPPING_AIRBORNE_STATUS', 'MODULE_SHIPPING_AIRBORNE_SYSTEMID', 'MODULE_SHIPPING_AIRBORNE_PASS', 'MODULE_SHIPPING_AIRBORNE_SHIP_KEY', 'MODULE_SHIPPING_AIRBORNE_SHIP_KEY_INTL', 'MODULE_SHIPPING_AIRBORNE_ACCT_NBR', 'MODULE_SHIPPING_AIRBORNE_SERVER', 'MODULE_SHIPPING_AIRBORNE_TYPES', 'MODULE_SHIPPING_AIRBORNE_DUTIABLE', 'MODULE_SHIPPING_AIRBORNE_DUTY_PAYMENT_TYPE', 'MODULE_SHIPPING_AIRBORNE_CONTENTS_DESCRIPTION', 'MODULE_SHIPPING_AIRBORNE_EST_DELIVERY', 'MODULE_SHIPPING_AIRBORNE_SHIP_WEIGHT', 'MODULE_SHIPPING_AIRBORNE_PACKAGE', 'MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE', 'MODULE_SHIPPING_AIRBORNE_DAYS_TO_SHIP', 'MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY', 'MODULE_SHIPPING_AIRBORNE_OVERRIDE_EXP_SAT', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_WEIGHT', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_TABLE', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_EXCLUSIVE', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_TYPE', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_VALUE', 'MODULE_SHIPPING_AIRBORNE_HANDLING', 'MODULE_SHIPPING_AIRBORNE_TAX_CLASS', 'MODULE_SHIPPING_AIRBORNE_ZONE', 'MODULE_SHIPPING_AIRBORNE_SORT_ORDER', 'MODULE_SHIPPING_AIRBORNE_DEBUG', 'MODULE_SHIPPING_AIRBORNE_DEBUG_METHOD', 'MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY');
    }

    function _setService($service) {
      $this->service = $service;
    }
    
    function _setMethods($methods) {
      $this->allowed_methods = explode(", ", $methods);
    }
	
    function _setContainer($container) {
      $this->container = $container;
    }

    function _setShippingDay($days_to_ship, $day) {
      if (MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE == 'Ship in x number of days') {
        $this->shipping_day = ((_makedate3254($days_to_ship, 'day', 'dddd') == 'Saturday') ? $days_to_ship+2 : ((_makedate3254($days_to_ship, 'day', 'dddd') == 'Sunday') ? $days_to_ship+1 : $days_to_ship));
      } elseif (MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE == 'Ship on certain day') {
        $i=1;
        while (_makedate3254($i, 'day', 'dddd') != $day) {
          $i++;
        }
        
        $this->shipping_day = $i;
      }
    }
		
    function _setWeight($shipping_weight) {
      $shipping_weight = ($shipping_weight < .5 ? .5 : $shipping_weight);
      // Wi-Gear Changed in v2.2 - round up weight
      $shipping_pounds = ceil($shipping_weight);
      $this->weight = $shipping_pounds;
    }
    
    function _setDimensions($exclusive) {
      $dimensions = split("[:xX,]", MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_TABLE);
      $size = sizeof($dimensions);
      for ($i=0, $n=$size; $i<$n; $i+=4) {
        if ($exclusive == 'true') {
          if (($_SESSION['cart']->count_contents()) == $dimensions[$i]) {
            $this->dimensions = true;
            // Wi-Gear Changed in v2.2 - round up dimensions
            $this->length = ceil($dimensions[$i+1]);
            $this->width = ceil($dimensions[$i+2]);
            $this->height = ceil($dimensions[$i+3]); 
          }
        } else {
          if (($_SESSION['cart']->count_contents()) >= $dimensions[$i]) {
            $this->dimensions = true;
            // Wi-Gear Changed in v2.2 - round up dimensions
            $this->length = ceil($dimensions[$i+1]);
            $this->width = ceil($dimensions[$i+2]);
            $this->height = ceil($dimensions[$i+3]); 
          }
        }
      }
    }
		
    function _setDestination($street_address, $city, $state, $postal, $country) {
      global $order;
      
      $postal = str_replace(' ', '', $postal);
      $state_query = tep_db_query("select zone_code from " . TABLE_ZONES . " where zone_name = '" . $state . "' and zone_country_id = '" . (int)$order->delivery['country']['id'] . "'");
      $state_info = tep_db_fetch_array($state_query);

      $this->destination_street_address = $street_address;
      $this->destination_city = $city;
      $this->destination_state = $state_info?$state_info['zone_code']:$state;
      $this->destination_postal = $postal;
      $this->destination_country = $country;
    }
    
    function _setAdditionalProtection($additional_value) {
      global $order;
      
      $additional_protection = $order->info['subtotal'];
      if (substr_count($additional_value, '%') > 0) {
        $additional_protection += ((($additional_protection*10)/10)*((str_replace('%', '', $additional_value))/100));
      } else {
        $additional_protection += $additional_value;
      }
      
      $this->additionalProtection = round($additional_protection, 0);
    }

    function _getQuote() {
      global $order;

      // if it is an international order get an international quote
      if ($order->delivery['country']['iso_code_2'] != 'US') {
        $rates = $this->_getInternationalQuote();
        return ((sizeof($rates) > 0) ? $rates : false);
      }

      // start the XML request
      $request = "<?xml version='1.0'?>" . 
                 "<eCommerce action='Request' version='1.1'>" .
                   "<Requestor>" .
                     "<ID>" . MODULE_SHIPPING_AIRBORNE_SYSTEMID . "</ID>" .
                     "<Password>" . MODULE_SHIPPING_AIRBORNE_PASS . "</Password>" .
                   "</Requestor>";
 
      if (isset($this->service)) {
        $this->types = array($this->service => $this->types[$this->service]);
      }

      $allowed_types = array();
      foreach ($this->types as $key => $value) {
        if (!in_array($key, $this->allowed_methods)) continue;
        
        // Letter Express not allowed with ground
        if (($key == 'G') && ($this->container == 'L')) continue;

        // International Express not allowed with Domestic
        if ($key == 'IE') continue;

        // basic shipment information
        $allowed_types[$key] = $value;
        $request .= "<Shipment action='RateEstimate' version='1.0'>" .
                      "<ShippingCredentials>" . 
                        "<ShippingKey>" . MODULE_SHIPPING_AIRBORNE_SHIP_KEY . "</ShippingKey>" .
                        "<AccountNbr>" . MODULE_SHIPPING_AIRBORNE_ACCT_NBR . "</AccountNbr>" .
                      "</ShippingCredentials>" .
                      "<ShipmentDetail>" . 
                        "<ShipDate>" . _makedate3254($this->shipping_day, 'day', 'yyyy-mm-dd') . "</ShipDate>" .
                        "<Service>" .
                          "<Code>" . substr($key, 0, 1) . "</Code>" .
                        "</Service>" .
                        "<ShipmentType>" .
                          "<Code>" . $this->container . "</Code>" .
                        "</ShipmentType>";

//          $request .= "<SpecialServices>" . 
//                        "<SpecialService>" .
//                          "<Code>HAZ</Code>" .
//                        "</SpecialService>" .
//                      "</SpecialServices>";
        // special Express services
        if ($key == 'E SAT') {
          $request .= "<SpecialServices>" . 
                        "<SpecialService>" .
                          "<Code>SAT</Code>" .
                        "</SpecialService>" .
                      "</SpecialServices>";
        } elseif ($key == 'E 10:30AM') {
          $request .= "<SpecialServices>" .
                        "<SpecialService>" .
                          "<Code>1030</Code>" .
                        "</SpecialService>" .
                      "</SpecialServices>";
        }

        // package weight & dimensions
        if ($this->container != 'L') {
          $request .= "<Weight>" . $this->weight . "</Weight>";
        }

        if (isset($this->dimensions)) {
          $request .= "<Dimensions>" .
                        "<Length>" . $this->length . "</Length>" .
                        "<Width>" . $this->width . "</Width>" .
                        "<Height>" . $this->height . "</Height>" .
                      "</Dimensions>";
        }

        // package additional protection
        if (isset($this->additionalProtection)) {
          $request .= "<AdditionalProtection>" .
                        "<Code>" . MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_TYPE . "</Code>" .
                        "<Value>" . $this->additionalProtection . "</Value>" .
                      "</AdditionalProtection>";
        }

        // billing & shipping information        
        $request .= "</ShipmentDetail>" . 
                    "<Billing>" .
                      "<Party>" .
                        "<Code>S</Code>" .
                      "</Party>" .
//		      "<CODPayment>" .
//                        "<Code>M</Code>" .
//                        "<Value>20</Value>" .
//		      "</CODPayment>" .
                    "</Billing>" .
                    "<Receiver>" .
                      "<Address>";
        if (tep_not_null($this->destination_city)) {
          $request .= "<City>" . $this->destination_city . "</City>";
        }
        // Lookup state if needed
        if (tep_not_null($this->destination_state)) {
          $request .= "<State>" . $this->destination_state . "</State>";
        } else {
          $request .= "<State>" . $this->zip_to_state($this->destination_postal) . "</State>";
        }
        $request .= "<Country>" . $this->destination_country . "</Country>" .
                        "<PostalCode>" . $this->destination_postal . "</PostalCode>" .
                      "</Address>" .
                    "</Receiver>";

        // shipment overrides
        if ((MODULE_SHIPPING_AIRBORNE_OVERRIDE_EXP_SAT == 'true') && ($key == 'E SAT')) {
          $request .= "<ShipmentProcessingInstructions>" .
                        "<Overrides>" .
                          "<Override>" .
                            "<Code>ES</Code>" .
                          "</Override>" .
                        "</Overrides>" .
                      "</ShipmentProcessingInstructions>";
        }
        
        $request .= "</Shipment>";
      }
        
      $request .= "</eCommerce>";
      
      // select proper server
      switch (MODULE_SHIPPING_AIRBORNE_SERVER) {
        case 'production':
          $api = "ApiLanding.asp";
          break;
        case 'test':
        default:
          $api = "ApiLandingTest.asp";
          break;
      }
        
      // begin cURL engine & execute the request
      if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://eCommerce.airborne.com/$api");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$request");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  //Added in 1.401 fix
//	  curl_setopt($ch, CURLOPT_CAINFO, 'C:/apache/bin/curl-ca-bundle.crt');
	 
        $airborne_response = curl_exec($ch);
        curl_close($ch);
      } else {
        // cURL method using exec() // curl -d -k if you have SSL issues
        exec("/usr/bin/curl -d \"$request\" https://eCommerce.airborne.com/$api", $response);
        $airborne_response = '';
        foreach ($response as $key => $value) {
          $airborne_response .= "$value";
        }
      }
      
      // Debugging
      if ($this->debug) {
        $this->captureXML($request, $airborne_response);
      }

      $airborne = _parsexml3254($airborne_response);
       
      if ($airborne[eCommerce]['->'][Faults][0]['->'][Fault][0]['->'][Code][0]['->']) {
        $error_message = 'The following errors have occured:';
        for($i=0; $i<5; $i++) {
          if ($airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Code][0]['->']) $error_message .= '<br>' . ($i+1) . '.&nbsp;' . $airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Description][0]['->'];
          if ($airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) $error_message .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>(' . htmlspecialchars($airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) . ')</em>';
          if (!$airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i+1]['->'][Code][0]['->']) break;
        }
        return array('error' => $error_message);
      } elseif ($airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][0]['->'][Code][0]['->']) {
        $error_message = 'The following errors have occured:';
        for($i=0; $i<5; $i++) {
          if ($airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i]['->'][Code][0]['->']) $error_message .= '<br>' . ($i+1) . '.&nbsp;' . $airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i]['->'][Desc][0]['->'];
          if ($airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) $error_message .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>(' . htmlspecialchars($airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) . ')</em>';
          if (!$airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i+1]['->'][Code][0]['->']) break;
        }
        return array('error' => $error_message);
      } else {
        $rates = array();
        $i = 0;
        foreach ($allowed_types as $key => $value) {
          if ($airborne[eCommerce]['->'][Shipment][$i]['->'][EstimateDetail][0]['->'][Service][0]['->'][Code][0]['->']) {
            $service = $key;
            $postage = $airborne[eCommerce]['->'][Shipment][$i]['->'][EstimateDetail][0]['->'][RateEstimate][0]['->'][TotalChargeEstimate][0]['->'];
            $description = (MODULE_SHIPPING_AIRBORNE_EST_DELIVERY == 'true') ? '&nbsp;<span class="smallText"><em>(' . $airborne[eCommerce]['->'][Shipment][$i]['->'][EstimateDetail][0]['->'][ServiceLevelCommitment][0]['->'][Desc][0]['->'] . ')</em></span>' : '';
            $rates[] = array($service => $postage, 'description' => $description);
          }
          $i++;
        }
      }
        
      return ((sizeof($rates) > 0) ? $rates : false);
    }

    function _getInternationalQuote() {
      global $order;
      

      // Check that 'IE' is a selected method
      if (!in_array('IE', $this->allowed_methods)) {
        return(array('error'=>'Error - In order to use DHL International Express Shipping the shipping zone must be enabled (which has been completed) and IE must be checked off as an available shipping method (which has not been completed)'));
//	return(false);
      };

      // start the XML request
      $request = "<?xml version='1.0'?>" .
        "<eCommerce action='Request' version='1.1'>" .
        "<Requestor>" .
        "<ID>" .
          MODULE_SHIPPING_AIRBORNE_SYSTEMID .
        "</ID>" .
	    "<Password>" .
	      MODULE_SHIPPING_AIRBORNE_PASS .
	    "</Password>" .
	  "</Requestor>" .
	  "<IntlShipment action='RateEstimate' version='1.0'>" .
	    "<ShippingCredentials>" .
	      "<ShippingKey>" .
		MODULE_SHIPPING_AIRBORNE_SHIP_KEY_INTL .
 	      "</ShippingKey>" .
	      "<AccountNbr>" .
	        MODULE_SHIPPING_AIRBORNE_ACCT_NBR .
	      "</AccountNbr>" .
	    "</ShippingCredentials>" .
	    "<ShipmentDetail>" .
	      "<ShipDate>" .
	        _makedate3254($this->shipping_day, 'day', 'yyyy-mm-dd') .
	      "</ShipDate>" .
	      "<Service>" .
	        "<Code>" .
	        "IE" .
	        "</Code>" .
	      "</Service>" .
	      "<ShipmentType>" .
	        "<Code>" .
	        "O" .
	        "</Code>" .
	      "</ShipmentType>";
      if ($this->container != 'L') {
        $request .= "<Weight>" . $this->weight . "</Weight>";
      }

      if (isset($this->dimensions)) {
        $request .= "<Dimensions>" .
          "<Length>" . $this->length . "</Length>" .
          "<Width>" . $this->width . "</Width>" .
          "<Height>" . $this->height . "</Height>" .
          "</Dimensions>";
      }
      $request .=
        "<ContentDesc>" .
          MODULE_SHIPPING_AIRBORNE_CONTENTS_DESCRIPTION .
		"</ContentDesc>";
        if (isset($this->additionalProtection)) {
          $request .= "<AdditionalProtection>" .
                        "<Code>" . MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_TYPE . "</Code>" .
                        "<Value>" . $this->additionalProtection . "</Value>" .
                      "</AdditionalProtection>";
        }
	$request.=	
	    "</ShipmentDetail>" .
	    "<Dutiable>" .
          "<DutiableFlag>" .
            ((MODULE_SHIPPING_AIRBORNE_DUTIABLE=='Yes')?"Y":"N") .
          "</DutiableFlag>" .
          "<CustomsValue>" .
            $order->info['subtotal'] .
	      "</CustomsValue>" .
	    "</Dutiable>" .
	    "<Billing>" .
	      "<Party>" .
	        "<Code>" .
	          "S" .
	        "</Code>" .
	      "</Party>" .
//	      "<Party>" .
//	        "<Code>" .
//	          "3" .
//	        "</Code>" .
//	      "</Party>" .
//	      "<AccountNbr>" . MODULE_SHIPPING_AIRBORNE_ACCT_NBR . "</AccountNbr>" .
	      "<DutyPaymentType>" .
	        MODULE_SHIPPING_AIRBORNE_DUTY_PAYMENT_TYPE .
	      "</DutyPaymentType>" .
	    ((MODULE_SHIPPING_AIRBORNE_DUTY_PAYMENT_TYPE=='3')?"<DutyPaymentAccountNbr>" . MODULE_SHIPPING_AIRBORNE_ACCT_NBR . "</DutyPaymentAccountNbr>":'') .
	    "</Billing>" .
	    "<Receiver>" .
	      "<Address>" .
	        "<Street>" .
		  $this->destination_street_address .
	        "</Street>" .
	        "<City>" .
		  $this->destination_city .
	        "</City>" .
	        "<State>" .
		  $this->destination_state .
	        "</State>" .
	        "<Country>" .
		  $this->destination_country .
	        "</Country>" .
	        "<PostalCode>" .
		  $this->destination_postal .
	        "</PostalCode>" .
	      "</Address>" .
	    "</Receiver>" .
        "</IntlShipment>" .
        "</eCommerce>";

      // select proper server
      switch (MODULE_SHIPPING_AIRBORNE_SERVER) {
        case 'production':
          $api = "ApiLanding.asp";
          break;
        case 'test':
        default:
          $api = "ApiLandingTest.asp";
          break;
      }
        
      // begin cURL engine & execute the request
      if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://eCommerce.airborne.com/$api");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$request");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $airborne_response = curl_exec($ch);
        curl_close($ch);
      } else {
        // cURL method using exec() // curl -d -k if you have SSL issues
        exec("/usr/bin/curl -d \"$request\" https://eCommerce.airborne.com/$api", $response);
        $airborne_response = '';
        foreach ($response as $key => $value) {
          $airborne_response .= "$value";
        }
      }

      // Debugging
      if ($this->debug) {
        $this->captureXML($request, $airborne_response);
      }

      $airborne = _parsexml3254($airborne_response);
      
      // Check for errors
      if ($airborne[eCommerce]['->'][Faults][0]['->'][Fault][0]['->'][Code][0]['->']) {
        $error_message = 'The following errors have occured:';
        for($i=0; $i<5; $i++) {
          if ($airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Code][0]['->']) $error_message .= '<br>' . ($i+1) . '.&nbsp;' . $airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Description][0]['->'];
          if ($airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) $error_message .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>(' . htmlspecialchars($airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) . ')</em>';
          if (!$airborne[eCommerce]['->'][Faults][0]['->'][Fault][$i+1]['->'][Code][0]['->']) break;
        }
        return array('error' => $error_message);
      } elseif ($airborne[eCommerce]['->'][IntlShipment][0]['->'][Faults][0]['->'][Fault][0]['->'][Code][0]['->']) {
        $error_message = 'The following errors have occured:';
        for($i=0; $i<5; $i++) {
          if ($airborne[eCommerce]['->']['IntlShipment'][0]['->'][Faults][0]['->'][Fault][$i]['->'][Code][0]['->']) {
		$error_message .= '<br>' . ($i+1) . '.&nbsp;' . $airborne[eCommerce]['->']['IntlShipment'][0]['->'][Faults][0]['->'][Fault][$i]['->'][Desc][0]['->'];
	  }
          if ($airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) {
	    $error_message .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>(' . htmlspecialchars($airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i]['->'][Context][0]['->']) . ')</em>';
	  }
          if (!$airborne[eCommerce]['->'][Shipment][0]['->'][Faults][0]['->'][Fault][$i+1]['->'][Code][0]['->']) break;
        }
        return array('error' => $error_message);
      } else {
        $rates = array();
        $i = 0;

      $service = 'IE';
      $postage = $airborne['eCommerce']['->']['IntlShipment']['0']['->']['EstimateDetail']['0']['->']['RateEstimate']['0']['->']['TotalChargeEstimate']['0']['->'];
      if(strcmp(MODULE_SHIPPING_AIRBORNE_EST_DELIVERY, "true") == 0) {
        $description = ' (' .$airborne['eCommerce']['->']['IntlShipment']['0']['->']['EstimateDetail']['0']['->']['ServiceLevelCommitment']['0']['->']['Desc']['0']['->'] . ')';
        } else {
          $description = '';
        }
      $rates[] = array('IE' => $postage, 'description' => $description);
      }
      return ((sizeof($rates) > 0) ? $rates : false);
    }

    function captureXML($request, $response) {
      if (MODULE_SHIPPING_AIRBORNE_DEBUG_METHOD == 'Print to screen') {
        echo 'Request:<br /><pre>' . htmlspecialchars($request) . '</pre><br /><br />';
        echo 'Response:<br /><pre>' . htmlspecialchars($response) . '</pre>';
      } else {
        $folder = ((substr(MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY, -1) != '/') ? MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY . '/' : MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY);

        $filename = $folder . 'request.txt';
        if (!$fp = fopen($filename, "w")) die("Failed opening file $filename");
        if (!fwrite($fp, $request)) die("Failed writing to file $filename");
        fclose($fp);

        $filename = $folder . 'response.txt';
        if (!$fp = fopen($filename, "w")) die("Failed opening file $filename");
        if (!fwrite($fp, $response)) die("Failed writing to file $filename");
        fclose($fp);
      }

      return true;
    }

    function zip_to_state($zip) {
// A PHP function to convert a zip code to a state code
// Created 6/16/06
// Copyright:  Verango
// Free for commercial and private usage under  GNU, copyright information must remain intact.

		switch (TRUE)
		{
        case (($zip >= 600 AND $zip <= 799) || ($zip >= 900 AND $zip <= 999)): // Puerto Rico (00600-00799 and 900--00999 ranges)
            return "PR";
        case ($zip >= 800 AND $zip <= 899): // US Virgin Islands (00800-00899 range)
            return "VI";
        case ($zip >= 1000 AND $zip <= 2799): // Massachusetts (01000-02799 range)
            return "MA";  
        case ($zip >= 2800 AND $zip <= 2999): // Rhode Island (02800-02999 range)
            return "RI";
        case ($zip >= 3000 AND $zip <= 3899): // New Hampshire (03000-03899 range)
            return "NH";
        case ($zip >= 3900 AND $zip <= 4999): // Maine (03900-04999 range)
            return "ME";
        case ($zip >= 5000 AND $zip <= 5999): // Vermont (05000-05999 range)
             return "VT";    
        case (($zip >= 6000 AND $zip <= 6999) AND $zip != 6390): // Connecticut (06000-06999 range excluding 6390)
              return "CT";    
        case ($zip >= 70000 AND $zip <= 8999): // New Jersey (07000-08999 range)
              return "NJ";    
        case (($zip >= 10000 AND $zip <= 14999) OR $zip == 6390 OR $zip == 501 OR $zip == 544): // New York (10000-14999 range and 6390, 501, 544)
              return "NY";    
        case ($zip >= 15000 AND $zip <= 19699): // Pennsylvania (15000-19699 range)
              return "PA";    
        case ($zip >= 19700 AND $zip <= 19999): // Delaware (19700-19999 range)
              return "DE";    
        case (($zip >= 20000 AND $zip <= 20099) OR ($zip >= 20200 AND $zip <= 20599) OR ($zip >= 56900 AND $zip <= 56999)): // District of Columbia (20000-20099, 20200-20599, and 56900-56999 ranges)
              return "DC";    
        case ($zip >= 20600 AND $zip <= 21999): // Maryland (20600-21999 range)
              return "MD";    
        case (($zip >= 20100 AND $zip <= 20199) OR ($zip >= 22000 AND $zip <= 24699)): // Virginia (20100-20199 and 22000-24699 ranges, also some taken from 20000-20099 DC range)
              return "VA";    
        case ($zip >= 24700 AND $zip <= 26999): // West Virginia (24700-26999 range)
              return "WV";    
        case ($zip >= 27000 AND $zip <= 28999): // North Carolina (27000-28999 range)
              return "NC";    
        case ($zip >= 29000 AND $zip <= 29999): // South Carolina (29000-29999 range)
              return "SC";    
        case (($zip >= 30000 AND $zip <= 31999) OR ($zip >= 39800 AND $zip <= 39999)): // Georgia (30000-31999, 39901[Atlanta] range)
              return "GA";    
        case ($zip >= 32000 AND $zip <= 34999): // Florida (32000-34999 range)
              return "FL";    
        case ($zip >= 35000 AND $zip <= 36999): // Alabama (35000-36999 range)
              return "AL";    
        case ($zip >= 37000 AND $zip <= 38599): // Tennessee (37000-38599 range)
              return "TN";    
        case ($zip >= 38600 AND $zip <= 39799): // Mississippi (38600-39999 range)
              return "MS";    
        case ($zip >= 40000 AND $zip <= 42799): // Kentucky (40000-42799 range)
              return "KY";    
        case ($zip >= 43000 AND $zip <= 45999): // Ohio (43000-45999 range)
              return "OH";    
        case ($zip >= 46000 AND $zip <= 47999): // Indiana (46000-47999 range)
              return "IN";    
        case ($zip >= 48000 AND $zip <= 49999): // Michigan (48000-49999 range)
              return "MI";    
        case ($zip >= 50000 AND $zip <= 52999): // Iowa (50000-52999 range)
              return "IA";    
        case ($zip >= 53000 AND $zip <= 54999): // Wisconsin (53000-54999 range)
              return "WI";    
        case ($zip >= 55000 AND $zip <= 56799): // Minnesota (55000-56799 range)
              return "MN";    
        case ($zip >= 57000 AND $zip <= 57999): // South Dakota (57000-57999 range)
              return "SD";    
        case ($zip >= 58000 AND $zip <= 58999): // North Dakota (58000-58999 range)
              return "ND";    
        case ($zip >= 59000 AND $zip <= 59999): // Montana (59000-59999 range)
              return "MT";    
        case ($zip >= 60000 AND $zip <= 62999): // Illinois (60000-62999 range)
              return "IL";    
        case ($zip >= 63000 AND $zip <= 65999): // Missouri (63000-65999 range)
              return "MO";    
        case ($zip >= 66000 AND $zip <= 67999): // Kansas (66000-67999 range)
              return "KS";    
        case ($zip >= 68000 AND $zip <= 69999): // Nebraska (68000-69999 range)
              return "NE";    
        case ($zip >= 70000 AND $zip <= 71599): // Louisiana (70000-71599 range)
              return "LA";    
        case ($zip >= 71600 AND $zip <= 72999): // Arkansas (71600-72999 range)
              return "AR";    
        case ($zip >= 73000 AND $zip <= 74999): // Oklahoma (73000-74999 range)
              return "OK";    
        case (($zip >= 75000 AND $zip <= 79999) OR ($zip >= 88500 AND $zip <= 88599)): // Texas (75000-79999 and 88500-88599 ranges)
              return "TX";    
        case ($zip >= 80000 AND $zip <= 81999): // Colorado (80000-81999 range)
              return "CO";    
        case ($zip >= 82000 AND $zip <= 83199): // Wyoming (82000-83199 range)
              return "WY";    
        case ($zip >= 83200 AND $zip <= 83999): // Idaho (83200-83999 range)
              return "ID";    
        case ($zip >= 84000 AND $zip <= 84999): // Utah (84000-84999 range)
              return "UT";    
        case ($zip >= 85000 AND $zip <= 86999): // Arizona (85000-86999 range)
              return "AZ";    
        case ($zip >= 87000 AND $zip <= 88499): // New Mexico (87000-88499 range)
              return "NM";    
        case ($zip >= 88900 AND $zip <= 89999): // Nevada (88900-89999 range)
              return "NV";    
        case ($zip >= 90000 AND $zip <= 96199): // California (90000-96199 range)
              return "CA";    
        case ($zip >= 96700 AND $zip <= 96899): // Hawaii (96700-96899 range)            
              return "HI";    
        case ($zip >= 97000 AND $zip <= 97999): // Oregon (97000-97999 range)
              return "OR";    
        case ($zip >= 98000 AND $zip <= 99499): // Washington (98000-99499 range)
              return "WA";    
        case ($zip >= 99500 AND $zip <= 99999): // Alaska (99500-99999 range) 
              return "AK";    
		}
	}
	// End of class
  }

/*
  Function to parse the returned XML data into an array.
  Borrowed from Hans Anderson's xmlize() function.
  http://www.hansanderson.com/php/xml/
*/
  function _parsexml3254($data, $WHITE=1) {
    $data = trim($data);
    $vals = $index = $array = array();
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $WHITE);
    xml_parse_into_struct($parser, $data, $vals, $index);
    xml_parser_free($parser);

    $i = 0; 
    $tagname = $vals[$i]['tag'];
    $array[$tagname]['@'] = (isset($vals[$i]['attributes'])) ? $vals[$i]['attributes'] : array();
    $array[$tagname]["->"] = xml_depth3254($vals, $i);
    return $array;
  }

  function xml_depth3254($vals, &$i) { 
    $children = array(); 
    if (isset($vals[$i]['value'])) array_push($children, $vals[$i]['value']);

    while (++$i < count($vals)) { 
      switch ($vals[$i]['type']) {
        case 'open':
          $tagname = (isset($vals[$i]['tag'])) ? $vals[$i]['tag'] : '';
          $size = (isset($children[$tagname])) ? sizeof($children[$tagname]) : 0;
          if (isset($vals[$i]['attributes'])) $children[$tagname][$size]['@'] = $vals[$i]["attributes"];
          $children[$tagname][$size]['->'] = xml_depth3254($vals, $i);
          break;
        case 'cdata':
          array_push($children, $vals[$i]['value']);
          break;
        case 'complete':
          $tagname = $vals[$i]['tag'];
          $size = (isset($children[$tagname])) ? sizeof($children[$tagname]) : 0;
          $children[$tagname][$size]["->"] = (isset($vals[$i]['value'])) ? $vals[$i]['value'] : '';
          if (isset($vals[$i]['attributes'])) $children[$tagname][$size]['@'] = $vals[$i]['attributes'];
          break;
        case 'close':
          return $children;
          break;
      }
    }
    
    return $children;
  }

/* 
  Function to generate arbitrary, formatted numeric or string date.
  Copyright (C) 2003  Erich Spencer
*/ 
  function _makedate3254($unit = '', $time = '', $mask = '') { 
    $validunit = '/^[-+]?\b[0-9]+\b$/'; 
    $validtime = '/^\b(day|week|month|year)\b$/i'; 
    $validmask = '/^(short|long|([dmy[:space:][:punct:]]+))$/i'; 

    if (!preg_match($validunit,$unit)) $unit = -1; 
    if (!preg_match($validtime,$time)) $time = 'day'; 
    if (!preg_match($validmask,$mask)) $mask = 'yyyymmdd'; 

    switch ($mask) { 
      case 'short': // 7/4/2003 
        $mask = "n/j/Y"; 
        break; 
      case 'long':  // Friday, July 4, 2003 
        $mask = "l, F j, Y"; 
        break; 
      default:
        $chars = (preg_match('/([[:space:]]|[[:punct:]])/', $mask)) ? preg_split('/([[:space:]]|[[:punct:]])/', $mask, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) : preg_split('/(m*|d*|y*)/i', $mask, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        foreach ($chars as $key => $char) { 
          switch (TRUE) { 
            case eregi("m{3,}",$chars[$key]): // 'mmmm' = month string 
              $chars[$key] = "F"; 
              break; 
            case eregi("m{2}",$chars[$key]):  // 'mm'   = month as 01-12 
              $chars[$key] = "m"; 
              break; 
            case eregi("m{1}",$chars[$key]):  // 'm'    = month as 1-12 
              $chars[$key] = "n"; 
              break; 
            case eregi("d{3,}",$chars[$key]): // 'dddd' = day string 
              $chars[$key] = "l"; 
              break; 
            case eregi("d{2}",$chars[$key]):  // 'dd'   = day as 01-31 
              $chars[$key] = "d"; 
              break; 
            case eregi("d{1}",$chars[$key]):  // 'd'    = day as 1-31 
              $chars[$key] = "j"; 
              break; 
            case eregi("y{3,}",$chars[$key]): // 'yyyy' = 4 digit year 
              $chars[$key] = "Y"; 
              break; 
            case eregi("y{1,2}",$chars[$key]):// 'yy'   = 2 digit year 
              $chars[$key] = "y"; 
              break; 
          }                     
        }
        
        $mask = implode('',$chars); 
        break; 
    } 

    $when = date($mask, strtotime("$unit $time")); 
    return $when; 
  }

/*
  Function to have options for shipping methods.
  Borrowed from UPS Choice v1.7
  Credit goes to Fritz Clapp
*/  
  function _selectOptions3254($select_array, $key_value, $key = '') {
    foreach ($select_array as $select_option) {
      $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
      $string .= '<br><input type="checkbox" name="' . $name . '" value="' . $select_option . '"';
      $key_values = explode(", ", $key_value);
      if (in_array($select_option, $key_values)) $string .= ' checked="checked"';
      $string .= '> ' . $select_option;
    } 
    return $string;
  }
?>
