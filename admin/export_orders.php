<?php

//$fd=fopen('/tmp/fffffffff','a');
//fwrite($fd,serialize($_POST).serialize($_SERVER)."\n");
//fclose($fd);

  require('includes/application_top.php');
  header('Content-Type: text/csv');

  $mod=tep_module('orderfeed_csv','orderfeed');

  $mod->exportNewOrders($_REQUEST['profile'],'');

?>