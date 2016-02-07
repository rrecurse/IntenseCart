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
//error_log(print_r('from /common/service/amazon_inventory - time now is ' . date('Y-m-d H:i:s'),1)); 

// # Add the path to the IX common classes to PHP's include path.
define ('DIR_FS_CORE', '/usr/share/IXcore/');
set_include_path (get_include_path ().PATH_SEPARATOR.DIR_FS_CORE.'common/classes');

// # Ensure that this script doesn't time out.
set_time_limit(0);

// # Set the polling limits as defined by the Amazon API.
define("SUBMIT_MAX", 15);
define('SUBMIT_TIMEOUT', 2*60); // 1 new request per 2 minutes.

// # Set application name and version for Amazon API.
define('APPLICATION_NAME', "IntenseCart Amazon API plugin");
define('APPLICATION_VERSION', '1.0');

// # Retrieve and validate the region.
$Region = $_SERVER['argv'][2];
$FeedName = 'dbfeed_amazon_'.$Region;

// # Define paths for later use.
define('DIR_FS_SITE', $_SERVER['argv'][1]);
define('LOCKFILE', DIR_FS_SITE."cache/inventory_{$Region}.lock");
define('FILE_RUNTIME', DIR_FS_SITE."cache/inventory_{$Region}.runtime");
define('FILE_DUMP', DIR_FS_SITE."cache/inventory_{$Region}.dump");
define('FILE_SUBMIT', DIR_FS_SITE."cache/inventory_{$Region}.submit");

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

// # Retrieve Amazon credentials from database.
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

