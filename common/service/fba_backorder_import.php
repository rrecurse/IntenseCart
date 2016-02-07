<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

// Include the custom error handler.
require_once ("errorhandler.php");

// Define default timezone.
define ('LOCAL_TIMEZONE', date_default_timezone_get());

// # using UTC or GMT places current time 4 hours ahead
// # GMT seems to produce more accurate time stamps
//date_default_timezone_set ('UTC');
//date_default_timezone_set ('GMT');
// # you may validate by using:
// # error_log(print_r('from /common/service/amazon_inventory - time now is ' . date('Y-m-d H:i:s'),TRUE));

// # Add the path to the IX common classes to PHP's include path.
define ('DIR_FS_CORE', '/usr/share/IXcore/');
set_include_path (get_include_path ().PATH_SEPARATOR.DIR_FS_CORE.'common/classes');

// # Ensure that this script doesn't time out.
set_time_limit (0);

// # Set application name and version for Amazon API.
define ('APPLICATION_NAME', "IntenseCart Amazon API plugin");
define ('APPLICATION_VERSION', '1.0');

// # Set the polling limits as defined by the Amazon API.
define ('ORDERS_MAX', 6);
define ('ORDERS_TIMEOUT', 1*60); // 1 new request per minute.
define ('ITEMS_MAX', 30);
define ('ITEMS_TIMEOUT', 2); // 1 new request per 2 seconds.
define ("SUBMIT_MAX", 15);
define ('SUBMIT_TIMEOUT', 2*60); // 1 new request per 2 minutes.

// # Retrieve and validate the region.
$Region = $_SERVER['argv'][2];
$FeedName = 'dbfeed_amazon_'.$Region;

// # Define paths for later use.
define ('DIR_FS_SITE', $_SERVER['argv'][1]);
define ('LOCKFILE', DIR_FS_SITE."cache/fba_orders_{$Region}.lock");
define ('FILE_RUNTIME', DIR_FS_SITE."cache/fba_orders_{$Region}.runtime");
define ('FILE_DUMP', DIR_FS_SITE."cache/fba_orders_{$Region}.dump");
define ('FILE_SUBMIT', DIR_FS_SITE."cache/fba_orders_{$Region}.submit");
define ('FILE_COOKIE', DIR_FS_SITE."cache/fba_cookie_$Region");

// # If the plugin haven't been installed yet, exit silently.
if (!is_file (DIR_FS_SITE."conf/{$FeedName}.conf")) {
    clog("Could not find conf file\n");
    exit(0);
}

// # Include the configuration and table name constants.
require_once (DIR_FS_SITE.'conf/configure.php');
require_once (DIR_FS_SITE."conf/{$FeedName}.conf");
require_once (DIR_FS_CORE."admin/includes/database_tables.php");

// # If script is already running, terminate and wait for next update.
if (is_file (LOCKFILE)) {
    clog("Lock file found at %s.  Stopping.\n", LOCKFILE);
    exit(8);
}

touch(LOCKFILE);
register_shutdown_function(function(){
    if (file_exists(LOCKFILE)){
        unlink(LOCKFILE);
    }
});

// # Retrieve Amazon credentials from database.
$DB = new mysqli ('127.0.0.1', DB_SERVER_USERNAME, base64_decode(DB_PASSWORD), DB_DATABASE);
if ($DB->errno) {
    clog("Could not connect to the database. Error: %s\n", $DB->error);
    exit(9);
}

// # Get the status of the module.
$Query = "SELECT mods_enabled FROM module_sets WHERE mods_module = '%s'";
$Query = sprintf ($Query, $DB->real_escape_string ($FeedName));
if (!$Res = $DB->query ($Query)) {
    clog("Error when checking module status.\nSQL: %s\nError: %s\n", $Query, $DB->error);
    exit(9);
}

// # Either not installed, or purged.
if ($Res->num_rows == 0) {
    clog('Module disabled. 0 rows');
    exit(0);
}

