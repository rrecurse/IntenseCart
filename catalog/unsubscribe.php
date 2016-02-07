<?php 
	require ('includes/application_top.php');
	require (DIR_WS_LANGUAGES.$language.'/'.FILENAME_UNSUBSCRIBE);
	$email_to_unsubscribe = filter_var ($_GET['email'], FILTER_VALIDATE_EMAIL);
	$breadcrumb->add (NAVBAR_TITLE,  tep_href_link (FILENAME_UNSUBSCRIBE, '', 'NONSSL'));
	$filename_unsubscribe_done =  FILENAME_UNSUBSCRIBE_DONE."?email=".$email_to_unsubscribe;
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN" >
<html <?php echo HTML_PARAMS;?>>
	<head>
		<meta http - equiv = "Content-Type" content="text/html;	charset=<?php echo CHARSET;?>">
<?php 
	if (file_exists (DIR_WS_INCLUDES.'header_tags.php')) {
		require (DIR_WS_INCLUDES.'header_tags.php');
	} else {
		echo '<title>'. TITLE .'</title>p
	}
?>
		<base href="<?php echo (getenv('HTTPS')	== 'on'	? HTTPS_SERVER : HTTP_SERVER) .	DIR_WS_CATALOG;	?>"> 
		<link rel = "stylesheet" type="text/css" href="stylesheet.css">
	</head>
	<body> 
<?php 
	require (DIR_WS_INCLUDES.'header.php');
?>
	<table border= "0" width= "100%" cellspacing= "3" cellpadding= "3"> 
		<tr> 
			<td valign="top">
				<table border="0" cellspacing= "0" cellpadding="2">
					 <?php require (DIR_WS_INCLUDES.'column_left.php');?>
				</table>
			</td> 
			<td width="100%" valign="top">
				<table border="0" width= "100%" cellspacing="0" cellpadding="0">
					<tr>
						 <td>
							<table border="0" width="100%" cellspacing="0" cellpadding= "0">
								<tr>
									<td class= "pageHeading">
										<?php echo HEADING_TITLE;?>
									</td> 
								</tr>
							</table>
						</td> 
					</tr> 
					<tr>
						  <td><?php echo tep_draw_separator ('pixel_trans.gif', '100%', '10');?></td> 
					</tr>
					 <tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								 <tr> 
									<td class="main">
										<?php echo UNSUBSCRIBE_TEXT_INFORMATION;?>
									</td> 
								</tr> 
							</table>
						</td>
					</tr> 
					<tr> 
						<td align="center" class="main">
							<br><?php echo '<a href="'.tep_href_link ($filename_unsubscribe_done, '', 'NONSSL').'">'.tep_image_button ('button_continue.gif', IMAGE_BUTTON_CONTINUE).'</a>';?>
						</td>
					</tr>
				</table>
			</td> 
			<td valign = "top">
				<table border="0" cellspacing="0" cellpadding="2"> 
					<?php require (DIR_WS_INCLUDES.'column_right.php');?>
				</table>
			</td>
		</tr>
	</table>
<?php require (DIR_WS_INCLUDES.'footer.php');?>
</body>
</html>
<?php require (DIR_WS_INCLUDES.'application_bottom.php');?>
