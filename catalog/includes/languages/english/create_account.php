<?php

define('NAVBAR_TITLE', 'Create an Account');
define('NAVBAR_TITLE_1', 'Checkout');
define('NAVBAR_TITLE_2', 'Customer Information');

define('TEXT_RETURNING_CUSTOMER', 'I am a returning customer.');
define('TEXT_PASSWORD_FORGOTTEN', 'Password forgotten? Click here.');

define('TEXT_LOGIN_ERROR', 'Error: No match for E-Mail Address and/or Password.');
define('TEXT_VISITORS_CART', '<font color="#ff0000"><b>Note:</b></font> Your &quot;Visitors Cart&quot; contents will be merged with your &quot;Members Cart&quot; contents once you have logged on. <a href="javascript:session_win();">[More Info]</a>');

define('HEADING_TITLE', 'My Account Information');

define('TEXT_ORIGIN_LOGIN', '<font color="#FF0000"><small><b>NOTE:</b></font></small> If you already have an account with us, please login at the <a href="%s"><u>login page</u></a>.');

// # GLOBAL ACCOUNT CREATE EMAIL  - BEGIN

define('EMAIL_SUBJECT', 'Welcome to ' . STORE_NAME);
define('EMAIL_GREET_MR', 'Dear Mr. %s,' . "\n\n");
define('EMAIL_GREET_MS', 'Dear Ms. %s,' . "\n\n");
define('EMAIL_GREET_NONE', 'Dear %s' . "\n\n");
define('EMAIL_WELCOME', 'We welcome you to <b>' . STORE_NAME . '</b>.' . "\n\n");
define('EMAIL_TEXT', 'You can now take part in the <b>various services</b> we have to offer you. Some of these services include:' . "\n\n" . '<li><b>Permanent Cart</b> - Any products added to your online cart remain there until you remove them, or check them out.' . "\n" . '<li><b>Order History</b> - View your history of purchases that you have made with us.' . "\n" . '<li><b>Products Reviews</b> - Share your opinions on products with our other customers.' . "\n\n");

// # GLOBAL ACCOUNT CREATE EMAIL  - END


// # STANDARD CUSTOMER EMAIL  - BEGIN

define('EMAIL_CONTACT', 'For help with any of our online services, please email the store-owner: ' . STORE_OWNER_EMAIL_ADDRESS . '.' . "\n\n");
define('EMAIL_WARNING', '<b>Note:</b> This email address was given to us by one of our customers. If you did not signup to be a member, please send an email to ' . STORE_OWNER_EMAIL_ADDRESS . '.' . "\n");

// # STANDARD CUSTOMER EMAIL  - END


// # VENDOR EMAIL  - BEGIN

define('VENDOR_EMAIL_WELCOME', 'Thank you for applying to become a ' . STORE_NAME . ' vendor' . "\n\n");
define('VENDOR_EMAIL_TEXT', 'Your application is being reviewed. You should recieve a response from our Vendor management team within 48 business hours. Thank you for your interest in becoming a ' . STORE_NAME . ' Vendor ' . "\n\n");

// # VENDOR EMAIL  - END


// # CCGV ADDED - BEGIN

define('EMAIL_GV_INCENTIVE_HEADER', "\n\n" .'As part of our welcome to new customers, we have sent you an e-Gift Voucher worth %s');
define('EMAIL_GV_REDEEM', 'The redeem code for the e-Gift Voucher is %s, you can enter the redeem code when checking out while making a purchase');
define('EMAIL_GV_LINK', 'or by following this link ');
define('EMAIL_COUPON_INCENTIVE_HEADER', 'Congratulations, to make your first visit to our online shop a more rewarding experience we are sending you an e-Discount Coupon.' . "\n" . ' Below are details of the Discount Coupon created just for you' . "\n");
define('EMAIL_COUPON_REDEEM', 'To use the coupon enter the redeem code which is %s during checkout while making a purchase');

// # CCGV ADDED - END

// # PayPal WPP Modification START

define('EMAIL_EC_ACCOUNT_INFORMATION', 'Thank you for using PayPal Express Checkout!  To make your next visit with us even smoother, an account has been automatically created for you.  Your new login information has been included below:' . "\n\n");
define('TEXT_PAYPALWPP_EC_HEADER', 'Fast, Secure Checkout with PayPal');
define('TEXT_PAYPALWPP_EC_BUTTON_TEXT', 'Save time. Checkout securely. Pay without sharing your financial information.');

// # PayPal WPP Modification END

?>
