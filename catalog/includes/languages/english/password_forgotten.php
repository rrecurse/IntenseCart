<?php

	define('NAVBAR_TITLE_1', 'Login');
	define('NAVBAR_TITLE_2', 'Password Forgotten');

	define('HEADING_TITLE', 'I\'ve Forgotten My Password!');

	define('TEXT_MAIN', 'If you\'ve forgotten your password, enter your e-mail address below and we\'ll send you an e-mail message containing your new password.');

	define('TEXT_NO_EMAIL_ADDRESS_FOUND', 'Error: The E-Mail Address was not found in our records, please try again.');

	define('EMAIL_PASSWORD_REMINDER_SUBJECT', STORE_NAME . ' - New Password');

	define('EMAIL_PASSWORD_REMINDER_BODY', 'A new password was requested from ' . $REMOTE_ADDR . '.' . "\n\n" . 'Your temporary password to \'' . STORE_NAME . '\' is:' . "\n\n" . '   %s' . "\n\n");

if($customers_id > 0) { 

	$customer_email = tep_db_result(tep_db_query("SELECT customers_email_address FROM customers WHERE customers_id = '".$customers_id."'"),0);

	define("EMAIL_PASSWORD_REMINDER_BODY_ADMIN", "A new password was requested from " . $REMOTE_ADDR . " for user account ".$customer_email."\n\n" . "Their temporary password to " . STORE_NAME . " is:" . "\n\n" . "   %s" . "\n\n");

} else {
	define('EMAIL_PASSWORD_REMINDER_BODY_ADMIN', 'A new password was requested from ' . $REMOTE_ADDR . '.' . "\n\n" . 'Their temporary password to \'' . STORE_NAME . '\' is:' . "\n\n" . '   %s' . "\n\n");
}
	define('SUCCESS_PASSWORD_SENT', 'Success: A new password has been sent to your e-mail address.');
?>