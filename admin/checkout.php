<?php

  require_once('../includes/filenames.php');

  require_once('includes/application_top.php');
  include(DIR_FS_CATALOG_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);  

  $show_cart=0;
  $show_find_members=0;
  if (isset($HTTP_GET_VARS['display_mode'])) {
    $display_mode=$HTTP_GET_VARS['display_mode'];
  } else $display_mode='user';
  if ($display_mode=='admin') {
    $return_link='admin/orders.php?oID=%d&action=edit';
    $show_cart=$show_find_members=1;
    $find_members_box_url=HTTPS_SERVER.'/admin/find_members_box.php';
  } else if ($display_mode=='affiliate') {
    $return_link='affiliate_summary.php';
    $show_cart=$show_find_members=1;
    $find_members_box_url=HTTPS_SERVER.'/affiliate_find_members_box.php';
  } else {
    $return_link=FILENAME_CHECKOUT_SUCCESS;
  }
  if (isset($HTTP_GET_VARS['return_link'])) $return_link=$HTTP_GET_VARS['return_link'];
  if (isset($HTTP_GET_VARS['show_cart']) && $HTTP_GET_VARS['show_cart']) $show_cart=1;

  require_once(DIR_WS_CLASSES. 'shopping_cart.php');
  $cart=new shoppingCart;

  
  require_once(DIR_WS_CLASSES . 'order.php');
//  $order = new order;
  require_once(DIR_WS_CLASSES . 'order_total.php');// CCGV
  $order_total_modules = new order_total;// CCGV
  
//  $total_weight = $cart->show_weight();
//  $multi_weight = $cart->show_multi_weight_line();
//  $total_count = $cart->count_contents();
//  $free_shipping = $cart->free_shipping;
  
  
  
//  if (!tep_session_is_registered('shipping')) tep_session_unregister('shipping');
  if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
  
  // if no shipping destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('sendto')) {
    tep_session_register('sendto');
    $sendto = $customer_default_address_id;
  } else {
    // verify the selected shipping address
    $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$sendto . "'");
    $check_address = tep_db_fetch_array($check_address_query);
    
    if ($check_address['total'] != '1') {
      $sendto = $customer_default_address_id;
      if (tep_session_is_registered('shipping')) tep_session_unregister('shipping');
    }
    
  }
