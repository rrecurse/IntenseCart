<?php

  require('includes/application_top.php');

  $mod=tep_module('orderfeed_csv','orderfeed');


  if ($_POST['imp_action']=='Import') {
    $result=$mod->importOrders($_FILES['csv_file']['tmp_name'],$_POST['profile_import']);
    if ($result) {
      echo $mod->renderPage("Orders successfully imported!","CSV Import");
    } else {
      echo $mod->renderPage("<b>Errors have been encountered:</b><br>".$mod->getErrors(),"CSV Import");
    }
    exit;
  }


?>