<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

  require('includes/application_top.php');
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_NEWSLETTERS);
  $location = ' &raquo; <a href="' . tep_href_link(FILENAME_NEWSLETTERS, '', 'NONSSL') . '" class="headerNavigation">' . NAVBAR_TITLE . '</a>';
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET;?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script type="text/javascript">

function verify(form) {
	var passed = false;
	var blnRetval, intAtSign, intDot, intComma, intSpace, intLastDot, intDomain, intStrLen;
    
	if (form.Email){
    	intAtSign = form.Email.value.indexOf("@");
        intDot = form.Email.value.indexOf(".",intAtSign);
		intComma = form.Email.value.indexOf(",");
		intSpace = form.Email.value.indexOf(" ");
		intLastDot = form.Email.value.lastIndexOf(".");
		intDomain = intDot-intAtSign;
		intStrLen = form.Email.value.length;

		// # CHECK FOR BLANK EMAIL VALUE
		if (form.Email.value == "") {
			alert("You have not entered an email address.");
    	    form.Email.focus();
        	passed = false;

		// # CHECK FOR THE  @ SIGN?
		} else if (intAtSign == -1) {
			
			alert("Your email address is missing the \"@\".");
    	   	form.Email.focus();
        	passed = false;

	    // # Check for commas ****
		} else if (intComma != -1) {
			alert("Email address cannot contain a comma.");
			form.Email.focus();
			passed = false;
	
		//  # Check for a space ****
		} else if (intSpace != -1) {
			alert("Email address cannot contain spaces.");
			form.Email.focus();
			passed = false;
	
		// # Check for char between the @ and dot, chars between dots, and at least 1 char after the last dot ****
		} else if ((intDot <= 2) || (intDomain <= 1)  || (intStrLen-(intLastDot+1) < 2)) {
			alert("Please enter a valid Email address.\n" + form.Email.value + " is invalid.");
			form.Email.focus();
			passed = false;
		} else {
			passed = true;
		}

	} else {
		passed = true;
	}

	return passed;
}
</script>
</head>
<body">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

