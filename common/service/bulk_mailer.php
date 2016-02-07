<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

// # Add the path to the IX common classes to PHP's include path.
define ('DIR_FS_SITE', $_SERVER['argv'][1]);
define ('DIR_FS_CORE', '/usr/share/IXcore/');
define ('LOCKFILE', DIR_FS_SITE."cache/mailer.lock");
define ('CHARSET', 'iso-8859-1');

// # If script is already running, terminate and wait for next update.
if (is_file (LOCKFILE)) {
	return 1;
}

// # Add lock file to the clients cache folder.
file_put_contents (LOCKFILE, time ());

// # Ensure that this script doesn't time out.
set_time_limit (0);

/**
 * Compatability function for the mailer.
 *
 * @param mixed $value
 * @return bool
 */
function tep_not_null ($value) {
	return !empty ($value) && trim ($value);
}

// # Include the configuration and table name constants.
require_once (DIR_FS_SITE.'conf/configure.php');
require_once (DIR_FS_SITE."cache/config_cache.php");
require_once (DIR_FS_CORE."admin/includes/database_tables.php");
require_once (DIR_FS_SITE."conf/dbfeed_amazon_us.conf");


// # Include plain text and HTML content types.
// # Replacement Mailer function (dumped existing class usage due to boundary problems).

function send_email($to='', $fromMail='', $subject='', $html_content='', $text_content='', $headers='') { 

	$html_content = (!empty($html_content)) ? str_replace(array("\r\n", "\r", "\n"), '', $html_content) : '';

	// # Setup mime boundary
	$mime_boundary = 'Multipart_Boundary_x'.md5(time()).'x';

	$headers  = "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\r\n";
	$headers .= "Content-Transfer-Encoding: 7bit\r\n";

	$body	 = "This is a multi-part message in mime format.\n\n";

	// # Add in plain text version
	$body	.= "--$mime_boundary\n";
	$body	.= "Content-Type: text/plain; charset=charset=\"us-ascii\"\n";
	$body	.= "Content-Transfer-Encoding: 7bit\n\n";
	$body	.= $text_content;
	$body	.= "\n\n";

	// # Add in HTML version
	$body	.= "--$mime_boundary\n";
	$body	.= "Content-Type: text/html; charset=\"UTF-8\"\n";
	$body	.= "Content-Transfer-Encoding: 8bit\n\n";
	$body	.= wordwrap($html_content,500,"\r\n");
	$body	.= "\n\n";

	// # Attachments would go here
	// # But this whole email thing should be turned into a class to more logically handle attachments, 

	// # End email
	$body	.= "--$mime_boundary--\n"; // # trailing --, required to close email body for mime's

	// # Finish off headers
	$headers .= "From: ".$fromMail."\r\n";
	$headers .= "X-Sender-IP: ".$_SERVER['argv'][3]."\r\n";
	$headers .= 'Date: '.date('n/d/Y g:i A')."\r\n";
	$headers .= "Reply-To: ".STORE_OWNER_EMAIL_ADDRESS." \r\n";

	// # Mail it out
	return mail($to, $subject, $body, $headers);
}


/** @TODO: Retrieve the run intverval from crontab, in minutes,
 * 		to automatically calculate the correct limit.
 */
//require_once (DIR_FS_CORE."common/classes/crontab.php");
//
//// Retrieve the run frequency from the cronjob
//$jobs = Crontab::getJobs();
//foreach ($jobs as $line) {
//	if (strpos ($line, __FILE__) === false) {
//		continue;
//	}
//
//
//}

// # Define the daily limits of mails to send, and run frequency of this script
// # Limit is actually 5k, but reserve 500 mails for non-queued items.
define ('DAILY_LIMIT', 4500); 
// # How often does the cron job run, in minutes?
define ('RUN_FREQUENCY', 1); 

