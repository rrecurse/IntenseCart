<?php

// Define default timezone.
define ('LOCAL_TIMEZONE', date_default_timezone_get ());
date_default_timezone_set ('UTC');

// Add the path to the IX common classes to PHP's include path.
define ('DIR_FS_CORE', '/usr/share/IXcore/');
set_include_path (get_include_path ().PATH_SEPARATOR.DIR_FS_CORE.'common/classes');

// Ensure that this script doesn't time out.
set_time_limit (0);

// Set application name and version for Amazon API.
define ('APPLICATION_NAME', "IntenseCart Amazon API plugin");
define ('APPLICATION_VERSION', '1.0');

// Set the polling limits as defined by the Amazon API.
define ('ORDERS_MAX', 6);
define ('ORDERS_TIMEOUT', 1*60); // 1 new request per minute.
define ('ITEMS_MAX', 30);
define ('ITEMS_TIMEOUT', 2); // 1 new request per 2 seconds.
define ("SUBMIT_MAX", 15);
define ('SUBMIT_TIMEOUT', 2*60); // 1 new request per 2 minutes.

// Retrieve and validate the region.
$Region = $_SERVER['argv'][2];
$FeedName = 'dbfeed_amazon_'.$Region;

// Define paths for later use.
define ('DIR_FS_SITE', $_SERVER['argv'][1]);

// Include the configuration and table name constants.
require_once (DIR_FS_SITE.'conf/configure.php');
require_once (DIR_FS_SITE."conf/{$FeedName}.conf");
require_once (DIR_FS_CORE."admin/includes/database_tables.php");

// Retrieve Amazon order IDs from database.
$DB = new mysqli ('127.0.0.1', DB_SERVER_USERNAME, DB_PASSWORD, DB_DATABASE);
if ($DB->errno) {
	trigger_error ("Could not connect to the database. Error: ".$DB->error, E_USER_ERROR);
	return 9;
}
$Query = "SELECT ref_value AS order_id FROM orders_items_refs";
if (!$Res = $DB->query ($Query)) {
	trigger_error ("Error when checking module status.\nSQL: $Query\nError: ".$DB->error, E_USER_ERROR);
	return 9;
}

$OrderImport = array ();
while ($Row = $Res->fetch_array ()) {
	// Add order to acknowledgement feed.
	$OrderImport[] = array ('order_id' => $Row['order_id'], 'status' => 'Success');
}

// Retrieve Amazon AWS credentials.
$Query = 'SELECT conf_key, conf_value FROM '.TABLE_MODULE_CONFIG.' WHERE conf_module = "%s"';
$Res = $DB->query (sprintf ($Query, $DB->real_escape_string ($FeedName)));
if (!$Res) {
	unlink (LOCKFILE);
	trigger_error ('Could not retrieve Amazon credentials: '.$Query."\nError: ".$DB->error, E_USER_ERROR);
	return 9;
}

// Add the credentials to an array for later use.
$Creds = array ();
while ($Row = $Res->fetch_array ()) {
	$Creds[$Row['conf_key']] = $Row['conf_value'];
}

// Select the correct Amazon service URL to use for feed submission
if ($Region == 'ca') {
	$ServiceURL =  "https://mws.amazonservices.ca";;
} else {
	$ServiceURL =  "https://mws.amazonservices.com";;
}

// Connnect to Amazon.
$Config = array (
		'ServiceURL' => $ServiceURL,
		'ProxyHost' => null,
		'ProxyPort' => -1,
		'MaxErrorRetry' => 3);
$Service = new MarketplaceWebService_Client ($Creds['access_key'], $Creds['secret_key'],
		$Config, APPLICATION_NAME, APPLICATION_VERSION);

// Mark orders as imported in Amazon MWS.
if ($Status = Update_AmazonOrderStatus ($Service, $Creds, $OrderImport, 'Acknowledgements')) {
	return $Status;
}

return 0;

/**
 * Submits the feed to Amazon.
 *
 * @param string $Type
 * @param object $Service
 * @param array $Creds
 * @param string $Feed
 * @param string $Name
 * @return int
 */
