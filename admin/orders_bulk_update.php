<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $orders_statuses = array();
  $orders_status_array = array();
  $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "' ORDER BY orders_status_id");
  while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                               'text' => $orders_status['orders_status_name']);
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
  }

  function prepare_date($date) {
    if (preg_match('/(\d+)-(\d+)-(\d\d\d\d)/',$date,$dp)) return date('Y-m-d h:i:s',mktime(0,0,0,$dp[1],$dp[2],$dp[3])-STORE_TZ*3600);
    return $date;
  }

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');


  include(DIR_WS_CLASSES . 'order.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Batch Orders</title>

<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>

</head>
<body style="margin:0; background:transparent;">
<?
    require(DIR_WS_INCLUDES . 'header.php');

    if (isset($_POST['set_status']) && isset($_POST['order_id'])) {
      $orders=Array();
      $set_status=$_POST['set_status']+0;
      foreach ($_POST['order_id'] AS $oID) {
	$orders[$oID]=new order($oID);
	$orders[$oID]->prepareStatus($set_status);
      }
      foreach ($_POST['order_id'] AS $oID) $orders[$oID]->setStatus($set_status);
    }

    $status=isset($_GET['status'])?$_GET['status']+0:1;
    $orders_query_raw = "select o.orders_id, o.customers_id, o.customers_company, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.orders_status_id = '" . (int)$status . "' and ot.class = 'ot_total' order by o.orders_id DESC";
    $orders_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
    $orders_query = tep_db_query($orders_query_raw);
    $ct=0;
?><div style="padding:5px;">
	<form action="orders_bulk_update.php" method="post">
<p>View Orders: <?=tep_draw_pull_down_menu('status',$orders_statuses,$status,'onChange="document.location=\'orders_bulk_update.php?status=\'+this.value"')?></p>
	    <table width="100%" border="0" cellpadding="0" cellspacing="0">

<tr style="background-color:#6295FD;">
<td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Select</td> <td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Order #</td> <td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Customer</td> <td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Order Total</td> <td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Controls</td> <td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Purchased</td> <td style="height:22px; color:#FFFFFF; font:bold 12px arial; text-align:center;">Status</td>
</tr>

<?
    while ($orders = tep_db_fetch_array($orders_query)) {
      $oInfo = new objectInfo($orders);
?>
          <tr>
            <td width="20" align="center" class="tableinfo_right-btm"><input type="checkbox" name="order_id[]" value="<?=$orders['orders_id']?>"></td>
            <td width="63" align="center" class="tableinfo_right-btm"><b><?php echo $orders['orders_id']; ?></b></td>
            <td width="117" align="center" class="tableinfo_right-btm"><div style="width:112px; white-space:nowrap; overflow-x:hidden; color:#000000;"><b>
<? if ($orders['customers_company']) { ?><?=$orders['customers_company']?><? } else { ?><?=$orders['customers_name']?><? } ?>
			</b></div></td>
            <td width="88" align="center" class="tableinfo_right-btm align_right"><b><?php echo strip_tags($orders['order_total']); ?></b></td>
            <td width="79" align="center" class="tableinfo_right-btm" style="font-weight:normal;">
			
			<a href="orders_view.php?oID=<?php echo $orders['orders_id'] . '&action=edit'?>" style="font: 10px verdana; color:0000FF;"><u>view</u></a>
			
             
             | <?php echo '<a href="' . tep_href_link(FILENAME_EDIT_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID='. $orders['orders_id']) . '" style="font: 10px verdana; color:0000FF;"><u>edit</u></a>'  ?></td>
            <td width="96" height="23" align="center" class="tableinfo_right-btm"><b><?php echo tep_date_short($orders['date_purchased']); ?></b></td>
            <td width="98" height="23" align="center" class="tableinfo_right-end"><b><?php echo $orders['orders_status_name']; ?></b></td>
            </tr>
<?php
    }
?>
  </table>
<br>
<p style="color:#FF0000"><b>Change selected orders status to:</b> &nbsp; <?=tep_draw_pull_down_menu('set_status',$orders_statuses,$status+1)?> <input type="submit" value="Update Orders"></p>
  
  </form>
    <table width="100%">
              <tr>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td style="font:bold 11px arial;"><?php echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
                    <td align="right" style="font:bold 11px arial; padding-top:12px;" class="pagejump"><?php echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></div>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