// load all enabled payment modules
  require_once(DIR_WS_CLASSES . 'payment.php');
  if (!tep_session_is_registered('cartID')) tep_session_register('cartID');
  $cartID = $cart->cartID;
  
  if (!tep_session_is_registered('customer_id')) {
    if (!tep_session_is_registered('billto')) {
      tep_session_register('billto');
      $billto = $customer_default_address_id;
    } else {
      // verify the selected billing address
      $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$billto . "'");
      $check_address = tep_db_fetch_array($check_address_query);
    
      if ($check_address['total'] != '1') {
        $billto = $customer_default_address_id;
      }
    }
  }

  
  require_once(DIR_FS_CATALOG_LANGUAGES . $language . '/' . FILENAME_CHECKOUT);

  $error = false;

  //Login block
  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'login') && isset($HTTP_POST_VARS['email_address'])) {
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
    if (isset($HTTP_POST_VARS['password'])) {
      $password = tep_db_prepare_input($HTTP_POST_VARS['password']);
      checkout_login($email_address, $password);
    } else {
      $password='';
      $password_crypt = tep_db_prepare_input($HTTP_POST_VARS['password_crypt']);
      checkout_login($email_address, $password, $password_crypt);
    }

    if ($error == true) {
      $messageStack->add('checkout', TEXT_LOGIN_ERROR);
    }
  }

  //Login block
  function checkout_login($email_address, $password, $pass_crypt) {
    global $error, $cart, $wishList;
      global $customer_id;
      global $customer_default_address_id;
      global $customer_first_name;
      global $customer_password;
      global $customer_country_id;
      global $customer_zone_id;

    $email_address = tep_db_prepare_input($email_address);
    $password = tep_db_prepare_input($password);

// Check if email exists
    $check_customer_query = tep_db_query("select customers_id, customers_firstname, customers_password, customers_email_address, customers_default_address_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
    if (!tep_db_num_rows($check_customer_query)) {
      $error = true;
    } else {
      $check_customer = tep_db_fetch_array($check_customer_query);
      // Check that password is good
      if (isset($pass_crypt) ? ($pass_crypt!=$check_customer['customers_password']) : !tep_validate_password($password, $check_customer['customers_password'])) {
        $error = true;
      } else {
        if (SESSION_RECREATE == 'True') {
          tep_session_recreate();
        }

        $check_country_query = tep_db_query("select entry_country_id, entry_zone_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$check_customer['customers_id'] . "' and address_book_id = '" . (int)$check_customer['customers_default_address_id'] . "'");
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

        tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1 where customers_info_id = '" . (int)$customer_id . "'");

        $cart->restore_contents();
        $wishList->restore_wishlist();
      }
    }
  }
  
  function get_zone($country, $state) {
    $zone_id = 0;
    if (!is_numeric($country) || $country < 0 || strlen(trim($state)) < 2) return '0';
    $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
    $check = tep_db_fetch_array($check_query);
    $entry_state_has_zones = ($check['total'] > 0);
    if ($entry_state_has_zones == true) {
      $zone_query = tep_db_query("select distinct zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and (zone_name like '" . tep_db_input($state) . "%' or zone_code like '%" . tep_db_input($state) . "%')");
      if (tep_db_num_rows($zone_query) == 1) {
        $zone = tep_db_fetch_array($zone_query);
        return $zone['zone_id'];
      } else {
        return '0';
      }
    }
  }

  if ($error == true) {
    $messageStack->add('checkout', TEXT_LOGIN_ERROR);
  }
  
  
  
  //Checkout block
  $process = false;
  if (isset($_POST['action']) && ($_POST['action'] == 'checkout')) {


    $process = true;

    $firstname = tep_db_prepare_input($_POST['firstname']);
    $lastname = tep_db_prepare_input($_POST['lastname']);
    $ship_street_address = tep_db_prepare_input($_POST['ship_street_address']);
    $ship_suburb = tep_db_prepare_input($_POST['ship_suburb']);
    $ship_postcode = tep_db_prepare_input($_POST['ship_postcode']);
    $ship_city = tep_db_prepare_input($_POST['ship_city']);
    //$ship_state = tep_db_prepare_input($_POST['ship_state']);
    $ship_country = tep_db_prepare_input($_POST['ship_country']);
//    $ship_zone_id = get_zone($ship_country, $ship_state);
    $ship_zone_id = tep_db_prepare_input($_POST['ship_state']);
    //$ship_zone_id = tep_db_prepare_input($_POST['ship_state']);
    $ship_state = tep_get_zone_name($ship_country, $ship_zone_id, '');
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
    if (isset($_POST['newsletter'])) {
      $newsletter = tep_db_prepare_input($_POST['newsletter']);
    } else {
      $newsletter = false;
    }
    $password = tep_db_prepare_input($_POST['password']);
    $confirmation = tep_db_prepare_input($_POST['confirmation']);
//rmh referral start
    $source = tep_db_prepare_input($HTTP_POST_VARS['source']);
    if (isset($HTTP_POST_VARS['source_other'])) $source_other = tep_db_prepare_input($HTTP_POST_VARS['source_other']);
//rmh referral end

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
          //They're a customer, so log them in if their password is correct
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
//rmh referral end

      if ($payment == '') $payment = 'paypal_wpp';
      if ($credit_covers) $payment=''; // CCGV 
      $payment_modules = new payment($payment);
    
    if (!$error) {
      if (!tep_session_is_registered('customer_id')) {
        $sql_data_array = array('customers_firstname' => $firstname,
                                'customers_lastname' => $lastname,
                                'customers_email_address' => $email_address,
                                'customers_telephone' => $telephone,
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
            $sql_data_array['entry_state'] = '';
          } else {
            $sql_data_array['entry_zone_id'] = '0';
            $sql_data_array['entry_state'] = $ship_state;
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
      

// By MegaJim
      if ( (isset($_POST['shipping'])) && (strpos($_POST['shipping'], '_')) ) {
        list($module, $method) = explode('_', $_POST['shipping']);
        require_once(DIR_WS_CLASSES . 'shipping.php');
        $shipping_modules = new shipping($_POST['shipping']);
	$shipping=Array(
	 id => $module.'_'.$method,
	 title => $shipping_options[$module][$method]['name'],
	 cost => $shipping_options[$module][$method]['cost']);
      }

      $order = new order;

      
      //Payment info
      if (!tep_session_is_registered('storecard')) tep_session_register('storecard');
      $_SESSION['storecard'] = $_POST['storecard'];
      if (!tep_session_is_registered('payment')) tep_session_register('payment');
      if (isset($_POST['payment'])) $payment = $_POST['payment'];

      
      include(DIR_WS_FUNCTIONS . 'encryption.php');
      $country_query = tep_db_query("SELECT countries_name, countries_iso_code_2, countries_iso_code_3 FROM " . TABLE_COUNTRIES . " WHERE countries_id = '" . (int)$bill_country . "'");
      $cty = tep_db_fetch_array($country_query);
      for ($x = 0; $x < 2; $x++) {
        if ($x == 0) {
          $o =& $order->customer;
        } elseif ($x == 1) {
          $o =& $order->billing;
        }

        //Fill out the order object
        $o['firstname'] = $firstname;
        $o['lastname'] = $lastname;
        $o['street_address'] = $bill_street_address;
        $o['suburb'] = $bill_suburb;
        $o['city'] = $bill_city;
        $o['postcode'] = $bill_postcode;
	$o['zone_id'] = (int)$bill_zone_id;
	$o['state'] = $bill_state;
        $o['country'] = array('id' => (int)$bill_country, 'title' => $cty['countries_name'], 'iso_code_2' => $cty['countries_iso_code_2'], 'iso_code_3' => $cty['countries_iso_code_3']);
        $o['country_id'] = (int)$bill_country;
        $o['format_id'] = tep_get_address_format_id($bill_country);
        $o['telephone'] = $telephone;
        $o['email_address'] = $email_address;
      }

      if ($order->content_type == 'virtual') {
        if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
//        $shipping = false;
        $sendto = false;
      }
      
      
      $country_query = tep_db_query("SELECT countries_name, countries_iso_code_2, countries_iso_code_3 FROM " . TABLE_COUNTRIES . " WHERE countries_id = '" . (int)$ship_country . "'");
      $cty = tep_db_fetch_array($country_query);
      $order->delivery = array('firstname' => $firstname,
                              'lastname' => $lastname,
                              'company' => $ship_company,
                              'street_address' => $ship_street_address,
                              'suburb' => $ship_suburb,
                              'city' => $ship_city,
                              'postcode' => $ship_postcode,
                              'state' => $ship_state,
                              'zone_id' => $ship_zone_id,
                              'country' => array('id' => $cty['countries_id'], 'title' => $cty['countries_name'], 'iso_code_2' => $cty['countries_iso_code_2'], 'iso_code_3' => $cty['countries_iso_code_3']),
                              'country_id' => $ship_country,
                              'format_id' => tep_get_address_format_id($ship_country));  



      $order_totals = $order_total_modules->process();


//      if ( (isset($_POST['shipping'])) && (strpos($_POST['shipping'], '_')) ) {
//        $shipping = $_POST['shipping'];
        
//        list($module, $method) = explode('_', $shipping);
//        list($module, $method) = explode('_', $_POST['shipping']);
//	$shipping=$shipping_options[$module][$method];
//        if ( is_object($$module) || ($shipping == 'free_free') ) {
//          if ($shipping == 'free_free') {
//            $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
//            $quote[0]['methods'][0]['cost'] = '0';
//          } else {
//            $quote = $shipping_modules->quote($method, $module);
//          }
          
//          if (isset($quote['error'])) {
//            tep_session_unregister('shipping');
//            $pns_errors[] = $quote['error'];
//          } else {
//            if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
//              $shipping = array('id' => $shipping,
//              'title' => (($free_shipping == true) ? $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
//              'cost' => $quote[0]['methods'][0]['cost']);
//              $order->info['shipping_method'] = $shipping['title'];
//              $order->info['shipping_cost'] = $shipping['cost'];
//            }
//          }
//        } else {
//          tep_session_unregister('shipping');
//        }
//      }
      //$order->info['shipping_method'] = 'NULL';
      //$order->info['shipping_cost'] = '0';
      //die('<pre>' . print_r($_POST) . '<br><br>' . print_r($order) . '</pre>');
      $$payment->before_process();

      $sql_data_array = array('customers_id' => $customer_id,
                              'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                              'customers_company' => $company,
                              'customers_street_address' => $order->customer['street_address'],
                              'customers_suburb' => $order->customer['suburb'],
                              'customers_city' => $order->customer['city'],
                              'customers_postcode' => $order->customer['postcode'], 
                              'customers_state' => $order->customer['state'], 
                              'customers_country' => $order->customer['country']['title'], 
                              'customers_telephone' => $order->customer['telephone'], 
                              'customers_email_address' => $order->customer['email_address'],
                              'customers_address_format_id' => $order->customer['format_id'], 
                              'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'], 
                              'delivery_company' => $order->delivery['company'],
                              'delivery_street_address' => $order->delivery['street_address'], 
                              'delivery_suburb' => $order->delivery['suburb'], 
                              'delivery_city' => $order->delivery['city'], 
                              'delivery_postcode' => $order->delivery['postcode'], 
                              'delivery_state' => $order->delivery['state'], 
                              'delivery_country' => $order->delivery['country']['title'], 
                              'delivery_address_format_id' => $order->delivery['format_id'], 
                              'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
                              'billing_company' => $order->billing['company'],
                              'billing_street_address' => $order->billing['street_address'], 
                              'billing_suburb' => $order->billing['suburb'], 
                              'billing_city' => $order->billing['city'], 
                              'billing_postcode' => $order->billing['postcode'], 
                              'billing_state' => $order->billing['state'], 
                              'billing_country' => $order->billing['country']['title'], 
                              'billing_address_format_id' => $order->billing['format_id'], 
                              'payment_method' => $order->info['payment_method'], 
                              'cc_type' => $order->info['cc_type'], 
                              'cc_owner' => $order->info['cc_owner'], 
                              'cc_number' => $order->info['cc_number'], 
                              'cc_expires' => $order->info['cc_expires'],
			      'cc_cvv2' => $order->info['cc_cvv2'], 
                              'date_purchased' => 'now()', 
                              'orders_status' => $order->info['order_status'], 
                              'currency' => $order->info['currency'], 
                              'currency_value' => $order->info['currency_value']);
      tep_db_perform(TABLE_ORDERS, $sql_data_array);
      $insert_id = tep_db_insert_id();
      
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
      }

      $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
      $sql_data_array = array('orders_id' => $insert_id, 
                              'orders_status_id' => $order->info['order_status'], 
                              'date_added' => 'now()', 
                              'customer_notified' => $customer_notification,
                              'comments' => $order->info['comments']);
      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

// initialized for the email confirmation
      $products_ordered = '';
      $subtotal = 0;
      $total_tax = 0;

      for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
// Stock Update - Joao Correia
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
	      foreach ($order->products[$i]['attributes'] AS $attr_data) {
	        if ($attr_data['products_id']) {
                  tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity - '" . $order->products[$i]['qty'] . "', `last_stock_change` = NOW() where products_id = '" . $attr_data['products_id'] . "'");
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

		// # Update products_ordered
        tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

		// # Retrieve current-day product costing from the products table and add to orders products.
		// # important to keep historical pricing / costs for inventory since this can fluctuate with time.

		$cost = tep_db_query("SELECT products_price_myself AS cost FROM ".TABLE_PRODUCTS." WHERE products_id = '".tep_get_prid($order->products[$i]['id'])."'");
		$cost_price = tep_db_fetch_array($cost);

        $sql_data_array = array('orders_id' => $insert_id, 
                                'products_id' => tep_get_prid($order->products[$i]['id']), 
                                'products_model' => $order->products[$i]['model'], 
                                'products_name' => $order->products[$i]['name'], 
                                'products_price' => $order->products[$i]['price'], 
                                'cost_price' => $cost_price['cost'], 
                                'final_price' => $order->products[$i]['final_price'], 
                                'products_tax' => $order->products[$i]['tax'], 
                                'products_quantity' => $order->products[$i]['qty']);
        tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
        $order_products_id = tep_db_insert_id();
	$order_total_modules->update_credit_account($i);// CCGV
//------insert customer choosen option to order--------
        $attributes_exist = '0';
        $products_ordered_attributes = '';
        if (isset($order->products[$i]['attributes'])) {
          $attributes_exist = '1';
          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
            if (DOWNLOAD_ENABLED == 'true') {
              $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                                   left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                    on pa.products_attributes_id=pad.products_attributes_id
                                   where pa.products_id = '" . $order->products[$i]['id'] . "' 
                                    and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' 
                                    and pa.options_id = popt.products_options_id 
                                    and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' 
                                    and pa.options_values_id = poval.products_options_values_id 
                                    and popt.language_id = '" . $languages_id . "' 
                                    and poval.language_id = '" . $languages_id . "'";
              $attributes = tep_db_query($attributes_query);
            } else {
              $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
            }
            $attributes_values = tep_db_fetch_array($attributes);

            $sql_data_array = array('orders_id' => $insert_id, 
                                    'orders_products_id' => $order_products_id, 
                                    'products_options' => $attributes_values['products_options_name'],
                                    'products_options_values' => $attributes_values['products_options_values_name'], 
                                    'options_values_price' => $attributes_values['options_values_price'], 
                                    'price_prefix' => $attributes_values['price_prefix']);
            tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

            if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
              $sql_data_array = array('orders_id' => $insert_id, 
                                      'orders_products_id' => $order_products_id, 
                                      'orders_products_filename' => $attributes_values['products_attributes_filename'], 
                                      'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
                                      'download_count' => $attributes_values['products_attributes_maxcount']);
              tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
            }
            $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
          }
        }
//------insert customer choosen option eof ----
        $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
        $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
        $total_cost += $total_products_price;

        $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
      }
      $order_total_modules->apply_credit();// CCGV
// lets start with the email confirmation


// Shipping Info - copied from checkout_process.php - by MegaJim

$ship = explode('_', $shipping['id']);
$ship[0] = trim($ship[0]);
$ship[1] = trim($ship[1]);
//if ($ship[0]=='free') $ship=explode('_', FREE_SHIPPING_METHOD);
if ($ship[0]=='free') $ship=Array('usps','Parcel Post');

//Shipping Information Arrays
$ship_info = array();
$ship_info['usps'] = array('name' => 'USPS', 'track_url' => 'http://www.usps.com/shipping/trackandconfirm.htm', 'track_name' => 'Delivery Confirmation Number');
$ship_info['ups'] = array('name' => 'UPS', 'track_url' => 'http://www.ups.com/WebTracking/track?loc=en_US', 'track_name' => 'Tracking Label Number');
$ship_info['fedex1'] = array('name' => 'FedEx', 'track_url' => 'http://www.fedex.com/Tracking', 'track_name' => 'Tracking Number');

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

foreach ($ship_info[$ship[0]]['timetable'] as $service => $eta) {
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
 $ot_label_html[]='<tr><td>'.$ot['title'].'</td><td>'.$ot['text'].'</td></tr>';
}


// Prepare Template Vars - by MegaJim
     if (USE_EMAIL_NOW=='Enable') {
	
     $tpl=Array();
	$tpl['config']=Array(
	    store_name => STORE_NAME,
	    store_owner_email_address => STORE_OWNER_EMAIL_ADDRESS,
	    http_server => HTTP_SERVER,
	);
	$tpl['link']=Array(
	    account_history => tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false),
	    account => tep_href_link('account.php'),
	    tell_a_friend => tep_href_link('tell_a_friend.php'),
	);
	$tpl['order_id']=$insert_id;
	$tpl['info']=$order->info;
	$tpl['customer']=$order->customer;
	$tpl['customer']['password']=isset($_SESSION['customer_password'])?$_SESSION['customer_password']:$HTTP_POST_VARS['password'];
	$tpl['date']=strftime(DATE_FORMAT_LONG);
	$tpl['address']=Array(
	    shipping => Array(text => tep_address_format($order->delivery['format_id'],$order->delivery,0,'',"\n"), html => tep_address_format($order->delivery['format_id'],$order->delivery,1,'',"\n")),
	    billing => Array(text => tep_address_format($order->billing['format_id'],$order->billing,0,'',"\n"), html => tep_address_format($order->billing['format_id'],$order->billing,1,'',"\n")),
	);
	$tpl['products_ordered']=$products_ordered;
	$payment_class = $$payment;
	$tpl['payment']=Array(
	    title => $payment_class->title,
	    subtotal => '$' . number_format($order->info['subtotal'], 2),
	    total => '$' . number_format($order->info['total'], 2),
	    order_total_label => Array(text => join("\n",$ot_label_text), html => "<table>\n".join("\n",$ot_label_html)."</table>"),
	    cc_type => (isset($order->info['cc_type'])?$order->info['cc_type']:''),
	    cc_number => (isset($order->info['cc_number'])?str_repeat('*',max(strlen($order->info['cc_number'])-4,0)).substr($order->info['cc_number'],-4,4):''),
	);
	$tpl['ship_info']=$ship_info[$ship[0]];
	$tpl['ship_info']['service_eta']=$service_eta;

	
	require_once(DIR_WS_FUNCTIONS . 'email_now.php');
	email_now('checkout_confirm',$tpl,(SEND_EXTRA_ORDER_EMAILS_TO!=''?split(',',SEND_EXTRA_ORDER_EMAILS_TO):NULL));

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
      if (is_object($$payment)) {
        $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . 
                        EMAIL_SEPARATOR . "\n";
        $payment_class = $$payment;
        $email_order .= $payment_class->title . "\n\n";
        if ($payment_class->email_footer) { 
          $email_order .= $payment_class->email_footer . "\n\n";
        }
      }

      tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT_1 . ' ' . $insert_id . ' ' .EMAIL_TEXT_SUBJECT_2 , $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

      if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
        tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }

    }



// remove items from wishlist if customer purchased them $wishList->clear();
$wishList->clear();

  // Include OSC-AFFILIATE 
  require(DIR_FS_CATALOG . 'includes/affiliate_checkout_process.php');

// load the after_process function from the payment modules
      $$payment->after_process();

      $cart->reset(true);

// unregister session variables used during checkout
      tep_session_unregister('sendto');
      tep_session_unregister('billto');
      tep_session_unregister('shipping');
      tep_session_unregister('payment');
      tep_session_unregister('comments');
  if(tep_session_is_registered('credit_covers')) tep_session_unregister('credit_covers');// CCGV
  $order_total_modules->clear_posts();// CCGV

//      tep_redirect(tep_href_link(sprintf($return_link,$insert_id), '', 'SSL'));
      tep_redirect(HTTPS_SERVER.'/'.sprintf($return_link,$insert_id));

    }
  } else {
// By MegaJim
    if (!isset($order)) $order = new order;
    if ($order->delivery['zone_id'] > 0) {
      $ship_zone_id = $order->delivery['zone_id'];
    } elseif ($order->delivery['state'] != '' && is_numeric($order->delivery['country']['id'])) {
      $ship_zone_id = get_zone($order->delivery['country']['id'], $order->delivery['state']);
    }
    if ($order->billing['zone_id'] > 0) {
      $bill_zone_id = $order->billing['zone_id'];
    } elseif ($order->billing['state'] != '' && is_numeric($order->billing['country']['id'])) {
      $bill_zone_id = get_zone($order->billing['country']['id'], $order->billing['state']);
    }
    $payment_modules = new payment;
  }
  

  if (!tep_session_is_registered('comments')) tep_session_register('comments');

