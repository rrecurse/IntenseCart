<?php

class IXorder {

  function readItems() {
    if (isset($this->items) || !$this->orderid) return;
    $itms=IXdb::read("SELECT * FROM orders_items WHERE orders_id='{$this->orderid}' ORDER BY orders_items_id",Array('orders_items_id'));
    $rfs=IXdb::read("SELECT * FROM orders_items_refs WHERE orders_id='{$this->orderid}' ORDER BY orders_items_refs_id",Array('orders_items_id',NULL));
    $this->items=Array();
    foreach ($itms AS $iid=>$itm) $this->items[]=IXorderitem::loadFromRow($itm,$rfs[$iid]);
  }
  
  function appendItem(&$item) {
    $this->readItems();
    $rs=$item->checkItem($this->items,NULL,$this);
    if (!$rs) return NULL;
    if ($rs>1) $this->isDirty=1;
    return true;
  }
  
  function purgeItems() {
    foreach ($this->items AS $iidx=>$itm) $this->items[$iidx]->setFulfill(0);
    $this->items=Array();
    $this->isDirty=1;
  }
  
  function findItems($class,$ikey=NULL) {
    $this->readItems();
    $rs=Array();
    foreach ($this->items AS $iidx=>$itm)
      if ($this->items[$iidx]->getClass()==$class && $this->items[$iidx]->matchItemKey($ikey)) $rs[]=&$this->items[$iidx];
    return $rs;
  }
  
  function loadFromRow($row,$itms=NULL,$rfs=NULL) {

    if ($this->orderid) return NULL; // # Sanity check

    $this->orderid = $row['orders_id'];
    
    
    foreach (array('customer'=>'customers_','delivery'=>'delivery_','billing'=>'billing_') AS $s=>$md) {
      $this->$s=new IXaddress(array('name' => $row[$md.'name'],
				            		'company' => $row[$md.'company'],
				           			'address' => $row[$md.'street_address'],
									'address2' => $row[$md.'suburb'],
									'city' => $row[$md.'city'],
									'postcode' => $row[$md.'postcode'],
									'state' => $row[$md.'state'],
									'country' => $row[$md.'country'],
		));
	}
    
	}

	function saveOrder() {

		if(!empty($this->info['orders_source'])) { 
			$orders_source = $this->info['orders_source']; 
		} else { 
			$orders_source = (!empty($_SESSION['orders_source'])) ? $_SESSION['orders_source'] : '-';
		}

	    $data=array('currency' => $this->info['currency'],
					'currency_value' => $this->info['currency_value'],
					'date_purchased' => $this->info['date_purchased'],
					'local_time_purchased' => $this->info['local_time_purchased'],
					'local_timezone' => $this->info['local_timezone'],
					'orders_status' => $this->status,
					'requested_status' => $this->requested_status,
					'comments' => $this->info['comments'],
					'last_modified' => date('Y-m-d H:i:s'),
					'customers_id' => $this->customerid,
					'orders_source' => $orders_source
					);
		foreach (array('customers'=>&$this->customer,'delivery'=>&$this->delivery,'billing'=>&$this->billing) AS $sec=>$addr) {
			$data[$sec.'_name']=$addr->getFullName();
			$data[$sec.'_company']=$addr->getCompany();
			$data[$sec.'_street_address']=$addr->getAddress();
			$data[$sec.'_suburb']=$addr->getAddress2();
			$data[$sec.'_city']=$addr->getCity();
			$data[$sec.'_state']=$addr->getZoneName();
			$data[$sec.'_country']=$addr->getCountryName();
			$data[$sec.'_postcode']=$addr->getPostCode();
		}

    IXdb::store('insert','orders',$data);
    $this->orderid=IXdb::insert_id();

    $this->saveItems();
	$this->saveTotals();
}

	function saveItems() {
    	$this->readItems();
	    foreach ($this->items AS $iidx=>$itm) $this->items[$iidx]->saveItem($this);
	}

	function getOrderTotal() {
    	$rs = array();

		foreach ($this->items AS $idx=>$item) {
    		foreach ($this->items[$idx]->getOrderTotal() AS $ot) {
				foreach ($rs AS $ridx=>$r) {
					if($r['class']==$ot['class'] && $r['class_ref']==$ot['class_ref']) {
						$rs[$ridx]['amount']+=$ot['amount'];
						$ot=NULL;
						break;
					}
				}

				if($ot) {
					$rs[]=$ot;
				}
			}
    	}

		return $rs;
	}

