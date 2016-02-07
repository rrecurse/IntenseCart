<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  require('includes/application_top.php');

if(!isset($_SESSION['origin']) || $_SESSION['origin'] != FILENAME_SUPPLY_REQUEST) { 
	session_start();
	$_SESSION['origin'] = FILENAME_SUPPLY_REQUEST;
} 

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $supply_request_status_ides = array();
  $supply_request_status_id_array = array();
  $supply_request_status_id_query = tep_db_query("SELECT supply_request_status_id, supply_request_status_name 
												  FROM " . TABLE_SUPPLY_REQUEST_STATUS . " 
												  WHERE language_id = '" . (int)$languages_id . "'
												");

	while ($supply_request_status_id = tep_db_fetch_array($supply_request_status_id_query)) {
	    $supply_request_status_ides[] = array('id' => $supply_request_status_id['supply_request_status_id'],
    		     							  'text' => $supply_request_status_id['supply_request_status_name']
									  		  );

		$supply_request_status_id_array[$supply_request_status_id['supply_request_status_id']] = $supply_request_status_id['supply_request_status_name'];

	}

	$action = (isset($_GET['action']) ? $_GET['action'] : '');


	if(!isset($_GET['page'])) { 
		$_GET['page'] = 1;
	}

	if (tep_not_null($action)) {
		switch ($action) {

	      case 'update_request':
        
			$sID = tep_db_prepare_input($_GET['sID']);
    	    $status = tep_db_prepare_input($_POST['status']);
        	$comments = tep_db_prepare_input($_POST['comments']);
	        $ups_track_num = tep_db_prepare_input($_POST['ups_track_num']);
        	$fedex_track_num = tep_db_prepare_input($_POST['fedex_track_num']);
    	    $usps_track_num = tep_db_prepare_input($_POST['usps_track_num']);
			$supplier_notified = 0;
        
			$order_updated = false;

			$check_status_query = tep_db_query("SELECT suppliers_name,
												suppliers_email_address, 
												supply_request_status_id, 
												fedex_track_num, 
												ups_track_num, 
												usps_track_num,
												date_requested	 
												FROM " . TABLE_SUPPLY_REQUEST . " 
												WHERE supply_request_id = '" . tep_db_input($sID) . "'
												");

    	    $check_status = tep_db_fetch_array($check_status_query);

			if($check_status['supply_request_status_id'] != $status || !empty($comments) || isset($ups_track_num) || isset($fedex_track_num) || isset($usps_track_num)) {
	
				tep_db_query("UPDATE " . TABLE_SUPPLY_REQUEST . " 
							  SET supply_request_status_id = '" . tep_db_input($status) . "', 
							  last_modified = NOW() 
							  WHERE supply_request_id = '" . (int)$sID . "'
							 ");
	
				$supplier_notified = '0';

					if($_POST['notify'] == 'on') {
						if(!empty($comments)) {
							$notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n";
							$supplier_notified = '1';
						} else {
							$notify_comments = '';
						}
					}
	
				if(empty($ups_track_num) && empty($fedex_track_num) && empty($usps_track_num)) {

    	        	$email = 'Dear ' . $check_status['suppliers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $sID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $sID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_requested']) . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $supply_request_status_id_array[$status]);

				} elseif(!empty($ups_track_num) || !empty($fedex_track_num) || !empty($usps_track_num)) {// # tracking feilds populated
	
					if(!empty($comments)) { 
						$comments .= '<br><br>';
					}
            			
					if(empty($ups_track_num)) {
						$ups_text = $ups_track_num = '';
            		} else {
						$ups_text = 'UPS: ';
						$ups_track_num = $ups_track_num . "\n";
						$comments .= $ups_text . ': ' . $ups_track_num;
					}
            			
					if(empty($fedex_track_num)) {
						$fedex_text = $fedex_track_num ='';
					} else {
						$fedex_text = 'Fedex: ';
						$fedex_track_num = $fedex_track_num . "\n";
						$comments .= $fedex_text . ': ' . $fedex_track_num;
					}
            
					if(empty($usps_track_num)) {
						$usps_text = $usps_track_num = '';
					} else {
						$usps_text = 'USPS: ';
						$usps_track_num = $usps_track_num . "\n";
						$comments .= $usps_text . ': ' . $usps_track_num;
					}
			
					$email = 'Dear ' . $check_status['suppliers_name'] . ',' . "\n\n" . STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $sID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $sID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_requested']) . "\n\n\n" . EMAIL_TEXT_TRACKING_NUMBER . "\n" . 'UPS: ' . $ups_track_num . "\n" . 'Fedex: ' . $fedex_track_num . "\n" . 'USPS: ' . $usps_track_num . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $supply_request_status_id_array[$status]);	
				}

					if(isset($ups_track_num)) {
			            tep_db_query("UPDATE " . TABLE_SUPPLY_REQUEST . " 
									  SET ups_track_num = '" . tep_db_input($ups_track_num) . "' 
									  WHERE supply_request_id = '" . tep_db_input($sID) . "'
									");
            			$order_updated = true;
			          }
    
					if(isset($fedex_track_num)) {
						tep_db_query("UPDATE " . TABLE_SUPPLY_REQUEST . "
									  SET fedex_track_num = '" . tep_db_input($fedex_track_num) . "' 
									  WHERE supply_request_id = '" . tep_db_input($sID) . "'
									");
						$order_updated = true;
					}

					if(isset($usps_track_num)) {
						tep_db_query("UPDATE " . TABLE_SUPPLY_REQUEST . "
									  SET usps_track_num = '" . tep_db_input($usps_track_num) . "' 
									  WHERE supply_request_id = '" . tep_db_input($sID) . "'
									");
			            $order_updated = true;
					}


				if($_POST['notify'] == 'on' && !empty($check_status['suppliers_email_address'])) {
					tep_mail($check_status['suppliers_name'], $check_status['suppliers_email_address'], EMAIL_TEXT_SUBJECT_1. $sID . EMAIL_TEXT_SUBJECT_2 . $supply_request_status_id_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);	
				}

			}

			tep_db_query("INSERT INTO " . TABLE_SUPPLY_REQUEST_STATUS_HISTORY . " 
						 SET supply_request_id = '". (int)$sID ."', 
						 supply_request_status_id = '". tep_db_input($status) ."',
						 date_added = NOW(),
						 supplier_notified = '". tep_db_input($supplier_notified) ."',
						 comments = '". tep_db_input($comments) ."'
						");

			if($order_updated == true) {
				$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
	        } else {
				$messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
    	    }

        	tep_redirect(tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('action')) . 'action=edit'));

	break;

	case 'deleteconfirm':
        $sID = (int)$_GET['sID'];

        tep_remove_supply_request($sID);	

        tep_redirect(tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')). 'status='));

	break;
    }
}

	if(($action == 'edit') && isset($_GET['sID'])) {
      
	$sID = (int)$_GET['sID'];

    $supply_request_query = tep_db_query("SELECT supply_request_id FROM " . TABLE_SUPPLY_REQUEST . " WHERE supply_request_id = '" . $sID . "'");

    $order_exists = true;

    if (!tep_db_num_rows($supply_request_query)) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $sID), 'error');
    }
  }

  include(DIR_WS_CLASSES . 'supply_request.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">


<style type="text/css">

.commentsTable {
border-collapse:collapse;
margin: 10px 0;
}

.commentsTable th {
border:1px solid #CCC;
background-color: rgb(98, 149, 253);
color:white;
font:bold 11px arial;
}

.commentsTable td {
border:1px solid #CCC;
padding:10px;
}

</style>
</head>

<body style="margin:0; background-color:#F0F5FB;">
<?php require(DIR_WS_INCLUDES . 'header.php') ?>

<table border="0" width="99%" cellspacing="0" cellpadding="0" align="center">
  <tr>
    
    <td width="100%" valign="top" colspan="2">

<table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if (($action == 'edit') && ($order_exists == true)) {
    $supply_request = new supply_request($sID);
?>
			<tr>
				<td>
			<table width="100%">
				<tr>
					<td width="53"><img src="/admin/images/icons/supply-icon.png" width="48" height="48"></td>
					<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
					<td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<!-- // # Start top info table -->

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="background-color:#FFF; border-collapse:collapse; margin: 0 0 5px 0">
          <tr>
            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF;">
   					<tr>
				<td style="border-top solid 1px #8CA9C4; height:21px; background-color:#6295FD; font:bold 13px arial;color:#FFFFFF;">&nbsp; Supplier Information:</td>
			</tr>
              <tr>
                <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px arial; color:#0B2D86">&nbsp; Attn:&nbsp; <a href="customers.php?cID=<?php echo $comp_check['customers_id']; ?>&amp;action=edit"><b><?php echo $supply_request->supplier['suppliers_name']; ?></b></a></td>
                          </tr>
<?php 
if($customers_group > 1) echo '<tr><td style="padding:5px; background-color:#FFFFC6; font:bold 11px Tahoma; color:#CC6600">&nbsp;Vendor: <a href="customers.php?cID='. $comp_check['customers_id'].'&amp;action=edit">'.$comp.'</a></td></tr>';
?>
                       </table></td>
                    </tr>
		    <tr>
                      <td style="padding:1px 0 0 0; background-color:#F0F5FB;">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
	      <?php echo (!empty($supply_request->supplier['company']))? '<tr><td>'.$supply_request->supplier['company'].'</td></tr>' : '';		?>
			  <tr><td><?php echo $supply_request->supplier['street_address']?><?php echo ($supply_request->supplier['suburb'] ? ', '.$supply_request->supplier['suburb'] : '');?></td></tr>
			  <tr><td><?php echo $supply_request->supplier['city']; ?>, <?php echo $supply_request->supplier['state']?></td></tr>
			  <tr><td><?php echo $supply_request->supplier['postcode']; ?> - <?php echo $supply_request->supplier['country']; ?></td></tr>
			  <tr><td>Phone: <?php echo $supply_request->supplier['telephone']; ?></td></tr>
			  <?php if(!empty($supply_request->supplier['fax'])) echo '<tr><td class="tableinfo_orders">Fax: '.$supply_request->supplier['fax'].'</td></tr>';?>
			  <tr><td>Email: <?php echo '<a href="mailto:' . $supply_request->supplier['email_address'] . '"><u>' . $supply_request->supplier['email_address'] . '</u></a>'; ?></td></tr>
</table>
				</td>
                </tr>
            	</table>
			</td>

            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4; border-right: 3px solid #FFF;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:#6295FD;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Delivery
                            Information:</span></td>
                      
                     </tr>
                    <tr>
                       <td  style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:bold 11px arial; color:#0B2D86">&nbsp; Attn:&nbsp; <?php echo $supply_request->delivery['name']; ?></td>
                          </tr>
                       </table></td>
                    </tr>
                    <tr>
                      <td style="padding-top:1px; background-color:#F0F5FB;">

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">
                        <tr>
                          <td><?php echo $supply_request->delivery['street_address']; ?>, <?php echo $supply_request->delivery['suburb']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $supply_request->delivery['city']; ?>, <?php echo $supply_request->delivery['state']; ?></td>
                          </tr>
                        <tr>
                          <td><?php echo $supply_request->delivery['postcode']; ?> - <?php echo $supply_request->delivery['country']; ?></td>
                          </tr>
                        <tr>
                          <td>Phone: <?php echo $supply_request->supplier['telephone']; ?></td>
                          </tr>
					
<?php

		if(!empty($supply_request->info['ups_track_num'])) { 

			$carrier = 'UPS';

			echo '<tr>
					<td> Ship Method:&nbsp;' . $carrier . ' </td>
				 </tr>
				<tr>
					<td>'.$carrier . '#: &nbsp; <a href="'. sprintf(UPS_TRACKING_URL, $supply_request->info['ups_track_num']).'" style="font:bold 11px arial" target="_blank">' . $supply_request->info['ups_track_num'].'</a> - '.date('m/d/Y', strtotime($supply_request->info['ship_date'])).'</td>
				</tr>';	

		} elseif(!empty($supply_request->info['fedex_track_num'])) {

			$carrier = 'FedEx';

			echo '<tr>
					<td> Ship Method:&nbsp;' . $carrier . ' </td>
				 </tr>
				<tr>
					<td>'.$carrier . '#: &nbsp; <a href="'. sprintf(FEDEX_TRACKING_URL, $supply_request->info['fedex_track_num']).'" style="font:bold 11px arial" target="_blank">' . $supply_request->info['fedex_track_num'].'</a> - '.date('m/d/Y', strtotime($supply_request->info['ship_date'])).'</td>
				</tr>';	
	
		} elseif(!empty($supply_request->info['usps_track_num'])) { 

			$carrier = 'USPS';

			echo '<tr>
					<td> Ship Method:&nbsp;' . $carrier . ' </td>
				 </tr>
				<tr>
					<td>'.$carrier . '#: &nbsp; <a href="'. sprintf(USPS_TRACKING_URL, $supply_request->info['usps_track_num']).'" style="font:bold 11px arial" target="_blank">' . $supply_request->info['usps_track_num'].'</a> - '.date('m/d/Y', strtotime($supply_request->info['ship_date'])).'</td>
				</tr>';		
		} else { 
			
			echo '<tr>
					<td>Not yet shipped</td>
				 </tr>
				<tr>
					<td>-</td>
				</tr>';		
		}
		


$payMeth = (!empty($supply_request->info['payment_method'])) ? $supply_request->info['payment_method'] : '-' ;
 ?>			

</table></td>
                    </tr>
            </table>
			</td>
            <td width="33%" valign="top" style="border-top:1px solid #8CA9C4;">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:1px solid #FFF">
                     <tr>
                       <td style="border-top:1px solid #8CA9C4; height:21px; background-color:#6295FD;"><span style="font:bold 13px arial;color:#FFFFFF;">&nbsp; Billing Information:</span></td>
                      
                     </tr>
                    <tr>
                       <td style="background-color:#DEEAF8; height:22px; border-top:solid 2px #FFF">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td style="font:normal 11px tahoma; color:#0B2D86"> &nbsp; <b>P/O  ID:&nbsp; <?php echo $sID?></b></td>
                          </tr>
                       </table></td>
                    </tr>

                    <tr>
                      <td valign="top" style="padding-top:1px; background-color:#F0F5FB;">
					  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="infoTable">	
				</td>
			</tr>
			<tr>
				<td>Created:&nbsp; <?php echo $supply_request->info['date_requested'] ? date('m/d/Y',strtotime($supply_request->info['date_requested'])) : ''; ?></td>
			</tr>
<?php
		if($payMeth == 'Credit Card') {
?>
			<tr>
				<td><b>Payment Method:&nbsp;</b> Credit Card</td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Card Type:&nbsp; <?php echo $supply_request->info['cc_type']; ?></td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Card Name:&nbsp; <?php echo $supply_request->info['cc_owner']; ?></td>
			</tr>
			<tr>
				<td>Card #:&nbsp; <?php echo $supply_request->info['cc_number']; ?></td>
			</tr>						

<?php 	} elseif ($payMeth == 'Check'){ ?>

			<tr>
				<td style="text-transform:capitalize;"><b>Payment Method:&nbsp;</b> Check</td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Account Type:&nbsp; <?php echo $supply_request->info['cc_type']; ?></td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Account Name:&nbsp; <?php echo $supply_request->info['cc_owner']; ?></td>
			</tr>
			<tr>
				<td>Account #:&nbsp; <?php echo $supply_request->info['cc_number']; ?></td>
			</tr>
<?php 	} else {  ?>
						<tr>
				<td style="text-transform:capitalize;"><b>Payment Method:&nbsp;</b> <?php echo $payMeth ?></td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Account Type:&nbsp; <?php echo $supply_request->info['cc_type']; ?></td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Account Name:&nbsp; <?php echo $supply_request->info['cc_owner']; ?></td>
			</tr>
			<tr>
				<td style="text-transform:capitalize;">Account #:&nbsp; <?php echo $supply_request->info['cc_number']; ?></td>
			</tr>
<?php 	} ?>

		</table>
	</td>
</tr>
</table></td>
          </tr>
        </table>

<!-- // # END top info table -->
<table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr class="dataTableHeadingRow">
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_QTY; ?></td>
            <td class="dataTableHeadingContent" style="padding-left:5px;"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_TAX; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_COST; ?></td>
            <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($supply_request->products); $i<$n; $i++) {

      echo '<tr class="dataTableRow">
				<td class="dataTableContent" align="center">' . $supply_request->products[$i]['qty'] . '</td>

				<td class="dataTableContent"><b>' . $supply_request->products[$i]['name'] . '</b>';


		if(isset($supply_request->products[$i]['attributes']) && (sizeof($supply_request->products[$i]['attributes']) > 0)) {

			for($j = 0, $k = sizeof($supply_request->products[$i]['attributes']); $j < $k; $j++) {

				echo '<br><small>&nbsp; &#8226; &nbsp;' . $supply_request->products[$i]['attributes'][$j]['option'] . ': ' . $supply_request->products[$i]['attributes'][$j]['value'];

				if($supply_request->products[$i]['attributes'][$j]['price'] != '0') {
					echo ' (' . $supply_request->products[$i]['attributes'][$j]['prefix'] . $currencies->format($supply_request->products[$i]['attributes'][$j]['price'] * $supply_request->products[$i]['qty'], true, $supply_request->info['currency'], $supply_request->info['currency_value']) . ')';
				}

				echo '</small>';
			}
		}

		echo '</td>';

		echo '<td class="dataTableContent" align="center">' . $supply_request->products[$i]['model'] . '</td>

			  <td class="dataTableContent" align="right">' . tep_display_tax_value($supply_request->products[$i]['tax']) . '%</td>

			  <td class="dataTableContent" align="right"><b>' . $currencies->format($supply_request->products[$i]['cost_price'], true, $supply_request->info['currency'], $supply_request->info['currency_value']) . '</b></td>

			  <td class="dataTableContent" align="right"><b>' . $currencies->format(tep_add_tax($supply_request->products[$i]['cost_price'], $supply_request->products[$i]['tax']) * $supply_request->products[$i]['qty'], true, $supply_request->info['currency'], $supply_request->info['currency_value']) . '</b></td>
		</tr>';
    }
?>
		<tr>
			<td class="tableinfo_right-btm">&nbsp;</td>
			<td class="tableinfo_right-btm">&nbsp;</td>
			<td class="tableinfo_right-btm">&nbsp;</td>
			<td class="tableinfo_right-btm" colspan="3" style="border-right:1px solid #FFF;">&nbsp;</td>
		</tr>
		<tr>
		  <td colspan="3" valign="top" bgcolor="#FFFFC4" class="tableinfo_right-btm" >
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
		    		<tr>
						<td height="91" valign="top" style="padding-top:5px; font:normal 11px arial; color:#000000">&nbsp; <u>P/O Comments</u>: <br>
		      				<br><div style="padding:5px 10px; font-weight:bold;"><?php echo $supply_request->info['comments']?></div></td>
		 			</tr>
					<tr>
						<td align="right" style="padding:5px;">  <a href="supply_request.php?sID=<?php echo $_GET['sID'] . '&action=edit#customer_comments'?>" style="color:#0000FF; font:normal 11px arial;"><u>view or add other comments</u></a></td>
		  			</tr>
				</table>
		  </td>
		  <td colspan="3" align="center" bgcolor="#EBF1F5" style="font:bold 11px arial; color:#000000;">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php

	for ($i = 0, $n = sizeof($supply_request->totals); $i < $n; $i++) {
		echo '<tr>
				<td height="23" class="tableinfo_right-btm" align="right" style="padding:0 5px 0 5px; font:bold 12px arial; color:#FF0000;">' . $supply_request->totals[$i]['title'] . '</td>
				<td align="right" class="tableinfo_right-end align_right" style="padding: 0 5px 0 5px; font:bold 12px arial; color:#000000; border-right:1px solid #FFF;">' . $supply_request->totals[$i]['text'] . '</td>
			</tr>';
    }
?>
            
          </table>
</td>
		  </tr>

      <tr>
        <td class="main" colspan="6">
			<table width="100%" border="0" cellspacing="0" cellpadding="5">
          <tr>
			<td>
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr> 
                <td>
						 <table border="0" cellspacing="0" cellpadding="2" width="100%" class="commentsTable">
                          <tr>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_DATE_ADDED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></th>
                            <th class="smallText" align="center"><b><?php echo TABLE_HEADING_STATUS; ?></b></th>
                            <th width="50%" class="smallText" align="center"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></th>
                          </tr>
<?php

		$supply_request_history_query = tep_db_query("SELECT supply_request_status_id, 
															 date_added, 
															 supplier_notified, 
															 comments 
													  FROM " . TABLE_SUPPLY_REQUEST_STATUS_HISTORY . " 
													  WHERE supply_request_id = '" . tep_db_input($sID) . "' 
													  ORDER BY date_added
													 ");

		if(tep_db_num_rows($supply_request_history_query) > 0) {

			while ($supply_request_history = tep_db_fetch_array($supply_request_history_query)) {
				echo '<tr>
						<td class="smallText" align="center">' . date('m/d/Y h:ia', strtotime($supply_request_history['date_added'])).'</td>	
						<td class="smallText" align="center">';

				if($supply_request_history['supplier_notified'] == '1') {

					echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK) . '</td>';
				} else {
					echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) . '</td>';
				}
				
				echo '<td class="smallText" align="center">' . $supply_request_status_id_array[$supply_request_history['supply_request_status_id']] . '</td>
					  <td class="smallText" style="background-color:#FFF; padding: 10px;">' . nl2br(tep_db_output($supply_request_history['comments'])) . '&nbsp;</td>
					</tr>' . "\n";
			}

		} else {

			echo '<tr>
					<td class="smallText" colspan="4">' . TEXT_NO_SUPPLY_REQUEST_HISTORY . '</td>
				</tr>';
		}
?>
			</table>
		</td>
      </tr>
 </table>
	<?php echo tep_draw_form('status', FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('action')) . 'action=update_request'); ?> 
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="main">
			<tr>
				<td style="padding-left:10px;">

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="main" style="margin-top:10px;">
	<tr>
        <td class="main"><b><?php echo TABLE_HEADING_UPS_TRACKING; ?>:</b>&nbsp; &nbsp; &nbsp;<?php echo tep_draw_textbox_field('ups_track_num', '20', '18', '', $supply_request->info['ups_track_num']); ?>  &nbsp; &nbsp; <?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><?php echo TABLE_HEADING_FEDEX_TRACKING; ?>:</b>&nbsp;&nbsp;<?php echo tep_draw_textbox_field('fedex_track_num', '20', '18', '', $supply_request->info['fedex_track_num']); ?>  &nbsp; &nbsp; <?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><?php echo TABLE_HEADING_USPS_TRACKING; ?>:</b>&nbsp; &nbsp;<?php echo tep_draw_textbox_field('usps_track_num', '20', '18', '', $supply_request->info['usps_track_num']); ?> &nbsp; &nbsp; <?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '25'); ?></td>
      </tr>
