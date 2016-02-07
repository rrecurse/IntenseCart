<?php

// Include the custom error handler.
require_once ("errorhandler.php");

// Define default timezone.
define ('LOCAL_TIMEZONE', date_default_timezone_get());

// # using UTC or GMT places current time 4 hours ahead
// # GMT seems to produce more accurate time stamps
//date_default_timezone_set ('UTC');
//date_default_timezone_set ('GMT');

// # test timestamp
//error_log(print_r('from /common/service/newegg_inventory - time now is ' . date('Y-m-d H:i:s'),1)); 

// # Add the path to the IX common classes to PHP's include path.
define ('DIR_FS_CORE', '/usr/share/IXcore/');
set_include_path (get_include_path ().PATH_SEPARATOR.DIR_FS_CORE.'common/classes');

// # Ensure that this script doesn't time out.
set_time_limit(0);

// # Set the polling limits as defined by the Newegg API.
define("SUBMIT_MAX", 15);
define('SUBMIT_TIMEOUT', 2*60); // 1 new request per 2 minutes.

// # Set application name and version for Newegg API.
define('APPLICATION_NAME', "IntenseCart Newegg API plugin");
define('APPLICATION_VERSION', '1.0');

// # Retrieve and validate the region.
$Region = $_SERVER['argv'][2];
$FeedName = 'dbfeed_newegg_'.$Region;

// # Define paths for later use.
define('DIR_FS_SITE', $_SERVER['argv'][1]);
define('LOCKFILE', DIR_FS_SITE."cache/newegg_inventory_{$Region}.lock");
define('FILE_RUNTIME', DIR_FS_SITE."cache/newegg_inventory_{$Region}.runtime");
define('FILE_DUMP', DIR_FS_SITE."cache/newegg_inventory_{$Region}.dump");
define('FILE_SUBMIT', DIR_FS_SITE."cache/newegg_inventory_{$Region}.submit");

// # If the plugin haven't been installed yet, exit silently.
if(!is_file (DIR_FS_SITE."conf/{$FeedName}.conf")) {
	return 0;
}

// # Include the configuration and table name constants.
require_once(DIR_FS_SITE.'conf/configure.php');
require_once(DIR_FS_SITE."conf/{$FeedName}.conf");
require_once(DIR_FS_CORE."admin/includes/database_tables.php");

// # Set the correct path to the product images.
define('DIR_FS_CATALOG_IMAGES', DIR_FS_SITE.'public_html/images/');

// # If script is already running, terminate and wait for next update.
if (is_file (LOCKFILE)) {
	return 8;
}

// # Get the timestamp from the last run, and save the current timestamp for later use.
if (!is_file (FILE_RUNTIME)) {
	$PollTime = strtotime ("-1 day");
} else {
	$PollTime = file_get_contents (FILE_RUNTIME);
}

$CurrentTime = time();

// # Retrieve Newegg credentials from database.
$DB = new mysqli ('127.0.0.1', DB_SERVER_USERNAME, base64_decode(DB_PASSWORD), DB_DATABASE);
if ($DB->errno) {
	trigger_error ("Could not connect to the database. Error: ".$DB->error, E_USER_ERROR);
	return 9;
}

// # Get the status of the module.
$Query = "SELECT mods_enabled FROM module_sets WHERE mods_module = '%s'";
$Query = sprintf ($Query, $DB->real_escape_string ($FeedName));
if (!$Res = $DB->query ($Query)) {
	trigger_error ("Error when checking module status.\nSQL: $Query\nError: ".$DB->error, E_USER_ERROR);
	return 9;
}

// # Either not installed, or purged.
if ($Res->num_rows == 0) {
	die ('Module disabled.');
}

// # Disabled.
$Row = $Res->fetch_array ();
if ($Row['mods_enabled'] == 0) {
	die ('Module disabled');
}

// # Add lock file to the clients cache folder.
file_put_contents(LOCKFILE, time ());

// # Retrieve Newegg AWS credentials.
$Query = 'SELECT conf_key, conf_value FROM '.TABLE_MODULE_CONFIG.' WHERE conf_module = "%s"';
$Res = $DB->query (sprintf ($Query, $DB->real_escape_string ($FeedName)));
if ($DB->errno) {
	unlink (LOCKFILE);
	trigger_error ("Could not retrieve Newegg credentials: ".$Query."\nError: ".$DB->error, E_USER_ERROR);
	return 9;
}

