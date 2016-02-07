<?php
/*
  $Id: server_info.php,v 1.6 2003/06/30 13:13:49 dgw_ Exp $

  
  

  Copyright (c) 2003 IntenseCart eCommerce

  
*/

  require('includes/application_top.php');

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2" class="menuBoxHeading">
      <tr>
        <td>
        BizRate: <?php echo '<a href="' . tep_href_link(FILENAME_BIZRATE) . '"target=_blank>' . BOX_FEEDERS_BIZRATE . '</a>'; ?>
        </td>
      </tr>
      <tr>
        <td>
        Froogle: <?php echo '<a href="' . tep_href_link(FILENAME_FROOGLE) . '" target=_blank">' . BOX_FEEDERS_FROOGLE . '</a>'; ?>
        </td>
      </tr>
      <tr>
        <td>
        Yahoo: <?php echo '<a href="' . tep_href_link(FILENAME_YAHOO) . '"target=_blank">' . BOX_FEEDERS_YAHOO . '</a>'; ?>
        </td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
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