//  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));

  $customer=Array();
  if (tep_session_is_registered('customer_id')) {
    $customer_query = tep_db_query("SELECT c.*, ab.* FROM " . TABLE_CUSTOMERS . " c LEFT JOIN " . TABLE_ADDRESS_BOOK . " ab ON (c.customers_default_address_id = ab.address_book_id) WHERE c.customers_id = '" . (int)$_SESSION['customer_id'] . "'");
    $customer = tep_db_fetch_array($customer_query);
  }
  
// By MegaJim - Feed from preview
//  foreach (Array(c=>'entry_country_id',s=>'entry_zone_id',p=>'entry_postcode') AS $post => $cst) {
//   if (isset($HTTP_GET_VARS[$post])) $customer[$cst]=$HTTP_GET_VARS[$post];
//  }
  
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script type="text/javascript" language="javascript" src="<?=DIR_WS_CATALOG?>includes/prototype.lite.js"></script>
<script language="javascript"><!--
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

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

function toggleBilling() {
  if ($('billing_address').style.display == 'none') {
    $('billing_address').style.display = 'block';
  } else {
    $('billing_address').style.display = 'none';
  }
}

function reloadShipping(ignoreStates) {
  reloadOT('');
  if ($('ship_postcode').value=='') {
    $('shipping_options').innerHTML= '<P>Please select your state and postal code</P>';
  } else {
    $('shipping_options').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Loading shipping costs, please wait...<br><img src="images/loading_bar.gif"></td></tr></table>';
    new ajax ('<?=HTTPS_SERVER;?>/shipping_options.php?zip='+document.checkout.ship_postcode.value+'&cnty='+document.checkout.ship_country.value+'&zone='+document.checkout.ship_state.value+'&weight=<?=$multi_weight?>&free=<?=$free_shipping?>', {method: 'get', update: $('shipping_options')});
  }
}