// # Add the credentials to an array for later use.
$Creds = array ();
while($Row = $Res->fetch_array ()) {
	$Creds[$Row['conf_key']] = $Row['conf_value'];
}

if (!is_file (FILE_DUMP)) {
	// # Retrieve product changes since last $PollTime.
	$Data = Get_UpdatedProducts ($DB, $FeedName, $PollTime);
	if (!is_array ($Data)) {
		unlink (LOCKFILE);
		return $Data;
	}

	// # Save data to dump file, and update runtime file.
	if (!file_put_contents (FILE_DUMP, serialize ($Data))) {
		unlink (LOCKFILE);
		trigger_error ('Could not save dump file', E_USER_ERROR);
		return 6;
	}
	if (!file_put_contents (FILE_RUNTIME, $CurrentTime)) {
		unlink (LOCKFILE);
		trigger_error ('Could not update runtime file', E_USER_ERROR);
		return 6;
	}
} else {
	// # Read cached data.
	$Data = unserialize (file_get_contents (FILE_DUMP));

}

// # Send data to Newegg, and leave the dump file on disk if errorstatus > 0.
if (!empty($Data) && $Res = Update_Newegg($Data, $Creds, $PollTime, $Region)) {
	unlink (LOCKFILE);
	return $Res;
}

// # Remove the dump and lock files.
unlink (FILE_DUMP);
unlink (LOCKFILE);

// # Return success
return 0;

/**
 *
 * @param mysqli $DB
 * @param string $FeedName
 * @param int $LastRun
 * @return array
 */
