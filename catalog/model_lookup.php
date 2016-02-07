<?
  $no_sts=1;
  include('includes/application_top.php');

  include(DIR_WS_MODULES.'models.php');

  display_model_selection(addslashes($_GET['mid']),addslashes($_GET['sel']),addslashes($_GET['field']),addslashes($_GET['class']));
?>