// # Disabled.
$Row = $Res->fetch_array ();
if ($Row['mods_enabled'] == 0) {
    clog('Module disabled');
    exit(0);
}

if (!is_file (FILE_DUMP)) {
    // # Retrieve Amazon AWS credentials.
    $Query = 'SELECT conf_key, conf_value FROM '.TABLE_MODULE_CONFIG.' WHERE conf_module = "%s"';
    $Res = $DB->query (sprintf ($Query, $DB->real_escape_string ($FeedName)));
    if (!$Res) {
        clog("Could not retrieve Amazon credentials: %s\nError: %s\n", $Query, $DB->error);
        exit(9);
    }

    // # Add the credentials to an array for later use.
    $Creds = array ();
    while ($Row = $Res->fetch_array ()) {
        $Creds[$Row['conf_key']] = $Row['conf_value'];
    }

    $PollTime = new DateTime('2014-6-1');
    $PollTime = $PollTime->getTimestamp();
    // # Retrieve orders from Amazon.
    if (($Status = Poll_Orders ($Region, $Creds, $PollTime)) > 0) {
        clog("Could not retrieve orders from Amazon, error status: %d\n", $Status);
        // Remove lock file and return error status.
        exit($Status);
    } elseif ($Status < 0) {
        clog("No orders found\n");
        // No new orders found.
        exit(0);
    }
} else {
    clog("** Found already existing dump file.\n");
}

/*

// # Start a new session.
$DB->query ('BEGIN');

// # Add new admin session to database, to avoid login problems.
// $SessID = $Region.'_'.substr (sha1 (mcrypt_create_iv (100, MCRYPT_DEV_URANDOM)), 0, 21);
$SessID = $Region.'_'.substr (sha1 (mt_rand (1000, 99999)."a"), 0, 21);
$Query = "INSERT IGNORE  INTO admin_sessions (admin_sessid, admin_user, ignore_addr, admin_addr, access_time, expire_minutes) ".
        "VALUES ('%s', '%s', 1, '127.0.0.1', NOW(), 1)";
$Query = sprintf ($Query, $DB->real_escape_string ($SessID), $DB->real_escape_string ($FeedName));
if (!$DB->query ($Query)) {
    unlink (LOCKFILE);
    trigger_error ("Could not create new admin session.\nQuery: $Query\nError: ".$DB->error, E_USER_ERROR);
    return 9;
}
$Query = sprintf ("INSERT IGNORE INTO ".TABLE_ADMIN_PERMISSIONS." VALUES ('%s', 'ALL')", $DB->real_escape_string ($FeedName));
if (!$DB->query ($Query)) {
        unlink (LOCKFILE);
    $Error = $DB->error;
    $DB->query ('ROLLBACK');
        trigger_error ("Could not add to admin group.\nQuery: $Query\nError: ".$Error, E_USER_ERROR);
        return 9;
}
$DB->query ('COMMIT');

// URL to order import form.
$URI = 'http://'.SITE_DOMAIN.'/admin/import_orders.php';

// Create cURL object.
$Upload = curl_init ($URI);


// # Configure cURL for retrieving all necessary cookies.
curl_setopt ($Upload, CURLOPT_HEADER, true);
curl_setopt ($Upload, CURLOPT_VERBOSE, false);
curl_setopt ($Upload, CURLOPT_RETURNTRANSFER, true);
curl_setopt ($Upload, CURLOPT_COOKIESESSION, true);
curl_setopt ($Upload, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
curl_setopt ($Upload, CURLOPT_COOKIEFILE, FILE_COOKIE);
curl_setopt ($Upload, CURLOPT_COOKIEJAR, FILE_COOKIE);
curl_setopt ($Upload, CURLOPT_FOLLOWLOCATION, true);
curl_setopt ($Upload, CURLOPT_COOKIE, "admin_sessid=$SessID; admin_user=$FeedName; IXAdminID=BloodyIXadminID");

// # get all cookies set properly.
curl_exec ($Upload);

// # Define the POST parameters that the import_orders.php file expects.
// # fix this to be more detective of the Amazon AFN / FBA region. name profile in db to Amazon.$Region-FBA and detect for '-FBA'
// # added additional crontab under user apache with region us-fba to compensate.
$Post = array (
    "csv_file" => "@".FILE_DUMP,
    'profile_import' => 'Amazon'.strtoupper($Region),
    'imp_action' => 'Import'
);

// # Set up the proper upload session.
curl_setopt ($Upload, CURLOPT_POST, true);
curl_setopt ($Upload, CURLOPT_POSTFIELDS, $Post);

// # Send upload request.
$Res = curl_exec ($Upload);

curl_close ($Upload);
*/

