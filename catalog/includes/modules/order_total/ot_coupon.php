<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class ot_coupon {
	var $title, $output;

	function ot_coupon() {

		$this->code = 'ot_coupon';
		$this->header = MODULE_ORDER_TOTAL_COUPON_HEADER;
		$this->title = MODULE_ORDER_TOTAL_COUPON_TITLE;
		$this->description = MODULE_ORDER_TOTAL_COUPON_DESCRIPTION;
		$this->user_prompt = '';
		$this->enabled = MODULE_ORDER_TOTAL_COUPON_STATUS;
		$this->sort_order = MODULE_ORDER_TOTAL_COUPON_SORT_ORDER;
		$this->include_shipping = MODULE_ORDER_TOTAL_COUPON_INC_SHIPPING;
		//$this->include_tax = MODULE_ORDER_TOTAL_COUPON_INC_TAX;
		$this->include_tax = true;
	
		$this->calculate_tax = MODULE_ORDER_TOTAL_COUPON_CALC_TAX;
		$this->tax_class = MODULE_ORDER_TOTAL_COUPON_TAX_CLASS;
		$this->credit_class = true;
		$this->output = array();

	}

	
	function process() {
		global $PHP_SELF, $order, $currencies;

		$order_total = $this->get_order_total();
		$od_amount = $this->calculate_credit($order_total);
		$tod_amount = 0.00;
		$this->deduction = $od_amount;

		if ($this->calculate_tax != 'None') {
			$tod_amount = $this->calculate_tax_deduction($order_total, $this->deduction, $this->calculate_tax);
		}

		$amount = ($od_amount + $tod_amount);

		if ($amount > 0) {

			$order->info['total'] = $order->info['total'] - $od_amount;

			$this->output[] = array('title' => $this->title . ' (' . $this->coupon_code .'):','text' => '<b>-' . $currencies->format($amount) . '</b>', 'value' => -$amount);
		}
	}

	function selection_test() {
		return false;
	}


	function pre_confirmation_check($order_total) {
		global $customer_id;
		return $this->calculate_credit($order_total);
	}

	function use_credit_amount() {
		return $output_string;
	}


	function credit_selection() {
		global $customer_id, $currencies, $language;
		$selection_string = '';
		$selection_string .= '<tr>' . "\n";
		$selection_string .= ' <td width="10">' . tep_draw_separator('pixel_trans.gif', '10', '1') .'</td>';
		$selection_string .= ' <td class="main">' . "\n";
		$image_submit = '<input type="image" name="submit_redeem" onclick="submitFunction()" src="' . DIR_WS_LANGUAGES . $language . '/images/buttons/button_redeem.gif" border="0" alt="' . IMAGE_REDEEM_VOUCHER . '" title = "' . IMAGE_REDEEM_VOUCHER . '">';
		$selection_string .= TEXT_ENTER_COUPON_CODE . tep_draw_input_field('gv_redeem_code') . '</td>';
		$selection_string .= ' <td align="right">' . $image_submit . '</td>';
		$selection_string .= ' <td width="10">' . tep_draw_separator('pixel_trans.gif', '10', '1') . '</td>';
		$selection_string .= '</tr>' . "\n";
		return $selection_string;
	}


	function collect_posts() {
		global $HTTP_POST_VARS, $customer_id, $currencies, $cc_id;

		if(!empty($_POST['gv_redeem_code'])) {

		$gv_redeem_code = tep_db_prepare_input($_POST['gv_redeem_code']);

		// # get some info from the coupon table
		$coupon_query = tep_db_query("SELECT coupon_id, 
											 coupon_amount, 
											 coupon_type, 
											 coupon_minimum_order,
											 uses_per_coupon, 
											 uses_per_user, 
											 restrict_to_products,
											 restrict_to_categories, 
											 restrict_to_customers,
											 restrict_to_pricegroups
									  FROM " . TABLE_COUPONS . " 
									  WHERE coupon_code='". $gv_redeem_code ."' 
									  AND coupon_active='Y'
									  ");

		$coupon_result = tep_db_fetch_array($coupon_query);

		if($coupon_result['coupon_type'] != 'G') {

		if (tep_db_num_rows($coupon_query) < 1) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_INVALID_REDEEM_COUPON), 'SSL'));
		}

		$date_query = tep_db_query("SELECT coupon_start_date 
									FROM " . TABLE_COUPONS . " 
									WHERE coupon_start_date <= NOW() 
									AND coupon_code = '". $gv_redeem_code ."'
									");

		if (tep_db_num_rows($date_query) < 1) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_INVALID_STARTDATE_COUPON), 'SSL'));
		}

		$date_query = tep_db_query("SELECT coupon_expire_date FROM " . TABLE_COUPONS . " WHERE coupon_expire_date >= NOW() AND coupon_code='".$gv_redeem_code."'");

    if (tep_db_num_rows($date_query) < 1) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_INVALID_FINISDATE_COUPON), 'SSL'));
		}

		$coupon_count = tep_db_query("select coupon_id from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $coupon_result['coupon_id']."'");
		$coupon_count_customer = tep_db_query("select coupon_id from " . TABLE_COUPON_REDEEM_TRACK . " where coupon_id = '" . $coupon_result['coupon_id']."' and customer_id = '" . $customer_id . "'");

		if (tep_db_num_rows($coupon_count)>=$coupon_result['uses_per_coupon'] && $coupon_result['uses_per_coupon'] > 0) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_INVALID_USES_COUPON . $coupon_result['uses_per_coupon'] . TIMES ), 'SSL'));
		}

		if (tep_db_num_rows($coupon_count_customer)>=$coupon_result['uses_per_user'] && $coupon_result['uses_per_user'] > 0) {
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_INVALID_USES_USER_COUPON . $coupon_result['uses_per_user'] . TIMES ), 'SSL'));
		}
		if ($coupon_result['coupon_type']=='S') {
			$coupon_amount = $order->info['shipping_cost'];
		} else {
			$coupon_amount = $currencies->format($coupon_result['coupon_amount']) . ' ';
		}
		if ($coupon_result['coupon_type']=='P') { 
			$coupon_amount = $coupon_result['coupon_amount'] . '% ';
		}

		if ($coupon_result['coupon_minimum_order'] > 0){
			 $coupon_amount .= 'on orders greater than ' . $coupon_result['coupon_minimum_order'];
		}

		if (!tep_session_is_registered('cc_id')) tep_session_register('cc_id'); //Fred - this was commented out before
		$cc_id = $coupon_result['coupon_id']; // # Fred ADDED, set the global and session variable
		// $_SESSION['cc_id'] = $coupon_result['coupon_id']; //Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.
	}
		if ($_POST['submit_redeem_coupon_x'] && !$_POST['gv_redeem_code']) {
		tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_REDEEM_CODE), 'SSL'));
	}

	} elseif($coupon_result['coupon_type'] == 'G')  {

			$cust_ids = preg_split("/[,]/", $coupon_result['restrict_to_customers']);

			for ($i = 0; $i < sizeof($cust_ids); $i++) {
				if (($cust_ids[$i] !='') && ($cust_ids[$i] != $customer_id)) {
					tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_INVALID_USES_USER_COUPON . $coupon_result['uses_per_user'] . TIMES ), 'SSL'));
				}
			}

	}
}

