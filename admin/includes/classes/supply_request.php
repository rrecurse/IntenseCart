<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class supply_request {
	var $info, $totals, $products, $customer, $delivery;


	function __construct($supply_request_id) {
		$this->info = array();
		$this->totals = array();
		$this->products = array();
		$this->customer = array();
		$this->delivery = array();
		$this->query($supply_request_id);

		$this->message = array();
		$this->error = array();

	}


	function query($supply_request_id) {
	// # Tracking begin
      $supply_request_id_query = tep_db_query("SELECT sr.*, s.*
											   FROM " . TABLE_SUPPLY_REQUEST . " sr
											   LEFT JOIN ". TABLE_SUPPLIERS . " s ON s.suppliers_id = sr.suppliers_id
											   WHERE supply_request_id = '" . tep_db_input($supply_request_id) . "'
											  ");
      $supply_request = tep_db_fetch_array($supply_request_id_query);

      $totals_query = tep_db_query("SELECT title, text 
									FROM " . TABLE_SUPPLY_REQUEST_TOTAL . " 
									WHERE supply_request_id = '" . (int)$supply_request_id . "' 
									ORDER BY sort_order
									");

      while ($totals = tep_db_fetch_array($totals_query)) {
        $this->totals[] = array('title' => $totals['title'],
                                'text' => $totals['text']);
      }

	
		// # grab the comments

		$supply_request_comments_query = tep_db_query("SELECT comments FROM " . TABLE_SUPPLY_REQUEST_STATUS_HISTORY . "
												  WHERE supply_request_id = '" . (int)$supply_request_id . "' 
												  ORDER BY date_added ASC
												");

		$supply_request_comments = tep_db_fetch_array($supply_request_comments_query);

      $this->info = array('currency' => $supply_request['currency'],
                          'currency_value' => $supply_request['currency_value'],
                          'payment_method' => $supply_request['payment_method'],
                          'cc_type' => $supply_request['cc_type'],
                          'cc_owner' => $supply_request['cc_owner'],
                          'cc_number' => $supply_request['cc_number'],
                          'date_requested' => $supply_request['date_requested'],
                          'supply_request_status_id' => $supply_request['supply_request_status_id'],
                          'ups_track_num' => $supply_request['ups_track_num'],
                          'usps_track_num' => $supply_request['usps_track_num'],
                          'fedex_track_num' => $supply_request['fedex_track_num'],
                          'last_modified' => $supply_request['last_modified'],
						  'comments' => $supply_request_comments['comments']
						  );

      $this->supplier = array('suppliers_id' => $supply_request['suppliers_id'],
							  'suppliers_name' => $supply_request['suppliers_name'],
                              'company' => $supply_request['suppliers_group_name'],
                              'street_address' => $supply_request['suppliers_street_address'],
                              'suburb' => $supply_request['suppliers_suburb'],
                              'city' => $supply_request['suppliers_city'],
                              'postcode' => $supply_request['suppliers_postcode'],
                              'state' => $supply_request['suppliers_state'],
                              'country' => $supply_request['suppliers_country'],
                              'format_id' => $supply_request['suppliers_address_format_id'],
                              'telephone' => $supply_request['suppliers_telephone'],
                              'email_address' => $supply_request['suppliers_email_address']
							  );

      $this->delivery = array('name' => $supply_request['delivery_name'],
                              'company' => $supply_request['delivery_company'],
                              'street_address' => $supply_request['delivery_street_address'],
                              'suburb' => $supply_request['delivery_suburb'],
                              'city' => $supply_request['delivery_city'],
                              'postcode' => $supply_request['delivery_postcode'],
                              'state' => $supply_request['delivery_state'],
                              'country' => $supply_request['delivery_country'],
							  'ship_date' => $supply_request['ship_date'],
                              'format_id' => $supply_request['delivery_address_format_id']);

		$this->add_product = array();
		$add_products_query	 = tep_db_query("SELECT * FROM " . TABLE_SUPPLY_REQUEST_PRODUCTS . " 
											 WHERE supply_request_id ='" . tep_db_input($supply_request_id) . "'
											");

		while ($add_products = tep_db_fetch_array($add_products_query)) {
			$this->add_product[] = array('supply_request_products_id' => $add_products['supply_request_products_id'],
										 'id' => $add_products['products_id'],
										 'name' => $add_products['products_name'],
										 'model' => $add_products['products_model'],
										 'cost' => $add_products['cost_price'],
										 'tax' => $add_products['products_tax'],
										 'weight' => $add_products['products_weight'],
										 'qty' => $add_products['products_quantity'],
										 'comments' => $add_products['products_comments'],
										 );

			$attributes_query = tep_db_query("SELECT * FROM  " . TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES . " 
											  WHERE supply_request_id = '" . $supply_request_id . "' 
											  AND supply_request_products_id = '" . (int)$add_products['supply_request_products_id'] . "'
											");

			if(tep_db_num_rows($attributes_query)) {
					while ($attributes = tep_db_fetch_array($attributes_query)) {
						$this->add_product[sizeof($this->add_product)-1]['attributes'][] = array('option' => $attributes['products_options'],
		    	                                      			'value' => $attributes['products_options_values'],
											  					'orders_products_attributes_id' => $attributes['orders_products_attributes_id']);
				} // # END while $attributes
			} // # END if tep_db_num_rows
		} // # END $add_products

      $index = 0;
   
      $supply_request_products_query = tep_db_query("SELECT supply_request_products_id, 
															products_name, 
															products_model, 
															products_price, 
															products_tax,
															products_quantity, 
															cost_price, 
															products_returned, 
															products_id,
															products_comments	
													FROM " . TABLE_SUPPLY_REQUEST_PRODUCTS . " 
													WHERE supply_request_id = '" . (int)$supply_request_id . "'
													");

	while ($supply_request = tep_db_fetch_array($supply_request_products_query)) {

		$this->products[$index] = array('qty' => $supply_request['products_quantity'],
                                        'name' => $supply_request['products_name'],
     									'id' => $supply_request['products_id'],
										'return' => $supply_request['products_returned'],
                                        'model' => $supply_request['products_model'],
                                        'tax' => $supply_request['products_tax'],
                                        'price' => $supply_request['products_price'],
                                        'cost_price' => $supply_request['cost_price'],
										'comments' => $supply_request['products_comments']
										);

        $subindex = 0;
        $attributes_query = tep_db_query("select products_options, products_options_values, options_values_price, price_prefix from " . TABLE_SUPPLY_REQUEST_PRODUCTS_ATTRIBUTES . " where supply_request_id = '" . (int)$order_id . "' and supply_request_products_id = '" . (int)$orders_products['supply_request_products_id'] . "'");
        if (tep_db_num_rows($attributes_query)) {
          while ($attributes = tep_db_fetch_array($attributes_query)) {
            $this->products[$index]['attributes'][$subindex] = array('option' => $attributes['products_options'],
                                                                     'value' => $attributes['products_options_values'],
                                                                     'prefix' => $attributes['price_prefix'],
                                                                     'price' => $attributes['options_values_price']);

            $subindex++;
          }
        }
        $index++;
      }
    }


	function setStatus($st='') {

		$st = (int)$st;
		$this->info['supply_request_status_id'] = (int)$this->info['supply_request_status_id'];

		if($st != $this->info['supply_request_status_id']) { 

		//error_log(print_r($st,1));
		//error_log(print_r($this->info['supply_request_status_id'],1));

			tep_db_query ("UPDATE " . TABLE_SUPPLY_REQUEST . " 
						   SET supply_request_status_id = '" . (int)$st . "', 
					       last_modified = NOW() 
						   WHERE supply_request_id='" . $this->supply_request_id . "'
						");
			$this->message[] = " Status Updated Successfully";

		} else { 

			$this->error[] = "Status Not Updated";
		}
		
		return true;
	}

 }
?>