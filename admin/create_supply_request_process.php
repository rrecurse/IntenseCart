<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_SUPPLY_REQUEST_PROCESS);

	$error = false;

	$suppliers_id = tep_db_prepare_input((int)$_POST['suppliers_id']);
	$suppliers_to_warehouse_id = tep_db_prepare_input((int)$_POST['suppliers_to_warehouse_id']);



	$suppl_query = tep_db_query("SELECT * FROM ".TABLE_SUPPLIERS." WHERE suppliers_id = ".$suppliers_id);
	$supplier = tep_db_fetch_array($suppl_query);

	$warehouse_query = tep_db_query("SELECT stw.*, sr.suppliers_to_warehouse_id
									 FROM suppliers_to_warehouse stw
									 LEFT JOIN ".TABLE_SUPPLY_REQUEST." sr ON sr.suppliers_to_warehouse_id = stw.suppliers_to_warehouse_id
									 WHERE stw.suppliers_to_warehouse_id = ".$suppliers_to_warehouse_id);


	$warehouse = tep_db_fetch_array($warehouse_query);

	$suppliers_name = (!empty($supplier['suppliers_name']) ? $supplier['suppliers_name'] : $supplier['suppliers_group_name']);

	$company = $supplier['suppliers_group_name'];

	$email_address = $supplier['suppliers_email_address'];

	$telephone = $supplier['suppliers_phone'];
	//$fax = tep_db_prepare_input($_POST['fax']);

	$address1 = $supplier['suppliers_address1'];
	$address2 = $supplier['suppliers_address2'];
	$postcode = $supplier['suppliers_zip'];
	$city = $supplier['suppliers_city'];
	$state = $supplier['suppliers_state'];
	$country = $supplier['suppliers_country'];
	
	$payment_method = tep_db_prepare_input($_POST['payment_method']);

	$format_id = "1";
	$new_value = "1";

	$temp_amount = "0";
	$temp_amount = number_format($temp_amount, 2, '.', ',');
  
	$currency_text = DEFAULT_CURRENCY . " 1";

	if(isset($_POST['currency'])) {
		$currency_text = tep_db_prepare_input($_POST['currency']);
	 	$currency_array = explode(" ", $currency_text);
	}

  
	if(!empty($_GET['pID']) && !empty($_GET['supplier_id'])) {
		$prod_id = (int)$_GET['pID'];
		$supplier_id = (int)$_GET['supplier_id'];
	}

	
  $currency = $currency_array[0];
  $currency_value = $currency_array[1];


    $sql_data_array = array('suppliers_id' => $suppliers_id,
							'suppliers_name' => $suppliers_name,
							'suppliers_company' => $company,
                            'suppliers_street_address' => $address1,
							'suppliers_suburb' => $address2,
							'suppliers_city' => $city,
							'suppliers_postcode' => $postcode,
							'suppliers_state' => $state,
							'suppliers_country' => $country,
							'suppliers_telephone' => $telephone,
                            'suppliers_email_address' => $email_address,
							'suppliers_address_format_id' => $format_id,
							'suppliers_to_warehouse_id' => $suppliers_to_warehouse_id,
							'delivery_name' => $warehouse['suppliers_to_warehouse_contact'],
							'delivery_company' => $warehouse['suppliers_to_warehouse_name'],
                            'delivery_street_address' => $warehouse['suppliers_to_warehouse_address1'],
							'delivery_suburb' => $warehouse['suppliers_to_warehouse_address2'],
							'delivery_city' => $warehouse['suppliers_to_warehouse_city'],
							'delivery_postcode' => $warehouse['suppliers_to_warehouse_zip'],
							'delivery_state' => $warehouse['suppliers_to_warehouse_state'],
							'delivery_country' => $warehouse['suppliers_to_warehouse_country'],

							'delivery_address_format_id' => $format_id,	
							'date_requested' => 'now()', 
                            'supply_request_status_id' => 1,
							'currency' => $currency,
							'currency_value' => $currency_value,
							'payment_method' => $payment_method
							); 


	tep_db_perform(TABLE_SUPPLY_REQUEST, $sql_data_array); 
 
	$insert_id = tep_db_insert_id();

	/* 
	// # why write a status  to the history table if nothing to write beyond initial creation date?
    $sql_data_array = array('supply_request_id' => $insert_id,
							'supply_request_status_id' => $new_value,
                            'date_added' => date('Y-m-d H:i:s', time())
							);

     tep_db_perform(TABLE_SUPPLY_REQUEST_STATUS_HISTORY, $sql_data_array);
	*/


	// # if product_id and suppliers_id are both present, 
	// # preselect the product from the supplier and add to the supply request products table

	if($prod_id && $supplier_id) { 
		
		$preselect_query = tep_db_query("SELECT spg.suppliers_group_price AS cost_price,
												pd.products_id AS products_id,
												pd.products_name,
												p.products_weight,
												p.products_model
										 FROM ". TABLE_PRODUCTS ." p
										 LEFT JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON pd.products_id = p.products_id
										 LEFT JOIN suppliers_products_groups spg ON spg.products_id = pd.products_id
										 WHERE spg.products_id = ".$prod_id."
										 AND spg.suppliers_group_id = ".$supplier_id."
										");
		if(tep_db_num_rows($preselect_query) > 0) { 

			$preselect = tep_db_fetch_array($preselect_query);

			$cost = number_format($preselect['cost_price'],2);

			$preselect_array = array('supply_request_id ' => $insert_id,
									 'products_id' => $preselect['products_id'],
									 'products_model' => $preselect['products_model'],
									 'products_name' => $preselect['products_name'],
									 'products_price' => $cost,
									 'cost_price' => $cost,
									 'products_quantity' => '1',
									 'products_weight' => $preselect['products_weight'],
									 );

			tep_db_perform(TABLE_SUPPLY_REQUEST_PRODUCTS, $preselect_array);
		}
	} // # END pre selection 

  
    $sql_data_array = array('supply_request_id' => $insert_id,
                            'title' => TEXT_SUBTOTAL,
                            'text' => (!empty($cost) ? '$'. $cost : $temp_amount),
                            'value' => (!empty($cost) ? $cost : '0.00'), 
                            'class' => "ot_subtotal", 
                            'sort_order' => "1");
	
	tep_db_perform(TABLE_SUPPLY_REQUEST_TOTAL, $sql_data_array);


   $sql_data_array = array('supply_request_id' => $insert_id,
                            'title' => TEXT_DISCOUNT,
                            'text' => $temp_amount,
                            'value' => "0.00",
                            'class' => "ot_customer_discount",
                            'sort_order' => "2");
   tep_db_perform(TABLE_SUPPLY_REQUEST_TOTAL, $sql_data_array);
  
    $sql_data_array = array('supply_request_id' => $insert_id,
                            'title' => TEXT_DELIVERY,
                            'text' => $temp_amount,
                            'value' => "0.00", 
                            'class' => "ot_shipping", 
                            'sort_order' => "3");
    tep_db_perform(TABLE_SUPPLY_REQUEST_TOTAL, $sql_data_array);
	
    $sql_data_array = array('supply_request_id' => $insert_id,
                            'title' => TEXT_TAX,
                            'text' => $temp_amount,
                            'value' => "0.00", 
                            'class' => "ot_tax", 
                            'sort_order' => "4");
    tep_db_perform(TABLE_SUPPLY_REQUEST_TOTAL, $sql_data_array);
  
      $sql_data_array = array('supply_request_id' => $insert_id,
                            'title' => TEXT_TOTAL,
                            'text' => (!empty($cost) ? '$'. $cost : $temp_amount),
                            'value' => (!empty($cost) ? $cost : '0.00'),  
                            'class' => "ot_total", 
                            'sort_order' => "5");
    tep_db_perform(TABLE_SUPPLY_REQUEST_TOTAL, $sql_data_array);
  

    tep_redirect(tep_href_link(FILENAME_EDIT_SUPPLY_REQUEST, 'sID=' . $insert_id, 'SSL'));


  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>