</table>
		
		</td>
		<td valign="top">
			<table width="100%" border="0" cellpadding="0" cellspacing="0" class="main">
				<tr>
					<td class="main" colspan="2"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
				</tr>
				<tr>
					<td class="main" valign="top" colspan="2"><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5', '', 'style="width:99%;"'); ?></td>
				</tr>
				<tr>
					<td class="main"><?php echo tep_draw_checkbox_field('notify', '', false) . ' <b>' . ENTRY_NOTIFY_SUPPLIER .'</b>' . tep_draw_hidden_field('notify_comments',true);?></td><td align="right" style="padding:5px"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
				</tr>
			</table>
</td>
</tr>
</table>
	
<table border="0" cellspacing="0" cellpadding="2">
      <tr>

			<td>
			<table border="0" cellspacing="0" cellpadding="2">
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main"><b><?php echo ENTRY_STATUS; ?></b> 
                                    <?php echo tep_draw_pull_down_menu('status', $supply_request_status_ides, $supply_request->info['supply_request_status_id']); ?></td>
                                </tr>
                               
                              </table></td>
                            <td valign="top"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                          </tr>
                        </table></td>
                    </tr>
				</table>
		</form>

<table width="100%" cellspacing="0" cellpadding="0" border="0">
      <tr>
		<td align="right">
		<?php echo ' <a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a> <a href="' . tep_href_link(FILENAME_EDIT_SUPPLY_REQUEST, 'sID='.$_GET['sID']).'">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a>'; ?>
	</td>
      </tr>

