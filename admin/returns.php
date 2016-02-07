<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	// # Choose whatever status should be green /active here:
	define('GREEN_STATUS', 4);
 
	require('includes/application_top.php');
	require(DIR_WS_CLASSES . 'order.php');

	$oID = (int)$_GET['oID'];
	require(DIR_WS_CLASSES . 'returns.php');
	$return = new order_return($oID);

	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();
	include(DIR_WS_LANGUAGES . $language . '/' . 'returns.php');

	$orders_statuses = array();
	$orders_status_array = array();
	$orders_status_query = tep_db_query("SELECT returns_status_id, returns_status_name 
										 FROM " . TABLE_RETURNS_STATUS . " 
										 WHERE language_id = '" . $languages_id . "'
										");

	while ($orders_status = tep_db_fetch_array($orders_status_query)) {

    	$orders_statuses[] = array('id' => $orders_status['returns_status_id'], 'text' => $orders_status['returns_status_name']);


	    $orders_status_array[$orders_status['returns_status_id']] = $orders_status['returns_status_name'];
  	}

	// # language query
	$languages = tep_get_languages();
	$languages_array = array();
	$languages_selected = DEFAULT_LANGUAGE;
 
	for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
		$languages_array[] = array('id' => $languages[$i]['code'], 'text' => $languages[$i]['name']);
		if($languages[$i]['directory'] == $language) $languages_selected = $languages[$i]['code'];
	}

	switch ($_GET['action']) {
    	case 'update_order':
			$oID = tep_db_prepare_input($_GET['oID']);

			$status = (!empty($_POST['status']) ? (int)$_POST['status'] : $return->info['orders_status']);

			$status_name = tep_db_result(tep_db_query("SELECT returns_status_name FROM ".TABLE_RETURNS_STATUS." 
														   WHERE language_id = '" . $languages_id . "' 
														   AND returns_status_id = '".$status."'
														   "),0);	


			// # setup some temp error checking to pin down these mysql_result errors.
			if(empty($status)) {
				error_log('status from returns.php not set on update_order case');
			} 


			$comments = tep_db_prepare_input($_POST['comments']);
			$refund_method = tep_db_prepare_input($_POST['refund_method']);
			$order_updated = false;
			$theDate = date('Y-m-d H:i:s',time());
			$posted_amount = preg_replace('/[^0-9\.]/', '',$_POST['refund_amount']);
			$refund_amount = (!empty($posted_amount) ? number_format($posted_amount, 2,'.', '') : 0.00);

			$exchange_amount = 0;

			// # With Restock charge
			if ($_POST['restock_charge'] == 'on') {

				$restock_query = tep_db_query("SELECT configuration_value 
											   FROM " . TABLE_CONFIGURATION . " 
											   WHERE configuration_key = 'DEFAULT_RESTOCK_VALUE'
											  ");
				$restock = tep_db_fetch_array($restock_query);
				$restock_charge = round((float)$restock['configuration_value'] * 100 ) / 100;
				$refund_with_tax = (!empty($_POST['refund_amount']) ? number_format((float)$_POST['refund_amount'],2, '.', '') : 0);
				$work_out_charge = ((float)$refund_with_tax / 100) * $restock_charge;
				$refund_amount =  ($refund_with_tax - $work_out_charge);

				$refund_method_query = tep_db_query("SELECT refund_method_name 
													  FROM " . TABLE_REFUND_METHOD . " 
													  WHERE refund_method_name = '".$refund_method."' 
													  AND language_id = '" . $languages_id . "'
													 ");
				$refund_method = tep_db_fetch_array($refund_method_query);
				$refund_method_name = $refund_method['refund_method_name'];
				$refund_ref = (!empty($_POST['refund_reference'])) ? $_POST['refund_date'] : '';
				$refund_date = (!empty($_POST['refund_date'])) ? $_POST['refund_date'] : date('Y-m-d H:i:s',time());

				$sql_data_array = array('customer_method' => $refund_method_name,
    		                            'refund_payment_value' => $refund_amount,
        		                        'refund_payment_date' => $refund_date,
            		                    'refund_payment_reference' => $refund_ref,
                		                'refund_payment_deductions' => $work_out_charge
                    		     		);
				tep_db_perform(TABLE_RETURN_PAYMENTS, $sql_data_array,  "update", "returns_id = '" . $oID . "'");
				$order_updated = true;

			} else { // # No restock charge

				$refund_with_tax = (!empty($_POST['refund_amount']) ? number_format((float)$_POST['refund_amount'],2, '.', '') : 0);
				$refund_amount = $refund_with_tax;
				$refund_method_name = $payment_method['payment_option_name'];
				$payment_ref = (!empty($_POST['refund_reference'])) ? $_POST['refund_date'] : '';
				$payment_date = (!empty($_POST['refund_date'])) ? $_POST['refund_date'] : date('Y-m-d H:i:s',time());
				$work_out_charge = '0';

				$sql_data_array = array('customer_method' => $refund_method_name,
										'refund_payment_value' => $refund_amount,
										'refund_payment_date' => $payment_date,
										'refund_payment_reference' => $payment_ref,
										'refund_payment_deductions' => $work_out_charge,
										);
				tep_db_perform(TABLE_RETURN_PAYMENTS, $sql_data_array,  "update", "returns_id = '" . $oID . "'");
				$order_updated = true;

			} 
			// # END restock charge condition

	// # query and send routine for gv-refund / exchange product refund
	if (!empty($_POST['refund_gv']) && (stripos($return->info['payment_method'], 'amazon') === false)) {

		$gv_amount = (float)tep_db_prepare_input($_POST['gv_amount']);

		if(!empty($return->info['refund_gv_id'])) {

			tep_db_query("UPDATE ". TABLE_COUPONS ." 
						  SET coupon_amount = '$gv_amount',
						  coupon_active = 'Y'
						  WHERE coupon_id='".$return->info['refund_gv_id']."'
						");
		} else { // # ELSE $return->info['refund_gv_id'] NOT SET

			include(DIR_WS_LANGUAGES . $language . '/' . 'gv_mail.php');

			$refund_amount_query = tep_db_query("SELECT pay.refund_payment_value, 
														r.customers_email_address, 
														r.customers_name, 
														r.customers_id
											   FROM ".TABLE_RETURN_PAYMENTS." pay
											   INNER JOIN ". TABLE_RETURNS . " r ON r.returns_id = pay.returns_id
											   WHERE r.returns_id = '".$oID."'
											  ");

			$refund_row = tep_db_fetch_array($refund_amount_query);

			$address = $refund_row['customers_email_address'];
			
			// # additional query for ccGV System
			$gv_name_query = tep_db_query("SELECT customers_id, 
												  customers_firstname, 
												  customers_lastname 
										   FROM " . TABLE_CUSTOMERS . " 
										   WHERE customers_email_address = '" . $refund_row['customers_email_address'] . "'
										  ");

			$gv_name = tep_db_fetch_array($gv_name_query);
			
			$customers_id = $gv_name['customers_id'];
			$firstname = $gv_name['customers_firstname'];
			$lastname = $gv_name['customers_lastname'];
			$fullname = $firstname . ' ' . $lastname;
			$customer = $refund_row['customers_id'];
			$gv_comments = $_POST['gv_comments'];

			$salt=$address;
			$gvid = md5(uniqid("","salt"));
			$gvid .= md5(uniqid("","salt"));
			$gvid .= md5(uniqid("","salt"));
			$gvid .= md5(uniqid("","salt"));
			srand((double)microtime()*1000000); // seed the random number generator
			$random_start = @rand(0, (128-16));

			$id1 = substr($gvid, $random_start,16);

			$message = tep_db_prepare_input($gv_comments);
			$message .= "\n\n" .'Hello '. $firstname ."\n\n" . 'Here is your credit for your return, you may use it on any of our products. Simply login to your account and use it at checkout in the coupon code feild.' . "\n";
			$message .= "<br>" . TEXT_GV_WORTH  .  $currencies->format($gv_amount) . "\n\n";
			$message .= TEXT_TO_REDEEM;
			$message .= TEXT_WHICH_IS . $id1 . TEXT_IN_CASE . "\n\n";
			$message .= '<b>To redeem please add products to your cart and use code at checkout in our Discount Code field.</b>'."\n\n";
			$message .= TEXT_OR_VISIT . HTTP_SERVER  . DIR_WS_CATALOG . ' ' . TEXT_ENTER_CODE . "\n\n\n";
			$message .= 'For more information on how our Store Credit works and how you can share it with others, please visit our Store Credit FAQ at: <a href="' . tep_catalog_href_link('FAQ.html') . '">' . tep_catalog_href_link('FAQ.html') . '</a>';	
	
			/* 	
			// # gv_tracking appears to have been deprecated long ago

			// # now create the tracking entry
			$gv_query=tep_db_query("insert into gv_tracking (gv_number, date_created, customer_id_sent, sent_firstname, sent_surname, emailed_to, gv_amount) values ('".$id1."', NOW(),'" . $customer . "','Sent by','Admin','" . $address . "','" . $refund . "')");  

			*/

			// # update the coupon table of ccGV System
			tep_db_query("INSERT INTO " . TABLE_COUPONS . " 
						  SET coupon_code = '" . $id1 . "',
						  coupon_type = 'G', 
						  coupon_amount = '" . $gv_amount . "',
						  coupon_minimum_order = '0.01',
						  uses_per_coupon = '100',
						  uses_per_user = '100',
						  coupon_active = 'Y',
						  date_created = NOW(),
						  date_modified = NOW(),
						  coupon_start_date = NOW(),
						  coupon_expire_date = '".date('Y-m-d H:i:d', strtotime('today + 2 years'))."',
						  coupon_apply_to_shipping = 'N',
						  restrict_to_customers = '".$customer."'
						");

			$coupon_id = tep_db_insert_id();
	

			// # update the coupon BALANCE table of ccGV system
			tep_db_query("INSERT INTO ". TABLE_COUPON_GV_BALANCE ." 
						  SET coupon_id = '". $coupon_id ."',
						  gv_balance = '" . $gv_amount . "'
						 ");

			// # update the coupon CUSTOMER table of ccGV system
			// # first we select for existing balance.
			$gv_balance_query = tep_db_query("SELECT amount FROM " . TABLE_COUPON_GV_CUSTOMER . " WHERE customer_id = '". $customer ."'");

			// # if balance found, add to it (don't overwrite it!) - incase of multiple store credits.
			if(tep_db_num_rows($gv_balance_query) > 0) {

				$gv_balance = tep_db_result($gv_balance_query,0);

				tep_db_query("UPDATE ". TABLE_COUPON_GV_CUSTOMER ."
							  SET customer_id = '". $customer ."',
							  amount = '" . ($gv_balance + $gv_amount) . "'
							  WHERE customer_id = '". $customers_id ."'
							 ");
			} else { 
	
				tep_db_query("INSERT INTO ". TABLE_COUPON_GV_CUSTOMER." 
							  SET customer_id = '". $customer ."',
							  amount = '" . $gv_amount . "'
							 ");
			}

			// # update the coupon email track table of the ccgv system
			tep_db_query("INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . "
						  SET coupon_id = '". $coupon_id ."', 
						  customer_id_sent = '". $customer ."',
						  sent_firstname = '" . $firstname . "',
						  sent_lastname = '" . $lastname . "', 
						  emailed_to = '" . $address . "', 
						  date_sent = NOW()
						 ");


			// # check if email was sent to customer already - halt if already sent.
			$email_sent = tep_db_query("SELECT * FROM " . TABLE_COUPON_EMAIL_TRACK . " WHERE coupon_id = ".$coupon_id);

			if(tep_db_num_rows($email_sent) < 1) { 

				// # Send message containing store credit to customer
				tep_mail($fullname, $address, EMAIL_TEXT_GV_SUBJECT, $message, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
			}



			// #  now update the refund table to reflect gv as refund method, and set the payment reference as the gv id
			tep_db_query("UPDATE ".TABLE_RETURNS." 
						  SET refund_gv_id = '$coupon_id' 
						  WHERE returns_id = '$oID'
						");

		$return	->info['refund_gv_id'] = $coupon_id;

			$payment_name = 'Store Credit';

			$gv_update_sql = array('customer_method' => $payment_name,
            	                   'refund_payment_value' => $gv_amount,
           	    	               'refund_payment_reference' => $id1,
                    			   'refund_payment_date' => 'now()',
	                              );
			tep_db_perform(TABLE_RETURN_PAYMENTS, $gv_update_sql, 'update', 'returns_id = "' . $oID . '"');
		} 
		// # END  $return->info['refund_gv_id'] NOT SET

        $order_updated = true;
        $refund_by_gv = true;

 
/*	} elseif(!$return->info['refund_gv_id']) {
		tep_db_query("UPDATE ".TABLE_COUPONS." 
					  SET coupon_active='N' 
					  WHERE coupon_id='".$return->info['refund_gv_id']."' 
					  AND coupon_active='Y'
					");
		if(tep_db_num_rows() > 0) {
			 tep_db_query("UPDATE ".TABLE_RETURNS." SET refund_gv_id=NULL WHERE returns_id='$oID'");
		$return->	info['refund_gv_id'] = NULL;
			$order_updated = true;
		}
*/
    } // # END $_POST['refund_gv']


	if ($_POST['exchange']) {
		if (!empty($_POST['order_products_data'])) {
			$exchange_amount=-$return->products['final_price']*$return->products['qty'];
			$update_list = array();

			foreach(explode("\n",$_POST['order_products_data']) AS $prod_data) {
				if(preg_match('/^(.+?):(.*?)\r?$/',$prod_data,$prod_data_ar)) {
					$update_ptr=&$update_list;
					foreach (explode(".",$prod_data_ar[1]) AS $update_idx) {
						if (!isset($update_ptr[$update_idx])) $update_ptr[$update_idx]=NULL;
						$update_ptr = &$update_ptr[$update_idx];
					}

					$update_ptr = $prod_data_ar[2];
				}
			}

			foreach($update_list AS $update_prod) {

				$cost_price_query = tep_db_query("SELECT cost_price FROM ". TABLE_ORDERS_PRODUCTS ." WHERE products_id = '".$update_prod['id']."' AND orders_id = '". $return->orderid ."' GROUP BY products_id");

				if(tep_db_num_rows($cost_price_query) > 0) { 

					$cost_price_result = tep_db_fetch_array($cost_price_query);
					$cost_price = $cost_price_result['cost_price'];
				} else {
					$cost_price = '0.00';
				}

				$RunningSubTotal += $update_prod["qty"] * $update_prod["final_price"];
				$RunningTax += (($update_prod["tax"]/100) * ($update_prod["qty"] * $update_prod["final_price"]));
				$update_fields = array();

				if(isset($update_prod['orders_products_id']) && $update_prod['orders_products_id']) { 
					$update_cond= "orders_products_id='".$update_prod['orders_products_id']."' AND exchange_returns_id='$oID'"; 
				} else { 
					$update_cond= '';
				}

				if($update_prod['qty'] <= 0) {
					if($update_cond) {
						tep_db_query("DELETE FROM ".TABLE_ORDERS_PRODUCTS." WHERE $update_cond");
						tep_db_query("DELETE FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES." 
									  WHERE orders_products_id='".$update_prod['orders_products_id']."'
									 ");
					}

				} else {
					$update_fields = array(orders_id => $return->orderid,
										   products_id => $update_prod['id'],
										   products_model => $update_prod['model'],
										   products_name => $update_prod['name'],
										   products_price => $update_prod['price'],
										   cost_price => $cost_price,
										   final_price => $update_prod['final_price'],
										   products_tax => $update_prod['tax'],
										   products_quantity => $update_prod['qty'],
										   products_stock_quantity => $update_prod['qty'],
										   products_stock_attributes => '',
										   products_returned => '0',
										   products_exchanged => '1',
										   products_exchanged_id => $update_prod['orders_products_id'],
										   free_shipping => $update_prod['free_shipping'],
										   separate_shipping => $update_prod['separate_shipping'],
										   products_weight => $update_prod['weight'],
										   exchange_returns_id => $oID);

					foreach($update_fields AS $post_key => $db_key);

					if(isset($update_prod[$post_key])){  
						$update_fields[$db_key] = $update_prod[$post_key];
					}

					$exchange_amount += $update_prod['final_price'] * $update_prod['qty'];
						
					if($update_cond) {

						tep_db_perform(TABLE_ORDERS_PRODUCTS,$update_fields,'update',$update_cond);

					} else {

	        			tep_db_perform(TABLE_ORDERS_PRODUCTS,$update_fields);

      				}

					if(isset($update_prod['attributes'])) {
						foreach($update_prod['attributes'] AS $attr) {
						    $update_attr_fields = array(products_options=>$attr['option'], products_options_values=>$attr['value']);
								
							if(isset($attr['orders_products_attributes_id']) && $attr['orders_products_attributes_id']) {
		          			tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES,$update_attr_fields,'update',"orders_products_attributes_id='".$attr['orders_products_attributes_id']."'");
							} else {
			    		    	$update_attr_fields['orders_id']=$return->orderid;
								$update_attr_fields['orders_products_id']=$update_prod['orders_products_id'];
								tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES,$update_attr_fields);
							}
		   				} // # END foreach
					} // # END $update_prod['attributes'] isset check
				} // # END else $update_prod['qty']
			} // # END foreach AS $update_prod
		  
			$exchange_amount-=max($_POST['exchange_discount'],0);
		} // # END $_POST['order_products_data'] isset and is not null check
	} // # END $_POST['exchange']


	$check_status_query = tep_db_query("SELECT * FROM " . TABLE_RETURNS . " WHERE returns_id = '" . tep_db_input($oID) . "'");
	$check_status = tep_db_fetch_array($check_status_query);

	// # if status change detected on submit, update return with new status
	if ($check_status['returns_status'] != $status) {

		tep_db_query("UPDATE " . TABLE_RETURNS . " 
					  SET returns_status = '" . (int)$status . "', 
					  last_modified = NOW() 
					  WHERE returns_id = '".tep_db_input($oID)."'
					");

		$order_updated = true;
	}

	
	$notify_status = ($_POST['notify'] == 'on') ? 1 : 0;
	
	tep_db_query("INSERT INTO " . TABLE_RETURNS_STATUS_HISTORY . " 
				  SET returns_id = '".tep_db_input($oID)."', 
				  returns_status = '".$status."',
				  date_added = NOW(),
				  customer_notified = '".$notify_status."', 
				  comments = '".tep_db_input($comments)."'
				");

// tep_db_query("UPDATE " . TABLE_RETURN_PAYMENTS . " set refund_payment_value = '" . $final_price . "' where returns_id = '" . $_GET['oID'] . "' ");

	$updateOrder = new order_return($oID);
	$customer_notified = '0';

	if($_POST['notify'] == 'on') {

		if($_POST['notify_comments'] == 'on') {
			if(!empty($comments) || $comments != '') {
				$comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments)  ."\n\n";
			} else {
				$comments = '';
			}
		}

			$tpl = array();
			$tpl['config'] = array(
			 						store_name => STORE_NAME,
									store_owner_email_address => STORE_OWNER_EMAIL_ADDRESS,
									http_server => HTTP_SERVER,
								   );

			// # If Amazon create alternative links to their amazon account
			$payMeth_query = tep_db_query("SELECT method FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$updateOrder->info['order_id']."'");
			$payMeth = tep_db_fetch_array($payMeth_query);

			if($payMeth['method'] == 'payment_amazonSeller') { 

				$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " 
												  WHERE orders_id = '".$updateOrder->info['order_id']."' 
												  AND method = 'payment_amazonSeller' LIMIT 1
												") or die(mysql_error());
				$theAmazonoID = tep_db_fetch_array($amazonOrder_query);

				// # Amazon found, create alternative links to their amazon account
				$tpl['link'] = array(
								 account_history => 'https://www.amazon.com/gp/css/summary/edit.html?ie=UTF8&orderID='.$theAmazonoID['ref_id'],
								 account => 'https://www.amazon.com/gp/css/order-history?ie=UTF8&ref_=gno_yam_yrdrs',
								 tell_a_friend => 'http://www.amazon.com/gp/browse.html?ie=UTF8&marketplaceID=ATVPDKIKX0DER&me=A1O77D5UJY7IVU',
								);

			} else {
			// # Else create standard link template

				$tpl['link'] = array(
								 account_history => tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $updateOrder->info['order_id'], 'SSL', false),
								 account => tep_catalog_href_link('account.php'),
								 tell_a_friend => tep_catalog_href_link('tell_a_friend.php'),
								);
			}

			$tpl['order_id'] = $updateOrder->orderid;
			$tpl['rma'] = $updateOrder->info['rma_value'];

			$tpl['info'] = $updateOrder->info;
			$tpl['info']['status'] = $orders_status_array[$status];
			$tpl['customer'] = $updateOrder->customer;
			$tpl['date_today'] = strftime(DATE_FORMAT_LONG);
			$tpl['date_requested'] = date('m/d/Y',strtotime($updateOrder->info['date_purchased']));

			$tpl['address'] = array(
									shipping => array(text => tep_address_format($return->delivery['format_id'],$return->delivery,0,'',"\n"), html => tep_address_format($updateOrder->delivery['format_id'],$updateOrder->delivery,1,'',"\n")),
								    billing => array(text => tep_address_format($return->billing['format_id'],$return->billing,0,'',"\n"), html => tep_address_format($updateOrder->billing['format_id'],$updateOrder->billing,1,'',"\n")),
									);
	
			$tpl['products_ordered'] = $products_ordered;

			$tpl['comments'] = $comments;


		if(!empty($tpl['order_id'])) { 
				require_once(DIR_WS_FUNCTIONS . 'email_now.php');
				$extra_emails = (SEND_EXTRA_ORDER_EMAILS_TO !='') ? explode(',',SEND_EXTRA_ORDER_EMAILS_TO) : NULL;
				email_now('return_notify',$tpl,$extra_emails);
	    		$customer_notified = '1';
			} else {
				tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, 'Return confirmation email attempted with no order data from IP '.$_SERVER['REMOTE_ADDR'], STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
			}
	 	
			$order_updated = true;
			$customer_notified = '1';

	} // # END notify


	$refund_shipping = (isset($_POST['refund_shipping'])) ? 1 : 0;
	$refund_shipping_amount = (isset($_POST['refund_shipping']) && !empty($_POST['refund_shipping_amount'])) ? number_format((float)$_POST['refund_shipping_amount'],2, '.','') : 0;


	$restock_quantity = (!empty($_POST['restock_products'])) ? $return->products['qty'] : 0; 

	tep_db_query("UPDATE ".TABLE_RETURNS_PRODUCTS_DATA." 
				  SET refund_shipping = ".$refund_shipping.",
				  refund_shipping_amount = '".$refund_shipping_amount."', 
				  restock_quantity = ".$restock_quantity.",
				  refund_amount = '".$refund_amount."',
				  exchange_amount = '".$exchange_amount."' 
				  WHERE returns_id = '".$oID."'
				");

	// # if customer comments change detected on submit, update with new comments
	if ($check_status['comments'] != $comments) {
		tep_db_query("UPDATE ".TABLE_RETURNS." 
					  SET comments = '".tep_db_input($comments)."',
					  last_modified = NOW()
					  WHERE returns_id = '".tep_db_input($oID)."'
					 ");
        $order_updated = true;
      }


	$order = new order($updateOrder->orderid);

	// # DO NOT fire updateTotals() function of setStatus() function if not marked as complete!
	// # Will prevent submission of dead or non-ready order adjustments to payment API's.
	if($_POST['complete']) {
		$order->updateTotals();
		$order_ok = $order->setStatus();
        $order_updated = true;
		$return_complete = true;
	} else {
		$order_ok = false;
        $order_updated = true;
		$return_complete = false;
	}

	if($order->error) {
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


	if ($order_ok) {
		$theDate = date('Y-m-d H:i:s', time());
		tep_db_query("UPDATE " . TABLE_RETURNS . " 
					  SET returns_date_finished = NOW(), 
					  date_finished = NOW(), 
					  returns_status = 4,
					  last_modified = NOW()
					  WHERE returns_id = '" . $oID . "'
					 ");
	}

	if($order_updated) {	
		if($return_complete) { 
			$messageStack->add_session(SUCCESS_RETURN_CLOSED, 'success');
			$messageStack->add(sprintf(SUCCESS_RETURN_CLOSED,$order->returns[0][rma]), 'success');
		}
		if ($order_ok) {
			$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
			$messageStack->add(sprintf(SUCCESS_ORDER_UPDATED,$order->returns[0][rma], $return->orderid), 'success');
		} else {

			$messageStack->add_session(WARNING_ORDER_UPDATED_NOT_COMPLETE, 'warning');
			$messageStack->add(sprintf(WARNING_ORDER_UPDATED_NOT_COMPLETE,$order->returns[0][rma], $return->orderid, $status_name), 'warning');	
		}
		//if($order_update) { 
		//	$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
		//	$messageStack->add(SUCCESS_ORDER_UPDATED, 'success');
		//}
		if ($refund_by_gv) { 
			$messageStack->add_session(SUCCESS_RETURNED_GIFT, 'success');
		}	
		if($restock_complete) { 
			$messageStack->add_session(SUCCESS_PRODUCT_TO_STOCK, 'success');
			$messageStack->add(SUCCESS_PRODUCT_TO_STOCK, 'success');
		}
		if($customer_notified == '1') { 
			$messageStack->add_session(sprintf(SUCCESS_CUSTOMER_NOTIFIED,$return->billing[name]), 'success');
			$messageStack->add(sprintf(SUCCESS_CUSTOMER_NOTIFIED,$return->billing[name]), 'success');	
		}		

	} else {
		$messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
		$messageStack->add(WARNING_ORDER_NOT_UPDATED, 'warning');
	}

	tep_redirect(tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('action')) . 'action=edit'));
break;

	case 'deleteconfirm':
		$oID = tep_db_prepare_input($_GET['oID']);
		tep_remove_return($oID, $_POST['restock']);
		tep_redirect(tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action'))));
	break;

	// # Remove CVV Number
}

	if ( ($_GET['action'] == 'edit') && ($_GET['oID']) ) {
    $oID = tep_db_prepare_input($_GET['oID']);

    $orders_query = tep_db_query("SELECT returns_id FROM " . TABLE_RETURNS . " WHERE returns_id = '" . tep_db_input($oID) . "'");
    $order_exists = true;
    if (tep_db_num_rows($orders_query) < 1) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    }
  }
 

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Returns Control</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script language="javascript" src="js/prototype.lite.js"></script>


<script language="javascript">

var allowCalc=1;

function setCalc(fl) {
 allowCalc=fl;
 reloadState('ship');
 reloadState('bill');
}

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

function reloadShipping() {
jQuery.noConflict();

	if (!allowCalc) return;

	if(jQuery('#ship_country').val() == '' || jQuery('#ship_postcode').val() == '') {
		jQuery('#shipping_box').html("Please select country and postcode");
	} else {
		jQuery('#shipping_box').html("Loading...");
		var ac_wt = 0;
		var p_list = new Array();
		for (var i=0; orderProducts[i]; i++) {
			if ((orderProducts[i].free_shipping==0) && (orderProducts[i].qty>0)) {
				if (orderProducts[i].separate_shipping>0) p_list[p_list.length]=((orderProducts[i].qty==1) ? '' : orderProducts[i].qty+'x')+orderProducts[i].weight;
				else ac_wt+=orderProducts[i].qty*orderProducts[i].weight;
			}
		}
	
		if (ac_wt>0) p_list.unshift(ac_wt);
		
	jQuery('#shipping_box').load('/admin/shipping_options.php', jQuery.param({
			weights: p_list.join(','),
			zip: jQuery('#ship_postcode').val(),
			cnty: jQuery('#ship_country').val(),
			state: jQuery('#ship_state').val()
			}), function(){
				contentChanged();	
		}); 
	}
}


function setShipping(key,title,value) {
  if (!allowCalc) return;
  setOrderTotal('ot_shipping',Number(value).toFixed(2),title);
}



function ReloadAddProduct(cat,pid) {
jQuery.noConflict();

  var url='/admin/includes/order_add_product.php';
  if (cat) {
    url+='?add_category_id='+cat;
    if (pid) url+='&add_product_id='+pid;
  }
	
  //new ajax(url, {method: 'get', update: $('add_product_box')});

	jQuery('#add_product_box').load(url, function(){
		contentChanged();
	}); 
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
    orderProducts[idx].tax=0;
    orderProducts[idx].attributes=attr;
    orderProducts[idx].final_price=Number(prod.price);
    renderProducts();
  }
  ReloadAddProduct();
}


function currencyFormat(val) {
  return '$'+Number(val).toFixed(2);
}

function removeExchangeItem(item) {
jQuery.noConflict();
	jQuery('[name=order_products_qty_'+item+']').val(0);
	orderProducts[item].qty=0;
	productsCleanup(1);
}

function renderProducts() {
jQuery.noConflict();
  var html=
 '  <table border="0" width="100%" cellspacing="0" cellpadding="2">'
+'	<tr class="dataTableHeadingRow">'
+'	  <td class="dataTableHeadingContent" width="20" align="center"> &nbsp; </td>'
+'	  <td class="dataTableHeadingContent" width="20" align="center">Qty</td>'
+'	  <td class="dataTableHeadingContent" width="400">Product</td>'
+'	  <td class="dataTableHeadingContent">Model</td>'
+'	  <td class="dataTableHeadingContent"width="20" align="center" style="white-space:nowrap">Free Ship?</td>'
+'	  <td class="dataTableHeadingContent" align="right" style="padding:0 10px 0 0">Price</td>'
+'	</tr>\n';
  for (var idx=0;orderProducts[idx];idx++) {
    if (orderProducts[idx].qty<=0) continue;
    html+='<tr class="dataTableRow">';
    html+='<td class="dataTableContent" style="padding:2px 5px;"><img src="images/remove-icon.gif" style="cursor:pointer" onclick="removeExchangeItem('+idx+');"></td>';
    html+='<td class="dataTableContent"><input type="text" name="order_products_qty_'+idx+'" value="'+HTMLescape(orderProducts[idx].qty)+'" size="3" onChange="orderProducts['+idx+'].qty=this.value; productsCleanup(1);" style="text-align:center"></td>';
    html+='<td class="dataTableContent">'+HTMLescape(orderProducts[idx].name);
    if (orderProducts[idx].attributes && orderProducts[idx].attributes[0]) {
      html+='\n<table>\n';
      for (var aidx=0;orderProducts[idx].attributes[aidx];aidx++) {
        html+='<tr><td>&bull;</td>\n';
        html+=' <td class="main">'+HTMLescape(orderProducts[idx].attributes[aidx].option)+':&nbsp;</td>';
        html+=' <td class="main"><b>'+HTMLescape(orderProducts[idx].attributes[aidx].value)+'</b></td>';
        html+='</tr>';
      }
      html+='</table>';
    }
    html+='</td>\n';
    html+='<td class="dataTableContent">'+(orderProducts[idx].model ? HTMLescape(orderProducts[idx].model) : '-')+'</td>\n';
    var cost=Number(orderProducts[idx].final_price)*orderProducts[idx].qty;
//    tax+=orderProducts[idx].tax*cost/100;
    html+='<td class="dataTableContent" align="center"><input type="checkbox" name="order_products_free_shipping_'+idx+'" value="1"'+(orderProducts[idx].free_shipping>0?' checked':'')+' onChange="orderProducts['+idx+'].free_shipping=(this.checked?1:0); reloadShipping()"></td>\n';
    html+='<td class="dataTableContent" align="right" style="padding:0 10px 0 0">'+cost.toFixed(2)+'</td>\n';
    
    html+='</tr>\n';
  }
  html+='</table>';
  jQuery('#products_box').html(html);
  calcExchangeAmount();
}

function fmtCurr(num) {
  num=Number(num);
  if (isNaN(num)) num=0;
  var rs=num.toFixed(2);
  if (num<0) rs='<span style="color:red">'+rs+'</span>';
  return rs;
}

function calcExchangeAmount() {
jQuery.noConflict();
  var subtotal=0.0;
  var tax=0.0;
  for (var idx=0;orderProducts[idx];idx++) {
    var cost=Number(orderProducts[idx].final_price)*orderProducts[idx].qty;
    subtotal+=cost;
    tax+=orderProducts[idx].tax*cost/100;
  }
  var diff = (subtotal + tax) - <?php echo $return->products['final_price'] * ($return->products['qty'] + 0) ?>;
	if (jQuery('#exchange_discount').val() == '') {
		jQuery('#exchange_discount').val((diff - <?php echo ($return->products['exchange_amount'] + 0) ?>).toFixed(2));
	} else if (jQuery('#exchange_discount').val() < 0) {
		jQuery('#exchange_discount').val(0);
	}
  jQuery('#exchange_price_diff').html(fmtCurr(diff));
  jQuery('#exchange_amount').html(fmtCurr(diff-jQuery('#exchange_discount').val()));
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
jQuery.noConflict();
  jQuery('#order_products_data').val(implodeList(orderProducts,''));
//  alert(jQuery('#order_products_data').val());
  return true;
}

jQuery(document).ready(function(e){
jQuery.noConflict();
	jQuery('.infoTable tr:even').css("background-color","#EBF1F5");
	jQuery('.completeTable tr:even').css("background-color","#EBF1F5");


	// # detect if restock product checkbox is checked.
	// # warns if status is not set to complete and prevents incomplete return

	jQuery("#restock_products").click(function(){

		var status = jQuery("#status").find("option:selected").attr("value");

		switch (status){

			case "1":
			case "2":
			case "3":
					alert('You may not add product back to inventory without first finalizing return and setting return status to Complete!');
					jQuery("#status").val(<?php echo $return->info['orders_status']?>);	

				if(jQuery("#restock_products").is(":checked")) { 
					jQuery('#restock_products').attr('checked', false);
				}	

			break;			
	
		}

	});
});



function updateComment(obj) {

	jQuery.noConflict();

<!--
	var mytextarea = jQuery('[name="comments"]'); 
    var thedate = '<?php echo date('l, F jS Y')?>'; 
    
	if(!obj.length){
		jQuery("textarea").empty();
	}

	if (!mytextarea.length || mytextarea.val() == "") {
		//mytextarea.append(thedate+':');
		mytextarea.append("\n");
		mytextarea.append(obj);
    } else { 
        mytextarea.empty();
        mytextarea.append(thedate+':');
		mytextarea.append("\n");
        mytextarea.append(obj);
    }
//-->
}

</script>

<style type="text/css">

body {
	min-height:800px; 
	width:100%;
	margin:0 auto;
}

.cbutton { 
	font:bold 12px arial;	
	padding: 2px;
	
	border: 1px solid #CCC;
	border-bottom: 1px solid #000000; 
	border-right: 1px solid #000000;  
	cursor: pointer;
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

.priceDiffTable {
	border-collapse:collapse;
	margin:0 0 5px 5px;
}

.priceDiffTable td {
	border:1px solid #CCC;
	padding:4px;
}

.completeTable {
border-collapse:collapse;
}

.completeTable td {
	font:bold 12px arial;
	color:#000;
	border:solid 1px #FFFFFF;
	padding:7px;
	height:auto;
	vertical-align:middle;
}
</style>

</head>
<body style="background:transparent;">
<?php
	require(DIR_WS_INCLUDES . 'header.php');

	// # if order is from Amazon, color amazon-orange
	if($return->info['payment_method'] == 'payment_amazonSeller') { 
		$sourceStyle = '#EC994F';
	} else { 
	// # if customers group is vendor then color GREEN > if not standard blue
		$sourceStyle = ($customers_group > 1) ? '#6EAC6D' : '#6295FD';
	}

	
	// # Edit the Return
	if ( ($_GET['action'] == 'edit') && ($order_exists) ) {
	
		$refund_methods = array();
		$refund_method_array = array();
		$refund_methods_query = tep_db_query("SELECT * FROM " . TABLE_REFUND_METHOD . " ");

		while ($refund_method_name = tep_db_fetch_array($refund_methods_query)) {

    		$refund_methods[] = array('id' => $refund_method_name['refund_method_name'],
        			                  'text' => $refund_method_name['refund_method_name']
									  );

			$refund_method_array[$refund_methods['refund_method_id']] = $refund_methods['refund_method_name'];

		} // # END while

		$restock_query = tep_db_query("SELECT configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'DEFAULT_RESTOCK_VALUE'");
		$restock = tep_db_fetch_array($restock_query);

		$return_complete_query = tep_db_query("SELECT returns_date_finished FROM " . TABLE_RETURNS . " WHERE returns_id = '" . $oID . "'");
		$return_finnished = tep_db_fetch_array($return_complete_query);

 ?>
		<table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding:0 5px;">
			<tr>
				<td style="padding:14px 0 0 0">
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
				        <tr>
					        <td class="pageHeading"><div style="display:inline-block"><img src="images/returns_icon.gif" alt="" width="40" height="38"></div> <div style="display:inline-block; height:40px; vertical-align:middle"><?php echo HEADING_TITLE; ?></div></td>
							<td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
				        </tr>
					</table>

					<?php echo tep_draw_form('status', FILENAME_RETURNS, tep_get_all_get_params(array('action')) . 'action=update_order','post',' onSubmit="return this.exchange.checked?prepareProductsData():true"');?>

						<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td>
									<table width="100%" border="0" cellspacing="0" cellpadding="0">
								        <tr>
											<td colspan="3">
<!-- // # Start top info table -->

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="background-color:#FFF; border-collapse:collapse; margin: 0 0 5px 0">
          <tr>
            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF;">
<?php

	$customer_query = tep_db_query("SELECT c.customers_group_id
									FROM " . TABLE_CUSTOMERS . " c
									LEFT JOIN ".TABLE_ORDERS." o ON o.customers_id = c.customers_id
									WHERE o.orders_id = '" . $return->orderid. "'
								   ");

	$customer_check = tep_db_fetch_array($customer_query);

	$customers_group = (int)$customer_check['customers_group_id'];

	echo '<tr>
				<td style="border-top solid 1px #8CA9C4; height:21px; background-color:'.$sourceStyle.'; font:bold 13px arial;color:#FFFFFF;">&nbsp; Billing Information:</td></tr>';

	$comp_query = tep_db_query("SELECT a.* FROM " . TABLE_ADDRESS_BOOK . " a
								  LEFT JOIN ".TABLE_ORDERS." o ON a.customers_id = o.customers_id
								  WHERE o.orders_id = '" . $return->orderid. "'");
	$comp_check = tep_db_fetch_array($comp_query);

	$comp = $comp_check['entry_company'];

	$contact_name_query = tep_db_query("SELECT pay.refund_payment_value, r.* 
										FROM " . TABLE_RETURN_PAYMENTS . " pay
										LEFT JOIN " . TABLE_RETURNS . " AS r ON r.returns_id = pay.returns_id 
										WHERE pay.returns_id = '" . $oID . "'
									  ");
	$contact_name = tep_db_fetch_array($contact_name_query);
	$contact_user_name = $contact_name['contact_user_name'];

	if(empty($contact_user_name)) $contact_user_name = $return->billing['name'];
?>
                    <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px Tahoma; color:#0B2D86">&nbsp; Attn:&nbsp; <a href="customers.php?cID=<?php echo $comp_check['customers_id']; ?>&amp;action=edit"><b><?php echo $contact_user_name; ?></b></a></td>
                          </tr>
<?php 
	if($customers_group > 1) { 
		echo '<tr><td style="padding:5px; background-color:#FFFFC6; font:bold 11px Tahoma; color:#CC6600">&nbsp;Vendor: <a href="customers.php?cID='. $comp_check['customers_id'].'&amp;action=edit">'.$comp.'</a></td></tr>';
	}
?>
                       </table></td>
                    </tr>
		    <tr>
                      <td style="padding:1px 0 0 0; background-color:#F0F5FB;">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
	      <?php echo (!empty($return->billing['company']))? '<tr><td>'.$return->billing['company'].'</td></tr>' : '';		?>
			  <tr><td><?php echo $return->billing['street_address']?><?php echo $return->billing['suburb'] ? ','.$return->billing['suburb'] : ''?></td></tr>
			  <tr><td><?php echo $return->billing['city']; ?>, <?php echo $return->billing['state']?></td></tr>
			  <tr><td><?php echo $return->billing['postcode']; ?> - <?php echo $return->billing['country']; ?></td></tr>
			  <tr><td>Phone: <?php echo $return->customer['telephone']; ?></td></tr>
			  <?php if(!empty($return->customer['fax'])) echo '<tr><td class="tableinfo_orders">Fax: '.$return->customer['fax'].'</td></tr>';?>
			  <tr><td>Email: <?php echo '<a href="mailto:' . $return->customer['email_address'] . '"><u>' . $return->customer['email_address'] . '</u></a>'; ?></td></tr>
</table>
				</td>
                </tr>
            	</table>
			</td>

            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:<?php echo $sourceStyle ?>;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Shipping Information:</span></td>
                      
                     </tr>
                    <tr>
                       <td  style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px arial; color:#0B2D86">&nbsp; Attn:&nbsp; <?php echo $return->delivery['name']; ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td style="padding-top:1px; background-color:#F0F5FB;">

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                        <tr>
                          <td><?php echo $return->delivery['street_address']; ?>, <?php echo $return->delivery['suburb']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $return->delivery['city']; ?>, <?php echo $return->delivery['state']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $return->delivery['postcode']; ?> - <?php echo $return->delivery['country']; ?></td>
                          </tr>
                        <tr>
                          <td>Phone: <?php echo $return->customer['telephone']; ?></td>
                          </tr>
							<tr>
						<td> Ship Method:&nbsp;

<?php 
//$UPSTracking = implode("\n", unserialize($return->info['ups_track_num']) ?: array());
//$UPSTracking = serialize(explode("\n", $UPSTracking));
//$UPSTracking = unserialize($UPSTracking);

$trackingResult = tep_db_query("SELECT * FROM orders_shipped WHERE orders_id = '".$return->orderid."'");

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

		echo 'target="_blank"> ' . $track["tracking_number"] . ' </a> - '. date('m/d/Y', strtotime($track["ship_date"]));
	}

} else { 
	
	echo ' none found ';
}

echo '</td>
	</tr>
 </table>';

$payMeth = (!empty($return->info['payment_method']) ? $return->info['payment_method'] : '-');

?>			
			</td>
		</tr>
	</table>
</td>
<td width="33%" valign="top" style="border-top:1px solid #8CA9C4;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:<?php echo $sourceStyle ?>;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Returns Information:</span></td>
                      
                     </tr>
                    <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:normal 11px tahoma; color:#0B2D86"> &nbsp; <b>Return ID:&nbsp; <?php echo $oID?></b> &nbsp; <b>&raquo;</b> &nbsp; Order ID:&nbsp; <a href="orders.php?date_from=&date_to=&cFind=<?php echo $return->orderid?>&action=cust_search&status=&open=1"><?php echo $return->orderid ?></a></td>
                          </tr>
                       </table></td>
                    </tr>

                    <tr>
                      <td valign="top" style="padding-top:1px; background-color:#F0F5FB;">
					  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                       <tr>
						<td><?php echo HEADING_TITLE_SEARCH . ' ' . $return->info['rma_value']?></td>
					</tr> 
						<tr>
                          <td><b>Refund Method:&nbsp;</b> 
							<?php
							if($payMeth == 'payment_amazonSeller') { 
							echo 'Amazon Seller API	</td>';
							} elseif ($payMeth == 'payment_authnet'){
								echo 'AuthorizeNet Credit Card	</td></tr><tr><td>&nbsp;</td>';
							} else {
								echo $payMeth.'	</td></tr><tr><td>&nbsp;</td>';
							}
?>					
                          </tr>
<?php
    if (tep_not_null($return->info['cc_type']) || tep_not_null($return->info['cc_owner']) || tep_not_null($return->info['cc_number'])) {
?>
                        <tr>
                          <td>Card Type:&nbsp; <?php echo $return->info['cc_type']; ?></td>
                          </tr>
                        <tr>
                          <td>Card Name:&nbsp; <?php echo $return->info['cc_owner']; ?></td>
                          </tr>
                        <tr>
                          <td>Card #:&nbsp; <?php echo $return->info['cc_number']; ?></td>
                          </tr>
                        <tr>
                          <td>&nbsp;</td>
                        </tr>						
						<?php
    }
?>
                        <tr>
                          <td>
						  Date Purchased:&nbsp; <?php echo tep_date_short($return->info['date_purchased']);?>
						  </td>
                        </tr>
                        <tr>
                          <td>Return Start Date:&nbsp; <font style="text-transform:uppercase;"><?php echo $return->info['date_purchased'] ? date('m/d/Y',strtotime($return->info['date_purchased'])) : ''; ?></font>
						   </td>
                        </tr>
<?php 
	if($payMeth == 'payment_amazonSeller') {

		$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$return->orderid."' AND method = 'payment_amazonSeller' LIMIT 1");

		$theAmazonoID = tep_db_fetch_array($amazonOrder_query);

		echo '<tr><td>Amazon Order ID: &nbsp; <a href="https://sellercentral.amazon.com/gp/returns/list?searchType=orderId&keywordSearch='.$theAmazonoID['ref_id'].'&preSelectedRange=365&exactFromDate=&exactToDate=" target="_blank" style="color:#FF0000"><b>'.$theAmazonoID['ref_id'].'</b></a></td>
			</tr>
			<tr>
				<td align="center" style="background:#FFF url(images/amazonSeller-logo.jpg) no-repeat center 5px; background-size: contain; height:37px">&nbsp;</td>
			</tr>';
	} 
?>

                      </table>
					  
					  </td>
                    </tr>
            </table></td>
          </tr>
        </table>

<!-- // # END top info table -->


		</td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F0F5FB; margin:0; padding:0">
		<tr>
            <td style="height:20px; background-color:<?php echo $sourceStyle ?>; font:bold 13px arial;color:#FFFFFF; margin:0; padding:0" colspan="7">&nbsp;Return ID:&nbsp; <?php echo $_GET['oID'] ?></td>
        	</tr>
		<tr>
            <td bgcolor="#DEEAF8" style="padding: 0 0 0 10px; height:20px; font:bold 11px arial; color:#0B2D86"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
            <td align="center" bgcolor="#DEEAF8" style="width:55px; font:bold 11px arial; color:#000000;">Qnty.</td>
            <td align="center" bgcolor="#DEEAF8" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Model</td>
            <td align="right" bgcolor="#DEEAF8" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Price</td>
            <td align="right" bgcolor="#DEEAF8" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Tax %</td>
            <td align="right" bgcolor="#DEEAF8" style="padding: 0 7px 0 0; width:100px; font:bold 11px arial; color:#000000;">Sub-total</td>
            <td align="right" bgcolor="#DEEAF8" style="padding: 0 7px 0 0; width:100px; font:bold 11px arial; color:#0B2D86">Total</td>
		</tr>
 
      <?php
    if ( (($return->info['cc_type']) || ($return->info['cc_owner']) || ($return->info['cc_number']) || ($return->info['cvvnumber'])) ) {
?>
 <tr>
    <td style="height:10px;"></td>
  </tr>
  <tr>
    <td><table border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td class="main"><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
        <td class="main"><?php echo $return->info['cc_type']; ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
        <td class="main"><?php echo $return->info['cc_owner']; ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
        <td class="main"><?php echo $return->info['cc_number']; ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
        <td class="main"><?php echo $return->info['cc_expires']; ?></td>
      </tr>
	</table></td>
  </tr>
<?php
    }

      echo '<tr>
				<td class="tableinfo_right-btm" style="text-align:left; font:bold 12px arial; padding:10px;">' . $return->products['name'] . '</td>

				<td align="center" class="tableinfo_right-btm align_right" style="text-align:center; font:bold 12px arial;">' . ($return->products['qty'] ? $return->products['qty'] : '&nbsp;') . '</td>
				<td class="tableinfo_right-btm" align="center">' . ($return->products['model'] ? $return->products['model'] : '-') . '</td>

				<td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial;">'.$currencies->format($return->products['final_price'], true, $return->info['currency'], $return->info['currency_value']) .'</td>

				<td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial;">' . tep_display_tax_value($return->products['tax']) . '%</td>

				<td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial;"><b>' . $currencies->format($return->products['final_price']*$return->products['qty'], true, $return->info['currency'], $return->info['currency_value']) . '</b></td>

				<td align="center" class="tableinfo_right-end align_right" style="font:bold 12px arial;"><b>' . $currencies->format(tep_add_tax($return->products['final_price'], $return->products['tax']) * $return->products['qty'], true, $return->info['currency'], $return->info['currency_value']) . '</b></td>
			</tr>';
?>


</table>
		</td>
	</tr>
	<tr>
                      <td colspan="7">
					      <table border="0" cellspacing="0" cellpadding="2" width="100%" class="commentsTable">
                          <tr>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_STATUS; ?></b></th>
                            <th width="50%" class="smallText" align="center"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></th>
                          </tr>
<?php
    $returns_history_query = tep_db_query("SELECT returns_status, date_added, customer_notified, comments 
										   FROM " . TABLE_RETURNS_STATUS_HISTORY . " 
										   WHERE returns_id = '".tep_db_input($oID)."' 
										   ORDER BY date_added
										  ");

	if(tep_db_num_rows($returns_history_query) > 0) {

		while ($returns_history = tep_db_fetch_array($returns_history_query)) {
        	echo '<tr>
					<td class="smallText" align="center">' . date('m/d/Y h:ia', strtotime($returns_history['date_added'])).'</td>
					<td class="smallText" align="center">';
			if($returns_history['customer_notified'] == '1') {
				echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK) . "</td>\n";
			} else {
				echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) . "</td>\n";
        	}

			echo '<td class="smallText" align="center">' . $orders_status_array[$returns_history['returns_status']] . '</td>
				  <td class="smallText" style="padding: 10px 20px; background-color:#FFF; text-align:justify;">' . nl2br(tep_db_output($returns_history['comments'])) . '&nbsp;</td>
				</tr>';
		}


	} elseif(strlen($return->info['comments']) != 0) {

		 echo '<tr>
				<td class="smallText">' . $return->info['refund_date'] . '</td>
				<td class="smallText">' . tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) .'</td>
				<td class="smallText" align="center">' . $order_status['returns_status_name'] . '</td>
				<td class="smallText" style="background-color:#FFF; text-align:justify;">' . nl2br($return->info['comments']) . '</td>
			  </tr>';

	} else { // # Invoked when old code (without status history) is phased out in the next version

	echo '<tr>
			<td class="smallText" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>
		 </tr>';
	}
?>
		</table>
	</td>
                    </tr>
<?php if ($return_finnished['returns_date_finished'] == 0) {
?>
  <tr>
    <td class="main" style="padding: 10px 0 5px 0"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
  </tr>
  <tr>
    <td class="main"><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5', '', 'style="width:100%; height:100px;"'); ?></td>
  </tr>
  <tr>
    <td style="padding:5px">

<span style="font:bold 11px arial;">Enter comments:</span> &nbsp; 
<button class="cbutton" onclick="updateComment(''); updateComment('Your return request has been approved. Provided the item is in its original condition and packaging, you may ship the item back to us using the provided return shipping label. This label is included with the packing slip that was included with your package(s). Shipment via a traceable method is highly recommended. <br /><br />Please note that all returns must be received within 15 days of RMA issuance. Upon receipt of the returned item, a refund for the amount of the purchased item will be issued electronically to the original payment method provided when the order was placed.'); return false;">Accepted</button> 

<button class="cbutton" onclick="updateComment(''); updateComment('Your return request has been declined.<br />Please contact us for more information.'); return false;">Rejected</button> 

<button class="cbutton" onclick="updateComment(''); updateComment('Your return request has been processed. A restocking fee will apply for this product return.<br />Your refund should appear within one or two billing statement cycles.'); return false;">Restock Charge Applied</button> 

<button class="cbutton" onclick="updateComment(''); updateComment('Your account has been refunded and made in the same manner of your original payment. Your refund should process within the next 48 hours and your statement should reflect this within one or two billing  cycles.'); return false;">Refund Made</button> 

<button class="cbutton" onclick="updateComment(''); updateComment('Your return has been processed. We\'ve sent a separate message containing a store credit code, issued in exchange for your recent product return. You can redeem this code during checkout when placing your next order. After redeeming, the credited funds will be available in your account. Simply check the box that will appear near the bottom of the payment screen to apply the credit to your order. On the final confirmation screen, please check that the credit is properly reflected in your account prior to submitting your order. Please let us know if you have any questions.'); return false;">Credit Issued</button> 

<button class="cbutton" onclick="updateComment(''); return false;">Clear all</button>

<div style="display:inline-block; height: 20px; vertical-align:middle; font:bold 11px arial; padding: 0 0 0 5px">
<?php 
	echo tep_draw_checkbox_field('notify', 'on', true).' <b>' .ENTRY_NOTIFY_CUSTOMER.'</b>';
	echo tep_draw_hidden_field('notify_comments', 'on', true);
?>
</div>
</td>
</tr>
<tr>
<td><table width="100%" border="0" cellspacing="0" cellpadding="2">
    <tr>
      <td>
		<table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" colspan="2" style="padding:15px 0"><b><font color="CC0000"><?php echo ENTRY_STATUS; ?></font></b> &nbsp; 

<?php echo tep_draw_pull_down_menu('status', $orders_statuses, $return->info['orders_status'], 'id="status"'); ?>
</td>
          </tr>
<?php 

	if($return->info['department'] == 'Exchange') { 
		$isExchange = 'products_exchanged';
		$exchange = true;
	} else {
		$isExchange = 'products_returned';
		$exchange = false;		
	}

	// # refund payment	
	$refund_payment = ($return->info['refund_amount'] > 0 || $return->info['refund_method'] == 'Refund') ? 1 : 0;
	$refundMeth = ($refund_payment == 1) ? 'Refund' : 'Refund';
	$price_new='';
	if($refund_payment == 1) { 
		$price_new = ($return->info['refund_amount']) ? number_format($return->info['refund_amount'],2, '.','') : number_format($return->products['final_price']*$return->products['qty'],2, '.','');
	} 
	// # Restock charge, derived from config value
	$restock_query = tep_db_query("SELECT configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'DEFAULT_RESTOCK_VALUE'");
	$restock = tep_db_fetch_array($restock_query);
	$restock_charge =  round((float)$restock['configuration_value'] * 100 ) / 100;
	$work_out_charge = (tep_add_tax($return->info['refund_amount'],$return->products['tax']) / 100) * $restock_charge;
?>
          <tr>
            <td class="main" colspan="2" valign="top"><b><?php echo TEXT_CUSTOM_PREF_METHOD; ?></b>  &nbsp; <b style="color:red"><?php echo $return->info['refund_method']; ?></b></td>
          </tr>
          <tr>
            <td class="main" colspan="2" style="padding:10px 0 0 0;"><?php echo tep_draw_checkbox_field('refund_payment', '', $refund_payment,'','onclick="document.getElementById(\'div_refund_payment\').style.display=this.checked?\'\':\'none\';  contentChanged();"')?> <b>Refund Payment</b><br>
<div id="div_refund_payment" style="<?php echo ($refund_payment) ? '' : 'display:none'?>; border:1px dashed #CCC; background-color:#FFF; padding:0 5px">
		<table border="0" cellspacing="0" cellpadding="3" style="margin:5px">
          <tr>
            <td class="main" valign="top"><b><font color="CC0000"><?php echo ENTRY_PAYMENT_METHOD; ?></font></b></td>
            <td class="main"><?php echo tep_draw_pull_down_menu('refund_method', $refund_methods, $refundMeth);?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><b><?php echo ENTRY_PAYMENT_REFERENCE; ?></b></td>
            <td class="main"><?php echo tep_draw_input_field('refund_reference', $return->info['payment_reference']); ?></td>
          </tr>
          <tr>
            <td class="main"><b><font color="CC0000"><?php echo ENTRY_PAYMENT_AMOUNT; ?></font></b></td>
            <td class="main"><?php echo tep_draw_input_field('refund_amount', $price_new ) . tep_draw_hidden_field('refund_date', ($return->info['refund_date'] > 0 ? $return->info['refund_date'] : ''), '') . tep_draw_hidden_field('add_tax', $return->products['tax']); ?>
			</td>
          </tr>
          <tr>
            <td class="main"><b><?php echo ENTRY_RESTOCK_CHARGE; ?></b></td>
            <td class="main"><?php echo tep_draw_checkbox_field('restock_charge', '', false); ?>&nbsp;&nbsp;(<?echo $currencies->format($work_out_charge); ?>)</td>
          </tr>
	  </table>
</div>
			</td>
				</tr>
<?php
	$refund_gv = ($return->info['refund_method'] == 'Store Credit') ? 1 : 0;
	$gv_row = NULL;
	$gv_price='';

	if ($refund_gv == 1) {

		$gv_row_query = tep_db_query("SELECT * FROM ".TABLE_COUPONS." c 
									  LEFT JOIN ". TABLE_COUPON_EMAIL_TRACK ." e ON e.coupon_id = c.coupon_id
									  WHERE e.unique_id='".$return->info['refund_gv_id']."'
									 ");

		$gv_row = tep_db_fetch_array($gv_row_query);

		if(!empty($gv_row['coupon_amount']) && $gv_row['coupon_amount'] > 0) { 
			$gv_price = $gv_row['coupon_amount'];
		} else { 
			$gv_price = number_format((float)$return->products['final_price']*$return->products['qty'],2,'.','');
		}

	}
	$gv_amount = ($gv_row) ? $gv_row['coupon_amount'] : $gv_price;
?>	  
          <tr>
            <td class="main" colspan="2" style="padding:10px 0 0 0;"> <?php echo tep_draw_checkbox_field('refund_gv', '1', $refund_gv,'','onclick="document.getElementById(\'div_refund_gv\').style.display=this.checked?\'\':\'none\';  contentChanged();"'); ?> <b><?php echo SUCCESS_RETURNED_GIFT?></b>
			</td>
          </tr>
	  <tr><td colspan="2" id="div_refund_gv" style="<?php echo ($refund_gv) ? '' : 'display:none' ?>; border:1px dashed #CCC; background-color:#FFF">

<table width="100%" border="0" cellspacing="0" cellpadding="0">

<?php 
	if ($gv_row) { 
		echo '<tr><td class="main" width="35%"><b>Gift Cert Code</b></td>
				  <td width="65%" class="main">';
		if ($gv_row['coupon_active']=='Y') { 
			echo'<span style="color:#6600CC; font-weight:700">'. $gv_row['coupon_code'].'</span>';
		} else { 
			echo '<span style="color:#7F7F7F">[redeemed]</span>';
		} 
		echo '</td></tr>';
	} 
?>
          <tr>
            <td class="main"><b><font color="CC0000">Gift Cert Amount</font></b></td>
            <td class="main"><?php echo tep_draw_input_field('gv_amount', $gv_amount ); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><b><?php echo TEXT_GIFT_COMMENT; ?></b></td>
            <td class="main" width="65%"><?php echo tep_draw_textarea_field('gv_comments', 'soft', '60', '5'); ?></td>
          </tr>
	  </table>
	</td>
		</tr>  
          <tr>
            <td class="main" colspan="2" style="padding:10px 0 0 0;">
<?php

			echo tep_draw_checkbox_field('exchange', '1',$exchange,'','onclick="document.getElementById(\'div_exchange\').style.display=this.checked?\'\':\'none\';  contentChanged();" id="exch"'); ?> 

			<b>Exchange / Replacement</b>

			<div id="div_exchange" style="display:<?php echo ($exchange) ? 'block':'none'?>; border:1px dashed #CCC; background-color:#FFF">
				<table width="100%" border="0" cellspacing="0" cellpadding="4">
		          <tr>
        		    <td class="main" valign="top"><div id="products_box"></div></td>
		          </tr>
        		  <tr>
		            <td class="main" valign="top"><div id="add_product_box"></div></td>
        		  </tr>
		          <tr>
					<td>
						<table border="0" cellpadding="0" cellspacing="0" class="priceDiffTable">
        		    <td class="main" style="font:bold 12px arial;">Price Difference: </td> <td class="main"> <span id="exchange_price_diff" style="color:green;"></span></td>
		          </tr>
		          <tr>
        		    <td class="main"><b>Exchange Discount:</b> </td> <td class="main"><input type="text" name="exchange_discount" id="exchange_discount" size="7" onChange="calcExchangeAmount();"></td>
		          </tr>
		          <tr>
		            <td class="main"><b>Apply Difference:</b> </td> <td class="main"><span id="exchange_amount"></span></td>
		          </tr>
	    	  </table>
			</td>
		</tr>
	</table>
			<input type="hidden" name="order_products_data" id="order_products_data" value="">
	<script type="text/javascript">
		orderProducts = <?php echo tep_js_quote_array($return->exchange)?>;
		renderProducts();
		ReloadAddProduct();
	</script>
		</div>
	</td>
</tr>
		<tr>
            <td class="main" align="left" colspan="2" style="padding:10px 0 0 0;"><?php echo tep_draw_checkbox_field('restock_products', '', $return->products['restock_quantity'], 'false', 'id="restock_products"'); ?> <b><?php echo TEXT_BACK_TO_STOCK; ?></b></td>
          </tr>
          <tr>
            <td class="main" align="left" style="padding:10px 0 0 0;"><?php echo tep_draw_checkbox_field('refund_shipping', '', $return->products['refund_shipping'],'','onClick="$(\'div_refund_shipping\').style.display=this.checked?\'\':\'none\'"'); ?> <b>Refund Shipping</b>
			</td>
            <td class="main" align="left" style="padding:0 0 0 10px;">

<span id="div_refund_shipping" style="<?php echo $return->products['refund_shipping']?'':'display:none'?>">Total shipping charge for this order: <?php echo sprintf('$%.2f',tep_db_read("SELECT value FROM orders_total WHERE orders_id='".$return->orderid."' AND class='ot_shipping'",NULL,'value'))?>

<br>Refund <input type="text" size="7" name="refund_shipping_amount" value="<?php echo $return->products['refund_shipping_amount']?>"></span></td>
          </tr>
        </table></td>
    <tr style="display:none;">
      <td class="main"><?php echo tep_draw_checkbox_field('complete', 'on', false); ?> <b><?php echo TEXT_COMPLETE_RETURN; ?></b></td>
    </tr>
    <tr>
      <td valign="top" style="padding:10px 0 0 0;"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
    </tr>
  </table></td>
</tr>

<tr>
  <td align="right"><?php echo

				'<a href="' . tep_href_link(FILENAME_RETURNS_INVOICE, 'oID=' . $_GET['oID']) . '" target="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a> <a href="' . tep_href_link("returns_packingslip.php", 'oID=' . $_GET['oID']) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a> <a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
</tr>
<?php 

// # else return is finished 
} else {

	$order = new order($return->orderid);

	$cust_meth = $return->info['refund_method'];
	$ref = $return->products['refund_amount'];
	$exch = $return->products['exchange_amount'];
	$giftv = $return->info['refund_gv_id'];
	$final_price = tep_add_tax($return->products['final_price'], $return->products['tax']) * $return->products['qty'];
	$refship = ($return->products['refund_shipping'] == 1) ? number_format((float)$return->products['refund_shipping_amount'],2,'.','') : '0.00';
	$return_date = ($return->info['refund_date'] > 0) ? $return->info['refund_date'] : $return->info['date_finished'];

	if($order->products[0]['return'] == '1' && $ref > 0) { 

		$refund_methods = ($final_price > $ref) ? 'Partial Refund' : 'Full Refund';

	} elseif($order->products[0]['exchange'] == '1' || $exch > 0) { 

		$refund_methods = 'Exchange';

	} elseif(!is_null($giftv)) { 
		$refund_methods = 'Store Credit';
	} elseif($ref > 0 && $cust_meth == 'PayPal') { 
		$refund_methods = 'PayPal';
	}  elseif(($exch == 0 && $ref == 0) && $refship > 0) { 
		$refund_methods = 'Shipping Refund';
	} else {
		$refund_methods = '--';	
	}
?>
		<tr><td>
<table cellpadding="5" cellspacing="0" border="0" width="100%">
	<tr>
    <td valign="top">

			<table cellpadding="0" cellspacing="0" border="0" class="completeTable">
			<tr>
                <td class="main" valign="top"><b><?php echo CUSTOMER_PREFERENCE;?></b>: &nbsp;</td>
                <td class="main"><?php echo $return->info['refund_method']; ?></td>
              </tr>
              <tr>
                <td class="main" valign="top"><b>Actual Method Used</b>: &nbsp;</td>
                <td class="main" style="color:red"><?php echo $refund_methods ?></td>
              </tr>
<?php if($return->info['payment_reference']) {
        echo '<tr>
                <td class="main" valign="top"><b>'. ENTRY_PAYMENT_REFERENCE .'</b>: &nbsp;</td>
                <td class=main>'. $return->info['payment_reference'].'</td>
              </tr>';
}

	if($ref > 0) { 
		echo '<tr>
                <td class="main"><b>' . ENTRY_PAYMENT_AMOUNT . '</b> &nbsp;</td>
                <td class="main">' . $currencies->format($ref) .'</td>
              </tr>';
	} elseif($exch > 0) {
        echo '<tr>
                <td class="main"><b>Exchange Difference</b>: &nbsp;</td>
                <td class="main">'.$currencies->format($exch).'</td>
              </tr>';
	}	
        echo '<tr>
                <td class="main"><b>Shipping Refund</b>: &nbsp;</td>
                <td class="main">' . $currencies->format($refship) .'</td>
              </tr>
			  <tr>
                <td class="main"><b>' . $refund_methods .' Date</b>: &nbsp;</td>
                <td class="main">' .tep_date_short($return_date) .'</td>
              </tr>';
?>
</table></td>
<td align="right" valign="top"><a href="<?php echo tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
</tr></table>
</td>
</tr>
<?php } ?>
</table>

</form>

<script type="text/javascript">
jQuery.noConflict();

jQuery(document).ready(function($){


<?php if (GOOGLE_ANALYTICS_UID) {
	$getHostname = str_replace('www.', '', $_SERVER['HTTP_HOST']);
?>
	$('form[name="status"]').submit(function(e) {
		//e.preventDefault();
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', '<?php echo GOOGLE_ANALYTICS_UID ?>', '<?php echo $getHostname ?>');

		ga('require', 'ec');

		var status = $('#status :selected').val();
		
		if(status == '4') {
			ga('ec:addProduct', {
				'id': '<?php echo $return->products['id'];?>',       // Product ID is required for partial refund.
				'quantity': <?php echo $return->products['qty'];?>    // Quantity is required for partial refund.
			});

			ga('ec:setAction', 'refund', {
				'id': '<?php echo $return->info['order_id'];?>',       // Transaction ID is required for partial refund.
			});
		
			ga('send', 'event', 'Ecommerce', 'Refund', {'nonInteraction': 1});	
			//alert('success?');
		}

	});
<?php } ?>

$('#status').change(function() {
	var status = $('#status :selected').val();
	if(status == '4') {
		jQuery('[name="complete"]').prop('checked',true);
	} else { 
		jQuery('[name="complete"]').prop('checked',false)
	}
});

$('[name="refund_payment"]').click(function() {
	if($('[name="refund_gv"]').prop('checked')) {
		$('[name="refund_gv"]').prop('checked',false);
		$('#div_refund_gv').hide();
	} else if($('[name="exchange"]').prop('checked')) {
		$('[name="exchange"]').prop('checked',false);
		$('#div_exchange').hide();
	}
contentChanged();
});

$('[name="refund_gv"]').click(function() {
	if($('[name="refund_payment"]').prop('checked')) {
		$('[name="refund_payment"]').prop('checked',false);
		$('#div_refund_payment').hide();
	} else if($('[name="exchange"]').prop('checked')) {
		$('[name="exchange"]').prop('checked',false);
		$('#div_exchange').hide();
	}
contentChanged();
});

$('[name="exchange"]').click(function() {
	if($('[name="refund_gv"]').prop('checked')) {
		$('[name="refund_gv"]').prop('checked',false);
		$('#div_refund_gv').hide();
	} else if($('[name="refund_payment"]').prop('checked')) {
		$('[name="refund_payment"]').prop('checked',false);
		$('#div_refund_payment').hide();
	}
contentChanged();
});

});

</script>

<?php
// # Product return request listing 
  } else {

?>
<script type="text/javascript" src="js/popcalendar.js"></script>
<table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding:0 0 0 5px">
<tr>
  <td>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td class="pageHeading"><div style="display:inline-block"><img src="images/returns_icon.gif" alt="" width="40" height="38"></div>
		<div style="display:inline-block; height:40px; vertical-align:middle"><?php echo HEADING_TITLE; ?></div>
		</td>
        <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td width="325" align="right" style="padding:0 5px 10px 0;">
<?php echo tep_draw_form('returns', FILENAME_RETURNS, '', 'get'); ?>
			<table width="325" border="0" cellspacing="0" cellpadding="0" style="background-color:#FFFFFF; border:solid 1px #D9E4EC;">
<tr>
<td style="padding-top:5px; padding-bottom:5px;">
  <table width="325" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>
                      <td width="25"><img src="images/mag-icon.gif" width="15" 
height="15" hspace="5"></td>
                      <td width="80" align="center" nowrap 
style="color:#6295FD; font: bold 11px Tahoma;">RMA Search &nbsp;</td>
                      <td width="212" align="right">
					  <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td align="right" style="padding-top:2px;">
			  <input type="text" name="date_from" style="font:bold 9px arial;" onclick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo isset($_GET['date_from'])?$_GET['date_from']:''?>" size="12" maxlength="11" textfield></td>

						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onclick="self.popUpCalendar(document.returns.date_from, document.returns.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding:1px 3px 0 3px;"> - </td>

                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onclick="self.popUpCalendar(document.returns.date_from, this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?php echo isset($_GET['date_to'])?$_GET['date_to']:''?>" size="12" maxlength="11" textfield></td>

                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onclick="self.popUpCalendar(document.returns.date_from, document.returns.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td valign="top" style="padding:2px 7px 0 2px;"><input type="submit" value="GO" style="margin:0; padding:none; cursor:pointer; border:none; font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;"></td>
                        </tr>
</table></td>
                    </tr>
                        
                      </table><br>
					  <table width="100%" border="0" cellspacing="0" cellpadding="0">
 <tr>
                          <td nowrap style="padding: 0 2px 0 6px; font: normal 11px Tahoma; color: rgb(5, 51, 137);" valign="top"> 
                            Name / Order# / RMA#: &nbsp;</td>
                          <td width="80" style="padding-right:5px;"><?php echo tep_draw_input_field('cID', '', 'size="20" style="font:bold 9px arial; width:100px;"') . tep_draw_hidden_field('action', 'edit'); ?></td>
<td align="center" valign="top" style="padding:0 0 0 7px; font: 11px Tahoma; color: rgb(5, 51, 137);">Display:</td>
		  <td width="92" align="center" style="padding: 0 5px 0 4px;"><?php echo tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => 'All Returns')), $orders_statuses), '', 'onChange="this.form.submit();" style="font:bold 10px arial; width:90px;"'); ?></td>
                        </tr>
</table>
</td>
		  
		</tr>
</table></form></td>
      </tr>
    </table></td>
</tr>
<tr>
  <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td valign="top">

			<table border="0" width="100%" cellspacing="0" cellpadding="10">
            <tr class="dataTableHeadingRow">
              <th class="dataTableHeadingContent" align="center"><?php echo HEADING_TITLE_SEARCH; ?></th>
              <th class="dataTableHeadingContent" align="left"><?php echo TABLE_HEADING_CUSTOMERS; ?></th>
              <th class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_ORDER_TOTAL; ?></th>
              <th class="dataTableHeadingContent" align="center" style="padding:10px 15px"><?php echo TABLE_HEADING_DATE_PURCHASED; ?></th>
              <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_REASON; ?></th>
              <th class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</th>
            </tr>
<?php
	$dateSearch = '';
	$dateSearch .= (!empty($_GET['date_from'])) ? " AND r.date_purchased >= '".date('Y-m-d H:i:s',strtotime($_GET['date_from']))."'" : "";
	$dateSearch .= (!empty($_GET['date_to'])) ? " AND r.date_purchased <= '".date('Y-m-d H:i:s',strtotime($_GET['date_to']))."'" : "";



    $cID = tep_db_prepare_input(trim($_GET['cID']));
	$status = tep_db_prepare_input($_GET['status']);
	$and = (!empty($cID) ? " AND " : "");

	// # to speed up query, detect ref_value before using.
	if(strlen($cID) > 12 && strpos($cID, '-', 3) == '3') { 
		$ref_value = ", oir.ref_value";
		$orders_items_refs_join = " LEFT JOIN orders_items_refs  oir ON r.order_id = oir.orders_id ";
		$OR_ref_value = " OR oir.ref_value = '".$cID."' ";
	}

      $orders_query_raw = "SELECT r.returns_id,
								  r.rma_value,
								  r.order_id,
								  r.customers_name,
								  r.date_purchased,
								  r.returns_date_finished,
								  rd.final_price, 
								  rr.return_reason_name,
								  rs.returns_status_name
								  ". $ref_value ."
						FROM " . TABLE_RETURNS . " r
						LEFT JOIN " . TABLE_RETURNS_PRODUCTS_DATA . " rd ON rd.returns_id = r.returns_id
						LEFT JOIN ".TABLE_RETURN_REASONS." rr ON (rr.return_reason_id = r.returns_reason AND rr.language_id = '" . $languages_id . "')
						LEFT JOIN ".TABLE_RETURNS_STATUS." rs ON (rs.returns_status_id = r.returns_status AND rs.language_id = '" . $languages_id . "')
						". $orders_items_refs_join ."
						" . (!empty($cID) || !empty($status)? "WHERE " : "") . "
						". (!empty($cID) ? "(r.rma_value = '".$cID ."' OR r.order_id = '".$cID."' OR r.customers_name LIKE '%".$cID."%' ". $OR_ref_value .")" : "") . (!empty($status) ? $and . "r.returns_status = '" . $status . "'" : "" ). "
						GROUP BY r.rma_value
						ORDER BY r.returns_id DESC";

    $orders_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
    $orders_query = tep_db_query($orders_query_raw);

    while ($orders = tep_db_fetch_array($orders_query)) {
		if (((!$_GET['oID']) || ($_GET['oID'] == $orders['returns_id'])) && (!$oInfo)) {
			$oInfo = new objectInfo($orders);
		}

		if( is_object($oInfo) && ($orders['returns_id'] == $oInfo->returns_id)) {
			echo '<tr class="dataTableRowSelected" style="cursor:pointer" onclick="document.location.href=\'' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID')) . 'oID=' . $orders['returns_id']).'&amp;action=edit\'">';
		} else {
			echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $orders['returns_id']) . '\'">';
		}

	  if($orders['returns_status'] == GREEN_STATUS && ($orders['returns_date_finished'] > 0)) {
	  	$order_done = '<font color="green">';

	  } elseif(($orders['returns_date_finished'] <= 0) && !$return_complete) {
		$order_done = '<font color="red">';

	  } else {
		$order_done = '';
	  }

	  if (!empty($orders['contact_user_name'])) {
		$return_customer_name = $orders['contact_user_name'];
	  } else {
		$return_customer_name =  $orders['customers_name'];
	  }
?>
	
		<td class="dataTableContent" align="center" style=""><b><?php echo $orders['rma_value']; ?></b></a>
              <td class="dataTableContent" style="padding: 5px 10px"><?php echo '<a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $orders['returns_id'] . '&action=edit') . '">' . $order_done . $return_customer_name . '</font></a>' ; ?></td>
              <td class="dataTableContent" align="right"><?php echo $currencies->format($orders['final_price']); ?></td>
              <td class="dataTableContent" align="center"><?php echo date('m/d/Y',strtotime($orders['date_purchased'])); ?></td>
              <td class="dataTableContent" align="right"><?php echo $orders['return_reason_name']; ?></td>
              <td class="dataTableContent" align="right"><?php echo $orders['returns_status_name'];?></td>
            </tr><?php
	} // # END while 
?>
            <tr>
              <td colspan="6"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
                    <td class="smallText" align="right"><?php echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
                  </tr>
                </table></td>
            </tr>
          </table></td>


<?php
  $heading = array();
  $contents = array();
  switch ($_GET['action']) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_ORDER . '</b>');

      $contents = array('form' => tep_draw_form('orders', FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->returns_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO . '<br><br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('restock') . ' ' . TEXT_INFO_RESTOCK_PRODUCT_QUANTITY);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->returns_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;

    default:

      if (is_object($oInfo)) {

        $heading[] = array('text' => '<b>Order: &nbsp;<a href="orders.php?cFind='. $oInfo->order_id .'&action=cust_search"><b>'.$oInfo->order_id.'</b></a> &nbsp;-&nbsp; '.date('m/d/Y',strtotime($oInfo->date_purchased)) . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->returns_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link("returns_packingslip.php", 'oID=' . $oInfo->returns_id) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a> <a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->returns_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_RETURNS_INVOICE, 'oID=' . $oInfo->returns_id) . '" target="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) .
 '</a>');   // don’t forget this very important

        $contents[] = array('text' => '<br>' . TEXT_DATE_ORDER_CREATED . ' ' . tep_date_short($oInfo->date_purchased));

        if (tep_not_null($oInfo->last_modified)) {
			$contents[] = array('text' => TEXT_DATE_ORDER_LAST_MODIFIED . ' ' . tep_date_short($oInfo->last_modified));
		}
		if ($oInfo->returns_date_finished !== '0000-00-00 00:00:00') {
        $contents[] = array('text' => '<br>' . TEXT_INFO_PAYMENT_METHOD . ' '  . tep_date_short($oInfo->returns_date_finished));
		}
      }
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '<td width="25%" valign="top" style="padding:0 5px 0 5px; white-space:nowrap;">';

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '</td>';
  }
?>
      </tr>
    </table></td>
</tr>
</table>
</td>
</tr>
</table>
<?php
  }
?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>