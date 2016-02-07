<?php
		if (!tep_session_is_registered('login'))
			tep_session_register('login');
	
	if (isset($HTTP_POST_VARS['sbmSubmit'])){
		$suppliers_name = $HTTP_POST_VARS['txtSuppliername'];
		$password = $HTTP_POST_VARS['txtPassword'];
		$password = md5($password);
		$suppliers_query = tep_db_query("select * from " . TABLE_SUPPLIERS . " where suppliers_name = '" . $suppliers_name . "' and suppliers_password = '" . $password . "'");
		$suppliers_rows = tep_db_num_rows($suppliers_query);
		$suppliers_result = tep_db_fetch_array($suppliers_query);
		if ($suppliers_rows >= 1){
		$login = $suppliers_result['suppliers_id'];
		if (!tep_session_is_registered('login'))
			tep_session_register('login');
			$login = $suppliers_result['suppliers_id'];
		}
		else
			$err_message = "Error message: Username or Password is wrong. Please try again.!";
	}	
	
//	if (!tep_session_is_registered('login')){
	if (!isset($_SESSION['login'])){
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Suppliers' Area of IdealHandling</title>
<style type="text/css"><!--
.style1 {color: #FF0000}
a { color:#080381; text-decoration:none; }
a:hover { color:#aabbdd; text-decoration:underline; }
a.text:link, a.text:visited { color: #000000; text-decoration: none; }
a:text:hover { color: #000000; text-decoration: underline; }
a.main:link, a.main:visited { color: #7187BB; text-decoration: none; }
A.main:hover { color: #D3DBFF; text-decoration: underline; }
a.sub:link, a.sub:visited { color: #dddddd; text-decoration: none; }
A.sub:hover { color: #dddddd; text-decoration: underline; }
a:link.headerLink { font-family: Verdana, Arial, sans-serif; font-size: 10px; color: #000000; font-weight: bold; text-decoration: none; }
a:visited.headerLink { font-family: Verdana, Arial, sans-serif; font-size: 10px; color: #000000; font-weight: bold; text-decoration: none }
a:active.headerLink { font-family: Verdana, Arial, sans-serif; font-size: 10px; color: #000000; font-weight: bold; text-decoration: none; }
a:hover.headerLink { font-family: Verdana, Arial, sans-serif; font-size: 10px; color: #000000; font-weight: bold; text-decoration: underline; }

#.heading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 20px; font-weight: bold; line-height: 1.5; color: #000000; }
.heading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 22px; font-weight: bold; line-height: 1.5; color: #D3DBFF; }
.main { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 17px; font-weight: bold; line-height: 1.5; color: #000000; }
.sub { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; line-height: 1.5; color: #dddddd; }
.text { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 13px; font-weight: bold; line-height: 1.5; color: #000000; }
#.menuBoxHeading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; color: #ffffff; font-weight: bold; background-color: #093570; border-color: #093570; border-style: solid; border-width: 1px; }
.menuBoxHeading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; color: #ffffff; font-weight: bold; background-color: #7187bb; border-color: #7187bb; border-style: solid; border-width: 1px; }
.infoBox { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; color: #080381; background-color: #ffffff; border-color: #7187bb; border-style: solid; border-width: 1px; }
.smallText { font-family: Verdana, Arial, sans-serif; font-size: 10px; }

.menusub { font-family: Verdana, Arial, sans-serif; font-size: 10px; }

//--></style>
</head>
<body marginwidth="50" marginheight="100" topmargin="100" bottommargin="50" leftmargin="50" rightmargin="50" bgcolor="#FFFFFF">
<center>
<table border="0" width="400" style="border: 1px solid black " cellpadding="0" cellspacing="0">
	<tr>
		<td height="50" colspan="2"><?php echo tep_image(DIR_WS_IMAGES . 'logo.gif', 'IntenseCart', '204', '70'); ?></td>
	</tr>
	<tr style="vertical-align:top">
		<td>
		<form method="post" name="frmLogin" enctype="multipart/form-data" action="">
			<table border="0" width="100%" cellpadding="2" cellspacing="2" class="text">
				<tr height="25" class="menuBoxHeading"><td class="heading" colspan="2">Login</td></tr>
				<tr>
					<td>Supplier name:</td><td><input name="txtSuppliername" type="text" size="30" class="text"></td>
				</tr>
				<tr>
					<td>Password:</td><td><input name="txtPassword" type="password" size="30" class="text"></td>
				</tr>
				<tr>
					<td align="right"><input name="sbmSubmit" type="submit" value="Submit" class="text"></td>
					<td align="left"><input name="rstReset" type="reset" value="Reset" class="text"></td>
				</tr>
			</table>
		</form>
		</td>
		<tr><td><?php echo $err_message?></td></tr>
	</tr>
</table>
</center>
</body>
</html>
<?php
	exit();
	}
?>
