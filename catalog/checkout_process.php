<?php

  include('includes/application_top.php');
  set_time_limit(300);
// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }
  
  if (!tep_session_is_registered('sendto')) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

  if ( (tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!tep_session_is_registered('payment')) ) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
 }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

  include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

// # load selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
  if (isset($HTTP_POST_VARS['payment'])) $payment = $HTTP_POST_VARS['payment'];
  //---PayPal WPP Modification START ---//	
    if (tep_paypal_wpp_enabled()) {
      $ec_enabled = true;
    } else {
      $ec_enabled = false;
    }
    
  if ($credit_covers) $payment=''; // CCGV
    if ($ec_enabled) {
      $ec_checkout = true;
      if (!tep_session_is_registered('paypal_ec_token') && !tep_session_is_registered('paypal_ec_payer_id') && !tep_session_is_registered('paypal_ec_payer_info')) { 
        $ec_checkout = false;
      } else {
        $payment = 'paypal_wpp';
      }
    }
  //---PayPal WPP Modification END ---//
  $payment_modules = new payment($payment);

// # load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($shipping);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;
  
  include(DIR_WS_FUNCTIONS . 'encryption.php');

// # load the before_process function from the payment modules
  $$payment->before_process();

  require_once(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;

  $order_totals = $order_total_modules->process();

  $sql_data_array = array('customers_id' => $customer_id,
                          'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                          'customers_company' => $order->customer['company'],
                          'customers_street_address' => $order->customer['street_address'],
                          'customers_suburb' => $order->customer['suburb'],
                          'customers_city' => $order->customer['city'],
                          'customers_postcode' => $order->customer['postcode'], 
                          'customers_state' => $order->customer['state'], 
                          'customers_country' => $order->customer['country']['title'], 
                          'customers_telephone' => $order->customer['telephone'], 
                          'customers_email_address' => $order->customer['email_address'],
                          'customers_address_format_id' => $order->customer['format_id'], 
                          'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'], 
                          'delivery_company' => $order->delivery['company'],
                          'delivery_street_address' => $order->delivery['street_address'], 
                          'delivery_suburb' => $order->delivery['suburb'], 
                          'delivery_city' => $order->delivery['city'], 
                          'delivery_postcode' => $order->delivery['postcode'], 
                          'delivery_state' => $order->delivery['state'], 
                          'delivery_country' => $order->delivery['country']['title'], 
                          'delivery_address_format_id' => $order->delivery['format_id'], 
                          'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
                          'billing_company' => $order->billing['company'],
                          'billing_street_address' => $order->billing['street_address'], 
                          'billing_suburb' => $order->billing['suburb'], 
                          'billing_city' => $order->billing['city'], 
                          'billing_postcode' => $order->billing['postcode'], 
                          'billing_state' => $order->billing['state'], 
                          'billing_country' => $order->billing['country']['title'], 
                          'billing_address_format_id' => $order->billing['format_id'], 
                          'payment_method' => $order->info['payment_method'], 
                          'cc_type' => $order->info['cc_type'], 
                          'cc_owner' => $order->info['cc_owner'], 
                          'cc_number' => $order->info['cc_number'], 
						  'cc_cvv2' => $order->info['cc_cvv2'],
                          'cc_expires' => $order->info['cc_expires'], 
                          'date_purchased' => 'now()', 
                          'orders_status' => $order->info['order_status'], 
                          'currency' => $order->info['currency'], 
                          'currency_value' => $order->info['currency_value']);

  tep_db_perform(TABLE_ORDERS, $sql_data_array);

  $insert_id = tep_db_insert_id();

  
  if (!$ec_checkout && $storecard == 'yes') {
    $cc_query = tep_db_query("SELECT customers_personal FROM customers_personal WHERE customers_id = '".(int)$customer_id."' LIMIT 1");
    $cc_combined = tep_db_prepare_input(tep_cc_encrypt($order->info['cc_type'] . '|' . $order->info['cc_number'] . '|' . $order->info['cc_owner'] . '|' . substr($order->info['cc_expires'], 0, 2) . '/' . substr($order->info['cc_expires'], -2) . '|' . $order->info['cc_checkcode']));
    
    if (tep_db_num_rows($cc_query) > 0) {
      $cc_res = tep_db_fetch_array($cc_query);
      //If they credit card used is not the same as what's in the database, update the database
      if (strlen($order->info['cc_number']) > 10 && $cc_combined != $cc_res['customers_personal']) {
        tep_db_query("UPDATE customers_personal SET customers_personal = '".$cc_combined."' WHERE customers_id = '".(int)$customer_id."'");
      }
    } else {
      tep_db_query("INSERT INTO customers_personal VALUES ('" . (int)$customer_id."', '".$cc_combined."')");
    }
  }
  
	for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    	$sql_data_array = array('orders_id' => $insert_id,
        	                    'title' => $order_totals[$i]['title'],
            	                'text' => $order_totals[$i]['text'],
                	            'value' => $order_totals[$i]['value'], 
                    	        'class' => $order_totals[$i]['code'], 
                        	    'sort_order' => $order_totals[$i]['sort_order']);

		tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

		if($order_totals[$i]['code'] == 'ot_total') {

			if($_SESSION['orders_source'] == 'email' && tep_session_is_registered('nID')) { 

				tep_db_query("UPDATE ".TABLE_NEWSLETTER_STATS." 
							  SET conversions = (conversions + 1),
							  conv_amount = (conv_amount + ".$order_totals[$i]['value'].")
							  WHERE newsletters_id = '".$_SESSION['nID']."'
							  AND email = '".$order->customer['email_address']."'
							  AND ip = '".(!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')."'
							 ");		
			}
		}
	}

  $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
  $sql_data_array = array('orders_id' => $insert_id, 
                          'orders_status_id' => $order->info['order_status'], 
                          'date_added' => 'now()', 
                          'customer_notified' => $customer_notification,
                          'comments' => $order->info['comments']);
  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

