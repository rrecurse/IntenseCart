<?php
/*
  $Id: shopping_cart.php,v 1.73 2003/06/09 23:03:56 hpdl Exp $

  
  

  

  
*/

  require("includes/application_top.php");
  require("includes/supplier_area_top.php");

  include(DIR_WS_LANGUAGES . $language . '/view_supply_request.php');

  require_once(DIR_WS_CLASSES . FILENAME_SUPPLY_REQUEST);
  $supprq = new supply_request($HTTP_GET_VARS['poID']);

  require_once(DIR_FS_CATALOG_CLASSES . 'boxes.php');
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="/includes/general.js"></script>
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr><td>


<?

 $status=isset($HTTP_GET_VARS['st'])?$HTTP_GET_VARS['st']+0:1;
 if (!isset($language_id) || !$language_id) $language_id=1;
 
 $statuslst=Array();
 $statussel=Array(Array(id=>0,text=>'ALL'));
 $st_query=tep_db_query("SELECT * FROM ".TABLE_SUPPLY_REQUEST_STATUS." WHERE language_id='$language_id' AND orders_cancel_order=0");
 while ($st_row=tep_db_fetch_array($st_query)) {
  $statuslst[$st_row['orders_status_id']]=$st_row['orders_status_name'];
  $statussel[]=Array(id=>$st_row['orders_status_id'],text=>$st_row['orders_status_name']);
 }
 

?>
 <P>Show requests with status <?=tep_draw_pull_down_menu('st',$statussel,$status,' onChange="window.location.href=\'index.php?st=\'+this.value"')?></P>
<?

 $sr_query=tep_db_query("SELECT * FROM ".TABLE_SUPPLY_REQUEST." WHERE suppliers_id='$login'".($status?" AND orders_status='$status'":"")." ORDER BY orders_id DESC");
 if ($sr_row=tep_db_fetch_array($sr_query)) {
?>
 <table width="100%" border="1">
 <tr>
  <td>ID</td>
  <td>Created</td>
  <td>Status</td>
  <td>&nbsp;</td>
 </tr><?
  while (1) {
?><tr>
  <td><?=$sr_row['orders_id']?></td>
  <td><?=$sr_row['date_purchased']?></td>
  <td><?=$statuslst[$sr_row['orders_status']]?></td>
  <td>[<a href="requests.php?poID=<?=$sr_row['orders_id']?>">View</a>]</td>
 </tr>
 <?
   if (!($sr_row=tep_db_fetch_array($sr_query))) break;
  }
?></table<?
 } else {
?>
 <P>No requests available</P>
<?
 }
?>

</td>
</tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
