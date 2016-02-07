<?php
  require_once('includes/application_top.php');

  $payModule=tep_module($_GET['use_module'],'payment');
  
  if (isset($payModule)) {
    $url=$payModule->initExternalCheckout($_SESSION['cart']);
    if ($url) {
      header('Location: '.$url);
      exit;
    }
  }
  header('Location: '.HTTP_SERVER.'/shopping_cart.php');
  exit;
  
  require_once(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
