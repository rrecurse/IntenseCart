<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $orders_statuses = array();
  $orders_status_array = array();
  $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "'");
  while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                               'text' => $orders_status['orders_status_name']);
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
  }

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
// BOF Admin Only Comments Box v1.2 [3004]
      case 'update_admin_comments':
	  	    $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
		      $comments = tep_db_prepare_input($HTTP_POST_VARS['admin_comments']);
		      $order_updated = false;
		
		      tep_db_query("insert into admin_comments (orders_id, date_added, comments) values ('" . (int)$oID . "', now(), '" . tep_db_input($comments)  . "')");

        $order_updated = true;
      
        if ($order_updated == true) {
         $messageStack->add_session('Admin Comments Updated', 'success');
        } else {
          $messageStack->add_session('Admin Comments Not Updated', 'warning');
        }

        tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=edit'));
        break;
// EOF Admin Only Comments Box v1.2
      case 'update_order':
        $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
		$customers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
        $status = tep_db_prepare_input($HTTP_POST_VARS['status']);
        $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
        $ups_track_num = tep_db_prepare_input($HTTP_POST_VARS['ups_track_num']);
        $usps_track_num = tep_db_prepare_input($HTTP_POST_VARS['usps_track_num']);
        $fedex_track_num = tep_db_prepare_input($HTTP_POST_VARS['fedex_track_num']);
        $order_updated = false;

//-- Tracking contribution begin -->
      $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, fedex_track_num, ups_track_num, usps_track_num, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . tep_db_input($oID) . "'");
