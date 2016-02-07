<?php $domain =  preg_replace('/^www\./','',$_SERVER['HTTP_HOST']);?>
<html>
<head>
<meta http-equiv="Content-Language" content="en-gb">
<meta http-equiv="Content-Type" content="text/html; charset="utf-8">
<title>Backlink Checker</title> 
<LINK href="../js/iwebtools.css" rel="stylesheet" type="text/css">
<LINK href="/admin/js/css.css" rel="stylesheet" type="text/css">
<script language="javascript" src="../js/gjs.js"></script> 
<script type="text/javascript" src="../js/iwebtools.js"></script>

<script type="text/javascript">
function contentChanged() {
  top.resizeIframe('myframe');
}

</script>

</head> 

<body onLoad="contentChanged(); javascript:document.pageform.submit();" style="margin:0; background:transparent;">
<div align="center"> <table border="0" cellpadding="2" width="100%" cellspacing="1">

  <tr> <td> <table border="0" cellpadding="2" width="100%" cellspacing="2" height="184"> <tr> <td width="37"> <img border="0" src="../images/backlink_checker.gif"></td> <td valign="top" width="501"> <font size="5">Backlink Checker<br> </font><font color="#515151"><span style="font-size: 8pt">Find those backlinks linking to you, their Description, Language and Size.</span></font></td> </tr> <tr> <td valign="top" colspan="2"> <table border="0" cellpadding="0" style="border-collapse: collapse" width="100%"> <tr> <td height="20" width="26" align="center"> <img src="../images/red_arrow.gif" border="0" onClick="contentChanged();"></td> <td bgcolor="#F8F8F8" width="95%"> <font style="font-size: 8pt"><div id="bc_text" onclick="contentChanged();"><a href="#" onclick="hs('bc');"><u> How do I use this tool? [+]</u></a></div></font></td> </tr> <tr> <td></td> <td> <div style="display: none;" id="bc"> <table border="1" cellpadding="3" width="100%" style="border-collapse: collapse" bordercolor="#D4D4D4" height="30" cellspacing="3" bgcolor="#EEEEEE"> <tr> <td valign="middle"> <table border="0" cellspacing="1" style="border-collapse: collapse" width="100%"> <tr> <td> <font style="font-size: 8pt"><b>How to use this tool</b><br>1. Enter the exact website address of the page you want to check the backlinks for into the text box. (eg. <?php echo $_SERVER['HTTP_HOST'];?> or <?=$domain ?>)
<br>
<br>2. Click the "Check!" button
<br>
<br>The results will be shown in a table, 20 results per page will be displayed. 
<br>
<br>Click the <img src="http://tool.iwebtool.com/tools/backlink_checker/img/o.gif"> icon to view more details about the website.<br><br></font></td> </tr> <tr> <td height="30"> <a href="#" onclick="hs('bc');return false; contentChanged();"> <font style="font-size: 8pt; font-weight: 700"><u>Hide this box</u></font></a></td> </tr> </table></td> </tr> </table> </div></td> </tr> </table> </td> </tr> <tr> <td valign="top" align="center" colspan="2"> 

<!-- Backlink Checker -->
<form method="get" name="pageform" action="http://www.iwebtool.com/tool/tools/backlink_checker/backlink_checker.php"  target="pageframe" onsubmit="return validate(this);">
<table border="0" style="border-collapse: collapse" width="100%">
<tr>
<td height="91" valign="top">
<table style="border-collapse: collapse" width="100%" height="76" class="tooltop">
<tr>
<td>
<table border="0" width="100%" cellspacing="5">
<tr>
<td height="28"><b><font size="2">Your domain:
</font></b></td>
<td height="28">
<span style="font:bold 14px arial"><?=$_SERVER["SERVER_NAME"] ?></span>
<input name="domain" type="hidden" value="<?=$_SERVER["SERVER_NAME"] ?>"></td>
<td height="28">
<input type="submit" value="Check!" style="float: left"></td>
</tr>

</table>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<iframe name="pageframe" width="100%" height="530" class="toolbot" frameborder="0">
</iframe></td>
</tr>
</table>
</form>
<script language="JavaScript">
function validate(theform) {
if (theform.domain.value == "") { alert("No domain provided"); return false; }
return true;
}
</script>
<!-- Backlink Checker --></td> 
</tr> <tr> <td valign="top" colspan="2"> <?php include 'seofooter.php';?> </td> 
  </tr> </table> 
</div>
</body>
</html> 