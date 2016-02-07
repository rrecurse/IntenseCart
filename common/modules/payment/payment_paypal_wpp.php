<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');

  define('MODULE_PAYMENT_PAYPAL_DP_CERT_PATH',DIR_FS_CATALOG_LOCAL.'paypal_wpp.crt');

   class payment_paypal_wpp extends IXpaymentCC {
    
    function payment_paypal_wpp() {
      $this->enableDebugging = 0;
    }

    function getName() {
      return 'PayPal Web Payments Pro';
    }
    
    function paymentBox() {
      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
      }
      $today = getdate(); 
      for ($i=$today['year']; $i < $today['year']+10; $i++) {
        $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }
      return $this->makeForm(Array(
					   array('title' => "Card Owner Type",
						 'field' => tep_draw_pull_down_menu('wpp_cc_type', Array(Array('id'=>'Visa','text'=>'Visa'),Array('id'=>'MasterCard','text'=>'MasterCard'),Array('id'=>'Amex','text'=>'Amex'),Array('id'=>'Discover','text'=>'Discover')))),
					   array('title' => "Card Owner Name",
						 'field' => tep_draw_input_field('wpp_cc_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'])),
                                           array('title' => "Card Number",
                                                 'field' => tep_draw_input_field('wpp_cc_num')),
                                           array('title' => "Card Expiration Date",
                                                 'field' => tep_draw_pull_down_menu('wpp_exp_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('wpp_exp_year', $expires_year)),
                                           array('title' => "Card Verification Code (CVV)",
                                                 'field' => tep_draw_input_field('wpp_cvv2')),
      ));
    }

    
    //Be gone which yo stank self
    function away_with_you($error_msg = '', $kill_sess_vars = false, $goto_page = '') {
      global $customer_first_name, $customer_id, $navigation, $paypal_ec_token, $paypal_ec_payer_id, $paypal_ec_payer_info, $payment_error, $paypal_ec_temp;
      
      if ($kill_sess_vars) {
        if ($paypal_ec_temp) { 
          $this->ec_delete_user($customer_id);
        }
        //Unregister the paypal session variables making the user start over
        if (tep_session_is_registered('paypal_ec_temp')) tep_session_unregister('paypal_ec_temp');
        if (tep_session_is_registered('paypal_ec_token')) tep_session_unregister('paypal_ec_token');
        if (tep_session_is_registered('paypal_ec_payer_id')) tep_session_unregister('paypal_ec_payer_id');
        if (tep_session_is_registered('paypal_ec_payer_info')) tep_session_unregister('paypal_ec_payer_info');
      }
      
      if (tep_session_is_registered('customer_first_name') && tep_session_is_registered('customer_id')) {
        $redirect_path = FILENAME_CHECKOUT_SHIPPING;
      } else {
        $navigation->set_snapshot(FILENAME_CHECKOUT_SHIPPING);
        $redirect_path = FILENAME_LOGIN;
      }
      if ($error_msg) {
        $payment_error = $error_msg;
        if (!tep_session_is_registered('payment_error')) tep_session_register('payment_error');
        
      } else {
        if (tep_session_is_registered('payment_error')) tep_session_unregister('payment_error');
      }
      tep_redirect(tep_href_link($redirect_path, '', 'SSL', true, false));
    }
    
    function return_transaction_errors($errors) {
      //Paypal will sometimes send back more than one error message, so we must loop through them if necessary
      
      $error_return = '';
      $iErr = 1;
      
      if (is_array($errors)) {
        foreach ($errors as $err) {
          if ($error_return) $error_return .= '<br><br>';
          if (sizeof($errors) > 1) {
            $error_return .= 'Error #' . $iErr . ': ';
          }
          $error_return .= $err->ShortMessage . ' (' . $err->ErrorCode . ')<br>' . $err->LongMessage;
          $iErr++;
        }
      } else {
        $error_return = $errors->ShortMessage . ' (' . $errors->ErrorCode . ')<br>' . $errors->LongMessage;
      }
      
      return $error_return;
    }
    

    //Initialize the connection with PayPal
    function paypal_init() {
      global $customer_id, $customer_first_name;

      require_once ('Services/PayPal.php');
      require_once ('Services/PayPal/Profile/Handler/Array.php');
      require_once ('Services/PayPal/Profile/API.php');

      $handler =& ProfileHandler_Array::getInstance(array(
        'username' => $this->getConf('wpp_username'),
        'certificateFile' => MODULE_PAYMENT_PAYPAL_DP_CERT_PATH,  //This needs to be an absolute path i.e. /home/user/cert.txt
        'subject' => '',
        'environment' => 'live'));
      
      $profile = APIProfile::getInstance($this->getConf('wpp_username'), $handler);
      $profile->setAPIPassword($this->getConf('wpp_password'));
      
      $caller =& Services_PayPal::getCallerServices($profile); //Create a caller object.  Ring ring, who's there?
      
      $caller->setOpt('curl', CURLOPT_SSL_VERIFYPEER, 0); 
      $caller->setOpt('curl', CURLOPT_TIMEOUT, 180);
      $caller->setOpt('curl', CURLOPT_SSL_VERIFYHOST, 0);
      
      if(Services_PayPal::isError($caller))  {
   
        $this->away_with_you('Error '  . $caller->Errors->ShortMessage . '<br>' . $caller->Errors->LongMessage . ' (' . $caller->Errors->ErrorCode . ')', true);
      } else {
        return $caller;
      }
    }

    function authorizePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'complete':
	  return $this->amount;
	case 'incomplete':
	  $this->WPPProcessSale($amount,$order);
	  return $this->getAuthAmount();
	default: return NULL;
      }
    }

    function settlePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	  break;
	case 'incomplete':
	  $this->WPPProcessSale($amount,$order);
	  break;
	case 'complete':