function applyCoupon(code) {
  $('coupon_code').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Verifying and applying coupon, please wait...<br><img src="images/loading_bar.gif"></td></tr></table>';
  new ajax ('<?=HTTPS_SERVER;?>/checkout_ot.php', {postBody: 'gv_redeem_code='+code, update: $('coupon_code')});
}

//function resetOT() {
//    clearTimeout(otTimer);
//  otTimer = setTimeout("reloadOT(document.checkout.shipping.value)", 500);
//}
//
//function loadZones(section) {
//  var section_country = $(section+'_country');
//  var section_state = $(section+'_state');
//  $(section+'_state').innerHTML = '<img src="images/loading_bar.gif">';
//  new ajax('<?=HTTPS_SERVER;?>/state_dropdown.php?country='+$(section+'_country').value+'&sec='+section+'&bill=<?=(isset($bill_zone_id) ? $bill_zone_id : $customer['entry_zone_id']);?>&ship=<?=(isset($ship_zone_id) ? $ship_zone_id : $customer['entry_zone_id']);?>&d='+document.checkout.ship_state.value, {method: 'get', update: $(section+'_state')});
//}
//
//var otTimer = '';

function reloadOT(v) {
  if (v==null) v=document.checkout.shipping.value;
  if (v=='') {
    $('order_total').innerHTML = 'No shipping selected';
    return;
  }
  zone=document.checkout.ship_state.value;
  $('order_total').innerHTML = '<table border="0" height="65"><tr><td align="right" valign="middle"><img src="images/loading.gif"></td></tr></table>';
  new ajax ('<?=HTTPS_SERVER;?>/order_total.php?s='+v+'&z='+zone+'&c='+document.checkout.ship_country.value, {method: 'get', update: $('order_total')});
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

var reloadStateBusy=Array();

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
  $(sec+'_state').innerHTML = '<img src="images/loading_bar.gif">';
  new ajax('<?=HTTPS_SERVER;?>/state_dropdown.php?country='+section_country+'&sec='+sec+'&postal='+section_postcode+'&d='+section_state, {method: 'get', update: $(sec+'_state')});
}

