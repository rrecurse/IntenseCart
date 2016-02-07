<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');

  class payment_amazonSeller extends IXpayment {

 function payment_amazonSeller() {
    }

 function getName() {
      return "Amazon Seller API";
    }

 function paymentBox() {
      return NULL;
    }

    function saveTransaction($status,$amount,$txtype='none') {
      $tid=$this->startTransaction($txtype,$amount);
      $this->finishTransaction($tid,'complete',NULL);
      $this->setPaymentStatus($status);
error_log(print_r('function saveTransaction triggered from /common/modules/payment/payment_amazonSeller $tld = '.$tid.' $status = ' .$status, TRUE));
    }

 function authorizePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'complete':
	  return $this->amount;
	case 'incomplete':
	  //$this->saveCard($amount,$order);
	  return $this->getAuthAmount();
	default: return NULL;
      }
    }

 function settlePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	  break;
	case 'incomplete':
	  $this->saveCard($amount,$order);
	  break;
	case 'complete':
	  if ($amount<$this->amount-0.005) $this->refundPayment($this->amount-$amount);
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
		$this->finishTransaction($tid,$st,$rs);
	    $this->amount-=$amount;
	    $this->setPaymentStatus('complete');
	  break;
	default: return NULL;
      }
      return $this->getSettleAmount();
    }


function cancelPayment($order) {
	$this->amount = 0;
	//error_log(print_r('function cancelPayment of /common/modules/payment/payment_amazonSeller::: $amount = ' . $amount, TRUE));
	  return $this->setPaymentStatus('cancelled');
	}

function capturePayment($amount,&$order) {
      if (!$this->initPayment($amount,$order)) return NULL;
      return $this->authorizePayment($amount,$order);
    }

 function isReady() {
      return true;
    }

//$pay->settlePayment($run,$this);
  }
?>