// # Retrieve Amazon AWS credentials.
$Query = 'SELECT conf_key, conf_value FROM '.TABLE_MODULE_CONFIG.' WHERE conf_module = "%s"';
$Res = $DB->query (sprintf ($Query, $DB->real_escape_string ($FeedName)));
if ($DB->errno) {
	unlink (LOCKFILE);
	trigger_error ("Could not retrieve Amazon credentials: ".$Query."\nError: ".$DB->error, E_USER_ERROR);
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

// # Send data to Amazon, and leave the dump file on disk if errorstatus > 0.
if (!empty($Data) && $Res = Update_Amazon($Data, $Creds, $PollTime, $Region)) {
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
	$Query = "SELECT
			/* General data */
			p.products_id,
			p.products_sku,
			pe.extra_value AS amazon_sku,
			p.products_model,
			p.products_upc,

			/* New products data */
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

			/* Products image data */
			p.products_image,

			/* Products pricing data */
			p.products_price,

			/* Inventory availability data */
			p.products_quantity AS quantity,

			/* Products overrides data */
			p.products_free_shipping,
			pe2.extra_value AS shipping_rate,
			pe3.extra_value AS amazon_surcharge

		FROM " . TABLE_PRODUCTS . " AS p
		INNER JOIN ".TABLE_DBFEEDS_PRODUCTS." AS dbp
				ON dbp.products_id = p.products_id
				AND dbp.dbfeed_class = '$FeedName'
		LEFT JOIN ".TABLE_DBFEEDS_PROD_EXTRA." AS pe
				ON pe.products_id = p.products_id
				AND pe.dbfeed_class = '$FeedName'
				AND pe.extra_field = 'sku'
		INNER JOIN
			" . TABLE_PRODUCTS_DESCRIPTION . " AS pd
				ON pd.products_id = p.master_products_id
				AND pd.language_id = 1
		INNER JOIN
			" . TABLE_MANUFACTURERS . " AS m
				ON m.manufacturers_id = p.manufacturers_id
		LEFT JOIN
			" . TABLE_DBFEEDS_PROD_EXTRA . " AS pe2
				ON pe2.products_id = p.products_id
				AND pe2.dbfeed_class = '$FeedName'
				AND pe2.extra_field = 'shipping_cost'
		LEFT JOIN
			" . TABLE_DBFEEDS_PROD_EXTRA . " AS pe3
				ON pe3.products_id = p.products_id
				AND pe3.dbfeed_class = '$FeedName'
				AND pe3.extra_field = 'amazon_surcharge'
		WHERE (p.products_last_modified >= '$LastRun' OR p.products_date_added >= '$LastRun' OR
			   p.last_stock_change >= '$LastRun') AND p.products_sku != '' AND p.products_sku IS NOT NULL
		GROUP BY p.products_id";


	// # Amazon requires price adjustment - they DO NOT do the currency conversion.
	// # We created function convert_currency() using the Google Finance API
	// # hopefully there isnt any noticable downtime with google it this doesnt fail.

	// # Canada
	if ($Region == 'ca') {
		$currency = 'CAD';
	// # Europe
	} elseif($Region == 'de' || $Region == 'es' || $Region == 'fr' || $Region == 'it') {
		$currency = 'EUR';
	// # UK
	} elseif($Region == 'uk') {
		$currency = 'GBP';
	// # Japan
	} elseif($Region == 'jp') {
    	$currency = 'JPY';
	// # India
	} elseif($Region == 'in') {
		$currency = 'INR';
	// # China
	} elseif($Region == 'cn') {
 		$currency = 'CNY';
 	// # United states
	} else { 
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

	$Data = array ('new' => array (), 'images' => array (), 'price' => array (), 'stock' => array (), 'shipping' => array ());

	while ($Row = $Res->fetch_assoc ()) {
		// Set the correct SKU for the product, or use model if no SKU is found.
		if (!empty ($Row['amazon_sku'])) {
			$SKU = $Row['amazon_sku'];
		} elseif (empty ($Row['amazon_sku']) && !empty ($Row['products_sku'])) {
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
			$shipping_option = 'Std Cont US Street Addr';
		} elseif($Region == 'ca') { 
			$shipping_option = 'Std CA Dom';
		} else { 
			$shipping_option = 'Std Cont US Street Addr';
		}

		if($Region == 'us') { 

			// # Add free shipping flags, keep it US only for now.
			// # TODO: If free shipping for other regions is desired, change this.
			if ($Row['products_free_shipping'] == '1' && $Row['shipping_rate'] < 0.01) {

				$Data['shipping'][$SKU][] = array (
						'sku' => $SKU,
						'option' => 'Std Cont US Street Addr',
						'type' => 'Exclusive',
						'price'	=> 0,
						'currency' => 'USD'
				);
	
			} elseif(!empty($Row['shipping_rate']) && $Row['shipping_rate'] > 0) {
	
				$Data['shipping'][$SKU][] = array(
					'sku' => $SKU,
					'option' => 'Std Cont US Street Addr',
					'type' => 'Exclusive',
					'price' => $currency_offset,
					'currency' => $currency
				);
			}

		} else { // # Region does != us

			$Data['shipping'][$SKU][] = array(
				  'sku' => $SKU,
				  'option' => $shipping_option,
				  'type' => 'Additive',
				  'price' => $currency_offset,
				  'currency' => $currency
				);
		}

		// # If we have a surcharge, add it to the price.
		if ($Row['amazon_surcharge']) {
			$Row['products_price'] += round($Row['amazon_surcharge'],2);
		}

		// # If the product has been added since last run, add it to the product feed data.
		if ($Row['products_date_added'] >= $LastRun || empty($Row['amazon_sku'])) {
			// # If no Amazon SKU is set, add it.
			if (!empty ($Row['amazon_sku'])) {
				$Query = "INSERT IGNORE INTO ".TABLE_DBFEEDS_PROD_EXTRA." VALUES ('%s', %d, 'sku', '%s')";
				$Query = sprintf ($Query, $FeedName, $Row['products_id'], $DB->real_escape_string ($SKU));
				if (!$Temp = $DB->Query ($Query)) {
					trigger_error ("Could not add Amazon SKU.\nSQL: $Query\nError: ".$DB->error, E_USER_WARNING);
					return 9;
				}
			}

			// # PATCH: To work around the missing search terms == fiery feed death.
			if (empty ($Row['searchterms'])) {
				$Row['searchterms'] = $Row['products_name'];
			}

			// Make sure keywords are no more than 50 characters long (Amazon limitation).
			// fix this crap to put one keyword per line at 50 chars max, not the entire keyword array!
			if (($Length = strlen ($Row['searchterms'])) > 50) {
				if ($Pos = strrpos ($Row['searchterms'], ",", 50-$Length)) {
					$Row['searchterms'] = trim (substr ($Row['searchterms'], 0, $Pos), " ,");
				} else {
					$Row['searchterms'] = substr ($Row['searchterms'], 0, strrpos ($Row['searchterms'], " ", 50-$Length));
				}
			}

			// Please make sure these items are added in the same order as they'll appear in the feed.
			$Data['new'][] = array (
				'sku' => $SKU,
				'tax-code' => $Row['products_tax_class_id'],
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
				'id_value' => $Row['products_upc']
			);

			// Add an image to the feed, if it exists.
			if (!empty ($Row['products_image']) && is_file (DIR_FS_CATALOG_IMAGES."/{$Row['products_image']}")) {
				$Data['images'][] = array (
					'sku' => $SKU,
					'type' => 'Main',
					'url' => 'http://'.SITE_DOMAIN.'/images/'.$Row['products_image']
				);
			}
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
//error_log(print_r('/common/service/amazon_inventory.php - no entry in warehouse table | products_id - ' .$Row['products_id'] . ' | products_quantity - ' .$Row['quantity'], 1));
			}

		} else { // # multi-warehousing is off

			$products_quantity = ($Row['quantity'] > 0 ? $Row['quantity'] : '0'); 

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
		$Data['stock'][] = array ('sku' => $SKU, 'quantity' => $products_quantity);
		$Data['price'][] = array ('sku' => $SKU, 'price' => $currency_offset);

	}
	return $Data;
}

/**
 * Creates and sends the product details feeds to amazon.
 *
 * Returns 0 on success, or an positive integer as the error code if not.
 *
 * @param array $Data
 * @param array $Creds
 * @param int $LastRun
 * @param string $Region
 * @return int
 */
function Update_Amazon($Data, $Creds, $LastRun, $Region) {

	// # Select the correct Amazon service URL to use.
	if ($Region == 'ca') {
		$ServiceURL =  "https://mws.amazonservices.ca";
	} elseif($Region == 'de' || $Region == 'es' || $Region == 'fr' || $Region == 'it' || $Region == 'uk') {
		$ServiceURL =  "https://mws-eu.amazonservices.com";
	} elseif($Region == 'jp') {
		$ServiceURL =  "https://mws.amazonservices.jp";
	} elseif($Region == 'in') {
		$ServiceURL =  "https://mws.amazonservices.in";
	} elseif($Region == 'cn') {
		$ServiceURL =  "https://mws.amazonservices.com.cn";
	} else { 
		$ServiceURL =  "https://mws.amazonservices.com";
	}

	// Connnect to Amazon.
	$Config = array (
			'ServiceURL' => $ServiceURL,
			'ProxyHost' => null,
			'ProxyPort' => -1,
			'MaxErrorRetry' => 3);
	$Service = new MarketplaceWebService_Client ($Creds['access_key'], $Creds['secret_key'],
			$Config, APPLICATION_NAME, APPLICATION_VERSION);

	// # Create and send new products feed.
	if ($Status = Update_AmazonProducts ($Service, $Creds, $Data['new'])) {
		return $Status;
	}

	// # Create and send image feed for new products
	if ($Status = Update_AmazonImages ($Service, $Creds, $Data['images'])) {
		return $Status;
	}

	// # Create and send price feed for changed products
	if ($Status = Update_AmazonPrices ($Service, $Creds, $Data['price'])) {
		return $Status;
	}

	// # Create and send (free) shipping details for products.
	if ($Status = Update_AmazonShipping ($Service, $Creds, $Data['shipping'])) {
		return $Status;
	}

	// # Create and send new inventory feed for changed products.
	if ($Status = Update_AmazonInventory ($Service, $Creds, $Data['stock'])) {
		return $Status;
	}

	return 0;
}

/**
 * Creates and sends the new products feed to Amazon.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonProducts ($Service, $Creds, $Data) {
	// If no updates, return successfully.
	if (empty ($Data)) {
		return 0;
	}

	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>%1\$s</MerchantIdentifier>
	</Header>
	<MessageType>Product</MessageType>
%2\$s
</AmazonEnvelope>
EOD;

	$ProductTemplate = <<<EOD
	<Message>
		<MessageID>%1\$d</MessageID>
		<Product>
			<SKU>%2\$s</SKU>
			<StandardProductID>
				<Type>%15\$s</Type>
				<Value>%16\$s</Value>
			</StandardProductID>
			<ProductTaxCode>%3\$d</ProductTaxCode>
			<DescriptionData>
				<Title>%4\$s</Title>
				<Description>%5\$s</Description>
				<ItemDimensions>
					<Length unitOfMeasure="%13\$s">%8\$s</Length>
					<Width unitOfMeasure="%13\$s">%9\$s</Width>
					<Height unitOfMeasure="%13\$s">%10\$s</Height>
					<Weight unitOfMeasure="%14\$s">%7\$s</Weight>
				</ItemDimensions>
				<Manufacturer>%6\$s</Manufacturer>
				<SearchTerms>%11\$s</SearchTerms>
			</DescriptionData>
			<ProductData>
				<CE>
					<ProductType>
						<%12\$s>
						</%12\$s>
					</ProductType>
				</CE>
			</ProductData>
		</Product>
	</Message>

EOD;

	$ProductData = '';
	$Run = 1;
	foreach ($Data as $Product) {
		array_unshift ($Product, $Run++);
		$ProductData .= vsprintf ($ProductTemplate, $Product);
	}

	$Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $ProductData);

	// Submit feed and return success status.
	return Submit_Feed ('_POST_PRODUCT_DATA_', $Service, $Creds, $Feed, 'Product');;
}

/**
 * Creates and sends the product images feed.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonImages ($Service, $Creds, $Data) {
	// If no updates, return successfully.
	if (empty ($Data)) {
		return 0;
	}

	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>%1\$s</MerchantIdentifier>
	</Header>
	<MessageType>ProductImage</MessageType>
%2\$s
</AmazonEnvelope>
EOD;

	$ImageTemplate = <<<EOD
	<Message>
		<MessageID>%1\$d</MessageID>
		<OperationType>Update</OperationType>
		<ProductImage>
			<SKU>%2\$s</SKU>
			<ImageType>%3\$s</ImageType>
			<ImageLocation>%4\$s</ImageLocation>
		</ProductImage>
	</Message>

EOD;

	$ImageData = '';
	$Run = 1;
	foreach ($Data as $Product) {
		$ImageData .= sprintf ($ImageTemplate, $Run++, $Product['sku'], $Product['type'], $Product['url']);
	}

	$Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $ImageData);

	// Submit feed and return success status.
	return Submit_Feed ('_POST_PRODUCT_IMAGE_DATA_', $Service, $Creds, $Feed, 'images');;
}

/**
 * Creates and send the updated product price feed to Amazon.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonPrices ($Service, $Creds, $Data) {
	// If no updates, return successfully.
	if (empty ($Data)) {
		return 0;
	}

	$Region = $_SERVER['argv'][2];

	// # Amazon requires price adjustment - they DO NOT do the currency conversion.
	// # We created function convert_currency() using the Google Finance API
	// # hopefully there isnt any noticable downtime with google it this doesnt fail.

	// # Canada
	if ($Region == 'ca') {
		$currency = 'CAD';
	// # Europe
	} elseif($Region == 'de' || $Region == 'es' || $Region == 'fr' || $Region == 'it') {
		$currency = 'EUR';
	// # UK
	} elseif($Region == 'uk') {
		$currency = 'GBP';
	// # Japan
	} elseif($Region == 'jp') {
    	$currency = 'JPY';
	// # India
	} elseif($Region == 'in') {
		$currency = 'INR';
	// # China
	} elseif($Region == 'cn') {
 		$currency = 'CNY';
 	// # United states
	} else { 
		$currency = 'USD';
	}


	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>%1\$s</MerchantIdentifier>
	</Header>
	<MessageType>Price</MessageType>
%2\$s
</AmazonEnvelope>
EOD;

	$PriceTemplate = <<<EOD
	<Message>
		<MessageID>%1\$d</MessageID>
		<Price>
			<SKU>%2\$s</SKU>
			<StandardPrice currency="%4\$s">%3\$s</StandardPrice>
		</Price>
	</Message>

EOD;

	$PriceData = '';
	$Run = 1;

	foreach ($Data as $Product) {
 
		$theRate = (float)preg_replace('/(\.\d\d).*/', '$1', $Product['price']);

		if($Region != 'us') { 
			$currency_offset = convert_currency('USD', $currency, $theRate);
		} else { 
			$currency_offset = $theRate;
		}

		$PriceData .= sprintf ($PriceTemplate, $Run++, $Product['sku'], $currency_offset, $currency);
	}

	$Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $PriceData);

	// Submit feed and return success status.
	return Submit_Feed ('_POST_PRODUCT_PRICING_DATA_', $Service, $Creds, $Feed, 'prices');;
}

