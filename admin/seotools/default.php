<?php

  require('../../admin/includes/application_top.php');
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/popcalendar.js"></script>
</head>
<body style="background-color:transparent; margin:0;">
<?
if (isset($_COOKIE['iframe_src_myframe'])) $FramePage=$_COOKIE['iframe_src_myframe'];
  if (isset($FramePage)) {
    $FramePageFile=preg_replace('|.*/|','',str_replace(' ','+',$FramePage));
  }
?>

<script type="text/javascript">
 
  function contentChanged() {
    return top.resizeIframe('myframe');
  }
  
  function rmenuInFrame(event) {
    return top.showMenu(event,'myframe');
  }

  var isSetBreadcrumb;
  function setBreadcrumb(cont) {
    if (isSetBreadcrumb) return true;
    isSetBreadcrumb=1;
    return top.setBreadcrumb(cont);
  }
  
  if (!window.noDefaultBreadcrumb) {
    var titleElement=document.getElementsByTagName('head')[0].getElementsByTagName('title')[0];
    var breadcrumbs=Array({},{});
    if (titleElement) breadcrumbs.push({title:titleElement.innerHTML,link:document.location});
    setBreadcrumb(breadcrumbs);
  }
  
</script>



<? if (array_key_exists( 'tool1', $_GET ) ) {
echo '
<form method="post" name="pageform" action="http://www.iwebtool.com/tool/tools/multirank/multirank.php"  target="pageframe" onsubmit="return validate(this);">
<table border="0" style="border-collapse: collapse" width="100%">
<tr>
<td height="91" valign="top">
<table class="tooltop" style="border-collapse: collapse" width="100%" height="76">
<tr>
<td>
<table border="0" style="border-collapse: collapse" width="100%" cellspacing="5" cellpadding="5">
<tr>
<td valign="top" colspan="4"><b><font size="2">Your domain(s): </font></b><font size="1">Enter each address on a new line (Maximum 10)</font></td>
</tr>
<tr>
<td valign="top" colspan="3">
<textarea rows="11" name="domain" style="width: 100%">' .$_SERVER["SERVER_NAME"] . '</textarea></td>
<td >
&nbsp;</td>
</tr>
<tr>
<td >
<input type="submit" value="Check!" style="float: left"></td>
<td ></td>
<td colspan="2">&nbsp;</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td >
<iframe name="pageframe" width="100%" height="251" class="toolbot" frameborder="0">
</iframe></td>
</tr>
<tr>
<td height="39">
&nbsp;</td>
</tr>
</table>
</form>
<script language="JavaScript">
function validate(theform) {
if (theform.domain.value == "") { alert("No domain provided"); return false; }
return true;
}
</script>';
}
elseif (isset($_GET['tool2'])) {
echo 'some other tool 2';
}
elseif (isset($_GET['tool3'])) {
echo 'some other 3';
}
elseif (empty($_GET[''])) {
echo 'nothing';
}
?>


</body>
</html>
<?php require('../../admin/includes/application_bottom.php'); ?>