function Get_UpdatedProducts (mysqli $DB, $FeedName, $LastRun) {
	// # Create a MySQL timestamp out of last runtime.
/*	$LastRun = new DateTime("$LastRun");
	$LastRun->setTimezone (new DateTimeZone (LOCAL_TIMEZONE));
	$LastRun = $LastRun->format ("Y-m-d H:i:s"); 
*/
	$LastRun = date("Y-m-d H:i:s", $LastRun);

	$Region = $_SERVER['argv'][2];

	$Data = array ();
	$Query = "SELECT p.products_id,
					 p.products_sku,
					 pe.extra_value AS newegg_sku,
					 p.products_model,
					 p.products_upc,
					 spg.products_msrp,
					 p.products_status,
					 p.products_date_added,
					 p.products_tax_class_id,
					 pd.products_name,
					 pd.products_info AS description,
					 m.manufacturers_name,
					 p.products_weight AS weight,
					 p.products_length AS length,
					 p.products_width AS width,
					 p.products_height AS height,
					 pd.products_head_keywords_tag AS searchterms,
					 p.products_image,
					 p.products_price,
					 p.products_quantity AS quantity,
					 p.products_free_shipping,
					 pe2.extra_value AS shipping_rate,
					 pe3.extra_value AS newegg_surcharge,
					 pe4.extra_value AS newegg_itemid
			FROM " . TABLE_PRODUCTS . " AS p
			INNER JOIN ".TABLE_DBFEEDS_PRODUCTS." dbp ON (dbp.products_id = p.products_id AND dbp.dbfeed_class = '$FeedName')
			LEFT JOIN ".TABLE_DBFEEDS_PROD_EXTRA." pe ON (pe.products_id = p.products_id AND pe.dbfeed_class = '$FeedName' AND pe.extra_field = 'sku')
			INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.master_products_id AND pd.language_id = 1)
			LEFT JOIN ". TABLE_SUPPLIERS_PRODUCTS_GROUPS. " spg ON spg.products_id = pd.products_id
			INNER JOIN " . TABLE_MANUFACTURERS . " m	ON m.manufacturers_id = p.manufacturers_id
			LEFT JOIN " . TABLE_DBFEEDS_PROD_EXTRA . " pe2 ON (pe2.products_id = p.products_id AND pe2.dbfeed_class = '$FeedName' AND pe2.extra_field = 'shipping_cost')
			LEFT JOIN " . TABLE_DBFEEDS_PROD_EXTRA . " pe3 ON (pe3.products_id = p.products_id AND pe3.dbfeed_class = '$FeedName' AND pe3.extra_field = 'newegg_surcharge')
			LEFT JOIN " . TABLE_DBFEEDS_PROD_EXTRA . " pe4 ON (pe4.products_id = p.products_id AND pe4.dbfeed_class = '$FeedName' AND pe4.extra_field = 'itemid')
			WHERE (p.products_last_modified >= '$LastRun' OR p.products_date_added >= '$LastRun' OR p.last_stock_change >= '$LastRun') 
			AND p.products_sku != '' 
			AND p.products_sku IS NOT NULL
			GROUP BY p.products_id";


	// # Newegg requires price adjustment - they DO NOT do the currency conversion.
	// # We created function convert_currency() using the Google Finance API
	// # hopefully there isnt any noticable downtime with google it this doesnt fail.

	if ($Region == 'ca') {
		// # Canada
		$currency = 'CAD';

	} else { 

	 	// # United states
		$currency = 'USD';
	}


	if(!defined("MULTI_WAREHOUSE_ACTIVE")) { 

		if (!$multiWarehouse = $DB->query("SELECT configuration_value AS warehouse_active FROM " . TABLE_CONFIGURATION ." WHERE configuration_key = 'MULTI_WAREHOUSE_ACTIVE'")) {
			trigger_error ("Could not get status of configuration value of database for MULTI_WAREHOUSE_ACTIVE - ".$DB->error, E_USER_ERROR);
			return 9;
		} 
		while ($mw = $multiWarehouse->fetch_assoc()) {
			define("MULTI_WAREHOUSE_ACTIVE", ($mw['warehouse_active'] == 'true' ? 'true' : 'false'));
		}
	}

	if (!$Res = $DB->query ($Query)) {
		trigger_error ("Could not retrieve updated product data.\nSQL: $Query\nError: ".$DB->error, E_USER_WARNING);
		return 9;
	}

	// If no updated products found, exit with success.
	if ($Res->num_rows == 0) {
		return 0;
	}

	$Data = array('new' => array(), 'inventoryandprice' => array());

	while ($Row = $Res->fetch_assoc ()) {
		// Set the correct SKU for the product, or use model if no SKU is found.
		if (!empty ($Row['newegg_sku'])) {
			$SKU = $Row['newegg_sku'];
		} elseif (empty ($Row['newegg_sku']) && !empty ($Row['products_sku'])) {
			$SKU = $Row['products_sku'];
		} else {
			$SKU = $Row['products_model'];
		}

		if($Region != 'us' && $Row['shipping_rate'] > 0) { 
			$currency_offset = convert_currency('USD', $currency, $Row['shipping_rate']);
		} else { 
			$currency_offset = $Row['shipping_rate'];
		}

		if($Region == 'us') { 
			$shipping_option = 'Default';
		} elseif($Region == 'ca') { 
			$shipping_option = 'default';
		} else { 
			$shipping_option = 'Default';
		}

		// # If we have a surcharge, add it to the price.
		if ($Row['newegg_surcharge']) {
			$Row['products_price'] += round($Row['newegg_surcharge'],2);
		}

		// # multi-warehousing - update tables for multi-warehousing.
		if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

			// # detect if product has an entry in the products_warehouse_inventory table.
			// # if not, then use default master quantity level.
			$warehouse_query = $DB->query("SELECT pwi.products_warehouse_id AS warehouse_id, 
										   pwi.products_quantity
										   FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." pwi 
										   LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE ." pw ON pw.products_warehouse_id = pwi.products_warehouse_id
										   WHERE pwi.products_id = '". $Row['products_id'] ."'
										   AND (pwi.products_warehouse_id = 1 OR pw.products_warehouse_name = 'Home')
										  ");

			if($warehouse_query->num_rows > 0) { 

				$warehouse = $warehouse_query->fetch_array(MYSQLI_ASSOC);

				$warehouse_query->free_result();

				$products_quantity = ($warehouse['products_quantity'] > 0 ? $warehouse['products_quantity'] : 0);

			} else { // # no entry in products_warehouse_inventory table.
	
				$products_quantity = ($Row['quantity'] > 0 ? $Row['quantity'] : '0'); 

			}

		} else { // # multi-warehousing is off

			$products_quantity = ($Row['quantity'] > 0 ? $Row['quantity'] : '0'); 

		}


		// # If the product has been added since last run, add it to the product feed data.
		if ($Row['products_date_added'] >= $LastRun || empty($Row['newegg_sku'])) {
			// # If no Newegg SKU is set, add it.
			if (!empty ($Row['newegg_sku'])) {
				$Query = "INSERT IGNORE INTO ".TABLE_DBFEEDS_PROD_EXTRA." VALUES ('%s', %d, 'sku', '%s')";
				$Query = sprintf ($Query, $FeedName, $Row['products_id'], $DB->real_escape_string ($SKU));
				if (!$Temp = $DB->Query ($Query)) {
					trigger_error ("Could not add Newegg SKU.\nSQL: $Query\nError: ".$DB->error, E_USER_WARNING);
					return 9;
				}
			}

			// # PATCH: To work around the missing search terms == fiery feed death.
			if (empty ($Row['searchterms'])) {
				$Row['searchterms'] = $Row['products_name'];
			}

			// Make sure keywords are no more than 50 characters long (Newegg limitation).
			// fix this crap to put one keyword per line at 50 chars max, not the entire keyword array!
			if (($Length = strlen ($Row['searchterms'])) > 50) {
				if ($Pos = strrpos ($Row['searchterms'], ",", 50-$Length)) {
					$Row['searchterms'] = trim (substr ($Row['searchterms'], 0, $Pos), " ,");
				} else {
					$Row['searchterms'] = substr ($Row['searchterms'], 0, strrpos ($Row['searchterms'], " ", 50-$Length));
				}
			}

			// # Please make sure these items are added in the same order as they'll appear in the feed.
			$Data['new'][] = array (
				'sku' 			=> $SKU,
				'itemid' 		=> $Row['newegg_itemid'],
				'title' 		=> htmlspecialchars ($Row['products_name']),
				'desc' 			=> htmlspecialchars ($Row['description']),
				'manufacturer' 	=> htmlspecialchars ($Row['manufacturers_name']),
				'weight' 		=> $Row['weight'],
				'length' 		=> $Row['length'],
				'width' 		=> $Row['width'],
				'height' 		=> $Row['height'],
				'keywords' 		=> htmlspecialchars ($Row['searchterms']),
				'type' 			=> 'ConsumerElectronics',
				'length_unit' 	=> 'IN',
				'weight_unit' 	=> 'LB',
				'id_type' 		=> 'UPC',
				'id_value' 		=> $Row['products_upc'],
				'msrp' 			=> $Row['products_msrp'],
				'active' 		=> ($Row['products_status'] == 1 && $Row['products_price'] > 0 ? 1 : 0),
				'quantity'		=> (int)$products_quantity,
				'freeship' 		=> $Row['products_free_shipping'],
				'price' 		=> $currency_offset
			);


			// # Add an image to the feed, if it exists.
			if (!empty ($Row['products_image']) && is_file (DIR_FS_CATALOG_IMAGES."/{$Row['products_image']}")) {
				$Data['images'][] = array (
					'sku' => $SKU,
					'type' => 'Main',
					'url' => 'http://'.SITE_DOMAIN.'/images/'.$Row['products_image']
				);
			}
		}

		// # strip the rate down to two decimal places - round() and number_format() did not produce wanted result
		$theRate = (float)preg_replace('/(\.\d\d).*/', '$1', $Row['products_price']);

		// # if the $Region is not the US
		// # trigger currency conversion using the google currency converter API (function at bottom)
		if($Region != 'us') { 
			$currency_offset = convert_currency('USD', $currency, $theRate);
		} else { 
			$currency_offset = $theRate;
		}
		
		// # Add pricing and inventory data.
		$Data['inventoryandprice'][] = array (
			'sku' => $SKU,
			'itemid' => $Row['newegg_itemid'],
			'title' => htmlspecialchars ($Row['products_name']),
			'desc' => htmlspecialchars ($Row['description']),
			'manufacturer' => htmlspecialchars ($Row['manufacturers_name']),
			'weight' => $Row['weight'],
			'length' => $Row['length'],
			'width' => $Row['width'],
			'height' => $Row['height'],
			'keywords' => htmlspecialchars ($Row['searchterms']),
			'type' => 'ConsumerElectronics',
			'length_unit' => 'IN',
			'weight_unit' => 'LB',
			'id_type' => 'UPC',
			'id_value' => $Row['products_upc'],
			'msrp' => number_format($Row['products_msrp'],2),
			'active' => $Row['products_status'],
			'quantity' => (int)$products_quantity,
			'freeship' => $Row['products_free_shipping'],
			'price' => $currency_offset
		);
	}

	return $Data;
}

