<?php

require('includes/application_top.php');

function table_columns($tab) {
  $lst=Array();
  $qry=tep_db_query("SHOW COLUMNS FROM $tab");
  while ($row=tep_db_fetch_array($qry)) $lst[]=$row['Field'];
  return $lst;
}


?>
<html>
<head></head>
<body>
<?

 print_r(table_columns(TABLE_PRODUCTS));
 print_r(table_columns(TABLE_PRODUCTS_DESCRIPTION));

?>
</body>
</html>
<?
require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>