if (is_file (FILE_COOKIE)) {
    unlink (FILE_COOKIE);
}
/*
// # Check for success, though I suspect this measure of "success" is rather vague.
if (!strpos ($Res, 'Orders successfully imported')) {
    trigger_error ("Orders not sucessfully imported.\nContent returned: ".$Res, E_USER_WARNING);
} else {
    // Import successful, unlink dump file and update runtime.
    unlink (FILE_DUMP);
    file_put_contents (FILE_RUNTIME, $CurrentTime);
}


// # Delete admin session.
$Query = "DELETE FROM admin_sessions WHERE admin_sessid = ''";
$Query = sprintf ($Query, $DB->real_escape_string ($SessID));
if (!$DB->query ($Query)) {
    trigger_error ("Could not delete admin session. Error: ".$DB->error, E_USER_WARNING);
}
$Query = sprintf ("DELETE FROM ".TABLE_ADMIN_PERMISSIONS." WHERE admin_user='%s'", $DB->real_escape_string ($FeedName));
if (!$DB->Query ($Query)) {
        trigger_error ("Coult not remove from admin group.\nQuery: $Query\nError: ".$DB->error, E_USER_WARNING);
}
*/
// # Remove.

// # Return success
exit(0);

/**
 * Checks for new orders from Amazon since last runtime.

 * EXPANDED TO INCLUDE AFN / FBA ORDERS VIA REPORTS CLASSES - 11/2014
 *
 * @param string $Region
 * @param array $Creds
 * @param int $PollTime
 * @return mixed
 */