/**
 * Creates and sends the product details feeds to newegg.
 *
 * Returns 0 on success, or an positive integer as the error code if not.
 *
 * @param array $Data
 * @param array $Creds
 * @param int $LastRun
 * @param string $Region
 * @return int
 */
function Update_Newegg($Data, $Creds, $LastRun, $Region) {

	// # Select the correct Newegg service URL to use.
	if ($Region == 'ca') {
		$ServiceURL = "https://api.newegg.ca/marketplace/datafeedmgmt/feeds/submitfeed?sellerid=".$Creds['seller_id'] . "&requesttype="; // # Requesttype added with $Type var below
	} else { 

		$ServiceURL = "https://api.newegg.com/marketplace/datafeedmgmt/feeds/submitfeed?sellerid=".$Creds['seller_id'] . "&requesttype="; // # Requesttype added with $Type var below

		//$ServiceURL = "https://api.newegg.com/marketplace/contentmgmt/item/inventoryandprice?sellerid=".$Creds['seller_id'] . "&version=304";
	}

	// Connnect to Newegg.
	$Config = array('ServiceURL' => $ServiceURL,
					'ProxyHost' => null,
					'ProxyPort' => -1,
					'MaxErrorRetry' => 3
					);

	$Service = new NeweggMarketplace_Client($Creds['auth_key'], $Creds['secret_key'], $Config, APPLICATION_NAME, APPLICATION_VERSION);

	// # Create and send new products feed.
	//if ($Status = Update_NeweggNewProducts($Service, $Creds, $Data['new'])) {
		//return $Status;
	//}
	// # Create and send inventory count and latest price
	if ($Status = Update_NeweggInventoryPrice($Service, $Creds, $Data['inventoryandprice'])) {
//error_log('Status - ' . $Status);
		return $Status;
	}

	return 0;
}