//-- Tracking contribution end -->

        $check_status = tep_db_fetch_array($check_status_query);

        if ( ($check_status['orders_status'] != $status) || tep_not_null($comments)) {
          tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int)$oID . "'");
          $order_updated = true;

          $customer_notified = '0';
          if ($HTTP_POST_VARS['notify'] == 'on' & ($ups_track_num == '' & $fedex_track_num == '' & $usps_track_num == '' ) ) {
            $notify_comments = '';
            if ($HTTP_POST_VARS['notify_comments'] == 'on') {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";
              if ($comments == null)
                $notify_comments = '';
            }

            $email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';

          } else {
            if ($HTTP_POST_VARS['notify'] == 'on' & ($ups_track_num == '' or $fedex_track_num == '' or $usps_track_num == '' ) ) {
            $notify_comments = '';
            if ($HTTP_POST_VARS['notify_comments'] == 'on') {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";
              if ($comments == null)
                $notify_comments = '';
            }
            if ($ups_track_num == null) {
              $ups_text = '';
             }else{
              $ups_text = 'UPS: ';
              $ups_track_num = $ups_track_num . "\n";
            }
            if ($fedex_track_num == null) {
              $fedex_text = '';
             }else{
              $fedex_text = 'Fedex: ';
              $fedex_track_num = $fedex_track_num . "\n";
            }
            if ($usps_track_num == null) {
              $usps_text = '';
             }else{
              $usps_text = 'USPS: ';
              $usps_track_num = $usps_track_num . "\n";
            }
			
            $email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . EMAIL_TEXT_TRACKING_NUMBER . "\n" . $ups_text . $ups_track_num . $fedex_text . $fedex_track_num . $usps_text . $usps_track_num . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';
          }
          }
        } else {
          if ($HTTP_POST_VARS['notify'] == 'on' & (tep_not_null($ups_track_num) & tep_not_null($fedex_track_num) & tep_not_null($usps_track_num) ) ) {
            $notify_comments = '';
            if ($HTTP_POST_VARS['notify_comments'] == 'on') {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";
              if ($comments == null)
                $notify_comments = '';
			}

            $email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . EMAIL_TEXT_TRACKING_NUMBER . "\n" . 'UPS: ' . $ups_track_num . "\n" . 'Fedex: ' . $fedex_track_num . "\n" . 'USPS: ' . $usps_track_num . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';
          }
	}

        tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");
        if (tep_not_null($ups_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set ups_track_num = '" . tep_db_input($ups_track_num) . "' where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }
    
        if (tep_not_null($usps_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set usps_track_num = '" . tep_db_input($usps_track_num) . "' where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }                                                         
        if (tep_not_null($fedex_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set fedex_track_num = '" . tep_db_input($fedex_track_num) . "' where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }

        if ($order_updated == true) {
         $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
        } else {
          $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
        }

        tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=edit'));
        break;
      case 'deleteconfirm':
        $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);

        tep_remove_order($oID, $HTTP_POST_VARS['restock']);

        tep_redirect(tep_href_link(FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('oID', 'action'))));
        break;
    }
  }

  if (($action == 'edit') && isset($HTTP_GET_VARS['oID'])) {
    $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
	$customers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
    $orders_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
    $order_exists = true;
    if (!tep_db_num_rows($orders_query)) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    }
  }

  include(DIR_WS_CLASSES . 'order.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/tabber.js"></script>
<link rel="stylesheet" href="js/tabber.css" TYPE="text/css" MEDIA="screen">

<script type="text/javascript">
document.write('<style type="text/css">.tabber{display:none;}<\/style>');
</script>
<script type="text/javascript">

function contentChanged() {
//  alert('resize');
  top.resizeIframe('myframe');
}
</script>
</head>
<body STYLE="background-color:transparent; margin:0">
<!-- header //-->
<?php
  require(DIR_WS_INCLUDES . 'header.php');
?>

<table border="0" width="571" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" colspan="2" valign="top"><?php
  if (($action == 'edit') && ($order_exists == true)) {
    $order = new order($oID);
?>
<table width="571" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="100%" valign="top" colspan="2"><table border="0" width="571" cellspacing="0" cellpadding="0">
      <tr>
        <td valign="top" style="padding-top:2px; padding-bottom:5px; padding-left:4px; padding-right:5px;"><table width="555" align="center" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF; border: solid 1px #999999;">
          <tr><td><table width="555" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="185" valign="top"><table width="183" border="0" align="center" cellpadding="0" cellspacing="0">
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td colspan="2"  style="height:20px; background-color:#6295FD; font:bold 13px arial;color:#FFFFFF;">&nbsp; Billing Information:</td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="background-color:#DEEAF8; height:20px;"><table width="183" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px Tahoma; color:#0B2D86">&nbsp; Attn:&nbsp; <a href="<?=tep_href_link(FILENAME_ORDERS,'cID='.$order->customer['id'])?>" style="font:bold 11px Tahoma; color:#0000FF"><?php echo $order->billing['name']; ?></a></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding-top:1px; background-color:#F0F5FB;"><table width="183" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td valign="top" class="tableinfo_orders" style="height:18px;">
						  <div style="position:relative">
						  <div style="position:absolute; font:bold 11px arial; color:#000000; line-height:18px;">
							   <?=$order->billing['company'] ? $order->billing['company'].'<br>' : ''?>  
							  <?=$order->billing['street_address']?><?=$order->billing['suburb'] ? ','.$order->billing['suburb'] : ''?><br>
							  <?php echo $order->billing['city']; ?>, <?=$order->billing['state']?><br>
							  <?php echo $order->billing['postcode']; ?> - <?php echo $order->billing['country']; ?><br>
							  Phone: <?php echo $order->customer['telephone']; ?><br>
							  Fax: <?php echo $order->customer['fax']; ?><br>
						    Email: <?php echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $order->customer['email_address'] . '</u></a>'; ?>						    </div></div> </td>
                          </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders">&nbsp;</td>
                          </tr>
                        <tr>
                          <td class="tableinfo_orders">&nbsp;</td>
                          </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders">&nbsp;</td>
                          </tr>
                        <tr>
                          <td class="tableinfo_orders">&nbsp;</td>
                        </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders">&nbsp;</td>
                        </tr>
                        <tr>
                          <td class="tableinfo_orders">&nbsp;</td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td style="height:1px;"></td>
                    </tr>
            </table></td>
            <td width="185" align="center" valign="top"><table width="183" border="0" align="center" cellpadding="0" cellspacing="0">
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td colspan="2"  style="height:20px; background-color:#6295FD;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Shipping Information:</span></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="background-color:#DEEAF8; height:20px;"><table width="183" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px Tahoma; color:#0B2D86">&nbsp; Attn:&nbsp; <?php echo $order->delivery['name']; ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding-top:1px; background-color:#F0F5FB;"><table width="183" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;"><?php echo $order->delivery['street_address']; ?>, <?php echo $order->delivery['suburb']; ?></td>
                          </tr>
                        <tr>
                          <td style="height:18px; font:bold 11px arial; color:#000000;" bgcolor="#EBF1F5" class="tableinfo_orders"><?php echo $order->delivery['city']; ?>, <?php echo $order->delivery['state']; ?></td>
                          </tr>
                        <tr>
                          <td style="height:18px; font:bold 11px arial; color:#000000;" class="tableinfo_orders"><?php echo $order->delivery['postcode']; ?> - <?php echo $order->delivery['country']; ?></td>
                          </tr>
                        <tr>
                          <td style="height:18px; font:bold 11px arial; color:#000000;" bgcolor="#EBF1F5" class="tableinfo_orders">Phone: <?php echo $order->customer['telephone']; ?></td>
                          </tr>
                        
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Prefered
                            Method:&nbsp; <? if ($order->info['shipping_method']) { ?><?=$order->info['shipping_method']?><? } else { ?><font style="color:#FF0000;"><b>None</b></font><? } ?></td>
                        </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Actual Ship Method:&nbsp; <? if ($order->info['ups_track_num']) { ?>UPS
  <?
  }
  else if ($order->info['fedex_track_num']) { ?>
  FedEx
  <?
  }
  else if ($order->info['usps_track_num']) { ?>
  USPS
			
<?php
  } else {
?>
  
  <a href="orders_view.php?oID=<?php echo $HTTP_GET_VARS['oID'] . '&action=edit#tracking_number'?>" style="color:#0000FF; font:normal 10px verdana;"><u>ship now</u></a>
  
 <?php 
  } 
 ?>						  </td>
                        </tr>
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Tracking #:&nbsp; <? if ($order->info['ups_track_num']) { ?><a style="font:bold 11px arial;" href="<?=sprintf(UPS_TRACKING_URL,$order->info['ups_track_num'])?>" target="_new"><?=$order->info['ups_track_num']?></a>
  <?
  }
  else if ($order->info['fedex_track_num']) { ?>
  <a href="<?=sprintf(FEDEX_TRACKING_URL,$order->info['fedex_track_num'])?>" target="_new" style="font:bold 11px arial;"><?=$order->info['fedex_track_num']?></a>
  <?
  }
  else if ($order->info['usps_track_num']) { ?>
  <a href="<?=sprintf(USPS_TRACKING_URL,$order->info['usps_track_num'])?>" target="_new" style="font:bold 11px arial;"><?=$order->info['usps_track_num']?></a>
			
<?php
  } else {
?>
  
  <a href="orders_view.php?oID=<?php echo $HTTP_GET_VARS['oID'] . '&action=edit#tracking_number'?>" style="color:#0000FF; font:10px verdana;"><u>enter tracking #</u></a>
  
 <?php 
  } 
 ?>						  </td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td style="height:1px;"></td>
                    </tr>
            </table>			</td>
            <td width="185" valign="top"><table width="183" border="0" align="center" cellpadding="0" cellspacing="0">
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#FFFFFF"></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:1px; background-color:#8CA9C4;"></td>
                     </tr>
                     <tr>
                       <td colspan="2"  style="height:20px; background-color:#6295FD;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Payment
                       Information:</span></td>
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="background-color:#DEEAF8; height:20px;"><table width="183" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px tahoma; color:#000000;">&nbsp; Order ID:&nbsp; <?php echo $HTTP_GET_VARS['oID'] ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td valign="top" style="padding-top:1px; background-color:#F0F5FB;">
					  <table width="183" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">
						  <b>Method:&nbsp;</b> <?php echo $order->info['payment_method']; ?></td>
                          </tr>
						  <?php
    if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {
?>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Card
                            Type:&nbsp; <?php echo $order->info['cc_type']; ?></td>
                          </tr>
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Card
                            Name:&nbsp; <?php echo $order->info['cc_owner']; ?></td>
                          </tr>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Card #:&nbsp; <?php echo $order->info['cc_number']; ?></td>
                          </tr>
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">&nbsp;</td>
                        </tr>						
						<?php
    }
?>
                        <tr>
                          <td bgcolor="#EBF1F5" class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">
						  Date Purchased:&nbsp; <?php echo tep_date_short($order->info['date_purchased']); ?>						  </td>
                        </tr>
                        <tr>
                          <td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Time Purchased:&nbsp; <font style="text-transform:uppercase;"><?=$order->info['local_time_purchased'] ? tep_time_format($order->info['local_time_purchased'],$order->info['local_timezone']) : tep_time_format($order->info['date_purchased'],'GMT'); ?></font>						   </td>
                        </tr>
                      </table>					  </td>
                    </tr>
                    <tr>
                      <td style="height:1px;"></td>
                    </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="555" cellspacing="0" cellpadding="0" style="background-color:#F0F5FB; margin:0; padding:0">
		<tr>
            <td style="height:20px; width:555px; background-color:#6295FD; font:bold 13px arial;color:#FFFFFF; margin:0; padding:0" colspan="8">&nbsp;Order ID:&nbsp; <?php echo $HTTP_GET_VARS['oID'] ?></td>
        	</tr>
		<tr>
            <td align="center" bgcolor="#DEEAF8" style="height:20px; width:162px; font:bold 10px tahoma; color:#0B2D86"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
            <td align="center" bgcolor="#DEEAF8" style="width:59px; font:9px tahoma; color:#000000;"> Qnty</td>
            <td align="center" bgcolor="#DEEAF8" style="width:61px; font:9px tahoma; color:#000000;">Model</td>
            <td align="center" bgcolor="#DEEAF8" style="width:60px; font:9px tahoma; color:#000000;">Price</td>
            <td align="center" bgcolor="#DEEAF8" style="width:60px; font:9px tahoma; color:#000000;">Tax %</td>
            <td align="center" bgcolor="#DEEAF8" style="width:74px; font:9px tahoma; color:#000000;">Sub-total</td>
            <td align="center" bgcolor="#DEEAF8" style="width:73px; font:bold 9px tahoma; color:#0B2D86">Total</td>
		</tr>
<?
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
?>
		<tr>
		  <td align="center" class="tableinfo_right-btm" style="width:162px; font:bold 11px arial; color:#000000; padding-left:5px;">
<?=$order->products[$i]['name']?></td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 11px arial; color:#000000;">
		  <? if ($order->products[$i]['qty']) { ?><?=$order->products[$i]['qty']?><? } else { ?>&nbsp;<? } ?>		  </td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 11px arial; color:#000000;">
		   <? if ($order->products[$i]['model']) { ?><?=$order->products[$i]['model']?><? } else { ?>&nbsp;<? } ?>		   </td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 11px arial; color:#000000;"><?=$currencies->format($order->products[$i]['final_price'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 11px arial; color:#000000;"><?=tep_display_tax_value($order->products[$i]['tax'])?>%</td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 11px arial; color:#000000;"><?=$currencies->format($order->products[$i]['final_price']*$order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-end" style="font:bold 11px arial; color:#000000;"><?=$currencies->format(tep_add_tax($order->products[$i]['final_price']*$order->products[$i]['qty'],$order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value'])?></td>
		</tr>
<? if (isset($order->products[$i]['attributes']) && (sizeof($order->products[$i]['attributes']) > 0)) {
        for ($j = 0, $k = sizeof($order->products[$i]['attributes']); $j < $k; $j++) {
?>
		<tr class="<?=$j&1?'rowOdd':'rowEven'?>">
		  <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm" style="font:11px arial; color:#000000;">
          &#8226; &nbsp;<?=$order->products[$i]['attributes'][$j]['option']?>:<?=$order->products[$i]['attributes'][$j]['value']?>		  </td>
		   <td class="tableinfo_right-btm">&nbsp;</td>
		    <td class="tableinfo_right-btm">&nbsp;</td>
			 <td class="tableinfo_right-btm" align="center">
<?
          if ($order->products[$i]['attributes'][$j]['price'] != '0') echo '<font style="color:#FF0000;">' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</font>';
          echo '&nbsp;</td><td class="tableinfo_right-btm">&nbsp; </td><td class="tableinfo_right-btm">&nbsp;</td><td class="tableinfo_right-end">&nbsp;</td></tr>';
        } 
      }
  } 
