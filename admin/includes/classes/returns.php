<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

  class order_return {
    var $info, $totals, $products, $customer, $delivery;

    function order_return($returns_id) {
      $this->info = array();
      $this->totals = array();
      $this->products = array();
      $this->customer = array();
      $this->delivery = array();

      $this->query($returns_id);
    }

	function query($returns_id) {
		$returns_query = tep_db_query("SELECT * FROM " . TABLE_RETURNS . " WHERE returns_id = '" . tep_db_input($returns_id) . "'");
		$returns = tep_db_fetch_array($returns_query);

		$refund_payment_query = tep_db_query("SELECT * FROM " . TABLE_RETURN_PAYMENTS . " where returns_id = '" . tep_db_input($returns_id) . "'");
		$refund_payment = tep_db_fetch_array($refund_payment_query);


		$this->returnid = $returns['returns_id'];
		$this->orderid = $returns['order_id'];

		$this->info = array('currency' => $returns['currency'],
              	            'currency_value' => $returns['currency_value'],
                			'payment_method' => $returns['payment_method'],
                    	    'rma_value' => $returns['rma_value'],
                        	'order_id' => $returns['order_id'],
							'cc_type' => $returns['cc_type'],
							'cc_owner' => $returns['cc_owner'],
							'cc_number' => $returns['cc_number'],
							'cvvnumber' => $returns['cvvnumber'],
							'cc_expires' => $returns['cc_expires'],
							'comments' => $returns['comments'],
							'date_purchased' => $returns['date_purchased'],
							'orders_status' => $returns['returns_status'],
							'date_finished' => $returns['date_finished'],
							'customer_method' => $refund_payment['customer_method'],
							'refund_method' => $refund_payment['refund_payment_name'],
							'payment_reference' => $refund_payment['refund_payment_reference'],
							//'refund_amount' => $refund_payment['refund_payment_value'],
							'refund_amount' => 0,
							'refund_date' => $refund_payment['refund_payment_date'],
							'refund_gv_id' => $returns['refund_gv_id'],
							'last_modified' => $returns['last_modified'],
							'return_reason' => $returns['returns_reason']
							);

		$this->customer = array('name' => $returns['customers_name'],
                        		'company' => $returns['customers_company'],
								'street_address' => $returns['customers_street_address'],
                        		'suburb' => $returns['customers_suburb'],
                        		'city' => $returns['customers_city'],
                        		'postcode' => $returns['customers_postcode'],
                        		'state' => $returns['customers_state'],
                        		'country' => $returns['customers_country'],
                        		'format_id' => $returns['customers_address_format_id'],
                        		'telephone' => $returns['customers_telephone'],
                        		'fax' => $returns['customers_fax'],
                        		'email_address' => $returns['customers_email_address']
								);

		$this->delivery = array('name' => $returns['delivery_name'],
                        		'company' => $returns['delivery_company'],
                        		'street_address' => $returns['delivery_street_address'],
                        		'suburb' => $returns['delivery_suburb'],
                        		'city' => $returns['delivery_city'],
                        		'postcode' => $returns['delivery_postcode'],
                        		'state' => $returns['delivery_state'],
                        		'country' => $returns['delivery_country'],
                        		'format_id' => $returns['delivery_address_format_id']);

		$this->billing = array('name' => $returns['billing_name'],
							   'company' => $returns['billing_company'],
							   'street_address' => $returns['billing_street_address'],
							   'suburb' => $returns['billing_suburb'],
							   'city' => $returns['billing_city'],
							   'postcode' => $returns['billing_postcode'],
							   'state' => $returns['billing_state'],
							   'country' => $returns['billing_country'],
							   'format_id' => $returns['billing_address_format_id']);


		$returns_products_query = tep_db_query("SELECT * FROM " . TABLE_RETURNS_PRODUCTS_DATA . " WHERE returns_id ='" . tep_db_input($returns_id) . "'");
		while ($returned_products = tep_db_fetch_array($returns_products_query)) {
			$this->products = array('qty' => $returned_products['products_quantity'],
									'name' => $returned_products['products_name'],
									'model' => $returned_products['products_model'],
									'tax' => $returned_products['products_tax'],
									'price' => $returned_products['products_price'],
									//'final_price' => $returned_products['final_price'],
									'id' => $returned_products['products_id'],
									'refund_shipping' => $returned_products['refund_shipping'],
									'restock_quantity' => $returned_products['restock_quantity'],
									'refund_amount' => $returned_products['refund_amount'],
									'exchange_amount' => $returned_products['exchange_amount'],
									'refund_shipping_amount' => $returned_products['refund_shipping_amount'],
									'final_price' => $returned_products['products_price']
									);
	
			$this->info['refund_amount']+=$returned_products['refund_amount'];
		} // # END while

		$this->exchange = array();
		$returns_products_query	 = tep_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " 
												 WHERE exchange_returns_id ='" . tep_db_input($returns_id) . "'
												");
		while ($returned_products = tep_db_fetch_array($returns_products_query)) {
			$this->exchange[] = array('orders_products_id' => $returned_products['orders_products_id'],
									  'qty' => $returned_products['products_quantity'],
									  'name' => $returned_products['products_name'],
									  'model' => $returned_products['products_model'],
									  'tax' => $returned_products['products_tax'],
									  'price' => $returned_products['products_price'],
									  'final_price' => $returned_products['products_price'],
									  'id' => $returned_products['products_id'],
									  'free_shipping' => $returned_products['free_shipping'],
									  'separate_shipping' => $returned_products['separate_shipping'],
									  'weight' => $returned_products['products_weight']
									  );
			$attributes_query = tep_db_query("SELECT * FROM  " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " 
											  WHERE orders_id = '" . $this->orderid . "' 
											  AND orders_products_id = '" . (int)$returned_products['orders_products_id'] . "'
											");

			if(tep_db_num_rows($attributes_query)) {
					while ($attributes = tep_db_fetch_array($attributes_query)) {
						$this->exchange[sizeof($this->exchange)-1]['attributes'][] = array('option' => $attributes['products_options'],
		    	                                      			'value' => $attributes['products_options_values'],
											  					'orders_products_attributes_id' => $attributes['orders_products_attributes_id']);
				} // # END while $attributes
			} // # END if tep_db_num_rows
		} // # END $returned_products

	} // # END function query()



  }
?>
