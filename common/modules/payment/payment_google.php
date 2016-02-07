<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  require_once(DIR_FS_COMMON.'modules/payment/google_library/googleall.php');

  class payment_google extends IXpaymentCC
  {

    var $handlerLogFile='/tmp/google_response_log.txt';
    var $handlerErrorFile='/tmp/google_response_error.txt';

    function getName()
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ public
      return 'Google Checkout';
    }

    function cancelPayment()
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ public
      if ($this->status=='pending') {
        if ($this->doAPIcalls()) {
          $tid=$this->startTransaction('cancel',NULL);
          $this->initGoogleRequest();
          $response=$this->request->SendCancelOrder($this->txnid,"Please see e-mail correspondence.",'via IntenseCart');
          if (!$this->handleGoogleResponse($response,'Cancel',$tid)) {
            return false;
          }
        }
      } elseif ($this->status=='complete') {
        $this->refundPayment($this->amount);
      }
      return NULL;
    }

    function handleGoogleResponse($response,$attempt,$tid)
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ private
      if ($response[0]==200) {
        $this->finishTransaction($tid,'pending');
        require_once(DIR_WS_FUNCTIONS.'debug.php');
        IXdebug("Succeeded sending Google API request [$attempt]: {$response[0]}/{$response[1]}");
        return true;
      }
      $this->finishTransaction($tid,'error',"{$response[0]}/{$response[1]}");
      require_once(DIR_WS_FUNCTIONS.'debug.php');
      IXdebug("Failed sending Google API request [$attempt]: {$response[0]}/{$response[1]}");
      return false;
    }

    function authorizePayment($amount,&$order,$batch=0)
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ public
      switch ($this->status) {
        case 'pending':
        case 'complete':
          return $this->amount;
        case 'incomplete':
          if ($this->doAPIcalls()) {
            $this->initGoogleRequest();
            $tid=$this->startTransaction('authorize',$this->amount);
            $response=$this->request->SendAuthorizeOrder($this->txnid);
            if (!$this->handleGoogleResponse($response,'Authorization',$tid)) {
              return $this->amount;
            }
            // Yes, we actually return false on successfully sending an asynchronous request
            return false;
          } else {
            return $this->amount;
          }
        default:
          return NULL;
      }
    }

    function googleCharge($amount)
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ private
      $tid=$this->startTransaction('charge',$amount);
      $this->initGoogleRequest();
      $response=$this->request->sendChargeOrder($this->txnid,$amount);
      if (!$this->handleGoogleResponse($response,'Charge',$tid)) {
        return false;
      }
      return $amount;
    }

    function settlePayment($amount)
    {
      //@@ Google ok
      //@@ public
      //@@ Tested ok
      switch ($this->status) {
        case 'pending':
          if ($amount>0.005) {
            if ($this->doAPIcalls()) {
              if (!$this->googleCharge(min($amount,$this->amount))) {
                return $this->amount;
              }
              // Yes, we actually return false on successfully sending an asynchronous request
              return false;
            } else {
              return $this->amount;
            }
          } else {
            $this->cancelPayment();
          }
          break;
        case 'incomplete':
          return NULL;
        case 'complete':
          if ($amount<$this->amount-0.005) {
            $this->refundPayment($this->amount-$amount);
          }
          break;
        default:
          return NULL;
      }
      return $this->getSettleAmount();
    }
    
    function updateAmount()
    {
      //@@ Google ok
      //@@ Tested ok
      // Since transaction types are specific to each payment type, this method should not
      // be moved to the parent class.
      $total=0;
      $transactions=$this->findTransactions('charge','complete');
      foreach($transactions as $transaction_ID) {
        $total+=$this->getTransactionAmount($transaction_ID);
      }
      $transactions=$this->findTransactions('refund','complete');
      require_once(DIR_WS_FUNCTIONS.'debug.php');
      foreach($transactions as $transaction_ID) {
        IXdebug("Found refund $transaction_ID!");
        $total-=$this->getTransactionAmount($transaction_ID);
      }
      IXdebug("Total: $total");
      $this->amount=$total;
      return $this->amount;
    }

    function refundPayment($amount)
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ public
      if ($this->status!='complete') return NULL;
      if (!$this->doAPIcalls()) return true;
      $tid=$this->startTransaction('refund',$amount);
      $this->initGoogleRequest();
      $response=$this->request->sendRefundOrder($this->txnid,$amount,'','via IntenseCart');
      if (!$this->handleGoogleResponse($response,'Refund',$tid)) {
        return false;
      }
      return true;
    }

    function validateConf($key,$val='--IX--special--null--')
    {
      //@@ Google ok
      //@@ public/private unknown, but this should be maintained
      if ($val=='--IX--special--null--') {
        $val=$this->getConf($key);
      }
      switch ($key) {
        case 'merchant':
        case 'merchant_pass':
          if ($val=='') {
            return 'Merchant ID and Key are not optional';
          }
          break;
        case 'server_type':
          if ($val!='sandbox' && $val!='production') {
            return 'Server type must be either "sandbox" or "production"';
          }
          break;
        case 'currency':
          if (strlen($val)!=3) {
            return 'Currency must be an ISO 4217 currency value';
          }
          break;
      }
      return NULL;
    }

    function isReady()
    {
      //@@ Google ok
      //@@ public/private -- unknown
      return
        !$this->validateConf('merchant') &&
        !$this->validateConf('merchant_pass') &&
        !$this->validateConf('server_type') &&
        !$this->validateConf('currency');
    }

    function listConf()
    {
      //@@ Google ok
      //@@ public/private unknown, but this should be maintained
      return Array(
        'merchant'=>Array('title'=>'Merchant ID','desc'=>'','default'=>''),
        'merchant_pass'=>Array('title'=>'Merchant Password','desc'=>'','default'=>''),
        'currency'=>Array('title'=>'Default currency','desc'=>'','default'=>'USD'),
        'server_type'=>Array('title'=>'Production or sandbox','desc'=>'','default'=>'sandbox')
      );
    }
    
    function &getCart()
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ private
      if ($this->cartObject) {
        return $this->cartObject;
      }
      $this->cartObject=new GoogleCart(
        $this->getConf('merchant'),
        $this->getConf('merchant_pass'),
        $this->getConf('server_type'),
        $this->getConf('currency')
      );
      $this->cartObject->SetMerchantCalculations(
//        'https://'.SITE_DOMAIN.'/google_checkout_calc.php','true','true','true'
        'https://plainmod.intensecart.com/google_checkout_calc.php','true','true','true'
      );
      return $this->cartObject;
    }
    
    function doAPIcalls()
    {
      //@@ Google ok
      //@@ Not tested
      //@@ private
      global $_IX_payment_google_inhibit_API_calls;
      return !$_IX_payment_google_inhibit_API_calls;
    }

    function checkout()
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ private
      if (!$this->cartObject) {
        return false;
      }
      list($status, $error) = $this->cartObject->CheckoutServer2Server();
      // if i reach this point, something was wrong
      echo "An error had ocurred: <br />HTTP Status: " . $status. ":";
      echo "<br />Error message:<br />";
      echo $error;
      return false;
    }
    
    function initGoogleRequest()
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ private
      if ($this->request) {
        return NULL;
      }
      $this->request = new GoogleRequest(
        $this->getConf('merchant'),
        $this->getConf('merchant_pass'),
        $this->getConf('server_type'),
        $this->getConf('currency')
      );
      return NULL;
    }

    function initiateResponseHandler()
    {
      //@@ Google ok
      //@@ Tested ok
      //@@ public, but specific to Google
      $this->response = new GoogleResponse(
        $this->getConf('merchant'),
        $this->getConf('merchant_pass')
      );

      $this->initGoogleRequest();

      //Setup the log file
      $this->response->SetLogFiles(
        $this->handlerErrorFile,
        $this->handlerLogFile,
        L_ALL
      );

      // Retrieve the XML sent in the HTTP POST request to the ResponseHandler
      global $HTTP_RAW_POST_DATA;
      $xml_response = isset($HTTP_RAW_POST_DATA)?
        $HTTP_RAW_POST_DATA:file_get_contents("php://input");
      if (get_magic_quotes_gpc()) {
        $xml_response = stripslashes($xml_response);
      }
      list($this->responseRoot, $this->responseData) = $this->response->GetParsedXML($xml_response);
      $this->response->SetMerchantAuthentication(
        $this->getConf('merchant'),
        $this->getConf('merchant_pass')
      );

      $status = $this->response->HttpAuthentication();
      return (bool) $status;
    }
  }
?>
