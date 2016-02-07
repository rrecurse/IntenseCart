<?php

	if(!$_GET['admin_logout']) { 
		//error_log('login attempt from admin login - ' . $_SERVER['REMOTE_ADDR']);
	}

  if (defined('SCRIPT_OUTPUT_FORMAT')) {
    if (SCRIPT_OUTPUT_FORMAT=='xml') {
?><login_required>
<error>Login Required</error>
</login_required><?
    }
  }
?>
<!DOCTYPE html>
<html>
<head>

<title><?php echo TITLE ?></title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<style type="text/css">
body {
	margin:0;
}
body, div, p, th, td, li, dd {
	font:normal 11px arial;
	color: #053389;
}
.button {
	border: 1px solid #666699; color: #333333; background-color: #FFFFFF; height: 20px; line-height:20px; font: bold 12px arial;
}
</style>
<?php if($mobile === true) { 

echo '<meta name="viewport" content="width=290, initial-scale=1, maximum-scale=1">';

}
?>

</head>

<body>
<?php if($mobile === true) { 

//echo 'mobile';

}
?>
<div style="width:100%; text-align:center;">

<form style="margin:0;" method="post" action="<?php echo HTTP_SERVER.(isset($_GET['admin_logout'])?DIR_WS_ADMIN:$_SERVER['REQUEST_URI'])?>">
<table border="0" cellpadding="0" cellspacing="0" align="center">
  <tr>
    <td valign="top" style="padding-top:90px;"><div style="position:relative;"><div style="position:absolute; top:-35px; left:-35px;">
<!--script type="text/javascript">
document.write('<img src="/admin/images/hats/image' + months[themonth] + '.png" alt="">');
</script-->
</div></div>
<table width="400" border="0" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC" align="center">
      <tr>
        <td width="398" height="78" align="right" style="padding-right:7px; background-image:url(images/login_logo-bg.jpg)"><?php echo tep_image(DIR_WS_IMAGES . 'logo.gif',  TITLE, '', ''); ?></td>
      </tr>
      <tr>
        <td bgcolor="#FFFFFF"><table width="100%" border="0" cellspacing="0" cellpadding="4" align="center">
          <tr>
            <td height="50" colspan="2" align="center" style="font:bold 12px arial; padding-right:10px;"><?php echo '' . TITLE . '' ;?></td>
            </tr>
          <tr>
            <td width="35%" align="right" style="font:bold 12px arial; padding-right:10px;">Username:</td>
            <td width="65%"><input type="text" name="_admin_login" value="<?php echo (isset($_POST['_admin_login']) ? $_POST['_admin_login'] : (isset($_COOKIE['admin_user']) ? $_COOKIE['admin_user']:''))?>"></td>
          </tr>
          <tr>
            <td align="right" style="font:bold 12px arial; padding-right:10px;">Password:</td>
            <td><input type="password" name="_admin_password"></td>
          </tr>
          <tr>
            <td colspan="2" align="center"  style="font:bold 11px arial; padding-top:10px;"><input type="checkbox" name="_admin_keep_name" value="1"<?php echo (isset($_COOKIE['admin_user']) ? ' checked' : '');?>> Remember Username on this computer</td>
            </tr>
          <tr>
            <td colspan="2" align="center" style="font:bold 11px arial;"><input type="checkbox" name="_admin_keep_session" value="1">
              Login Automatically on this computer</td>
            </tr>
          
          <tr>
            <td height="30" colspan="2" align="center" style="padding:20px;"><input type="submit" name="_admin_process_login" value="  Login  " class="button"></td>
            </tr>
        </table></td>
      </tr>
    </table>
     </td>
  </tr>

</table>
</form><br><br><br>
<table align="center" style="font:normal 11px tahoma; width:900px;">
<tr><td><?php include 'footer.php' ;?></td>
</tr></table></div>
<script type="text/javascript">
  <!--
    // Break out of frames
    if (top.frames.length > 0)
    top.location=self.document.location;
  //-->
</script>

</body>
</html>
