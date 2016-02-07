<?php
/*
  $Id: stats_sales_report.php,v 1.3 2002/11/27 19:02:22 cwi Exp $

  
*/

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  // default view (daily)
  $sales_report_default_view = 2;
  // report views (1: hourly 2: daily 3: weekly 4: monthly 5: yearly)
  $sales_report_view = $sales_report_default_view;
  if ( ($HTTP_GET_VARS['report']) && (tep_not_null($HTTP_GET_VARS['report'])) ) {
    $sales_report_view = $HTTP_GET_VARS['report'];
  }
  if ($sales_report_view > 5) {
    $sales_report_view = $sales_report_default_view;
  }
	else {
    $report = $sales_report_view;
  }

  switch( $report ) {
  	case 1:
    	$summary1 = AVERAGE_HOURLY_TOTAL;
    	$summary2 = TODAY_TO_DATE;
    	$report_desc = REPORT_TYPE_HOURLY;
    	break;
    	
  	case 2:
    	$summary1 = AVERAGE_DAILY_TOTAL;
    	$summary2 = WEEK_TO_DATE;
    	$report_desc = REPORT_TYPE_DAILY;
    	break;
    	
  	case 3:
     $summary1 = AVERAGE_WEEKLY_TOTAL;
     $summary2 = MONTH_TO_DATE;
     $report_desc = REPORT_TYPE_WEEKLY;
     break;
     
 	 case 4:
  	$summary1 = AVERAGE_MONTHLY_TOTAL;
    $summary2 = YEAR_TO_DATE;
    $report_desc = REPORT_TYPE_MONTHLY;
    break;
    
  	case 5:
    	$summary1 = AVERAGE_YEARLY_TOTAL;
    	$summary2 = YEARLY_TOTAL;
    	$report_desc = REPORT_TYPE_YEARLY;
    	break;
  }

  // check start and end Date
  $startDate = "";
  if ( ($HTTP_GET_VARS['startDate']) && (tep_not_null($HTTP_GET_VARS['startDate'])) ) {
    $startDate = $HTTP_GET_VARS['startDate'];
  }
  $endDate = "";
  if ( ($HTTP_GET_VARS['endDate']) && (tep_not_null($HTTP_GET_VARS['endDate'])) ) {
    $endDate = $HTTP_GET_VARS['endDate'];
  }

  // check filters
  if (($HTTP_GET_VARS['filter']) && (tep_not_null($HTTP_GET_VARS['filter']))) {
    $sales_report_filter = $HTTP_GET_VARS['filter'];
    $sales_report_filter_link = "&filter=$sales_report_filter";
  }

  require(DIR_WS_CLASSES . 'sales_report.php');
  $report = new sales_report($sales_report_view, $startDate, $endDate, $sales_report_filter);

  if (strlen($sales_report_filter) == 0) {
    $sales_report_filter = $report->filter;
    $sales_report_filter_link = "";
  }
  

?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body style="margin:0; background:transparent;">
<SCRIPT LANGUAGE="JavaScript1.2" SRC="jsgraph/graph.js"></SCRIPT>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
    	<td width="100%" valign="top" colspan="2">
			<table border="0" width="100%" cellspacing="0" cellpadding="2">
				<tr>
					<td colspan=2>
						<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td class="pageHeading"><?php echo HEADING_TITLE . ' - ' . $report_desc; ?></td>
								<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			    <tr>
					<td colspan=2>
						<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
<td align=right class="menuBoxHeading">
<?php
  echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'report=1' . $sales_report_filter_link, 'NONSSL') . '">' . REPORT_TYPE_HOURLY .'</a> | ';
  echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'report=2' . $sales_report_filter_link, 'NONSSL') . '">' . REPORT_TYPE_DAILY .'</a> | ';
  echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'report=3' . $sales_report_filter_link, 'NONSSL') . '">' . REPORT_TYPE_WEEKLY . '</a> | ';
  echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'report=4' . $sales_report_filter_link, 'NONSSL') . '">' . REPORT_TYPE_MONTHLY . '</a> | ';
  echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, 'report=5' . $sales_report_filter_link, 'NONSSL') . '">' . REPORT_TYPE_YEARLY . '</a>';
?>
</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top" width="200" align="center">
<?php 
if ($sales_report_view > 1) {
	if ($report->size > 1) {

		echo tep_draw_separator('pixel_trans.gif', 250,10).'<br>';

		$last_value = 0;
		$order_cnt = 0;
		$sum = 0;

		for ($i = 0; $i < $report->size; $i++) {

			if ($last_value != 0) {
				$percent = 100 * $report->info[$i]['sum'] / $last_value - 100;
			} else {
				$percent = "0";
			}

			$sum += $report->info[$i]['sum'];
			$avg += $report->info[$i]['avg'];
			$order_cnt += $report->info[$i]['count'];
			$last_value = $report->info[$i]['sum'];
		}
	}


	$url = array();

	foreach ($report->info AS $info) {
		$url[]='sum[]='.urlencode($info['sum']);
		$url[]='avg[]='.urlencode($info['avg']);
		$url[]='text[]='.urlencode(preg_replace('|/\d\d\d\d$|m','',preg_replace('/\s+-\s+/',"\n",$info['text'])));
	}

?>
	<img src="stats_sales_report_chart.php?width=320&height=240&<?=join('&',$url)?>">

<?php

}
?>

				</td>
			        <td width=100% valign=top>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td valign="top">
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
										<tr class="dataTableHeadingRow">
											<td class="dataTableHeadingContent"></td>
											<td class="dataTableHeadingContent" align=center><?php echo TABLE_HEADING_ORDERS; ?></td>
											<td class="dataTableHeadingContent" align=right><?php echo TABLE_HEADING_CONV_PER_ORDER; ?></td>
											<td class="dataTableHeadingContent" align=right><?php echo TABLE_HEADING_CONVERSION; ?></td>
											<td class="dataTableHeadingContent" align=right><?php echo TABLE_HEADING_VARIANCE; ?></td>
										</tr>