function Poll_Orders ($Region, $Creds, $PollTime) {

    // # Select the correct service URL to use.
    // # Canada
    if ($Region == 'ca') {
        $ServiceURL = "https://mws.amazonservices.ca/Orders/2011-01-01";
    // # Europe
    } elseif($Region == 'de' || $Region == 'es' || $Region == 'fr' || $Region == 'it' || $Region == 'uk') {
        $ServiceURL = "https://mws-eu.amazonservices.com/Orders/2011-01-01";
    // # Japan
    } elseif($Region == 'jp') {
        $ServiceURL = "https://mws.amazonservices.jp/Orders/2011-01-01";
    // # India
    } elseif($Region == 'in') {
        $ServiceURL = "https://mws.amazonservices.in/Orders/2011-01-01";
    // # China
    } elseif($Region == 'cn') {
        $ServiceURL = "https://mws.amazonservices.com.cn/Orders/2011-01-01";
    // # United states
    } else {
        $ServiceURL = "https://mws.amazonservices.com/Orders/2011-01-01";
    }

    // # Work around to get around Amazon's "last_updated" confusion.
    // # Every 4 hours, look back 4 hours to ensure all orders are fetched.
    if (date ('H') % 4 == 0 && date ('i') == 0) {
        $PollTime = strtotime ("-4 hours", $PollTime);
    }

    // # Connnect to Amazon.
    $Config = array (
            'ServiceURL' => $ServiceURL,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3);

    $Service = new MarketplaceWebServiceOrders_Client ($Creds['access_key'], $Creds['secret_key'], APPLICATION_NAME, APPLICATION_VERSION, $Config);

    // # Set up the request to fetch new orders.
    $Request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest ();
    $Request->setSellerId ($Creds['merchant_id']);

    // # List all orders udpated after last run.
    $PollTime = new DateTime ('@'.$PollTime);
    $Request->setLastUpdatedAfter ($PollTime);

    // # Set the marketplaces queried in this ListOrdersRequest
    $Markets = new MarketplaceWebServiceOrders_Model_MarketplaceIdList ();
    $Markets->setId (array ($Creds['marketplace_id']));
    $Request->setMarketplaceId ($Markets);

    // # Retrieve the list of orders.
    for ($run = 0; $run < 5; $run++) {
        try {
            clog("Sending initial request for orders.\n");
            $Response = $Service->listOrders($Request);
            break;
        } catch (Exception $ex) {
            // If we're throttled, wait for the limit to be increased and try again.
            if ($ex->getErrorCode () == 'RequestThrottled') {
                clog("Script has been throttled.  Waiting for %d seconds before trying again.\n", ORDERS_TIMEOUT);
                sleep (ORDERS_TIMEOUT);
                continue;
            }
            
            clog("Caught Exception: %s\n", $ex->getMessage ());
            clog("Response Status Code: %s\n", $ex->getStatusCode ());
            clog("Error Code: %s\n", $ex->getErrorCode ());
            clog("Error Type: %s\n", $ex->getErrorType ());
            clog("Request ID: %s\n", $ex->getRequestId ());
            clog("XML: %s\n", $ex->getXML ());
            clog("ResponseHeaderMetadata: %s\n", $ex->getResponseHeaderMetadata ());
            return 10;
        }
    }

    // # Timed out, try again later.
    if ($run >= 5) {
        clog("Timed out while trying to send initital listOrders request.\n");
        return 11;
    }

    // # Make sure we actually got a result from the query.
    if (!$Response->isSetListOrdersResult()) {
        // # Trigger error and return with error code 1.
        clog("Could not retrieve list of orders.\n");
        return 1;
    }

    // # Log response ID for missing MFN order problems.
    $Meta = $Response->getResponseMetadata ();
    clog("Requst ID %s\n", $Meta->getRequestId ());

    // # Retrieve list of all MFN orders.
    $Response = $Response->getListOrdersResult();

    // # If no new MFN orders, return success (-1).
    if (!$Response->isSetOrders()) {
        clog("No orders\n");
        return -1;
    }

    // # Retrieve orders.
    $OrderData = array ();
    foreach ($Response->getOrders()->getOrder() as $Order) {
        $OrderData[] = $Order;
    }

    // # Check for additional orders.
    $NextToken = $Response->getNextToken ();
    while (!empty ($NextToken)) {
        clog("Getting more orders via next token: %s\n", $NextToken);
        $Extra = Get_NextOrders ($NextToken, $Creds, $Service);

        // # Handle errors.
        if (!is_array ($Extra)) {
            return $Extra;
        }

        // # Add additional orders to data array.
        $OrderData = array_merge ($OrderData, $Extra);
    }

    // # If no new orders, return success (-1).
    if (empty ($OrderData)) {
        clog("No orders found\n");
        return -1;
    }

    $NewOrderData = array();
    $totalOrders = count($total);
    $lastPercent = 0;
    foreach ($OrderData as $idx=>$Order){
        $percent = $idx/$total*100;
        if ($percent - $lastPercent > 1){
            clog("Categorizing orders by AFN vs MFN... %0.1f%% complete\r", $percent);
            $lastPercent = $percent;
        }

        /** @var MarketplaceWebServiceOrders_Model_Order $Order */
        $FulfillmentChannel = $Order->getFulfillmentChannel();
        $Status = $Order->getOrderStatus();
        if ($FulfillmentChannel === 'AFN' && $Status == 'Shipped'){
            $NewOrderData[] = $Order;
        }
    }
    clog("\n");
    $OrderData = $NewOrderData;

    if (empty($OrderData)){
        clog("No AFN orders found.");
        return -1;
    }

    // Retrieve details for each individual order.
    $Data = $OrderImport = array ();
    foreach ($OrderData as $Order) {
        // # Create shipping address variable for easier referencing.
        $ShippingAddress = $Order->getShippingAddress ();

        // # Retrieve items for each individual order.
        clog("*Order ID: %s\n", $Order->getAmazonOrderId ());
        clog("**Getting Item data\n");
        $ItemData = Get_ItemData ($Order->getAmazonOrderId (), $Creds, $Service);

        // # Handle errors.
        if (!is_array ($ItemData)) {
            clog("***Failed to get item data\n");
            return $ItemData;
        }

        // Add each item to the order data array.
        foreach ($ItemData as $Item) {
            /** @var MarketplaceWebServiceOrders_Model_OrderItem $Item */
            // Retrieve order objects.
            $ItemPrice = $Item->getItemPrice ();
            $ItemTax = $Item->getItemTax ();
            $ItemShipPrice = $Item->getShippingPrice ();
            $ItemShipTax = $Item->getShippingTax ();

            // Add order to acknowledgement feed.
            $Channel = $Order->getFulfillmentChannel();
            if ($Channel == 'MFN'){
                $OrderImport[] = array ('order_id' => $Order->getAmazonOrderId (), 'status' => 'Success');
            }

            // Split the first and last name of the buyer.
            $BuyerName = $Order->getBuyerName ();
            if (preg_match ('/^(\S+)\s+(.*)/', $BuyerName, $Match)) {
                $BuyerName = array ($Match[1], $Match[2]);
            } else {
                $BuyerName = array ($BuyerName, '');
            }

            // Replace The Amazon shipping method names with generic, easier to understand names.
            $ShipReplace = array (
                "Std Cont US Street Addr" => "Standard Ground",
                "Exp Cont US Street Addr" => "Expedited",
            );
            $ShippingMethod = $Order->getShipServiceLevel ();
            $ShippingMethod = str_replace (array_keys ($ShipReplace), array_values ($ShipReplace), $ShippingMethod);

            // Add always-set fields to temp array.
            if ($Channel === 'AFN'){
                $orderSource = 'Amazon-FBA';
            }
            else {
                $orderSource = "dbfeed_amazon_" . $Region;
            }

            $Temp = array (
                "order-id" => $Order->getAmazonOrderId (),
                "order-status" => $Order->getOrderStatus(),
                "purchase-date" => $Order->getPurchaseDate (),
                "payments-date" => $Order->getPurchaseDate (),
                "buyer-email" => $Order->getBuyerEmail (),
                "buyer-first-name" => $BuyerName[0],
                "buyer-last-name" => $BuyerName[1],
                "order-item-id" => $Item->getOrderItemId (),
                "sku" => $Item->getSellerSKU (),
                "asin" => $Item->getASIN (),
                "product-name" => $Item->getTitle (),
                "quantity-purchased" => $Item->getQuantityOrdered (),
                "payment-method" => "payment_amazonSeller",
                "ship-service-level" => $ShippingMethod,
                "orders_source" => $orderSource,
                'fulfillment-center-id' => 1
            );

            // # Add potential fields to temp array.
            if ($ItemPrice instanceof MarketplaceWebServiceOrders_Model_Money) {
                $Temp['currency'] = $ItemPrice->getCurrencyCode ();

                // Amazon API hack: ItemPrice is actually total amount, divide by
                // quantity ordered to get the correct price.
                $Temp['item-price'] = $ItemPrice->getAmount () / $Item->getQuantityOrdered ();
                //$Temp['item-price'] = $Item->getItemPrice ()->getAmount ();
            } else {
                $Temp["currency"] = 'USD';
                $Temp["item-price"] = '0';
            }

            if ($ItemTax instanceof MarketplaceWebServiceOrders_Model_Money) {
                $Temp['item-tax'] = $ItemTax->getAmount();
            } else {
                $Temp["item-tax"] = '';
            }
            if ($ItemShipPrice instanceof MarketplaceWebServiceOrders_Model_Money) {
                $Temp['shipping-price'] = $ItemShipPrice->getAmount ();
            } else {
                $Temp["shipping-price"] = '';
            }
            if ($ItemShipTax instanceof MarketplaceWebServiceOrders_Model_Money) {
                $Temp['shipping-tax'] = $ItemShipTax->getAmount ();
            } else {
                $Temp["shipping-tax"] = '';
            }

            $Temp["recipient-fist-name"] = $Temp["recipient-last-name"] = $Temp["buyer-phone-number"] = $Temp["ship-phone-number"]
                = $Temp['billing-address-1'] = $Temp["ship-address-1"] = $Temp['billing-address-2'] = $Temp["ship-address-2"]
                = $Temp['billing-address-3'] = $Temp["ship-address-3"] = $Temp['billing-city'] = $Temp["ship-city"]
                = $Temp['billing-postal-code'] = $Temp["ship-postal-code"] = $Temp['billing-state'] = $Temp["ship-state"]
                = $Temp['billing-country'] = $Temp["ship-country"]
                = ''
            ;
            if ($ShippingAddress instanceof MarketplaceWebServiceOrders_Model_Address) {
                if ($ShippingAddress->isSetName()){
                    $Name = $ShippingAddress->getName ();
                    if (preg_match ('/^(\S+)\s+(.*)/', $Name, $Match)) {
                        $Name =  array ($Match[1], $Match[2]);
                    } else {
                        $Name = array ($Name, '');
                    }
                    $Temp['recipient-first-name'] = $Name[0];
                    $Temp['recipient-last-name'] = $Name[1];
                }
                if ($ShippingAddress->isSetPhone()){
                    $Temp['ship-phone-number'] = $Temp['buyer-phone-number'] = $ShippingAddress->getPhone ();
                }
                if ($ShippingAddress->isSetAddressLine1()) {
                    $Temp['billing-address-1'] = $Temp["ship-address-1"] = $ShippingAddress->getAddressLine1 ();
                }
                if ($ShippingAddress->isSetAddressLine2 ()) {
                    $Temp['billing-address-2'] = $Temp["ship-address-2"] = $ShippingAddress->getAddressLine2 ();
                }
                if ($ShippingAddress->isSetAddressLine3 ()) {
                    $Temp['billing-address-3'] = $Temp["ship-address-3"] = $ShippingAddress->getAddressLine3 ();
                }
                if ($ShippingAddress->isSetCity ()) {
                    $Temp['billing-city'] = $Temp["ship-city"] = $ShippingAddress->getCity();
                }
                if ($ShippingAddress->isSetPostalCode ()) {
                    $Temp['billing-postal-code'] = $Temp["ship-postal-code"] = $ShippingAddress->getPostalCode ();
                }
                if ($ShippingAddress->isSetStateOrRegion ()) {
                    $Temp['billing-state'] = $Temp["ship-state"] = $ShippingAddress->getStateOrRegion ();
                }
                if ($ShippingAddress->isSetCountryCode ()) {
                    $Temp['billing-country'] = $Temp["ship-country"] = $ShippingAddress->getCountryCode ();
                }
            }

            $Temp["item-promotion-discount"] = '';
            if ($Item->isSetPromotionDiscount() && ($Discount=$Item->getPromotionDiscount()) && $Discount instanceof MarketplaceWebServiceOrders_Model_Money){
                if ($Discount->isSetAmount()) {
                    $Temp["item-promotion-discount"] = $Discount->getAmount();
                }
            }

            $Temp["ship-promotion-discount"] = '';
            if ($Item->isSetShippingDiscount () && ($Discount=$Item->getShippingDiscount()) && $Discount instanceof MarketplaceWebServiceOrders_Model_Money){
                if ($Discount->isSetAmount()) {
                    $Temp["ship-promotion-discount"] = $Discount->getAmount();
                }
            }

            // Add temp array to export data.
            $Data[] = $Temp;
        }
    }

    // # If no data has been found, return OK status.
    if (empty ($Data)) {
        return -1;
    }

    // # Add the headers to the output.
    $Headers = array_keys ($Data[0]);
    array_unshift ($Data, $Headers);

    // # Create TAB-delimited list of items.
    foreach ($Data as &$Line) {
        $Line = implode("\t", $Line);
    }

    // # Write to dump CSV file, and return success.
    unset ($Line);
    $Data = implode ("\n", $Data);
    if (!file_put_contents (FILE_DUMP, $Data)) {
        trigger_error ("Could not write dump file", E_USER_ERROR);
        return 6;
    }

    return 0;
}

