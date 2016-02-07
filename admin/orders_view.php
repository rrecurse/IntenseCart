<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();

	require(DIR_WS_CLASSES . 'order.php');
    $oID = tep_db_prepare_input($_GET['oID']);
    $order = new order($oID);

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

	if(MODULE_SHIPPING_UPSXML_RATES_ACCESS_KEY !='') { 
		require(DIR_WS_CLASSES . 'upsxml_labels.php');
	}


	if(MODULE_SHIPPING_USPS_STAMPS_STATUS == 'True') {
		require(DIR_WS_CLASSES . 'stampscom_labels.php');
		//$stamps = new USPSStampsLabelGen();
	}

		// # query default warehouse shipping info
		$warehouse_query = tep_db_query("SELECT * FROM ". TABLE_PRODUCTS_WAREHOUSE ." WHERE products_warehouse_id = '1'");

		if(tep_db_num_rows($warehouse_query) > 0) { 
			$warehouse = tep_db_fetch_array($warehouse_query);
			$warehouse_country_name = tep_db_result(tep_db_query("SELECT countries_name FROM ". TABLE_COUNTRIES ." WHERE countries_iso_code_2 = '".$warehouse['products_warehouse_country']."'"),0);
		}

	if(isset($_GET['download']) && $_GET['download'] == 'ups_customs_forms' && !empty($_GET['ups_track_num'])) {
	
		require(DIR_WS_CLASSES . 'pdftk-php.php');
		$pdfmaker = new pdftk_php;

		// # parse address from constant
		// # Zwaveproducts.com\r\n201-706-7190\r\n\r\nEmail: sales@zwaveproducts.com\r\n70 Commercial Ave.  - Warehouse A\r\nMoonachie NJ 07074
		$origin_address = explode("\r\n", STORE_NAME_ADDRESS);

		// # Define variables for all the data fields in the PDF form. You need to assign a column in the database to each field that you'll be using in the PDF.
		// # Example:
		// # $pdf_column = $data['column'];
		// # You can also format the MySQL data how you want here. One common example is formatting a date saved in the database. For example:
		// # $pdf_date = date("l, F j, Y, g:i a", strtotime($data['date']));

		$pdf_origin_taxid = $warehouse['products_warehouse_taxid'];
		$pdf_origin_contact_name = $warehouse['products_warehouse_contact'];
		$pdf_origin_company = $warehouse['products_warehouse_company'];
		$pdf_origin_address = $warehouse['products_warehouse_address1'] . "\n". $warehouse['products_warehouse_address2'];
		$pdf_origin_city_state = $warehouse['products_warehouse_city'] . ' / ' . $warehouse['products_warehouse_state'];
		$pdf_origin_postalcode_country = $warehouse['products_warehouse_zip'] . ' / ' . $warehouse['products_warehouse_country'];
		$pdf_origin_phone = $warehouse['products_warehouse_phone'];

		$pdf_destination_taxid = '';
		$pdf_destination_contact_name = $order->delivery['name'];
		$pdf_destination_company = $order->delivery['company'];
		$pdf_destination_address = $order->delivery['street_address'] . "\n" . $order->delivery['suburb'];
		$pdf_destination_city_state = $order->delivery['city'] . ' / ' . $order->delivery['state'];
		$pdf_destination_postalcode_country = $order->delivery['postcode'] . ' / ' . $order->delivery['country'];
		$pdf_destination_phone = $order->customer['telephone'];

		$pdf_billing_taxid = '';
		$pdf_billing_contact_name = $order->billing['name'];
		$pdf_billing_company = $order->billing['company'];
		$pdf_billing_address = $order->billing['street_address'] . "\n" . $order->billing['suburb'];
		$pdf_billing_city_state = $order->billing['city'] . ' / ' . $order->billing['state'];
		$pdf_billing_postalcode_country = $order->billing['postcode'] . ' / ' . $order->billing['country'];
		$pdf_billing_phone = $order->customer['telephone'];


		// # shipment_id based on tracking number
		$pdf_shipment_id = substr($_GET['ups_track_num'], 2, 11);
		$pdf_orders_id = $order->orderid;

		
		// # these incoterms are specified on page 10 of 
		// # http://global.ups.com/wp-content/themes/ups/assets/pdfs/UPS_Intl_Shipping_How-to_Guide.pdf
		// # default to FCA -  The seller delivers the goods export-cleared to the carrier stipulated by the buyer
		// # Buyer assumes all risks and costs associated with delivery of goods to final destination including all delivery fees to carrier 
		// # and any customs fees to import the product into a foreign country.

		$pdf_terms = 'FCA';

		$pdf_export_reason = 'Sale';
		$pdf_comments = '';

		$pdf_ups_track_num = $_GET['ups_track_num'];

		$pdf_date = date('m/d/Y', strtotime($order->info['date_purchased']));

		$pdf_order_subtotal = number_format(tep_db_result(tep_db_query("SELECT value FROM ". TABLE_ORDERS_TOTAL ." WHERE class = 'ot_subtotal' AND orders_id = '". $order->orderid ."'"), 0),2);
		$pdf_order_discount =  number_format(tep_db_result(tep_db_query("SELECT value FROM ". TABLE_ORDERS_TOTAL ." WHERE class = 'ot_coupon' AND orders_id = '". $order->orderid ."'"), 0),2);
		$pdf_orders_afterdiscount = number_format(($pdf_order_subtotal - $pdf_order_discount),2);
		$pdf_order_shipping =  number_format(tep_db_result(tep_db_query("SELECT value FROM ". TABLE_ORDERS_TOTAL ." WHERE class = 'ot_shipping' AND orders_id = '". $order->orderid ."'"), 0),2);

		$pdf_order_insurance = '';
		$pdf_order_othercharges = '0.00';

		$pdf_order_total = number_format($order->info['total'],2);
		$pdf_currency = $order->info['currency'];

		// # now generate list of products
		$pdf_products_qty = '';
		$pdf_products_unitofmeasure = '';
		$pdf_products_name = '';
		$pdf_products_harmonized_code = '';
		$pdf_products_co = '';
		$pdf_products_price = '';
		$pdf_products_final_price = '';
		$pdf_products_currency = '';

		foreach($order->products as $products) {

			$pdf_products_qty .= $products['qty'] . "\n\n";
			
			$pdf_products_unitofmeasure .= 'INDV' . "\n\n";

			// # truncate description of goods to fit
			$pdf_products_name .= (strlen($products['name']) > 40 ? (strlen($products['name']) > 100 ? substr($products['name'],0,100).'...' : substr($products['name'],0,100)) . "\n" : $products['name'] . "\n\n");

			// # Harmonized code lookup
			$pdf_products_harmonized_code .= (!empty($products['harmonized_code']) ? $products['harmonized_code'] : '8517') . "\n\n";

			$pdf_products_co .= (!empty($products['origin_country']) ? $products['origin_country'] : 'CN') . "\n\n";

			$pdf_products_price .= number_format($products['price'], 2, '.', '') . "\n\n";
			$pdf_products_final_price .= number_format(($products['final_price'] * $products['qty']), 2, '.', '') . "\n\n";
			$pdf_products_currency .= $pdf_currency . "\n\n";

		}

		$pdf_order_num_packages = 1;
		$pdf_order_total_weight = tep_db_result(tep_db_query("SELECT shipped_weight FROM ". TABLE_ORDERS_SHIPPED ." WHERE tracking_number = '".	$pdf_ups_track_num ."'"),0) . ' ' . strtolower(SHIPPING_UNIT_WEIGHT);

		$EEA = 'The exporter of the products covered by this document declares that except where otherwise clearly indicated these products are of EEA preferential origin.';
		$Invoice = 'I hereby certify that the information on this invoice is true and correct and the contents and value of this shipment is as stated above.';
		$NAFTA = 'I hereby certify that the goods covered by this shipment qualify as originating goods for purposes of preferential tariff treatment under the NAFTA.';

		$pdf_declaration_statement = $Invoice;
	
		// # $fdf_data_strings associates the names of the PDF form fields to the PHP variables you just set above. 
		// # In order to work correctly the PDF form field name has to be exact.You can use pdftk to discover the real names of your PDF form fields: 
		// # run "pdftk form.pdf dump_data_fields > form-fields.txt" to generate a report.

		$fdf_data_strings= array('origin_taxid' => $pdf_origin_taxid,
								 'origin_contact-name' => $pdf_origin_contact_name,
								 'origin_company' => $pdf_origin_company,
								 'origin_address' => $pdf_origin_address,
								 'origin_city-state' => $pdf_origin_city_state,
								 'origin_postalcode-country' => $pdf_origin_postalcode_country,
								 'origin_phone' => $pdf_origin_phone,
								 'destination_taxid' => $pdf_destination_taxid,
								 'destination_contact-name' => $pdf_destination_contact_name,
								 'destination_company' => $pdf_destination_company,
								 'destination_address' => $pdf_destination_address,
								 'destination_city-state' => $pdf_destination_city_state,
								 'destination_postalcode-country' => $pdf_destination_postalcode_country,
								 'destination_phone' => $pdf_destination_phone,
								 'billing_taxid' => $pdf_billing_taxid,
								 'billing_contact-name' => $pdf_billing_contact_name,
								 'billing_company' => $pdf_billing_company,
								 'billing_address' => $pdf_billing_address,
								 'billing_city-state' => $pdf_billing_city_state,
								 'billing_postalcode-country' => $pdf_billing_postalcode_country,
								 'billing_phone' => $pdf_billing_phone,
								 'ups_track_num' => $pdf_ups_track_num,
								 'shipment_id' => $pdf_shipment_id,
								 'orders_id' => $pdf_orders_id,
								 'terms' => $pdf_terms,
								 'export_reason' => $pdf_export_reason,
								 'comments' => $pdf_comments,
								 'date' => $pdf_date,
								 'order_subtotal' => $pdf_order_subtotal,
								 'order_discount' => $pdf_order_discount,
								 'orders_afterdiscount' => $pdf_orders_afterdiscount,
								 'order_shipping' => $pdf_order_shipping,
								 'order_insurance' => $pdf_order_insurance,
								 'order_othercharges' => $pdf_order_othercharges,
								 'order_total' => $pdf_order_total,
								 'currency' => $pdf_currency,
								 'order_num-packages' => $pdf_order_num_packages,
								 'order_total-weight' => $pdf_order_total_weight,
								 'products_qty' => $pdf_products_qty,
								 'products_unitofmeasure' => $pdf_products_unitofmeasure,
								 'products_name' => $pdf_products_name,
								 'products_harmonized_code' => $pdf_products_harmonized_code,
								 'products_co' => $pdf_products_co,
								 'products_price' => $pdf_products_price,
								 'products_final_price' => $pdf_products_final_price,
								 'products_currency' => $pdf_products_currency,
								 'declaration-statement' => $pdf_declaration_statement
								);
	
		// # See the documentation of pdftk-php.php for more explanation of these other variables.
		// # Used for radio buttons and check boxes
		// # Example: (For check boxes options are Yes and Off)
		// # $pdf_checkbox1 = "Yes";
		// # $pdf_checkbox2 = "Off";
		// # $pdf_checkbox3 = "Yes";
		// # $fdf_data_names = array('checkbox1' => $pdf_checkbox1,'checkbox2' => $pdf_checkbox2,'checkbox3' => $pdf_checkbox3,'checkbox4' => $pdf_checkbox4);
	
		$fdf_data_names = array(); // # Leave empty if there are no radio buttons or check boxes
	
		$fields_hidden = array(); // # Used to hide form fields

		// # Used to make fields read only - however, flattening the output with pdftk will in effect make every field read only. 
		// # If you don't want a flattened pdf and still want some read only fields, use this variable and remove the flatten flag near line 70 in pdftk-php.php
		$fields_readonly = array(); 


		// # Take each REQUEST value and clean it up for fdf creation

		foreach( $_REQUEST as $key => $value ) {
			// # Translate tildes back to periods
			$fdf_data_strings[strtr($key, '~', '.')]= $value;
		}

		// # Name of file to be downloaded
		$pdf_filename = 'UPS-Export-Document_order-'.$pdf_orders_id.'.pdf';
		// # Name/location of original, empty PDF form
	
		$pdf_original = "UPS-customs-commercial-invoice-with-feilds.pdf";
		// # Finally make the actual PDF file!
		$pdfmaker->make_pdf($fdf_data_strings, $fdf_data_names, $fields_hidden, $fields_readonly, $pdf_original, $pdf_filename);
		// # The end!
		exit();
	}


	$orders_statuses = array();
	$orders_status_array = array();
	
	$orders_status_query = tep_db_query("SELECT orders_status_id, 
												orders_status_name 
										 FROM " . TABLE_ORDERS_STATUS . " 
										 WHERE language_id = '" . (int)$languages_id . "'
										");

	while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    	$orders_statuses[] = array('id' => $orders_status['orders_status_id'],
        	                       'text' => $orders_status['orders_status_name']);

		$orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
	}

	$package_info_query = tep_db_query("SELECT COALESCE(SUM(op.products_weight * op.products_quantity),0) AS products_weight,
											   COALESCE(TRUNCATE(SUM(op.final_price * op.products_quantity),2),0.00) AS products_value,	
											   p.products_separate_shipping
									 		FROM ". TABLE_ORDERS ." o
											LEFT JOIN ". TABLE_ORDERS_PRODUCTS ." op ON op.orders_id = o.orders_id
											LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_id = op.products_id
										 WHERE o.orders_id = '" . $_GET['oID'] . "'
										 ");

	$package_info = tep_db_fetch_array($package_info_query);


	$action = (isset($_GET['action']) ? $_GET['action'] : '');

	if (tep_not_null($action)) {

		switch ($action) {

			case 'update_admin_comments':

				$oID = tep_db_prepare_input($_GET['oID']);
				$admin_comments = tep_db_prepare_input($_POST['admin_comments']);
				$order_updated = false;

				// # get current admin username
				$admin_user = (!empty($_COOKIE['admin_user']) ? $_COOKIE['admin_user'] : '');

				tep_db_query("INSERT INTO admin_comments
							  SET orders_id = '" . (int)$oID . "', 
							  date_added = NOW(), 
							  comments = '" . tep_db_prepare_input($admin_comments)  . "',
							  admin_user = '".$admin_user."'
							 ");

				$order_updated = true;
      
				if ($order_updated == true) {
					$messageStack->add_session('Admin Comments Updated', 'success');
					$messageStack->add('Admin Comments Updated', 'success');
				} else {
					$messageStack->add_session('Admin Comments Not Updated', 'warning');
					$messageStack->add('Admin Comments Not Updated', 'warning');
				}

				tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=edit'));
			
			break;


      case 'update_order':

        $oID = tep_db_prepare_input($_GET['oID']);
        $order = new order($oID);

		// # Checking for current status info
		$check_status_query = tep_db_query("SELECT o.customers_name, 
											     o.customers_email_address, 
											     o.orders_status, 
											     o.date_purchased, 
											     os.ship_carrier, 
											     os.tracking_number, 
											     os.ship_date 
										   FROM ".TABLE_ORDERS." o 
										   LEFT JOIN ".TABLE_ORDERS_SHIPPED." os ON o.orders_id = os.orders_id 
										   WHERE o.orders_id = '". $oID."'
										  ");

        $check_status = tep_db_fetch_array($check_status_query);


		$customers_id = tep_db_prepare_input($_GET['cID']);
        $status = tep_db_prepare_input($_POST['status']);
        $comments = tep_db_prepare_input($_POST['comments']);

		// ###################################

		//# Logic routine to grab array of tracking numbers from textarea
		//$ups_track_num = (!empty($_POST['ups_track_num'])) ? tep_db_prepare_input(serialize(explode("\n", trim($_POST['ups_track_num'])))) : '';
		// # In case we need to use this again to send in the emails for example.
		//$UPSTracking = (!empty($_POST['ups_track_num'])) ? explode("\n", $_POST['ups_track_num']) : '';

		// # assign the UPS tracking number
		// $UPSTracking = ($check_status['ship_carrier'] == 'UPS' && !empty($check_status['tracking_number'])) ? $check_status['tracking_number'] : '';

		// ########

        $usps_track_num = (!empty($_POST['usps_track_num']) && $_POST['usps_track_num'] != "\n") ? explode("\n", trim($_POST['usps_track_num'])) : '';
		$USPSTracking = (!empty($_POST['usps_track_num'])) ? explode("\n", $_POST['usps_track_num']) : '';

		// ########

        $fedex_track_num = (!empty($_POST['fedex_track_num'])) ? tep_db_prepare_input(serialize(explode("\n", trim($_POST['fedex_track_num'])))) : '';
		$FEDEXTracking = (!empty($_POST['fedex_track_num'])) ? explode("\n", $_POST['fedex_track_num']) : '';

		// ########

		// # Old routine
       	//$ups_track_num = tep_db_prepare_input($_POST['ups_track_num']);
		//$dhl_track_num = tep_db_prepare_input($_POST['dhl_track_num']);
    
		// ###################################

	    $order_updated = false;	


		// # Start UPS insert - loop through lines, explode on \n (new line)
        // # to-do: create support  for dynimcally added form feilds for broken up shipments.

		if(!$_POST['upsLabels'] && !is_array($_POST['ups_track_num'])) { 

			$ups_track_num = (!empty($_POST['ups_track_num'])) ? tep_db_prepare_input($_POST['ups_track_num']) : '';

			$UPSTracking = $ups_track_num;

			$package_weight = ($package_info['products_weight']) ? $package_info['products_weight']: '';
			$shipMethod = (!empty($_POST['update_shipping_method'])) ? tep_db_prepare_input($_POST['update_shipping_method']) : '';
			$theDigest = !empty($_POST['UPS_digest']) ? $_POST['UPS_digest'] : '';

			$theUPSquery= "INSERT IGNORE INTO orders_shipped
								SET orders_id = '".(int)$oID."', 
								ship_carrier = 'UPS', 
								tracking_number = '".$ups_track_num."', 
								ship_date = NOW(), 
								shipped_weight = '".$package_weight."',
								shipped_method = '".$shipMethod."',
								ship_type = 'unconfirmed',
								label_digest = '".$theDigest."'";

			if(!empty($ups_track_num)) {
				tep_db_query($theUPSquery);
			}
	
			if($status == 3) {
					// # create the array with passed methods and any overrides from default method passed.
					// # usage: array(PACKAGE TYPE CODE, SHIPPING METHOD)
					$method = array('02', $shipMethod, $package_weight);
 
					// # usage: UPSLabelGen(ORDER ID, ARRAY WITH METHODS, SUPPLIER ID);
					$upsLabel = new UPSLabelGen($_GET['oID'], $method, '1');
					$upsLabel->AcceptRequest($theDigest, $oID);
				} 		


		} else {

			foreach((array)$_POST['upsLabels'] as $labelInfo){

                $ups_track_num = ($labelInfo['tracknum']) ? $labelInfo['tracknum'] : tep_db_prepare_input($_POST['ups_track_num']);
       			$UPSTracking = $ups_track_num;

				if(!empty($ups_track_num)) {
					$package_weight = (!empty($labelInfo['weight'])) ? $labelInfo['weight'] : $package_info['products_weight'] + SHIPPING_BOX_WEIGHT;
					$shipMethod = (!empty($labelInfo['method'])) ? $labelInfo['method'] : tep_db_prepare_input($_POST['update_shipping_method']);
					$upstrack = preg_replace("/(\r?)\n/", "", trim($ups_track_num));
					$theDigest = !empty($labelInfo['digest']) ? $labelInfo['digest'] : '';

					if(!$theDigest){
						$retrieveDigest_query = tep_db_query("SELECT label_digest FROM ".TABLE_ORDERS_SHIPPED."
															  WHERE orders_id = '".$oID."'
															  AND tracking_number = '".$upstrack."'
															  AND ship_type = 'unconfirmed'
															  ");
						$retrieveDigest = tep_db_fetch_array($retrieveDigest_query);
 		
						if(tep_db_num_rows($retrieveDigest_query) > 0) $theDigest = $retrieveDigest['label_digest'];
						
					}

			
					// # write string to database to confirm label later
					$theUPSquery= "INSERT IGNORE INTO orders_shipped
									SET orders_id = '".(int)$oID."', 
									ship_carrier = 'UPS', 
									tracking_number = '".$upstrack."', 
									ship_date = NOW(), 
									shipped_weight = '".$package_weight."',
									shipped_method = '".$shipMethod."',
									ship_type = 'unconfirmed',
									label_digest = '".$theDigest."'
									";
	
					if (!empty($upstrack)) tep_db_query($theUPSquery);
		
					tep_db_query("UPDATE " . TABLE_ORDERS . " 
								  SET ups_track_num = '".$upstrack ."', 
								  last_modified = NOW() 
								  WHERE orders_id = '" . tep_db_input($oID) . "'
								");
	
					if($status == 3) {
						// # create the array with passed methods and any overrides from default method passed.
						// # usage: array(PACKAGE TYPE CODE, SHIPPING METHOD, WEIGHT)
						$method = array('02', $shipMethod, $package_weight);

						// # usage: UPSLabelGen(ORDER ID, ARRAY WITH METHODS, SUPPLIER ID);
						$upsLabel = new UPSLabelGen($_GET['oID'], $method, '1');
						$upsLabel->AcceptRequest($theDigest, $oID);
					} 			

				}
			}

		$order_updated = true;	
	}

	// # Start US Postal Service insert
	if (!empty($usps_track_num)) {
		$USPSTracking = explode("\n", $_POST['usps_track_num']);

		foreach($USPSTracking as $uspstrack) { 

			$uspstrack = preg_replace("#(\r?)\n#", "", trim($uspstrack)); 

			$theUSPSquery= "INSERT IGNORE INTO `orders_shipped` 
							SET orders_id = '".(int)$oID."', 
							ship_carrier = 'USPS', 
							tracking_number = '".$uspstrack."', 
							ship_date = NOW(), 
							ship_type = 'Full'";

				if (!empty($uspstrack)) tep_db_query($theUSPSquery);

		} // # end foreach

		tep_db_query("UPDATE " . TABLE_ORDERS . " 
					  SET usps_track_num = '" . tep_db_prepare_input($usps_track_num) . "', 
					  last_modified = NOW() 
					  WHERE orders_id = '" . tep_db_input($oID) . "'
					");

		$order_updated = true;
	} 

	// # Start FedEX insert
	if (!empty($fedex_track_num)) {
		$FEDEXTracking = explode("\n", $_POST['fedex_track_num']);

		foreach($FEDEXTracking as $fedextrack) { 

			$fedextrack = preg_replace("#(\r?)\n#", "", trim($fedextrack)); 

			$theFEDEXquery= "INSERT IGNORE INTO `orders_shipped` 
							 SET orders_id = $oID, 
							 ship_carrier = 'FedEx', 
							 tracking_number = '". $fedextrack."', 
							 ship_date = NOW(), 
							 ship_type = 'Full'
							";

			if(!empty($fedextrack)) tep_db_query($theFEDEXquery);
		}

          tep_db_query("update " . TABLE_ORDERS . " set fedex_track_num = '" . tep_db_prepare_input($fedex_track_num) . "', last_modified = NOW() where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
    }

		
	// # Start DHL insert
	if (!empty($dhl_track_num)) {

		tep_db_query("update " . TABLE_ORDERS . " set dhl_track_num = '" . tep_db_prepare_input($dhl_track_num) . "', last_modified = NOW() where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
	}

	// # Send email notifications
	$customer_notified = '0';

	if($_POST['notify'] == 'on') {

		$customer_notified = '1';

		if($check_status['orders_status'] != $status) {
			$order->setStatus($status);
			$order_updated = true;
			$thestatus = sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status])."\n\n";
		} elseif($check_status['orders_status'] == $status){
			$thestatus = '';
		}

		//if(!empty($ups_track_num)) $ups_track_num = (string)$ups_track_num;

		if(!empty($ups_track_num) || !empty($fedex_track_num) || !empty($usps_track_num) || !empty($dhl_track_num)) {	

			if($_POST['notify_comments'] == 'on') {
				if(!empty($comments) || $comments != '') {
					$comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)  ."\n\n";
				} else {
					$comments = '';
				}
			}
						
			if(empty($ups_track_num)) {
				$ups_text = '';
				$ups_track_link="";
			} else {
				$ups_text = 'UPS: ';
				$ups_track_link = '';
				if(is_array($ups_track_num)) { 
					foreach($ups_track_num as $key => $value) { 
						$ups_track_link .= '<a href="http://wwwapps.ups.com/WebTracking/processInputRequest?HTMLVersion=5.0&tracknums_displayed=5&TypeOfInquiryNumber=T&loc=en_US&InquiryNumber1='.$value.'&AgreeToTermsAndConditions=yes">'.$value.'</a>' . "\n";
						$ups_track_num = $value . "\n";	
					}
				} else { 
					$ups_track_link .= '<a href="http://wwwapps.ups.com/WebTracking/processInputRequest?HTMLVersion=5.0&tracknums_displayed=5&TypeOfInquiryNumber=T&loc=en_US&InquiryNumber1='.$UPSTracking.'&AgreeToTermsAndConditions=yes">'.$UPSTracking.'</a>';
				}
			}
	
			if(empty($fedex_track_num)) {
				$fedex_text = '';
			} else {
				$fedex_text = 'FedEx: ';
				$fedex_track_num = $FEDEXTracking . "\n";
				$fedex_track_link = '';
			}

			if(empty($usps_track_num)) {
				$usps_text = '';
			} else {
				$usps_text = 'USPS: ';
				if(is_array($usps_track_num)) { 
					foreach($usps_track_num as $key => $value) { 
						$usps_track_link .= '<a href="https://tools.usps.com/go/TrackConfirmAction.action?tRef=fullpage&tLc=1&text28777=&tLabels='.$value.'">'.$value.'</a>' . "\n";
						$usps_track_num = $value . "\n";	
					}
				} else { 
					$usps_track_link .= '<a href=""https://tools.usps.com/go/TrackConfirmAction.action?tRef=fullpage&tLc=1&text28777=&tLabels='.$USPSTracking.'">'.$USPSTracking.'</a>';
				}

			}

			if(empty($dhl_track_num)) {
				$dhl_text = '';
			} else {
				$dhl_text = 'DHL: ';
				$dhl_track_num = $dhl_track_num . "\n";
				$dhl_track_link = '';
			}
	
			// # If Amazon create alternative links to their amazon account
			$payMeth_query = tep_db_query("SELECT method FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$oID."'");
			$payMeth = tep_db_fetch_array($payMeth_query);

			if($payMeth['method'] == 'payment_amazonSeller') { 

				$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " 
												   WHERE orders_id = '".$oID."' 
												   AND method = 'payment_amazonSeller' LIMIT 1
												 ");

				$theAmazonoID = tep_db_fetch_array($amazonOrder_query);


				$email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' https://www.amazon.com/gp/css/summary/edit.html?ie=UTF8&orderID='.$theAmazonoID['ref_id']."\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']). "\n\n" . $thestatus . EMAIL_TEXT_TRACKING_NUMBER . "\n" . $ups_text . $ups_track_link . $fedex_text . $fedex_track_num . $usps_text . $usps_track_num . $dhl_text . $dhl_track_num . "\n\n" . $comments . "\n" . EMAIL_TEXT_FOOTER;


			} else { // # Not Amazon order, construct email as normal
//error_log(print_r($usps_track_num,1));
				$email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' <a href="'.tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL').'">My Order History</a>'."\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']). "\n\n" . $thestatus . EMAIL_TEXT_TRACKING_NUMBER . "\n" . $ups_text . $ups_track_link . $fedex_text . $fedex_track_num . $usps_text . $usps_track_num . $dhl_text . $dhl_track_num . "\n\n" . $comments . "\n" . EMAIL_SHIPPING_NOTE . "\n\n" .  EMAIL_TEXT_FOOTER;
			}	

			tep_mail($check_status['customers_name'], $check_status['customers_email_address'], sprintf(EMAIL_TEXT_SUBJECT_UPDATE, $oID), $email, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS);

			// # Send a copy to the store admin
			tep_mail($check_status['customers_name'], STORE_OWNER_EMAIL_ADDRESS, sprintf(EMAIL_TEXT_SUBJECT_UPDATE, $oID), $email, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS);


		} else {	

			if($_POST['notify_comments'] == 'on') {
				if(!empty($comments) || $comments != '') {
					$comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)  ."\n\n";
				} else {
					$comments = '';
				}
			}

			// # If Amazon create alternative links to their amazon account
			$payMeth_query = tep_db_query("SELECT method FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$oID."'");
			$payMeth = tep_db_fetch_array($payMeth_query);
	
			if($payMeth['method'] == 'payment_amazonSeller') { 

				$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " 
												   WHERE orders_id = '".$oID."' 
												   AND method = 'payment_amazonSeller' LIMIT 1
												  ");

				$theAmazonoID = tep_db_fetch_array($amazonOrder_query);
	
				$email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' https://www.amazon.com/gp/css/summary/edit.html?ie=UTF8&orderID='.$theAmazonoID['ref_id']. "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $comments . $thestatus . "\n" . EMAIL_TEXT_FOOTER;

			} else {

				$email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' <a href="'.tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') .'">My Order History</a>'. "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $comments . $thestatus . "\n" . EMAIL_TEXT_FOOTER;

			}

			tep_mail($check_status['customers_name'], $check_status['customers_email_address'], sprintf(EMAIL_TEXT_SUBJECT_UPDATE, $oID), $email, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS);

			// # Send a copy to the store admin
			tep_mail($check_status['customers_name'], STORE_OWNER_EMAIL_ADDRESS, sprintf(EMAIL_TEXT_SUBJECT_UPDATE, $oID), $email, STORE_NAME, STORE_OWNER_EMAIL_ADDRESS); 
	
		}
	
	} 

if(!isset($_POST['notify'])) { 

	if($check_status['orders_status'] != $status) {
		$order->setStatus($status);
		$order_updated = true;
	} elseif($check_status['orders_status'] == $status){
		$order_updated = false;
	}

}
	// # Update order status history table with current status
	$comments = strip_tags(tep_db_input(trim(str_replace(array('Comments: ','\n\n'), '', $comments))));

	// # get current admin username
	$admin_user = (!empty($_COOKIE['admin_user']) ? $_COOKIE['admin_user'] : '');

	tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " 
				  SET orders_id = '" . (int)$oID . "',
				  orders_status_id = '" . tep_db_input($status) . "',
				  date_added = NOW(), 
				  customer_notified = '".$customer_notified."', 
				  comments = '" .$comments. "',
				  admin_user = '".$admin_user."'
				");	
			
	// # Update Message Stack
	if ($order_updated == true) {
		$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
		$messageStack->add(SUCCESS_ORDER_UPDATED, 'success');
	} else {
		$messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
		$messageStack->add(WARNING_ORDER_NOT_UPDATED, 'warning');
	}

	// # Redirect that sends back to order list - nullifies message stack messages when redirect
	tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=edit'));

break;

case 'deleteconfirm':
        $oID = tep_db_prepare_input($_GET['oID']);

        tep_remove_order($oID, $_POST['restock']);

        tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('oID', 'action'))));
break;
}

  }

  if (($action == 'edit') && isset($_GET['oID'])) {
    $oID = tep_db_prepare_input($_GET['oID']);
	$customers_id = tep_db_prepare_input($_GET['cID']);
    $orders_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
    $order_exists = true;
    if (!tep_db_num_rows($orders_query)) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    }
  }
    $order = new order($oID);
	$current_shipping_method = ($order->info['shipping_method']) ? $order->info['shipping_method'] : NULL;

	// # grab the default combined weights for everything on the order
	// # for default label generation without override.
	$products_weight_query = tep_db_query("SELECT op.products_quantity AS qnty, 
										   (op.products_weight * op.products_quantity) AS weight
										   FROM ".TABLE_ORDERS_PRODUCTS." op
										   WHERE op.orders_id ='".$_GET['oID']."'
										  ");
	$theWeights = 0;
	while($products_weight = tep_db_fetch_array($products_weight_query)) {
		$theWeights += (float) number_format($products_weight['weight'],2);
	}

	$theWeights = $theWeights + SHIPPING_BOX_WEIGHT;
?>
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Order #<?php echo $_GET['oID'] ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/jquery.lightbox_me.js"></script>

<script type="text/javascript" src="js/tabber.js"></script>
<link rel="stylesheet" href="js/tabber.css" TYPE="text/css" MEDIA="screen">

<script type="text/javascript">
document.write('<style type="text/css">.tabber{display:none;}<\/style>');
</script>
<script type="text/javascript">

function contentChanged() {
  top.resizeIframe('myframe');
}
</script>

<script type="text/javascript">

var allowCalc=1;

function reloadState(sec) {
jQuery.noConflict();

	if (!allowCalc) {
		var thehtml = '<input type="text" size="25" name="'+sec+'_state_null" value="'+jQuery('#'+sec+'_state').val()+'" onChange="setState(\'#\'+\''+sec+'\',this.value)">';
		jQuery('#'+sec+'_state_box').html(thehtml);
    return;
  }
  if (jQuery('#'+sec+'_country').val() == '' || jQuery('#'+sec+'_postcode').val() == '') {
		jQuery('#'+sec+'_state_box').html("Please enter postcode");
  } else {
		jQuery('#'+sec+'_state_box').html("Loading...");


	jQuery('#'+sec+'_state_box').load('/admin/state_dropdown.php', jQuery.param({
			sec: sec,
			d: jQuery('#'+sec+'_state').val(),
			postal: jQuery('#'+sec+'_postcode').val(),
			country: jQuery('#'+sec+'_country').val()
			}), function(){
				contentChanged();	
		});
  }
}

function setState(sec,val) {
jQuery.noConflict();
  jQuery('#'+sec+'_state').val(val);
  if (sec=='ship') reloadShipping();
}


function reloadShipping(flg,overide_ac_wt) {

jQuery.noConflict();

	jQuery("#theMethodUsed").hide();

	if (!allowCalc) return;

	var rc_fld = jQuery('#recalculate_shipping');

	if (rc_fld && flg != null) {
		rc_fld.checked = flg;
	}

<?php if($current_shipping_method) { ?>

	if (rc_fld && !rc_fld.checked) {

		jQuery('#genLabelButton').fadeIn();
   		jQuery('#shipping_box').html("");
   		jQuery('#UPS_digest').val('');
		jQuery('#ups_track_num').attr("type","text").val('');
		jQuery('#shipping_method').val('<?php echo $current_shipping_method?>');
		jQuery('#weight_override_offExpand').attr("name","weight_override").val('<?php echo $theWeights?>');

		jQuery('#UPS_options').html('<input type="hidden" name="package_type" id="package_type" value=""><input type="hidden" name="delivery_confirmation" id="delivery_confirmation"><input type="hidden" name="insurance_value" id="insurance_value" value=""><input type="hidden" name="UPS_notification" id="UPS_notification"><input type="hidden" name="package_height" id="package_height" value=""><input type="hidden" name="package_width" id="package_width" value=""><input type="hidden" name="package_length" id="package_length" value=""><input type="hidden" name="print_type" id="print_type">">');

		return false;

		if(flg != null) {

<?php $totals_query = tep_db_query("SELECT * FROM ". TABLE_ORDERS_TOTAL ." 
									WHERE orders_id = '" . (int)$oID . "' 
									AND class='ot_shipping'
									");
	$order->totals = array();

	while ($t = tep_db_fetch_array($totals_query)) {

		echo "
			setShipping('".$current_shipping_method."','".addslashes($t["title"])."','".($t["value"]+0)."');
			";

	} // # end while
?>

		}

    	return;
	}

<?php 
} // # end if($current_shipping_method)
?>

	if(jQuery('#ship_country').val() == '' || jQuery('#ship_postcode').val() == '') {
		jQuery('#shipping_box').html("Please select country and postcode");
	} else {
		jQuery("#loadimage").fadeIn();
		var p_list=new Array();
		var ac_wt = <?php echo SHIPPING_BOX_WEIGHT?>;
		var overide = false;

		for (var i=0; orderProducts[i]; i++) {
			if (typeof overide_ac_wt === 'undefined') { 
				ac_wt+=(orderProducts[i].qty * orderProducts[i].weight);
			} else {
				var	overide = true;
				ac_wt=parseFloat(overide_ac_wt);
				if(ac_wt <= 0) ac_wt=0.00;
			}

			if ((orderProducts[i].free_shipping == 0) && (orderProducts[i].qty > 0)) {
				if(orderProducts[i].separate_shipping>0) { 
					p_list[p_list.length]=( (orderProducts[i].qty == 1 ) ? '' : orderProducts[i].qty+'x')+orderProducts[i].weight;
				} else {
					if(!overide) { 
						ac_wt+=(orderProducts[i].qty*orderProducts[i].weight);
					} else { 
						ac_wt=parseFloat(overide_ac_wt);
					}
				}	
			}
		}

	if (ac_wt>0) p_list.unshift(ac_wt);

	jQuery('#shipping_box').load('/admin/upsxml_labels.php', jQuery.param({
			weights: p_list.join(','),
			zip: jQuery('#ship_postcode').val(),
			cnty: jQuery('#ship_country').val(),
			state: jQuery('#ship_state').val(),
			d: jQuery('#shipping_method').val(),	
			oID: '<?php echo $_GET['oID']?>',
			package_type: jQuery('#package_type').val(),
			delivery_confirmation: jQuery('#delivery_confirmation:checked').val(),
			insurance_value: jQuery('#insurance_value').val(),
			package_height: jQuery('#package_height').val(),
			package_width: jQuery('#package_width').val(),
			package_length: jQuery('#package_length').val()	
			}), function(response){	
				jQuery("#genLabelButton").fadeOut('fast');
				jQuery('#ups_track_num').attr("type","hidden");
				jQuery('#weight_override_offExpand').removeAttr('name')
				jQuery("#UPS_options").replaceWith('<div id="UPS_options"></div>');
				contentChanged();

			var error = response[0].search(/Error/ig);
			if(error > 0) alert('UPS API Response: \n'+response[0]);
		}); 
  }
}



function setShipping(key,title,value) {

jQuery.noConflict();

	if (jQuery('#shipping_method')) {
		jQuery('#shipping_method').val(key);
	}
  if (!allowCalc) return;
	jQuery('#loadimage').fadeIn();

	genUPSLabel('<?php echo $_GET['oID']?>', key);

}


function genUPSLabel(oID, method) {
jQuery.noConflict();

	jQuery('#label_box').load('/admin/upsxml_labels.php', jQuery.param({
			weights: jQuery('input[name=weight_override]').val(),
			oID: oID,
			genLabel: '1',
			shipping_method: method,
			package_type: jQuery('#package_type').val(),
			delivery_confirmation: jQuery('#delivery_confirmation:checked').val(),
			insurance_value: jQuery('#insurance_value').val(),
			UPS_notification: jQuery('#UPS_notification:checked').val(),
			package_height: jQuery('#package_height').val(),
			package_width: jQuery('#package_width').val(),
			package_length: jQuery('#package_length').val(),
			print_type: jQuery('#print_type').val(),
			warehouse: jQuery('#warehouse').val(),
			}), function(response){
				contentChanged();
			var data = response.split("<br><br><br>");
			jQuery("#UPS_digest").val(data[0]);
			jQuery("#ups_track_num").val(data[1]);
			jQuery("#loadimage").hide();

			// # set the shipping method hidden feild 
			jQuery("#shipping_method").val(method);

			if(!jQuery('#recalculate_shipping').is(':checked') && data[2]) { 
				jQuery("#theMethodUsed").html('Method: ' + data[2]).show();
			} else {
				jQuery("#theMethodUsed").hide();
			}

			var error = data[0].search(/Error/ig);
			if(error > 0) alert('UPS API Response: \n'+data[0]);
		}).hide(); 
}


 var rowNum = 0;
function addRow(frm) {
		var weight = frm.weight_override.value;
		var tracknum = frm.ups_track_num.value;
		var upsdigest = frm.UPS_digest.value;
		var method = frm.update_shipping_method.value;
		methodStripped = method.replace('upsxml_UPS',''); 
     
		rowNum ++;
		var row = '<div id="rowNum'+rowNum+'" class="newLabel">'
					+ '<input type="hidden" name="upsLabels['+rowNum+'][tracknum]" value="'+tracknum+'"> ' + tracknum
					+ '<br>'
					+ '<input type="hidden" name="upsLabels['+rowNum+'][method]" value="'+method+'"> ' + methodStripped
					+ ' - '
					+ '<input type="hidden" name="upsLabels['+rowNum+'][weight]" value="'+weight+'"> ' + weight + ' lbs. '
					+ '<input type="hidden" name="upsLabels['+rowNum+'][digest]" value="'+upsdigest+'">'
					+ '<input type="button" onclick="removeRow('+rowNum+');" style="background:transparent url(images/delete.png) no-repeat 0 0; background-size: 22px 22px; width:22px; height:22px; border:0">'
					+ '</div>';
     
		if(tracknum) {
			jQuery('#LabelRows').append(row);
		} else {
			alert('You must first select a weight then method to generate a new label');
		}
		//jQuery('#LabelRows').append(row);
		frm.ups_track_num.value = '';
		frm.weight_override.value = '';
		frm.UPS_digest.value = '';
		frm.update_shipping_method.value = '';
    }



function removeRow(rnum) {
  jQuery('#rowNum'+rnum).remove();
}



function reloadQuotes(package_type, delivery_confirmation, insurance_value, package_height, package_width, package_length) {
jQuery.noConflict();

	package_type = (package_type !='') ? package_type : jQuery('#package_type').val();
	delivery_confirmation = (delivery_confirmation !='') ? delivery_confirmation : jQuery('#delivery_confirmation:checked').val();
	insurance_value = (insurance_value !='') ? insurance_value : jQuery('#insurance_value').val();
	package_height = (package_height !='') ? package_height : jQuery('#package_height').val();
	package_width = (package_width !='') ? package_width : jQuery('#package_width').val();
	package_length = (package_length !='') ? package_length : jQuery('#package_length').val();

	if(jQuery('#weight_override').val() > 0) {
		var weight = jQuery('#weight_override').val();
	} else {
		alert('You must select a weight above zero.');
		var weight ='<?php echo (SHIPPING_BOX_WEIGHT > 0 ? SHIPPING_BOX_WEIGHT : '0.01')?>';
		jQuery('#weight_override').val(weight);

		//return false;
	}

	jQuery("#loadimage").fadeIn();

	jQuery('#shipping_quotes').load('/admin/upsxml_labels.php #shipping_quotes', jQuery.param({
			weights: weight,
			zip: jQuery('#ship_postcode').val(),
			cnty: jQuery('#ship_country').val(),
			state: jQuery('#ship_state').val(),
			d: jQuery('#shipping_method').val(),
			oID:'<?php echo $_GET['oID']?>',
			shipping_method: jQuery('#shipping_method').val(),
			package_type: package_type,
			delivery_confirmation: delivery_confirmation,
			insurance_value: insurance_value,
			UPS_notification: jQuery('#UPS_notification:checked').val(),
			package_height: package_height,
			package_width: package_width,
			package_length: package_length,
			print_type: jQuery('#print_type').val(),
			}), function(response, status, xhr){
				jQuery("#loadimage").hide();
			
				var error = response[0].search(/Error/ig);
				if(error > 0) alert('UPS API Response: \n'+response[0]);

				genUPSLabel('<?php echo $_GET['oID']?>', shipping_method);

				 var content = jQuery(response).find("#shipping_quotes");
				jQuery("#shipping_quotes").replaceWith(content);

				jQuery("#weightText").empty().append(parseFloat(weight)+" lbs");

				contentChanged();
			}); 
}



function recoverLabel(oID,tracking) {

	jQuery('#loadimage').fadeIn();

	jQuery('#label_box').load('/admin/upsxml_labels.php', jQuery.param({	
			oID: oID,
			tracking: tracking,
			recoverLabel: '1'
			}), function(response){	
				contentChanged();	
				jQuery("#loadimage").hide();
		var therecover = response;
		therecover = therecover.replace("<br><br><br>", "");
		alert('UPS API Response: \n'+therecover);
		}).hide(); 
}


function voidLabel(oID,tracking) {
jQuery.noConflict();

	if(confirm('Are you sure you want to Void this label? \n\nThis cannot be undone...')) { 

	jQuery('#loadimage').fadeIn();

	jQuery('#label_box').load('/admin/upsxml_labels.php', jQuery.param({	
			oID: oID,
			tracking: tracking,
			voidLabel: '1'
			}), function(response){
				contentChanged();
				jQuery("#loadimage").hide();
		var thevoid = response;
		thevoid = thevoid.replace("<br><br><br>", "");
		alert('UPS API Response: \n'+thevoid);
		location.reload(); 	
		}).hide(); 
	} else { 
		jQuery("#loadimage").hide();
		alert('Nothing Changed - Label NOT Voided!');
	}
}

function printUPSLabel(oID,tracking,theLabel) {
jQuery.noConflict();

	jQuery('#loadimage').fadeIn();

theLabel = theLabel.replace("data:image/gif;base64,","");

	jQuery('#qz').load('/admin/upsxml_labels.php', jQuery.param({	
			oID: oID,
			tracking: tracking,
			printLabel: '1'
			}), function(response){

		// # Start jzebra / qz-print logic
		
		jQuery('#qz').show();

	 if (qz != null) {
               //qz.findPrinter("Canon MX450 series Printer");
				 qz.findPrinter();
               while (!qz.isDoneFinding()) {
                    // Note, endless while loops are bad practice.
               }

             
               // No suitable printer found, exit
               if (qz.getPrinter() == null) {
                   alert("Could not find a suitable printer for printing an image.");
                   return;
               }

               // Optional, set up custom page size.  These only work for PostScript printing.
               // setPaperSize() must be called before setAutoSize(), setOrientation(), etc.

				//qz.setPaperSize("210mm", "297mm");  // A4
				qz.setPaperSize("8.5in", "11.0in");  // US Letter
				qz.setAutoSize(true);
				qz.appendImage('data:image/png;base64,'+theLabel);
	    }

	    monitorAppending2();

		//jQuery.getScript("js/directPrint.js");

		// # END jzebra / qz-print logic
				contentChanged();
				jQuery("#loadimage").hide();
		}).hide(); 
}

var printed = false;
function printUPSThermalLabel(oID,tracking,theLabel) {
jQuery.noConflict();

	jQuery('#loadimage').fadeIn();

    if (printed) {
		alert('You have already sent this to the printer.\n If it does not print, refresh page and try again.');
		jQuery("#loadimage").hide()
        return;
    }
	printed = true;
	jQuery('#qz').load('/admin/upsxml_labels.php', jQuery.param({	
			oID: oID,
			tracking: tracking,
			printLabel: '1'
			}), function(response){

		// # Start jzebra / qz-print logic
		
		jQuery('#qz').show();

	 if (qz != null) {
		printed = true;
		qz.findPrinter("Star");
		//qz.findPrinter();
        
		while (!qz.isDoneFinding()) {
        // # Note, endless while loops are bad practice.
        }
               // No suitable printer found, exit
               if (qz.getPrinter() == null) {
					alert("Could not find a suitable printer for printing a Thermal Label.\n Please regenerate label using Image(GIF) as Label type.");
					jQuery("#loadimage").hide();
					printed = true;
                   return;
               }

				qz.append64(theLabel);
				qz.print();
				printed = true;
	    }

	    monitorAppending2();
		// # END jzebra / qz-print logic

			contentChanged();
			jQuery("#loadimage").hide();
		}).hide();
			printed = false;
}

function monitorPrinting() {
	
	if (qz != null) {
		if (!qz.isDonePrinting()) {
			window.setTimeout('monitorPrinting()', 100);
		} else {
			var e = qz.getException();
			alert(e == null ? "Printed Successfully" : "Exception occured: " + e.getLocalizedMessage());
			qz.clearException();
		}
	} else {
		alert("Applet not loaded!");
	}
}

function monitorAppending2() {
		
	if (qz != null) {
	   if (!qz.isDoneAppending()) {
	      window.setTimeout('monitorAppending2()', 100);
	   } else {
			qz.printPS(); // Don't print until all of the image data has been appended
			monitorPrinting();
		}
	} else {
		alert("Applet not loaded!");
	}
}


var orderProducts=new Array();

jQuery(document).ready( function() {
jQuery.noConflict();

var	printed = false;

<?php $package = tep_db_query("SELECT label_digest FROM ".TABLE_ORDERS_SHIPPED." WHERE orders_id = '".$_GET['oID']."'");
	if(tep_db_num_rows($package) > 0) echo ' jQuery("#shiptab").addClass("tabbertabdefault")'; ?>;

	jQuery('#genLabelButton').click(function() {
		genUPSLabel('<?php echo $_GET['oID']?>', '<?php echo $order->info['shipping_method']?>');
		jQuery("#loadimage").fadeIn();
	});

	jQuery('.theLabel').click(function() {
		if (printed) return;
		printed = true;

		var tracking = jQuery(this).attr('id');
		var theLabel = jQuery(this).attr('src');
		printUPSLabel('<?php echo $_GET['oID']?>', tracking, theLabel);
		
	});

	jQuery('.theThermalLabel').click(function() {
		if (printed) return;
		printed = true;

		var tracking = jQuery(this).attr('id');
		var theLabel = jQuery(this).attr('data-thelabel');
		printUPSThermalLabel('<?php echo $_GET['oID']?>', tracking, theLabel);
		
	});


	// # detect if tracking feilds are empty and warn if so
	jQuery("#status").change(function(){

		var status = jQuery(this).find("option:selected").attr("value");

		switch (status){

			case "0":
				if(!confirm('Are you certain you want to mark as Canceled?')){ 
					jQuery("#status").val(<?php echo $order->info['orders_status']?>);
				}	
			break;			

			case "3":

		    // textarea or input is empty or contains only white-space

			var ups_track_num = jQuery.trim(jQuery("#ups_track_num").val());
			var fedex_track_num = jQuery.trim(jQuery("#fedex_track_num").val());
			var usps_track_num = jQuery.trim(jQuery("#usps_track_num").val());

			if(!ups_track_num && !fedex_track_num && !usps_track_num) {

				if(jQuery('[name="payment_method"]').val() == 'payment_amazonSeller') {

					alert('You may not flag Amazon orders as shipped without first entering a Tracking Number');
					jQuery("#status").val(<?php echo $order->info['orders_status']?>);
					
				} else {

					if(!confirm('Are you sure you want to mark as Shipped with no label?')){ 
						jQuery("#status").val(<?php echo $order->info['orders_status']?>);
					}
				}
				
			} 
			break;
		}

	});

 });





</script>

<style type="text/css">

.infoTable tr:nth-child(even) {
    background-color: #EBF1F5;
}

.up {
cursor:pointer;
width:11px;
height:11px;
background:transparent url(images/plusmin.gif) no-repeat 0 0;
}
.down {
cursor:pointer;
width:11px;
height:11px;
background:transparent url(images/plusmin.gif) no-repeat 0 -11px;
}
.theLabel { 
	margin:0 2px;
}

.theThermalLabel { 
	margin:0 2px;
}

input[type="text"][disabled] {
	color: black;
	background-color:#FBE6A3;
	border:0;	
}

.existingLabel {
	background-color:#FFF; 
	border:1px dashed #999; 
	border-radius:4px; 
	margin-top:5px;
	white-space:nowrap;
}

.existingLabel td {
	padding:5px;
	line-height:18px;
}

.newLabel {
	background-color:#FFFFC4; 
	border:1px dashed #999; 
	border-radius:4px; 
	padding:5px;
	margin-top:5px;
}

#LabelRows {
	min-width:184px;
	white-space:nowrap;
}

#quoteTable_UPS {

}

#shipOptions {
	cursor:pointer;
	color:#999;
	font: normal 10px arial;
	margin:0 5px;
}

.void-label {
	display:inline-block; 
	background:transparent url(images/void-label.png) no-repeat 0 -23px; 
	width:90px; 
	height:23px;
	cursor:pointer;
}

.void-label:hover {
	background:transparent url(images/void-label.png) no-repeat 0 0; 
}

.print-label {
	display:inline-block; 
	background:transparent url(images/print-label.png) no-repeat 0 -23px; 
	width:90px; 
	height:23px;
	margin-right:1px;
	cursor:pointer;
}

.print-label:hover {
	background:transparent url(images/print-label.png) no-repeat 0 0; 
}

.print-export-docs {
	display:inline-block; 
	background:transparent url(images/print-export-docs.png) no-repeat 0 -23px; 
	width:185px; 
	height:23px;
	cursor:pointer;
}

.print-export-docs:hover {
	background:transparent url(images/print-export-docs.png) no-repeat 0 0; 
}

@media print {
	body {
		page-break-after:avoid !important;
		width:99%;
		height: 99%;
		margin: 0 auto;
	}

	.hide_print { 
		display:none;
		overflow:hidden;
	}

	.tabOdd { 
		background-color:transparent !important;
	}
}
</style>
</head>
<body style="background-color:transparent; margin:0">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" colspan="2" valign="top">
<?php
  if (($action == 'edit') && ($order_exists == true)) {
	
	$order = new order($oID);
    $payments = $order->getPayments();

// # if order is from Amazon, color amazon-orange
if(!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') { 

	if (stripos($order->info['orders_source'],'Amazon-FBA') !== false) {
		$sourceStyle = '#86BBBB';
	} else { 
		$sourceStyle = '#EC994F';
	}

} else { 
	// # if customers group is vendor then color GREEN if not standard blue
	$sourceStyle = ($customers_group > 1) ? '#6EAC6D' : '#6295FD';
}
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td style="padding:2px 5px 5px 4px;"><table width="100%" align="center" style="background-color:#FFFFFF;">
          <tr><td>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="background-color:#FFF; border-collapse:collapse;">
          <tr>
            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF;">
<?php
	  $customer_query = tep_db_query("select * FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '" . $order->customer['id'] . "'");
      $customer_check = tep_db_fetch_array($customer_query);
      $customers_group = $customer_check['customers_group_id'];

	echo '<tr>
				<td style="border-top solid 1px #8CA9C4; height:21px; background-color:'.$sourceStyle.'; font:bold 13px arial;color:#FFFFFF;">&nbsp; Billing Information:</td></tr>';
			
		$comp_query = tep_db_query("select * FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . $order->customer['id'] . "'");
		$comp_check = tep_db_fetch_array($comp_query);
		$comp = $comp_check['entry_company'];
?>                      
                      <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px Tahoma; color:#0B2D86">&nbsp; Attn:&nbsp; <a href="customers.php?cID=<?php echo $order->customer['id']; ?>&amp;action=edit"><?php echo $order->billing['name']; ?></a></td>
                          </tr>
<?php if($customers_group > 1) echo '<tr><td style="padding:5px; background-color:#FFFFC6; font:bold 11px Tahoma; color:#CC6600">&nbsp;Vendor: <a href="customers.php?cID='. $order->customer['id'].'&amp;action=edit">'.$comp.'</a></td></tr>';
?></table></td>
                    </tr>
		    <tr>
                      <td colspan="2" style="padding-top:1px; background-color:#F0F5FB;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
		      <?php echo (!empty($order->billing['company'])) ? '<tr><td class="tableinfo_orders">'.$order->billing['company'].'</td></tr>' : ''?> 
			  <tr><td class="tableinfo_orders"><?php echo $order->billing['street_address']?><?php echo $order->billing['suburb'] ? ', '.$order->billing['suburb'] : ''?></td></tr>
			  <tr><td><?php echo $order->billing['city']; ?>, <?php echo $order->billing['state']?></td></tr>
			  <tr><td><?php echo $order->billing['postcode']; ?> - <?php echo $order->billing['country']; ?></td></tr>
			  <tr><td>Phone: <?php echo $order->customer['telephone']; ?></td></tr>
			  <?php if(!empty($order->customer['fax'])) echo '<tr><td>Fax: '.$order->customer['fax'].'</td></tr>';?>
			  <tr><td>Email:&nbsp;
<?php 
// # trunacte email address if longer then 29 characters to fit in our HTML table
$customers_email = (strlen($order->customer['email_address']) > 29) ? substr($order->customer['email_address'],0, 29) . "..." : $order->customer['email_address'];
				echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $customers_email . '</u></a>'; ?></td></tr>
                        </table></td>
                    </tr>
            </table></td>
<td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:<?php echo $sourceStyle ?>;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Shipping Information:</span></td>
                      
                     </tr>
                    <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px arial; color:#0B2D86">&nbsp; Attn:&nbsp; <?php echo $order->delivery['name']; ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td style="padding-top:1px; background-color:#F0F5FB;">

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                        <tr>
                          <td><?php echo $order->delivery['street_address']; ?>, <?php echo $order->delivery['suburb']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $order->delivery['city']; ?>, <?php echo $order->delivery['state']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $order->delivery['postcode']; ?> - <?php echo $order->delivery['country']; ?></td>
                          </tr>
                        <tr>
                          <td>Phone: <?php echo $order->customer['telephone']; ?></td>
                          </tr> 
<tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Prefered Method:&nbsp; 
<?php if ($order->info['shipping_method']) { 
	echo preg_replace('/.*?_/','',$order->info['shipping_method']);
 } else {
echo '<font style="color:#FF0000;"><b>None</b></font>'; 
} ?>
</td>
                        </tr>
							<tr>
						<td> Ship Method:&nbsp;

<?php 

$trackingResult = tep_db_query("SELECT * FROM orders_shipped WHERE orders_id = '".$order->orderid."'");

$countResults =  tep_db_num_rows($trackingResult);

if ($countResults > 0) {

	while ($track = tep_db_fetch_array($trackingResult)) {
		echo $track['ship_carrier'].' </td></tr><tr><td>'.$track['ship_carrier'].' #: &nbsp; <a style="font:bold 11px arial"';
	if ($track['ship_carrier'] == 'UPS') { 
		echo 'href="'. sprintf(UPS_TRACKING_URL, $track["tracking_number"]).'"';
	} elseif ($track['ship_carrier'] == 'FedEx') {
		echo 'href="'. sprintf(FEDEX_TRACKING_URL,$track["tracking_number"]).'"';
	} elseif ($track['ship_carrier'] == 'USPS') { 
		echo 'href="'. sprintf(USPS_TRACKING_URL, $track["tracking_number"]).'"';
	} elseif ($track['ship_carrier'] == 'DHL') {
		echo 'href="'. sprintf(DHL_TRACKING_URL,$track["tracking_number"]).'"';
	}
		echo 'target="_blank"> ' . $track["tracking_number"] . ' </a> - '. date('m/d/Y', strtotime($track["ship_date"])).'</td></tr>';
} // # END WHILE

// # else if no results from $countResults
} else { 
		echo '<a href="orders_view.php?oID='.$_GET['oID'].'&action=edit#tracking_number" style="color:#0000FF; font:normal 10px verdana;">
				<u>enter tracking #</u></a></td></tr>';
	}


 ?>			

</table></td>
                    </tr>
            </table>
			</td>
                     <td width="33%" valign="top" style="border-top:1px solid #8CA9C4;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:<?php echo $sourceStyle ?>;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Payment Information:</span></td>
                      
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px tahoma; color:#000000;">&nbsp; Order ID:&nbsp; <?php echo $_GET['oID'] ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td valign="top" style="padding-top:1px; background-color:#F0F5FB;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                        <tr>
                          <td><b>Method:&nbsp;</b>
<?php
	$payMeth = (!empty($order->info['payment_method'])) ? $order->info['payment_method'] : '-' ;
	
	if($payMeth == 'payment_amazonSeller') { 
		echo 'Amazon Seller Central';
	} elseif ($payMeth == 'payment_authnet'){
		echo 'AuthorizeNet Credit Card';
	} else {
		// # if error, check payments table for entry

		if(!empty($payments[0])) { 
			echo $payments[0]->getName();
		} else { 
			echo '-';
		}
	}
?>		</td></tr>
<?php if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {
?>                     <tr>
                          <td>Card Type:&nbsp; <?php echo $order->info['cc_type']; ?></td>
                          </tr>
                        <tr>
                          <td>Card Name:&nbsp; <?php echo $order->info['cc_owner']; ?></td>
                          </tr>
                        <tr>
                          <td>Card #:&nbsp; <?php echo $order->info['cc_number']; ?></td>
                          </tr>
<?php
    }
?>                      <tr>
                          <td>
						  Date Purchased:&nbsp; <?php echo tep_date_short($order->info['date_purchased']); ?>
						  </td>
                        </tr>
                        <tr>
                          <td>Time Purchased:&nbsp; <font style="text-transform:uppercase;"><?php echo $order->info['local_time_purchased'] ? tep_time_format($order->info['local_time_purchased'],$order->info['local_timezone']) : tep_time_format($order->info['date_purchased'],'GMT'); ?></font>
						   </td>
                        </tr>
<?php 

	if($payMeth == 'payment_amazonSeller') {

		$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$order->orderid."' AND method = 'payment_amazonSeller' LIMIT 1") or die(mysql_error());

		$theAmazonoID = tep_db_fetch_array($amazonOrder_query);

		if($order->info['orders_source'] == 'dbfeed_amazon_us') { 

			echo '<tr>
					<td>Amazon Order ID: &nbsp; <a href="https://sellercentral.amazon.com/gp/orders-v2/details/ref=ag_orddet_cont_myo?ie=UTF8&orderID='.$theAmazonoID['ref_id'].'" target="_blank" style="color:#FF0000"><b>'.$theAmazonoID['ref_id'].'</b></a></td>
				</tr>';

		} elseif($order->info['orders_source'] == 'dbfeed_amazon_ca') { 

			echo '<tr>
					<td>Amazon Order ID: &nbsp; <a href="https://sellercentral.amazon.ca/gp/orders-v2/details/ref=ag_orddet_cont_myo?ie=UTF8&orderID='.$theAmazonoID['ref_id'].'" target="_blank" style="color:#FF0000"><b>'.$theAmazonoID['ref_id'].'</b></a></td>
				</tr>';

		}

		if (stripos($order->info['orders_source'],'Amazon-FBA') !== false) {
		//if($order->info['orders_source'] == 'Amazon-FBA') { 

			echo '<tr>
					<td align="center" style="background:#FFF url(images/amazonFBA-logo.jpg) no-repeat center 5px; background-size: contain; height:35px; width:auto;">&nbsp;</td>
				</tr>';

		} else { 

			echo '<tr>
					<td align="center" style="background:#FFF url(images/amazonSeller-logo.jpg) no-repeat center 5px; background-size: contain; height:37px">&nbsp;</td>
				</tr>';

		}
} 
?>
  </table>
					  
					  </td>
                    </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F0F5FB; margin:0; padding:0">
		<tr>
            <td style="height:20px; background-color:<?php echo $sourceStyle ?>; font:bold 13px arial;color:#FFFFFF; margin:0; padding:0" colspan="8">&nbsp;Order ID:&nbsp; <?php echo $_GET['oID'] ?></td>
        	</tr>
		<tr bgcolor="#DEEAF8">
            <td style="padding: 0 0 0 10px; height:20px; font:bold 11px arial; color:#0B2D86"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
            <td align="center" style="width:55px; font:bold 11px arial; color:#000000;">Qnty.</td>
            <td align="center" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Model</td>
            <td align="right" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Price</td>
            <td align="right" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Tax %</td>
            <td align="right" style="padding: 0 7px 0 0; width:100px; font:bold 11px arial; color:#000000;">Sub-total</td>
            <td align="right" style="padding: 0 7px 0 0; width:100px; font:bold 11px arial; color:#0B2D86">Total</td>
		</tr>
<?php
    foreach ($order->products AS $prod)  {
			$xcls='';
			$xclsp='';
			$rqty=0;
			foreach ($order->returns AS $rt) { 
				if($rt['id'] == $prod['id']) {
					 $rqty+=$rt['qty'];
				}
			}
		if($rqty) $xclsp.= ' returned';
		if($rqty >= $prod['qty']) $xcls .= ' returned';

		echo '<tr class=" '.($ct++&1 ? 'tabEven' : 'tabOdd').'">';
?>
			<td class="tableinfo_right-btm<?php echo $xcls?>" style="text-align:left; font:bold 12px arial; padding: 8px 10px"><?php echo $prod['name']?></td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xclsp?>" style="text-align:center; font:bold 12px arial;">
			<?php echo (isset($prod['qty'])) ? $prod['qty'] : '&nbsp;';?>
		  </td>
		  <td align="center" class="tableinfo_right-btm<?php echo $xcls?>" style="font:bold 12px arial; padding: 10px">
			<?php echo ($prod['model']) ? $prod['model'] : '&nbsp;';?></td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xcls?>" style="font:bold 12px arial;">
			<?php echo $currencies->format($prod['final_price'], true, $order->info['currency'], $order->info['currency_value'])?>
		</td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xcls?>" style="font:bold 12px arial;"><?php echo tep_display_tax_value($prod['tax'])?>%</td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xcls?>" style="font:bold 12px arial;"><?php echo $currencies->format($prod['final_price']*$prod['qty'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-end align_right<?php echo $xclsp?>" style="font:bold 12px arial;"><?php echo $currencies->format(tep_add_tax($prod['final_price']*$prod['qty'],$prod['tax']), true, $order->info['currency'], $order->info['currency_value'])?></td>
		</tr>
<?php if (isset($prod['attributes']) && (sizeof($prod['attributes']) > 0)) {
        for ($j = 0, $k = sizeof($prod['attributes']); $j < $k; $j++) {
?>
		<tr class="<?php echo $j&1?'rowOdd':'rowEven'?>">
		  <td bgcolor="#EBF1F5" class="tableinfo_right-btm<?php echo $xcls?>" style="font:11px arial; padding-left:13px;">
          &#8226; &nbsp;<?php echo $prod['attributes'][$j]['option']?>: <?php echo $prod['attributes'][$j]['value']?>
		  </td>
		   <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
		    <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
			 <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">
<?php
          if ($prod['attributes'][$j]['price'] != '0') echo '<font style="color:#FF0000;">' . $prod['attributes'][$j]['prefix'] . $currencies->format($prod['attributes'][$j]['price'] * $prod['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</font>';
          echo '&nbsp;</td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp; </td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp;</td><td class="tableinfo_right-end" bgcolor="#EBF1F5">&nbsp;</td></tr>';
        } 
      }
    foreach ($order->getPurchaseInfo() AS $pinfo) {
?>    
		<tr><td style="padding:4px;background:#CFDFFF;color:#000000">&nbsp;</td><td colspan="6" style="padding:4px;background:#CFDFFF;color:#000000"><?php echo $pinfo?></td><tr>
<?php
    }
      foreach ($order->returns AS $rt) 
		if($rt['id'] == $prod['id']) {

	$isExchange = ($prod[exchange] == '1') ? 'Exchange' : 'Return';

?>
		<tr>
		  <td class="tableinfo_right-btm" style="font:bold 11px arial; color:#BF0000; padding-left:13px;">
- <a style="color:#BF0000" href="returns.php?cID=<?php echo $rt['rma']?>&page=1&oID=<?php echo $rt['returns_id'] . '">' . $isExchange. ' RMA '. $rt['rma'] ;?></a></td>
		  <td align="center" class="tableinfo_right-btm" style="text-align:center; font:bold 11px arial; color:#000000;"><?php echo ($prod[exchange] == '1') ? '' : $rt['qty']?></td>
		  <td align="center" colspan="5" class="tableinfo_right-end align_right<?php echo $xclsp?>" style="font:bold 11px arial; color:#000000;">&nbsp;</td>
		</tr>
<?php
      foreach ($order->products AS $xprod) if (($xprod['exchange_returns_id'] == $rt['returns_id']) && ($xprod[exchange] == '1')) {
?>
		<tr bgcolor="#FFFFFC">
		  <td class="tableinfo_right-btm" style="font:bold 11px arial; color:#0000BF; padding-left:13px;">
<?php echo $xprod['name']?></td>
		  <td align="center" class="tableinfo_right-btm" style="text-align:center; font:bold 11px arial; color:#0000BF;">
		  <?php echo ($rt['qty']) ? $rt['qty'] : '&nbsp;';?>
		  </td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 12px arial; color:#0000BF;">
		   <?php echo ($xprod['model']) ? $xprod['model'] : '&nbsp;';?>
		   </td>
		  <td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo $currencies->format($xprod['price'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo tep_display_tax_value($xprod['tax'])?>%</td>
		  <td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo $currencies->format($xprod['final_price']*$xprod['qty'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="right" class="tableinfo_right-end align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo $currencies->format(tep_add_tax($xprod['final_price']*$xprod['qty'],$xprod['tax']), true, $order->info['currency'], $order->info['currency_value'])?></td>
		</tr>
<?php
 	if (isset($xprod['attributes']) && (sizeof($xprod['attributes']) > 0)) {
          for ($j = 0, $k = sizeof($xprod['attributes']); $j < $k; $j++) {
?>
		<tr class="<?php echo $j&1?'rowOdd':'rowEven'?>">
		  <td bgcolor="#EBF1F5" class="tableinfo_right-btm" style="font:11px arial; color:#0000BF; padding-left:15px;">
          &#8226; &nbsp;<?php echo $xprod['attributes'][$j]['option']?>:<?php echo $xprod['attributes'][$j]['value']?>
		  </td>
		   <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
		    <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
			 <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">
<?php
          if ($xprod['attributes'][$j]['price'] != '0') echo '<font style="color:#FF0000;">' . $xprod['attributes'][$j]['prefix'] . $currencies->format($xprod['attributes'][$j]['price'] * $xprod['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</font>';
          echo '&nbsp;</td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp; </td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp;</td><td class="tableinfo_right-end" bgcolor="#EBF1F5">&nbsp;</td></tr>';
        } // # END for $xprod['attributes']
      } // # END isset($xprod['attributes'])

      } // # END foreach ($order->products AS $xprod)

    } // # END foreach ($order->returns AS $rt)
  } 

?>	  
		<tr>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-end">&nbsp;</td>
		</tr>
		  <td colspan="4" valign="top" bgcolor="#FFFFC4" class="tableinfo_right-btm" ><table width="100%" border="0" cellpadding="0" cellspacing="0">
		    <tr><td height="91" valign="top" style="padding-top:5px; font:bold 11px arial; color:#000000">&nbsp; Customer Comments: <br>
		      <br><div style="padding:5px; font-weight:normal;"><?php echo $order->info['comments']?></div></td>
		  </tr>
		  <tr>
		  <td align="right" style="padding:5px;">  <a href="orders_view.php?oID=<?php echo $_GET['oID'] . '&action=edit#customer_comments'?>" style="color:#0000FF; font:normal 11px arial;"><u>view or add other comments</u></a></td>
		  </tr>
		  </table>
		  </td>
		  <td colspan="3" align="center" bgcolor="#EBF1F5" style="font:bold 11px arial; color:#000000;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
           
              <?php
  

    for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
      echo '              <tr>' . "\n" .
           '                <td height="23" class="tableinfo_right-btm" align="right" style="padding:0 5px 0 5px; font:bold 12px arial; color:#FF0000;">' . $order->totals[$i]['title'] . '</td>' . "\n" .
           '                <td align="right" class="tableinfo_right-end align_right" style="width:102px; padding: 0 5px 0 0; font:bold 12px arial; color:#000000;">' . $order->totals[$i]['text'] . '</td>' . "\n" .
           '              </tr>' . "\n";
    }
?>
            
          </table></td>
		  </tr>
          <tr>
            
<td align="right" colspan="8"></td>
          </tr>
        </table></td>
      </tr>
      <tr>
		<td colspan="2" style="padding:5px;">
    <div id="loadimage" style="display:none; position:relative; width:100%;"><div style="position: absolute; top:15px; right:5px"><img src="data:image/gif;base64,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" width="163" height="15"></div></div>

<div class="tabber" id="t" onClick="contentChanged();">

     <div class="tabbertab">
	  <h2>Customer Correspondence</h2>
	 <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr> 
                      <td><table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><table width="100%" border="0" cellpadding="5" cellspacing="0" style="border:solid 1px #0099FF">
                              <tr>
                                <td width="114" align="center" class="smallText" style="border:solid 1px #0099FF; border-top:none; border-left:none;"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></td>
                                <td width="86" align="center" class="smallText" style="white-space:nowrap; border:solid 1px #0099FF; border-top:none; border-left:none;"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></td>
                                <td width="109" align="center" class="smallText" style="border:solid 1px #0099FF; border-top:none; border-left:none;"><b><?php echo TABLE_HEADING_STATUS; ?></b></td>
                                <td align="center" class="smallText" style="border:solid 1px #0099FF; border-top:none; border-left:none; border-right:none;"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
                              </tr>
<?php

	$orders_history_query = tep_db_query("SELECT orders_status_id, 
												 date_added, 
												 customer_notified, 
												 comments,
												 admin_user
										  FROM " . TABLE_ORDERS_STATUS_HISTORY . " 
										  WHERE orders_id = '" . tep_db_input($oID) . "' 
										  ORDER BY date_added
										 ");

	if (tep_db_num_rows($orders_history_query)) {

		while ($orders_history = tep_db_fetch_array($orders_history_query)) {
				echo '<tr class=" '.($ct++&1 ? 'tabEven' : 'tabOdd').'">';
				echo '	<td class="smallText" align="center" style="border-right:solid 1px #0099FF; border-bottom:1px solid #FFF">' . date('m/d/Y - g:i:sa',strtotime($orders_history['date_added'])+STORE_TZ) . (!empty($orders_history['admin_user']) ? '<br><br><b> By: '.ucwords($orders_history['admin_user']) : '') .'</b></td>
					<td class="smallText" align="center" style="border-right:solid 1px #0099FF; border-bottom:1px solid #FFF">';
			if($orders_history['customer_notified'] == '1') {
				echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK) . "</td>\n";
			} else {
				echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) . "</td>\n";
			}
			
			echo '<td class="smallText" align="center" style="border-right:solid 1px #0099FF; border-bottom:1px solid #FFF">' . $orders_status_array[$orders_history['orders_status_id']] . '</td>
					<td class="smallText" style="border-bottom:1px solid #FFF;"><br>' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>
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
	<tr class="hide_print">
		<td class="main"><br><b><a name="customer_comments"></a> <?php echo TABLE_HEADING_COMMENTS; ?></b></td>
	</tr>
                       
<?php 
$result = tep_db_query("SELECT * FROM orders_shipped WHERE orders_id = '".$_GET['oID']."'");

	$existingTrack_query = tep_db_query("SELECT * FROM ".TABLE_ORDERS_SHIPPED." 
										WHERE orders_id = '".$_GET['oID']."' 
										AND (tracking_number IS NOT NULL OR tracking_number !='')
										");

	echo tep_draw_form('status', FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update_order'); ?>


						<tr class="hide_print">
                          <td valign="top" class="main"><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
                        </tr>
                        <tr class="hide_print">
                          <td style="padding:10px; padding-left:0;"><?php include ("comment_bar.php"); ?></td>
                        </tr>
                 
                        <tr>
                          <td style="padding:10px 0 0 0;"><table border="0" cellpadding="0" cellspacing="0">
                            <tr class="hide_print">
                                <td class="main"><b><?php echo ENTRY_NOTIFY_CUSTOMER; ?></b> <?php echo tep_draw_checkbox_field('notify', '', true); ?></td>
                              <td class="main" align="left" style="padding:0 0 0 15px;"><b><?php echo ENTRY_NOTIFY_COMMENTS; ?></b> <?php echo tep_draw_checkbox_field('notify_comments', '', true); ?></td>
                            </tr>
                          </table></td>
                        </tr>
                        <tr>
                          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '25'); ?></td>
                        </tr>
                        <tr>
                          <td><table width="100%" border="0" cellpadding="0" cellspacing="0">

                              <tr>
                                <td>
									<table width="100%" border="0" cellpadding="2" cellspacing="0">

<?php 
	if(strpos($order->info['orders_source'],'Amazon-FBA') === false) {

		echo '<tr>
				<td class="main"><b>'. ENTRY_STATUS .'</b> '. tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status'], 'id="status"') . '</td>
			</tr>';
	}


	while($existingTrack = tep_db_fetch_array($existingTrack_query)) { 
	
	// # remove the module name from the method by detecting first space and removing everything before.
	$existingTrack['shipped_method'] = strstr($existingTrack['shipped_method'], ' ');
	
		if($existingTrack['ship_carrier'] == 'UPS' && !empty($existingTrack['tracking_number'])) { 

		echo'<tr>
				<td style="font:bold 11px arial; padding-top:10px;">
					<div style="display:inline-block">UPS Tracking: '.$existingTrack["shipped_method"].'<br>
						<a style="font:bold 11px arial;" href="'. sprintf(UPS_TRACKING_URL, $existingTrack["tracking_number"]).'" target="_new"> ' . $existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"])) . '</div>
				</td>
			</tr>';

		} elseif($existingTrack['ship_carrier'] == 'FedEx' && !empty($existingTrack['tracking_number'])) { 

		echo'<tr><td>';
			echo 'FedEx Tracking: <br>
				<a href="'.sprintf(FEDEX_TRACKING_URL,$existingTrack["tracking_number"]).'" target="_new" style="font:bold 11px arial;">'.$existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"]));
echo '<br><br>';
		echo '</td></tr>';

		} elseif($existingTrack['ship_carrier'] == 'USPS' && !empty($existingTrack['tracking_number'])) {

		echo'<tr><td>';
			echo 'Post Office Tracking: <br>
				<a href="'.sprintf(USPS_TRACKING_URL,$existingTrack["tracking_number"]).'" target="_new" style="font:bold 11px arial;">'.$existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"]));
echo '<br><br>';
		echo '</td></tr>';

		} elseif($existingTrack['ship_carrier'] == 'DHL' && !empty($existingTrack['tracking_number'])) {
			
		echo'<tr><td>';
			echo 'DHL Tracking: <br>
		<a href="'.sprintf(DHL_TRACKING_URL,$existingTrack["tracking_number"]).'" target="_new" style="font:bold 11px arial;">'.$existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"]));
echo '<br><br>';
		echo '</td></tr>';
		}

	} 
	echo tep_draw_hidden_field('payment_method', $order->info['payment_method']);
?></td>
                                    </tr>
                                    <tr class="hide_print">
                                      <td align="right" colspan="2"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                                    </tr>
                                </table></td>
                              </tr>
                              <tr> </tr>
                          </table></td>
                           </tr>
</form>
                        
                      </table></td>
                      </tr>
                  </table>
     </div>


     <div class="tabbertab hide_print">
	  <h2>Admin Only Comments</h2>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr> 
                      <td><table width="528" border="0" cellpadding="0" cellspacing="0" style="border:solid 1px #0099FF;">
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
						  <?php echo tep_draw_form('status', FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update_admin_comments'); ?> 
                          <tr> 
                            <td><?php echo tep_draw_textarea_field('admin_comments', 'soft', '60', '5', '', 'style="background-color:#FFFFD9;"'); ?></td>
                          </tr>
                          <tr class="hide_print"> 
                            <td align="right" style="padding-top:10px"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                          </tr><?php echo '</form>'?>
                        </table></td>
                    </tr>
					
  </table>
     </div>
	 
     <div class="tabbertab hide_print" id="shiptab">
	  <h2 style="width:100%; border:solid 1px #E2E2E2">Shipping and Tracking</h2>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
	    <tr>
                          <td>  
<?php

function check_base64_image($base64) {

	$imgdata = base64_decode($base64);
	$f = finfo_open();
	$mime_type = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);
	$mime_type = str_replace('image/','',$mime_type);
	$imgtype = array("gif", "GIF", "jpg", "JPG", "png", "PNG","TIFF","tiff");
	if (in_array($mime_type, $imgtype)) {
		return true;
	} else { 
		return false;
	}
}

echo tep_draw_form('status', FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update_order', 'post', 'enctype="multipart/form-data" id="form_shipping"');?>  
					  <table width="100%" border="0" cellpadding="3" cellspacing="5">
	         	   		<tr>
                          <td width="70" bgcolor="#FFFFFF" align="center"><a name="tracking_number"></a>
<img src="data:image/gif;base64,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" width="70" height="70" alt="<?php echo TABLE_HEADING_UPS_TRACKING; ?>">
								<div style="position:relative">
									<div style="position:absolute; top:-82px; left:-13px;"><img src="images/green_new.png" width="32" height="32"></div>
								</div>
							</td>
						  <td style="background-color:#FBE6A3; border-top-right-radius:10px">

<table width="100%" border="0" cellpadding="5" cellspacing="0">
<tr>
<td width="1%">
	<input type="text" id="ups_track_num" name="ups_track_num" size="18" value="" style="margin-left:10px">
	<input type="hidden" id="UPS_digest" name="UPS_digest" value="" size="2">
	<input type="hidden" id="weight_override_offExpand" name="weight_override" value="<?php echo $theWeights?>">
	<div id="theMethodUsed" style="display:none; padding:5px 0 0 10px;"></div>
</td>

<td>
<?php 

if(empty($order->info['shipping_method'])) { 

	$order->info['shipping_method'] = 'Standard';
}

		if($order->info['shipping_method']) {

			$getUPSTracking = mysql_query("SELECT * FROM orders_shipped WHERE orders_id = '".$_GET['oID']."' AND ship_carrier = 'UPS'");

			echo '<table width="100%"><tr>';
			if(tep_db_num_rows($getUPSTracking) > 0) { 

				echo '<td><img id="genLabelButton" onclick="reloadShipping(\'true\');jQuery(\'#recalculate_shipping\').prop(\'checked\', true);" src="data:image/png;base64,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" style="cursor:pointer; padding: 0 10px">
   <applet id="qz" name="QZ Print Plugin" code="qz.PrintApplet.class" archive="./includes/jars/qz-print.jar" width="1px" height="1px" style="display:none;"">
      <param name="permissions" value="all-permissions" />
      <param name="printer" value="zebra">
	  <param name="cache_archive" value="./includes/jars/qz-print.jar, ./includes/jars/jssc_qz.jar, ./includes/jars/pdf-renderer_qz.jar">
   </applet>
</td>';
			} else {
				echo '<td width="1%"><img id="genLabelButton" src="data:image/png;base64,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" style="cursor:pointer; padding: 0 5px"></td>';
			}

			echo '<td width="98%"><label><input type="checkbox" id="recalculate_shipping" name="recalculate_shipping" onClick="reloadShipping(this.checked);"> Override Shipping</label></td></tr></table>';
		} 

?>
   <div id="shipping_box"></div>
   <div id="label_box"></div>

 	<input type="hidden" id="shipping_method" name="update_shipping_method" value="<?php echo $order->info['shipping_method']?>">
    <input type="hidden" name="update_customer_state" value="<?php echo tep_html_quotes($order->customer['state'])?>" id="bill_state">
    <div id="bill_state_box"></div>
    <input type="hidden" name="update_delivery_state" value="<?php echo tep_html_quotes($order->delivery['state'])?>" id="ship_state">
    <div id="ship_state_box"></div>
	<input type="hidden" name="update_customer_postcode" value="<?php echo $order->customer['postcode']; ?>" id="bill_postcode">
	<input type="hidden"  name="update_delivery_postcode" value="<?php echo $order->delivery['postcode']; ?>" id="ship_postcode">

	<input type="hidden" name="update_delivery_postcode" value="<?php echo $order->delivery['country']; ?>" id="ship_country">
<input type="hidden" name="order_products_data" id="order_products_data" value="">

<script type="text/javascript">
<?php
  foreach($order->products AS $prod) {
if(is_string($prod['weight'])) $prod['weight'] = (float)$prod['weight'];
	if($prod['weight'] == 0) { 
		$products_weight_query = tep_db_query("SELECT products_weight AS weight FROM ".TABLE_PRODUCTS." WHERE products_id ='".$prod['id']."' LIMIT 1");
		$products_weight = tep_db_fetch_array($products_weight_query);
	$prod['weight'] = $products_weight['weight'];
	}
    $attr_js_data=Array();
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
    id: '<?php echo addslashes($prod['id'])?>',
    name: '<?php echo addslashes($prod['name'])?>',
    model: '<?php echo addslashes($prod['model'])?>',
    price: '<?php echo addslashes($prod['price'])?>',
    final_price: '<?php echo addslashes($prod['final_price'])?>',
    tax: '<?php echo addslashes($prod['tax'])?>',
    free_shipping: '<?php echo addslashes($prod['weight'])?>',
    separate_shipping: '<?php echo addslashes($prod['separate_shipping'])?>',
    weight: '<?php echo $prod['weight'];?>',
    qty: '<?php echo addslashes($prod['qty'])?>',
    attr: <?php echo tep_js_quote_array($prod['attributes'])?>
  };
<?php
  }
?>
</script>

</td>
	<td>

<div id="LabelRows">
<?php 

if(tep_db_num_rows($getUPSTracking) > 0) { 

/*	//$labelRecoverable = ($theUPSTrack['ship_type'] != 'unconfirmed') ? '<div style="display:inline-block; padding: 0 5px 0 0; cursor:pointer"><img src="images/delete.png" id="recover_'.$theUPSTrack["tracking_number"].'"></div>' : '';
	echo '
<script type="text/javascript">
jQuery.noConflict();
	jQuery(\'#recover_'.$theUPSTrack['tracking_number'].'\').click(function() {
		recoverLabel(\''.$_GET['oID'].'\', \''.$theUPSTrack['tracking_number'].'\');
	});

</script>';
*/

	$cnt=1;
	while ($theUPSTrack = tep_db_fetch_array($getUPSTracking)) {
		$theLabel = $theUPSTrack['shipped_method'];
		$theLabel = str_replace('upsxml_', '', $theLabel);

	if(check_base64_image($theUPSTrack['label_digest'])) {
	
		// # if the digest resolves to a valid base64 image
		$printableLable = '<img class="theLabel" id="'.$theUPSTrack['tracking_number'].'" src="data:image/gif;base64,'. $theUPSTrack['label_digest']. '" width="34" height="60">';

		$thePrintButton = '<div id="print_'.$theUPSTrack["tracking_number"].'"class="print-label" onclick="printUPSLabel(\''.$_GET['oID'].'\', \''.$theUPSTrack['tracking_number'].'\', \''.$theUPSTrack['label_digest'].'\');"></div>';
	
			} elseif($theUPSTrack['ship_type'] != 'unconfirmed' && !check_base64_image($theUPSTrack['label_digest']) && $theUPSTrack['label_digest'] !='') {
				$printableLable = '<img class="theThermalLabel" id="'.$theUPSTrack['tracking_number'].'" data-thelabel="'.$theUPSTrack['label_digest'].'" src="data:image/gif;base64,R0lGODlhIgA8ALMAAP///+7u7t3d3czMzLu7u6qqqpmZmYiIiHd3d2ZmZlVVVURERDMzMyIiIhEREQAAACH5BAAHAP8ALAAAAAAiADwAAAT/UBAyZjElB8EDEQWxcaQQjMEACAMrAAEAy/QwHASty0Nmpi3biLUrGnUnFiFkOR2fO5NtympBrzITdms0GRKGgSHGvWpX1GUlVClnX4DplHxyujlZFNxdxPO5fhQBBX9Hfj9KQoVvMh9BjgNJcS8mEmRdcAhfBwcGAgeanQAKBwEGpxhPfotQZ6xPKbB1MnW1tnVSSxcYBgcFnSC6BQJhxRIFHhQFNoQFChmcnQkqAwsE0wAI2QAJAAYK3t85MwRjBghLEioCYAcv3d3gAwkCCKDaKyueGGLqry8BFBi4lmBZGx3zEnBScGPAsz76ziH4dcDGDgIKFCBAwGDeAAaE/6LEGUYAB7plFzNyWjAAgYAFIXUAzPCrmII9jRYs2Mjg2kcDEFNciFEAnZEJHHKoQGakgc4FDJ4qWNCAgVWrCxIkyLiVK9SvUB0oG7PEgrolACgcSGvhQhxtYGBUOFWhYCqKQDV1i3YKHAAGABYwhAHHzoxLtGAgpgH4gQpcVSCTSCIFJy1wfgdVsDH5VgdaQ0iskFAO55qCnIoOLGmPUxgwy+jRcJX2lINvChnAVsCAawICDRQScHBpg44lBhoU0GpgZ4YEWRUkQPDR3oAGl1alvaCcufPl0adXr4h9Nk7k3cF8h75VPAPr5RnRQL9c/UTw7am/J5/9PPf6zd3HnoZ0+sHX33GnNIBbc7v1RuB1Bs62QwoYlYQDGGopZI8nAg3jFw3RSdeeiFppRWKJJo44lWALnANKL8tMIOOMNNZIY0yvwJKjKpbtONtiPmaxWQsfkKYCFy0MFMkKMRimGJBP+PLAb4QYh0UQL3DmQlrq9MDkLWAiwQWUQZZp5plopqnmmmdGAAA7" width="34" height="60">';

		$thePrintButton = '<div id="print_'.$theUPSTrack["tracking_number"].'"class="print-label" onclick="printUPSThermalLabel(\''.$_GET['oID'].'\', \''.$theUPSTrack['tracking_number'].'\', \''.$theUPSTrack['label_digest'].'\');"></div>';

			} else {
				$printableLable = '';
				$thePrintButton='';
			}

		echo '<table class="existingLabel">
				<tr>
					<td style="" valign="top" nowrap>
						<input type="hidden" name="ups_track_num[]" value="'. $theUPSTrack['tracking_number'].'"><a style="font:bold 11px arial;" href="'. sprintf(UPS_TRACKING_URL, $theUPSTrack["tracking_number"]).'" target="_new">'.$theUPSTrack['tracking_number'].'</a><br>
						'.$theLabel.' <br>
						Billed Weight: '.$theUPSTrack['shipped_weight'].' '.strtolower(SHIPPING_UNIT_WEIGHT) . ' <br>
						'. date('m/d/Y - h:ia', strtotime($theUPSTrack['ship_date'])) . '
						</td>
					<td style="padding:10px 6px 0 2px;" valign="top" align="center" nowrap>' . $printableLable . '</td>
				</tr>';
	if($theUPSTrack['ship_type'] != 'unconfirmed') { 
		echo '<tr>
				<td colspan="2" style="padding:0px 0px 0px 2px; margin:0; line-height:0;" valign="bottom">'.$thePrintButton .'  
					<div id="void_'.$theUPSTrack["tracking_number"].'" class="void-label" onclick="voidLabel(\''.$_GET['oID'].'\', \''.$theUPSTrack['tracking_number'].'\');"></div>
				</td>
			</tr>';
	}

	if($order->delivery['country'] != $warehouse_country_name) { 
		echo '<tr>
				<td colspan="2"><div id="print-export-docs_'.$theUPSTrack["tracking_number"].'" class="print-export-docs" onclick="location.href=\'orders_view.php?oID='.$_GET['oID'].'&action=edit&download=ups_customs_forms&ups_track_num='.$theUPSTrack['tracking_number'].'\';"></div></td>
			 </tr>';
	}

echo '</table>
<input type="hidden" id="UPS_digest_'.$cnt.'" name="UPS_digest[]" value="'. $theUPSTrack['label_digest'].'" size="2">';
		
	$cnt++;
	}
}
 
?>

</div>

</td>
</tr>
</table>
</td>
	</tr>
<tr>
	<td align="center"><img src="images/usps-icon-header.gif" width="70" height="70" alt="<?php echo TABLE_HEADING_USPS_TRACKING; ?>"></td>
	<td style="background-color:#D8EFFE; border-bottom-right-radius:10px"> &nbsp; 

	<?php 
		$getUSPSTracking = mysql_query("SELECT * FROM orders_shipped WHERE orders_id = '".$_GET['oID']."' AND ship_carrier = 'USPS'");


		echo '<textarea id="usps_track_num" name="usps_track_num" cols="30" rows="3" wrap="soft">';
		if(tep_db_num_rows($getUSPSTracking) > 0) { 
			while ($theUSPSTrack = tep_db_fetch_array($getUSPSTracking)) {
				if(!empty($theUSPSTrack['tracking_number']))  echo trim($theUSPSTrack['tracking_number']) . "\n";

			}
		} else {
			echo "\n";
		}

		echo '</textarea>';

		//echo '<input type="text" id="usps_track_num" name="usps_track_num" size="18" value="" style="margin-left:10px">';

		//echo tep_draw_textbox_field('usps_track_num', '25', '25', '', $order->info['usps_track_num']); 

?>
</td>
                        </tr>

	<tr>
		<td align="center"><img src="images/fedex-icon-header.gif" width="70" height="70" alt="<?php echo TABLE_HEADING_FEDEX_TRACKING; ?>"></td>
		<td style="background-color:#EBEBEB"> &nbsp; 

<?php 

$getFedEXTracking = mysql_query("SELECT * FROM orders_shipped WHERE orders_id = '".$_GET['oID']."' AND ship_carrier = 'FedEx'");

echo '<textarea id="fedex_track_num" name="fedex_track_num" cols="30" rows="3" wrap="soft">';
while ($theFedEXTrack = mysql_fetch_array($getFedEXTracking, MYSQL_ASSOC)) {

if(!empty($theFedEXTrack['tracking_number']))  echo trim($theFedEXTrack['tracking_number']) . "\n";

}
echo '</textarea>';


//echo tep_draw_textbox_field('fedex_track_num', '25', '25', '', $order->info['fedex_track_num']); ?>
</td>
                        </tr>
                       <!--tr>
                          <td> &nbsp; <b><?php // echo TABLE_HEADING_DHL_TRACKING; ?>:</b></td>
						  <td> &nbsp; <?php // echo tep_draw_textbox_field('dhl_track_num', '25', '25', '', $order->info['dhl_track_num']); ?></td>
                        </tr--> 
                        <tr class="hide_print">
                          <td colspan="2" style="padding:10px 0 0 0"><b><?php echo ENTRY_NOTIFY_CUSTOMER; ?></b> <?php echo tep_draw_checkbox_field('notify', '', false); ?></td>
                        </tr>
                        <tr>
                          <td colspan="2" align="right" style="padding:10px 0 0 0"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                        </tr>
<?php 

	echo tep_draw_hidden_field('status',3);
 	$existingTrack_query = tep_db_query("SELECT * FROM ".TABLE_ORDERS_SHIPPED." 
										WHERE orders_id = '".$_GET['oID']."' 
										AND (tracking_number IS NOT NULL OR tracking_number !='')
										");

	while($existingTrack = tep_db_fetch_array($existingTrack_query)) { 
	
	// # remove the module name from the method by detecting first space and removing everything before.
	$existingTrack['shipped_method'] = strstr($existingTrack['shipped_method'], ' ');

if($existingTrack['ship_carrier'] == 'FedEx' && !empty($existingTrack['tracking_number'])) { 

		echo'<tr><td colspan="3">';
			echo 'FedEx Tracking: <br>
				<a href="'.sprintf(FEDEX_TRACKING_URL,$existingTrack["tracking_number"]).'" target="_new" style="font:bold 11px arial;">'.$existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"]));
echo '<br><br>';
		echo '</td></tr>';

		} elseif($existingTrack['ship_carrier'] == 'USPS' && !empty($existingTrack['tracking_number'])) {

		echo'<tr><td colspan="2">';
			echo 'Post Office Tracking: <br>
				<a href="'.sprintf(USPS_TRACKING_URL,$existingTrack["tracking_number"]).'" target="_new" style="font:bold 11px arial;">'.$existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"]));
echo '<br><br>';
		echo '</td></tr>';

		} elseif($existingTrack['ship_carrier'] == 'DHL' && !empty($existingTrack['tracking_number'])) {
			
		echo'<tr><td colspan="2">';
			echo 'DHL Tracking: <br>
		<a href="'.sprintf(DHL_TRACKING_URL,$existingTrack["tracking_number"]).'" target="_new" style="font:bold 11px arial;">'.$existingTrack["tracking_number"] . ' </a> &nbsp; | &nbsp; Shipped: '. date('D., M. d, Y - h:ia T', strtotime($existingTrack["ship_date"]));
echo '<br><br>';
		echo '</td></tr>';
		}

	} 
?>
</table></form>
  </td></tr>
  </table>
     </div>

</div>

</td>
      </tr>
      <tr>
		<td colspan="2" style="padding:10px;">		
<?php 

	$status_query = tep_db_query("SELECT orders_status FROM " . TABLE_ORDERS . " where orders_id = '" . tep_db_input($oID) . "'");
	$the_status = tep_db_fetch_array($status_query);

	// # if equals to status shipped - pay attention these match your shipping config values!
	if($the_status['orders_status'] == 3) { 

		// # if status is shipped display return button in place of edit button.
		$editbutton = '<a href="return_product.php?order_id='.$_GET['oID'].'" class="hide_print">' . tep_image_button('button_return.gif', IMAGE_EDIT) . '</a>';   

		if($order->info['payment_method'] == 'payment_manual') {

			// # BUT if status is shipped and its a manual order, display the edit button anyway.
			$editbutton .= ' <a href="' . tep_href_link(FILENAME_EDIT_ORDERS, 'oID=' . $_GET['oID']) . '" class="hide_print">' . tep_image_button('button_modifier.gif', IMAGE_EDIT) . '</a>'; 
		}
       
	} elseif($the_status['orders_status'] == 0 || strpos($order->info['orders_source'],'Amazon-FBA') !== false || strpos($order->info['orders_source'],'dbfeed_') !== false) { 

		// # if status is cancelled or the order is an Amazon order, hide the return and edit buttons.
		$editbutton = '';

	} else { // # if status is pending or pre-settlement

		$editbutton = '<a href="' . tep_href_link(FILENAME_EDIT_ORDERS, 'oID=' . $_GET['oID']) . '" class="hide_print">' . tep_image_button('button_modifier.gif', IMAGE_EDIT) . '</a>'; 
	}


	if(strpos($order->info['orders_source'],'Amazon-FBA') === false) {

		echo '<a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $_GET['oID']) . '" target="_blank" class="hide_print">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $_GET['oID']) . '" target="_blank" class="hide_print">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a>  ' . $editbutton . ' ';
	}

		echo tep_image_button('button_print.gif', IMAGE_ORDERS_PRINT, 'class="hide_print" onclick="window.print(); return false;" style="cursor:pointer"');

?> </td>
      </tr>
    
<?php
  } else {
?>
      
<?php
  }
?>
</table>
</td>

  </tr>
</table>
  </tr>
</table>



</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