/**
 * Creates and send the updated shipping price feed to Amazon.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonShipping ($Service, $Creds, $Data) {
	// # If no updates, return successfully.
	if(empty($Data)) {
		return 0;
	}

	$Region = $_SERVER['argv'][2];

	// # Amazon requires price adjustment - they DO NOT do the currency conversion.
	// # We created function convert_currency() using the Google Finance API
	// # hopefully there isnt any noticable downtime with google it this doesnt fail.

	// # Canada
	if ($Region == 'ca') {
		$currency = 'CAD';
	// # Europe
	} elseif($Region == 'de' || $Region == 'es' || $Region == 'fr' || $Region == 'it') {
		$currency = 'EUR';
	// # UK
	} elseif($Region == 'uk') {
		$currency = 'GBP';
	// # Japan
	} elseif($Region == 'jp') {
    	$currency = 'JPY';
	// # India
	} elseif($Region == 'in') {
		$currency = 'INR';
	// # China
	} elseif($Region == 'cn') {
 		$currency = 'CNY';
 	// # United states
	} else { 
		$currency = 'USD';
	}


	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>%1\$s</MerchantIdentifier>
	</Header>
	<MessageType>Override</MessageType>
%2\$s
</AmazonEnvelope>
EOD;

	$ProductTemplate = <<<EOD
	<Message>
		<MessageID>%1\$d</MessageID>
		<Override>
			<SKU>%2\$s</SKU>
%3\$s
		</Override>
	</Message>

EOD;

	$ShippingTemplate = <<<EOD
			<ShippingOverride>
				<ShipOption>%1\$s</ShipOption>
				<Type>%2\$s</Type>
				<ShipAmount currency="%4\$s">%3\$s</ShipAmount>
			</ShippingOverride>

EOD;

	$PriceData = '';
	$Run = 1;
	foreach ($Data as $SKU => $Product) {

		$ShippingData = '';

		foreach ($Product as $Ship) {

		// # strip the rate down to two decimal places - round() and number_format() did not produce wanted result
		$theRate = (float)preg_replace('/(\.\d\d).*/', '$1', $Ship['price']);

			// # if the $Region is not the US and the shipping amount is greater then zero
			//# trigger currency conversion using the google currency converter API (function at bottom)
            if($Region != 'us' && $Ship['price'] > 0) { 
                $currency_offset = convert_currency('USD', $currency, $theRate);
            } else { 
                $currency_offset = $theRate;
            }

			$ShippingData .= sprintf ($ShippingTemplate, $Ship['option'], $Ship['type'], $currency_offset, $currency);
		}

		$PriceData .= sprintf ($ProductTemplate, $Run++, $SKU, $ShippingData);
	}

	$Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $PriceData);

	// # Submit feed and return success status.
	return Submit_Feed ('_POST_PRODUCT_OVERRIDES_DATA_', $Service, $Creds, $Feed, 'shipping');;
}