/**
 * Creates and sends the new products feed to Newegg.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_NeweggNewProducts($Service, $Creds, $Data) {
	// If no updates, return successfully.
	if (empty ($Data)) {
		return 0;
	}

	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<NeweggEnvelope>
	<Header>
		<DocumentVersion>1.0</DocumentVersion>
	</Header>
	<MessageType>Inventory</MessageType>
	<Overwrite>No</Overwrite>
		%2\$s
</NeweggEnvelope>
EOD;

	$ProductTemplate = <<<EOD

		<Message>
			<Inventory>
				<Item>
					<SellerPartNumber>%2\$s</SellerPartNumber>
					<NeweggItemNumber>%3\$s</NeweggItemNumber>
					<Currency>USD</Currency>
					<MSRP>%17\$s</MSRP>
					<MAP>0</MAP>
					<CheckoutMAP>False</CheckoutMAP>
					<SellingPrice>%21\$s</SellingPrice>
					<Inventory>%19\$s</Inventory>
					<FulfillmentOption>Seller</FulfillmentOption>
					<Shipping>default</Shipping>
					<ActivationMark>%18\$s</ActivationMark>
				</Item>
			</Inventory>
		</Message>
EOD;

	$ProductData = '';
	$Run = 1;
	foreach ($Data as $Product) {
		array_unshift ($Product, $Run++);
		$ProductData .= vsprintf($ProductTemplate, $Product);
	}

	$Feed = sprintf ($FeedTemplate, $Creds['seller_id'], $ProductData);

	// # Submit feed and return success status.
	return Submit_Feed('ITEM_DATA', $Service, $Creds, $Feed, 'BatchCreate');
}


/**
 * Creates and sends the updated inventory feed to Newegg.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_NeweggInventoryPrice($Service, $Creds, $Data) {
	// # If no updates, return successfully.
	if (empty ($Data)) {
		trigger_error('/common/service/newegg_inventory.php $Data is empty inside Update_NeweggInventoryPrice() function');
		return 0;
	}

	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<NeweggEnvelope>
	<Header>
		<DocumentVersion>1.0</DocumentVersion>
	</Header>
	<MessageType>Inventory</MessageType>
	<Overwrite>No</Overwrite>
	<Message>
		<Inventory>
		%1\$s
		</Inventory>
	</Message>
</NeweggEnvelope>
EOD;

	$ProductTemplate = <<<EOD
		<Item>
			<SellerPartNumber>%2\$s</SellerPartNumber>
			<NeweggItemNumber>%3\$s</NeweggItemNumber>
			<Currency>USD</Currency>
			<MSRP>%17\$s</MSRP>
			<MAP>0</MAP>
			<CheckoutMAP>False</CheckoutMAP>
			<SellingPrice>%21\$s</SellingPrice>
			<Inventory>%19\$s</Inventory>
			<FulfillmentOption>Seller</FulfillmentOption>
			<Shipping>%18\$s</Shipping>
			<ActivationMark>1</ActivationMark>
		</Item>
EOD;


	$ProductData = '';
	$Run = 1;

	foreach ($Data as $Product) {

		array_unshift($Product, $Run++);

		$ProductData .= vsprintf($ProductTemplate, $Product);
	}

	$Feed = sprintf($FeedTemplate, $ProductData);

	// # Submit feed and return success status.
	return Submit_Feed('INVENTORY_AND_PRICE_DATA', $Service, $Creds, $Feed, 'InventoryPrice');
}



/**
 * Submits the feed to Newegg.
 *
 * @param string $Type
 * @param object $Service
 * @param array $Creds
 * @param string $Feed
 * @param string $Name
 * @return int
 */
