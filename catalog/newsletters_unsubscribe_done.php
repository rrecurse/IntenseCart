<?php
////////////////////////////////////////////////////////////////////////////
// $Id: Newsletter Unsubscribe, v 1.0 (/catalog/unsubscribe_done.php) 2003/01/24
// Programed By: Christopher Bradley (www.wizardsandwars.com)
//
// Developed for 
// 
// 
//
// 
//
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////

require('includes/application_top.php');
require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_NEWSLETTERS_UNSUBSCRIBE);
$email_to_unsubscribe=$HTTP_GET_VARS['email'];
$breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_NEWSLETTERS_UNSUBSCRIBE, '', 'NONSSL'));
  
///////////////////////////////////////////////////////////////////////////////////////////

/// Check and see if the email exists in the database, and is subscribed to the newsletter.
$cus_subscribe_raw = "SELECT 1 FROM subscribers WHERE customers_newsletter = '1' AND subscribers_email_address = '" . $email_to_unsubscribe . "'";
$cus_subscribe_query = tep_db_query($cus_subscribe_raw);
$cus_subscribe = tep_db_fetch_array($cus_subscribe_query);
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
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
          <?php
            // If we found the customers email address, and they currently subscribe
            if ($cus_subscribe) {
              // Unsubscribe them
              tep_db_query("UPDATE customers SET customers_newsletter = '0' WHERE customers_email_address = '" .$email_to_unsubscribe . "'");
              tep_db_query("UPDATE subscribers SET customers_newsletter = '0' WHERE subscribers_email_address = '" .$email_to_unsubscribe . "'");
//              tep_db_query("DELETE from subscribers WHERE subscribers_email_address = '" .$email_to_unsubscribe . "'");

           ?>
             <td class="main"><?php echo UNSUBSCRIBE_DONE_TEXT_INFORMATION . $email_to_unsubscribe; ?></td>
           <?php 
             // Otherwise, we want to display an error message (This should never occur, unless they try to unsubscribe twice)
             } else {
           ?>
               <td class="main"><?php echo UNSUBSCRIBE_ERROR_INFORMATION . $email_to_unsubscribe; ?></td>
           <?php
             }
           ?>
               
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center" class="main"><br><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT, '', 'NONSSL') . '">' . tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
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