<?php

  require('includes/application_top.php');

	// # report times based on default time zone in php config or override as seen below
	// # default application timezone is UCT set in /includes/application_top.php
	if(date_default_timezone_get() != 'EST' && STORE_TZ == '-5') { 
		date_default_timezone_set('EST');
	}

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

  function prepare_date($date) {
    if (preg_match('/(\d+)-(\d+)-(\d\d\d\d)/',$date,$dp)) return date('Y-m-d h:i:s',mktime(0,0,0,$dp[1],$dp[2],$dp[3])-STORE_TZ*3600);
    return $date;
  }

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {

      case 'update_admin_comments':
	  	    $oID = tep_db_prepare_input($_GET['oID']);
		      $comments = tep_db_prepare_input($_POST['admin_comments']);
		      $order_updated = false;
		
		      tep_db_query("insert into admin_comments (orders_id, date_added, comments) values ('" . (int)$oID . "', now(), '" . tep_db_input($comments)  . "')");

        $order_updated = true;
      
        if ($order_updated == true) {
         $messageStack->add_session('Admin Comments Updated', 'success');
        } else {
          $messageStack->add_session('Admin Comments Not Updated', 'warning');
        }

        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action')) . 'action=edit'));
        break;

      case 'update_order':
        $oID = tep_db_prepare_input($_GET['oID']);
        $status = tep_db_prepare_input($_POST['status']);
        $comments = tep_db_prepare_input($_POST['comments']);
        $ups_track_num = tep_db_prepare_input($_POST['ups_track_num']);
        $usps_track_num = tep_db_prepare_input($_POST['usps_track_num']);
        $fedex_track_num = tep_db_prepare_input($_POST['fedex_track_num']);
        $dhl_track_num = tep_db_prepare_input($_POST['dhl_track_num']);
        $order_updated = false;


      $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, fedex_track_num, ups_track_num, usps_track_num, dhl_track_num, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . tep_db_input($oID) . "'");

        $check_status = tep_db_fetch_array($check_status_query);

		tep_db_free_result($check_status_query);

        if ( ($check_status['orders_status'] != $status) || tep_not_null($comments)) {
          tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = NOW() WHERE orders_id = '" . (int)$oID . "'");
          $order_updated = true;

          $customer_notified = '0';
          if ($_POST['notify'] == 'on' & ($ups_track_num == '' & $fedex_track_num == '' & $usps_track_num == '' & $dhl_track_num == '' ) ) {
            $notify_comments = '';
            if ($_POST['notify_comments'] == 'on') {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";
              if ($comments == null)
                $notify_comments = '';
            }

            $email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';

          } else {
            if ($_POST['notify'] == 'on' & ($ups_track_num == '' or $fedex_track_num == '' or $usps_track_num == '' or $dhl_track_num == '' ) ) {
            $notify_comments = '';
            if ($_POST['notify_comments'] == 'on') {
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
            if ($dhl_track_num == null) {
              $dhl_text = '';
             }else{
              $dhl_text = 'DHL: ';
              $dhl_track_num = $dhl_track_num . "\n";
            }

            $email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . EMAIL_TEXT_TRACKING_NUMBER . "\n" . $ups_text . $ups_track_num . $fedex_text . $fedex_track_num . $usps_text . $usps_track_num . $dhl_text . $dhl_track_num . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';
          }
          }
        } else {
          if ($_POST['notify'] == 'on' & (tep_not_null($ups_track_num) & tep_not_null($fedex_track_num) & tep_not_null($usps_track_num) & tep_not_null($dhl_track_num) ) ) {
            $notify_comments = '';
            if ($_POST['notify_comments'] == 'on') {
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";
              if ($comments == null)
                $notify_comments = '';
			}

            $email = 'Dear ' . $check_status['customers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . EMAIL_TEXT_TRACKING_NUMBER . "\n" . 'UPS: ' . $ups_track_num . "\n" . 'Fedex: ' . $fedex_track_num . "\n" . 'USPS: ' . $usps_track_num . "\n" . 'DHL: ' . $dhl_track_num . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';
          }
	}

        tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");
        if (tep_not_null($ups_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set ups_track_num = '" . tep_db_input($ups_track_num) . "', last_modified = NOW() where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }
    
        if (tep_not_null($usps_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set usps_track_num = '" . tep_db_input($usps_track_num) . "', last_modified = NOW() where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }                                                         
        if (tep_not_null($fedex_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set fedex_track_num = '" . tep_db_input($fedex_track_num) . "', last_modified = NOW() where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }
        if (tep_not_null($dhl_track_num)) {
          tep_db_query("update " . TABLE_ORDERS . " set dhl_track_num = '" . tep_db_input($dhl_track_num) . "', last_modified = NOW() where orders_id = '" . tep_db_input($oID) . "'");
          $order_updated = true;
        }

        if ($order_updated == true) {
         $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
        } else {
          $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
        }

        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action')) . 'action=edit'));
        break;
      case 'deleteconfirm':
        $oID = tep_db_prepare_input($_GET['oID']);

        tep_remove_order($oID, $_POST['restock']);

        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action'))));
        break;
    }
  }

  if (($action == 'edit') && isset($_GET['oID'])) {
    $oID = tep_db_prepare_input($_GET['oID']);

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
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="refresh" content="600">
<title>Orders View</title>

<link rel="stylesheet" href="js/css.css" type="text/css">
<script src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>
<style type="text/css">

.AccordionPanelTab {
	cursor:pointer;
    margin:0;
	padding:0;
}

.AccordionPanelOpen .AccordionPanelTab tr {
	background-color: #FFFFC4;
}

.AccordionPanelOpen .AccordionPanelContent {
	background-color: #FFFFC4;
}

.AccordionPanelTabHover {
	background-color: #FFFFC4;
}

.AccordionPanelContent {
	border:0
}

.AccordionPanelTabHover table {
	background-color:#FFFFC4;
}

.pagejump select {
	font:8pt verdana;
}
.infoTable tr:nth-child(even) {
    background-color: #EBF1F5;
}

.dataTableHeadingContent a:link, .dataTableHeadingContent a:visited {
	font:bold 11px arial; 
	color: #FFF !important;
}

.dataTableHeadingContent a:hover, .dataTableHeadingContent a:active {
	color: yellow !important;
}

</style>

</head>
<body style="margin:0; background:transparent;">
<?php
  if (($action == 'edit') && ($order_exists == true)) {
    $order = new order($oID);
    $payments = $order->getPayments();

// # if order is from Amazon, color amazon-orange
if(!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') { 

	if (stripos($order->info['orders_source'],'Amazon-FBA') !== false) {
		$sourceStyle = '#86BBBB';
	} else { 
		$sourceStyle = '#EC994F';
	}

} else { 
	// # if customers group is vendor then color GREEN if not standard blue
	$sourceStyle = ($customers_group > 1) ? '#6EAC6D' : '#6295FD';
}
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td style="padding:2px 5px 5px 4px;"><table width="100%" align="center" style="background-color:#FFFFFF; border: dashed 1px #999999;">
          <tr><td>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="background-color:#FFF; border-collapse:collapse;">
          <tr>
            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF;">
<?php
		$customers_group_query = tep_db_query("SELECT customers_group_id FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '" . $order->customer['id'] . "'");
		$customers_group = (tep_db_num_rows($customers_group_query) > 0 ? tep_db_result($customers_group_query,0) : 0);
		tep_db_free_result($customers_group_query);

	echo '<tr>
				<td style="border-top solid 1px #8CA9C4; height:21px; background-color:'.$sourceStyle.'; font:bold 13px arial;color:#FFFFFF;">&nbsp; Billing Information:</td></tr>';
?>                      
                      <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px Tahoma; color:#0B2D86">&nbsp; Attn:&nbsp; <a href="orders.php?cID=<?php echo $order->customer['id']; ?>"><?php echo $order->billing['name']; ?></a></td>
                          </tr>
<?php if($customers_group > 1) echo '<tr><td style="padding:5px; background-color:#FFFFC6; font:bold 11px Tahoma; color:#CC6600">&nbsp;Vendor: <a href="orders.php?cID='. $order->customer['id'].'&amp;action=edit">'.$order->customer['company'].'</a></td></tr>';
?></table></td>
                    </tr>
		    <tr>
                      <td colspan="2" style="padding-top:1px; background-color:#F0F5FB;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
		      <?php echo (!empty($order->billing['company'])) ? '<tr><td class="tableinfo_orders">'.$order->billing['company'].'</td></tr>' : ''?> 
			  <tr><td class="tableinfo_orders"><?php echo $order->billing['street_address']?><?php echo $order->billing['suburb'] ? ', '.$order->billing['suburb'] : ''?></td></tr>
			  <tr><td><?php echo $order->billing['city']; ?>, <?php echo $order->billing['state']?></td></tr>
			  <tr><td><?php echo $order->billing['postcode']; ?> - <?php echo $order->billing['country']; ?></td></tr>
			  <tr><td>Phone: <?php echo $order->customer['telephone']; ?></td></tr>
<?php 

if(!empty($order->customer['fax'])) { 
	echo '<tr><td>Fax: '.$order->customer['fax'] .'</td></tr>';
}

?>
			  <tr><td>Email:&nbsp;
<?php
// # trunacte email address if longer then 29 characters to fit in our HTML table
$customers_email = (strlen($order->customer['email_address']) > 29) ? substr($order->customer['email_address'],0, 29) . "..." : $order->customer['email_address'];
				echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $customers_email . '</u></a>'; 

?></td></tr>

                        </table></td>
                    </tr>
            </table></td>
<td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:<?php echo $sourceStyle ?>;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Shipping Information:</span></td>
                      
                     </tr>
                    <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF; font:bold 11px arial; color:#0B2D86;">
					&nbsp; Attn:&nbsp; <?php echo $order->delivery['name']; ?>	</td>
                    </tr>
                    <tr>
                      <td style="padding-top:1px; background-color:#F0F5FB;"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                        <tr>
                          <td><?php echo $order->delivery['street_address'] . ', ' . $order->delivery['suburb']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $order->delivery['city'] . ', ' . $order->delivery['state']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $order->delivery['postcode']; ?> - <?php echo $order->delivery['country']; ?></td>
                          </tr>
                        <tr>
                          <td>Phone: <?php echo $order->customer['telephone']; ?></td>
                          </tr> 
<?php 

		if (stripos($order->info['orders_source'],'Amazon-FBA') === false) {

		echo '<tr>
				<td class="tableinfo_orders" style="height:18px; font:bold 11px arial; color:#000000;">Prefered Method:&nbsp;';

		if($order->info['shipping_method']) { 
			echo preg_replace('/.*?_/','',$order->info['shipping_method']);
		} else {
			echo '<font style="color:#FF0000;"><b>None</b></font>'; 
		} 

		echo '</td>
			</tr>';
	}

	echo '<tr>
			<td> Ship Method:&nbsp;';

		if (stripos($order->info['orders_source'],'Amazon-FBA') === false) {

		//$UPSTracking = implode("\n", unserialize($order->info['ups_track_num']) ?: array());
		//$UPSTracking = serialize(explode("\n", $UPSTracking));
		//$UPSTracking = unserialize($UPSTracking);

		$trackingResult_query = tep_db_query("SELECT * FROM orders_shipped WHERE orders_id = '".$order->orderid."'");

		$countResults =  tep_db_num_rows($trackingResult_query);

		if ($countResults > 0) {

			while ($track = tep_db_fetch_array($trackingResult_query)) {
				echo ' </td></tr><tr><td>'.$track['ship_carrier'].' #: &nbsp; <a style="font:bold 11px arial"';
				if ($track['ship_carrier'] == 'UPS') { 
					echo 'href="'. sprintf(UPS_TRACKING_URL, $track["tracking_number"]).'"';
				} elseif ($track['ship_carrier'] == 'FedEx') {
					echo 'href="'. sprintf(FEDEX_TRACKING_URL,$track["tracking_number"]).'"';
				} elseif ($track['ship_carrier'] == 'USPS') { 
					echo 'href="'. sprintf(USPS_TRACKING_URL, $track["tracking_number"]).'"';
				} elseif ($track['ship_carrier'] == 'DHL') {
					echo 'href="'. sprintf(DHL_TRACKING_URL,$track["tracking_number"]).'"';
				}
			
				echo 'target="_blank"> ' . $track["tracking_number"] . ' </a> - '. date('m/d/Y', strtotime($track["ship_date"])).'</td></tr>';
			} // # END WHILE

			tep_db_free_result($trackingResult_query);

		} else { 		// # else if no results from $countResults
			echo '<a href="orders_view.php?oID='.$_GET['oID'].'&action=edit#tracking_number" style="color:#0000FF; font:normal 10px verdana;">
				<u>enter tracking #</u></a></td></tr>';
		}

	} else {
		preg_match_all('/((?:^|[A-Z])[a-z]+)/', $order->info['shipping_method'], $matches);
		echo $matches[0][0] . ' ' . $matches[0][1];
	}

?>			

</table></td>
                    </tr>
            </table>
			</td>
                     <td width="33%" valign="top" style="border-top:1px solid #8CA9C4;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:<?php echo $sourceStyle ?>;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Payment Information:</span></td>
                      
                     </tr>
                     <tr>
                       <td colspan="2" style="height:2px; background-color:#FFFFFF;"></td>
                     </tr>
                    <tr>
                       <td colspan="2" style="background-color:#DEEAF8; height:20px;"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px tahoma; color:#000000;">&nbsp; Order ID:&nbsp; <?php echo $_GET['oID'] ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td valign="top" style="padding-top:1px; background-color:#F0F5FB;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                        <tr>
                          <td><b>Method:&nbsp;</b>
<?php
							if(empty($payments)) { 
								echo '-';	
							} else { 
								echo $payments[0]->getName();	
							}
 
							$payMeth = (!empty($payments)) ? $payments[0]->getName() : '-' ;
							//echo $payMeth;
?>
							</td>
                          </tr>
<?php if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {
?>                     <tr>
                          <td>Card Type:&nbsp; <?php echo $order->info['cc_type']; ?></td>
                          </tr>
                        <tr>
                          <td>Card Name:&nbsp; <?php echo $order->info['cc_owner']; ?></td>
                          </tr>
                        <tr>
                          <td>Card #:&nbsp; <?php echo $order->info['cc_number']; ?></td>
                          </tr>
<?php
    }
?>                      <tr>
                          <td>
						  Date Purchased:&nbsp; <?php echo tep_date_short($order->info['date_purchased']); ?>
						  </td>
                        </tr>
                        <tr>
                          <td>Time Purchased:&nbsp; <font style="text-transform:uppercase;"><?php echo $order->info['local_time_purchased'] ? tep_time_format($order->info['local_time_purchased'],$order->info['local_timezone']) : tep_time_format($order->info['date_purchased'],'GMT'); ?></font>
						   </td>
                        </tr>

<?php 

	if(!preg_match("/amazon/i", $order->info['orders_source'])) {

		$cust_ip_query = tep_db_query("SELECT ip_address FROM supertracker WHERE order_id = '".$oID."' ORDER BY order_id DESC LIMIT 1");

		if(tep_db_num_rows($cust_ip_query) > 0) { 

			$cust_ip = tep_db_result($cust_ip_query,0);

			echo '<tr><td>Customer IP: &nbsp; <a href="http://www.ip-tracker.org/locator/ip-lookup.php?ip='.$cust_ip.'" target="_blank">'. $cust_ip .'</a></td></tr>';
		}

		tep_db_free_result($cust_ip_query);
	}

	if($payMeth == 'Amazon Seller API') {

		$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$_GET['oID']."' AND method = 'payment_amazonSeller' LIMIT 1") or die(mysql_error());

		$theAmazonoID = tep_db_fetch_array($amazonOrder_query);
 

		if($order->info['orders_source'] == 'dbfeed_amazon_us') { 

			echo '<tr>
					<td>Amazon Order ID: &nbsp; <a href="https://sellercentral.amazon.com/gp/orders-v2/details/ref=ag_orddet_cont_myo?ie=UTF8&orderID='.$theAmazonoID['ref_id'].'" target="_blank" style="color:#FF0000">'.$theAmazonoID['ref_id'].'</a></td>
				</tr>';

		} elseif($order->info['orders_source'] == 'dbfeed_amazon_ca') { 

			echo '<tr>
					<td>Amazon Order ID: &nbsp; <a href="https://sellercentral.amazon.ca/gp/orders-v2/details/ref=ag_orddet_cont_myo?ie=UTF8&orderID='.$theAmazonoID['ref_id'].'" target="_blank" style="color:#FF0000">'.$theAmazonoID['ref_id'].'</a></td>
				</tr>';

		}

		if (stripos($order->info['orders_source'],'Amazon-FBA') !== false) {

			echo '<tr>
					<td align="center" style="background:#FFF url(images/amazonFBA-logo.jpg) no-repeat center 5px; background-size: contain; height:35px; width:auto;">&nbsp;</td>
				</tr>';

		} else { 

			echo '<tr>
					<td align="center" style="background:#FFF url(images/amazonSeller-logo.jpg) no-repeat center 5px; background-size: contain; height:37px">&nbsp;</td>
				</tr>';

		}

		tep_db_free_result($amazonOrder_query);
	} 
?>
		</table>
					  
					  </td>
                    </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F0F5FB; margin:0; padding:0">
		<tr>
            <td style="height:20px; background-color:<?php echo $sourceStyle ?>; font:bold 13px arial;color:#FFFFFF; margin:0; padding:0" colspan="8">&nbsp;Order ID:&nbsp; <?php echo $_GET['oID'] ?></td>
        	</tr>
		<tr bgcolor="#DEEAF8">
            <td style="padding: 0 0 0 10px; height:20px; font:bold 11px arial; color:#0B2D86"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
            <td align="center" style="width:55px; font:bold 11px arial; color:#000000;">Qnty.</td>
            <td align="center" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Model</td>
            <td align="right" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Price</td>
            <td align="right" style="padding: 0 7px 0 0; width:75px; font:bold 11px arial; color:#000000;">Tax %</td>
            <td align="right" style="padding: 0 7px 0 0; width:100px; font:bold 11px arial; color:#000000;">Sub-total</td>
            <td align="right" style="padding: 0 7px 0 0; width:100px; font:bold 11px arial; color:#0B2D86">Total</td>
		</tr>
<?php
    foreach ($order->products AS $prod)  {
			$xcls='';
			$xclsp='';
			$rqty=0;

			foreach ($order->returns AS $rt) { 
				if($rt['id'] == $prod['id']) {
					 $rqty+=$rt['qty'];
				}
			}
		if($rqty) $xclsp.= ' returned';
		if($rqty >= $prod['qty']) $xcls .= ' returned';

		echo '<tr class=" '.($ct++&1 ? 'tabEven' : 'tabOdd').'">';
?>
			<td class="tableinfo_right-btm<?php echo $xcls?>" style="text-align:left; font:bold 12px arial; padding: 8px 10px"><?php echo $prod['name']?></td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xclsp?>" style="text-align:center; font:bold 12px arial;">
			<?php echo (isset($prod['qty'])) ? $prod['qty'] : '&nbsp;';?>
		  </td>
		  <td align="center" class="tableinfo_right-btm<?php echo $xcls?>" style="font:bold 12px arial; padding: 10px">
			<?php echo ($prod['model']) ? $prod['model'] : '&nbsp;';?></td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xcls?>" style="font:bold 12px arial;"><?php echo $currencies->format($prod['final_price'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xcls?>" style="font:bold 12px arial;"><?php echo tep_display_tax_value($prod['tax'])?>%</td>
		  <td align="center" class="tableinfo_right-btm align_right<?php echo $xcls?>" style="font:bold 12px arial;"><?php echo $currencies->format($prod['final_price']*$prod['qty'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-end align_right<?php echo $xclsp?>" style="font:bold 12px arial;"><?php echo $currencies->format(tep_add_tax($prod['final_price']*$prod['qty'],$prod['tax']), true, $order->info['currency'], $order->info['currency_value'])?></td>
		</tr>
<?php if (isset($prod['attributes']) && (sizeof($prod['attributes']) > 0)) {
        for ($j = 0, $k = sizeof($prod['attributes']); $j < $k; $j++) {
?>
		<tr class="<?php echo $j&1?'rowOdd':'rowEven'?>">
		  <td bgcolor="#EBF1F5" class="tableinfo_right-btm<?php echo $xcls?>" style="font:11px arial; padding-left:13px;">
          &#8226; &nbsp;<?php echo $prod['attributes'][$j]['option']?>: <?php echo $prod['attributes'][$j]['value']?>
		  </td>
		   <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
		    <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
			 <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">
<?php
          if ($prod['attributes'][$j]['price'] != '0') echo '<font style="color:#FF0000;">' . $prod['attributes'][$j]['prefix'] . $currencies->format($prod['attributes'][$j]['price'] * $prod['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</font>';
          echo '&nbsp;</td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp; </td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp;</td><td class="tableinfo_right-end" bgcolor="#EBF1F5">&nbsp;</td></tr>';
        } 
      }
    foreach ($order->getPurchaseInfo() AS $pinfo) {
?>    
		<tr><td style="padding:4px;background:#CFDFFF;color:#000000">&nbsp;</td><td colspan="6" style="padding:4px;background:#CFDFFF;color:#000000"><?php echo $pinfo ?></td><tr>
<?php
    }
      foreach ($order->returns AS $rt) if($rt['id'] == $prod['id']) {

	$isExchange = ($prod['exchange'] == '1') ? 'Exchange' : 'Return';

?>
		<tr>
		  <td class="tableinfo_right-btm" style="font:bold 11px arial; color:#BF0000; padding-left:13px;">
- <a style="color:#BF0000" href="returns.php?cID=<?php echo $rt['rma']?>&page=1&oID=<?php echo $rt['returns_id'] . '">' . $isExchange. ' RMA '. $rt['rma'] ;?></a></td>
		  <td align="center" class="tableinfo_right-btm" style="text-align:center; font:bold 11px arial; color:#000000;"><?php echo ($prod[exchange] == '1') ? '' : $rt['qty']?></td>
		  <td align="center" colspan="5" class="tableinfo_right-end align_right<?php echo $xclsp?>" style="font:bold 11px arial; color:#000000;">&nbsp;</td>
		</tr>
<?php
	foreach ($order->products AS $xprod) {
		if (($xprod['exchange_returns_id'] == $rt['returns_id']) && ($xprod[exchange] == '1')) {
?>
		<tr bgcolor="#FFFFFC">
		  <td class="tableinfo_right-btm" style="font:bold 11px arial; color:#0000BF; padding-left:13px;">
<?php echo $xprod['name']?></td>
		  <td align="center" class="tableinfo_right-btm" style="text-align:center; font:bold 11px arial; color:#0000BF;">
		  <?php echo ($rt['qty']) ? $rt['qty'] : '&nbsp;';?>
		  </td>
		  <td align="center" class="tableinfo_right-btm" style="font:bold 12px arial; color:#0000BF;">
		   <?php echo ($xprod['model']) ? $xprod['model'] : '&nbsp;';?>
		   </td>
		  <td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo $currencies->format($xprod['price'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo tep_display_tax_value($xprod['tax'])?>%</td>
		  <td align="center" class="tableinfo_right-btm align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo $currencies->format($xprod['final_price']*$xprod['qty'], true, $order->info['currency'], $order->info['currency_value'])?></td>
		  <td align="right" class="tableinfo_right-end align_right" style="font:bold 12px arial; color:#0000BF;"><?php echo $currencies->format(tep_add_tax($xprod['final_price']*$xprod['qty'],$xprod['tax']), true, $order->info['currency'], $order->info['currency_value'])?></td>
		</tr>
<?php
 	if (isset($xprod['attributes']) && (sizeof($xprod['attributes']) > 0)) {
          for ($j = 0, $k = sizeof($xprod['attributes']); $j < $k; $j++) {
?>
		<tr class="<?php echo $j&1?'rowOdd':'rowEven'?>">
		  <td bgcolor="#EBF1F5" class="tableinfo_right-btm" style="font:11px arial; color:#0000BF; padding-left:15px;">
          &#8226; &nbsp;<?php echo $xprod['attributes'][$j]['option']?>:<?php echo $xprod['attributes'][$j]['value']?>
		  </td>
		   <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
		    <td bgcolor="#EBF1F5" class="tableinfo_right-btm">&nbsp;</td>
			 <td align="center" bgcolor="#EBF1F5" class="tableinfo_right-btm">
<?php
          if ($xprod['attributes'][$j]['price'] != '0') echo '<font style="color:#FF0000;">' . $xprod['attributes'][$j]['prefix'] . $currencies->format($xprod['attributes'][$j]['price'] * $xprod['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</font>';
          echo '&nbsp;</td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp; </td><td class="tableinfo_right-btm" bgcolor="#EBF1F5">&nbsp;</td><td class="tableinfo_right-end" bgcolor="#EBF1F5">&nbsp;</td></tr>';
        } // # END for $xprod['attributes']
      } // # END isset($xprod['attributes'])
}
      } // # END foreach ($order->products AS $xprod)

    } // # END foreach ($order->returns AS $rt)
  } 

?>	  
		<tr>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-btm">&nbsp;</td>
<td class="tableinfo_right-end">&nbsp;</td>
		</tr>
		  <td colspan="4" valign="top" bgcolor="#FFFFC4" class="tableinfo_right-btm" ><table width="100%" border="0" cellpadding="0" cellspacing="0">
		    <tr><td height="91" valign="top" style="padding-top:5px; font:bold 11px arial; color:#000000">&nbsp; Customer Comments: <br>
		      <br><div style="padding:5px; font-weight:normal;"><?php echo $order->info['comments']?></div></td>
		  </tr>
		  <tr>
		  <td align="right" style="padding:5px;"> 
<?php
	if (stripos($order->info['orders_source'],'Amazon-FBA') === false) {
		echo '<a href="orders_view.php?oID='. $_GET['oID'] . '&action=edit#customer_comments" style="color:#0000FF; font:normal 11px arial;"><u>view or add other comments</u></a>';
	}
?>
</td>
		  </tr>
		  </table>
		  </td>
		  <td colspan="3" align="center" bgcolor="#EBF1F5" style="font:bold 11px arial; color:#000000;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
           
              <?php
  

    for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
      echo '              <tr>' . "\n" .
           '                <td height="23" class="tableinfo_right-btm" align="right" style="padding:0 5px 0 5px; font:bold 12px arial; color:#FF0000;">' . $order->totals[$i]['title'] . '</td>' . "\n" .
           '                <td align="right" class="tableinfo_right-end align_right" style="width:102px; padding: 0 5px 0 0; font:bold 12px arial; color:#000000;">' . $order->totals[$i]['text'] . '</td>' . "\n" .
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
		<td colspan="2" style="padding:5px;">
<?php 

// # if equals to status shipped - pay attention these match your shipping config values!

if($order->info['orders_status'] == 3) {
	$editbutton = '<a href="return_product.php?order_id='.$_GET['oID'].'">' . tep_image_button('button_return.gif', 'Create Return') . '</a>';

		if($order->info['payment_method'] == 'payment_manual') {

			$editbutton .= ' <a href="' . tep_href_link(FILENAME_EDIT_ORDERS, 'oID=' . $_GET['oID']) . '" class="hide_print">' . tep_image_button('button_modifier.gif', IMAGE_EDIT) . '</a>'; 
		}

// # if status cancelled dont show edit button
} elseif ($order->info['orders_status'] == 0 || $payMeth =='Amazon Seller API') { 

	$editbutton = '';

// # if status is pending or pre-settlement
} else { 

	$editbutton = '<a href="' . tep_href_link(FILENAME_EDIT_ORDERS, 'oID=' . $_GET['oID']) . '">' . tep_image_button('button_modifier.gif', IMAGE_EDIT) . '</a>'; 
}
	if (stripos($order->info['orders_source'],'Amazon-FBA') === false) {

		echo '<a href="'.tep_href_link(FILENAME_ORDERS_INVOICE, 'oID='. $_GET['oID']).'" target="_blank">'.tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE).'</a> <a href="'.tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID='.$_GET['oID']).'" target="_blank">'.tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP).'</a> <a href="orders_view.php?oID='.$_GET['oID'] . '&action=edit">'.tep_image_button('button_update.gif', IMAGE_UPDATE).'</a> '.$editbutton;
	}

echo '<br></td></tr>';

} else {
require(DIR_WS_INCLUDES . 'header.php');

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
         
<?php 

// # SORT ORDER PAGE
          
	switch ($listing) {
		case "id-asc":
			$o_sort = "o.orders_id ASC ";
		break;

		default:
		case "id-desc":
			$o_sort = "o.orders_id DESC ";
		break;

		case "customers":
			$o_sort = " customers_name ASC ";
		break;

		case "customers-desc":
			$o_sort = " customers_name DESC";
		break;

		case "ottotal":
			$o_sort = " ot.value ASC ";
		break;

		case "ottotal-desc":
			$o_sort = "ot.value DESC ";
		break;

		case "status-asc":
			$o_sort = "s.orders_status_name ASC ";
		break;

		case "status-desc":
			$o_sort = "s.orders_status_name DESC ";
		break;

		case "date-asc":
			$o_sort = "o.date_purchased ASC ";
		break;

		case "date-desc":
			$o_sort = "o.date_purchased DESC ";
		break;

		case "channel-asc":
			$o_sort = "orders_source ASC";
		break;

		case "channel-desc":
			$o_sort = "orders_source DESC ";
		break;

          }

			$date_from = (isset($_GET['date_from'])) ? $_GET['date_from'] : '';
			$date_to = (isset($_GET['date_to']) && $_GET['date_to'] > 0) ? $_GET['date_to'] : '';
		       ?>

<td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td><table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="171" valign="top" style="padding:0 0 0 10px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="45"><img src="images/orders-icon.gif" width="38" height="36" border="0"></td>
    <td style="font:bold 17px arial;">Order Manager</td>
  </tr>
</table>
</td>
  </tr>
</table>
</td>
            <td width="325" align="right" style="padding:4px 5px 6px 0;">
<?php echo tep_draw_form('date_range', FILENAME_ORDERS, (!empty($o_sort) ? 'listing='.$_GET['listing'] : '') . (!empty($o_sort) ? '&status='.$_GET['status'] : '') , 'GET'); ?>
			<table width="325" border="0" cellspacing="0" cellpadding="0" style="background-color:#FFFFFF; border:solid 1px #D9E4EC;">
<tr>
<td style="padding-top:5px; padding-bottom:5px;">
  <table width="325" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>
                      <td width="25"><img src="images/mag-icon.gif" width="15" 
height="15" hspace="5"></td>
                      <td width="80" align="center" nowrap 
style="color:#6295FD"><b>Order Search</b>&nbsp; </td>
                      <td width="212" align="right">
					  <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td align="right" style="padding-top:2px;">
<script type="text/javascript" src="js/popcalendar.js"></script>
						  <input type="text" name="date_from" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo $date_from;?>" size="12" maxlength="11" textfield></td>
						  <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td align="center" style="padding:1px 3px 0 3px;"> - </td>
                          <td align="right" style="padding-top:2px;"><input type="text" name="date_to" onClick="self.popUpCalendar(document.date_range.date_from,this,'mm/dd/yyyy',document);" style="font:bold 9px arial;" value="<?php echo $date_to;?>" size="12" maxlength="11" textfield></td>
                          <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.date_range.date_from,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
                          <td valign="top" style="padding:2px 7px 0 2px;"><input type="submit" value="GO" style="margin:0; padding:none; cursor:pointer; border:none; font:bold 11px arial; background-color:#6295FD; color:#FFFFFF;"></td>
                        </tr>
</table></td>
                    </tr>
                        
                      </table><br>
					  <table width="100%" border="0" cellspacing="0" cellpadding="0">
 <tr>
                          <td nowrap style="padding: 0 2px 0 6px;" valign="top"> 
                            Name or Order ID: </td>
                          <td width="80" style="padding-right:5px;"><?php echo tep_draw_input_field('cFind', '', 'size="20" style="font:bold 9px arial; width:100px;"') . tep_draw_hidden_field('action', 'cust_search'); ?></td>
<td align="center" valign="top" style="padding:0 0 0 7px;">Display:</td>
		  <td width="92" align="center" style="padding: 0 5px 0 4px;"><?php echo tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_ALL_ORDERS)), $orders_statuses), '', 'onchange="this.form.submit();" style="font:bold 10px arial; width:90px;"'); ?></td>
                        </tr>
</table>
</td>
		  
		</tr>
</table></form></td>
      </tr>
      <tr>
        <td colspan="3">

		<table border="0" width="100%" cellspacing="0" cellpadding="0" height="670">
          <tr>
			<td valign="top" style="padding:0 5px 0 0">

				<table border="0" width="100%" cellspacing="0" cellpadding="0">
        	      <tr class="dataTableHeadingRow">
            	    <td width="35" height="22" align="center" class="dataTableHeadingContent">&nbsp;</td>
                	<td width="65" align="center" class="dataTableHeadingContent"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'id-asc' ? 'id-desc':'id-asc').(!empty($_GET['status']) ? '&status='.$_GET['status'] : '');?>">Order #</a></td>

	                <td width="150" align="left" class="dataTableHeadingContent" style="padding:0 0 0 13px">
<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'customers' ? 'customers-desc':'customers').(isset($_GET['status']) ? '&status='.$_GET['status'] : '');?>">Customer</a></td>
	
    	            <td width="75" align="right" class="dataTableHeadingContent" style="padding-right:3px;">
<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'ottotal' ? 'ottotal-desc':'ottotal').(isset($_GET['status']) ? '&status='.$_GET['status'] : '');?>">Order Total</a></td>

            	    <td width="105" align="center" class="dataTableHeadingContent">
<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'date-desc' ? 'date-asc':'date-desc').(isset($_GET['status']) ? '&status='.$_GET['status'] : '');?>">Purchased</a></td>

            	    <td width="105" align="center" class="dataTableHeadingContent">
<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'channel-asc' ? 'channel-desc':'channel-asc').(isset($_GET['status']) ? '&status='.$_GET['status'] : '').(!empty($_GET['action']) ? '&action='.$_GET['action'] : '');?>">Channel</a></td>
                
					<td width="120" align="center" class="dataTableHeadingContent">
<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'status-asc' ? 'status-desc':'status-asc').(isset($_GET['status']) ? '&status='.$_GET['status'] : '');?>">Status</a></td>

	       	        <td width="90" align="center" class="dataTableHeadingContent">Controls</td>
    	          </tr>
				</table>

	<div class="Accordion" id="ordersAccordion" tabindex="0">

<?php
    //--------------------------------------    
// #### HURL CUSTOMER ORDER SEARCH ####
// ### PRECONDITION: need order details based upon a customers first name and or last name
// ### POSTCONDITION: check for the get var custName -- new to this contrib
// if exists create an sql query based upon the customer name
// passed from said get var
//
// cFind instead of custName & order id
//-------------------------------------

	$o_tables='';
	$o_where='';

	$sort_modes = array('id'=>'o.orders_id','customers'=>'o.customers_name','ottotal'=>'ot.value','status'=>'o.orders_status');

	if(!empty($_GET['cFind'])){
	  $cFind = trim(tep_db_input($_GET['cFind']));
		if(preg_match('/[-]/',$cFind) && !preg_match('/[a-zA-Z]/',$cFind)){
		  $o_from = 'oir.ref_value, ';
		  $o_join = ' LEFT JOIN orders_items_refs oir ON o.orders_id = oir.orders_id';
		  $o_where.=" AND oir.ref_value = '$cFind'";
		} else {
		  $o_from='';
		  $o_join='';
		  $o_where.=" AND (o.customers_name LIKE '%".$cFind."%' OR o.orders_id='$cFind' OR o.customers_company LIKE '%".$cFind."%')";
		}
	}
	if (isset($_GET['cID'])) {
	      $cID = tep_db_prepare_input($_GET['cID']);
	      $o_where.=" AND o.customers_id = '" . (int)$cID . "'";
	} 
	if (isset($_GET['pID'])) {
	      $pID = tep_db_prepare_input($_GET['pID']);
	      $o_tables.=" LEFT JOIN ".TABLE_ORDERS_PRODUCTS." op ON op.orders_id=o.orders_id LEFT JOIN ".TABLE_PRODUCTS." p ON op.products_id=p.products_id";
	      $o_where.=" AND (p.master_products_id = '" . (int)$pID . "' OR p.products_id = '" . (int)$pID . "')";
	} 

	if (isset($_GET['status']) && preg_match('/^[\d\,]+$/',$_GET['status'])) {
	    $status = $_GET['status'];
	    $o_where .=" AND o.orders_status IN ($status)";
	} 


	if(!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
		$o_where .= " AND o.date_purchased BETWEEN '". date("Y-m-d 00:00:01", strtotime($_GET["date_from"])) ."' AND '". date("Y-m-d 23:59:59", strtotime($_GET["date_to"])) ."'";

	} elseif(!empty($_GET['date_from']) && empty($_GET['date_to'])) { 
		$o_where .= " AND o.date_purchased >= '". date("Y-m-d 00:00:01", strtotime($_GET["date_from"])) ."' ";

	} elseif(empty($_GET['date_from']) && !empty($_GET['date_to'])) { 
		$o_where .= " AND o.date_purchased <= '". date("Y-m-d 23:59:59", strtotime($_GET["date_to"])) ."' ";
	}
	
	if (isset($_GET['listing']) && preg_match('/^(\w+)(-(asc|desc))?$/i',$_GET['listing'],$sort_p) && isset($sort_modes[$sort_p[1]])) { 
		$o_sort .=', '. $sort_modes[$sort_p[1]].' '.$sort_p[3];
	}
	
	$orders_query_raw = "SELECT o.orders_id, 
							o.customers_id, 
							o.customers_company,
							IF((o.customers_company IS NULL OR o.customers_company = ''), o.customers_name, o.customers_company) as customers_name,
							o.customers_id, 
							o.payment_method, 
							o.date_purchased, 
							o.last_modified, 
							o.currency, 
							o.currency_value,
							IF(o.orders_source LIKE 'Amazon-FBA%','Amazon FBA', 
								IF(o.orders_source LIKE 'dbfeed_amazon%','Amazon MFN',
									IF(o.orders_source LIKE 'email','Email',
										IF(o.orders_source LIKE 'vendor','Vendor',
							'Direct')))) AS orders_source,

							o.orders_status,
							s.orders_status_name,
						   $o_from ot.text AS order_total	
						FROM " . TABLE_ORDERS . " o 
						LEFT JOIN " . TABLE_ORDERS_TOTAL . " ot ON ot.orders_id = o.orders_id
						LEFT JOIN " . TABLE_ORDERS_STATUS . " s ON s.orders_status_id = o.orders_status
					    ".$o_join." 
						".$o_tables." 
						WHERE ot.class = 'ot_total'
						AND s.language_id = '" . (int)$languages_id . "'
						 ".$o_where."						
						GROUP BY o.orders_id 
						ORDER BY ".$o_sort;

// #### CUSTOMER ORDER SEARCH ####

    $orders_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
    $orders_query = tep_db_query($orders_query_raw);
    $ct=0;

    while ($orders = tep_db_fetch_array($orders_query)) {
	    if ((!isset($_GET['oID']) || (isset($_GET['oID']) && ($_GET['oID'] == $orders['orders_id']))) && !isset($oInfo)) {
    	    $oInfo = new objectInfo($orders);
	      }
?>
	<div class="AccordionPanel">
	  <div class="AccordionPanelTab <?php echo ($ct++&1) ? 'tabEven' : 'tabOdd' ?>" style="width:100%; height:23px; border:solid 1px #FFFFFF; border-bottom:0; border-right:0;" onMouseover="preloadOrder('<?php echo $orders['orders_id']?>')">
	    <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="35" align="center" class="tableinfo_right-btm"><h4 style="height:11px; width:11px;"></h4></td>
            <td width="65" align="center" class="tableinfo_right-btm"><b><?php echo $orders['orders_id']; ?></b></td>
            <td width="150" align="left" class="tableinfo_right-btm" style="text-transform:capitalize; font-weight:bold; padding:0 0 0 13px;">
<?php echo mb_strimwidth($orders['customers_name'], 0, 24, ' ...'); ?></td>
            <td width="75" align="center" class="tableinfo_right-btm align_right" style="padding:0 10px 0 0;"><b><?php echo strip_tags($orders['order_total']); ?></b></td>
     
            <td width="105" height="23" align="center" class="tableinfo_right-btm" style="font-weight:bold">
<?php 

	// # check for late orders
	if(strtotime("now") > strtotime($orders['date_purchased'] . " + 3 days") && ($orders['orders_status'] !== '3' && $orders['orders_status'] !== '0')) { 	

 		echo '<div style="position:absolute; padding:0 0 0 10px;"><img alt="" src="data:image/gif;base64,R0lGODlhEAAQANUAAAAAAPrJD8qVA8eSAfnqdfrIVPTROea0Cv7zhPnhXue7H/rRI//8nvzcQtajCfjhX/fHGvHQQPrvhey8FPrNGP74kt+tDv/WIc6aBfvWMf7tdP/OGf3nY/3hUv/OIf//ptunC/nKD+e2D/DAFtacCP/MZgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHACUALAAAAAAQABAAAAaCwJJwWIBACsOkEiKRQJTQ4udzhCaZU6dVWBgxAADGCGmdECrgCmFiLYgQCDBcRE6KHhoNOJ8QKQsWHIJgghwWdSUgBh2MYIwdESBEDg2VDWCWDQ5kGAoZn6ChCiQlBRgLqKhgqagYBQITFLKyYLOyIgICIgG8vb68BwMFA8TFxsYFQQA7" width="14" height="13"></div><span style="color:red">'. tep_date_short($orders['date_purchased']) . '</span>'; 
	} else {
			echo date('m/d/Y', strtotime($orders['date_purchased'])); 
		}
?>
			</td>
            <td width="105" height="23" align="center" class="tableinfo_right-btm"><b>
<?php 
				if($orders['orders_source'] == 'Amazon MFN') {
					echo 'Amazon <b style="color:#FF6600;">MFN</b>'; 
				} elseif($orders['orders_source'] == 'Amazon FBA') {
					echo 'Amazon <b style="color:#665;">FBA</b>';
				} else {
					echo $orders['orders_source'];
				}

?>
</b></td>

            <td width="120" height="23" align="center" class="tableinfo_right-btm"><b><?php echo $orders['orders_status_name']; ?></b></td>

			<td width="90" align="center" class="tableinfo_right-btm" style="font-weight:normal;">
			
			<a href="orders_view.php?oID=<?php echo $orders['orders_id'] . '&action=edit'?>" style="font: 10px verdana; color:0000FF;"><u>view</u></a>
<?php 

	if($orders['orders_status'] != '3' && $orders['orders_status'] != '0' && $orders['payment_method'] != 'payment_amazonSeller'){
		echo ' | <a href="' . tep_href_link(FILENAME_EDIT_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID='. $orders['orders_id']) . '" style="font: 10px verdana; color:0000FF;"><u>edit</u></a>';
	}
?>
			</td>

            </tr>
        </table>
	  </div>
	  <div class="AccordionPanelContent" onExpanderOpenPanel="viewOrder('<?php echo $orders['orders_id']?>')"><div id="view_order_<?php echo $orders['orders_id']?>"></div></div>
    </div>
<?php
    }

		tep_db_free_result($orders_query);
?>
  </div>
<script type="text/javascript">

function orderLoadComplete(req,id) {
//  window.alert(id);
  var box=$('view_order_'+id);
  if (box) box.innerHTML=req.responseText;
  ordersLoaded[id]=true;
  ordersAccordion.openPendingPanel();
}

var ordersLoaded={};

function viewOrder(id) {
  if (ordersLoaded[id]==undefined) {
    ordersLoaded[id]=false;
    new ajax('<?php echo tep_href_link(FILENAME_ORDERS,'action=edit&oID=\'+id+\'')?>',{ onComplete:orderLoadComplete, onCompleteArg:id });
  }

  return ordersLoaded[id];
}

function preloadOrder(id) {
  for (var i in ordersLoaded) if (!ordersLoaded[i]) return true;
  viewOrder(id);
  return true;
}
<?php if(isset($_GET['open']) && $_GET['open'] == '1') { ?>
	var ordersAccordion = new Spry.Widget.Accordion("ordersAccordion",{enableClose:true,defaultPanel: 0});
<?php } else { ?>
var ordersAccordion = new Spry.Widget.Accordion("ordersAccordion",{enableClose:true});
<?php } ?>

</script>
</div>

    <table width="100%">
              <tr>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td style="font:bold 11px arial;"><?php echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
                    <td align="right" style="font:bold 11px arial; padding-top:12px;" class="pagejump"><?php echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
    </table></td>

  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
