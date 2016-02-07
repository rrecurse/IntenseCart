<?php
 /*
 $id author Puddled Internet - http://www.puddled.co.uk
  email support@puddled.co.uk
   
  

  Copyright (c) 2002 IntenseCart eCommerce

  


*/
?>
<?php require('includes/application_top.php');?>




<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="Javascript1.2"><!-- // load htmlarea
_editor_url = "<?php echo HTTP_SERVER . DIR_WS_ADMIN . 'htmlarea/' ?>";                     // URL to htmlarea files
var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);
if (navigator.userAgent.indexOf('Mac')        >= 0) { win_ie_ver = 0; }
if (navigator.userAgent.indexOf('Windows CE') >= 0) { win_ie_ver = 0; }
if (navigator.userAgent.indexOf('Opera')      >= 0) { win_ie_ver = 0; }
if (win_ie_ver >= 5.5) {
 document.write('<scr' + 'ipt src="' +_editor_url+ 'editor.js"');
 document.write(' language="Javascript1.2"></scr' + 'ipt>');
} else { document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); }
// --></script>
</head>
<body style="margin:0; background:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
     <td class="pageHeading" valign="top" colspan="2"><?php
       echo "Returns Text";
 ?> <?php


  if ($REQUEST_METHOD=="POST")
  {
  
    mysql_query('REPLACE INTO return_text VALUES (1, "' . $languages_id . '", "'  . $aboutus .'")')
          or die(mysql_error());
  }

  $sql=mysql_query("SELECT * FROM return_text where return_text_id = '1' and language_id = '" . $languages_id . "'")
    or die(mysql_error());
  $row=mysql_fetch_array($sql);

?>





<br>
<div class="Title"></div>
<br>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0">
<form name="aboutusform" method="Post" action="">
<tr>
  <td width="400px" valign="top"><b>Returns Text</b><br>
  <textarea name="aboutus" cols="75" rows="15"><?php echo $row['return_text_one'] ?></textarea></td>
    <br>
  <script language="JavaScript1.2" defer>
editor_generate('aboutus');
</script>
</tr>
<tr>
  <td colspan="2">&nbsp;</td>
</tr>
<tr>
  <td align="right"><input type="submit" name="Save" value="Save" style="width: 70px"</td>
  <td>&nbsp;</td>
</tr>
</form>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