// # initialized for the email confirmation
  $products_ordered = '';
  $subtotal = 0;
  $total_tax = 0;
  $subtotal_price = 0;
  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
    $products_stock_attributes = null;
    if (STOCK_LIMITED == 'true') {
        $products_attributes = $order->products[$i]['attributes'];
	//if (DOWNLOAD_ENABLED == 'true') {

        $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename 
                            FROM " . TABLE_PRODUCTS . " p
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                             ON p.products_id=pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                             ON pa.products_attributes_id=pad.products_attributes_id
                            WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";

	// # Will work with only one option for downloadable products
	// # otherwise, we have to build the query dynamically with a loop

		//$products_attributes = $order->products[$i]['attributes'];

        if (is_array($products_attributes)) {
          $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
      }
        $stock_query = tep_db_query($stock_query_raw);
      } else {
        $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
      }
      if (tep_db_num_rows($stock_query) > 0) {
        $stock_values = tep_db_fetch_array($stock_query);

        $actual_stock_bought = $order->products[$i]['qty'];
        $download_selected = false;
        if ((DOWNLOAD_ENABLED == 'true') && isset($stock_values['products_attributes_filename']) && tep_not_null($stock_values['products_attributes_filename'])) {
          $download_selected = true;
          $products_stock_attributes='$$DOWNLOAD$$';
        }
		// # If not downloadable and attributes present, adjust attribute stock
        if (!$download_selected && is_array($products_attributes)) {
          $all_nonstocked = true;
          $products_stock_attributes_array = array();
          foreach ($products_attributes as $attribute) {
            if ($attribute['track_stock'] == 1) {
              $products_stock_attributes_array[] = $attribute['option_id'] . "-" . $attribute['value_id'];
              $all_nonstocked = false;
            }
          } 
          if ($all_nonstocked) {
            $actual_stock_bought = $order->products[$i]['qty'];
          }  else {
            asort($products_stock_attributes_array, SORT_NUMERIC);
            $products_stock_attributes = implode(",", $products_stock_attributes_array);
            $attributes_stock_query = tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_stock_attributes = '$products_stock_attributes' AND products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
            if (tep_db_num_rows($attributes_stock_query) > 0) {
              $attributes_stock_values = tep_db_fetch_array($attributes_stock_query);
              $attributes_stock_left = $attributes_stock_values['products_stock_quantity'] - $order->products[$i]['qty'];
              tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity = '" . $attributes_stock_left . "' where products_stock_attributes = '$products_stock_attributes' AND products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
              $actual_stock_bought = ($attributes_stock_left < 1) ? $attributes_stock_values['products_stock_quantity'] : $order->products[$i]['qty'];
            } else {
              $attributes_stock_left = 0 - $order->products[$i]['qty'];
              tep_db_query("insert into " . TABLE_PRODUCTS_STOCK . " (products_id, products_stock_attributes, products_stock_quantity) values ('" . tep_get_prid($order->products[$i]['id']) . "', '" . $products_stock_attributes . "', '" . $attributes_stock_left . "')");
              $actual_stock_bought = 0;
            }
          }
        }
//        $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
//      }
//      if (tep_db_num_rows($stock_query) > 0) {
//        $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
        if (!$download_selected) {
          $stock_left = $stock_values['products_quantity'] - $actual_stock_bought;
          tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET products_quantity = products_quantity - '" . $actual_stock_bought . "', last_stock_change=NOW() WHERE products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
//++++ QT Pro: End Changed Code
        if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
          tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
        }
      }

    }


