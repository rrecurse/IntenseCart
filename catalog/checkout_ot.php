<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('includes/application_top.php');
	require(DIR_WS_CLASSES . 'order_total.php');
	require(DIR_WS_CLASSES . 'order.php');

	$order = new order($cart);

	$success_message = '';
	$error_message = '';
	$order_total_modules = new order_total;
	$gv_redeem_code = '';

	// # get customer session if exists
	$customer_id = (tep_session_is_registered('customer_id') ? $_SESSION['customer_id'] : NULL);

	// # check if coupon code was posted

	if($_POST['gv_redeem_code']) {

		$gv_redeem_code =  strtolower(tep_db_prepare_input($_POST['gv_redeem_code']));

		// # quick double check to ensure code is legit before we set the session
		$coupon_query = tep_db_query("SELECT coupon_id FROM ".TABLE_COUPONS." 
									  WHERE coupon_code LIKE '".$gv_redeem_code."' 
									  AND coupon_active = 'Y'
									  AND coupon_expire_date > NOW()");


		if(tep_db_num_rows($coupon_query) > 0) { 

			$coupon = tep_db_fetch_array($coupon_query);

			// # create the session if not already started:
			if(!tep_session_is_registered('cc_id')) {
				tep_session_register('cc_id');		
				$_SESSION['cc_id'] = $coupon['coupon_id'];
			} else {
				$_SESSION['cc_id'] = $coupon['coupon_id'];				
			}

		} else { // # no active coupon found

			if(tep_session_is_registered('cc_id')) {
				$_SESSION['cc_id'] = '';
				tep_session_unregister('cc_id');
		
				if(isset($_SESSION['cc_id'])) { 
					unset($_SESSION['cc_id']);
				}
			}

			$gv_redeem_code='';
		}

	} else { 

		if(tep_session_is_registered('cc_id')) { 

			$coupon_query = tep_db_query("SELECT coupon_code FROM ".TABLE_COUPONS." WHERE coupon_id = '".$_SESSION['cc_id']."'");

			if(tep_db_num_rows($coupon_query) > 0) { 
				$coupon = tep_db_fetch_array($coupon_query);
				$gv_redeem_code = $coupon['coupon_code'];
			} else {
				$gv_redeem_code='';
			}

		} else {
			$gv_redeem_code='';
		}
		
	}


	if(!empty($gv_redeem_code)) {

	  // # get some info from the coupon table
  		$coupon_query = tep_db_query("SELECT cu.*, gvb.gv_balance 
									  FROM " . TABLE_COUPONS . " cu 
									  LEFT JOIN coupon_gv_balance gvb ON gvb.coupon_id = cu.coupon_id 
									  WHERE cu.coupon_code LIKE '".$gv_redeem_code."' 
									  AND cu.coupon_active = 'Y'
									  AND cu.coupon_expire_date > NOW()
									");

		$coupon_result = tep_db_fetch_array($coupon_query);

		$price_groups = (!empty($coupon_result['restrict_to_pricegroups']) ? explode(',', $coupon_result['restrict_to_pricegroups']) : array('0'));
		$customer_group_id = (tep_session_is_registered('sppc_customer_group_id') ? $_SESSION['sppc_customer_group_id'] : '0');

   		// # Coupon
		if ($coupon_result['coupon_type'] != 'G') {

			// # Check if coupon has started yet
			$startdate_query = tep_db_query("SELECT coupon_start_date 
											 FROM " . TABLE_COUPONS . " 
											 WHERE coupon_start_date <= NOW() 
											 AND coupon_code LIKE '".$gv_redeem_code."'
											");

			$gv_startdate = (tep_db_num_rows($startdate_query) > 0 ? tep_db_result($startdate_query,0) : NULL);

			// # Count the coupon usages
			// # uses tep_num_rows to count
			$coupon_use_count_query = tep_db_query("SELECT COUNT(coupon_id)
												FROM " . TABLE_COUPON_REDEEM_TRACK . " 
												WHERE coupon_id = '" . $coupon_result['coupon_id']."'
												");

			$coupon_use_count = (tep_db_num_rows($coupon_use_count_query) > 0 ? tep_db_result($coupon_use_count_query,0) : 0);

	
			$gv_redeem_count_query = tep_db_query("SELECT COUNT(coupon_id)
														 FROM " . TABLE_COUPON_REDEEM_TRACK . " 
										  				 WHERE coupon_id = '" . $coupon_result['coupon_id']."'
										  				 AND customer_id = '" . (int)$customer_id . "'
														");

			$gv_redeem_count = (tep_db_num_rows($gv_redeem_count_query) > 0 ? tep_db_result($gv_redeem_count_query,0) : 0);

			// # Check if coupon expired

			$findate_query = tep_db_query("SELECT coupon_expire_date 
										   FROM " . TABLE_COUPONS . " 
										   WHERE coupon_expire_date >= NOW() 
										   AND coupon_code LIKE '".$gv_redeem_code."'
										  ");

			$gv_expire = (tep_db_num_rows($findate_query) > 0 ? tep_db_result($findate_query,0) : NULL);

			if(empty($_POST['method'])) { 
	
				$options = (is_array($shipping_options)) ? array_keys($shipping_options) : ''; 
				$methods = (isset($shipping_options[$options[0]])) ? array_keys($shipping_options[$options[0]]) : ''; 
				$selected_method = (isset($shipping_options[$options[0]][$methods[0]]["name"])) ? $shipping_options[$options[0]][$methods[0]]["name"] : '-';

			} else {
	
				$selected_method = $_POST['method'];
			}
	

			$UPSmethod = (MODULE_SHIPPING_UPSXML_SUREPOST == 'True' ? 'SurePost' : 'Ground');

			$allowedShippingMethods = array($UPSmethod, 'Best', 'Zipcode', 'Table', 'Flat', 'Per');
	
			$validPromoMethods = (preg_match('/'.implode('|', $allowedShippingMethods).'/i', $selected_method, $matches));

		  	if (tep_db_num_rows($coupon_query) == 0) {

				$error_message .= ERROR_NO_INVALID_REDEEM_COUPON . '<br>';

		  	} else if($gv_startdate > date('Y-m-d H:i:s', time())) {

				$error_message .= ERROR_INVALID_STARTDATE_COUPON . '<br>';

		  	} else if(date('Y-m-d H:i:s', time()) > $gv_expire) {

	  			$error_message .= ERROR_INVALID_FINISDATE_COUPON . '<br>';
	
		  	} else if((float)$order->info['total'] < (float)$coupon_result['coupon_minimum_order']) {

				// # strip any added html tags to the currency class (like structured data spans and meta tags).
				$theAmount = $currencies->format($coupon_result['coupon_minimum_order']);
				$theAmount = strip_tags(trim($theAmount));

				$error_message .= '<b style="color:red">Order must be greater then or equal to '.$theAmount.' to use this coupon' . '</b><br>';

			} else if($coupon_use_count >= $coupon_result['uses_per_coupon'] && $coupon_result['uses_per_coupon'] > 0) {

				$error_message .= ERROR_INVALID_USES_COUPON . $coupon_result['uses_per_coupon'] . TIMES . '<br>';
	
		  	} else if($gv_redeem_count >= $coupon_result['uses_per_user'] && $coupon_result['uses_per_user'] > 0) {

				$error_message .= ERROR_INVALID_USES_USER_COUPON . $coupon_result['uses_per_user'] . TIMES . '<br>';

			// # zones derived from /admin/zones.php
			} else if($coupon_result['coupon_type'] == 'S' && !$validPromoMethods) {
	
				$error_message .= '<b style="color:red">Cannot use coupon code for this shipping method.</b><br>';
	
			} else if($coupon_result['coupon_type'] == 'S' && in_array($ship_zone_id, array(2,21,52))) {

				$error_message .= '<b style="color:red">Coupon code not valid for your shipping location.</b><br>';

			} else if(!in_array($customer_group_id, $price_groups)) {

	  			$error_message .= '<div style="color:red; font-weight:bold; padding: 5px">Sorry, this coupon code isn\'t valid for your currently logged in user account. <br><br>Please check your login credentials or sign-in with the account that allows this type of promotion to be used.</div>';
				
			}

	
			if (empty($error_message)) {

   				if ($coupon_result['coupon_type']=='S') {

   					$coupon_amount = $order->info['shipping_cost'];

    			} else {

  					if (!tep_session_is_registered('cc_id')) tep_session_register('cc_id');
					if (tep_session_is_registered('cc_gv_id')) tep_session_unregister('cc_gv_id');

					// # strip any added html tags to the currency class (like structured data spans and meta tags).
					$coupon_amount = $currencies->format($coupon_result['coupon_amount']);
					$coupon_amount = strip_tags(trim($coupon_amount)) . ' ';

   				}
        
    			if ($coupon_result['coupon_type']=='P') $coupon_amount = $coupon_result['coupon_amount'] . '% ';

	    		if ($coupon_result['coupon_minimum_order'] > 0) $coupon_amount .= 'on orders greater than ' . $coupon_result['coupon_minimum_order'];

    			$_SESSION['cc_id'] = $coupon_result['coupon_id'];
				$success_message = '<b style="color:green">Coupon applied successfully!</b>';

      		} else { // # if errors occurred, reset coupon session.

   				if (tep_session_is_registered('cc_id')) tep_session_unregister('cc_id');
				if (tep_session_is_registered('cc_gv_id')) tep_session_unregister('cc_gv_id');
			}

		
		} else if($coupon_result['coupon_type'] == 'G') {
	

			if (!tep_session_is_registered('cc_gv_id'))	tep_session_register('cc_gv_id');		
			if (tep_session_is_registered('cc_id')) tep_session_unregister('cc_id');
			$_SESSION['cc_gv_id'] = $coupon_result['coupon_id'];

			$gv_balance = (!isset($coupon_result['gv_balance'])) ? $coupon_result['coupon_amount'] : $coupon_result['gv_balance'];

			$cust_ids = explode(',', $coupon_result['restrict_to_customers']);

			$customer_id = (tep_session_is_registered('customer_id')) ? $_SESSION['customer_id'] : NULL;

			if (!in_array($customer_id, $cust_ids)) {
			
				if (tep_session_is_registered('cc_gv_id')) tep_session_unregister('cc_gv_id');
	  		
				$error_message .= '<b style="color:red">Sorry, this credit code isn\'t valid for your currently logged in user account. Please check your login credentials or sign-in with the account in which your credit code is applied too.</b><br>';

			} else if ((float)$gv_balance < '0.01') { 

				if (tep_session_is_registered('cc_gv_id')) tep_session_unregister('cc_gv_id');
	  		
				$error_message .= '<b style="color:red">Sorry, this credit code has a zero balance.</b><br>';
				
			}

			// # strip any added html tags to the currency class (like structured data spans and meta tags).
			$storeCredit = $currencies->format($gv_balance);
			$storeCredit = strip_tags(trim($storeCredit));

			if (!$error_message) {
				$success_message .= '<b style="color:green">Credit for '.$storeCredit.' applied successfully!</b>';
			} 

			/* 
			// # commented out due to what seems like duplication and lack of order_id	
			// # also found in function apply_credit() - /IXcore/common/modules/ot/ot_coupon.php around line 350.

			$gv_redeem = tep_db_query("INSERT INTO " . TABLE_COUPON_REDEEM_TRACK . " 
									   SET coupon_id = '" . tep_db_prepare_input($coupon_result['coupon_id']) . "', 
									   customer_id = '" . (int)$customer_id . "', 
									   redeem_date = NOW(), 
									   redeem_ip = '" . $REMOTE_ADDR . "'
									  ");

				// # strip any added html tags to the currency class (like structured data spans and meta tags).
				$storeCredit = $currencies->format($gv_balance);
				$storeCredit = strip_tags(trim($storeCredit));

		      $error_message .= ERROR_REDEEMED_AMOUNT. $storeCredit . '<br>';
			*/

		}

	} // # END if empty($gv_redeem_code)

	if ($_POST['submit_redeem_coupon_x'] && !$_POST['gv_redeem_code']) {
		$error_message .= ERROR_NO_REDEEM_CODE . '<br>';
	}

	//$order_total_modules->pre_confirmation_check();
  
  if($error_message) {
    echo '<div style="color:red; background-color:#FFFFE4; width:100%; font:bold 12px arial; padding:5px;">'. $error_message . '</div>';
  } elseif ($success_message) {
    echo $success_message . '<br><br>';
  }



	echo '<table class="couponcode">
			<tr><td>Coupon Code: </td>
				<td>' . tep_draw_input_field('gv_redeem_code', $gv_redeem_code, 'id="gv_redeem_code"') . '</td>
				<td><img onclick="applyCoupon($(\'gv_redeem_code\').value)" src="' . DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/' . $language . '/button_redeem.gif" border="0" alt="' . IMAGE_REDEEM_VOUCHER . '" title="' . IMAGE_REDEEM_VOUCHER . '" style="cursor:pointer"></td>
				<td>&nbsp; <a href="'.$parentpage.'?removecode=1" class="removecode">Remove code</a></td>
			</tr>
		</table>';
?>
<script type="text/javascript">
  reloadOT();
</script>
