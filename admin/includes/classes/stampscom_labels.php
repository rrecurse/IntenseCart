<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

// # reference: https://swsim.stamps.com/swsim/swsimv18.asmx

// # Begin Stamps.com SOAP to XML to CURL Label generation Class

class USPSStampsLabelGen {

	var $shipTo, $street_address, $city, $zip, $phone, $preferred_method;


	// # __construct function
	// # $orders_id - the order id (duh!)
	// # $method - array of shipping method and shipping zone
	// # $supplier_id - the supplier as assigned by product
	// # $options - array of additional shipping options like delivery confirmation, insurance etc.
	// # $dimensions array.

	function __construct($orders_id, $method, $suppliers_id, $options='', $dimensions='') { 

	error_log(print_r(MODULE_SHIPPING_USPS_STAMPS_STATUS,1));

		if (MODULE_SHIPPING_USPS_STAMPS_STATUS == 'True') {
		}

		$this->logfile = '/home/zwave/logs/stampscom.log';
		$this->merchantEmail = STORE_OWNER_EMAIL_ADDRESS;

    	$this->integration_id = MODULE_SHIPPING_USPS_STAMPS_INTEGRATION_ID;
		$this->access_username = MODULE_SHIPPING_USPS_STAMPS_USERNAME;
		$this->access_password = MODULE_SHIPPING_USPS_STAMPS_PASSWORD;
		$this->access_url = MODULE_SHIPPING_USPS_STAMPS_ACCESS_URL;


		// test 
		$this->authenticator();



	}

	function authenticator() {


		$authenticate = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
								<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"
								xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
								xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
								xmlns:tns=\"http://stamps.com/xml/namespace/2014/01/swsim/swsimv35\">
									<soap:Body>
										<tns:AuthenticateUser>
											<tns:Credentials>
												<tns:IntegrationID>".$this->integration_id."</tns:IntegrationID>
												<tns:Username>".$this->access_username."</tns:Username>
												<tns:Password>".$this->access_password."</tns:Password>
											</tns:Credentials>
										</tns:AuthenticateUser>
									</soap:Body>
								</soap:Envelope>";

		$headers = array("User-Agent: Crosscheck Networks SOAPSonar",
						 "Content-Type: text/xml; charset=utf-8",
						 "SOAPAction: http://stamps.com/xml/namespace/2014/01/swsim/swsimv35/AuthenticateUser", 
						 "Content-length: ".strlen($authenticate)
						);


            // # PHP cURL  for https connection with auth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $this->access_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->access_username.":".$this->access_password);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $authenticate); // the SOAP request
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // # converting
            $response = curl_exec($ch); 

            curl_close($ch);

            // # converting
            $response1 = str_replace("<soap:Body>","",$response);
            $response2 = str_replace("</soap:Body>","",$response1);

            // # convertingc to XML
            $parser = simplexml_load_string($response2);
			error_log($parser);
            // user $parser to get your data out of XML response and to display it.


	}
}
?>