/**
 * Creates and sends the updated inventory feed to Amazon.
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonInventory ($Service, $Creds, $Data) {
	// # If no updates, return successfully.
	if (empty ($Data)) {
		trigger_error('/common/service/amazon_inventory.php $Data is empty inside Update_AmazonInventory() function');
		return 0;
	}

	$FeedTemplate = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>%1\$s</MerchantIdentifier>
	</Header>
	<MessageType>Inventory</MessageType>
%2\$s
</AmazonEnvelope>
EOD;

	$InventoryTemplate = <<<EOD
	<Message>
		<MessageID>%1\$d</MessageID>
		<OperationType>Update</OperationType>
		<Inventory>
			<SKU>%2\$s</SKU>
			<Quantity>%3\$d</Quantity>
		</Inventory>
	</Message>

EOD;

	$StockData = '';
	$Run = 1;
	foreach ($Data as $Product) {
		array_unshift ($Product, $Run++);
		$products_quantity = (int)($Product['quantity'] > 0) ? $Product['quantity'] : '0';
		$StockData .= sprintf ($InventoryTemplate, $Run++, $Product['sku'], $products_quantity);
	}

	$Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $StockData);

	// # Submit feed and return success status.
	return Submit_Feed ('_POST_INVENTORY_AVAILABILITY_DATA_', $Service, $Creds, $Feed, 'inventory');;
}

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
	// # Store feed in memory for the Amazon API.

//error_log('/common/service/amazon_inventory.php - Submit_Feed() function');
//error_log(print_r($Feed,1));

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

	// # Send feed to Amazon.
	for ($Run = 0; $Run < 5; $Run++) {
		try {
			$Response = $Service->submitFeed($Request);

			if (!$Response->isSetSubmitFeedResult()) {
				// # Sleep for timeout, and try again.
				sleep (SUBMIT_TIMEOUT);
				continue;
			}

			$Response = $Response->getSubmitFeedResult();

			if (!$Response->isSetFeedSubmissionInfo()) {
				// # Sleep for timeout, and try again.
				sleep (SUBMIT_TIMEOUT);
				continue;
			}

			$Response = $Response->getFeedSubmissionInfo();

			if ($Response->isSetFeedSubmissionId ()) {
				$SubmitID = $Response->getFeedSubmissionId();
			}

			if ($Response->isSetFeedProcessingStatus ()) {
				$Status = $Response->getFeedProcessingStatus ();
			}

		} catch (MarketplaceWebService_Exception $ex) {
			// # If we're throttled, wait for the limit to be increased and try again.
			if ($ex->getErrorCode() == 'RequestThrottled') {
				sleep (SUBMIT_TIMEOUT);
				continue;
			}

//error_log(print_r($Response,1));
error_log(print_r($ex,1));

//			echo ("Caught Exception: " . $ex->getMessage() . "\n");
//			echo ("Response Status Code: " . $ex->getStatusCode () . "\n");
//			echo ("Error Code: " . $ex->getErrorCode() . "\n");
//			echo ("Error Type: " . $ex->getErrorType() . "\n");
//			echo ("Request ID: " . $ex->getRequestId() . "\n");
//			echo ("XML: " . $ex->getXML () . "\n");
//			echo ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata () . "\n");
			fclose ($FH);

			$ErrMsg = "Caught exception: ".$ex->getMessage(). "\nCode # ". $ex->getErrorCode() . "\nType: ".$ex->getErrorType();
			trigger_error($ErrMsg, E_USER_WARNING);
			return 10;
		}

		break;
	}
	fclose ($FH);

	// Timed out, return error status.
	if ($Run >= 5) {
		trigger_error ('Amazon submission timed out. Type: '.$Type, E_USER_WARNING);
		return 4;
	}

	// If Amazon is still processing.
	if ($Status == '_SUBMITTED_') {
		// # Write submission ID to temp file.
		file_put_contents (FILE_SUBMIT, date ("Y-m-d H:i")."\t". $Name . "\t". $SubmitID ."\n", FILE_APPEND);
	}

	// Return success.
	return 0;
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

// # Currency conversion for Amazon currency by region.

function convert_currency($from_code, $to_code, $amount) {

	$content = file_get_contents('http://www.google.com/finance/converter?a='.$amount.'&from='.$from_code.'&to='.$to_code);

	$doc = new DOMDocument;
	libxml_use_internal_errors(true);
	@$doc->loadHTML($content);
	$xpath = new DOMXpath($doc);

	$result = $xpath->query('//*[@id="currency_converter_result"]/span')->item(0)->nodeValue;

	return str_replace(' '.$to_code, '', $result);
}