</table>


<?php
  } else {
?>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
      <tr>
         
<?php 

	$sID = (int)$_GET['sID'];
	$supply_request = new supply_request($sID);

	// #### SORT ORDER PAGE #### //
	switch ($listing) {
		case "id-asc":
			$orderby = "sr.supply_request_id ASC";
		break;

		case "id-desc":
			$orderby = "sr.supply_request_id DESC";
		break;

		case "suppliers-asc":
			$orderby = "sup.suppliers_group_name ASC";
		break;
		
		case "suppliers-desc":
       		$orderby = "sup.suppliers_group_name DESC";
		break;

		case "sottotal-asc":
			$orderby = "sot.value ASC";
		break;

		case "sottotal-desc":
			$orderby = "sot.value DESC";
		break;

		case "date-asc":
			$orderby = "sr.date_requested ASC";
		break;

		case "date-desc":
			$orderby = "sr.date_requested DESC";
		break;

		case "status-asc":
			$orderby = "s.supply_request_status_name";
		break;

		case "status-desc":
			$orderby = "s.supply_request_status_name DESC";
		break;

        default:
			$orderby = "sr.supply_request_id DESC";
	}
?>

		<td width="100%">
			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td width="53" style="padding:5px"><img src="/admin/images/icons/supply-icon.png" width="48" height="48"></td>
					<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
					<td align="right">
						<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td class="smallText" align="right">
									<?php echo tep_draw_form('customer_search', FILENAME_SUPPLY_REQUEST, '', 'get'); ?>
										<b>Name Search</b>: <input type="text" onblur="if (this.value=='') this.value=this.defaultValue" onclick="if (this.defaultValue==this.value) this.value=''" value="Supplier Name or Contact" name="custName" size="20">

										<?php echo tep_draw_hidden_field('action', 'cust_search'); ?>
									</form>
								</td>
								<td class="smallText" align="right">
<?php 
		echo tep_draw_form('supply_request', FILENAME_SUPPLY_REQUEST, '', 'get'); 
		echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('sID', '', 'size="5"');
		echo tep_draw_hidden_field('action', 'edit'); 
?>
		</form>
								</td>
								<td class="smallText" align="right"><?php echo tep_draw_form('status', FILENAME_SUPPLY_REQUEST, '', 'get') . '
				' . HEADING_TITLE_STATUS . ' ' . tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_ALL_ORDERS)), $supply_request_status_ides), '', 'onChange="this.form.submit();"'); 
?>
								</form>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td valign="top">
						<table border="0" width="100%" cellspacing="0" cellpadding="5">
							<tr class="dataTableHeadingRow">
								<td class="dataTableHeadingContent"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'id-asc' ? 'id-desc':'id-asc').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">View</a>
								<td class="dataTableHeadingContent">
									<a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'suppliers-asc' ? 'suppliers-desc':'suppliers-asc').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_SUPPLIERS; ?></a>
								</td>
								<td class="dataTableHeadingContent" align="right"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'sottotal-asc' ? 'sottotal-desc':'sottotal-asc').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_ORDER_TOTAL; ?></a>
								</td>
								<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'date-asc' ? 'date-desc':'date-asc').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_DATE_REQUESTED; ?></a>
								</td>
								<td class="dataTableHeadingContent" align="right"><a href="<?php echo $_SERVER['PHP_SELF'] .'?listing='.($_GET['listing'] == 'status-asc' ? 'status-desc':'status-asc').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_STATUS; ?></a>
								</td>
				                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
							</tr>
