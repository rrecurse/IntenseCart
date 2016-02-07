<?php
/*
  $Id: application_bottom.php,v 1.8 2002/03/15 02:40:38 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/

// close session (store variables)
  tep_session_close();

  if (STORE_PAGE_PARSE_TIME == 'true') {
    if (!is_object($logger)) $logger = new logger;
    echo $logger->timer_stop(DISPLAY_PAGE_PARSE_TIME);
  }
?>