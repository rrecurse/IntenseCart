<?php
/*
  $Id: stats_products_purchased.php,v 1.29 2003/06/29 22:50:52 hpdl Exp $

  
  

  Copyright (c) 2003 IntenseCart eCommerce

  
*/
//die ();
  require('includes/application_top.php');
  if (!tep_session_is_registered('login')){
	  require('includes/supplier_area_top.php');
  }
  
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
  
  $suppliers_id = $_SESSION['login'];

  $IXAdminID = $_REQUEST['IXAdminID'];
  
  $years_array = array();
  $years_query = tep_db_query("select distinct year(date_purchased) as date_purchased from " . TABLE_ORDERS . "");
  while ($year = tep_db_fetch_array($years_query)){
  	$years_array[] = array('id' => $year['date_purchased'], 'text' => $year['date_purchased']);
  }

  $months_name = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
  $months_array = array();
  for ($i = 1; $i < 13; $i++){
  	$months_array[] = array('id' => $i, 'text' => strftime('%B', mktime(0,0,0,$i,15)));
  }

$type_array = array(array('id' => 'daily', 'text' => 'One Month'),
					array('id' => 'monthly', 'text' => 'One Year'),
					array('id' => 'yearly', 'text' => 'All Year') );
 ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header_supplier.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<center>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
<!--    <td width="<?php//echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php// echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">-->
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
<!--        </table></td>-->
<!-- body_text //-->

	<tr>
		<td>
<?php 
/*echo "<form method='post' action = 'supplier_s_statistic.php?IXAdminID=" .$IXAdminID . "'>";*/
			 echo tep_draw_form('year', FILENAME_SUPPLIER_STATISTIC, '', 'get');?><!--<table border="0" width="100%" cellpadding="0" cellspacing="0">-->
			<?php //echo tep_draw_hidden_field('month', $HTTP_GET_VARS['end_month']); ?>		
			<?php echo tep_draw_hidden_field('IXAdminID', $IXAdminID);?>
			<?php echo 'Type: ' . ' ' . tep_draw_pull_down_menu('type', $type_array, (($HTTP_GET_VARS['type']) ? $HTTP_GET_VARS['type'] : 'daily'), 'onChange="this.form.submit();"'); ?>
		</td><!--</table>-->
		<?php 
			switch($HTTP_GET_VARS['type']){
				case 'yearly': break;
				case 'monthly': 
					echo '<td align="left">' . 'Year: ' . ' ' . tep_draw_pull_down_menu('year', $years_array, (($HTTP_GET_VARS['year']) ? $HTTP_GET_VARS['year'] : date('Y')), 'onChange="this.form.submit();"') . '</td>';
					break;
				default: case 'daily':
					echo '<td align="left">' . 'Month: ' . ' ' . tep_draw_pull_down_menu('month', $months_array, (($HTTP_GET_VARS['month']) ? $HTTP_GET_VARS['month'] : date('n')), 'onChange="this.form.submit();"') . '</td>';
					echo '<td align="left">' . 'Year: ' . ' ' . tep_draw_pull_down_menu('year', $years_array, (($HTTP_GET_VARS['year']) ? $HTTP_GET_VARS['year'] : date('Y')), 'onChange="this.form.submit();"') . '</td>';     
					break;
			}
		?>	
	</tr>
<?php echo "</form>";?>	
      <tr>
        <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php 
	$start_year = 2000;
	$start_month = '1';
	$end_year = date(Y) + 1;
	$end_month = '1';
	$order =  " AND TO_DAYS(date_purchased) >= TO_DAYS('" . $start_year . "-" . $start_month . "-01') AND TO_DAYS(o.date_purchased) <= TO_DAYS('" . $end_year . "-" . $end_month . "-01') -1"; 
    $order1 =  " AND TO_DAYS(date_purchased) >= TO_DAYS('" . $start_year . "-" . $start_month . "-01')  AND TO_DAYS(o.date_purchased) <= TO_DAYS('" . $end_year . "-" . $end_month . "-01') -1";

  switch($HTTP_GET_VARS['type']){
  	case 'daily': 
		$start_year = $HTTP_GET_VARS['year'];
		$start_month = $HTTP_GET_VARS['month'];
		$end_year = $HTTP_GET_VARS['year'];
		$end_month = $HTTP_GET_VARS['month'] + 1;
		if ($end_month == '13') {$end_month = '1'; $end_year = $end_year +1;}
		$order =  " AND TO_DAYS(date_purchased) >= TO_DAYS('" . $start_year . "-" . $start_month . "-01') AND TO_DAYS(o.date_purchased) <= TO_DAYS('" . $end_year . "-" . $end_month . "-01') -1"; 
	    $order1 =  " AND TO_DAYS(date_purchased) >= TO_DAYS('" . $start_year . "-" . $start_month . "-01')  AND TO_DAYS(o.date_purchased) <= TO_DAYS('" . $end_year . "-" . $end_month . "-01' -1) ";		
		break;
	case 'monthly':
		$start_year = $HTTP_GET_VARS['year'];  
		$start_month = '01';
		$end_year = $HTTP_GET_VARS['year'] + 1;
		$end_month = '01';		
        $order =  " AND TO_DAYS(date_purchased) >= TO_DAYS('" . $start_year . "-" . $start_month . "-01') AND TO_DAYS(o.date_purchased) <= TO_DAYS('" . $end_year . "-" . $end_month . "-01') - 1"; 
        $order1 =  " AND TO_DAYS(date_purchased) >= TO_DAYS('" . $start_year . "-" . $start_month . "-01')  AND TO_DAYS(o.date_purchased) <= TO_DAYS('" . $end_year . "-" . $end_month . "-01') - 1 "; 		
		break;
	default: 
//        $order =  " AND TO_DAYS(date_purchased)"; 
		break;
  }

