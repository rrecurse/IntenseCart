<?php
/*
  $Id: zipship.php,v 1.27 2003/02/05 22:41:52 hpdl Exp $

  
  

   - by mark enriquez
  
    USAGE
  By default, the module comes with support for 3 postcode "zones".  
  This can be easily changed by editing the line below in the zones constructor 
  that defines $this->num_zones.

  Next, you will want to activate the module by going to the Admin screen,
  clicking on Modules, then clicking on Shipping.  A list of all shipping
  modules should appear.  Click on the green dot next to the one labeled 
  zipships.php.  A list of settings will appear to the right.  Click on the
  Edit button. 

  PLEASE NOTE THAT YOU WILL LOSE YOUR CURRENT SHIPPING RATES AND OTHER 
  SETTINGS IF YOU TURN OFF THIS SHIPPING METHOD.  Make sure you keep a 
  backup of your shipping settings somewhere at all times.

  If you want an additional handling charge applied to orders that use this
  method, set the Handling Fee field.

  Next, you will need to define which postcodes are in each zone.  Determining
  this might take some time and effort.  You should group a set of postcodes
  that has similar shipping charges for the same weight.  As an example, one 
  of my customers is using this set of postcodes/zones:

  When you enter these postcode lists, enter them into the Zone X Zipcodes
  fields, where "X" is the number of the zone.  They should be entered as
  five diget postcodes.  They should be
  separated by commas with no spaces or other punctuation. For example:
 
    1: 32903,32937
    2: 32901,32935
    3: 32940,32904


  Now you need to set up the shipping/deliver rate tables for each zone.  Again,
  some time and effort will go into setting the appropriate rates.  You
  will define a set of weight ranges and the shipping price for each
  range.  For instance, you might want an order than weighs more than 0
  and less than or equal to 3 to cost 5.50 to ship to a certain zone.  
  This would be defined by this:  3:5.5

  You should combine a bunch of these rates together in a comma delimited
  list and enter them into the "Zone X Shipping Table" fields where "X" 
  is the zone number.  For example, this might be used for Zone 1:
    1:3.5,2:3.95,3:5.2,4:6.45,5:7.7,6:10.4,7:11.85, 8:13.3,9:14.75,10:16.2,11:17.65,
    12:19.1,13:20.55,14:22,15:23.45

  The above example includes weights over 0 and up to 15.  Note that
  units are not specified in this explanation since they should be
  specific to your locale.

  CAVEATS
  At this time, it does not deal with weights that are above the highest amount
  defined.  This will probably be the next area to be improved with the
  module.  For now, you could have one last very high range with a very
  high shipping rate to discourage orders of that magnitude.  For 
  instance:  999:1000

  If you want to be able to ship to any postcode in the world, you will 
  need to enter every postcode into the zipcodes fields. For most
  shops, you will not want to enter every postcode, and in fact this module
  has NOT BEEN DESIGNED TO ENCOMPESS MANY POSTCODES.  If a postcode is not
  listed, then the module will add a $0.00 shipping charge and will
  indicate that shipping is not available to that destination.  
  PLEASE NOTE THAT THE ORDER CAN STILL BE COMPLETED AND PROCESSED!

  It appears that the shipping system automatically rounds the 
  shipping weight up to the nearest whole unit.  This makes it more
  difficult to design precise shipping tables.  If you want to, you 
  can hack the shipping.php file to get rid of the rounding.

  Lastly, there is a limit of 255 characters on each of the Zipcode
  Shipping Tables and Zone Countries. This limits you to 255/6 = 42
  MAX zipcodes per zone.  DO NOT ENTER MORE THAN 42 POSTCODES PER ZONE!!


  
*/

  class zipship {
    var $code, $title, $description, $enabled, $num_zones;

// class constructor
    function zipship() {

      $this->code = 'zipship';
      $this->title = MODULE_SHIPPING_ZIPSHIP_TEXT_TITLE;
      $this->description = MODULE_SHIPPING_ZIPSHIP_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_SHIPPING_ZIPSHIP_SORT_ORDER;
      $this->icon = '';
      $this->tax_class = MODULE_SHIPPING_ZIPSHIP_TAX_CLASS;
      $this->enabled = ((MODULE_SHIPPING_ZIPSHIP_STATUS == 'True') ? true : false);

      // CUSTOMIZE THIS SETTING FOR THE NUMBER OF ZONES NEEDED
      $this->num_zones = 3;
    }
// class methods
    function quote($method = '') {
      global $order, $shipping_weight, $shipping_num_boxes;

      $dest_zipcode = $order->delivery['postcode'];
      $dest_zone = 0;
      $error = false;

      for ($i=1; $i<=$this->num_zones; $i++) {
        $zipcode_table = constant('MODULE_SHIPPING_ZIPSHIP_CODES_' . $i);
        $zipcode_zones = split("[,]", $zipcode_table);
        if (in_array($dest_zipcode, $zipcode_zones)) {
          $dest_zone = $i;
          break;
        }
      }

      if ($dest_zone == 0) {
        $error = true;
      } else {
        $shipping = -1;
        $zipcode_cost = constant('MODULE_SHIPPING_ZIPSHIP_COST_' . $dest_zone);

        $zipcode_table = split("[:,]" , $zipcode_cost);
        $size = sizeof($zipcode_table);
        for ($i=0; $i<$size; $i+=2) {
          if ($shipping_weight <= $zipcode_table[$i]) {
            $shipping = $zipcode_table[$i+1];
            $shipping_method = MODULE_SHIPPING_ZIPSHIP_TEXT_WAY . ' ' . $dest_zipcode . ' : ' . $shipping_weight . ' ' . MODULE_SHIPPING_ZIPSHIP_TEXT_UNITS;
            $tableIdx = $i + 1;
            break;
          }
        }

        if ($shipping == -1) {
          $shipping_cost = 0;
          $shipping_method = MODULE_SHIPPING_ZIPSHIP_UNDEFINED_RATE;
        } else {
          $shipping_cost = ($shipping * $shipping_num_boxes) + constant('MODULE_SHIPPING_ZIPSHIP_HANDLING_' . $tableIdx);
        }
      }

      $this->quotes = array('id' => $this->code,
                            'module' => MODULE_SHIPPING_ZIPSHIP_TEXT_TITLE,
                            'methods' => array(array('id' => $this->code,
                                                     'title' => $shipping_method,
                                                     'cost' => $shipping_cost)));

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

      if ($error == true) $this->quotes['error'] = MODULE_SHIPPING_ZIPSHIP_INVALID_CODE;

      return $this->quotes;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_ZIPSHIP_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable Zipcode Method', 'MODULE_SHIPPING_ZIPSHIP_STATUS', 'True', 'Do you want to offer Zipcode rate shipping/delivery?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_ZIPSHIP_TAX_CLASS', '0', 'Use the following tax class on the shipping/delivery fee.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_ZIPSHIP_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
      for ($i = 1; $i <= $this->num_zones; $i++) {
        $default_zipcodes = '';
        if ($i == 1) {
          $default_zipcodes = '32903,32937';
          $default_dlvtable = '4:5,10:6,99:10';
        } else if ($i == 2) {
          $default_zipcodes = '32901,32935';
          $default_dlvtable = '4:7,10:10,99:13.50';
        } else if ($i == 3) {
          $default_zipcodes = '32951,32940';
          $default_dlvtable = '4:10,10:15,99:17.50';
        }
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i ." Zipcode(s)', 'MODULE_SHIPPING_ZIPSHIP_CODES_" . $i ."', '" . $default_zipcodes . "', 'Comma separated list of 5-diget zipcodes that are part of Zone " . $i . ".', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i ." Shipping/Delivery Fee Table', 'MODULE_SHIPPING_ZIPSHIP_COST_" . $i ."', '" . $default_dlvtable . "', 'Shipping rates to Zone " . $i . " destinations based on a group of maximum order weights. Example: 4:5,8:7,... weights less than or equal to 4 would cost $5 for Zone " . $i . " destinations.', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zone " . $i ." Handling Fee', 'MODULE_SHIPPING_ZIPSHIP_HANDLING_" . $i."', '0', 'Handling Fee for this Zipcode', '6', '0', now())");
      }
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      $keys = array('MODULE_SHIPPING_ZIPSHIP_STATUS', 'MODULE_SHIPPING_ZIPSHIP_TAX_CLASS', 'MODULE_SHIPPING_ZIPSHIP_SORT_ORDER');

      for ($i=1; $i<=$this->num_zones; $i++) {
        $keys[] = 'MODULE_SHIPPING_ZIPSHIP_CODES_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZIPSHIP_COST_' . $i;
        $keys[] = 'MODULE_SHIPPING_ZIPSHIP_HANDLING_' . $i;
      }

      return $keys;
    }
  }
?>