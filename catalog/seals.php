<?php 

require('includes/application_top.php');

$date = date("F jS, Y", time());
$hostname = $_SERVER['SERVER_NAME']; 
$hostname2 = str_replace('www.', '', $hostname);

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta name="robots" content="noindex,nofollow">
<title>IntenseSSL Business and Security Verification</title>
<style type="text/css">

body {
	margin:0;
	background-color:#FFFFFF;
	background-attachment:fixed;
}

body,td,th {
	font: normal 12px Arial;

}
.style1 {
	font-size: 14px;
	font-weight: bold;
}
.style2 {font-size: 14px}
.style3 {color: #006600}
.style7 {color: #006600; font-weight: bold; }
.style8 {
	font-size: 10px;
	color: #666666;
}

.style9 {font-size: 12px}
.style10 {font-size: 10px; text-align:center}
.style11 {font-size: 10px; color: #4E4E4E; text-align:center}

.desc {padding:8px 12px 10px 5px; text-align:justify;}
</style>
<script type="text/JavaScript">
<!--
function MM_reloadPage(init) {  //reloads the window if Nav4 resized
  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
    document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}
  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) location.reload();
}
MM_reloadPage(true);
//-->
</script>
</head> 

<body onLoad="window.moveTo(250,0); window.resizeTo(528,screen.availHeight);">
<div style="position:absolute; left:2px; top:0; width:100px; height:100%; z-index:2"><table width="500" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="5"><div style="position:fixed"><img src="/seals-header.jpg" width="500" height="65" alt=""></div></td>
  </tr>
  <tr>
    <td colspan="5">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="5">The following information has been <span class="style3"><b>Verified</b></span> by <b>IntenseSSL</b>:</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td width="150">&nbsp;</td>
    <td width="13">&nbsp;</td>
    <td width="330">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>

    <td class="style2">&nbsp;</td>
    <td class="style2"><div align="right"><span class="style3"><b>Verified</b></span> <b>URL:</b> </div></td>
    <td class="style2">&nbsp;</td>
    <td><span class="style1"> <?=$hostname?></span></td>
    <td>&nbsp;</td>
  </tr>

  <tr>
    <td>&nbsp;</td>
    <td><div align="right"></div></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>

    <td nowrap><div align="right"><span class="style2"><span class="style3">Verified</span> Company:</span> </div></td>
    <td>&nbsp;</td>
    <td><span class="style2"><?=STORE_NAME?></span></td>
    <td>&nbsp;</td>
  </tr>
  <tr>

    <td>&nbsp;</td>
    <td><div align="right"><span class="style2"><span class="style2"><span class="style2"><span class="style2"></span></span></span></span></div></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" nowrap><div align="right" class="style2">      <span class="style3">Verified</span> Business Info:</div></td>
    <td>&nbsp;</td>
    <td valign="top"><span class="style2"><?=STORE_NAME_ADDRESS?>
      </span></td>

    <td>&nbsp;</td>
  </tr>

  <tr>
    <td colspan="5">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td><div align="right"></div></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>

    <td>&nbsp;</td>
    <td><div align="right" class="style2"><span class="style3">Verified</span> Email: </div></td>
    <td><div align="right"><span class="style2"><span class="style2"><span class="style2"><span class="style2"></span></span></span></span></div></td>
    <td><a href="mailto:<?=STORE_OWNER_EMAIL_ADDRESS?>" class="style2"><?=STORE_OWNER_EMAIL_ADDRESS?></a></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
  <td colspan="5">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><div align="right" class="style2">Account <span class="style3">Active</span>:</div></td>

    <td><div align="right"><span class="style2"><span class="style2"><span class="style2"><span class="style2"></span></span></span></span></div></td>
    <td colspan="2"><span class="style2"><?=$date?></span></td>
  </tr>
  <tr>
    <td colspan="5">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="5" align="center" style="padding:10px 0 10px 0"><span class="style1"><b><?=$hostname?></b></span> has
      been <span class="style7">Authorized</span> to use the following <b>Seals</b>:</td>
  </tr>
  <tr>
    <td colspan="5">
      <hr size="1">
</td>
  </tr>
<?php
if ($_GET['seal']==1)
{
?>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right"><img src="/intenseSSL.php?seal=1" alt="" width="100" height="60" border="0"></div></td>
    <td valign="top"></td>
    <td colspan="2" valign="top" class="desc"><b>Security Verified :</b> In order for a website to qualify for the IntenseSSL Security Verified Seal, IntenseSSL must verify that the website is using 128-Bit SSL Encryption on pages where  private information can be entered, such as credit cards, Social Security numbers, loan information, etc.

     <br>
<br>
<span class="style8">(Note: All SSL Certificates  are generated by third party companies. If you are ever in doubt about the security of a site, look for http<u>s</u>:// in the URL, and also look for a lock symbol at the bottom of your browser. You can click on the lock to view the certificate details.)</span> </td>
  </tr>
  <tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
<?php 
}
elseif ($_GET['seal']==2)
{
?>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right">      <img src="/intenseSSL.php?seal=2" alt="" width="100" height="60" border="0"></div></td>
    <td valign="top">&nbsp;</td>
    <td colspan="2" valign="top" class="desc"><b>Privacy Verified: </b>In order for a company to qualify for the IntenseSSL Privacy Verified Seal, it must include three core statements in its Privacy Policy in order to fully protect their customers:<br>
      <br>
      1) Customer information, whether public or private, will not be sold, exchanged, transferred, or given to any other company for any reason, without the consent of the customer, other than for the express purpose of delivering the purchased product or service requested by the customer.
      <br>
      <br>
      2) Private Customer information (credit cards, S.S. Numbers, financials, etc.) will not be kept on file for more than 60 days. (If a company requires  more than 60 days, they must receive special approval from IntenseSSL.)      <br>
      <br>
      3) Customer will not receive any continual email solicitation from the company, unless the customer consents to the solicitation at checkout, or through a double opt-in process. And, if the customer does consent to receive email from the company, the company agrees to have a simple unsubscribe option available, along with unsubscribe instructions in every email.</td>
  </tr>
  <tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
