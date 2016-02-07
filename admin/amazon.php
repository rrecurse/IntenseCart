<?php

/**
 * @author Fagerheim Software
 */

set_include_path (get_include_path () . PATH_SEPARATOR . '../common/classes');

require ('includes/application_top.php');

define ('CACHE_REQ_FILE', DIR_FS_SITE . 'cache/requestid_');
define ('CACHE_REP_FILE', DIR_FS_SITE . 'cache/report_');
define ('CACHE_TIME_FILE', DIR_FS_SITE . 'cache/start_');

if (defined ('SITE_SERVICE_TYPE') && SITE_SERVICE_TYPE == 'intensesite') {
	header ('Status: 302 Redirect');
	header ('Location: ' . HTTP_SERVER . DIR_WS_ADMIN . 'index-site.php');
	exit ();
}

// Show completion message.
if (isset ($_GET['finished']) && $_GET['finished'] == true) {
	die ("<h1>Configuration complete</h1>\n<p>You may now close this window and activate the plugin.</p>");
}

// Validate the feed name given in the URL.
if (!isset ($_GET['feed']) || !$FeedName = preg_replace ("/[^a-z_]/", '', strtolower ($_GET['feed']))) {
	trigger_error ("No valid feeds given.", E_USER_ERROR);
}

// Check if unique constraint has been added already.
$query = "SELECT DISTINCT constraint_name FROM information_schema.table_constraints WHERE constraint_schema = '" .
		DB_DATABASE . "' AND constraint_name = 'feed_prod_field'";
if (!$res = mysql_query ($query)) {
	trigger_error ('Database key restriction check failed.', E_USER_ERROR);
}

// If not, then add it now.
if (mysql_num_rows ($res) != 1) {
	$query = "ALTER TABLE `" . TABLE_DBFEEDS_PROD_EXTRA . "` ADD UNIQUE feed_prod_field (`dbfeed_class`, `products_id`, `extra_field`)";
	if (!mysql_query ($query)) {
		trigger_error ('Could not update database constraints.', E_USER_ERROR);
	}
}

// Retrieve Amazon AWS credentials.
$query = 'SELECT conf_key, conf_value FROM ' . TABLE_MODULE_CONFIG . ' WHERE conf_module = "' .
		mysql_real_escape_string ($FeedName).'"';
if (!$res = mysql_query ($query)) {
	trigger_error ('Could not retrieve Amazon credentials:'.$query, E_USER_ERROR);
}

// Add the credentials to an array for later use.
$Creds = array ();
while ($row = mysql_fetch_array ($res)) {
	$Creds[$row['conf_key']] = $row['conf_value'];
}

// Ensure that we've got the necessary credentials before trying to run the process.
if (count ($Creds) < 6) {
	die ("Please make sure you've configured the plugin before trying to activate it.");
}

// Split feed name and region.
list ($module, $FeedName, $region) = explode ('_', $FeedName);
$FeedName = $module.'_'.$FeedName;

// Select the proper Amazon MWS URL.
if ($region == 'ca') {
	$serviceUrl = "https://mws.amazonservices.ca";
} else {
	$serviceUrl = "https://mws.amazonservices.com";
}

// Create service that connects to the Amazon MWS.
$config = array ('ServiceURL' => $serviceUrl, 'ProxyHost' => null, 'ProxyPort' => -1, 'MaxErrorRetry' => 3);
$service = new MarketplaceWebService_Client ($Creds['access_key'], $Creds['secret_key'], $config, 'IntenseCart Amazon Plugin Installer', '1.0');

// Request the initial inventory report, if it hasn't been done.
if (!is_file (CACHE_REQ_FILE . $region) && !is_file (CACHE_REP_FILE . $region)) {
	do_request ($service, $region, $Creds);
}

// Wait for the Amazon reports to be generated, and parse them.
if (is_file (CACHE_REQ_FILE . $region)) {
	$runtime = do_report ($service, $region, $Creds);

	// Show "waiting" page.
	write_waiting ($runtime);
	die ();
}

// Show and parse the matching form for products.
if (is_file (CACHE_REP_FILE.$region)) {
	echo do_match ($region);
	die ();
}

