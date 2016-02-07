<?php

  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  if ($_GET['selected_box']) {
    $_GET['action']='';
    $_GET['old_action']='';
  }
  
  if (($_GET['action'] == 'send_email_to_user') && ($_POST['customers_email_address']) && (!$_POST['back_x'])) {
    switch ($_POST['customers_email_address']) {
    case '***':
      $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS);
      $mail_sent_to = TEXT_ALL_CUSTOMERS;
      break;
    case '**D':
      $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_newsletter = '1'");
      $mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
      break;
    default:
      $customers_email_address = tep_db_prepare_input($_POST['customers_email_address']);
      $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($customers_email_address) . "'");
      $mail_sent_to = $_POST['customers_email_address'];
      break;
    }

    $coupon_query = tep_db_query("select coupon_code from " . TABLE_COUPONS . " where coupon_id = '" . $_GET['cid'] . "'");
    $coupon_result = tep_db_fetch_array($coupon_query);
    $coupon_name_query = tep_db_query("select coupon_name from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $_GET['cid'] . "' and language_id = '" . $languages_id . "'");
    $coupon_name = tep_db_fetch_array($coupon_name_query);

    $from = tep_db_prepare_input($_POST['from']);
    $subject = tep_db_prepare_input($_POST['subject']);

    while ($mail = tep_db_fetch_array($mail_query)) {
      $message = tep_db_prepare_input($_POST['message']);
      $message .= "\n\n" . TEXT_TO_REDEEM . "\n\n";
      $message .= TEXT_VOUCHER_IS . $coupon_result['coupon_code'] . "\n\n";
      $message .= TEXT_REMEMBER . "\n\n";
      $message .= TEXT_VISIT . "\n\n";
     
      //Let's build a message object using the email class
      $mimemessage = new email(array('X-Mailer: bulk mailer'));
      // add the message to the object
      $mimemessage->add_text($message);
      $mimemessage->build_message();    
      $mimemessage->send($mail['customers_firstname'] . ' ' . $mail['customers_lastname'], $mail['customers_email_address'], '', $from, $subject);
    }

    tep_redirect(tep_href_link(FILENAME_COUPON_ADMIN, 'mail_sent_to=' . urlencode($mail_sent_to)));
  }
 
  if ( ($_GET['action'] == 'preview_email') && (!$_POST['customers_email_address']) ) {
    $_GET['action'] = 'email';    
    $messageStack->add(ERROR_NO_CUSTOMER_SELECTED, 'error');
  }

  if ($_GET['mail_sent_to']) {
    $messageStack->add(sprintf(NOTICE_EMAIL_SENT_TO, $_GET['mail_sent_to']), 'notice');
  }

  switch ($_GET['action']) {
    case 'confirmdelete':
      $delete_query = tep_db_query("UPDATE " . TABLE_COUPONS . " SET coupon_active = 'N' where coupon_id='".$_GET['cid']."'");
      break;

    case 'update':

		// # get all HTTP_POST_VARS and validate
		$_POST['coupon_code'] = strtolower(trim($_POST['coupon_code']));

        $languages = tep_get_languages();

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $language_id = $languages[$i]['id'];
          $_POST['coupon_name'][$language_id] = trim($_POST['coupon_name'][$language_id]);
          $_POST['coupon_desc'][$language_id] = trim($_POST['coupon_desc'][$language_id]);
		}

		$_POST['coupon_amount'] = trim($_POST['coupon_amount']);

		$update_errors = 0;

		if (!$_POST['coupon_name']) {
	        $update_errors = 1;
    	    $messageStack->add(ERROR_NO_COUPON_NAME, 'error');
		}

		if ((!$_POST['coupon_amount']) && (!$_POST['coupon_free_ship'])) {
	        $update_errors = 1;
    	    $messageStack->add(ERROR_NO_COUPON_AMOUNT, 'error');
		}

		if (!$_POST['coupon_code']) {
	        $coupon_code = create_coupon_code(); 
		}

		if($_POST['coupon_code']) { 
			$coupon_code = tep_db_prepare_input(strtolower(trim($_POST['coupon_code'])));
		}	

		$query1 = tep_db_query("select coupon_code from " . TABLE_COUPONS . " where coupon_code = '" . $coupon_code . "'");

		if (tep_db_num_rows($query1) && $_POST['coupon_code'] && $_GET['oldaction'] != 'voucheredit')  {
			$update_errors = 1;
			$messageStack->add(ERROR_COUPON_EXISTS, 'error');
		}

		if ($update_errors != 0) {
			$_GET['action'] = 'new';  
		} else {  
			$_GET['action'] = 'update_preview';
		}
	break;

	case 'update_confirm':

		if ( ($_POST['back_x']) || ($_POST['back_y']) ) {
	
			$_GET['action'] = 'new';

		} else {

			$coupon_type = "F";

			if(substr($_POST['coupon_amount'], -1) == '%') {
				$coupon_type = 'P';
			}

			if($_POST['coupon_free_ship']) {
				$coupon_type = 'S';
			}

			$coupon_pricegroups = @unserialize($_POST['coupon_pricegroups']);

			if(is_array($coupon_pricegroups)) {
				$coupon_pricegroups = implode(',',$coupon_pricegroups);
			} else {
				$coupon_pricegroups = $coupon_pricegroups;

			}

			$sql_data_array = array('coupon_code' => tep_db_prepare_input(strtolower(trim($_POST['coupon_code']))),
            	                    'coupon_amount' => tep_db_prepare_input($_POST['coupon_amount']),
									'coupon_active' => (strtotime($_POST['coupon_finishdate']) > time() ? 'Y' : 'N'),
                	                'coupon_type' => $coupon_type,
                    	            'uses_per_coupon' => (int)$_POST['coupon_uses_coupon'],
                        	        'uses_per_user' => (int)$_POST['coupon_uses_user'],
                            	    'coupon_minimum_order' => tep_db_prepare_input($_POST['coupon_min_order']),
	                                'coupon_apply_to_shipping' => ($_POST['coupon_apply_to_shipping']?'Y':'N'),
    	                            'restrict_to_customers' => (!empty($_POST['coupon_customers']) ? tep_db_input($_POST['coupon_customers']) : 'null'),
        	                        'restrict_to_products' => (!empty($_POST['coupon_products']) ? tep_db_input($_POST['coupon_products']) : 'null'),
            	                    'restrict_to_categories' => (!empty($_POST['coupon_categories']) ? tep_db_input($_POST['coupon_categories']) : 'null'),
                	                'restrict_to_pricegroups' =>  preg_replace('/[^0-9,]/', '', $coupon_pricegroups),
                    	            'coupon_start_date' => $_POST['coupon_startdate'],
                        	        'coupon_expire_date' => $_POST['coupon_finishdate'],
                            	    'date_created' => date('Y-m-d H:i:s', time()),
                                	'date_modified' => date('Y-m-d H:i:s', time())
									);

			$languages = tep_get_languages();

			for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {

				$language_id = $languages[$i]['id'];

				$sql_data_marray[$i] = array('coupon_name' => tep_db_prepare_input($_POST['coupon_name'][$language_id]),
		        		                     'coupon_description' => tep_db_prepare_input($_POST['coupon_desc'][$language_id])
											);
			}

			if ($_GET['oldaction'] == 'voucheredit') {

				tep_db_perform(TABLE_COUPONS, $sql_data_array, 'update', "coupon_id='" . $_GET['cid']."'"); 

				for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
					
					$language_id = $languages[$i]['id'];

					$update = tep_db_query("UPDATE " . TABLE_COUPONS_DESCRIPTION . " 
											SET coupon_name = '" . tep_db_prepare_input($_POST['coupon_name'][$language_id]) . "', 
											coupon_description = '" . tep_db_prepare_input($_POST['coupon_desc'][$language_id]) . "' 
											WHERE coupon_id = '" . $_GET['cid'] . "' 
											AND language_id = '" . $language_id . "'
										   ");

				}

			} else {

				$query = tep_db_perform(TABLE_COUPONS, $sql_data_array);
				$insert_id = tep_db_insert_id($query);
          
				for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {

					$language_id = $languages[$i]['id'];
		            $sql_data_marray[$i]['coupon_id'] = $insert_id;
    		        $sql_data_marray[$i]['language_id'] = $language_id;
	        	    tep_db_perform(TABLE_COUPONS_DESCRIPTION, $sql_data_marray[$i]);            
    			}
			}
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
<script src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script>var dateAvailable = new ctlSpiffyCalendarBox("dateAvailable", "new_product", "products_date_available","btnDate1","<?php echo $pInfo->products_date_available; ?>",scBTNMODE_CUSTOMBLUE);</script>
</head>
<body style="margin:0; background:transparent;">
<div id="spiffycalendar" class="text"></div>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>



<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    
<?php 
  switch ($_GET['action']) {
  case 'voucherreport':
?>
      <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo CUSTOMER_ID; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo CUSTOMER_NAME; ?></td>	
                <td class="dataTableHeadingContent" align="center"><?php echo IP_ADDRESS; ?></td>	
                <td class="dataTableHeadingContent" align="center"><?php echo REDEEM_DATE; ?></td>	
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $cc_query_raw = "select * from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $_GET['cid'] . "'";
    $cc_split = new splitPageResults($_GET['page'], '100', $cc_query_raw, $cc_query_numrows);
    $cc_query = tep_db_query($cc_query_raw);
    while ($cc_list = tep_db_fetch_array($cc_query)) {
      $rows++;
      if (strlen($rows) < 2) {
        $rows = '0' . $rows;
      }
      if (((!$_GET['uid']) || (@$_GET['uid'] == $cc_list['unique_id'])) && (!$cInfo)) {
        $cInfo = new objectInfo($cc_list);
      }
      if ( (is_object($cInfo)) && ($cc_list['unique_id'] == $cInfo->unique_id) ) {
        echo '          <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link('coupon_admin.php', tep_get_all_get_params(array('cid', 'action', 'uid')) . 'cid=' . $cInfo->coupon_id . '&action=voucherreport&uid=' . $cinfo->unique_id) . '\'">' . "\n";
      } else {
        echo '          <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link('coupon_admin.php', tep_get_all_get_params(array('cid', 'action', 'uid')) . 'cid=' . $cc_list['coupon_id'] . '&action=voucherreport&uid=' . $cc_list['unique_id']) . '\'">' . "\n";
      }
$customer_query = tep_db_query("select customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " where customers_id = '" . $cc_list['customer_id'] . "'");
$customer = tep_db_fetch_array($customer_query);

?>
                <td class="dataTableContent"><?php echo $cc_list['customer_id']; ?></td>
                <td class="dataTableContent" align="center"><?php echo $customer['customers_firstname'] . ' ' . $customer['customers_lastname']; ?></td>
                <td class="dataTableContent" align="center"><?php echo $cc_list['redeem_ip']; ?></td>
                <td class="dataTableContent" align="center"><?php echo tep_date_short($cc_list['redeem_date']); ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($cInfo)) && ($cc_list['unique_id'] == $cInfo->unique_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_COUPON_ADMIN, 'page=' . $_GET['page'] . '&cid=' . $cc_list['coupon_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>

 

             </table></td>
<?php
    $heading = array();
    $contents = array();
      $coupon_description_query = tep_db_query("select coupon_name from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $_GET['cid'] . "' and language_id = '" . $languages_id . "'");
      $coupon_desc = tep_db_fetch_array($coupon_description_query);
      $count_customers = tep_db_query("select * from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $_GET['cid'] . "' and customer_id = '" . $cInfo->customer_id . "'");
       
      $heading[] = array('text' => '<b>[' . $_GET['cid'] . ']' . COUPON_NAME . ' ' . $coupon_desc['coupon_name'] . '</b>');
      $contents[] = array('text' => '<b>' . TEXT_REDEMPTIONS . '</b>');
      $contents[] = array('text' => TEXT_REDEMPTIONS_TOTAL . '=' . tep_db_num_rows($cc_query));
      $contents[] = array('text' => TEXT_REDEMPTIONS_CUSTOMER . '=' . tep_db_num_rows($count_customers));
      $contents[] = array('text' => '');
?>
    <td width="25%" valign="top">
<?php
      $box = new box;
      echo $box->infoBox($heading, $contents);
      echo '            </td>' . "\n";
?>
<?php
    break;
  case 'preview_email': 
    $coupon_query = tep_db_query("select coupon_code from " .TABLE_COUPONS . " where coupon_id = '" . $_GET['cid'] . "'");
    $coupon_result = tep_db_fetch_array($coupon_query);
    $coupon_name_query = tep_db_query("select coupon_name from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $_GET['cid'] . "' and language_id = '" . $languages_id . "'");
    $coupon_name = tep_db_fetch_array($coupon_name_query);
    switch ($_POST['customers_email_address']) {
    case '***':
      $mail_sent_to = TEXT_ALL_CUSTOMERS;
      break;
    case '**D':
      $mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
      break;
    default:
      $mail_sent_to = $_POST['customers_email_address'];
      break;
    }
?>
      <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
          <tr><?php echo tep_draw_form('mail', FILENAME_COUPON_ADMIN, 'action=send_email_to_user&cid=' . $_GET['cid']); ?>
            <td><table border="0" width="100%" cellpadding="0" cellspacing="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_CUSTOMER; ?></b><br><?php echo $mail_sent_to; ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_COUPON; ?></b><br><?php echo $coupon_name['coupon_name']; ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_FROM; ?></b><br><?php echo htmlspecialchars(stripslashes($_POST['from'])); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_SUBJECT; ?></b><br><?php echo htmlspecialchars(stripslashes($_POST['subject'])); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_MESSAGE; ?></b><br><?php echo nl2br(htmlspecialchars(stripslashes($_POST['message']))); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td>
<?php
/* Re-Post all POST'ed variables */
    reset($_POST);
    while (list($key, $value) = each($_POST)) {
      if (!is_array($_POST[$key])) {
        echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
      }
    }
?>
                <table border="0" width="100%" cellpadding="0" cellspacing="2">
                  <tr>
                    <td><?php ?>&nbsp;</td>
                    <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_COUPON_ADMIN) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a> ' . tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </form></tr>
<?php 
    break;       
  case 'email':
    $coupon_query = tep_db_query("select coupon_code from " . TABLE_COUPONS . " where coupon_id = '" . $_GET['cid'] . "'");
    $coupon_result = tep_db_fetch_array($coupon_query);
    $coupon_name_query = tep_db_query("select coupon_name from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $_GET['cid'] . "' and language_id = '" . $languages_id . "'");
    $coupon_name = tep_db_fetch_array($coupon_name_query);
?>
      <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>

          <tr><?php echo tep_draw_form('mail', FILENAME_COUPON_ADMIN, 'action=preview_email&cid='. $_GET['cid']); ?>
            <td><table border="0" cellpadding="0" cellspacing="2">
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
<?php
    $customers = array();
    $customers[] = array('id' => '', 'text' => TEXT_SELECT_CUSTOMER);
    $customers[] = array('id' => '***', 'text' => TEXT_ALL_CUSTOMERS);
    $customers[] = array('id' => '**D', 'text' => TEXT_NEWSLETTER_CUSTOMERS);
    $mail_query = tep_db_query("select customers_email_address, customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " order by customers_lastname");
    while($customers_values = tep_db_fetch_array($mail_query)) {
      $customers[] = array('id' => $customers_values['customers_email_address'],
                           'text' => $customers_values['customers_lastname'] . ', ' . $customers_values['customers_firstname'] . ' (' . $customers_values['customers_email_address'] . ')');
    }
?>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_COUPON; ?>&nbsp;&nbsp;</td>
                <td><?php echo $coupon_name['coupon_name']; ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_CUSTOMER; ?>&nbsp;&nbsp;</td>
                <td><?php echo tep_draw_pull_down_menu('customers_email_address', $customers, $_GET['customer']);?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_FROM; ?>&nbsp;&nbsp;</td>
                <td><?php echo tep_draw_input_field('from', EMAIL_FROM); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>

              <!--tr>
                <td class="main"><?php echo TEXT_RESTRICT; ?>&nbsp;&nbsp;</td>
                <td><?php //echo tep_draw_checkbox_field('customers_restrict', $customers_restrict);?></td>
              </tr-->

              <tr>
                <td class="main"><?php echo TEXT_SUBJECT; ?>&nbsp;&nbsp;</td>
                <td><?php echo tep_draw_input_field('subject'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td valign="top" class="main"><?php echo TEXT_MESSAGE; ?>&nbsp;&nbsp;</td>
                <td><?php echo tep_draw_textarea_field('message', 'soft', '60', '15'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td colspan="2" align="right"><?php echo tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL); ?></td>
              </tr>
            </table></td>
          </form></tr>

      </tr>
      </td>
<?php      
    break;
  case 'update_preview':
?>
      <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
      <td>
<?php echo tep_draw_form('coupon', 'coupon_admin.php', 'action=update_confirm&oldaction=' . $_GET['oldaction'] . '&cid=' . $_GET['cid']); ?>
      <table border="0" width="100%" cellspacing="0" cellpadding="6">
<?php
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
            $language_id = $languages[$i]['id'];
?>
      <tr>
        <td align="left"><?php echo COUPON_NAME; ?></td>
        <td align="left"><?php echo $_POST['coupon_name'][$language_id]; ?></td>
      </tr>
<?php
}
?>
<?php
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
            $language_id = $languages[$i]['id'];
?>
      <tr>
        <td align="left"><?php echo COUPON_DESC; ?></td>
        <td align="left"><?php echo $_POST['coupon_desc'][$language_id]; ?></td>
      </tr>
<?php
}
?>
      <tr>
        <td align="left"><?php echo COUPON_AMOUNT; ?></td>
        <td align="left"><?php echo $_POST['coupon_amount']; ?></td>
      </tr>
 
      <tr>
        <td align="left"><?php echo COUPON_MIN_ORDER; ?></td>
        <td align="left"><?php echo $_POST['coupon_min_order']; ?></td>
      </tr>

      <tr>
        <td align="left"><?php echo COUPON_FREE_SHIP; ?></td>
<?php
    if ($_POST['coupon_free_ship']) {
?>
        <td align="left"><?php echo TEXT_FREE_SHIPPING; ?></td>
<?php
    } else { 
?>
        <td align="left"><?php echo TEXT_NO_FREE_SHIPPING; ?></td>
<?php
    }
?>
      </tr>
      <tr>
        <td align="left"><?php echo COUPON_CODE; ?></td>
<?php
    if ($_POST['coupon_code']) {
      $c_code = tep_db_prepare_input(strtolower(trim($_POST['coupon_code'])));
    } else {
      $c_code = $coupon_code;
    }
?>
        <td align="left"><?php echo $coupon_code; ?></td>
      </tr>
      
      <tr>
        <td align="left"><?php echo COUPON_USES_COUPON; ?></td>
        <td align="left"><?php echo $_POST['coupon_uses_coupon']; ?></td>
      </tr>

      <tr>
        <td align="left"><?php echo COUPON_USES_USER; ?></td>
        <td align="left"><?php echo $_POST['coupon_uses_user']; ?></td>
      </tr>
      
       <tr>
        <td align="left"><?php echo COUPON_PRODUCTS; ?></td>
        <td align="left"><?php echo $_POST['coupon_products']; ?></td>
      </tr>


      <tr>
        <td align="left"><?php echo COUPON_CATEGORIES; ?></td>
        <td align="left"><?php echo $_POST['coupon_categories']; ?></td>
      </tr>

      <tr>
        <td align="left"><?php echo COUPON_PRICE_GROUPS; ?></td>
        <td align="left"><?php echo (is_array($coupon_pricegroups) ? implode(', ',$coupon_pricegroups): $coupon_pricegroups) ; ?></td>
      </tr>

      <tr>
        <td align="left"><?php echo COUPON_STARTDATE; ?></td>
<?php
    $start_date = date(DATE_FORMAT, mktime(0, 0, 0, $_POST['coupon_startdate_month'],$_POST['coupon_startdate_day'] ,$_POST['coupon_startdate_year'] ));
?>
        <td align="left"><?php echo $start_date; ?></td>
      </tr>
      
      <tr>
        <td align="left"><?php echo COUPON_FINISHDATE; ?></td>
<?php
    $finish_date = date(DATE_FORMAT, mktime(0, 0, 0, $_POST['coupon_finishdate_month'],$_POST['coupon_finishdate_day'] ,$_POST['coupon_finishdate_year'] ));
    echo date('Y-m-d', mktime(0, 0, 0, $_POST['coupon_startdate_month'],$_POST['coupon_startdate_day'] ,$_POST['coupon_startdate_year'] ));
?>
        <td align="left"><?php echo $finish_date; ?></td>
      </tr>
<?php
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $language_id = $languages[$i]['id'];
          echo tep_draw_hidden_field('coupon_name[' . $languages[$i]['id'] . ']', $_POST['coupon_name'][$language_id]);
          echo tep_draw_hidden_field('coupon_desc[' . $languages[$i]['id'] . ']', $_POST['coupon_desc'][$language_id]);
       }
    echo tep_draw_hidden_field('coupon_amount', $_POST['coupon_amount']);
    echo tep_draw_hidden_field('coupon_min_order', $_POST['coupon_min_order']);
    echo tep_draw_hidden_field('coupon_free_ship', $_POST['coupon_free_ship']);
    echo tep_draw_hidden_field('coupon_apply_to_shipping', $_POST['coupon_apply_to_shipping']);
    echo tep_draw_hidden_field('coupon_code', $c_code);
    echo tep_draw_hidden_field('coupon_uses_coupon', $_POST['coupon_uses_coupon']);
    echo tep_draw_hidden_field('coupon_uses_user', $_POST['coupon_uses_user']);
    echo tep_draw_hidden_field('coupon_products', $_POST['coupon_products']);
    echo tep_draw_hidden_field('coupon_categories', $_POST['coupon_categories']);
    echo tep_draw_hidden_field('coupon_pricegroups', serialize($coupon_pricegroups));
    echo tep_draw_hidden_field('coupon_startdate', date('Y-m-d', mktime(0, 0, 0, $_POST['coupon_startdate_month'],$_POST['coupon_startdate_day'] ,$_POST['coupon_startdate_year'] )));
    echo tep_draw_hidden_field('coupon_finishdate', date('Y-m-d', mktime(0, 0, 0, $_POST['coupon_finishdate_month'],$_POST['coupon_finishdate_day'] ,$_POST['coupon_finishdate_year'] )));
?>
     <tr>
        <td align="left"><?php echo tep_image_submit('button_confirm.gif',COUPON_BUTTON_CONFIRM); ?></td>
        <td align="left"><?php echo tep_image_submit('button_back.gif',COUPON_BUTTON_BACK, 'name=back'); ?></td>
      </td>
      </tr>
      
      </td></table></form>
      </tr>

      </table></td>
<?php      
   
    break;
  case 'voucheredit':
  case 'new':
    $languages = tep_get_languages();
    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
      $language_id = $languages[$i]['id'];
      $coupon_desc_query = tep_db_query("select coupon_name,coupon_description from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" .  $_GET['cid'] . "' and language_id = '" . $language_id . "'");
      $coupon = tep_db_fetch_array($coupon_desc_query);
      $coupon_name[$language_id] = $coupon['coupon_name'];
      $coupon_desc[$language_id] = $coupon['coupon_description'];
    }
    $coupon_query=tep_db_query("select * from " . TABLE_COUPONS . " where coupon_id = '" . $_GET['cid'] . "'");
    $coupon=tep_db_fetch_array($coupon_query);
    $coupon_amount = $coupon['coupon_amount'];
    if ($coupon['coupon_type']=='P') {
      $coupon_amount .= '%';
    }
    if ($coupon['coupon_type']=='S') {
      $coupon_free_ship .= true;
    }     
    if ($coupon['coupon_apply_to_shipping']=='Y') {
      $coupon_apply_to_shipping .= true;
    }     
    $coupon_min_order = $coupon['coupon_minimum_order'];
    $coupon_code = $coupon['coupon_code'];
    $coupon_uses_coupon = $coupon['uses_per_coupon'];
    $coupon_uses_user = $coupon['uses_per_user'];
    $coupon_products = $coupon['restrict_to_products'];
    $coupon_categories = $coupon['restrict_to_categories'];
	$coupon_pricegroups = (!empty($coupon['restrict_to_pricegroups']) ? $coupon['restrict_to_pricegroups'] :'0');


	$pricegroups_query = tep_db_query("SELECT customers_group_id, customers_group_name FROM customers_groups WHERE customers_group_id != 1");

    $pricegroups = array();

	while($pricegroup = tep_db_fetch_array($pricegroups_query)) { 

      $pricegroups[] = array('id' => $pricegroup['customers_group_id'],	'text' => $pricegroup['customers_group_name']);

	}


	$preselected_pricegroups = array();

	if(strpos($coupon_pricegroups, ',')) { 
		$coupon_pricegroups = explode(',', $coupon_pricegroups);
	}

	if(is_array($coupon_pricegroups)) {

		foreach($coupon_pricegroups as $restrict_to_pricegroups) { 

			$restrict_to_pricegroups_query = tep_db_query("SELECT customers_group_id, customers_group_name FROM customers_groups WHERE customers_group_id = '". $restrict_to_pricegroups ."'");

			$restrict_to_pricegroups = tep_db_fetch_array($restrict_to_pricegroups_query);
			$preselected_pricegroups[] = array('id' => (string)$restrict_to_pricegroups['customers_group_id'],	'text' => $restrict_to_pricegroups['customers_group_name']);
			
		}

//error_log(print_r($preselected_pricegroups,1));

			
	} else {

		$restrict_to_pricegroups_query = tep_db_query("SELECT customers_group_id, customers_group_name FROM customers_groups WHERE customers_group_id = '". $coupon_pricegroups ."'");

		while($restrict_to_pricegroups = tep_db_fetch_array($restrict_to_pricegroups_query)) { 
			$preselected_pricegroups[] = array('id' => (string)$restrict_to_pricegroups['customers_group_id'],	'text' => $restrict_to_pricegroups['customers_group_name']);
		}		
	}

	$coupon_startdate_day = (!empty($coupon['coupon_start_date'])) ? date('d',strtotime($coupon['coupon_start_date'])) : date('d');
	$coupon_startdate_month = (!empty($coupon['coupon_start_date'])) ? date('m',strtotime($coupon['coupon_start_date'])) : date('m');
	$coupon_startdate_year =(!empty($coupon['coupon_start_date'])) ? date('Y',strtotime($coupon['coupon_start_date'])) : date('Y');

	$coupon_finishdate_day = (!empty($coupon['coupon_expire_date'])) ? date('d',strtotime($coupon['coupon_expire_date'])) : date('d'); 
	$coupon_finishdate_month = (!empty($coupon['coupon_expire_date'])) ? date('m',strtotime($coupon['coupon_expire_date'])) : date('m')+1;
	$coupon_finishdate_year =(!empty($coupon['coupon_expire_date'])) ? date('Y',strtotime($coupon['coupon_expire_date'])) : date('Y');	
	
	// # set some defaults
    if (!$coupon_uses_user) $coupon_uses_user=1;
?>
      <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
      <td>
<?php 
    echo tep_draw_form('coupon', 'coupon_admin.php', 'action=update&oldaction='.$_GET['action'] . '&cid=' . $_GET['cid']); 
?>
      <table border="0" width="100%" cellspacing="0" cellpadding="6">
<?php
        $languages = tep_get_languages();

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
	        $language_id = $languages[$i]['id'];
?>
    	  <tr>
        	<td align="left" class="main"><?php if ($i==0) echo COUPON_NAME; ?></td>
	        <td align="left"><?php echo tep_draw_input_field('coupon_name[' . $languages[$i]['id'] . ']', $coupon_name[$language_id]) . '&nbsp;' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?></td>
    	    <td align="left" class="main" width="40%"><?php if ($i==0) echo COUPON_NAME_HELP; ?></td>
		 </tr>
<?php
		}
?>
<?php
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
        $language_id = $languages[$i]['id'];
?>

      <tr>
        <td align="left" valign="top" class="main"><?php if ($i==0) echo COUPON_DESC; ?></td>
        <td align="left" valign="top"><?php echo tep_draw_textarea_field('coupon_desc[' . $languages[$i]['id'] . ']','physical','24','3', $coupon_desc[$language_id]) . '&nbsp;' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?></td>
        <td align="left" valign="top" class="main"><?php if ($i==0) echo COUPON_DESC_HELP; ?></td>
      </tr>
<?php
}
?>
      <tr>
        <td align="left" class="main"><?php echo COUPON_AMOUNT; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_amount', $coupon_amount); ?></td>
        <td align="left" class="main"><?php echo COUPON_AMOUNT_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_MIN_ORDER; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_min_order', $coupon_min_order); ?></td>
        <td align="left" class="main"><?php echo COUPON_MIN_ORDER_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main">Apply to Shipping Cost</td>
        <td align="left"><?php echo tep_draw_checkbox_field('coupon_apply_to_shipping', $coupon_apply_to_shipping); ?></td>
        <td align="left" class="main">Apply the discount to shipping cost along with products cost</td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_FREE_SHIP; ?></td>
        <td align="left"><?php echo tep_draw_checkbox_field('coupon_free_ship', $coupon_free_ship); ?></td>
        <td align="left" class="main"><?php echo COUPON_FREE_SHIP_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_CODE; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_code', $coupon_code); ?></td>
        <td align="left" class="main"><?php echo COUPON_CODE_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_USES_COUPON; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_uses_coupon', $coupon_uses_coupon); ?></td>
        <td align="left" class="main"><?php echo COUPON_USES_COUPON_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_USES_USER; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_uses_user', $coupon_uses_user); ?></td>
        <td align="left" class="main"><?php echo COUPON_USES_USER_HELP; ?></td>
      </tr>

      <tr>
        <td align="left" class="main"><?php echo COUPON_PRICE_GROUPS; ?></td>
        <td align="left"><?php
echo tep_draw_pull_down_menu('coupon_pricegroups[]', $pricegroups, $preselected_pricegroups, 'id="coupon_pricegroups" style="width:150px;" multiple');

 //echo tep_draw_input_field('coupon_pricegroups', $coupon_pricegroups); 
?></td>
        <td align="left" class="main"><?php echo PRICE_GROUPS_HELP; ?></td>
      </tr>

       <tr>
        <td align="left" class="main"><?php echo COUPON_PRODUCTS; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_products', $coupon_products); ?> <a href="validproducts.php" target="_blank" onclick="window.open('validproducts.php', 'Valid_Products', 'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600'); return false">View</A></td>
        <td align="left" class="main"><?php echo COUPON_PRODUCTS_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_CATEGORIES; ?></td>
        <td align="left"><?php echo tep_draw_input_field('coupon_categories', $coupon_categories); ?> <a href="validcategories.php" target="_blank" onclick="window.open('validcategories.php', 'Valid_Categories', 'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600'); return false">View</A></td>
        <td align="left" class="main"><?php echo COUPON_CATEGORIES_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_STARTDATE ?></td>
        <td align="left"><?php echo tep_draw_date_selector('coupon_startdate', mktime(0, 0, 0, $coupon_startdate_month, $coupon_startdate_day, $coupon_startdate_year)); ?></td>
        <td align="left" class="main"><?php echo COUPON_STARTDATE_HELP; ?></td>
      </tr>
      <tr>
        <td align="left" class="main"><?php echo COUPON_FINISHDATE; ?></td>
        <td align="left"><?php echo tep_draw_date_selector('coupon_finishdate', mktime(0, 0, 0, $coupon_finishdate_month, $coupon_finishdate_day, $coupon_finishdate_year)); ?></td>
        <td align="left" class="main"><?php echo COUPON_FINISHDATE_HELP; ?></td>
      </tr>
      <tr>
        <td align="left"><?php echo tep_image_submit('button_preview.gif','Preview Coupon'); ?></td>
        <td align="left"><?php echo '&nbsp;&nbsp;<a href="' . tep_href_link('coupon_admin.php', ''); ?>"><?php echo tep_image_button('button_cancel.gif', IMAGE_CANCEL); ?></a>
      </td>
      </tr>
      </td></table></form>
      </tr>

      </table></td>
<?php
    break;
  default:
?>    
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="main"><?php echo tep_draw_form('status', FILENAME_COUPON_ADMIN, '', 'get'); ?>
<?php
    $status_array[] = array('id' => 'Y', 'text' => TEXT_COUPON_ACTIVE);
    $status_array[] = array('id' => 'N', 'text' => TEXT_COUPON_INACTIVE);
    $status_array[] = array('id' => '*', 'text' => TEXT_COUPON_ALL);

    if ($_GET['status']) {
      $status = tep_db_prepare_input($_GET['status']);
    } else { 
      $status = 'Y';
    } 
    echo HEADING_TITLE_STATUS . ' ' . tep_draw_pull_down_menu('status', $status_array, $status, 'onChange="this.form.submit();"'); 
?>
              </form>
           </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo COUPON_NAME; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo COUPON_AMOUNT; ?></td>	
                <td class="dataTableHeadingContent" align="center"><?php echo COUPON_CODE; ?></td>	
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    if ($_GET['page'] > 1) $rows = $_GET['page'] * 20 - 20;
    if ($status != '*') {
      $cc_query_raw = "select coupon_id, coupon_code, coupon_amount, coupon_type, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories, restrict_to_pricegroups, date_created,date_modified from " . TABLE_COUPONS ." where coupon_active='" . tep_db_input($status) . "' and coupon_type != 'G' ORDER BY date_created DESC";
    } else {
      $cc_query_raw = "select coupon_id, coupon_code, coupon_amount, coupon_type, coupon_start_date,coupon_expire_date,uses_per_user,uses_per_coupon,restrict_to_products, restrict_to_categories,  restrict_to_pricegroups, date_created,date_modified from " . TABLE_COUPONS . " where coupon_type != 'G' ORDER BY date_created DESC";
    }
    $cc_split = new splitPageResults($_GET['page'], '100', $cc_query_raw, $cc_query_numrows);
    $cc_query = tep_db_query($cc_query_raw);
    while ($cc_list = tep_db_fetch_array($cc_query)) {

	$update_expired_query = tep_db_query("UPDATE " . TABLE_COUPONS . " SET coupon_active = 'N' 
										  WHERE coupon_id = '". $cc_list['coupon_id'] ."' 
										  AND coupon_expire_date < NOW()
										  AND coupon_type != 'G'
										");
      $rows++;
      if (strlen($rows) < 2) {
        $rows = '0' . $rows;
      }
      if (((!$_GET['cid']) || (@$_GET['cid'] == $cc_list['coupon_id'])) && (!$cInfo)) {
        $cInfo = new objectInfo($cc_list);
      }
      if ( (is_object($cInfo)) && ($cc_list['coupon_id'] == $cInfo->coupon_id) ) {
        echo '          <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link('coupon_admin.php', tep_get_all_get_params(array('cid', 'action')) . 'cid=' . $cInfo->coupon_id . '&action=edit') . '\'">' . "\n";
      } else {
        echo '          <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link('coupon_admin.php', tep_get_all_get_params(array('cid', 'action')) . 'cid=' . $cc_list['coupon_id']) . '\'">' . "\n";
      }
      $coupon_description_query = tep_db_query("select coupon_name from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $cc_list['coupon_id'] . "' and language_id = '" . $languages_id . "'");
      $coupon_desc = tep_db_fetch_array($coupon_description_query);
?>
                <td class="dataTableContent"><?php echo $coupon_desc['coupon_name']; ?></td>
                <td class="dataTableContent" align="center">
<?php  
      if ($cc_list['coupon_type'] == 'P') {
        echo $cc_list['coupon_amount'] . '%';
      } elseif ($cc_list['coupon_type'] == 'S') {
        echo TEXT_FREE_SHIPPING;
      } else {
        echo $currencies->format($cc_list['coupon_amount']);
      }
?>
            &nbsp;</td>
                <td class="dataTableContent" align="center"><?php echo $cc_list['coupon_code']; ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($cInfo)) && ($cc_list['coupon_id'] == $cInfo->coupon_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_COUPON_ADMIN, 'page=' . $_GET['page'] . '&cid=' . $cc_list['coupon_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>
          <tr>
            <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="smallText">&nbsp;<?php echo $cc_split->display_count($cc_query_numrows, '100', $_GET['page'], TEXT_DISPLAY_NUMBER_OF_COUPONS); ?>&nbsp;</td>

               

 <td align="right" class="smallText">&nbsp;
<?php echo $cc_split->display_links($cc_query_numrows, '100', MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?>


&nbsp;</td>




              </tr>

              <tr>
                <td align="right" colspan="2" class="smallText"><?php echo '<a href="' . tep_href_link('coupon_admin.php', 'page=' . $_GET['page'] . '&cID=' . $cInfo->coupon_id . '&action=new') . '">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>

<?php

    $heading = array();
    $contents = array();

    switch ($_GET['action']) {
    case 'release':
      break;
    case 'voucherreport':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_COUPON_REPORT . '</b>');
      $contents[] = array('text' => TEXT_NEW_INTRO);
      break;
    case 'neww':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_NEW_COUPON . '</b>');
      $contents[] = array('text' => TEXT_NEW_INTRO);
      $contents[] = array('text' => '<br>' . COUPON_NAME . '<br>' . tep_draw_input_field('name'));
      $contents[] = array('text' => '<br>' . COUPON_AMOUNT . '<br>' . tep_draw_input_field('voucher_amount'));
      $contents[] = array('text' => '<br>' . COUPON_CODE . '<br>' . tep_draw_input_field('voucher_code'));
      $contents[] = array('text' => '<br>' . COUPON_USES_COUPON . '<br>' . tep_draw_input_field('voucher_number_of'));
      break;
    default:
      $heading[] = array('text'=>'['.$cInfo->coupon_id.']  '.$cInfo->coupon_code);
      $amount = $cInfo->coupon_amount;
      if ($cInfo->coupon_type == 'P') {
        $amount .= '%';
      } else {
        $amount = $currencies->format($amount);
      }
      if ($_GET['action'] == 'voucherdelete') {
        $contents[] = array('text'=> TEXT_CONFIRM_DELETE . '</br></br>' . 
                '<a href="'.tep_href_link('coupon_admin.php','action=confirmdelete&cid='.$_GET['cid'],'NONSSL').'">'.tep_image_button('button_confirm.gif','Confirm Delete Voucher').'</a>' .
                '<a href="'.tep_href_link('coupon_admin.php','cid='.$cInfo->coupon_id,'NONSSL').'">'.tep_image_button('button_cancel.gif','Cancel').'</a>'
                );
      } else { 
        $prod_details = NONE;
        if ($cInfo->restrict_to_products) {
          $prod_details = '<a href="listproducts.php?cid=' . $cInfo->coupon_id . '" target="_blank" onclick="window.open(\'listproducts.php?cid=' . $cInfo->coupon_id . '\', \'Valid_Categories\', \'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600\'); return false">View</a>';
        }     
        $cat_details = NONE;
        if ($cInfo->restrict_to_categories) {
          $cat_details = '<a href="listcategories.php?cid=' . $cInfo->coupon_id . '" target="_blank" onclick="window.open(\'listcategories.php?cid=' . $cInfo->coupon_id . '\', \'Valid_Categories\', \'scrollbars=yes,resizable=yes,menubar=yes,width=600,height=600\'); return false">View</a>';
        }

        $coupon_name_query = tep_db_query("SELECT coupon_name 
										   FROM " . TABLE_COUPONS_DESCRIPTION . " 
										   WHERE coupon_id = '" . $cInfo->coupon_id . "' 
										   AND language_id = '" . $languages_id . "'
										 ");

        $coupon_name = tep_db_fetch_array($coupon_name_query);

        $contents[] = array('text' =>  '<div align="center" style="padding:5px 0 10px 0"><a href="'.tep_href_link('coupon_admin.php','action=voucheredit&cid='.$cInfo->coupon_id,'NONSSL').'">'.tep_image_button('button_edit.gif','Edit Voucher').'</a> ' .
					 '<a href="'.tep_href_link('coupon_admin.php','action=voucherdelete&cid='.$cInfo->coupon_id,'NONSSL').'">'.tep_image_button('button_delete.gif','Delete Voucher').'</a> <br>' .
                     '<a href="'.tep_href_link('coupon_admin.php','action=email&cid='.$cInfo->coupon_id,'NONSSL').'">'.tep_image_button('button_email.gif','Email Voucher').'</a> ' .
                     '<a href="'.tep_href_link('coupon_admin.php','action=voucherreport&cid='.$cInfo->coupon_id,'NONSSL').'">'.tep_image_button('button_report.gif','Voucher Report').'</a></div>' .
					 '<div style="padding:0 5px 5px 5px">' . COUPON_NAME . '&nbsp;::&nbsp; ' . $coupon_name['coupon_name'] . '<br>' .
                     COUPON_AMOUNT . '&nbsp;::&nbsp; ' . $amount . '<br>' .
                     COUPON_STARTDATE . '&nbsp;::&nbsp; ' . date('m/d/Y',strtotime($cInfo->coupon_start_date)) . '<br>' .
                     COUPON_FINISHDATE . '&nbsp;::&nbsp; ' . date('m/d/Y',strtotime($cInfo->coupon_expire_date)) . '<br>' .
                     COUPON_USES_COUPON . '&nbsp;::&nbsp; ' . $cInfo->uses_per_coupon . '<br>' .
                     COUPON_USES_USER . '&nbsp;::&nbsp; ' . $cInfo->uses_per_user . '<br>' .
                     COUPON_PRODUCTS . '&nbsp;::&nbsp; ' . $prod_details . '<br>' .
                     COUPON_CATEGORIES . '&nbsp;::&nbsp; ' . $cat_details . '<br>' .
					 ($cInfo->restrict_to_pricegroups == '0' || !empty($cInfo->restrict_to_pricegroups) ? COUPON_PRICE_GROUPS . '&nbsp;::&nbsp; ' . $cInfo->restrict_to_pricegroups . '<br>' : '') .
                     DATE_CREATED . '&nbsp;::&nbsp; ' . date('m/d/Y',strtotime($cInfo->date_created)) . '<br>' .
                     DATE_MODIFIED . '&nbsp;::&nbsp; ' . date('m/d/Y',strtotime($cInfo->date_modified)) . '<br></div>'
					 );
        }
        break;
      }
?>                       
    <td width="25%" valign="top" style="padding: 0 3px 0 5px;">
<?php
      $box = new box;
      echo $box->infoBox($heading, $contents);
    echo '            </td>' . "\n";
    }
?>
      </tr>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
