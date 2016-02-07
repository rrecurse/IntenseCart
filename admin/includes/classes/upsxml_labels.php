<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

// # reference: https://www.ups.com/gec/techdocs/pdf/Shipping_Pkg_Gnd.zip

// # Begin UPS XML Label generation Class

class UPSLabelGen {
	var $shipTo, $street_address, $city, $zip, $phone, $preferred_method;


	// # __construct function
	// # $orders_id - the order id 
	// # $method - array of shipping method and shipping zone
	// # $supplier_id - the supplier as assigned by product
	// # $options - array of additional shipping options like delivery confirmation, insurance etc.

	function __construct($orders_id, $method, $suppliers_id, $options='', $dimensions='') { 

		// # To-Do: Add CONSTANT checks and set defaults if not set

		$this->logfile = '/home/zwave/logs/upsxml.log';	
		$this->merchantEmail = STORE_OWNER_EMAIL_ADDRESS;

    	$this->access_key = MODULE_SHIPPING_UPSXML_RATES_ACCESS_KEY;
		$this->access_username = MODULE_SHIPPING_UPSXML_RATES_USERNAME;
		$this->access_password = MODULE_SHIPPING_UPSXML_RATES_PASSWORD;

		// # To-do: Grab warehouse location from db and assign appropriate UPS account number and shipping location
		$supplier_info_query = tep_db_query("SELECT * FROM suppliers s WHERE s.suppliers_id = '" . $suppliers_id . "'");
		$supplier_info = tep_db_fetch_array($supplier_info_query);

		// # Alternative the Shipper Account number depending on shipping warehouse
		if(is_null($supplier_info['suppliers_shipper_account']) || $supplier_info['suppliers_shipper_account'] == '') {
			$this->access_account_number = MODULE_SHIPPING_UPSXML_RATES_UPS_ACCOUNT_NUMBER;
		} elseif((!is_null($supplier_info['suppliers_shipper_account']) || $supplier_info['suppliers_shipper_account'] != '') && $supplier_info['suppliers_default'] == '1') { 
			$this->access_account_number = $supplier_info['suppliers_shipper_account'];
		} else {
		$this->access_account_number = MODULE_SHIPPING_UPSXML_RATES_UPS_ACCOUNT_NUMBER;
		}

		$this->shipperInfo_address1 = $supplier_info['suppliers_address1'];
		$this->shipperInfo_address2 = ($supplier_info['suppliers_address2']) ? "\n" .'<AddressLine2>'.$supplier_info['suppliers_address2'].'</AddressLine2>' : '';
        $this->shipperInfo_address3 = ($supplier_info['suppliers_address3']) ? "\n" . '<AddressLine3>'.$supplier_info['suppliers_address3'].'</AddressLine3>' : '';

		$this->shipperInfo_city = ($supplier_info['suppliers_city']) ? $supplier_info['suppliers_city'] : MODULE_SHIPPING_UPSXML_RATES_CITY;
		$this->shipperInfo_zip = ($supplier_info['suppliers_zip']) ? $supplier_info['suppliers_zip'] : MODULE_SHIPPING_UPSXML_RATES_POSTALCODE;
		$this->shipperInfo_state = ($supplier_info['suppliers_state']) ? $supplier_info['suppliers_state'] : MODULE_SHIPPING_UPSXML_RATES_STATEPROV;
		$this->shipperInfo_country = ($supplier_info['suppliers_country']) ? $supplier_info['suppliers_country'] : MODULE_SHIPPING_UPSXML_RATES_COUNTRY;

		$this->shipperInfo_phone = ($supplier_info['suppliers_phone']) ? $supplier_info['suppliers_phone'] : '';
		$this->tax_id = ($supplier_info['suppliers_taxid']) ? $supplier_info['suppliers_taxid'] : '';
		

		// # Grab the customer order details from the orders table based on the orders_id passed.
		$order_query = tep_db_query("SELECT o.customers_name, 
											o.customers_company,
											o.delivery_name,
											o.delivery_street_address,
											o.delivery_suburb,
											o.delivery_city,
											o.delivery_postcode,
											o.delivery_state,
											o.delivery_country,
											o.customers_telephone,
											o.shipping_method,
											o.customers_email_address,
											o.comments,
											ot.value AS order_total
									  FROM " . TABLE_ORDERS . " o
									  LEFT JOIN " . TABLE_ORDERS_TOTAL . " ot ON (ot.orders_id = o.orders_id AND ot.class = 'ot_total')
									  WHERE o.orders_id = '" . $orders_id . "'
									 ");

		$order = tep_db_fetch_array($order_query);

		$this->customer_name = str_replace('&', ' ', $order['customers_name']);
		$this->delivery_name = str_replace('&', ' ', $order['delivery_name']);
		$this->company = ($order['customers_company'] ? str_replace('&', ' ', $order['customers_company']) : '-');
		$this->street_address = str_replace('&', ' ', $order['delivery_street_address']);
		$this->street_address2 = ($order['delivery_suburb']) ? "\n" .'<AddressLine2>'.str_replace('&', ' ', $order['delivery_suburb']).'</AddressLine2>' : '';

		$this->city = $order['delivery_city'];

		// # Perform country query to grab country code (full name not accepted)
		$country_query = tep_db_query("SELECT countries_iso_code_2 AS country_code FROM " . TABLE_COUNTRIES . " WHERE countries_name LIKE '".$order['delivery_country']."' LIMIT 1");
		$country = tep_db_fetch_array($country_query);
		$this->country = $country['country_code'];

		// # Perform state query to grab state code (full name not accepted)
		$state_query = tep_db_query("SELECT zone_code FROM " . TABLE_ZONES . " WHERE zone_name LIKE '".$order['delivery_state']."' LIMIT 1");
		$state = tep_db_fetch_array($state_query);

		$this->state = $state['zone_code'];

		$this->zip = $order['delivery_postcode'];

		if($this->shipperInfo_country == $this->country) {

			// # if US based scrub postal-4 from the code in case it's present
			$this->zip = (strlen($this->zip) > 5) ? substr($this->zip,0,5) : $this->zip;
		}
	

		// # sanitize phone number, including removing periods / dots and other illegal chars
		// # also remove country US pre-fix and any characters to avoid UPS error 120217 - ShipTo phone number and phone extension together cannot be more than 15 digits long
		$this->phone = preg_replace('/[^0-9]/i', '', $order['customers_telephone']);

		// # detect phone number length - if less then 10 digits, pass a blank value (to avoid UPS error code 120213
		$this->phone = (strlen($this->phone) > 9 ? $this->phone : '');

		$this->preferred_method = $order['shipping_method'];

		$this->customer_email = $order['customers_email_address'];

		// # Grab the package weight from the orders_products table and combine into one weight (for single package).
		// # To-Do: expand on logic to include multiple packages. Will require rewriting query below.

		$package_info_query = tep_db_query("SELECT COALESCE(SUM(op.products_weight * op.products_quantity),0) AS products_weight,
												   COALESCE(TRUNCATE(SUM(op.final_price * op.products_quantity),2), 0.00) AS products_value,	
												   p.products_separate_shipping
									 		FROM ". TABLE_ORDERS ." o
											LEFT JOIN ". TABLE_ORDERS_PRODUCTS ." op ON op.orders_id = o.orders_id
											LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_id = op.products_id
											WHERE o.orders_id = '" . $orders_id . "'
										 	");


		$package_info = tep_db_fetch_array($package_info_query);


		// # check if its a residential delivery
		$this->isResidential = (!$order['customers_company']) ? "\n" . '<ResidentialAddress/>' : '';

		$defaultPackageWeight = (number_format((float)$package_info['products_weight'],2) + SHIPPING_BOX_WEIGHT);

		$this->package_weight = (!empty($method[1]) ? $method[1] : $defaultPackageWeight);
		$this->package_weight = number_format((float)$this->package_weight,2);

		$this->shipment_partialFull = ((float)$this->package_weight >= $defaultPackageWeight) ? 'Full' : 'Partial';
		
		$this->unit_length = (defined('SHIPPING_UNIT_LENGTH')) ? SHIPPING_UNIT_LENGTH : 'IN';
		$this->package_unitof = (defined('SHIPPING_UNIT_WEIGHT')) ? SHIPPING_UNIT_WEIGHT : 'LBS';

		$this->currency = (MODULE_SHIPPING_UPSXML_CURRENCY_CODE) ? MODULE_SHIPPING_UPSXML_CURRENCY_CODE : 'USD';

		// # grab the order totals for international shipments
		$this->order_subtotal = number_format($package_info['products_value'], 2);
		$this->order_total = number_format($order['order_total'], 2);

		// # 35 char limit, so we need to decipher a way to dynamically build this, unless we have some static value
		$this->description_of_goods = 'Z-Wave Wireless Network Equipment';

		$this->seperateBox = ($package_info['products_separate_shipping'] == '1') ? "\n" . '<LargePackageIndicator/>' : '';
		$this->additional_handling = ($package_info['products_separate_handling']) ? "\n" . '<AdditionalHandling>'.$package_info['products_separate_handling'].'</AdditionalHandling>' : '';

		$this->shipMethod = str_replace('upsxml_UPS ', '', $method[0]);

		// # RESOLVED! - on /admin/upsxml_labels.php there is mapping logic which HAD 'Expedited' (for Amazon) in the str_replace array. This removed ALL instanced of Expedited and broke shipping codes with 'Expedited' in them
		// # Mmay as well leave this code here just in case. Feel free to remove since we revised 'Expedited' to import as 'Expedited Delivery'

		if($this->shipMethod == 'Worldwide 3 Day Select' || $this->shipMethod == 'Worldwide' || $this->shipMethod == 'Worldwide Saver' || $this->shipMethod == 'Worldwide Expedited Saver') {
			$this->shipMethod = str_replace(array('Worldwide 3 Day Select', 'Worldwide', 'Worldwide Saver', 'Worldwide Expedited Saver'), 'Worldwide Expedited', $this->shipMethod);
		}

		// # get the label format for desired printer
		// # to-do: pass in the value dynamically
		// # $labelformat = strtoupper('zpl');

		$labelformat = strtoupper($options[3]);
		$this->LabelPrintMethod = $labelformat;
		$this->LabelImageFormat = $labelformat;
		$this->LabelImageHeight = '4';
		$this->LabelImageWidth = '6';
		
		// # Service codes

		$this->service_codes = array('01' => 'Next Day Air',
									 '02' => '2nd Day Air',
									 '03' => 'Ground',
									 '07' => 'Worldwide Express',
									 '08' => 'Worldwide Expedited',
									 '11' => 'Standard',
									 '12' => '3 Day Select',
									 '13' => 'Next Day Air Saver',
									 '14' => 'Next Day Air Early AM',
									 '54' => 'Express Plus',
									 '59' => '2nd Day Air A.M.',
									 '65' => 'Saver',
									 'M2' => 'First Class Mail',
									 'M3' => 'Priority Mail',
									 'M4' => 'Expedited Mail Innovations',
									 'M5' => 'Priority Mail Innovations',
									 'M6' => 'Economy Mail Innovations',
									 '82' => 'Today Standard',
									 '83' => 'Today Dedicated Courier',
									 '84' => 'Today Intercity',
									 '85' => 'Today Express',
									 '86' => 'Today Express Saver',
									 '96' => 'Worldwide Express Freight',
									 // # Added SurePost compatibility 12/15/2014
									 '92' => 'SurePost',
									 '93' => 'SurePost',
									 '94' => 'SurePost BPM',
									 '95' => 'SurePost Media'
									 );

		// # END service codes

		$this->serviceCode = array_search($this->shipMethod, $this->service_codes);

		// # non-standard package options - to be set with each Label

		// # set delivery confirmation - ADD LOGIC TO CHECK VALID SHIP TO OPTIONS!
		// # EXAMPLE - SERVICE CODE 14 (NEXT DAY AIR A.M.) ISN'T AVAILABLE FOR DELIVERY CONFIRMATION
		// # reference Appendix P (page 407) of the documentation.
		// # 1 - Delivery Confirmation  || 2 - Delivery Confirmation Signature Required || 
		// # 3 - Delivery Confirmation Adult Signature Required || 4 - USPS Delivery Confirmation

		if($options[0] == 1 && $order['comments'] != 'NO SIGNATURE REQUIRED') {
			$this->delivery_confirmation = "\n".'<DeliveryConfirmation>'."\n".
												'<DCISType>2</DCISType>'."\n".
										   '</DeliveryConfirmation>';
		} else { 
			$this->delivery_confirmation = '';
		}

		// # To-Do - complete insurance addition.
		// # create over ride for value passed.

		if($options[1] > 0) { 
		$this->insurance_value = "\n" . '<InsuredValue>' . "\n" . 
								 '<Code>02</Code>' . "\n" . 
								 '<CurrencyCode>' . $this->currency . '</CurrencyCode>' . "\n" . 
								 '<MonetaryValue>'. $options[1] . '</MonetaryValue>' . "\n" . 
								 '</InsuredValue>';
		} else {
		$this->insurance_value = '';
		}


		// # Notifications 

		if($options[2] == '1') {
			// # To-do: Add logic to grab comments - determine if special seperate shipment comments are appropriate.	
			$comments = '';

			// # To-do: determine or circle back to logic to handle notification code for returns. Reference page 276 in docs.
			// # <NotificationCode> = 3 - Receiver Return Notification || 6 - Quantum View Email Notification

			$this->notification = "\n".'<Notification>' . "\n" . 
										'<NotificationCode>6</NotificationCode>' . "\n" .
										'<EMailMessage>' . "\n" . 
											'<EMailAddress>'.$this->customer_email.'</EMailAddress>' . "\n" . 
											'<FromEMailAddress>'.$this->merchantEmail.'</FromEMailAddress>' .  
						(!empty($comments) ? "\n" . '<Memo>'.$comments.'</Memo>' : '') . "\n" .
										'</EMailMessage>' . "\n" . 
									'</Notification>';
		} else {
			$this->notification = '';
		}

		// # To-do: Create package type selection - default to Customer Supplied for now
		//$this->package_type_code = ($package_info['products_package_type']) ? $package_info['products_package_type'] : '02';
	
		$this->package_codes = array('01' => 'UPS Letter',
									 '02' => 'Customer Supplied',
									 '03' => 'Tube',
									 '07' => 'PAK',
									 '21' => 'UPS Express Box',
									 '24' => 'UPS 25KG Box',
									 '25' => 'UPS 10KG Box',
									 '30' => 'Pallet', 
									 '2a' => 'Small Express Box', 
									 '2b' => 'Medium Express Box',
									 '2c' => 'Large Express Box',
									 '56' => 'Flats',
									 '57' => 'Parcels', 
									 '58' => 'BPM', 
									 '59' => 'First Class', 
									 '60' => 'Priority', 
									 '61' => 'Machinables', 
									 '62' => 'Irregulars', 
									 '63' => 'Parcel Post', 
									 '64' => 'BPM Parcel',
									 '65' => 'Media Mail',
									 '66' => 'BMP Flat', 
									 '67' => 'Standard Flat'
									);

		$this->package_type = $method[2];
		$this->package_type_code = array_search($this->package_type, $this->package_codes);

		// # Dimenional Override support
		// # To-Do: integrate with existing dimensional support
		$this->package_height = (float)(!empty($dimensions[0])) ? $dimensions[0] : '';
		$this->package_width = (float)(!empty($dimensions[1])) ? $dimensions[1] : '';
		$this->package_length = (float)(!empty($dimensions[2])) ? $dimensions[2] : '';


		if($this->package_height > 0 && $this->package_width > 0 && $this->package_length > 0) { 

		$this->dimensions = "\n" . '<Dimensions>' . "\n" .
										'<UnitOfMeasurement>' . "\n" .
											'<Code>'.$this->unit_length.'</Code>' . "\n" .
										'</UnitOfMeasurement>' . "\n" .
										'<Length>'.$this->package_length.'</Length>' . "\n" .
										'<Width>'.$this->package_width.'</Width>' . "\n" .
										'<Height>'.$this->package_height.'</Height>' . "\n" .
									'</Dimensions>';

		} else { 
			$this->dimensions = '';
		}

		// # END non-standard package options - to be set with each Label

	}

    
	// # will take that ship-to array and do the leg work against the API
    function ConfirmRequest($orders_id) { 

		if(empty($orders_id)) return 0;


// # UPS XML LABEL SHIP CONFIRMATION REQUEST - STEP 1

	// # create the Access Rquest Header
$AccessRequestHeader = <<<EOD
	<?xml version="1.0" ?>
		<AccessRequest xml:lang='en-US'>
			<AccessLicenseNumber>%1\$s</AccessLicenseNumber>
			<UserId>%2\$s</UserId>
			<Password>%3\$s</Password>
		</AccessRequest>
EOD;

// # create the ShipmentConfirmRequest top portion we will join later
// # Note - Add support for dynamic label format switching.
// # <LabelPrintMethod>
// #   <Code>EPL</Code>
// # </LabelPrintMethod>
// # Gif may not print well on thermal printer - look for alternative formats.

$ShipmentConfirmRequestTOP = <<<EOD
	<?xml version="1.0"?>
		<ShipmentConfirmRequest>
			<Request>
				<TransactionReference>
					<CustomerContext></CustomerContext>
					<XpciVersion>1.0001</XpciVersion>
				</TransactionReference>
				<RequestAction>ShipConfirm</RequestAction>
			<RequestOption>nonvalidate</RequestOption>
		</Request>
		<Shipment>
EOD;

// # shipper (main merchant information)
$ShipperInfo = <<< EOD
	<Shipper>
        <Name>%1\$s</Name>
		<AttentionName>%1\$s</AttentionName>
        <PhoneNumber>%2\$s</PhoneNumber>
        <ShipperNumber>%3\$s</ShipperNumber>
		<Address>
			<AddressLine1>%5\$s</AddressLine1> %6\$s %7\$s
			<City>%8\$s</City>
			<StateProvinceCode>%9\$s</StateProvinceCode>
			<CountryCode>%10\$s</CountryCode>
			<PostalCode>%11\$s</PostalCode>
		</Address>
	</Shipper>
EOD;

// # The customer Ship-to.
$ShipTo = <<< EOD
	<ShipTo>
		<CompanyName>%1\$s</CompanyName>
		<AttentionName>%2\$s</AttentionName>
		<PhoneNumber>%3\$s</PhoneNumber>
		<Address>
			<AddressLine1>%4\$s</AddressLine1> %5\$s
			<City>%6\$s</City>
			<StateProvinceCode>%7\$s</StateProvinceCode>
			<CountryCode>%8\$s</CountryCode>
			<PostalCode>%9\$s</PostalCode> %10\$s            
		</Address>
	</ShipTo>
EOD;

$ShippingMethod = <<< EOD
	<Service>
		<Code>%1\$s</Code>
		<Description>%2\$s</Description>
	</Service>
EOD;

$PaymentInformation = <<< EOD
      <PaymentInformation>
         <Prepaid>
            <BillShipper>
               <AccountNumber>%1\$s</AccountNumber>
            </BillShipper>
         </Prepaid>
      </PaymentInformation>
EOD;

// # To-Do: Expand for multiple suppler / warehouse locations
$ShipFrom = <<< EOD
	<ShipFrom>
		<CompanyName>%1\$s</CompanyName>
		<AttentionName>%2\$s</AttentionName>
		<PhoneNumber>%3\$s</PhoneNumber>
		<TaxIdentificationNumber>%4\$s</TaxIdentificationNumber>
		<Address>
			<AddressLine1>%5\$s</AddressLine1> %6\$s %7\$s
            <City>%8\$s</City>
            <StateProvinceCode>%9\$s</StateProvinceCode>
            <PostalCode>%10\$s</PostalCode>
            <CountryCode>%11\$s</CountryCode>
         </Address>
      </ShipFrom>
EOD;

$Package = <<< EOD
	<Package>
		<PackagingType>
			<Code>%1\$s</Code>
		</PackagingType>
		<PackageWeight>
			<UnitOfMeasurement> 
				<Code>%9\$s</Code>
			</UnitOfMeasurement> 
            <Weight>%2\$s</Weight>
         </PackageWeight>
EOD;

if($this->shipperInfo_country == $this->country) { 

$Package .= <<< EOD

	<ReferenceNumber>
		<Code>%1\$s</Code>
		<Value>%12\$s</Value>
	</ReferenceNumber>

EOD;

}

$Package .= <<< EOD
		<PackageServiceOptions>%3\$s %4\$s %5\$s %6\$s %7\$s
		</PackageServiceOptions> %8\$s
	</Package>
	<RateInformation>
		<NegotiatedRatesIndicator/>
	</RateInformation>
EOD;

	// # Domestic shipments must NOT pass InvoiceLineTotal - detect destination country and remove if ness.

	// # some country destinations do not like InvoiceLineTotal and require things like description
	// # an example would be South Korea (KR), Mexico (MX) and Singapore (SG).
	
	$no_invoice_countries = array('KR','MX','SG','MY','CL', 'BR','IL', 'NG', 'PL', 'GB');

if(($this->shipperInfo_country != $this->country) && !in_array($this->country, $no_invoice_countries)) { 

$Package .= <<< EOD

	<InvoiceLineTotal>
		<CurrencyCode></CurrencyCode>
		<MonetaryValue>%10\$d</MonetaryValue>
	</InvoiceLineTotal>

	<Description>%11\$s</Description>
EOD;

}

	// # some international destinations require description

if(($this->shipperInfo_country != $this->country) && in_array($this->country, $no_invoice_countries)) { 

$Package .= <<< EOD

	<Description>%11\$s</Description>
EOD;
}

$ShipmentConfirmRequestBTM = <<< EOD
	</Shipment>
	<LabelSpecification>
		<LabelStockSize>
			<Height>%1\$s</Height>
			<Width>%2\$s</Width>
		</LabelStockSize>
		<LabelPrintMethod>
				<Code>%3\$s</Code>
		</LabelPrintMethod>
		<HTTPUserAgent>Mozilla/4.5</HTTPUserAgent>
	<LabelImageFormat>
        <Code>%4\$s</Code>
        <Description>%5\$s</Description>
    </LabelImageFormat>
	</LabelSpecification>
</ShipmentConfirmRequest>
EOD;

	// # join credentials from class to $AccessRequest template above
	$AccessRequestHeader = sprintf($AccessRequestHeader,$this->access_key, $this->access_username, $this->access_password);

	// # join ShipmentConfirmRequestTOP vars with config values
	$ShipmentConfirmRequestTOP = sprintf($ShipmentConfirmRequestTOP);

	// # detect which warehouse is shipping the product to the customer
	$ShipperInfo = sprintf($ShipperInfo, STORE_NAME, $this->shipperInfo_phone, $this->access_account_number, $this->tax_id, $this->shipperInfo_address1, $this->shipperInfo_address2, $this->shipperInfo_address3, $this->shipperInfo_city, $this->shipperInfo_state, $this->shipperInfo_country, $this->shipperInfo_zip);	

	$ShipTo = sprintf($ShipTo, $this->company, $this->delivery_name, trim($this->phone), $this->street_address, $this->street_address2, $this->city, $this->state, $this->country, $this->zip, $this->isResidential);


	$PaymentInformation = sprintf($PaymentInformation, $this->access_account_number);

	$ShipFrom = sprintf($ShipFrom, STORE_NAME, '', $this->shipperInfo_phone, $this->tax_id, $this->shipperInfo_address1, $this->shipperInfo_address2, $this->shipperInfo_address3, $this->shipperInfo_city, $this->shipperInfo_state, $this->shipperInfo_zip, $this->shipperInfo_country);


	// # IF SurePost methods are returned, change unit of measurement to ounces and convert weights to ounces

	if($this->serviceCode == '92' || $this->serviceCode == '93' || $this->serviceCode =='94' || $this->serviceCode == '95') { 

		if($this->package_weight < 1) {
			// # convert to Ounces for SurePost Less then 1 lb method.
			if($this->package_unitof == 'KGS') { 
				$this->package_weight = ($this->package_weight * 35.274);
			} elseif($this->package_unitof == 'LBS') { 
				$this->package_weight = ($this->package_weight * 16);
			}

			$this->package_unitof = 'OZS';			
		}

		$this->surePost_parcelType = ($this->package_unitof == 'LBS' && $this->package_weight >= 1 ? '93' : '92');

		$this->serviceCode = $this->surePost_parcelType;

	}

	// # Create the package for delivery - may require loop
	$Package = sprintf($Package, $this->package_type_code, $this->package_weight, $this->seperateBox, $this->additional_handling, $this->delivery_confirmation, $this->notification, $this->insurance_value, $this->dimensions, $this->package_unitof, $this->order_total, $this->description_of_goods, $this->package_type);

	$ShippingMethod = sprintf($ShippingMethod, $this->serviceCode, $this->shipMethod);

	$ShipmentConfirmRequestBTM = sprintf($ShipmentConfirmRequestBTM, $this->LabelImageHeight, $this->LabelImageWidth, $this->LabelPrintMethod, $this->LabelImageFormat, $this->LabelImageFormat);

	// # join all HEREDOC parts into one master var
	$xmlRequest = $AccessRequestHeader . "\n" . 
				  $ShipmentConfirmRequestTOP . "\n" . 
				  $ShipperInfo . "\n" . 
				  $ShipTo . "\n" . 
				  $ShippingMethod . "\n" . 
				  $PaymentInformation . "\n" . 
				  $ShipFrom . "\n" . 
				  $Package . "\n" . 
				  $ShipmentConfirmRequestBTM;

//error_log($xmlRequest,1,'chrisd@zwaveproducts.com');
//error_log(print_r($xmlRequest,1));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://onlinetools.ups.com/ups.app/xml/ShipConfirm");
	// # uncomment the next line if you get curl error 60: error setting certificate verify locations
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// # uncommenting the next line is most likely not necessary in case of error 60
	// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

	//if ($this->logfile) {
	//error_log("UPS REQUEST: " . $xmlRequest . "\n", 3, $this->logfile);
	//}

	// # SHIP CONFIRMATION RESPONSE
 	$xmlResponse = curl_exec($ch);
	if(curl_exec($ch) === false) print_r('<div class="upload_error">Curl error: ' . curl_error($ch).'</div>');


	$xml = $xmlResponse;
//error_log(print_r($xml,1));
//error_log($xml,1,'chrisd@zwaveproducts.com');

	preg_match_all("/\<ShipmentConfirmResponse\>(.*?)\<\/ShipmentConfirmResponse\>/s", $xml, $responseNodes);

	foreach($responseNodes[1] as $Nodes) {

		// # GET REQUEST RESPONSE STATUS
		preg_match_all( "/\<ResponseStatusDescription\>(.*?)\<\/ResponseStatusDescription\>/",$Nodes, $ResponseStatus);
		$ResponseStatus = $ResponseStatus[1][0];

			if($ResponseStatus == 'Success') { 
				// # SHIPPING DIGEST
				preg_match_all( "/\<ShipmentDigest\>(.*?)\<\/ShipmentDigest\>/", $Nodes, $theDigest); 
				$theDigest = $theDigest[1][0];
				echo $theDigest . '<br><br><br>';
		
				// # TRACKING NUMBER
				preg_match_all( "/\<ShipmentIdentificationNumber\>(.*?)\<\/ShipmentIdentificationNumber\>/",$Nodes, $tracking); 
				$tracking = $tracking[1][0];
				echo $tracking . '<br><br><br>';
		
				$method = $this->shipMethod;
				echo $method;
	
			} elseif($ResponseStatus == 'Failure') { 

				// # GET LABEL RECOVERY RESPONSE MESSAGE
				preg_match_all( "/\<Error\>(.*?)\<\/Error\>/",$Nodes, $Error); 

				foreach($Error[1] as $ErrorNodes) {

					preg_match_all( "/\<ErrorCode\>(.*?)\<\/ErrorCode\>/",$ErrorNodes, $ErrorCode); 
					$ErrorCode = $ErrorCode[1][0];

					preg_match_all( "/\<ErrorDescription\>(.*?)\<\/ErrorDescription\>/",$ErrorNodes, $ErrorDescription); 
					$ErrorDescription = $ErrorDescription[1][0];
				}	

			echo $ErrorDescription . "\n\n" . 'Error code: ' . $ErrorCode;
			error_log(print_r('Error code:' . $ErrorCode . "\n" . $ErrorDescription,TRUE));
			}
	}
}


    function AcceptRequest($shippingDigest, $orders_id) { // # will take the response and save to the db	
	// # Code to print UPS label.
	// # For generating UPS label you just need to pass shipping digest which you will get once you get ship confirmation response.
	// # SHIP ACCEPT REQUEST

	// # create the Access Rquest Header

$AccessRequestHeader = <<<EOD
	<?xml version="1.0" ?>
		<AccessRequest xml:lang='en-US'>
			<AccessLicenseNumber>%1\$s</AccessLicenseNumber>
			<UserId>%2\$s</UserId>
			<Password>%3\$s</Password>
		</AccessRequest>
EOD;

$ConfirmResponse = <<<EOD
	<?xml version="1.0" ?>
		<ShipmentAcceptRequest>
			<Request>
				<TransactionReference>
					<CustomerContext>Comments</CustomerContext>
					<XpciVersion>1.0001</XpciVersion>
				</TransactionReference>
				<RequestAction>ShipAccept</RequestAction>
			</Request>
			<ShipmentDigest>%1\$s</ShipmentDigest>
		</ShipmentAcceptRequest>
EOD;

	$LabelRequest = <<< EOD
%1\$s
%2\$s
EOD;

	// # join credentials from class to $AccessRequest template above
	$AccessRequestHeader = sprintf($AccessRequestHeader,$this->access_key, $this->access_username, $this->access_password);
	$ConfirmResponse = sprintf($ConfirmResponse, $shippingDigest);

	$LabelRequest = sprintf($LabelRequest, $AccessRequestHeader, $ConfirmResponse);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://onlinetools.ups.com/ups.app/xml/ShipAccept");
// # uncomment the next line if you get curl error 60: error setting certificate verify locations
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// # uncommenting the next line is most likely not necessary in case of error 60
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $LabelRequest);
curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

//if ($this->logfile) {
//   error_log("UPS REQUEST: " . $LabelRequest . "\n", 3, $this->logfile);
//}


	// # SHIP ACCEPT RESPONSE
	$xmlResponse = curl_exec ($ch); 

	if(curl_exec($ch) === false) print_r('<div class="upload_error">Curl error: ' . curl_error($ch).'</div>');

	$xml = $xmlResponse;

//error_log($xml, 1, 'chrisd@zwaveproducts.com');

	preg_match_all( "/\<ShipmentAcceptResponse\>(.*?)\<\/ShipmentAcceptResponse\>/s",$xml, $ResponseNodes);

	foreach( $ResponseNodes[1] as $LabelNodes) {
		// # GET LABEL
		preg_match_all( "/\<GraphicImage\>(.*?)\<\/GraphicImage\>/",$LabelNodes, $theLabel);
		$theLabel = $theLabel[1][0];

		// # GET TRACKING NUMBER
		preg_match_all( "/\<TrackingNumber\>(.*?)\<\/TrackingNumber\>/",$LabelNodes, $tracking); 
		$tracking = $tracking[1][0];

	// # SHIPPING COST
		preg_match_all( "/\<GrandTotal><CurrencyCode>$this->currency<\/CurrencyCode><MonetaryValue>(.*?)\<\/MonetaryValue><\/GrandTotal\>/",$LabelNodes, $shipcost); 
		$shipcost = $shipcost[1][0];

		// # BILLED WEIGHT
		preg_match_all( "/\<Weight\>(.*?)\<\/Weight\>/",$LabelNodes, $BilledWeight); 
		$BilledWeight = $BilledWeight[1][0];
		//echo $BilledWeight;	

	}
		// # show the image Label on screen for printing
		//echo '<img src="data:image/gif;base64,'. $theLabel. '"/>';

	   // # write results to database for future referencing
		tep_db_query("UPDATE ".TABLE_ORDERS_SHIPPED." 
						SET label_digest = '".$theLabel."', 	
						ship_type ='".$this->shipment_partialFull."',
						negotiated_rate = '".$shipcost."',
						shipped_weight = '".$BilledWeight."'
						WHERE orders_id = '".$orders_id."'
						AND tracking_number = '".$tracking."'
						");

	// # tried to retrieve label before processed by UPS (although confirmed) - DID NOT WORK!
	// return $this->labelRecovery($orders_id, $tracking);
}

// # LABEL RECOVERY REQUEST
function labelRecovery($orders_id, $TrackingNumber) {

// # INFO:
// # For up to 30 days after customers schedule return shipments, UPS maintains a copy of the shipping labels
// # for the returned package. If customers need to print additional copies of return labels, the Label Recovery
// # Shipping API can retrieve those labels.
// # Merchants typically provide this functionality on their own web site. Their customers access the merchant's
// # web site, which acts as an intermediary on their behalf to retrieve the label from UPS.


// # create the Access Rquest Header
$AccessRequestHeader = <<< EOD
	<?xml version="1.0" ?>
		<AccessRequest xml:lang='en-US'>
			<AccessLicenseNumber>%1\$s</AccessLicenseNumber>
			<UserId>%2\$s</UserId>
			<Password>%3\$s</Password>
		</AccessRequest>
EOD;

	// # join credentials from class to $AccessRequest template above
	$AccessRequestHeader = sprintf($AccessRequestHeader,$this->access_key, $this->access_username, $this->access_password);

	$LabelRecoveryRequest = <<< EOD
<?xml version="1.0" ?>
	<LabelRecoveryRequest>
		<Request>
			<TransactionReference>
				<XpciVersion>1.0001</XpciVersion>
			</TransactionReference>
		<RequestAction>LabelRecovery</RequestAction>
		</Request>
		<LabelSpecification>
			<H1TPUserAgent>Mozilla/4.5</H1TPUserAgent>
			<LabellmageFormat>
				<Code>%1\$s</Code>
			</LabellmageFormat>
		</LabelSpecification>
		<TrackingNumber>%2\$s</TrackingNumber>
	</LabelRecoveryRequest>
EOD;

	$LabelRecoveryRequest = sprintf($LabelRecoveryRequest, 'GlF', $TrackingNumber);

	$LabelRecovery = <<< EOD
%1\$s
%2\$s
EOD;

	$LabelRecovery = sprintf($LabelRecovery, $AccessRequestHeader, $LabelRecoveryRequest);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://onlinetools.ups.com/ups.app/xml/LabelRecovery");
	// # uncomment the next line if you get curl error 60: error setting certificate verify locations
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// # uncommenting the next line is most likely not necessary in case of error 60
	// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $LabelRecovery);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

	//if ($this->logfile) {
	//error_log("UPS REQUEST: " . $LabelRecovery . "\n", 3, $this->logfile);
	//}

	// # SHIP CONFIRMATION RESPONSE
 	$xmlResponse = curl_exec($ch);

	if(curl_exec($ch) === false) {
		print_r('<div class="upload_error">Curl error: ' . curl_error($ch).'</div>');
		error_log($orders_id . " - curl error - ". curl_error($ch) . "\n\n" . "UPS XML API Label Recovery Void Error:" .  "\n\n" . $LabelRecovery . "\n", 3, $this->logfile);
	//error_log($LabelRecovery,1,'chrisd@zwaveproducts.com');
	} 

	$xml = $xmlResponse;
//error_log($xml,1,'chrisd@zwaveproducts.com');
	preg_match_all( "/\<LabelRecoveryResponse\>(.*?)\<\/LabelRecoveryResponse\>/s",$xml, $ResponseNodes);

		// # GET VOID RESPONSE STATUS
		preg_match_all( "/\<ResponseStatusDescription\>(.*?)\<\/ResponseStatusDescription\>/",$VoidNodes, $ResponseStatus);
		$ResponseStatus = $ResponseStatus[1][0];


		foreach( $ResponseNodes[1] as $LabelRecoveryNodes) {
			// # GET RESPONSE STATUS
			preg_match_all( "/\<ResponseStatusCode\>(.*?)\<\/ResponseStatusCode\>/",$LabelRecoveryNodes, $ResponseStatus);
			$ResponseStatus = $ResponseStatus[1][0];

			if($ResponseStatus == 'Success') { 
				// # GET LABEL
				preg_match_all( "/\<GraphicImage\>(.*?)\<\/GraphicImage\>/",$LabelRecoveryNodes, $theLabel);
				$theLabel = $theLabel[1][0];
	
				// # GET TRACKING NUMBER
				preg_match_all( "/\<TrackingNumber\>(.*?)\<\/TrackingNumber\>/",$LabelRecoveryNodes, $tracking); 
				$tracking = $tracking[1][0];

			} elseif($ResponseStatus == 'Failure') { 

				// # GET LABEL RECOVERY RESPONSE MESSAGE
				preg_match_all( "/\<Error\>(.*?)\<\/Error\>/",$LabelRecoveryNodes, $Error); 

				foreach($Error[1] as $ErrorNodes) {

					preg_match_all( "/\<ErrorCode\>(.*?)\<\/ErrorCode\>/",$ErrorNodes, $ErrorCode); 
					$ErrorCode = $ErrorCode[1][0];

					preg_match_all( "/\<ErrorDescription\>(.*?)\<\/ErrorDescription\>/",$ErrorNodes, $ErrorDescription); 
					$ErrorDescription = $ErrorDescription[1][0];
				}	

			echo $ErrorDescription . "\n\n" . 'Error code: ' . $ErrorCode;
			}

		}

	}


// # will clean up and format and prepare for printing (may not be required - just thinking out loud here).
function voidLabel($orders_id, $TrackingNumber, $ShipmentIdentificationNumber) { 

		
		// # Grab the customer order details from the orders table based on the orders_id passed.
		$label_status_query = tep_db_query("SELECT os.*										
									  FROM " . TABLE_ORDERS_SHIPPED . " os
									  WHERE os.orders_id = '" . $orders_id . "'
									  AND os.ship_type != 'unconfirmed'
									  AND os.tracking_number = '".$TrackingNumber."'
									 ");
		$label_status = tep_db_fetch_array($label_status_query);

		// # detect parent / grouped shipments
		if($ShipmentIdentificationNumber !== $TrackingNumber) {
			$this->TrackingNumber = "\n". '<TrackingNumber>'.$TrackingNumber.'</TrackingNumber>'."\n";
		} else { 
			$this->TrackingNumber = '';
		}

		$this->ShipmentIdentificationNumber = ($ShipmentIdentificationNumber) ? $ShipmentIdentificationNumber : $label_status_query['tracking_number'];

	// # SHIP VOID REQUEST

// # create the Access Rquest Header
$AccessRequestHeader = <<<EOD
	<?xml version="1.0" ?>
		<AccessRequest xml:lang='en-US'>
			<AccessLicenseNumber>%1\$s</AccessLicenseNumber>
			<UserId>%2\$s</UserId>
			<Password>%3\$s</Password>
		</AccessRequest>
EOD;

// # To-do: Expand to loop through multiple packages
// # figure out what the difference between ShipmentIdentificationNumber and TrackingNumber
$VoidRequest = <<<EOD
	<?xml version="1.0" encoding="UTF-8" ?>
		<VoidShipmentRequest>
			<Request>
				<TransactionReference>
					<CustomerContext>Customer Transaction ID</CustomerContext>
					<XpciVersion>1.0001</XpciVersion>
				</TransactionReference>
				<RequestAction>Void</RequestAction>
				<RequestOption />
			</Request>
			<ExpandedVoidShipment>
				<ShipmentIdentificationNumber>%1\$s</ShipmentIdentificationNumber> %2\$s
			</ExpandedVoidShipment>
		</VoidShipmentRequest>
EOD;

	$triggerVoid = <<< EOD
%1\$s
%2\$s
EOD;

	// # join credentials from class to $AccessRequest template above
	$AccessRequestHeader = sprintf($AccessRequestHeader,$this->access_key, $this->access_username, $this->access_password);
	$VoidRequest = sprintf($VoidRequest, $this->ShipmentIdentificationNumber, $this->TrackingNumber);

	$triggerVoid = sprintf($triggerVoid, $AccessRequestHeader, $VoidRequest);

//error_log($triggerVoid, 1, 'chrisd@zwaveproducts.com');

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://onlinetools.ups.com/ups.app/xml/Void");
	// # uncomment the next line if you get curl error 60: error setting certificate verify locations
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// # uncommenting the next line is most likely not necessary in case of error 60
	// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $triggerVoid);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

	//if ($this->logfile) {
	//error_log("UPS REQUEST: " . $triggerVoid . "\n", 3, $this->logfile);
	//}

	// # SHIP CONFIRMATION RESPONSE
 	$xmlResponse = curl_exec($ch);
	if(curl_exec($ch) === false) {
		print_r('<div class="upload_error">Curl error: ' . curl_error($ch).'</div>');
		error_log($orders_id . " - curl error - ". curl_error($ch) . "\n\n" . "UPS XML API Trigger Void Error:" .  "\n\n" . $triggerVoid . "\n", 3, $this->logfile);
		//error_log($xmlRequest,1,'chrisd@zwaveproducts.com');
	} 

	$xml = $xmlResponse;

//error_log('orders id: ' . $orders_id . "\n\n" . $xml, 1, 'chrisd@zwaveproducts.com');
	preg_match_all( "/\<VoidShipmentResponse\>(.*?)\<\/VoidShipmentResponse\>/s",$xml, $VoidResponseNodes);

	foreach($VoidResponseNodes[1] as $VoidNodes) {

		// # GET VOID RESPONSE STATUS
		preg_match_all( "/\<ResponseStatusDescription\>(.*?)\<\/ResponseStatusDescription\>/",$VoidNodes, $ResponseStatus);
		$ResponseStatus = $ResponseStatus[1][0];

		// # GET VOID RESPONSE MESSAGE
		preg_match_all( "/\<Error\>(.*?)\<\/Error\>/",$VoidNodes, $Error); 

		foreach($Error[1] as $ErrorNodes) {

			preg_match_all( "/\<ErrorCode\>(.*?)\<\/ErrorCode\>/",$ErrorNodes, $ErrorCode); 
			$ErrorCode = $ErrorCode[1][0];

			preg_match_all( "/\<ErrorDescription\>(.*?)\<\/ErrorDescription\>/",$ErrorNodes, $ErrorDescription); 
			$ErrorDescription = $ErrorDescription[1][0];
		}	

	}
//error_log($ResponseStatus . ' - ' . $ErrorDescription, 1, 'chrisd@zwaveproducts.com');
	if($ResponseStatus == 'Success') {
	
		// # clean successfully voided shipments, as per the API response code
		// # delete from orders_shipped table
		tep_db_query("DELETE FROM ".TABLE_ORDERS_SHIPPED." 
					  WHERE orders_id = '".$orders_id."'
					  AND tracking_number = '".$this->ShipmentIdentificationNumber."'
					 ");
		/*
	   // # Update orders_shipped table and wipe out label image and update to Voided 
		tep_db_query("UPDATE ".TABLE_ORDERS_SHIPPED." 
					  	SET label_digest = '', 	
					  	ship_type ='Voided',
					  	ship_date = NOW(),
					  	negotiated_rate = NULL
					  WHERE orders_id = '".$orders_id."'
					  AND tracking_number LIKE '".$this->ShipmentIdentificationNumber."'
					");
		*/
	echo $ResponseStatus . ' - ' . $this->ShipmentIdentificationNumber . "\n" . 'Label Successfully Voided!';

	// # clean successfully voided shipments, as per the API response code
	// # delete from orders_shipped table
	} elseif($ResponseStatus == 'Failure') { 

		if($ErrorCode == '190117') { 
			tep_db_query("DELETE FROM ".TABLE_ORDERS_SHIPPED." 
						  WHERE orders_id = '".$orders_id."'
						  AND tracking_number = '".$this->ShipmentIdentificationNumber."'
						");
		} elseif($ErrorCode == '190102') { 

			error_log('Order ID: ' . $orders_id . ' - ' . $ErrorDescription . "\n\n" . "UPS XML API Trigger Void Error: " . "\n\n" . $xml . "\n", 1, 'chrisd@zwaveproducts.com');
	
			echo 'You have exceed the allowed time limit allowed to void a label'."\n".
				 'Please contact UPS Billing at 1-800-742-5877'. "\n\n";
		}

	echo 'Error code: ' . $ErrorCode . "\n" . $ErrorDescription;
	
		error_log($orders_id . ' - ' . $ErrorDescription . "\n\n" . "UPS XML API Trigger Void Error: " . "\n\n" . $xml . "\n", 3, $this->logfile);

	}
}

	// # will clean up and format and prepare for printing (may not be required - just thinking out loud here).
	function printLabel($orders_id, $TrackingNumber) { 

		// # Grab the customer order details from the orders table based on the orders_id passed.
		$label_digest_query = tep_db_query("SELECT label_digest AS label									
									  FROM " . TABLE_ORDERS_SHIPPED . "
									  WHERE orders_id = '" . $orders_id . "'
									  AND (ship_type = 'Full' OR ship_type = 'Partial')
									  AND tracking_number = '".$TrackingNumber."'
									 ");
		$label_digest = tep_db_fetch_array($label_digest_query);
		
		if(tep_db_num_rows($label_digest_query) > 0) { 
			echo base64_decode($label_digest['label']);
		} else {
			return false;
		}

//error_log(print_r($label_digest['label'] . ' - ' . $orders_id,TRUE));
	}


}

?>