// # Update products_ordered

    tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET products_ordered = (products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . ") WHERE products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");


    if (!isset($products_stock_attributes)) $products_stock_attributes=null;

		// # Retrieve current-day product costing from the products table and add to orders products.
		// # important to keep historical pricing / costs for inventory since this can fluctuate with time.

		// # if no cost found in suppliers_products_groups, try the products table for old format

		// # costing from suppliers_products_groups table
		$cost_price_query = tep_db_query("SELECT suppliers_group_price FROM suppliers_products_groups WHERE products_id = '". tep_get_prid($order->products[$i]['id']) ."' AND priority = '0' LIMIT 1");
		$cost_price = (tep_db_num_rows($cost_price_query) > 0 ? tep_db_result($cost_price_query,0) : 0);

		// # costing from products table
		$cost_old_query = tep_db_query("SELECT products_price_myself FROM ". TABLE_PRODUCTS ." WHERE products_id = '". tep_get_prid($order->products[$i]['id']) ."'");	
		$cost_old = (tep_db_num_rows($cost_old_query) > 0 ? tep_db_result($cost_old_query,0) : 0);

		// # if supplier cost is empty, use old format
		$cost = (!empty($cost_price) ? $cost_price : $cost_old);


    $sql_data_array = array('orders_id' => $insert_id, 
                            'products_id' => tep_get_prid($order->products[$i]['id']), 
                            'products_model' => $order->products[$i]['model'], 
                            'products_name' => $order->products[$i]['name'], 
                            'products_price' => $order->products[$i]['price'], 
                            'cost_price' => (float)$cost,
                            'final_price' => $order->products[$i]['final_price'], 
                            'products_tax' => $order->products[$i]['tax'], 
                            'products_quantity' => $order->products[$i]['qty'],
                            'products_stock_attributes' => $products_stock_attributes);

    tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = tep_db_insert_id();