function calculate_credit($amount) {

	global $customer_id, $order, $cc_id;

	//Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.
	//$cc_id = $_SESSION['cc_id']; 

	$od_amount = 0;
	if (isset($cc_id) ) {
		$coupon_query = tep_db_query("select coupon_code from " . TABLE_COUPONS . " where coupon_id = '" . $cc_id . "'");
		if (tep_db_num_rows($coupon_query) > 0 ) {
			$coupon_result = tep_db_fetch_array($coupon_query);
			$this->coupon_code = $coupon_result['coupon_code'];
			$coupon_get = tep_db_query("select * from " . TABLE_COUPONS ." where coupon_code = '". $coupon_result['coupon_code'] . "'");
			$get_result = tep_db_fetch_array($coupon_get);
			if ($get_result['uses_per_user']>0) {
			  if ($customer_id && IXdb::read("SELECT COUNT(0) AS ct FROM coupon_redeem_track WHERE coupon_id='{$get_result['coupon_id']}' AND customer_id='$customer_id'",NULL,'ct')>=$get_result['uses_per_user']) return 0;
			}
			$c_deduct = $get_result['coupon_amount'];

			if ($get_result['coupon_apply_to_shipping'] == 'Y') $amount += $this->shipping_cost;
			if ($get_result['coupon_type'] == 'S') $c_deduct = $order->info['shipping_cost'];

			if ($get_result['coupon_minimum_order'] <= $this->get_order_total()) {

				if ($get_result['restrict_to_products'] || $get_result['restrict_to_categories'] || $get_result['restrict_to_customers'] || $get_result['restrict_to_pricegroups']) {

					for ($i=0; $i<sizeof($order->products); $i++) {

					// # detect restriction profile of discount. Is it restricted to categories? products? customer?
					// # detect product restriction
						if ($get_result['restrict_to_products']) {

							$pr_ids = preg_split("/[,]/", $get_result['restrict_to_products']);

							$products_query = tep_db_query("SELECT products_id FROM products WHERE products_id='".$order->products[$i]['id']."' AND master_products_id IN (".join(',',$pr_ids).")");

							if (tep_db_num_rows($products_query) > 0) {

								for ($i=0; $i<sizeof($order->products); $i++) {
									if ($get_result['coupon_type'] == 'P') {	
										$pr_c = $order->products[$i]['final_price'];
										$pod_amount = round($pr_c*10)/10*$c_deduct/100;
										$od_amount = $od_amount + $pod_amount;
									} else {
										$od_amount = $c_deduct;
									}
								}
							}

							// # detect category restriction
							} elseif($get_result['restrict_to_categories']) {

								$cat_ids = preg_split("/[,]/", $get_result['restrict_to_categories']);

								for ($i=0; $i < sizeof($order->products); $i++) {
									$my_path = tep_get_product_path(tep_get_prid($order->products[$i]['id']));
									$sub_cat_ids = preg_split("/[_]/", $my_path);
									for ($iii = 0; $iii < count($sub_cat_ids); $iii++) {
								
										if (in_array($sub_cat_ids[$iii],$cat_ids)) {
											if ($get_result['coupon_type'] == 'P') {
												
												$pr_c = $order->products[$i]['final_price']*$order->products[$i]['qty'];	
												$pod_amount = round($pr_c*10)/10*$c_deduct/100;
												$od_amount = $od_amount + $pod_amount;
											} else {
												$od_amount = $c_deduct;
											}
										break;
										}
									}
								}

							} elseif($get_result['restrict_to_pricegroups']) {

								$price_groups = preg_split("/[,]/", $get_result['restrict_to_pricegroups']);

								$customer_group_id = (tep_session_is_registered('sppc_customer_group_id') ? (int)$_SESSION['sppc_customer_group_id'] : '0');

								if(in_array($customer_group_id, $price_groups)) {

									for ($i=0; $i < sizeof($order->products); $i++) {
	
										if ($get_result['coupon_type'] == 'P') {	
											$pr_c = $order->products[$i]['final_price'];
											$pod_amount = round($pr_c*10)/10*$c_deduct/100;
											$od_amount = $od_amount + $pod_amount;
										} else {
											$od_amount = $c_deduct;
										}	
									}
								}


							} else {
								$cust_ids = preg_split("/[,]/", $get_result['restrict_to_customers']);
								$customer_id = (tep_session_is_registered('customer_id')) ? $_SESSION['customer_id'] : NULL;
								if ($get_result['coupon_type'] == 'G') {
									for ($i = 0; $i < sizeof($cust_ids); $i++) {
										if ($customer_id == $cust_ids[$i]) {	
											$pr_c = $order->products[$i]['final_price'];
											$pod_amount = round($pr_c*10)/10*$c_deduct/100;
											$od_amount = $od_amount + $pod_amount;	
										}
									}
								} else {
									$od_amount = $c_deduct;
								}
							}
						}
					} else {
						if ($get_result['coupon_type'] !='P') {
							$od_amount = $c_deduct;
						} else {
							$od_amount = $amount * $get_result['coupon_amount'] / 100;
						}
					}
				}
			}
		if ($od_amount>$amount) $od_amount = $amount;
		}
	return $od_amount;
}


