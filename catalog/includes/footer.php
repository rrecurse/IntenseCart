<?php

  // # STS: ADD
  // # Get the output between column_right.php and footer.php
  $sts_block_name = 'columnright2footer';
  require(STS_RESTART_CAPTURE);
  // # STS: EOADD


// # STS: ADD
  $sts_block_name = 'footer';
  require(STS_RESTART_CAPTURE);
// # STS: EOADD
echo ' ';
  if ($banner = tep_banner_exists('dynamic', '468x60')) {

// # STS: ADD
  $sts_block_name = 'banner';
  require(STS_RESTART_CAPTURE);

// # STS: EOADD
  
  }

?>
