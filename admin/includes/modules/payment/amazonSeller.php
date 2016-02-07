<?php
/*
  Copyright (c) 2013 IntenseCart eCommerce
  
*/

  class amazonSeller {
    var $code, $title, $description, $enabled;

// class constructor
    function amazonSeller() {
      global $order;

      $this->code = 'amazonSeller';
      $this->title = MODULE_PAYMENT_AMAZONSELLER_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_AMAZONSELLER_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_AMAZONSELLER_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_AMAZONSELLER_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_AMAZONSELLER_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_AMAZONSELLER_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
    }

// class methods
    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_AMAZONSELLER_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_AMAZONSELLER_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
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

  
 
    function get_error() {
      global $HTTP_GET_VARS;

      $error = array('title' => ' - -' . MODULE_PAYMENT_CC_TEXT_ERROR,
                     'error' => stripslashes(urldecode($HTTP_GET_VARS['error'])));

      return $error;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_CC_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }


  }
?>