function reloadPostal(sec) {
  document.checkout[sec+'_postcode'].value = '';
  reloadState(sec);
}

function processOrder(f) {
  if (!check_form(f)) { return; }
  f.submit();
  var oForm = f.elements;
  for(i=0; i < oForm.length; i++) { 
    if (oForm[i].type=="select-one") {
      oForm[i].disabled=true;
      oForm[i].style.backgroundColor="#999999";
    }
  }
  window.scroll(0,0);
  $('overlay').style.display = "block";
  $('overlay').style.height = "2000px";
  $('dialog_box').style.width = "300px";
  $('dialog_box').style.height = "100px";
  $('dialog_box').style.top = ((document.body.clientHeight / 2) - 50)+"px";
  $('dialog_box').style.left = ((document.body.clientWidth / 2) - 150)+"px";
  $('dialog_box').style.display = "block";
  $('dialog_box').className = "dialog_process";
}
//--></script>
<?php require_once('../includes/form_check_checkout_1.js.php'); ?>
<?php if (is_object($payment_modules)) echo $payment_modules->javascript_validation(); ?>
<?php require_once('../includes/form_check_checkout_2.js.php'); ?>
</head>
<body>
<?php require_once(DIR_WS_INCLUDES . 'header.php'); ?>
<table width="535" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top">
      <table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
        <?php require_once(DIR_WS_INCLUDES . 'column_left.php'); ?>
      </table>
    </td>
    <td width="100%" valign="top">
      <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
