<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


// # This section covers the very first confirmation email sent to a customer, to say that their RMA request has been received.

define('EMAIL_SUBJECT_OPEN', 'RMA request sent to ' . STORE_NAME);
define('EMAIL_TEXT_TICKET_OPEN', 'RMA number: <b><i>' . $rma_value . '</b></i>' . "\n");
define('EMAIL_THANKS_OPEN', 'Thank you for submitting your return request to <b>' . STORE_NAME . '</b>.' . "\n");
define('EMAIL_TEXT_OPEN', 'Your request has been sent to the relevant department for processing. ' . 'If you need to contact us regarding this matter, please quote the above RMA number so that we may keep track of all relevant correspondance.<br>');
define('EMAIL_CONTACT_OPEN', 'For help with any of our online services, please contact us at: ' . STORE_OWNER_EMAIL_ADDRESS . '.' . "\n\n");
define('EMAIL_WARNING_OPEN', '<b>Note:</b> This email address was given to us by someone using it to submit a support request. If you did not send this request, please send a message to ' . STORE_OWNER_EMAIL_ADDRESS . '.');


// # This section covers the confirmation email sent to the assigned administrator after an RMA request has been edited by a customer, 
// # in order to inform the admin that the ticket has been edited.

define('EMAIL_SUBJECT_ADMIN', 'Return request received for order# %s ');
define('EMAIL_ADMIN_INTRO', 'This message is meant to inform you that the above return authorization has been requested by the customer.' . "\n");
define('EMAIL_TEXT_TICKET_ADMIN', 'RMA number - <b>%s</b>' . "\n" . 'Order number - <b>%s</b>' . "\n");
define('EMAIL_TEXT_ADMIN', 'Please log into the admin area to see the return information.' . "\n %s \n");
?>