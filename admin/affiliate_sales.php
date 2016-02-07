<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();


	// # check start and end Date
	$date_from = (isset($_GET['date_from'])) ? $_GET['date_from'] : date('01/01/Y',time());
	$date_to = (isset($_GET['date_to'])) ? $_GET['date_to'] : date('m/d/Y',time());

  if ($_GET['acID'] > 0) {

    $affiliate_sales_raw = "
	  SELECT s.*, 
			 os.orders_status_name AS orders_status, 
			 a.affiliate_firstname, 
			 a.affiliate_lastname,
			 o.date_purchased
	  from " . TABLE_AFFILIATE_SALES . " s 
      LEFT JOIN " . TABLE_ORDERS . " o ON s.affiliate_orders_id = o.orders_id 
      LEFT JOIN " . TABLE_ORDERS_STATUS . " os ON o.orders_status = os.orders_status_id AND language_id = " . $languages_id . "
      LEFT JOIN " . TABLE_AFFILIATE . " a ON a.affiliate_id = s.affiliate_id
      WHERE s.affiliate_id = '".$_GET['acID']."'
	  AND s.affiliate_date >= '".date('Y-m-d 00:00:00',strtotime($date_from))."'
	  AND s.affiliate_date <= '".date('Y-m-d 24:00:00',strtotime($date_to))."'
      ORDER BY s.affiliate_date DESC 
      ";
    $affiliate_sales_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $affiliate_sales_raw, $affiliate_sales_numrows);

  } else {

    $affiliate_sales_raw = "
	  SELECT s.*, 
			 os.orders_status_name AS orders_status, 
			 a.affiliate_firstname, 
			 a.affiliate_lastname,
			 o.date_purchased
	  from " . TABLE_AFFILIATE_SALES . " s 
      LEFT JOIN " . TABLE_ORDERS . " o ON s.affiliate_orders_id = o.orders_id 
      LEFT JOIN " . TABLE_ORDERS_STATUS . " os ON o.orders_status = os.orders_status_id AND language_id = ".$languages_id." 
      LEFT JOIN " . TABLE_AFFILIATE . " a ON a.affiliate_id = s.affiliate_id
	  WHERE s.affiliate_date >= '".date('Y-m-d 00:00:00',strtotime($date_from))."'
	  AND s.affiliate_date <= '".date('Y-m-d 24:00:00',strtotime($date_to))."'
      ORDER BY s.affiliate_date DESC 
      ";
    $affiliate_sales_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $affiliate_sales_raw, $affiliate_sales_numrows);
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="js/popcalendar.js"></script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td width="58"><img src="images/handshake-icon.gif" width="48" height="48"></td>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>

<td class="pageHeading" align="right">

					<?php echo tep_draw_form('date_range', 'affiliate_sales.php', '', 'get'); ?>
					  <table border="0" cellpadding="0" cellspacing="0">

                        <tr>
                          <td align="right" style="padding-top:2px;">
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?=$date_from?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding-top:1px; padding-left:3px; padding-right:3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?=$date_to?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td style="padding: 0 7px 0 0;">&nbsp;<a href="javascript:document.date_range.submit();"><font style="font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;">&nbsp;GO&nbsp;</font></a>                          </td>
                        </tr>
</table>
</form>

</td></tr>
        </table>
	<img src="affpulse.php?width=800&amp;height=315&amp;qtyprv_color=6295FD-1&amp;qty_color=85B761-1&amp;ret_color=BED9AA-1&amp;retprv_color=BED9AA-1&amp;ytd_color=11911C-25&amp;ytd_markcolor=E4E4E4-50&amp;ytd_mark=8&amp;ytd_thick=4&amp;prv_color=9FB9D6-25&amp;prv_markcolor=E4E4E4-50&amp;prv_mark=8&amp;prv_thick=4&amp;bg_color=F4F4F4&amp;bg_plot_color=FFFFFF&amp;x_font=8-0B2D86&amp;y_font=8-0B861D&amp;pad_left=38&amp;pad_top=13&amp;pad_bottom=15&amp;pad_right=35&amp;bar_width=72" width="100%" alt="">
</td>
      </tr>
      <tr>
        <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="4">
          <tr class="dataTableHeadingRow">
            <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_AFFILIATE; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_DATE; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ORDER_ID; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_VALUE; ?></td>
            <td class="dataTableHeadingContent" align="right" width="100"><?php echo TABLE_HEADING_PERCENTAGE; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_SALES; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
          </tr>
<?php
  if ($affiliate_sales_numrows > 0) {
    $affiliate_sales_values = tep_db_query($affiliate_sales_raw);
    $number_of_sales = '0';
    while ($affiliate_sales = tep_db_fetch_array($affiliate_sales_values)) {
      $number_of_sales++;
      if (($number_of_sales / 2) == floor($number_of_sales / 2)) {
        echo '          <tr class="dataTableRowSelected">';
      } else {
        echo '          <tr class="dataTableRow">';
      }

      $link_to = '<a href="orders.php?action=edit&oID=' . $affiliate_sales['affiliate_orders_id'] . '">' . $affiliate_sales['affiliate_orders_id'] . '</a>';
?>
            <td class="dataTableContent"><?php echo $affiliate_sales['affiliate_firstname'] . " ". $affiliate_sales['affiliate_lastname']; ?></td>
            <td class="dataTableContent" align="center"><?php echo tep_date_short($affiliate_sales['affiliate_date']); ?></td>
            <td class="dataTableContent" align="right"><?php echo $link_to; ?></td>
            <td class="dataTableContent" align="right">&nbsp;&nbsp;<?php echo $currencies->display_price($affiliate_sales['affiliate_value'], ''); ?></td>
            <td class="dataTableContent" align="right"><?php echo $affiliate_sales['affiliate_percent'] . "%" ; ?></td>
            <td class="dataTableContent" align="right">&nbsp;&nbsp;<?php echo $currencies->display_price($affiliate_sales['affiliate_payment'], ''); ?></td>
            <td class="dataTableContent" align="center"><?php if ($affiliate_sales['orders_status']) echo $affiliate_sales['orders_status']; else echo TEXT_DELETED_ORDER_BY_ADMIN; ?></td>
<?php
    }
  } else {
?>
          <tr class="dataTableRowSelected">
            <td colspan="7" class="smallText"><?php echo TEXT_NO_SALES; ?></td>
          </tr>
<?php
  }
  if ($affiliate_sales_numrows > 0 && (PREV_NEXT_BAR_LOCATION == '2' || PREV_NEXT_BAR_LOCATION == '3')) {
?>
          <tr>
            <td colspan="7"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText" valign="top"><?php echo $affiliate_sales_split->display_count($affiliate_sales_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_SALES); ?></td>
                <td class="smallText" align="right"><?php echo $affiliate_sales_split->display_links($affiliate_sales_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
  }
?>
        </table></td>
      </tr>
    </table></td>

  </tr>
</table>
<?php 
	if ($_GET['acID'] > 0) {
		echo '<td class="pageHeading" align="right"><a href="' . tep_href_link(FILENAME_AFFILIATE_STATISTICS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a></td>';
	} else {
		echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_SUMMARY, '') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>';
	}
?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php');?>
