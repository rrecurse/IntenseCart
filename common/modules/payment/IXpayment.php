<?php

class IXpayment extends IXmodule {

	var $payid=NULL;
	var $error=NULL;
	var $status='incomplete';
	var $txnid=NULL;
	var $amount=0;

	function getTitle() {
    	return 'Credit Card';
	}

	function setPaymentStatus($st,$txnid=NULL) {

    	$this->status = $st;

		if(isset($txnid)) $this->txnid=$txnid;

    	if ($this->payid) {
			tep_db_query("UPDATE ".TABLE_PAYMENTS." 
						  SET status='".addslashes($st)."', 
						  amount='".addslashes($this->amount)."', 
						  ref_id='".addslashes($this->txnid)."', 
						  date_processed=NOW() 
						  WHERE payments_id='".addslashes($this->payid)."'
						");
		}

	}

	function setError($er) {

		$this->error = $er;

		if($this->payid) {
			tep_db_query("UPDATE ".TABLE_PAYMENTS." 
						  SET extra_info = CONCAT(IF(extra_info IS NULL,'', CONCAT(extra_info,'; ---- ')),'".addslashes($er)."') 
						  WHERE payments_id = '".addslashes($this->payid)."'
						 ");

			if(!is_null($er)) {
				error_log(addslashes($er).' - '. $_SERVER['REMOTE_ADDR'], 1, 'support@zwaveproducts.com');
			}

		} else {
			return NULL;
		}
	}

	function verifyPaymentInfo() {
    	return NULL;
	}


	function startTransaction($type,$amts,$data=NULL) {

    	tep_db_query("INSERT INTO payment_transactions (trans_type,trans_method,date_started,trans_data) 
					  VALUES ('".$type."','".get_class($this)."',NOW(),".(isset($data)?"'".addslashes(serialize($data))."'":'NULL').")
					");

		$tid = tep_db_insert_id();

		if(!is_array($amts)) $amts = array(array('module' => &$this, 'amount' => $amts));

		$recs = array();

		foreach ($amts AS $rf) $recs[]="('$tid','".$rf['module']->payid."','".addslashes($rf['amount'])."','incomplete')";

		tep_db_query("INSERT INTO payments_to_transactions (trans_id,payments_id,trans_amount,trans_status) VALUES ".join(',',$recs));

		return $tid;
	}

	function finishTransaction($tid,$statuses,$response=NULL,$memo=NULL) {

		tep_db_query("UPDATE payment_transactions 
						SET date_finished = NOW(),
						trans_response = ".(isset($response) ? "'".addslashes(serialize($response))."'" : 'NULL').",
						trans_memo = ".(isset($memo) ? "'".addslashes($memo)."'" : 'NULL')." 
					  WHERE trans_id='".addslashes($tid)."'
					 ");

		if(!is_array($statuses)) {
			$statuses = array(array('module' => &$this,'status' => $statuses));
		}


		foreach ($statuses AS $rf) {

			tep_db_query("UPDATE payments_to_transactions 
						  SET trans_status = '".addslashes($rf['status'])."' 
						  WHERE trans_id = '".addslashes($tid)."' 
						  AND payments_id='".$rf['module']->payid."'");

			if($rf['status']=='error') $this->error=$response;
		}
	}

	function paymentBox() {

    	return '<script type="text/javascript">
					function onSubmit_'.get_class($this).'() {	
						return true;
					}
				</script>';
	}

	function cancelPayment() {
    	return NULL;
	}

	function refundPayment($amount) {
    	return NULL;
	}

	function settlePayment($amount=NULL) {
    	return NULL;
	}

	function capturePayment($amount,&$order) {
    	if (!$this->initPayment($amount,$order)) return NULL;
		return $this->settlePayment($amount,$order);
	}

	function getAuthAmount() {
    	switch ($this->status) {
			case 'pending':
			case 'complete':
				return $this->amount;
			default: return 0;
		}
	}

	function getSettleAmount() {
		return ($this->status == 'complete' ? $this->amount : 0);
	}

	function initPayment($amount,&$order) {
		if(isset($this->payid)) return NULL;
		$this->amount = $amount;

		tep_db_query("INSERT INTO ".TABLE_PAYMENTS." (method,amount,status,date_created) 
					  VALUES ('".get_class($this)."','".addslashes($this->amount)."','".addslashes($this->status)."',NOW())
					");

		return $this->payid = tep_db_insert_id();
	}

	function finishPayment(&$order) {
		if (!$this->payid) return NULL;

		tep_db_query("UPDATE ".TABLE_PAYMENTS." SET orders_id='".addslashes($order->orderid)."' WHERE payments_id='".addslashes($this->payid)."'");
		return true;
	}

	function getError() {
    	return $this->error;
	}

	function makeForm($frm) {

		// # strip whitespace(space, tab or newline).
		if(!empty($frm)) preg_replace('/\s+/', ' ', $frm);

    	$html="<table>";

	    foreach ($frm AS $row) {
			$html.="<tr><td>".$row['title']."</td><td>".$row['field']."</td></tr>";
		}

    	$html.="</table>";

	    return $html;
	}

	function loadPayment($payid) {
    	$row = tep_db_fetch_array(tep_db_query("SELECT * FROM ".TABLE_PAYMENTS." WHERE payments_id='".addslashes($payid)."'"));
		if (!$row) return NULL;
		return $this->loadPaymentFromRow($row);
	}

	function loadPaymentFromRow($row) {
    	$obj=tep_module($row['method'],'payment');
		if (isset($obj)) {
			$obj->payid=$row['payments_id'];
			$obj->status=$row['status'];
			$obj->txnid=$row['ref_id'];
			$obj->amount=$row['amount'];
		}
    
		return $obj;    
	}

	function storePaymentInfo(&$order) {
		$ac = $order->info['cc_number'] = str_repeat('*',strlen($this->payinfo['cc_number'])-4).substr($this->payinfo['cc_number'],-4);
		
		if (!$order->info['payment_method']) {
			$order->info['payment_method']=get_class($this);
			$order->info['cc_type']=$this->payinfo['cc_type'];
			$order->info['cc_owner']=$this->payinfo['cc_owner'];
			$order->info['cc_number']=$ac;
			$order->info['cc_expires']=$this->payinfo['exp_month'].$this->payinfo['exp_year'];
		}

		$cr = tep_module('crypto');

		if ($cr) $enc = $cr->encrypt($this->payinfo);

		if (isset($enc)) tep_db_query("INSERT INTO payment_info (payments_id,payment_info,payment_acct) 
									   VALUES ('".addslashes($this->payid)."','".addslashes($enc)."','".addslashes($ac)."')
									  ");
	}

	function checkStoredPaymentInfo() {
    	return tep_db_read("SELECT payment_acct FROM payment_info WHERE payments_id='".addslashes($this->payid)."'",NULL,'payment_acct');
	}

	function recurPayment($class=NULL) {

    	$row = tep_db_fetch_array(tep_db_query("SELECT * FROM payment_info WHERE payments_id='".addslashes($this->payid)."'"));

		if(!$row) return NULL;

		$cr = tep_module('crypto');

		if($cr) $dec=$cr->decrypt($row['payment_info']);

		if (isset($dec)) {
			if (!$class) $class=get_class($this);

			$pay = tep_module($class,'payment');


			$pay->payinfo = $dec;

			if(!isset($pay->payinfo['cc_type'])) $pay->payinfo['cc_type'] = 'Visa';
			
			return $pay;
		}

		return NULL;
	}

	function isRecurrable() {
    	return false;
	}

	function getExternalCheckoutButton() {
    	return NULL;
	}
}


class IXpaymentCC extends IXpayment {

	function verifyPaymentInfo() {

    	if (!isset($this->payinfo)) {
			$this->acquirePaymentInfo();
		}

		if (!isset($this->payinfo['cc_number']) || strlen($this->payinfo['cc_number']) < 15) {
			return $this->setError('Bad Card Number');
		}

		return true;
	}
}

?>