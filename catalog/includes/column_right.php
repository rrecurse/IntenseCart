<?php 
// # moved everything to column_left.php
 // # STS: ADD
  $sts_block_name = 'columnleft2columnright';
  require(STS_RESTART_CAPTURE);
  // # STS: EOADD
    
  require(DIR_WS_BOXES . 'reviews.php');

  // # STS: ADD
  $sts_block_name = 'reviewsbox';
  require(STS_RESTART_CAPTURE);
  
  if($wishList->count_wishlist() != '0') {
    require(DIR_WS_BOXES . 'wishlist.php'); 
  }
?>