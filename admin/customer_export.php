<?php

ini_set('session.cache_limiter','private');

require('includes/application_top.php');

function csv_row($flds) {
  $qflds=Array();
  foreach ($flds AS $fld) $qflds[]=preg_match('/[^\\w\\-\\ \\@\\.]/',$fld)?'"'.str_replace('"','""',$fld).'"':$fld;
  return join(',',$qflds)."\r\n";
}

if (!$HTTP_GET_VARS['submit'])
{
	?>
	<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//DE">
	<html <?php echo HTML_PARAMS; ?>>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title>Email list export</title>
	<script type="text/javascript" src="js/prototype.lite.js"></script>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body style="margin:0; background:transparent;">

	<?php require(DIR_WS_INCLUDES.'header.php'); ?>

	 <table border="0" width="100%" cellspacing="0" cellpadding="0" >
	  <tr>
	    <td valign="top" class="pageHeading" colspan="2">
	Export E-Mail Data to CSV<br><br>
	</td></tr>
	<tr><td>
	  <form action="<?=$phpself?>" onSubmit="return this.export_customers.checked || this.export_subscribers.checked;">
	  <input type="checkbox" value="1" name="export_customers" checked onClick="$('cust_groups').style.display=this.checked?'':'none'"> Export Store Customers<br>
	  <div id="cust_groups">
	  <? foreach (IXdb::read("SELECT * from customers_groups",'customers_group_id','customers_group_name') AS $gid=>$grp) { ?>
	  &nbsp; &nbsp; <input type="checkbox" value="<?=$gid?>" name="export_customers_group[]" checked> <?=$grp?><br>
	  <? } ?>
	  </div>
	  <input type="checkbox" value="1" name="export_subscribers" checked> Export Newsletter Subscribers<br>
	  <input type="submit" value="Click here to export" name="submit"></form>
	</td>
	</table>

	</body>
	</html>
	<?php
} else {

        header('Content-Type: text/csv');
	header("Content-Disposition: attachment; filename=export.csv");
	$emails=Array();
	echo "First Name,Last Name,Company,Email\r\n";
	if ($_REQUEST['export_customers'] && $_REQUEST['export_customers_group']) {

	  $user_query = mysql_query("SELECT c.customers_id, c.customers_firstname, c.customers_lastname, a.customers_id, c.customers_email_address,a.entry_company 
								FROM ". TABLE_CUSTOMERS ." c, address_book a 
								WHERE c.customers_group_id IN ('".join("','",$_REQUEST['export_customers_group'])."')
								AND c.customers_id = a.customers_id
								");
	  while($row = mysql_fetch_array($user_query)) 
	  {
		if (isset($emails[$row['customers_email_address']])) continue;
		$emails[$row['customers_email_address']]=true;
		echo csv_row(Array($row['customers_firstname'],$row['customers_lastname'],$row['entry_company'],$row['customers_email_address']));
	  }
	}
	if ($_REQUEST['export_subscribers']) {
	  $user_query = mysql_query('select * from subscribers');
	  while($row = mysql_fetch_array($user_query)) 
	  {
		if (isset($emails[$row['subscribers_email_address']])) continue;
		$emails[$row['subscribers_email_address']]=true;
		echo csv_row(Array($row['subscribers_firstname'],$row['subscribers_lastname'],$row['subscribers_email_address']));
	  }
	}

}
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>