<?php

  class usaepay {
    var $code, $title, $description, $enabled;

// class constructor
    function intensepay() {
      global $order;

      $this->code = 'intensepay';
      $this->title = MODULE_PAYMENT_INTENSEPAY_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_INTENSEPAY_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_INTENSEPAY_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_INTENSEPAY_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_INTENSEPAY_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_INTENSEPAY_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
    }

// class methods
    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_INTENSEPAY_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_CC_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      $js = '  if (payment_value == "' . $this->code . '") {' . "\n" .
            '    var cc_owner = document.checkout.intensepay_cc_name.value;' . "\n" .
            '    var cc_number = document.checkout.intensepay_cc_num.value;' . "\n" .
            '    if (cc_owner == "" || cc_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
            '      error_message = error_message + "' . MODULE_PAYMENT_CC_TEXT_JS_CC_OWNER . '";' . "\n" .
            '      error = 1;' . "\n" .
            '    }' . "\n" .
            '    if (cc_number == "" || cc_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
            '      error_message = error_message + "' . MODULE_PAYMENT_CC_TEXT_JS_CC_NUMBER . '";' . "\n" .
            '      error = 1;' . "\n" .
            '    }' . "\n" .
            '  }' . "\n";

      return $js;
    }

    function selection() {
      global $order;

      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
      }

      $today = getdate(); 
      for ($i=$today['year']; $i < $today['year']+10; $i++) {
        $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }

      $selection = array('id' => $this->code,
                         'module' => $this->title,
                         'fields' => array(array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_OWNER,
                                                 'field' => tep_draw_input_field('intensepay_cc_name', $order->billing['firstname'] . ' ' . $order->billing['lastname'])),
                                           array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_NUMBER,
                                                 'field' => tep_draw_input_field('intensepay_cc_num')),
                                           array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_EXPIRES,
                                                 'field' => tep_draw_pull_down_menu('intensepay_cc_exp_mon', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('intensepay_cc_exp_year', $expires_year)),
					   array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_CVV2,
                                                 'field' => tep_draw_input_field('intensepay_cc_cvv2', '', 'style="width:40px" maxlength="4"'))));
      return $selection;
    }

    function pre_confirmation_check() {
      global $HTTP_POST_VARS;

      include(DIR_WS_CLASSES . 'cc_validation.php');

      $cc_validation = new cc_validation();
      $result = $cc_validation->validate($HTTP_POST_VARS['cc_number'], $HTTP_POST_VARS['cc_expires_month'], $HTTP_POST_VARS['cc_expires_year']);

      $error = '';
      switch ($result) {
        case -1:
          $error = sprintf(TEXT_CCVAL_ERROR_UNKNOWN_CARD, substr($cc_validation->cc_number, 0, 4));
          break;
        case -2:
        case -3:
        case -4:
          $error = TEXT_CCVAL_ERROR_INVALID_DATE;
          break;
        case false:
          $error = TEXT_CCVAL_ERROR_INVALID_NUMBER;
          break;
      }

      if ( ($result == false) || ($result < 1) ) {
        $payment_error_return = 'payment_error=' . $this->code . '&error=' . urlencode($error) . '&cc_owner=' . urlencode($HTTP_POST_VARS['cc_owner']) . '&cc_expires_month=' . $HTTP_POST_VARS['cc_expires_month'] . '&cc_expires_year=' . $HTTP_POST_VARS['cc_expires_year'];

        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
      }

      $this->cc_card_type = $cc_validation->cc_type;
      $this->cc_card_number = $cc_validation->cc_number;
    }

    function confirmation() {
      global $HTTP_POST_VARS;

      $confirmation = array('title' => $this->title . ': ' . $this->cc_card_type,
                            'fields' => array(array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_OWNER,
                                                    'field' => $HTTP_POST_VARS['cc_owner']),
                                              array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_NUMBER,
                                                    'field' => substr($this->cc_card_number, 0, 4) . str_repeat('X', (strlen($this->cc_card_number) - 8)) . substr($this->cc_card_number, -4)),
					      array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_CVV2,
                                                    'field' => $_POST['cc_cvv2']),
                                              array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_EXPIRES,
                                                    'field' => strftime('%B, %Y', mktime(0,0,0,$HTTP_POST_VARS['cc_expires_month'], 1, '20' . $HTTP_POST_VARS['cc_expires_year'])))));

      return $confirmation;
    }

    function process_button() {
      global $HTTP_POST_VARS;

      $process_button_string = tep_draw_hidden_field('cc_owner', $HTTP_POST_VARS['cc_owner']) .
                               tep_draw_hidden_field('cc_expires', $HTTP_POST_VARS['cc_expires_month'] . $HTTP_POST_VARS['cc_expires_year']) .
                               tep_draw_hidden_field('cc_type', $this->cc_card_type) .
			       tep_draw_hidden_field('cc_cvv2', $_POST['cc_cvv2']) .
                               tep_draw_hidden_field('cc_number', $this->cc_card_number);

      return $process_button_string;
    }

    function before_process() {
      global $HTTP_POST_VARS, $order;

      require(DIR_FS_CATALOG_MODULES."payment/usaepay/usaepay.php");

      $tran=new umTransaction;

      $tran->key=MODULE_PAYMENT_INTENSEPAY_KEY;
      $tran->testmode=(MODULE_PAYMENT_INTENSEPAY_TEST=='True');
      $tran->card=$_POST['intensepay_cc_num'];		// card number, no dashes, no spaces
      $tran->exp=$_POST['intensepay_cc_exp_mon'].$_POST['intensepay_cc_exp_year'];			// expiration date 4 digits no /
      $tran->amount=$order->info['total'];			// charge amount in dollars (no international support yet)
      $tran->invoice="1234";   		// invoice number.  must be unique.
      $tran->cardholder=$_POST['intensepay_cc_name']; 	// name of card holder
      $tran->street=$order->billing['address1'];	// street address
      $tran->zip=$order->billing['postcode'];			// zip code
      $tran->description="Online Order - @ ".STORE_NAME;	// description of charge
      $tran->cvv2=$_POST['intensepay_cvv2'];			// cvv2 code	

      if ($tran->Process())
      {
	return true;
	echo "<b>Card approved</b><br>";
	echo "<b>Authcode:</b> " . $tran->authcode . "<br>";
	echo "<b>AVS Result:</b> " . $tran->avs_result . "<br>";
	echo "<b>Cvv2 Result:</b> " . $tran->cvv2_result . "<br>";
      } else {

	echo "<b>Card Declined</b> (" . $tran->result . ")<br>";
	echo "<b>Reason:</b> " . $tran->error . "<br>";	
	if($tran->curlerror) echo "<b>Curl Error:</b> " . $tran->curlerror . "<br>";	
      }		
    }

    function after_process() {
      global $insert_id;

      if ( (defined('MODULE_PAYMENT_CC_EMAIL')) && (tep_validate_email(MODULE_PAYMENT_CC_EMAIL)) ) {
        $message = 'Order #' . $insert_id . "\n\n" . 'Middle: ' . $this->cc_middle . "\n\n";
        
        tep_mail('', MODULE_PAYMENT_CC_EMAIL, 'Extra Order Info: #' . $insert_id, $message, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }
    }

    function get_error() {
      global $HTTP_GET_VARS;

      $error = array('title' => MODULE_PAYMENT_CC_TEXT_ERROR,
                     'error' => stripslashes(urldecode($HTTP_GET_VARS['error'])));

      return $error;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_INTENSEPAY_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      if (CORE_PERMISSION) {
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable IntensePay Module', 'MODULE_PAYMENT_INTENSEPAY_STATUS', 'True', 'Do you want to accept credit card payments via IntensePay?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('IntensePay Test Mode', 'MODULE_PAYMENT_INTENSEPAY_TEST', 'False', 'Do you want to run IntensePay in test mode?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
//        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Split Credit Card E-Mail Address', 'MODULE_PAYMENT_INTENSEPAY_EMAIL', '', 'If an e-mail address is entered, the middle digits of the credit card number will be sent to the e-mail address (the outside digits are stored in the database with the middle digits censored)', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_INTENSEPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0' , now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_INTENSEPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_INTENSEPAY_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CORE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Access Key', 'MODULE_PAYMENT_INTENSEPAY_KEY', '897asdfjha98ds6f76324hbmnBZc9769374ybndfs876', 'IntensePay Access Key', '6', '0', now())");
      }
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_id,configuration_key,configuration_value) SELECT configuration_id,configuration_key,configuration_value FROM ". TABLE_CORE_CONFIGURATION ." where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
      if (CORE_PERMISSION) tep_db_query("delete from " . TABLE_CORE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_INTENSEPAY_STATUS', 'MODULE_PAYMENT_INTENSEPAY_TEST', 'MODULE_PAYMENT_INTENSEPAY_EMAIL', 'MODULE_PAYMENT_INTENSEPAY_ZONE', 'MODULE_PAYMENT_INTENSEPAY_ORDER_STATUS_ID', 'MODULE_PAYMENT_INTENSEPAY_SORT_ORDER', 'MODULE_PAYMENT_INTENSEPAY_KEY');
    }
  }
?>
