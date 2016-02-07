<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  require('includes/application_top.php');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if ( ($action == 'send_email_to_user') && isset($HTTP_POST_VARS['customers_email_address']) && !isset($HTTP_POST_VARS['back_x']) ) {
    switch ($HTTP_POST_VARS['customers_email_address']) {
      case '***':
        $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS);
        $mail_sent_to = TEXT_ALL_CUSTOMERS;
        break;
      case '**D':
        $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_newsletter = '1'");
        $mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
        break;
      default:
        $customers_email_address = tep_db_prepare_input($HTTP_POST_VARS['customers_email_address']);

        $mail_query = tep_db_query("select customers_firstname, customers_lastname, customers_email_address from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($customers_email_address) . "'");
        $mail_sent_to = $HTTP_POST_VARS['customers_email_address'];
        break;
    }

    $from = tep_db_prepare_input($HTTP_POST_VARS['from']);
    $subject = tep_db_prepare_input($HTTP_POST_VARS['subject']);
    $message = tep_db_prepare_input($HTTP_POST_VARS['message']);

    // # Let's build a message object using the email class
    $mimemessage = new email(array('X-Mailer: IntenseCart eCommerce'));
    // # add the message to the object

	if(EMAIL_USE_HTML == 'true') {
		$mimemessage->add_html($message);
	} else {
		$mimemessage->add_text($message);
	}

    $mimemessage->build_message();

    while ($mail = tep_db_fetch_array($mail_query)) {
      $mimemessage->send($mail['customers_firstname'] . ' ' . $mail['customers_lastname'], $mail['customers_email_address'], '', $from, $subject);
    }

    tep_redirect(tep_href_link(FILENAME_MAIL, 'mail_sent_to=' . urlencode($mail_sent_to)));
  }

  if ( ($action == 'preview') && !isset($HTTP_POST_VARS['customers_email_address']) ) {
    $messageStack->add(ERROR_NO_CUSTOMER_SELECTED, 'error');
  }

  if (isset($HTTP_GET_VARS['mail_sent_to'])) {
    $messageStack->add(sprintf(NOTICE_EMAIL_SENT_TO, $HTTP_GET_VARS['mail_sent_to']), 'success');
  }
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<script type="text/javascript">
<!-- 
// # WYSIWYG HTML Area Box + Admin Function
	_editor_url = "<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_ADMIN; ?>htmlarea/";  // URL to htmlarea files
	var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);

	if(navigator.userAgent.indexOf('Mac') >= 0) { 
		win_ie_ver = 0; 
	}
	
	if(navigator.userAgent.indexOf('Windows CE') >= 0) { 
		win_ie_ver = 0; 
	}
	
	if(navigator.userAgent.indexOf('Opera') >= 0) { 
		win_ie_ver = 0; 
	}
<?php 
	if(HTML_AREA_WYSIWYG_BASIC_EMAIL == 'Basic'){ 
?>  
		if (win_ie_ver >= 5.5) {
			document.write('<scr' + 'ipt src="' +_editor_url+ 'editor_basic.js"');
			document.write(' language="Javascript1.2"></scr' + 'ipt>');
		} else { 
			document.write('<scr'+'ipt>function editor_generate() { return false; }	</scr'+'ipt>'); 
		}

     
<?php 

	} else { 
?> 

	if (win_ie_ver >= 5.5) {
		document.write('<scr' + 'ipt src="' +_editor_url+ 'editor_advanced.js"');
		document.write(' type="text/javascript"></scr' + 'ipt>');
	} else { 
		document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); 
	}

<?php } ?>
// -->
</script>

<script type="text/javascript" src="htmlarea/validation.js"></script>
<script type="text/javascript">
<!-- // # Begin

	function init() {
		define('customers_email_address', 'string', 'Customer or Newsletter Group');
	}
// # End -->
</script>

