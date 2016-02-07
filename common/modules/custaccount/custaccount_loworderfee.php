<?php
  
// Customer Account Extn
  class custaccount_loworderfee extends IXmodule {
    function getName() {
      return 'Low Order Fee';
    }
    function getAdminFields($cus_id) {
      if (!IXdb::read("SELECT customers_group_id FROM customers WHERE customers_id='$cus_id'",NULL,'customers_group_id')) return NULL;
      $flds=IXdb::read("SELECT * FROM customers_extra WHERE customers_id='$cus_id'",'customers_extra_key','customers_extra_value');
      return Array(
        Array('title'=>'If order total is less than $','html'=>'<input type="text" name="extra[order_minimum]" value="'.htmlspecialchars($flds['order_minimum']).'">'),
        Array('title'=>'Surcharge %','html'=>'<input type="text" name="extra[order_surcharge_percent]" value="'.htmlspecialchars($flds['order_surcharge_percent']).'">'),
        Array('title'=>'or flat amount $, whichever is greater','html'=>'<input type="text" name="extra[order_surcharge_amount]" value="'.htmlspecialchars($flds['order_surcharge_amount']).'">'),
      );
    }
    function isReady() {
      return true;
    }
  }
?>