?>			
				<tr>
				<td class="smallText" align="left"><?php echo 'From' . ' ' . date("d-M-Y", mktime(0,0,0,$start_month  ,1,$start_year)); ?>&nbsp;&nbsp;<?php echo 'To' . ' ' . date("d-M-Y", mktime(0,0,0,$end_month  ,0,$end_year)); ?></td>
				</tr>
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PURCHASED; ?>&nbsp;</td>
				<td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_VIEWED; ?>&nbsp;</td>
				<td class="dataTableHeadingContent" align="center"><?php echo 'Total Price'; ?>&nbsp;</td>
              </tr>
<?php
  if (isset($HTTP_GET_VARS['page']) && ($HTTP_GET_VARS['page'] > 1)) $rows = $HTTP_GET_VARS['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;
//  $products_query_raw = "select p.products_id, p.products_ordered, p.suppliers_id, pd.products_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . $languages_id. "' and p.suppliers_id = '" . (int)$suppliers_id . "' and p.products_ordered > 0 group by pd.products_id order by p.products_ordered DESC, pd.products_name";
//	$products_query_raw = "select p.products_id, p.products_ordered, p.suppliers_id, pd.products_name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.suppliers_id = '" . (int)$suppliers_id . "' and p.products_ordered > 0 group by pd.products_id order by p.products_ordered DESC, pd.products_name";	
  $products_query_raw = "select p.products_id, p.products_ordered, pd.products_name, pd.products_viewed from  " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_ORDERS . " o, " . TABLE_ORDERS_PRODUCTS . " od where od.orders_id = o.orders_id and od.products_id = p.products_id and pd.products_id = p.products_id and pd.language_id = '" . $languages_id. "' and p.suppliers_id = '" . (int)$suppliers_id . "'" . $order . " group by pd.products_id order by p.products_ordered DESC, pd.products_name";
//  $products_query_raw = "select p.products_id, p.products_ordered, pd.products_name, pd.products_viewed from  " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_ORDERS . " o, " . TABLE_ORDERS_PRODUCTS . " od where od.orders_id = o.orders_id and od.products_id = p.products_id and pd.products_id = p.products_id and pd.language_id = '" . $languages_id. "' and p.suppliers_id = '" . (int)$suppliers_id . "' and p.products_ordered > 0" . $order . " group by pd.products_id order by p.products_ordered DESC, pd.products_name";  
  $products_query = tep_db_query($products_query_raw);
  $products_query_numrows = tep_db_num_rows($products_query);
  $products_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_query_raw, $products_query_numrows);

  $rows = 0;
  $total = 0;

  while ($products = tep_db_fetch_array($products_query)) {
  	$products_id = $products['products_id'];
	$orders_products_query = tep_db_query("select products_price, final_price from " . TABLE_ORDERS_PRODUCTS . " where products_id = '" . $products_id . "'");
	$products_purchased = tep_db_num_rows($orders_products_query);
	$final_price = 0;
	
	while ($orders_products = tep_db_fetch_array($orders_products_query)){
		$final_price += $orders_products['final_price'];
	}
	
	$total += $final_price;
//	print_r($products_query);
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php  echo tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=new_product_preview&read=only&pID=' . $products['products_id']); ?>'">
                <td class="dataTableContent"><?php echo $rows; ?>.</td>
                <td class="dataTableContent"><?php  echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=new_product_preview&read=only&pID=' . $products['products_id']/* . '&origin=' . FILENAME_STATS_PRODUCTS_PURCHASED . '?page=' . $HTTP_GET_VARS['page'], 'NONSSL'*/) . '">' . $products['products_name'] . '</a>'; ?></td>
                <td class="dataTableContent" align="center"><?php echo $products_purchased;//$products['products_ordered']; ?>&nbsp;</td>
				<td class="dataTableContent" align="center"><?php echo $products['products_viewed']; ?>&nbsp;</td>
				<td class="dataTableContent" align="center"><?php echo $currencies->format($final_price); ?>&nbsp;</td>
              </tr>
<?php
  }
?>
            </table></td>
          </tr>
		  <tr><td colspan="3" class="smallText" align="right"><?php echo 'Total: ' . $currencies->format($total);?></td></tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
</center>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
