<?php 

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

$oID = '9842';
$from = '"Zwave Pro"';

// # connection to imap
$hostname = '{imap.secureserver.net:143/imap}INBOX';
$username = 'customerservice@zwaveproducts.com';
$password = '2W@ve321';

// # try to connection
$inbox = imap_open($hostname, $username, $password, OP_READONLY) or die('Cannot connect to IMAP: ' . imap_last_error());

$date = date ('d-M-Y', strtotime('-3 weeks'));

//$x = imap_search($inbox, 'SUBJECT "HOWTO be Awesome" SINCE "8 August 2008"', SE_UID);

//$uids = imap_search($inbox, 'SUBJECT "#'. $oID.'" SINCE "'.$date.'"', SE_UID);

//print_r($uids);

//var_dump($uids);


$MC = imap_check($inbox);

// Fetch an overview for all messages in INBOX
$result = imap_fetch_overview($inbox,"1:{$MC->Nmsgs}",0);
	foreach ($result as $overview) {

		// # now lets grab the message header to extract our FROM email
		$header = imap_headerinfo($inbox, $overview->msgno);
		// # From email extracted from parts of header.
		$fromaddr = $header->from[0]->mailbox . "@" . $header->from[0]->host;

		// # message body
		$message = imap_fetchbody($inbox,$overview->msgno, 1.2, FT_PEEK);

		echo $fromaddr;

   		echo '<br><br>' . '#{$overview->msgno} ({$overview->date}) - From: {$overview->from} - Subject: {$overview->subject} <br><br>';

		echo '<br><br>' . strip_tags(quoted_printable_decode($message),'<br><p>');
}
imap_close($inbox);



/*
$headers = imap_headers($inbox);

$date = date ('d-M-Y', strtotime('-1 weeks'));

$MC = imap_check($inbox);

if($inbox) {

	// # Trying to get UID's by email address, only "name" seams to work.
	//$uids = imap_search($inbox, 'SUBJECT "The Z-Wave Superstore - Your order: #'. $oID.'" SINCE "'.$date.'"', SE_UID);

	// # following line won't find by email address. 
	// # Tried without quotes - breaks date ranging and still doesnt work
	//$uids = imap_search($inbox, 'FROM '.$from.' SINCE "'.$date.'"', SE_UID);

	//print_r($uids);

	// # commented out the following block - It's quite resource intensive, reading all available headers for mailbox

	// # Fetch an overview for all messages in INBOX
	$result = imap_fetch_overview($inbox,"1:{$MC->Nmsgs}",0);
	foreach ($result as $overview) {

		// # now lets grab the message header to extract our FROM email
		$header = imap_headerinfo($inbox, $overview->msgno);
		// # From email extracted from parts of header.
		$fromaddr = $header->from[0]->mailbox . "@" . $header->from[0]->host;

		// # message body
		$message = imap_fetchbody($inbox,$overview->msgno, 1.2, FT_PEEK);

		echo $fromaddr;

   		echo '<br><br>' . '#{$overview->msgno} ({$overview->date}) - From: {$overview->from} - Subject: {$overview->subject} <br><br>';

		echo '<br><br>' . strip_tags(quoted_printable_decode($message),'<br><p>');

		// # our eventual goal is to match the email address and order number and compare to the customer database. 
		// # If match found, we will store the message by Order ID for that customer.
		// # Store and Match Uid Message ID to prevent duplicate messages.
		// # messages shall be stored in the order_status_history table.
	}

}

imap_close($inbox);
*/


?>