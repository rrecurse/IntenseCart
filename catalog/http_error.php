<?php
/*
  $Id: http_error.php,v 1.2 2003/02/01 00:00:00 hobbzilla Exp $
  * Modifications by chris@buchi-online.net:
  * A1.0: TEXT_INFORMATION not followed by URL and Referrer informations

  
  

  

  
*/
  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_HTTP_ERROR);

  switch ($HTTP_GET_VARS['error_id']) {
     case '400':  $error_text = ERROR_400_DESC; break;
     case '401':  $error_text = ERROR_401_DESC; break;
     case '403':  $error_text = ERROR_403_DESC; break;
     case '404':  
		   $error_text = ERROR_404_DESC;
			 $error_text.= '<br>REFERER:'.$HTTP_SERVER_VARS['HTTP_REFERER'];
			 $error_text.= '<br>URI:'.$_SERVER['REQUEST_URI'];
			 break;
     case '405':  $error_text = ERROR_405_DESC; break;
     case '408':  $error_text = ERROR_408_DESC; break;
     case '415':  $error_text = ERROR_415_DESC; break;
     case '416':  $error_text = ERROR_416_DESC; break;
     case '417':  $error_text = ERROR_417_DESC; break;
     case '500':  $error_text = ERROR_500_DESC; break;
     case '501':  $error_text = ERROR_501_DESC; break;
     case '502':  $error_text = ERROR_502_DESC; break;
     case '503':  $error_text = ERROR_503_DESC; break;
     case '504':  $error_text = ERROR_504_DESC; break;
     case '505':  $error_text = ERROR_505_DESC; break;
     default:     $error_text = UNKNOWN_ERROR_DESC; break;
  }

// Send the HTTP Error to Store Owner
  if (EMAIL_HTTP_ERROR == 'true') {
    tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, EMAIL_TEXT_SUBJECT, sprintf(EMAIL_BODY, HTTP_SERVER, $HTTP_GET_VARS['error_id'], $error_text, date("m/d/Y G:i:s"), HTTP_SERVER . $REQUEST_URI, $REMOTE_ADDR, $HTTP_USER_AGENT, $HTTP_REFERER), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, '');
  }

// Save the HTTP Error Report to disk
  if (STORE_HTTP_ERROR == 'true') {
    error_log(strftime(STORE_PARSE_DATE_TIME_FORMAT) . ',' . $HTTP_GET_VARS['error_id'] . ',' . HTTP_SERVER . $REQUEST_URI . ',' . $REMOTE_ADDR . ',' . $HTTP_USER_AGENT . ',' . $HTTP_REFERER . "\n", 3, STORE_HTTP_ERROR_LOG);
  }

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo sprintf(HEADING_TITLE, $HTTP_GET_VARS['error_id']); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_specials.gif', HEADING_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><br><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo sprintf(TEXT_INFORMATION, $error_text); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="right" class="main"><br><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT, '', 'NONSSL') . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>