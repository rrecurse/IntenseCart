<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="js/css.css">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>
</head>
<body style="margin:0; background-color:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); 

	$date_from = (isset($_GET['date_from'])) ? $_GET['date_from'] : $_GET['date_from'] = date('01/01/Y',time());
	$date_to = (isset($_GET['date_to'])) ? $_GET['date_to'] : $_GET['date_to'] = date('m/d/Y',time());
?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td valign="top"><?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
       <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <td width="58" style="padding:2px 0 5px 8px"><img src="/admin/images/top-customers_icon.png" width="48" height="48" alt=""></td>
								<td class="pageHeading" colspan="2"><?php echo HEADING_TITLE ?></td></tr></table></td>
            <td class="pageHeading" align="right">


<?php 
			echo tep_draw_form('date_range', 'stats_customers.php'.(isset($_GET['orderby']) ? '?orderby='.(($_GET['orderby'] == 'value') ? 'value' : 'sold') : ''), '', 'get');
?>
					  <table border="0" cellpadding="0" cellspacing="0">

                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?=$date_from?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?=$date_to?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding-right:7px;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
                        </tr>
</table>
</form>

</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="4" style="border-left:solid 1px #FFF">
              <tr class="dataTableHeadingRow">

                <td class="dataTableHeadingContent" width="20"><?php echo TABLE_HEADING_HISTORY; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS; ?></td>
                <td class="dataTableHeadingContent" align="right">