function Submit_Feed ($Type, $Service, $Creds, $Feed, $Name) {
	// # Store feed in memory for the Newegg API.

	$FH = fopen('php://memory', 'rw+');
	fwrite ($FH, $Feed);
	rewind ($FH);

	// # Create Newegg request.
	$header_array = array('Content-Type: application/xml',
						  'Accept:application/xml',
						  'Authorization:' . $Creds['auth_key'],
						  'SecretKey: '. $Creds['secret_key']
						  );

	// # Send feed to Newegg.
	for ($Run = 0; $Run < 5; $Run++) {
		try {
//error_log(print_r("Newegg InventoryPrice Feed" . "\n" . $Feed,1));

			// # added $Type for datafeed URL requirement.
			$ServiceURL = $Service->config['ServiceURL'].$Type;

			// # Get the curl session object
			$ch = curl_init($ServiceURL);
			$putString = stripslashes($Feed);
			$putData = tmpfile();
			fwrite($putData, $putString);
			fseek($putData, 0);

			// # Set the POST options.
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER,$header_array);
			//curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $Feed);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_INFILE, $putData);
			curl_setopt($ch, CURLOPT_INFILESIZE, strlen($putString));

			// # Do the POST and then close the session
			$response = curl_exec($ch);
			curl_close($ch);

//error_log(print_r("Newegg InventoryPrice Response" . "\n" . $response,1));

			preg_match_all("/\<ResponseBody\>(.*?)\<\/ResponseBody\>/s", $response, $responseNodes);

			foreach($responseNodes[1] as $Nodes) {
				preg_match_all( "/\<RequestId\>(.*?)\<\/RequestId\>/",$Nodes, $RequestId);
				$RequestId = $RequestId[1][0];
			}

			$Response = (isset($RequestId) ? 1 : '0');

			if ($Response == 1) {
				$SubmitID = $RequestId;

//error_log(print_r($SubmitID,1));

				$Status = $Response;
			} else {
				preg_match_all("/\<Errors\>(.*?)\<\/Errors\>/s", $response, $errorNodes);
				foreach($errorNodes[1] as $Errors) {
					preg_match_all( "/\<Message\>(.*?)\<\/Message\>/",$Errors, $ErrorStatus);
					$ErrorMessage = $ErrorStatus[1][0];

					trigger_error("Newegg submission error - ".$ErrorMessage. "\n" . "Type: ".$Type, E_USER_WARNING);

//error_log(print_r($ErrorStatus,1));
				}
			}
			
		} catch (InvalidArgumentException $e) {
			curl_close($ch);
			throw $e;
		} catch (Exception $e) {
			curl_close($ch);
			throw $e;
			echo "Caught exception: ". $e->getMessage() . "\n";
		}

		break;
	}
	fclose ($FH);

	// # Timed out, return error status.
	if ($Run >= 5) {
		trigger_error ('Newegg submission timed out. Type: '.$Type, E_USER_WARNING);
		return 4;
	}

	// # If Newegg is still processing.
	if (isset($Status) && $Status == 1) {
		// # Write submission ID to temp file.
		file_put_contents (FILE_SUBMIT, date ("Y-m-d H:i")."\t$Name\t$SubmitID\n", FILE_APPEND);
	}

	// Return success.
	return 0;
}

/**
 * Autoloader for Newegg API classes.
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

// # Currency conversion for Newegg currency by region.

function convert_currency($from_code, $to_code, $amount) {

	$content = file_get_contents('http://www.google.com/finance/converter?a='.$amount.'&from='.$from_code.'&to='.$to_code);

	$doc = new DOMDocument;
	libxml_use_internal_errors(true);
	@$doc->loadHTML($content);
	$xpath = new DOMXpath($doc);

	$result = $xpath->query('//*[@id="currency_converter_result"]/span')->item(0)->nodeValue;

	return str_replace(' '.$to_code, '', $result);
}