/**
 * Shows and parses the product matching form.
 *
 * @param string $region
 * @return string
 */
function do_match ($region) {
	// Retrieve product data from local database.
	$query = 'SELECT p.`products_id`, p.`products_model`, d.`products_name`
FROM `'.TABLE_DBFEEDS_PRODUCTS.'` AS f
INNER JOIN `'.TABLE_PRODUCTS.'` AS p ON f.`products_id` = p.`products_id`
INNER JOIN `'.TABLE_PRODUCTS_DESCRIPTION.'` AS d ON p.`products_id` = d.`products_id` AND d.`language_id` = 1
WHERE f.`dbfeed_class` LIKE "dbfeed_amazon%"
GROUP BY p.`products_id`
ORDER BY d.`products_name`';

	if (!$res = mysql_query ($query)) {
		trigger_error('{27} SQL: '.$query."<br>\nError:".mysql_error (), E_USER_ERROR);
	}

	$localData = array ();
	while ($row = mysql_fetch_array ($res)) {
		$localData[$row[0]] = array ($row['products_model'], mb_convert_encoding(substr ($row[2], 0, 40), 'UTF-8'));
	}

	// Initialize variable to contain form status message.
	$message = '';

	// Check if the form has been submitted.
	if (isset ($_POST['submit'])) {
		// Initialize variables for validation purposes.
		$check = true;
		$error = $validated = $duplicates = array ();

		$values = '';
		$_POST['match'] = array_reverse ($_POST['match']);
		foreach ($_POST['match'] as $SKU => $prodID) {
			// Split ASIN and product ID.
			$ASIN = key ($prodID);
			$prodID = $prodID[$ASIN];

			// Skip marked products.
			if ($prodID == 'skip') {
				continue;
			}

			// Make certain the ASIN hasn't been touched.
			if (!Val_alnum ($ASIN, 10) || strlen ($ASIN) < 10) {
				$check = false;
				$message = '<p id="form_error" class="message error">'.
						"One or more invalid ASINs were found. Please make sure they're unchanged from Amazon.</p>\n";
				return Gen_Form ($message, $localData, $region);
			}

			// Validate the product ID given.
			if (!$prodID = intval ($prodID)) {
				$check = false;
				$error[] = $ASIN;
			}

			// Make sure product ID hasn't been taken already.
			if ($prodID && in_array ($prodID, $validated)) {
				$check = false;
				$duplicates[] = $ASIN;
			} else {
				// Add to list of validated IDs
				$validated[$SKU] = $prodID;
			}

			// Add ASIN, SKU and product ID combination to the update query.
			$values .= "('dbfeed_amazon_$region', $prodID, 'asin', '".mysql_real_escape_string ($ASIN)."'),\n";
			$values .= "('dbfeed_amazon_$region', $prodID, 'sku', '".mysql_real_escape_string ($SKU)."'),\n";
		}

		// Make sure validation completed successfully.
		if (!$check) {
			// Validation failed, show appropriate error messages.
			$message = '<p id="form_error" class="message error">';
			if (!empty ($error)) {
				$error = "<ul>\n\t<li>".implode ("</li>\n\t<li>", $error)."</li>\n</ul>";
				$message .= sprintf ('Following ASINS are missing a valid product ID: %s', $error);
			}

			if (!empty ($duplicates)) {
				$duplicates = "<ul>\n\t<li>".implode ("</li>\n\t<li>", $duplicates)."</li>\n</ul>";
				$message .= "<br>\n".sprintf ('Following ASINs have been associated with previously used product IDS: %s', $duplicates);
			}

			// Finish the error message block, show the form, and halt the script.
			$message .= "</p>\n";
			return Gen_Form ($message, $localData, $region, $validated);
		}

		// Add ASINs and Amazon SKUs to the database.
		$values = substr ($values, 0, -2);
		$query = "INSERT IGNORE INTO `".TABLE_DBFEEDS_PROD_EXTRA."` VALUES ".$values;
		if (!mysql_query ($query)) {
			trigger_error ('{24} '.mysql_error ()."\nSQL: ".$query, E_USER_ERROR);
		}

		// Mark plugin as installed.
		$config = "<?php\ndefine ('DB_PASSWORD', '".DB_SERVER_PASSWORD."');\n";
		if (!file_put_contents (DIR_FS_SITE.'conf/dbfeed_amazon_'.$region.'.conf', $config)) {
			trigger_error ('Could not create plugin installation file.', E_USER_ERROR);
		}

		// Delete report cache file.
		unlink (CACHE_REP_FILE.$region);

		// Redirect to last page.
		$URL = "http://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}";
		header ("Location: {$URL}?feed=".rawurlencode ($_GET['feed']).'&finished=true');
		die ();
	}

	return Gen_Form ($message, $localData, $region);
}

