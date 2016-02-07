<?php

	require('includes/application_top.php');

	$date_from = isset($_GET['date_from']) ? date('Y-m-d 00:00:01', strtotime($_GET['date_from'])) : date('Y-01-01 00:00:01');
	$date_to = isset($_GET['date_to']) ? date('Y-m-d 23:59:59', strtotime($_GET['date_to'])) : date('Y-m-d 23:59:59');


	switch($_GET['sort']) {

		case 'sortbyorderidASC':
			$sortby = ' rp.order_id ASC';
		break;

		case 'sortbyorderidDESC':
			$sortby = ' rp.order_id DESC';
		break;

		case 'sortbyRMAASC':
			$sortby = ' rp.rma_value ASC';
		break;

		case 'sortbyRMADESC':
			$sortby = ' rp.rma_value DESC';
		break;

		case 'sortbyrefundASC':
			$sortby = ' rpd.refund_amount ASC';
		break;

		case 'sortbyrefundDESC':
			$sortby = ' rpd.refund_amount DESC';
		break;

		case 'sortbydateASC':
			$sortby = ' rp.date_purchased ASC';
		break;

		case 'sortbydateDESC':
			$sortby = ' rp.date_purchased DESC';
		break;

    	default:
			$sortby = ' rp.date_purchased DESC';
	}


	$refunds = tep_db_query("SELECT rp.returns_id, 
				 				 rp.rma_value, 
				 				 rp.order_id, 
				 				 rp.returns_status, 
				 				 rp.date_purchased, 
				 				 s.return_reason_name,
				 				 rpd.refund_amount AS refund_amount 
							 FROM returned_products rp 
							 LEFT JOIN returns_products_data rpd ON rp.returns_id = rpd.returns_id
							 LEFT JOIN ". TABLE_RETURN_REASONS ." s ON rp.returns_reason = s.return_reason_id 
							 LEFT JOIN ". TABLE_RETURNS_STATUS ." rs ON rp.returns_status = rs.returns_status_id
							 WHERE rp.returns_status = 4
							 AND s.language_id = '" . $languages_id . "' 
							 AND rpd.refund_amount > 0
							 AND rp.date_purchased BETWEEN '".$date_from."' AND '".$date_to."'
							  ORDER BY ". $sortby);

	$exchange = tep_db_query("SELECT rp.returns_id, 
								  rp.rma_value, 
								  rp.order_id, 
								  rp.returns_status, 
								  rp.date_purchased, 
								  rpd.exchange_amount AS exchange_amount
							  FROM returned_products rp 
							  LEFT JOIN returns_products_data rpd ON rp.returns_id = rpd.returns_id
							  LEFT JOIN ". TABLE_RETURN_REASONS ." s ON rp.returns_reason = s.return_reason_id 
							  LEFT JOIN ". TABLE_RETURNS_STATUS ." rs ON rp.returns_status = rs.returns_status_id
							  WHERE s.language_id = '" . $languages_id . "'
							  AND rpd.exchange_amount < 0
							  AND rp.returns_status = 4
							  AND rp.date_purchased BETWEEN '".$date_from."' AND '".$date_to."'
							  ORDER BY ". $sortby);

// # totals

$totalsqueryYTD = "SELECT ( SUM(rpd.refund_amount) - SUM(LEAST(0, rpd.exchange_amount)) ) AS totals, rp.date_purchased
				   FROM returned_products rp 
				   LEFT JOIN returns_products_data rpd ON rp.returns_id = rpd.returns_id
		 		   LEFT JOIN ". TABLE_RETURN_REASONS ." s ON rp.returns_reason = s.return_reason_id 
				   LEFT JOIN ". TABLE_RETURNS_STATUS ." rs ON rp.returns_status = rs.returns_status_id
				   WHERE s.language_id = '" . $languages_id . "'
				   AND rp.returns_status = 4
				   AND rp.date_purchased BETWEEN '".$date_from."' AND '".$date_to."'
				   GROUP BY s.language_id"; 


$totalsqueryLTD = "SELECT ( SUM(rpd.refund_amount) - SUM(LEAST(0, rpd.exchange_amount)) ) AS totals, rp.date_purchased
				   FROM returned_products rp 
				   LEFT JOIN returns_products_data rpd ON rp.returns_id = rpd.returns_id
				   LEFT JOIN ". TABLE_RETURN_REASONS ." s ON rp.returns_reason = s.return_reason_id
				   LEFT JOIN ". TABLE_RETURNS_STATUS ." rs ON rp.returns_status = rs.returns_status_id
				   WHERE  s.language_id = '" . $languages_id . "' 
				   AND rp.date_purchased BETWEEN '".date('Y-m-d 00:00:01', strtotime($date_from . '-1 years'))."' AND '".date('Y-m-d 23:59:59', strtotime($date_to . '-1 years'))."'
				   GROUP BY s.language_id"; 


	setlocale(LC_MONETARY, 'en_US');


	// # Export 
	if(isset($_GET['export']) && $_GET['export'] == 'csv') {
	
		$products_ids_string = $_GET['product_ids'];


		$export_products_query = tep_db_query("SELECT rp.returns_id AS 'Return ID', 
				 									  rp.rma_value AS 'RMA Number', 
				 									  rp.order_id AS 'Order Number',
				 									  rp.date_purchased AS 'Refund Date', 
				 									  s.return_reason_name AS 'Return Reason',
				 									  rpd.refund_amount AS 'Refund Amount',
													  rpd.exchange_amount AS 'Exchange Amount'
							 				   FROM returned_products rp 
							 				   LEFT JOIN returns_products_data rpd ON rp.returns_id = rpd.returns_id
							 				   LEFT JOIN ". TABLE_RETURN_REASONS ." s ON rp.returns_reason = s.return_reason_id 
							 				   LEFT JOIN ". TABLE_RETURNS_STATUS ." rs ON rp.returns_status = rs.returns_status_id
							 				   WHERE rp.returns_status = 4
							 				   AND s.language_id = '" . $languages_id . "' 
							 				   AND rpd.refund_amount > 0
							 				   AND rp.date_purchased BETWEEN '".$date_from."' AND '".$date_to."'
							 				   ORDER BY ". $sortby);
	
		$filename = SITE_DOMAIN.'_returns-report_'.date('m-d-Y', time()).'.csv';
		$filename = str_replace('www.','',$filename);

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename=' . $filename);

		$count = mysql_num_fields($export_products_query);
	
		for ($i = 0; $i < $count; $i++) {
    		$header[] = mysql_field_name($export_products_query, $i);
		}
	
		print implode(',', $header) . "\r\n";

		while ($row = tep_db_fetch_array($export_products_query)) {	

    		foreach ($row as $value) {
        		$values[] = '"' . str_replace('"', '""', html_entity_decode(strip_tags($value))) . '"';
	    	}
    	
			print implode(',', $values) . "\r\n";
		    unset($values);
		}
		
		exit();
	}
?>
<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Refunds &amp; Exchanges Report</title>

	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<link rel="stylesheet" type="text/css" href="js/css.css">

	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript" src="js/prototype.lite.js"></script>
	<script language="javascript" src="js/ajaxfade.js"></script>
	<script type="text/javascript" src="js/popcalendar.js"></script>

<style type="text/css">

body {
	min-height:500px;
}
</style>

</head>

<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td valign="top">

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr><td style="padding:5px 0 0 0">
<table><tr>
<td style="padding: 0 0 0 12px;" width="58"><img src="/admin/images/reports-icon.png" alt="" height="48" width="48"></td>
<td class="pageHeading" colspan="2">Refunds &amp; Exchanges Report</td>	
</tr></table>
</td>
<td align="right" style="padding:0 5px 0 0">
<?php echo tep_draw_form('date_range', 'returns_report.php', '', 'get'); 

	// # reformat date for calander feilds
	$date_from = date('m/d/Y', strtotime($date_from));
	$date_to = date('m/d/Y', strtotime($date_to));

?>

<table border="0" cellpadding="0" cellspacing="0">
<tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo $date_from?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?php echo $date_to?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px; padding-top:1px;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a></td>
</tr></table>

</form>
</td></tr></table>
</td></tr>
<tr>
<td style="padding:10px 0 0 0">
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr><td>
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tr>
							<td valign="top">
							<img src="refunds_chart.php?width=670&amp;height=268&amp;qtyprv_color=6295FD-1&amp;qty_color=85B761-1&amp;ret_color=BED9AA-1&amp;retprv_color=BED9AA-1&amp;ytd_color=FF0000-25&amp;ytd_markcolor=E4E4E4-50&amp;ytd_mark=8&amp;ytd_thick=4&amp;prv_color=85B761-25&amp;prv_markcolor=E4E4E4-50&amp;prv_mark=8&amp;prv_thick=4&amp;bg_color=F4F4F4&amp;bg_plot_color=FFFFFF&amp;x_font=8-0B2D86&amp;y_font=7-666666&amp;pad_left=40&amp;pad_top=8&amp;pad_bottom=14&amp;pad_right=30&amp;bar_width=71" width="100%" height="268" alt="" />
</td>
							<td valign="top" width="105" align="left" style="padding:10px 10px 0 5px;">
							<table cellpadding="0" cellspacing="0" border="0" width="100%">
								<tr>
									<td valign="top" align="left" style="padding:0 0 0 3px;">
									<img src="images/refunds-graph-key.jpg" width="105" height="115" alt="" />									</td>
								</tr>
								<tr>
									<td style="padding-left:5px; padding-top:15px;">
									<table width="100%" border="0" cellpadding="0" cellspacing="0">
										<tr><td><font style="color:#FF0000; font: bold 11px Tahoma;">
										Refund Period</font>
										</td></tr>
										<tr>
											<td style="padding-top:4px;">
<?php echo date ('m/d/y',strtotime($date_from)) ." - ". date ('m/d/y',strtotime($date_to)) ."<br>"; ?>
</td></tr>
<tr><td style="padding:4px 0 0 0">

<?php 
 
$totalresultA = mysql_query($totalsqueryYTD) or die(mysql_error());

while($row = mysql_fetch_array($totalresultA)){
	echo '<b>'.money_format('%(#10n', $row['totals']).'</b>';
}

?>
										  </td>
										</tr>
										<tr><td>
												----------------------<br />
										</td></tr>
										<tr><td>
											<font style=" color: #999999; font-family:Tahoma; font-size:11px">Refund Period <?php echo (date ("Y",strtotime($date_to))-1)?> </font>
										</td></tr>
										<tr><td style="padding-top:4px;">
<?php

$last_from = mktime(0, 0, 0, date("m",strtotime($date_from)), date("d",strtotime($date_from)), date("y",strtotime($date_from))-1);
$last_to = mktime(0, 0, 0, date("m",strtotime($date_to)), date("d",strtotime($date_to)), date("y",strtotime($date_to))-1);
echo '<span style=" color: #999999;">'.date("m/d/y", $last_from)." - ". date("m/d/y", $last_to).'</span>'; 


?> </td></tr>
<tr><td style="padding-top:4px">
<?php 
$totalresultB = mysql_query($totalsqueryLTD) or die(mysql_error());

while($row = mysql_fetch_array($totalresultB)){
	echo '<span style=" color: #999999;"><b>'. money_format('%(#10n', $row['totals']).'</b></span>'; 
} 
?>	
											
										</td></tr>
									</table></td>
						</tr>
					</table></td>
						</tr>
					</table>
</td></tr>
<tr><td style="padding:20px 0 0 10px;">
<table border="0" cellpadding="5" cellspacing="0" width="100%">
<?php

// # Show returns
if(mysql_num_rows($refunds) > 0){

	echo '<tr><td colspan="3" class="pageHeading">Refunds</td></tr>';
	echo '<tr class="dataTableHeadingRow">
				<td class="dataTableHeadingContent" width="25%"><a href="returns_report.php?sort='.($_GET['sort'] == 'sortbyorderidASC' ? 'sortbyorderidDESC':'sortbyorderidASC').'">Order ID</a></td><td class="dataTableHeadingContent" width="25%"><a href="returns_report.php?sort='.($_GET['sort'] == 'sortbyRMAASC' ? 'sortbyRMADESC':'sortbyRMAASC').'">RMA Number</a></td><td class="dataTableHeadingContent" width="25%"><a href="returns_report.php?sort='.($_GET['sort'] == 'sortbyrefundASC' ? 'sortbyrefundDESC':'sortbyrefundASC').'">Refund Amount</a></td><td class="dataTableHeadingContent" width="25%"><a href="returns_report.php?sort='.($_GET['sort'] == 'sortbydateASC' ? 'sortbydateDESC':'sortbydateASC').'">Refund Date</a></td></tr>';

	$total_refunds = 0;
	while($row = mysql_fetch_array($refunds)) {
		echo '<tr class=" '.($ct++&1 ? 'tabEven' : 'tabOdd').'">';
		echo '<td class="dataTableContent"><a href="orders.php?cFind=' . $row['order_id'] .'&amp;action=cust_search"><b>' . $row['order_id'] .'</b></a></td> <td class="dataTableContent"><a href="returns.php?page=1&amp;oID=' . $row['returns_id'] .'&amp;action=edit"><b> ' . $row['rma_value'] .'</b></a></td> <td class="dataTableContent">' .money_format('%(#10n', $row['refund_amount']) .'</td> <td class="dataTableContent">' . date('m/d/Y',strtotime($row['date_purchased'])).'</td>';
		echo '</tr>';
		$total_refunds += $row['refund_amount'];
	}
} else { 
	echo '<tr><td colspan="3"><br><b>No Returns for your selected time period.</b></td></tr>';
}

if(mysql_num_rows($exchange) > 0){

	echo '<tr><td colspan="3" class="pageHeading"><br>Exchanges</td></tr>';

	// # Show exchanges
	echo '<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" width="25%">Order ID.</td><td class="dataTableHeadingContent" width="25%">RMA Number</td><td class="dataTableHeadingContent" width="25%">Refund Amount</td><td class="dataTableHeadingContent" width="25%">Refund date</td></tr>';
	$total_exchange = 0;
	while($row = mysql_fetch_array($exchange)){
		echo '<tr class=" '.($ct++&1 ? 'tabEven' : 'tabOdd').'">';
		echo '<td class="dataTableContent"><a href="orders.php?cFind=' . $row['order_id'] .'&amp;action=cust_search"><b>'.$row['order_id'].'</b></a></td>  <td class="dataTableContent"><a href="returns.php?page=1&amp;oID=' . $row['returns_id'] .'&amp;action=edit"><b> ' . $row['rma_value'] .'</b></a></td> <td class="dataTableContent">' . money_format('%(#10n', $row['exchange_amount']).'</td> <td class="dataTableContent">'.date('m/d/Y',strtotime($row['date_purchased'])).'</td>';
		echo '</tr>';
		$total_exchange += $row['exchange_amount'];
	}

} else { 

echo '<tr><td colspan="3"><br><b>No Exchanges for your selected time period.</b></td></tr>';

}

$totalresult = mysql_query($totalsqueryYTD) or die(mysql_error());

if(mysql_num_rows($totalresult) > 0){

	echo '<tr><td colspan="4"><table><tr><td style="padding:10px 0 0 0; font:bold 13px arial" width="130"> Refund Sub-total:</td><td style="padding:10px 0 0 0; font:bold 13px arial"> '.money_format('%(#10n', $total_refunds).'</td></tr>';
	echo '<tr><td style="padding:10px 0 0 0; font:bold 13px arial"> Xchange Sub-total:</td><td style="padding:10px 0 0 0; font:bold 13px arial"> '.money_format('%(#10n', $total_exchange).'</td></tr>';

	while($row = mysql_fetch_array($totalresult)){
		echo '<tr><td style="padding:10px 0 0 0; color:red; font:bold 13px arial"> Combined Total:</td><td style="padding:10px 0 0 0; color:red; font:bold 13px arial"> '. money_format('%(#10n', $row['totals']).'</td></tr>';
	}

	echo'</table></td><tr><td colspan="4" style="padding:10px 0 0 5px;">';
	echo '<table border="0" cellpadding="0" cellspacing="0"><tr><td><a href="returns_report.php?export=csv&date_from='.$date_from.'&date_to='.$date_to.'"><img src="images/csv-icon.gif" alt="" border="0"></a></td><td style="padding:0 0 0 5px"><a href="returns_report.php?export=csv&date_from='.$date_from.'&date_to='.$date_to.'"> export to CSV </a></td></tr></table></td></tr>';

} else { 
	echo '<tr><td><br><b> No results for your selected time period. </b></td></tr>';

}
?>

</table>
</td></tr></table>
</td></tr></table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>