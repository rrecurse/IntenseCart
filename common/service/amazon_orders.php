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
define ('LOCKFILE', DIR_FS_SITE."cache/orders_{$Region}.lock");
define ('FILE_RUNTIME', DIR_FS_SITE."cache/orders_{$Region}.runtime");
define ('FILE_DUMP', DIR_FS_SITE."cache/orders_{$Region}.dump");
define ('FILE_SUBMIT', DIR_FS_SITE."cache/orders_{$Region}.submit");
define ('FILE_COOKIE', DIR_FS_SITE."cache/cookie_$Region");

// # If the plugin haven't been installed yet, exit silently.
if (!is_file (DIR_FS_SITE."conf/{$FeedName}.conf")) {
    return 0;
}

// # Include the configuration and table name constants.
require_once (DIR_FS_SITE.'conf/configure.php');
require_once (DIR_FS_SITE."conf/{$FeedName}.conf");
require_once (DIR_FS_CORE."admin/includes/database_tables.php");

// # If script is already running, terminate and wait for next update.
if (is_file (LOCKFILE)) {
    return 8;
}

// # Get the timestamp from the last run, and save the current timestamp for later use.
if (!is_file (FILE_RUNTIME)) {
    $PollTime = strtotime ("-7 days");
} else {
    $PollTime = file_get_contents (FILE_RUNTIME);
}

$CurrentTime = time ();

// # Add lock file to the clients cache folder.
file_put_contents (LOCKFILE, time ());

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
    die ('Module disabled. 0 rows');
}

// # Disabled.
$Row = $Res->fetch_array ();
if ($Row['mods_enabled'] == 0) {
    die ('Module disabled');
}

if (!is_file (FILE_DUMP)) {
    // # Retrieve Amazon AWS credentials.
    $Query = 'SELECT conf_key, conf_value FROM '.TABLE_MODULE_CONFIG.' WHERE conf_module = "%s"';
    $Res = $DB->query (sprintf ($Query, $DB->real_escape_string ($FeedName)));
    if (!$Res) {
        unlink (LOCKFILE);
        trigger_error ('Could not retrieve Amazon credentials: '.$Query."\nError: ".$DB->error, E_USER_ERROR);
        return 9;
    }

    // # Add the credentials to an array for later use.
    $Creds = array ();
    while ($Row = $Res->fetch_array ()) {
        $Creds[$Row['conf_key']] = $Row['conf_value'];
    }

    // # Get locally updated orders, and send them to Amazon.
    if ($Status = Update_Amazon ($DB, $Creds, $PollTime, $Region)) {
        // # Remove lock file and return the error code.
        unlink (LOCKFILE);
echo "Could not update amazon, error code $Status\n";
        return $Status;
    }

    // # Retrieve orders from Amazon.
    if (($Status = Poll_Orders ($Region, $Creds, $PollTime)) > 0) {
echo "Could not retrieve orders from Amazon, error status: $Status\n";
        // Remove lock file and return error status.
        unlink (LOCKFILE);
        return $Status;
    } elseif ($Status < 0) {
echo "No orders found\n";
        // No new orders found.
        file_put_contents (FILE_RUNTIME, $CurrentTime);
        unlink (LOCKFILE);
        return 0;
    }
} else {
    echo "** Found dump file.\n";
}

// # Start a new session.
$DB->query ('BEGIN');

// # Add new admin session to database, to avoid login problems.
// $SessID = $Region.'_'.substr (sha1 (mcrypt_create_iv (100, MCRYPT_DEV_URANDOM)), 0, 21);
$SessID = $Region.'_'.substr (sha1 (mt_rand (1000, 99999)."a"), 0, 21);
$Query = "REPLACE INTO admin_sessions (admin_sessid, admin_user, ignore_addr, admin_addr, access_time, expire_minutes) ".
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
if (is_file (FILE_COOKIE)) {
    unlink (FILE_COOKIE);
}