<?php	

	// # display the newsletter body when linked from email	
	if(isset($_GET['view_online']) && ($_GET['view_online'] == '1') && isset($_GET['nID'])) { 
		
		// # get the protocol and domain.
		$theHTTP = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . rtrim($_SERVER['HTTP_HOST'],'/').'/';

		// # sanitize email address passed
		$customers_email_address = (!empty($_GET['email'])) ? filter_var($_GET['email'],FILTER_SANITIZE_EMAIL) : '';
	
		// # Retreive and sanitize Newsletter ID
		$nID = (int)$_GET['nID'];


		// # select the newsletter by nID
		$newsletters_query = tep_db_query("SELECT n.*, 
												 si.module_subscribers,
												 sd.unsubscribea AS default_foot, 
												 si.unsubscribea AS custom_foot												
										  FROM " . TABLE_NEWSLETTERS . " n
										  LEFT JOIN " . TABLE_SUBSCRIBERS_INFOS . " si ON si.newsletters_id = n.newsletters_id
										  LEFT JOIN " . TABLE_SUBSCRIBERS_DEFAULT . " sd ON sd.module_subscribers = n.module
										  WHERE n.newsletters_id = '".$nID."'
										");
	
		if(mysql_num_rows($newsletters_query) > 0) {
	
	
			$newsletters = tep_db_fetch_array($newsletters_query);

			$customers_query = tep_db_query("SELECT * 
											 FROM (SELECT DISTINCT c.customers_email_address, 
														  c.customers_firstname, 
														  c.customers_lastname
												   FROM customers c
												   WHERE c.customers_email_address = '".$customers_email_address."'	

											 UNION ALL

											 SELECT DISTINCT s.subscribers_email_address,
													s.subscribers_firstname,
													s.subscribers_lastname
											 FROM subscribers s
											 WHERE s.subscribers_email_address = '".$customers_email_address."') AS table1
											");
	
			$customers = tep_db_fetch_array($customers_query);
				

			$vars = array('[customer_firstname]' => $customers['customers_firstname'],
						  '[customer_lastname]' => $customers['customers_lastname'],
						  '[customer_email]' => $customers_email_address,
						  '[unsubscribe_link]' => $theHTTP.'unsubscribe.php?email=' . $customers_email_address,
						  '[view_online]' => $theHTTP.'newsletters.php?view_online=1&nID=' . $nID.'&email='.$customers_email_address,
						);

			// # Parse the content, and add both HTML and plain text versions to the e-mail.
			$mailHTML = str_replace(array_keys($vars), array_values($vars), $newsletters['content'].$newsletters['custom_foot']);
			$mailHTML = preg_replace('~>\s+<~', '><', $mailHTML);	


		echo $mailHTML;
		} else {
			tep_redirect(tep_href_link(FILENAME_NEWSLETTERS, '', 'SSL'));
		}
	
	// # else not view online mode	
	} else {
?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
	</tr>
	<tr>
		<td>
			<p class="main"><?php echo TEXT_ORIGIN_EXPLAIN_TOP;?></p>

<form name="newsletter" action="<?php echo tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBE, '', 'NONSSL'); ?>" method="POST" onsubmit="return verify(this);">

	<input type="hidden" name="submitted" value="true">
	<input type="hidden" name="email_type" value="HTML">

	<table cellspacing="2" cellpadding="2" border="0" width="100%" class="topBarTitle">
		<tr>
			<td><p class="main"><?php echo TEXT_EMAIL; ?>&nbsp;&nbsp;&nbsp;</p></td>
			 <td>&nbsp;</td><td><input type="text" name="Email" value="" size="25" maxlength="50"></td>
		</tr>
		<!--tr>
			<td colspan="3">
<?php // echo TEXT_EMAIL_FORMAT; ?>
<input type="hidden" name="email_type" value="HTML">
<?php // echo TEXT_EMAIL_HTML; ?> 
<!--input type="radio" name="email_type" value="TEXT"-->
<?php // echo TEXT_EMAIL_TXT; ?>
</td>
		</tr-->
		<!--tr>
			<td><p class="main"><?php // echo TEXT_GENDER; ?>&nbsp;&nbsp;&nbsp;</p></td>
			<td>&nbsp;</td><td><p class="main">
				<input type="radio" name="gender" value="m"  checked><?php // echo TEXT_GENDER_MR; ?></input> 
				<input type="radio" name="gender" value="f"><?php // echo TEXT_GENDER_MRS; ?></input></p></td>
		 </tr-->
		 <tr>
			<td><p class="main"><?php echo TEXT_FIRST_NAME; ?>&nbsp;&nbsp;&nbsp;</p></td>
			<td>&nbsp;</td><td><input type="text" name="firstname" value="" size="25" maxlength="50"></td>
		 </tr>
		 <tr>
			<td><p class="main"><?php echo TEXT_LAST_NAME; ?>&nbsp;&nbsp;&nbsp;</p></td>
			 <td>&nbsp;</td><td><input type="text" name="lastname" value="" size="25" maxlength="50"></td>
		 </tr>
		<!--tr>
			<td colspan="3"><p class="main"><?php // echo TEXT_ZIP_INFO; ?>&nbsp;</p></td>
		</tr-->
		 <tr>
			<td><p class="main"><?php echo TEXT_ZIP_CODE; ?>&nbsp;&nbsp;&nbsp;</p></td>
			<td>&nbsp;</td><td><input type="text" name="zip" value="" size="6" maxlength="6"></td>
		</tr>
		<tr>
			<td class="main"><?php echo ENTRY_COUNTRY; ?></td>
			<td>&nbsp;</td> <td class="main"><?php  echo tep_get_country_list('country',  $subscribers_country_id ) . '&nbsp;' ;?></td>
		</tr>
		<tr>
			<td style="padding:15px 0 0 0"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT, '', 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
			<td colspan="2" align="right" style="padding:15px 0 0 0"><?php echo ''. tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE) .'';?></td>
		</tr>
	</table>
	</form>

			<?php // echo TEXT_ORIGIN_EXPLAIN_BOTTOM; ?>
</td>
</tr>
</table>
<?php
	// # END else for showing email online templates
	}
?>

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>