// # Database table names.
$queueTable = TABLE_NEWSLETTERS_QUEUE;
$newsletterTable = TABLE_NEWSLETTERS;
$subscribersTable = TABLE_SUBSCRIBERS;
$subscribersInfos = TABLE_SUBSCRIBERS_INFOS;
$subscribersDefault = TABLE_SUBSCRIBERS_DEFAULT;



// # Connect to the database.
$DB = new mysqli ('localhost', DB_SERVER_USERNAME, base64_decode(DB_PASSWORD.'='), DB_DATABASE);
if ($DB->errno) {
	trigger_error ("Could not connect to the database. Error: ".$DB->error, E_USER_ERROR);
	return 9;
}

	// # Using floor () to ensure we never go above the limit. Divide by number of minutes in a day.
	$maxMails = floor (DAILY_LIMIT * RUN_FREQUENCY / (24*60));

$Query = <<<OutSQL
SELECT n.`newsletters_id`, n.`subject`, n.`fromMail`, n.`content`, n.`content_text`, q.`user_id`, q.`firstname`, q.`lastname`, q.`email`, si.`module_subscribers`, si.`unsubscribea` AS custom_foot, sd.`unsubscribea` AS default_foot
FROM `{$newsletterTable}` AS n
INNER JOIN `{$queueTable}` q ON q.`newsletters_id` = n.`newsletters_id`
LEFT JOIN `{$subscribersInfos}` si ON si.`newsletters_id` = n.`newsletters_id`
LEFT JOIN `{$subscribersDefault}` sd ON sd.`module_subscribers` = n.`module`
WHERE n.`status` IN('pending', 'processing') 
AND q.`status` = 'pending' 
AND n.`date_scheduled` <= NOW()
ORDER BY n.`priority` DESC
LIMIT $maxMails
OutSQL;

if (!$res = $DB->query ($Query)) {
	unlink (LOCKFILE);
	trigger_error ("Error when retrieving mail jobs.\nSQL: $Query\nError: ".$DB->error, E_USER_ERROR);
	return 9;
}

// # If no rows returned, delete lock file and exit normally.
if ($res->num_rows == 0) {
	unlink (LOCKFILE);
	return 0;
}

// # Variables for use in the mail headers.

// # moved $fromMail below inside while loop to account for custom from lines.
//$fromMail = '';
$sender = STORE_NAME;


$completed = array();
$failed = array();
$failedList = array();

// # Process the mails.
$emailStatuses = array();
$theHTTP = $_SERVER['argv'][2];
 
while ($row = $res->fetch_assoc()){
        // # Define template variables and their contents.
        $vars = array(
                '[customer_firstname]' => $row['firstname'],
                '[customer_lastname]' => $row['lastname'],
                '[customer_email]' => $row['email'],
                '[unsubscribe_link]' => $theHTTP.'/unsubscribe.php',
                '[view_online]' => $theHTTP.'/newsletters.php?view_online=1',
                '[store_name]' => $sender,
        );
 
        $email_footer = (!empty($row['custom_foot']) ? $row['custom_foot'] : $row['default_foot']);
 
        // # Parse the content, and add both HTML and plain text versions to the e-mail.
        $mailHTML = str_replace(array_keys($vars), array_values($vars), $row['content'].'<img src="'.$theHTTP.'/px.php?email=' . $row['email'].'&ref=email&nID='.$row['newsletters_id'].'" height="1" width="1" alt="" border="0">'.$email_footer);
        $mailHTML = preg_replace ('~>\s+<~', '><', $mailHTML);
 
        // # Plain text
        $mailText = str_replace (array_keys($vars), array_values($vars), $row['content_text']);

		// # Use the `fromMail` column if different from store STORE_OWNER_EMAIL_ADDRESS constant.

		if($row['fromMail'] != STORE_OWNER_EMAIL_ADDRESS && filter_var($row['fromMail'], FILTER_VALIDATE_EMAIL)) { 
  
			$fromMail = $row['fromMail'];
		
		} else {

			$fromMail = STORE_OWNER_EMAIL_ADDRESS;		
 		}

        // # replace any dynamic email vars in subject also
        $subject = str_replace (array_keys($vars), array_values($vars), $row['subject']);
 
        // # Create the recpipient string.
        $rcpt = $row['firstname'].' '.$row['lastname'] .'<'.$row['email'].'>';
 
        // # Remove any commas from their names to avoid hiccups.
        $rcpt = str_replace(',',' ',$rcpt);
 
        // # Attempt to send the e-mails to the MTA and log their status.
        //#if($mailer->send($rcpt, $row['email'], STORE_NAME, EMAIL_FROM, $row['subject'])) {
 
        if(send_email($rcpt, $sender.' <'.$fromMail.'>', $subject, $mailHTML, $mailText)) {
                $emailStatuses[$row['newsletters_id']]['complete'][] = $row['email'];
        } else {
                $emailStatuses[$row['newsletters_id']]['failed'][] = $row['email'];
        }
}
 
