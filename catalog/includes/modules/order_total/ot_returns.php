<?php

  class ot_returns {
    var $title, $output;

    function ot_tax() {
      $this->code = 'ot_returns';
      $this->title = MODULE_ORDER_TOTAL_RETURNS_TITLE;
      $this->description = MODULE_ORDER_TOTAL_RETURNS_DESCRIPTION;
      $this->enabled = ((MODULE_ORDER_TOTAL_RETURNS_STATUS == 'true') ? true : false);
      $this->sort_order = MODULE_ORDER_TOTAL_RETURNS_SORT_ORDER;

      $this->output = array();
    }

    function process() {
      global $order, $currencies;

      $a=0;
      foreach ($order->returns AS $rt) $a+=$rt['refund_amount'];
      if ($a>0)
          $this->output[] = array('title' => $this->title . ':',
                                  'text' => $currencies->format(-$a, true, $order->info['currency'], $order->info['currency_value']),
                                  'value' => -$a);
        }
      }
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_RETURNS_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }

      return $this->_check;
    }

    function keys() {
      return array('MODULE_ORDER_TOTAL_RETURNS_STATUS', 'MODULE_ORDER_TOTAL_RETURNS_SORT_ORDER');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Tax', 'MODULE_ORDER_TOTAL_RETURNS_STATUS', 'true', 'Do you want to display the order tax value?', '6', '1','tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_RETURNS_SORT_ORDER', '3', 'Sort order of display.', '6', '2', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
  }
?>