<?php

if(isset($_GET['custName'])){
	$custName = $_GET['custName'];

	$where = " AND (sup.suppliers_group_name LIKE '%".$custName."%' OR sup.suppliers_name LIKE  '%".$custName."%')";
	$orderby = ' sr.supply_request_id DESC';

} else {

	if (isset($_GET['sID']) && (isset($_GET['action']) && $_GET['action'] == 'delete')) {

		$supID = (int)$supply_request->supplier['suppliers_id'];

		$where = " AND sr.suppliers_id = '" . $supID . "'";
		$orderby = ' sr.supply_request_id DESC';

	} elseif(!empty($_GET['status'])) {

		$status = (int)$_GET['status'];

		$where = " AND s.supply_request_status_id = '".$status."'";
		$orderby = ' sr.supply_request_id DESC';

	} else {

		$where = "";
	
	}
}


		$supply_request_query_raw = "SELECT sr.supply_request_id, 
											sr.suppliers_id, 
											sr.suppliers_company, 
											sr.payment_method, 
											sr.date_requested, 
											sr.last_modified, 
											sr.currency, 
											sr.currency_value, 
											s.supply_request_status_name, 
											sot.text as order_total,
											sup.suppliers_group_name AS suppliers_name
									FROM " . TABLE_SUPPLY_REQUEST . " sr 
									LEFT JOIN " . TABLE_SUPPLY_REQUEST_STATUS . " s ON s.supply_request_status_id = sr.supply_request_status_id
									LEFT JOIN " . TABLE_SUPPLY_REQUEST_TOTAL . " sot ON sot.supply_request_id = sr.supply_request_id
									LEFT JOIN ". TABLE_SUPPLIERS." sup ON sup.suppliers_id = sr.suppliers_id
									WHERE s.language_id = '" . (int)$languages_id . "' 
									AND sot.class = 'ot_total'
									".$where."
									ORDER BY ".$orderby;


