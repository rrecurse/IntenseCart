<?php
 
	require_once('includes/application_top.php');

	//@include_once(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/create_account.php');  
	if(file_exists(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/create_account.php')) @include_once(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/create_account.php');

	include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);  

	$parentpage = $_SERVER["SCRIPT_NAME"];

	// # remove the Coupon code - resets page! need to fix.
	if(isset($_GET['removecode']) && $_GET['removecode'] == 1) {
		tep_session_unregister('cc_id');
		$cc_id = '';
		header("Location: https://" . $_SERVER['HTTP_HOST'] . "/checkout.php");
	}

	$show_cart = 0;

	$show_find_members = 0;

	if (isset($_GET['display_mode'])) {
		$display_mode = $_GET['display_mode'];
	} else { 
		$display_mode = 'user';
	}

	if ($display_mode == 'admin') {

		$return_link = 'admin/orders.php?oID=%d&action=edit';
		$show_cart = $show_find_members = 1;

		$find_members_box_url = HTTPS_SERVER.'/admin/find_members_box.php';

	} else if($display_mode == 'affiliate') {

		$return_link = 'affiliate_summary.php';
		$show_cart = $show_find_members=1;
		$find_members_box_url = HTTPS_SERVER.'/affiliate_find_members_box.php';

	} else {

		$return_link = FILENAME_CHECKOUT_SUCCESS;
	}

	if(isset($_GET['return_link'])) $return_link = $_GET['return_link'];
	if(isset($_GET['show_cart']) && $_GET['show_cart']) $show_cart = 1;

	if(!$show_cart && $cart->count_contents() < 1) {
		tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
	}
  
	if (tep_paypal_wpp_enabled()) {
		$ec_enabled = true;
	} else {
		$ec_enabled = false;
	}

	$payset = tep_module('checkout');
  
	require_once(DIR_WS_CLASSES . 'order.php');
	//$order = new order;

	require_once(DIR_WS_CLASSES . 'order_total.php');// CCGV
	$order_total_modules = new order_total;// CCGV
  
	$total_weight = $cart->show_weight();
	$multi_weight = $cart->show_multi_weight_line();

	$total_count = $cart->count_contents();
	$free_shipping = $cart->free_shipping;

	if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
  
	// # if no shipping destination address was selected, use the customers own address as default
	if (!tep_session_is_registered('sendto')) {

		tep_session_register('sendto');
		$sendto = $customer_default_address_id;

	} else {

		// # verify the selected shipping address
		$check_address_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int)$customer_id . "' AND address_book_id = '" . (int)$sendto . "'");
		$check_address = (tep_db_num_rows($check_address_query) > 0 ? tep_db_result($check_address_query,0) : 0);
    
		if ($check_address < 1) {
			$sendto = $customer_default_address_id;
			if (tep_session_is_registered('shipping')) tep_session_unregister('shipping');
		}
	}

	// # load all enabled payment modules
	//require_once(DIR_WS_CLASSES . 'payment.php');

	if(!tep_session_is_registered('cartID')) tep_session_register('cartID');

	$cartID = $cart->cartID;
  
	if (!tep_session_is_registered('customer_id')) {

		if (!tep_session_is_registered('billto')) {
			tep_session_register('billto');
			$billto = $customer_default_address_id;
		} else {
	      // # verify the selected billing address
    	  $check_address_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int)$customer_id . "' AND address_book_id = '" . (int)$billto . "'");
	      $check_address = (tep_db_num_rows($check_address_query) > 0 ? tep_db_result($check_address_query,0) : 0);
    
    		if ($check_address < 1) {
				$billto = $customer_default_address_id;
			}
		}
	}

  
	require_once(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT);
	$error = false;

	// # Login block
	if (isset($_GET['action']) && ($_GET['action'] == 'login') && isset($_POST['email_address'])) {

		$email_address = tep_db_prepare_input($_POST['email_address']);

		if (isset($_POST['password'])) {
		
			$password = tep_db_prepare_input($_POST['password']);
			checkout_login($email_address, $password);

		} else {

			$password='';
			$password_crypt = tep_db_prepare_input($_POST['password_crypt']);
			checkout_login($email_address, $password, $password_crypt);
		}

		if ($error == true) {
			$messageStack->add('checkout', TEXT_LOGIN_ERROR);
		}
	}

	// # Login block
	function checkout_login($email_address, $password, $pass_crypt=NULL) {
	
		// # group global vars by type.
		
		// # objects
		global $error, $cart, $wishList;

		// # integars
		global $customer_id, $customer_country_id, $customer_zone_id, $customer_default_address_id;

		// # strings
		global $customer_first_name;
		global $customer_password;


		$email_address = tep_db_prepare_input($email_address);
		$password = tep_db_prepare_input($password);

		// # Check if email exists

		$check_customer_query = tep_db_query("SELECT customers_id, customers_firstname, customers_password, customers_email_address, customers_default_address_id 
											  FROM " . TABLE_CUSTOMERS . " 
											  WHERE customers_email_address = '" . tep_db_input($email_address) . "'
											");

		if (!tep_db_num_rows($check_customer_query)) {

			$error = true;

		} else {

			$check_customer = tep_db_fetch_array($check_customer_query);

			// # Check that password is good
			if (isset($pass_crypt) ? ($pass_crypt!=$check_customer['customers_password']) : !tep_validate_password($password, $check_customer['customers_password'])) {
				$error = true;
			} else {

				if (SESSION_RECREATE == 'True') tep_session_recreate();

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
				tep_session_register('customer_id');
				tep_session_register('customer_default_address_id');
				tep_session_register('customer_first_name');
				tep_session_register('customer_password');
				tep_session_register('customer_country_id');
				tep_session_register('customer_zone_id');
				tep_session_unregister('referral_id'); //rmh referral

				tep_db_query("UPDATE " . TABLE_CUSTOMERS_INFO . " 
							  SET customers_info_date_of_last_logon = NOW(), 
							  customers_info_number_of_logons = (customers_info_number_of_logons+1)
							  WHERE customers_info_id = '" . (int)$customer_id . "'
							");

				$cart->restore_contents();
				$wishList->restore_wishlist();
			}
		}

	}
  
function get_zone($country, $state) {

	    if (!is_numeric($country) || $country < 1 || strlen(trim($state)) < 2) return '0';

    	$zone_check_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$country . "'");
	    $zone_check = (tep_db_num_rows($zone_check_query) > 0 ? tep_db_result($zone_check_query, 0) : 0);

    	$entry_state_has_zones = ($zone_check > 0 ? true : false);

	    if ($entry_state_has_zones == true) {

    		$zone_query = tep_db_query("SELECT DISTINCT zone_id 
										FROM " . TABLE_ZONES . " 
										WHERE zone_country_id = '" . (int)$country . "' 
										AND (zone_name LIKE '" . tep_db_input($state) . "%' OR zone_code LIKE '%" . tep_db_input($state) . "%')
										");

			$zone_id = (tep_db_num_rows($zone_query) > 0 ? tep_db_result($zone_query, 0) : 0);

			return $zone_id;
		}
}


  if ($error == true) {
    $messageStack->add('checkout', TEXT_LOGIN_ERROR);
  }
  
  
  
  // # Checkout block
  $process = false;
  if (isset($_POST['action']) && ($_POST['action'] == 'checkout')) {


    $process = true;
	//$paymods = $payset->getModulesCustomer($customer_id);
    $payModule = tep_module($_POST['pay_method'],'payment');

    $firstname = tep_db_prepare_input($_POST['firstname']);
    $lastname = tep_db_prepare_input($_POST['lastname']);
    $ship_street_address = tep_db_prepare_input($_POST['ship_street_address']);
    $ship_suburb = tep_db_prepare_input($_POST['ship_suburb']);
    $ship_postcode = tep_db_prepare_input($_POST['ship_postcode']);
    $ship_city = tep_db_prepare_input($_POST['ship_city']);
    $ship_country = tep_db_prepare_input($_POST['ship_country']);
    $ship_zone_id = tep_db_prepare_input($_POST['ship_state']);
    $ship_state = tep_get_zone_name($ship_country, $ship_zone_id, '');

    if (!$ship_state) $ship_state = $ship_zone_id;
    if (!isset($cc_expires) && isset($cc_expires_month) && isset($cc_expires_year)) $cc_expires="$cc_expires_month$cc_expires_year";
    
    if ($_POST['bill_same'] != '1') {
      $bill_street_address = tep_db_prepare_input($_POST['bill_street_address']);
      $bill_suburb = tep_db_prepare_input($_POST['bill_suburb']);
      $bill_postcode = tep_db_prepare_input($_POST['bill_postcode']);
      $bill_city = tep_db_prepare_input($_POST['bill_city']);
      $bill_country = tep_db_prepare_input($_POST['bill_country']);
      $bill_zone_id = tep_db_prepare_input($_POST['bill_state']);
      $bill_state = tep_get_zone_name($bill_country, $bill_zone_id, '');
    } else {
      $bill_street_address = $ship_street_address;
      $bill_suburb = $ship_suburb;
      $bill_postcode = $ship_postcode;
      $bill_city = $ship_city;
      $bill_state = $ship_state;
      $bill_country = $ship_country;
      $bill_zone_id = $ship_zone_id;
    }

    $telephone = tep_db_prepare_input($_POST['telephone']);
    $company = tep_db_prepare_input($_POST['company']);

    if(isset($_POST['newsletter'])) {
      $newsletter = tep_db_prepare_input($_POST['newsletter']);
    } else {
      $newsletter = false;
    }

    $password = tep_db_prepare_input($_POST['password']);
    $confirmation = tep_db_prepare_input($_POST['confirmation']);

    $source = tep_db_prepare_input($_POST['source']);
    if (isset($_POST['source_other'])) $source_other = tep_db_prepare_input($_POST['source_other']);

	// # added orders_source logic to detect and save order source 
	$orders_source = (!empty($_SESSION['orders_source'])) ? $_SESSION['orders_source'] : '';
	

    $error = false;

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
      $error = true;
      $messageStack->add('checkout', ENTRY_FIRST_NAME_ERROR);
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
      $error = true;
      $messageStack->add('checkout', ENTRY_LAST_NAME_ERROR);
    }
    
    if (!tep_session_is_registered('customer_id')) {
      $password_error = false;
      if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
        $error = true;
        $password_error = true;
        $messageStack->add('checkout', ENTRY_PASSWORD_ERROR);
      } elseif ($password != $confirmation) {
        $error = true;
        $password_error = true;
        $messageStack->add('checkout', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
      }
    }

   if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('checkout', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (tep_validate_email($email_address) == false) {
      $error = true;

      $messageStack->add('checkout', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {

      if (!tep_session_is_registered('customer_id')) {

        $check_email_query = tep_db_query("select customers_password, customers_id as id, customers_paypal_ec as ec from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");

        if (tep_db_num_rows($check_email_query) > 0) {

          $check_email = tep_db_fetch_array($check_email_query);

          // # They're a customer, so log them in if their password is correct
          if ($password_error || !tep_validate_password($password, $check_email['customers_password'])) {
            $error = true;
            $messageStack->add('checkout', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
          } else {
            checkout_login($email_address, $password);
          }
        }
      }
    }

    if (strlen($ship_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('checkout', ENTRY_STREET_ADDRESS_ERROR);
    }

    if (strlen($ship_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
      $error = true;

      $messageStack->add('checkout', ENTRY_POST_CODE_ERROR);
    }

    if (strlen($ship_city) < ENTRY_CITY_MIN_LENGTH) {
      $error = true;

      $messageStack->add('checkout', ENTRY_CITY_ERROR);
    }
    
    if (!is_numeric($ship_country)) {
      $error = true;
      $messageStack->add('checkout', ENTRY_COUNTRY_ERROR);
    }
    
    if ($_POST['bill_same'] != '1') {
      if (strlen($bill_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout', ENTRY_STREET_ADDRESS_ERROR);
      }

      if (strlen($bill_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout', ENTRY_POST_CODE_ERROR);
      }

      if (strlen($bill_city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout', ENTRY_CITY_ERROR);
      }
      if (!is_numeric($bill_country)) {
        $error = true;
        $messageStack->add('checkout', ENTRY_COUNTRY_ERROR);
      }
    }
    
    if (!tep_session_is_registered('comments')) tep_session_register('comments');
    if (tep_not_null($_POST['comments'])) {
      $comments = tep_db_prepare_input($_POST['comments']);
    }
   

    if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
      $error = true;

      $messageStack->add('checkout', ENTRY_TELEPHONE_NUMBER_ERROR);
    }
//rmh referral start
    if ((REFERRAL_REQUIRED == 'true') && (is_numeric($source) == false)) {
        $error = true;

        $messageStack->add('checkout', ENTRY_SOURCE_ERROR);
    }

    if ((REFERRAL_REQUIRED == 'true') && (DISPLAY_REFERRAL_OTHER == 'true') && ($source == '9999') && (!tep_not_null($source_other)) ) {
        $error = true;

        $messageStack->add('create_account', ENTRY_SOURCE_OTHER_ERROR);
    }
	// # END referral

	// if ($payment == '') $payment = 'paypal_wpp';
      if ($credit_covers) $payment=''; // CCGV 
	// $payment_modules = new payment($payment);
    
    if (!$error) {
      if (!tep_session_is_registered('customer_id')) {
        $sql_data_array = array('customers_firstname' => $firstname,
                                'customers_lastname' => $lastname,
                                'customers_email_address' => $email_address,
                                'customers_telephone' => $telephone,
                                'customers_fax' => $fax,
				'customers_newsletter' => $newsletter,
        			'customers_password' => tep_encrypt_password($password),
                                );
        if (isset($affiliate_ref)) $sql_data_array['customers_referred_by']=$affiliate_ref;

        tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

        $customer_id = tep_db_insert_id();

        $sql_data_array = array('customers_id' => $customer_id,
                                'entry_firstname' => $firstname,
                                'entry_lastname' => $lastname,
                                'entry_company' => $company,
                                'entry_street_address' => $ship_street_address,
                                'entry_postcode' => $ship_postcode,
                                'entry_city' => $ship_city,
                                'entry_country_id' => $ship_country);

        $sql_data_array['entry_suburb'] = $ship_suburb;

        if (ACCOUNT_STATE == 'true') {

          if ($ship_zone_id > 0) {

            $sql_data_array['entry_zone_id'] = $ship_zone_id;
            $sql_data_array['entry_state'] = $ship_state;

          } else {

            $sql_data_array['entry_zone_id'] = '0';
            $sql_data_array['entry_state'] = $ship_state;

          }
        }

if ($_POST['newsletter'] == '1') {

$subscribers_info = tep_db_query("select subscribers_id from " . TABLE_SUBSCRIBERS . " where subscribers_email_address = '" . $_POST['email_address'] . "' ");
$date_now = date('Ymd');

if (!tep_db_num_rows($subscribers_info)) {
$gender = '' ;
tep_db_query("insert into " . TABLE_SUBSCRIBERS . " (subscribers_email_address, subscribers_firstname, subscribers_lastname, language, date_account_created, customers_newsletter, subscribers_blacklist, status_sent1, source_import) values ('" . strtolower($_POST['email_address']) . "', '" . ucwords(strtolower($_POST['firstname'])) . "', '" . ucwords(strtolower($_POST['lastname'])) . "', 'English', now() , '1', '0', '1', 'subscribe_newsletter')");
} else {
tep_db_query("update " . TABLE_SUBSCRIBERS . " set customers_newsletter = '1' where subscribers_email_address = '" . $_POST['email_address'] . "' ");
}
	
}

        tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

        $address_id = tep_db_insert_id();
	$sendto=$address_id;

        tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");

        tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int)$customer_id . "', '0', now())");

        //Billing address
        if ($_POST['bill_same'] != '1') {
          $sql_data_array = array('customers_id' => $customer_id,
                                  'entry_firstname' => $firstname,
                                  'entry_lastname' => $lastname,
                                  'entry_company' => $company,
                                  'entry_street_address' => $bill_street_address,
                                  'entry_postcode' => $bill_postcode,
                                  'entry_city' => $bill_city,
                                  'entry_country_id' => $bill_country);

          $sql_data_array['entry_suburb'] = $bill_suburb;

          if ($bill_zone_id > 0) {
            $sql_data_array['entry_zone_id'] = $bill_zone_id;
            $sql_data_array['entry_state'] = '';
          } else {
            $sql_data_array['entry_zone_id'] = '0';
            $sql_data_array['entry_state'] = $bill_state;
          }
          tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
          $billto = tep_db_insert_id();
        } else $billto=$sendto;
//rmh referral start

      tep_db_query("update " . TABLE_CUSTOMERS_INFO . " SET customers_info_source_id = '" . (int)$source . "' WHERE customers_info_id = '" . (int)$customer_id . "'");

      if ($source == '9999') {
        tep_db_perform(TABLE_SOURCES_OTHER, array('customers_id' => (int)$customer_id, 'sources_other_name' => tep_db_input($source_other)));
      }
//rmh referral end
        
        if (SESSION_RECREATE == 'True') {
          tep_session_recreate();
        }

        if (!tep_session_is_registered('customer_first_name')) {
          $customer_first_name = $firstname;
          $customer_default_address_id = $address_id;
          $customer_country_id = $ship_country;
          $customer_zone_id = $ship_zone_id;
          tep_session_register('customer_id');
          tep_session_register('customer_first_name');
          tep_session_register('customer_default_address_id');
          tep_session_register('customer_country_id');
          tep_session_register('customer_zone_id');
	 	  tep_session_unregister('referral_id'); //rmh referral
        }

        $cart->restore_contents();
        $wishList->restore_wishlist();

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

        $email_text .= EMAIL_WELCOME . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
        tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      } else {
        $customer_first_name = $firstname;
        $customer_default_address_id = $address_id;
        $customer_country_id = $ship_country;
        $customer_zone_id = $ship_zone_id;
        if (!tep_session_is_registered('customer_first_name')) {
          tep_session_register('customer_id');
          tep_session_register('customer_first_name');
          tep_session_register('customer_default_address_id');
          tep_session_register('customer_country_id');
          tep_session_register('customer_zone_id');
        }
      }
      

		if((isset($_POST['shipping'])) && (strpos($_POST['shipping'], '_'))) {
			
			list($module, $method) = explode('_', $_POST['shipping']);
			require_once(DIR_WS_CLASSES . 'shipping.php');
			$shipping_modules = new shipping($_POST['shipping']);

			$shipping = array(id => $module.'_'.$method,
							  title => $shipping_options[$module][$method]['name'],
							  cost => $shipping_options[$module][$method]['cost']);
		}

		$order = new order($cart);

		// # Payment info
		if (!tep_session_is_registered('storecard')) tep_session_register('storecard');
		$_SESSION['storecard'] = $_POST['storecard'];
		if (!tep_session_is_registered('payment')) tep_session_register('payment');
		if (isset($_POST['payment'])) $payment = $_POST['payment'];
		include(DIR_WS_FUNCTIONS . 'encryption.php');

		$bill_country_query = tep_db_query("SELECT countries_name FROM " . TABLE_COUNTRIES . " WHERE countries_id = '" . (int)$bill_country . "'");
		$bill_country = (tep_db_num_rows($bill_country_query) > 0 ? tep_db_result($bill_country_query,0) : '');
			
		for ($x = 0; $x < 2; $x++) {

			if ($x == 0) {
				$o =& $order->customer;
			} elseif ($x == 1) {
				$o =& $order->billing;
			}

        // # Fill out the order object
        $o['firstname'] = $firstname;
        $o['lastname'] = $lastname;
        $o['street_address'] = $bill_street_address;
        $o['suburb'] = $bill_suburb;
        $o['city'] = $bill_city;
        $o['postcode'] = $bill_postcode;
		$o['zone_id'] = (int)$bill_zone_id;
		$o['state'] = $bill_state;
        $o['country'] = $bill_country;
        $o['country_id'] = (int)$bill_country;
        $o['format_id'] = tep_get_address_format_id($bill_country);
        $o['telephone'] = $telephone;
        $o['email_address'] = $email_address;
		}

		if ($order->content_type == 'virtual') {
        if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
		// $shipping = false;
		$sendto = false;
		}
      
      
		$ship_country_query = tep_db_query("SELECT countries_name FROM " . TABLE_COUNTRIES . " WHERE countries_id = '" . (int)$ship_country . "'");
		$ship_country = (tep_db_num_rows($ship_country_query) > 0 ? tep_db_result($ship_country_query,0) : $bill_country);


      $order->delivery = array('firstname' => $firstname,
                              'lastname' => $lastname,
                              'company' => $ship_company,
                              'street_address' => $ship_street_address,
                              'suburb' => $ship_suburb,
                              'city' => $ship_city,
                              'postcode' => $ship_postcode,
                              'state' => $ship_state,
                              'zone_id' => $ship_zone_id,
                              'country' => $ship_country,
                              'country_id' => $ship_country,
                              'format_id' => tep_get_address_format_id($ship_country));  


      $order_totals = $order_total_modules->process();
      
      $order->info['shipping_method']=isset($_POST['shipping'])?$_POST['shipping']:'';


	if (STOCK_ALLOW_CHECKOUT=='false' && !$cart->checkStock(1000)) {
		if ($_GET['ajax']) {
?>
<table width="100%" border="0">
<tr><td>Stock Level Error:</td></tr>
<tr><td style="color:red;font-weight:700;text-align:center;">Some of the products have been purchased while you were shopping. Please review your shopping cart</td></tr>
<tr><td style="text-align:center;"><button type="button" onClick="document.location='/shopping_cart.php'; return false;">Review Shopping Cart</button></td></tr>
</table>
<?php
	} else tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,'','SSL'));
	exit;
      }
  
  
      if ($order->info['total']>0.005) if (!$payModule->capturePayment($order->info['total'], $order)) {
		$payment_error=$payModule->getError();
		if (!tep_session_is_registered('payment_error')) tep_session_register('payment_error');

		if ($_GET['ajax']) {
?>
<table width="100%" border="0">
<tr><td>Payment Gateway Response:</td></tr>
<tr><td style="color:red;font-weight:700;text-align:center;"><?php echo htmlspecialchars($payment_error)?></td></tr>
<tr><td style="text-align:center;"><button type="button" onClick="reviewOrder(); return false;">Review Order</button></td></tr>
</table>
<?php
	} else tep_redirect(tep_href_link(FILENAME_CHECKOUT,'','SSL'));
	exit;
      }
      
      $order->info['orders_status'] = ($payModule->getSettleAmount() ? 2 : 1);

	  //$thedate = date('Y-m-d H:i:s',time()+STORE_TZ*3600);


	// # detect the orders source

	// # set it to null to avoid unset
	$orders_source = '';
	
	// # check the orders class info array for the presence of orders source
	if(isset($order->info['orders_source'])) {
		$orders_source = $order->info['orders_source'];

	// # if not in info array, check for the SESSION orders_source
	} elseif(!empty($_SESSION['orders_source'])) {
	 	$orders_source = $_SESSION['orders_source'];

	// # and finally, if neither of the two are set, and the referrer is present
	} elseif(isset($_SERVER['HTTP_REFERER']) && (!isset($order->info['orders_source']) || empty($_SESSION['orders_source'])) ) { 
		$parse = parse_url($_SERVER['HTTP_REFERER']);
		$orders_source = $parse['host'];

		// # if the referrer present is the site domain, flag as retail sale
		if(strtolower(SITE_DOMAIN) == strtolower($parse['host'])) {
			$orders_source = 'retail';
		} 
	// # if vendor session is present, flag as vendor sale
	} elseif(!empty($_SESSION['sppc_customer_group_id']) && $_SESSION['sppc_customer_group_id'] > 1) {
		 $orders_source = 'vendor';
	}

      $sql_data_array = array('customers_id' => $customer_id,
                              'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                              'customers_company' => $company,
                              'customers_street_address' => $order->customer['street_address'],
                              'customers_suburb' => $order->customer['suburb'],
                              'customers_city' => $order->customer['city'],
                              'customers_postcode' => $order->customer['postcode'], 
                              'customers_state' => $order->customer['state'], 
                              'customers_country' => $order->customer['country'], 
                              'customers_telephone' => $order->customer['telephone'], 
                              'customers_fax' => $order->customer['fax'], 
                              'customers_email_address' => $order->customer['email_address'],
                              'customers_address_format_id' => $order->customer['format_id'], 
                              'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'], 
                              'delivery_company' => $order->delivery['company'],
                              'delivery_street_address' => $order->delivery['street_address'], 
                              'delivery_suburb' => $order->delivery['suburb'], 
                              'delivery_city' => $order->delivery['city'], 
                              'delivery_postcode' => $order->delivery['postcode'], 
                              'delivery_state' => $order->delivery['state'], 
                              'delivery_country' => $order->delivery['country'], 
                              'delivery_address_format_id' => $order->delivery['format_id'], 
                              'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
                              'billing_company' => $order->billing['company'],
                              'billing_street_address' => $order->billing['street_address'], 
                              'billing_suburb' => $order->billing['suburb'], 
                              'billing_city' => $order->billing['city'], 
                              'billing_postcode' => $order->billing['postcode'], 
                              'billing_state' => $order->billing['state'], 
                              'billing_country' => $order->billing['country'], 
                              'billing_address_format_id' => $order->billing['format_id'], 
                              'shipping_method' => $order->info['shipping_method'], 
                              'payment_method' => $order->info['payment_method'], 
                              'cc_type' => $order->info['cc_type'], 
                              'cc_owner' => $order->info['cc_owner'], 
                              'cc_number' => $order->info['cc_number'], 
                              'cc_expires' => $order->info['cc_expires'],
                              'date_purchased' => 'now()', 
                              'orders_status' => $order->info['orders_status'], 
                              'currency' => $order->info['currency'], 
                              'currency_value' => $order->info['currency_value'],
                              'comments' => $order->info['comments'],
							  'orders_source' => $orders_source
							  );


      if (isset($_POST['local_time']) && preg_match('/(\d?\d:\d\d:\d\d)(.*)/',$_POST['local_time'],$local_time_parsed)) {
        $sql_data_array['local_time_purchased']=$local_time_parsed[1];
	if (preg_match('/\((.*?)\)/',$local_time_parsed[2],$tz_parsed)) $sql_data_array['local_timezone']=$tz_parsed[1];
	else if (preg_match('/[\+\-]\d\d/',$local_time_parsed[2],$tz_parsed)) $sql_data_array['local_timezone']=$tz_parsed[0];
	else if (preg_match('/\b([A-Z]+)\b/',$local_time_parsed[2],$tz_parsed)) $sql_data_array['local_timezone']=$tz_parsed[1];
	else {
	  $local_split = explode(':',$local_time_parsed[1]);
	  $sql_data_array['local_timezone']=sprintf('%+03d',(($local_split[0]-date('H'))*60+($local_split[1]-date('i')))/60);
	}
      }

	// # INSERT INTO ORDERS TABLE

      tep_db_perform(TABLE_ORDERS, $sql_data_array);
      $insert_id = $order->orderid = tep_db_insert_id();
      
      if (!$ec_checkout && $storecard == 'yes') {
        $cc_query = tep_db_query("SELECT customers_personal FROM customers_personal WHERE customers_id = '".(int)$customer_id."' LIMIT 1");
        $cc_combined = tep_cc_encrypt($order->info['cc_type'] . '|' . $order->info['cc_number'] . '|' . $order->info['cc_owner'] . '|' . substr($order->info['cc_expires'], 0, 2) . '/' . substr($order->info['cc_expires'], -2) . '|' . $order->info['cc_checkcode']);
        
        if (tep_db_num_rows($cc_query) > 0) {
          $cc_res = tep_db_fetch_array($cc_query);
          //If they credit card used is not the same as what's in the database, update the database
          if (strlen($order->info['cc_number']) > 10 && $cc_combined != $cc_res['customers_personal']) {
            tep_db_query("UPDATE customers_personal SET customers_personal = '".$cc_combined."' WHERE customers_id = '".(int)$customer_id."'");
          }
        } else {
          tep_db_query("INSERT INTO customers_personal VALUES ('" . (int)$customer_id."', '".$cc_combined."'");
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
                              'orders_status_id' => $order->info['orders_status'], 
                              'date_added' => 'now()', 
                              'customer_notified' => $customer_notification,
                              'comments' => '');

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

		// # initialized for the email confirmation
		$products_ordered = '';
		$products_ordered_html = '&nbsp;';
		$subtotal = 0;
		$total_tax = 0;

		for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {

			$products_id = tep_get_prid($order->products[$i]['id']);
			$products_id = (empty($products_id)) ? '0' : $products_id;

			// # Disabled the download options for software purchases
/*
// # Stock Update - Joao Correia
        if (STOCK_LIMITED == 'true') {
          if (DOWNLOAD_ENABLED == 'true') {
            $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename 
                                FROM " . TABLE_PRODUCTS . " p
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                 ON p.products_id=pa.products_id
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                 ON pa.products_attributes_id=pad.products_attributes_id
                                WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
            $products_attributes = $order->products[$i]['attributes'];
            if (is_array($products_attributes)) {
              $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
            }
            $stock_query = tep_db_query($stock_query_raw);
          } else {
            $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
          }
          if (tep_db_num_rows($stock_query) > 0) {
            $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
            if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
              $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
              tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
              if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
                tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
              }
// By MegaJim
	      if ($order->products[$i]['attributes']) foreach ($order->products[$i]['attributes'] AS $attr_data) {
	        if ($attr_data['products_id']) {
                  tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity - '" . $order->products[$i]['qty'] . "' where products_id = '" . $attr_data['products_id'] . "'");
		  if (STOCK_ALLOW_CHECKOUT == 'false') {
                    $attr_stk_query=tep_db_query("select products_id from " . TABLE_PRODUCTS . " where products_id = '" . $attr_data['products_id'] . "' AND products_quantity>0");
                    if (tep_db_num_rows($attr_stk_query)<1) {
                      tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . $attr_data['products_id'] . "'");
                    }
		  }
		}
	      }
            } else {
              $stock_left = $stock_values['products_quantity'];
            }
          }
        }
*/
		// # END Disable of download options for software purchases

	
		// # Update products_ordered 

		tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_ordered` = (products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . "), `last_stock_change` = NOW() WHERE products_id = '".$products_id."'");

		
		// # Retrieve current-day product costing from the products table and add to orders products.
		// # important to keep historical pricing / costs for inventory since this can fluctuate with time.

		// # if no cost found in suppliers_products_groups, try the products table for old format

		// # costing from suppliers_products_groups table
		$cost_price_query = tep_db_query("SELECT suppliers_group_price FROM suppliers_products_groups WHERE products_id = '". $products_id ."' AND priority = '0' LIMIT 1");
		$cost_price = (tep_db_num_rows($cost_price_query) > 0 ? tep_db_result($cost_price_query,0) : 0);

		// # costing from products table
		$cost_old_query = tep_db_query("SELECT products_price_myself FROM ". TABLE_PRODUCTS ." WHERE products_id = '". $products_id ."'");	
		$cost_old = (tep_db_num_rows($cost_old_query) > 0 ? tep_db_result($cost_old_query,0) : 0);

		// # if supplier cost is empty, use old format
		$cost = (!empty($cost_price) ? $cost_price : $cost_old);
		
		// # multi-warehousing - update tables for multi-warehousing.
		if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

			$warehouse_id_query = tep_db_query("SELECT pwi.products_warehouse_id 
												FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." pwi 
												LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE ." pw ON pw.products_warehouse_id = pwi.products_warehouse_id
												WHERE pwi.products_id = '". $products_id ."'
												AND pw.products_warehouse_default = 1
											   ");

			$warehouse_id = (tep_db_num_rows($warehouse_id_query) > 0 ? tep_db_result($warehouse_id_query,0) : 1);


			tep_db_query("UPDATE ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." 
						  SET products_quantity = (products_quantity - ". $order->products[$i]['qty'].") 
						  WHERE products_warehouse_id = '". $warehouse_id ."' 
						  AND products_id = '". $products_id ."'
						");
			
		} else { 

			$warehouse_id = 1;	
		}

if($warehouse_id < 1) error_log('warehouse_id is 0 (zero) on checkout', 1, 'chrisd@zwaveproducts.com');

        $sql_data_array = array('orders_id' => $insert_id, 
                                'products_id' => $products_id, 
                                'products_model' => $order->products[$i]['model'], 
                                'products_name' => $order->products[$i]['name'], 
                                'products_price' => $order->products[$i]['price'], 
                                'cost_price' => (float)$cost, 
                                'final_price' => $order->products[$i]['final_price'], 
                                'products_tax' => $order->products[$i]['tax'], 
                                'products_weight' => $order->products[$i]['weight'], 
                                'free_shipping' => $order->products[$i]['free_shipping'], 
                                'separate_shipping' => $order->products[$i]['separate_shipping'], 
                                'products_quantity' => $order->products[$i]['qty'],
								'warehouse_id' => $warehouse_id,
								);

        tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

        $order_products_id = $order->products[$i]['orders_products_id'] = tep_db_insert_id();

		$order_total_modules->update_credit_account($i); // CCGV

		// # ------insert customer choosen option to order--------
        $attributes_exist = '0';
        $products_ordered_attributes = '';

        if (isset($order->products[$i]['attributes'])) {

          $attributes_exist = '1';

          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {



            $sql_data_array = array('orders_id' => $insert_id, 
                                    'orders_products_id' => $order_products_id, 
                                    'products_options' => $order->products[$i]['attributes'][$j]['option'],
                                    'products_options_values' => $order->products[$i]['attributes'][$j]['value'],
        		      );
            tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
	    


            if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
              $sql_data_array = array('orders_id' => $insert_id, 
                                      'orders_products_id' => $order_products_id, 
                                      'orders_products_filename' => $attributes_values['products_attributes_filename'], 
                                      'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
                                      'download_count' => $attributes_values['products_attributes_maxcount']);
              tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
            }
            $products_ordered_attributes .= "\n\t" . $order->products[$i]['attributes'][$j]['option'] . ' ' . $order->products[$i]['attributes'][$j]['value'];
          }
        }
		
		// # ------insert customer choosen option eof ----
        $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
        $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
        $total_cost += $total_products_price;

        $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
        $products_ordered_html .= '<table width="100%"><tr><td align="center" style="width:40px; font:normal 12px arial; text-align:center;">x'.$order->products[$i]['qty'].'</td><td align="left" style="width:235px; padding-left:10px; font:normal 12px arial;">'.$order->products[$i]['name'].'</td><td style="width:68px; font:normal 12px arial;">'.$order->products[$i]['model'].'</td><td align="left" style="width:63px; font:normal 12px arial;">'.$products_ordered_attributes.'</td><td align="left" style="width:69px; font:normal 12px arial;">' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) .'</td></tr></table><br>'."\n";
      }
      $products_ordered_html .= '<br>';
      $order_total_modules->apply_credit();// CCGV
      $order->approvePurchase();
      $pinfo=$order->getPurchaseInfo();
      if ($pinfo) {
        $products_ordered.="\n-----------------\n".join("\n",$pinfo);
		$products_ordered_html .= join("<br>",$pinfo);
      }
// # lets start with the email confirmation


// # Shipping Info - copied from checkout_process.php - by MegaJim

$ship = explode('_', $shipping['id']);
$ship[0] = trim($ship[0]);
$ship[1] = trim($ship[1]);
//if ($ship[0]=='free') $ship=explode('_', FREE_SHIPPING_METHOD);
if ($ship[0]=='free') $ship = array('usps','Parcel Post');

//Shipping Information Arrays
$ship_info = array();
$ship_info['usps'] = array('name' => 'USPS', 'track_url' => 'http://www.usps.com/shipping/trackandconfirm.htm', 'track_name' => 'Delivery Confirmation Number');
$ship_info['ups'] = array('name' => 'UPS', 'track_url' => 'http://www.ups.com/WebTracking/track?loc=en_US', 'track_name' => 'Tracking Label Number');
$ship_info['fedex1'] = array('name' => 'FedEx', 'track_url' => 'http://www.fedex.com/Tracking', 'track_name' => 'Tracking Number');
$ship_info['dhlairborne'] = array('name' => 'DHL', 'track_url' => 'http://www.dhl-usa.com/TrackByNbr.asp', 'track_name' => 'Tracking Number');
$ship_info['dhl'] = $ship_info['dhlairborne'];
$shpf=strtolower(preg_replace('/\s.*/','',$ship[1]));
if (isset($ship_info[$shpf])) $ship_info['flat'] = $ship_info[$shpf];

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

$ship_info['upsxml']=$ship_info['ups'];

$service_eta = '';

if (isset($ship_info[$ship[0]])) foreach ($ship_info[$ship[0]]['timetable'] as $service => $eta) {
  if (strpos($ship[1], $service) !== false) {
    $service_eta = $eta;
  }
}

if ($service_eta == '') $service_eta = 5;

// Order Total label
$ot_title_wd=0;
$ot_text_wd=0;
$ot_label_text=Array();
$ot_label_html=Array();
foreach ($order_totals AS $idx => $ot) {
 $ot_txt=Array();
 $ot_title_wd=max($ot_title_wd,strlen($order_totals[$idx]['strip_title']=preg_replace('/<.*?>/','',$ot['title'])));
 $ot_text_wd=max($ot_text_wd,strlen($order_totals[$idx]['strip_text']=preg_replace('/<.*?>/','',$ot['text'])));
}
foreach ($order_totals AS $ot) {
 $ot_label_text[]=str_repeat(' ',$ot_title_wd+2-strlen($ot['strip_title'])).$ot['strip_title'].str_repeat(' ',$ot_text_wd+2-strlen($ot['strip_text'])).$ot['strip_text'];
$ot_label_html[]='<tr><td style="font-size: 12px; padding:0 10px 0 0;">'.$ot['title'].'</td><td style="font-size: 12px">'.$ot['text'].'</td></tr>';
}


		// # Prepare Template Vars
		if (USE_EMAIL_NOW=='Enable') {
	
		$tpl = array();

		$tpl['config'] = array(store_name => STORE_NAME,
							   store_owner_email_address => STORE_OWNER_EMAIL_ADDRESS,
							   http_server => HTTP_SERVER,
							   );

		$tpl['link'] = array(account_history => tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false),
							 account => tep_href_link('account.php'),
							 tell_a_friend => tep_href_link('tell_a_friend.php'),
							);

		$tpl['order_id'] = $insert_id;

		$tpl['info'] = $order->info;
		$tpl['customer'] = $order->customer;

		$tpl['customer']['password'] = isset($_SESSION['customer_password']) ? $_SESSION['customer_password'] : $_POST['password'];

		if(!$tpl['customer']['password']) {
			$tpl['customer']['password']='*************';
		}

		$tpl['date'] = strftime(DATE_FORMAT_LONG);

		$tpl['address'] = array(shipping => array(text => tep_address_format($order->delivery['format_id'],$order->delivery,0,'',"\n"), html => tep_address_format($order->delivery['format_id'],$order->delivery,1,'',"\n")),
								billing => array(text => tep_address_format($order->billing['format_id'],$order->billing,0,'',"\n"), html => tep_address_format($order->billing['format_id'],$order->billing,1,'',"\n")),
								);

		$tpl['products_ordered'] = array('text'=>$products_ordered,'html'=>$products_ordered_html);

		$tpl['payment'] = array(title => $payModule->getTitle(),
	    						subtotal => '$' . number_format($order->info['subtotal'], 2),
								total => '$' . number_format($order->info['total'], 2),
								order_total_label => array(text => join("\n",$ot_label_text), html => "<table width=\"100%\">\n".join("\n",$ot_label_html)."</table>"),
								cc_type => (isset($order->info['cc_type'])?$order->info['cc_type']:''),
								cc_number => (isset($order->info['cc_number'])?str_repeat('*',max(strlen($order->info['cc_number'])-4,0)).substr($order->info['cc_number'],-4,4):''),
							   );

		$tpl['ship_info'] = isset($ship_info[$ship[0]])?$ship_info[$ship[0]]:$ship_info['ups'];

		$tpl['ship_info']['service_eta'] = $service_eta;

		$tpl['ship_info']['method'] = $ship[1];

		$tpl['comments']=$order->info['comments'];

	
		require_once(DIR_WS_FUNCTIONS . 'email_now.php');
		email_now('checkout_confirm',$tpl,(SEND_EXTRA_ORDER_EMAILS_TO!=''?explode(',',SEND_EXTRA_ORDER_EMAILS_TO):NULL));

	} else {

      for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
        $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
      }

      if ($order->content_type != 'virtual') {
        $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" . 
                        EMAIL_SEPARATOR . "\n" .
                        tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
      }

      $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                      EMAIL_SEPARATOR . "\n" .
                      tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";


      tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT_1 . ' ' . $insert_id . ' ' .EMAIL_TEXT_SUBJECT_2 , $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
	  

      if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
        tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }

    }

	// # remove items from wishlist if customer purchased them $wishList->clear();

  // # Include AFFILIATE processing
  require(DIR_WS_INCLUDES . 'affiliate_checkout_process.php');

// # load the after_process function from the payment modules
//      $payment_modules->after_process();

      $payModule->finishPayment($order);

      $cart->reset(true);

// unregister session variables used during checkout
      tep_session_unregister('sendto');
      tep_session_unregister('billto');
      tep_session_unregister('shipping');
      tep_session_unregister('payment');
      tep_session_unregister('comments');
  if(tep_session_is_registered('credit_covers')) tep_session_unregister('credit_covers');// CCGV
  $order_total_modules->clear_posts();// CCGV

  if (EDI_ENABLE=='True') {
    include(DIR_WS_FUNCTIONS.'edi.php');
    edi_send_850($insert_id);
  }
  
  $feeds=tep_module('orderfeed');
  if ($feeds) foreach ($feeds->getModules() AS $fmod) $fmod->sendOrder(new order($insert_id));

//      tep_redirect(tep_href_link(sprintf($return_link,$insert_id), '', 'SSL'));
      if ($_GET['ajax']) {
?>
<table width="100%" border="0">
<tr><td>Payment Processing Status:</td></tr>
<tr><td style="color:green;font-weight:700;text-align:center;">Payment Approved</td></tr>
</table>
<script type="text/javascript">
  document.location='<?=HTTPS_SERVER.'/'.sprintf($return_link,$insert_id)?>';
</script>
<?php
      } else tep_redirect(HTTPS_SERVER.'/'.sprintf($return_link,$insert_id));
      exit;

    }
  } else {

    if (!isset($order)) $order = new order($cart);
    if ($order->delivery['zone_id'] > 0) {
      $ship_zone_id = $order->delivery['zone_id'];
    } elseif ($order->delivery['state'] != '' && tep_get_country_id($order->delivery['country'])) {
      $ship_zone_id = get_zone(tep_get_country_id($order->delivery['country']), $order->delivery['state']);
    }
    if ($order->billing['zone_id'] > 0) {
      $bill_zone_id = $order->billing['zone_id'];
    } elseif ($order->billing['state'] != '' && tep_get_country_id($order->billing['country'])) {
      $bill_zone_id = get_zone(tep_get_country_id($order->billing['country']), $order->billing['state']);
    }
//    $payment_modules = new payment;
  }
  
//  require_once(DIR_WS_CLASSES . 'shipping.php');
//  $shipping_modules = new shipping;
  // # get all available shipping quotes
//  $quotes = $shipping_modules->quote();

  if (!tep_session_is_registered('comments')) tep_session_register('comments');

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));

  $customer = array();

	if (tep_session_is_registered('customer_id')) {

		$customer_query = tep_db_query("SELECT c.*, ab.* 
										FROM " . TABLE_CUSTOMERS . " c 
										LEFT JOIN " . TABLE_ADDRESS_BOOK . " ab ON (c.customers_default_address_id = ab.address_book_id) 
										WHERE c.customers_id = '" . (int)$_SESSION['customer_id'] . "'
										");

		if(tep_db_num_rows($customer_query) > 0) { 

			$customer = tep_db_fetch_array($customer_query);
		}
	}
  

	if ($_GET['ajax']) {

?>
<table width="100%" border="0">
<tr><td style="color:red;font-weight:700;"><?php echo $messageStack->output('checkout'); ?></td></tr>
<tr><td style="text-align:center;"><button type="button" onClick="reviewOrder(); return false;">Review Order</button></td></tr>
</table>
<?php
    exit;
  }
  if (tep_session_is_registered('payment_error')) {
    $messageStack->add('checkout', $_SESSION['payment_error']);
    tep_session_unregister('payment_error');
  }
  
  $paymods=Array();
  if ($_REQUEST['use_module']) {
    $paymod=tep_module($_REQUEST['use_module'],'payment');
    if (isset($paymod) && $paymod->prepareCheckout()) {
      $paymods[$_REQUEST['use_module']]=&$paymod;
      $addr=$paymod->getPayerAddress();
      if ($addr) {
        if ($addr->getEmail()) {
          $cid=IXdb::read("SELECT customers_id FROM customers WHERE customers_email_address = '" . addslashes($addr->getEmail()) . "'",NULL,'customers_id');
	  if ($cid) $_SESSION['customer_id']=$cid;
	}
	$customer['customers_email_address']=$addr->getEmail();
	$customer['entry_street_address']=$addr->getAddress();
	$customer['entry_suburb']=$addr->getAddress2();
	$customer['customers_firstname']=$addr->getFirstName();
	$customer['customers_lastname']=$addr->getLastName();
	if ($addr->getPhone()) $customer['customers_telephone']=$addr->getPhone();
	$customer['entry_city']=$addr->getCity();
	$customer['entry_postcode']=$addr->getPostCode();
	$customer['entry_country_id']=$addr->getCountryID();
	$customer['entry_zone_id']=$addr->getZoneID();
      }
    }
  } else $paymods=$payset->getModulesCustomer($customer_id);
  
  
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<script type="text/javascript"><!--
var selected;
function selectRowEffect(object, sec, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected = object;

// one button is not an array
  if (sec != 's') {
    if (document.checkout.payment[0]) {
      document.checkout.payment[buttonSelect].checked=true;
    } else {
      document.checkout.payment.checked=true;
    }
  }
}


function toggleBilling() {
  if ($('billing_address').style.display == 'none') {
    $('billing_address').style.display = 'block';
  } else {
    $('billing_address').style.display = 'none';
  }
}

function reloadShipping(ignoreStates) {
	var $ = jQuery;
	var postCode = $('#ship_postcode').val();
	var country = $('#ship_country').val();
	var state = $('input[name=ship_state]').val();
	var address = $('input[name=ship_street_address]').val();
	var city = $('input[name=ship_city]').val();

	var url = '/shipping_options.php?'+$.param({
		zip: postCode
		, cnty: country
		, zone: state
		, weight: '<?php echo json_encode($multi_weight)?>' 
		, free: '<?php echo json_encode($free_shipping);?>' 
	});
	var postData = {
		street_address: address
		, city: city
		, state: state
	};

	var $options = $('#shipping_options');

	if (postCode == ''){

		$options.html('<p>Please select your state and postal code<\/p>');

	} else {

		$options.html('<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Loading shipping costs, please wait...<br><img src="images\/loading_bar.gif" alt=""><\/td><\/tr><\/table>');
		$.post(url, postData, 'html')
			.done(function(html){
				$options.html(html);
				reloadOT('');
			})
			.fail(function(){
				$options.html('Unable to load shipping options.');
			});
	}

}

function applyCoupon(code) {
	var $ = jQuery;
	$('#coupon_code').html('<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Verifying and applying coupon, please wait...<br><img src="images\/loading_bar.gif" alt=""><\/td><\/tr><\/table>');

	var shippingSelection = document.checkout.shipping.value;
	var url = '<?=HTTPS_SERVER;?>/checkout_ot.php';
	$.post(url, 'gv_redeem_code='+code+'&method='+shippingSelection, 'html')
		.done(function(html){
			$('#coupon_code').html(html);
			reloadShipping(shippingSelection);
		})
		.fail(function(){
			$('#coupon_code').html('Unable to apply coupon code.');
		})
	;
}

function reloadOT(v) {
	var $=jQuery;
	v = v || document.checkout.shipping.value;

	if (!v){
		$('#order_total').html('No shipping selected');
		return;
	}

	$('#order_total').html('<table border="0" height="65"><tr><td align="right" valign="middle"><img src="images\/loading.gif" alt=""><\/td><\/tr><\/table>');

	var zone=document.checkout.ship_state.value;
	var country = document.checkout.ship_country.value;
	var url = '<?php echo HTTPS_SERVER;?>/order_total.php';
	var params = {s: v, z: zone, c: country};
	$.get(url, $.param(params), 'html')
		.done(function(html){
			$('#order_total').html(html);
		})
		.fail(function(){
			$('#order_total').html('Could not load order totals.');
		});

}


function setState(sec,v) {
  document.checkout[sec+"_state"].value = v;
  if (sec=='ship') reloadShipping();
}

function setShipping(v) {
  document.checkout.shipping.value = v;
  reloadOT(v);
  return true;
}

var reloadStateBusy = Array();

function reloadState(sec) {

  if (reloadStateBusy[sec]) return;
  reloadStateBusy[sec]=1;
  window.setTimeout('reloadStateBusy[\''+sec+'\']=0',500);
  var section_country = document.checkout[sec+'_country'].value;
  var section_state = document.checkout[sec+'_state'].value;
  var section_postcode = document.checkout[sec+'_postcode'].value;

  if (section_postcode=='') {
    $(sec+'_state').innerHTML = 'Please enter postcode first';
    if (sec=='ship') reloadShipping();
    return;
  }
  $(sec+'_state').innerHTML = '<img src="images\/loading_bar.gif" alt="">';
  new ajax('/state_dropdown.php?country='+section_country+'&sec='+sec+'&postal='+section_postcode+'&d='+section_state, {method: 'get', update: $(sec+'_state')});

	// ###### address validation - get the city name
	var zip = document.checkout.ship_postcode.value;

	// # detect country - USPS will only work for United States and its territories
	var ship_country = document.checkout.ship_country.value;

	var url = '<?php echo HTTPS_SERVER;?>/address_validation.php';

	if(ship_country == 223) {
		jQuery.get(url, 'address_validate=CityStateLookupRequest&zip='+zip+'&ship_country='+ship_country, 'html')
			.done(function(html){
				if(html.length > 0) {
					jQuery('input[name="ship_city"]').val(html);
				}
			})
			.fail(function(){
				//jQuery('input[name="ship_city"]').val();
				//alert('Having trouble validating your postal or zip code');
			});
	// # canada post - get city name
	} else if (ship_country == 38) { 
		jQuery.get(url, 'address_validate=reversePostalcode&zip='+zip+'&ship_country='+ship_country, 'html')
			.done(function(html){
				if(html.length > 0) {
					jQuery('input[name="ship_city"]').val(html);
				}
			})
			.fail(function(){
				jQuery('input[name="ship_city"]').val();
				console.log('Having trouble validating your postal or zip code');
			});		
	}
}


function reloadPostal(sec) {
//  document.checkout[sec+'_postcode'].value = '';
  reloadState(sec);
}

function processOrder(f) {
	if(f.local_time) f.local_time.value = new Date().toString();
	if(window.checkoutSubmitted || !check_form(f)) { 
		return; 
	}

	if (!$('overlay')) $('default_overlay').id='overlay';
	if (!$('dialog_box')) $('default_dialog_box').id='dialog_box';
	if (!$('progress_bar_status')) $('default_progress_bar_status').id='progress_bar_status';
	if (!$('checkout_response')) {
    	var rsbox=document.createElement('div');
	    rsbox.id='checkout_response';
    	$('dialog_box').insertBefore(rsbox,null);
	}

	window.scroll(0,0);
	$('overlay').style.display = "block";
	$('overlay').style.height = "2000px";
	$('dialog_box').style.width = "300px";
	$('dialog_box').style.height = "100px";
	$('dialog_box').style.top = (((window.innerHeight?window.innerHeight:(document.documentElement.clientHeight?document.documentElement:document.body).clientHeight) / 2) - 50)+"px";
  $('dialog_box').style.left = (((window.innerWidth?window.innerWidth:(document.documentElement.clientWidth?document.documentElement:document.body).clientWidth) / 2) - 150)+"px";
  $('progress_bar_status').style.display = "block";
  $('checkout_response').style.display = "none";
  $('dialog_box').style.display = "block";
  $('dialog_box').className = "dialog_process";
  window.checkoutSubmitted=true;
  
  new ajax('checkout.php?ajax=1',{ postForm:f,onComplete:function(req) {
    $('progress_bar_status').style.display = "none";
    $('checkout_response').style.display = "block";
    $('checkout_response').innerHTML=req.responseText;
    var sc=$('checkout_response').getElementsByTagName('script');
    for (var i=0;sc[i];i++) window.eval(sc[i].innerHTML);
  }});
//  f.submit();

  var oForm = f.elements;
  for(i=0; i < oForm.length; i++) { 
    if (oForm[i].type=="select-one") {
      oForm[i].disabled=true;
//      oForm[i].style.backgroundColor="#999999";
    }
  }
}

function reviewOrder() {
  window.checkoutSubmitted=false;
  $('dialog_box').style.display = "none";
  $('overlay').style.display = "none";
  var oForm=document.checkout.elements;
  for(i=0; i < oForm.length; i++) { 
    if (oForm[i].type=="select-one") {
      oForm[i].disabled=false;
    }
  }
}

// # Perform Address verification and validation using USPS API and address_validation.php
jQuery( document ).ready(function() {

	jQuery('input[name="ship_street_address"], input[name="ship_suburb"], input[name="ship_city"]').blur(function() { 


		setTimeout(function() { 

		var firstname = document.checkout.firstname.value;

		var ship_street_address1 = document.checkout.ship_street_address.value;

		var ship_street_address2 = document.checkout.ship_suburb.value;
		ship_street_address2 = (jQuery.isNumeric(ship_street_address2.charAt(0)) ? 'APT '+ship_street_address2 : ship_street_address2);

		var zip = document.checkout.ship_postcode.value;

		var ship_city = document.checkout.ship_city.value;

		var ship_country = document.checkout.ship_country.value;

		var url = '<?php echo HTTPS_SERVER;?>/address_validation.php';

		if(ship_street_address1 && ship_country == 223) { 				

			jQuery('#simplemodal-overlay').css({'display':'block'});
			jQuery('#simplemodal-container').css({'display':'block'});

			jQuery.get(url, 'address_validate=Verify&zip='+zip+'&ship_street_address1='+ship_street_address1+'&ship_street_address2='+ship_street_address2+'&ship_city='+ship_city+'&firstname='+firstname+'&ship_country='+ship_country, 'html')
				.done(function(data) {

					var obj = jQuery.parseJSON(data);

					var usingSuggested = jQuery('#suggestedAddress').val();

					var ship_street_address1NEW = obj[1];
					var ship_street_address2NEW = obj[2];
					var ship_cityNEW = obj[3];
					var ship_stateNEW = obj[4];

					if(obj[6]) { 
					var zipNEW = obj[5] + '-' + obj[6];
					}


					if((ship_street_address1 != ship_street_address1NEW) || ( ship_street_address2 != ship_street_address2NEW) || (zip != zipNEW) || (ship_city != ship_cityNEW)) { 
						var usingSuggested = 0;
					} 

					if(obj[7] || !ship_street_address1NEW) { 
						var returnText = obj[7];
						returnText = returnText.replace('Default address: ', '');

						jQuery('#modal-contents').html('<div class="returnText">'+returnText+'<br><br>Please correct your address.</div>');

						jQuery('#modal-contents').append('<div class="returnTextContinue" align="center"><a class="simplemodal-close"><img class="modalCloseImg" src="/layout/img/buttons/english/button_continue.gif"></a></div>');

						jQuery('#basic-modal > .basic').click();


					} else {


						if(usingSuggested != 'useSuggestedAddress') {

							jQuery('#modal-contents').html('<form id="address_verification"><div style="font:bold 13px arial; padding:15px 0 10px 0">Suggested Address:</div> <input type="radio" name="suggestedAddress" id="suggestedAddress" value="useSuggestedAddress"> <label for="suggestedAddress">'+ship_street_address1NEW+'&nbsp;  '+ship_street_address2NEW+',  &nbsp; '+ship_cityNEW+',  &nbsp;'+ship_stateNEW+'&nbsp; '+zipNEW+'</label><div style="font:bold 13px arial; padding:15px 0 10px 0">Keep Address As Entered:</div> <input type="radio" name="suggestedAddress" id="asEnteredAddress" value="asEnteredAddress"> <label for="asEnteredAddress">Keep original address as entered (may result in delivery delays)</label><div style="width:90%;height:30px; padding:15px 0 10px 0;" align="center"><a class="simplemodal-close"><img id="useSuggestedAddress" src="/layout/img/buttons/english/button_continue.gif"></a></div></form>');

							jQuery('#basic-modal > .basic').click();
						}	
	
					}

					window.ship_street_address1 = ship_street_address1NEW;
					window.ship_street_address2 = ship_street_address2NEW;
					window.ship_city = ship_cityNEW;
					window.zip = zipNEW;
				})

				.fail(function(){	
					//alert('Having trouble validating your postal or zip code');
				});
			}
				}, 3000); // # End setTimeout() function
		});

	jQuery(document).on('change', '#suggestedAddress, #asEnteredAddress', function() {	
			var whichAddress = jQuery('input:radio[name=suggestedAddress]:checked').val();

			if(whichAddress == 'useSuggestedAddress') {

				jQuery(document).on('click', '#useSuggestedAddress', function() {
					jQuery('input[name="ship_street_address"]').val(window.ship_street_address1);
					jQuery('input[name="ship_suburb"]').val(window.ship_street_address2);
					jQuery('input[name="ship_city"]').val(window.ship_city);
					jQuery('input[name="ship_postcode"]').val(window.zip);
					
					reloadState('ship');

				});
			}
			
		});

		jQuery(document).on('click', '.simplemodal-close, #simplemodal-overlay', function() {

			jQuery('#simplemodal-overlay').css({'display':'none'});
			jQuery('#simplemodal-container').css({'display':'none'});
		});

		jQuery('body').append('<div id="basic-modal"><a href="#" class="basic"><\/a></div><div id="basic-modal-content"><div class="header"><div>Shipping Address Verification<\/div><\/div><div id="modal-contents"><\/div><div class="modal-footer"><div class="modal-footer-text">Verification Provided By: <\/div><div class="modal-footer-logo"><img src="\/images/items\/usps-logo-modal-footer.png" width="149" height="25" alt="United States Postal Service"><\/div><\/div><\/div><div style="display:none"><img src="/layout/img/x.png" alt=""><img src="/images\/items\/usps-logo-modal-footer.png"><\/div><script type="text\/javascript" src="\/js\/jquery.simplemodal.js"><\/script><script type="text\/javascript" src="\/js/basic.js"><\/script>');

});
//--></script>
<?php require_once('includes/form_check_checkout_1.js.php'); ?>
<?php //if (is_object($payment_modules)) echo $payment_modules->javascript_validation(); ?>
<?php require_once('includes/form_check_checkout_2.js.php'); ?>
</head>
<body>

<?php require_once(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require_once(DIR_WS_INCLUDES . 'column_left.php'); ?>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top" colspan="2">
      <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
          <td><div style="padding-top:10px; font:bold 15px Arial;">Enter your payment information and confirm your order ...</div></td>
          </td>
        </tr>
<?php
	if ($_GET['db'] == '1') { 
		echo '<pre>'; print_r($order); echo '</pre>'; 
	}

	if ($messageStack->size('checkout') > 0) {

		echo '<tr>
        	  	<td>'. $messageStack->output('checkout') .'</td>
        </tr>';
	}

	if ($show_cart) { ?>
      <tr>
        <td class="checkout_SectionTitle" style=" padding:3px; padding-left:10px; height:23px;"><b>&bull; Shopping Cart Contents</b></td>
          
      </tr>
      <tr>
        <td>
<?php include(DIR_WS_MODULES.'express_cart.php') ?>
        </td>
      </tr>
<?php }

  if ($show_find_members) {
?>

      <?php echo tep_draw_form('find_members', tep_href_link(FILENAME_CHECKOUT, 'action=login&'.tep_get_all_get_params(Array('action')), 'SSL'), 'POST', '', 'style="margin:0;"'); ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="checkout_SectionTitle" style="padding:3px; padding-left:10px; height:23px;"><b>&#149; Find Member</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
         <?php include(DIR_WS_MODULES.'find_members.php') ?>
         </td>
      </tr>
      </form>
<?php
  } else if (!tep_session_is_registered('customer_id')) {
?>
      <tr>
        <td style="height:20px"></td>
      </tr>
<tr>
        <td style="background-color:#000000; height:1px"></td>
      </tr>


      <tr>
        <td>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TEXT_RETURNING_CUSTOMER; ?></b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td height="35" style="padding-left:5px;">
	 <div id="login_form"><table width="100%" border="0" cellpadding="0" cellspacing="0">
	   <tr>
        <td style="width:14px; padding-top:2px;"><a href="/login.php" onClick="showLoginForm(); return false;"><img src="images/icons/plus.gif" border="0" alt=""></a> </td>
<td><a href="/login.php" onClick="showLoginForm(); return false;">&nbsp; <b>Returning customers, please click here to login and populate all fields below.</b></a></td>
</tr></table></div>
	 <script type="text/javascript"><!--
  function showLoginForm() {
    $('login_form').innerHTML=
'          <?php echo tep_draw_form('login', tep_href_link(FILENAME_CHECKOUT, 'action=login&'.tep_get_all_get_params(Array('action')), 'SSL'), 'POST', '', 'style="margin:0;"'); ?>\n'+
'          <table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">\n'+
'            <tr class="infoBoxContents">\n'+
'              <td style="padding-top:10px;">\n'+
'                <table width="100%" border="0" align="center" cellpadding="5" cellspacing="5">\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b><?php echo ENTRY_EMAIL_ADDRESS; ?><\/b><\/td>\n'+
'                    <td class="main"><?php echo tep_draw_input_field('email_address', '', 'style="width:150px; height:20px"'); ?><\/td>\n'+
'                  <\/tr>\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b><?php echo ENTRY_PASSWORD; ?><\/b><\/td>\n'+
'                    <td class="main"><?php echo tep_draw_password_field('password', '', 'style="width:150px; height:20px"'); ?><\/td>\n'+
'                  <\/tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" class="smallText" align="center"><?php echo '<a href="' . tep_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '<\/a>'; ?><\/td>\n'+
'                  <\/tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" align="center" style="padding-right:5px;"><?php echo ($_GET['co'] == '1' ? tep_draw_hidden_field('co', '1') : '') . tep_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN); ?><\/td>\n'+
'                  <\/tr>\n'+
'                <\/table>\n'+
'              <\/td>\n'+
'            <\/tr>\n'+
'          <\/table>\n'+
'          <\/form>\n';
  }
  //-->
  </script>

        </td>
      </tr>

<?php

	}
?>
<tr><td>

<?php echo tep_draw_form('checkout', tep_href_link('checkout.php', tep_get_all_get_params(array('action')), 'SSL'), 'POST', 'onsubmit="return check_form(checkout);"')?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><?=tep_draw_hidden_field('action', 'checkout'); ?></td>
      </tr>

<?php
  if ($display_mode=='admin') {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="checkout_SectionTitle" style="padding:3px; padding-left:10px; height:23px;"><b>&#149; Admin Controls</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
         <?php include(DIR_WS_MODULES.'checkout_admin_controls.php') ?>
         </td>
      </tr>
<?php
  }
?>

      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="border-right:0; padding:3px; padding-left:10px; height:23px;"><b>&#149; 
<?php echo (!tep_session_is_registered('customer_id') ? TEXT_NEW_CUSTOMER : TEXT_RETURNING_CUSTOMER); ?></b></td>
           <td class="checkout_SectionTitle" style="border-left:0; padding:3px; font-weight:bold; color:#FF0000; padding-right:15px;" align="right"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_PERSONAL; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="250" class="inputRequirement"><?php echo ENTRY_FIRST_NAME . '<br>' . tep_draw_input_field('firstname', $customer['customers_firstname'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></td>
                    <td width="245" class="inputRequirement"><?php echo ENTRY_LAST_NAME . '<br>' . tep_draw_input_field('lastname', $customer['customers_lastname'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></td>
                  </tr>
                  <tr valign="bottom">
                    <td height="45" class="inputRequirement"><?php echo ENTRY_TELEPHONE_NUMBER . '<br>' . tep_draw_input_field('telephone', $customer['customers_telephone'], 'style="width:100px" maxlength=32') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
                    <td height="45" class="inputRequirement"><?php echo ENTRY_EMAIL_ADDRESS . '<br>' . tep_draw_input_field('email_address', $customer['customers_email_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': ''); ?></td>
                  </tr>
                  <tr valign="bottom">
                    <td height="45" class="inputRequirement"><?php echo ENTRY_COMPANY . '<br>' . tep_draw_input_field('company', $customer['customers_company'], 'style="width:100px"') ?></td>
                    <td height="45" class="inputRequirement"><?php echo ENTRY_FAX_NUMBER . '<br>' . tep_draw_input_field('fax', $customer['customers_fax'], 'style="width:100px" maxlength=32') ?></td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo TABLE_HEADING_SHIPPING_ADDRESS; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;">
                  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="250" class="main" style="width:240px"><?php echo ENTRY_STREET_ADDRESS . '<br>' . tep_draw_input_field('ship_street_address', $customer['entry_street_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
                      <td width="245" class="main"><?php echo 'Suite / Floor / Other:
<br>' . tep_draw_input_field('ship_suburb', $customer['entry_suburb'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
                    </tr>
                    <tr valign="bottom">
                      <td height="45" class="main"><?php echo ENTRY_CITY . '<br>' . tep_draw_input_field('ship_city', (!empty($customer['entry_city']) ? $customer['entry_city'] : (!empty($_SESSION['customer_city']) ? $_SESSION['customer_city'] : '')), 'style="width:200px" maxlength="255"') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
                      <td height="45" class="main"><table><tr><td colspan="2"><?=ENTRY_STATE?></td></tr><tr><td><div id="ship_state" style="display:block;position:relative;z-index: 1"></div>

<input type="hidden" name="ship_state" value="<?php echo $customer['entry_zone_id']?>"> 
					</td>
					<td><?php if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT . '</span>'; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
                    <tr valign="bottom">
                      <td colspan="2">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                          <tr>
			  <?php if (!$customer['entry_postcode']) $customer['entry_postcode']=$GLOBALS['ship_postcode']; ?>
                            <td width="240" height="45"  class="inputRequirement">
<?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('ship_postcode', $customer['entry_postcode'], 'style="width:100px" maxlength=10 id="ship_postcode" onBlur="reloadState(\'ship\', document.checkout.ship_state.value)"','text',false) . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
                            <td height="45"  class="inputRequirement">&nbsp;<?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('ship_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : ($ship_country?$ship_country:STORE_COUNTRY)), 'id="ship_country" onChange="reloadPostal(\'ship\')"') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo TABLE_HEADING_BILLING_ADDRESS; ?><span style="padding-left: 50px;" ><input type="checkbox" name="bill_same" value="1" CHECKED onClick="toggleBilling();" class="bill_same"><?php echo TEXT_BILLING_SAME; ?></span></td>
              </tr>
              <tr>
                <td style="padding-top:10px;">
                  <div id="billing_address" style="display:none">
                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="250" class="main" style="width:240px"><?php echo ENTRY_STREET_ADDRESS . '<br>' . tep_draw_input_field('bill_street_address', $customer['entry_street_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
                        <td width="245" class="main"><?php echo ENTRY_SUBURB . '<br>' . tep_draw_input_field('bill_suburb', $customer['entry_suburb'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
                      </tr>
                      <tr valign="bottom">
                        <td height="45" class="main"><?php echo ENTRY_CITY . '<br>' . tep_draw_input_field('bill_city', $customer['entry_city'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
                        <td height="45" class="main"><?php echo ENTRY_STATE . '<br><div id="bill_state"></div><input type="hidden" name="bill_state" value="' . $customer['entry_zone_id'] . '">'; if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT . '</span>'; ?></td>
                      </tr>
                      <tr valign="bottom">
                        <td colspan="2">
                          <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                              <td width="200" height="45" class="inputRequirement"><?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('bill_postcode', $customer['entry_postcode'], 'style="width:100px" maxlength=10 onChange="reloadState(\'bill\')"') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
                              <td width="400" height="45" class="inputRequirement">&nbsp;<?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('bill_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : STORE_COUNTRY), 'id="bill_country" onChange="reloadPostal(\'bill\')"') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </div>
                </td>
              </tr>
<?php
	if (USE_COUPONS=='Enable') {
?>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Coupon Code</td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="100%" valign="top"><div id="coupon_code"><table><tr><td>Coupon Code: </td><td class="inputRequirement"><?=tep_draw_input_field('gv_redeem_code', '', 'id="gv_redeem_code"') . '</td><td>&nbsp; <img onClick="applyCoupon($(\'gv_redeem_code\').value)" src="' . DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/' . $language . '/button_redeem.gif" border="0" alt="' . IMAGE_REDEEM_VOUCHER . '" title="' . IMAGE_REDEEM_VOUCHER . '" style="cursor:pointer">'; ?></td></tr></table>
<noscript>Please enable javascript to checkout.</noscript></td>
                  </tr>
                </table>
</td>
              </tr>

<?php
	}
		  
	if (!tep_session_is_registered('customer_id')) {
?>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; padding-top:20px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_OPTIONS; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="100%" valign="top"><?php echo ENTRY_NEWSLETTER; ?>? &nbsp; <?php echo tep_draw_checkbox_field('newsletter', '1') . '&nbsp;' . (tep_not_null(ENTRY_NEWSLETTER_TEXT) ? '<span class="inputRequirement">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); ?></td>
                  </tr>
                </table>
</td>
              </tr>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Create New Password<?//php echo CATEGORY_PASSWORD; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr valign="top">
                    <td width="250" height="45" class="main" style="width:240px"><?php echo ENTRY_PASSWORD . '<br>' . tep_draw_password_field('password', (isset($_POST['password'])?$_POST['password']:''), 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>': ''); ?></td>
                    <td width="245" height="45" class="main"><?php echo ENTRY_PASSWORD_CONFIRMATION . '<br>' . tep_draw_password_field('confirmation', (isset($_POST['confirmation'])?$_POST['confirmation']:''), 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>': ''); ?></td>
                  </tr>
                </table></td>
              </tr>
                  <?
  }
?>
              <tr>
                <td class="checkout_itemTitle" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Additional Information</td>
              </tr>
<!-- //rmh referral start -->
<?php
  if (!isset($customer_id)) {
  
  if ((tep_not_null(tep_get_sources()) || DISPLAY_REFERRAL_OTHER == 'true') && (!tep_session_is_registered('referral_id') || (tep_session_is_registered('referral_id'))) ) {
    if ((tep_session_is_registered('referral_id') && tep_not_null($referral_id)) || tep_not_null($_POST['source_other'])) {
      $source_id = '9999';
    } else {
      $source_id = $_POST['source'];
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="checkout_itemTitle"><b><?php echo CATEGORY_SOURCE; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="checkout_itemTitle"><?php echo ENTRY_SOURCE; ?></td>
                <td ><?php echo tep_get_source_list('source', true, $source_id) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_TEXT . '</span>': ''); ?></td> 
              </tr>
              <tr>
                <td class="checkout_itemTitle"><?php echo ENTRY_SOURCE_OTHER; ?></td>
                <td ><?php echo tep_draw_input_field('source_other', (tep_not_null($referral_id) ? $referral_id : '')) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_OTHER_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_OTHER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
  
  } else {
    echo tep_draw_hidden_field('source',6666);
  }
?>
<!-- //rmh referral end -->
	      <tr>
	        <td class="checkout_itemTitle"><b><?php echo CATEGORY_COMMENTS; ?></b></td>
	      </tr>
              <tr>
                <td style="padding-top:10px;"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr valign="top">
                    <td width="250" height="45" class="main" style="width:240px"><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
                    </tr>
                </table></td>
              </tr>
            </table>
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '30'); ?></td>
      </tr>
      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="border-right:0; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TABLE_HEADING_SHIPPING_METHOD; ?></b></td>
            <td align="right" class="checkout_SectionTitle" style="border-left:0; padding:3px; font-weight:bold; color:#ff0000; padding-right:15px;"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
          <div id="shipping_options"><noscript>Please enable javascript to checkout.</noscript></div>
          <input type="hidden" name="shipping" value="">
        </td>
      </tr>
      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="checkout_SectionTitle" style="border-right:0; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TABLE_HEADING_PAYMENT_METHOD; ?></b></td>
            <td align="right" class="checkout_SectionTitle" style="border-left:0; padding:3px; font-weight:bold; color:#ff0000; padding-right:15px;"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">
          <tr class="infoBoxContents">
            <td>
<?php
    
  if (!sizeof($paymods)) echo 'No payment methods available';
  else {
    $pay_selbox=Array();
    foreach ($paymods AS $mkey=>$mod) $pay_selbox[]=Array('id'=>$mkey,'text'=>$mod->getTitle());
    $selmod=$pay_selbox[0]['id'];
    echo (sizeof($pay_selbox)>1?tep_draw_pull_down_menu('pay_method',$pay_selbox,$selmod,'id="pay_method" onChange="setPayMethod()"'):tep_draw_hidden_field('pay_method',$selmod).$pay_selbox[0]['text']);
    foreach ($paymods AS $mkey=>$mod) {
      echo '<div id="'.$mkey.'" style="'.($mkey==$selmod?'':'display:none').';">';
      echo $mod->paymentBox();
      echo "</div>\n";
    }
?>
<script type="text/javascript">
  function setPayMethod() {
    var blk=$('pay_method');
    if (!blk) return false;
    for (var i=0;blk.options[i];i++) if ($(blk.options[i].value)) $(blk.options[i].value).style.display=blk.options[i].selected?'':'none';
    return true;
  }
</script>
<?php
  }
?>
            
        </td>
      </tr>
          
	  <tr>
	    <td align="right"><div id="order_total"></div>
	    
<div id="default_overlay" style="display: none; position:absolute;"></div>
<div id="default_dialog_box" style="display: none; position:absolute;">
    <div id="default_dialog_box_header">
      <div style="width: 16px"><img src="images/icons/lock_blue.gif" height="16" width="16" alt=""></div>
      <div style="position: absolute; top: 4px; left: 30px;color:#FFF"><b>Order Processing!</b></div>
    </div>
    <div id="default_progress_bar_status"><table border="0"><tr><td style="width:50px"><img src="images/loading.gif" alt=""></td><td><b>Your order is currently being processed and may take up to two minutes to complete.  Thank you for your patience!</b></td></tr></table></div>
    <div id="default_checkout_response" style="display:none"></div>
</div>
	    
	    </td>
	  </tr>
          
          <tr>
            <td><script type="text/javascript">reloadState('ship'); reloadState('bill');</script>
<table border="0" width="100%" cellspacing="1" cellpadding="2"  class="infoBox">
              <tr>
                <td align="right"><input type="hidden" name="local_time"><div id="confirm_button"><a href="javascript: void(0)" onClick="processOrder(document.checkout); return false"><?php echo tep_image(DIR_WS_CATALOG_LAYOUT_IMAGES.'buttons/'.$language.'/button_confirm_order.gif', IMAGE_BUTTON_CONFIRM_ORDER, 'border="0"'); ?></a></div></td>
              </tr>
            </table></td>
          </tr>
    </table></form>
</td>
</tr></table></td>

    <td valign="top">

<?php include(DIR_WS_INCLUDES . 'column_right.php'); ?>

</td>
  </tr>
</table>

<?php include(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require_once(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
