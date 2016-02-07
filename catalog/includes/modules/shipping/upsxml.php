<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


// # Incorporate the XML conversion library 
  if (PHP_VERSION >= '5.0.0') { // # PHP 5 does not need to use call-time pass by reference
    require_once (DIR_WS_CLASSES . 'xml_5.php');
  } else {
    require_once (DIR_WS_CLASSES . 'xml.php');
  }


class upsxml {
    var $code, $title, $description, $icon, $enabled, $types, $boxcount;

    //***************
    function __construct() {
        global $order, $packing;
        $this->code = 'upsxml';
        $this->title = MODULE_SHIPPING_UPSXML_RATES_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_UPSXML_RATES_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_SHIPPING_UPSXML_RATES_SORT_ORDER;
        $this->icon = DIR_WS_ICONS . 'shipping_ups.gif';
        $this->tax_class = MODULE_SHIPPING_UPSXML_RATES_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_UPSXML_RATES_STATUS == 'True') ? true : false);
        $this->access_key = MODULE_SHIPPING_UPSXML_RATES_ACCESS_KEY;
        $this->access_username = MODULE_SHIPPING_UPSXML_RATES_USERNAME;
        $this->access_password = MODULE_SHIPPING_UPSXML_RATES_PASSWORD;
        $this->access_account_number = MODULE_SHIPPING_UPSXML_RATES_UPS_ACCOUNT_NUMBER;

        $this->origin = MODULE_SHIPPING_UPSXML_RATES_ORIGIN;
        $this->origin_city = MODULE_SHIPPING_UPSXML_RATES_CITY;
        $this->origin_stateprov = MODULE_SHIPPING_UPSXML_RATES_STATEPROV;
        $this->origin_country = MODULE_SHIPPING_UPSXML_RATES_COUNTRY;
        $this->origin_postalcode = MODULE_SHIPPING_UPSXML_RATES_POSTALCODE;
        $this->pickup_method = MODULE_SHIPPING_UPSXML_RATES_PICKUP_METHOD;
        $this->package_type = MODULE_SHIPPING_UPSXML_RATES_PACKAGE_TYPE;

		// # the variables for unit weight, unit length, and dimensions support are accesible
		// # vi admin -> Configuration -> Shipping/Packaging
		// # Run the configuration_shipping.sql to add these to your configuration
        $this->unit_weight = (defined('SHIPPING_UNIT_WEIGHT')) ? SHIPPING_UNIT_WEIGHT : 'LBS';

        $this->unit_length = (defined('SHIPPING_UNIT_LENGTH')) ? SHIPPING_UNIT_LENGTH : 'IN';

        if (defined('SHIPPING_DIMENSIONS_SUPPORT') && SHIPPING_DIMENSIONS_SUPPORT == 'Ready-to-ship only') {
          $this->dimensions_support = 1;
        } elseif (defined('SHIPPING_DIMENSIONS_SUPPORT') && SHIPPING_DIMENSIONS_SUPPORT == 'With product dimensions') {
          $this->dimensions_support = 2;
        } else {
          $this->dimensions_support = 0;
        }

        $this->email_errors = ((MODULE_SHIPPING_UPSXML_EMAIL_ERRORS == 'Yes') ? true : false);
        $this->handling_type = MODULE_SHIPPING_UPSXML_HANDLING_TYPE;
        $this->handling_fee = MODULE_SHIPPING_UPSXML_RATES_HANDLING;
        $this->quote_type = MODULE_SHIPPING_UPSXML_RATES_QUOTE_TYPE;
        $this->customer_classification = MODULE_SHIPPING_UPSXML_RATES_CUSTOMER_CLASSIFICATION_CODE;
        $this->protocol = 'https';
        $this->host = ((MODULE_SHIPPING_UPSXML_RATES_MODE == 'Test') ? 'wwwcie.ups.com' : 'onlinetools.ups.com');
        $this->port = '443';
        $this->path = '/ups.app/xml/Rate';
        $this->transitpath = '/ups.app/xml/TimeInTransit';
        $this->version = 'UPSXML Rate 1.0001';
        $this->transitversion = 'UPSXML Time In Transit 1.0002';
        $this->timeout = '60';
        $this->xpci_version = '1.0001';
        $this->transitxpci_version = '1.0002';
        $this->items_qty = 0;
        $this->timeintransit = '0';
        $this->timeInTransitView = MODULE_SHIPPING_UPSXML_RATES_TIME_IN_TRANSIT_VIEW;
        $this->weight_for_timeintransit = '0';
        $now_unix_time = mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));
        $this->today_unix_time = $now_unix_time;
        $this->today = date("Ymd");

        $this->pkgvalue = ceil($order->info['subtotal']); // is divided by number of boxes later
        // # to enable logging, create an empty "upsxml.log" file at the location you set below, 
		// # give it write permissions (777) and uncomment the next line

		//$this->logfile = '/home/zwave/logs/upsxml.log';

        // # to enable logging of just the errors, do as above but call the file upsxml_error.log
        $this->ups_error_file = '/home/zwave/logs/upsxml_error.log';


        if (($this->enabled == true) && ((int)MODULE_SHIPPING_UPSXML_RATES_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_UPSXML_RATES_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");


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

        // # Available pickup types - set in admin
        $this->pickup_methods = array(
            'Daily Pickup' => '01',
            'Customer Counter' => '03',
            'One Time Pickup' => '06',
            'On Call Air Pickup' => '07',
            'Suggested Retail Rates (UPS Store)' => '11',
            'Letter Center' => '19',
            'Air Service Center' => '20'
        );

        // # Available package types
        $this->package_types = array(
            'UPS Letter' => '01',
            'Package' => '02',
            'UPS Tube' => '03',
            'UPS Pak' => '04',
            'UPS Express Box' => '21',
            'UPS 25kg Box' => '24',
            'UPS 10kg Box' => '25'
        );

        // # Human-readable Service Code lookup table. The values returned by the Rates and Service "shop" method are numeric.
        // # Using these codes, and the administratively defined Origin, the proper human-readable service name is returned.
        // # Note: The origin specified in the admin configuration affects only the product name as displayed to the user.

        $this->service_codes = array(
            // US Origin
            'US Origin' => array(
                '01' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_01,
                '02' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_02,
                '03' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_03,
                '07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_07,
                '08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_08,
                '11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_11,
                '12' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_12,
                '13' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_13,
                '14' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_14,
                '54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_54,
                '59' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_59,
                '65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_65,
				// # Added SurePost compatibility 12/15/2014
				'92' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_92,
				'93' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_93,
				'94' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_94,
				'95' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_95
            ),
            // Canada Origin
            'Canada Origin' => array(
                '01' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_01,
                '02' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_02,
                '07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_07,
                '08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_08,
                '11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_11,
                '12' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_12,
                '13' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_13,
                '14' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_14,
                '54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_54,
                '65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_65
            ),
            // European Union Origin
            'European Union Origin' => array(
                '07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_07,
                '08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_08,
                '11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_11,
                '54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_54,
                '65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_65,
                // # next five services Poland domestic only
                '82' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_82,
                '83' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_83,
                '84' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_84,
                '85' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_85,
                '86' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_86
            ),
            // Puerto Rico Origin
            'Puerto Rico Origin' => array(
                '01' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_01,
                '02' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_02,
                '03' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_03,
                '07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_07,
                '08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_08,
                '14' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_14,
                '54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_54,
                '65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_65
            ),
            // Mexico Origin
            'Mexico Origin' => array(
                '07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_07,
                '08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_08,
                '54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_54,
                '65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_65
            ),
            // All other origins
            'All other origins' => array(
                // service code 7 seems to be gone after January 2, 2007
                '07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_07,
                '08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_08,
                '11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_11,
                '54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_54,
                '65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_65
            )
        );

		// # Activate UPS SurePost?
		// # default is False
		$this->surepost_active = (MODULE_SHIPPING_UPSXML_SUREPOST == 'True' ? 'True' : bool(0));

    } // end __construct function

    // # class methods
	public function quote($method='',$options='', $use_negotiated_rates='') {


        global $HTTP_POST_VARS, $order, $shipping_weight, $shipping_num_boxes, $total_weight, $boxcount, $cart, $packing, $delivery_confirmation;

		$this->delivery_confirmation = (!empty($options[0])) ? (bool)$options[0] : (bool)0;
		$this->insurance_cost = (!empty($options[1]) && $options[1] > 0) ? (float)$options[1] : (float)0;

		// # Use Negotiated rates for customer quotes?
		// # default is False
		$this->use_negotiated_rates = (!empty($use_negotiated_rates) ? (bool)1 : (bool)0);

	     // # insurance addition
		if($this->insurance_cost > 0 || MODULE_SHIPPING_UPSXML_INSURE == 'True') {
          $this->insure_package = true;
		} else {
			$this->insure_package = false;
		}
        // # end insurance addition

		// # NEGOTIATED RATES
		if($this->use_negotiated_rates === true) {
			// # manual negotated rate is an override set in configuration -> shipping modules -> ups 
			// # this will override the returned negotated rate if any. Purpose is really unknown but it does have one.
			$this->manual_negotiated_rate = intval(MODULE_SHIPPING_UPSXML_RATES_MANUAL_NEGOTIATED_RATE);
			// # the negotated rate flag - usage: 'True' : 'False'
    	    $this->use_negotiated_rates = MODULE_SHIPPING_UPSXML_RATES_USE_NEGOTIATED_RATES;
		} else {
			$this->manual_negotiated_rate = '';
    	    $this->use_negotiated_rates = 'False';
		}
		
        $state = $order->delivery['state'];

        $zone_query = tep_db_query("select zone_code from " . TABLE_ZONES . " where zone_name = '" .  tep_db_input($order->delivery['state']) . "' and zone_country_id = '" . $order->delivery['country']['id'] . "'");

        if (tep_db_num_rows($zone_query) > 0) {
            $zone = tep_db_fetch_array($zone_query);
            $state = $zone['zone_code'];
        }

		// # modify for support for Warehouse locations - last paramter resevred for warehouse ID
        $this->_upsOrigin(MODULE_SHIPPING_UPSXML_RATES_CITY, MODULE_SHIPPING_UPSXML_RATES_STATEPROV, MODULE_SHIPPING_UPSXML_RATES_COUNTRY, MODULE_SHIPPING_UPSXML_RATES_POSTALCODE, '');


		// # expand to do a city lookup realtime based on entered zipcode if $order->delivery['city'] is NULL

		if(empty($order->delivery['city']) && tep_session_is_registered('customer_city')) { 

			$order->delivery['city'] = $_SESSION['customer_city'];
		}

		if(empty($order->delivery['street_address']) && tep_session_is_registered('delivery_street_address')) { 

			$order->delivery['street_address'] = $_SESSION['delivery_street_address'];
		}

		if(empty($order->delivery['name']) && tep_session_is_registered('delivery_name')) { 

			$order->delivery['name'] = $_SESSION['delivery_name'];
		}

		if(empty($order->delivery['company']) && tep_session_is_registered('delivery_company')) { 

			$order->delivery['company'] = $_SESSION['delivery_company'];
		}

		if(empty($order->customer['telephone']) && tep_session_is_registered('customer_telephone')) { 

			$order->customer['telephone'] = $_SESSION['customer_telephone'];
		}

		$order->delivery['street_address'] = preg_replace('/[^A-Za-z0-9\-\s,.]/', '', $order->delivery['street_address']);

        $this->_upsDest($order->delivery['city'], $state, $order->delivery['country']['iso_code_2'], $order->delivery['postcode'],  $order->delivery['street_address'], $order->delivery['name'], $order->delivery['company'], $order->customer['telephone']);
    
		// # the check on $packing being an object will puzzle people who do things wrong (no changes when 
		// # you enable dimensional support without changing checkout_shipping.php) but better be safe
        if ($this->dimensions_support > 0 && is_object($packing)) {
          $boxValue = 0;
          $totalWeight = $packing->getTotalWeight();
          $boxesToShip = $packing->getPackedBoxes();
          for ($i = 0; $i < count($boxesToShip); $i++) {
            $this->_addItem($boxesToShip[$i]['item_length'], $boxesToShip[$i]['item_width'], $boxesToShip[$i]['item_height'], $boxesToShip[$i]['item_weight'], $boxesToShip[$i]['item_price']);
          } // end for ($i = 0; $i < count($boxesToShip); $i++)
        } else {

            // The old method. tell us how many boxes, plus the weight of each (or total? - might be sw/num boxes)
          $this->items_qty = 0; // # reset quantities

		// # $this->pkgvalue has been set as order subtotal around line 108, it will cause overcharging of insurance if 
		// # not divided by the number of boxes

          for ($i = 0; $i < $shipping_num_boxes; $i++) {
            $this->_addItem(0, 0, 0, $shipping_weight, number_format(($this->pkgvalue/$shipping_num_boxes), 2, '.', ''));
          }

        }

		// # Time In Transit: used for expected delivery dates is skipped when set to "Not" in the admin
      if ($this->timeInTransitView != 'Not') {
        if ($this->dimensions_support > 0) {
            $this->weight_for_timeintransit = round($totalWeight,1);
        } else {
            $this->weight_for_timeintransit = round($shipping_num_boxes * $shipping_weight,1);
        }

        // # Added to workaround time in transit error 270033 if total weight of packages is over 150lbs or 70kgs
        if (($this->weight_for_timeintransit > 150) && ($this->unit_weight == "LBS")) {
          $this->weight_for_timeintransit = 150;          
        } else if (($this->weight_for_timeintransit > 70) && ($this->unit_weight == "KGS")) {
          $this->weight_for_timeintransit = 70;          
        }
       
        // # make sure that when TimeinTransit fails to get results (error or not available)
        // # this is not obvious to the client
        $_upsGetTimeServicesResult = $this->_upsGetTimeServices();
        if ($_upsGetTimeServicesResult != false && is_array($_upsGetTimeServicesResult)) {
          $this->servicesTimeintransit = $_upsGetTimeServicesResult;
        }
        if ($this->logfile) {
          error_log("------------------------------------------\n", 3, $this->logfile);
          error_log("Time in Transit: " . $this->timeintransit . "\n", 3, $this->logfile);
        }
      } // # END if ($this->timeInTransitView != 'Not') 
   
	// # END Time In Transit


		// # request two different rates methods. 
		// # one method triggers "shop" rates (list of allowed shipping methods)
		// # the other request is to grab the SurePost rate, if available.


		// # IF surePost is active, and no rates returned for standard UPS shipping, use SurePost as default.

		if($this->surepost_active == 'True' && !is_array($this->_upsGetQuote())) { 
    	    $upsQuote = $this->_upsGetQuote('surePost');
		} else {
	        $upsQuote = $this->_upsGetQuote();
		}

		// # do NOT run a SurePost quote if the address is international - will recieve API errors - Exception Puerto Rico.
		if($this->surepost_active == 'True' && ($order->delivery['country']['iso_code_2'] == 'US' || $order->delivery['country']['iso_code_2'] == 'PR') && is_array($this->_upsGetQuote()) && is_array($this->_upsGetQuote('surePost'))) { 

			// # Pass the trigger to the _upsGetQuote() function for SurePost response
    	    $upsSurePostQuote = $this->_upsGetQuote('surePost');

			// # now merge the two response arrays!
	        $upsQuote = array_merge($upsQuote, $upsSurePostQuote);
		}

        if ((is_array($upsQuote)) && (sizeof($upsQuote) > 0)) {
          if (defined('MODULE_SHIPPING_UPSXML_WEIGHT1') &&  MODULE_SHIPPING_UPSXML_WEIGHT1 == 'False') {
            $this->quotes = array('id' => $this->code, 'module' => $this->title);
            usort($upsQuote, array($this, "rate_sort_func"));
          } else {
            if ($this->dimensions_support > 0) {
                $this->quotes = array('id' => $this->code, 'module' => $this->title . ' (' . $this->boxCount . ($this->boxCount > 1 ? ' pkgs, ' : ' pkg, ') . number_format($totalWeight,2) . ' ' . strtolower($this->unit_weight) . ' total)');
            } else {
                $this->quotes = array('id' => $this->code, 'module' => $this->title . ' (' . $shipping_num_boxes . ($this->boxCount > 1 ? ' pkgs x ' : ' pkg x ') . number_format($shipping_weight, 2) . ' ' . strtolower($this->unit_weight) . ' total)');

            }
            usort($upsQuote, array($this, "rate_sort_func"));
          } // end else/if if (defined('MODULE_SHIPPING_UPSXML_WEIGHT1')
            $methods = array();
            for ($i=0; $i < sizeof($upsQuote); $i++) {
                list($type, $cost) = each($upsQuote[$i]);
                if (strpos($type, ' (')) {
                  $basetype = substr($type, 0, strpos($type, ' ('));
                } else {
                  $basetype = $type;
                }
                // BOF limit choices, behaviour changed from versions < 1.2
                if ($this->exclude_choices($basetype)) continue;
                // EOF limit choices
                if ( $method == '' || $method == $basetype ) {
                    $_type = $type;

                    if ($this->timeInTransitView == "Raw") {
                      if (isset($this->servicesTimeintransit[$basetype])) {
                        $_type = $_type;
                      }        
                    } else {
                      if (isset($this->servicesTimeintransit[$basetype])) {
                        $eta_array = explode("-", $this->servicesTimeintransit[$basetype]["date"]);
                        $months = array (" ", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
                        $eta_arrival_date = $months[(int)$eta_array[1]]." ".$eta_array[2].", ".$eta_array[0];
                        $_type .= ", Est Delivery Date: ".$eta_arrival_date;
                      }          
                    }                    
					// # changed to make handling percentage based
                    if ($this->handling_type == "Percentage") {
                        if ($_type) $methods[] = array('id' => $basetype, 'title' => $_type, 'cost' => ((($this->handling_fee * $cost)/100) + $cost));
                    } else {
                        if ($_type) $methods[] = array('id' => $basetype, 'title' => $_type, 'cost' => ($this->handling_fee + $cost));
                    }
                }
            }
            if ($this->tax_class > 0) {
                $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
            }
            $this->quotes['methods'] = $methods;
        } else {
            if ( $upsQuote != false ) {
                $errmsg = $upsQuote;
            } else {
                $errmsg = MODULE_SHIPPING_UPSXML_RATES_TEXT_UNKNOWN_ERROR;
            }
            $errmsg .= '<br>' . MODULE_SHIPPING_UPSXML_RATES_TEXT_IF_YOU_PREFER . ' ' . STORE_NAME.' via <a href="mailto:'.STORE_OWNER_EMAIL_ADDRESS.'"><u>Email</u></a>.';
            $this->quotes = array('module' => $this->title, 'error' => $errmsg);
        }
        if (tep_not_null($this->icon)) {
            $this->quotes['icon'] = tep_image($this->icon, $this->title);
        }

        return $this->quotes;
    }

    //**************
    function check() {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_UPSXML_RATES_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }


    //*************
    function keys() {
        return array('MODULE_SHIPPING_UPSXML_RATES_STATUS', 'MODULE_SHIPPING_UPSXML_RATES_SORT_ORDER', 'MODULE_SHIPPING_UPSXML_RATES_ACCESS_KEY', 'MODULE_SHIPPING_UPSXML_RATES_USERNAME', 'MODULE_SHIPPING_UPSXML_RATES_PASSWORD', 'MODULE_SHIPPING_UPSXML_RATES_PICKUP_METHOD', 'MODULE_SHIPPING_UPSXML_RATES_PACKAGE_TYPE', 'MODULE_SHIPPING_UPSXML_RATES_CUSTOMER_CLASSIFICATION_CODE', 'MODULE_SHIPPING_UPSXML_RATES_ORIGIN', 'MODULE_SHIPPING_UPSXML_RATES_CITY', 'MODULE_SHIPPING_UPSXML_RATES_STATEPROV', 'MODULE_SHIPPING_UPSXML_RATES_COUNTRY', 'MODULE_SHIPPING_UPSXML_RATES_POSTALCODE', 'MODULE_SHIPPING_UPSXML_RATES_MODE', 'MODULE_SHIPPING_UPSXML_RATES_QUOTE_TYPE', 'MODULE_SHIPPING_UPSXML_RATES_USE_NEGOTIATED_RATES', 'MODULE_SHIPPING_UPSXML_RATES_UPS_ACCOUNT_NUMBER', 'MODULE_SHIPPING_UPSXML_RATES_MANUAL_NEGOTIATED_RATE', 'MODULE_SHIPPING_UPSXML_HANDLING_TYPE', 'MODULE_SHIPPING_UPSXML_RATES_HANDLING', 'MODULE_SHIPPING_UPSXML_INSURE', 'MODULE_SHIPPING_UPSXML_CURRENCY_CODE', 'MODULE_SHIPPING_UPSXML_RATES_TAX_CLASS', 'MODULE_SHIPPING_UPSXML_RATES_ZONE','MODULE_SHIPPING_UPSXML_TYPES', 'MODULE_SHIPPING_UPSXML_SHIPPING_DAYS_DELAY', 'MODULE_SHIPPING_UPSXML_EMAIL_ERRORS', 'MODULE_SHIPPING_UPSXML_RATES_TIME_IN_TRANSIT_VIEW', 'MODULE_SHIPPING_UPSXML_WEIGHT1', 'MODULE_SHIPPING_UPSXML_SUREPOST');
    }

    //***********************
    function _upsProduct($prod){
        $this->_upsProductCode = $prod;
    }

    //**********************************************
    function _upsOrigin($city, $stateprov, $country, $postal, $warehouse=''){
        $this->_upsOriginWarehouse = $warehouse;
        $this->_upsOriginCity = $city;
        $this->_upsOriginStateProv = $stateprov;
        $this->_upsOriginCountryCode = $country;
        $postal = str_replace(' ', '', $postal);
        if ($country == 'US') {
            $this->_upsOriginPostalCode = substr($postal, 0, 5);
        } else {
            $this->_upsOriginPostalCode = $postal;
        }
    }

    //**********************************************
    function _upsDest($city, $stateprov, $country, $postal, $street_address, $name, $company, $phone) {

        $this->_upsDestCity = $city;
        $this->_upsDestStreetAddress = $street_address;
        $this->_upsDestStateProv = $stateprov;
        $this->_upsDestCountryCode = $country;

        $this->_upsDestName = $name;
        $this->_upsDestCompany = $company;
        $this->_upsDestPhoneNumber = $phone;

        $postal = str_replace(' ', '', $postal);
        if ($country == 'US') {
            $this->_upsDestPostalCode = substr($postal, 0, 5);
            $territories = array('AS','FM','GU','MH','MP','PR','PW','VI');
            if (in_array($this->_upsDestStateProv,$territories)) {
              $this->_upsDestCountryCode = $stateprov;
              }
        } else if ($country == 'BR') {
            $this->_upsDestPostalCode = substr($postal, 0, 5);
        } else {
            $this->_upsDestPostalCode = $postal;
        }
    }

    //************************
    function _upsAction($action) {
        // # rate - Single Quote; shop - All Available Quotes
        $this->_upsActionCode = $action;
    }

    //********************************************
    function _addItem($length, $width, $height, $weight, $price = 0 ) {

		// # weight default calculation for zero weight items
		// # zero weights are usually due to Free Shipping override
		// # Free Shipping override removes weight for multi-item shipments
		// # UPS API will throw an error for Zero weight if not defaulted to something.

        if ((float)$weight < 0.01) {
			$weight = 0.01;
        } else {
        	$weight = number_format($weight, 2);
     	}

        $index = $this->items_qty;
        $this->item_length[$index] = ($length ? (string)$length : '0' );
        $this->item_width[$index] = ($width ? (string)$width : '0' );
        $this->item_height[$index] = ($height ? (string)$height : '0' );
        $this->item_weight[$index] = ($weight ? (string)$weight : '0' );
        $this->item_price[$index] = $price;
        $this->items_qty++;

    }

    //*********************
    function _upsGetQuote($surePost=false) {

        $xmlResult = '';

		if($surePost == 'surePost') { 
			$surePost = true;
		}

		// # parse address from constant
		// # Zwaveproducts.com\r\n201-706-7190\r\n\r\nEmail: sales@zwaveproducts.com\r\n70 Commercial Ave.  - Warehouse A\r\nMoonachie NJ 07074
		$addressline1 = explode("\r\n", STORE_NAME_ADDRESS);

        // Create the access request
        $accessRequestHeader =
        "<?xml version=\"1.0\"?>\n".
        "<AccessRequest xml:lang=\"en-US\">\n".
        "   <AccessLicenseNumber>". $this->access_key ."</AccessLicenseNumber>\n".
        "   <UserId>". $this->access_username ."</UserId>\n".
        "   <Password>". $this->access_password ."</Password>\n".
        "</AccessRequest>\n";

        $ratingServiceSelectionRequestHeader =
        "<?xml version=\"1.0\"?>\n".
        "<RatingServiceSelectionRequest xml:lang=\"en-US\">\n".
        "   <Request>\n".
        "       <TransactionReference>\n".
        "           <CustomerContext>XOLT Rate</CustomerContext>\n".
        "           <XpciVersion>". $this->xpci_version ."</XpciVersion>\n".
        "       </TransactionReference>\n".
        "       <RequestAction>Rate</RequestAction>\n";


        if ($this->surepost_active == 'True' && $surePost === true) {

 	       $ratingServiceSelectionRequestHeader .=
    	    "       <RequestOption>Rate</RequestOption>\n";
		} else {
    	    $ratingServiceSelectionRequestHeader .=
        	"       <RequestOption>shop</RequestOption>\n";
		}

        $ratingServiceSelectionRequestHeader .=
        "   </Request>\n";
        // # according to UPS the CustomerClassification and PickupType containers should
        // # not be present when the origin country is non-US see:

        if ($this->origin_country == 'US') {
	        $ratingServiceSelectionRequestHeader .=
    	    "   <PickupType>\n".
        	"       <Code>". $this->pickup_methods[$this->pickup_method] ."</Code>\n".
	        "   </PickupType>\n";
     		"   <CustomerClassification>\n".
	        "       <Code>". $this->customer_classification ."</Code>\n".
    	    "   </CustomerClassification>\n";
        }

        $ratingServiceSelectionRequestHeader .=
        "   <Shipment>\n".
        "       <Shipper>\n".
   	    "         <ShipperNumber>" . $this->access_account_number . "</ShipperNumber>\n".
        "           <Address>\n".
       	"               <AddressLine1>". $addressline1[4] ."</AddressLine1>\n".
        "               <City>". $this->_upsOriginCity ."</City>\n".
        "               <StateProvinceCode>". $this->_upsOriginStateProv ."</StateProvinceCode>\n".
        "               <PostalCode>". $this->_upsOriginPostalCode ."</PostalCode>\n".
        "               <CountryCode>". $this->_upsOriginCountryCode ."</CountryCode>\n".
        "           </Address>\n".
        "       </Shipper>\n".
        "       <ShipTo>\n";

		if(!empty($this->_upsDestCompany)) { 
			$ratingServiceSelectionRequestHeader .= 
			"			<CompanyName>". $this->_upsDestCompany ."</CompanyName>\n";
		}

        $ratingServiceSelectionRequestHeader .=
        "    	   <AttentionName>". $this->_upsDestName ."</AttentionName>\n".
        "    	   <PhoneNumber>". $this->_upsDestPhoneNumber ."</PhoneNumber>\n".
        "          <Address>\n".
		"				<AddressLine>". $this->_upsDestStreetAddress ."</AddressLine>\n".
        "               <City>". $this->_upsDestCity ."</City>\n".
        "               <StateProvinceCode>". $this->_upsDestStateProv ."</StateProvinceCode>\n".
        "               <CountryCode>". $this->_upsDestCountryCode ."</CountryCode>\n".
        "               <PostalCode>". $this->_upsDestPostalCode ."</PostalCode>\n".
        ($this->quote_type == "Residential" ? "<ResidentialAddressIndicator/>\n" : "") .
        "           </Address>\n".
        "       </ShipTo>\n";


        if ($this->surepost_active == 'True' && $surePost === true) {

			// # Extend ShipFrom to use multiple warehouse extension
			// # for now default to Origin
			// # added capability for $this->_upsOriginWarehouse parameter for conditions

    	    $ratingServiceSelectionRequestHeader .=
        	"       <ShipFrom>\n".
	        "           <CompanyName>".STORE_NAME."</CompanyName>\n".
    	    "           <Address>\n".
        	"               <AddressLine1>". $addressline1[4] ."</AddressLine1>\n".
	        "               <City>". $this->_upsOriginCity ."</City>\n".
    	    "               <StateProvinceCode>". $this->_upsOriginStateProv ."</StateProvinceCode>\n".
        	"               <PostalCode>". $this->_upsOriginPostalCode ."</PostalCode>\n".
	        "               <CountryCode>". $this->_upsOriginCountryCode. "</CountryCode>\n".
    	    "           </Address>\n".
        	"       </ShipFrom>\n";


				// # expand parcelType to detect media type - perhaps !in_array(explode(",", MODULE_SHIPPING_UPSXML_TYPES));
				// # service code 92 and 93 are for parcel weights either < or > 1 lb.
				// # service code 94 is for BPM (Bound Printed Matter / books) and 95 is for Media.

				// # for now just base on weight
				
				if($this->item_weight[0] < 1) {
					// # convert to Ounces for SurePost Less then 1 lb method.
					if($this->unit_weight == 'KGS') { 
						$this->item_weight[0] = ($this->item_weight[0] * 35.274);
					} elseif($this->unit_weight == 'LBS') { 
						$this->item_weight[0] = ($this->item_weight[0] * 16);
					}

					$this->unit_weight = 'OZS';			
				}

				$this->surePost_parcelType = ($this->unit_weight == 'LBS' && $this->item_weight[0] >= 1 ? '93' : '92');

    		    $ratingServiceSelectionRequestHeader .=
				"		<Service>\n".
        		"			<Code>". $this->surePost_parcelType ."</Code>\n".
	        	"			<Description>Parcel Select</Description>\n".
				"		</Service>\n";

        }

		// # UPS can only process 50 packages at once
        $numgroups = ceil($this->items_qty / 50); 

		// # process each group of packages
        for ($g = 0; $g < $numgroups; $g++) { 
	        $ratingServiceSelectionRequestPackageContent = '';
    	    $start = $g * 50;
        	$end = ($g + 1 == $numgroups) ? $this->items_qty : $start + 50; // # if last group end with number of packages otherwise do 50 more

	        for ($i = $start; $i < $end; $i++) {

    	        $ratingServiceSelectionRequestPackageContent .=
        	    "       <Package>\n".
	            	"           <PackagingType>\n".
    	        "               <Code>". $this->package_types[$this->package_type] ."</Code>\n".
        	    "           </PackagingType>\n";
            
				if ($this->dimensions_support > 0 && ($this->item_length[$i] > 0 ) && ($this->item_width[$i] > 0 ) && ($this->item_height[$i] > 0)) {

	                $ratingServiceSelectionRequestPackageContent .=
    	            "           <Dimensions>\n".
        	        "               <UnitOfMeasurement>\n".
            	    "                   <Code>". $this->unit_length ."</Code>\n".
	                "               </UnitOfMeasurement>\n".
    	            "               <Length>". $this->item_length[$i] ."</Length>\n".
        	        "               <Width>". $this->item_width[$i] ."</Width>\n".
	                "               <Height>". $this->item_height[$i] ."</Height>\n".
    	            "           </Dimensions>\n";
	            }


	            $ratingServiceSelectionRequestPackageContent .=
    	        "           <PackageWeight>\n".
	            "               <UnitOfMeasurement>\n".
    	        "                   <Code>". $this->unit_weight ."</Code>\n".
        	    "               </UnitOfMeasurement>\n".
	            "               <Weight>". $this->item_weight[$i] ."</Weight>\n".
	   	        "           </PackageWeight>\n".
    	      	"           <PackageServiceOptions>\n";

		

				if ($this->insure_package == true) {

					if($this->insurance_cost > $this->item_price[$i] || $this->insurance_cost == 0) {
						$this->insurance_cost = $this->item_price[$i];
					} else {
						$this->insurance_cost = $this->insurance_cost;
					}

	        	    $ratingServiceSelectionRequestPackageContent .=
		            "               <InsuredValue>\n".
    		        "                   <CurrencyCode>".MODULE_SHIPPING_UPSXML_CURRENCY_CODE."</CurrencyCode>\n".
        		    "                   <MonetaryValue>".$this->insurance_cost[$i]."</MonetaryValue>\n".
	        	    "               </InsuredValue>\n";
    	        } // end if ($this->insure_package == true)


				if ($this->delivery_confirmation === true) {	
	
		    	   $ratingServiceSelectionRequestPackageContent .=
	    		    "          <DeliveryConfirmation>\n".
		            "         	<DCISType>2</DCISType>\n".
		            "	       </DeliveryConfirmation>\n";
				}
     

				$ratingServiceSelectionRequestPackageContent .=
					"           </PackageServiceOptions>\n".
		            "       </Package>\n";
			}


			$ratingServiceSelectionRequestFooter = '';
        	//"   <ShipmentServiceOptions/>\n".

	        if ($this->use_negotiated_rates === true) {
    		    $ratingServiceSelectionRequestFooter .=
            	"       <RateInformation>\n".
	            "         <NegotiatedRatesIndicator/>\n".
    	        "       </RateInformation>\n";
        	}

	        $ratingServiceSelectionRequestFooter .= "   </Shipment>\n";

        // # according to UPS the CustomerClassification and PickupType containers should
        // # not be present when the origin country is non-US see:

        if ($this->origin_country == 'US') {
        $ratingServiceSelectionRequestFooter .=
              "   <CustomerClassification>\n".
              "       <Code>". $this->customer_classification ."</Code>\n".
              "   </CustomerClassification>\n";
        }
        $ratingServiceSelectionRequestFooter .=
        "</RatingServiceSelectionRequest>\n";

        $xmlRequest = $accessRequestHeader .
        $ratingServiceSelectionRequestHeader .
        $ratingServiceSelectionRequestPackageContent .
        $ratingServiceSelectionRequestFooter;

        //post request $strXML;
        $result = $this->_post($this->protocol, $this->host, $this->port, $this->path, $this->version, $this->timeout, $xmlRequest);
        if ($xmlResult == '') { // if first group of packages
          $xmlResult = $result;
        } else { //if second group of packages then results must be combined into one larger result
          if (strpos($xmlResult, '</RatingServiceSelectionResponse>') !== false)
            $xmlResult = substr($xmlResult, 0, strpos($xmlResult, '</RatingServiceSelectionResponse>'));
          if (strpos($result, '</Response>') !== false)
            $result = substr($result, strpos($result, '</Response>') + 11);
          $xmlResult .= $result;
        }
        // BOF testing with a response from UPS saved as a text file
        // needs commenting out the line above: $xmlResult = $this->_post($this->protocol, etcetera
        /* $filename = '/home/zwave/logs/upsxml_response.xml';
        $fp = fopen($filename, "r") or die("couldn't open file");
        $xmlResult = "";
        while (! feof($fp)) {
          $xmlResult .= fgets($fp, 1024);
        } 
        // EOF testing with a text file */

        } //end groups loop

// # send test request to email 
//error_log(print_r($xmlRequest,1), 1, 'chrisd@zwaveproducts.com');


        return $this->_parseResult($xmlResult);
    }

    //******************************************************************
    function _post($protocol, $host, $port, $path, $version, $timeout, $xmlRequest) {
        $url = $protocol."://".$host.":".$port.$path;
        if ($this->logfile) {
            error_log("------------------------------------------\n", 3, $this->logfile);
            error_log("DATE AND TIME: ".date('Y-m-d H:i:s')."\n", 3, $this->logfile);
            error_log("UPS URL: " . $url . "\n", 3, $this->logfile);
        }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            // # uncomment the next line if you get curl error 60: error setting certificate verify locations
            //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // # uncommenting the next line is most likely not necessary in case of error 60
            //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
            curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

            if ($this->logfile) {
                error_log("UPS REQUEST: " . $xmlRequest . "\n", 3, $this->logfile);
            }

            $xmlResponse = curl_exec ($ch);

            if (curl_errno($ch) && $this->logfile) {
                $error_from_curl = sprintf('Error [%d]: %s', curl_errno($ch), curl_error($ch));
                error_log("Error from cURL: " . $error_from_curl . "\n", 3, $this->logfile);
            }

            // # send email if enabled in the admin section
            if (curl_errno($ch) && $this->email_errors) {
                $error_from_curl = sprintf('Error [%d]: %s', curl_errno($ch), curl_error($ch));
                error_log("Error from cURL: " . $error_from_curl . " experienced by customer with id " . $_SESSION['customer_id'] . " on " . date('Y-m-d H:i:s'), 1, STORE_OWNER_EMAIL_ADDRESS);
            }
            // # log errors to file ups_error.log when set
            if (curl_errno($ch) && $this->ups_error_file) {
                $error_from_curl = sprintf('Error [%d]: %s', curl_errno($ch), curl_error($ch));
                error_log(date('Y-m-d H:i:s')."\tcURL\t" . $error_from_curl . "\t" . $_SESSION['customer_id']."\n", 3, $this->ups_error_file);    
            }
            if ($this->logfile) {
                error_log("UPS RESPONSE: " . $xmlResponse . "\n", 3, $this->logfile);
            }
            curl_close ($ch);

        if(!$xmlResponse || strstr(strtolower(substr($xmlResponse, 0, 120)), "bad request"))  {
             // # Sometimes the UPS server responds with an HTML message (differing depending on whether the test server
             // # or the production server is used) but both have in the title tag "Bad request".
             // # Parsing this response will result in a fatal error:
             // # Call to a member function on a non-object in /blabla/includes/classes/xmldocument.php on line 57
             // # It only results in not showing Estimated Delivery Dates to the customer so avoiding the fatal error should do.
            
            $xmlResponse = "<?xml version=\"1.0\"?>\n".
            "<RatingServiceSelectionResponse>\n".
            "   <Response>\n".
            "       <TransactionReference>\n".
            "           <CustomerContext>Rating and Service</CustomerContext>\n".
            "           <XpciVersion>1.0001</XpciVersion>\n".
            "       </TransactionReference>\n".
            "       <ResponseStatusCode>0</ResponseStatusCode>\n".
            "       <ResponseStatusDescription>". MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_UNKNOWN_ERROR ."</ResponseStatusDescription>\n".
            "   </Response>\n".
            "</RatingServiceSelectionResponse>\n";

		return $xmlResponse;
	}


	// # Error response reporting:
	preg_match_all("/\<RatingServiceSelectionResponse\>(.*?)\<\/RatingServiceSelectionResponse\>/s", $xmlResponse, $responseNodes);

	foreach($responseNodes[1] as $Nodes) {

		// # GET REQUEST RESPONSE STATUS
		preg_match_all( "/\<ResponseStatusDescription\>(.*?)\<\/ResponseStatusDescription\>/",$Nodes, $ResponseStatus);
		$ResponseStatus = $ResponseStatus[1][0];

			if($ResponseStatus == 'Failure') { 

				// # GET LABEL RECOVERY RESPONSE MESSAGE
				preg_match_all( "/\<Error\>(.*?)\<\/Error\>/",$Nodes, $Error); 

				foreach($Error[1] as $ErrorNodes) {

					preg_match_all( "/\<ErrorCode\>(.*?)\<\/ErrorCode\>/",$ErrorNodes, $ErrorCode); 
					$ErrorCode = $ErrorCode[1][0];

					preg_match_all( "/\<ErrorDescription\>(.*?)\<\/ErrorDescription\>/",$ErrorNodes, $ErrorDescription); 
					$ErrorDescription = $ErrorDescription[1][0];
				}	

			//echo $ErrorDescription . "\n\n" . 'Error code: ' . $ErrorCode;
			error_log(print_r('Error code:' . $ErrorCode . "\n" . $ErrorDescription,TRUE));
			}
	}
	// # END error response reporting

// # test response 
//error_log(print_r($xmlResponse,1), 1, 'chrisd@zwaveproducts.com');
//error_log(print_r($xmlResponse,1));

            return $xmlResponse;
    }

    //*****************************
    function _parseResult($xmlResult) {
        // Parse XML message returned by the UPS post server.
        $doc = XML_unserialize ($xmlResult);

        // Get version. Must be xpci version 1.0001 or this might not work.
        $responseVersion = $doc['RatingServiceSelectionResponse']['Response']['TransactionReference']['XpciVersion'];
        if ($this->xpci_version != $responseVersion) {
            $message = MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_VERSION_ERROR;
            return $message;
        }
        // Get response code: 1 = SUCCESS, 0 = FAIL
        $responseStatusCode = $doc['RatingServiceSelectionResponse']['Response']['ResponseStatusCode'];
        if ($responseStatusCode != '1') {
            $errorMsg = $doc['RatingServiceSelectionResponse']['Response']['Error']['ErrorCode'];
            $errorMsg .= ": ";
            $errorMsg .= $doc['RatingServiceSelectionResponse']['Response']['Error']['ErrorDescription'];
            // send email if enabled in the admin section
            if ($this->email_errors) {
                error_log("UPSXML Rates Error: " . $errorMsg . " experienced by customer with id " . $_SESSION['customer_id'] . " on " . date('Y-m-d H:i:s', time()). ' from IP address ' . $_SERVER['REMOTE_ADDR'], 1, STORE_OWNER_EMAIL_ADDRESS);
            }
            // log errors to file ups_error.log when set
            if ($this->ups_error_file) {
                error_log(date('Y-m-d H:i:s')."\tRates\t" . $errorMsg . "\t" . $_SESSION['customer_id']."\n", 3, $this->ups_error_file);    
            }
                return $errorMsg;
        }

        $ratedShipments = $doc['RatingServiceSelectionResponse']['RatedShipment'];


        $aryProducts = false;
        $upstemp = array();
        if (isset($doc['RatingServiceSelectionResponse']['RatedShipment'][0])) { // more than 1 rate
          for ($i = 0; $i < count($ratedShipments); $i++) {

            $serviceCode = $ratedShipments[$i]['Service']['Code'];

			if ($this->use_negotiated_rates === true && isset($ratedShipments[$i]['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'])) {
    
				$totalCharge = $ratedShipments[$i]['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'];

			} elseif ($this->manual_negotiated_rate > 0) {
		
				$totalCharge = $ratedShipments[$i]['TotalCharges']['MonetaryValue'] * ($this->manual_negotiated_rate/100);

			} else {
			
				// # standard UPS rates
				$totalCharge = $ratedShipments[$i]['TotalCharges']['MonetaryValue'];

				$negoRateMargin = $ratedShipments[$i]['TotalCharges']['MonetaryValue'] - ($ratedShipments[$i]['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue']);
           	}

			if (!($serviceCode && $totalCharge)) continue;

            $ratedPackages = $ratedShipments[$i]['RatedPackage']; // only do this once for the first service given

            if (isset($ratedShipments[$i]['RatedPackage'][0])) { // multidimensional array of packages
              $boxCount = count($ratedPackages);
            } else {
              $boxCount = 1; // if there is only one package count($ratedPackages) returns
              // the number of fields in the array like TransportationCharges and BillingWeight
            }
            // if more than one group of packages, service codes will be repeated and therefore data needs to be combined
            $upstemp[$serviceCode]['charge'] += $totalCharge;
            $upstemp[$serviceCode]['boxes'] += $boxCount;
            $upstemp[$serviceCode]['billed_weight'] += $ratedShipments[$i]['BillingWeight']['Weight'];
            $upstemp[$serviceCode]['weight_code'] = $ratedShipments[$i]['BillingWeight']['UnitOfMeasurement']['Code'];

          } // end for ($i = 0; $i < count($ratedShipments); $i++)

          $i = 0;
          foreach ($upstemp as $key => $value) {
            $this->boxCount = $value['boxes']; // set total grouped package count
            $title = $this->service_codes[$this->origin][$key];
            if (MODULE_SHIPPING_UPSXML_WEIGHT1 == 'True')
              $title .= ' (' . UPSXML_TEXT_BILLED_WEIGHT . $value['billed_weight'] . ' ' . $value['weight_code'] . ')';
            $aryProducts[$i] = array($title => $value['charge']);
            $i++;
          }
        } elseif (isset($doc['RatingServiceSelectionResponse']['RatedShipment'])) { // only 1 rate
          $serviceCode = $ratedShipments['Service']['Code'];

		if ($this->use_negotiated_rates === true && isset($ratedShipments['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'])) {
			$totalCharge = $ratedShipments['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'];
		} elseif ($this->manual_negotiated_rate > 0) {
			$totalCharge = $ratedShipments['TotalCharges']['MonetaryValue'] * ($this->manual_negotiated_rate/100);
		} else {

			// # standard UPS rates
			$totalCharge = $ratedShipments['TotalCharges']['MonetaryValue'];

			$negoRateMargin = $ratedShipments[$i]['TotalCharges']['MonetaryValue'] - ($ratedShipments[$i]['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue']);	
		}

         if(!($serviceCode && $totalCharge))  return $aryProducts; // # is false

            $ratedPackages = $ratedShipments['RatedPackage']; // only do this once for the first service given

            if (isset($ratedShipments['RatedPackage'][0])) { // multidimensional array of packages
              $this->boxCount = count($ratedPackages);
            } else {
              $this->boxCount = 1; // if there is only one package count($ratedPackages) returns
              // the number of fields in the array like TransportationCharges and BillingWeight
            }
            $title = $this->service_codes[$this->origin][$serviceCode];
            if (MODULE_SHIPPING_UPSXML_WEIGHT1 == 'True')
              $title .= ' (' . UPSXML_TEXT_BILLED_WEIGHT . $ratedShipments['BillingWeight']['Weight'] . ' ' . $ratedShipments['BillingWeight']['UnitOfMeasurement']['Code'] . ')';
            $aryProducts[] = array($title => $totalCharge);
        }
        return $aryProducts;
    }

    // BOF Time In Transit

    //********************
    function _upsGetTimeServices() {

        if (defined('MODULE_SHIPPING_UPSXML_SHIPPING_DAYS_DELAY')) {
            $shipdate = date("Ymd", $this->today_unix_time + (86400*MODULE_SHIPPING_UPSXML_SHIPPING_DAYS_DELAY));
            $day_of_the_week = date ("w", $this->today_unix_time + (86400*MODULE_SHIPPING_UPSXML_SHIPPING_DAYS_DELAY) ) ;
            if ($day_of_the_week == "0" || $day_of_the_week == "7") { // order supposed to leave on Sunday
                $shipdate = date("Ymd", $this->today_unix_time + (86400*MODULE_SHIPPING_UPSXML_SHIPPING_DAYS_DELAY) + 86400);
            } elseif ($day_of_the_week == "6") { // order supposed to leave on Saturday
                $shipdate = date("Ymd", $this->today_unix_time + (86400*MODULE_SHIPPING_UPSXML_SHIPPING_DAYS_DELAY) + 172800);
            } 
        } else {
            $shipdate = $this->today;
        }

        // Create the access request
        $accessRequestHeader =
        "<?xml version=\"1.0\"?>\n".
        "<AccessRequest xml:lang=\"en-US\">\n".
        "   <AccessLicenseNumber>". $this->access_key ."</AccessLicenseNumber>\n".
        "   <UserId>". $this->access_username ."</UserId>\n".
        "   <Password>". $this->access_password ."</Password>\n".
        "</AccessRequest>\n";

        $timeintransitSelectionRequestHeader =
        "<?xml version=\"1.0\"?>\n".
        "<TimeInTransitRequest xml:lang=\"en-US\">\n".
        "   <Request>\n".
        "       <TransactionReference>\n".
        "           <CustomerContext>Time in Transit</CustomerContext>\n".
        "           <XpciVersion>". $this->transitxpci_version ."</XpciVersion>\n".
        "       </TransactionReference>\n".
        "       <RequestAction>TimeInTransit</RequestAction>\n".
        "   </Request>\n".
        "   <TransitFrom>\n".
        "       <AddressArtifactFormat>\n".
        "           <PoliticalDivision2>". $this->origin_city ."</PoliticalDivision2>\n".
        "           <PoliticalDivision1>". $this->origin_stateprov ."</PoliticalDivision1>\n".
        "           <CountryCode>". $this->_upsOriginCountryCode ."</CountryCode>\n".
        "           <PostcodePrimaryLow>". $this->origin_postalcode ."</PostcodePrimaryLow>\n".
        "       </AddressArtifactFormat>\n".
        "   </TransitFrom>\n".
        "   <TransitTo>\n".
        "       <AddressArtifactFormat>\n".
        "           <PoliticalDivision2>". $this->_upsDestCity ."</PoliticalDivision2>\n".
        "           <PoliticalDivision1>". $this->_upsDestStateProv ."</PoliticalDivision1>\n".
        "           <CountryCode>". $this->_upsDestCountryCode ."</CountryCode>\n".
        "           <PostcodePrimaryLow>". $this->_upsDestPostalCode ."</PostcodePrimaryLow>\n".
        "           <PostcodePrimaryHigh>". $this->_upsDestPostalCode ."</PostcodePrimaryHigh>\n".
        "       </AddressArtifactFormat>\n".
        "   </TransitTo>\n".
        "   <ShipmentWeight>\n".
        "       <UnitOfMeasurement>\n".
        "           <Code>" . $this->unit_weight . "</Code>\n".
        "       </UnitOfMeasurement>\n".
        "       <Weight>" . $this->weight_for_timeintransit . "</Weight>\n".
        "   </ShipmentWeight>\n".
        "   <InvoiceLineTotal>\n".
        "       <CurrencyCode>" . MODULE_SHIPPING_UPSXML_CURRENCY_CODE . "</CurrencyCode>\n".
        "       <MonetaryValue>" . $this->pkgvalue . "</MonetaryValue>\n".
        "   </InvoiceLineTotal>\n".
        "   <PickupDate>" . $shipdate . "</PickupDate>\n".
        "</TimeInTransitRequest>\n";

        $xmlTransitRequest = $accessRequestHeader .
        $timeintransitSelectionRequestHeader;

        //post request $strXML;
        $xmlTransitResult = $this->_post($this->protocol, $this->host, $this->port, $this->transitpath, $this->transitversion, $this->timeout, $xmlTransitRequest);
        return $this->_transitparseResult($xmlTransitResult);
    }

    //***************************************
    
    // GM 11-15-2004: modified to return array with time for each service, as
    //                opposed to single transit time for hardcoded "GND" code

    function _transitparseResult($xmlTransitResult) {
         $transitTime = array();

        // # Parse XML message returned by the UPS post server.
        $doc = XML_unserialize ($xmlTransitResult);
        // # Get version. Must be xpci version 1.0001 or this might not work.
        // # 1.0001 and 1.0002 seem to be very similar, forget about this for the moment
        /*        $responseVersion = $doc['TimeInTransitResponse']['Response']['TransactionReference']['XpciVersion'];
        if ($this->transitxpci_version != $responseVersion) {
            $message = MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_VERSION_ERROR;
            return $message;
        } */
        // # Get response code. 1 = SUCCESS, 0 = FAIL
        $responseStatusCode = $doc['TimeInTransitResponse']['Response']['ResponseStatusCode'];
        if ($responseStatusCode != '1') {
            $errorMsg = $doc['TimeInTransitResponse']['Response']['Error']['ErrorCode'];
            $errorMsg .= ": ";
            $errorMsg .= $doc['TimeInTransitResponse']['Response']['Error']['ErrorDescription'];
            // # send email if enabled in the admin section
            if ($this->email_errors) {
                error_log("UPSXML TimeInTransit Error: " . $errorMsg . " experienced by customer with id " . $_SESSION['customer_id'] . " on " . date('Y-m-d H:i:s'), 1, STORE_OWNER_EMAIL_ADDRESS);
            }
            // # log errors to file ups_error.log when set
            if ($this->ups_error_file) {
                error_log(date('Y-m-d H:i:s')."\tTimeInTransit\t" . $errorMsg . "\t" . $_SESSION['customer_id'] ."\n", 3, $this->ups_error_file);    
            }
           //  return $errorMsg;
           return false;
        }

        if (isset($doc['TimeInTransitResponse']['TransitResponse']['ServiceSummary'][0])) { // more than one EDD
               foreach ($doc['TimeInTransitResponse']['TransitResponse']['ServiceSummary'] as $key_index => $service_array) {
                    // # index by description because that's all we can relate back to the service 
                    // # with (though it can probably return the code as well but they are very
                    // # different from those used by the Rates Service and there is a lot of 
                    // # duplication so pretty useless)
                    $serviceDesc = $service_array['Service']['Description'];
                    // # hack to get EDD for UPS Saver recognized (Time in Transit uses UPS Worldwide Saver
                    // # but the service in Rates and Services is called UPS Saver)
                    if ($serviceDesc == "UPS Worldwide Saver") {
                      $serviceDesc = "UPS Saver";
                    }
                    // # only date is used so why bother with days and guaranteed?
                    // # $transitTime[$serviceDesc]["days"] = $serviceSummary[$s]->getValueByPath("EstimatedArrival/BusinessTransitDays");
                    $transitTime[$serviceDesc]['date'] = $service_array['EstimatedArrival']['Date'];
                    // # $transitTime[$serviceDesc]["guaranteed"] = $serviceSummary[$s]->getValueByPath("Guaranteed/Code");
                } // end foreach ($doc['TimeInTransitResponse']['ServiceSummary'] etc.
        } elseif (isset($doc['TimeInTransitResponse']['TransitResponse']['ServiceSummary'])) { // only one EDD
          $serviceDesc = $doc['TimeInTransitResponse']['TransitResponse']['ServiceSummary']['Service']['Description'];
          $transitTime[$serviceDesc]['date'] = $doc['TimeInTransitResponse']['TransitResponse']['ServiceSummary']['EstimatedArrival']['Date'];
        } else {
          $errorMsg = MODULE_SHIPPING_UPSXML_TIME_IN_TRANSIT_TEXT_NO_RATES;
            if ($this->ups_error_file) {
                error_log(date('Y-m-d H:i:s')."\tTimeInTransit\t" . $errorMsg . "\t" . $_SESSION['customer_id'] ."\n", 3, $this->ups_error_file);    
            }
          return false;
        }
        if ($this->logfile) {
            error_log("------------------------------------------\n", 3, $this->logfile);
            foreach($transitTime as $desc => $time) {
                error_log("Business Transit: " . $desc ." = ". $time["date"] . "\n", 3, $this->logfile);
            }
        }
        return $transitTime;
    }

    //EOF Time In Transit
 //  ***************************
  function exclude_choices($type) {
    // Used for exclusion of UPS shipping options, disallowed types are read from db (stored as 
    // short defines). The short defines are not used as such, to avoid collisions
    // with other shipping modules, they are prefixed with UPSXML_
    // These defines are found in the upsxml language file (UPSXML_US_01, UPSXML_CAN_14 etc.)
    $disallowed_types = explode(",", MODULE_SHIPPING_UPSXML_TYPES);

    if (strstr($type, "UPS")) {
        // # this will chop off "UPS" from the beginning of the line - typically something like UPS Next Day Air (1 Business Days)
        $type_minus_ups = explode("UPS", $type );
        $type_root = trim($type_minus_ups[1]);
    } else { // service description does not contain UPS (unlikely)
        $type_root = trim($type);
    }

    for ($za = 0; $za < count ($disallowed_types); $za++ ) {
      // when no disallowed types are present, --none-- is in the db but causes an error because --none-- is
      // not added as a define
      if ($disallowed_types[$za] == '--none--' ) continue; 
        if ($type_root == constant('UPSXML_' . trim($disallowed_types[$za]))) {
            return true;
        } // end if ($type_root == constant(trim($disallowed_types[$za]))).
    }
    // if the type is not disallowed:
    return false;
  }
// Next function used for sorting the shipping quotes on rate: low to high is default.
  function rate_sort_func ($a, $b) {
    
   $av = array_values($a);
   $av = $av[0];
   $bv = array_values($b);
   $bv = $bv[0];

//  return ($av == $bv) ? 0 : (($av < $bv) ? 1 : -1); // for having the high rates first
  return ($av == $bv) ? 0 : (($av > $bv) ? 1 : -1); // low rates first
  
  }
} // # end class upsxml


// # Next two functions are used only in the admin for disallowed shipping options.
// # The (short) constants like US_12, CAN_14 are stored in the database
// # to stay below 255 characters. The defines themselves are found in the upsxml
// # language file prefixed with UPSXML_ to avoid collisions with other shipping modules.
// # They can be moved to admin/includes/function/general.php if you like but don't forget
// # to remove them from this file in future updates or you will get an error in the admin
// # about re-declaring functions

  function get_multioption_upsxml($values) {
         if (tep_not_null($values)) {
             $values_array = explode(',', $values);
             foreach ($values_array as $key => $_method) {
               if ($_method == '--none--') {
                 $method = $_method;
               } else {
                 $method = constant('UPSXML_' . trim($_method));
               }
               $readable_values_array[] = $method;
             }
             $readable_values = implode(', ', $readable_values_array);
             return $readable_values;
         } else {
           return '';
         }
  }
  
  function upsxml_cfg_select_multioption_indexed($select_array, $key_value, $key = '') {
    for ($i=0; $i<sizeof($select_array); $i++) {
      $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
      $string .= '<br><input type="checkbox" name="' . $name . '" value="' . $select_array[$i] . '"';
      $key_values = explode( ", ", $key_value);
      if ( in_array($select_array[$i], $key_values) ) $string .= ' CHECKED';
      $string .= '> ' . constant('UPSXML_' . trim($select_array[$i]));
    } 
    $string .= '<input type="hidden" name="' . $name . '" value="--none--">';
    return $string;
  }
?>