<?php

  $last_value = 0;
  $sum = 0;
  for ($i = 0; $i < $report->size; $i++) {
    if ($last_value != 0) {
      $percent = 100 * $report->info[$i]['sum'] / $last_value - 100;
    } else {
      $percent = "0";
    }
    $sum += $report->info[$i]['sum'];
    $avg += $report->info[$i]['avg'];
    $last_value = $report->info[$i]['sum'];
?>
										<tr class="dataTableRow" onmouseover="this.className='dataTableRowOver';this.style.cursor='hand'" onmouseout="this.className='dataTableRow'">
							                <td class="dataTableContent">
<?php
    if (strlen($report->info[$i]['link']) > 0 ) {
      echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, $report->info[$i]['link'], 'NONSSL') . '">';
    }
    echo $report->info[$i]['text'] . $date_text[$i];
    if (strlen($report->info[$i]['link']) > 0 ) {
      echo '</a>';
    }
?></td>
											<td class="dataTableContent" align=center><?php echo $report->info[$i]['count']?></td>
											<td class="dataTableContent"align=right><?php echo $currencies->format($report->info[$i]['avg'])?></td>
											<td class="dataTableContent" align=right><?php echo $currencies->format($report->info[$i]['sum'])?></td>
											<td class="dataTableContent" align=right>
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

<?php
  if (strlen($report->previous . " " . $report->next) > 1) {
?>
										<tr>
											<td width=100% colspan=5>
												<table width=100%>
													<tr>
														<td align=left>
<?php
    if (strlen($report->previous) > 0) {
      echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, $report->previous, 'NONSSL') . '">&lt;&lt;&nbsp;Previous</a>';
    }
?>
														</td>
										                <td align=right>
<?php
    if (strlen($report->next) > 0) {
      echo '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, $report->next, 'NONSSL') . '">Next&nbsp;&gt;&gt;</a>';
      echo "";
    }
?>
														</td>
													</tr>
												</table>
											</td>
										</tr>
<?php
  }
?>

                  </table>
                  <p>
                  <table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php if ($order_cnt != 0){
?>
                    <tr class="dataTableRow">
                      <td class="dataTableContent" width=100% align=right><?php echo '<b>'. AVERAGE_ORDER . ' </b>' ?></td>
                      <td class="dataTableContent" align=right><?php echo $currencies->format($sum / $order_cnt) ?></td>
                    </tr>
<?php } 
  if ($report->size != 0) {
?>
                    <tr class="dataTableRow">
                      <td class="dataTableContent" width=100% align=right><?php echo '<b>'. $summary1 . ' </b>' ?></td>
                      <td class="dataTableContent" align=right><?php echo $currencies->format($sum / $report->size) ?></td>
                    </tr>
<?php } ?>
                    <tr class="dataTableRow">
                      <td class="dataTableContent" width=100% align=right><?php echo '<b>'. $summary2 . ' </b>' ?></td>
                      <td class="dataTableContent" align=right><?php echo $currencies->format($sum) ?></td>
                    </tr>
                  </table>
                  <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                  <table align ="center" border="2"  color="black" width="200" cellspacing="0" cellpadding="2">
                     <tr class="dataTableRow">
                      <td class="dataTableContent" align="left"><?php echo '<b>'. FILTER_STATUS .'</b>' ?></td>
                      <td class="dataTableContent" align="left"><?php echo '<b>'. FILTER_VALUE .'</b>' ?></td>
                    </tr>
<?php
  if (($sales_report_filter) == 0) {
    for ($i = 0; $i < $report->status_available_size; $i++) {
      $sales_report_filter .= "0";
    }
  }
  for ($i = 0; $i < $report->status_available_size; $i++) {
?>
                    <tr>
                      <td class="dataTableContent" align="left"><?php echo $report->status_available[$i]['value'] ?></a></td>
<?php
    if (substr($sales_report_filter,$i,1) ==  "0") {
      $tmp = substr($sales_report_filter, 0, $i) . "1" . substr($sales_report_filter, $i+1, $report->status_available_size - ($i + 1));

      $tmp = tep_href_link(FILENAME_STATS_SALES_REPORT, $report->filter_link . "&filter=". $tmp, 'NONSSL');
?>
      <td class="dataTableContent" width="100%" align="left"><?php echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) ?>&nbsp;<a href="<?php echo $tmp; ?>"><?php echo tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) ?></a></td>
<?php
    } else {
      $tmp = substr($sales_report_filter, 0, $i) . "0" . substr($sales_report_filter, $i+1);
      $tmp = tep_href_link(FILENAME_STATS_SALES_REPORT, $report->filter_link . "&filter=". $tmp, 'NONSSL');
?>
      <td class="dataTableContent" width="100%" align="left"><a href="<?php echo $tmp; ?>"><?php echo tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) ?></a>&nbsp;<?php echo tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) ?></td>
<?php
    }
?>
                    </tr>
<?php
  }
?>
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