?>	  <tr>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td colspan="2" class="tableinfo_right-end">&nbsp;</td>
</tr>
		<tr>
		  <td colspan="4" valign="top" bgcolor="#FFFFC4" class="tableinfo_right-btm" style="padding-top:5px; font:bold 11px arial; color:#000000">&nbsp; Customer Comments: <br><br><div style="padding:5px; font-weight:normal;"><?=$order->info['comments']?></div></td>
		  <td colspan="3" align="center" bgcolor="#EBF1F5" style="font:bold 11px arial; color:#000000;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
           
              <?php
    for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
      echo '              <tr>' . "\n" .
           '                <td height="23" class="tableinfo_right-btm" align="right" style="width:132px; padding-right:5px; font:bold 11px arial; color:#FF0000;">' . $order->totals[$i]['title'] . '</td>' . "\n" .
           '                <td align="center" class="tableinfo_right-end" style="width:75px; font:bold 11px arial; color:#000000;">' . $order->totals[$i]['text'] . '</td>' . "\n" .
           '              </tr>' . "\n";
    }
?>
            
          </table></td>
		  </tr>
          <tr>
            
<td align="right" colspan="8"></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2"><div class="tabber" onClick="contentChanged();">

     <div class="tabbertab">
	  <h2>Customer Coorespondance</h2>
	 <table border="0" cellspacing="0" cellpadding="0">
                    <tr> 
                      <td><table width="555" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><table width="550" border="1" cellpadding="0" cellspacing="0" bordercolor="#0099FF">
                              <tr>
                                <td width="114" align="center" class="smallText"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></td>
                                <td width="86" align="center" class="smallText"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></td>
                                <td width="109" align="center" class="smallText"><b><?php echo TABLE_HEADING_STATUS; ?></b></td>
                                <td width="236" align="center" class="smallText"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
                              </tr>
                              <?php
    $orders_history_query = tep_db_query("select orders_status_id, date_added, customer_notified, comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . tep_db_input($oID) . "' order by date_added");
    if (tep_db_num_rows($orders_history_query)) {
      while ($orders_history = tep_db_fetch_array($orders_history_query)) {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" align="center">' . tep_datetime_short($orders_history['date_added']) . '</td>' . "\n" .
             '            <td class="smallText" align="center">';
        if ($orders_history['customer_notified'] == '1') {
          echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK) . "</td>\n";
        } else {
          echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) . "</td>\n";
        }
        echo '            <td class="smallText">' . $orders_status_array[$orders_history['orders_status_id']] . '</td>' . "\n" .
             '            <td class="smallText"><br>' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>' . "\n" .
             '          </tr>' . "\n";
      }
    } else {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>' . "\n" .
             '          </tr>' . "\n";
    }
