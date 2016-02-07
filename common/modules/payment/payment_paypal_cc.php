<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  require_once(dirname(__FILE__)."/paypal_base_class.php");

  class payment_paypal_cc extends payment_paypal_base
  {
    // might be used after all
    //var $handlerLogFile='/tmp/google_response_log.txt';
    //var $handlerErrorFile='/tmp/google_response_error.txt';

    //@@ private
    var $cachedEndpoint=NULL;

    function getName()
    {
      //@@ PayPal ok
      //@@ Tested ok
      //@@ public
      return 'PayPal API - Credit Card';
    }

    function paymentBox() {
      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
      }
      $today = getdate(); 
      for ($i=$today['year']; $i < $today['year']+11; $i++) {
        $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }
      return $this->makeForm(
        Array(
          array(
            'title' => "Card Type",
						'field' => tep_draw_pull_down_menu(
              'paypal_cc_type',
              Array(
                Array(
                  'id'=>'Visa',
                  'text'=>'Visa'
                ),
                Array(
                  'id'=>'MasterCard',
                  'text'=>'MasterCard'
                ),
                Array(
                  'id'=>'Amex',
                  'text'=>'Amex'
                ),
                Array(
                  'id'=>'Discover',
                  'text'=>'Discover'
                )
              )
            )
          ),
          array(
            'title' => "Card Owner Name",
            'field' => tep_draw_input_field(
              'paypal_cc_owner',
              $order->billing['firstname'].' '.$order->billing['lastname']
            )
          ),
          array(
            'title' => "Card Number",
            'field' => tep_draw_input_field('paypal_cc_num')
          ),
          array('title' => "Card Expiration Date",
            'field' => tep_draw_pull_down_menu(
              'paypal_exp_month',
              $expires_month
            ).'&nbsp;'.tep_draw_pull_down_menu(
              'paypal_exp_year',
              $expires_year
            )
          ),
          array(
            'title' => "Card Verification Code (CVV)",
            'field' => tep_draw_input_field('paypal_cc_cvv2')
          ),
        )
      );
    }

    function acquirePaymentInfo()
    {
      $this->payinfo=Array(
        'cc_type'=>$_POST['paypal_cc_type'],
        'cc_number'=>$_POST['paypal_cc_num'],
        'cc_owner'=>$_POST['paypal_cc_owner'],
        'exp_month'=>$_POST['paypal_exp_month'],
        'exp_year'=>$_POST['paypal_exp_year'],
        'cc_cvv2'=>$_POST['paypal_cc_cvv2']
      );
    }

    function verifyPaymentInfo()
    {
      if (!isset($this->payinfo))
        $this->acquirePaymentInfo();
      return
        $this->payinfo['cc_type'] &&
        $this->payinfo['cc_number'] &&
        $this->payinfo['cc_owner'] &&
//        $this->payinfo['cc_cvv2'] &&
        $this->payinfo['exp_month'] &&
        $this->payinfo['exp_year'];
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
      $pd=&$order->billing; // personal data
      list($fname,$lname)=explode(" ",$pi['cc_owner']);
      $tid=$this->startTransaction($type=='sale'?'sale':'authorize',$amount);
      $response=$this->hash_call(
        "DoDirectPayment",
        "CREDITCARDTYPE={$pi['cc_type']}&".
          "ACCT={$pi['cc_number']}&".
          "EXPDATE={$pi['exp_month']}20{$pi['exp_year']}&". // To review around year 2090! :-)
          "CVV2={$pi['cc_cvv2']}&".
          "AMT=".sprintf('%.2f',$amount)."&".
          "CURRENCYCODE=".DEFAULT_CURRENCY."&".
          "FIRSTNAME=$fname&".
          "LASTNAME=$lname&".
          "IPADDRESS={$_SERVER['REMOTE_ADDR']}&".
          "STREET={$pd['street_address']}&".
          "CITY={$pd['city']}&".
          "STATE={$pd['state']}&".
          "COUNTRY={$pd['country']}&".
          "COUNTRYCODE=US&".
          "ZIP={$pd['postcode']}&".
          "PAYMENTACTION=".($type=='sale'?'Sale':'Authorization')
      );
      if (!$this->handlePayPalResponse($response,$txn,$tid,false)) {
        return false;
      }
      if (!$response['TRANSACTIONID']) {
        $this->finishTransaction($tid,'error',"Failed retrieving transaction ID!");
        return false;
      }
// WTF
// no need to return errors unless PP returned one!
// - Jimbo
//      if (in_array($response['AVSCODE'],array('N','R','S','U','C','I','B'))) {
        // see https://www.paypal.com/IntegrationCenter/ic_direct-payment.html
//        $this->finishTransaction($tid,'error',"Address verification failed!");
//        return false;
//      }
//      if ($response['CVV2MATCH']!='M') {
        // see https://www.paypal.com/IntegrationCenter/ic_direct-payment.html
//        $this->finishTransaction($tid,'error',"CVV2 verification failed!");
//        return false;
//      }
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