/**
 *
 *
 * @param string $OrderID
 * @param array $Creds
 * @param object $Service
 * @return mixed
 */
function Get_ItemData ($OrderID, $Creds, $Service) {
    $Request = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest ();
    $Request->setSellerId ($Creds['merchant_id']);
    $Request->setAmazonOrderId ($OrderID);

    for ($run = 0; $run < 5; $run++) {
        try {
            clog("*** Sending listOrderItems request\n");
            $Response = $Service->listOrderItems ($Request);

            if (!$Response->isSetListOrderItemsResult ()) {
                clog("Could not retrieve item list for order\n");
                return 3;
            }

            $Response = $Response->getListOrderItemsResult ();
            if ($Response->isSetNextToken ()) {
                $NextToken = $Response->getNextToken ();
            }

            if (!$Response->isSetOrderItems ()) {
                return array ();
            }

            foreach ($Response->getOrderItems ()->getOrderItem () as $Line) {
                $Data[] = $Line;
            }

            while (!empty ($NextToken)) {
                clog("*** Getting more items from next token %s\n", $NextToken);
                $Extra = Get_NextItems ($NextToken, $Creds, $Service);

                // Handle errors.
                if (!is_array ($Extra)) {
                    return $Extra;
                }

                $Data = array_merge ($Data, $Extra);
            }

            return $Data;
        } catch (MarketplaceWebServiceOrders_Exception $ex) {
            if ($ex->getErrorCode () == 'RequestThrottled') {
                clog("*** Throttled. Waiting %s seconds\n", ITEMS_TIMEOUT);
                sleep (ITEMS_TIMEOUT);
                continue;
            }

            clog("Caught Exception: %s\n", $ex->getMessage ());
            clog("Response Status Code: %s\n", $ex->getStatusCode ());
            clog("Error Code: %s\n", $ex->getErrorCode ());
            clog("Error Type: %s\n", $ex->getErrorType ());
            clog("Request ID: %s\n", $ex->getRequestId ());
            clog("XML: %s\n", $ex->getXML ());
            clog("ResponseHeaderMetadata: %s\n", $ex->getResponseHeaderMetadata ());
            return 10;
        }
    }

    // Timed out, try again later.
    return 11;
}

