<?php
  //include(DIR_WS_LANGUAGES . $language . '/modules/payment/' . MODULE_PAYMENT_STORED_CC_TRUE_MODULE . '.php');
  //include(DIR_WS_MODULES . 'payment/' . MODULE_PAYMENT_STORED_CC_TRUE_MODULE . '.php');

  class stored_cc {
    var $code, $title, $description, $enabled, $payment_modules;
    
// class constructor
    function stored_cc() {
      global $order, $customer_id;

      $this->code = 'stored_cc';
      $this->title = MODULE_PAYMENT_STORED_CC_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_STORED_CC_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_STORED_CC_SORT_ORDER;
      //$this->pmt = new payment(MODULE_PAYMENT_STORED_CC_TRUE_MODULE, true);
      
      $stored_exists = tep_db_query("SELECT customers_personal FROM customers_personal WHERE customers_id = '".(int)$customer_id."' LIMIT 1");
      if (tep_db_num_rows($stored_exists) > 0) {
        $this->enabled = ((MODULE_PAYMENT_STORED_CC_STATUS == 'True') ? true : false);
      } else {
        $this->enabled = false;
      }

      if ((int)MODULE_PAYMENT_STORED_CC_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_STORED_CC_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
    }

// class methods
    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_STORED_CC_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_STORED_CC_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->delivery['zone_id']) {
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
      return false;
    }

    function selection() {
      global $customer_id;
      include(DIR_WS_FUNCTIONS . 'encryption.php');
      
      $stored_exists = tep_db_query("SELECT customers_personal FROM customers_personal WHERE customers_id = '".(int)$customer_id."' LIMIT 1");
      $stored_num = tep_db_fetch_array($stored_exists);
      $stored_num = tep_cc_decrypt($stored_num['customers_personal']);
      $cc_array = explode('|', $stored_num);

      return array('id' => $this->code,
                   'module' => $this->title,
                   'fields' => array(array('title' => 'Use the credit card on file:',
                                           'field' => substr($cc_array[1], 0, 4) . str_repeat('X', (strlen($cc_array[1]) - 8)) . substr($cc_array[1], -4))                                           
                                          )
                  );
    }

    function pre_confirmation_check() {
      return true;
    }

    function confirmation() {
      global $customer_id;
      include(DIR_WS_FUNCTIONS . 'encryption.php');

      $stored_exists = tep_db_query("SELECT customers_personal FROM customers_personal WHERE customers_id = '".(int)$customer_id."' LIMIT 1");
      $stored_num = tep_db_fetch_array($stored_exists);
      $stored_num = tep_cc_decrypt($stored_num['customers_personal']);
      $cc_array = explode('|', $stored_num);
      
      $cust_name = tep_parse_name($cc_array[2]);
      
      $confirmation = array('title' => 'Credit Card',
                            'fields' => array(array('title' => 'Using the credit card on file:',
                                                    'field' => substr($cc_array[1], 0, 4) . str_repeat('X', 8) . substr($cc_array[1], -4)),
                                              array('title' => '',
                                                    'field' => tep_draw_hidden_field('payment', MODULE_PAYMENT_STORED_CC_TRUE_MODULE) . 
                                                               tep_draw_hidden_field(MODULE_PAYMENT_STORED_CC_FIELD_TYPE, $cc_array[0]) . 
                                                               tep_draw_hidden_field(MODULE_PAYMENT_STORED_CC_FIELD_NUMBER, $cc_array[1]) . 
                                                               tep_draw_hidden_field(MODULE_PAYMENT_STORED_CC_FIELD_FIRSTNAME, $cust_name[1]) . 
                                                               tep_draw_hidden_field(MODULE_PAYMENT_STORED_CC_FIELD_LASTNAME, $cust_name[0]) . 
                                                               tep_draw_hidden_field(MODULE_PAYMENT_STORED_CC_FIELD_EXPIRATION, $cc_array[3]) . 
                                                               tep_draw_hidden_field(MODULE_PAYMENT_STORED_CC_FIELD_CHECKCODE, $cc_array[4]))
                                                   )
                            );
                                           
      return $confirmation;
    }

    function process_button() {
      return false;
    }

    function before_process() {
      return false;
    }

    function after_process() {
      return false;
    }

    function get_error() {
      return false;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_STORED_CC_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Stored CC Module', 'MODULE_PAYMENT_STORED_CC_STATUS', 'True', 'Do you want to enable the credit card storage feature?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_STORED_CC_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_STORED_CC_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '3', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_STORED_CC_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '4', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Credit Card Module.', 'MODULE_PAYMENT_STORED_CC_TRUE_MODULE', '', 'What credit card module will you be using to charge stored cards? (Use the codeTitle name)', '6', '5', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CC Type Field', 'MODULE_PAYMENT_STORED_CC_FIELD_TYPE', 'cc_type', 'What\'s the field name for CC type?', '6', '6', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CC Number Field', 'MODULE_PAYMENT_STORED_CC_FIELD_NUMBER', 'cc_number', 'What\'s the field name for CC number?', '6', '7', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CC Firstname Field', 'MODULE_PAYMENT_STORED_CC_FIELD_FIRSTNAME', 'cc_owner', 'What\'s the field name for CC first name?', '6', '8', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CC Lastname Field', 'MODULE_PAYMENT_STORED_CC_FIELD_LASTNAME', 'cc_owner', 'What\'s the field name for CC last name?', '6', '9', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CC Expiration Field', 'MODULE_PAYMENT_STORED_CC_FIELD_EXPIRATION', 'cc_expiration', 'What\'s the field name for CC expiration?', '6', '10', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CC Checkcode Field', 'MODULE_PAYMENT_STORED_CC_FIELD_CHECKCODE', 'cc_checkcode', 'What\'s the field name for CC checkcode?', '6', '11', now())");
   }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_STORED_CC_STATUS', 'MODULE_PAYMENT_STORED_CC_ZONE', 'MODULE_PAYMENT_STORED_CC_ORDER_STATUS_ID', 'MODULE_PAYMENT_STORED_CC_SORT_ORDER', 'MODULE_PAYMENT_STORED_CC_TRUE_MODULE', 'MODULE_PAYMENT_STORED_CC_FIELD_CHECKCODE', 'MODULE_PAYMENT_STORED_CC_FIELD_EXPIRATION', 'MODULE_PAYMENT_STORED_CC_FIELD_FIRSTNAME', 'MODULE_PAYMENT_STORED_CC_FIELD_LASTNAME', 'MODULE_PAYMENT_STORED_CC_FIELD_NUMBER', 'MODULE_PAYMENT_STORED_CC_FIELD_TYPE');
    }
  }
?>