/**
 * Generates and displays the matching form, with predefined data.
 *
 * @param string $message
 * @param array $localData
 * @param string $region
 * @param array[optional] $validated
 * @return string
 */
function Gen_Form ($message, $localData, $region, $validated = array ()) {
	// Make sure the file exists.
	if (!is_file (CACHE_REP_FILE.$region)) {
		trigger_error ('Could not find inventory report file', E_USER_ERROR);
	}

	// Read the data from the file.
	if (!$amazonData = file_get_contents (CACHE_REP_FILE.$region)) {
		trigger_error ('Could not read Inventory report file', E_USER_ERROR);
	}

	// Convert data back into an array.
	$amazonData = unserialize ($amazonData);

	$import = $match = array ();
	while ($product = array_shift ($amazonData)) {
		// Reference SKU and ASIN for use in product matching.
		$SKU = $product['sku'];
		$ASIN = $product['asin'];

		// Check if the Amazon SellerSKU matches the local model name.
		if (!empty ($validated) && isset ($validated[$SKU])) {
			$itemID = $validated[$SKU];
		} else {
			// Run through all local products until we find a match.
			$itemID = false;
			foreach ($localData as $prodID => $line) {
				if ($line[0] == $SKU) {
					// Match found, set item ID for preselection in drop down.
					$itemID = $prodID;
					break;
				}
			}
		}

		/**
		 * @todo Find out how to deal with duplicated products.
		 */

		// Generate the dropdown for the local product, preselecting if we have a matching ID.
		$dropDown = Gen_Dropdown ($localData, "match[$SKU][$ASIN]", 'Select product', $itemID, "\t\t\t\t");

		// Put matched and unmatched products into different arrays, for easier sorting.
		if (!$itemID) {
			$import[] = array ($SKU, $ASIN, $product['name'], $product['price'], $product['desc'], $dropDown);
		} else {
			$match[] = array ($SKU, $ASIN, $product['name'], $product['price'], $product['desc'], $dropDown);
		}
	}

	// Merge the two arrays, making sure unmatched products are at the top.
	$import = array_merge ($import, $match);

	// Read the template file, and add all the products to the template.
	return write_form ($message, $import);
}

/**
 * Writes the actual product matching form to screen.
 *
 * @param string $message
 * @param array $data
 * @return string;
 */
function write_form ($message, $data) {
	$products = '';
	$template = <<<OutHTML
			<tr>
				<td>%1\$s</td>
				<td>%2\$s</td>
				<td>%3\$s</td>
				<td>%4\$s</td>
				<td>%6\$s</td>
			</tr>

OutHTML;

	foreach ($data as $line) {
		$products .= vsprintf ($template, $line);
	}

	return <<<OutHTML
<html>
<head>

<title>IntenseCart Amazon MWS plugin installer</title>

</head>
<body>

<h1>Product matching</h1>

{$message}
<form method="post" action="">
	<fieldset class="buttons">
		<input type="reset" name="reset" value="Reset form">
		<input type="submit" name="submit" value="Save products">
	</fieldset>

	<table>
		<thead>
			<tr>
				<th>Amazon SKU:</th>
				<th>Amazon ASIN:</th>
				<th>Amazon name:</th>
				<th>Amazon price:</th>
				<th>Local product:</th>
			</tr>
		</thead>
		<tbody>
{$products}
		</tbody>
	</table>

	<fieldset class="buttons">
		<input type="reset" name="reset" value="Reset form">
		<input type="submit" name="submit" value="Save products">
	</fieldset>
</form>

</body>
</html>
OutHTML;
}

