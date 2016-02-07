<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  class payment_manual extends IXpayment {

    function getName() {
      return "Manual / Cash";
    }

    function paymentBox() {

// # created a random comments box as part of the form feilds used for manual / cash payments
// # commented out for obvious reasons.

/*      return $this->makeForm(array(array('title' => "Comments",
										 'field' => tep_draw_textarea_field('payment_manual_comments','')
										)
								  )
							);
*/
    }

 
    function saveTransaction($status,$amount) {
      $tid=$this->startTransaction('manual',$amount);
      $this->finishTransaction($tid,'complete',$_POST['payment_manual_comments']);
      $this->setPaymentStatus($status);
    }
    
    
    function authorizePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'complete':
	  return $this->amount;
	case 'incomplete':
	  $this->amount=$amount;
	  $this->saveTransaction('pending',$amount);
	  return $this->getAuthAmount();
	default: return NULL;
      }
    }

    function settlePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'incomplete':
	case 'complete':
          $this->amount=$amount;
	  $this->saveTransaction('complete',$amount);
	  break;
	default: return NULL;
      }
      return $this->getSettleAmount();
    }

    function cancelPayment($order) {
      $this->amount=$amount;
      $this->saveTransaction('cancelled',NULL);
    }
    
    function isReady() {
      return true;
    }

    function validateConf($key,$val) {
      return NULL;
    }

    function listConf() {
      return Array();
    }
  }
?>