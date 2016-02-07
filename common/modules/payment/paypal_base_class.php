<?php
  class payment_paypal_base extends IXpayment
  {
    var $txnData=array();
    var $txnDataKeys=array(
      'token'=>'TK',
      'transaction'=>'T',
      'authorization'=>'A',
      'payer'=>'P'
    );

    //@@ Tested ok
    function ppb_setTxnData($data)
    {
      $finalString=$this->ppb_txnDataToString($data);
      $this->setPaymentStatus($this->status,$finalString);
      $this->ppb_parseTxnData();
      return true;
    }

    //@@ Tested ok
    function ppb_txnDataToString($data)
    {
      $finalData=array();
      $finalString=array();
      ksort($data); // for consistency in the database
      foreach($data as $k=>$v) {
        if (!$this->txnDataKeys[$k]) {
          continue;
        }
        $finalData[$k]=$v;
        $finalString[]=$this->txnDataKeys[$k]."=".$v;
      }
      $finalString=implode("|",$finalString);
      return $finalString;
    }

    //@@ Tested ok
    function ppb_parseTxnData()
    {
      $data=$this->txnid;
      if (!$data) {
        return array();
      }
      $data=explode("|",$data);
      $finalArray=array();
      foreach($data as $atom) {
        $pos=strpos($atom,"=");
        if (!$pos) {
          continue;
        }
        $key=substr($atom,0,$pos);
        $value=substr($atom,$pos+1);
        $finalKey=NULL;
        foreach($this->txnDataKeys as $tk=>$tv) {
          if ($tv==$key) {
            $finalKey=$tk;
            break;
          }
        }
        if (!$finalKey) {
          continue;
        }
        $finalArray[$finalKey]=$value;
      }
      $this->txnData=$finalArray;
      return true;
    }

    function getPayPalErrors($response)
    {
      return
        $response['L_SEVERITYCODE0'].' '. // "Error"
        $response['L_ERRORCODE0'].': '. // Error number
        $response['L_LONGMESSAGE0'] // Error message
      ;
    }

    function handlePayPalResponse($response,$attempt,$tid,$finish=true)
    {
      //@@ PayPal ok
      //@@ Tested ok
      //@@ private
//      print_r($response);
//      exit;
      if (
        ($response['ACK']=='Success') ||
        ($response['ACK']=='SuccessWithWarning')
      ) {
        $this->ppb_parseTxnData();
        $data=$this->txnData;
	if ($response['TRANSACTIONID']) {
          $data['transaction']=$response['TRANSACTIONID'];
          $this->ppb_setTxnData($data);
	}

        if ($finish) {
          if ($response['ACK']=='Success') {
            $notes=NULL;
          } else {
            $notes=$this->getPayPalErrors();
          }
          $this->finishTransaction($tid,'complete',$notes);
        }
        return true;
      }
      $this->finishTransaction($tid,'error',
        $this->getPayPalErrors($response)
      );
      return false;
    }

    //@@ Tested ok
    function ppb_capture($amount)
    {
      $this->ppb_parseTxnData();
      ($authID=$this->txnData['authorization']) ||
        ($authID=$this->txnData['transaction']) ||
        $authID=false;
      if (!$authID) {
        return false;
      }
      $tid=$this->startTransaction("charge",$amount);
      // to determine whether it's complete or not!
      $response=$this->hash_call(
        "DoCapture",
        "AUTHORIZATIONID=$authID&".
        "AMT=$amount&".
        "CURRENCYCODE=".DEFAULT_CURRENCY."&".
        "COMPLETETYPE=NotComplete"
      );
      return $this->handlePayPalResponse($response,"charge",$tid);
    }

    function doAuthSale() {
      die(get_class($this));
    }

    function updateAmount()
    {
      //@@ PayPal ok
      //@@ NOT tested
      // Since transaction types are specific to each payment type, this method should not
      // be moved to the parent class.
      $total=0;
      $transactions=$this->findTransactions('charge','complete');
      foreach($transactions as $transaction_ID) {
        $total+=$this->getTransactionAmount($transaction_ID);
      }
      $transactions=$this->findTransactions('refund','complete');
      foreach($transactions as $transaction_ID) {
        $total-=$this->getTransactionAmount($transaction_ID);
      }
      $this->amount=$total;
      return $this->amount;
    }

    function refundPayment($amount)
    {
      //@@ PayPal ok
      //@@ Tested ok
      //@@ public
      if ($this->status!='complete') {
        return NULL;
      }
      $this->ppb_parseTxnData();
      if (!$this->txnData['transaction']) {
        return NULL;
      }
      $tid=$this->startTransaction('refund',$amount);
//      if ($this->amount-$amount>0.005) {
        $refundType='Partial';
        $amountRequest="AMT=$amount&CURRENCYCODE=".DEFAULT_CURRENCY."&";
//      } else {
//        $refundType='Full';
//        $amountRequest='';
//      }
      $response=$this->hash_call(
        "RefundTransaction",
        "TRANSACTIONID=".$this->txnData['transaction']."&".
          $amountRequest.
          "REFUNDTYPE=$refundType"
      );
      if (!$this->handlePayPalResponse($response,'Refund',$tid)) {
        return false;
      }
      $this->amount-=$amount;
      $this->setPaymentStatus('complete');
      return true;
    }

    function cancelPayment()
    {
      //@@ PayPal ok
      //@@ Tested ok
      //@@ public
      if ($this->status=='pending') {
        $this->ppb_parseTxnData();
        if (!$this->txnData['authorization']) {
          return false;
        }
        $tid=$this->startTransaction('cancel',NULL);
        $response=$this->hash_call(
          "DoVoid",
          "AUTHORIZATIONID=".$this->txnData['authorization']
        );
        if (!$this->handlePayPalResponse($response,'Cancel',$tid)) {
          return false;
        }
        $this->setPaymentStatus('canceled');
      } elseif ($this->status=='complete') {
        $this->refundPayment($this->amount);
        if ($this->amount<0.005) $this->setPaymentStatus('canceled');
      }
      return NULL;
    }

    function authorizePayment($amount,$order) {
      $this->doAuthSale($amount,$order,'auth');
      return $this->getAuthAmount();
    }
    
    //@@ PayPal ok
    //@@ Tested ok
    function settlePayment($amount,&$order)
    {
      //@@ PayPal ok
      //@@ NOT tested
      //@@ public
      switch ($this->status) {
        case 'incomplete':
	  $this->doAuthSale($amount,$order,'sale');
	  break;
        case 'pending':
          if ($amount>0.005) {
            if ($this->ppb_capture(min($amount,$this->amount))) {
              $this->setPaymentStatus('complete');
            }
          } else {
            $this->cancelPayment();
          }
          break;
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

    function validateConf($key,$val='--IX--special--null--')
    {
      //@@ PayPal ok
      //@@ public/private unknown, but this should be maintained
      if ($val=='--IX--special--null--') {
        $val=$this->getConf($key);
      }
      switch ($key) {
        case 'username':
        case 'password':
        case 'signature':
          if ($val=='') {
            return 'The username, password and signature are not optional';
          }
          break;
        case 'server_type':
          if ($val!='sandbox' && $val!='beta-sandbox' && $val!='production') {
            return 'Server type must be either "sandbox" or "production"';
          }
          break;
      }
      return NULL;
    }

    function isReady()
    {
      //@@ PayPal ok
      //@@ public/private -- unknown
      return
        !$this->validateConf('username') &&
        !$this->validateConf('password') &&
        !$this->validateConf('signature') &&
        !$this->validateConf('server_type');
    }

    function listConf()
    {
      //@@ PayPal ok
      //@@ public/private unknown, but this should be maintained
      return Array(
        'username'=>Array('title'=>'PayPal username','desc'=>'','default'=>''),
        'password'=>Array('title'=>'PayPal password','desc'=>'','default'=>''),
        'signature'=>Array('title'=>'PayPal signature','desc'=>'','default'=>''),
        'server_type'=>Array('title'=>'Production or sandbox','desc'=>'','default'=>'sandbox','type'=>'radio','values'=>Array('production'=>'Production','sandbox'=>'Sandbox','beta-sandbox'=>'Beta Sandbox'))
      );
    }
    
    function getPayPalEndpoint()
    {
      /*
        Reference: https://www.paypal.com/IntegrationCenter/ic_api-reference.html
        -------------------------------------------------------------------------
        Environment  	Authentication    Calling           Endpoint
        Live          API Certificate   Name-Value Pair   https://api.paypal.com/nvp
        Live          API Signature     Name-Value Pair   https://api-3t.paypal.com/nvp
        Live          API Certificate   SOAP              https://api.paypal.com/2.0/
        Live          API Signature     SOAP              https://api-3t.paypal.com/2.0/
        Sandbox       API Certificate   Name-Value Pair   https://api.sandbox.paypal.com/nvp
        Sandbox       API Signature     Name-Value Pair   https://api-3t.sandbox.paypal.com/nvp
        Sandbox       API Certificate   SOAP              https://api.sandbox.paypal.com/2.0/
        Sandbox       API Signature     SOAP              https://api-3t.sandbox.paypal.com/2.0/
        -------------------------------------------------------------------------
      */
      if ($this->cachedEndpoint) {
        return $this->cachedEndpoint;
      }
      switch(strtolower($this->getConf('server_type'))) {
        case "sandbox":
          $this->cachedEndpoint="https://api-3t.sandbox.paypal.com/nvp";
          break;
        case "beta-sandbox":
          $this->cachedEndpoint="https://api-3t.beta-sandbox.paypal.com/nvp";
          break;
        default:
          $this->cachedEndpoint="https://api-3t.paypal.com/nvp";
          break;
      }
      return $this->cachedEndpoint;
    }

    function getPayPalURL()
    {
      /*
        Define the PayPal URL. This is the URL that the buyer is
        first sent to to authorize payment with their paypal account
        change the URL depending if you are testing on the sandbox
        or going to the live PayPal site
        For the sandbox, the URL is
        https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
        For the live site, the URL is
        https://www.paypal.com/webscr&cmd=_express-checkout&token=
      */
      return "https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=";
    }

    /****************************************************
    The following methods adapted from CallerService.php
    (in https://www.paypal.com/IntegrationCenter/sdk/PayPal_PHP_NVP_Samples.zip,
    see https://www.paypal.com/IntegrationCenter/ic_downloads.html)
    ****************************************************/

    /*
    
    // Original comments and initialization, obsolete

    This file uses the constants.php to get parameters needed 
    to make an API call and calls the server.if you want use your
    own credentials, you have to change the constants.php

    Called by TransactionDetails.php, ReviewOrder.php, 
    DoDirectPaymentReceipt.php and DoExpressCheckoutPayment.php.

    require_once 'constants.php';

    $API_Endpoint =API_ENDPOINT;


    $version=VERSION;

    session_start();

    */

    /**
      * hash_call: Function to perform the API call to PayPal using API signature
      * @methodName is name of API  method.
      * @nvpStr is nvp string.
      * returns an associtive array containing the response from the server.
    */


    function hash_call($methodName,$nvpStr)
    {
      //@@debug
      //echo "<b>HASH($methodName,".htmlspecialchars($nvpStr).")</b><br>\n";

      // For versions, see
      // https://www.paypal.com/IntegrationCenter/ic_api-reference.html
      $version="3.2";
      $urlp=parse_url($this->getPayPalEndpoint());

      if ($nvpStr) {
        $nvpStr='&'.$nvpStr;
      }
      //NVPRequest for submitting to server
      $nvpreq=
        "METHOD=".urlencode($methodName)."&".
        "VERSION=".urlencode($version)."&".
        "PWD=".urlencode($this->getConf("password"))."&".
        "USER=".urlencode($this->getConf("username"))."&".
        "SIGNATURE=".urlencode($this->getConf("signature")).
        $nvpStr;
      $sk=@fsockopen("ssl://".$urlp['host'],443);
      if (!$sk) return NULL;
      fwrite($sk,"POST https://{$urlp['host']}{$urlp['path']} HTTP/1.0\r\n");
      fwrite($sk,"Content-Type: application/x-www-form-urlencoded\r\n");
      fwrite($sk,"Content-Length: ".strlen($nvpreq)."\r\n");
      fwrite($sk,"\r\n");
      fwrite($sk,$nvpreq);
      while (preg_match('/^\w/',fgets($sk,1024)));
      $rsp='';
      while (!feof($sk)) $rsp.=@fread($sk,65535);
      fclose($sk);

      $nvpResArray=$this->deformatNVP($rsp);

      return $nvpResArray;








      //setting the curl parameters.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$this->getPayPalEndpoint());
      curl_setopt($ch, CURLOPT_VERBOSE, 1);

      //turning off the server and peer verification(TrustManager Concept).
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch, CURLOPT_POST, 1);
      /*
       //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
       //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
      if(USE_PROXY)
      curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT); 
      */
      if ($nvpStr) {
        $nvpStr='&'.$nvpStr;
      }

      //NVPRequest for submitting to server
      $nvpreq=
        "METHOD=".urlencode($methodName)."&".
        "VERSION=".urlencode($version)."&".
        "PWD=".urlencode($this->getConf("password"))."&".
        "USER=".urlencode($this->getConf("username"))."&".
        "SIGNATURE=".urlencode($this->getConf("signature")).
        $nvpStr;

      //setting the nvpreq as POST FIELD to curl
      curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

      //getting response from server
      $response = curl_exec($ch);
      //convrting NVPResponse to an Associative Array

      /*
      // Debugging
      $nvpReqArray=$this->deformatNVP($nvpreq);
      $_SESSION['nvpReqArray']=$nvpReqArray;
      */

      if (curl_errno($ch)) {
        // Error!
        /*
        // obsolete
        // moving to display page to display curl errors
        $_SESSION['curl_error_no']=curl_errno($ch) ;
        $_SESSION['curl_error_msg']=curl_error($ch);
        $location = "APIError.php";
        header("Location: $location");
        */
      } else {
        //closing the curl
        curl_close($ch);
      }

      //@@debug
      //echo "<pre>";
      //var_dump($nvpResArray);
      //echo "</pre>";

    }

    /** This function will take NVPString and convert it to an Associative Array and it will decode the response.
      * It is usefull to search for a particular key and displaying arrays.
      * @nvpstr is NVPString.
      * @nvpArray is Associative Array.
      */

    function deformatNVP($nvpstr)
    {

      $intial=0;
      $nvpArray = array();


      while(strlen($nvpstr)){
        //postion of Key
        $keypos= strpos($nvpstr,'=');
        //position of value
        $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

        /*getting the Key and Value values and storing in a Associative Array*/
        $keyval=substr($nvpstr,$intial,$keypos);
        $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
        //decoding the respose
        $nvpArray[urldecode($keyval)] =urldecode( $valval);
        $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
      }
      return $nvpArray;
    }
  }
?>