/**
 * Prints HTML code for the waiting page.
 *
 * @param string $runtime
 * @return void
 */
function write_waiting ($runtime) {
	echo <<<OutHTML
<html>
<head>

<title>IntenseCart Amazon MWS plugin installer</title>

<meta http-equiv="refresh" content="60">
<script type="text/javascript">
function Add_Timer () {
	document.getElementById ('runtime').innerHTML += ".";
}
setInterval ('Add_Timer()', 2500);
</script>

</head>
<body>

<h1>Waiting for Amazon inventory report</h1>

<p>This will take several minutes, maybe even as much as half an hour or more.<br>
Unfortunately Amazon provides no ETA on this step, so all we can do is wait.</p>

<p>Current running time: <span id="runtime">{$runtime}</span></p>

<p>As soon as the details have been retrieved, the installation will automatically proceed to the next step.</p>

</body>
</html>
OutHTML;
}

/**
 * Wait for the reports to be generated, requests and parses them.
 *
 * @param object $service
 * @param string $region
 * @param array $creds
 * @return string
 */
function do_report (MarketplaceWebService_Interface $service, $region, $creds) {
	// Retrieve, validate and add the report request ID to the template.
	if (!$requestID = trim (file_get_contents (CACHE_REQ_FILE . $region))) {
		trigger_error ('Could not read Request ID cache file', E_USER_ERROR);
	}

	// Create an array with the regional report request IDs.
	$requestID = explode ("\n", $requestID);

	// Retrieve the start time, if set. Otherwise generate it now.
	if (is_file (CACHE_TIME_FILE . $region)) {
		$starttime = intval (file_get_contents (CACHE_TIME_FILE . $region));
	} else {
		$starttime = time ();
		file_put_contents (CACHE_TIME_FILE . $region, $starttime);
	}

	$reportID = array ();

	// Create the request object to retrieve all reports from the last day.
	$request = new MarketplaceWebService_Model_GetReportListRequest ();
	$request->setMerchant ($creds['merchant_id']);
//	$request->setAvailableFromDate (new DateTime ('-1 days', new DateTimeZone ('UTC')));

	$IDList = new MarketplaceWebService_Model_IdList();
	$IDs = array ();
	foreach ($requestID as $line) {
		list ($region, $ID) = explode ("\t", $line);
		$IDs[] = $ID;
	}
	$IDList->setId ($IDs);

	$request->setReportRequestIdList ($IDList);
//	$request->setAcknowledged (false);

	$reportID = array ();
	foreach ($requestID as $line) {
		// Split region and request ID, and ready region for use in constants.
		list ($region, $ID) = explode ("\t", $line);

		// If current regions report ID has been retrieved, skip to next.
		if (isset ($reportID[$region])) {
			continue;
		}

		// Retrieve the report IDs.
		$ID = Get_ReportID ($service, $request, $ID, $creds['merchant_id']);
		if ($ID) {
			$reportID[$region] = $ID;
		}
	}

	// Calculate running time.
	$runtime = intval ((time () - $starttime) / 60);
	if ($runtime > 1) {
		$runtime .= " minutes";
	} else {
		$runtime .= " minute";
	}

	// If not all report IDs has been found, keep waiting for 1 more minute then refresh.
	if (count ($reportID) != count ($requestID)) {
		return $runtime;
	}

	// Fetch previously added items from dbfeeds_products_extra.
	$tableFeeds = TABLE_DBFEEDS_PROD_EXTRA;
	$tableProds = TABLE_PRODUCTS;
	$tableProdDesc = TABLE_PRODUCTS_DESCRIPTION;
	$query = <<<OutSQL
SELECT p.`products_id`, fe.`extra_value` AS asin
FROM dbfeed_products_extra AS fe
INNER JOIN products AS p ON p.`products_id` = fe.`products_id`
INNER JOIN products_description AS pd ON pd.`products_id` = p.`products_id` AND pd.`language_id` = 1
WHERE fe.`dbfeed_class` LIKE 'dbfeed_amazon%' AND `extra_field` = 'asin'
GROUP BY p.`products_id`
ORDER BY pd.`products_name`
OutSQL;

	if (!$res = mysql_query ($query)) {
		trigger_error ("Could not retrieve local product details: ".$query."\n".mysql_error(), E_USER_ERROR);
	}

	// Store ASIN and PID in a temp array to check for existing products when parsing Amazon reports.
	$localData = array ();
	while ($row = mysql_fetch_array ($res)) {
		$localData[$row['asin']] = $row['products_id'];
	}

	$amazonData = $skipList = array ();
	foreach ($reportID as $region => $ID) {
		// Retrieve report from Amazon.
		$request = new MarketplaceWebService_Model_GetReportRequest ();
		$request->setMerchant ($creds['merchant_id']);
		$request->setReport (@fopen ('php://memory', 'rw+'));
		$request->setReportId ($ID);

		// Lower-case region for use in filename.
		$region = strtolower ($region);

		// Retrieve report details and save for aggregation.
		$temp = explode ("\n", mb_convert_encoding (Get_Report ($service, $request), 'UTF-8'));

		// Fetch primary data for Canada.
		if ($region == 'ca') {
			// Create array of headers, for easier indexing of data.
			$headers = array_flip (explode ("\t", trim (array_shift ($amazonData['ca']))));

			// Create the data source array in a specific order, to ensure there are no surprises later on.
			while ($product = array_shift ($temp)) {
				$SKU = $product[$headers['seller-sku']];

				// If current element exists in the skip-list: Skip.
				if (isset ($skipList[$SKU])) {
					continue;
				}

				// New product, add to output data array.
				$amazonData[$SKU]['asin'] = '';
				$amazonData[$SKU]['sku'] = $product[$headers['seller-sku']];
				$amazonData[$SKU]['price'] = $product[$headers['price']];
				$amazonData[$SKU]['desc'] = $product[$headers['item-name']];
				$amazonData[$SKU]['name'] = $product[$headers['item-name']];
			}
		} else
		// Since CA's main report is lacking the ASIN, add it to the data array.
		if ($region == 'ca asin') {
			$headers = array_flip (explode ("\t", trim (array_shift ($amazonData['ca asin']))));

			// Save all Amazon CA ASIN numbers, for later referencing.
			while ($product = array_shift ($temp)) {
				$SKU = $product[$headers['sku']];
				$ASIN = $product[$headers['asin']];

				// If ASIN has been set in the database already.
				if (isset ($localData[$ASIN])) {
					// Check if the main report has been processed.
					if (isset ($amazonData[$SKU])) {
						// Have, remove current line from the output data array.
						unset ($amazonData[$SKU]);
					} else {
						// Have not, add current product to the skip-list.
						$skipList[$SKU] = true;
					}

					// Skip to next element.
					continue;
				}

				// New product, add the ASIN to the output data array.
				$product = explode ("\t", trim ($product));
				$amazonData[$SKU]['asin'] = $ASIN;
			}
		} else {
			// Create array of US CSV headers, for easier indexing of data.
			$headers = array_flip (explode ("\t", trim (array_shift ($temp))));

			while ($product = array_shift ($temp)) {
				// Explode the current CSV line, and ready variables for inclusion into the output data array.
				$product = explode ("\t", trim ($product));
				$SKU = $product[$headers['seller-sku']];
				$ASIN = $product[$headers['asin1']];
				$name = $product[$headers['item-name']];
				$price = $product[$headers['price']];
				$desc = mb_substr ($product[$headers['item-description']], 0, 100, 'UTF-8');

				$amazonData[$SKU] = array ('asin' => $ASIN, 'sku' => $SKU, 'price' => $price, 'desc' => $desc, 'name' => $name);
			}
		}
	}

	// Serialize the data, and save it to temp file, so that we don't have to repeat all of this waiting again.
	if (!file_put_contents (CACHE_REP_FILE . $region, serialize ($amazonData))) {
		trigger_error ('Could not write to aggregate report data file.', E_USER_ERROR);
	}

	// Delete request ID cache file.
	unlink (CACHE_REQ_FILE . $region);
	unlink (CACHE_TIME_FILE . $region);

	// Redirect to next step.
	header ("Location: http://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}?feed=".rawurlencode ($_GET['feed']));
	die ();
}

