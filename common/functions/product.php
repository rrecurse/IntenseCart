<?php
class IXproduct {
  var $product_id;
  function IXproduct($pid=NULL) {
    
  }
  function productFromDB($row) {
    $this->prod_info=$row;
  }

}
?>