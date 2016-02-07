<?php

include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');

class payment_authnet extends IXpaymentCC {

	function __construct() {
    }

    function getName() {
		return "AuthorizeNet Credit Card";
	}


	function paymentBox() {

		// # declared new class instance for order
		$order = new order((int)$_GET['oID']);

		for ($i=1; $i<13; $i++) {
        	$expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
		}

		$today = getdate(); 

		for($i=$today['year']; $i < $today['year']+10; $i++) {
			$expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
		}

		return $this->makeForm(array(array('title' => "Card Owner Type",
										   'field' => tep_draw_pull_down_menu('authnet_cc_type',array(
												array('id'=>'Visa','text'=>'Visa'), 
												array('id'=>'MasterCard','text'=>'MasterCard'), 
												array('id'=>'Amex','text'=>'Amex'), 
												array('id'=>'Discover','text'=>'Discover')
											), (!empty($order->info['cc_type']) ? $order->info['cc_type'] : '')
										)
									),

											array('title' => "Name On Card", 
												  'field' => tep_draw_input_field('authnet_cc_owner', $order->info['cc_owner'])
												),

                                           array('title' => "Card Number",
                                                 'field' => tep_draw_input_field('authnet_cc_num', $order->info['cc_number'])
												),

                                           array('title' => "Card Expiration Date",
                                                 'field' => tep_draw_pull_down_menu('authnet_exp_month', $expires_month, substr($order->info['cc_expires'], 0, 2)) . '&nbsp;' . tep_draw_pull_down_menu('authnet_exp_year', $expires_year, substr($order->info['cc_expires'], 2, 4))),
                                           array('title' => "Card Verification Code (CVV)",
                                                 'field' => tep_draw_input_field('authnet_cvv2')),
									));
    }
 
	function txnAuthNet($txn) {
		
		$txn['x_login'] = $this->getConf('login');
		$txn['x_tran_key'] = $this->getConf('tran_key');
		$txn['x_delim_char'] = '|';
		$txn['x_delim_data'] = 'TRUE';
		$txn['x_version'] = '3.1';
		$txn['x_relay_response'] = 'FALSE';
		// # x_device_type is set to "Website"	
		$txn['x_device_type'] = '8';

		if($this->getConf('mode') == 'production') { 

			$url = 'https://secure2.authorize.net/gateway/transact.dll';
		} else {
			$url = 'https://test.authorize.net/gateway/transact.dll';
		}

		$fields = array();
		
		foreach($txn as $key => $value) {
			$fields[] = "$key=" . urlencode($value);
		}

		$xml = join('&',$fields);

//error_log(print_r($xml,1));

		$sk = @fsockopen("ssl://".preg_replace('|/.*|','',preg_replace('|^https://|','',$url)),443);

		if(!$sk) return NULL;

		//fwrite($log,$xml."\n\n\n\n");

		fwrite($sk,"POST $url HTTP/1.0\r\n");
		fwrite($sk,"Content-Type: application/x-www-form-urlencoded\r\n");
		fwrite($sk,"Content-Length: ".strlen($xml)."\r\n");
		fwrite($sk,"\r\n");
		fwrite($sk,$xml);

		while (preg_match('/^\w/',fgets($sk,1024)));
	
		$resp = @fread($sk,65535);
      
/*
      $ch = curl_init($url); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, join('&',$fields));
//      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
      $resp = curl_exec($ch); //execute post and get results
      curl_close ($ch);
*/
		return explode('|',$resp);
	}


	function getAuthNetStatus($st) {
		switch ($st) {
			case 1: return 'complete';
			case 2: return 'declined';
			case 4: return 'on_hold';
			default: return 'error';
		}
	}


	function acquirePaymentInfo() {
      	$this->payinfo = array('cc_type' => $_POST['authnet_cc_type'],
							   'cc_number' => $_POST['authnet_cc_num'],
							   'cc_owner' => $_POST['authnet_cc_owner'],
							   'exp_month' => $_POST['authnet_exp_month'],
							   'exp_year' => $_POST['authnet_exp_year'],
							   'cc_cvv2' => $_POST['authnet_cvv2']
							   );
	}


