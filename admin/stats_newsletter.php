<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

////////////////////// REWRITE FOR NEWSLETTERS! - CURRENTLY NOT NEWSLETTERS - CLONE OF STATS_SALES.PHP /////////////////////////////////////////

  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  if (!isset($_GET['month'])) {
    $month = date("m", time()+STORE_TZ*3600);
    $year = date('Y',time()+STORE_TZ*3600);
  } else {
    $month = $_GET['month'];
    $year = $_GET['year'];
  }
  
  $months = array();
  $months[] = array('id' => 1, 'text' => 'January');
  $months[] = array('id' => 2, 'text' => 'February');
  $months[] = array('id' => 3, 'text' => 'March');
  $months[] = array('id' => 4, 'text' => 'April');
  $months[] = array('id' => 5, 'text' => 'May');
  $months[] = array('id' => 6, 'text' => 'June');
  $months[] = array('id' => 7, 'text' => 'July');
  $months[] = array('id' => 8, 'text' => 'August');
  $months[] = array('id' => 9, 'text' => 'September');
  $months[] = array('id' => 10, 'text' => 'October');
  $months[] = array('id' => 11, 'text' => 'November');
  $months[] = array('id' => 12, 'text' => 'December');

  $years = array();

  $years[] = array('id' => 2006, 'text' => '2006');
  $years[] = array('id' => 2007, 'text' => '2007');
  $years[] = array('id' => 2008, 'text' => '2008');
  $years[] = array('id' => 2009, 'text' => '2009');
  $years[] = array('id' => 2010, 'text' => '2010');
  $years[] = array('id' => 2011, 'text' => '2011');
  $years[] = array('id' => 2012, 'text' => '2012');
  $years[] = array('id' => 2013, 'text' => '2013');
  $years[] = array('id' => 2014, 'text' => '2014');
  $years[] = array('id' => 2015, 'text' => '2015');
  $years[] = array('id' => 2016, 'text' => '2016');
  $years[] = array('id' => 2017, 'text' => '2017');
  $years[] = array('id' => 2018, 'text' => '2018');
  $years[] = array('id' => 2019, 'text' => '2019');

  $status = $_GET['status'];

  $statuses_query = tep_db_query("select * from orders_status where language_id = $languages_id order by orders_status_name");
  $statuses = array();
  $statuses[] = array('id' => '', 'text' => 'Show All');

  while ($st = tep_db_fetch_array($statuses_query)) {

     $statuses[] = array('id' => $st['orders_status_id'], 'text' => $st['orders_status_name']);
  }

($status != '') ? $os = " AND o.orders_status='".$status."'" : $os = '';

$lastday = date('t',$month);
$from = date('Y-m-d H:i:s', strtotime($month.'/01/'.$year)+STORE_TZ*3600);
$to = date('Y-m-d H:i:s', strtotime($month.'/'.$lastday.'/'.$year)+STORE_TZ*3600);

