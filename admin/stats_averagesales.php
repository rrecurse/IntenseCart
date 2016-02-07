<?php
/*
  $Id: stats_average.php,v 1.29 2002/05/16 15:32:22 hpdl Exp $

  Copyright (c) 2006 IntenseCart eCommerce
  
*/

	require('includes/application_top.php');
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_STATS_AVERAGE);

	define ('HEADING_TITLE','Sales Snapshot');

	$tmp1 = explode('/', tep_db_prepare_input($_POST['end_date']));

	$today = strtotime('now');
	$yesterday = strtotime('-1 day', $today);

	$tmp2 = explode('-',$_POST['start_date']);
	$lastmonth = $tmp2[0]."/".$tmp2[1]."/".$tmp2[2];

	$start_date = ($_POST['start_date'] ? tep_db_prepare_input(date('Y-m-d 00:00:01', strtotime($_POST['start_date']))) : date('Y-m-01 00:00:01'));
	$end_date = ($_POST['end_date'] ? tep_db_prepare_input(date('Y-m-d 23:59:59', strtotime($_POST['end_date']))) : date('Y-m-d 23:59:59', strtotime('today')));

	$comp_order = tep_db_query("SELECT COUNT(orders_id) AS tot_comp_order 
								FROM orders 
								WHERE date_purchased BETWEEN '". $start_date ."' AND '". $end_date ."' 
								AND (orders_status > 0 AND orders_status < 4)
								");

	$row_order = tep_db_fetch_array($comp_order);

	$tot_cust = tep_db_query("SELECT COUNT(customers_id) AS tot_cust 
							  FROM orders 
							  WHERE date_purchased BETWEEN '". $start_date ."' AND '". $end_date ."' 
							  AND (orders_status > 0 AND orders_status < 4)
							  GROUP BY customers_id
							 ");

	$row_cust = tep_db_fetch_array($tot_cust);

	$tot_sale = tep_db_query("SELECT SUM(ot.value) AS tot_sale
							  FROM orders_total ot
							  LEFT JOIN orders o ON o.orders_id = ot.orders_id
							  WHERE o.date_purchased BETWEEN '". $start_date ."' AND '". $end_date ."' 
							  AND (o.orders_status > 0 AND o.orders_status < 4)
							  AND ot.class = 'ot_total'
							");

	$row_sale = tep_db_fetch_array($tot_sale);

	$days_to_complete_query = tep_db_query("SELECT SUM(TO_DAYS(last_modified) - TO_DAYS(date_purchased)) / COUNT(orders_id) AS days_to_complete 
											FROM orders 
											WHERE date_purchased BETWEEN '". $start_date ."' AND '". $end_date ."'
										   ");

	$days_to_complete = tep_db_fetch_array($days_to_complete_query);

	$res = tep_db_query("SELECT TO_DAYS('{$yesterday}') - TO_DAYS('{$lastmonth}') as noofdays");
	$row = tep_db_fetch_array($res);

	if($row['noofdays']<=0) { 
    	$row['noofdays']=1;
	} else { 
		$row['noofdays']=$row['noofdays']+1;
	}

	$tot_no_of_prod = tep_db_query("SELECT COUNT(op.products_id) AS tot_no_of_prod 
									FROM orders_products op
									LEFT JOIN orders o ON o.orders_id = op.orders_id
									WHERE o.date_purchased BETWEEN '".$start_date ."' AND '".$end_date ."' 
									AND (o.orders_status > 0 AND o.orders_status < 4)
									GROUP BY op.products_id
									");

	$row_no_of_prod=tep_db_fetch_array($tot_no_of_prod);

	$new_customers = tep_db_query("SELECT COUNT(customers_info_id) AS tot_new_customers 
								   FROM customers_info
								   WHERE customers_info_date_account_created BETWEEN '".$start_date ."' AND '".$end_date ."'
								   #GROUP BY customers_info_id
								 ");

	$row_new_customers = tep_db_fetch_array($new_customers);

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="js/popcalendar.js"></script>
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
</head>
<body style="margin:0; background:transparent; min-height:700px">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
       <tr>
          <td>
            <form method="post" action="stats_averagesales.php" name="frm">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="40%" align="right" class="pageHeading"><?php echo STATS_AVERAGE_TIME ?></td>
                  <td width="1%" class="pageHeading">&nbsp;</td>
                  <td width="59%" class="pageHeading"><?php echo date('m/d/Y',strtotime($start_date)) . " - " . date('m/d/Y',strtotime($end_date))?></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_TOT_SALE ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent"><?php echo ($row_sale['tot_sale'] ? '$'.number_format($row_sale['tot_sale'],2) : '');?></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_TOT_CUST ?></b></td>
                  <td >&nbsp;</td>
                  <td class="dataTableContent"><?php echo $row_cust['tot_cust'];?></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_TOT_COMP_ORDER ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent"><?php echo $row_order['tot_comp_order'];?></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_AV_COMP_ORDER ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent">
<?php
                     if ($row_order['tot_comp_order'] <= '0' || $row_cust['tot_cust'] <= '0'){
                     	echo '0';
                     } else {
                    	 echo round($row_order['tot_comp_order']/$row_cust['tot_cust'],2) . ' ' . STATS_AVERAGE_DAYS;
                     }
?>					</td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_AV_SALE ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent"><?php
                     if ($row_sale['tot_sale'] <= '0' || $row_order['tot_comp_order'] <= '0'){
						echo '0';
                     } else {
                     	echo '$'.number_format($row_sale['tot_sale']/$row_order['tot_comp_order'],2);
                     }
                     ?>
                  </td>
				    <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_AV_CUST ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent"><?php
                     if ($row_sale['tot_sale'] <= '0' || $row_cust['tot_cust'] <= '0'){
                     echo '0';
                     } else{
                     echo '$'.number_format($row_sale['tot_sale']/$row_cust['tot_cust'],2);
                     }
                     ?>
                  </td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_AV_TIME ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent"><?php echo round($days_to_complete['days_to_complete'],2) . " " . STATS_AVERAGE_DAYS; ?> </td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_NEW_CUST ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent"><?php echo $row_new_customers['tot_new_customers'];?></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><b><?php echo STATS_AVERAGE_AV_NEW_CUST ?></b></td>
                  <td>&nbsp;</td>
                  <td class="dataTableContent">
<?php 
					$daysDiff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
					echo round($row_new_customers['tot_new_customers'] / $daysDiff,2);
?>					</td>
               </tr>
               <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>

<?php
/*
	$res = tep_db_query("SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%m/%d-%Y') AS lastmonth, 
								DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%m-%d-%Y') AS lastweek, 
								DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') AS yesterday, 
								DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 0 day), '%Y-%m-%d') AS today
						");

	$row = tep_db_fetch_array($res);
*/
?>

				<tr>
                  <td align="right" class="dataTableContent"><?php echo STATS_AVERAGE_START_DATE ?></td>
                  <td>&nbsp;</td>
                  <td>
                    <input type="text" name="start_date" class="dataTableContent" onClick="popUpCalendar(this,this,'mm/dd/yyyy');" value="<?php echo date('m/d/Y',strtotime($start_date))?>" >
                  </td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><?php echo STATS_AVERAGE_END_DATE ?></td>
                  <td>&nbsp;</td>
                  <td>
                    <input type="text" name="end_date" class="dataTableContent" onClick="popUpCalendar(this,this,'mm/dd/yyyy');" value="<?php echo date('m/d/Y',strtotime($end_date))?>">
                  </td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>
                    <input type="submit" name="submit" value="<?php echo STATS_AVERAGE_BUTTON_REPORT ?>" class="dataTableContent">
                  </td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
              </table>
            </form>
          </td>
      </tr>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>