<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');
	require(DIR_WS_CLASSES . 'order.php');

	if(isset($_POST['order_id']) && $_POST['order_id'] > 0) {
		$orders_id = tep_db_input($_POST['order_id']);
	} else { 
		$orders_id = (isset($_GET['order_id']) ? tep_db_input($_GET['order_id']) : tep_db_input($_GET['oID']));
	}

	$order = new order($orders_id);

	// # if order source is from Amazon FBA, forward back to orders screen - cannot run returns on FBA sales! 
	if(strpos($order->info['orders_source'],'Amazon-FBA') !== false) {
		tep_redirect(tep_href_link(FILENAME_ORDERS, 'date_from=&date_to=&cFind=' . (int)$order->orderid).'&action=cust_search&status=');
		exit();
	}


	require(DIR_WS_CLASSES . 'currencies.php');

	$currencies = new currencies();

	$support_alternate_email = STORE_OWNER_EMAIL_ADDRESS;

	 // # Default return_reason if not set
	$default_reason_query = tep_db_query("SELECT configuration_value 
										  FROM " . TABLE_CONFIGURATION . " 
										  WHERE configuration_key = 'DEFAULT_RETURN_REASON'
										 ");

	$default_reason = tep_db_fetch_array($default_reason_query);

	$default_refund_query = tep_db_query("SELECT configuration_value 
										  FROM " . TABLE_CONFIGURATION . " 
										  WHERE configuration_key = 'DEFAULT_REFUND_METHOD'
										 ");

	$default_refund = tep_db_fetch_array($default_refund_query);

	if (!$_GET['action']) {
		$_GET['action'] = 'new';
	}

	if ($_GET['action']) {

		switch ($_GET['action']) {

			case 'insert':
			case 'update':

			// # product id
			if(!isset($_GET['products_id'])) {
				$_GET['products_id']=$_POST['products_id'];
			}

			$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id = '". $orders_id ."' AND method = 'payment_amazonSeller' LIMIT 1");
	
			$theAmazonoID = tep_db_fetch_array($amazonOrder_query);

			if($theAmazonoID && $theAmazonoID['ref_id']) {

				// # just a quick order table check for orders_source
				$order_check = tep_db_query("SELECT orders_id, orders_source 
											 FROM " . TABLE_ORDERS . " 
											 WHERE orders_id = '".$_GET['oID']."' 
											 AND payment_method = 'payment_amazonSeller' LIMIT 1
											");
		
				$theOcheck = tep_db_fetch_array($order_check);
	
				$credQuery = tep_db_query("SELECT conf_key, conf_value 
									   FROM ".TABLE_MODULE_CONFIG." 
									   WHERE conf_module = '".$theOcheck['orders_source']."'
									  ");
				if(!$credQuery) {
					tep_redirect(tep_href_link(FILENAME_RETURN, 'rma_error=yes&order_id=' . $orders_id . '&products_id=' . $_GET['products_id']));
				}

				// # Grab the credentials and add array for later use.
				$theCreds = array();
	
				while($row = tep_db_fetch_array($credQuery)) {
					$theCreds[$row['conf_key']] = $row['conf_value'];
				}
	
				// # define the Amazon Login URL according to source found in orders.orders_source
	
				if($theOcheck['orders_source'] == 'dbfeed_amazon_us'){ 
					$loginURL = 'https://sellercentral.amazon.com';
				} elseif ($theOcheck['orders_source'] == 'dbfeed_amazon_ca') {
					$loginURL = 'https://sellercentral.amazon.ca';	
				}
	

				// # Amazon orders need to have the return authorized and rma assigned
				require(DIR_FS_COMMON . 'service/amazon_curl.php');

				$azSellerObj = new AmazonSellerCentralSession();
				$azSellerObj->login($theCreds['sellercentral_user'], $theCreds['sellercentral_pass'], $loginURL);
				$rma_create = $azSellerObj->authorizeReturn($theAmazonoID['ref_id']);

				// # extract the buyer comments from Amazon RMA request if they exist.
	
				$azBuyerComments = $azSellerObj->getBuyerComments($theAmazonoID['ref_id']);
	
				if ($rma_create === false) {
					//tep_redirect(tep_href_link(FILENAME_RETURN, 'rma_error=yes&order_id=' . $orders_id . '&products_id=' . $_GET['products_id']));
					print_r('Error Publishing RMA to Amazon');
	
					// # added 10/30/2013
					// # create an RMA ID if Amazon process fails.
					// # this is to ensure the RMA creation process is still happening.
					$rma_create = tep_create_rma_value(11);
				}

			} else {
			
				$rma_create = tep_create_rma_value(11);
			}

         // # carry out a query on all the existing orders tables, to get the required information
         $returns_status_query = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'DEFAULT_RETURN_STATUS_ID'");

         $default_return = tep_db_fetch_array($returns_status_query);

         $order_returns_query = tep_db_query("SELECT o.*, op.*, p.products_sku AS products_sku
											  FROM " . TABLE_ORDERS . " o
											  LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op ON op.orders_id = o.orders_id
											  LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_model = op.products_model
											  WHERE o.orders_id = '" . $_GET['oID'] . "' AND op.products_id = '" . $_GET['products_id'] . "'
											");
         $orders_return = tep_db_fetch_array($order_returns_query);
         //CHECKING FOR ERRORS BEFORE SUBMISSION

         if ($_POST['returns_quantity'] > $orders_return['products_quantity']) {
             tep_redirect(tep_href_link(FILENAME_RETURN, 'qty_error=yes&order_id=' . $orders_id . '&products_id=' . $_GET['products_id']));
         }
           if (tep_validate_email($_POST['customers_email_address']) == false) {
		      tep_redirect(tep_href_link(FILENAME_RETURN, 'email_error=yes&order_id=' . $orders_id . '&products_id=' . $_GET['products_id']));
         }
         if ($_POST['contact_user_name'] == '') {
		      tep_redirect(tep_href_link(FILENAME_RETURN, 'name_error=yes&order_id=' . $orders_id . '&products_id=' . $_GET['products_id']));
         }
		 $theDate = date('Y-m-d H:i:s', time());
         $customers_id = $orders_return['customers_id'];
         $rma_value = tep_db_prepare_input($rma_create);
         $customers_name = $orders_return['customers_name'];
         $customers_acct = $orders_return['customers_acct'];
         $customers_company = $orders_return['customers_company'];
         $customers_street_address = $orders_return['customers_street_address'];
         $customers_suburb = $orders_return['customers_suburb'];
         $customers_city = $orders_return['customers_city'];
         $customers_postcode = $orders_return['customers_postcode'];
         $customers_state = $orders_return['customers_state'];
         $customers_country = $orders_return['customers_country'];
         $customers_telephone = $orders_return['customers_telephone'];
         $customers_fax = $orders_return['customers_fax'];
         $customers_email = tep_db_prepare_input($_POST['customers_email_address']);
         $customers_address_format_id = $orders_return['customers_address_format_id'];
         $delivery_name = $orders_return['delivery_name'];
         $delivery_company = $orders_return['delivery_company'];
         $delivery_street_address = $orders_return['delivery_street_address'];
         $delivery_suburb = $orders_return['delivery_suburb'];
         $delivery_city = $orders_return['delivery_city'];
         $delivery_postcode = $orders_return['delivery_postcode'];
         $delivery_state = $orders_return['delivery_state'];
         $delivery_country = $orders_return['delivery_country'];
         $delivery_address_format_id = $orders_return['delivery_address_format_id'];
         $billing_name = $orders_return['billing_name'];
         $billing_acct = $orders_return['billing_acct'];
         $billing_company = $orders_return['billing_company'];
         $billing_street_address = $orders_return['billing_street_address'];
         $billing_suburb = $orders_return['billing_suburb'];
         $billing_city = $orders_return['billing_city'];
         $billing_postcode = $orders_return['billing_postcode'];
         $billing_state = $orders_return['billing_state'];
         $billing_country = $orders_return['billing_country'];
         $billing_address_format_id = $orders_return['billing_address_format_id'];

		// # default notification to customer is now set to FALSE
		$notify_customer = (isset($_POST['notify_customer']) && $_POST['notify_customer'] == 1) ? 1 : 0;

		// # detect if these are Amazon comments:

		 if (!empty($azBuyerComments)){
			 foreach ($azBuyerComments as $item=>$itemComment){
				 $comments .= PHP_EOL.$item.': '.$itemComment;
			 }
		 } else {
			$comments = (!empty($_POST['support_text'])) ? tep_db_prepare_input($_POST['support_text']) : '';
		 }

		// # END amazon comments detection.

         $returns_status =  $default_return['configuration_value'];
         $returns_reason = tep_db_prepare_input($_POST['return_reason']);
         $products_model = $orders_return['products_model'];
         $products_name = $orders_return['products_name'];
         $products_price = $orders_return['products_price'];
         $products_tax = $orders_return['products_tax'];
         $discount_made = $orders_return['products_discount_made'];
         $contact_user_name = tep_db_prepare_input($_POST['contact_user_name']);

         // # work out price with tax
         $price_inc_tax = $products_price + tep_calculate_tax($products_price, $products_tax);
         $price_inc_quantity = $price_inc_tax * $_POST['returns_quantity'];
         $final_price =  $price_inc_quantity;
         $products_quantity = $_POST['returns_quantity'];// $orders_return['products_quantity'];
         $serial_number = ($orders_return['products_upc']) ? $orders_return['products_upc'] : $orders_return['products_sku'];
         $currency = $orders_return['currency'];
         $currency_value = $orders_return['currency_value'];
         $refund_method = $_POST['refund_method'];
		 $refund_amount = ($_POST['refund_method'] == 'Refund') ? $final_price : '0.00';
		 
		// # get the payment method from the orders table to populate returned_products table
		$payment_method = tep_db_result(tep_db_query("SELECT payment_method FROM orders WHERE orders_id='".$orders_id."'"), 0);

	 // # error checking goes in here
	   $support_error = false;

	// # None currently   Email, name and qty are checked above
   if (!$support_error) {

		$theDate = date('Y-m-d H:i:s', time());

          $sql_data_array = array('customers_id' => $customers_id,
                                  'rma_value' => $rma_value,
                                  'order_id' => $orders_id,
                                  'customers_name' => $customers_name,
                                  'customers_acct' => $customers_acct,
                                  'customers_company' => $customers_company,
                                  'customers_street_address' => $customers_street_address,
                                  'customers_suburb' => $customers_suburb,
                                  'customers_city' => $customers_city,
                                  'customers_postcode' => $customers_postcode,
                                  'customers_state' => $customers_state,
                                  'customers_country' => $customers_country,
                                  'customers_telephone' => $customers_telephone,
                                  'customers_fax' => $customers_fax,
                                  'customers_email_address' => $customers_email,
                                  'customers_address_format_id' => $customers_address_format_id,
                                  'delivery_name' => $delivery_name,
                                  'delivery_company' => $delivery_company,
                                  'delivery_street_address' => $delivery_street_address,
                                  'delivery_suburb' => $delivery_suburb,
                                  'delivery_city' => $delivery_city,
                                  'delivery_postcode' => $delivery_postcode,
                                  'delivery_state' => $delivery_state,
                                  'delivery_country' => $delivery_country,
                                  'delivery_address_format_id' => $delivery_address_format_id,
                                  'billing_name' => $billing_name,
                                  'billing_acct' => $billing_acct,
                                  'billing_company' => $billing_company,
                                  'billing_street_address' => $billing_street_address,
                                  'billing_suburb' => $billing_suburb,
                                  'billing_city' => $billing_city,
                                  'billing_postcode' => $billing_postcode,
                                  'billing_state' => $billing_state,
                                  'billing_country' => $billing_country,
                                  'billing_address_format_id' => $billing_address_format_id,
                                  'comments' => $comments,
                                  'returns_status' => $returns_status,
                                  'returns_reason' => $returns_reason,
                                  'currency' => $currency,
                                  'currency_value' =>$currency_value,
                                  'contact_user_name' => $contact_user_name,
								  'payment_method' => $payment_method,
								  'last_modified' => $theDate
                                 );

          if ($_GET['action'] == 'insert') {

            // # returns information table updated,
            tep_db_perform(TABLE_RETURNS, $sql_data_array);
            $ticket_id = tep_db_insert_id();
			tep_db_query("UPDATE ".TABLE_RETURNS." SET date_purchased = '".$theDate."' WHERE returns_id = '" .$ticket_id."'");

        	tep_db_query("UPDATE ".TABLE_ORDERS." SET `last_modified` = '".$theDate."' WHERE `orders_id` = '".$orders_id."'");
			
			// # now update returns products, and history tables
             $data_insert_sql = array('returns_id' => $ticket_id,
                                      'order_id' => $orders_id,
                                      'products_id' => $_GET['products_id'],
                                      'products_model' =>$products_model,
                                      'products_name' => $products_name,
                                      'products_price' => $products_price,
                                      'products_discount_made' => $discount_made,
                                      'final_price' => $final_price,
                                      'products_tax' => $products_tax,
                                      'products_quantity' => $products_quantity,
                                      'products_serial_number' => $serial_number,
									  'refund_amount' => $refund_amount
                                     );

         	$returns_payment_sql = array('returns_id' => $ticket_id,
            	                         'refund_payment_name' => $refund_method,
										 'refund_payment_value' => $refund_amount
                                      	 );

            tep_db_perform(TABLE_RETURN_PAYMENTS, $returns_payment_sql);
            tep_db_perform(TABLE_RETURNS_PRODUCTS_DATA, $data_insert_sql);

			$isExchange = ($_POST['refund_method'] == 'Exchange') ? 'products_exchanged' : 'products_returned';

            tep_db_query("UPDATE " . TABLE_ORDERS_PRODUCTS . " 
						SET ".$isExchange."  = '1',
						exchange_returns_id = '".$ticket_id."'
						WHERE orders_id = '".$orders_id."' 
						AND products_id = '".$_GET['products_id']."'
						");

          }

		  // # Add returns status to returns status history table added 12-22-05
		  tep_db_query("INSERT INTO " . TABLE_RETURNS_STATUS_HISTORY . "
						SET returns_id = '" . $ticket_id . "', 
						returns_status = '".$returns_status."', 
						date_added = NOW(), 
						customer_notified = 1,
						comments = '".tep_db_input($comments)."'
						");

		// # now send email to customer if checked
		require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_RETURN_EMAILS);

		if($notify_customer == 1) {

			// # Skip sending the email notification if it's an Amazon customer 
			if($payment_method != 'payment_amazonSeller') { 

			$tpl = array();
			$tpl['config'] = array(
			    store_name => STORE_NAME,
			    store_owner_email_address => STORE_OWNER_EMAIL_ADDRESS,
			    http_server => HTTP_SERVER,
			);
		
			$tpl['link'] = array(
			    account_history => tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders_id, 'SSL', false),
			    account => tep_catalog_href_link('account.php'),
			    tell_a_friend => tep_catalog_href_link('tell_a_friend.php'),
			);
	
			$tpl['order_id'] = $order->orderid;
			$tpl['info'] = $order->info;
			$tpl['customer'] = $order->customer;
			list($tpl['customer']['firstname'],$tpl['customer']['lastname']) = explode(' ',$tpl['customer']['name']);

			$tpl['date']=strftime(DATE_FORMAT_LONG);

			$tpl['address'] = array(
			    shipping => array(text => tep_address_format($order->delivery['format_id'],$order->delivery,0,'',"\n"), html => tep_address_format($order->delivery['format_id'],$order->delivery,1,'',"\n")),
			    billing => array(text => tep_address_format($order->billing['format_id'],$order->billing,0,'',"\n"), html => tep_address_format($order->billing['format_id'],$order->billing,1,'',"\n")),
			);
	
			$tpl['rma_value'] = $rma_value;
	
			$support_alternate_email = STORE_OWNER_EMAIL_ADDRESS;

			require_once(DIR_WS_FUNCTIONS . 'email_now.php');
			email_now('return_confirm',$tpl,$support_alternate_email);

			}
		}

		// # now send an email to the default administrator to let them know of new ticket
		// # send email to alternate address
		if(strlen($support_alternate_email) > 0) {

			$orderManagerURL = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/admin/orders_view.php?oID='.$order->orderid.'&action=edit';

			$orderManagerURL = '<a href="'.$orderManagerURL.'">'.$orderManagerURL.'</a>';

			$email_admin_subject = sprintf(EMAIL_SUBJECT_ADMIN, $order->orderid);
			$email_admin_body = sprintf(EMAIL_TEXT_ADMIN, $orderManagerURL);
			$email_admin_rma_number = sprintf(EMAIL_TEXT_TICKET_ADMIN, $rma_value, $order->orderid);
				
			$email_text_admin = $email_admin_rma_number . EMAIL_ADMIN_INTRO . $email_admin_body;
	    
			tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS , $email_admin_subject, nl2br($email_text_admin), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		}

          // # redirect to confirmation
         tep_redirect(tep_href_link(FILENAME_RETURN . '?action=sent&rma_value='. $rma_value . '&return_id=' . $ticket_id));

	} else {

          $_GET['action'] = 'new';
	}
			break;

			case 'default':
				tep_redirect(tep_href_link(FILENAME_DEFAULT));
			break;
		}
	}

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_RETURN);
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>

<script language="javascript"><!--
	function popupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=280,screenX=150,screenY=150,top=150,left=150')
}
//--></script>
<script>
 var submitDone = false;

 function submitForm(myForm, button) {

      if (!submitDone) {
         submitDone = true;
         button.value = 'Please Wait';
         button.disabled = true;
         myForm.submit();
      }
      else {
        alert ("Already submitted, please wait!");
      }
   return true;
 }
</script>
</head>

<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top" colspan="2"><center><table border="0"  width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">

          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td align="right"></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php
if ($_GET['action'] == 'sent'){
           $text_query = tep_db_query("SELECT * FROM " . TABLE_RETURNS_TEXT . " where return_text_id = '1' and language_id = '" . $languages_id . "'");
           $text = tep_db_fetch_array($text_query);

        //   tep_db_query("INSERT into " . TABLE_RETURN_PAYMENTS . " values ('', '" . $_GET['id'] . "', '', '', '', '', '')");
             ?>
          <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="1">
          <tr>
            <td class="main"><?php echo TEXT_RMA_CREATED; ?></td>
          </tr>
           <tr>
			<td><?php echo tep_draw_separator('pixel_trans.gif', '10', '100%'); ?></td>
          </tr>
          <tr>
		  <td class="main"><?php echo '<center><font color=cc0000 size=3px><b>' . TEXT_YOUR_RMA_NUMBER . $_GET['rma_value'] . '</b></font></center>'; ?></td>
          </tr>
           <tr>
			<td><?php echo tep_draw_separator('pixel_trans.gif', '20', '20'); ?></td>
          </tr>

         <tr>
            <td align="right" valign=bottom><br><?php echo '<a href="' . tep_href_link(FILENAME_ORDERS) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
         </tr>
        </table></td>
      </tr>


<?php

} elseif(!$orders_id) {
?>
<tr><td><form method="POST" action="<?=tep_href_link(FILENAME_RETURN)?>">Select the Order:
<?php

	$order_select = array();

	$order_query = tep_db_query("SELECT * 
							     FROM orders 
								 WHERE orders_status > 0 
								 AND orders_source NOT LIKE 'Amazon-FBA%' 
								 ORDER BY orders_id DESC
								");

	while ($order_row = tep_db_fetch_array($order_query)) {
		$order_select[] = array('id' => $order_row['orders_id'], 
								'text'=>$order_row['orders_id'].': '.$order_row['customers_name'].' - ('.date('m/d/Y g:ia',strtotime($order_row['date_purchased'])).')'
								);
	}

	echo tep_draw_pull_down_menu('order_id',$order_select);
?>

<input type="submit" name="submit" value="Go">
</form>
</td>
</tr>
<?

} else {
         $account_query = tep_db_query("SELECT customers_firstname, customers_lastname, customers_email_address FROM " . TABLE_CUSTOMERS . " where customers_id = '" . $customer_id . "'");
         $account = tep_db_fetch_array($account_query);
         // query the order table, to get all the product details
         $returned_products_query = tep_db_query("SELECT opa . * , op . * , o . * FROM ".TABLE_ORDERS_PRODUCTS." op JOIN ".TABLE_ORDERS." o ON o.orders_id = op.orders_id LEFT JOIN ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES." opa ON opa.orders_products_id = op.orders_products_id WHERE op.orders_id = '" . $orders_id . "'");
	 $returned_list=Array();
         while($returned_row=tep_db_fetch_array($returned_products_query)) {
	   if (!isset($returned_list[$returned_row['orders_products_id']])) $returned_list[$returned_row['orders_products_id']]=Array('id'=>$returned_row['products_id'],'text'=>sprintf(($returned_row['exchange_returns_id']?'[Exchanged] ':'')."%s ($%.2f) - %d",$returned_row['products_name'],$returned_row['final_price'],$returned_row['products_quantity']),'attr'=>Array());
	   if ($returned_row['products_options']) $returned_list[$returned_row['orders_products_id']]['attr'][]=$returned_row['products_options'].': '.$returned_row['products_options_values'];
	 }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr><form name="longsubmit" action="return_product.php?action=insert&oID=<?php echo $orders_id;?>" method=post>
        <td><table border="0" cellspacing="0" cellpadding="2" width=100%>
        <?php
        if (isset($qty_error)=='yes') {
        ?> <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr>
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><font color="CC0000"><? echo TEXT_ERROR; ?></font></b></td>
              </tr>

           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
            <tr>
            <td class="main" align="left" width="100%">
            <?php
         			echo '<b><font color="CC0000">' . TEXT_ERROR_QUANTITY . '</b></font>' . "\n";
            ?>
            </td>
            </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '15'); ?></td>
          </tr>
              <?php
              }
            ?>
              <?php
	          if (isset($email_error)=='yes') {
	          ?> <tr>
	          <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
	            <tr>
	              <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	                <tr>
	                  <td class="main"><b><font color="CC0000"><? echo TEXT_ERROR; ?></font></b></td>
	                </tr>

	             </table></td>
	              <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	              <tr>
	              <td class="main" align="left" width="100%">
	              <?php
	  				    echo '<b><font color="CC0000">' . TEXT_ERROR_EMAIL . '</b></font>' . "\n";
	              ?>
	              </td>
	              </tr>
	              </table></td>
	            </tr>
	          </table></td>
	        </tr>
	        <tr>
	              <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '15'); ?></td>
	            </tr>
	                <?php
	                }
            ?>
              <?php
	          if (isset($name_error)=='yes') {
	          ?> <tr>
	          <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
	            <tr>
	              <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	                <tr>
	                  <td class="main"><b><font color="CC0000"><? echo TEXT_ERROR; ?></font></b></td>
	                </tr>

	             </table></td>
	              <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	              <tr>
	              <td class="main" align="left" width="100%">
	              <?php
	  				    echo '<b><font color="CC0000">' . TEXT_ERROR_CONTACT . '</b></font>' . "\n";
	              ?>
	              </td>
	              </tr>
	              </table></td>
	            </tr>
	          </table></td>
	        </tr>
	        <tr>
	              <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '15'); ?></td>
	            </tr>
	                <?php
	                }
            ?>

            <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" colspan="2"><b><?php echo TEXT_SUPPORT_RETURN_HEADING; ?></small></b></td>
          </tr>

        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="30%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_PRODUCT_RETURN; ?></b><BR></td>
              </tr>



            </table></td>
            <td width="70%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if (sizeof($order->info['tax_groups']) > 1) {
?>
                  <tr>
                    <td class="main" colspan="2"><b>Qty</b></td>

                    <td class="smallText" align="right"><b><?php echo HEADING_PRODUCTS; ?></b></td>
                    <td class="smallText" align="right"><b><?php echo HEADING_TOTAL; ?></b></td>
                  </tr>
<?php
  } else {
?>
                  <tr>
                    <td class="main">&nbsp;</td>
                    <td class="main" colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;<b><?php echo HEADING_PRODUCTS; ?></b></td>
                  </tr>
<?php
  }

    echo '          <tr>' . "\n" .
         '            <td class="main" align="right" valign="top" width="30">' . tep_draw_input_field('returns_quantity', 1, 'size=5') . '</td>' . "\n" .
         '            <td class="main" valign="top"><table border="0" cellspacing="0" cellpadding="0">';

	 foreach ($returned_list AS $ret) {
?><tr><td valign="top"><input type="radio" name="products_id" value="<?=$ret['id']?>"></td><td><?=$ret['text']?>
<?
	    foreach ($ret['attr'] AS $attrl) {
?><br><span style="font-style:italic;">-&nbsp;<?=$attrl?></span>
<?
	    }
?></td></tr>
<?
	 }
?></table>
<?
	 
echo '</td>' . "\n";
//echo '            <td class="main" align="right" valign="top">' . $currencies->format(($returned_products['products_price'] + (tep_calculate_tax(($returned_products['products_price']),($returned_products['products_tax'])))) * ($returned_products['products_quantity'])) . '</td>' . "\n" .
         '          <td></td></tr>' . "\n";

?>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>


        <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '15'); ?></td>
          </tr>
              <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_BILLING_ADDRESS; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' . tep_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>') . '<br><b>' . $order->customer['email_address'] . '</b></td>' . "\n" .
         '              </tr>' . "\n";
           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_DELIVERY_ADDRESS; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' . tep_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>') . '</td>' . "\n" .
         '              </tr>' . "\n";
           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>

      <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_USER_EMAIL; ?></b></td>
              </tr>

           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' .  tep_draw_input_field('customers_email_address',$order->customer['email_address']) . '</td>' . "\n" .
         '              </tr>' . "\n";

           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_USER_NAME; ?></b></td>
              </tr>

           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
         <?
    echo '              <tr>' . "\n" .
         '                <td class="main" align="left" width="5%">&nbsp;</td>' . "\n" .
         '                <td class="main" align="left" width=95%>' .  tep_draw_input_field('contact_user_name',$order->customer['name']) . '</td>' . "\n" .
         '              </tr>' . "\n";

           ?>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_WHY_RETURN; ?></b></td>
              </tr>
           </table></td>
            <td width="60%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
               		<tr>
            			<td class="main" width="5%">&nbsp;</td>
						<td class="main" width="95%"><?php //echo tep_draw_input_field('link_url');?>