?>
                          </table></td>
                        </tr>
                        <tr>
                          <td class="main"><b><br>
                               <a name="customer_comments"></a> <?php echo TABLE_HEADING_COMMENTS; ?></b></td>
                        </tr>
                        <tr>
                          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
                        </tr>
                        <?php echo tep_draw_form('status', FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update_order'); ?>
						<tr>
                          <td valign="top" class="main"><?php echo tep_draw_textarea_field('comments', 'soft', '40', '5'); ?></td>
                        </tr>
                        <tr>
                          <td><?php include ("comment_bar.php"); ?></td>
                        </tr>
                        <tr>
                          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                        </tr>
                        <tr>
                          <td><table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="main"><b><?php echo ENTRY_NOTIFY_CUSTOMER; ?></b> <?php echo tep_draw_checkbox_field('notify', '', false); ?></td>
                              <td class="main"><b><?php echo ENTRY_NOTIFY_COMMENTS; ?></b> <?php echo tep_draw_checkbox_field('notify_comments', '', true); ?></td>
                            </tr>
                          </table></td>
                        </tr>
                        <tr>
                          <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '25'); ?></td>
                        </tr>
                        <tr>
                          <td><table width="100%" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td><table width="100%" border="0" cellpadding="2" cellspacing="0">
                                    <tr>
                                      <td class="main"><b><?php echo ENTRY_STATUS; ?></b> <?php echo tep_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status']); ?></td>
                                    </tr>
                                    <tr>
                                      <td align="right"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                                    </tr>
                                </table></td>
                              </tr>
                              <tr> </tr>
                          </table></td>
                           </tr><?php echo '</form>';?>
                        
                      </table></td>
                      </tr>
                  </table>
     </div>


     <div class="tabbertab">
	  <h2>Admin Only Comments</h2>
	  <table width="550" border="0" cellpadding="0" cellspacing="0">
                    <tr> 
                      <td><table width="550" border="1" cellpadding="0" cellspacing="0" bordercolor="#0099FF">
                          <tr> 
                            <td width="91" align="center" class="smallText"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></td>
                            <td align="center" class="smallText"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
                          </tr>
                          <?php
    $admin_comments_query = tep_db_query("select orders_id, date_added, comments from admin_comments where orders_id = '" . tep_db_input($oID) . "' order by date_added");
    if (tep_db_num_rows($admin_comments_query)) {
      while ($admin_history = tep_db_fetch_array($admin_comments_query)) {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" align="center">' . tep_datetime_short($admin_history['date_added']) . '</td>' . "\n" .
             '            <td class="smallText">' . tep_db_output($admin_history['comments']) . '&nbsp;</td>' . "\n" .
             '          </tr>' . "\n";
      }
    } else {

        echo '          <tr>' . "\n" .
             '            <td class="smallText" colspan="2">No Admin Comments.</td>' . "\n" .
             '          </tr>' . "\n";
    }
