<?php
  include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
  class payment_openbalance1 extends IXpayment {

    function getName() {
      return "Open Balance";
    }

    function getTitle() {
      return "Open Balance";
    }

    function paymentBox() {
      return $this->makeForm(Array(
					   array('title' => "Open Balance Code",
						 'field' => tep_draw_input_field('openbalance_code', '')),
      ));
    }

 
    function acquirePaymentInfo() {
      $this->payinfo=Array('code'=>$_POST['openbalance_code']);
    }

    function saveTransaction($status,$amount,$txtype='none') {
      $tid=$this->startTransaction($txtype,$amount);
      $this->finishTransaction($tid,'complete',$_POST['payment_manual_comments']);
      $this->setPaymentStatus($status);
    }
    
    function chkCustomer(&$order) {
      $code=IXdb::read("SELECT customers_extra_value FROM customers_extra WHERE customers_id='{$order->customer['customers_id']}' AND customers_extra_key='openbalance_code'",NULL,'customers_extra_value');
      if ($code=='' || $code!=$_POST['openbalance_code']) return $this->setError("Code doesn't match");
      $order->info['payment_method']=get_class($this);
      return true;
    }
    
    function authorizePayment($amount,&$order,$batch=0) {
      switch ($this->status) {
	case 'pending':
	case 'complete':
	  return $this->amount;
	case 'incomplete':
	  if (!$this->chkCustomer($order)) break;
	  $this->amount=$amount;
	  $this->saveTransaction('pending',$amount,'auth');
	  return $this->getAuthAmount();
	default: return NULL;
      }
      return $this->getAuthAmount();
    }

    function settlePayment($amount,&$order,$batch=0) {
//    print_r($order);
//    exit;
      switch ($this->status) {
	case 'incomplete':
	  if (!$this->chkCustomer($order)) break;
	case 'pending':
          $this->amount=$amount;
	  $this->saveTransaction('complete',$amount,'settle');
	  break;
	case 'complete':
	  $diff=$amount-$this->amount;
	  if (abs($diff)>0.005) $this->saveTransaction('complete',$diff,'adjust');
	  break;
	default: return NULL;
      }
      return $this->getSettleAmount();
    }

    function cancelPayment(&$order) {
      $this->amount=0;
      $this->saveTransaction('cancelled',NULL,'void');
    }
    
    function validateConf($key,$val) {
      switch ($key) {
        default: break;
      }
      return NULL;
    }

    function isReady() {
      return true;
    }

    function listConf() {
      return Array(
      );
    }
  }
  
// Customer Account Extn
  class custaccount_openbalance1 extends IXmodule {
    function getName() {
      return 'Open Balance Payment';
    }
    function getAdminFields($cus_id) {
      if (!IXdb::read("SELECT customers_group_id FROM customers WHERE customers_id='$cus_id'",NULL,'customers_group_id'))
	  {
	  //echo "RETURN NULL<br/>"; 
	  return NULL;
	  }
      $flds=IXdb::read("SELECT * FROM customers_extra WHERE customers_id='$cus_id'",'customers_extra_key','customers_extra_value');
//	  echo "Did getAdminFields()<br/>";
      return Array(
        Array('title'=>'Open Balance Code','html'=>'<input type="text" name="extra[openbalance_code]" value="'.htmlspecialchars($flds['openbalance_code']).'">'),
      );
    }
    function isReady() {
      return true;
    }
  }
?>