if(!$supply_request_query_numrows) $supply_request_query_numrows = 50;

    $supply_request_split = new splitPageResults($_GET['page'], 25, $supply_request_query_raw, $supply_request_query_numrows);
    $supply_request_query = tep_db_query($supply_request_query_raw);

    while ($supply_request = tep_db_fetch_array($supply_request_query)) {

		if ((!isset($_GET['sID']) || (isset($_GET['sID']) && ($_GET['sID'] == $supply_request['supply_request_id']))) && !isset($sInfo)) {
        	$sInfo = new objectInfo($supply_request);
		}

		if(isset($sInfo) && is_object($sInfo) && ($supply_request['supply_request_id'] == $sInfo->supply_request_id)) {

			echo '<tr id="defaultSelected" class="dataTableRowSelected" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID=' . $sInfo->supply_request_id . '&action=edit') . '\'">' . "\n";

      } else {

			echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID')) . 'sID=' . $supply_request['supply_request_id']) . '\'">' . "\n";
      }

?>

	<td class="dataTableContent">
		<?php echo '<a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID=' . $supply_request['supply_request_id'] . '&action=edit') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '&nbsp;'; ?></a>
	</td>

	<td class="dataTableContent" align="left">
		<?php echo '<a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID='. $supply_request['supply_request_id']) . '&action=edit">' . $supply_request['suppliers_name'] . '</a>'  ?>
	</td>
    
	<td class="dataTableContent" align="right"><?php echo $supply_request['order_total']; ?></td>
	<td class="dataTableContent" align="center"><?php echo tep_datetime_short($supply_request['date_requested']); ?></td>
	<td class="dataTableContent" align="right"><?php echo $supply_request['supply_request_status_name']; ?></td>
	<td class="dataTableContent" align="right">