//------insert customer choosen option to order--------
    $attributes_exist = '0';
    $products_ordered_attributes = '';
    if (isset($order->products[$i]['attributes'])) {
      $attributes_exist = '1';
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        if (DOWNLOAD_ENABLED == 'true') {
          $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . $order->products[$i]['id'] . "' 
                                and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' 
                                and pa.options_id = popt.products_options_id 
                                and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' 
                                and pa.options_values_id = poval.products_options_values_id 
                                and popt.language_id = '" . $languages_id . "' 
                                and poval.language_id = '" . $languages_id . "'";
          $attributes = tep_db_query($attributes_query);
        } else {
          $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
        }
        $attributes_values = tep_db_fetch_array($attributes);

        $sql_data_array = array('orders_id' => $insert_id, 
                                'orders_products_id' => $order_products_id, 
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $attributes_values['products_options_values_name'], 
                                'options_values_price' => $attributes_values['options_values_price'], 
                                'price_prefix' => $attributes_values['price_prefix']);
        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
          $sql_data_array = array('orders_id' => $insert_id, 
                                  'orders_products_id' => $order_products_id, 
                                  'orders_products_filename' => $attributes_values['products_attributes_filename'], 
                                  'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
                                  'download_count' => $attributes_values['products_attributes_maxcount']);
          tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
        }
        $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
      }
    }
//------insert customer choosen option eof ----
    $subtotal_price += $order->products[$i]['final_price'];
    $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
    $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
    $total_cost += $total_products_price;

    $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
  }
 $order_total_modules->apply_credit();// CCGV
$ship = explode('_', $shipping['id']);
$ship[0] = trim($ship[0]);
$ship[1] = trim($ship[1]);

//Shipping Information Arrays
$ship_info = array();
$ship_info['usps'] = array('name' => 'USPS', 'track_url' => 'http://www.usps.com/shipping/trackandconfirm.htm', 'track_name' => 'Delivery Confirmation Number');
$ship_info['ups'] = array('name' => 'UPS', 'track_url' => 'http://www.ups.com/WebTracking/track?loc=en_US', 'track_name' => 'Tracking Label Number');
$ship_info['fedex'] = array('name' => 'FedEx', 'track_url' => 'http://www.fedex.com/Tracking', 'track_name' => 'Tracking Number');

$ship_info['usps']['timetable']['Express Mail'] = '2';
$ship_info['usps']['timetable']['Priority Mail'] = '2-3';		
$ship_info['usps']['timetable']['Parcel Post'] = '7-10';
  
$ship_info['ups']['timetable']['Next Day'] = '1';	
$ship_info['ups']['timetable']['2nd Day Air'] = '2';
$ship_info['ups']['timetable']['3 Day Select'] = '3';
$ship_info['ups']['timetable']['Ground'] = '3-5';

$ship_info['fedex1']['timetable']['Priority'] = '2';
$ship_info['fedex1']['timetable']['2 Day Air'] = '2';
$ship_info['fedex1']['timetable']['Standard Overnight'] = '1';
$ship_info['fedex1']['timetable']['First Overnight'] = '1';
$ship_info['fedex1']['timetable']['Express Saver'] = '3';
$ship_info['fedex1']['timetable']['Home Delivery'] = '3-5';
$ship_info['fedex1']['timetable']['Ground Service'] = '3-5';

$service_eta = 0;

foreach ($ship_info[$ship[0]]['timetable'] as $service => $eta) {
  if (strpos($ship[1], $service) !== false) {
    $service_eta = $eta;
  }
}

if ($service_eta == 0) $service_eta = 5;

