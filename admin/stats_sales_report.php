<?php

	require('includes/application_top.php');

	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();


  	$channel = (isset($_GET['channel']) ? $_GET['channel'] : '');


	// # report views (H: hourly d: daily w: weekly m: monthly Y: yearly)
	$dateMode = (!empty($_GET['datemode']) ? $_GET['datemode'] : 'm');

	if(!isset($_GET['datemode'])) {
		$_GET['datemode'] = 'm';
	}

		switch($dateMode) {

			// # hourly
			case 'H':

				// # check start and end Date
				$date_from = (!empty($_GET['date_from']) ? $_GET['date_from'] : date('m/d/Y', time()));
				$date_to = (!empty($_GET['date_to']) ? $_GET['date_to'] : date('m/d/Y', time()));

				$summary1 = AVERAGE_HOURLY_TOTAL;
				$summary2 = TODAY_TO_DATE;
				$report_desc = REPORT_TYPE_HOURLY;
			break;

			// # daily
			case 'd':

				// # check start and end Date
			    $date_from = (!empty($_GET['date_from']) ? $_GET['date_from'] : date('m/01/Y', time()));
			    $date_to = (!empty($_GET['date_to']) ? $_GET['date_to'] : date('m/t/Y', time()));
	
				$summary1 = AVERAGE_DAILY_TOTAL;
				$summary2 = WEEK_TO_DATE;
				$report_desc = REPORT_TYPE_DAILY;

			break;

			// # weekly
			case 'w':

				// # check start and end Date
			    $date_from = (!empty($_GET['date_from']) ? $_GET['date_from'] : date('01/01/Y', time()));
			    $date_to = (!empty($_GET['date_to']) ? $_GET['date_to'] : date('12/t/Y', time()));


				$summary1 = AVERAGE_WEEKLY_TOTAL;
				$summary2 = MONTH_TO_DATE;
				$report_desc = REPORT_TYPE_WEEKLY;
	
			break;

			// # monthly
			case 'm':
      		case 'default':
				// # check start and end Date
			    $date_from = (!empty($_GET['date_from']) ? $_GET['date_from'] : date('01/01/Y'));
			    $date_to = (!empty($_GET['date_to']) ? $_GET['date_to'] : date('12/t/Y'));

			  	$summary1 = AVERAGE_MONTHLY_TOTAL;
			    $summary2 = YEAR_TO_DATE;
			    $report_desc = REPORT_TYPE_MONTHLY;
			break;

			// # yearly
			case 'Y':
	
				$globalStartDate = date("01/01/Y 00:00:01", strtotime(tep_db_result(tep_db_query("SELECT MIN(date_purchased) FROM " . TABLE_ORDERS),0)));

				// # check start and end Date
			    $date_from = (!empty($_GET['date_from']) ?  $_GET['date_from'] : $globalStartDate);
			    $date_to = (!empty($_GET['date_to']) ? $_GET['date_to'] : date('12/t/Y 23:59:59'));

		    	$summary1 = AVERAGE_YEARLY_TOTAL;
    			$summary2 = YEARLY_TOTAL;
		    	$report_desc = REPORT_TYPE_YEARLY;
	
			break;
        }

		if(!isset($_GET['date_from'])) {
			$_GET['date_from'] = $date_from;
		}

		if(!isset($_GET['date_to'])) {
			$_GET['date_to'] = $date_to;
		}

 	// # Fire-up the  sales_report class
	require(DIR_WS_CLASSES . 'sales_report.php');

	// # get all possible status for filter
	$status = !empty($_GET['status']) ? $_GET['status'] : 3;

	$statuses_query = tep_db_query("SELECT * FROM orders_status WHERE language_id = '".$languages_id."' ORDER BY orders_status_name");
	$statuses = array();
	$statuses[] = array('id' => 'all', 'text' => 'Show All');

	while ($st = tep_db_fetch_array($statuses_query)) {
		$statuses[] = array('id' => $st['orders_status_id'], 'text' => $st['orders_status_name']);
	}

	tep_db_free_result($statuses_query);

	 // # check filters	
	$filter_link = (!empty($status) ? '&status='.$status : '') . (!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '');

	$report = new sales_report($status, $dateMode, $date_from, $date_to, $channel);

//var_dump('channel - ' . $channel);
//var_dump('status - ' . $status);

	if(isset($_GET['export']) && $_GET['export'] == 'csv') {
	
		$report->export($status, $dateMode, $date_from, $date_to, $channel);
	}
//var_dump($filter_link);
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body style="margin:5px 0 0 0; background:transparent;">

<script type="text/javascript" src="jsgraph/graph.js"></script>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
	<td width="100%" valign="top" colspan="2">

		<table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td>

<table width="100%" cellpadding="0" cellspacing="0">
<tr><td valign="top">

<table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td width="58" style="padding:0 0 0 12px"><img src="/admin/images/reports-icon.png" width="48" height="48" alt=""></td>
				<td class="pageHeading" colspan="2"><?php echo HEADING_TITLE . ' - ' . $report_desc; ?></td>	
			</tr>
		</table>

		<table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td valign="top" style="font:bold 11px arial; padding:10px">

					Filter by: &nbsp; 