// # Check for success
if (!strpos ($Res, 'Orders successfully imported')) {
    trigger_error ("Orders not sucessfully imported.\nContent returned: ".$Res, E_USER_WARNING);
} else {
    // # Import successful, unlink dump file and update runtime.
    rename(FILE_DUMP, FILE_DUMP.'.previous');
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

// # Remove.
unlink (LOCKFILE);

// # Return success
return 0;

/*
 # Retrieves new and updated orders from the database, and sends them to Amazon.
 #
 # Returns 0 on success, or an positive integer as the error code if not.
 #
 # @param object $DB
 # @param array $Creds
 # @param int $PollTime
 # @param string $Region
 # @return int
 */
function Update_Amazon (mysqli $DB, $Creds, $PollTime, $Region) {
    // # Retrieve order changes from the database.
    $Updates = array ();
    $Updates = Get_ShippedOrders ($DB, $PollTime);
    if (!is_array ($Updates)) {
        // An error occured, so exit with error code.
        return $Updates;
    }

    $RMAs = Get_RMAOrders ($DB, $PollTime, $Region);
    if (!is_array ($RMAs)) {
        return $RMAs;
    }

    $Cancels = Get_CancelledOrders ($DB, $PollTime);
    if (!is_array ($Cancels)) {
        return $Cancels;
    }

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

    // # Send RMA'd orders as 'OrderAdjustment'
    if ($Status = Update_AmazonRMA ($Service, $Creds, $RMAs, $Region)) {
        return $Status;
    }

    // Send cancelled orders as 'OrderAcknowledgement'
    if ($Status = Update_AmazonOrderStatus ($Service, $Creds, $Cancels, 'Cancellations')) {
        return $Status;
    }

    // Send shipped orders as 'OrderFulfillment'
    if ($Status = Update_AmazonShip ($Service, $Creds, $Updates)) {
        return $Status;
    }

    return 0;
}

/*
 * # Retrieves the shipped orders since $LastRun, and returns an array with the order details.
 *
 * # @param object $DB
 * # @param int $LastRun Unix timestamp
 * # @return mixed
 */
function Get_ShippedOrders ($DB, $LastRun) {
    // # Define table name variables for use in HereDOC strings.
    $TableOrders = TABLE_ORDERS;
    $TableOrdersProducts = TABLE_ORDERS_PRODUCTS;
    $TableOrdersItemsRefs = TABLE_ORDERS_ITEMS_REFS;
    $TableRefundItems = TABLE_RETURNS_PRODUCTS_DATA;
    $TableShipping = 'orders_shipped';

    // # FIXME: Define constant in database.php and use it instead.
    //$TableShipping = TABLE_SHIPPING;

    // # Create a MySQL timestamp out of last runtime.
    $LastRun = new DateTime ("@$LastRun");
    $LastRun->setTimezone (new DateTimeZone (LOCAL_TIMEZONE));
    $LastRun = $LastRun->format ("Y-m-d H:i:s");
    //error_log(print_r('from /common/service/amazon_orders - $LastRun is: ' . $LastRun . ' time now is ' . date('Y-m-d H:i:s'),TRUE));

    // # Retrieve updated orders from database.

    $Query = <<<OutSQL
SELECT oir.ref_value AS orders_id, s.ship_carrier, s.tracking_number, s.ship_date, s.ship_type, o.last_modified,
    oir.ref_refid AS item_id, op.products_quantity AS quantity
FROM $TableOrders AS o
INNER JOIN $TableOrdersProducts AS op ON op.orders_id = o.orders_id
INNER JOIN $TableOrdersItemsRefs AS oir ON oir.ref_item_id = op.orders_products_id
LEFT JOIN $TableShipping AS s ON s.orders_id = o.orders_id
LEFT JOIN $TableRefundItems AS r ON r.order_id = o.orders_id
WHERE
    o.customers_email_address LIKE "%@marketplace.amazon.%"
    AND o.orders_status = 3
    AND o.last_modified >= '$LastRun'
    AND r.refund_amount IS NULL
    AND o.orders_source NOT LIKE 'Amazon-FBA%'
ORDER BY o.orders_id, op.products_id
OutSQL;

    // Return with error code if query fails.
    if (!$Res = $DB->Query ($Query)) {
        trigger_error ("Could not retrieve order shipment updates.\nSQL: $Query\nError: ".$DB->error, E_USER_WARNING);
        return 10;
    }

    // Create a datetime object for formatting the last modified date.
    $LocalTimezone = new DateTimeZone (LOCAL_TIMEZONE);
    $LastMod = new DateTime ();
    $LastMod->setTimezone ($LocalTimezone);

    // Run through all orders, adding them to the updates array.
    $Data = array ();
    $LastOrder = $OldTrack = 0;
    while ($Row = $Res->fetch_array ()) {
        // Save the order ID for easier referencing.
        $OrderID = $Row['orders_id'];

        // Check for new order.
        if ($LastOrder != $OrderID) {
            // Remove extra whitespace from shipping method, and handle extra "unknown" values.
            $Row['ship_carrier'] = trim ($Row['ship_carrier']);
            if ($Row['ship_carrier'] != '_' && $Row['ship_carrier'] != '') {
                // Fetch the carrier name.
                $Carrier = $Row['ship_carrier'];
                $Method = 'Std Cont US Street Addr';
            } else {
                // No carrier name, attempt to determine carrier by shipping method.
                if (strpos ($Row['ship_carrier'], 'upsxml_') !== false) {
                    $Carrier = 'UPS';
                    $Method = 'UPS';;
                } elseif (trim ($Row['ship_carrier']) == 'Std Cont US Street Addr') {
                    $Carrier = 'USPS';
                    $Method = 'Std Cont Us Street Addr';
                } else {
                    $Carrier = "N/A";
                    $Method = '';
                }
            }

            // Set the modification time for formatting.
            $LastMod->setTimezone ($LocalTimezone);
            $LastMod->createFromFormat ('Y-m-d H:i:s', $Row['last_modified'], $LocalTimezone);

            // Add order details to array.
            $Data[$OrderID]['id'] = $OrderID;
            $Data[$OrderID]['date'] = $LastMod->format (DateTime::ATOM);
            $Data[$OrderID]['carrier'] = $Carrier;
            $Data[$OrderID]['method'] = $Method;

            // Set last active order to current order.
            $LastOrder = $OrderID;
            $Tracking = array ();
        }

        if ($Row['tracking_number'] != $OldTrack) {
            // Remove extra whitespace from shipping method, and handle extra "unknown" values.
            $Row['ship_carrier'] = trim ($Row['ship_carrier']);
            if ($Row['ship_carrier'] != '_' && $Row['ship_carrier'] != '') {
                // Fetch the tracking number.
                $Data[$OrderID]['tracking'][] = trim ($Row['tracking_number']);
            }

            $OldTrack = $Row['tracking_number'];
        }

        // Add item to current order.
        $Data[$OrderID]['items'][] = array ('id' => $Row['item_id'], 'quantity' => $Row['quantity']);
    }

    return $Data;
}

/**
 * Retrieves the RMAd orders since $LastRun, and returns an array of order IDs with items.
 *
 * @param object $DB
 * @param int $LastRun Unix timestamp
 * @return mixed
 */
function Get_RMAOrders ($DB, $LastRun, $Region) {

    // Define table name variables for use in HereDOC strings.
    $TableOrders = TABLE_ORDERS;
    $TableOrdersProducts = TABLE_ORDERS_PRODUCTS;
    $TableOrdersItemsRefs = TABLE_ORDERS_ITEMS_REFS;
    $TableRefundItems = TABLE_RETURNS_PRODUCTS_DATA;

    // Create a MySQL timestamp out of last runtime.
    $LastRun = new DateTime("@$LastRun");
    $LastRun->setTimezone (new DateTimeZone (LOCAL_TIMEZONE));
    $LastRun = $LastRun->format ("Y-m-d H:i:s");

    // Retrieve updated orders from database.
    $Query = <<<OutSQL
SELECT DISTINCT oir.ref_value AS orders_id, 
        rp.last_modified AS last_modified,
        r.refund_amount AS refund_amount,
        oir.ref_refid AS item_id, 
        op.products_quantity AS quantity,
        op.products_exchanged,
        r.refund_shipping_amount AS shipping,
        rp.rma_value AS rma_num
FROM $TableOrders AS o
INNER JOIN $TableOrdersProducts op ON op.orders_id = o.orders_id
INNER JOIN $TableOrdersItemsRefs oir ON oir.ref_item_id = op.orders_products_id
INNER JOIN returned_products rp ON rp.order_id = o.orders_id
INNER JOIN $TableRefundItems r ON r.order_id = o.orders_id AND r.products_id = op.products_id
INNER JOIN refund_payments pay ON pay.returns_id = rp.returns_id
WHERE
    o.customers_email_address LIKE "%@marketplace.amazon.%"
    AND (op.products_returned = 1 OR op.products_exchanged = 1)
    AND rp.returns_status = 4
    AND rp.returns_date_finished >= '{$LastRun}'
    /*  AND rp.date_purchased >=  DATE_ADD(NOW(), INTERVAL - 1 MONTH)*/ 
ORDER BY o.orders_id, op.products_id
OutSQL;

    // Return with error code if query fails.
    if (!$Res = $DB->Query ($Query)) {
        trigger_error ("Could not retrieve order shipment updates.\nSQL: $Query\nError: ".$DB->error, E_USER_WARNING);
        return 11;
    }

    // Reasons for RMA.
    $ReasonRMA = array (
                "Other",
                "NoInventory",
                "CustomerReturn",
                "GeneralAdjustment",
                "CouldNotShip",
                "DifferentItem",
                "Abandoned",
                "CustomerCancel",
                "PriceError",
                "ProductOutofStock",
                "CustomerAddressIncorrect",
                "Exchange",
                "CarrierCreditDecision",
                "RiskAssessmentInformationNotValid",
                "CarrierCoverageFailure",
                "TransactionRecord",
                "Undeliverable",
                "RefusedDelivery"
    );

    // Run through all orders, adding them to the updates array.
    $Data = array ();
    $LastOrder = 0;
 
    while ($Row = $Res->fetch_array()) {
        // # Save the order ID for easier referencing.
        $OrderID = $Row['orders_id'];

        // Check for new order.
        if ($LastOrder != $OrderID) {
            // Add order details to array.
            $Data[$OrderID]['id'] = $OrderID;
            $LastOrder = $OrderID;
        }

        // # Amazon requires price adjustment - they DO NOT do the currency conversion.
        // # We created function () using the Google Finance API
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

        if($Region != 'us') { 
            $currency_offset = convert_currency($currency, 'USD', $Row['refund_amount']);
        } else { 
            $currency_offset = number_format($Row['refund_amount'],2);
        }

        // # Define the proper refund reason.
        // # Detect products_exchanged flag from orders_products table
        $Reason = ($Row['products_exchanged'] == '1' ) ? $ReasonRMA[11] : $ReasonRMA[2];

        // # Define the shipping charges
        $shipping = $Row['shipping'];

        // # Define Internal RMA value:
        $rma_num = $Row['rma_num'];

        // # Add item to current order.
        $ItemID = $Row['item_id'];


        $Data[$OrderID]['items'][$ItemID] = array (
                'id' => $ItemID,
                'reason' => $Reason,
                'quantity' => $Row['quantity'],
                'amount' => $currency_offset,
                'shipping' => $shipping,
                'rma_num' => $rma_num
        );
    
        if($Row['products_exchanged'] == 1) {   

            // # If Amazon requires the price adjustment data, or to enable support for partial refunds.
            $Data[$OrderID]['items'][$ItemID]['rma'] = array (
                            'type' => 'Principal',
                            'currency' => $currency, 
                            'value' => $currency_offset
                            );
        } // # end exchange detection
    }

//if(!empty($Data)) error_log(print_r($Data,1), 1, 'chrisd@zwaveproducts.com', 'Subject: Data dump from amazon_orders php Get_RMAOrders function');

    return $Data;
}

/**
 * Retrieves the cancelled orders since $LastRun, and returns an array of order IDs.
 *
 * @param object $DB
 * @param int $LastRun Unix timestamp
 * @return mixed
 */
function Get_CancelledOrders (mysqli $DB, $LastRun) {
    // # Define table name variables for use in HereDOC strings.
    $TableOrders = TABLE_ORDERS;
    $TableOrdersItemsRefs = TABLE_ORDERS_ITEMS_REFS;

    // Create a MySQL timestamp out of last runtime.
    $LastRun = new DateTime("@$LastRun");
    $LastRun->setTimezone (new DateTimeZone (LOCAL_TIMEZONE));
    $LastRun = $LastRun->format ("Y-m-d H:i:s");

    // Retrieve cancelled orders from database.
    $Query = <<<OutSQL
SELECT oir.ref_value AS orders_id
FROM $TableOrders AS o
INNER JOIN $TableOrdersItemsRefs AS oir ON oir.orders_id = o.orders_id
WHERE
    o.customers_email_address LIKE "%@marketplace.amazon.%"
    AND o.orders_status = 0
    AND o.last_modified >= '$LastRun'
OutSQL;

    // Return with error code if query fails.
    if (!$Res = $DB->Query ($Query)) {
        trigger_error ("Could not retrieve order shipment updates.\nSQL: $Query\nError: ".$DB->error, E_USER_WARNING);
        return 12;
    }

    // Run through all orders, adding them to the cancels array.
    $Data = array ();
    while ($Row = $Res->fetch_array ()) {
        $Data[] = array ('order_id' => $Row['orders_id'], 'status' => "Failure");
    }

    return $Data;
}

/**
 *
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonShip ($Service, $Creds, $Data) {
    // If no updates, return successfully.
    if (empty ($Data)) {
        return 0;
    }

    $FeedTemplate = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>%1$s</MerchantIdentifier>
    </Header>
    <MessageType>OrderFulfillment</MessageType>
%2$s
</AmazonEnvelope>
';

    $OrderTemplate = '
    <Message>
        <MessageID>%6$d</MessageID>
        <OrderFulfillment>
           <AmazonOrderID>%1$s</AmazonOrderID>
           <FulfillmentDate>%2$s</FulfillmentDate>
           <FulfillmentData>
               <CarrierCode>%3$s</CarrierCode>
%4$s
           </FulfillmentData>
%5$s
        </OrderFulfillment>
    </Message>

';

    $ItemTemplate = '
            <Item>
               <AmazonOrderItemCode>%1$s</AmazonOrderItemCode>
               <Quantity>%2$d</Quantity>
            </Item>
';

    // # Template for optional tracking number.
    $TrackingTemplate = '               <ShipperTrackingNumber>%1$s</ShipperTrackingNumber>';

    $OrderData = '';
    $Run = 1;
    foreach ($Data as $Order) {
        $ItemData = $Track = '';
        foreach ($Order['items'] as $Item) {
            $ItemData .= sprintf ($ItemTemplate, $Item['id'], $Item['quantity']);
        }

        // # Add tracking number from template, if set.
        if (!empty ($Order['tracking'])) {
            foreach ($Order['tracking'] as $TrackNum) {
                $Track .= sprintf ($TrackingTemplate, $TrackNum);
            }
        }

        $OrderData .= sprintf ($OrderTemplate, $Order['id'], $Order['date'], $Order['carrier'], $Track, $ItemData, $Run++);
    }

    $Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $OrderData);
//error_log($Feed, 1, 'chrisd@zwaveproducts.com');
    // Return 0 for debug purposes.
    return Submit_Feed ('_POST_ORDER_FULFILLMENT_DATA_', $Service, $Creds, $Feed, 'Shipping');
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

    $FeedTemplate = '<?xml version="1.0"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
<Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>%1$s</MerchantIdentifier>
</Header>
<MessageType>OrderAcknowledgement</MessageType>
%2$s
</AmazonEnvelope>
';

    $OrderTemplate = '
<Message>
        <MessageID>%1$d</MessageID>
        <OrderAcknowledgement>
           <AmazonOrderID>%2$s</AmazonOrderID>
           <StatusCode>%3$s</StatusCode>
        </OrderAcknowledgement>
</Message>

';

    // Loop through all cancelled orders.
    $OrderData = '';
    $Run = 1;
    foreach ($Data as $Order) {
        // Add order ID to the feed data.
        $OrderData .= sprintf ($OrderTemplate, $Run++, $Order['order_id'], $Order['status']);
    }

    // Add merchant ID, random message ID, and all orders data to feed.
    $Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $OrderData);

    // Return 0 for debug purposes.
    return Submit_Feed ('_POST_ORDER_ACKNOWLEDGEMENT_DATA_', $Service, $Creds, $Feed, $Type);
}

/**
 *
 *
 * @param object $Service
 * @param array $Creds
 * @param array $Data
 * @return int
 */
function Update_AmazonRMA ($Service, $Creds, $Data, $Region) {

    // # If no updates, return successfully.
    if (empty ($Data)) return 0;

    // # run some last minute queries to populate data.

    $FeedTemplate = '<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>%1$s</MerchantIdentifier>
    </Header>
    <MessageType>OrderAdjustment</MessageType>
%2$s
</AmazonEnvelope>
';

    $OrderTemplate = '
    <Message>
        <MessageID>%1$d</MessageID>
        <OrderAdjustment>
            <AmazonOrderID>%2$s</AmazonOrderID>
%3$s
        </OrderAdjustment>
    </Message>%4$s
';

    $ItemTemplate = '
            <AdjustedItem>
                <AmazonOrderItemCode>%1$s</AmazonOrderItemCode>
                <MerchantAdjustmentItemID>%2$s</MerchantAdjustmentItemID>
                <AdjustmentReason>%3$s</AdjustmentReason>%4$s
';

// # TO-DO: Create logic to detect if coupon was used for this purchase.
// # if coupon was used, parse the following block with appropriate valus in sprintf below
if(isset($couponCode)) { 

    $PromotionTemplate = '
%5$s
                <PromotionAdjustments>
                    <PromotionClaimCode>%1$s</PromotionClaimCode>
                    <MerchantPromotionID>%2$s</MerchantPromotionID>
                    <Component>
                        <Type>Principal</Type>
                        <Amount currency="%4$s">%3$s</Amount>
                    </Component>
                </PromotionAdjustments>
%5$s
';

} else { 

    $PromotionTemplate = '';
}

    $PriceTemplate = '
                <ItemPriceAdjustments>
                    <Component>
                        <Type>Principal</Type>
                        <Amount currency="%6$s">%1$s</Amount>
                    </Component>
                    <Component>
                        <Type>Shipping</Type>
                        <Amount currency="%6$s">%2$s</Amount>
                    </Component>
                </ItemPriceAdjustments>'.$PromotionTemplate.'
            </AdjustedItem>
';

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

    $OrderData = '';
    $Run = 1;
    foreach ($Data as $Order) {
        $ItemData = '';
        foreach ($Order['items'] as $Item) {

            if($Region != 'us') { 
                $currency_offset = convert_currency($currency, 'USD', $Item['amount']);
            } else { 
                $currency_offset = $Item['amount'];
            }

            $ItemData .= sprintf ($ItemTemplate, $Item['id'], $Item['rma_num'], $Item['reason'], "\n");
            $ItemData .= sprintf ($PriceTemplate, $Item['amount'], $Item['shipping'], '0.00', '0.00', $Item['quantity'], $currency);
            $ItemData .= sprintf ($PromotionTemplate, '', '', '',$currency, "\n");
            // # TO-DO: create logic to pass data object coupon / promo paramters used - retrieve from Amazon (requires new logic)
            //$ItemData .= sprintf ($PromotionTemplate, $Item['coupon_code'], $Item['coupon_code'], $Item['coupon_amount'],$currency, "\n");
        }
        $OrderData .= sprintf ($OrderTemplate, $Run++, $Order['id'], $ItemData, "\n");
    }

    $Feed = sprintf ($FeedTemplate, $Creds['merchant_id'], $OrderData);
//echo "RMA:\n";
//error_log(print_r($Feed,1), 1, 'chrisd@zwaveproducts.com', 'Subject: RMA from amazon_orders');
    // # Return 0 for debug purposes.
    return Submit_Feed ('_POST_PAYMENT_ADJUSTMENT_DATA_', $Service, $Creds, $Feed, 'RMA');
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
        file_put_contents (FILE_SUBMIT, date('Y-m-d - H:i')."\t$Name\t$SubmitID\n", FILE_APPEND);
    }

    // Return success.
    return 0;
}

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
            $Response = $Service->listOrders($Request);
        } catch (Exception $ex) {
            // # If we're throttled, wait for the limit to be increased and try again.
            if ($ex->getErrorCode () == 'RequestThrottled') {
                sleep (ORDERS_TIMEOUT);
                continue;
            }

            echo ("Caught Exception: ".$ex->getMessage ()."\n");
            echo ("Response Status Code: ".$ex->getStatusCode ()."\n");
            echo ("Error Code: ".$ex->getErrorCode ()."\n");
            echo ("Error Type: ".$ex->getErrorType ()."\n");
            echo ("Request ID: ".$ex->getRequestId ()."\n");
            echo ("XML: ".$ex->getXML ()."\n");
            echo ("ResponseHeaderMetadata: ".$ex->getResponseHeaderMetadata ()."\n");
            return 10;
        }

        break;
    }

    // # Timed out, try again later.
    if ($run >= 5) {
        return 11;
    }

    // # Make sure we actually got a result from the query.
    if (!$Response->isSetListOrdersResult()) {
        // # Trigger error and return with error code 1.
        trigger_error ("Could not retrieve list of orders.", E_USER_WARNING);
        return 1;
    }

    // # Log response ID for missing MFN order problems.
    $Meta = $Response->getResponseMetadata ();
    echo date ("Y-m-d H:i:s")."\t".$Meta->getRequestId ()."\n";

    // # Retrieve list of all MFN orders.
    $Response = $Response->getListOrdersResult();

    // # If no new MFN orders, return success (-1).
    if (!$Response->isSetOrders()) {
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
        return -1;
    }

    $NewOrderData = array();

	// # limit to 30 loops to conserve system resources
	// # added array_slice() inside foreach loop with range limit
	// # uncomment the foreach(array_slice line when importing past days when API is down
	//foreach(array_slice($OrderData, 0, 75) as $Order) { 

    foreach ($OrderData as $Order){
        /** @var MarketplaceWebServiceOrders_Model_Order $Order */
        $FulfillmentChannel = $Order->getFulfillmentChannel();
        $Status = $Order->getOrderStatus();

		// # grab orders from both MFN and AFN/FBA

        if ($FulfillmentChannel == 'MFN' && $Status == 'Unshipped'){
            $NewOrderData[] = $Order;

        } else if ($FulfillmentChannel === 'AFN' && $Status == 'Shipped'){

            $NewOrderData[] = $Order;
        }

    }

    $OrderData = $NewOrderData;

    // Retrieve details for each individual order.
    $Data = $OrderImport = array ();
    foreach ($OrderData as $Order) {
        // # Create shipping address variable for easier referencing.
        $ShippingAddress = $Order->getShippingAddress ();

        // # Retrieve items for each individual order.
        $ItemData = Get_ItemData ($Order->getAmazonOrderId (), $Creds, $Service);
        echo " ** Order ID: ".$Order->getAmazonOrderId ()."\n";

        // # Handle errors.
        if (!is_array ($ItemData)) {
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

            // # Replace The Amazon shipping method names with generic, easier to understand names.
            $ShipReplace = array (
                "Std Cont US Street Addr" => "Standard Ground",
				"Std APO/FPO Street Addr" => "APO/FPO - Armed Forces",
                "Exp Cont US Street Addr" => "Expedited Delivery",
            );

            $ShippingMethod = $Order->getShipServiceLevel ();
            $ShippingMethod = str_replace (array_keys ($ShipReplace), array_values ($ShipReplace), $ShippingMethod);

            // # Add always-set fields to temp array.
            if ($Channel === 'AFN'){

                $orderSource = 'Amazon-FBA_'.strtoupper($Region);

            } else {

                $orderSource = 'dbfeed_amazon_'. $Region;
            }

            $Status = $Order->getOrderStatus();

            $StatusIdMap = array('Shipped' => 3, 'Unshipped' => 1);

            $Temp = array (
                "order-id" => $Order->getAmazonOrderId (),
                "order-status" => $StatusIdMap[$Status],
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
                $Temp['currency'] = $ItemPrice->getCurrencyCode();

                // Amazon API hack: ItemPrice is actually total amount, divide by
                // quantity ordered to get the correct price.
                $Temp['item-price'] = $ItemPrice->getAmount() / $Item->getQuantityOrdered();
            } else {
                $Temp["currency"] = 'USD';
                $Temp["item-price"] = '0.00';
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

            $Temp["recipient-first-name"] = $Temp["recipient-last-name"] = $Temp["buyer-phone-number"] = $Temp["ship-phone-number"]
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

            // # Add temp array to export data.
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

    // # Select the correct Amazon service URL to use for feed submission
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

    // # Connnect to Amazon.
    $Config = array (
            'ServiceURL' => $ServiceURL,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3);

    $Service = new MarketplaceWebService_Client ($Creds['access_key'], $Creds['secret_key'],
            $Config, APPLICATION_NAME, APPLICATION_VERSION);

    // # Mark orders as imported in Amazon MWS.
    if ($Status = Update_AmazonOrderStatus ($Service, $Creds, $OrderImport, 'Acknowledgements')) {
        return $Status;
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
            $Response = $Service->listOrderItems ($Request);

            if (!$Response->isSetListOrderItemsResult ()) {
                trigger_error ('Could not retrieve item list for order', E_USER_WARNING);
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
                $Extra = Get_NextItems ($NextToken, $Creds, $Service);

                // Handle errors.
                if (!is_array ($Extra)) {
                    return $Extra;
                }

                $Data = array_merge ($Data, $Extra);
            }

            return $Data;
        } catch (MarketplaceWebServiceOrders_Exception $ex) {
            // If we're throttled, wait for the limit to be increased and try again.
            if ($ex->getErrorCode () == 'RequestThrottled') {
                sleep (ITEMS_TIMEOUT);
                continue;
            }

            echo ("Caught Exception: ".$ex->getMessage ()."\n");
            echo ("Response Status Code: ".$ex->getStatusCode ()."\n");
            echo ("Error Code: ".$ex->getErrorCode ()."\n");
            echo ("Error Type: ".$ex->getErrorType ()."\n");
            echo ("Request ID: ".$ex->getRequestId ()."\n");
            echo ("XML: ".$ex->getXML ()."\n");
            echo ("ResponseHeaderMetadata: ".$ex->getResponseHeaderMetadata ()."\n");
            return 10;
        }

        break;
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
            // If we're throttled, wait for the limit to be increased and try again.
            if ($ex->getErrorCode () == 'RequestThrottled') {
                sleep (ITEMS_TIMEOUT);
                continue;
            }

            echo ("Caught Exception: ".$ex->getMessage ()."\n");
            echo ("Response Status Code: ".$ex->getStatusCode ()."\n");
            echo ("Error Code: ".$ex->getErrorCode ()."\n");
            echo ("Error Type: ".$ex->getErrorType ()."\n");
            echo ("Request ID: ".$ex->getRequestId ()."\n");
            echo ("XML: ".$ex->getXML ()."\n");
            echo ("ResponseHeaderMetadata: ".$ex->getResponseHeaderMetadata ()."\n");
            return 10;
        }

        break;
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
            // If we're throttled, wait for the limit to be increased and try again.
            if ($ex->getErrorCode () == 'RequestThrottled') {
                sleep (ORDERS_TIMEOUT);
                continue;
            }

            echo ("Caught Exception: ".$ex->getMessage ()."\n");
            echo ("Response Status Code: ".$ex->getStatusCode ()."\n");
            echo ("Error Code: ".$ex->getErrorCode ()."\n");
            echo ("Error Type: ".$ex->getErrorType ()."\n");
            echo ("Request ID: ".$ex->getRequestId ()."\n");
            echo ("XML: ".$ex->getXML ()."\n");
            echo ("ResponseHeaderMetadata: ".$ex->getResponseHeaderMetadata ()."\n");
            return 10;
        }

        break;
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

// # Currency conversion for Amazon currency by region.

function convert_currency($from_code, $to_code, $amount) {

	$content = file_get_contents('http://www.google.com/finance/converter?a='.$amount.'&from='.$from_code.'&to='.$to_code);

	$doc = new DOMDocument;
	libxml_use_internal_errors(true);
	@$doc->loadHTML($content);
	$xpath = new DOMXpath($doc);

	$nodelist = $xpath->query('//*[@id="currency_converter_result"]/span');

	if($nodelist->length) {
		// # a DOMNodelist has a length-property
		$result = $nodelist->item(0)->nodeValue;
	} else {
		$result = $amount;
	}


	return str_replace(' '.$to_code, '', $result);
}


