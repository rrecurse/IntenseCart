<?php

	include('includes/application_top.php');
	global $customer_city;

	// # USPS city detection by Postal code

	if($_GET['address_validate'] == 'CityStateLookupRequest' && !empty($_GET['zip'])) { 

		if(defined('MODULE_SHIPPING_USPS_USERID')) { 


			$_GET['zip'] = substr($_GET['zip'], 0, 5);

			$xml = '<CityStateLookupRequest USERID="'.MODULE_SHIPPING_USPS_USERID.'"><ZipCode ID="0"><Zip5>'.$_GET['zip'].'</Zip5></ZipCode></CityStateLookupRequest>';

			$url = 'http://production.shippingapis.com/ShippingAPI.dll';

		    // # setting the curl parameters.
		    $ch = curl_init();

		    curl_setopt($ch, CURLOPT_URL,$url);
		    curl_setopt($ch, CURLOPT_POST, 1);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, "API=CityStateLookup&XML=".$xml);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        	if (curl_errno($ch)) {
	    	    // # moving to display page to display curl errors
				error_log(curl_errno($ch));
				error_log(curl_error($ch));
    		} else {
        		// # getting response from server
		        $response = curl_exec($ch);

				$xml = simplexml_load_string($response);

				if (tep_session_is_registered('customer_city')) {
				    tep_session_unregister('customer_city');
				}
				if (!tep_session_is_registered('customer_city')) {
					tep_session_register('customer_city');

				}

				$customer_city = (string)$xml->ZipCode->City;
		
				echo $customer_city;

				curl_close($ch);
		    }

		}
		exit();
	}


	// # USPS address verification and suggested address

	if($_GET['address_validate'] == 'Verify' && !empty($_GET['zip'])) { 

		if(defined('MODULE_SHIPPING_USPS_USERID')) { 

			$xml = '<AddressValidateRequest USERID="'.MODULE_SHIPPING_USPS_USERID.'"><Address ID="0"><FirmName>'.$_GET['firstname'].'</FirmName><Address1>'.$_GET['ship_street_address1'].'</Address1><Address2>'.$_GET['ship_street_address2'].'</Address2><City>'.$_GET['ship_city'].'</City><State></State><Zip5>'.$_GET['zip'].'</Zip5><Zip4></Zip4></Address></AddressValidateRequest>';

			$url = 'http://production.shippingapis.com/ShippingAPI.dll';

		    // # setting the curl parameters.
		    $ch = curl_init();

		    curl_setopt($ch, CURLOPT_URL,$url);
		    curl_setopt($ch, CURLOPT_POST, 1);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, "API=Verify&XML=".$xml);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        	if (curl_errno($ch)) {
	    	    // # moving to display page to display curl errors
				error_log(curl_errno($ch));
				error_log(curl_error($ch));
    		} else {
        		// # getting response from server
		        $response = curl_exec($ch);

				$xml = simplexml_load_string($response);

				if (tep_session_is_registered('customer_city')) {
				    tep_session_unregister('customer_city');
				}
				if (!tep_session_is_registered('customer_city')) {
					tep_session_register('customer_city');

				}

				$customer_firstname = (string)$xml->Address->FirmName;
				$customer_address1 = (string)$xml->Address->Address2;
				$customer_address2 = (string)$xml->Address->Address1;
				$customer_city = (string)$xml->Address->City;
				$customer_state = (string)$xml->Address->State;
				$customer_zip5 = (string)$xml->Address->Zip5;
				$customer_zip4 = (string)$xml->Address->Zip4;
		
				$returnText = (string)$xml->Address->ReturnText;

				echo json_encode(array($customer_firstname, $customer_address1, $customer_address2, $customer_city, $customer_state, $customer_zip5, $customer_zip4, $returnText));
		
				curl_close($ch);
		    }

		}
		exit();
	}


	// # CanadaPost city detection by Postal code (reverse postalcode lookup). - NOTICE - DISABLED BELOW - SCRAPER NO LONGER WORKS!
	if($_GET['address_validate'] == 'reversePostalcode' && !empty($_GET['zip'])) { 

		// # country code 38 is Canada
		if($_GET['ship_country'] == 38) { 
		/*
			// # include the CanadaPost screen scraper class - NO LONGER WORKS! =(
			require(DIR_WS_CLASSES . 'canadapost-findzip.php');
			$cp = new CanadaPost;

			// # returns an array with multiple street name responses
			$customer_city = $cp->reverse_postalcode($_GET['zip']);

			// # reset the customer_city session
			if (tep_session_is_registered('customer_city')) {
			    tep_session_unregister('customer_city');
			}

			// # set session customer_city if not set
			if (!tep_session_is_registered('customer_city')) {
				tep_session_register('customer_city');
			}

			// # transverse array to city to return city name only
			echo $customer_city[0]['city'];
		*/
		}
		exit();
	}
?>