switch ($_GET['by']){
  default:
  case 'name':

$sales_products_query = tep_db_query("

 SELECT op.products_id,
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
FROM   orders o
       LEFT JOIN orders_products op ON o.orders_id = op.orders_id
       LEFT JOIN returns_products_data rpd ON op.exchange_returns_id = rpd.returns_id


		WHERE MONTH(o.date_purchased) = '".$month."' 
		AND YEAR(o.date_purchased) = '".$year."' 
	   	".$os." 
		GROUP BY products_id
		ORDER BY op.products_model ASC
		");

break;

  case 'product':
    $sales_products_query = tep_db_query("select sum(op.final_price*op.products_quantity) as daily_prod, SUM(op.cost_price * op.products_quantity) AS cost, sum(op.final_price*op.products_quantity*(1+op.products_tax/100)) as withtax, o.date_purchased, op.products_name, op.products_id, sum(op.products_quantity) as qty, op.products_model from orders as o, orders_products as op where o.orders_id = op.orders_id and month(o.date_purchased) = " . $month . " and year(o.date_purchased) = " . $year . $os . " GROUP by products_id ORDER BY daily_prod DESC");
  break;
  case 'units':
  	$sales_products_query = tep_db_query("select sum(op.final_price*op.products_quantity) as daily_prod, SUM(op.cost_price * op.products_quantity) AS cost, sum(op.final_price*op.products_quantity*(1+op.products_tax/100)) as withtax, o.date_purchased, op.products_name, op.products_id, sum(op.products_quantity) as qty, op.products_model from orders as o, orders_products as op where o.orders_id = op.orders_id and month(o.date_purchased) = " . $month . " and year(o.date_purchased) = " . $year . $os . " GROUP by products_id ORDER BY qty DESC");
  break;
  case 'date':

      $sales_products_query = tep_db_query("

 SELECT op.products_id,
       o.date_purchased,
       op.products_model,
       op.products_name,
       COALESCE(SUM(op.cost_price), 0) AS avg_cost,
       SUM(op.products_quantity) AS qty,
       COALESCE(SUM(op.cost_price * op.products_quantity), 0) AS cost,
       SUM((op.final_price * op.products_quantity)) AS daily_prod,
       SUM(op.final_price * op.products_quantity * ( 1 + op.products_tax / 100 )) AS withtax,
       COALESCE(SUM( IF(rpd.refund_amount > 0, rpd.products_quantity, 0)), 0) AS ret_qty,
       COALESCE(SUM(rpd.refund_amount), '0.00') AS ret
FROM   orders o
       LEFT JOIN orders_products op ON o.orders_id = op.orders_id
       LEFT JOIN returns_products_data rpd ON op.exchange_returns_id = rpd.returns_id
WHERE MONTH(o.date_purchased) = '".$month."' 
		AND YEAR(o.date_purchased) = '".$year."' 
	   	".$os." 
	GROUP BY DAYOFMONTH(DATE_ADD(o.date_purchased,INTERVAL 1 SECOND_MICROSECOND)), products_id 
	ORDER BY o.date_purchased,products_id
	");
break;
    }

if(isset($_GET['download']) && $_GET['download'] == 1) {

	$export_products_query = tep_db_query("SELECT op.products_id AS `Admin ID`,
										  op.products_model AS `Model`,
										  op.products_name AS `Product Name`,
										  SUM(op.products_quantity) AS `Qty Sold`,
										  SUM(op.final_price * op.products_quantity * (1 + op.products_tax / 100)) AS `Sales Total`,
										  SUM(op.cost_price * op.products_quantity) AS `Product Cost`,
										  SUM((op.final_price * op.products_quantity * (1 + op.products_tax / 100)) - (op.cost_price * op.products_quantity)) AS `Gross Profit`
										  FROM orders AS o, 
										  orders_products AS op 
										  WHERE o.orders_id = op.orders_id 
										  AND o.date_purchased >= '".$from."'
										  AND o.date_purchased <= '".$to."' 
										  ".$os." 
												GROUP BY products_id 
												ORDER BY op.products_name
										");

//error_log(print_r($month.'-'.$year,TRUE));

$filename = SITE_DOMAIN.'_product-sales_'.$month.'-'.$year.'.csv';
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


} else { 

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<meta http-equiv="Page-Exit" content="blendTrans(Duration=0.25)">
<title><?php echo  HEADING_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="includes/general.js"></script>

<style type="text/css">
.dataTableHeadingContent a:link, .dataTableHeadingContent a:visited {color:#FFF !important; font:bold 11px arial;}
.dataTableHeadingContent a:hover {text-decoration:underline};

</style>

</head>
<body style="margin:0 5px; background:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php');?>

<table border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td valign="top" colspan="2">
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
		<form action="stats_sales.php" method="get">
		<tr>
		<td class="pageHeading">
<table><tr>
<td width="55"><img src="/admin/images/stats-icon.gif" width="48" height="48" alt=""></td>
								<td class="pageHeading" colspan="2"><?php echo  HEADING_TITLE ?></td>	
</tr></table></td>
		<td class="main" align="right"><?php echo 'Mo.: ' . tep_draw_pull_down_menu('month', $months, $month, 'onchange=\'this.form.submit();\'') . '&nbsp; Yr.: ' . tep_draw_pull_down_menu('year', $years, $year, 'onchange=\'this.form.submit();\'')?> &nbsp; <?php echo 'Status: ' . tep_draw_pull_down_menu('status', $statuses, $status, 'onchange=\'this.form.submit();\'')?></td>
		</tr>	
		<input type="hidden" name="by" value="<?php echo $_GET['by']?>">
		</form>
		</table></td></tr>
    <td><? //include_once 'ofc-library/open_flash_chart_object.php';
//open_flash_chart_object('100%', '400', 'ofc-library/grafik_data.php?yearf='.$year.'&monthf='.$month, false );  ?></td>
  </tr>
  <tr>
	  <tr><td style="padding:10px 0 15px 0; font:bold 11px arial">
	   Show data: &nbsp;  <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'&by=date', 'SSL') ?>" style="color:#CC6600"><b>By date</b></a> &nbsp; | &nbsp; 
	    <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'&by=product', 'SSL') ?>" style="color:#CC6600"><b>By dollar Sales</b></a> &nbsp; | &nbsp; 
	    <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'&by=units', 'SSL') ?>" style="color:#CC6600"><b>By Units sold</b></a> &nbsp; | &nbsp; 
	    <a href="<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'&by=name', 'SSL') ?>" style="color:#CC6600"><b>By name</b></a> 
	    </td></tr>
<?php

  if (tep_db_num_rows($sales_products_query) > 0) {
    $dp = '';
    $total=0;
	$total_wtax=0;
	$total_cost=0;
	$sum_rets='';
	$sum_rets_qty='';
	$total_avg =0;
	
    $attrs=Array();

    while ($sales_products = tep_db_fetch_array($sales_products_query)) {

	
      if (!isset($attrs[$sales_products['products_id']])) $attrs[$sales_products['products_id']]=IXdb::read("SELECT pa.options_id,pov.products_options_values_name FROM products_attributes pa LEFT JOIN products_options_values pov ON (pa.options_values_id=pov.products_options_values_id AND language_id='$languages_id') WHERE pa.products_id='".$sales_products['products_id']."'",'options_id','products_options_values_name');
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
            </table></td>
           </tr>
        </table></td>
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

                <td class="dataTableHeadingContent" width="30%"><a href=<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'&by=name', 'SSL') ?>><?php echo TABLE_HEADING_NAME; ?></a></td>

                <td class="dataTableHeadingContent" align="center">Avg. Cost</td>

                <td class="dataTableHeadingContent" align=center width="40"><a href=<?php echo tep_href_link(FILENAME_STATS_SALES, tep_get_all_get_params(array('by')).'&by=units', 'SSL') ?>><?php echo TABLE_HEADING_QUANTITY; ?></a></td>

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

                <td class="dataTableContent" align="center"><?php echo $currencies->display_price($sales_products['cost'] / $sales_products['qty'],0) ?></td>

                <td class="dataTableContent" align="center"><?php echo $sales_products['qty']; ?></td>

				<td class="dataTableContent" align="right"> <?php echo $currencies->display_price($sales_products['cost'],0); ?>&nbsp;</td>

                <td class="dataTableContent" align="right"><?php echo $currencies->display_price($sales_products['daily_prod'],0); ?>&nbsp;</td>

				<td class="dataTableContent" align="right"><?php echo $currencies->display_price(($sales_products['withtax'] - $sales_products['daily_prod']),0); ?>&nbsp;</td>

				<td class="dataTableContent" align="center"> <?php echo $sales_products['ret_qty']; ?></td>

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
?>				&nbsp;</td>

				<td class="dataTableContent" align="center"> 
<?php 
				$GPpercent = ($sales_products['withtax'] - $sales_products['cost']) / $sales_products['withtax'] * 100; 
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
echo '
<tr><td colspan="14" align="right">
<table width="100%" class="main" cellspacing="0" cellpadding="5" border="0" style="margin:15px auto">
<tr><td colspan="2" align="right">'.TEXT_MONTHLY_SALES.'</td><td width="55" nowrap>'.$currencies->display_price($total,0).' </td></tr> 
<tr><td colspan="2" align="right">'.TEXT_MONTHLY_SALES_TAX.'</td><td>'.$currencies->display_price(($total_wtax-$total),0).'</td></tr>
<tr><td colspan="2" align="right">Product Cost:</td><td>'.$currencies->display_price($total_cost,0).'</td></tr>

<tr><td colspan="2" align="right">Refunds:</td><td>'.$currencies->display_price($sum_rets,0).'</td></tr>

<tr><td colspan="2" align="right">Avg. GP %</td><td>'.$total_avg_percent.'</td></tr>
<tr><td>
<form action="stats_sales.php?download=1&month='.$month.'&year='.$year.'" method="post">
<input type="submit" value="Download CSV" name="download">
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