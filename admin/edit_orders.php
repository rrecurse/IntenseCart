<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


session_save_path ('/tmp');
session_start('cart');

	if(!isset($_SESSION['cart'])) $_SESSION['cart'] = true;

	require('includes/application_top.php');

	// # Ajax suggest search 
	if(isset($_GET['search']) && !empty($_GET['search'])) {

		$thestr = (strlen($_GET['search']) > 2) ? tep_db_prepare_input($_GET['search']) : '';

		$customers_group_query = tep_db_query("SELECT c.customers_group_id
											   FROM ".TABLE_CUSTOMERS." c 
											   LEFT JOIN ". TABLE_ORDERS ." o ON o.customers_id = c.customers_id
											   WHERE o.orders_id = '".(int)$_GET['oID']."'
											  ");

		$customers_group_id = (tep_db_num_rows($customers_group_query) > 0 ? tep_db_result($customers_group_query,0) : 0);

		$query = tep_db_query("SELECT p2c.categories_id, 
									  p.products_id, 
									  cd.categories_name, 
									  p.products_image, 
									  pd.products_name
								FROM ". TABLE_CATEGORIES_DESCRIPTION ." cd
								JOIN ". TABLE_PRODUCTS_TO_CATEGORIES ." p2c ON p2c.categories_id = cd.categories_id
								JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON pd.products_id = p2c.products_id
								JOIN ". TABLE_PRODUCTS ."  p ON p.products_id = p2c.products_id
								JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '".$customers_group_id."')
								JOIN ". TABLE_SUPPLIERS_PRODUCTS_GROUPS ." spg ON spg.products_id = p.products_id
								WHERE (pd.products_name LIKE '%".$thestr."%' 
										OR p.products_model LIKE '%".$thestr."%' 
										OR p.products_upc LIKE '%".$thestr."%' 
										OR p.products_sku LIKE '%".$thestr."%' 
										OR spg.suppliers_sku LIKE '%".$thestr."%')	
								AND p.products_status = 1 
								AND (p.products_price > 0 OR pg.customers_group_price > 0)
								GROUP BY p.products_id
								ORDER BY pd.products_name, p.products_model
								LIMIT 10
							   ");

		if(tep_db_num_rows($query) > 0) {

			$thejson = array();

			while($row = tep_db_fetch_array($query)) { 

				$thejson[] = array($row['categories_id'], $row['products_id'], $row['categories_name'], $row['products_image'], $row['products_name']);

			}

			tep_db_free_result($query);
			echo json_encode($thejson);
		}

		exit();
    }

	// # END Ajax suggest search 


	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();

	include(DIR_WS_CLASSES . 'order.php');

	// # START CONFIGURATION ################################

	// # Correction tax pre-values

	// # Optional Tax Rates, e.g. shipping tax of 17.5% is "17.5"
	$AddCustomTax = "7.0"; 
	$AddShippingTax = "7.0"; 
	$AddLevelDiscountTax = "7.0"; 
	$AddCustomerDiscountTax = "7.0"; 
	
	// # END OF CONFIGURATION ################################

	$CommentsWithStatus = tep_field_exists(TABLE_ORDERS_STATUS_HISTORY, "comments");
  
	$orders_statuses = array();
	$orders_status_array = array();
	$orders_status_query = tep_db_query("SELECT orders_status_id, orders_status_name 
										 FROM " . TABLE_ORDERS_STATUS . " 
										 WHERE language_id = '" . (int)$languages_id . "'
										 ORDER BY orders_status_id
									 	");

	while ($orders_status = tep_db_fetch_array($orders_status_query)) {

		$orders_statuses[] = array('id' => $orders_status['orders_status_id'],
        	                       'text' => $orders_status['orders_status_name']);

		$orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
	}

	$action = (isset($_GET['action']) ? $_GET['action'] : 'edit');
	$sub_action = (isset($_POST['sub_action']) && $_POST['sub_action']) ? $_POST['sub_action'] : 'edit';

	// # Update Inventory Quantity
	$order_query = tep_db_query("SELECT orders_id FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$oID . "'");

	if(tep_not_null($action)) {
		switch ($action) {
 	
		// # 1. UPDATE ORDER 

			case 'update_order':

				//require_once(DIR_WS_CLASSES . 'payment.php');
				//$payment = new payment;

				if(tep_db_num_rows($order_query) > 0) {
					$order_exists = true;
				} else {
					$order_exists = false;
					//tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=edit'));	
				}

				$order_updated = false;
				$oID = tep_db_prepare_input($_GET['oID']);


				// # Checking for current status info
				$check_status_query = tep_db_query("SELECT o.customers_name, 
														   o.customers_email_address, 
														   o.orders_status, 
														   o.date_purchased, 
														   os.ship_carrier, 
														   os.tracking_number, 
														   os.ship_date 
												   FROM ". TABLE_ORDERS ." o 
												   LEFT JOIN ".TABLE_ORDERS_SHIPPED." os ON o.orders_id = os.orders_id 
												   WHERE o.orders_id = '". tep_db_input($oID)."'
												  ");

				$check_status = tep_db_fetch_array($check_status_query);

				$status = tep_db_prepare_input((int)$_POST['status']);

				$comments = tep_db_prepare_input($_POST['comments']);

				$order = new order($oID);

				// # check pricing group ID and set string name for orders_source
				$price_group_id_query = tep_db_query("SELECT customers_group_id FROM ". TABLE_CUSTOMERS ." WHERE customers_id = '".$order->customer['customers_id']."'");
				$price_group_id = (tep_db_num_rows($price_group_id_query) > 0 ? tep_db_result($price_group_id_query,0) : 0);
				$price_group = ($price_group_id > 1 ? 'vendor' : 'retail');

				$orders_source = (!empty($order->info['orders_source']) ? $order->info['orders_source'] : $price_group);

				// # 1.1 UPDATE ORDER INFO #####

				$pay_method = (!empty($_POST['pay_method']) ? $_POST['pay_method'] : $order->info['payment_method']);

				$meth = str_replace('payment_', '', $pay_method);

				$cc_number = (!empty($_POST[$meth.'_cc_num']) ? '************'. substr($_POST[$meth.'_cc_num'], -4) : $order->info['cc_number']);

				$cc_type = (!empty($_POST[$meth.'_cc_type']) ? $_POST[$meth.'_cc_type'] : $order->info['cc_type']);
				$cc_owner = (!empty($_POST[$meth.'_cc_owner']) ? $_POST[$meth.'_cc_owner'] : $order->info['cc_owner']);

				$cc_expires = (!empty($_POST[$meth.'_exp_month']) && !empty($_POST[$meth.'_exp_year']) ? (int)$_POST[$meth.'_exp_month'] . (int)$_POST[$meth.'_exp_year'] : $order->info['cc_expires']);

				$cc_cvv2 = (!empty($_POST[$meth.'_cvv2']) ? $_POST[$meth.'_cvv2'] : '');


				$update_totals = $_POST['update_totals'];

				// # ensure the update_totals var is a populated array
				// # if not properly formated, foreach($update_totals ....) lines will throw warnings.

				if(!is_array($update_totals)) {

					$update_totals = array();

					$totals_query = tep_db_query("SELECT title, value, class, orders_total_id as total_id FROM ". TABLE_ORDERS_TOTAL." WHERE orders_id = '".$oID."'");

					while($order_total =  tep_db_fetch_array($totals_query)) {
				  		$update_totals[] = array('title' => $order_total['title'],
												 'value' => (!empty($order_total['value']) ? $order_total['value'] : '0.00'),
												 'class' => $order_total['class'],
												 'total_id' => $order_total['total_id']
												);
					}

				}


				// # lets define some vars for cleaner usage below

				$customers_name = (!empty($_POST['update_customer_name']) && $_POST['update_customer_name'] != $order->customer['name'] ? tep_db_prepare_input($_POST['update_customer_name']) : $order->customer['name']);

				$customers_company = (!empty($_POST['update_customer_company']) && $_POST['update_customer_company'] != $order->customer['company'] ? tep_db_prepare_input($_POST['update_customer_company']) : $order->customer['company']);

				$customers_street_address = (!empty($_POST['update_customer_street_address']) && $_POST['update_customer_street_address'] != $order->customer['street_address'] ? tep_db_prepare_input($_POST['update_customer_street_address']) : $order->customer['street_address']);

				$customers_suburb = (!empty($_POST['update_customer_suburb']) && $_POST['update_customer_suburb'] != $order->customer['suburb'] ? tep_db_prepare_input($_POST['update_customer_suburb']) : $order->customer['suburb']);

				$customers_city = (!empty($_POST['update_customer_city']) && $_POST['update_customer_city'] != $order->customer['city'] ? tep_db_prepare_input($_POST['update_customer_city']) : $order->customer['city']);
				
				$customers_postcode = (!empty($_POST['update_customer_postcode']) && $_POST['update_customer_postcode'] != $order->customer['postcode'] ? tep_db_prepare_input($_POST['update_customer_postcode']) : $order->customer['postcode']);

				$customers_state = (!empty($_POST['update_customer_state']) && $_POST['update_customer_state'] != $order->customer['state'] ? tep_db_prepare_input($_POST['update_customer_state']) : $order->customer['state']);

				$customers_country = (!empty($_POST['update_customer_country']) && $_POST['update_customer_country'] != $order->customer['country'] ? tep_db_prepare_input($_POST['update_customer_country']) : $order->customer['country']);


				$customers_telephone = (!empty($_POST['update_customer_telephone']) && $_POST['update_customer_telephone'] != $order->customer['telephone'] ? tep_db_prepare_input($_POST['update_customer_telephone']) : $order->customer['telephone']);

				$customers_email_address = (!empty($_POST['update_customer_email_address']) && $_POST['update_customer_email_address'] != $order->customer['email_address'] ? tep_db_prepare_input($_POST['update_customer_email_address']) : $order->customer['email_address']);


				$billing_name = (!empty($_POST['update_billing_name']) && $_POST['update_billing_name'] != $order->billing['name'] ? tep_db_prepare_input($_POST['update_billing_name']) : $order->billing['name']);

				$billing_company = (!empty($_POST['update_customer_company']) && $_POST['update_customer_company'] != $order->billing['company'] ? tep_db_prepare_input($_POST['update_customer_company']) : $order->billing['company']);

				$billing_street_address = (!empty($_POST['update_customer_street_address']) && $_POST['update_customer_street_address'] != $order->billing['street_address'] ? tep_db_prepare_input($_POST['update_customer_street_address']) : $order->billing['street_address']);

				$billing_suburb = (!empty($_POST['update_billing_suburb']) && $_POST['update_billing_suburb'] != $order->billing['suburb'] ? tep_db_prepare_input($_POST['update_billing_suburb']) : $order->billing['suburb']);
				
				$billing_city = (!empty($_POST['update_customer_city']) && $_POST['update_customer_city'] != $order->billing['city'] ? tep_db_prepare_input($_POST['update_customer_city']) : $order->billing['city']);

				$billing_state = (!empty($_POST['update_customer_state']) && $_POST['update_customer_state'] != $order->billing['state'] ? tep_db_prepare_input($_POST['update_customer_state']) : $order->billing['state']);
			
				$billing_postcode = (!empty($_POST['update_customer_postcode']) && $_POST['update_customer_postcode'] != $order->billing['postcode'] ? tep_db_prepare_input($_POST['update_customer_postcode']) : $order->billing['postcode']);
		
				$billing_country = (!empty($_POST['update_customer_country']) && $_POST['update_customer_country'] != $order->billing['country'] ? tep_db_prepare_input($_POST['update_customer_country']) : $order->billing['country']);
				

				$delivery_name = (!empty($_POST['update_delivery_name']) && $_POST['update_delivery_name'] != $order->delivery['name'] ? tep_db_prepare_input($_POST['update_delivery_name']) : $order->delivery['name']);

				$delivery_company = (!empty($_POST['update_delivery_company']) && $_POST['update_delivery_company'] != $order->delivery['company'] ? tep_db_prepare_input($_POST['update_delivery_company']) : $order->delivery['company']);

				$delivery_street_address = (!empty($_POST['update_delivery_street_address']) && $_POST['update_delivery_street_address'] != $order->delivery['street_address'] ? tep_db_prepare_input($_POST['update_delivery_street_address']) : $order->delivery['street_address']);
	
				$delivery_suburb = (!empty($_POST['update_delivery_suburb']) && $_POST['update_delivery_suburb'] != $order->delivery['suburb'] ? tep_db_prepare_input($_POST['update_delivery_suburb']) : $order->delivery['suburb']);
			
				$delivery_city = (!empty($_POST['update_delivery_city']) && $_POST['update_delivery_city'] != $order->delivery['city'] ? tep_db_prepare_input($_POST['update_delivery_city']) : $order->delivery['city']);

				$delivery_postcode = (!empty($_POST['update_delivery_postcode']) && $_POST['update_delivery_postcode'] != $order->delivery['postcode'] ? tep_db_prepare_input($_POST['update_delivery_postcode']) : $order->delivery['postcode']);
			
				$delivery_state = (!empty($_POST['update_delivery_state']) && $_POST['update_delivery_state'] != $order->delivery['state'] ? tep_db_prepare_input($_POST['update_delivery_state']) : $order->delivery['state']);

				$delivery_country = (!empty($_POST['update_delivery_country']) && $_POST['update_delivery_country'] != $order->delivery['country'] ? tep_db_prepare_input($_POST['update_delivery_country']) : $order->delivery['country']);


				$update_shipping_method = (!empty($_POST['update_shipping_method']) ? tep_db_prepare_input($_POST['update_shipping_method']) : $order->info['shipping_method']);

				$update_currency = (!empty($_POST['update_currency']) ? tep_db_prepare_input($_POST['update_currency']) : $order->info['update_currency']);


				
				$update_orders_array = array('customers_name' => $customers_name,
											 'customers_company' => $customers_company,
											 'customers_street_address' => $customers_street_address,
											 'customers_suburb' => $customers_suburb,
											 'customers_city' => $customers_city,
											 'customers_postcode' => $customers_postcode,
											 'customers_state' => $customers_state,
											 'customers_country' => $customers_country,
											 'customers_telephone' => $customers_telephone,
											 'customers_email_address' => $customers_email_address,
											 'billing_name' => $billing_name,
											 'billing_company' => $billing_company,
											 'billing_street_address' => $billing_street_address,
											 'billing_suburb' => $billing_suburb,
											 'billing_city' => $billing_city,
											 'billing_state' => $billing_state,
											 'billing_postcode' => $billing_postcode,
											 'billing_country' => $billing_country,
											 'delivery_name' => $delivery_name,
											 'delivery_company' => $delivery_company,
											 'delivery_street_address' => $delivery_street_address,
											 'delivery_suburb' => $delivery_suburb,
											 'delivery_city' => $delivery_city,
											 'delivery_postcode' => $delivery_postcode,
											 'delivery_state' => $delivery_state,
											 'delivery_country' => $delivery_country,
		    		                         'payment_method' => $pay_method,
		    		                         'cc_type' => $cc_type,
		    		                         'cc_owner' => $cc_owner,
		    		                         'cc_number' => $cc_number,
		    		                         'shipping_method' => $update_shipping_method,
		    		                         'currency' =>  $update_currency,
		    		                         'currency_value' => $currencies->get_value($update_currency),
											 'last_modified' => date('Y-m-d H:i:s', time()),
		    		                         'date_purchased' => (!empty($check_status['date_purchased']) ? $check_status['date_purchased'] : date('Y-m-d H:i:s', time())),
											 'orders_source' => $orders_source,
											); 
	
				if(!$oID) {
					$oID = tep_db_insert_id();
				}

				tep_db_perform(TABLE_ORDERS, $update_orders_array, 'update', "orders_id = '" . $oID . "'");



				// # Send email notifications
				$customer_notified = 0;
				
				if(!empty($cc_cvv2) && !empty($cc_number)) { 

					$process = 1;

				} else {

					$process = 0;
				}


				if($check_status['orders_status'] != $status) {
					$order->setStatus($status, $process);
					$order_updated = true;
					$thestatus = sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status])."\n\n";

				} else {
					$thestatus = '';
				}

				if($_POST['notify'] == 'on') {

					$customer_notified = 1;

					if($_POST['notify_comments'] == 'on') {
						if(!empty($comments) || $comments != '') {
							$comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments);
						} else {
							$comments = '';
						}
					}


					$email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' <a href="'.tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') .'">My Order History</a>'. "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $comments . $thestatus . "\n" . EMAIL_TEXT_FOOTER;

					tep_mail($check_status['customers_name'], $check_status['customers_email_address'], sprintf(EMAIL_TEXT_SUBJECT_UPDATE, $oID), $email, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS);

					// # Send a copy to the store admin
					tep_mail($check_status['customers_name'], STORE_OWNER_EMAIL_ADDRESS, sprintf(EMAIL_TEXT_SUBJECT_ADMIN, $oID), $email, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS); 
		
					// # Update order status history table with current status
					$comments = strip_tags(tep_db_input(trim(str_replace(array("Comments:","\n\n"), "", $comments))));
				}

				// # get current admin username
				$admin_user = (!empty($_COOKIE['admin_user']) ? $_COOKIE['admin_user'] : '');

				tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " 
							  SET orders_id = '" . (int)$oID . "',
							  orders_status_id = '" . (int)$status . "',
							  date_added = NOW(), 
							  customer_notified = '".$customer_notified."', 
							  comments = '" .$comments. "',
							  admin_user = '".$admin_user."'
							");

		// # 1.3 UPDATE PRODUCTS #####
		
		$RunningSubTotal = 0;
		$RunningTax = 0;

	    // # Do pre-check for subtotal field existence
		$ot_subtotal_found = false;
;
		foreach($update_totals as $total_details) {
			extract($total_details,EXTR_PREFIX_ALL,"ot");
		
			if($ot_class == "ot_subtotal") {
				$ot_subtotal_found = true;
    			break;
			}
		}


		if (isset($_POST['order_products_data']) && $_POST['order_products_data'] != '') {

		  $update_list = array();

		  foreach(explode("\n",$_POST['order_products_data']) AS $prod_data) {

		    if (preg_match('/^(.+?):(.*?)\r?$/',$prod_data,$prod_data_ar)) {

		      $update_ptr = &$update_list;

		      foreach (explode(".",$prod_data_ar[1]) AS $update_idx) {

		        if (!isset($update_ptr[$update_idx])) $update_ptr[$update_idx]=NULL;

				$update_ptr=&$update_ptr[$update_idx];
		      }

		      $update_ptr=$prod_data_ar[2];
		    }
		  }

		  foreach ($update_list AS $update_prod) {


			// # update tables for multi-warehousing. - restock source warehouse.
			if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

				// # now update the warehousing table (if multi-warehousing module active).

				$old_qty_query = tep_db_query("SELECT products_quantity FROM ". TABLE_ORDERS_PRODUCTS ." WHERE orders_products_id = '".$update_prod["orders_products_id"]."'");
				$old_qty = (tep_db_num_rows($old_qty_query) > 0 ? tep_db_result($old_qty_query,0) : 0);

				$warehouse_id = ($update_prod["warehouse_id"] > 0 ? $update_prod["warehouse_id"] : 1);

				//if($order->info['orders_status'] != 3) { // # do not update inventory if already shipped!

					if($update_prod["qty"] != $old_qty && $update_prod["qty"] > 0) {

						$new_qty = ((int)$update_prod["qty"] - $old_qty);
	
						tep_db_query("UPDATE ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." 
									  SET products_quantity = (products_quantity - ". $new_qty .") 
									  WHERE products_warehouse_id = '". $warehouse_id ."' 
									  AND products_id = '".(int)$update_prod['id']."'
									");

					} else if($update_prod["qty"] != $old_qty && $update_prod["qty"] == 0) {
		
						tep_db_query("UPDATE ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." 
									  SET products_quantity = (products_quantity + ".$old_qty .") 
									  WHERE products_warehouse_id = '". $warehouse_id ."' 
									  AND products_id = '".(int)$update_prod["id"]."'
									 ");
					}
				//}
				
			}

		    $RunningSubTotal += $update_prod["qty"] * $update_prod["final_price"];
		    $RunningTax += (($update_prod["tax"]/100) * ($update_prod["qty"] * $update_prod["final_price"]));
			
		    
			$update_fields = array();

		    $update_cond = (isset($update_prod['orders_products_id']) && $update_prod['orders_products_id']) ? "orders_products_id='".$update_prod['orders_products_id']."' AND orders_id='$oID'":'';

		    if ($update_prod['qty'] < 1) {

		      if ($update_cond) {
		        tep_db_query("DELETE FROM ".TABLE_ORDERS_PRODUCTS." WHERE $update_cond");
		        tep_db_query("DELETE FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES." WHERE $update_cond");
		      }

		    } else {

				foreach(array(id => 'products_id',
							  name => 'products_name',
							  model => 'products_model',
							  price => 'products_price', 
							  cost_pric => 'cost_price',
							  final_price => 'final_price',
							  tax => 'products_tax',
							  free_shipping => 'free_shipping',
							  separate_shipping => 'separate_shipping',
							  weight => 'products_weight',
							  qty => 'products_quantity',
							  warehouse_id => 'warehouse_id'
							 ) AS $post_key => $db_key)
	
				if(isset($update_prod[$post_key])) {
					$update_fields[$db_key] = $update_prod[$post_key];
				}
				
				if ($update_cond) {

		 	       tep_db_perform(TABLE_ORDERS_PRODUCTS,$update_fields,'update',$update_cond);

				} else {

			        $update_fields['orders_id'] = $oID;

			        tep_db_perform(TABLE_ORDERS_PRODUCTS,$update_fields);

					$update_prod['orders_products_id'] = tep_db_insert_id();
		
					if(MULTI_WAREHOUSE_ACTIVE == 'true' && $warehouse_id > 0) $update_prod['warehouse_id'] = $warehouse_id;
				}


				if (isset($update_prod['attr'])) {

					foreach ($update_prod['attr'] AS $attr) {
	
						$update_attr_fields = array(products_options => $attr['option'],
													products_options_values => $attr['value'],
													options_values_price => abs($attr['price']),
													price_prefix => ($attr['price']<0?'-':'+'));
	
						if (isset($attr['orders_products_attributes_id']) && $attr['orders_products_attributes_id']) {
	
							tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES,$update_attr_fields,'update',"orders_products_attributes_id = '".$attr['orders_products_attributes_id']."'");
	
						} else {
	
							$update_attr_fields['orders_id'] = $oID;
							$update_attr_fields['orders_products_id'] = $update_prod['orders_products_id'];
							tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $update_attr_fields);
						}
					}
				}


		    }
		  }
		}

	// # 1.4. UPDATE SHIPPING, DISCOUNT & CUSTOM TAXES #####

		// # Update Coupon Redeem Track		
		if (isset($_POST['coupon_id']) && $_POST['coupon_id']!='') {
		  tep_db_query("INSERT INTO ".TABLE_COUPON_REDEEM_TRACK." (coupon_id,customer_id,redeem_date,order_id) SELECT '".tep_db_prepare_input($_POST['coupon_id'])."',customers_id,NOW(),orders_id FROM ".TABLE_ORDERS." WHERE orders_id='$oID' AND customers_id!=0");
		}
		
		foreach($update_totals as $total_index => $total_details)	{
			extract($total_details,EXTR_PREFIX_ALL,"ot");
			

		// # Here is the major caveat: the product is priced in default currency, while shipping etc. are priced in target currency. We need to convert target currency
		// # into default currency before calculating RunningTax (it will be converted back before display)
			if ($ot_class == "ot_shipping" || $ot_class == "ot_customer_discount" || $ot_class == "ot_custom" || $ot_class == "ot_cod_fee") {
				$order = new order($oID);
				$RunningTax += $ot_value * $products_details['tax'] / (!empty($order->info['currency_value']) ? $order->info['currency_value'] : '1.0000') / 100 ;
			}
		}
		
		// # 1.5 UPDATE TOTALS #####
		
		$RunningTotal = 0;
		$sort_order = 0;
			
			// # 1.5.1 Do pre-check for Tax field existence
			$ot_tax_found = 0;
			foreach($update_totals as $total_details)	{
				extract($total_details,EXTR_PREFIX_ALL,"ot");
				if($ot_class == "ot_tax") {
					$ot_tax_found = 1;
					break;
				}
			}
			
			// # 1.5.2. Summing up total
			foreach($update_totals as $total_index => $total_details) {
			
			 	 // # 1.5.2.1 Prepare Tax Insertion			
				extract($total_details,EXTR_PREFIX_ALL,"ot");
			
				// # add tax if none found
				if (trim(strtolower($ot_title)) == "iva" || trim(strtolower($ot_title)) == "iva:") {
						if ($ot_class != "ot_tax" && $ot_tax_found == 0) {
							// Inserting Tax
							$ot_class = "ot_tax";
							$ot_value = "x"; // This gets updated in the next step
							$ot_tax_found = 1;
						}
				}
				
				// # 1.5.2.2 Update ot_subtotal, ot_tax, and ot_total classes
				if (trim($ot_title) && trim($ot_value)!='') {
				
					$sort_order++;

					if ($ot_class == "ot_subtotal") {

						//$ot_value = $RunningSubTotal;
						$ot_value = $total_details['value'];

					}

					if ($ot_class == "ot_tax") {
						$ot_value = $total_details['value'];
						// print "ot_value = $ot_value<br>\n";
					}

				    // # Check for existence of subtotals (CWS)                      
					if ($ot_class == "ot_total") {

					// # Correction tax calculation
						//$ot_value = $RunningTotal-$RunningTax;
				        //$ot_value = $RunningTotal;
						$ot_value = $total_details['value'];
				         		          
						//if(!$ot_subtotal_found) { 						
							// # There was no subtotal on this order, lets add the running subtotal in.
				            //$ot_value +=  $RunningSubTotal;
						//}
					}
									
					$order = new order($oID);
					$ot_text = $currencies->format($ot_value, true, $order->info['currency'], $order->info['currency_value']);
						
					if ($ot_class == "ot_total") {
						$ot_text = "<b>" . $ot_text . "</b>";
					}

					if($ot_total_id > 0) { // # Already in database --> Update

						tep_db_query("UPDATE " . TABLE_ORDERS_TOTAL . " 
									  SET title = '" . $ot_title . "',
									  text = '" . $ot_text . "',
									  value = '" . $ot_value . "',
									  sort_order = '" . $sort_order . "'
									  WHERE orders_total_id = '" . $ot_total_id . "'
									");

					} else { // # New Insert

						tep_db_query("INSERT INTO " . TABLE_ORDERS_TOTAL . " 
									  SET orders_id = '" . $oID . "',
									  title = '" . $ot_title . "',
									  text = '" . $ot_text . "',
									  value = '" . ($ot_value > 0 ? $ot_value : '0.00') . "',
									  class = '" . $ot_class . "',
									  sort_order = '" . $sort_order . "'
						  			");
					}
					
					if ($ot_class == "ot_shipping" || $ot_class == "ot_lev_discount" || $ot_class == "ot_customer_discount" || $ot_class == "ot_custom" || $ot_class == "ot_cod_fee") {
						// # Again, because products are calculated in terms of default currency, we need to align shipping, custom etc. values with default currency
						$RunningTotal += $ot_value / (!empty($order->info['currency_value']) ? $order->info['currency_value'] : '1.000000');
					} elseif($ot_class!='ot_total') {
						$RunningTotal += $ot_value;
					}
				

				} elseif(($ot_total_id > 0) && ($ot_class != "ot_shipping")) { // # Delete Total Piece
				
					tep_db_query("DELETE FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_total_id = '". $ot_total_id ."'");
				}

			}

			$order->info['total'] = $RunningTotal;

			$order_ok = $order->setStatus($status, $process);
			
			if ($order_ok && $order->info['payment_method']) {

			  $cfields = array();

			  foreach(array('payment_method','cc_type','cc_owner','cc_number','cc_expires') AS $cfld) {
				$cfields[$cfld] = $order->info[$cfld];
			  }

			  tep_db_perform('orders',$cfields,'update',"orders_id='$oID' AND (payment_method='' OR payment_method IS NULL)");


				// # manual / cash payments werent being saved to payments table when order is Zero - force entry here.
				if($order->info['payment_method'] == 'payment_manual') { 
		
					$sql_data_array = array('orders_id' => $oID,
    	    			                    'method' => 'payment_manual',
        	    			                'status' => 'pending',
            	    			            'amount' => $order->info['total'], 
                	    			        'ref_payments_id' => 'null', 
                    			    		'date_created' => 'now()', 
		                    		        'date_processed' => 'now()', 
        		            		        'ref_id' => 'null', 
                		        		    'extra_info' => 'null');

					tep_db_perform(TABLE_PAYMENTS, $sql_data_array);
				}

			}
			
			
			if ($order->error) {
				foreach ($order->error AS $msg) {
					$messageStack->add_session($msg, 'error');
					$messageStack->add($msg, 'error');
				}
			} elseif($order->message) {
				foreach ($order->message AS $msg) {
					$messageStack->add_session($msg, 'success');
					$messageStack->add($msg, 'success');
				}
    		}
			
			if (!$order_ok) {
				$messageStack->add_session('Payment Processing Failed', 'error');
				$messageStack->add('Payment Processing Failed', 'error');
			}
		

		// # 1.6 SUCCESS MESSAGE #####
		
		if ($order_updated && $order_ok)	{
			$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
		}


		// # Send Confirmation		
		if (isset($_POST['send_confirmation'])) {

			$order = new order($oID);

			$products_ordered='';
			$products_ordered_html='';
 			
			$attributes_exist = '0';
			$products_ordered_attributes = '';

			foreach ($order->products AS $product) {	

			    //$products_ordered.=sprintf("%2d x %s\n",$product['qty'],$product['name']);

			    if(isset($product['attributes'])) {
					$attributes_exist = '1';
					for ($j=0, $n2=sizeof($product['attributes']); $j<$n2; $j++) {
						$products_ordered_attributes .= "\n\t" . $product['attributes'][$j]['option'] . ' ' . $product['attributes'][$j]['value'];
    	      		}
				}

		        $products_ordered .= $product['qty'] . ' x ' . $product['name'] . ' (' . $product['model'] . ') = ' . $currencies->display_price($product['final_price'], $product['tax'], $product['qty']) . $products_ordered_attributes . "\n";

				$products_ordered_html .= '<table width="100%"><tr><td align="center" style="width:40px; font:normal 12px arial; text-align:center;">x'.$product['qty'].'</td><td align="left" style="width:235px; padding-left:10px; font:normal 12px arial;">'.$product['name'].'</td><td style="width:68px; font:normal 12px arial;">'.$product['model'].'</td><td align="left" style="width:63px; font:normal 12px arial;">'.$products_ordered_attributes.'</td><td align="left" style="width:69px; font:normal 12px arial;">' . $currencies->display_price($product['final_price'], $product['tax'], $product['qty']) .'</td></tr></table><br>'."\n";
				
			}

			// # Order Total label
			$ot_title_wd = 0;
			$ot_text_wd = 0;
			$ot_label_text = array();
			$ot_label_html = array();
			$order_totals = array();

			$ot_query = tep_db_query("SELECT * FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id='$oID' ORDER BY sort_order");
	
			while ($order_totals_row = tep_db_fetch_array($ot_query)) {
				$order_totals[] = $order_totals_row;
			}

			foreach ($order_totals AS $idx => $ot) {
		    	$ot_txt = array();
			    $ot_title_wd = max($ot_title_wd,strlen($order_totals[$idx]['strip_title']=preg_replace('/<.*?>/','',$ot['title'])));
			    $ot_text_wd = max($ot_text_wd,strlen($order_totals[$idx]['strip_text']=preg_replace('/<.*?>/','',$ot['text'])));
			}
		
			foreach ($order_totals AS $ot) {
			    $ot_label_text[] = str_repeat(' ',$ot_title_wd+2-strlen($ot['strip_title'])).$ot['strip_title'].str_repeat(' ',$ot_text_wd+2-strlen($ot['strip_text'])).$ot['strip_text'];
			    $ot_label_html[] = '<tr><td style="font-size: 12px; padding:0 10px 0 0;">'.$ot['title'].'</td><td style="font-size: 12px">'.$ot['text'].'</td></tr>';
			}

			$ship_info = array();
			$ship_info['usps'] = array('name' => 'USPS', 'track_url' => 'http://www.usps.com/shipping/trackandconfirm.htm', 'track_name' => 'Delivery Confirmation Number');
			$ship_info['ups'] = array('name' => 'UPS', 'track_url' => 'http://www.ups.com/WebTracking/track?loc=en_US', 'track_name' => 'Tracking Label Number');
			$ship_info['fedex1'] = array('name' => 'FedEx', 'track_url' => 'http://www.fedex.com/Tracking', 'track_name' => 'Tracking Number');
			$ship_info['dhlairborne'] = array('name' => 'DHL', 'track_url' => 'http://www.dhl-usa.com/TrackByNbr.asp', 'track_name' => 'Tracking Number');
			$ship_info['dhl'] = $ship_info['dhlairborne'];
			$ship_method=preg_replace('/_.*/','',$order->info['shipping_method']);

			if(!isset($ship_info[$ship_method])) $ship_method=MODULE_SHIPPING_AIRBORNE_STATUS=='True'?'dhl':(MODULE_SHIPPING_USPS_STATUS=='True'?'usps':'ups');
	         
			$tpl = array();
			$tpl['config'] = array(store_name => STORE_NAME,
								   store_owner_email_address => STORE_OWNER_EMAIL_ADDRESS,
								   http_server => HTTP_CATALOG_SERVER,
						  		  );
	
			$tpl['link']= array(account_history => tep_catalog_href_link('account_history_info.php', 'order_id=' . $oID, 'SSL', false),
							    account => tep_catalog_href_link('account.php'),
							    tell_a_friend => tep_catalog_href_link('tell_a_friend.php'),
								);

			$tpl['order_id'] = $oID;
			$tpl['info'] = $order->info;
			$tpl['customer'] = $order->customer;

			$tpl['customer']['password'] = '********';

			list($tpl['customer']['firstname'],$tpl['customer']['lastname']) = explode(' ',$tpl['customer']['name']);

			$tpl['date'] = strftime(DATE_FORMAT_LONG);

			$tpl['address'] = array(shipping => array(text => tep_address_format($order->delivery['format_id'], $order->delivery,0,'',"\n"), 
													  html => tep_address_format($order->delivery['format_id'],$order->delivery,1,'',"\n")),
									billing => array(text => tep_address_format($order->billing['format_id'],$order->billing,0,'',"\n"), 
													 html => tep_address_format($order->billing['format_id'],$order->billing,1,'',"\n")),
									);

			$tpl['products_ordered'] = array('text'=>$products_ordered,'html'=>$products_ordered_html);

			$tpl['payment'] = array(title => $order->info['payment_method'],
					    			subtotal => '$' . number_format($order->info['subtotal'], 2),
								    total => '$' . number_format($order->info['total'], 2),
								    order_total_label => array(text => join("\n",$ot_label_text), html => "<table width=100%>\n".join("\n",$ot_label_html)."</table>"),
		    						cc_type => (isset($order->info['cc_type'])?$order->info['cc_type']:''),
									cc_number => (isset($order->info['cc_number']) ? str_repeat('*',max(strlen($order->info['cc_number'])-4,0)).substr($order->info['cc_number'],-4,4) : ''),
								  );

			$tpl['ship_info'] = $ship_info[$ship_method];
			$tpl['ship_info']['service_eta'] = 3;
			$tpl['ship_info']['method'] = preg_replace('/^.*?_/','',$order->info['shipping_method']);
			$tpl['comments'] = (!empty($order->info['comments']) ? $order->info['comments'] : '');

			if(!empty($tpl['order_id'])) { 
				require_once(DIR_WS_FUNCTIONS . 'email_now.php');
				$extra_emails = (SEND_EXTRA_ORDER_EMAILS_TO !='') ? explode(',',SEND_EXTRA_ORDER_EMAILS_TO) : NULL;
				email_now('checkout_confirm',$tpl,$extra_emails);
			} else {
				tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, 'Order email attempted with no order data from IP '.$_SERVER['REMOTE_ADDR'], STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
			}

			$messageStack->add_session('An email order confirmation has been sent.', 'success');
			$messageStack->add('An email order confirmation has been sent.', 'success');

		} else { // # POST['send_confirmation'] is not set

			$messageStack->add_session('You have opted not to send an order confirmation email.', 'warning');
			$messageStack->add('You have opted not to send an order confirmation email.', 'warning');
		}

		
		// # Update Message Stack
		if ($order_updated == true) {
			$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
			$messageStack->add(SUCCESS_ORDER_UPDATED, 'success');

		} elseif($customer_notified == 1) {
			$messageStack->add_session('Customer has been notified of order status update', 'success');
			$messageStack->add('Customer has been notified of order status update', 'success');
	
		} else {
			$messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
			$messageStack->add(WARNING_ORDER_NOT_UPDATED, 'warning');
		}

		if(isset($_POST['update_admin_comments']) && !empty($_POST['admin_comments'])) { 

			$admin_comments = tep_db_prepare_input($_POST['admin_comments']);

			// # get current admin username
			$admin_user = (!empty($_COOKIE['admin_user']) ? $_COOKIE['admin_user'] : '');

			tep_db_query("INSERT INTO admin_comments
						  SET orders_id = '" . $oID . "', 
						  date_added = NOW(), 
						  comments = '" . tep_db_prepare_input($admin_comments)  . "',
						  admin_user = '".$admin_user."'
						 ");

			$order_updated = true;
      
			$messageStack->add_session('Admin Comments Updated', 'success');
			$messageStack->add('Admin Comments Updated', 'success');		
		}

		// # Redirect that sends back to order list - nullifies message stack messages when redirect
		//tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=edit'));
		//tep_redirect(tep_href_link(FILENAME_EDIT_ORDERS, tep_get_all_get_params(array('action','oID')) . 'oID='.$oID.'&action='.$sub_action));
		
	break;

  
	// # 2. ADD A PRODUCT ###############################################################################################
	case 'add_product':
	
		if($step == 5) {

			// 2.1 GET ORDER INFO #####
			
			$oID = tep_db_prepare_input($_GET['oID']);
			$order = new order($oID);

			$AddedOptionsPrice = 0;

			// 2.1.1 Get Product Attribute Info

			if(isset($add_product_options))	{

				foreach($add_product_options as $option_id => $option_value_id)	{

					$result = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON po.products_options_id=pa.options_id LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON pov.products_options_values_id=pa.options_values_id WHERE products_id='$add_product_products_id' and options_id=$option_id and options_values_id=$option_value_id and po.language_id = '" . (int)$languages_id . "' and pov.language_id = '" . (int)$languages_id . "'");
					$row = tep_db_fetch_array($result);
					extract($row, EXTR_PREFIX_ALL, "opt");
					$AddedOptionsPrice += $opt_options_values_price;
					$option_value_details[$option_id][$option_value_id] = array ("options_values_price" => $opt_options_values_price);
					$option_names[$option_id] = $opt_products_options_name;
					$option_values_names[$option_value_id] = $opt_products_options_values_name;
				}
			}

			// # 2.1.2 Get Product Info


			$customers_id = $order->customer['customers_id'];

			$customers_group = tep_db_result("SELECT customers_group_id FROM ". TABLE_CUSTOMERS ." WHERE customers_id = '". $customers_id ."'",0);

			$result = tep_db_query("SELECT p.products_model, 
										   p.products_price, 
										   pd.products_name, 
										   p.products_tax_class_id,
										   p.products_price_myself,
										   spg.suppliers_group_price AS cost_price
						  			FROM " . TABLE_PRODUCTS . " p 
									LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id 
									LEFT JOIN suppliers_products_groups spg ON spg.products_id = p.products_id
									WHERE p.products_id = '".$add_product_products_id."' 
									AND pd.language_id = '" . (int)$languages_id . "'
									AND spg.priority = 0
								   ");

			$row = tep_db_fetch_array($result);
			extract($row, EXTR_PREFIX_ALL, "p");

			$rs_offer = tep_db_query("SELECT * FROM ". TABLE_SPECIALS . " WHERE products_id = '". $add_product_products_id."'");
			$offer = tep_db_fetch_array($rs_offer);
			
			
			$p_products_price = $row['products_price'];

			if ($offer) {
				$p_products_price = $offer['specials_new_products_price'];
			}
			
			// # Following functions are defined at the bottom of this file
			$CountryID = tep_get_country_id($order->delivery["country"]);
			$ZoneID = tep_get_zone_id($CountryID, $order->delivery["state"]);
			
			$products_tax = tep_get_tax_rate($p_products_tax_class_id, $CountryID, $ZoneID);
			
			// # 2.2 UPDATE ORDER #####
			
			// # Retrieve current-day product costing from the products table and add to orders products.
			// # important to keep historical pricing / costs for inventory since this can fluctuate with time.

			// # if no cost found in suppliers_products_groups, try the products table for old format

			// # costing from suppliers_products_groups table
			$cost_price = ($row['cost_price'] > 0 ? $row['cost_price'] : 0);

			// # costing from products table
			$cost_old = ($row['products_price_myself'] > 0 ? $row['products_price_myself'] : 0);

			// # if supplier cost is empty, use old format
			$cost = (!empty($cost_price) ? $cost_price : $cost_old);

			tep_db_query("INSERT INTO ". TABLE_ORDERS_PRODUCTS ." 
						 SET orders_id = ". $oID .",
						 products_id = ". $add_product_products_id .",
						 products_model = '".$p_products_model."',
						 products_name = '" . str_replace("'", "&#39;", $p_products_name) . "',
						 products_price = '".$p_products_price."',
            		     cost_price = '". (float)$cost ."',
						 final_price = '" . ($p_products_price + $AddedOptionsPrice) . "',
						 products_tax = '" . $products_tax ."',
						 products_quantity = '".$add_product_quantity."'
						");

			$new_product_id = tep_db_insert_id();
			
			// # 2.2.1 Update inventory Quantity

			//tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity - " . $add_product_quantity . ", products_ordered = products_ordered + " . $add_product_quantity . " where products_id = '" . $add_product_products_id . "'");

			if (isset($add_product_options)) {

				foreach($add_product_options as $option_id => $option_value_id) {

					tep_db_query("INSERT INTO " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " 
								  SET orders_id = $oID,
								  orders_products_id = $new_product_id,
								  products_options = '" . $option_names[$option_id] . "',
								  products_options_values = '" . tep_db_input($option_values_names[$option_value_id]) . "',
								  options_values_price = '" . $option_value_details[$option_id][$option_value_id]["options_values_price"] . "',
								  price_prefix = '+'
								  ");
				}
			}
			
			// # 2.2.2 Calculate Tax and Sub-Totals
			$order = new order($oID);
			$RunningSubTotal = 0;
			$RunningTax = 0;

			for ($i=0; $i<sizeof($order->products); $i++) {

				// # Correction of $RunningSubTotal - our SubTotal in our shop is WITH TAX (Michel Haase, 2005-02-18)
				// # -> in line 240 (or near) there was the choice WITH/WITHOUT tax; if WITH tax, then you have to
				// # calculate it here also (or ?) !!!
			  $RunningSubTotal += ($order->products[$i]['qty'] * $order->products[$i]['final_price']);
			  //$RunningSubTotal += (tep_add_tax(($order->products[$i]['qty'] * $order->products[$i]['final_price']), $order->products[$i]['tax'])*20)/20;

			  $RunningTax += (($order->products[$i]['tax'] / 100) * ($order->products[$i]['qty'] * $order->products[$i]['final_price']));			
			}
			
			// 2.2.2.1 Tax
			// # Correction of number_format - German format (Michel Haase, 2005-02-18)
			$Query = "update " . TABLE_ORDERS_TOTAL . " set
				text = '" . number_format($RunningTax, 2, ',', '') . ' ' . $order->info['currency'] . "', 
				value = '" . $RunningTax . "'
				where class='ot_tax' and orders_id=$oID";
			tep_db_query($Query);

			// 2.2.2.2 Sub-Total
			// # Correction of number_format - German format (Michel Haase, 2005-02-18)
			$Query = "update " . TABLE_ORDERS_TOTAL . " set
				text = '" . number_format($RunningSubTotal + $RunningTax, 2, ',', '') . ' ' . $order->info['currency'] . "',
				value = '" . $RunningSubTotal . "'
				where class='ot_subtotal' and orders_id=$oID";
			tep_db_query($Query);

			// 2.2.2.3 Total
			// # Correction of number_format - German format (Michel Haase, 2005-02-18)
			$Query = "select sum(value) as total_value from " . TABLE_ORDERS_TOTAL . " where class != 'ot_total' and orders_id=$oID";
			$result = tep_db_query($Query);
			$row = tep_db_fetch_array($result);
			$Total = $row["total_value"];

			$Query = "update " . TABLE_ORDERS_TOTAL . " set
				text = '<b>" . number_format($Total, 2, ',', '') . ' ' . $order->info['currency'] . "</b>',
				value = '" . $Total . "'
				where class='ot_total' and orders_id=$oID";
			tep_db_query($Query);

			// 2.3 REDIRECTION #####
			
			tep_redirect(tep_href_link("edit_orders.php", tep_get_all_get_params(array('action')) . 'action=edit'));

	}
	
break;



case 'add_non_inv':
	
		if($step == 2) {

			$oID = tep_db_prepare_input($_GET['oID']);
			$order = new order($oID);

			$row = array('products_model' => $_POST['products_model'],
						 'products_price' => $_POST['products_price'],
						 'products_name' => $_POST['products_name'],
						 'products_tax_class_id' => $_POST['products_tax_class_id']
						);


			extract($row, EXTR_PREFIX_ALL, "p");
			
			// Following functions are defined at the bottom of this file
			$CountryID = tep_get_country_id($order->delivery["country"]);
			$ZoneID = tep_get_zone_id($CountryID, $order->delivery["state"]);
			
			$ProductsTax = tep_get_tax_rate($p_products_tax_class_id, $CountryID, $ZoneID);
			
			// 2.2 # UPDATE ORDER #####	

			$Query = "INSERT INTO ".TABLE_ORDERS_PRODUCTS." 
				SET	orders_id = $oID,
				products_id = '',
				products_model = '" . $p_products_model . "',
				products_name = '" . str_replace("'", "&#39;", $p_products_name) . "',
				products_price = '" . $p_products_price . "',
				final_price = '" . ($p_products_price) . "',
				products_tax = '" . $ProductsTax . "',
				products_quantity = '" . $_POST['products_quantity'] . "'";
			tep_db_query($Query);
			$new_product_id = tep_db_insert_id();
						
			// 2.2.2 Calculate Tax and Sub-Totals
			$order = new order($oID);
			$RunningSubTotal = 0;
			$RunningTax = 0;

			for ($i=0; $i<sizeof($order->products); $i++) {

			// # Correction of $RunningSubTotal - our SubTotal in our shop is WITH TAX
			// # in line 240 (or near) there was the choice WITH/WITHOUT tax; if WITH tax, then you have to
			// # calculate it here also (or ?) !!!
			  $RunningSubTotal += ($order->products[$i]['qty'] * $order->products[$i]['final_price']);
			  //$RunningSubTotal += (tep_add_tax(($order->products[$i]['qty'] * $order->products[$i]['final_price']), $order->products[$i]['tax'])*20)/20;

			  $RunningTax += (($order->products[$i]['tax'] / 100) * ($order->products[$i]['qty'] * $order->products[$i]['final_price']));			
			}
			
			// 2.2.2.1 Tax
// Correction of number_format - German format (Michel Haase, 2005-02-18)
			$Query = "update " . TABLE_ORDERS_TOTAL . " set
				text = '" . number_format($RunningTax, 2, ',', '') . ' ' . $order->info['currency'] . "', 
				value = '" . $RunningTax . "'
				where class='ot_tax' and orders_id=$oID";
			tep_db_query($Query);

			// 2.2.2.2 Sub-Total
// Correction of number_format - German format (Michel Haase, 2005-02-18)
			$Query = "update " . TABLE_ORDERS_TOTAL . " set
				text = '" . number_format($RunningSubTotal + $RunningTax, 2, ',', '') . ' ' . $order->info['currency'] . "',
				value = '" . $RunningSubTotal . "'
				where class='ot_subtotal' and orders_id=$oID";
			tep_db_query($Query);

			// 2.2.2.3 Total
// Correction of number_format - German format (Michel Haase, 2005-02-18)
			$Query = "select sum(value) as total_value from " . TABLE_ORDERS_TOTAL . " where class != 'ot_total' and orders_id=$oID";
			$result = tep_db_query($Query);
			$row = tep_db_fetch_array($result);
			$Total = $row["total_value"];

			$Query = "update " . TABLE_ORDERS_TOTAL . " set
				text = '<b>" . number_format($Total, 2, ',', '') . ' ' . $order->info['currency'] . "</b>',
				value = '" . $Total . "'
				where class='ot_total' and orders_id=$oID";
			tep_db_query($Query);

			// 2.3 REDIRECTION #####
			
			tep_redirect(tep_href_link("edit_orders.php", tep_get_all_get_params(array('action')) . 'action=edit'));

		}
	
	  break;
  }

}

if ($action == 'edit') {

    $order_exists = false;

	if(isset($_GET['oID'])) { 
		$oID = (int)$_GET['oID'];
	} else {
		tep_redirect('create_order.php');
	}

    $current_shipping_method = NULL;

    if($oID > 0) {

      $orders_query = tep_db_query("SELECT orders_id, customers_id, shipping_method 
									FROM " . TABLE_ORDERS . " 
									WHERE orders_id = '" . $oID . "'
								  ");

      if (tep_db_num_rows($orders_query) > 0) {

	    $order_exists = true;
        $orders_result = tep_db_fetch_array($orders_query);
        $cID = $orders_result['customers_id'];
		$current_shipping_method = $orders_result['shipping_method'];

      } else {

        $order_exists = false;
        $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');

      }

	} else {

		$cID = $_POST['customers_id'];

		if(empty($cID)) {

			if(preg_match('/(.+)\@(.+)/',$_POST['email_address'])) {

				$cID = tep_db_read("SELECT customers_id 
									FROM customers 
									WHERE customers_email_address = '".$_POST['email_address']."'
								   ",NULL,'customers_id');
	
				if($cID) $messageStack->add("Using existing customer account", 'warning');
				
	
			} else {

				$cntry_code = tep_db_read("SELECT countries_id 
										   FROM countries 
										   WHERE (countries_iso_code_2 = '".$_POST['country']."' 
											OR countries_name = '".$_POST['country']."')
									  		",NULL,'countries_id');

				if(!$cntry_code) $cntry_code = 223;

				$state_info = tep_db_read("SELECT zone_id, zone_name 
										   FROM zones 
										   WHERE (zone_code = '".$_POST['state']."' OR zone_name='".$_POST['state']."')
										  ");

				if(!$state_info) {
					$state_info = array('zone_name' => $_POST['state']);
				}

				if(!$cntry_code) $cntry_code=223;
	
				$cus_flds = array('customers_email_address'=>$_POST['email_address'],
							      'customers_firstname'=>$_POST['firstname'],
							      'customers_lastname'=>$_POST['lastname'],
							      'customers_telephone'=>$_POST['telephone'],
							      'customers_fax'=>$_POST['fax'],
							    );

				$addr_flds = array('entry_firstname'=>$_POST['firstname'],
							       'entry_lastname'=>$_POST['lastname'],
								   'entry_company'=>$_POST['company'],
								   'entry_street_address'=>$_POST['street_address'],
								   'entry_city'=>$_POST['city'],
								   'entry_suburb'=>$_POST['suburb'],
								   'entry_postcode'=>$_POST['postcode'],
								   'entry_state'=>$state_info['zone_name'],
								   'entry_country_id'=>$cntry_code,
								   'entry_zone_id'=>$state_info['zone_id'],
								   );

			    tep_db_perform('address_book',$addr_flds);
	
			    $cus_flds['customers_default_address_id'] = tep_db_insert_id();

			    tep_db_perform('customers',$cus_flds);

			    $cID = tep_db_insert_id();

			    if($cID) $messageStack->add("Customer account created: ".$cID, 'warning');
			}
		}
	}
}

// $payment_pull_down = array();

//  foreach($payment_modules->selection() AS $payment_sel) {
//    $payment_pull_down[]=Array(id=>$payment_sel['id'],text=>$payment_sel['module']);
//  }
//  $cards_pull_down=Array();
//  foreach (Array ('Visa','MasterCard','AmEx','Discover') AS $card_type) $cards_pull_down[]=Array(id=>$card_type,text=>$card_type);
  

  $countries_pull_down=Array();
  $countries_query=tep_db_query("SELECT countries_name FROM countries ORDER BY countries_id");
  while ($countries_row=tep_db_fetch_array($countries_query)) {
    $countries_pull_down[]=Array(id=>$countries_row['countries_name'],text=>$countries_row['countries_name']);
  }
  
  $currencies_pull_down=Array();
  foreach ($currencies->currencies AS $cur=>$cur_data) {
    $currencies_pull_down[]=Array(id=>$cur,text=>$cur);
  }
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Edit Order</title>
<link rel="stylesheet" href="js/css.css" type="text/css">
<link rel="stylesheet" href="includes/stylesheet.css" type="text/css">

<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>


<script type="text/javascript" src="js/tabber.js"></script>
<link rel="stylesheet" href="js/tabber.css" type="text/css" media="screen">
<script type="text/javascript">
function contentChanged() {
  top.resizeIframe('myframe');
}
</script>


<style type="text/css">

select {
font-size:11px;
}

input {
font-size:11px;
}


.Subtitle {
  font-family: Verdana, Arial, Helvetica, sans-serif;
  font-size: 11px;
  font-weight: bold;
  color: #FF6600;
}

.commentsTable {
border-collapse:collapse;
margin: 10px 0;
}

.commentsTable th {
border:1px solid #CCC;
background-color: rgb(98, 149, 253);
color:white;
font:bold 11px arial;
}

.commentsTable td {
border:1px solid #CCC;
padding:10px;
}

#prodSearchDIV {
	background-color:#FFF;
	border:1px solid #6295FD;
	border-top:0;
	top:12px;
	left: 1px;
}

.dataRow:nth-child(even) {background: #F9F9F9}
.dataRow:nth-child(odd) {background: #FFF}


.dataRow:hover {
	background-color:#FFFFC6;
}

#products_box tr:nth-child(even) {background: #FFF}
#products_box tr:nth-child(odd) {background: #F0F1F1;}

</style>

</head>
<body style="background-color:transparent; margin:0; font:normal 11px arial;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<script type="text/javascript">

var allowCalc=1;

function setCalc(fl) {
	allowCalc=fl;
	reloadState('ship');
	reloadState('bill');
}

function reloadState(sec) {
	if (!allowCalc) {
		$(sec+'_state_box').innerHTML='<input type="text" size="25" name="'+sec+'_state_null" value="'+$(sec+'_state').value+'" onChange="setState(\''+sec+'\',this.value)">';
		return;
	}

	if($(sec+'_country').value=='' || $(sec+'_postcode').value=='') {
	    $(sec+'_state_box').innerHTML="Please enter postcode";
	} else {
    	$(sec+'_state_box').innerHTML="Loading...";
	    new ajax ('<?php echo HTTP_SERVER;?>/admin/state_dropdown.php?sec='+sec+'&d='+escape($(sec+'_state').value)+'&postal='+escape($(sec+'_postcode').value)+'&country='+escape($(sec+'_country').value), {method: 'get', update: $(sec+'_state_box')});
	}
}

function setState(sec,val) {
  $(sec+'_state').value=val;
  if (sec=='ship') reloadShipping();
}


function reloadShipping(flg) {
  if (!allowCalc) return;
  var rc_fld=$('recalculate_shipping');
  if (rc_fld && flg!=null) rc_fld.checked=flg;
<?php if ($current_shipping_method) { ?>
  if (rc_fld && !rc_fld.checked) {
    $('shipping_box').innerHTML="";
    if (flg!=null) {
<?php
    $totals_query = tep_db_query("select * from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$oID . "' AND class='ot_shipping'");
    $order->totals = array();
    while ($t = tep_db_fetch_array($totals_query)) {
?>
      setShipping('<?php echo $current_shipping_method?>','<?php echo addslashes($t['title'])?>',<?php echo $t['value']+0?>);
<?php
    }
?>
    }
    return;
  }
<?php } ?>
  if ($('ship_country').value=='' || $('ship_postcode').value=='') {
    $('shipping_box').innerHTML="Please select country and postcode";
  } else {
    $('shipping_box').innerHTML="Loading...";
    var ac_wt=0;
    var p_list=new Array();
    var subtotal=0;
    for (var i=0;orderProducts[i];i++) {
      subtotal+=Number(orderProducts[i].final_price)*Number(orderProducts[i].qty);
      if ((orderProducts[i].free_shipping==0) && (orderProducts[i].qty>0)) {
        if (orderProducts[i].separate_shipping>0) p_list[p_list.length]=((orderProducts[i].qty==1)?'':orderProducts[i].qty+'x')+orderProducts[i].weight;
	else {
	  if (ac_wt<0.1) ac_wt=0.1;
	  ac_wt+=orderProducts[i].qty*orderProducts[i].weight;
	}
      }
    }
    if (ac_wt>0) p_list.unshift(ac_wt);
    new ajax ('<?php echo HTTP_SERVER;?>/admin/shipping_options.php?weights='+p_list.join(',')+'&zip='+escape($('ship_postcode').value)+'&cnty='+escape($('ship_country').value)+'&state='+escape($('ship_state').value)+'&subtotal='+subtotal+'&d='+escape($('shipping_method').value), {method: 'get', update: $('shipping_box')});
  }
}

function setShipping(key,title,value) {
  if ($('shipping_method')) $('shipping_method').value=key;
  if (!allowCalc) return;
  setOrderTotal('ot_shipping',Number(value).toFixed(2),title);
}


function ReloadAddProduct(cat,pid) {
  var url='<?php echo HTTP_SERVER.DIR_WS_ADMIN?>includes/order_add_product.php?country='+escape($('ship_country').value)+'&state='+escape($('ship_state').value);
  if (cat) {
    url+='&add_category_id='+cat;
    if (pid) url+='&add_product_id='+pid;
  }
  new ajax(url, {method: 'get', update: $('add_product_box')});
}


function HTMLescape(s) {
  s+='';
  return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/>/g,'&gt;').replace(/</g,'&lt;');
}

var orderProducts=new Array();

function AddToOrder(prod,qty,attr) {
  if (qty>0) {
    var idx=orderProducts.length;
    orderProducts[idx]=prod;
    orderProducts[idx].qty=qty;
    if (!orderProducts[idx].tax) orderProducts[idx].tax=0;
    orderProducts[idx].attr=attr;
    orderProducts[idx].final_price=Number(prod.price);
//    for (var aidx=0;attr[aidx];aidx++) if (isNaN(attr[aidx].price)) alert(attr[aidx].option+':'+attr[aidx].value+':'+attr[aidx].price); else orderProducts[idx].final_price+=Number(attr[aidx].price);
    renderProducts();
  }
  ReloadAddProduct();
}


function currencyFormat(val) {
  return '$'+Number(val).toFixed(2);
}


function renderProducts(flg) {
  var subtotal=0.0;
  var tax=0.0;
  var html=
 '  <table border="0" width="100%" cellspacing="0" cellpadding="2">\n'
  for (var idx=0;orderProducts[idx];idx++) {
    if (orderProducts[idx].qty<=0) continue;
    html+='<tr class="dataTableRow">\n';
    html+='<td class="dataTableContent" width="50"><input type="text" name="order_products_qty_'+idx+'" value="'+HTMLescape(orderProducts[idx].qty)+'" size="3" onChange="orderProducts['+idx+'].qty=this.value; productsCleanup(1);" style="text-align:center"></td>\n';
    html+='<td class="dataTableContent" width="10">x</td>\n';
    html+='<td class="dataTableContent" width="300">'+HTMLescape(orderProducts[idx].name);
    if (orderProducts[idx].attr && orderProducts[idx].attr.length) {
      html+='\n<table>\n';
      for (var aidx=0;orderProducts[idx].attr[aidx];aidx++) {
        html+='<tr><td>-</td>\n';
        html+=' <td>'+HTMLescape(orderProducts[idx].attr[aidx].option)+'</td>\n';
        html+=' <td>'+HTMLescape(orderProducts[idx].attr[aidx].value)+'</td>\n';
        html+='</tr>\n';
      }
      html+='</table>\n';
    }
    html+='</td>\n';
    html+='<td class="dataTableContent" width="125">'+HTMLescape(orderProducts[idx].model)+'</td>\n';

    var cost = Number(orderProducts[idx].final_price) * orderProducts[idx].qty;

	// # if tax is present recalculate the tax and quantity into the total var.
	if(orderProducts[idx].tax > 0) {
		var total = ( cost + ((orderProducts[idx].tax * orderProducts[idx].final_price / 100) * orderProducts[idx].qty) );
	} else {
		total = cost;
	}
//console.log(cost);
    subtotal += cost;
    tax += orderProducts[idx].tax * cost / 100;

    html+='<td class="dataTableContent" width="75"><input type="text" name="order_products_final_price_'+idx+'" value="'+Number(orderProducts[idx].final_price).toFixed(2)+'" size="7" onChange="orderProducts['+idx+'].final_price=this.value; renderProducts();"></td>\n';
    //html+='<td class="dataTableContent" width="75">'+Number(orderProducts[idx].price).toFixed(2)+'</td>\n';
    html+='<td class="dataTableContent" width="60" align="center"><input type="text" name="order_products_tax_'+idx+'" value="'+Number(orderProducts[idx].tax).toFixed(2)+'" size="2" onChange="orderProducts['+idx+'].tax=this.value; renderProducts();"></td>\n';
	html+='<td class="dataTableContent" width="65">$'+total.toFixed(2)+'</td>\n';
    html+='<td class="dataTableContent" width="60" align="center"><input type="checkbox" name="order_products_free_shipping_'+idx+'" value="1"'+(orderProducts[idx].free_shipping>0?' checked':'')+' onChange="orderProducts['+idx+'].free_shipping=(this.checked?1:0); reloadShipping(true)"></td>\n';
    
    html+='</tr>\n';
  }
  html+='</table>';
  $('products_box').innerHTML=html;

  setOrderTotal('ot_subtotal',subtotal.toFixed(2));
  setOrderTotal('ot_tax',tax.toFixed(2));
  reloadShipping(flg==null?true:null);
}

function productsCleanup(rf) {
  var j=0;
  for (var i=0;orderProducts[i];i++) {
    if ((orderProducts[i].qty>0) || orderProducts[i].orders_products_id) orderProducts[j++]=orderProducts[i];
    else rf=1;
  }
  if (rf) {
    orderProducts.length=j;
    renderProducts();
  }
}

function implodeList(lst,pref) {
  var rs='';
  for (var idx in lst) {
    if (typeof(lst[idx])=='object') rs+=implodeList(lst[idx],pref+idx+'.');
    else if ((typeof(lst[idx])=='string') || (typeof(lst[idx])=='number')) rs+=pref+idx+':'+lst[idx]+'\n';
  }
  return rs;
}

function prepareProductsData() {
  $('order_products_data').value = implodeList(orderProducts,'');

<?php if ($oID) { ?>
  return true;
<?php } else { ?>
  return window.confirm('This action will create a new order\nPlease check if the order is correct.');
<?php } ?>
}

var orderTotalValues={};

function setOrderTotal(cl,val,title) {
  val=isNaN(val)?0:Number(val);
  orderTotalValues[cl]=val;
  if ($(cl+'_value')) $(cl+'_value').value=val.toFixed(2);
  if ($(cl+'_value_txt')) $(cl+'_value_txt').innerHTML=currencyFormat(val);
  if (title) {
    if ($(cl+'_title')) $(cl+'_title').value=title;
    if ($(cl+'_title_txt')) $(cl+'_title_txt').innerHTML=title;
  }
  if (cl!='ot_total') {
    var total=0.0;
    for (var c in orderTotalValues) {
      if (c!='ot_total') total+=Number(orderTotalValues[c]);
    }
    setOrderTotal('ot_total',total.toFixed(2));
  }
}


function reloadCoupon(code) {
  $('coupon_box').innerHTML="Loading...";
  new ajax ('<?php echo HTTP_SERVER;?>/admin/coupon_redeem_box.php?cID=<?php echo $cID?>&code='+escape(code), {method: 'get', update: $('coupon_box')});
}

function setCoupon(id, name, type, val) {
  $('coupon_id').value=id;
  if (val>0) val=-val;
  if (type=='S') val=-orderTotalValues['ot_shipping'];
  else if (type=='P') val=orderTotalValues['ot_subtotal']*val/100;
  if (isNaN(val)) val=0;
  var title='Discount Coupons';
  if (name) title+=' ('+name+')';
  setOrderTotal('ot_coupon',val,title);
}

</script>

<table border="0" width="100%" cellspacing="0" cellpadding="5">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
		
<?php
if (($action == 'edit' || ($action == 'update_order')) && ($order_exists == true)) {
  $order = new order($oID);
?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE . ($oID ? ('&nbsp;(' . HEADING_TITLE_NUMBER . '&nbsp;' . $oID . '&nbsp;' . HEADING_TITLE_DATE  . '&nbsp;' . tep_datetime_short($order->info['date_purchased']) . ')') : ''); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action'))) . '&action=edit">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
          </tr>
          <tr>
	          <td class="main" colspan="3"><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
          </tr>
          <tr>
            <td class="main" colspan="3"><?php echo HEADING_SUBTITLE; ?></td>
          </tr>
          <tr>
	          <td class="main" colspan="3"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
        </table></td>
      </tr>

      <tr><?php echo tep_draw_form('edit_order', "edit_orders.php", tep_get_all_get_params(array('action','paycc')) . 'action=update_order','post',' onsubmit="return prepareProductsData()"'); ?>
           <input type="hidden" name="sub_action" value="">
	  </tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr>   
      <tr>
	      <td>
          <table width="100%" border="0" cellpadding="2" cellspacing="1">
            <tr>
              <td class="main" bgcolor="#FAEDDE"><?php echo HINT_PRESS_UPDATE; ?></td>
              <td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
              <td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FF9933" width="120" align="center"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
	          </tr>
          </table>
				</td>
      </tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>   

      <tr>
	      <td><input type="checkbox" name="allow_calc" value="1" checked onChange="setCalc(this.checked)"> Allow Automatic Calculators
	      </td>
      <tr>

      <tr>
	    <td class="SubTitle"><?php echo MENUE_TITLE_CUSTOMER; ?></td>
	  </tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr>   

			<tr>
			  <td>

<input type="hidden" name="update_customers_id" value="<?php echo $cID?>">

<table width="100%" border="0" class="dataTableRow" cellpadding="2" cellspacing="0">
  <tr class="dataTableHeadingRow">
    <td class="dataTableHeadingContent" width="80"></td>
    <td class="dataTableHeadingContent" width="150"><?php echo ENTRY_BILLING_ADDRESS; ?></td>
    <td class="dataTableHeadingContent" width="6">&nbsp;</td>
    <td class="dataTableHeadingContent" width="150"><?php echo ENTRY_SHIPPING_ADDRESS; ?></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_COMPANY; ?>: </b></td>
    <td><span class="main"><input name="update_customer_company" size="25" value="<?php echo tep_html_quotes($order->billing['company']); ?>"></span></td>
	<td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_company" size="25" value="<?php echo tep_html_quotes($order->delivery['company']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_NAME; ?>: </b></td>
    <td><span class="main"><input name="update_billing_name" size="25" value="<?php echo tep_html_quotes($order->billing['name']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_name" size="25" value="<?php echo tep_html_quotes($order->delivery['name']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_ADDRESS; ?>: </b></td>
    <td><span class="main"><input name="update_customer_street_address" size="25" value="<?php echo tep_html_quotes($order->billing['street_address']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_street_address" size="25" value="<?php echo tep_html_quotes($order->delivery['street_address']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_SUBURB; ?>: </b></td>
    <td><span class="main"><input name="update_customer_suburb" size="25" value="<?php echo tep_html_quotes($order->billing['suburb']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_suburb" size="25" value="<?php echo tep_html_quotes($order->delivery['suburb']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_CITY; ?>: </b></td>
    <td><span class="main"><input name="update_customer_city" size="25" value="<?php echo tep_html_quotes($order->billing['city']); ?>"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_city" size="25" value="<?php echo tep_html_quotes($order->delivery['city']); ?>"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_STATE; ?>: </b></td>



    <td><span class="main">
    <input type="hidden" name="update_customer_state" value="<?php echo tep_html_quotes($order->billing['state'])?>" id="bill_state">
    <div id="bill_state_box"></div>
    </span></td>
    <td>&nbsp;</td>
    <td><span class="main">
    <input type="hidden" name="update_delivery_state" value="<?php echo tep_html_quotes($order->delivery['state'])?>" id="ship_state">
    <div id="ship_state_box"></div>
    </span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_POSTCODE; ?>: </b></td>
    <td><span class="main"><input name="update_customer_postcode" size="25" value="<?php echo $order->billing['postcode']; ?>" id="bill_postcode" onChange="reloadState('bill')"></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><input name="update_delivery_postcode" size="25" value="<?php echo $order->delivery['postcode']; ?>" id="ship_postcode" onChange="reloadState('ship')"></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_COUNTRY; ?>: </b></td>
    <td><span class="main"><?php echo tep_draw_pull_down_menu('update_customer_country',$countries_pull_down,$order->billing['country'],' id="bill_country" onChange="reloadState(\'bill\')" style="width:200px"')?></span></td>
    <td>&nbsp;</td>
    <td><span class="main"><?php echo tep_draw_pull_down_menu('update_delivery_country',$countries_pull_down,$order->delivery['country'],' id="ship_country" onChange="reloadState(\'ship\')" style="width:200px"')?></span></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_PHONE; ?>: </b></td>
    <td><span class="main"><input name="update_customer_telephone" size="25" value="<?php echo $order->customer['telephone']; ?>"></span></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_CUSTOMER_EMAIL; ?>: </b></td>
    <td colspan="3"><span class="main"><input name="update_customer_email_address" size="25" value="<?php echo $order->customer['email_address']; ?>"></span></td>
  </tr>
</table>

				</td>
			</tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>      

      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_PAYMENT; ?></td>
			</tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr>   
      <tr>
	      <td>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr><td valign="top">
				
<table width="100%" border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
  <tr class="dataTableHeadingRow">
    <td colspan="2" class="dataTableHeadingContent">Payment Record:</td>
	</tr>
  <tr>
	  <td colspan="2" class="main">
<?php 
	if($order->info['payment_method'] == 'payment_authnet') {
		echo 'Authorize.Net / Credit Card'; 
	} else {
		echo $order->info['payment_method'];
	}
?>
	  <!--input name='update_info_payment_method' size='35' value='<?php echo $order->info['payment_method']; ?>'--></td>
	</tr>
	  <tr>
	    <td class="main"><?php echo ENTRY_CURRENCY; ?></td>
	    <td class="main"><?php echo tep_draw_pull_down_menu('update_currency',$currencies_pull_down,$order->info['currency'])?></td>
	  </tr>
	<?php 
		if(!empty($order->info['cc_type'])) { ?>
	  <tr>
	    <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
	  </tr>
	  <tr>
	    <td class="main"><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
	    <td class="main"><?php echo $order->info['cc_type']?></td>
	  </tr>
	  <tr>
	    <td class="main"><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
	    <td class="main"><?php echo $order->info['cc_owner']; ?></td>
	  </tr>
	  <tr>
	    <td class="main"><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
	    <td class="main"><?php echo $order->info['cc_number']; ?></td>
	  </tr>
	  <tr>
	    <td class="main"><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
	    <td class="main"><?php echo $order->info['cc_expires']; ?></td>
	  </tr>
	<?php } ?>
</table>

	</td><td valign="top">
<table width="100%" border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
  <tr class="dataTableHeadingRow">
    <td class="dataTableHeadingContent">New Payment Information:</td>
	</tr>
	<tr>
	  <td class="main">
<?php

	// # Retrieve available payment modules for checkout

	$payset = tep_module('checkout');
	$paymods = $payset->getModules();
	$paymods['payment_manual'] = tep_module('payment_manual','payment');
	$pay_selbox = array();
	$onfile_selbox = array();
	
	foreach ($order->getPayments() AS $mod) {
		$ac = $mod->checkStoredPaymentInfo();
		if(!empty($ac)) {
			$pay_selbox[] = array('id'=>$mod->payid.':'.get_class($mod),'text'=>'On File: '.$ac);
		}
	}
  
	foreach ($paymods AS $mkey=>$mod) {

    	$pay_selbox[] = array('id'=>$mkey,'text'=>$mod->getName());

		if($mod->isRecurrable()) {
			$onfile_selbox[]=Array('id'=>$mkey,'text'=>$mod->getName());
		}
	}

	$selmod = (empty($order->info['payment_method']) ? $pay_selbox[0]['id'] : $order->info['payment_method']);

	echo '<div id="payment_on_file" style="display:none; padding:5px 0 15px 0">';
	echo 'using '.tep_draw_pull_down_menu('payment_on_file',$onfile_selbox);
	echo "</div>\n";

  echo '<div style="main"><table><tr><td width="136">Payment method:</td><td>' .tep_draw_pull_down_menu('pay_method', $pay_selbox, $selmod, 'id="pay_method" onChange="setPayMethod()"').'</tr></table></div>';

  foreach ($paymods AS $mkey => $mod) {
    echo '<div id="'.$mkey.'" style="'.($mkey == $selmod ? '' : 'display:block').';">';
    echo $mod->paymentBox();
    echo "</div>\n";
  }

?>
<script type="text/javascript">

 function setPayMethod() {

	// # detect pay_method div above
	// # assign to var blk
	var blk = $('pay_method');

	// # if blk does not exist return false
    if (!blk) return false;
	// # now loop through each of the options in the select
    for (var i=0;blk.options[i];i++) {
		// # assign each value to a pm instance
		var pm = blk.options[i].value;

		// # if option ':' detected, assign instance to payment_on_file
		if (pm.match(/:/)) pm = 'payment_on_file';

		// # now a ternary detect for previous selection and style
		if ($(pm)) $(pm).style.display = blk.options[i].selected ? '' : 'none';
	}

    return true;

 } // # end function setPayMethod();

 // # dont forget to initilaize it!
 setPayMethod();
</script>

</td>
	</tr>
	</table>
	</td></tr>
	</table>
        </td>
      </tr>
	    <?php if ($order->info['payment_method'] != "Credit Card") { ?>
  	    <tr>
	        <td class="smalltext"></td>
	      </tr>
	    <?php } ?>
      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_ORDER; ?></td>
	</tr>
</table>

<table width="100%" cellpadding="10" cellspacing="0" border="0">
			<tr class="dataTableHeadingRow">
			<td class="dataTableHeadingContent" width="50" align="center"><?php echo TABLE_HEADING_QUANTITY; ?></td>
			<td class="dataTableHeadingContent" width="1"></td>
			<td class="dataTableHeadingContent" width="300"><?php echo TABLE_HEADING_PRODUCTS; ?> &nbsp; <input type="text" id="prodSearch" name="prodSearch" style="font:normal 11px arial; width:150px; border: 1px solid #FFF" value="Product Search" autocomplete="off"><div style="position:relative;"><div id="prodSearchDIV" style="position:absolute; display:none;"></div></div></td>
			<td class="dataTableHeadingContent" width="125"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
			<td class="dataTableHeadingContent" width="75"><?php echo TABLE_HEADING_UNIT_PRICE_TAXED; ?></td>
			<td class="dataTableHeadingContent" width="60" align="center"><?php echo TABLE_HEADING_TAX; ?></td>
			<td class="dataTableHeadingContent" width="65"><?php echo TABLE_HEADING_TOTAL_PRICE_TAXED; ?></td>
			<td class="dataTableHeadingContent" align="center" width="60"><?php echo TABLE_HEADING_FREE_SHIPPING; ?></td>
</tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
	      <td>
				  
<?php
    // # Override order.php Class's Field Limitations
		$index = 0;
		$order->products = array();
		$orders_products_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = '" . (int)$oID . "'");

		while ($orders_products = tep_db_fetch_array($orders_products_query)) {


			// # Retrieve current-day product costing from the products table and add to orders products.
			// # important to keep historical pricing / costs for inventory since this can fluctuate with time.
			// # if no cost found in suppliers_products_groups, try the products table for old format

			// # costing from suppliers_products_groups table
			$cost_price_query = tep_db_query("SELECT suppliers_group_price FROM ". TABLE_SUPPLIERS_PRODUCTS_GROUPS ." WHERE products_id = '". $orders_products['products_id'] ."' AND priority = '0' LIMIT 1");
			$cost_price = (tep_db_num_rows($cost_price_query) > 0 ? tep_db_result($cost_price_query,0) : 0);
			tep_db_free_result($cost_price_query);

			// # costing from products table
			$cost_old_query = tep_db_query("SELECT products_price_myself FROM ". TABLE_PRODUCTS ." WHERE products_id = '". $orders_products['products_id'] ."'");			
			$cost_old = (tep_db_num_rows($cost_old_query) > 0 ? tep_db_result($cost_old_query,0) : 0);
			tep_db_free_result($cost_old_query);

			// # if supplier cost is empty, use old format
			$cost = (!empty($cost_price) ? $cost_price : $cost_old);


			// # Retrieve price from products_groups table. 

			$pricing_query = tep_db_query("SELECT pg.customers_group_price 
										   FROM ". TABLE_PRODUCTS_GROUPS ." pg 
										   LEFT JOIN ". TABLE_CUSTOMERS ." c ON c.customers_group_id = pg.customers_group_id
										   LEFT JOIN ". TABLE_ORDERS ." o ON o.customers_id = c.customers_id
										   WHERE o.orders_id = '". $oID ."'
										   AND products_id = '".$orders_products['products_id']."'
										  ");

			$price = (tep_db_num_rows($pricing_query) > 0 ? tep_db_result($pricing_query, 0) : $orders_products['products_price']);
			tep_db_free_result($pricing_query);

			// # populate orders_products_data array

			$order->products[$index] = array(
										 'qty' => $orders_products['products_quantity'],
    	                                 'name' => str_replace("'", "&#39;", $orders_products['products_name']),
        	                             'model' => $orders_products['products_model'],
            	                         'tax' => $orders_products['products_tax'],
                	                     'price' => (float)$price,
										 'cost' => (float)$cost, 
                    	                 'final_price' => $orders_products['final_price'],
                        	             'products_id' => $orders_products['products_id'],
                            	         'free_shipping' => $orders_products['free_shipping'],
                                	     'separate_shipping' => $orders_products['separate_shipping'],
                                    	 'products_weight' => $orders_products['products_weight'],
	                                     'orders_products_id' => $orders_products['orders_products_id'],
										 'warehouse_id' => $orders_products['warehouse_id']
										);

			$subindex = 0;
			$attributes_query_string = "select * from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$oID . "' and orders_products_id = '" . (int)$orders_products['orders_products_id'] . "'";
			$attributes_query = tep_db_query($attributes_query_string);

			if (tep_db_num_rows($attributes_query)) {
	
				while ($attributes = tep_db_fetch_array($attributes_query)) {
				  $order->products[$index]['attributes'][$subindex] = array('option' => $attributes['products_options'],
		    		                                                        'value' => $attributes['products_options_values'],
		            		                                                'prefix' => $attributes['price_prefix'],
		                    		                                        'price' => $attributes['options_values_price'],
		                            		                                'orders_products_attributes_id' => $attributes['orders_products_attributes_id']
																			);
				  $subindex++;
				}
			}
	
			$index++;
		} // # END while
	
?>

<div id="products_box"></div>
<div id="add_product_box"></div>
<input type="hidden" name="order_products_data" id="order_products_data" value="">

<script type="text/javascript">
<?php
  foreach($order->products AS $prod) {
    $attr_js_data = array();
    if (isset($prod['attributes'])) foreach($prod['attributes'] AS $attr) {
      $attr_js_data[]="{"
        ." option: '".addslashes($attr['option'])."',"
        ." value: '".addslashes($attr['value'])."',"
        ." orders_products_attributes_id: '".addslashes($attr['orders_products_attributes_id'])."',"
        ." price: '".($attr['price_prefix']=='-'?-$attr['price']:$attr['price'])."' "
      ."}";
    }
?>
  orderProducts[orderProducts.length]={
    orders_products_id: '<?php echo addslashes($prod['orders_products_id'])?>',
    id: '<?php echo addslashes($prod['products_id'])?>',
    name: '<?php echo addslashes($prod['name'])?>',
    model: '<?php echo addslashes($prod['model'])?>',
    price: '<?php echo addslashes($prod['price'])?>',
	cost: '<?php echo addslashes($prod['cost'])?>',
    final_price: '<?php echo addslashes($prod['final_price'])?>',
    tax: '<?php echo addslashes($prod['tax'])?>',
    free_shipping: '<?php echo addslashes($prod['free_shipping'])?>',
    separate_shipping: '<?php echo addslashes($prod['separate_shipping'])?>',
    weight: '<?php echo addslashes($prod['products_weight'])?>',
    qty: '<?php echo addslashes($prod['qty'])?>',
	warehouse_id: '<?php echo addslashes($prod['warehouse_id'])?>',
    attr: <?php echo tep_js_quote_array($prod['attributes'])?>
  };

<?php
  }
?>
</script>


<table border="0" width="100%" cellspacing="0" cellpadding="2">
	<!--tr class="dataTableHeadingRow">
	  <td class="dataTableHeadingContent" width="25"><?php echo TABLE_HEADING_QUANTITY; ?></td>
	  <td class="dataTableHeadingContent" width="10">&nbsp;</td>
	  <td class="dataTableHeadingContent" width="300"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
	  <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
	  <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_TAX; ?></td>
	  <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_UNIT_PRICE; ?></td>
	  <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_UNIT_PRICE_TAXED; ?></td>
	  <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_PRICE; ?></td>
	  <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_PRICE_TAXED; ?></td>
	</tr>
	
<?php
	for ($i=0; $i<sizeof($order->products); $i++) {
		$orders_products_id = $order->products[$i]['orders_products_id'];
		$RowStyle = "dataTableContent";
		echo '	  <tr class="dataTableRow">' . "\n" .
		     '	    <td class="' . $RowStyle . '" align="left" width="25">' . "<input name='update_products[$orders_products_id][qty]' size='2' value='" . $order->products[$i]['qty'] . "'></td>\n" . 
 		     '	    <td class="' . $RowStyle . '" width="10">&nbsp;x&nbsp;</td>' . 
		     '	    <td class="' . $RowStyle . '" width="300">' . $order->products[$i]['name'] . "<input name='update_products[$orders_products_id][name]' type='hidden' value='" . $order->products[$i]['name'] . "'>";
		
		// # Has Attributes?
		if (sizeof($order->products[$i]['attributes']) > 0) {
			for ($j=0; $j<sizeof($order->products[$i]['attributes']); $j++) {
				$orders_products_attributes_id = $order->products[$i]['attributes'][$j]['orders_products_attributes_id'];
				echo '<br><nobr><small>&nbsp;<i> - ' . 
				 htmlspecialchars($order->products[$i]['attributes'][$j]['option']) .
				 ': ' . 
				 htmlspecialchars($order->products[$i]['attributes'][$j]['value']);
			}
		}
		
		echo '	    </td>' . "\n" .
		     '	    <td class="' . $RowStyle . '">' . $order->products[$i]['model'] . "<input name='update_products[$orders_products_id][model]' size='12' type='hidden' value='" . $order->products[$i]['model'] . "'>" . '</td>' . "\n" .
		     '	    <td class="' . $RowStyle . '">' . "<input name='update_products[$orders_products_id][tax]' size='4' value='" . tep_display_tax_value($order->products[$i]['tax']) . "'>" . '%</td>' . "\n" .
		     '	    <td class="' . $RowStyle . '" align="right">' . $currencies->format($order->products[$i]['final_price']) . "<input name='update_products[$orders_products_id][final_price]' size='5' type='hidden' value='" . $order->products[$i]['final_price'] . "'>" . '</td>' . "\n" . 
		     '	    <td class="' . $RowStyle . '" align="right">' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']) . '</td>' . "\n" . 
		     '	    <td class="' . $RowStyle . '" align="right">' . $currencies->format($order->products[$i]['final_price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</td>' . "\n" . 
		     '	    <td class="' . $RowStyle . '" align="right"><b>' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" . 
		     '	  </tr>' . "\n";
	}
	?>
</table>

        </td>
</tr>
      <tr>
	      <td>
				  <table width="100%" cellpadding="0" cellspacing="0">
					  <tr>
						  <td valign="top"><?php echo "<span class='smalltext'>" . HINT_DELETE_POSITION . "</span>"; ?></td>
			        <td align="right"><?php echo '<a href="' . $PHP_SELF . '?oID=' . $oID . '&action=add_product&step=1" onClick="document.forms[\'edit_order\'].sub_action.value=\'add_product\'; document.forms[\'edit_order\'].submit(); return false;">' . tep_image_button('button_add_article.gif', ADDING_TITLE) . '</a>&nbsp;<a href="' . $PHP_SELF . '?oID=' . $oID . '&action=add_non_inv" onClick="document.forms[\'edit_order\'].sub_action.value=\'add_non_inv\'; document.forms[\'edit_order\'].submit(); return false;">' . tep_image_button('button_add_noninv.gif', ADDING_TITLE) . '</a>'; ?></td>
						</tr>
					</table>
			  </td>
      </tr-->
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
	
      <tr>
	      <td>
          <table width="100%" border="0" cellpadding="2" cellspacing="1">
            <tr>
              <td class="main" bgcolor="#FAEDDE"><?php echo HINT_PRESS_UPDATE; ?></td>
              <td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
              <td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FF9933" width="120" align="center"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
	          </tr>
          </table>
				</td>
      </tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>   
      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_TOTAL; ?></td>
			</tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr>   
      <tr>
	      <td>

<table width="100%">
<tr><td style="white-space:nowrap">

<table>
<tr><td>

<table border="0" cellspacing="0" cellpadding="2" class="dataTableRow">
	<tr class="dataTableHeadingRow">
	  <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_TOTAL_MODULE; ?></td>
	  <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_TOTAL_AMOUNT; ?></td>
	  <td class="dataTableHeadingContent"width="1"><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
	</tr>
<?php

  if ($oID) {
	// # Override order.php Class's Field Limitations
    $totals_query = tep_db_query("select * from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$oID . "' order by sort_order");
    $order->totals = array();

    while ($totals = tep_db_fetch_array($totals_query)) { 
	  $order->totals[] = array('title' => $totals['title'], 'text' => $totals['text'], 'class' => $totals['class'], 'value' => $totals['value'], 'orders_total_id' => $totals['orders_total_id']); 
	}
  }

	// # START OF MAKING ALL INPUT FIELDS THE SAME LENGTH 
	$max_length = 0;
	$TotalsLengthArray = array();

	for ($i=0; $i<sizeof($order->totals); $i++) {
		$TotalsLengthArray[] = array("Name" => $order->totals[$i]['title']);
	}

	reset($TotalsLengthArray);

	foreach($TotalsLengthArray as $TotalIndex => $TotalDetails) {
		if (strlen($TotalDetails["Name"]) > $max_length) {
			$max_length = strlen($TotalDetails["Name"]);
		}
	}
	// # END OF MAKING ALL INPUT FIELDS THE SAME LENGTH


	$TotalsArray = array();

	$ct_custom = 0;

	for ($i=0; $i<sizeof($order->totals); $i++) {

		$TotalsArray[] = array("Name" => htmlspecialchars($order->totals[$i]['title']), "Price" => number_format($order->totals[$i]['value'], 2, '.', ''), "Class" => $order->totals[$i]['class'], "TotalID" => $order->totals[$i]['orders_total_id'], Negative=>(strpos('discount',$order->totals[$i]['class'])));

		if ($order->totals[$i]['class'] == 'ot_custom') $ct_custom++;
	}

	if ($ct_custom==0) array_splice($TotalsArray,sizeof($TotalsArray)-1,0,Array(Array("Name" => "Manager Discount", "Price" => "", "Class" => "ot_custom", "TotalID" => "0", Negative=>1)));
	if ($ct_custom<2) array_splice($TotalsArray,sizeof($TotalsArray)-1,0,Array(Array("Name" => "", "Price" => "", "Class" => "ot_custom", "TotalID" => "0")));
	
	$total_value = array();

	foreach($TotalsArray as $TotalIndex => $TotalDetails) {
		$TotalStyle = "smallText";
		$total_id=$TotalDetails["Class"];
		while (isset($total_value[$total_id])) $total_id.='_';
		$total_value[$total_id]=$TotalDetails["Price"];
		$hide = false;

		switch ($TotalDetails["Class"]) {
		  case "ot_total":
		  case "ot_subtotal":
		  case "ot_tax":
			$hide=true;
		}

		echo '<tr>
				<td align="right" class="' . $TotalStyle . '">'.($hide ? '<div id="'.$total_id.'_title_txt"><b>' . $TotalDetails["Name"] . '</b></div>' : '').'
					<input name="update_totals['.$TotalIndex.'][title]" id="'.$total_id.'_title" type="'.($hide ? 'hidden' : 'text').'" value="'. trim($TotalDetails["Name"]) .'" size="24" >'.'</td>
				<td align="right" class="' . $TotalStyle . '">'.($hide ? '<div id="'.$total_id.'_value_txt"><b>' . $currencies->format($TotalDetails["Price"], true, $order->info['currency'], $order->info['currency_value']) . '</b></div>':''). '<input name="update_totals['.$TotalIndex.'][value]" id="'.$total_id.'_value" type="'.($hide?'hidden':'text').'" value="' . $TotalDetails["Price"] . '" size="6" onChange="setOrderTotal(\''.$total_id.'\',this.value'.($TotalDetails["Negative"]?'.replace(/^(\d)/,\'-$1\')':'').')">' . 
				   '<input name="update_totals['.$TotalIndex.'][class]" type="hidden" value="' . $TotalDetails["Class"] . '">' . 
				   '<input type="hidden" name="update_totals['.$TotalIndex.'][total_id]" value="' . $TotalDetails["TotalID"] . '"></b></td>' . 
				   '		<td align="right" class="' . $TotalStyle . '"><b>' . tep_draw_separator('pixel_trans.gif', '1', '17') . '</b>' . 
				   '	</tr>' . "\n";
	}
?>
</table>
<script type="text/javascript">
<?php
    foreach ($total_value AS $ot_class => $ot_value) {
      $ot_value+=0;
      echo "  orderTotalValues['$ot_class']=$ot_value;\n";
    }
?>
</script>
</td>
<td valign="top">

<table>
	<tr>
		<td>Coupon Code: <input type="text" size="20" name="coupon_code" id="coupon_code"><input type="button" value="Apply" onclick="reloadCoupon($('coupon_code').value); return false;"><input type="button" value="Reset" onClick="reloadCoupon(''); return false"></td>
	</tr>
	<tr>
		<td><div id="coupon_box"></div><input type="hidden" name="coupon_id" value="" id="coupon_id"></td>
	</tr>
	<tr>
		<td></td>
	</tr>
</table>

</td></tr>
</table>
<table width="100%">
      <tr>
			  <td class="smalltext"><?php echo HINT_TOTALS; ?></td>
      </tr></table> <br>
      <input type="hidden" id="shipping_method" name="update_shipping_method" value="<?php echo $order->info['shipping_method']?>">
<?php if ($order->info['shipping_method']) { ?>
      <input type="checkbox" id="recalculate_shipping" name="recalculate_shipping" onClick="reloadShipping(this.checked)"> Recalculate Shipping Quote
<?php 
} 
?>
   <div id="shipping_box">Shipping</div>
	      </td>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      
<script type="text/javascript">
	setCalc(1);
	renderProducts(false);
	ReloadAddProduct();
</script>

      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_STATUS; ?></td>
			</tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr> 

<?php
if ($oID) {
?>

      <tr>
        <td class="main">
<table border="0" cellspacing="0" cellpadding="2" width="100%" class="commentsTable">
                           <tr>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_STATUS; ?></b></th>
                            <th width="50%" class="smallText" align="center"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></th>
                          </tr>
<?php
		$orders_history_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_STATUS_HISTORY . " 
											  WHERE orders_id = '" . tep_db_input($oID) . "' 
											  ORDER BY date_added
											");
		if(tep_db_num_rows($orders_history_query)) {
			while ($orders_history = tep_db_fetch_array($orders_history_query)) {
    			echo '<tr>
						<td class="smallText" align="center">' . date('m/d/Y  - g:ia', strtotime($orders_history['date_added'])). (!empty($orders_history['admin_user']) ? '<br><br>By: ' . $orders_history['admin_user'] : '').'</td>
						<td class="smallText" align="center">';

	if ($orders_history['customer_notified'] == '1') {
		echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK);
	} else {
		echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS);
	}
    
	echo '</td><td class="smallText" align="left">' . $orders_status_array[$orders_history['orders_status_id']] . '</td>
			<td class="smallText" align="left"  style="padding: 10px 20px; background-color:#FFF; text-align:justify;">' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>
			</tr>';
  }

} else {
	echo '<tr>
			<td class="smallText" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>
		</tr>';
}
?>
</table>

			  </td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr>
<?php
}
?>

<tr>
	  <td>			
<table border="0" cellspacing="0" cellpadding="2" class="dataTableRow" width="100%">
  <tr class="dataTableHeadingRow">
    <td class="dataTableHeadingContent" align="left"><?php echo TABLE_HEADING_STATUS; ?></td>
    <td class="main" width="10">&nbsp;</td>
    <td class="dataTableHeadingContent" align="left"><?php echo TABLE_HEADING_COMMENTS; ?></td>
  </tr>
	<tr>
	  <td style="padding:10px 0 0 0" valign="top">
		  <table border="0" cellspacing="0" cellpadding="2">
        <tr>
          <td class="main" nowrap><b><?php echo ENTRY_STATUS; ?></b> &nbsp; </td>
          <td class="main" align="right"> <?php echo tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status']); ?></td>
        </tr>
        <tr>
          <td class="main"><b><?php echo ENTRY_NOTIFY_CUSTOMER; ?></b></td>
          <td class="main" align="right">
<?php 
	echo tep_draw_checkbox_field('notify', '', true);
	echo tep_draw_hidden_field('notify_comments', 'on'); 
?>
</td>
        </tr>
        <tr>
          <td class="main"><b><?php echo ENTRY_SEND_CONFIRMATION; ?></b></td>
          <td class="main" align="right"><?php echo tep_draw_checkbox_field('send_confirmation', '', !$oID); ?></td>
        </tr>
      </table>
	  </td>
    <td class="main" width="10">&nbsp;</td>
    <td class="main" width="75%">

<div class="tabber" onClick="contentChanged();">

     <div class="tabbertab">
	  <h2>Customer Correspondence</h2>

<?php echo tep_draw_textarea_field('comments', 'soft', '40', '5', '', 'style="width:100%"');?>

</div>

     <div class="tabbertab hide_print">
	  <h2>Admin Only Comments</h2>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr> 
                      <td><table width="100%" border="0" cellpadding="0" cellspacing="0" style="border:solid 1px #0099FF;">
                          <tr> 
                            <td width="91" align="center" class="smallText" style="border:solid 1px #0099FF; border-top:0; border-left:0"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></td>
                            <td align="center" class="smallText" style="border-bottom:solid 1px #0099FF;"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
                          </tr>
<?php

    $admin_comments_query = tep_db_query("SELECT orders_id, 
												 date_added, 
												 comments, 
												 admin_user 
										 FROM admin_comments 
										 WHERE orders_id = '" . tep_db_input($oID) . "'
										 ORDER BY date_added
										");

	if (tep_db_num_rows($admin_comments_query)) {
		while ($admin_history = tep_db_fetch_array($admin_comments_query)) {
			echo '<tr>
					<td class="smallText" align="center"  style="border-right:solid 1px #0099FF; padding:10px;">' . tep_datetime_short($admin_history['date_added']) . (!empty($admin_history['admin_user']) ? '<br><br><b>By: '.$admin_history['admin_user'] : '') . '</b></td>
					<td class="smallText" style="padding:10px;">' . tep_db_output($admin_history['comments']) . '&nbsp;</td>
				</tr>';
    	}

	} else {

        echo '<tr>
				<td class="smallText" colspan="2" style="padding:10px;">No Admin Comments.</td>
			  </tr>';
    }
?>
			</table></td>
                    </tr>
                    <tr> 
                      <td> 
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="main">
                          <tr> 
                            <td class="main" style="padding-top:10px;"><b>Admin Only Comments</b></td>
                          </tr>
						  
                          <tr> 
                            <td><?php echo tep_draw_textarea_field('admin_comments', 'soft', '90%', '5', '', 'style="background-color:#FFFFD9;"'); ?></td>
                          </tr>
                          <tr class="hide_print"> 
                            <td align="right" style="padding-top:10px"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                          </tr>
                        </table></td>
                    </tr>
					
  </table>
</div>
</div>
    </td>
  </tr>
</table>

			  </td>
			</tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
	
      <tr>
	      <td class="SubTitle"><?php echo MENUE_TITLE_UPDATE; ?></td>
			</tr>
      <tr>
	      <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
      </tr>   
      <tr>
	      <td>
          <table width="100%" border="0" cellpadding="2" cellspacing="1">
            <tr>
              <td class="main" bgcolor="#FAEDDE"><?php echo HINT_PRESS_UPDATE; ?></td>
              <td class="main" bgcolor="#FBE2C8" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FFCC99" width="10">&nbsp;</td>
              <td class="main" bgcolor="#F8B061" width="10">&nbsp;</td>
              <td class="main" bgcolor="#FF9933" width="120" align="center"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
	          </tr>
          </table>
				</td>
      </tr>

	
      </form>

<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function(){

	jQuery("#prodSearch").on('click', function(){

	    if(this.value == 'Product Search') { 
			this.value = '';
		}

		jQuery("#prodSearchDIV").hide();
	});

	jQuery(document).on("click", "#products_box", function(e) {

		jQuery('#prodSearchDIV').hide();
		jQuery('#prodSearchDIV').html('');
		
	});

	var delayTimer;

	jQuery("#prodSearch").on('keyup paste', function(e) {
		
		clearTimeout(delayTimer);

		if(jQuery(this).val().length > 1 ) {

			var thestr = jQuery(this).val().trim();
		
			delayTimer = setTimeout(function() {


				jQuery.get('<?php echo $_SERVER['PHP_SELF'] ?>?search', { search : thestr }, function(data) {

					jQuery('#prodSearchDIV').html('');
			
					if(data.length > 0) { 

						jQuery("#prodSearchDIV").fadeIn('fast');

						jQuery('#prodSearchDIV').prepend('<table width="100%" cellpadding="5" cellspacing="0" border="0" id="prodSearchTable">');	

						for (var i in data) {

							if(data[i][4]) {

								jQuery('<tr class="dataRow" id="'+data[i][0]+'_'+data[i][1]+'"><td><img src="/images/cache/50x50/'+data[i][3]+'"></td><td>'+data[i][4]+'</td></tr>').appendTo(jQuery('#prodSearchTable'));
							}
						}


						jQuery('#prodSearchDIV').append('</table>');

						jQuery(".dataRow").on('click', function(){

							var row = jQuery(this).attr("id");

							row = row.toString().split('_')

							ReloadAddProduct(row[0],row[1]);

							jQuery("#prodSearchDIV").hide();
						});
					}

   	        	}, 'json');

			}, 900); // # Will do the ajax stuff after 1000 ms, or 1 s
	    }
	
	});
});

jQuery('body').keypress(function(e) {
  if (e.keyCode == '13') {
     e.preventDefault();
   }
});

</script>

			
<?php
} 

	if($action == "add_product") {
?>
      <tr>
        <td width="100%">
				  <table border="0" width="100%" cellspacing="0" cellpadding="0">
            <tr>
              <td class="pageHeading"><?php echo ADDING_TITLE; ?> (Nr. <?php echo $oID; ?>)</td>
              <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
              <td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_EDIT_ORDERS, tep_get_all_get_params(array('action'))) . '&action=edit">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
            </tr>
          </table>
				</td>
      </tr>

<?php
	// ############################################################################
	//   Get List of All Products
	// ############################################################################

		//$result = tep_db_query("SELECT products_name, p.products_id, x.categories_name, ptc.categories_id FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id=p.products_id LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc ON ptc.products_id=p.products_id LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id=ptc.categories_id LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " x ON x.categories_id=ptc.categories_id ORDER BY categories_id");

		$result = tep_db_query("SELECT pd,products_name, 
									   p.products_id, 
									   cd.categories_name, 
									   ptc.categories_id  
								FROM " . TABLE_PRODUCTS . " p 
								LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id 
								LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc ON ptc.products_id = p.products_id 
								LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = ptc.categories_id 
								WHERE pd.language_id = '" . (int)$languages_id . "' 
								ORDER BY cd.categories_name
							   ");

		while($row = tep_db_fetch_array($result)) {
			extract($row,EXTR_PREFIX_ALL,"db");
			$ProductList[$db_categories_id][$db_products_id] = $db_products_name;
			$CategoryList[$db_categories_id] = $db_categories_name;
			$LastCategory = $db_categories_name;
		}
		
		//ksort($ProductList);
		
		$LastOptionTag = "";
		$ProductSelectOptions = "<option value='0'>Don't Add New Product" . $LastOptionTag . "\n";
		$ProductSelectOptions .= "<option value='0'>&nbsp;" . $LastOptionTag . "\n";

		foreach($ProductList as $Category => $Products) {
			$ProductSelectOptions .= "<option value='0'>$Category" . $LastOptionTag . "\n";
			$ProductSelectOptions .= "<option value='0'>---------------------------" . $LastOptionTag . "\n";
			asort($Products);
			foreach($Products as $Product_ID => $Product_Name) {
				$ProductSelectOptions .= "<option value='$Product_ID'> &nbsp; $Product_Name" . $LastOptionTag . "\n";
			}
			
			if($Category != $LastCategory) {
				$ProductSelectOptions .= "<option value='0'>&nbsp;" . $LastOptionTag . "\n";
				$ProductSelectOptions .= "<option value='0'>&nbsp;" . $LastOptionTag . "\n";
			}
		}
	
	
	// ############################################################################
	//   Add Products Steps
	// ############################################################################
	
		print "<tr><td><table border='0'>\n";
		
		// # Set Defaults
			if(!isset($add_product_categories_id))
			$add_product_categories_id = 0;

			if(!isset($add_product_products_id))
			$add_product_products_id = 0;
		
		// # Step 1: Choose Category
			print "<tr class=\"dataTableRow\"><form action='$PHP_SELF?oID=$oID&action=$action' method='POST'>\n";
			print "<td class='dataTableContent' align='right'><b>" . ADDPRODUCT_TEXT_STEP . " 1:</b></td>\n";
			print "<td class='dataTableContent' valign='top'>";
			echo ' ' . tep_draw_pull_down_menu('add_product_categories_id', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
			print "<input type='hidden' name='step' value='2'>";
			print "</td>\n";
			print "<td class='dataTableContent'>" . ADDPRODUCT_TEXT_STEP1 . "</td>\n";
			print "</form></tr>\n";
			print "<tr><td colspan='3'>&nbsp;</td></tr>\n";

		// # Step 2: Choose Product
		if(($step > 1) && ($add_product_categories_id > 0)) {
			print "<tr class=\"dataTableRow\"><form action='$PHP_SELF?oID=$oID&action=$action' method='POST'>\n";
			print "<td class='dataTableContent' align='right'><b>" . ADDPRODUCT_TEXT_STEP . " 2: </b></td>\n";
			print "<td class='dataTableContent' valign='top'><select name=\"add_product_products_id\" onChange=\"this.form.submit();\">";
			$ProductOptions = "<option value='0'>" .  ADDPRODUCT_TEXT_SELECT_PRODUCT . "\n";
			asort($ProductList[$add_product_categories_id]);

			foreach($ProductList[$add_product_categories_id] as $ProductID => $ProductName)	{
				$ProductOptions .= "<option value='$ProductID'> $ProductName\n";
			}

			$ProductOptions = str_replace("value='$add_product_products_id'","value='$add_product_products_id' selected", $ProductOptions);

			print $ProductOptions;
			print "</select></td>\n";
			print "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
			print "<input type='hidden' name='step' value='3'>\n";
			print "<td class='dataTableContent'>" . ADDPRODUCT_TEXT_STEP2 . "</td>\n";
			print "</form></tr>\n";
			print "<tr><td colspan='3'>&nbsp;</td></tr>\n";
		}

		// # Step 3: Choose Options
		if(($step > 2) && ($add_product_products_id > 0)) {
			// Get Options for Products
			$result = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
									LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON (po.products_options_id=pa.options_id AND po.language_id = '".(int)$languages_id ."')
									LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON (pov.products_options_values_id=pa.options_values_id AND pov.language_id = '".(int)$languages_id ."') 
									WHERE products_id='$add_product_products_id'
								   ");
			
			// # Skip to Step 4 if no Options
			if(tep_db_num_rows($result) == 0) {

				echo '<tr class="dataTableRow">
						<td class="dataTableContent" align="right"><b>' . ADDPRODUCT_TEXT_STEP . ' 3: </b></td>
						<td class="dataTableContent" valign="top" colspan="2"><i>' . ADDPRODUCT_TEXT_OPTIONS_NOTEXIST . '</i></td>
					  </tr>';

				$step = 4;

			} else {

				while($row = tep_db_fetch_array($result)) {

					//extract($row,EXTR_PREFIX_ALL,"db");
					//$Options[$db_products_options_id] = $db_products_options_name;
					//$ProductOptionValues[$db_products_options_id][$db_products_options_values_id] = $db_products_options_values_name;

					$Options[$row['products_options_id']] = $row['products_options_name'];
					$ProductOptionValues[$row['products_options_id']][$row['products_options_values_id']] = $row['products_options_values_name'];
				}
			
				echo '<tr class="dataTableRow"><form action="'.$PHP_SELF.'?oID=$oID&action=$action" method="POST">
						<td class="dataTableContent" align="right"><b>' . ADDPRODUCT_TEXT_STEP . ' 3: </b></td>
						<td class="dataTableContent" valign="top">';

				foreach($ProductOptionValues as $OptionID => $OptionValues) {

					$OptionOption = '<b>' . $Options[$OptionID] . '</b> - <select name="add_product_options[$OptionID]">';

					foreach($OptionValues as $OptionValueID => $OptionValueName) {
						$OptionOption .= "<option value='$OptionValueID'>$OptionValueName</option>\n";
					}

					$OptionOption .= '</select><br>';
					
					if(isset($add_product_options)) {
						$OptionOption = str_replace('value="' . $add_product_options[$OptionID] . '"','value="' . $add_product_options[$OptionID] . '" selected',$OptionOption);
					}
					
					print $OptionOption;
				}		
				print "</td>";
				print "<td class='dataTableContent' align='center'><input type='submit' value='" . ADDPRODUCT_TEXT_OPTIONS_CONFIRM . "'>";
				print "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
				print "<input type='hidden' name='add_product_products_id' value='$add_product_products_id'>";
				print "<input type='hidden' name='step' value='4'>";
				print "</td>\n";
				print "</form></tr>\n";
			}

			print "<tr><td colspan='3'>&nbsp;</td></tr>\n";
		}

		// # Step 4: Confirm
		if($step > 3) {
			print "<tr class=\"dataTableRow\"><form action='$PHP_SELF?oID=$oID&action=$action' method='POST'>\n";
			print "<td class='dataTableContent' align='right'><b>" . ADDPRODUCT_TEXT_STEP . " 4: </b></td>";
			print "<td class='dataTableContent' valign='top'><input name='add_product_quantity' size='2' value='1'> " . ADDPRODUCT_TEXT_CONFIRM_QUANTITY . "</td>";
			print "<td class='dataTableContent' align='center'><input type='submit' value='" . ADDPRODUCT_TEXT_CONFIRM_ADDNOW . "'>";

			if(isset($add_product_options)) {

				foreach($add_product_options as $option_id => $option_value_id)	{
					print "<input type='hidden' name='add_product_options[$option_id]' value='$option_value_id'>";
				}
			}

			print "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
			print "<input type='hidden' name='add_product_products_id' value='$add_product_products_id'>";
			print "<input type='hidden' name='step' value='5'>";
			print "</td>\n";
			print "</form></tr>\n";
		}
		
		print "</table></td></tr>\n";

} elseif ($action == 'add_non_inv') {
?>
 <tr><td><table class="tableBorder" border="0" width="100%" cellpadding="2" cellspacing="1" align="center">
 <form action="<?php echo $PHP_SELF;?>?oID=<?php echo $oID;?>&action=add_non_inv&step=2" method='POST'>
 <input type="hidden" name="posted" value="true">
 <tr>

 <td width="100%" class="main" colspan="2" align="center">
  <b>Add Non-Inventory Product</b>
 </td>
 </tr>
  <tr>
 <td width="20%" class="main"><b>Product Name:</b></td>
 <td width="80%"><input type="text" name="products_name" size="50" maxlength="255" value=""></td>
 </tr>

 <tr>
 <td width="20%" class="main"><b>Model Number:</b></td>
 <td width="80%"><input type="text" name="products_model" size="15" value=""></td>
 </tr>
 <tr>
 <td width="20%" class="main"><b>Price:</b></td>
 <td width="80%">$<input type="text" name="products_price" size="14" value="0.00"></td>

 </tr>
 <tr>
 <td width="20%" class="main"><b>Quantity:</b></td>
 <td width="80%">
 <input type="text" name="products_quantity" size="4" maxlegnth="4" value="1">
 (Note: Number to add to order)
 </td>
 </tr>
<?php
    $tax_query = tep_db_query("SELECT tax_class_id, tax_class_title FROM tax_class ORDER BY tax_class_id");
  
    if (tep_db_num_rows($tax_query) > 0) {
?>
 <tr>
  <td class="main"><b>Tax Class:</b></td>
  <td width="80%"><select name="products_tax_class_id"><option value="0">--None--</option>
<?php
  while ($row = tep_db_fetch_array($tax_query)) {
    echo '<option value="' . $row['tax_class_id'] . '">' . $row['tax_class_title'] . '</option>';
  }
?>
  </select></td>
 </tr>
<?php
    }
?>
 <tr>
 <td width="100%" class="main" colspan="2" align="center">
  <input type="submit" value="Add Product to Order">
 </td>
 </tr>
 </form>
 </table></td></tr>
<?php
  }
?>
    </table></td>

  </tr>
</table>
<?php
/*if(isset($_SESSION['cart'])) {
echo 'session name - '. session_name("cart").'<br>';
echo '$ _SESSION[\'cart\'] - ' . $_SESSION['cart']. ' - TRUE<br>';
echo 'php_ini session.save_path - '.ini_get('session.save_path').'<br>';
echo 'session save path - '. session_save_path() .'<br>';
echo 'DOCUMENT_ROOT - ' .$_SERVER['DOCUMENT_ROOT'].'<br>';
echo 'PHPSESSID - '.$PHPSESSID .'<br>';
echo 'session.save_handler - ' .ini_get('session.save_handler').'<br>';
print_r($_SESSION);
} else {
echo $_SESSION['cart']. ' - FALSE<br>';
} */
?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