//	  if ($amount<$this->amount-0.005) $this->refundPayment($this->amount-$amount);
	  break;
	default: return NULL;
      }
      return $this->getSettleAmount();
    }

    function cancelPayment($order) {
      return NULL;
    }

    function acquirePaymentInfo() {
      $this->payinfo=Array('cc_type'=>$_POST['wpp_cc_type'],'cc_number'=>$_POST['wpp_cc_num'],'cc_owner'=>$_POST['wpp_cc_owner'],'cc_cvv2'=>$_POST['wpp_cvv2'],'exp_month'=>$_POST['wpp_exp_month'],'exp_year'=>$_POST['wpp_exp_year']);
    }

    function WPPProcessSale($amount,&$order) {
      if (!$this->verifyPaymentInfo()) return NULL;
      $caller = $this->paypal_init();
      if (tep_session_is_registered('paypal_ec_token') && tep_session_is_registered('paypal_ec_payer_id') && tep_session_is_registered('paypal_ec_payer_info')) {
      } else {  // Do DP checkout
        $cc_type = $this->payinfo['cc_type'];
        $cc_number = $this->payinfo['cc_number'];
        $cc_checkcode = $this->payinfo['cc_cvv2'];
        $cc_first_name = $this->payinfo['cc_owner'];
        $cc_last_name = '';
        $cc_owner_ip = $_SERVER['REMOTE_ADDR'];
	$iso_code_2 = 'US';

        $cc_expdate_month = $this->payinfo['exp_month'];
        $cc_expdate_year = $this->payinfo['exp_year'];
        if (strlen($cc_expdate_year) < 4) $cc_expdate_year = '20'.$cc_expdate_year;

        $state_query = tep_db_query("SELECT zone_code FROM " . TABLE_ZONES . " WHERE zone_id = '".$order->billing['zone_id']."'");
        $state_result = tep_db_fetch_array($state_query);
        $order_state = $state_result['zone_code'];

        $order->billing['state'] = $order_state;
        $order->delivery['state'] = $order_state;

        //Thanks goes to SteveDallas for improved international support
        //Set the billing state field depending on what PayPal wants to see for that country
        switch ($iso_code_2) {
          case 'US':
          case 'CA':
          //Paypal only accepts two character state/province codes for some countries
            if (strlen($order->billing['state']) > 2) {
              $state_query = tep_db_query("SELECT zone_code FROM " . TABLE_ZONES . " WHERE zone_name = '".$order->billing['state']."'");
              if (tep_db_num_rows($state_query) > 0) {
                $state = tep_db_fetch_array($state_query);
                $order->billing['state'] = $state['zone_code'];
              } else {
                $this->away_with_you('State Error');
              }
            }
            if (strlen($order->delivery['state']) > 2) {
              $state_query = tep_db_query("SELECT zone_code FROM " . TABLE_ZONES . " WHERE zone_name = '".$order->delivery['state']."'");
              if (tep_db_num_rows($state_query) > 0) {
                $state = tep_db_fetch_array($state_query);
                $order->delivery['state'] = $state['zone_code'];
              } else {
                $this->away_with_you('MODULE_PAYMENT_PAYPAL_DP_TEXT_STATE_ERROR');
              }
            }
            
            break;
          case 'AT':
          case 'BE':
          case 'FR':
          case 'DE':
          case 'CH':
            $order->billing['state'] = '';
            break;
          default:
            break;
        }

        //Fix contributed by Glen Hoag.  This wasn't handling the shipping state correctly if it was different than the billing
        if (tep_not_null($order->delivery['street_address'])) {
          //Set the delivery state field depending on what PayPal wants to see for that country
          switch ($iso_code_2) {
            case 'US':
            case 'CA':
            //Paypal only accepts two character state/province codes for some countries
              if (strlen($order->delivery['state']) > 2) {
                $state_query = tep_db_query("SELECT zone_code FROM " . TABLE_ZONES . " WHERE zone_name = '".$order->delivery['state']."'");
                if (tep_db_num_rows($state_query) > 0) {
                  $state = tep_db_fetch_array($state_query);
                  $order->delivery['state'] = $state['zone_code'];
                } else {
                  $this->away_with_you('MODULE_PAYMENT_PAYPAL_DP_TEXT_STATE_ERROR');
                }
              }
              if (strlen($order->delivery['state']) > 2) {
                $state_query = tep_db_query("SELECT zone_code FROM " . TABLE_ZONES . " WHERE zone_name = '".$order->delivery['state']."'");
                if (tep_db_num_rows($state_query) > 0) {
                  $state = tep_db_fetch_array($state_query);
                  $order->delivery['state'] = $state['zone_code'];
                } else {
                  $this->away_with_you('MODULE_PAYMENT_PAYPAL_DP_TEXT_STATE_ERROR');
                }
              }
              
              break;
            case 'AT':
            case 'BE':
            case 'FR':
            case 'DE':
            case 'CH':
              $order->delivery['state'] = '';
              break;
            default:
              break;
          }
        }
        
        $wpp_currency = 'USD';
        
        //If the cc type sent in the post var isn't any one of the accepted cards, send them back to the payment page
        //This error should never come up unless the visitor is  playing with the post vars or they didn't get passed to checkout_confirmation.php
        if ($cc_type != 'Visa' && $cc_type != 'MasterCard' && $cc_type != 'Discover' && $cc_type != 'Amex') {
          $this->away_with_you('Bad Card', false, FILENAME_CHECKOUT_SHIPPING);
        }
  
        //If they're still here, and awake, set some of the order object's variables
        $order->info['cc_type'] = $cc_type;
//        $order->info['cc_number'] = substr($cc_number, 0, 4) . str_repeat('X', (strlen($cc_number) - 8)) . substr($cc_number, -4);
        $order->info['cc_number'] = str_repeat('*', (strlen($cc_number) - 4)) . substr($cc_number, -4);
        $order->info['cc_owner'] = $cc_first_name . ' ' . $cc_last_name;
        $order->info['cc_expires'] = $cc_expdate_month . substr($cc_expdate_year, -2);
  
        //It's time to start a'chargin.  Initialize the paypal caller object
        $caller = $this->paypal_init();
  
        $ot =& Services_PayPal::getType('BasicAmountType');
        $ot->setattr('currencyID', $wpp_currency);
        $ot->setval(number_format($order->info['total'], 2));

        // Begin ShippingAddress -- WILLBRAND //
        if( $order->delivery['street_address'] != '' ) {
          $sat =& Services_PayPal::getType('AddressType');
          $sat->setName($order->delivery['firstname'] . ' ' . $order->delivery['lastname']);
          $sat->setStreet1($order->delivery['street_address']);
          $sat->setStreet2($order->delivery['suburb']);
          $sat->setCityName($order->delivery['city']);
          $sat->setPostalCode($order->delivery['postcode']);
          $sat->setStateOrProvince($order_state);
          $sat->setCountry($iso_code_2);
        }
        // End ShippingAddress -- WILLBRAND //

        $pdt =& Services_PayPal::getType('PaymentDetailsType');
        $pdt->setOrderTotal($ot);
        if (tep_not_null($order->delivery['street_address'])) $pdt->setShipToAddress($sat);

        $at =& Services_PayPal::getType('AddressType');
        $at->setStreet1($order->billing['street_address']);
        $at->setStreet2($order->billing['suburb']);
        $at->setCityName($order->billing['city']);
        $at->setStateOrProvince($order_state);
        $at->setCountry($iso_code_2);
        $at->setPostalCode($order->billing['postcode']);
  
        $pnt =& Services_PayPal::getType('PersonNameType');
        $pnt->setFirstName($cc_first_name);
        $pnt->setLastName($cc_last_name);
  
        $pit =& Services_PayPal::getType('PayerInfoType');
        $pit->setPayerName($pnt);
        $pit->setAddress($at);
        
        // Send email address of payee -- WILLBRAND //
        $pit->setPayer($order->customer['email_address']);
  
        $ccdt =& Services_PayPal::getType('CreditCardDetailsType');
        $ccdt->setCardOwner($pit);
        $ccdt->setCreditCardType($cc_type);
        $ccdt->setCreditCardNumber($cc_number);
        $ccdt->setExpMonth($cc_expdate_month);
        $ccdt->setExpYear($cc_expdate_year);
        $ccdt->setCVV2($cc_checkcode);
  
        $ddp_req =& Services_PayPal::getType('DoDirectPaymentRequestDetailsType');
        //Should the action be a variable? Uhmmm....I'm thinking no
        $ddp_req->setPaymentAction('Sale');
        $ddp_req->setPaymentDetails($pdt);
        $ddp_req->setCreditCard($ccdt);
        $ddp_req->setIPAddress($cc_owner_ip);
  
        $ddp_details =&Services_PayPal::getType('DoDirectPaymentRequestType');
        $ddp_details->setVersion('2.0');
        $ddp_details->setDoDirectPaymentRequestDetails($ddp_req);
        $final_req = $caller->DoDirectPayment($ddp_details);

        //If the transaction wasn't a success, start the error checking
        if (strpos($final_req->Ack, 'Success') === false) {
          $error_occurred = false;
          $ts_result = false;
          //If an error or failure occurred, don't do a transaction check
          if (strpos($final_req->Ack, 'Error') !== false || strpos($final_req->Ack, 'Failure') !== false) {
            $error_occurred = true;
            $error_log = $this->return_transaction_errors($final_req->Errors);
          } else {
            //Do a transaction search to make sure the connection didn't just timeout
            //It searches by email of payer and amount.  That should be accurate enough
            $ts =& Services_PayPal::getType('TransactionSearchRequestType');
            
            //Set to one day ago to avoid any time zone issues.  This does introduce a possible bug, but 
            //the chance of the same person buying the exact same amount of products within one day is pretty unlikely
            $ts->setStartDate(date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1,  date("Y"))) . 'T00:00:00-0700');
            $ts->setPayer($order->customer['email_address']);
            $ts->setAmount(number_format($order->info['total'], 2));

            $ts_req = $caller->TransactionSearch($ts);
            
            //If a matching transaction was found, tell us
            if(tep_not_null($ts_req->PaymentTransactions) && strpos($ts_req->Ack, 'Success') !== false) {
              $ts_result = true;
            } else {
              $error_log = $this->return_transaction_errors($final_req->Errors);
            }
          }

          if (!$error_occurred && $ts_result) {
            $return_codes = array($ts_req->PaymentTransactions[0]->TransactionID, 'No AVS Code Returned', 'No CVV2 Code Returned');
          } else {

            if ($this->enableDebugging == '1') {
              //Send the store owner a complete dump of the transaction
              $dp_dump = print_r($ddp_details, true);
              $final_req_dump = print_r($final_req, true);
              $spacer =           "---------------------------------------------------------------------\r\n";
              $dp_dump_title =    "-------------------------------DP_DUMP-------------------------------\r\n";
              $dp_dump_title .=   "------------This is the information that was sent to PayPal----------\r\n";
              $final_req_title =  "-------------------------------FINAL_REQ-----------------------------\r\n";
              $final_req_title .= "-------------------This is the response from PayPal------------------\r\n";
              $ts_req_dump = print_r($ts_req, true);
              $ts_req_title =     "---------------------------------TS_REQ------------------------------\r\n";
              $ts_req_title .=    "--------Results of the transaction search if it was executed---------\r\n";
              
              tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, 'PayPal Error Dump', "In function: before_process() - Direct Payment\r\nDid first contact attempt return error? " . ($error_occurred ? "Yes" : "Nope") . " \r\n" . $spacer . $dp_dump_title . $spacer . $dp_dump . $spacer . $final_req_title . $spacer . $final_req_dump . "\r\n\r\n" . $spacer . $ts_req_title . $spacer . $ts_req_dump, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
            
              //If the return is empty
              if (!tep_not_null($error_log)) {
                $this->away_with_you('Declined - No response from PayPal<br>No response was received from PayPal.  Please contact the store owner for assistance.', false, FILENAME_CHECKOUT_SHIPPING);
              } else {
                $this->away_with_you('Declined - '. $error_log, false, FILENAME_CHECKOUT_SHIPPING);
              }
          }
        } else {
          $return_codes = array($final_req->TransactionID, $final_req->AVSCode, $final_req->CVV2Code);
        }
        $this->payment_type = 'PayPal Direct Payment';     
        $this->trans_id = $return_codes[0];
        $this->payment_status = 'Completed';
        $ret_avs = $return_codes[1];
        $ret_cvv2 = $return_codes[2];
        
        switch ($ret_avs) {
        case 'A':
          $ret_avs_msg = 'Address Address only (no ZIP)';
          break;
        case 'B':
          $ret_avs_msg = 'International “A” Address only (no ZIP)';
          break;
        case 'C':
          $ret_avs_msg = 'International “N” None';
          break;
        case 'D':
          $ret_avs_msg = 'International “X” Address and Postal Code';
          break;
        case 'E':
          $ret_avs_msg = 'Not allowed for MOTO (Internet/Phone)';
          break;
        case 'F':
          $ret_avs_msg = 'UK-specific “X” Address and Postal Code';
          break;
        case 'G':
          $ret_avs_msg = 'Global Unavailable Not applicable';
          break;
        case 'I':
          $ret_avs_msg = 'International Unavailable Not applicable';
          break;
        case 'N':
          $ret_avs_msg = 'No None';
          break;
        case 'P':
          $ret_avs_msg = 'Postal (International “Z”) Postal Code only (no Address)';
          break;
        case 'R':
          $ret_avs_msg = 'Retry Not applicable';
          break;
        case 'S':
          $ret_avs_msg = 'Service not Supported Not applicable';
          break;
        case 'U':
          $ret_avs_msg = 'Unavailable Not applicable';
          break;
        case 'W':
          $ret_avs_msg = 'Whole ZIP Nine-digit ZIP code (no Address)';
          break;
        case 'X':
          $ret_avs_msg = 'Exact match Address and nine-digit ZIP code';
          break;
        case 'Y':
          $ret_avs_msg = 'Yes Address and five-digit ZIP';
          break;
        case 'Z':
          $ret_avs_msg = 'ZIP Five-digit ZIP code (no Address)';
          break;
        default:
          $ret_avs_msg = 'Error';
        }

        switch ($ret_cvv2) {
        case 'M':
          $ret_cvv2_msg = 'Match CVV2';
          break;
        case 'N':
          $ret_cvv2_msg = 'No match None';
          break;
        case 'P':
          $ret_cvv2_msg = 'Not Processed Not applicable';
          break;
        case 'S':
          $ret_cvv2_msg = 'Service not Supported Not applicable';
          break;
        case 'U':
          $ret_cvv2_msg = 'Unavailable Not applicable';
          break;
        case 'X':
          $ret_cvv2_msg = 'No response Not applicable';
          break;
        default:
          $ret_cvv2_msg = 'Error';
          break;
        }

        $this->avs = $ret_avs_msg;
        $this->cvv2 = $ret_cvv2_msg;
      }
      $this->storePaymentInfo($order);
      $this->setPaymentStatus('complete',$this->trans_id);
      return true;
    }

    function validateConf($key,$val) {
      switch ($key) {
      case 'wpp_username':
	if ($val=='') return 'Username cannot be empty';
	break;
      }
      return NULL;
    }

    function isReady() {
      return true;
    }

    function listConf() {
      return Array(
	'wpp_username'=>Array('title'=>'PayPal WPP API Username','default'=>''),
	'wpp_password'=>Array('title'=>'PayPal WPP API Password','default'=>''),
	'wpp_cert'=>Array('title'=>'PayPal Certificate','type'=>'savefile','default'=>DIR_FS_CATALOG_LOCAL.'paypal_wpp.crt'),
      );
    }

}
