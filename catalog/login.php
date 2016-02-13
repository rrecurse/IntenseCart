<?php

  require('includes/application_top.php');

	if(SITE_ENABLE_SSL === 1) { 
		if($_SERVER["HTTPS"] != "on") {
			header("HTTP/1.1 301 Moved Permanently");
    		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
	    	exit();
		}
	}

	$ssl = (SITE_ENABLE_SSL === 1 && $_SERVER['HTTPS'] != 'off' ? 'SSL' : 'NOSSL' );

	$url = FILENAME_ACCOUNT;

	// # if session customer_id is already registered, no need for rest of page.
	if(tep_session_is_registered('customer_id')) {
		tep_redirect(tep_href_link($url, '', $ssl));
		exit();
	}

// # PayPal WPP Modification START
// # Assign a variable to cut down on database calls
// # Don't show checkout option if cart is empty.  It does not satisfy the paypal

  if (tep_paypal_wpp_enabled() && $cart->count_contents() > 0) {
    //$ec_enabled = true;
    $ec_enabled = false;
  } else {
    $ec_enabled = false;
  }
  
	if ($ec_enabled) {

		// # If they're here, they're either about to go to paypal or were sent back by an error, so clear these session vars
    	if(tep_session_is_registered('paypal_ec_temp')) tep_session_unregister('paypal_ec_temp');
	    if(tep_session_is_registered('paypal_ec_token')) tep_session_unregister('paypal_ec_token');
    	if(tep_session_is_registered('paypal_ec_payer_id')) tep_session_unregister('paypal_ec_payer_id');
  		if(tep_session_is_registered('paypal_ec_payer_info')) tep_session_unregister('paypal_ec_payer_info');
    
		//# Find out if the user is logging in to checkout so that we know to draw the EC box          
		$checkout_login = false;
		if(sizeof($navigation->snapshot) > 0 || isset($_GET['payment_error'])) {
			if(strpos($navigation->snapshot['page'], 'checkout_') !== false || isset($_GET['payment_error'])) {
				$checkout_login = true;
		    }
		}
	}
	//# PayPal WPP Modification END

	// # redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
	if($session_started == false) {
    	tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
	}

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGIN);

	$error = false;

	if(!empty($_POST['email_address']) && !empty($_POST['password'])) {

		$email_address = tep_db_prepare_input($_POST['email_address']);
    	$password = tep_db_prepare_input($_POST['password']);

		// # Check if user account exists
    	$check_customer_query = tep_db_query("SELECT * FROM " . TABLE_CUSTOMERS . " WHERE customers_email_address LIKE '" . tep_db_input($email_address) . "'");
	    if(tep_db_num_rows($check_customer_query) == 0) {

			$error = true;

    	} else {

			$check_customer = tep_db_fetch_array($check_customer_query);
    
			  // # Check that password is good
			if(!tep_validate_password($password, $check_customer['customers_password'])) {

		        $error = true;

			} else {

		        if(SESSION_RECREATE == 'True') {
        			tep_session_recreate();
				}

			$check_country_query = tep_db_query("SELECT entry_country_id, entry_zone_id 
												 FROM " . TABLE_ADDRESS_BOOK . " 
												 WHERE customers_id = '" . (int)$check_customer['customers_id'] . "' 
												 AND address_book_id = '" . (int)$check_customer['customers_default_address_id'] . "'
												");

			$check_country = tep_db_fetch_array($check_country_query);

			$customer_id = $check_customer['customers_id'];
			$customer_default_address_id = $check_customer['customers_default_address_id'];
			$customer_first_name = $check_customer['customers_firstname'];
			$customer_password = $password;
			$customer_country_id = $check_country['entry_country_id'];
			$customer_zone_id = $check_country['entry_zone_id'];
			$sppc_customer_group_id = $check_customer['customers_group_id'];

			tep_session_register('customer_id');
			tep_session_register('customer_default_address_id');
			tep_session_register('customer_first_name');
			tep_session_register('customer_password');
			tep_session_register('customer_country_id');
			tep_session_register('customer_zone_id');
			tep_session_register('sppc_customer_group_id');

			tep_session_unregister('referral_id'); // # rmh referral

			tep_db_query("UPDATE " . TABLE_CUSTOMERS_INFO . " 
						  SET customers_info_date_of_last_logon = NOW(), 
						  customers_info_number_of_logons = (customers_info_number_of_logons + 1)
						  WHERE customers_info_id = '" . (int)$customer_id . "'
						");

			// # restore cart contents
			$cart->restore_contents();

			// # restore wishlist to sesssion $wishList->restore_wishlist();
        	$wishList->restore_wishlist();
        
			$ssl = ($_SERVER['HTTPS'] != 'off' ? 'SSL' : 'NOSSL' );
			$url = FILENAME_ACCOUNT;

			if(!empty($navigation->snapshot)) {

				$origin_href = $navigation->snapshot['page'];  

				$current_url = ltrim($_SERVER["REQUEST_URI"], '/');

				$navigation->clear_snapshot();

				if($origin_href != $current_url)  { 
					$url = $origin_href;
				} else { 
					$url = FILENAME_ACCOUNT;	
				}
			}

			if($_POST['co'] == '1') {
				$url = FILENAME_CHECKOUT_SHIPPING;
    	    } elseif(isset($_GET['return'])) {
				$url = filter_var($_GET['return'], FILTER_SANITIZE_URL);
				$url = parse_url($url, PHP_URL_PATH);
			}

			tep_redirect(tep_href_link($url, '', $ssl));

		}
	}
}

	if($error == true) {
		$messageStack->add('login', TEXT_LOGIN_ERROR);
	}

	// # PayPal WPP Modification START
	if($ec_enabled) {
		if(tep_session_is_registered('paypal_error')) {
			$checkout_login = true;
			$messageStack->add('login', $paypal_error);
			tep_session_unregister('paypal_error');
		}
	}
	// # PayPal WPP Modification END

	$breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_LOGIN, '', 'SSL'));
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex, nofollow">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>

