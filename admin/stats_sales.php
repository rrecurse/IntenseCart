<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  	$channel = (isset($_GET['channel']) ? $_GET['channel'] : '');

		// # Channel Source

        switch($channel) {

          // # Amazon
          case 'amazon':
            $channelFilter = " AND (o.orders_source = 'dbfeed_amazon_us' OR o.orders_source LIKE '%amazon%' OR o.customers_name LIKE 'Amazon%') ";
            break;

          // # eBay
          case 'ebay':
            $channelFilter = " AND o.orders_source LIKE '%ebay%' ";
            break;

          // # E-Mail
          case 'email':
            $channelFilter = " AND o.orders_source LIKE 'email%' ";
            break;

         // # Retail sales
          case 'retail':
            $channelFilter = " AND o.customers_name NOT LIKE 'Amazon%' AND o.orders_source != 'vendor' AND o.orders_source NOT LIKE '%amazon%' ";
            break;

         // # Vendor sales
          case 'vendor':
            $channelFilter = " AND o.orders_source LIKE 'vendor' ";
            break;

         // # all
          case 'default':
            $channelFilter = '';
            break;

        }

    $date_from = (!empty($_GET['date_from']) ? date('Y-m-d 00:00:01', strtotime($_GET['date_from'])) : date('Y-m-01 00:00:01'));
    $date_to = (!empty($_GET['date_to']) ? date('Y-m-d 23:59:59', strtotime($_GET['date_to'])) : date('Y-m-t 23:59:59'));

	$status = $_GET['status'];

	$statuses_query = tep_db_query("SELECT * FROM orders_status WHERE language_id = ".$languages_id." ORDER BY orders_status_name");
	$statuses = array();
	$statuses[] = array('id' => '', 'text' => 'Show All');

	while ($st = tep_db_fetch_array($statuses_query)) {

		$statuses[] = array('id' => (string)$st['orders_status_id'], 'text' => $st['orders_status_name']);
	}

	tep_db_free_result($statuses_query);


	// # status !='' is intentional 
	// # !empty() did not filter cancelled / status zero.
	$os = ($status != '' ? " AND o.orders_status = '". $status ."'" : " ");

	switch ($_GET['by']){
		default:
		case 'name':

			$sales_products_query = tep_db_query("SELECT op.products_id,
														 o.orders_id,
														 o.date_purchased,
														 op.products_model,
														 op.products_name,
														 COALESCE(SUM(op.cost_price), 0.00) AS avg_cost,
														 SUM(op.products_quantity) AS qty,
														 COALESCE(SUM(op.cost_price * op.products_quantity), 0) AS cost,
														 SUM((op.final_price * op.products_quantity)) AS daily_prod,
														 SUM(op.final_price * op.products_quantity * ( 1 + op.products_tax / 100 )) AS withtax,
														 COALESCE(SUM( IF(rpd.refund_amount > 0, rpd.products_quantity, 0)), 0) AS ret_qty,
														 COALESCE(SUM(rpd.refund_amount), '0.00') AS ret
												  FROM orders o
												  LEFT JOIN orders_products op ON o.orders_id = op.orders_id
												  LEFT JOIN returns_products_data rpd ON op.exchange_returns_id = rpd.returns_id
												  WHERE op.products_id IS NOT NULL
												  AND (o.date_purchased BETWEEN '". $date_from ."' AND '". $date_to ."')
												  ". $os . $channelFilter ." 
												  GROUP BY products_id
												  ORDER BY op.products_model ASC
												  ");

		break;

		case 'product':
			$sales_products_query = tep_db_query("SELECT SUM(op.final_price * op.products_quantity) AS daily_prod, 
														 SUM(op.cost_price * op.products_quantity) AS cost, 		
														 SUM(op.final_price * op.products_quantity * (1+op.products_tax/100)) as withtax, 
														 o.date_purchased, 
														 op.products_name, 
														 op.products_id, 
														 SUM(op.products_quantity) AS qty, 
														 op.products_model 
												  FROM orders o
												  LEFT JOIN orders_products op ON o.orders_id = op.orders_id
												  WHERE op.products_id IS NOT NULL
												  AND (o.date_purchased BETWEEN '". $date_from ."' AND '". $date_to ."')
												  ". $os . $channelFilter ." 
												  GROUP by products_id 
												  ORDER BY daily_prod DESC
												 ");
		break;
		
		case 'units':
			$sales_products_query = tep_db_query("SELECT SUM(op.final_price * op.products_quantity) as daily_prod, 
														 COALESCE(SUM(op.cost_price * op.products_quantity), 0) AS cost,
														 SUM(op.final_price * op.products_quantity * (1 + op.products_tax/100)) as withtax, 
														 o.date_purchased, 
														 op.products_name, 
														 op.products_id, 
														 SUM(op.products_quantity) as qty, 
														 op.products_model 
												  FROM ". TABLE_ORDERS ." o 
												  LEFT JOIN ". TABLE_ORDERS_PRODUCTS ." op ON op.orders_id = o.orders_id
												  WHERE op.products_id IS NOT NULL
											 	  AND (o.date_purchased BETWEEN '". $date_from ."' AND '". $date_to ."')
												  ". $os . $channelFilter ." 
												  GROUP by products_id 
													  ORDER BY qty DESC
											");
		break;

	case 'date':

		$sales_products_query = tep_db_query("SELECT op.products_id,
													 DATE_FORMAT(o.date_purchased,'%Y-%m-%d') as date_purchased,
													 op.products_model,
													 op.products_name,
													 COALESCE(SUM(op.cost_price), 0) AS avg_cost,
													 SUM(op.products_quantity) AS qty,
													 COALESCE(SUM(op.cost_price * op.products_quantity), 0) AS cost,
													 SUM((op.final_price * op.products_quantity)) AS daily_prod,
													 SUM(op.final_price * op.products_quantity * ( 1 + op.products_tax / 100 )) AS withtax,
													 COALESCE(SUM( IF(rpd.refund_amount > 0, rpd.products_quantity, 0)), 0) AS ret_qty,
													 COALESCE(SUM(rpd.refund_amount), '0.00') AS ret
											   FROM orders o
											   LEFT JOIN orders_products op ON o.orders_id = op.orders_id
											   LEFT JOIN returns_products_data rpd ON op.exchange_returns_id = rpd.returns_id
											   WHERE op.products_id IS NOT NULL
											   AND (o.date_purchased BETWEEN '". $date_from ."' AND '". $date_to ."')
											  ". $os . $channelFilter ." 
											   GROUP BY op.products_id, DAYOFMONTH(DATE_ADD(date_purchased,INTERVAL 1 SECOND_MICROSECOND))
											   ORDER BY date_purchased,op.products_id
											  ");
break;
    }

if(isset($_GET['download']) && $_GET['download'] == 1) {

	$export_products_query = tep_db_query("SELECT op.products_model AS `Model`,
											  op.products_name AS `Product Name`,
											  SUM(op.products_quantity) AS `Qty Sold`,
											  SUM(op.final_price * op.products_quantity * (1 + op.products_tax / 100)) AS `Sales Total`,
											  SUM(op.cost_price * op.products_quantity) AS `Product Cost`,
											  SUM((op.final_price * op.products_quantity * (1 + op.products_tax / 100)) - (op.cost_price * op.products_quantity)) AS `Gross Profit`
										  FROM orders AS o
										  LEFT JOIN orders_products AS op ON op.orders_id = o.orders_id
										  WHERE op.products_id IS NOT NULL
										  AND (o.date_purchased BETWEEN '". $date_from ."' AND '". $date_to ."')
										  ". $os . $channelFilter ." 
										  GROUP BY products_id 
										  ORDER BY op.products_model
										");

	$filename = SITE_DOMAIN.'_product-sales_'.(!empty($_GET['channel']) ? $_GET['channel'] .'_' : '').$month.'-'.$year.'.csv';
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

	tep_db_free_result($export_products_query);

} else { 

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="includes/general.js"></script>

<style type="text/css">
.dataTableHeadingContent a:link, .dataTableHeadingContent a:visited {color:#FFF !important; font:bold 11px arial;}
.dataTableHeadingContent a:hover {text-decoration:underline};

.go-button {
	height:25px; 
	color:#FFFFFF; 
}

.go-button:link, .go-button:visited {
	background-color:#6295FD; 
	font:bold 11px arial; 
	color:#FFFFFF;
	padding:2px;
	border:1px solid #FFF;
}

.go-button:hover {
	text-decoration:none;
	color:#FFFFD2; 
}

</style>
<script language="javascript" src="js/popcalendar.js"></script>
</head>
<body style="margin:0 5px; background:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php');?>

<table border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td valign="top" colspan="2">
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td>
<?php 

	echo tep_draw_form('statuses', 'stats_sales.php', '?by='.$_GET['by'].(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : ''), 'GET');
?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td class="pageHeading">
			<table>
				<tr>
					<td width="55"><img src="/admin/images/stats-icon.gif" width="48" height="48" alt=""></td>
					<td class="pageHeading" colspan="2"><?php echo  HEADING_TITLE ?></td>
				</tr>
			</table>
		</td>
	
		<td class="main" align="right">
				  <table border="0" cellpadding="0" cellspacing="0">

                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo date('m/d/Y',strtotime($date_from));?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.statuses.date_from,document.statuses.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.statuses.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?php echo date('m/d/Y',strtotime($date_to))?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.statuses.date_from,document.statuses.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px;">&nbsp;<a href="javascript:document.statuses.submit();" class="go-button">&nbsp;GO&nbsp;</a></td>
                        </tr>
					</table>
		</td>
	</tr>
</table>
				<input type="hidden" name="by" value="<?php echo $_GET['by']?>">
				<input type="hidden" name="channel" value="<?php echo $_GET['channel']?>">
				<input type="hidden" name="status" value="<?php echo $_GET['status'];?>">
			</form>
		</td>
	</tr>
	<tr>
		<td style="padding:10px 0 15px 0;">
<table width="100%" cellspacing="0" cellpadding="0" align="center" border="0">
<tr>
<td style="font:bold 11px arial">
			Show data: &nbsp;  <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'by=date', 'SSL') ?>" style="color:#CC6600"><b>By date</b></a> &nbsp; | &nbsp; 
	    						<a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'by=product', 'SSL') ?>" style="color:#CC6600"><b>By dollar Sales</b></a> &nbsp; | &nbsp; 
							    <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'by=units', 'SSL') ?>" style="color:#CC6600"><b>By Units sold</b></a> &nbsp; | &nbsp; 
							    <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'by=name', 'SSL') ?>" style="color:#CC6600"><b>By name</b></a> &nbsp; &nbsp; 
</td>
<td valign="top">

<?php 

	echo tep_draw_form('channel', 'stats_sales.php',  '?by='.$_GET['by'].(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '').(!empty($_GET['date_from']) ? '&from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&to='.$_GET['date_to'] : ''), 'GET', 'style="font:bold 9px arial;"');
?>
					<table width="95%" border="0" cellpadding="0" cellspacing="0" align="right" style="margin-right:8px">
						<tr>	
							<td align="right"><b style="font:bold 11px arial">Sales Channel:</b>  &nbsp; 

	<?php 
	echo '<select name="channel" onchange="this.form.submit();">
		    <option value="" '.(empty($_GET['channel']) ? 'selected="selected"' : '').'>ALL</option>
    		<option value="retail" '.($_GET['channel'] == 'retail' ? 'selected' : '').'>Retail</option>
			<option value="vendor" '.($_GET['channel'] == 'vendor' ? 'selected' : '').'>Vendors</option>
    		<option value="amazon" '.($_GET['channel'] == 'amazon' ? 'selected' : '').'>Amazon</option>
	    	<option value="ebay" '.($_GET['channel'] == 'ebay' ? 'selected' : '').'>eBay</option>
	    	<option value="email" '.($_GET['channel'] == 'email' ? 'selected' : '').'>E-Mail</option>
    	</select>
		<input type="hidden" name="by" value="'.$_GET['by'].'">
		<input type="hidden" name="date_from" value="'.$date_from.'">
		<input type="hidden" name="date_to" value="'.$date_to.'">';
?></td>
	<td align="right"><b style="font:bold 11px arial">Status:</b> &nbsp;<?php echo tep_draw_pull_down_menu('status', $statuses, $status, 'style="font:bold 9px arial;" onchange="document.channel.submit()"');?></td> 
						</tr>
</table>
	</form>
</td>
</tr>
</table>
	    </td>
	</tr>
<?php

  if (tep_db_num_rows($sales_products_query) > 0) {

    $dp = '';
    $total=0;
	$total_wtax=0;
	$total_cost=0;
	$sum_rets='';
	$sum_rets_qty='';
	$total_avg =0;
	
    $attrs = array();

    while ($sales_products = tep_db_fetch_array($sales_products_query)) {

		//var_export($sales_products);
		if (!isset($attrs[$sales_products['products_id']])) {

			$attrs[$sales_products['products_id']] = IXdb::read("SELECT pa.options_id,pov.products_options_values_name FROM products_attributes pa LEFT JOIN products_options_values pov ON (pa.options_values_id=pov.products_options_values_id AND language_id='$languages_id') WHERE pa.products_id='".$sales_products['products_id']."'",'options_id','products_options_values_name');

		}
		
		if ($_GET['by']=='product' || $_GET['by'] == 'units' || $_GET['by'] == 'name' ) {
	    	$ddp='Product';
			$table_title = '';
		} else {

		    $ddp = tep_date_short($sales_products['date_purchased']);
    	    $table_title = tep_date_long($sales_products['date_purchased']);

		}

		if (($dp != $ddp)) { //# if day has changed (or first day)
        	if ($dp != '') { // # close previous day if not first one
?>
            </table>
		</td>
	</tr>
</table>
</td>
</tr>

      <tr>
        <td><br></td>
      </tr>

<?php
        }
?>
      <tr>
        <td class="main"><b><?php echo $table_title ?></td>
      </tr>

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" cellspacing="0" cellpadding="5" width="100%">
              <tr class="dataTableHeadingRow">
			    <td class="dataTableHeadingContent" width="75"><?php echo TABLE_HEADING_MODEL; ?></td>

                <td class="dataTableHeadingContent" width="30%"><a href=<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'by=name', 'SSL') ?>><?php echo TABLE_HEADING_NAME; ?></a></td>

                <td class="dataTableHeadingContent" align="center">Avg. Cost</td>

                <td class="dataTableHeadingContent" align=center width="40"><a href=<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'by=units', 'SSL') ?>><?php echo TABLE_HEADING_QUANTITY; ?></a></td>

				<td class="dataTableHeadingContent" align="center">Total Cost</td>

                <td class="dataTableHeadingContent" align="center">Total Sales</td>

				<td class="dataTableHeadingContent" align="center" width="50"><?php echo TABLE_HEADING_TOTAL_TAX; ?>&nbsp;</td>

				<td class="dataTableHeadingContent" align="center">Ret. Qty.</td>

				<td class="dataTableHeadingContent" align="center">Ref.</td>

				<td class="dataTableHeadingContent" align="center">Ref.%</td>

				<td class="dataTableHeadingContent" align="center">GP Per</td>

				<td class="dataTableHeadingContent" align="center">Total GP</td>

				<td class="dataTableHeadingContent" align="center">GP %</td>
              </tr>
<?php }


?>
              <tr class="dataTableRow <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd') ?>">
			    <td class="dataTableContent"><?php echo $sales_products['products_model']; ?></td>
                <td class="dataTableContent"><?php echo $sales_products['products_name']; ?><?php echo $attrs[$sales_products['products_id']]?' - '.join(' - ',$attrs[$sales_products['products_id']]):''?></td>

                <td class="dataTableContent" align="center"><?php echo $currencies->display_price($sales_products['cost'] / $sales_products['qty'],0);?>
				</td>

                <td class="dataTableContent" align="center"><?php echo $sales_products['qty']; ?></td>

				<td class="dataTableContent" align="right"> <?php echo $currencies->display_price($sales_products['cost'],0); ?>&nbsp;</td>

                <td class="dataTableContent" align="right"><?php echo $currencies->display_price($sales_products['daily_prod'],0); ?>&nbsp;</td>

				<td class="dataTableContent" align="right"><?php echo $currencies->display_price(($sales_products['withtax'] - $sales_products['daily_prod']),0); ?>&nbsp;</td>

				<td class="dataTableContent" align="center"> <?php echo ($sales_products['ret_qty'] > 0) ? $sales_products['ret_qty'] : '0'; ?></td>

				<td class="dataTableContent" align="right"> <?php echo $currencies->display_price($sales_products['ret'],0); ?>&nbsp;</td>

				<td class="dataTableContent" align="right"> 
				<?php echo ($sales_products['ret'] > 0) ? round( ($sales_products['ret_qty']) / $sales_products['qty'] * 100, 0) : '0';	?>%
				</td>

				<td class="dataTableContent" align="right"> 
				<?php echo ($sales_products['cost'] > 0) ? $currencies->display_price(($sales_products['withtax'] - $sales_products['cost']) / $sales_products['qty'], 0) : '$0.00';?>
				</td>

				<td class="dataTableContent" align="right"> 
<?php 
				//echo $currencies->display_price(($sales_products['withtax'] - ($sales_products['ret'] - $sales_products['cost'])),0); 

				echo $currencies->display_price(($sales_products['withtax'] - $sales_products['cost'] - $sales_products['ret']),0); 
?>
				</td>

				<td class="dataTableContent" align="center"> 
<?php 
				if($sales_products['withtax'] > 0) {
					$GPpercent = ($sales_products['withtax'] - $sales_products['cost']) / $sales_products['withtax'] * 100; 
				} else { 
					$GPpercent = 0; 
				}

				echo number_format($GPpercent,2). '%';
?>
</td>

              </tr>
<?php 
      $total += $sales_products['daily_prod'];
	  $total_wtax += $sales_products ['withtax'];
	  $total_cost += $sales_products['cost'];
	  $sum_rets += $sales_products['ret'];
	  $thetotal += (($sales_products['withtax'] - $sales_products['cost']) - $sales_products['ret']);
	  $total_avg += $GPpercent;
      $dp = $ddp;
    }


	$month = date('m', mktime(0, 0, 0, $month));
	$total_avg_percent = number_format(($total_avg / tep_db_num_rows($sales_products_query)),2) .'%';

	tep_db_free_result($sales_products_query);	

	echo '<tr><td colspan="14" align="right">

			<table width="100%" class="main" cellspacing="0" cellpadding="5" border="0" style="margin:15px auto">
				<tr><td colspan="2" align="right">'.TEXT_MONTHLY_SALES.'</td><td width="55" nowrap>'.$currencies->display_price($total,0).' </td></tr> 
				<tr><td colspan="2" align="right">'.TEXT_MONTHLY_SALES_TAX.'</td><td>'.$currencies->display_price(($total_wtax-$total),0).'</td></tr>
				<tr><td colspan="2" align="right">Product Cost:</td><td>'.$currencies->display_price($total_cost,0).'</td></tr>
				<tr><td colspan="2" align="right">Refunds:</td><td>'.$currencies->display_price($sum_rets,0).'</td></tr>
				<tr><td colspan="2" align="right">Avg. GP %</td><td>'.$total_avg_percent.'</td></tr>
				<tr><td>
<form action="stats_sales.php?download=1&by='.$_GET['by'].(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" method="post">
<input type="submit" value="Download CSV" name="download">
<input type="hidden" name="channel" value="'.(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '').'">
</form>
</td> 
	<td class="main" align="right"><b>Total Gross Margin:</b></td> <td><b>'.$currencies->display_price($thetotal,0).'</b></td>
</tr>
</table>
</td></tr>';
    
   } else {
echo '
  <tr>
    <td class="main"><b>There are no sales in this month</td>
  </tr>';
  }
?>
            </table></td>
           </tr>
        </table></td>
      </tr>


    </table></td>
  </tr>
</table>
</body>
</html>
<?php 

} // # End else for $_GET['download']

require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>