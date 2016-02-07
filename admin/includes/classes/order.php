<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

class order {
	var $info, $totals, $products, $customer, $delivery, $order_return;

	// # Use to store orders_products_ids for FK referencing in orderfeed_csv.php
	public $orderItemIDs = array ();

	function __construct($order_ref = NULL) {
		$this->info = array();
		$this->totals = array();
		$this->products = array();
		$this->customer = array();
		$this->delivery = array();
		$this->orderid = NULL;
		$this->message = array();
		$this->error = array();

		if ($order_ref) {
			if (!is_object($order_ref)) {
				$this->query($order_ref);
			} else {
				$this->cart($order_ref);
			}
		} else {
			$this->blank();
		}
	}

	function query($order_id) {

		global $customers_group;

		$order_query = tep_db_query ("SELECT o.*, ab.entry_company, c.customers_group_id
									  FROM " . TABLE_ORDERS . " o
									  LEFT JOIN ". TABLE_CUSTOMERS ." c ON c.customers_id = o.customers_id
									  LEFT JOIN ". TABLE_ADDRESS_BOOK ." ab ON (ab.customers_id = c.customers_id AND ab.address_book_id = c.customers_default_address_id)
									  WHERE o.orders_id = '" . tep_db_input($order_id) . "'");

		$order = tep_db_fetch_array($order_query);

		$totals_query = tep_db_query ("SELECT * FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '" . (int)$order_id . "' ORDER BY sort_order");

		while ($totals = tep_db_fetch_array ($totals_query)) {

			$this->totals[] = array ('title' => $totals['title'], 
						'text' => $totals['text'], 
						'class' => $totals['class'], 
						'value' => $totals['value'], 
						'id' => $totals['orders_total_id']);

			if ($totals['class'] == 'ot_total') {
				$total = $totals['value'];
			}
		}

		$this->orderid = $order['orders_id'];

		$customer_group = $order['customers_group_id'];

		if($customer_group > 1) { 

			$orders_source = 'vendor';

		} else {

			$orders_source = (!empty($order['orders_source']) ? $order['orders_source'] : 'retail');

		}

		$this->info = array (
						'currency' => $order['currency'], 
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

		$this->customer = array (
						'name' => $order['customers_name'], 
						'company' => (!empty($order['customers_company']) ? $order['customers_company'] : $order['entry_company']), 
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
						'email_address' => $order['customers_email_address']);

		$this->delivery = array ('name' => $order['delivery_name'], 
						'company' => $order['delivery_company'], 
						'street_address' => $order['delivery_street_address'], 
						'suburb' => $order['delivery_suburb'], 
						'city' => $order['delivery_city'], 
						'postcode' => $order['delivery_postcode'], 
						'state' => $order['delivery_state'], 
						'country' => $order['delivery_country'], 
						'format_id' => $order['delivery_address_format_id']);

		$this->billing = array ('name' => $order['billing_name'], 
						'company' => $order['billing_company'], 
						'street_address' => $order['billing_street_address'], 
						'suburb' => $order['billing_suburb'], 
						'city' => $order['billing_city'], 
						'postcode' => $order['billing_postcode'], 
						'state' => $order['billing_state'], 
						'country' => $order['billing_country'], 
						'format_id' => $order['billing_address_format_id']);

		$index = 0;

		//$orders_products_query = tep_db_query("select orders_products_id, products_name, products_model, products_price, products_tax, products_quantity, final_price from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");

		$ptax = array();

		$orders_products_query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = '". $this->orderid ."'");

		while ($orders_products = tep_db_fetch_array($orders_products_query)) {

			// # retrieve the cost price from the supplier_products_groups table
			// # if no cost found in suppliers_products_groups, try the products table for old format

			// # costing from suppliers_products_groups table
			$cost_price_query = tep_db_query("SELECT suppliers_group_price FROM suppliers_products_groups WHERE products_id = '".$orders_products['products_id']."' AND priority = '0' LIMIT 1");
			$cost_price = (tep_db_num_rows($cost_price_query) > 0 ? tep_db_result($cost_price_query,0) : '0.00');
			
			// # costing from products table
			$cost_old_query = tep_db_query("SELECT products_price_myself FROM ". TABLE_PRODUCTS ." WHERE products_id = '".$orders_products['products_id']."'");	
			$cost_old = (tep_db_num_rows($cost_old_query) > 0 ? tep_db_result($cost_old_query,0) : '0.00');

			// # if supplier cost is empty, use old format
			$cost = ($cost_price > 0 ? $cost_price : $cost_old);


			$this->products[$index] = array ('qty' => $orders_products['products_quantity'], 
											 'name' => $orders_products['products_name'], 
											 'id' => $orders_products['products_id'], 
											 'return' => $orders_products['products_returned'], 
											 'model' => $orders_products['products_model'], 
											 'tax' => $orders_products['products_tax'], 
											 'price' => $orders_products['products_price'], 
											 'cost_price' => (float)$cost, 
											 'weight' => $orders_products['products_weight'], 
											 'final_price' => $orders_products['final_price'], 
											 'stock_qty' => $orders_products['products_stock_quantity'], 
											 'free_shipping' => $orders_products['free_shipping'], 
											 'separate_shipping' => $orders_products['separate_shipping'], 
											 'exchange' => $orders_products['products_exchanged'], 
											 'exchange_id' => $orders_products['products_exchanged_id'], 
											 'orders_products_id' => $orders_products['orders_products_id'], 
											 'exchange_returns_id' => $orders_products['exchange_returns_id'],
											 'warehouse_id' => ($orders_products['warehouse_id'] > 0 ? $orders_products['warehouse_id'] : 1)
											);

			if (!isset ($this->info['tax_groups']['Tax'])) $this->info['tax_groups']['Tax'] = 0;

			$this->info['tax_groups']['Tax'] += $orders_products['products_tax'] * $orders_products['products_quantity'] * $orders_products['final_price'] / 100;

			$ptax[$orders_products['products_id']] = $orders_products['products_tax'];

			$subindex = 0;

			$attributes_query = tep_db_query ("select products_options, products_options_values, options_values_price, price_prefix from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int) $order_id . "' and orders_products_id = '" . (int) $orders_products['orders_products_id'] . "'");

			if (tep_db_num_rows($attributes_query) > 0) {

				while ($attributes = tep_db_fetch_array ($attributes_query)) {
					$this->products[$index]['attributes'][$subindex] = array ('option' => $attributes['products_options'], 
						'value' => $attributes['products_options_values'], 
						'prefix' => $attributes['price_prefix'], 
						'price' => $attributes['options_values_price']);

					$subindex++;
				}
			}
			$index++;
		}


		$this->returns = array();

		$r_qry = tep_db_query("SELECT * FROM ".TABLE_RETURNS_PRODUCTS_DATA." rp
								LEFT JOIN ".TABLE_RETURNS." r ON rp.returns_id = r.returns_id 
								LEFT JOIN refund_payments rf ON rf.returns_id = r.returns_id 
								WHERE rp.order_id='".(int)$order_id."'
								");

		while ($r_row = tep_db_fetch_array($r_qry)) {

			$this->returns[] = array(
									'returns_products_id' => $r_row['returns_products_id'], 
									'returns_id' => $r_row['returns_id'], 
									'id' => $r_row['products_id'], 
									'qty' => $r_row['products_quantity'], 
									'rma' => $r_row['rma_value'], 
									'restock' => $r_row['restock_quantity'], 
									'refund_amount' => $r_row['refund_amount'], 
									'exchange_amount' => $r_row['exchange_amount'], 
									'refund_shipping_amount' => $r_row['refund_shipping_amount'], 
									'refund_shipping' => $r_row['refund_shipping']
								);
			$this->info['tax_groups']['Tax'] -= $ptax[$r_row['products_id']] * ($r_row['refund_amount'] - $r_row['exchange_amount']) / 100;
		}
	}

	function blank () {
		global $HTTP_POST_VARS;

		$outerArray = array(
						'name', 
						'company', 
						'street_address', 
						'suburb', 
						'city', 
						'postcode', 
						'state', 
						'country', 
						'format_id');

		foreach ($outerArray as $field)
			$this->customer[$field] = $this->delivery[$field] = $this->billing[$field] = (isset($HTTP_POST_VARS[$field]) ? $HTTP_POST_VARS[$field] : '');

	if (isset ($HTTP_POST_VARS['firstname']) && isset ($HTTP_POST_VARS['lastname'])) 
		$this->customer['name'] = $this->delivery['name'] = $this->billing['name'] = $HTTP_POST_VARS['firstname'] . ' ' . $HTTP_POST_VARS['lastname'];

	foreach (array('telephone','email_address') as $field) $this->customer[$field] = (isset($HTTP_POST_VARS[$field]) ? $HTTP_POST_VARS[$field] : '');

	$this->info = array(
					'currency' => 'USD',
					'currency_value' => '1',
					'payment_method' => '',
					'cc_type' => '',
					'cc_owner' => '',
					'cc_number' => '',
					'cc_expires' => '',
					'date_purchased' => '',
					'orders_status' => 1,
					'ups_track_num' => '',
					'usps_track_num' => '',
					'fedex_track_num' => '',
					'last_modified' => '',
					'total' => '0.00',
					'orders_source' => ''
					);

	$this->totals = array(
						array(
							'class' => 'ot_subtotal',
							'title' => 'Subtotal:',
							'text' => '0.00',
							'value' => '0.00',
							'order_total_id' => 0
						),
						array(
							'class' => 'ot_shipping',
							'title' => 'Shipping:',
							'text' => '0.00',
							'value' => '0.00',
							'order_total_id' => 0
						),
						array(
							'class' => 'ot_coupon',
							'title' => 'Discount Coupons:',
							'text' => '0.00',
							'value' => '0.00',
							'order_total_id' => 0
						),
						array(
							'class' => 'ot_tax',
							'title' => 'Tax:',
							'text' => '0.00',
							'value' => '0.00',
							'order_total_id' => 0
						),
						array(
							'class' => 'ot_total',
							'title' => 'Total:',
							'text' => '0.00',
							'value' => '0.00',
							'order_total_id' => 0
						)
					);

	$this->products = array();
	$this->returns = array();
}

	function getShippingWeights () {
		$rt = array();
		foreach ($this->returns as $r)
			if ($r['refund_shipping'])
				$rt[$r['id']] += $r['qty'];
		$wt = array(0);

		foreach ($this->products as $p)
			if (!$p['free_shipping']) {
				$q = $p['qty'];
				if (isset ($rt[$p['id']])) {
					$q -= $rt[$p['id']];
					if ($q >= 0)
						$rt[$p['id']] = 0;
					else {
						$q = 0;
						$rt[$p['id']] -= $p['qty'];
					}
				}
				if ($p['separate_shipping']) {
					for ($i = 0; $i < $q; $i++)
						$wt[] = $p['weight'];
				} else if ($q > 0)
					$wt[0] = max ($wt[0], 1) + $p['weight'] * $q;
			}
		if ($wt[0] == 0)
			array_shift ($wt);
		return $wt;
	}

	function getShippingRefund ($cost) {
		if (!$this->getShippingWeights ())
			return $cost;
		$rf = 0;
		foreach ($this->returns as $ret)
			if ($ret['refund_shipping'])
				$rf += $ret['refund_shipping_amount'];
		return min ($rf, $cost);
	}

	function getSubTotal () {
		$a = 0;
		foreach ($this->products as $p)
			$a += $p['final_price'] * $p['qty'];

		//      foreach ($this->returns AS $r) $a-=$r['refund_amount'];
		return $a;
	}

	function getPayments() {
		include_once (DIR_FS_COMMON . 'modules/payment/IXpayment.php');
		if (!isset ($this->payments)) {
			$this->payments = array ();
			if (isset ($this->orderid)) {
				$pay_qry = tep_db_query ("SELECT * FROM " . TABLE_PAYMENTS . " WHERE orders_id='" . $this->orderid . "'");

				while ($pay_row = tep_db_fetch_array ($pay_qry)) {
					$pay = IXpayment::loadPaymentFromRow($pay_row);
					if (isset ($pay))
						$this->payments[] = $pay;
				}
			}
		}
		return $this->payments;
	}

	function cart(&$cart) {
		global $customer_id, $customer_group, $languages_id, $sendto, $billto, $currency, $currencies, $shipping, $payment;

		$this->content_type = $cart->get_content_type();

		$customer_address_query = tep_db_query ("SELECT c.customers_firstname, 
														c.customers_lastname, 
														c.customers_telephone, 
														c.customers_fax, 
														c.customers_email_address, 
														ab.entry_company, 
														ab.entry_street_address, 
														ab.entry_suburb, 
														ab.entry_postcode, 
														ab.entry_state,
														ab.entry_city, 
														ab.entry_zone_id, 
														z.zone_name, 
														co.countries_id, 
														co.countries_name, 
														co.countries_iso_code_2, 
														co.countries_iso_code_3, 
														co.address_format_id
												 FROM " . TABLE_CUSTOMERS . " c
												 LEFT JOIN " . TABLE_ADDRESS_BOOK . " ab ON ab.customers_id = c.customers_id
												 LEFT JOIN " . TABLE_ZONES . " z ON z.zone_id = ab.entry_zone_id
												 LEFT JOIN " . TABLE_COUNTRIES . " co ON  co.countries_id = ab.entry_country_id 
												 WHERE c.customers_id = '" . (int) $customer_id . "' 
												 AND ab.customers_id = '" . (int) $customer_id . "' 
												 AND c.customers_default_address_id = ab.address_book_id
												");


		$customer_address = tep_db_fetch_array ($customer_address_query);

		$shipping_address_query = tep_db_query ("SELECT ab.entry_firstname,
														ab.entry_lastname,
														ab.entry_company,
														ab.entry_street_address,
														ab.entry_suburb,
														ab.entry_postcode,
														ab.entry_city,
														ab.entry_state,
														ab.entry_zone_id,
														ab.entry_country_id,
														cn.countries_id,
														cn.countries_name,
														cn.countries_iso_code_2,
														cn.countries_iso_code_3,
														cn.address_format_id,
														z.zone_name 
												 FROM " . TABLE_ADDRESS_BOOK . " ab 
												 LEFT JOIN " . TABLE_ZONES . " z ON z.zone_id = ab.entry_zone_id 
												 LEFT JOIN " . TABLE_COUNTRIES . " cn ON cn.countries_id = ab.entry_country_id
												 WHERE ab.customers_id = '" . (int)$customer_id . "' 
												 AND ab.address_book_id = '" . (int)$sendto . "'
												");

		$shipping_address = tep_db_fetch_array ($shipping_address_query);

		$billing_address_query = tep_db_query ("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int) $customer_id . "' and ab.address_book_id = '" . (int) $billto . "'");

		$billing_address = tep_db_fetch_array ($billing_address_query);



		$tax_address_query = tep_db_query ("select ab.entry_country_id, ab.entry_zone_id from " . TABLE_ADDRESS_BOOK . " ab left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id) where ab.customers_id = '" . (int) $customer_id . "' and ab.address_book_id = '" . (int) ($this->content_type == 'virtual' ? $billto : $sendto) . "'");


		$tax_address = tep_db_fetch_array ($tax_address_query);

		$this->info = array ('orders_status' => NULL, 
						'currency' => $currency, 
						'currency_value' => $currencies->currencies[$currency]['value'], 
						'payment_method' => $payment, 
						'cc_type' => (isset ($GLOBALS['cc_type']) ? $GLOBALS['cc_type'] : ''), 
						'cc_owner' => (isset ($GLOBALS['cc_owner']) ? $GLOBALS['cc_owner'] : ''), 
						'cc_number' => '****************', 
						'cc_expires' => 'MMYY', 
						'shipping_method' => $shipping['title'], 
						'shipping_cost' => $shipping['cost'], 
						'subtotal' => 0, 
						'tax' => 0, 
						'tax_groups' => array (),

		'comments' => (isset ($GLOBALS['comments']) ? $GLOBALS['comments'] : ''));

		if (isset ($GLOBALS['comment_extra']) && is_array ($GLOBALS['comment_extra']))
			foreach ($GLOBALS['comment_extra'] as $comm)
				if ($comm)
					$this->info['comments'] = "$comm\n" . $this->info['comments'];

		$this->customer = array (
				'customers_id' => $customer_id,
				'firstname' => $customer_address['customers_firstname'],
				'lastname' => $customer_address['customers_lastname'],
				'company' => $customer_address['entry_company'],
				'street_address' => $customer_address['entry_street_address'],
				'suburb' => $customer_address['entry_suburb'],
				'city' => $customer_address['entry_city'],
				'postcode' => $customer_address['entry_postcode'],
				'state' => ((tep_not_null ($customer_address['entry_state'])) ? $customer_address['entry_state'] : $customer_address['zone_name']),
				'zone_id' => $customer_address['entry_zone_id'],
				'country' => $customer_address['countries_name'],
				'format_id' => $customer_address['address_format_id'],
				'telephone' => $customer_address['customers_telephone'],
				'fax' => $customer_address['customers_fax'],
				'email_address' => $customer_address['customers_email_address']
		);

		$this->delivery = array (
				'firstname' => $shipping_address['entry_firstname'],
				'lastname' => $shipping_address['entry_lastname'],
				'company' => $shipping_address['entry_company'],
				'street_address' => $shipping_address['entry_street_address'],
				'suburb' => $shipping_address['entry_suburb'],
				'city' => $shipping_address['entry_city'],
				'postcode' => $shipping_address['entry_postcode'],
				'state' => ((tep_not_null ($shipping_address['entry_state'])) ? $shipping_address['entry_state'] : $shipping_address['zone_name']),
				'zone_id' => $shipping_address['entry_zone_id'],
				'country' => $shipping_address['countries_name'],
				'country_id' => $shipping_address['entry_country_id'],
				'format_id' => $shipping_address['address_format_id']
		);

		$this->billing = array (
				'firstname' => $billing_address['entry_firstname'],
				'lastname' => $billing_address['entry_lastname'],
				'company' => $billing_address['entry_company'],
				'street_address' => $billing_address['entry_street_address'],
				'suburb' => $billing_address['entry_suburb'],
				'city' => $billing_address['entry_city'],
				'postcode' => $billing_address['entry_postcode'],
				'state' => ((tep_not_null ($billing_address['entry_state'])) ? $billing_address['entry_state'] : $billing_address['zone_name']),
				'zone_id' => $billing_address['entry_zone_id'],
				'country' => $billing_address['countries_name'],
				'country_id' => $billing_address['entry_country_id'],
				'format_id' => $billing_address['address_format_id']
		);

		$index = 0;

		$this->returns = array();

		$products = $cart->get_products();

		for ($i = 0, $n = sizeof($products); $i < $n; $i++) {

			$this->products[$index] = array (
					'id' => $products[$i]['id'],
					'qty' => $products[$i]['quantity'],
					'name' => $products[$i]['name'],
					'model' => $products[$i]['model'],
					'tax' => tep_get_tax_rate($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
					'tax_description' => tep_get_tax_description ($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
					'price' => (float)$products[$i]['price'],
					'cost_price' => (float)$products[$i]['cost_price'],
					'final_price' => (float)$products[$i]['price'],
					'weight' => $products[$i]['weight'],
					'products_width' => $products[$i]['products_width'],
					'products_height' => $products[$i]['products_height'],
					'products_length' => $products[$i]['products_length'],
					'free_shipping' => $products[$i]['products_free_shipping'],
					'separate_shipping' => $products[$i]['products_separate_shipping'],
					'orders_products_id' => NULL,
					'harmonized_code' => $products[$i]['harmonized_code'],
					'origin_country' => $products[$i]['origin_country'],
					'warehouse_id' => ($products[$i]['warehouse_id'] > 0 ? $products[$i]['warehouse_id'] : 1),

			);

			$customer_group_id_query = tep_db_query("SELECT customers_group_id FROM ". TABLE_CUSTOMERS ." WHERE customers_id = '". $this->customer['customers_id'] ."'");
			$customer_group = (tep_db_num_rows($customer_group_id_query) > 0 ? tep_db_result($customer_group_id_query,0) : 0);

			if ($customer_group > 0) {

				$customers_price_query = tep_db_query ("SELECT pg.customers_group_price
														FROM " . TABLE_PRODUCTS_GROUPS . " pg 
														WHERE pg.customers_group_id = '" . $customer_group . "' 
														AND pg.products_id = '" . $products[$i]['id'] . "'
													   ");

				$customers_price = tep_db_fetch_array($customers_price_query);


				while ($customers_price = tep_db_fetch_array($customers_price_query)) {
					$this->products[$index] = array (
							'price' => $customers_price['customers_group_price'],
							'final_price' => ($customers_price['customers_group_price'] + $cart->attributes_price ($products[$i]['id']))
					);
				}
			}

			if ($products[$i]['attributes']) {
				$subindex = 0;
				reset ($products[$i]['attributes']);
				while (list ($option, $value) = each ($products[$i]['attributes'])) {

					$this->products[$index]['attributes'][$subindex] = array ('option' => $option, 
						'value' => $value, 
						'option_id' => $option, 
						'value_id' => $value, 
						'prefix' => '', 
						'price' => 0, 
						'products_id' => 0, 
						'track_stock' => 0);

					$subindex++;
				}
			}


			$shown_price = tep_add_tax ($this->products[$index]['final_price'], $this->products[$index]['tax']) * $this->products[$index]['qty'];
			$this->info['subtotal'] += $shown_price;

			$products_tax = $this->products[$index]['tax'];
			$products_tax_description = $this->products[$index]['tax_description'];


			global $sppc_customer_group_show_tax;

			if (!tep_session_is_registered ('sppc_customer_group_show_tax')) {
				$customer_group_show_tax = '1';
			} else {
				$customer_group_show_tax = $sppc_customer_group_show_tax;
			}

			if (DISPLAY_PRICE_WITH_TAX == 'true' && $customer_group_show_tax == '1') {
				// EOF Separate Pricing Per Customer, show_tax modification
				$this->info['tax'] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace ('.', 
						'', $products_tax) : "1." . str_replace ('.', 
						'', $products_tax)));
				if (isset ($this->info['tax_groups']["$products_tax_description"])) {
					$this->info['tax_groups']["$products_tax_description"] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace ('.', 
						'', $products_tax) : "1." . str_replace ('.', 
						'', $products_tax)));
				} else {
					$this->info['tax_groups']["$products_tax_description"] = $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace ('.', 
						'', $products_tax) : "1." . str_replace ('.', 
						'', $products_tax)));
				}
			} else {
				$this->info['tax'] += ($products_tax / 100) * $shown_price;
				if (isset ($this->info['tax_groups']["$products_tax_description"])) {
					$this->info['tax_groups']["$products_tax_description"] += ($products_tax / 100) * $shown_price;
				} else {
					$this->info['tax_groups']["$products_tax_description"] = ($products_tax / 100) * $shown_price;
				}
			}

			$index++;
		}

		global $sppc_customer_group_show_tax;

		if (!tep_session_is_registered ('sppc_customer_group_show_tax')) {
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


	function prepareStatus ($st) {
	// # EMPTY FUNCTION
	}

	function setStatus ($st=NULL, $process=1) {

		if(is_null($st)) $st = $this->info['orders_status'];


			// # retrieve the payment id for the order if one exists.
			$check_payid_query = tep_db_query("SELECT payments_id FROM ". TABLE_PAYMENTS ." WHERE orders_id = '". $this->orderid ."' ORDER BY payments_id DESC LIMIT 1");
			$check_payid = (tep_db_num_rows($check_payid_query) > 0 ? tep_db_result($check_payid_query,0) : 0);

			// # check for existing authorization status before forcing new payment status. 
			// # this avoids setting the status incorrectly if already settled. We cannot void settled funds. 
			
			$settle_check_query = tep_db_query("SELECT pt.trans_type 
												FROM payment_transactions pt 
												LEFT JOIN payments_to_transactions p2t ON p2t.trans_id = pt.trans_id
												WHERE p2t.payments_id = '". $check_payid ."'
												ORDER BY pt.trans_id DESC
												LIMIT 1
											   ");

			$settle_check = (tep_db_num_rows($settle_check_query) > 0 ? tep_db_result($settle_check_query,0) : '');


		// # if order status = cancelled, force payments status to "pending" to trigger proper void condition inside cancelPayment() function
		if($st == 0) { 

			if($settle_check == 'AUTH_ONLY') { 
		
				// # force payments status to "pending" to trigger proper void condition inside cancelPayment() function
				tep_db_query("UPDATE ". TABLE_PAYMENTS ." SET status ='pending' WHERE payments_id = '". $check_payid ."'");
			}

		} else if($st == 1) {


			if($settle_check == 'AUTH_ONLY') { 
				$trans_status = 'pending';
			} else if($settle_check != 'settle' || $settle_check != 'AUTH_ONLY') { 
				$trans_status = 'incomplete';
			}

			// # force payments status to "incomplete" to trigger proper condition inside authorizePayment() function
			// # limit to the transaction while ref_id field is null.
			tep_db_query("UPDATE ". TABLE_PAYMENTS ." 
						  SET method ='". $this->info['payment_method'] ."', 
						  status = '". $trans_status  ."' 
						  WHERE payments_id = '". $check_payid ."'
						  AND (ref_id IS NULL OR ref_id = '')
						");
		}


		$this->getPayments();

		$run = $this->info['total'];

		foreach ($this->payments as $idx => $p) {

			$pay = &$this->payments[$idx];

			switch ($st) {

				case 0 :

					// # force payments status to "pending" to trigger proper void condition inside cancelPayment() function
					//tep_db_query("UPDATE ". TABLE_PAYMENTS ." SET status ='pending' WHERE payments_id = '". $p->payid ."'");


					$pay->cancelPayment($this);

					if(!empty($p->error)) {
						$this->error[] = get_class($pay) . ": Cancellation Failed: " . $p->error;
						$pay->setError($p->error);
					} else if($p->status == 'cancelled') {
						$this->message[] = get_class($pay) . ": Order Cancelled Successfully - Transaction ID: " . (int)$p->txnid;
					}


				break;

				case 1 :

					if ($run <= 0.005) break;

/*					// # force payments status to "incomplete" to trigger proper condition inside authorizePayment() function
					tep_db_query("UPDATE ". TABLE_PAYMENTS ." 
								  SET method ='". $this->info['payment_method'] ."', 
								  status='incomplete' 
								  WHERE payments_id = '". $p->payid ."' 
								  AND (ref_id NOT NULL ref_id != '')
								");

					$r = $pay->doAuthSale($run,$this,'AUTH_ONLY');
*/

					$r = $pay->authorizePayment($run, $this);

					if ($r) { 
						$run = $run - $r;
					}

					//if ($run<=0.005) $this->error[] = get_class($pay).": Refund Failed";

					if(!empty($p->error)) {
						$this->error[] = get_class($pay) . ": Charge Failed! : " . $p->error;
						//$pay->setError($p->error);
					} else if(!empty($p->txnid)) {

						$this->message[] = get_class($pay) . ": ". sprintf ("$%.2f", $r) . " Processed Successfully - Transaction ID: " . (int)$p->txnid;
						
					}

				break;

				case 4 :
				break;

				default :
					if ($run < 0.01)
						$run = 0;
					$r = $pay->settlePayment ($run, $this);
					if ($r)	$run -= $r;

					if ($run < -0.005) $this->error[] = get_class($pay) . ": Refund Failed";
			}
		}

		if ($st > 0 && $st != 4 && $run > 0.005) {

			if(!empty($_POST['pay_method'])) {

				$pay_qry = tep_db_query ("SELECT payments_id, ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id='" . $this->orderid . "'");
				
				$pay_id = NULL;
				$ref_id = NULL;


				if(tep_db_num_rows($pay_qry) > 0) { 

					$ref_array = tep_db_fetch_array($pay_qry);

					$pay_id = $ref_array['payments_id'];
					$ref_id = $ref_array['ref_id'];
				}

				if (strpos($ref_id,':') !== false) {

					// # split the stored ref_id (712341234:4321) by ":"
					$ref = preg_split("/[:]+/", $ref_id);

					// # for the strlen function to work below, we need to cast the $ref[0] as a string
					$transID = (string)$ref[0];
					$last4 = (int)$ref[1];

					// # check if first part of ref is a transaction ID
					// # usually transaction ID's are over 9 digits.
					if(strlen($transID) > 9) {

						foreach ($this->payments as $p) { 

							if ($p->payid == $pay_id) {

								// # added new POST requirement for recuring flag (currently not on page and not used)
								if(isset($_POST['recurPayment'])) { 
									$pay = $p->recurPayment(isset($_POST['payment_on_file']) ? $_POST['payment_on_file'] : (!empty($last4) ? $last4 : NULL));
								}

								if (isset($pay)) break;
							}
						}

					} else { 

						$pay = tep_module($_POST['pay_method'], 'payment');

					}
				}

			} else { 

				foreach ($this->payments as $p) {

					// # added new POST requirement for recuring flag (currently not on page and not used)
					if(isset($_POST['recurPayment'])) { 
						$pay = $p->recurPayment();
					}

					if (isset($pay)) break;
				}
			}

			if (isset($pay) && $pay->checkConf()) {

				$pay->initPayment($run, $this);

				$this->payments[] = &$pay;
				
				if($st == 1 && $process == 1) { 

					$r = $pay->authorizePayment($run, $this);

				} else if(($st == 2 || $st == 3) && $process == 1) {

					$r = $pay->settlePayment($run, $this);

				} else {

					$r = 0;
				}

				if($r) {

					$pay->finishPayment($this);

					//$run -= $r;
					$run = $run - $r; 

					if ($run < 0.01) { 

						$this->error[] = get_class($pay) . ": Bad Surcharge Amount (" . sprintf ("$%.2f", $run) . " more to charge)";
					}

				} elseif($r == 0  && $process == 0) {

					$this->error[] = "No Card number or CVV code provided for additional charges";

				} else {

					$this->error[] = get_class($pay) . ": Surcharge Failed: " . $pay->getError();

				}
			}

		} else if ($st == '0' && $run > 0.005) {

			// # wasnt cancelling orders, so we added the cancelPayment() function.
			if($pay) $pay->cancelPayment($this);

		} else if ($st > 1 && $run <-0.005) { 

			return NULL;

		} else if ($st == 2 || $st == 3) {

			$this->approvePurchase();

		} else if ($st == 4 && $run > 0.005) { 
		// # status level 4 is set to "Hold for Verification" in database table orders_status

			tep_db_query ("UPDATE " . TABLE_ORDERS . " SET orders_status = '" . (int)$st . "', last_modified = NOW() WHERE orders_id='" . $this->orderid . "'");

			return NULL;
		} 

		tep_db_query ("UPDATE " . TABLE_ORDERS . " 
					   SET orders_status = '" . (int)$st . "', 
					   last_modified = NOW() 
					   WHERE orders_id='" . $this->orderid . "'
					 ");

		$this->info['orders_status'] = $st;
		$this->adjustStock();

		return true;
	}

	function approvePurchase () {
		$objs = $this->getProducts ();
		foreach ($objs as $idx => $obj)
			$obj->approvePurchase ($this->products[$idx]['qty'], $this->products[$idx]['orders_products_id'], $this);
		$this->adjustStock ();
	}

	function adjustStock () {
		$rt = Array ();
		foreach ($this->returns as $r)
			$rt[$r['id']] += $r['restock'];
		foreach ($this->products as $idx => $p) {
			if ($p['orders_products_id']) {
				if ($this->info['orders_status'] > 0) {
					$q = $p['qty'];
					if (isset ($rt[$p['id']])) {
						$q -= ($dq = min ($q, $rt[$p['id']]));
						$rt[$p['id']] -= $dq;
					}
				} else
					$q = 0;
				if ($q != $p['stock_qty']) {
					if (!isset ($prods))
						$prods = $this->getProducts ();
					$adj = (isset ($prods[$idx]) && $prods[$idx]->pid) ? $prods[$idx]->adjustStock ($p['stock_qty'] - $q) + 0 : 0;
					$p['stock_qty'] -= $adj;
					$this->products[$idx]['stock_qty'] -= $adj;
					if ($adj)
						tep_db_query ("UPDATE orders_products SET products_stock_quantity=products_stock_quantity-($adj) WHERE orders_products_id='" . $p['orders_products_id'] . "'");
				}
			}
		}
	}

	function getPurchaseInfo () {
		$rs = Array ();
		$objs = $this->getProducts ();
		foreach ($objs as $idx => $obj)
			$rs = array_merge ($rs, $obj->getPurchaseInfo ($this->products[$idx]['orders_products_id'], $this));
		return $rs;
	}

	function getProducts () {
		$rs = Array ();
		foreach ($this->products as $idx => $pr) {
			$obj = IXproduct::load ($pr['id']);
			if ($obj)
				$rs[$idx] = $obj;
		}
		return $rs;
	}

	function updateTotals () {
		$mod = tep_module ('order_total');
		$this->totals = $mod->calculateTotal ((isset ($this->totals) ? $this->totals : Array ()), $this);
		$this->saveTotals ();
	}

	function saveOrder () {
		$data = array (
				'currency' => $this->info['currency'],
				'currency_value' => $this->info['currency_value'],
				'shipping_method' => $this->info['shipping_method'],
				'payment_method' => $this->info['payment_method'],
				'cc_type' => $this->info['cc_type'],
				'cc_owner' => $this->info['cc_owner'],
				'cc_number' => $this->info['cc_number'],
				'cc_expires' => $this->info['cc_expires'],
				'date_purchased' => $this->info['date_purchased'],
				'local_time_purchased' => $this->info['local_time_purchased'],
				'local_timezone' => $this->info['local_timezone'],
				'orders_status' => $this->info['orders_status'],
				'ups_track_num' => $this->info['ups_track_num'],
				'usps_track_num' => $this->info['usps_track_num'],
				'fedex_track_num' => $this->info['fedex_track_num'],
				'dhl_track_num' => $this->info['dhl_track_num'],
				'comments' => $this->info['comments'],
				'last_modified' => date ('Y-m-d H:i:s'),
				'customers_id' => $this->customer['id'],
				'customers_telephone' => $this->customer['telephone'],
				'customers_fax' => $this->customer['fax'],
				'customers_email_address' => $this->customer['email_address'],
				'orders_source' => $this->info['orders_source']
		);

		// # Moved these two arrays out of the foreach loops.
		$outerArray = array('customers' => $this->customer, 
						'delivery' => $this->delivery, 
						'billing' => $this->billing);

		$innerArray = array('name' => 'name', 
						'company' => 'company', 
						'street_address' => 'street_address', 
						'suburb' => 'suburb', 
						'city' => 'city', 
						'postcode' => 'postcode', 
						'state' => 'state', 
						'country' => 'country', 
						'format_id' => 'address_format_id'
						);
		foreach ($outerArray as $sec => $lst) {
			foreach ($innerArray as $f => $dbf)
				$data[$sec . '_' . $dbf] = $lst[$f];
		}

		if ($this->orderid)
			IXdb::store ('update', 
						'orders', $data, "orders_id='{$this->orderid}'");
		else {
			IXdb::store ('insert', 
						'orders', $data);
			$this->orderid = IXdb::insert_id ();
		}

		$this->saveProducts ();
		$this->updateTotals ();
	}

	function saveProducts () {
		$this->adjustStock ();

		foreach ($this->products as $idx => $pr) {
			$pdata = Array ('products_quantity' => $pr['qty'], 
						'products_name' => $pr['name'], 
						'products_id' => $pr['id'], 
						'products_model' => $pr['model'], 
						'products_tax' => $pr['tax'], 
						'products_price' => $pr['price'], 
						'final_price' => $pr['final_price'], 
						'free_shipping' => $pr['free_shipping'], 
						'separate_shipping' => $pr['separate_shipping']);
			if ($pr['orders_products_id']) {
				if ($pr['qty'] > 0) {
					IXdb::store ('update', 
						'orders_products', $pdata, "orders_products_id");
					// # Added amazon order item ID to be used in orderfeed_csv.php.
					$this->orderItemIDs[$pr['amazon_order_item_id']] = $pr['orders_products_id'];
trigger_error ('Updating Amazon order ID: '.$pr['amazon_order_item_id']." == {$pr['orders_products_id']}", E_USER_NOTICE);
				} else {
					IXdb::query ("DELETE FROM orders_products WHERE orders_products_id='{$pr['orders_products_id']}");
					IXdb::query ("DELETE FROM orders_products_attributes WHERE orders_products_id='{$pr['orders_products_id']}");
					IXdb::query ("DELETE FROM orders_items_refs WHERE ref_item_id = '{$pr['amazon_order_item_id']}'");
					unset ($this->products[$idx]);
					continue;
				}
			} else {
				$pdata['orders_id'] = $this->orderid;
				IXdb::store ('insert', 
						'orders_products', $pdata);
				$this->products[$idx]['orders_products_id'] = $pr['orders_products_id'] = IXdb::insert_id ();
trigger_error ('Adding Amazon order ID: '.$pr['amazon_order_item_id']." == {$pr['orders_products_id']}", E_USER_NOTICE);

				// # Save newly created prod id to be saved in orderfeed_csv for proper FK referencing.
				$this->orderItemIDs[$pr['amazon_order_item_id']] = $pr['orders_products_id'];

				//	  IXdb::write('orders_products',$pr['attrs'],'products_options','products_options_values',Array('orders_id'=>$this->orderid,'orders_products_id'=>$pr['orders_products_id']));
				if ($pr['attributes']) {
					foreach ($pr['attributes'] as $at) {
						IXdb::store ('insert', 
						'orders_products_attributes', Array ('products_options' => $at['option'], 
						'products_options_values' => $at['value'], 
						'orders_id' => $this->orderid, 
						'orders_products_id' => $pr['orders_products_id']));
					}
				}
			}
		}

		$this->adjustStock ();
	}

	function saveTotals () {
		//      print_r($this->totals);
		//      return;
		$idlst = Array ();
		foreach ($this->totals as $idx => $t) {
			if (isset ($t['id'])) {
				$idlst[] = "'" . $t['id'] . "'";
			}
		}

		tep_db_query ("DELETE FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id='" . $this->orderid . "'" . ($idlst ? " AND orders_total_id NOT IN (" . join (',', $idlst) . ")" : ''));

		foreach ($this->totals as $idx => $t) {
			$qry = Array ('class' => $t['class'], 
						'value' => $t['value'], 
						'text' => $t['text'], 
						'title' => $t['title'], 
						'sort_order' => $idx);
			if (isset ($t['id']))
				tep_db_perform (TABLE_ORDERS_TOTAL, $qry, 
						'update', "orders_total_id='" . $t['id'] . "'");
			else {
				$qry['orders_id'] = $this->orderid;
				tep_db_perform (TABLE_ORDERS_TOTAL, $qry);
				$this->totals[$idx]['id'] = tep_db_insert_id ();
			}
		}
	}

	function batchItemCount ($ids, $class = NULL) {
		if (!$ids)
			return Array ();
		return IXdb::read ("
          SELECT
            orders_id,
            COUNT(0) AS ct
          FROM
            orders_products
          WHERE
            orders_id IN (" . join (',', $ids) . ")
          GROUP BY orders_id", 
						'orders_id', 
						'ct');
	}

	function getProductList () {
		$rs = Array ();
		foreach ($this->products as $prod) {
			$pinfo = IXdb::read ("SELECT * FROM products WHERE products_id='{$prod['id']}'");
			$rs[] = Array (
					'upc' => $pinfo['products_sku'],
					'sku' => $pinfo['products_sku'],
					'name' => $prod['name'],
					'price' => $prod['price'],
					'qty' => $prod['qty']
			);
		}
		return $rs;
	}

	function getPromoList () {
		return Array ();
	}

	function getBillTo () {
		return new IXaddress ($this->billing, $this->orderid);
	}

	function getShipTo () {
		return new IXaddress ($this->delivery, $this->orderid);
	}

	function getShipping () {
		// stub; returns the current shipping price; must deal with defaults!
		return array ('name' => preg_replace ('/^.*?_/', 
						'', $this->info['shipping_method']), 
						'cost' => 4.5);
	}

	function addInfoRef ($class, $fld, $val, $amazonOrderItemID = false, $prodOrderID = false) {
		// # Moved array generation, and check for orderItemID.
		// # Added field for orders_products_id to preserve proper referencing
		// # when confronted with Amazon's multiple lines with same item.
		// # This caused items to become duplicated when sending shipping updates.

		$itemid = 0;
		$data = array ('orders_id' => $this->orderid, 
						'orders_items_id' => &$itemid, 
						'ref_type' => $class, 
						'ref_key' => $fld, 
						'ref_value' => $val, 
						'ref_item_id' => $prodOrderID);

		if ($amazonOrderItemID != false) {
			$data['ref_refid'] = $amazonOrderItemID;
		}

		$itemid = IXdb::read ("SELECT orders_items_id FROM orders_items WHERE orders_id='{$this->orderid}' AND item_class='orderitem_info'", NULL, 
						'orders_items_id');
		if (!$itemid)
			$itemid = IXdb::store ('INSERT', 
						'orders_items', Array ('orders_id' => $this->orderid, 
						'item_class' => 'orderitem_info'));
		IXdb::store ('INSERT', 
						'orders_items_refs', $data);
	}

	function queryInfoRefIDs ($class, $fld, $val) {
		return IXdb::read ("SELECT o.orders_id FROM orders o
      		LEFT JOIN orders_items oi ON (oi.orders_id=o.orders_id AND oi.item_class='orderitem_info')
      		LEFT JOIN orders_items_refs oir ON (oir.orders_items_id=oi.orders_items_id
      			AND oir.ref_type='$class' AND oir.ref_key='$fld')
      		WHERE o.orders_status>0 AND  oir.ref_value" . (isset ($val) ? "='$val'" : " IS NULL"), Array (NULL), 
						'orders_id');
	}

	function create ($cus_id, $ship_addr = NULL, $bill_addr = NULL) {
		//    print_r($ship_addr);
		//    print_r($bill_addr);
		if ($cus_id)
			$cus = IXaddress::load ($cus_id);
		$ship = $ship_addr ? new IXaddress($ship_addr, $this->orderid) : $cus;
		if (!$cus)
			$cus = $ship;
		else
			$cus->populateFrom ($ship);
		$bill = $bill_addr ? new IXaddress($bill_addr, $this->orderid) : $cus;
		$bill->populateFrom ($ship);
		$this->customer = $this->_mkAddr ($cus);
		$this->customer['id'] = $cus_id + 0;
		$this->delivery = $this->_mkAddr ($ship);
		$this->billing = $this->_mkAddr ($bill);

		// FagSoft: Changed to support external payment method (Amazon module).
		if (!empty ($bill_addr['payment_method'])) {
			$this->info['payment_method'] = $bill_addr['payment_method'];
		} else {
			$this->info['payment_method'] = 'none';
		}
		$this->info['orders_status'] = 1;
	}

	function _mkAddr ($addr) {
		return array ('name' => $addr->getFullName (), 
						'company' => $addr->getCompany (true), 
						'street_address' => $addr->getAddress (), 
						'suburb' => $addr->getAddress2 (), 
						'city' => $addr->getCity (), 
						'postcode' => $addr->getPostCode (), 
						'state' => $addr->getZoneName (), 
						'zone_id' => $addr->getZoneID (), 
						'country' => $addr->getCountryName (), 
						'format_id' => 1, 
						'telephone' => $addr->getPhone (), 
						'fax' => $addr->getFax (), 
						'email_address' => $addr->getEmail ());
	}

	function addProduct ($pid, $qty = 1, $attrs = NULL, $price = NULL, $extra = NULL) {
		$prod = IXdb::read ("
        SELECT *
        FROM products p
        LEFT JOIN products_description pd ON
          p.master_products_id=pd.products_id AND
          pd.language_id='{$GLOBALS['languages_id']}'
        WHERE
          p.products_id='$pid'
      ");
		if (!isset ($price))
			$price = $prod['products_price'];
		$attrlst = Array ();
		if ($attrs)
			foreach ($attrs as $k => $v)
				$attrlst[] = Array ('option' => $k, 
						'value' => $v);

		//      else if ($prod) $attrlst=IXdb::read("SELECT FROM "Array('option' => $k,'value' => $v);
		$pd = $this->products[] = Array ('qty' => $qty, 
						'id' => $pid, 
						'name' => $extra['name'] ? $extra['name'] : $prod['products_name'] . '', 
						'price' => $price, 
						'final_price' => $price, 
						'attributes' => $attrlst, 
						'tax' => $extra['tax'] ? $extra['tax'] + 0 : 0, 
						'free_shipping' => $prod['products_free_shipping'] ? 1 : 0, 
						'separate_shipping' => $prod['products_separate_shipping'] ? 1 : 0, // FagSoft: Added to identify product line for FK purposes in table orders_items_refs.
		'amazon_order_item_id' => !empty ($extra['order_item_id']) ? $extra['order_item_id'] : '');
		if (!isset ($this->info['tax_groups']['Tax']))
			$this->info['tax_groups']['Tax'] = 0;
		$this->info['tax_groups']['Tax'] += $pd['tax'] * $qty;
		$this->info['subtotal'] += $price * $qty;
	}

	function setShipping ($shp, $cost = NULL, $name = NULL) {
		$this->info['shipping_method'] = $name;
		$this->info['shipping_cost'] = $cost;
		foreach ($this->totals as $tidx => $t)
			if ($t['class'] == 'ot_shipping') {
				$this->totals[$tidx]['title'] = 'Shipping: ' . $name;
				$this->totals[$tidx]['value'] = $cost;
				$this->totals[$tidx]['text'] = sprintf ('$%.2f', $cost);
			}
	}

	function setPromo () {}

	function getShippingMethods () {
		// stub
		return array (array ('title' => '2nd day shipping', 
						'price' => '10.00'), array ('title' => 'Next day shipping', 
						'price' => '25.00'), array ('title' => 'Next year shipping', 
						'price' => '1.52'));
	}

	function getOrderTotalValue ($class) {
		switch ($class) {
			case 'ot_total' :
			case 'ot_subtotal' :
				$subtotal = $this->info['subtotal'];
				if ($class == 'ot_subtotal') {
					return $subtotal;
				}
				break;
			case 'ot_tax' :
				$tax = 0;
				if ($this->info['tax_groups'])
					foreach ($this->info['tax_groups'] as $tx)
						$tax += $tx;
				return $tax;
			case 'ot_shipping' :
				$shipping = $this->getShipping ();
				return $shipping;
			case 'ot_discount' :
				$discount = $this->getPromo ();
				return $discount;

				// ot_total
				return $subtotal + $tax + $shipping - $discount;
			default :
				return NULL;
		}
	}

	function setTrackingNumber ($pkgid, $trk) {
		$idx = preg_replace ('/.*-/', 
						'', $pkgid) - 1;
		$trks = split (';', $this->info['ups_track_num']);
		$trks[$idx] = $trk;
		$t = Array ();
		for ($i = 0; $i < $idx || isset ($trks[$i]); $i++)
			$t[] = $trks[$i];
		$this->info['ups_track_num'] = join (';', $t);
	}

}
?>
