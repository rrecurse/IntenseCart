<?php

  require('includes/application_top.php');

  if (isset($HTTP_GET_VARS['amount'])) {
    $manager_discount=$HTTP_GET_VARS['amount']+0;
    if (!tep_session_is_registered('manager_discount')) tep_session_register('manager_discount');
    printf("Manager Discount: $%.2d\n",$manager_discount);
    echo '<eval code="reloadOT()">';
  } else {
    echo("Please specify amount");
  }
?>