<?php 
	if (isset($sInfo) && is_object($sInfo) && ($supply_request['supply_request_id'] == $sInfo->supply_request_id)) { 
		echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); 
	} else { 
		echo '<a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID')) . 'sID=' . $supply_request['supply_request_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; 
	} 
?>
		&nbsp;</td>
			</tr>
<?php
    }
?>
              <tr>
                <td colspan="6">
					<table border="0" width="100%" cellspacing="0" cellpadding="2">
                  		<tr>
                    		<td class="smallText" valign="top"><?php echo $supply_request_split->display_count($supply_request_query_numrows, 25, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
                    		<td class="smallText" align="right"><?php echo $supply_request_split->display_links($supply_request_query_numrows, 25, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], tep_get_all_get_params(array('page', 'sID', 'action'))); ?></td>
                  		</tr>
                	</table>
				</td>
              </tr>
              <tr>
                <td colspan="6" align="right" style="padding:0 5px">
					<a href="create_supply_request.php"><img src="images/createPO_button.png"></a>
				</td>
			  </tr>
            </table>
		</td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {

    case 'delete':
 		$supply_request = new supply_request($sID);

		$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_ORDER . '</b>');

		$contents = array('form' => tep_draw_form('supply_request', FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID=' . $sID.'&action=deleteconfirm'));

		$contents[] = array('text' => TEXT_INFO_DELETE_INTRO . '<br><br><b>' . $supply_request->supplier['company'] . '</b><br>'.HEADING_TITLE_SEARCH . ' ' . $sID);

		$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID=' . $sInfo->supply_request_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

      break;

    default:

      if (isset($sInfo) && is_object($sInfo)) {

        $heading[] = array('text' => '<b>P/O: &nbsp;' . $sInfo->supply_request_id . '&nbsp; ' . date('m/d/Y - h:ia' ,strtotime($sInfo->date_requested)) . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_EDIT_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID=' . $sInfo->supply_request_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, tep_get_all_get_params(array('sID', 'action')) . 'sID=' . $sInfo->supply_request_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');

        $contents[] = array('text' => '<br>' . TEXT_DATE_ORDER_CREATED . ' ' . tep_date_short($sInfo->date_requested));

        if (tep_not_null($sInfo->last_modified)) {
			$contents[] = array('text' => TEXT_DATE_ORDER_LAST_MODIFIED . ' ' . tep_date_short($sInfo->last_modified));
		}

        $contents[] = array('text' => '<br>' . TEXT_INFO_SUPPLIER_NAME . ' '  . $sInfo->suppliers_name);
        $contents[] = array('text' => TEXT_INFO_SUPPLY_REUQEST_TOTAL . ' '  . $sInfo->order_total);
        $contents[] = array('text' => TEXT_INFO_PAYMENT_METHOD . ' '  . $sInfo->payment_method);
	}

	break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top" style="padding:0 0 0 5px">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
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
