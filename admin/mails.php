<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('includes/application_top.php');

	if(($_GET['action'] == 'send_email_to_user') && ($_POST['subscribers_email_address']) && (!$_POST['back_x'])) {
		switch ($_POST['subscribers_email_address']) {
			case '***':
    	    $mail_query = tep_db_query("SELECT subscribers_firstname, subscribers_lastname, subscribers_email_address 
										FROM " . TABLE_SUBSCRIBERS . " 
										WHERE customers_newsletter = '1'
										");

        	$mail_sent_to = TEXT_NEWSLETTER_SUBSCRIBERS;

		break;
    
		default:

        	$subscribers_email_address = tep_db_prepare_input($_POST['subscribers_email_address']);

	        $mail_query = tep_db_query("SELECT subscribers_firstname, subscribers_lastname, subscribers_email_address 
										FROM " . TABLE_SUBSCRIBERS . " 
										WHERE subscribers_email_address = '" . tep_db_input($subscribers_email_address) . "'
									   ");
    	    $mail_sent_to = $_POST['subscribers_email_address'];
        break;
    }

    $from = tep_db_prepare_input($_POST['from']);
    $subject = tep_db_prepare_input($_POST['subject']);
    $message = tep_db_prepare_input($_POST['message']);

    // # Let's build a message object using the email class
    $mimemessage = new email(array('X-Mailer: IntenseCart eCommerce'));

    // # add the message to the object

	$mimemessage->add_text($message);
	$mimemessage->build_message();

		while ($mail = tep_db_fetch_array($mail_query)) {

			$mimemessage->send($mail['subscribers_firstname'] . ' ' . $mail['subscribers_lastname'], $mail['subscribers_email_address'], '', $from, $subject);

		}

		tep_redirect(tep_href_link(FILENAME_MAILS, 'mail_sent_to=' . urlencode($mail_sent_to)));
  }

  if ( ($_GET['action'] == 'preview') && (!$_POST['subscribers_email_address']) ) {
    $messageStack->add(ERROR_NO_SUBSCRIBER_SELECTED, 'error');
  }

  if ($_GET['mail_sent_to']) {
    $messageStack->add(sprintf(NOTICE_EMAIL_SENT_TO, $_GET['mail_sent_to']), 'notice');
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

       <script language="Javascript1.2"><!-- // load htmlarea
// MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 - 2.2 MS2 HTML Email HTML - <head>
      _editor_url = "<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_ADMIN; ?>htmlarea/";  // URL to htmlarea files
        var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);
         if (navigator.userAgent.indexOf('Mac')        >= 0) { win_ie_ver = 0; }
          if (navigator.userAgent.indexOf('Windows CE') >= 0) { win_ie_ver = 0; }
           if (navigator.userAgent.indexOf('Opera')      >= 0) { win_ie_ver = 0; }
       <?php if (HTML_AREA_WYSIWYG_BASIC_EMAIL == 'Basic'){ ?>  if (win_ie_ver >= 5.5) {
       document.write('<scr' + 'ipt src="' +_editor_url+ 'editor_basic.js"');
       document.write(' language="Javascript1.2"></scr' + 'ipt>');
          } else { document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); }
       <?php } else{ ?> if (win_ie_ver >= 5.5) {
       document.write('<scr' + 'ipt src="' +_editor_url+ 'editor_advanced.js"');
       document.write(' language="Javascript1.2"></scr' + 'ipt>');
          } else { document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); }
       <?php }?>
// --></script>
       <script language="JavaScript" src="htmlarea/validation.js"></script>
       <script language="JavaScript">
<!-- Begin
       function init() {
define('customers_email_address', 'string', 'Customer or Newsletter Group');
}
//  End -->
</script>
<style type="text/css">
.main select {width:400px; overflow-x:hidden}
</style>
</head>
<body OnLoad="init()" style="margin:0;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td colspan=2 width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if ( ($HTTP_GET_VARS['action'] == 'preview') && ($_POST['subscribers_email_address']) ) {
    switch ($_POST['subscribers_email_address']) {
      case '***':
        $mail_sent_to = TEXT_ALL_SUBSCRIBERS;
        break;
        $mail_sent_to = $_POST['subscribers_email_address'];
        break;
    }
?>
          <tr><?php echo tep_draw_form('mail', FILENAME_MAILS, 'action=send_email_to_user'); ?>
            <td><table border="0" width="100%" cellpadding="0" cellspacing="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="smallText"><b><?php echo TEXT_SUBSCRIBER; ?></b><br><?php echo $mail_sent_to; ?></td>
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
                <td class="smallText"><b><?php echo TEXT_MESSAGE; ?></b><br> <?php echo nl2br(htmlspecialchars(stripslashes($_POST['message']))); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td>
