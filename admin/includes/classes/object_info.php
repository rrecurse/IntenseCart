<?php
// ##########################################
/*  Copyright (c) 2014 IntenseCart eCommerce  */
// ##########################################

class objectInfo {
// # class constructor
    function objectInfo($object_array) {
      reset($object_array);
      while (list($key, $value) = each($object_array)) {
        $this->$key = tep_db_prepare_input($value);
      }
    }
  }
?>