/**
 *
 *
 * @param string $Token
 * @param array $Creds
 * @param object $Service
 * @return mixed
 */
function Get_NextItems ($Token, $Creds, $Service) {
    $Request = new MarketplaceWebServiceOrders_Model_ListOrderItemsByNextTokenRequest ();
    $Request->setSellerId ($Creds['merchant_id']);
    $Request->setNextToken ($Token);

    for ($run = 0; $run < 5; $run++) {
        try {
            $Response = $Service->listOrderItemsByNextToken ($Request);

            if (!$Response->isSetListOrderItemsByNextTokenResult ()) {
                trigger_error ('Could not retrieve list of extra items', E_USER_WARNING);
                return 4;
            }

            $Response = $Response->getListOrderItemsByNextTokenResult ();
            if ($Response->isSetNextToken ()) {
                $Token = $Response->getNextToken ();
            } else {
                $Token = '';
            }

            if (!$Response->isSetOrderItems ()) {
                return array ();
            }

            foreach ($Response->getOrderItems ()->getOrderItem () as $Line) {
                $Data[] = $Line;
            }

            return $Data;
        } catch (MarketplaceWebServiceOrders_Exception $ex) {
            if ($ex->getErrorCode () == 'RequestThrottled') {
                clog("*** Throttled. Waiting %s seconds\n", ITEMS_TIMEOUT);
                sleep (ITEMS_TIMEOUT);
                continue;
            }

            clog("Caught Exception: %s\n", $ex->getMessage ());
            clog("Response Status Code: %s\n", $ex->getStatusCode ());
            clog("Error Code: %s\n", $ex->getErrorCode ());
            clog("Error Type: %s\n", $ex->getErrorType ());
            clog("Request ID: %s\n", $ex->getRequestId ());
            clog("XML: %s\n", $ex->getXML ());
            clog("ResponseHeaderMetadata: %s\n", $ex->getResponseHeaderMetadata ());
            return 10;
        }
    }

    // Timed out, try again later.
    return 11;
}

