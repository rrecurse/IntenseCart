<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  require_once(dirname(__FILE__)."/paypal_base_class.php");

  class payment_paypal_ec extends payment_paypal_base {
    // might be used after all
    //var $handlerLogFile='/tmp/google_response_log.txt';
    //var $handlerErrorFile='/tmp/google_response_error.txt';

    //@@ private
    var $cachedEndpoint=NULL;
    var $button_img='https://www.paypal.com/en_US/i/IntegrationCenter/scr/EC-button.gif';

    function getName()
    {
      //@@ PayPal ok
      //@@ Tested ok
      //@@ public
      return 'PayPal Express Checkout';
    }

    function getTitle()
    {
      return 'PayPal Express Checkout';
    }

    function paymentBox() {
      $cls=$this->getClass();
      if ($this->verifyPaymentInfo())
        return '<input type="hidden" name="'.$cls.'[token]" value="'.htmlspecialchars($this->payinfo['token']).'">'.
    	       '<input type="hidden" name="'.$cls.'[PayerID]" value="'.htmlspecialchars($this->payinfo['PayerID']).'">';
      else return '<a href="'.tep_href_link('external_checkout.php','use_module='.$cls).'"><img src="'.$this->button_img.'" border="0"></a>';
    }

    function getExternalCheckoutButton() {
      return '<a href="'.tep_href_link('external_checkout.php','use_module='.$this->getClass()).'"><img src="'.$this->button_img.'" border="0"></a>';
    }

    function acquirePaymentInfo()
    {
      $req=&$_POST[$this->getClass()];
      if (!isset($req)) $req=&$_GET;
      $this->payinfo=Array(
        'token'=>$req['token'],
        'PayerID'=>$req['PayerID']
      );
    }

    function verifyPaymentInfo()
    {
      if (!isset($this->payinfo)) $this->acquirePaymentInfo();
      return
        $this->payinfo['token'] &&
        $this->payinfo['PayerID'];
    }
    
    function initExternalCheckout(&$cart) {
      $subtotal=$cart->show_total();
      $response=$this->hash_call(
        "SetExpressCheckout",
          "AMT=$subtotal&".
          "CURRENCYCODE=".DEFAULT_CURRENCY."&".
          "RETURNURL=".urlencode(HTTPS_SERVER."/checkout.php?use_module=".get_class($this))."&".
          "CANCELURL=".urlencode(HTTP_SERVER."/shopping_cart.php")."&".
          "PAYMENTACTION=Authorization"
      );
      if ($response['TOKEN']) {
        switch ($this->getConf('server_type')) {
	  case 'sandbox': return "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=".$response['TOKEN'];
	  case 'beta-sandbox': return "https://www.beta-sandbox.paypal.com/webscr?cmd=_express-checkout&token=".$response['TOKEN'];
	  default: return "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=".$response['TOKEN'];
	}
      }
      return NULL;
    }
        
    function prepareCheckout(&$order)
    {
      if (!$this->verifyPaymentInfo()) {
        return false;
      }
//      $addr=$order->getShipTo(); // personal data
      $response=$this->hash_call(
        "GetExpressCheckoutDetails",
          "TOKEN=".urlencode($this->payinfo['token']).""
      );
      
      $addr=new IXaddress(Array(
        'email'=>$response['EMAIL'],
        'name'=>$response['SHIPTONAME'],
        'address'=>$response['SHIPTOSTREET'],
        'address2'=>$response['SHIPTOSTREET2'],
        'city'=>$response['SHIPTOCITY'],
        'state'=>$response['SHIPTOSTATE'],
        'country'=>$response['SHIPTOCOUNTRYCODE'],
        'postcode'=>$response['SHIPTOZIP'],
        'phone'=>$response['PHONENUM'],
      ));
      $this->payer_addr=$addr;
      return true;
      
      if (!$this->handlePayPalResponse($response,$txn,$tid,false)) {
        return false;
      }
      if (!$response['TRANSACTIONID']) {
        $this->finishTransaction($tid,'error',"Failed retrieving transaction ID!");
        return false;
      }
      if (in_array($response['AVSCODE'],array('N','R','S','U','C','I','B'))) {
        // see https://www.paypal.com/IntegrationCenter/ic_direct-payment.html
        $this->finishTransaction($tid,'error',"Address verification failed!");
        return false;
      }
      if ($response['CVV2MATCH']!='M') {
        // see https://www.paypal.com/IntegrationCenter/ic_direct-payment.html
        $this->finishTransaction($tid,'error',"CVV2 verification failed!");
        return false;
      }
      $this->finishTransaction($tid,'complete');
      $this->setPaymentStatus('pending');
      $this->ppb_setTxnData(array(
        'transaction'=>$response['TRANSACTIONID'],
        'authorization'=>$response['TRANSACTIONID']
      ));
      return $this->amount;
    }
    
    function getPayerAddress() {
      return $this->payer_addr;
    }

    function doAuthSale($amount,&$order,$type)
    {
      //@@ PayPal ok
      //@@ Not tested
      //@@ private
      if ($this->status!='incomplete') return $this->amount;
      if (!$this->verifyPaymentInfo()) {
        return false;
      }
      $pi=&$this->payinfo; // payment info
      $addr=$order->getShipTo(); // personal data
      $tid=$this->startTransaction($type=='sale'?'sale':'authorize',$amount);
      $response=$this->hash_call(
        "DoExpressCheckoutPayment",
          "TOKEN=".urlencode($this->payinfo['token'])."&".
          "PAYERID=".urlencode($this->payinfo['PayerID'])."&".
          "AMT=".$amount."&".
          "CURRENCYCODE=".DEFAULT_CURRENCY."&".
          "INVNUM={$order->orderid}&".
          "SHIPTONAME=".urlencode($addr->getFullName())."&".
          "SHIPTOSTREET=".urlencode($addr->getAddress())."&".
          "SHIPTOSTREET2=".urlencode($addr->getAddress2())."&".
          "SHIPTOCITY=".urlencode($addr->getCity())."&".
          "SHIPTOSTATE=".urlencode($addr->getZoneCode())."&".
          "SHIPTOCOUNTRYCODE=".urlencode($addr->getCountryCode())."&".
          "SHIPTOZIP=".urlencode($addr->getPostCode())."&".
          "SHIPTOPHONENUM=".urlencode($addr->getPhone())."&".
          "PAYMENTACTION=".($type=='sale'?'Sale':'Authorization')
      );
      if (!$this->handlePayPalResponse($response,$txn,$tid,false)) {
        return false;
      }
      if (!$response['TRANSACTIONID']) {
        $this->finishTransaction($tid,'error',"Failed retrieving transaction ID!");
        return false;
      }
      $this->finishTransaction($tid,'complete');
      $this->setPaymentStatus($type=='sale'?'complete':'pending');
      $this->ppb_setTxnData(array(
        'transaction'=>$response['TRANSACTIONID'],
        'authorization'=>$response['TRANSACTIONID']
      ));
      return $this->amount;
    }

  }
?>