/**
 * Requests the initial inventory reports from amazon.
 *
 * Stores the request ID returned from Amazon in a cache file,
 * and in the case of Amazon CA actually requests both reports
 * needed to make the completed comparison form.
 *
 * @param object $service
 * @param string $region
 * @param array $creds
 * @return void
 */
function do_request (MarketplaceWebService_Interface $service, $region, $creds) {
	// Create the request object to retrieve full inventory listing.
	$request = new MarketplaceWebService_Model_RequestReportRequest ();
	$request->setMerchant ($creds['merchant_id']);
	$request->setReportType ('_GET_MERCHANT_LISTINGS_DATA_');
	$request->setReportOptions ('ShowSalesChannel=true');

	// Request report and retrieve request ID.
	$requestID .= $region . "\t" . Request_Report ($request, $service) . "\n";

	if ($region == 'ca') {
		// Create the request object to retrieve full inventory listing.
		$request = new MarketplaceWebService_Model_RequestReportRequest ();
		$request->setMerchant ($creds['merchant_id']);
		$request->setReportType ('_GET_FLAT_FILE_OPEN_LISTINGS_DATA_');
		$request->setReportOptions ('ShowSalesChannel=true');

		// Request report and retrieve request ID.
		$requestID .= $region . " asin\t" . Request_Report ($request, $service) . "\n";
	}

	file_put_contents (CACHE_REQ_FILE . $region, $requestID);
}