foreach ($emailStatuses as $newsletterId => $statuses){
        // # Delete completed emails
        if (!empty($statuses['complete'])){
                $emailList = array_map(array($DB, 'real_escape_string'), $statuses['complete']);
                $sql = 'DELETE FROM '.$queueTable.' WHERE newsletters_id='.$newsletterId.' AND email IN(\''.implode("','", $emailList).'\')';
                if(!$DB->query($sql)) trigger_error ($DB->error . " \n\n Could not remove completed jobs.", E_USER_WARNING);

                if(!$DB->query("OPTIMIZE TABLE `newsletter_queue`")) {
					trigger_error ($DB->error . " \n\n Could not optimize newsletter_queue table.", E_USER_WARNING);
				}
        }
 
        // # Mark failed
        if (!empty($statuses['failed'])){
			$emailList = array_map(array($DB, 'real_escape_string'), $statuses['failed']);
            $sql = 'UPDATE '.$queueTable.' SET status=\'failed\' WHERE newsletters_id='.$newsletterId.' AND email IN(\''.implode("','", $emailList).'\')';
                if(!$DB->query($sql)) trigger_error ($DB->error . " \n\n Could not mark failed jobs.", E_USER_WARNING);
        }
 
        // # Mark the newsletter as complete or partial depending on if anything exists in the queue still

		$sql = "UPDATE `$newsletterTable` n 
				SET status = 'completed',
				date_sent = NOW()
				WHERE n.newsletters_id = $newsletterId
				AND n.newsletters_id NOT IN (SELECT newsletters_id FROM `$queueTable`)
				";

		// # doesnt work to change statuses
/*
        $sql = "UPDATE `$newsletterTable` n
		        INNER JOIN (
       		        SELECT newsletters_id, 
						   IF(status='failed', 1, 0) as failCount,
			 			   IF(status='pending', 1, 0) as pendingCount
					 	   #SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failCount, 
						   #SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pendingCount
          			FROM `$queueTable` GROUP BY newsletters_id
					) q ON q.newsletters_id = n.newsletters_id
    		    #SET status = CASE WHEN q.failCount=0 AND q.pendingCount=0 THEN 'completed' ELSE 'processing' END, 
				SET status = IF(failCount=0 AND pendingCount=0, 'completed', 'processing'),
				date_sent = NOW()
        		WHERE n.newsletters_id = $newsletterId
			   ";
*/

        if(!$DB->query($sql)) trigger_error ($DB->error . " \n\n Could not update newsletter status.", E_USER_WARNING);
}
// # Set the default error level.
$errorLevel = 0;

// # Show list of failed e-mails, cron will mail it to root.
if (!empty ($failedList)) {
	
	error_log("The following mails were not sent:" . "\n" . print_r ($failedList,1));

	// # Set the error level to EX_TEMPFAIL
	$errorLevel = 75;
}

// # Script finished without fatal errors, exit with the error level.
unlink (LOCKFILE);
return $errorLevel;