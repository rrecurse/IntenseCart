<?php

  require('includes/application_top.php');

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body style="margin:0; background:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="middle" class="pageHeading">
    Product Sales vs Views
    </td>
    <td align="right">
    <form name="date_range">
    <table border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td>from &nbsp;</td>
    <td><input type="text" name="date_from" value="<?=date('m/d/Y',time()-86400*7)?>" onClick="parent.window.popUpCalendar(this,this,'mm/dd/yyyy',document);" size="12" maxlength="11"></td>
    <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="parent.window.popUpCalendar(document.date_range.date_from,document.date_range.date_from,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
    </tr>
    <tr>
    <td>to &nbsp;</td>
    <td><input type="text" name="date_to" value="<?=date('m/d/Y',time())?>" onClick="parent.window.popUpCalendar(this,this,'mm/dd/yyyy',document);" size="12" maxlength="11"></td>
    <td><img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="parent.window.popUpCalendar(document.date_range.date_to,document.date_range.date_to,'mm/dd/yyyy',document);" style="cursor:pointer"></td>
    </tr></table>
    </form>
    </td>
  </tr>
  <tr>
    <td valign="top" colspan="2">
    </td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