/**
 * Request a report and return the request ID, or 0 if not retrieved.
 *
 * @param object $request
 * @param object $service
 * @return int
 */
function Request_Report ($Request, MarketplaceWebService_Interface $Service) {
	try {
		$Res = $Service->requestReport ($Request);
		if (!$Res->isSetRequestReportResult ()) {
			return 0;
		}
		$Res = $Res->getRequestReportResult ();

		if (!$Res->isSetReportRequestInfo ()) {
			return 0;
		}

		$Res = $Res->getReportRequestInfo ();

		if (!$Res->isSetReportRequestId ()) {
			return 0;
		}

		return $Res->getReportRequestId ();
	} catch (MarketplaceWebService_Exception $ex) {
		echo <<<OutEx
<pre>
Function: Request_Report ()
Caught Exception: {$ex->getMessage ()}
Response Status Code: {$ex->getStatusCode ()}
Error Code: {$ex->getErrorCode ()}
Error Type: {$ex->getErrorType ()}
Request ID: {$ex->getRequestId ()}
XML: {$ex->getXML ()}
ResponseHeaderMetadata: {$ex->getResponseHeaderMetadata ()}
OutEx;
		die ();
	}
}

/**
 * Retrieves the report ID for the given request ID, or 0 if not found.
 *
 * $Request can be either an instance of MarketplaceWebService_Model_GetReportListRequest,
 * MarketplaceWebService_Model_GetReportListByNextTokenRequest or an array of parameters
 *
 * @param MarketplaceWebService_Interface $Service
 * @param mixed $Request
 * @param int $RequestID
 * @return int
 */
function Get_ReportID (MarketplaceWebService_Interface $Service, $Request, $RequestID, $MerchantID) {
	try {
		if ($Request instanceof MarketplaceWebService_Model_GetReportListByNextTokenRequest) {
			$Res = $Service->getReportListByNextToken ($Request);
			if (!$Res->isSetGetReportListByNextTokenResult ()) {
				return 0;
			}

			$Res = $Res->getGetReportListByNextTokenResult ();
		} else {
			$Res = $Service->getReportList ($Request);
			if (!$Res->isSetGetReportListResult ()) {
				return 0;
			}

			$Res = $Res->getGetReportListResult ();
		}

		$List = $Res->getReportInfoList ();
		foreach ($List as $Report) {
			if (!$Report->isSetReportRequestId ()) {
				continue;
			}

			if ($Report->getReportRequestId () != $RequestID) {
				continue;
			}

			if ($Report->isSetReportId ()) {
				return $Report->getReportId ();
			}
		}

		if ($Res->isSetNextToken ()) {
			$Request = new MarketplaceWebService_Model_GetReportListByNextTokenRequest ();
			$Request->setMerchant ($MerchantID);
			$Request->setNextToken ($Res->getNextToken ());

			return Get_ReportID ($Service, $Request, $RequestID, $MerchantID);
		}

		return 0;
	} catch (MarketplaceWebService_Exception $ex) {
		echo <<<OutEx
<pre>
Function: Get_ReportID ()
Caught Exception: {$ex->getMessage ()}
Response Status Code: {$ex->getStatusCode ()}
Error Code: {$ex->getErrorCode ()}
Error Type: {$ex->getErrorType ()}
Request ID: {$ex->getRequestId ()}
XML: {$ex->getXML ()}
ResponseHeaderMetadata: {$ex->getResponseHeaderMetadata ()}
OutEx;
		die ();
	}
}

