<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  class payment_intensepay extends IXpaymentCC {

    function payment_intensepay() {
    }

    function getName() {
      return "GoEmerchant Credit Card";
//      if (SITE_RESELLER=='goemerchant') return "GoEmerchant Credit Card";
//      return "IntensePAY Credit Card";
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
						 'field' => tep_draw_pull_down_menu('intensepay_cc_type', Array(Array('id'=>'Visa','text'=>'Visa'),Array('id'=>'MasterCard','text'=>'MasterCard'),Array('id'=>'Amex','text'=>'Amex'),Array('id'=>'Discover','text'=>'Discover')))),
					   array('title' => "Card Owner Name",
						 'field' => tep_draw_input_field('intensepay_cc_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'])),
                                           array('title' => "Card Number",
                                                 'field' => tep_draw_input_field('intensepay_cc_num')),
                                           array('title' => "Card Expiration Date",
                                                 'field' => tep_draw_pull_down_menu('intensepay_exp_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('intensepay_exp_year', $expires_year)),
                                           array('title' => "Card Verification Code (CVV)",
                                                 'field' => tep_draw_input_field('intensepay_cvv2')),
      ));
    }

 
    function goEMerchant($txn) {
//$log=fopen(DIR_FS_CACHE.'paylog','a');
      $gwhost="secure.goemerchant.com";
      $gwpath="/secure/gateway/xmlgateway.aspx";
      $xml='<?xml version="1.0" encoding="UTF-8"?>';
      $xml.="<TRANSACTION>";
      $xml.="<FIELDS>";
      foreach ($txn AS $key=>$val) $xml.='<FIELD KEY="'.htmlspecialchars($key).'"'.(isset($val)?'>'.htmlspecialchars($val).'</FIELD>':' />');
      $xml.="</FIELDS>";
      $xml.="</TRANSACTION>";
//      mail('jim@intensecart.com','goemerchant',$xml);
      $sk=@fsockopen("ssl://".$gwhost,443);
      if (!$sk) return NULL;
//fwrite($log,$xml."\n\n\n\n");
      fwrite($sk,"POST https://$gwhost$gwpath HTTP/1.0\r\n");
      fwrite($sk,"Content-Type: text/xml\r\n");
      fwrite($sk,"Content-Length: ".strlen($xml)."\r\n");
      fwrite($sk,"\r\n");
      fwrite($sk,$xml);
      while (preg_match('/^\w/',fgets($sk,1024)));
      $rsp=@fread($sk,65535);
      $result=Array();
      if (preg_match_all('!<FIELD\s+KEY="(\w+)"\s*(/>|>(.*?)</FIELD>)!',$rsp,$rsparse)) for ($i=0;isset($rsparse[0][$i]);$i++) $result[$rsparse[1][$i]]=$rsparse[3][$i];
//fwrite($log,$rsp."\n\n\n\n");
//fclose($log);
      return $result;
    }
    function goEMerchantStatus($st) {
      switch ($st) {
	case 1: return 'complete';
	case 2: return 'declined';
	default: return 'error';
      }
    }

    function acquirePaymentInfo() {
      $this->payinfo=Array('cc_type'=>$_POST['intensepay_cc_type'],'cc_number'=>$_POST['intensepay_cc_num'],'cc_owner'=>$_POST['intensepay_cc_owner'],'exp_month'=>$_POST['intensepay_exp_month'],'exp_year'=>$_POST['intensepay_exp_year'],'cc_cvv2'=>$_POST['intensepay_cvv2']);
    }

    function doAuthSale($amount,&$order,$txn='auth') {
      if (!$this->verifyPaymentInfo()) return false;
      $tid=$this->startTransaction($txn,$amount);
      $rs=$this->goEMerchant(Array(
	"merchant"=>$this->getConf('merchant'),
	"password"=>$this->getConf('merchant_pass'),
	"gateway_id"=>$this->getConf('gateway_id'),
	"operation_type"=>$txn,
	"order_id"=>$this->getConf('order_prefix').$this->payid,
	"total"=>sprintf("%.2f",$amount),
	"card_name"=>$this->payinfo['cc_type'],
	"card_number"=>$this->payinfo['cc_number'],
	"card_exp"=>$this->payinfo['exp_month'].$this->payinfo['exp_year'],
	"cvv2"=>$this->payinfo['cc_cvv2'],
	"owner_name"=>$this->payinfo['cc_owner'],
	"owner_street"=>$order->billing['street_address'],
	"owner_city"=>$order->billing['city'],
	"owner_state"=>($order->billing['state']!=''?$order->billing['state']:'none'),
	"owner_zip"=>$order->billing['postcode'],
	"owner_country"=>$order->billing['country'],
	"owner_email"=>$order->customer['email_address'],
	"owner_phone"=>$order->customer['telephone'],
	"recurring"=>0,
	"recurring_type"=>NULL,
	"remote_ip_address"=>$_SERVER['REMOTE_ADDR']
      ));
      $st=$this->goEMerchantStatus($rs['status']);
      $this->finishTransaction($tid,$st,$rs);
      if ($st!='complete') {
        $this->storePaymentInfo($order);
	$this->setError($rs['error']?$rs['error']:$rs['auth_response']);
	$this->setPaymentStatus('error');
	return false;
      } else {
	$this->payinfo['cc_cvv2']=NULL;
        $this->storePaymentInfo($order);
	$this->setPaymentStatus(($txn=='sale'?'complete':'pending'),$rs['reference_number']);
	return $rs['auth_code'];
      }
    }

    function cancelPayment() {
      if ($this->status=='pending') {
        $tid=$this->startTransaction('void',NULL);
        $rs=$this->goEMerchant(Array(
	  "merchant"=>$this->getConf('merchant'),
	  "password"=>$this->getConf('merchant_pass'),
	  "gateway_id"=>$this->getConf('gateway_id'),
	  "operation_type"=>'void',
	  "total_number_transactions"=>1,
	  "reference_number1"=>$this->txnid,
        ));
        $st=$this->goEMerchantStatus($rs['status1']);
        $this->finishTransaction($tid,$st,$rs);
        if ($st!='complete') {
	  $this->setError($rs['error1']?$rs['error1']:$rs['response1']);
	  return false;
        } else {
	  $this->amount=0;
	  $this->setPaymentStatus('cancelled');
	  return true;
        }
      } else if ($this->status=='complete') $this->refundPayment($this->amount);
      return NULL;
    }

    function authorizePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'complete':
	  return $this->amount;
	case 'incomplete':
	  $this->doAuthSale($amount,$order,'auth');
	  return $this->getAuthAmount();
	default: return NULL;
      }
    }

    function settlePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	  if ($amount>0.005) $this->settleBatch(Array(Array('module'=>&$this,'amount'=>min($this->amount,$amount))));
	  else $this->cancelPayment($order);
	  break;
	case 'incomplete':
	  $this->doAuthSale($amount,$order,'sale');
	  break;
	case 'complete':
	  if ($amount<$this->amount-0.005) $this->refundPayment($this->amount-$amount);
	  break;
	default: return NULL;
      }
      return $this->getSettleAmount();
    }
    
    function capturePayment($amount,&$order) {
      if (!$this->initPayment($amount,$order)) return NULL;
      return $this->getConf('txn_type')=='sale'?$this->settlePayment($amount,$order):$this->authorizePayment($amount,$order);
    }

    function settleBatch($batch) {
      $tid=$this->startTransaction('settle',$batch);
      $txn=Array(
	"merchant"=>$this->getConf('merchant'),
	"password"=>$this->getConf('merchant_pass'),
	"gateway_id"=>$this->getConf('gateway_id'),
	"operation_type"=>'settle',
	"total_number_transactions"=>sizeof($batch),
      );
      $idx=0;
      foreach ($batch AS $bt) {
	$idx++;
	$txn['reference_number'.$idx]=$bt['module']->txnid;
	$txn['settle_amount'.$idx]=sprintf("%.2f",$bt['amount']);
      }
      $rs=$this->goEMerchant($txn);
      $idx=0;
      foreach ($batch AS $idx=>$bt) {
	$idx++;
	$st=$this->goEMerchantStatus($rs['status'.$idx]);
        if ($st!='complete') {
	  $bt['module']->setError($rs['error'.$idx]?$rs['error'.$idx]:$rs['response'.$idx]);
        } else {
	  $bt['module']->amount=$rs['settle_amount'.$idx];
	  $bt['module']->setPaymentStatus('complete');
	  $batch[$idx]['status']=$st;
	}
      }
      $this->finishTransaction($tid,$batch,$rs);
    }

    function refundPayment($amount) {
      if ($this->status!='complete') return NULL;
      $tid=$this->startTransaction('credit',$amount);
      $rs=$this->goEMerchant(Array(
	"merchant"=>$this->getConf('merchant'),
	"password"=>$this->getConf('merchant_pass'),
	"gateway_id"=>$this->getConf('gateway_id'),
	"operation_type"=>'credit',
	"total_number_transactions"=>1,
	"reference_number1"=>$this->txnid,
	"credit_amount1"=>sprintf("%.2f",$amount),
      ));
      $st=$this->goEMerchantStatus($rs['status1']);
      $this->finishTransaction($tid,$st,$rs);
      if ($st!='complete') {
	$this->setError($rs['error1']);
	return false;
      } else {
	$this->amount-=$rs['credit_amount1'];
	$this->setPaymentStatus('complete');
	return true;
      }
    }

    function processBatch() {
      global $goEMerchantSettleBatch;
      if (isset($goEMerchantSettleBatch)) {
	$this->settleBatch($goEMerchantSettleBatch);
	$goEMerchantSettleBatch=NULL;
      }
    }

    function isRecurrable() {
      return true;
    }

    function validateConf($key,$val) {
      switch ($key) {
      case 'merchant':
	if ($val=='') return 'Merchant id cannot be empty';
	break;
      }
      return NULL;
    }

    function isReady() {
      return true;
    }

    function listConf() {
      return Array(
	'merchant'=>Array('title'=>'Merchant ID','desc'=>'','default'=>''),
        'merchant_pass'=>Array('title'=>'Merchant Password','desc'=>'','default'=>''),
        'gateway_id'=>Array('title'=>'Gateway ID','desc'=>'','default'=>''),
        'order_prefix'=>Array('title'=>'Order ID Prefix','desc'=>'','default'=>''),
        'txn_type'=>Array('title'=>'Transaction Type','desc'=>'','type'=>'radio','values'=>Array('sale'=>'Authorize&amp;Sale','auth'=>'Authorize Only'),'default'=>'sale'),
      );
    }
  }
?>