<?php 
}
elseif ($_GET['seal']==3)
{
?>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right">      <img src="/intenseSSL.php?seal=3" alt="" width="100" height="60" border="0"></div></td>
    <td>&nbsp;</td>

    <td colspan="2" valign="top" class="desc"><b>Business Verified:</b> In order for a company to be IntenseSSL Business Verified, they must undergo a thorough identification process, including:
      <br>
        <br>
        Address Verification - The companies address is confirmed via Priority Mail, using a special double signature verification process, unique to IntenseSSL.<br>
        <br>
        Email Verification - An email is sent and received to in order to confirm the companies support email address.<br>
        <br>
      Phone Verification - A phone call is placed to confirm the companies phone number. </td>
  </tr>

  <tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
<?php 
}
elseif ($_GET['seal']==4)
{
?>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right"><img src="/intenseSSL.php?seal=1" alt="" width="100" height="60" border="0"></div></td>
    <td valign="top"></td>
    <td colspan="2" valign="top" class="desc"><b>Security Verified :</b> In order for a website to qualify for the IntenseSSL Security Verified Seal, IntenseSSL must verify that the website is using 128-Bit SSL Encryption on pages where  private information can be entered, such as credit cards, Social Security numbers, loan information, etc.

     <br>
<br>
<span class="style8">(Note: All SSL Certificates  are generated by third party companies. If you are ever in doubt about the security of a site, look for http<u>s</u>:// in the URL, and also look for a lock symbol at the bottom of your browser. You can click on the lock to view the certificate details.)</span> </td>
  </tr>
  <tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right">      <img src="/intenseSSL.php?seal=2" alt="" width="100" height="60" border="0"></div></td>
    <td valign="top">&nbsp;</td>
    <td colspan="2" valign="top" class="desc"><b>Privacy Verified: </b>In order for a company to qualify for the IntenseSSL Privacy Verified Seal, it must include three core statements in its Privacy Policy in order to fully protect their customers:<br>
      <br>
      1) Customer information, whether public or private, will not be sold, exchanged, transferred, or given to any other company for any reason, without the consent of the customer, other than for the express purpose of delivering the purchased product or service requested by the customer.
      <br>
      <br>
      2) Private Customer information (credit cards, S.S. Numbers, financials, etc.) will not be kept on file for more than 60 days. (If a company requires  more than 60 days, they must receive special approval from IntenseSSL.)      <br>
      <br>
      3) Customer will not receive any continual email solicitation from the company, unless the customer consents to the solicitation at checkout, or through a double opt-in process. And, if the customer does consent to receive email from the company, the company agrees to have a simple unsubscribe option available, along with unsubscribe instructions in every email.</td>
  </tr>
  <tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right">      <img src="/intenseSSL.php?seal=3" alt="" width="100" height="60" border="0"></div></td>
    <td>&nbsp;</td>

    <td colspan="2" valign="top" class="desc"><b>Business Verified:</b> In order for a company to be IntenseSSL Business Verified, they must undergo a thorough identification process, including:
      <br>
        <br>
        Address Verification - The companies address is confirmed via Priority Mail, using a special double signature verification process, unique to IntenseSSL.<br>
        <br>
        Email Verification - An email is sent and received to in order to confirm the companies support email address.<br>
        <br>
      Phone Verification - A phone call is placed to confirm the companies phone number. </td>
  </tr>

  <tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top" style="padding:10px 0 0 0"><div align="right"><img src="/intenseSSL.php?seal=4" alt="" width="100" height="60" border="0"></div></td>
    <td>&nbsp;</td>
    <td colspan="2" valign="top" class="desc"><b>Certified: </b>In order to receive a IntenseSSL Certified
        Seal, a company must meet all of the requirements for the Security Verified,
        Privacy Verified, and Business Verified Seals, plus a managing member
        of the company must supply their name, phone number, email, and have
        their home address verified using IntenseSSL's double signature verification
        process.      <b><br>
        <br>
      We certify that the information we have received from the company above has been verified to the best of our ability, and that they have met all of the IntenseSSL Verification requirements.<br>
        <br>
        - </b><b>IntenseSSL </b></td>
  </tr> 
