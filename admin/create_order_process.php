<?php

// ############################################
/*  Copyright (c) 2006 - 2016 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ORDER_PROCESS);

	//if ($_POST['action'] != 'process') {
		//tep_redirect(tep_href_link(FILENAME_CREATE_ORDER, '', 'SSL'));
	//}

	$error = false; // # reset error flag

	$customer_id = tep_db_prepare_input($_POST['customers_id']);
	$firstname = tep_db_prepare_input($_POST['firstname']);
	$lastname = tep_db_prepare_input($_POST['lastname']);
	$company = tep_db_prepare_input($_POST['company']);
	$street_address = tep_db_prepare_input($_POST['street_address']);
	$suburb = tep_db_prepare_input($_POST['suburb']);
	$postcode = tep_db_prepare_input($_POST['postcode']);
	$city = tep_db_prepare_input($_POST['city']);
	$zone_id = tep_db_prepare_input($_POST['zone_id']);
	$state = tep_db_prepare_input($_POST['state']);
	$country = tep_db_prepare_input($_POST['country']);
	$email_address = tep_db_prepare_input($_POST['email_address']);
	$telephone = tep_db_prepare_input($_POST['telephone']);

	$gender = tep_db_prepare_input($_POST['gender']);
	$dob = tep_db_prepare_input($_POST['dob']);
	$fax = tep_db_prepare_input($_POST['fax']);
	$newsletter = tep_db_prepare_input($_POST['newsletter']);
	$password = tep_db_prepare_input($_POST['password']);
	$confirmation = tep_db_prepare_input($_POST['confirmation']);

	$billing_name = (!empty($_POST['billing_name']) ? tep_db_prepare_input($_POST['billing_name']) : $firstname . ' ' . $lastname);
	$billing_company = (!empty($_POST['billing_company']) ? tep_db_prepare_input($_POST['billing_company']) : $company);
	$billing_street_address = (!empty($_POST['billing_street_address']) ? tep_db_prepare_input($_POST['billing_street_address']) : $street_address);
	$billing_suburb = (isset($_POST['billing_suburb']) ? tep_db_prepare_input($_POST['billing_suburb']) : $suburb);
	$billing_postcode = (!empty($_POST['billing_postcode']) ? tep_db_prepare_input($_POST['billing_postcode']) : $postcode);
	$billing_city = (!empty($_POST['billing_city']) ? tep_db_prepare_input($_POST['billing_city']) : $city);
	$billing_state = (!empty($_POST['billing_state']) ? tep_db_prepare_input($_POST['billing_state']) : $state);

	if(!empty($_POST['billing_country'])) { 
	
		if(is_numeric($_POST['billing_country'])) { 

			$country_name_query = tep_db_query("SELECT countries_name FROM ". TABLE_COUNTRIES ." WHERE countries_id = '". (int)$_POST['billing_country'] ."'");
			$billing_country = (tep_db_num_rows($country_name_query) > 0 ? tep_db_result($country_name_query,0) : $country);

		} else { 
		
			$billing_country =  tep_db_prepare_input($_POST['delivery_country']);
		}

	} else {
	
		$billing_country =  $country;
	}


	$delivery_name = (!empty($_POST['delivery_name']) ? tep_db_prepare_input($_POST['delivery_name']) : $firstname . ' ' . $lastname);
	$delivery_company = (!empty($_POST['delivery_company']) ? tep_db_prepare_input($_POST['delivery_company']) : $company);
	$delivery_street_address = (!empty($_POST['delivery_street_address']) ? tep_db_prepare_input($_POST['delivery_street_address']) : $street_address);
	$delivery_suburb = (isset($_POST['delivery_suburb']) ? tep_db_prepare_input($_POST['delivery_suburb']) : $suburb);
	$delivery_postcode = (!empty($_POST['delivery_postcode']) ? tep_db_prepare_input($_POST['delivery_postcode']) : $postcode);
	$delivery_city = (!empty($_POST['delivery_city']) ? tep_db_prepare_input($_POST['delivery_city']) : $city);
	$delivery_state = (!empty($_POST['delivery_state']) ? tep_db_prepare_input($_POST['delivery_state']) : $state);

	if(!empty($_POST['delivery_country'])) { 
	
		if(is_numeric($_POST['delivery_country'])) { 

			$country_name_query = tep_db_query("SELECT countries_name FROM ". TABLE_COUNTRIES ." WHERE countries_id = '". (int)$_POST['delivery_country'] ."'");
			$delivery_country = (tep_db_num_rows($country_name_query) > 0 ? tep_db_result($country_name_query,0) : $country);

		} else { 
		
			$delivery_country =  tep_db_prepare_input($_POST['delivery_country']);
		}

	} else {
	
		$delivery_country =  $country;
	}


	$format_query = tep_db_query("SELECT address_format_id FROM ".TABLE_COUNTRIES." WHERE countries_name='".$country."'");
	$format_row = tep_db_fetch_array($format_query);
	$format_id = ($format_row) ? $format_row['address_format_id'] : 1;

	$size = "1";
	$payment_method = (!empty($_POST['pay_method']) ? tep_db_prepare_input($_POST['pay_method']) : DEFAULT_PAYMENT_METHOD);

	// # check pricing group ID and set string name for orders_source
	$price_group_id = tep_db_result(tep_db_query("SELECT customers_group_id FROM ". TABLE_CUSTOMERS ." WHERE customers_id = '".$customer_id."'"),0);
	$orders_source = ($price_group_id > 1 ? 'vendor' : 'retail');

	$new_value = "1";

	$temp_amount = "0";
	$temp_amount = number_format($temp_amount, 2, '.', '');
  
	$currency_text = DEFAULT_CURRENCY . ", 1";

	if(isset($_POST['Currency'])) {
		$currency_text = tep_db_prepare_input($_POST['Currency']);
	}
  
	$currency_array = explode(",", $currency_text);
  
	$currency = $currency_array[0];
	$currency_value = $currency_array[1];


	$sql_data_array = array('customers_id' => $customer_id,
							'customers_name' => $firstname . ' ' . $lastname,
							'customers_company' => $company,
                            'customers_street_address' => $street_address,
							'customers_suburb' => $suburb,
							'customers_city' => $city,
							'customers_postcode' => $postcode,
							'customers_state' => $state,
							'customers_country' => $country,
							'customers_telephone' => $telephone,
                            'customers_email_address' => $email_address,
							'customers_address_format_id' => $format_id,

							'delivery_name' => $delivery_name,
							'delivery_company' => $delivery_company,
                            'delivery_street_address' => $delivery_street_address,
							'delivery_suburb' => $delivery_suburb,
							'delivery_city' => $delivery_city,
							'delivery_postcode' => $delivery_postcode,
							'delivery_state' => $delivery_state,
							'delivery_country' => $delivery_country,
							'delivery_address_format_id' => $format_id,

							'billing_name' => $billing_name,
							'billing_company' => $billing_company,
                            'billing_street_address' => $billing_street_address,
							'billing_suburb' => $billing_suburb,
							'billing_city' => $billing_city,
							'billing_postcode' => $billing_postcode,
							'billing_state' => $billing_state,
							'billing_country' => $billing_country,
							'billing_address_format_id' => $format_id,

							'date_purchased' => 'now()', 
                            'orders_status' => DEFAULT_ORDERS_STATUS_ID,
							'currency' => $currency,
							'currency_value' => $currency_value,
							'payment_method' => $payment_method,
							'orders_source' => $orders_source
							); 

	// # old
	tep_db_perform(TABLE_ORDERS, $sql_data_array);

	$insert_id = tep_db_insert_id();
 
  	$sql_data_array = array('orders_id' => $insert_id,
							//'new_value' => $new_value,	//for 2.2
							'orders_status_id' => $new_value, //for MS1 or MS2
                            'date_added' => 'now()');
	tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  
  
  	$sql_data_array = array('orders_id' => $insert_id,
                            'title' => TEXT_SUBTOTAL,
                            'text' => $temp_amount,
                            'value' => "0.00", 
                            'class' => "ot_subtotal", 
                            'sort_order' => "1");
    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

/*
	$sql_data_array = array('orders_id' => $insert_id,
                            'title' => TEXT_DISCOUNT,
                            'text' => $temp_amount,
                            'value' => "0.00",
                            'class' => "ot_customer_discount",
                            'sort_order' => "2");
   tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
*/

	$sql_data_array = array('orders_id' => $insert_id,
                            'title' => 'Discount Coupons',
                            'text' => $temp_amount,
                            'value' => "0.00",
                            'class' => "ot_coupon",
                            'sort_order' => "2");
   tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

  
    $sql_data_array = array('orders_id' => $insert_id,
                            'title' => TEXT_DELIVERY,
                            'text' => $temp_amount,
                            'value' => "0.00", 
                            'class' => "ot_shipping", 
                            'sort_order' => "3");
    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
	
    $sql_data_array = array('orders_id' => $insert_id,
                            'title' => TEXT_TAX,
                            'text' => $temp_amount,
                            'value' => "0.00", 
                            'class' => "ot_tax", 
                            'sort_order' => "4");
    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
  
      $sql_data_array = array('orders_id' => $insert_id,
                            'title' => TEXT_TOTAL,
                            'text' => $temp_amount,
                            'value' => "0.00", 
                            'class' => "ot_total", 
                            'sort_order' => "5");
    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);


	// # create payments table entry. Needed to avoid dependency errors with $pay object inside /admin/includes/classes/order.php class.
	// # will be update with actual details of order once saved.

  	$sql_data_array = array('orders_id' => $insert_id,
							'method' => 'payment_manual',
                            'status' => 'incomplete',
                            'amount' => $temp_amount,
                            'ref_payments_id' => 'null',
                            'date_created' => 'now()',
                            'date_processed' => 'null',
                            'ref_id' => 'null',
                            'extra_info' => 'null'
							);

	tep_db_perform(TABLE_PAYMENTS, $sql_data_array);

  

    tep_redirect(tep_href_link(FILENAME_EDIT_ORDERS, 'oID=' . $insert_id, 'SSL'));


  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>