<?php
	echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'datemode=H' . $filter_link, 'SSL') . '" style="font:bold 11px arial; color:#CC6600">' . REPORT_TYPE_HOURLY .'</a> &nbsp; | &nbsp;  

		<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'datemode=d' . $filter_link, 'SSL') . '" style="font:bold 11px arial; color:#CC6600">' . REPORT_TYPE_DAILY .'</a> &nbsp; | &nbsp; 
	
		<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'datemode=w' . $filter_link, 'SSL') . '" style="font:bold 11px arial; color:#CC6600">' . REPORT_TYPE_WEEKLY . '</a>&nbsp; | &nbsp;

		<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'datemode=m' . $filter_link, 'SSL') . '" style="font:bold 11px arial; color:#CC6600">' . REPORT_TYPE_MONTHLY . '</a> &nbsp;|&nbsp;

		<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'datemode=Y' . $filter_link, 'SSL') . '" style="font:bold 11px arial; color:#CC6600">' . REPORT_TYPE_YEARLY . '</a> &nbsp; &nbsp; Sales Channel:  &nbsp;';


	echo '<select onchange="location.href=this.options[this.selectedIndex].value;">
    <option value="'. $PHP_SELF .(!empty($_GET['datemode']) ? '?datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" '.(empty($_GET['channel']) ? 'selected' : '').'>ALL</option>
    <option value="'. $PHP_SELF .'?channel=retail' .(!empty($_GET['datemode']) ? '&datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" '.($_GET['channel'] == 'retail' ? 'selected' : '').'>Retail</option>
<option value="'. $PHP_SELF .'?channel=vendor' .(!empty($_GET['datemode']) ? '&datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" '.($_GET['channel'] == 'vendor' ? 'selected' : '').'>Vendors</option>
    <option value="'. $PHP_SELF .'?channel=amazon'.(!empty($_GET['datemode']) ? '&datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" '.($_GET['channel'] == 'amazon' ? 'selected' : '').'>Amazon</option>
    <option value="'. $PHP_SELF .'?channel=ebay'.(!empty($_GET['datemode']) ? '&datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" '.($_GET['channel'] == 'ebay' ? 'selected' : '').'>eBay</option>
    <option value="'. $PHP_SELF .'?channel=email' .(!empty($_GET['datemode']) ? '&datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').'" '.($_GET['channel'] == 'email' ? 'selected' : '').'>E-Mail</option>
    </select>';

?>

</td>
	</tr>
		</table>

	</td>
<td valign="bottom" style="padding-bottom:10px; font:bold 11px arial; color:#CC6600">

<?php 

	echo tep_draw_form('statuses', 'stats_sales_report.php', (!empty($_GET['datemode']) ? 'datemode='.$_GET['datemode'] : '').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : ''), 'GET');

	echo 'Status: &nbsp;' . tep_draw_pull_down_menu('status', $statuses, $status, 'onchange="this.form.submit()"');
	echo tep_draw_hidden_field('channel', $_GET['channel']);
?>
</form>
</td>
				</tr>
</table>

</td>
				</tr>



				<tr>
					<td valign="top" align="center" colspan="2" style="padding:5px;">
<?php 

		if ($report->size > 1) {
			$last_value = 0;
			$order_count = 0;
			$order_sum = 0;
			$items = 0;

			for ($i = 0; $i < $report->size; $i++) {

				if ($last_value != 0) {
					$percent = 100 * $report->info[$i]['order_sum'] / $last_value - 100;
				} else {
					$percent = "0";
				}

				$order_sum += $report->info[$i]['order_sum'];
				$order_avg += $report->info[$i]['order_avg'];
				$order_count += $report->info[$i]['order_count'];
				$last_value = $report->info[$i]['order_sum'];
			}
		}


		$url = array();

		for ($i=0;($info = $report->info[$i]) || $i<2;$i++) {
			$url[]='order_sum[]='.urlencode($info['order_sum']+0);
			$url[]='order_avg[]='.urlencode($info['order_avg']+0);
			$url[]='text[]='.urlencode(preg_replace('|/\d\d\d\d$|m','',preg_replace('/\s+-\s+/',"\n",$info['text'])));
		}


		// # now fire up the chart image 

		echo '<img width="100%" height="400" src="stats_sales_report_chart.php?width=795&height=400&bg_color=F0F5FB&bg_plot_color=F0F5FB&'. join('&',$url).'">';


?>

</td>
</tr>
<tr>
<td valign="top">
<?php
	
	if($dateMode == 'H') {

		$date_to = $report->date_to;
		//echo 'Time &amp; Date Range: '. $report->date_from->format('M d Y ga') . ' - ' .  $report->date_to->modify('+ 23 hours');
	} elseif($dateMode == 'w') { 
	} elseif($dateMode == 'd') { 
	} elseif($dateMode == 'm') { 
	} elseif($dateMode == 'Y') { 
	}