function Submit_Feed ($Type, $Service, $Creds, $Feed, $Name) {
	// Store feed in memory for the Amazon API.
	$FH = fopen('php://memory', 'rw+');
	fwrite ($FH, $Feed);
	rewind ($FH);

	// Create Amazon request.
	$Request = new MarketplaceWebService_Model_SubmitFeedRequest ();
	$Request->setMerchant ($Creds['merchant_id']);
	$Request->setMarketplace ($Creds['marketplace_id']);
	$Request->setFeedType ($Type);
	$Request->setContentMd5 (base64_encode (md5 (stream_get_contents ($FH), true)));
	rewind ($FH);
	$Request->setPurgeAndReplace (false);
	$Request->setFeedContent ($FH);

	rewind ($FH);

	// Send feed to Amazon.
	for ($Run = 0; $Run < 5; $Run++) {
		try {
			$Response = $Service->submitFeed ($Request);

			if (!$Response->isSetSubmitFeedResult ()) {
				// Sleep for timeout, and try again.
				sleep (SUBMIT_TIMEOUT);
				continue;
			}

			$Response = $Response->getSubmitFeedResult ();
			if (!$Response->isSetFeedSubmissionInfo ()) {
				// Sleep for timeout, and try again.
				sleep (SUBMIT_TIMEOUT);
				continue;
			}

			$Response = $Response->getFeedSubmissionInfo ();
			if ($Response->isSetFeedSubmissionId ()) {
				$SubmitID = $Response->getFeedSubmissionId ();
			}

			if ($Response->isSetFeedProcessingStatus ()) {
				$Status = $Response->getFeedProcessingStatus ();
			}
		} catch (MarketplaceWebService_Exception $ex) {
			// If we're throttled, wait for the limit to be increased and try again.
			if ($ex->getErrorCode () == 'RequestThrottled') {
				sleep (SUBMIT_TIMEOUT);
				continue;
			}

			echo ("Caught Exception: " . $ex->getMessage () . "\n");
			echo ("Response Status Code: " . $ex->getStatusCode () . "\n");
			echo ("Error Code: " . $ex->getErrorCode () . "\n");
			echo ("Error Type: " . $ex->getErrorType () . "\n");
			echo ("Request ID: " . $ex->getRequestId () . "\n");
			echo ("XML: " . $ex->getXML () . "\n");
			echo ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata () . "\n");
			fclose ($FH);
			return 10;
		}

		break;
	}
	fclose ($FH);

	// Timed out, return error status.
	if ($Run >= 5) {
		return 4;
	}

	// If Amazon is still processing.
	if ($Status == '_SUBMITTED_') {
		// Write submission ID to temp file.
		echo date ("Y-m-d H:i")."\t$Name\t$SubmitID\n";
	}

	// Return success.
	return 0;
}

/**
 * Generates the XML for the cancelled orders, and sends it to Amazon.
 *
 * If still processing it'll write the submission ID to the SUBMIT_FILE
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonOrderStatus ($Service, $Creds, $Data, $Type) {
	// If no updates, return successfully.
	if (empty ($Data)) {
		return 0;
	}

	$FeedTemplate = <<<EOD
<?xml version="1.0"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
<Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>%1\$s</MerchantIdentifier>
</Header>
<MessageType>OrderAcknowledgement</MessageType>
%2\$s
</AmazonEnvelope>
EOD;

	$OrderTemplate = <<<EOD
<Message>
        <MessageID>%1\$d</MessageID>
        <OrderAcknowledgement>
           <AmazonOrderID>%2\$s</AmazonOrderID>
           <StatusCode>%3\$s</StatusCode>
        </OrderAcknowledgement>
</Message>

EOD;

	// Loop through all cancelled orders.
	$OrderData = '';
	$Run = 1;
	foreach ($Data as $Order) {
		// Add order ID to the feed data.
		$OrderData .= sprintf ($OrderTemplate, $Run++, $Order['order_id'], $Order['status']);
	}

	// Add merchant ID, random message ID, and all orders data to feed.
	$Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $OrderData);

	return Submit_Feed ('_POST_ORDER_ACKNOWLEDGEMENT_DATA_', $Service, $Creds, $Feed, $Type);
}

/**
 * Autoloader for Amazon MWS classes.
 *
 * @param string $className
 * @return void
 */
function __autoload ($className) {
	$filePath = str_replace ('_', DIRECTORY_SEPARATOR, $className).'.php';
	$includePaths = explode (PATH_SEPARATOR, get_include_path ());

	foreach ($includePaths as $includePath) {
		if (file_exists ($includePath.DIRECTORY_SEPARATOR.$filePath)) {
			require_once $filePath;
			return;
		}
	}
}
