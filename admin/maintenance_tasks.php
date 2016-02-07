<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body style="background-color:transparent; margin:0">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>

    <td width="100%" valign="top" colspan="2">
<table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo 'Maintenance Tasks'; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center" valign="midddle" style="height:300px"><a href="/googlesitemap/index.php" style="font:14px arial;font-weight:bold">Update Google Sitemap</a> (Do everyday)<br><a href="/admin/froogle.php" style="font:14px arial;font-weight:bold">Update Froogle</a>(Once a week)</td>
      </tr>
    </table></td>
  </tr>
</table>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