<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require(DIR_WS_INCLUDES . 'column_left.php');?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" valign="top" style="padding-top:5px;" colspan="2">
	
	
<?php 
		echo tep_draw_form('loginMain', tep_href_link(FILENAME_LOGIN, (isset($_GET['return']) ? '&return=' . $_GET['return'] : ''), 'SSL'));
?>

<table width="95%" border="0" align="center" cellpadding="0" cellspacing="0" >
						


						<?php
  if ($messageStack->size('login') > 0) {
?>
      <tr>
        <td style="padding-bottom:10px;" colspan="3"><?php echo $messageStack->output('login'); ?></td>
      </tr>
<?php
  }

  if ($cart->count_contents() > 0) {
?>
      <tr>
        <td class="smallText" style="padding-bottom:10px;" colspan="3"><?php echo TEXT_VISITORS_CART; ?></td>
      </tr>
<?php
  }
?>
							<tr valign="top">
							<td class="lftprtborder" align="left">&#160;<b><span class="subheadline"><?php echo HEADING_RETURNING_CUSTOMER; ?></span></b></td>	
							<td width="10" rowspan="2"></td>
							<td class="lftprtborder" align="left" style="height: 21px;">&#160;<b><span class="subheadline" style="width:151px;"><?php echo HEADING_NEW_CUSTOMER; ?></span></b> </td>
							</tr>
							<tr class="MediumCell">
								<td class="LightCell" align="left" valign="top" width="50%">
									<table cellspacing="0" cellpadding="0" border="0" width="100%">
										 <tr>
										 <td colspan=2>
												<table width="100%" border="0" cellspacing="0" cellpadding="0">
												<tr>
												<td width="120" class="emailTxt">&#160; <?php echo ENTRY_EMAIL_ADDRESS; ?></td>
												<td align="left"><?php echo tep_draw_input_field('email_address'); ?></td>
										 </tr>
                                            <tr>
                                                <td style="padding-top:5px;" class="passwordTxt"> &#160; <?php echo ENTRY_PASSWORD; ?>  </td> 
												<td align="left" style="padding-top:5px;"> <?php echo tep_draw_password_field('password'); ?></td>
  </tr>