/**
 * Retrieves and return the actual report.
 *
 * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
 * @param mixed $request MarketplaceWebService_Model_GetReport or array of parameters
 * @return string
 */
function Get_Report (MarketplaceWebService_Interface $Service, $Request) {
	try {
		$Res = $Service->getReport ($Request);
		if ($Res->isSetGetReportResult ()) {
			$Res = $Res->getGetReportResult ();
			if ($Res->isSetContentMd5 ()) {
				$Res->getContentMd5 ();
			}
		}

		return (stream_get_contents ($Request->getReport ()));
	} catch (MarketplaceWebService_Exception $ex) {
		echo <<<OutEx
<pre>
Function: Get_Report ()
Caught Exception: {$ex->getMessage ()}
Response Status Code: {$ex->getStatusCode ()}
Error Code: {$ex->getErrorCode ()}
Error Type: {$ex->getErrorType ()}
Request ID: {$ex->getRequestId ()}
XML: {$ex->getXML ()}
ResponseHeaderMetadata: {$ex->getResponseHeaderMetadata ()}
OutEx;
		die ();
	}
}

/**
 * Validates a string to consist of letters and numbers only, optional max length can be set.
 */
function Val_alnum ($String, $MaxLength = '+') {
	if ($MaxLength == "*" && $String == '') {
		return '';
	}

	if (is_int ($MaxLength)) {
		$MaxLength = "{1,$MaxLength}";
	} elseif ($MaxLength != "*") {
		$MaxLength = '+';
	}

	if (preg_match ('/^[a-zA-Z\\d]'.$MaxLength.'\\z/', $String)) {
		return $String;
	}

	return false;
}

/**
 * Generates a select list from source array.
 *
 * $Name is the name used for the POST key value.
 *
 * Set $Preselect to the ID value that's to be preselected.
 * Only valid array index values for this variable.
 *
 * @param array $Source
 * @param string $Name
 * @param string $Header
 * @param mixed $Preselect = false
 * @return string
 */
function Gen_Dropdown ($Source, $Name, $Header, $Preselect = false, $Tabs = '') {
	$Template = "$Tabs\t".'<option value="%1$s"%3$s>%2$s</option>'."\n";
	$Dropdown = $Tabs.'<select id="inp_'.$Name.'" name="'.$Name.'">'."\n\t$Tabs<option value=\"\">** $Header</option>\n".
				$Tabs."\t<option value=\"skip\">** SKIP PRODUCT</option>\n";

	foreach ($Source as $Key => $Value) {
		// Enable non-associative arrays to be used.
		if (is_array ($Value)) {
			$Value = $Value[1];
		}

		// Check if value is to be pre-selected.
		if ($Preselect !== false && $Key == $Preselect) {
			$Dropdown .= sprintf ($Template, htmlspecialchars ($Key), htmlspecialchars ($Value), ' selected="selected"');
			continue;
		}

		$Dropdown .= sprintf ($Template, htmlspecialchars ($Key), htmlspecialchars ($Value), '');
	}

	return $Dropdown."$Tabs</select>\n";
}

/**
 * Autoloader for Amazon MWS classes.
 *
 * @param string $className
 * @return void
 */
function __autoload ($className) {
	$filePath = str_replace ('_', DIRECTORY_SEPARATOR, $className) . '.php';
	$includePaths = explode (PATH_SEPARATOR, get_include_path ());

	foreach ($includePaths as $includePath) {
		if (file_exists ($includePath . DIRECTORY_SEPARATOR . $filePath)) {
			require_once $filePath;
			return;
		}
	}
}