<?php 
	if(!isset($orderby) || ($orderby == "sold" && $sorted == "ASC")) { 
		$to_sort = "DESC"; 
	} else {
		$to_sort = "ASC"; 
	}
	
	echo '<a href="' . tep_href_link('stats_customers.php', 'orderby=sold&sorted='. $to_sort) . '&date_from='.$date_from.'&date_to='.$date_to.'" class="main" style="color:#FFF; font:bold 11px arial"># of Orders</a></td>
                <td class="dataTableHeadingContent" align="right" style="padding-right:5px">';

	if(!isset($orderby) || ($orderby == "value" && $sorted == "ASC")) {
		$to_sort = "DESC"; 
	} else { 
		$to_sort = "ASC"; 
	} 

	echo '<a href="' . tep_href_link('stats_customers.php', 'orderby=value&sorted='. $to_sort) . '&date_from='.$date_from.'&date_to='.$date_to.'" class="main" style="color:#FFF; font:bold 11px arial">' . TABLE_HEADING_TOTAL_PURCHASED . '</a></td>
              </tr>';

  if (isset($_GET['page']) && $_GET['page'] > 1){
		$rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;
}
    if ($orderby == "value") {
		$db_orderby = "ordersum";
	} elseif ($orderby == "sold") {
		$db_orderby = "ordertotal";
	} else {
		$db_orderby = "ordersum DESC";
	}

	$date_from = (!empty($_GET['date_from'])) ? date('Y-m-d',strtotime($date_from)) : date('01/01/2001',time());
	$date_to = (!empty($_GET['date_to'])) ? date('Y-m-d',strtotime($date_to)) :  date('Y-m-d',time());

	$customers_query_raw = "SELECT c.customers_id, 
									c.customers_firstname, 
									c.customers_lastname, 
									c.customers_email_address, 
									COUNT(DISTINCT o.orders_id) AS ordertotal, 
									SUM(ot.value) AS ordersum
							FROM " . TABLE_ORDERS_TOTAL . " ot
							LEFT JOIN " . TABLE_ORDERS . " o ON o.orders_id = ot.orders_id
							LEFT JOIN " . TABLE_CUSTOMERS . " c ON c.customers_id = o.customers_id
							WHERE ot.class = 'ot_total'
							AND o.orders_status > 0 
		  ". ($date_from ? "AND o.date_purchased >= '$date_from' " : "").
		  	   ($date_to ? "AND o.date_purchased < '$date_to' " : "")." 
							GROUP BY c.customers_email_address, c.customers_id
							ORDER BY $db_orderby $sorted";

	$customers_query_raw = preg_replace('/\s+/', ' ', $customers_query_raw);

    $customers_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $customers_query_raw, $customers_query_numrows);
	$rows = 0;
	
	$customers_query = tep_db_query($customers_query_raw);

	
	while ($customers = tep_db_fetch_array($customers_query)) {

	    $rows++;

    	if (strlen($rows) < 2) {
	      $rows = '0' . $rows;
    	}

		echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'">

    	            <td class="dataTableContent"><a href="'.tep_href_link(FILENAME_ORDERS, 'cID=' . $customers['customers_id'], 'NONSSL').'"><img src="images/icons/order-hist_ico.png"></a></td>
					<td class="dataTableContent">';

		if(!is_null($customers['customers_id'])) {
			echo '<a href="' . tep_href_link(FILENAME_CUSTOMERS, 'cID=' . $customers['customers_id'] . '&action=edit', 'SSL') . '">' . $customers['customers_firstname'] . ' ' . $customers['customers_lastname'] . '</a>';
		} else { 

			echo 'Marketplace Customers';
		}
					
				
		echo ' </td>
            	    <td class="dataTableContent" align="right">'. $customers['ordertotal'].'&nbsp;</td>
	                <td class="dataTableContent" align="right">'. $currencies->format($customers['ordersum']).'&nbsp;</td>
    	          </tr>';
	  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3">
<?php
	// # get total count of custommers.
	$customers_count_query = tep_db_query($customers_query_raw);
	$customers_count = tep_db_fetch_array($customers_count_query);


	// # get the total number of unique orders totaling more then one (1)
	// # basic repeat customers count
	$customers_repeat_query = tep_db_query("SELECT COUNT(repeats) AS total_repeats
											FROM 
												(
											SELECT COUNT(o.customers_id) AS repeats 
											FROM orders o 
											WHERE o.orders_status > 0 
".( $date_from ? "AND o.date_purchased >= '".$date_from."' " : "").( $date_to ? " AND o.date_purchased < '".$date_to."'" : "")."
											GROUP BY o.customers_id
											HAVING COUNT(o.customers_id) > 1
												) r
										  ");

	$customers_repeat = tep_db_fetch_array($customers_repeat_query);


	// # get the total number of unique orders totaling more then ONE (1)
	$customers_repeat_more1_query = tep_db_query("SELECT COUNT(repeats) AS more1_repeats
											FROM 
												(
											SELECT COUNT(o.customers_id) AS repeats 
											FROM orders o 
											WHERE o.orders_status > 0 
".( $date_from ? "AND o.date_purchased >= '$date_from' " : "").( $date_to ? "AND o.date_purchased < '$date_to' " : "")."
											GROUP BY o.customers_id
											HAVING COUNT(o.customers_id) = 2
												) r
										  ");

	$customers_repeat_more1 = tep_db_fetch_array($customers_repeat_more1_query);



	// # get the total number of unique orders totaling more then TWO (2)
	$customers_repeat_more2_query = tep_db_query("SELECT COUNT(repeats) AS more2_repeats
											FROM 
												(
											SELECT COUNT(o.customers_id) AS repeats 
											FROM orders o 
											WHERE o.orders_status > 0 
".( $date_from ? "AND o.date_purchased >= '$date_from' " : "").( $date_to ? "AND o.date_purchased < '$date_to' " : "")."
											GROUP BY o.customers_id
											HAVING COUNT(o.customers_id) = 3
												) r
										  ");

	$customers_repeat_more2 = tep_db_fetch_array($customers_repeat_more2_query);


	// # get the total number of unique orders totaling more then Three (3)
	$customers_repeat_more3_query = tep_db_query("SELECT COUNT(repeats) AS more3_repeats
											FROM 
												(
											SELECT COUNT(o.customers_id) AS repeats 
											FROM orders o 
											WHERE o.orders_status > 0 
".( $date_from ? "AND o.date_purchased >= '$date_from' " : "").( $date_to ? "AND o.date_purchased < '$date_to' " : "")."
											GROUP BY o.customers_id
											HAVING COUNT(o.customers_id) > 3
												) r
										  ");
	$customers_repeat_more3 = tep_db_fetch_array($customers_repeat_more3_query);

	$date_from = (!empty($date_from)) ? date('n/j/Y',strtotime($date_from)) : date('01/01/Y',time());
	$date_to = (!empty($date_to)) ? date('n/j/Y',strtotime($date_to)): date('m/d/Y',time());
?>
<table border="0" width="100%" cellspacing="0" cellpadding="3" style="margin:10px 0">
	<tr>
		<td align="right">
			<table cellpadding="6" cellspacing="0" border="0" style="background-color:#FFFFFF; border:1px solid #333;">
				<tr>
					<td colspan="3" style="padding:6px 5px" align="center">
						<u><?php echo ($date_from ? '<b style="font:bold 13px arial;">Range: '.$date_from.' - '.$date_to.'</b>' : '<b style="font:bold 14px arial;">Lifetime Customer Stats</b>');?></u>:</td>
				</tr>
				<tr>
					<td align="right">Total Customers: </td> <td align="right"><b><?php echo $customers_query_numrows; ?></b></td> <td align="right"></td>
				</tr>
				<tr>
					<td align="right" nowrap>Total Repeat Customers: </td> <td align="right"><b><?php echo $customers_repeat['total_repeats'];?></b></td> <td align="right"><?php echo round(( $customers_repeat['total_repeats'] / $customers_query_numrows) * 100,2);?>%</td>
				</tr>
				<tr>
					<td align="right" style="border-top:dashed 1px #ccc">Customers w/ 2 Orders: </td> <td style="border-top:dashed 1px #ccc" align="right"><b><?php echo $customers_repeat_more1['more1_repeats'];?></b></td> <td style="border-top:dashed 1px #ccc" align="right"><?php echo round(( $customers_repeat_more1['more1_repeats'] / $customers_query_numrows) * 100,2);?>%</td>
				</tr>
				<tr>
					<td align="right">Customers w/ 3 Orders: </td> <td align="right"><b><?php echo $customers_repeat_more2['more2_repeats'];?></b></td> <td align="right"><?php echo round(( $customers_repeat_more2['more2_repeats'] / $customers_query_numrows) * 100,2);?>%</td>
				</tr>	
				<tr>
					<td align="right">More then 3 orders: </td> <td align="right"><b><?php echo $customers_repeat_more3['more3_repeats'];?></b></td> <td align="right"><?php echo round(( $customers_repeat_more3['more3_repeats'] / $customers_query_numrows) * 100,2);?>%</td>
				</tr>	
			</table>
					</td>
	</tr>
</table>


<table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $customers_split->display_count($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_CUSTOMERS); ?></td>
                <td class="smallText" align="right"><?php echo $customers_split->display_links($customers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], "orderby=" . $orderby . "&sorted=" . $sorted); ?>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