<?php
/* Re-Post all POST'ed variables */
    reset($HTTP_POST_VARS);
    while (list($key, $value) = each($HTTP_POST_VARS)) {
      if (!is_array($_POST[$key])) {
        echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
      }
    }
?>
                <table border="0" width="100%" cellpadding="0" cellspacing="2">
                  <tr>
                    <td><?php echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="back"'); ?></td>
                    <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_MAILS) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a> ' . tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL); ?></td>
                    </tr>
                    <td class="smallText">
                <?php if (HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Disable'){echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="back"');
                } ?><?php if (HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Disable') {echo(TEXT_EMAIL_BUTTON_HTML);
                 } else { echo(TEXT_EMAIL_BUTTON_TEXT); } ?>
                    </td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </form></tr>
<?php
  } else {
?>
          <tr><?php echo tep_draw_form('mail', FILENAME_MAILS, 'action=preview'); ?>
            <td><table border="0" cellpadding="0" cellspacing="2">
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
<?php
    $customers = array();
    $customers[] = array('id' => '', 'text' => TEXT_SELECT_SUBSCRIBER);
    $customers[] = array('id' => '***', 'text' => TEXT_ALL_SUBSCRIBERS);

    $mail_query = tep_db_query("SELECT s.subscribers_email_address, 
									   s.subscribers_firstname, 
									   s.subscribers_lastname
								FROM " . TABLE_SUBSCRIBERS . " s
								LEFT JOIN ". TABLE_CUSTOMERS ." c ON c.customers_id = s.customers_id
								WHERE (c.customers_newsletter = 1 OR s.customers_newsletter = 1)
								ORDER BY s.subscribers_lastname, s.subscribers_firstname
							   ");


	while($customers_values = tep_db_fetch_array($mail_query)) {
		$customers[] = array('id' => $customers_values['subscribers_email_address'],
        					 'text' => $customers_values['subscribers_lastname'] . ', ' . $customers_values['subscribers_firstname'] . ' (' . $customers_values['subscribers_email_address'] . ')');
	}
?>
              <tr>
                <td class="main"><?php echo TEXT_SUBSCRIBER; ?></td>
                <td class="main"><?php echo tep_draw_pull_down_menu('subscribers_email_address', $customers, $HTTP_GET_VARS['customer']);?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_FROM; ?></td>
                <td><?php echo tep_draw_input_field('from', EMAIL_FROM, 'size=45'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo TEXT_SUBJECT; ?></td>
                <td><?php echo tep_draw_input_field('subject','', 'size=45'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                
                <td colspan="2" class="main"><?php echo TEXT_MESSAGE; ?>
<br><?php echo tep_draw_textarea_field('message', 'soft', '60', '15'); ?></td>
<?php //###################### WYSIWYG  ###################### ?>
<?php if (HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Enable') { ?>
          <script language="JavaScript1.2" defer>
// MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 - 2.2 MS2 HTML Email HTML - <body>
           var config = new Object();  // create new config object
           config.width = "<?php echo EMAIL_AREA_WYSIWYG_WIDTH; ?>px";
           config.height = "<?php echo EMAIL_AREA_WYSIWYG_HEIGHT; ?>px";
           config.bodyStyle = 'background-color: <?php echo HTML_AREA_WYSIWYG_BG_COLOUR; ?>; font-family: "<?php echo HTML_AREA_WYSIWYG_FONT_TYPE; ?>"; color: <?php echo HTML_AREA_WYSIWYG_FONT_COLOUR; ?>; font-size: <?php echo HTML_AREA_WYSIWYG_FONT_SIZE; ?>pt;';
           config.debug = <?php echo HTML_AREA_WYSIWYG_DEBUG; ?>;
           editor_generate('message',config);
<?php }
//###################### END - WYSIWYG Vendor Locator ######################
?>
</script>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
              
              
                <td colspan="2" align="center">
                 <?php if (HTML_AREA_WYSIWYG_DISABLE_EMAIL == 'Enable'){ echo tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL, 'onClick="validate();return returnVal;"');
                   } else {
                echo tep_image_submit('button_send_mail.gif', IMAGE_SEND_EMAIL); }?>
                </td>
                
                
              </tr>
            </table></td>
          </form></tr>
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