<?php

	$return_reasons_query = tep_db_query("SELECT return_reason_id, return_reason_name FROM ". TABLE_RETURN_REASONS . " WHERE language_id = '" . $languages_id . "' ORDER BY return_reason_id DESC");
	
	$select_box = '<select name="return_reason"  size="' . MAX_MANUFACTURERS_LIST . '">';
    
	if (MAX_MANUFACTURERS_LIST < 2) {}

	while ($return_reasons = tep_db_fetch_array($return_reasons_query)) {
	
		$select_box .= '<option value="' . $return_reasons['return_reason_id'] . '" '.($default_reason['configuration_value'] ==  $return_reasons['return_reason_id'] ? ' selected' : '');       
		$select_box .= '>' . substr($return_reasons['return_reason_name'], 0, 20) . '</option>';
	}
    
	$select_box .= "</select>";

	echo $select_box;
?>
            </td>
          </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<!-- Begin refund method selection -->
      <tr>
                <td class="main">&nbsp;</td>
              </tr>
       <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><? echo TEXT_PREF_REFUND_METHOD; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                <tr>
            <td class=main width=5%>&nbsp;</td>
            <td class="main" width=95%><?php //echo tep_draw_input_field('link_url'); ?>
<?php

	$refund_methods_query = tep_db_query("SELECT refund_method_id, refund_method_name 
										  FROM ". TABLE_REFUND_METHOD . " 
										  WHERE language_id = '" . $languages_id . "' 
										  ORDER BY refund_method_id ASC
										");

	$select_box = '<select name="refund_method"  size="' . MAX_MANUFACTURERS_LIST . '">';

	if (MAX_MANUFACTURERS_LIST < 2) {
                     }
		while ($refund_values = tep_db_fetch_array($refund_methods_query)) {

			$select_box .= '<option value="' . $refund_values['refund_method_name'] . '"';
			if($default_refund['configuration_value'] ==  $refund_values['refund_method_id']) $select_box .= ' selected';
			
			$select_box .= '>' . substr($refund_values['refund_method_name'], 0, 20) . '</option>';
		}

		$select_box .= "</select>";

		echo $select_box . '<br><br>';

		$charge_query = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " where configuration_key = 'DEFAULT_RESTOCK_VALUE'");
		$charge = tep_db_fetch_array($charge_query);
		// # Dont show re-stocking info if its set to zero in Admin > Configuration > Stock

		if ($charge['configuration_value'] != 0) {
			echo TEXT_SUPPORT_SURCHARGE . $charge['configuration_value'] .'%' . TEXT_SUPPORT_SURCHARGE_TWO;
		}
?>
		</td>
	</tr>
</table></td>
          </tr>
        </table></td>
      </tr>

                <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
       <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="dataTableRow">
            <td width="40%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo TEXT_SUPPORT_TEXT; ?></b></td>
              </tr>
              <tr>
                <td class="main">&nbsp;</td>
              </tr>
           </table></td>
            <td width="60%" valign="top">

				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td class="main" align="left" width="5%">&nbsp;</td>
						<td class="main" align="left" width="95%"><?php echo tep_draw_textarea_field('support_text', 'soft', '40', '7')?></td>
					</tr>

					<tr>
						<td></td>
						<td class="main"><input type="checkbox" value="1" name="notify_customer"><b>Notify customer?</b></td>
					</tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
                <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>

       </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td colspan="2" class="main" valign="top" nowrap align="center">



            <input type="submit" value="Submit" onClick="return submitForm(document.longsubmit, this)"></td>
          </tr>
        </table></td>
      </form></tr>
<?php
}
?>

            </td>
          </tr>
        </table></td>
      </tr>

    </table></td>

    </table></td>
  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