<?
/*
echo '<pre>';
print_r($order);
echo '</pre>';
*/
?>
        <tr>
          <td><table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td valign="top" style="width:535px; height:20px;"><div style="padding-top:15px; font:15px Arial; padding-left:15px; font-weight:bold; color:#FF0000;">Enter your payment information and confirm your order ...</div></td>
          </tr>
        </table></td>
        </tr>
<?php
  if ($_GET['db'] == '1') { echo '<pre>'; print_r($order); echo '</pre>'; }
  if ($ec_enabled) {
    if (tep_session_is_registered('paypal_error')) {
      $checkout_login = true;
      $messageStack->add('checkout', $_SESSION['paypal_error']);
      tep_session_unregister('paypal_error');
    }
  }
  if (1) {
?>
        <tr>
          <td><?php echo $messageStack->output('checkout'); ?></td>
        </tr>
        <tr>
          <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
        </tr>
<?php
  }
  if ($show_cart) { ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" style="background-color:#EBEBEB; padding:3px; padding-left:10px; height:23px;"><b>&#149;Shopping Cart Content</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
<? include(DIR_FS_CATALOG_MODULES.'express_cart.php') ?>
        </td>
      </tr>
<? }

  if ($show_find_members) {
?>
      <? echo tep_draw_form('find_members', tep_href_link(FILENAME_CHECKOUT, 'action=login&'.tep_get_all_get_params(Array('action')), 'SSL'), 'POST', '', 'style="margin:0;"'); ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" style="background-color:#EBEBEB; padding:3px; padding-left:10px; height:23px;"><b>&#149; Find Member</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
         <? include(DIR_FS_CATALOG_MODULES.'find_members.php') ?>
         </td>
      </tr>
      </form>
<?
  } else if (!tep_session_is_registered('customer_id')) {
?>
      <tr>
        <td style="height:20px"></td>
      </tr>
<tr>
        <td style="background-color:#000000; height:1px"></td>
      </tr>


      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" style="background-color:#EBEBEB; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TEXT_RETURNING_CUSTOMER; ?></b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
	 <div id="login_form"><p><a href="/login.php" onClick="showLoginForm(); return false;">Returning customers please click here</a></p></div>
	 <script language="JavaScript"><!--
  function showLoginForm() {
    $('login_form').innerHTML=
'          <? echo tep_draw_form('login', tep_href_link(FILENAME_CHECKOUT, 'action=login&'.tep_get_all_get_params(Array('action')), 'SSL'), 'POST', '', 'style="margin:0;"'); ?>\n'+
'          <table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">\n'+
'            <tr class="infoBoxContents">\n'+
'              <td style="padding-top:10px;">\n'+
'                <table width="100%" border="0" align="center" cellpadding="5" cellspacing="5">\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b><?php echo ENTRY_EMAIL_ADDRESS; ?></b></td>\n'+
'                    <td class="main"><?php echo tep_draw_input_field('email_address', '', 'style="width:150px; height:20px"'); ?></td>\n'+
'                  </tr>\n'+
'                  <tr>\n'+
'                    <td class="main" align="right"><b><?php echo ENTRY_PASSWORD; ?></b></td>\n'+
'                    <td class="main"><?php echo tep_draw_password_field('password', '', 'style="width:150px; height:20px"'); ?></td>\n'+
'                  </tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" class="smallText" align="center"><?php echo '<a href="' . tep_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></td>\n'+
'                  </tr>\n'+
'                  <tr>\n'+
'                    <td colspan="2" align="center" style="padding-right:5px;"><?php echo ($HTTP_GET_VARS['co'] == '1' ? tep_draw_hidden_field('co', '1') : '') . tep_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN); ?></td>\n'+
'                  </tr>\n'+
'                </table>\n'+
'              </td>\n'+
'            </tr>\n'+
'          </table>\n'+
'          </form>\n';
  }
  //-->
  </script>
        </td>
      </tr>

<?
//    $customer = array();
  }
?>
      <tr>
        <td><?php echo tep_draw_form('checkout', tep_href_link(FILENAME_CHECKOUT, tep_get_all_get_params(Array('action')), 'SSL'), 'post', 'onSubmit="return check_form(checkout);"') . tep_draw_hidden_field('action', 'checkout'); ?></td>
      </tr>