function calculate_tax_deduction($amount, $od_amount, $method) {
	global $customer_id, $order, $cc_id, $cart;
	
	//$cc_id = $_SESSION['cc_id']; //Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.

	$coupon_query = tep_db_query("select coupon_code from " . TABLE_COUPONS . " where coupon_id = '" . $cc_id . "'");
	if (tep_db_num_rows($coupon_query) !=0 ) {
		$coupon_result = tep_db_fetch_array($coupon_query);
		$coupon_get = tep_db_query("select * from " . TABLE_COUPONS . " where coupon_code = '". $coupon_result['coupon_code'] . "'");

		$get_result = tep_db_fetch_array($coupon_get);

		if ($get_result['coupon_type'] == 'S') {
		    $order->info['total'] -= $order->info['shipping_tax_amount'];
		    $order->info['tax'] -= $order->info['shipping_tax_amount'];
		    foreach ($order->info['tax_groups'] AS $k=>$v) {
				$order->info['tax_groups'][$k]-=$order->info['shipping_tax_amount'];
				break;
		    }
		}

		if ($get_result['coupon_type'] != 'S') {

			// # RESTRICTION--------------------------------
			if ($get_result['restrict_to_products'] || $get_result['restrict_to_categories'] || $get_result['restrict_to_pricegroups']) {

				// # Loop through all products and build a list of all product_ids, price, tax class
				// # at the same time create total net amount.
				// # then
				// # for percentage discounts. simply reduce tax group per product by discount percentage
				// # or
				// # for fixed payment amount
				// # calculate ratio based on total net
				// # for each product reduce tax group per product by ratio amount.

				$products = $cart->get_products();
				$valid_product = false;

				for ($i=0; $i<sizeof($products); $i++) {
					$valid_product = false;
					$t_prid = tep_get_prid($products[$i]['id']);
					$prod_query = tep_db_query("SELECT products_id, products_tax_class_id 
												FROM " . TABLE_PRODUCTS . " 
												WHERE products_id = '" . $t_prid . "'
											  ");

					$cc_result = tep_db_fetch_array($prod_query);

					if ($get_result['restrict_to_products']) {
						
						$pr_ids = preg_split("/[,]/", $get_result['restrict_to_products']);

						if (in_array($cc_result['products_id'],$pr_ids)) {
							$valid_product = true;
						}

						//for ($p = 0; $p < sizeof($pr_ids); $p++) {
							//if ($pr_ids[$p] == $t_prid) $valid_product = true;
						//}
					}

					if ($get_result['restrict_to_categories']) {
						$cat_ids = preg_split("/[,]/", $get_result['restrict_to_categories']);
						for ($c = 0; $c < sizeof($cat_ids); $c++) {
							$cat_query = tep_db_query("select products_id from products_to_categories where products_id = '" . $cc_result['master_products_id'] . "' and categories_id = '" . $cat_ids[$i] . "'");
							if (tep_db_num_rows($cat_query) !=0 ) $valid_product = true;
						}
					}


					if ($get_result['restrict_to_pricegroups']) {
						
						$price_groups = preg_split("/[,]/", $get_result['restrict_to_pricegroups']);
						$customer_group_id = (tep_session_is_registered('sppc_customer_group_id') ? (int)$_SESSION['sppc_customer_group_id'] : '0');

						if (in_array($customer_group_id, $price_groups)) {
							$valid_product = true;
						}
					}

					if ($valid_product) {
						$price_excl_tax = $products[$i]['final_price'] * $products[$i]['quantity']; 
						$price_incl_tax = $this->product_price($t_prid); 
						$valid_array[] = array('product_id' => $t_prid, 
											   'products_price' => $price_excl_tax, 
											   'products_tax_class' => $cc_result['products_tax_class_id']
											   ); 
						$total_price += $price_excl_tax;
					}
				}
				if (sizeof($valid_array) > 0) { 
					if ($get_result['coupon_type'] == 'P') {
						$ratio = $get_result['coupon_amount']/100;
					} else {
						$ratio = $od_amount / $total_price;
					}
					if ($get_result['coupon_type'] == 'S') $ratio = 1;
					if ($method=='Credit Note') {
						$tax_rate = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
						$tax_desc = tep_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
						if ($get_result['coupon_type'] == 'P') {
							$tod_amount = $od_amount / (100 + $tax_rate)* $tax_rate;
						} else {
							$tod_amount = $order->info['tax_groups'][$tax_desc] * $od_amount/100;
						}
						$order->info['tax_groups'][$tax_desc] -= $tod_amount;
						$order->info['total'] -= $tod_amount; //  need to modify total ...OLD
						$order->info['tax'] -= $tod_amount; //Fred - added
					} else {
						for ($p=0; $p<sizeof($valid_array); $p++) {
							$tax_rate = tep_get_tax_rate($valid_array[$p]['products_tax_class'], $order->delivery['country']['id'], $order->delivery['zone_id']);
							$tax_desc = tep_get_tax_description($valid_array[$p]['products_tax_class'], $order->delivery['country']['id'], $order->delivery['zone_id']);
							if ($tax_rate > 0) {
								//Fred $tod_amount[$tax_desc] += ($valid_array[$p]['products_price'] * $tax_rate)/100 * $ratio; //OLD
								$tod_amount = ($valid_array[$p]['products_price'] * $tax_rate)/100 * $ratio; // calc total tax Fred - added
								$order->info['tax_groups'][$tax_desc] -= ($valid_array[$p]['products_price'] * $tax_rate)/100 * $ratio;
								$order->info['total'] -= ($valid_array[$p]['products_price'] * $tax_rate)/100 * $ratio; // adjust total
								$order->info['tax'] -= ($valid_array[$p]['products_price'] * $tax_rate)/100 * $ratio; // adjust tax -- Fred - added
							}
						}
					}
				}
				//NO RESTRICTION--------------------------------
			} else {
				if ($get_result['coupon_type'] =='F') {
					$tod_amount = 0;
					if ($method=='Credit Note') {
						$tax_rate = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
						$tax_desc = tep_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
						$tod_amount = $od_amount / (100 + $tax_rate)* $tax_rate;
						$order->info['tax_groups'][$tax_desc] -= $tod_amount;
					} else {
//						$ratio1 = $od_amount/$amount;   // this produces the wrong ratipo on fixed amounts
						reset($order->info['tax_groups']);
						while (list($key, $value) = each($order->info['tax_groups'])) {
							$ratio1 = $od_amount/($amount-$order->info['tax_groups'][$key]); ////debug
							$tax_rate = tep_get_tax_rate_from_desc($key);
							$net = $tax_rate * $order->info['tax_groups'][$key];
							if ($net>0) {
								$god_amount = $order->info['tax_groups'][$key] * $ratio1;
								$tod_amount += $god_amount;
								$order->info['tax_groups'][$key] = $order->info['tax_groups'][$key] - $god_amount;
							}
						}
					}
					$order->info['total'] -= $tod_amount; //OLD
					$order->info['tax'] -= $tod_amount; //Fred - added
			}
			if ($get_result['coupon_type'] =='P') {
				$tod_amount=0;
				if ($method=='Credit Note') {
					$tax_desc = tep_get_tax_description($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
					$tod_amount = $order->info['tax_groups'][$tax_desc] * $od_amount/100;
					$order->info['tax_groups'][$tax_desc] -= $tod_amount;
				} else {
					reset($order->info['tax_groups']);
					while (list($key, $value) = each($order->info['tax_groups'])) {
						$god_amout=0;
						$tax_rate = tep_get_tax_rate_from_desc($key);
						$net = $tax_rate * $order->info['tax_groups'][$key];
						if ($net>0) {
							$god_amount = $order->info['tax_groups'][$key] * $get_result['coupon_amount']/100;
							$tod_amount += $god_amount;
							$order->info['tax_groups'][$key] = $order->info['tax_groups'][$key] - $god_amount;
						}
					}
				}
				$order->info['total'] -= $tod_amount; // have to modify total also
				$order->info['tax'] -= $tod_amount;
			}
		}
	}
}
return $tod_amount;
}

function update_credit_account($i) {
	return false;
}

function apply_credit() {

global $insert_id, $customer_id, $REMOTE_ADDR, $cc_id;
	//$cc_id = $_SESSION['cc_id']; //Fred commented out, do not use $_SESSION[] due to backward comp. Reference the global var instead.
	if ($this->deduction > 0) {
		tep_db_query("insert into " . TABLE_COUPON_REDEEM_TRACK . " (coupon_id, redeem_date, redeem_ip, customer_id, order_id) values ('" . $cc_id . "', NOW(), '" . $REMOTE_ADDR . "', '" . $customer_id . "', '" . $insert_id . "')");
	}
	tep_session_unregister('cc_id');
}

function get_order_total() {

	global $order, $cart, $customer_id, $cc_id;

	$cc_id = (!empty($_SESSION['cc_id'])) ? $_SESSION['cc_id'] : '';
	$order_total = $order->info['total'];

	// # Check if gift voucher is in cart and adjust total
	$products = $cart->get_products();

	for ($i=0; $i < sizeof($products); $i++) {

		$t_prid = tep_get_prid($products[$i]['id']);
		$gv_query = tep_db_query("SELECT products_price, 
										 products_tax_class_id, 
										 products_model 
								  FROM " . TABLE_PRODUCTS . " 
								  WHERE products_id = '" . $t_prid . "'
								 ");

		$gv_result = tep_db_fetch_array($gv_query);

		if (preg_match('/^GIFT/', addslashes($gv_result['products_model']))) {
			$qty = $cart->get_quantity($t_prid);
			$products_tax = tep_get_tax_rate($gv_result['products_tax_class_id']);

			if ($this->include_tax =='false') {
				$gv_amount = $gv_result['products_price'] * $qty;
			} else {
				$gv_amount = ($gv_result['products_price'] + tep_calculate_tax($gv_result['products_price'],$products_tax)) * $qty;
			}

			$order_total=$order_total - $gv_amount;
		}
	}

	if ($this->include_tax == 'false') $order_total=$order_total-$order->info['tax'];
	$order_total = ($order_total - $order->info['shipping_cost']);

	$this->shipping_cost = ($this->include_shipping == 'false') ? 0 : $order->info['shipping_cost'];

	
	// # OK thats fine for global coupons but what about restricted coupons
	// # where you can only redeem against certain products/categories.

	$coupon_query = tep_db_query("select coupon_code from " . TABLE_COUPONS . " where coupon_id='".$cc_id."'");

	if (tep_db_num_rows($coupon_query) > 0) {

		$coupon_result=tep_db_fetch_array($coupon_query);
		$coupon_get = tep_db_query("SELECT coupon_amount, 
										   coupon_minimum_order,
										   restrict_to_products,
										   restrict_to_categories,
										   restrict_to_customers,
										   restrict_to_pricegroups,
										   coupon_type 
									FROM " . TABLE_COUPONS . " 
									WHERE coupon_code = '".$coupon_result['coupon_code']."'
								  ");
		$get_result = tep_db_fetch_array($coupon_get);

		$in_cat = true;

		if ($get_result['restrict_to_categories']) {

			$cat_ids = preg_split("/[,]/", $get_result['restrict_to_categories']);
			$in_cat = false;

			for ($i = 0; $i < count($cat_ids); $i++) {
				if (is_array($this->contents)) {
					reset($this->contents);
					while (list($products_id, ) = each($this->contents)) {
						$cat_query = tep_db_query("select products_id from products_to_categories where products_id = '" . $products_id . "' and categories_id = '" . $cat_ids[$i] . "'");
						if (tep_db_num_rows($cat_query) !=0 ) {
							$in_cat = true;
							$total_price += $this->get_product_price($products_id);
						}
					}
				}
			}
		}

		$in_cart = true;

		if ($get_result['restrict_to_products']) {

			$pr_ids = preg_split("/[,]/", $get_result['restrict_to_products']);

			$in_cart=false;
			$products_array = $cart->get_products();

			for ($i = 0; $i < sizeof($pr_ids); $i++) {
				for ($ii = 1; $ii<=sizeof($products_array); $ii++) {
					if (tep_get_prid($products_array[$ii-1]['id']) == $pr_ids[$i]) {
						$in_cart=true;
						$total_price += $this->get_product_price($products_array[$ii-1]['id']);
					}
				}
			}
			$order_total = $total_price;
		}


		if($get_result['restrict_to_customers']) {

			$cust_ids = preg_split("/[,]/", $get_result['restrict_to_customers']);
			$in_cart = false;

			for ($i = 0; $i < sizeof($cust_ids); $i++) {
				if ($customer_id == $cust_ids[$i]) {
					$in_cart = true;
					$total_price += $this->get_product_price($products_id);
				}
			}

			$order_total = $total_price;
		}

		if($get_result['restrict_to_pricegroups']) {

			$price_groups = preg_split("/[,]/", $get_result['restrict_to_pricegroups']);
			$in_cart = false;

			$customer_group_id = (tep_session_is_registered('sppc_customer_group_id') ? (int)$_SESSION['sppc_customer_group_id'] : '0');
				
			for ($i = 0; $i < sizeof($price_groups); $i++) {
				if ($customer_group_id == $price_groups[$i]) {
					$in_cart = true;
					$total_price += $this->get_product_price($products_id);
				}
			}

			$order_total = $total_price;
		}

	}

	return $order_total;
}

function get_product_price($product_id) {
global $cart, $order;
	$products_id = tep_get_prid($product_id);
	$qty = $cart->contents[$product_id]['qty'];
	$product_query = tep_db_query("select products_id, products_price, products_tax_class_id, products_weight from " . TABLE_PRODUCTS . " where products_id='" . $product_id . "'");

	if ($product = tep_db_fetch_array($product_query)) {
		$prid = $product['products_id'];
		$products_tax = tep_get_tax_rate($product['products_tax_class_id']);
		$products_price = $product['products_price'];
		$specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . $prid . "' and status = '1'");
		if (tep_db_num_rows ($specials_query)) {
			$specials = tep_db_fetch_array($specials_query);
			$products_price = $specials['specials_new_products_price'];
		}

		if ($this->include_tax == 'true') {
			$total_price += ($products_price + tep_calculate_tax($products_price, $products_tax)) * $qty;
		} else {
			$total_price += $products_price * $qty;
		}

		// # attributes price
		if (isset($cart->contents[$product_id]['attributes'])) {
			reset($cart->contents[$product_id]['attributes']);
			while (list($option, $value) = each($cart->contents[$product_id]['attributes'])) {
				$attribute_price_query = tep_db_query("select options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . $prid . "' and options_id = '" . $option . "' and options_values_id = '" . $value . "'");
				$attribute_price = tep_db_fetch_array($attribute_price_query);
				if ($attribute_price['price_prefix'] == '+') {
					if ($this->include_tax == 'true') {
						$total_price += $qty * ($attribute_price['options_values_price'] + tep_calculate_tax($attribute_price['options_values_price'], $products_tax));
					} else {
						$total_price += $qty * ($attribute_price['options_values_price']);
					}
				} else {
					if ($this->include_tax == 'true') {
						$total_price -= $qty * ($attribute_price['options_values_price'] + tep_calculate_tax($attribute_price['options_values_price'], $products_tax));
					} else {
						$total_price -= $qty * ($attribute_price['options_values_price']);
					}
				}
			}
		}
	}
	if ($this->include_shipping == 'true') {

		$total_price += $order->info['shipping_cost'];
	}

	return $total_price;
}

// # RETURN THE PRODUCT PRICE (INCL ATTRIBUTE PRICES) WITH OR WITHOUT TAX
function product_price($product_id) {
	$total_price = $this->get_product_price($product_id);
	if ($this->include_shipping == 'true') $total_price -= $order->info['shipping_cost'];
	return $total_price;
}
// # END RETURN PRODUCT PRICE


function check() {
	if (!isset($this->check)) {
		$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_COUPON_STATUS'");
		$this->check = tep_db_num_rows($check_query);
	}

	return $this->check;
}

function keys() {
	return array('MODULE_ORDER_TOTAL_COUPON_STATUS', 'MODULE_ORDER_TOTAL_COUPON_SORT_ORDER', 'MODULE_ORDER_TOTAL_COUPON_INC_SHIPPING', 'MODULE_ORDER_TOTAL_COUPON_INC_TAX', 'MODULE_ORDER_TOTAL_COUPON_CALC_TAX', 'MODULE_ORDER_TOTAL_COUPON_TAX_CLASS');
}

function install() {
	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Total', 'MODULE_ORDER_TOTAL_COUPON_STATUS', 'true', 'Do you want to display the Discount Coupon value?', '6', '1','tep_cfg_select_option(array(\'true\', \'false\'), ', NOW())");
	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_COUPON_SORT_ORDER', '9', 'Sort order of display.', '6', '2', NOW())");
	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function ,date_added) values ('Include Shipping', 'MODULE_ORDER_TOTAL_COUPON_INC_SHIPPING', 'true', 'Include Shipping in calculation', '6', '5', 'tep_cfg_select_option(array(\'true\', \'false\'), ', NOW())");
	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function ,date_added) values ('Include Tax', 'MODULE_ORDER_TOTAL_COUPON_INC_TAX', 'true', 'Include Tax in calculation.', '6', '6','tep_cfg_select_option(array(\'true\', \'false\'), ', NOW())");
	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function ,date_added) values ('Re-calculate Tax', 'MODULE_ORDER_TOTAL_COUPON_CALC_TAX', 'None', 'Re-Calculate Tax', '6', '7','tep_cfg_select_option(array(\'None\', \'Standard\', \'Credit Note\'), ', NOW())");
	tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_ORDER_TOTAL_COUPON_TAX_CLASS', '0', 'Use the following tax class when treating Discount Coupon as Credit Note.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', NOW())");
}

function remove() {
	$keys = '';
	$keys_array = $this->keys();
	for ($i=0; $i<sizeof($keys_array); $i++) {
		$keys .= "'" . $keys_array[$i] . "',";
	}
	$keys = substr($keys, 0, -1);

	tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in (" . $keys . ")");
	}
}
?>