?>
                        </table></td>
                    </tr>
                    <tr> 
                      <td>
                        <table width="555" border="0" cellpadding="0" cellspacing="0" class="main">
                          <tr> 
                            <td class="main"><b>Admin Only Comments</b></td>
                          </tr>
						  <?php echo tep_draw_form('status', FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update_admin_comments'); ?> 
                          <tr> 
                            <td><?php echo tep_draw_textarea_field('admin_comments', 'soft', '40', '5', '', 'style="background-color:#FFFFD9;"'); ?></td>
                          </tr>
                          <tr> 
                            <td><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                          </tr><?php echo '</form>'?>
                        </table></td>
                    </tr>
					
  </table>
     </div>
	 
     <div class="tabbertab">
	  <h2>Shipping and Tracking</h2>
	  <table width="550" border="0" cellpadding="0" cellspacing="0">
	    <tr>
                          <td class="main">  
						  <table width="555" border="0" cellpadding="0" cellspacing="0">
	         <?php echo tep_draw_form('status', FILENAME_ORDERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update_order'); ?>  
	         <tr>
                          <td><a name="tracking_number"></a><b><?php echo TABLE_HEADING_UPS_TRACKING; ?>:</b></td>
						  <td><?php echo tep_draw_textbox_field('ups_track_num', '20', '18', '', $order->info['ups_track_num']); ?></td>
                        </tr>
                        <tr>
                          <td><b><?php echo TABLE_HEADING_FEDEX_TRACKING; ?>:</b></td>
						  <td><?php echo tep_draw_textbox_field('fedex_track_num', '20', '18', '', $order->info['fedex_track_num']); ?></td>
                        </tr>
                        <tr>
                          <td><b><?php echo TABLE_HEADING_USPS_TRACKING; ?>:</b></td>
						  <td><?php echo tep_draw_textbox_field('usps_track_num', '20', '18', '', $order->info['usps_track_num']); ?></td>
                        </tr>
                        <tr>
                          <td colspan="2"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                        </tr>
						<?
  if ($order->info['ups_track_num']) { ?>
		    <tr><td colspan="2"><a href="<?=sprintf(UPS_TRACKING_URL,$order->info['ups_track_num'])?>" target="_new">UPS Tracking: <?=$order->info['ups_track_num']?></a>
		    </td></tr><?
  }
  if ($order->info['fedex_track_num']) { ?>
		    <tr><td colspan="2"><a href="<?=sprintf(FEDEX_TRACKING_URL,$order->info['fedex_track_num'])?>" target="_new">FedEx Tracking: <?=$order->info['fedex_track_num']?></a>
		    </td></tr><?
  }
  if ($order->info['usps_track_num']) { ?>
		    <tr><td colspan="2"><a href="<?=sprintf(USPS_TRACKING_URL,$order->info['usps_track_num'])?>" target="_new">USPS Tracking: <?=$order->info['usps_track_num']?></a>
		    </td></tr><?php echo '</form>';?><?
  } ?></table>
  </td></tr>
  </table>
     </div>

</div></td>
      </tr>
      <tr>
		<td colspan="2" style="padding:10px;">		
		<?php echo '<a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $HTTP_GET_VARS['oID']) . '" TARGET="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a> <a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $HTTP_GET_VARS['oID']) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a> <a href="' . tep_href_link(FILENAME_EDIT_ORDERS, 'oID=' . $HTTP_GET_VARS['oID']) . '">' . tep_image_button('button_modifier.gif', IMAGE_EDIT) . '</a>'; ?></td>
      </tr>
    
<?php
  } else {
?>
      
<?php
  }
?>
</table>
</td>

  </tr>
</table>
  </tr>
</table>



</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
