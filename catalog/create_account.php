<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

if($_SERVER['SERVER_PORT'] != 443) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit();
}

	require('includes/application_top.php');
  
	if(isset($_POST['apply'])) { 
		$apply = tep_db_prepare_input($_POST['apply']); 
	} elseif(isset($_GET['apply'])) { 
		$apply = tep_db_prepare_input($_GET['apply']); 
	}

  if($apply == 'affiliate') tep_redirect(FILENAME_AFFILIATE_SIGNUP);

	// # PayPal WPP Modification START
	// # Assign a variable to cut down on database calls
	// # Don't show checkout option if cart is empty.  It does not satisfy the paypal
	if(tep_paypal_wpp_enabled() && $cart->count_contents() > 0) {
		//$ec_enabled = true;
	    $ec_enabled = false;
	} else {
		$ec_enabled = false;
	}
  
	if($ec_enabled) {

	    // # If they're here, they're either about to go to paypal or were sent back by an error, so clear these session vars
    	if (tep_session_is_registered('paypal_ec_temp')) tep_session_unregister('paypal_ec_temp');
	    if (tep_session_is_registered('paypal_ec_token')) tep_session_unregister('paypal_ec_token');
    	if (tep_session_is_registered('paypal_ec_payer_id')) tep_session_unregister('paypal_ec_payer_id');
	    if (tep_session_is_registered('paypal_ec_payer_info')) tep_session_unregister('paypal_ec_payer_info');
	}

	if($_GET['co'] == '1') {
		$checkout = true;
	} else {
		$checkout = false;
	}
	// # PayPal WPP END

	// # needs to be included earlier to define the CONSTANT success message in the messageStack
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ACCOUNT);

	$process = false;
	$error = false;
	if(isset($_POST['action']) && ($_POST['action'] == 'process')) {

		$process = true;

	    $firstname = tep_db_prepare_input($_POST['firstname']);
	    $lastname = tep_db_prepare_input($_POST['lastname']);
	    $street_address = tep_db_prepare_input($_POST['street_address']);
    	$postcode = tep_db_prepare_input($_POST['postcode']);
	    $city = tep_db_prepare_input($_POST['city']);

    	$country = (!empty($_POST['country'])) ? tep_db_prepare_input($_POST['country']) : STORE_COUNTRY;

		// # Detect if country is passed as name instead of ID then convert back to ID
		if(!is_numeric($country)) { 

			$country = tep_db_result(tep_db_query("SELECT countries_id FROM " . TABLE_COUNTRIES . " WHERE countries_name LIKE '" . $country . "'"), 0);
		}

		// # strip any non-numeric characters and spaces from phone number
	    $telephone = preg_replace('/[^0-9]/', '', $_POST['telephone']);

    	$fax = tep_db_prepare_input($_POST['fax']);
	    $password = tep_db_prepare_input($_POST['password']);
    	$confirmation = tep_db_prepare_input($_POST['confirmation']);
	    $source = (!empty($_POST['source'])) ? tep_db_prepare_input($_POST['source']) : '';
	
    	if (ACCOUNT_DOB == 'true') {
			$dob = tep_db_prepare_input($_POST['dob']);
		}

		if(ACCOUNT_GENDER == 'true') {
			if(isset($_POST['gender'])) {
				$gender = tep_db_prepare_input($_POST['gender']);
			} else { 
				$error = true;
				$messageStack->add('create_account', ENTRY_GENDER_ERROR);

			}
    	}

	    if(ACCOUNT_COMPANY == 'true') {

		    $company = tep_db_prepare_input($_POST['company']);

			// # Strip out all characters except hyphens and numbers
    		$company_tax_id = (!empty($_POST['tax_id'])) ? preg_replace('/[^-0-9]/', '', $_POST['tax_id']) : '';
	    }

		// # Address 2 check
		$suburb = (!empty($_POST['suburb'])) ? tep_db_prepare_input($_POST['suburb']) : '';

		if(ACCOUNT_STATE == 'true') {
			$state = tep_db_prepare_input($_POST['state']);
			$zone_id = (isset($_POST['zone_id']) ? tep_db_prepare_input($_POST['zone_id']) : false);
		}

		$newsletter = (isset($_POST['newsletter'])) ? tep_db_prepare_input($_POST['newsletter']) : 1;

    	$source_other = (isset($_POST['source_other'])) ? tep_db_prepare_input($_POST['source_other']) : '';

		// # check if email address posted and validate it
		if(!filter_var($_POST['email_address'], FILTER_VALIDATE_EMAIL)) {
			$email_address = '';
    	    $error = true;
			$messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
		} else {
			$email_address = preg_replace("/[^,;a-zA-Z0-9_.@-]/i",'', $_POST['email_address']);
		}

		// # check length of first name against minimums in admin
		if(strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
	    	$error = true;
			$messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
		}

		// # check length of last name against minimums in admin
	    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
			$error = true;
			$messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);

		}

		if(ACCOUNT_DOB == 'true') {
			if(checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4)) == false) {
	    	    $error = true;
    	    	$messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
			}
		}

		// # check length of email addres against minimums in admin
    	if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
			$error = true;
			$messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);

		} elseif(tep_validate_email($email_address) === false) {

			$error = true;
			$messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);

		} else {

		// # PayPal WPP START

			if ($ec_enabled) {
				if (tep_session_is_registered('paypal_error')) {
					$messageStack->add('create_account', $paypal_error);
					tep_session_unregister('paypal_error');
				}
			}

			$check_email_query = tep_db_query("SELECT customers_id AS id, 
											   customers_paypal_ec AS ec 
											   FROM " . TABLE_CUSTOMERS . " 
											   WHERE customers_email_address = '" . tep_db_input($email_address) . "'
											  ");

			if(tep_db_num_rows($check_email_query) > 0) {

				$check_email = tep_db_fetch_array($check_email_query);
			
				if($check_email['ec'] == '1') {
					// # It's a temp account, so delete it and let the user create a new one
					tep_db_query("delete from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$check_email['id'] . "'");
					tep_db_query("delete from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$check_email['id'] . "'");
					tep_db_query("delete from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . (int)$check_email['id'] . "'");
					tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$check_email['id'] . "'");
					tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$check_email['id'] . "'");
					tep_db_query("delete from " . TABLE_WHOS_ONLINE . " where customer_id = '" . (int)$check_email['id'] . "'");
				} else {
					$error = true;
					$messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
				} // # PayPal WPP END
			}
		} 

    if(strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
		$error = true;
		$messageStack->add('create_account', ENTRY_STREET_ADDRESS_ERROR);
	}

    if(strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
		$error = true;
		$messageStack->add('create_account', ENTRY_POST_CODE_ERROR);
    }

    if(strlen($city) < ENTRY_CITY_MIN_LENGTH) {
		$error = true;
		$messageStack->add('create_account', ENTRY_CITY_ERROR);
    }

	if(!isset($country)) {

		$error = true;
		$messageStack->add('create_account', ENTRY_COUNTRY_ERROR);

    }

    if (ACCOUNT_STATE == 'true') {
		$zone_id = 0;

		$check_query = tep_db_query("SELECT count(0) AS total FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$country . "'");

		$entry_country_has_zones = (tep_db_num_rows($check_query) > 0 ? true : false);

		if($entry_country_has_zones == true) {

			$zone_query = tep_db_query("SELECT DISTINCT zone_id 
										FROM " . TABLE_ZONES . " 
										WHERE zone_country_id = '" . (int)$country . "' 
										AND (zone_name LIKE '%" . tep_db_input($state) . "%' OR zone_code LIKE '%" . tep_db_input($state) . "%')
									   ");
		
			if(tep_db_num_rows($zone_query) > 0) {
				$zone = tep_db_fetch_array($zone_query);
				$zone_id = $zone['zone_id'];
	        } else {
    			$error = true;
				$messageStack->add('create_account', ENTRY_STATE_ERROR_SELECT);
			}

		} else {

        if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
			$error = true;
			$messageStack->add('create_account', ENTRY_STATE_ERROR);
        }
      }
    }

	if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
		$error = true;
		$messageStack->add('create_account', ENTRY_TELEPHONE_NUMBER_ERROR);
	}

    if ((REFERRAL_REQUIRED == 'true') && (is_numeric($source) == false)) {
        $error = true;
        $messageStack->add('create_account', ENTRY_SOURCE_ERROR);
    }

    if ((REFERRAL_REQUIRED == 'true') && (DISPLAY_REFERRAL_OTHER == 'true') && ($source == '9999') && (!tep_not_null($source_other)) ) {
        $error = true;
        $messageStack->add('create_account', ENTRY_SOURCE_OTHER_ERROR);
    }

    if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
		$error = true;
		$messageStack->add('create_account', ENTRY_PASSWORD_ERROR);

	}

	if($password != $confirmation) {
		$error = true;
		$messageStack->add('create_account', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
    }

    if ($apply=='vendor') {
      if(empty($company)) {
        $error = true;
        $messageStack->add('create_account', ENTRY_COMPANY_ERROR);
      }
      if(empty($company_tax_id)) {
        //$error = true;
        $messageStack->add('create_account', ENTRY_COMPANY_TAX_ID_ERROR);
      }
    }


    if ($error == false) {
      $sql_data_array = array('customers_firstname' => $firstname,
                              'customers_lastname' => $lastname,
                              'customers_email_address' => $email_address,
                              'customers_telephone' => $telephone,
                              'customers_fax' => $fax,
                              'customers_newsletter' => $newsletter,
                              'customers_password' => tep_encrypt_password($password),
                              'customers_advertiser' => tep_db_prepare_input($advertiser),
                              'customers_referer_url' => tep_db_prepare_input($referer_url)
							);

      if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $gender;
      if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($dob);

      // # if you would like to have an alert in the admin section when either a company name has been entered in
      // # the appropriate field or a tax id number, or both then uncomment the next line and comment the default
      // # setting: only alert when a tax_id number has been given

		if ($apply=='vendor') {
			$sql_data_array['customers_group_ra'] = '1';
			$sql_data_array['customers_group_id'] = '1';
			$sppc_customer_group_id=1;
		} else {
			$sppc_customer_group_id=0;
		}

 		if(isset($affiliate_ref) && $affiliate_ref) {
			$sql_data_array['customers_referred_by'] = $affiliate_ref;
		}

	
	tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

	$customer_id = tep_db_insert_id();
	
	$sql_data_array = array('customers_id' => $customer_id,
                            'entry_firstname' => $firstname,
                            'entry_lastname' => $lastname,
                            'entry_street_address' => $street_address,
                            'entry_postcode' => $postcode,
                            'entry_city' => $city,
                            'entry_country_id' => $country);

	if(ACCOUNT_GENDER == 'true') {
		$sql_data_array['entry_gender'] = $gender;
	}
	
      $sql_data_array['entry_company'] = (!empty($company)) ? $company : '';
      $sql_data_array['entry_company_tax_id'] = (!empty($company_tax_id)) ? $company_tax_id : '';


		if(!empty($suburb)) {
			$sql_data_array['entry_suburb'] = $suburb;
		}

      if (ACCOUNT_STATE == 'true') {
        if ($zone_id > 0) {
          $sql_data_array['entry_zone_id'] = $zone_id;
          $sql_data_array['entry_state'] = $state;
        } else {
          $sql_data_array['entry_zone_id'] = '0';
          $sql_data_array['entry_state'] = $state;
        }
      }

	tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

	$address_id = tep_db_insert_id();

	tep_db_query("UPDATE " . TABLE_CUSTOMERS . " 
				  SET customers_default_address_id = '" . (int)$address_id . "' 
				  WHERE customers_id = '" . (int)$customer_id . "'
			     ");

	tep_db_query("INSERT INTO " . TABLE_CUSTOMERS_INFO . " 
				  SET customers_info_id = '".(int)$customer_id."', 
				  customers_info_number_of_logons = '1', 
				  customers_info_date_account_created = NOW(),
				  customers_info_date_account_last_modified = NOW(), 
				  customers_info_date_of_last_logon = NOW(),
				  customers_info_source_id = '".(int)$source."'
				");

	if($source == '9999') {
        tep_db_perform(TABLE_SOURCES_OTHER, array('customers_id' => (int)$customer_id, 'sources_other_name' => tep_db_input($source_other)));
      }

	if (SESSION_RECREATE == 'True') {
		tep_session_recreate();
	}

      $customer_first_name = $firstname;
      $customer_default_address_id = $address_id;
      $customer_country_id = $country;
      $customer_zone_id = $zone_id;
      tep_session_register('customer_id');
      tep_session_register('customer_first_name');
      tep_session_register('customer_default_address_id');
      tep_session_register('customer_country_id');
      tep_session_register('customer_zone_id');
      tep_session_register('sppc_customer_group_id');
      tep_session_unregister('referral_id');


	// # restore cart contents
    $cart->restore_contents();
    $wishList->restore_wishlist();
	// # build the message content
    $name = $firstname . ' ' . $lastname;

      if (ACCOUNT_GENDER == 'true') {
         if ($gender == 'm') {
           $email_text = sprintf(EMAIL_GREET_MR, $lastname);
         } else {
           $email_text = sprintf(EMAIL_GREET_MS, $lastname);
         }
      } else {
        $email_text = sprintf(EMAIL_GREET_NONE, $firstname);
      }

    if ($apply=='vendor') { 
      $email_text .= VENDOR_EMAIL_WELCOME . VENDOR_EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
    } else { 
      $email_text .= EMAIL_WELCOME . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
	}

	// # CCGV CODE BLOCK BEGIN
	if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {

		$coupon_code = create_coupon_code();

		tep_db_query("INSERT IGNORE INTO " . TABLE_COUPONS . " 
					  SET coupon_code = '" . $coupon_code . "', 
					  coupon_type = 'G', 
					  coupon_amount = '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', 
					  date_created = NOW()
					");

		tep_db_query("INSERT IGNORE INTO " . TABLE_COUPON_EMAIL_TRACK . " 
					   SET customer_id_sent = '0', 
					   sent_firstname = 'Admin', 
					   emailed_to = '" . $email_address . "', 
					   date_sent = NOW()
					  ");

		$email_text .= sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) . "\n\n" . sprintf(EMAIL_GV_REDEEM, $coupon_code) . "\n\n" . EMAIL_GV_LINK . tep_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code,'NONSSL', false) . "\n\n";
	
	}


	if(NEW_SIGNUP_DISCOUNT_COUPON != '') {
		$coupon_code = NEW_SIGNUP_DISCOUNT_COUPON;
		$coupon_query = tep_db_query("select * from " . TABLE_COUPONS . " where coupon_code = '" . $coupon_code . "'");
	
		if(tep_db_num_rows($coupon_query) > 0) { 

		    $coupon = tep_db_fetch_array($coupon_query);

			$coupon_id = $coupon['coupon_id'];		
    		$coupon_desc_query = tep_db_query("SELECT * FROM " . TABLE_COUPONS_DESCRIPTION . " WHERE coupon_id = '" . $coupon_id . "' AND language_id = '" . (int)$languages_id . "'");
	    	$coupon_desc = tep_db_fetch_array($coupon_desc_query);

			$insert_query = tep_db_query("INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . " 
										  SET coupon_id = '" . $coupon_id ."', 
										  customer_id_sent = '0', 
										  sent_firstname = 'Admin', 
										  emailed_to = '" . $email_address . "', 
										  date_sent = NOW()
										");
			$insert_id = tep_db_insert_id($insert_query);
		}


    	$email_text .= EMAIL_COUPON_INCENTIVE_HEADER .  "\n" . sprintf("%s", $coupon_desc['coupon_description']) ."\n\n" . sprintf(EMAIL_COUPON_REDEEM, $coupon['coupon_code']) . "\n\n" . "\n\n";
	}
	// # CCGV CODE BLOCK END

		tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

		// # alert shop owner of account created by a company
    	// # if you would like to have an email when either a company name has been entered in
	    // # the appropriate field or a tax id number, or both then uncomment the next line and comment the default
    	// # setting: only email when a tax_id number has been given
		//if( (ACCOUNT_COMPANY == 'true' && tep_not_null($company) ) || (ACCOUNT_COMPANY == 'true' && tep_not_null($company_tax_id) ) ) {

		if($apply=='vendor') {
		//if ( ACCOUNT_COMPANY == 'true' && tep_not_null($company_tax_id) ) {
			$alert_email_text = "Please note that " . $firstname . " " . $lastname . " of the company: " . $company . " has applied for a Vendor account.";

			tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, 'Company account created', $alert_email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		} else {
		// # alert shop owner of account created by a company
			$email_text = '<br><br><b>New Account was created by:</b><br>' . $firstname . ' ' . $lastname . '<br>' . $company . '<br>' . $city . ', ' . $state . '<br>' . $email_address;
			tep_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'New Online Store Account', $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		}
    
		if($checkout) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
		} else {
    		tep_redirect(tep_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS, '', 'SSL'));
		}
	}
}
  if ($checkout) {
    $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_CREATE_ACCOUNT, 'co=1', 'SSL'));
  } else {
    $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'));
  }
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<?php require('includes/form_check.js.php'); ?>
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top">
<?php echo tep_draw_form('create_account', tep_href_link(FILENAME_CREATE_ACCOUNT, "", 'SSL'), 'post', 'autocomplete="off" onsubmit="return check_form(create_account);"') . tep_draw_hidden_field('action', 'process'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php
  if ($messageStack->size('create_account') > 0) {
?>
      <tr>
        <td id="messageStack"><?php echo $messageStack->output('create_account'); ?></td>
      </tr>
<?php
  }


// # PayPal WPP Modification START
?>
	<tr>
        <td>
          <table border=0 width="100%" cellspacing=0 cellpadding=0>
			<tr>
<?php
    if ($ec_enabled && $checkout) {
?>
        <td style="width:200px">
          <table border=0 width="100%" cellspacing=0 cellpadding=0>
          <tr>
            <td class="main" width="50%" valign="top"><b><?php echo TEXT_PAYPALWPP_EC_HEADER; ?></b></td>
          </tr>
          <tr>
            <td width="100%" colspan=2 valign="top"><table border="0" width="100%" height="100%" cellspacing="1" cellpadding="2" class="infoBox">
              <tr class="infoBoxContents">
                <td><table border="0" width="100%" height="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                      <tr>
                        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                        <td align="center"><a href="<?php echo tep_href_link('ec_process.php', '', 'SSL'); ?>"><img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" border=0></a></td>
												<td align="left" valign="middle"><span style="font-size:11px; font-family: Arial, Verdana;"><?php echo TEXT_PAYPALWPP_EC_BUTTON_TEXT; ?></span></td>
                        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                      </tr>
                    </table></td>
                  </tr>
                </table></td>
              </tr>
            </table>
            </td>
          </tr>
          </table>
        </td>
        <td style="width:1px;background-color:#000000;"></td>
<?php } ?>
        <td valign="top">
          <table border=0 width="493" cellspacing=0 cellpadding=0>         
            <tr>
              <td style="padding-bottom:10px;">
                <span style="font-size:13px; font-family: Arial, Verdana; color:#FF0000;"><?php echo sprintf(TEXT_ORIGIN_LOGIN, tep_href_link(FILENAME_LOGIN, tep_get_all_get_params(), 'SSL')); ?></span>
              </td>
            </tr>

          </table>
        </td>
        </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td style="background-color:#000000;height:2px"></td>
      </tr>
<?php // # PayPal WPP Modification END ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" style="background-color:#EFEFEF; padding:5px"><b>&#149; <?php echo CATEGORY_PERSONAL; ?></b></td>
           <td class="inputRequirement" style="background-color:#EFEFEF; padding:5px; padding-right:21px;" align="right"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">

              <tr>
                <td class="main"><?php echo ENTRY_FIRST_NAME; ?></td>
                <td class="main"><?php echo tep_draw_input_field('firstname', '') . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_LAST_NAME; ?></td>
                <td class="main"><?php echo tep_draw_input_field('lastname', '') . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></td>
              </tr>

<?php
  if (ACCOUNT_GENDER == 'true') {
?>
              <tr>
                <td class="main"><?php echo ENTRY_GENDER; ?></td>
                <td class="main"><?php echo tep_draw_radio_field('gender', '') . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('gender', 'f') . '&nbsp;&nbsp;' . FEMALE . '&nbsp;' . (tep_not_null(ENTRY_GENDER_TEXT) ? '<span class="inputRequirement">' . ENTRY_GENDER_TEXT . '</span>': ''); ?></td>
              </tr>
<?php
  }
?>

<?php
  if (ACCOUNT_DOB == 'true') {
?>
              <tr>
                <td class="main"><?php echo ENTRY_DATE_OF_BIRTH; ?></td>
                <td class="main"><?php echo tep_draw_input_field('dob','') . '&nbsp;' . (tep_not_null(ENTRY_DATE_OF_BIRTH_TEXT) ? '<span class="inputRequirement">' . ENTRY_DATE_OF_BIRTH_TEXT . '</span>': ''); ?></td>
              </tr>
<?php
  }
?>

              <tr>
                <td class="main"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
                <td class="main"><?php echo tep_draw_input_field('telephone', '', 'style="width:100px" maxlength=32') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
              </tr>


              <tr>
                <td class="main"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
                <td class="main"><?php echo tep_draw_input_field('email_address', ''). '&nbsp;' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  if (ACCOUNT_COMPANY == 'true') {
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" style="background-color:#EFEFEF;padding:5px"><b>&#149; <?php echo CATEGORY_COMPANY; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><?php echo ENTRY_COMPANY; ?></td><td class="main">
<?php echo tep_draw_input_field('company','','',$apply) . '&nbsp;' . (tep_not_null(ENTRY_COMPANY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COMPANY_TEXT . '</span>': ''); ?></td>
              </tr>

<?php if($apply=='vendor') {
?>
			<tr>
                <td class="main"><?php echo ENTRY_COMPANY_TAX_ID; ?></td>
                <td class="main"><?php echo tep_draw_input_field('tax_id','','',true) . '&nbsp;' . (tep_not_null(ENTRY_COMPANY_TAX_ID_TEXT) ? '<span class="inputRequirement">' . ENTRY_COMPANY_TAX_ID_TEXT . '</span>': ''); ?></td>
              </tr>
<?php
    } else { 
		echo tep_draw_hidden_field('tax_id','');
	}
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" style="background-color:#EFEFEF;padding:5px"><b>&#149; <?php echo CATEGORY_ADDRESS; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main" style="width:240px"><?php echo ENTRY_STREET_ADDRESS . '<br>' . tep_draw_input_field('street_address', '', 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
                <td class="main" colspan=2><?php echo ENTRY_SUBURB . '<br>' . tep_draw_input_field('suburb', '', 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main"></td>
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_CITY . '<br>' . tep_draw_input_field('city', '', 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
                <td class="main"><?php echo ENTRY_STATE . '<br>'; ?>
<?php
    if ($process == true) {

       	$zones_array = array();
		$zones_query = tep_db_query("SELECT zone_name FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$country . "' ORDER BY zone_name");

		if(tep_db_num_rows($zones_query) > 0) { 

			while ($zones_values = tep_db_fetch_array($zones_query)) {
				$zones_array[] = array('id' => $zones_values['zone_name'], 'text' => $zones_values['zone_name']);
       		}

			echo tep_draw_pull_down_menu('state', $zones_array, 'style="width:150px" maxlength=32');

		} else {
			echo tep_draw_input_field('state', '', 'style="width:150px" maxlength="32"');
		}

	} else {
    	echo tep_draw_input_field('state', '', 'style="width:150px" maxlength=32');
	}

    if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT;
?>
                </td>
                <td><?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('postcode', '', 'style="width:100px" maxlength=10') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main" colspan=3><?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('country', '223') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
     
<?php
  if (isset($affiliate_ref) && $affiliate_ref) {
   echo tep_draw_hidden_field('source',9000);
  } else if ((tep_not_null(tep_get_sources()) || DISPLAY_REFERRAL_OTHER == 'true') && (!tep_session_is_registered('referral_id') || (tep_session_is_registered('referral_id') && DISPLAY_REFERRAL_SOURCE == 'true')) ) {
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><?php echo CATEGORY_SOURCE; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><?php echo ENTRY_SOURCE; ?></td>
                <td class="main"><?php echo tep_get_source_list('source', (DISPLAY_REFERRAL_OTHER == 'true' || (tep_session_is_registered('referral_id') && tep_not_null($referral_id)) ? true : false), (tep_session_is_registered('referral_id') && tep_not_null($referral_id)) ? '9999' : '') . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_TEXT . '</span>': ''); ?></td>
              </tr>
<?php
    if (DISPLAY_REFERRAL_OTHER == 'true' || (tep_session_is_registered('referral_id') && tep_not_null($referral_id))) {
?>
              <tr>
                <td class="main"><?php echo ENTRY_SOURCE_OTHER; ?></td>
                <td class="main"><?php echo tep_draw_input_field('source_other', (tep_not_null($referral_id) ? $referral_id : '')) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_OTHER_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_OTHER_TEXT . '</span>': ''); ?></td>
              </tr>
<?php
    }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
	} else if(DISPLAY_REFERRAL_SOURCE == 'false') {
	
	echo tep_draw_hidden_field('source', (tep_session_is_registered('referral_id') && !empty($referral_id) ? '9999' : ''));
	echo tep_draw_hidden_field('source_other', (tep_not_null($referral_id) ? $referral_id : ''));

  }
?>
<!-- //rmh referral end -->
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" style="background-color:#EFEFEF;padding:5px"><b>&#149; <?php echo CATEGORY_PASSWORD; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main" style="width:240px"><?php echo ENTRY_PASSWORD . '<br>' . tep_draw_password_field('password', '', 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>': ''); ?></td>
                <td class="main"><?php echo ENTRY_PASSWORD_CONFIRMATION . '<br>' . tep_draw_password_field('confirmation', '', 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" style="background-color:#EFEFEF;padding:5px"><b>&#149; <?php echo CATEGORY_OPTIONS; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><?php echo ENTRY_NEWSLETTER; ?></td>
                <td class="create_account"><?php echo tep_draw_checkbox_field('newsletter', '1') . '&nbsp;' . (tep_not_null(ENTRY_NEWSLETTER_TEXT) ? '<span class="inputRequirement">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>



      <tr>
        <td align="right" style="padding-right:15px; padding-bottom:10px;">
<?php 
		echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); 
		echo tep_draw_hidden_field('apply', $apply);

?></td>
      </tr>
    </table>
</form></td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php include(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php include(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