// lets start with the email confirmation
  $email_order =  'Thank you for shopping at ' . STORE_NAME . '!' . "\n\n";

  $email_order .= 'Your Order has been received.' . "\n\n";

  $email_order .= 'Please allow up to 24 hours for your order to be processed.  Order processing usually occurs on business days. We will process your order and deliver your package using ' . $order->info['shipping_method'] . ' within ' . $service_eta . ' business days.' . "\n\n";

  $email_order .= 'Once your package has been shipped you will receive an e-mail with your ' . $ship_info[$ship[0]]['name'] . ' "' . $ship_info[$ship[0]]['track_name'] . '" number which can be tracked via the ' . $ship_info[$ship[0]]['name'] . ' website at: ' . $ship_info[$ship[0]]['track_url'] . "\n\n";

  $email_order .= 'You may return to your ' . STORE_NAME . ' account anytime for the status of this order by revisiting bodyinacinch.com and selecting the "my account" link, or by clicking here:' . "\n\n";

  $email_order .= tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n\n";

  $email_order .= 'If an item you purchased is on back order, we will contact you personally either by phone or e-mail regarding your order status.' . "\n\n";

  $email_order .= 'Please note*' . "\n\n";

  $email_order .= 'On your next visit, you may skip the sign up process at checkout by signing in with your email address and password you specified on your first visit.'  . "\n\n";

  $email_order .= 'Sign-in at any time to view your past order history and add your previous purchases into your shopping cart for speedier checkouts. You can track orders, keep a wishlist and even send your wishlist to your friends or family. ' . "\n\n";

  $email_order .= 'If you forgot your password, kindly follow the online instructions on how to retrieve it.' . "\n\n";

  $email_order .= 'Once again, thank you for shopping at ' . STORE_NAME . '!' . "\n\n";

  $email_order .= 'Sales Team' . "\n";
  $email_order .= STORE_OWNER_EMAIL_ADDRESS . "\n";
  $email_order .= HTTP_SERVER . "\n\n";

  $email_order .= 'P.S.  - Don\'t forget to tell your friends about us! - send our website to all your friends and family by using our easy use "tell a friend" feature!' . "\n\n"; 

  $email_order .= tep_href_link('tell_a_friend.php') . "\n\n";

  $email_order .= 'Below you will find your receipt of purchase:' . "\n\n";

  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Sold To' . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= tep_address_label($customer_id, $billto, 0, '', "\n") . "\n";
  $email_order .= 'Phone: ' . $order->customer['telephone'] . "\n";
  $email_order .= 'Email Address: ' . $order->customer['email_address'] . "\n\n";

  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Shipped To' . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= STORE_NAME . ' Order Number: ' . $insert_id . "\n";
  $email_order .= 'Detailed Invoice: ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n";
  $email_order .= 'Date Ordered: ' . strftime(DATE_FORMAT_LONG) . "\n\n";

  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Products' . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= $products_ordered . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Sub-Total: $' . number_format($subtotal_price, 2) . "\n";
  $email_order .= 'Total: $' . number_format($order->info['total'], 2) . "\n\n";

  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Delivery Address' . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Billing Address' . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Payment Method' . "\n";
  $email_order .= '------------------------------------------------------' . "\n";
  $payment_class = $$payment;
  $email_order .= $payment_class->title . "\n\n";
  
  $email_order .= '------------------------------------------------------' . "\n";
  $email_order .= 'Use this information next time you order for quick access to your Customer Information.' . "\n";
  $email_order .= '------------------------------------------------------' . "\n\n";

  $email_order .= tep_href_link('account.php') . "\n";
  $email_order .= 'User Name: ' . $order->customer['email_address'] . "\n";
  $email_order .= 'Password: ' . $_SESSION['customer_password'] . "\n";


  tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT_1, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

  if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
    tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT_1, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
  }

// remove items from wishlist if customer purchased them $wishList->clear();
  $wishList->clear();

// load the after_process function from the payment modules
  $$payment->after_process();

  $cart->reset(true);

// unregister session variables used during checkout
  tep_session_unregister('sendto');
  tep_session_unregister('billto');
  tep_session_unregister('shipping');
  tep_session_unregister('payment');
  tep_session_unregister('comments');
  if(tep_session_is_registered('credit_covers')) tep_session_unregister('credit_covers');// CCGV
  if(tep_session_is_registered('orders_source')) tep_session_unregister('orders_source');
  if(tep_session_is_registered('nID')) tep_session_unregister('nID');
  $order_total_modules->clear_posts();// CCGV

  tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));

  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
