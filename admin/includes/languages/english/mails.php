<?php


define('HEADING_TITLE', 'Send Email To Subscribers');

define('TEXT_SUBSCRIBER', 'Subscriber:');
define('TEXT_SUBJECT', 'Subject:');
define('TEXT_FROM', 'From:');
define('TEXT_MESSAGE', 'Message:');
define('TEXT_SELECT_SUBSCRIBER', 'Select Subscriber');
define('TEXT_ALL_SUBSCRIBERS', 'All Subscriber');
define('TEXT_NEWSLETTER_SUBSCRIBERS', 'To All Newsletter Subscribers');

define('NOTICE_EMAIL_SENT_TO', 'Notice: Email sent to: %s');

define('ERROR_NO_CUSTOMER_SELECTED', 'Error: no customer was selected.');
// MaxiDVD Added Line For WYSIWYG HTML Area: BOF
define('TEXT_EMAIL_BUTTON_TEXT', '<p><HR><b><font color="red">The Back Button has been DISABLE while HTML WYSIWG Editor is turned ON,</b></font> WHY? - Because if you click the back button to edit your HTML email, The PHP (php.ini - "Magic Quotes = On") will automatically add "\\\\\\\" backslashes everywhere Double Quotes " appear (HTML uses them in Links, Images and More) and this destorts the HTML and the pictures will dissapear once you submit the email again, If you turn OFF WYSIWYG Editor in Admin the HTML Ability of IntenseCart eCommerce is also turned OFF and the back button will re-appear. A fix for this HTML and PHP issue would be nice if someone knows a solution Iv\'e tried.<br><br><b>If you really need to Preview your emails before sending them, use the Preview Button located on the WYSIWYG Editor.<br><HR>');
define('TEXT_EMAIL_BUTTON_HTML', '<p><HR><b><font color="red">HTML is currently Disabled!</b></font><br><br>If you want to send HTML email, Enable WYSIWYG Editor for Email in: Admin-->Configuration-->WYSIWYG Editor-->Options<br>');
// MaxiDVD Added Line For WYSIWYG HTML Area: EOF
define('TEXT_EMAIL_BUTTON_HTML', '<p><HR><b><font color="red">The editor HTML is not at present validated!!</b></font><br><br>If you want to send a mail to HTML, to Validate the editor HTML: Admin-->Configuration-->Editeur HTML-->Options<br>');
define('NOTICE_EMAIL_SENT_TO', 'Information: email sent in: %s');
define('ERROR_NO_SUBSCRIBER_SELECTED', 'Error: No Subscriber has been selected.');
?>