<tr>
    <td colspan="5" style="padding:5px"><hr size="1"></td>
  </tr>
<?php 
}
?>
 
  <tr>
  <td height="10" colspan="5"></td>
  </tr>
  <tr valign="middle" bgcolor="#EBEBEB">
    
    <td colspan="5" align="center" valign="top" style="padding:5px"><p class="style10">This IntenseSSL
        Verification page was generated with the IntenseSSL Certificate. <br>
&copy; <?=date("Y");?> <a href="http://www.intenseSSL.com" target="_blank">IntenseSSL</a> -
A division of intenseCommerce LLC. All Rights Reserved.</p>

      <p class="style11"><b>IntenseSSL Disclaimer:</b> IntenseSSL
        is a website verification company. We take pride in our verification
        process and strive to offer accurate, reliable information to consumers.
        If an IntenseSSL Verified company changes its information without informing
        IntenseSSL, we cannot be held responsible. </p></td>
  </tr>
</table>
</div>
<div style="position:absolute; z-index:1; left: 355px; top: 92px; border: 0px none #000000;"><img src="/seals-bg.jpg" width="144" height="144" alt=""></div>
<script type="text/JavaScript">
<!--
function clickIE() {if (document.all) {(message);return false;}}
function clickNS(e) {if 
(document.layers||(document.getElementById&&!document.all)) {
if (e.which==2||e.which==3) {(message);return false;}}}
if (document.layers) 
{document.captureEvents(Event.MOUSEDOWN);document.onmousedown=clickNS;}
else{document.onmouseup=clickNS;document.oncontextmenu=clickIE;}

document.oncontextmenu=new Function("return false")
// --> 
</script>
</body>
</html>

