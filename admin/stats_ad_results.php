<?php

  require('includes/application_top.php');
  
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
  
    if (isset($HTTP_GET_VARS['start_date'])) {
    $start_date = $HTTP_GET_VARS['start_date'];
  } else {
    $start_date = date('Y-m-01');
  }

  if (isset($HTTP_GET_VARS['end_date'])) {
    $end_date = $HTTP_GET_VARS['end_date'];
  } else {
    $end_date = date('Y-m-d');
  }
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">

<?php
  if ($printable != 'on') {
  require(DIR_WS_INCLUDES . 'header.php');
  }; ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
  <?php 
   if ($printable != 'on') {;?>
   
		<?php }; ?>
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>     
      <tr><td><table>
<tr><td></td><td class="main">
<?php
    echo tep_draw_form('date_range','stats_ad_results.php' , '', 'get');
    echo ENTRY_STARTDATE . tep_draw_input_field('start_date', $start_date). '&nbsp;';
    echo '';
    echo ENTRY_TODATE . tep_draw_input_field('end_date', $end_date). '&nbsp;';
    echo ENTRY_PRINTABLE . tep_draw_checkbox_field('printable', $print). '&nbsp;';
    echo ENTRY_SORTVALUE . tep_draw_checkbox_field('total_value', $total_value). '&nbsp;&nbsp;';
    echo '<input type="submit" value="'. ENTRY_SUBMIT .'">';
    echo '</td></form>';

    $grand_total_value = 0;
    $total_number_sales = 0;
?>
</td></tr>
</table></td></tr>          
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
			<tr>
			<?php echo tep_draw_form('ad_results', FILENAME_STATS_AD_RESULTS, 'action=new_product_preview', 'post', 'enctype="multipart/form-data"'); ?>
          </tr>
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_ADS; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER_OF_SALES; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_AMOUNT; ?>&nbsp;</td>
              </tr>
<?php

 if ($total_value =='on') {
  $ad_query_raw = "select distinct customers_advertiser, count(*) as count, sum(value) as total_value from " . TABLE_CUSTOMERS . ", " . TABLE_ORDERS . ", " . TABLE_ORDERS_TOTAL . " WHERE customers_advertiser <> '' AND date_purchased BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59:59' AND customers.customers_id = orders.customers_id and orders.orders_id = orders_total.orders_id and class = 'ot_subtotal' and date_purchased group by customers_advertiser ORDER BY total_value DESC";
  } else {
     $ad_query_raw = "select distinct customers_advertiser, count(*) as count, sum(value) as total_value from " . TABLE_CUSTOMERS . ", " . TABLE_ORDERS . ", " . TABLE_ORDERS_TOTAL . " WHERE customers_advertiser <> '' AND date_purchased BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59:59' AND customers.customers_id = orders.customers_id and orders.orders_id = orders_total.orders_id and class = 'ot_subtotal' and date_purchased group by customers_advertiser";
   } 

  $products_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_query_raw, $products_query_numrows);

  $ad_query = tep_db_query($ad_query_raw);
  while ($ads = tep_db_fetch_array($ad_query)) {
    $rows++;

    if (strlen($rows) < 2) {
      $rows = '0' . $rows;
    }
?>
 
                <tr class="dataTableRow" onmouseover="this.className='dataTableRowOver';" onmouseout="this.className='dataTableRow'">
                <td class="dataTableContent"><?php echo $rows; ?>.</td>
                <td class="dataTableContent"><?php echo $ads['customers_advertiser']; ?></td>
                <td class="dataTableContent"><?php echo $ads['count']; ?></td>
                <td class="dataTableContent" align="right"><?php echo $currencies->format($ads['total_value']); ?>&nbsp;</td>
              </tr>
<?php
  $grand_total_value = $grand_total_value + $ads['total_value'];
  $total_number_sales = $total_number_sales + $ads['count'];
  }
?>

                <tr bgcolor="#F6F6F6">
                <td class="dataTableContent"><b><?php echo ENTRY_TOTAL; ?></b></td>
                <td class="dataTableContent"></td>
                <td class="dataTableContent"><b><?php echo $total_number_sales; ?></b></td>
                <td class="dataTableContent" align="right"><b><?php echo $currencies->format($grand_total_value); ?></b>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td colspan="3">
			<!--- <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?>&nbsp;</td>
              </tr>
            </table> --->
			</td>
          </tr>
        </table></td>
      </tr>
    </table></td>

  </tr>
</table>



<?php
  if ($printable != 'on') {
 ;
  }
?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>