	function saveTotals() {
    	foreach ($this->getOrderTotal() AS $idx=>$t) {

			$qry = array('class'=>$t['class'],
						 'value'=>$t['value'],
						 'text'=>$t['text'],
						 'title'=>$t['title'],
						 'sort_order'=>$idx,
						 'orders_id' => $this->orderid
						);
			IXdb::store('insert','orders_total',$qry);
		}
	}
  

	function query($order_id) {

		$order_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS . " WHERE orders_id = '" . tep_db_input($order_id) . "'");
		$order = tep_db_fetch_array($order_query);

		$totals_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '" . (int)$order_id . "' ORDER BY sort_order");

		while ($totals = tep_db_fetch_array($totals_query)) {
			if ($totals['class']=='ot_total') $total=$totals['value'];
	        $this->totals[] = array('title' => $totals['title'],
				              		'text' => $totals['text'],
									'class' => $totals['class'],
									'value' => $totals['value'],
									'id' =>  $totals['orders_total_id']
									);
		}

      $this->orderid = $order_id;

		if(!is_null($order['orders_source'])) { 
			$orders_source = $order['orders_source']; 
		} else { 
			$orders_source = (!empty($_SESSION['orders_source'])) ? $_SESSION['orders_source'] : '';
		}

      $this->info = array('currency' => $order['currency'],
						  'currency_value' => $order['currency_value'],
						  'shipping_method' => $order['shipping_method'],
						  'payment_method' => $order['payment_method'],
						  'cc_type' => $order['cc_type'],
						  'cc_owner' => $order['cc_owner'],
						  'cc_number' => $order['cc_number'],
						  'cc_expires' => $order['cc_expires'],
						  'date_purchased' => $order['date_purchased'],
						  'local_time_purchased' => $order['local_time_purchased'],
						  'local_timezone' => $order['local_timezone'],
						  'orders_status' => $order['orders_status'],
						  'ups_track_num' => $order['ups_track_num'],
						  'usps_track_num' => $order['usps_track_num'],
						  'fedex_track_num' => $order['fedex_track_num'],
						  'dhl_track_num' => $order['dhl_track_num'],
						  'comments' => $order['comments'],
						  'last_modified' => $order['last_modified'],
						  'tax_groups' => array(),
						  'total' => $total,
						  'orders_source' => $orders_source
						);

      $this->customer = array('name' => $order['customers_name'],
				            'company' => $order['customers_company'],
				            'id' => $order['customers_id'],
				            'street_address' => $order['customers_street_address'],
				            'suburb' => $order['customers_suburb'],
				            'city' => $order['customers_city'],
				            'postcode' => $order['customers_postcode'],
				            'state' => $order['customers_state'],
				            'country' => $order['customers_country'],
				            'format_id' => $order['customers_address_format_id'],
				            'telephone' => $order['customers_telephone'],
				            'fax' => $order['customers_fax'],
				            'email_address' => $order['customers_email_address']
						);

      $this->delivery = array('name' => $order['delivery_name'],
				            'company' => $order['delivery_company'],
				            'street_address' => $order['delivery_street_address'],
				            'suburb' => $order['delivery_suburb'],
				            'city' => $order['delivery_city'],
				            'postcode' => $order['delivery_postcode'],
				            'state' => $order['delivery_state'],
				            'country' => $order['delivery_country'],
				            'format_id' => $order['delivery_address_format_id']
						);

      $this->billing = array('name' => $order['billing_name'],
				           'company' => $order['billing_company'],
				           'street_address' => $order['billing_street_address'],
				           'suburb' => $order['billing_suburb'],
				           'city' => $order['billing_city'],
				           'postcode' => $order['billing_postcode'],
				           'state' => $order['billing_state'],
				           'country' => $order['billing_country'],
				           'format_id' => $order['billing_address_format_id']
						);

		$index = 0;
         
		$orders_products_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = '" . (int)$order_id . "'");
    