</table></td>
                                            </tr>
                                            <tr valign="baseline">
                      
                                                <td colspan="2" align="left" style="padding-top:10px;">
                                                   <?php echo ($_GET['co'] == '1' ? tep_draw_hidden_field('co', '1') : '') . tep_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN); ?> </td>
                                            </tr>
<tr>
	<td colspan="2" style="width:100%"><?php echo '<a href="' . tep_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></td>
</tr>
                                                <tr>
                                                    <td colspan="2" style="width:100%">
                                                    <a href="/account.php" class="sublinks">My Account/Change Password</a>                                                    </td>
                                                </tr>
                                                <tr >
                                                    <td colspan="2" style="width:100%">
<a href="/contact_us.php" class="sublinks">Problem logging in?</a></td>
                                                </tr>
                                     
                                            <tr valign="baseline">
                                                <td colspan="2" align="right" valign="middle" style="width: 225px"></td>
                                            </tr>
                                            
                                            <tr valign="baseline">
                                                <td colspan="2" align="right" valign="middle" style=" height: 38px;"></td>
                                            </tr>
                                       		<tr>
												<td colspan="3"><div class="vendorLogin">
			
												   <table width="100%" border="0" cellpadding="0" cellspacing="0">
											          <tr>
                				                        <td align="left" class="lftprtborder">&#160;<b><span class="subheadline"><?php echo HEADING_VENDOR_SIGNUP; ?></span></b></td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="height:23px; margin-left:5px" class="bodycontent" >
                                           <?php echo TEXT_VENDOR_SIGNUP; ?><br><br></td>
                                    </tr>
                                    <tr>
                                    <td align="left" style="height: 23px">
									 <?php echo '<a href="' . tep_href_link(FILENAME_CREATE_ACCOUNT, 'apply=vendor', 'SSL') . '">' . tep_image_button('button_create_account.gif', IMAGE_BUTTON_CONTINUE) . ' </a>'; ?></td>
                                    </tr>
									</table></div></td></tr>
								  </table>								</td> 
								<td valign="top">
								<table width="100%" border="0" cellpadding="0" cellspacing="0">
								<tr>
								<td align="left" class="bodycontent"><?php echo TEXT_NEW_CUSTOMER; ?></td>
							    </tr>

                                    <tr>
                                        <td align="left">
                                        <br>
                                            
                                    <?php echo '<a href="' . tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL') . '">' . tep_image_button('button_create_account.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                                    </tr>
								  </table>
<?php

	// # PayPal WPP Modification START

	if ($ec_enabled) {
		if ($checkout_login) {
?>								 
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td class="main" width="50%" valign="top" colspan="3"><b><?php echo TEXT_PAYPALWPP_EC_HEADER; ?></b></td></tr>
				<tr>
					<td width="100%" colspan="3" valign="top">
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
			 				<tr>
								<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
								<td align="center"><a href="<?php echo tep_href_link('ec_process.php', '', 'SSL'); ?>"><img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" border=0></a></td>
								<td align="left" valign="middle"><span style="font-size:11px; font-family: Arial, Verdana;"><?php echo TEXT_PAYPALWPP_EC_BUTTON_TEXT; ?></span></td>
								<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    		</tr>
						</table>
					</td>
				</tr>
	      </table>		
<?php 
			} 
		}

	// # PayPal WPP Modification END

?>

</td>
  </tr>
</table>
</form></td>
		</tr>


      </table>		
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
