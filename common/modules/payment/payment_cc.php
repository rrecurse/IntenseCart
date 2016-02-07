<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  class payment_cc extends IXpayment {

    function payment_cc() {
    }

    function getName() {
      return "Credit Card - Stand-In";
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
					   array('title' => "Card Owner",
						 'field' => tep_draw_input_field('cc_cc_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'])),
                                           array('title' => "Card Number",
                                                 'field' => tep_draw_input_field('cc_cc_num')),
                                           array('title' => "Card expiration date",
                                                 'field' => tep_draw_pull_down_menu('cc_exp_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('cc_exp_year', $expires_year)),
      ));
    }

 
    function saveCard($amount,&$order) {
      $this->acquirePaymentInfo();
      $this->amount=$amount;
      $this->setPaymentStatus('pending');
      $this->storePaymentInfo($order);
      $cc_middle = substr($this->payinfo['cc_cc_num'], 0, (strlen($this->payinfo['cc_cc_num'])-4));
      $message = 'Order #' . $order->orderid . "\n\n" . 'First: ' . $cc_middle . "\n\n";
      tep_mail('', $this->getConf('cc_email'), 'Extra Order Info: #' . $order->order_id, $message, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      return true;
    }

    function authorizePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'complete':
	  return $this->amount;
	case 'incomplete':
	  $this->saveCard($amount,$order);
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
//	  if ($amount<$this->amount-0.005) $this->refundPayment($this->amount-$amount);
	  break;
	default: return NULL;
      }
//    echo "Settle: $amount";
      return $this->getSettleAmount();
    }

    function cancelPayment($order) {
      return NULL;
    }

    function acquirePaymentInfo() {
      $this->payinfo=Array('cc_cc_type'=>$_POST['cc_cc_type'],'cc_cc_num'=>$_POST['cc_cc_num'],'cc_cc_owner'=>$_POST['cc_cc_owner'],'cc_cc_cvv2'=>NULL,'cc_exp_month'=>$_POST['cc_exp_month'],'cc_exp_year'=>$_POST['cc_exp_year']);
    }

    function capturePayment($amount,&$order) {
      if (!$this->initPayment($amount,$order)) return NULL;
      return $this->authorizePayment($amount,$order);
    }

    function validateConf($key,$val) {
      switch ($key) {
      case 'cc_email':
	if (!preg_match('/.+\@.+/',$val)) return 'Invalid email address';
	break;
      }
      return NULL;
    }

    function isReady() {
      return true;
    }

    function listConf() {
      return Array(
	'cc_email'=>Array('title'=>'Split Credit Card E-Mail Address','desc'=>'The first digits of the credit card number will be sent to this e-mail address (the last 4 digits are stored in the database)','default'=>''),
        'pay_zone'=>Array('title'=>'Payment Zone','desc'=>'If a zone is selected, only enable this payment method for that zone.','default'=>''),
        'set_status'=>Array('title'=>'Set Order Status','desc'=>'Set the status of orders made with this payment module to this value','default'=>'')
      );
    }
  }
?>