		while ($orders_products = tep_db_fetch_array($orders_products_query)) {
        
		
			$this->products[$index] = array('qty' => $orders_products['products_quantity'],
									'name' => $orders_products['products_name'],
									'id' => $orders_products['products_id'],
									'return' => $orders_products['products_returned'],
									'model' => $orders_products['products_model'],
									'tax' => $orders_products['products_tax'],
									'price' => $orders_products['products_price'],
									'cost_price' => $orders_products['cost_price'],
									'weight' => $orders_products['products_weight'],
									'final_price' => $orders_products['final_price'],
									'stock_qty' => $orders_products['products_stock_quantity'],
									'free_shipping' => $orders_products['free_shipping'],
									'separate_shipping' => $orders_products['separate_shipping'],
									'exchange' => $orders_products['products_exchanged'],
									'exchange_id' => $orders_products['products_exchanged_id'],
									'orders_products_id' => $orders_products['orders_products_id'],
									'exchange_returns_id' => $orders_products['exchange_returns_id'],
									'warehouse_id' => $orders_products['warehouse_id']
									);

		if (!isset($this->info['tax_groups']['Tax'])) $this->info['tax_groups']['Tax']=0;

		$this->info['tax_groups']['Tax']+=$orders_products['products_tax'];
        $subindex = 0;

        $attributes_query = tep_db_query("SELECT products_options, products_options_values, options_values_price, price_prefix FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " WHERE orders_id = '" . (int)$order_id . "' AND orders_products_id = '" . (int)$orders_products['orders_products_id'] . "'");

        if (tep_db_num_rows($attributes_query)) {
        	while ($attributes = tep_db_fetch_array($attributes_query)) {
            	$this->products[$index]['attributes'][$subindex] = array('option' => $attributes['products_options'],
							 				 				             'value' => $attributes['products_options_values'],
				 							 				             'prefix' => $attributes['price_prefix'],
				 							 				             'price' => $attributes['options_values_price']
																	);

            $subindex++;
			}
        }
        $index++;
      }
      
		$this->returns=Array();
      	$r_qry=tep_db_query("SELECT * FROM ".TABLE_RETURNS_PRODUCTS_DATA." rp 
							LEFT JOIN ".TABLE_RETURNS." r ON rp.returns_id=r.returns_id 
							LEFT JOIN refund_payments rf ON rf.returns_id=r.returns_id 
							WHERE rp.order_id='" . (int)$order_id . "'
							");

      while ($r_row=tep_db_fetch_array($r_qry)) {
        $this->returns[] = Array(
			'returns_products_id' => $r_row['returns_products_id'],
			'returns_id' => $r_row['returns_id'],
			'id' => $r_row['products_id'],
			'qty' => $r_row['products_quantity'],
			'rma' => $r_row['rma_value'],
			'restock' => $r_row['restock_quantity'],
			'refund_amount' => $r_row['refund_amount'],
			'exchange_amount' => $r_row['exchange_amount'],
			'refund_shipping_amount' => $r_row['refund_shipping_amount'],
			'refund_shipping' => $r_row['refund_shipping'],
			);
      }

}
	
	
	function getPayments() {
		include_once(DIR_FS_COMMON.'modules/payment/IXpayment.php');
		if (!isset($this->payments)) {
			$this->payments=Array();
			if (isset($this->orderid)) {
				$pay_qry = tep_db_query("SELECT * FROM ".TABLE_PAYMENTS." WHERE orders_id='".$this->orderid."'");
				while ($pay_row=tep_db_fetch_array($pay_qry)) {
					$pay = IXpayment::loadPaymentFromRow($pay_row);
					if(isset($pay)) $this->payments[] = $pay;
				}
			}
		}
		return $this->payments;
	}


	function cart(&$cart) {
      global $customer_id, $sendto, $billto, $languages_id, $currency, $currencies, $shipping, $payment;

      $this->content_type = $cart->get_content_type();

      $customer_address_query = tep_db_query("select c.customers_firstname, c.customers_lastname, c.customers_telephone, c.customers_fax, c.customers_email_address, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, co.countries_id, co.countries_name, co.countries_iso_code_2, co.countries_iso_code_3, co.address_format_id, ab.entry_state from " . TABLE_CUSTOMERS . " c, " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) left join " . TABLE_COUNTRIES . " co on (ab.entry_country_id = co.countries_id) where c.customers_id = '" . (int)$customer_id . "' and ab.customers_id = '" . (int)$customer_id . "' and c.customers_default_address_id = ab.address_book_id");
      $customer_address = tep_db_fetch_array($customer_address_query);

      $shipping_address_query = tep_db_query("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int)$customer_id . "' and ab.address_book_id = '" . (int)$sendto . "'");
      $shipping_address = tep_db_fetch_array($shipping_address_query);
      
      $billing_address_query = tep_db_query("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int)$customer_id . "' and ab.address_book_id = '" . (int)$billto . "'");
      $billing_address = tep_db_fetch_array($billing_address_query);

      $tax_address_query = tep_db_query("select ab.entry_country_id, ab.entry_zone_id from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) where ab.customers_id = '" . (int)$customer_id . "' and ab.address_book_id = '" . (int)($this->content_type == 'virtual' ? $billto : $sendto) . "'");
      $tax_address = tep_db_fetch_array($tax_address_query);

		$orders_source = (!empty($_SESSION['orders_source'])) ? $_SESSION['orders_source'] : '';

		$this->info = array('orders_status' => NULL,
				      	'currency' => $currency,
					      'currency_value' => $currencies->currencies[$currency]['value'],
    					  'payment_method' => $payment,
        	              'cc_type' => (isset($GLOBALS['cc_type']) ? $GLOBALS['cc_type'] : ''),
            	          'cc_owner' => (isset($GLOBALS['cc_owner']) ? $GLOBALS['cc_owner'] : ''),
                	      'cc_number' => '****************',
				  	      'cc_expires' => 'MMYY',
				          'shipping_method' => $shipping['title'],
					      'shipping_cost' => $shipping['cost'],
    					  'subtotal' => 0,
        	              'tax' => 0,
            	          'tax_groups' => array(),
                  		  'comments' => (isset($GLOBALS['comments']) ? $GLOBALS['comments'] : ''),
						  'orders_source' => $orders_source
					 	);


      $this->customer = array('customers_id'=>$customer_id,
    			     		'firstname' => $customer_address['customers_firstname'],
				            'lastname' => $customer_address['customers_lastname'],
				            'company' => $customer_address['entry_company'],
				            'street_address' => $customer_address['entry_street_address'],
				            'suburb' => $customer_address['entry_suburb'],
				            'city' => $customer_address['entry_city'],
				            'postcode' => $customer_address['entry_postcode'],
				            'state' => (tep_not_null($customer_address['entry_state'])) ? $customer_address['entry_state'] : $customer_address['zone_name'],
				            'zone_id' => $customer_address['entry_zone_id'],
				            'country' => $customer_address['countries_name'],
				            'format_id' => $customer_address['address_format_id'],
				            'telephone' => $customer_address['customers_telephone'],
				            'fax' => $customer_address['customers_fax'],
				            'email_address' => $customer_address['customers_email_address']
						);

      $this->delivery = array('firstname' => $shipping_address['entry_firstname'],
				            'lastname' => $shipping_address['entry_lastname'],
				            'company' => $shipping_address['entry_company'],
				            'street_address' => $shipping_address['entry_street_address'],
				            'suburb' => $shipping_address['entry_suburb'],
				            'city' => $shipping_address['entry_city'],
				            'postcode' => $shipping_address['entry_postcode'],
				            'state' => (tep_not_null($shipping_address['entry_state'])) ? $shipping_address['entry_state'] : $shipping_address['zone_name'],
				            'zone_id' => $shipping_address['entry_zone_id'],
				            'country' => $shipping_address['countries_name'],
				            'country_id' => $shipping_address['entry_country_id'],
				            'format_id' => $shipping_address['address_format_id']
						);

      $this->billing = array('firstname' => $billing_address['entry_firstname'],
				           'lastname' => $billing_address['entry_lastname'],
				           'company' => $billing_address['entry_company'],
				           'street_address' => $billing_address['entry_street_address'],
				           'suburb' => $billing_address['entry_suburb'],
				           'city' => $billing_address['entry_city'],
				           'postcode' => $billing_address['entry_postcode'],
				           'state' => (tep_not_null($billing_address['entry_state'])) ? $billing_address['entry_state'] : $billing_address['zone_name'],
				           'zone_id' => $billing_address['entry_zone_id'],
				           'country' => $billing_address['countries_name'],
				           'country_id' => $billing_address['entry_country_id'],
				           'format_id' => $billing_address['address_format_id']
						);

      $index = 0;

      $this->returns = array();

      $products = $cart->get_products();

      for ($i=0, $n=sizeof($products); $i<$n; $i++) {

			$this->products[$index] = array(
									'qty' => $products[$i]['quantity'],
									'name' => $products[$i]['name'],
									'model' => $products[$i]['model'],
									'tax' => tep_get_tax_rate($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
									'tax_description' => tep_get_tax_description($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
									'price' => $products[$i]['price'],
									'final_price' => $products[$i]['price'],
									'weight' => $products[$i]['weight'],
									'free_shipping' => $products[$i]['products_free_shipping'],
									'separate_shipping' => $products[$i]['products_separate_shipping'],
									'id' => $products[$i]['id'],
									'orders_products_id' => NULL,
									'warehouse_id' => $products[$i]['warehouse_id']
									);


	// # Separate Pricing Per Customer
	if(!tep_session_is_registered('sppc_customer_group_id')) { 
		$customer_group_id = '0';
	} else {
		$customer_group_id = $sppc_customer_group_id;
  }

	if ($customer_group_id != '0'){
		$orders_customers_price = tep_db_query("SELECT customers_group_price FROM " . TABLE_PRODUCTS_GROUPS . " where customers_group_id = '". $customer_group_id . "' and products_id = '" . $products[$i]['id'] . "'");
		$orders_customers = tep_db_fetch_array($orders_customers_price);

		if($orders_customers = tep_db_fetch_array($orders_customers_price)) {
			$this->products[$index] = array('price' => $orders_customers['customers_group_price'],'final_price' => $orders_customers['customers_group_price'] + $cart->attributes_price($products[$i]['id']));
		}
	}
	// # END Separate Pricing Per Customer


        if ($products[$i]['attributes']) {
          $subindex = 0;
          reset($products[$i]['attributes']);
          while (list($option, $value) = each($products[$i]['attributes'])) {

            $this->products[$index]['attributes'][$subindex] = array('option' => $option,
				 				 				             'value' => $value,
				 				 				             'option_id' => $option,
				 				 				             'value_id' => $value,
				 				 				             'prefix' => '',
				 				 				             'price' => 0,
				 				 				             'products_id' => 0,
				 				 				             'track_stock' => 0
																);
            $subindex++;
          }
        }

        $shown_price = tep_add_tax($this->products[$index]['final_price'], $this->products[$index]['tax']) * $this->products[$index]['qty'];
        $this->info['subtotal'] += $shown_price;

        $products_tax = $this->products[$index]['tax'];
        $products_tax_description = $this->products[$index]['tax_description'];

	global $sppc_customer_group_show_tax;
        if(!tep_session_is_registered('sppc_customer_group_show_tax')) { 
        $customer_group_show_tax = '1';
        } else {
        $customer_group_show_tax = $sppc_customer_group_show_tax;
        }		
        if (DISPLAY_PRICE_WITH_TAX == 'true' && $customer_group_show_tax == '1') {
	

          $this->info['tax'] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          if (isset($this->info['tax_groups']["$products_tax_description"])) {
            $this->info['tax_groups']["$products_tax_description"] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          } else {
            $this->info['tax_groups']["$products_tax_description"] = $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          }
        } else {
          $this->info['tax'] += ($products_tax / 100) * $shown_price;
          if (isset($this->info['tax_groups']["$products_tax_description"])) {
            $this->info['tax_groups']["$products_tax_description"] += ($products_tax / 100) * $shown_price;
          } else {
            $this->info['tax_groups']["$products_tax_description"] = ($products_tax / 100) * $shown_price;
          }
        }

        $index++;
      }

	global $sppc_customer_group_show_tax;
        if(!tep_session_is_registered('sppc_customer_group_show_tax')) { 
        $customer_group_show_tax = '1';
        } else {
        $customer_group_show_tax = $sppc_customer_group_show_tax;
        }	
        if ((DISPLAY_PRICE_WITH_TAX == 'true') && ($customer_group_show_tax == '1')) {	

        $this->info['total'] = $this->info['subtotal'] + $this->info['shipping_cost'];
      } else {
        $this->info['total'] = $this->info['subtotal'] + $this->info['tax'] + $this->info['shipping_cost'];
      }
    }

// ^^^^^^^^^ i'll review this mess later ^^^^^^^^^^


    function prepareStatus($st) {
    }

    function setStatus($st=NULL) {
      if (!isset($st)) $st=$this->info['orders_status'];
      $this->getPayments();
      $run=$this->info['total'];
      foreach ($this->payments AS $idx=>$p) {
	$pay=&$this->payments[$idx];
	switch ($st) {
	  case 0: 
	    $pay->cancelPayment($this);
	    break;
	  case 1:
	    if ($run<=0) break;
	    $r=$pay->authorizePayment($run,$this);
	    if ($r) $run-=$r;
//	    if ($run<-0.005) $this->error[]=get_class($pay).": Refund Failed";
	    break;
	  default:
	    if ($run<=0) $run=0;
	    $r=$pay->settlePayment($run,$this);
	    if ($r) $run-=$r;
	    if ($run<-0.005) $this->error[]=get_class($pay).": Refund Failed";
	}
      }

      if ($st>0 && $run>0.005) {
	if (isset($_POST['pay_method']) && $_POST['pay_method']) {
	  if (preg_match('/^(\d+)(:(.*))?/',$_POST['pay_method'],$pp)) {
	    foreach ($this->payments AS $p) if ($p->payid==$pp[1]) {
	      $pay=$p->recurPayment(isset($_POST['payment_on_file'])?$_POST['payment_on_file']:($pp[3]?$pp[3]:NULL));
	      if (isset($pay)) break;
	    }
	  } else $pay=tep_module($_POST['pay_method'],'payment');
	} else foreach ($this->payments AS $p) {
	  $pay=$p->recurPayment();
	  if (isset($pay)) break;
	}
	if (isset($pay) && $pay->checkConf()) {
	  $pay->initPayment($run,$this);
	  $this->payments[]=&$pay;
	  $r=$st==1?$pay->authorizePayment($run,$this):$pay->settlePayment($run,$this);
	  if ($r>0) {
	    $pay->finishPayment($this);
	    $run-=$r;
	    if ($run>0.005) $this->error[]=get_class($pay).": Bad Surcharge Amount (".sprintf("$%.2f",$run)." more to charge)";
	    else $this->message[]=get_class($pay).": Surcharged ".sprintf("$%.2f",$r);
	  } else {
	    $this->error[]=get_class($pay).": Surcharge Failed: ".$pay->getError();
	  }
	}
      }
      if ($st>0 && $run>0.005) return NULL;
      if ($st>1 && $run<-0.005) return NULL;
      if ($st>1) $this->approvePurchase();
      tep_db_query("UPDATE ".TABLE_ORDERS." SET orders_status='".addslashes($st)."' WHERE orders_id='".$this->orderid."'");
      $this->info['orders_status']=$st;
      $this->adjustStock();
      return true;
    }

    function approvePurchase() {
      $objs=$this->getProducts();
      foreach ($objs AS $idx=>$obj) $obj->approvePurchase($this->products[$idx]['qty'],$this->products[$idx]['orders_products_id'],$this);
      $this->adjustStock();
    }

    function adjustStock() {
      $rt=Array();
      foreach ($this->returns AS $r) $rt[$r['id']]+=$r['restock'];

      foreach ($this->products AS $idx=>$p) if ($p['orders_products_id']) {
        if ($this->info['orders_status']>0) {
          $q=$p['qty'];
	  if (isset($rt[$p['id']])) {
	    $q-=($dq=min($q,$rt[$p['id']]));
	    $rt[$p['id']]-=$dq;
	  }
	} else $q=0;
	if ($q!=$p['stock_qty']) {
	  if (!isset($prods)) $prods=$this->getProducts();
	  $adj=isset($prods[$idx])?$prods[$idx]->adjustStock($p['stock_qty']-$q)+0:0;
	  $p['stock_qty']-=$adj;
	  $this->products[$idx]['stock_qty']-=$adj;
	  if ($adj) tep_db_query("UPDATE orders_products SET products_stock_quantity=products_stock_quantity-($adj) WHERE orders_products_id='".$p['orders_products_id']."'");
	}
      }
    }

    function getPurchaseInfo() {
      $rs=Array();
      $objs=$this->getProducts();
      foreach ($objs AS $idx=>$obj) $rs=array_merge($rs,$obj->getPurchaseInfo($this->products[$idx]['orders_products_id'],$this));
      return $rs;
    }

    function getProducts() {
      $rs=Array();
      foreach ($this->products AS $idx=>$pr) {
	$obj=IXproduct::load($pr['id']);
	if ($obj) $rs[$idx]=$obj;
      }
      return $rs;
    }
    
    function updateTotals() {
      $mod=tep_module('order_total');
      $this->totals=$mod->calculateTotal((isset($this->totals)?$this->totals:Array()),$this);
      $this->saveTotals();
    }
    
 
    function saveProducts() {
      $this->adjustStock();

      foreach ($this->products AS $idx=>$pr) {

			$pdata = array('products_quantity' => $pr['qty'], 
						   'products_name' => $pr['name'], 
						   'products_id' => $pr['id'], 
						   'products_model' => $pr['model'], 
						   'products_tax' => $pr['tax'], 
						   'products_price' => $pr['price'], 
						   'cost_price' => $pr['cost_price'], 
						   'final_price' => $pr['final_price'], 
						   'free_shipping' => $pr['free_shipping'], 
						   'separate_shipping' => $pr['separate_shipping'],
						   'products_weight' => $pr['products_weight']
						   );

        if ($pr['orders_products_id']) {

			if ($pr['qty'] > 0) {

			    IXdb::store('update','orders_products',$pdata,"orders_products_id");

			} else {

			    IXdb::query("DELETE FROM orders_products WHERE orders_products_id='{$pr['orders_products_id']}");
	    		IXdb::query("DELETE FROM orders_products_attributes WHERE orders_products_id='{$pr['orders_products_id']}");
			    unset($this->products[$idx]);
	    		continue;
			}


		} else { // # insert new entry into orders_products table	

			$pdata['orders_id'] = $this->orderid;

			IXdb::store('insert','orders_products',$pdata);
			$this->products[$idx]['orders_products_id']=$pr['orders_products_id']=IXdb::insert_id();
			IXdb::write('orders_products',$pr['attrs'],'products_options','products_options_values',Array('orders_id'=>$this->orderid,'orders_products_id'=>$pr['orders_products_id']));
		}
	}

	$this->adjustStock();


    }



	// # orderitem_product
	function addProduct($pid,$qty=1,$attrs=NULL,$price=NULL,$extra=NULL) {

		$prod = IXdb::read("SELECT * FROM products p
        					LEFT JOIN products_description pd ON p.master_products_id = pd.products_id AND pd.language_id='{$GLOBALS['languages_id']}'
					        WHERE p.products_id='$pid'
					      ");

		// # Retrieve current-day product costing from the products table and add to orders products.
		// # important to keep historical pricing / costs for inventory since this can fluctuate with time.


		// # costing from suppliers_products_groups table
		$cost_price = '0';
		$cost_price_query = tep_db_query("SELECT suppliers_group_price FROM suppliers_products_groups WHERE products_id = '".$pid."' AND priority = '0' LIMIT 1");

		if(tep_db_num_rows($cost_price_query) > 0) { 
			$cost_price = tep_db_result($cost_price_query,0);
		}
	
		// # if no supplier cost found, use old format
		$cost = (!empty($cost_price) ? $cost_price : $prod['products_price_myself'])

		if (!isset($price)) {
			$price = $prod['products_price'];
		}

		$attrlst = array();

		if($attrs) foreach ($attrs AS $k=>$v) $attrlst[]=Array('option' => $k,'value' => $v);

		$this->products[] = array('qty'=>$qty,
								  'id'=>$pid,
								  'name'=>$prod['products_name'].'',
								  'price'=>$price,
								  'cost_price' => $cost,
								  'final_price'=>$price,
								  'attributes'=>$attrlst,
								  'tax'=>0,
								  'free_shipping'=>$prod['products_free_shipping']?1:0,
								  'separate_shipping'=>$prod['products_separate_shipping']?1:0,
								  );

		$this->info['subtotal'] += ($price * $qty);
	}


	// # orderitem_info
	function setExtraInfo($class,$info) {

		$item = IXmodule::module('orderitem_info');

		$item->initOrderItem(array($class=>$info));

		return $this->appendItem($item);
	}

  
	function getExtraInfo($class,$key=NULL) {
		
		$mds=$this->findItems('orderitem_info');
		if(!$mds) return NULL;
		return $mds[0]->getExtraInfo($class,$key);
	}
}
?>