<?
  if ($display_mode=='admin') {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" style="background-color:#EBEBEB; padding:3px; padding-left:10px; height:23px;"><b>&#149; Admin Controls</b></td>
           </tr>
        </table></td>
      </tr>
      <tr>
        <td>
         <? include(DIR_FS_CATALOG_MODULES.'checkout_admin_controls.php') ?>
         </td>
      </tr>
<?
  }
?>

      <tr>
        <td style="background-color:#000000;height:1px"></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="main" style="background-color:#EbEbEb; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo (!tep_session_is_registered('customer_id') ? TEXT_NEW_CUSTOMER : TEXT_RETURNING_CUSTOMER); ?></b></td>
           <td class="inputRequirement" style="background-color:#EbEbEb;; padding:3px; font-weight:bold; color:#FF0000; padding-right:15px;" align="right"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td class="main" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_PERSONAL; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="545" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="250"><?php echo ENTRY_FIRST_NAME . '<br>' . tep_draw_input_field('firstname', $customer['customers_firstname'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></td>
                    <td width="245"><?php echo ENTRY_LAST_NAME . '<br>' . tep_draw_input_field('lastname', $customer['customers_lastname'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></td>
                  </tr>
                  <tr valign="bottom">
                    <td height="45"><?php echo ENTRY_TELEPHONE_NUMBER . '<br>' . tep_draw_input_field('telephone', $customer['customers_telephone'], 'style="width:100px" maxlength=32') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
                    <td height="45"><?php echo ENTRY_EMAIL_ADDRESS . '<br>' . tep_draw_input_field('email_address', $customer['customers_email_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': ''); ?></td>
                  </tr>
                  <tr valign="bottom">
                    <td height="45"><?php echo ENTRY_COMPANY . '<br>' . tep_draw_input_field('company', $customer['customers_company'], 'style="width:100px"') ?></td>
                    <td height="45">&nbsp;</td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td class="main" style="padding:7px; padding-top:20px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo TABLE_HEADING_SHIPPING_ADDRESS; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;">
                  <table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="250" class="main" style="width:240px"><?php echo ENTRY_STREET_ADDRESS . '<br>' . tep_draw_input_field('ship_street_address', $customer['entry_street_address'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
                      <td width="245" class="main"><?php echo ENTRY_SUBURB . '<br>' . tep_draw_input_field('ship_suburb', $customer['entry_suburb'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
                    </tr>
                    <tr valign="bottom">
                      <td height="45" class="main"><?php echo ENTRY_CITY . '<br>' . tep_draw_input_field('ship_city', $customer['entry_city'], 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
                      <td height="45" class="main"><table><tr><td colspan="2"><?=ENTRY_STATE?></td></tr><tr><td><div id="ship_state" style="display:block;position:relative;z-index: 1"></div><input type="hidden" name="ship_state" value="<?=$customer['entry_zone_id']?>"></td><td><?php if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT . '</span>'; ?></td></tr></table></td>
                    </tr>
                    <tr valign="bottom">
                      <td colspan="2">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="200" height="45"><?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('ship_postcode', $customer['entry_postcode'], 'style="width:100px" maxlength=10 id="ship_postcode" onBlur="reloadState(\'ship\')"') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
                            <td width="400" height="45">&nbsp;<?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('ship_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : '223'), 'id="ship_country" onChange="reloadPostal(\'ship\')"') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td class="main" style="padding:7px; padding-top:20px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo TABLE_HEADING_BILLING_ADDRESS; ?><span style="padding-left: 50px; font-size: 11px; font-weight: normal" class="main"><input type="checkbox" name="bill_same" value="1" CHECKED onClick="toggleBilling();"><? echo TEXT_BILLING_SAME; ?></span></td>
              </tr>
              <tr>
                <td style="padding-top:10px;">
                  <div id="billing_address" style="display:none">
                    <table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
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
                              <td width="200" height="45"><?php echo ENTRY_POST_CODE . '<br>' . tep_draw_input_field('bill_postcode', $customer['entry_postcode'], 'style="width:100px" maxlength=10 onChange="reloadState(\'bill\')"') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
                              <td width="400" height="45">&nbsp;<?php echo ENTRY_COUNTRY . '<br>' . tep_get_country_list('bill_country', (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : '223'), 'id="bill_country" onChange="reloadPostal(\'bill\')"') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </div>
                </td>
              </tr>
	      <?
	       if (USE_COUPONS=='Enable') {
	      ?>
              <tr>
                <td class="main" style="padding:7px; padding-top:20px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Coupon Code</td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="545" valign="top" class="main"><div id="coupon_code">Coupon Code: <?=tep_draw_input_field('gv_redeem_code', '', 'id="gv_redeem_code"') . '<input type="image" name="submit_redeem" onClick="applyCoupon($(\'gv_redeem_code\').value)" src="' . DIR_FS_CATALOG_LANGUAGES . $language . '/images/buttons/button_redeem.gif" border="0" alt="' . IMAGE_REDEEM_VOUCHER . '" title = "' . IMAGE_REDEEM_VOUCHER . '">'; ?></div><noscript>Please enable javascript to checkout.</noscript></td>
                  </tr>
                </table>
</td>
              </tr>

                  <?
		 }
		  
  if (!tep_session_is_registered('customer_id')) {
?>
              <tr>
                <td class="main" style="padding:7px; padding-top:20px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_OPTIONS; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="545" valign="top" class="main">Opt-in for our <?php echo ENTRY_NEWSLETTER; ?>? &nbsp; <?php echo tep_draw_checkbox_field('newsletter', '1') . '&nbsp;' . (tep_not_null(ENTRY_NEWSLETTER_TEXT) ? '<span class="inputRequirement">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); ?></td>
                  </tr>
                </table>
</td>
              </tr>
              <tr>
                <td class="main" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; <?php echo CATEGORY_PASSWORD; ?></td>
              </tr>
              <tr>
                <td style="padding-top:10px;"><table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr valign="top">
                    <td width="250" height="45" class="main" style="width:240px"><?php echo ENTRY_PASSWORD . '<br>' . tep_draw_password_field('password', (isset($HTTP_POST_VARS['password'])?$HTTP_POST_VARS['password']:''), 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>': ''); ?></td>
                    <td width="245" height="45" class="main"><?php echo ENTRY_PASSWORD_CONFIRMATION . '<br>' . tep_draw_password_field('confirmation', (isset($HTTP_POST_VARS['confirmation'])?$HTTP_POST_VARS['confirmation']:''), 'style="width:200px" maxlength=255') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>': ''); ?></td>
                  </tr>
                </table></td>
              </tr>
                  <?
  }
?>
              <tr>
                <td class="main" style="padding:7px; padding-top:15px; font-weight: bold; border-bottom: 1px solid #333333">&#149; Additional Information</td>
              </tr>
<!-- //rmh referral start -->
<?php
// MegaJim - Fuck this box!
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
        <td class="main"><b><?php echo CATEGORY_SOURCE; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><?php echo ENTRY_SOURCE; ?></td>
                <td class="main"><?php echo tep_get_source_list('source', true, $source_id) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_TEXT . '</span>': ''); ?></td> 
              </tr>
              <tr>
                <td class="main"><?php echo ENTRY_SOURCE_OTHER; ?></td>
                <td class="main"><?php echo tep_draw_input_field('source_other', (tep_not_null($referral_id) ? $referral_id : '')) . '&nbsp;' . (tep_not_null(ENTRY_SOURCE_OTHER_TEXT) ? '<span class="inputRequirement">' . ENTRY_SOURCE_OTHER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?
  }
  
  } else {
    echo tep_draw_hidden_field('source',6666);
  }
?>
<!-- //rmh referral end -->
	      <tr>
	        <td class="main"><b><?php echo CATEGORY_COMMENTS; ?></b></td>
	      </tr>
              <tr>
                <td style="padding-top:10px;"><table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
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
            <td class="main" style="background-color:#EbEbEb; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TABLE_HEADING_SHIPPING_METHOD; ?></b></td>
            <td align="right" class="inputRequirement" style="background-color:#EbEbEb; padding:3px; font-weight:bold; color:#ff0000; padding-right:15px;"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
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
            <td class="main" style="background-color:#EbEbEb; padding:3px; padding-left:10px; height:23px;"><b>&#149; <?php echo TABLE_HEADING_PAYMENT_METHOD; ?></b></td>
            <td align="right" class="inputRequirement" style="background-color:#EbEbEb; padding:3px; font-weight:bold; color:#ff0000; padding-right:15px;"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" align="center" cellpadding="2" cellspacing="2">
<?php
  $selection = $payment_modules->selection();

  $radio_buttons = 0;
  for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
    if ( ($selection[$i]['id'] == $payment) || ($n == 1) ) {
      echo '                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, \'p\', ' . $radio_buttons . ')">' . "\n";
    } else {
      echo '                  <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, \'p\', ' . $radio_buttons . ')">' . "\n";
    }
?>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '1', '1'); ?></td>
                    <td class="main" align="right" style="width:20px">
<?php
    if (sizeof($selection) > 1) {
      echo tep_draw_radio_field('payment', $selection[$i]['id'], false,'id="payment_'.$selection[$i]['id'].'"');
    } else {
      echo tep_draw_hidden_field('payment', $selection[$i]['id'], 'id="payment_'.$selection[$i]['id'].'"');
    }
?>
                    </td>
                    <td class="main" colspan="3" width="100%"><b><?php echo $selection[$i]['module']; ?></b></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
<?php
    if (isset($selection[$i]['error'])) {
?>
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" colspan="4"><?php echo $selection[$i]['error']; ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
<?php
    } elseif (isset($selection[$i]['fields']) && is_array($selection[$i]['fields'])) {
?>
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td colspan="4"><table border="0" cellspacing="0" cellpadding="2">
<?php
      for ($j=0, $n2=sizeof($selection[$i]['fields']); $j<$n2; $j++) {
?>
                      <tr>
                        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                        <td class="main"><?php echo $selection[$i]['fields'][$j]['title']; ?></td>
                        <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                        <td class="main"><?php echo $selection[$i]['fields'][$j]['field']; ?></td>
                        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                      </tr>
<?php
      }
?>
                    </table></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
<?php
    }
?>
                </table></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
    $radio_buttons++;
  }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '15'); ?></td>
          </tr>
	  <tr>
	    <td align="right"><div id="order_total"></div></td>
	  </tr>
          <script language="javascript">reloadState('ship'); reloadState('bill');</script>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '15'); ?></td>
          </tr>
          <tr>
            <td><table border="0" width="100%" cellspacing="1" cellpadding="2"  class="infoBox">
              <tr class="infoBoxContents">
                <td align="right"><div id="confirm_button"><a href="javascript: void(0)" onClick="processOrder(document.checkout);return false"><?php echo tep_image('images/icons/button_confirm_order.gif', IMAGE_BUTTON_CONFIRM_ORDER, 'border="0"'); ?></a></div></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '15'); ?></td>
          </tr>
    </table></form></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php include(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->
<!-- footer //-->
<?php include(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require_once(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