?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td valign="top" style="padding: 10px">
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
										<tr class="dataTableHeadingRow">
											<td class="dataTableHeadingContent">&nbsp;Dates</td>
											<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_ORDERS; ?></td>
											<td class="dataTableHeadingContent" align="right">Items Sold</td>
											<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_CONV_PER_ORDER; ?></td>
											<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_CONVERSION; ?></td>
											<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_VARIANCE; ?></td>
										</tr>
<?php

	$last_value = 0;
	$order_sum = 0;
	$items = 0;

	for ($i = 0; $i < $report->size; $i++) {
    	if ($last_value != 0) {
	      $percent = 100 * $report->info[$i]['order_sum'] / $last_value - 100;
    	} else {
	      $percent = "0";
    	}

		$order_sum += $report->info[$i]['order_sum'];
		$order_avg += $report->info[$i]['order_avg'];
		$items += $report->info[$i]['items'];
		$last_value = $report->info[$i]['order_sum'];

		echo '<tr  class="'.($ct++&1 ? 'tabEven' : 'tabOdd').' dataTableRow">
				<td class="dataTableContent">';

		if (strlen($report->info[$i]['link']) > 0 ) {
			echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, $report->info[$i]['link'], 'NONSSL') . '">';
		}

		echo $report->info[$i]['text'] . $date_text[$i];

		if (strlen($report->info[$i]['link']) > 0 ) {
			echo '</a>';
		}
?>

</td>
	<td class="dataTableContent" align="center"><?php echo $report->info[$i]['order_count']?></td>
	<td class="dataTableContent" align="right"><?php echo $report->info[$i]['items']?></td>
	<td class="dataTableContent"align="right"><?php echo $currencies->format($report->info[$i]['order_avg'])?></td>
	<td class="dataTableContent" align="right"><?php echo $currencies->format($report->info[$i]['order_sum'])?></td>
	<td class="dataTableContent" align="right">
<?php
    if ($percent == 0){
      echo "---";
    } else {
      echo number_format($percent,0) . "%";
    }
?>
</td>
										</tr>
<?php
 }
?>

                  </table>

<table width="100%">
<tr>
<td valign="top">

<?php
  if (strlen($report->previous . " " . $report->next) > 1) {
?>
	<table cellpadding="5">
		<tr>
			<td>
<?php
	
	if (strlen($report->previous) > 0) {
		echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, $report->previous.(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : ''), 'NONSSL') . '"><b>&lt;&lt;&nbsp;Previous</b></a>';
	}
?>
		</td>
		<td align="right">
<?php

	if (strlen($report->next) > 0) {
		echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, $report->next.(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : ''), 'NONSSL') . '"><b>Next&nbsp;&gt;&gt;</b></a>';
	}
?>
			</td>
		</tr>
	</table>
<?php
	}
?>
<table style="margin-top:10px;">
	<tr>
		<td><img src="images/icons/excel_csv.png" width="16" height="16"></td>
		<td>&nbsp;<a href="<?php echo $PHP_SELF.'?export=csv'.(!empty($_GET['datemode']) ? '&datemode='.$_GET['datemode'] : '').(!empty($_GET['date_from']) ? '&date_from='.$_GET['date_from'] : '').(!empty($_GET['date_to']) ? '&date_to='.$_GET['date_to'] : '').(!empty($_GET['channel']) ? '&channel='.$_GET['channel'] : '')?>"><b>Export to CSV</b></a>
</td>
	</tr>
</table>

</td>
<td width="50%">
	<table border="0" cellspacing="0" cellpadding="2" style="border:1px solid #FFFFFF; margin-top:10px" width="60%" align="right">
		<tr>
			<td style="padding:5px 0 0 0" colspan="2"></td>
		</tr>

	<tr>
		<td class="dataTableContent" width="100%" align="right"><?php echo '<b>Total Items Sold:</b>' ?></td>
		<td class="dataTableContent" align="right"><?php echo $items ?></td>
	</tr>


<?php if ($order_count != 0){
?>
	<tr>
		<td class="dataTableContent" width="100%" align="right"><?php echo '<b>'. AVERAGE_ORDER . ' </b>' ?></td>
		<td class="dataTableContent" align="right"><?php echo $currencies->format($order_sum / $order_count) ?></td>
	</tr>
<?php } 
  if ($report->size != 0) {
?>
                    <tr>
                      <td class="dataTableContent" width="100%" align="right"><?php echo '<b>'. $summary1 . ' </b>' ?></td>
                      <td class="dataTableContent" align="right"><?php echo $currencies->format($order_sum / $report->size) ?></td>
                    </tr>
<?php } ?>
                    <tr>
                      <td class="dataTableContent" width="100%" align="right"><?php //echo '<b>'. $summary2 . ' </b>' ?><b>Total: </b></td>
                      <td class="dataTableContent" align="right"><?php echo $currencies->format($order_sum) ?></td>
                    </tr>
                  </table>

</td>
</tr>
</table>


                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