	function doAuthSale($amount, &$order, $txn = 'AUTH_ONLY') {

		if (!$this->verifyPaymentInfo()) return false;

		$tid = $this->startTransaction($txn,$amount);

		$bill = $order->getBillTo();
		$ship = $order->getShipTo();


		preg_match('/(.*)\s(.*)/', $this->payinfo['cc_owner'], $billto);

		$fullname = $ship->getFullName();

		// # since order_id does not yet exist, we have to guess the next order ID 
		// # not the best approach

		if(!empty($order->orderid)) { 

			$orders_id = $order->orderid;

		} else {

			$orders_id_query = tep_db_query("SELECT (orders_id + 1) AS orders_id FROM orders ORDER BY orders_id DESC LIMIT 1");

			$orders_id = tep_db_result($orders_id_query,0);	

		}


		$delivery_query = tep_db_query("SELECT o.delivery_name, 
											   o.delivery_street_address,
											   o.delivery_suburb,
											   o.delivery_city,
											   o.delivery_postcode,
											   o.delivery_state,
											   o.delivery_country
									   FROM orders o
									   WHERE o.orders_id = '". $orders_id ."'
									   AND o.orders_id > 0
									  ");

		if(tep_db_num_rows($delivery_query) > 0) { 

			$delivery = tep_db_fetch_array($delivery_query);

			preg_match('/^\s*((.*?)\s+)?(\S*)\s*$/',$delivery['delivery_name'], $delivery_name);

		} else {

			preg_match('/^\s*((.*?)\s+)?(\S*)\s*$/',$this->payinfo['cc_owner'], $delivery_name);
		}

		$delivery_firstname = (!empty($delivery_name[2]) ? $delivery_name[2] : $ship->getFirstName());
		$delivery_lastname = (!empty($delivery_name[3]) ? $delivery_name[3] : $ship->getLastName());

//error_log('payment_authnet delivery name - '. $delivery_firstname . ' - ' . $delivery_lastname . ' - ' . $_SESSION['cartID']);

		// # detect customer IP
		// # This field is required with customer-IP-based Advanced Fraud Detection Suite (AFDS) filters
		$customer_ip = (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');


		$rs = $this->txnAuthNet(array("x_type"	=> $txn,
									  "x_method"	=> "CC",
									  "x_card_num"	=> $this->payinfo['cc_number'],
									  "x_exp_date"	=> $this->payinfo['exp_month'].$this->payinfo['exp_year'],
									  "x_card_code"	=> $this->payinfo['cc_cvv2'],
									  "x_description" => "Order ".$orders_id,
									  "x_amount" => sprintf('%.2f',$amount),
									  "x_first_name" => $billto[1],
									  "x_last_name"	=> $billto[2],
									  "x_phone" => $bill->getPhone(),
									  "x_company" => $bill->getCompany(),
									  "x_address" => $bill->getAddress(),
									  "x_city"	=> $bill->getCity(),
									  "x_state"	=> $bill->getZoneCode(),
									  "x_zip" => $bill->getPostcode(),
									  "x_country" => $bill->getCountryCode(),
									  "x_ship_to_first_name" => $delivery_firstname,
									  "x_ship_to_last_name"	=> $delivery_lastname,
									  "x_ship_to_address" => $ship->getAddress(),
									  "x_ship_to_city" => $ship->getCity(),
									  "x_ship_to_state" => $ship->getZoneCode(),
									  "x_ship_to_zip" => $ship->getPostcode(),
									  "x_ship_to_country" => $ship->getCountryCode(),
									  "x_invoice_num" => $orders_id,
									  "x_customer_ip" => $customer_ip,
									));

		$st = $this->getAuthNetStatus($rs[0]);

		// # finishTransaction method (non-API) is
		// # internal DB update to current status only
		// # tid = startTransaction
		// # st = status
		// # $rs = order object
		$this->finishTransaction($tid,$st,$rs);

		if($st != 'complete') {
			$this->storePaymentInfo($order);
			$this->setError($rs[2].' '.$rs[3]);
			$this->setPaymentStatus('error');
			return false;
		} else {
			$this->payinfo['cc_cvv2'] = NULL;
			$this->storePaymentInfo($order);
			$this->setPaymentStatus(($txn == 'AUTH_CAPTURE' ? 'complete' : 'pending'), $rs[6].':'.substr($this->payinfo['cc_number'],-4));
			return $rs[4];
		}
	}


	function cancelPayment() {

		if ($this->status =='pending') {

        	$tid = $this->startTransaction('void', NULL);

	        $rs = $this->txnAuthNet(array("x_type" => 'VOID',
										  "x_trans_id" => preg_replace('/:.*/','',$this->txnid),
	  									  "x_invoice_num" => $orders_id,
										 ));

			$st = $this->getAuthNetStatus($rs[0]);

			$this->finishTransaction($tid,$st,$rs);

			if ($st != 'complete') {
				$this->setError($rs[3]);
				return false;
			} else {
				$this->amount=0;
				$this->setPaymentStatus('cancelled');
				return true;
			}
		
		} else if ($this->status == 'complete') {
			$this->refundPayment($this->amount);
		}
	
		return NULL;
	}


	function authorizePayment($amount,&$order,$batch=0) {

		switch ($this->status) {

			case 'pending':
			case 'complete':
				return $this->amount;

			case 'incomplete':
				$this->doAuthSale($amount,$order,'AUTH_ONLY');
				return $this->getAuthAmount();

			default: 
				return NULL;
		}
    }


	function capturePayment($amount,&$order) {
		if (!$this->initPayment($amount,$order)) return NULL;
		return $this->getConf('txn_type') == 'sale' ? $this->settlePayment($amount,$order) : $this->authorizePayment($amount,$order);
	}


	function settlePayment($amount,&$order) {
//error_log($this->status);
		switch ($this->status) {
			case 'pending':
		
				$tid = $this->startTransaction('settle',$amount);
	
				$rs = $this->txnAuthNet(array("x_type"=>'PRIOR_AUTH_CAPTURE',
											  "x_trans_id"=>preg_replace('/:.*/','',$this->txnid),
											  "x_amount"=>sprintf("%.2f",$amount),
											  "x_invoice_num" => $order->orderid,
											//"x_card_num"=>preg_replace('/.*:/','',$this->txnid),
											));

				$st = $this->getAuthNetStatus($rs[0]);

				$this->finishTransaction($tid,$st,$rs);

				if ($st!='complete') {
					$this->setError($rs[3]);
				} else {
					$this->amount=$amount;
					$this->setPaymentStatus('complete');
				}
			
				break;

			case 'incomplete':
				$this->doAuthSale($amount,$order,'AUTH_CAPTURE');
			break;
	
			case 'complete':
				if($amount<$this->amount-0.005) $this->refundPayment($this->amount-$amount);
			break;
		
			default: return NULL;
		}

		return $this->getSettleAmount();
    }


	function refundPayment($amount) {
		switch ($this->status) {
        	case 'pending':
			break;
		
			case 'complete':

				$tid = $this->startTransaction('credit',$amount);
				$rs = $this->txnAuthNet(array("x_type"=>'CREDIT',
											  "x_trans_id"=>preg_replace('/:.*/','',$this->txnid),
											  "x_amount"=>sprintf("%.2f",$amount),
											  "x_card_num"=>preg_replace('/.*:/','',$this->txnid),
										));

				$st = $this->getAuthNetStatus($rs[0]);

		  		$this->finishTransaction($tid,$st,$rs);

				if($st!='complete') {
					$this->setError($rs[3]);
				} else {
		    		$this->amount-=$amount;
					$this->setPaymentStatus('complete');
				}
			break;

			default: return NULL;
		}

		return $this->getSettleAmount();
	}


	function isRecurrable() {
		return true;
	}

	function validateConf($key,$val) {
		switch ($key) {
			case 'merchant':
				if($val=='') {
					return 'Merchant id cannot be empty';
				}
			
			break;
		}
	
		return NULL;
	}

	function isReady() {
		return true;
	}

	function listConf() {
		return array('login' => array('title'=>'AuthNet Login','desc'=>'','default'=>''),
					 'tran_key' => array('title'=>'Transaction Key','desc'=>'','default'=>''),
					 'mode' => array('title'=>'Transaction Mode',
									 'desc' => '',
								     'default'=>'test',
									 'type'=>'radio',
									 'values'=> array('test'=>'Test','production'=>'Production')
									),
					 'txn_type' => array('title'=>'Transaction Type',
										 'desc' => '',
										 'type' => 'radio',
										 'values' => array('sale'=>'Authorize&amp;Sale','auth'=>'Authorize Only'),'default'=>'sale'),
										 );
	}
}
?>