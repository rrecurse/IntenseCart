<?php

	require('includes/application_top.php');
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_NEWSLETTERS);



	if (tep_not_null($_POST['Email'])) {

		$customers_email_address = strtolower(tep_db_prepare_input($_POST['Email']));
		$customers_firstname = ucwords(strtolower(tep_db_prepare_input($_POST['firstname'])));
		$customers_lastname = ucwords(strtolower(tep_db_prepare_input($_POST['lastname'])));
		$email_type = (!empty($_POST['email_type']) ? tep_db_prepare_input($_POST['email_type']) : 'HTMLXX');
		$gender = (!empty($_POST['gender']) ? tep_db_prepare_input($_POST['gender']) : '');

		if (tep_validate_email($_POST['Email'])) {

			$subscribers_info = tep_db_query("SELECT subscribers_id 
											  FROM " . TABLE_SUBSCRIBERS . " 
											  WHERE subscribers_email_address = '".$customers_email_address."'
											");

			$source_import = (!empty($_POST['vendor']) && $_POST['vendor'] == '1' ? 'vendors_subscribers' : 'subscribers');
	
			if(tep_db_num_rows($subscribers_info) < 1) {

				$gender = '' ;

				tep_db_query("INSERT INTO " . TABLE_SUBSCRIBERS . " 
							  SET subscribers_email_address = '".$customers_email_address."',
							  subscribers_firstname = '".$customers_firstname."',
							  subscribers_lastname = '".$customers_lastname."',
							  language = 'English', 
							  subscribers_email_type = '".$email_type."', 
							  date_account_created = NOW(), 
							  customers_newsletter = 1,  
							  subscribers_blacklist = 0, 
							  hardiness_zone = '".$domain4."', 
							  status_sent1 = 0, 
							  source_import = '". $source_import ."'
							 "); 
	
			} else {
				
				tep_db_query("UPDATE " . TABLE_SUBSCRIBERS . " 
							  SET customers_newsletter = 1, 
							  subscribers_email_type = '".$email_type."',
							  source_import = '". $source_import ."'
							  WHERE subscribers_email_address = '".$customers_email_address."'
							");
			}
	
			if ($email_type  == "HTMLXX") {
	
				// # build the message content		
				$newsletter_id='3';
		
			    $newsletter = tep_db_query("SELECT ni.*, n.*	
											FROM newsletter_info ni, 
											LEFT JOIN ".TABLE_NEWSLETTERS." n ON n.newsletter_id = ni.newsletter_id
											WHERE ni.newsletter_id = '" . $newsletter_id . "'
										  ");
		
				$newsletter = tep_db_fetch_array($newsletter_query);
		
				if($gender == 'F') {
    		    	$email_greet1 = EMAIL_GREET_MS;
				} else {
					$email_greet1 = EMAIL_GREET_MR;
				}

			    $from = STORE_NAME;
    			$subject = $newsletter['newsletter_info_subject']  ;
		    	$name = $firstname . " " . $lastname;
			    $store_owner = '';
			    $store_owner_email = '';
			    $domain4 = trim($domain4);

			    $email_text = BLOCK1 . $newsletter['newsletter_info_title'] . BLOCK2 . $newsletter['newsletter_info_promo1_name'] . BLOCK3 . $newsletter['newsletter_info_promo1_url'] . BLOCK4 . $newsletter['newsletter_info_promo1_img'] . BLOCK5 . $newsletter['newsletter_info_promo1_des'] . BLOCK6 . $newsletter['newsletter_info_promo1_url'] . BLOCK7 . $newsletter['newsletter_info_promo1_link'] . BLOCK8 . BLOCK9 . $email_greet1  . $firstname . ' ' .  $lastname . ', ' . $newsletter['newsletter_info_greetings'] . '<br>' . BLOCK10 . '<br>' . $newsletter['newsletter_info_intro'] . BLOCK11 .  BLOCK12 .  BLOCK13 .  BLOCK14 .  BLOCK15 .  BLOCK16 .  BLOCK17 . $newsletter['newsletter_info_final_para'] . BLOCK18 . $newsletter['newsletter_info_closing'] . BLOCK19 . BLOCK20 . $customers_email_address . BLOCK22  . BLOCK23 . 'email=' . $customers_email_address . '&action=view' . BLOCK23A . BLOCK24 . 'email=' . $customers_email_address . '&action=view' . BLOCK24A . BLOCK25;
  
				//tep_mail($name, $customers_email_address, $subject, $email_text, $store_owner, $store_owner_email, '');

			} else {

				$message .= EMAIL_WELCOME . CLOSING_BLOCK1 . CLOSING_BLOCK2 . CLOSING_BLOCK3 . UNSUBSCRIBE . $_POST['Email'] ;
	
				 mail($customers_email_address, EMAIL_WELCOME_SUBJECT, $message, "From: " . EMAIL_FROM);
			}
	
			if($_POST['origin']) {
	
				if($_POST['connection'] == 'SSL') {
    	    		$connection_type = 'SSL';
				} else {
    				$connection_type = 'NONSSL';
				}
	
				tep_redirect(tep_href_link(tep_db_prepare_input($_POST['origin']), '', $connection_type));
	
			} else {
	
				tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBE_SUCCESS, '', 'NONSSL'));
			}
	
		} else {
	
			$messageStack->add('newsletter_subscribe', 'Invalid Email address '.$_POST['Email']);
		}
	}

?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
	<html <?php echo HTML_PARAMS; ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<?php
	if(file_exists(DIR_WS_INCLUDES . 'header_tags.php')) {
		require(DIR_WS_INCLUDES . 'header_tags.php');
	} else {
		echo '<title>'. TITLE .'</title>';
	}
?>

	<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
	<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
	<table cellspacing="0" cellpadding="0" width="100%" border="0" align="center">	
		<tr>	
			<td valign="top">

				<table border="0" width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td><?php echo HEADING_TITLE ?></td>
					</tr>

<?php
  if ($messageStack->size('newsletter_subscribe') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('newsletter_subscribe'); ?></td>
      </tr>
<?php
  }
?>
      <tr>
        <td class="newsletters_subscribe">


              <?php echo tep_draw_form('newsletter',tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBE),'post');?>
              <table cellspadding=0 cellspacing=0>
				 <tr>
                  <td>First Name:</td><td><?php echo tep_draw_input_field('firstname')?></td>
                </tr>
				 <tr>
                  <td>Last Name:</td><td><?php echo tep_draw_input_field('lastname')?></td>
                </tr>
                <tr>
                  <td><?php echo TEXT_EMAIL; ?></td><td><?php echo tep_draw_input_field('Email')?></td>
                </tr>
                <tr>
                  <td colspan="2"><?php echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?></td>
                </tr>
              </table>
              </form>
		</td>
	</tr>
</table>
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>