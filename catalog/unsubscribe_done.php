<?php

	require('includes/application_top.php');

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_UNSUBSCRIBE);

	$breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_UNSUBSCRIBE, '', 'NONSSL'));

	$email_to_unsubscribe = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
  
	// # Check and see if the email exists in the database, and is subscribed to the newsletter.
	$cus_subscribe_query = tep_db_query("SELECT * FROM customers 
										 WHERE customers_newsletter = '1' 
										 AND customers_email_address LIKE '" . $email_to_unsubscribe . "'
										");

	$sub_subscribe_query = tep_db_query("SELECT * FROM subscribers 
										 WHERE customers_newsletter = '1' 
										 AND subscribers_email_address LIKE '" . $email_to_unsubscribe . "'
										");


	// # If we found the customers email address, and they currently subscribe
	// # Unsubscribe them
	
	$success = false;
	if(tep_db_num_rows($cus_subscribe_query) > 0) {
		tep_db_query("UPDATE " . TABLE_CUSTOMERS . " 
					  SET customers_newsletter = '0' 
					  WHERE customers_email_address LIKE '" .$email_to_unsubscribe . "'
					 ");

		$success = true;

		if( (isset($_GET['ref']) && $_GET['ref'] == 'email') && isset($_GET['nID'])) {

			tep_db_query("UPDATE " . TABLE_NEWSLETTER_STATS. " 
						  SET unsubscribed = '1' 
						  WHERE email LIKE '" .$email_to_unsubscribe . "' 
						  AND newsletters_id = '". (int)$_GET['nID']."'
						  AND user_agent LIKE '".$_SERVER['HTTP_USER_AGENT']."'
						");
		}

	} elseif(tep_db_num_rows($sub_subscribe_query) > 0) {
 
		tep_db_query("DELETE FROM subscribers WHERE subscribers_email_address LIKE '" .$email_to_unsubscribe . "'");

		$success = true;	

		if( (isset($_GET['ref']) && $_GET['ref'] == 'email') && isset($_GET['nID'])) {

			tep_db_query("UPDATE " . TABLE_NEWSLETTER_STATS . " 
						  SET unsubscribed = '1'
						  WHERE email LIKE '" .$email_to_unsubscribe . "' 
						  AND newsletters_id = '".(int)$_GET['nID']."'
						  AND user_agent LIKE '".$_SERVER['HTTP_USER_AGENT']."'
						 ");
		}

		// #  Otherwise, we want to display an error message (This should never occur, unless they try to unsubscribe twice)
	} else {
        $success = false;
	}


//error_log(print_r($email_to_unsubscribe,1));

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="100%" valign="top" colspan="2">
		<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
<?php

	if($success){
		echo '<td class="main">'. UNSUBSCRIBE_DONE_TEXT_INFORMATION . $email_to_unsubscribe . '</td>';		
	} else {
		 echo '<td class="main">'. UNSUBSCRIBE_ERROR_INFORMATION . $email_to_unsubscribe .'</td>';
	}
?>
               
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center" class="main"><br><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT, '', 'NONSSL') . '">' . tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
      </tr>
    </table></td>

    <td valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>