/**
 *
 *
 * @param string $Token
 * @param array $Creds
 * @param object $Service
 * @return mixed
 */
function Get_NextOrders (&$Token, $Creds, $Service) {
    // Define the request for the next page of orders.
    $Request = new MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest ();
    $Request->setSellerId ($Creds['merchant_id']);
    $Request->setNextToken ($Token);

    // Try to wait at most 5 times.
    for ($run = 0; $run < 5; $run++) {
        try {
            $Response = $Service->listOrdersByNextToken ($Request);
            if (!$Response->isSetListOrdersByNextTokenResult ()) {
                trigger_error ('Could not retrieve additional orders.', E_USER_WARNING);
                return 2;
            }

            $Response = $Response->getListOrdersByNextTokenResult ();

            if ($Response->isSetNextToken ()) {
                $Token = $Response->getNextToken ();
            } else {
                $Token = '';
            }

            if (!$Response->isSetOrders ()) {
                return array ();
            }

            $Data = array ();
            foreach ($Response->getOrders ()->getOrder () as $Line) {
                $Data[] = $Line;
            }

            return $Data;
        } catch (MarketplaceWebServiceOrders_Exception $ex) {
            if ($ex->getErrorCode () == 'RequestThrottled') {
                clog("*** Throttled. Waiting %s seconds\n", ORDERS_TIMEOUT);
                sleep (ORDERS_TIMEOUT);
                continue;
            }

            clog("Caught Exception: %s\n", $ex->getMessage ());
            clog("Response Status Code: %s\n", $ex->getStatusCode ());
            clog("Error Code: %s\n", $ex->getErrorCode ());
            clog("Error Type: %s\n", $ex->getErrorType ());
            clog("Request ID: %s\n", $ex->getRequestId ());
            clog("XML: %s\n", $ex->getXML ());
            clog("ResponseHeaderMetadata: %s\n", $ex->getResponseHeaderMetadata ());
            return 10;
        }
    }

    // Timed out, try again later.
    return 11;
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

function clog(/*$fmt, ...*/){
    static $stderr = null;
    if (!$stderr){
        $stderr = fopen('php://stderr', 'w');
    }

    $args = func_get_args();
    $fmt = array_shift($args);

    $fmt = "[%s] ".$fmt;
    $now = new DateTime();
    array_unshift($args, $now->format('Y-m-d H:i:s'));
    $msg = vsprintf($fmt, $args);
    fwrite($stderr, $msg);
}

/**
 * Currency conversion for Amazon currency by region.
 **/

function convert_currency($amount, $from_code, $to_code){

    $googleAPI = 'https://www.google.com/ig/calculator?hl=en&q=' . $amount . $from_code . '=?' . $to_code;

    $response = file_get_contents($googleAPI);
    $result = explode('"', $response);

    $array_result = $result['3'];
    $result = preg_replace("/[^0-9\.]/", '', number_format($array_result,2));
    return $result;
}