</head>
<body onload="init()" style="background-color:transparent; margin:0;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
<?php

	if(($action == 'preview') && isset($_POST['customers_email_address'])) {
    
		switch ($_POST['customers_email_address']) {

			case '***':
				$mail_sent_to = TEXT_ALL_CUSTOMERS;
			break;

			case '**D':
				$mail_sent_to = TEXT_NEWSLETTER_CUSTOMERS;
			break;

			default:
				$mail_sent_to = $HTTP_POST_VARS['customers_email_address'];
			break;
		}
?><?php echo tep_draw_form('mail', FILENAME_MAIL, 'action=send_email_to_user'); ?>
	<table border="0" width="100%" cellspacing="0" cellpadding="2">
		<tr>
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
                <td class="smallText"><b><?php echo TEXT_FROM; ?></b><br><?php echo htmlspecialchars(stripslashes($HTTP_POST_VARS['from'])); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_SUBJECT; ?></b><br><?php echo htmlspecialchars(stripslashes($HTTP_POST_VARS['subject'])); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_MESSAGE; ?></b><br>
<?php 
	if(HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Enable') {
		echo (stripslashes($HTTP_POST_VARS['message'])); 
	} else { 
		nl2br(htmlspecialchars(stripslashes($_POST['message']))); 
	} 
?>
	</td>
	</tr>

              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
				<td>
<?php
// # Re-Post all POST'ed variables
    reset($HTTP_POST_VARS);
    while (list($key, $value) = each($HTTP_POST_VARS)) {
      if (!is_array($HTTP_POST_VARS[$key])) {
        echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
      }
    }
?>
	<table border="0" width="100%" cellpadding="0" cellspacing="2">
                  <tr>
                    <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_MAIL) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a> ' . tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL); ?></td>
                    </tr>
                    <td class="smallText">
<?php 
	if (HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Disable'){
		echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="back"');
		echo(TEXT_EMAIL_BUTTON_HTML);
	} else { 
		echo(TEXT_EMAIL_BUTTON_TEXT); 
	} 
?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
	</form>
		</td>

</tr>

<?php
  } else {
?>
          <tr>
            <td>

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

<?php if(HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Enable') { ?>

	<script type="text/javascript" defer>
		// #  create new config object
		var config = new Object();
		config.width = "<?php echo EMAIL_AREA_WYSIWYG_WIDTH; ?>px";
		config.height = "<?php echo EMAIL_AREA_WYSIWYG_HEIGHT; ?>px";
		config.bodyStyle = 'background-color: <?php echo HTML_AREA_WYSIWYG_BG_COLOUR; ?>; 
		font-family: "<?php echo HTML_AREA_WYSIWYG_FONT_TYPE; ?>"; 
		color: <?php echo HTML_AREA_WYSIWYG_FONT_COLOUR; ?>; 
		font-size: <?php echo HTML_AREA_WYSIWYG_FONT_SIZE; ?>pt;';
		config.debug = <?php echo HTML_AREA_WYSIWYG_DEBUG; ?>;
		editor_generate('message',config);
	</script>
<?php } ?>

<?php echo tep_draw_form('mail', FILENAME_MAIL, 'action=preview'); ?>
<table border="0" cellpadding="0" cellspacing="2" width="100%">
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_CUSTOMER; ?></td>
                <td><?php echo tep_draw_pull_down_menu('customers_email_address', $customers, (isset($_GET['customer']) ? $_GET['customer'] : ''));?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_FROM; ?></td>
                <td><?php echo tep_draw_input_field('from', EMAIL_FROM); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_SUBJECT; ?></td>
                <td><?php echo tep_draw_input_field('subject'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td valign="top" class="main"><?php echo TEXT_MESSAGE; ?></td>
                <td><?php echo tep_draw_textarea_field('message', 'soft', '60', '15'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td colspan="4" align="right">
<?php 
	if (HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Enable'){ 
		echo tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL, 'onClick="validate();return returnVal;"');
	} else {
		echo tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL); 
	}
?>
		</td>
		</tr>
            </table>
</form></td>
          </tr>
<?php
  }
?>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
