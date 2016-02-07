<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	// # GA Universal

	// # Get order id
    $orders_query = tep_db_query("SELECT o.orders_id, o.cc_type, o.currency
								  FROM " . TABLE_ORDERS . " o
								  WHERE o.customers_id = '" . (int)$customer_id . "' 
								  ORDER BY o.date_purchased DESC LIMIT 1
								");

    $orders = tep_db_fetch_array($orders_query);
    $orders_id = $orders['orders_id'];

	$payment_method = $orders['cc_type'];
	$currency = $orders['currency'];

	// # Set value for  "affiliation"
    $ga_affiliation = str_replace('http://', '', str_replace('www.', '', HTTP_SERVER));

	// # Set values for "total", "tax" and "shipping"
    $totals_query = tep_db_query("SELECT value, class FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '" . (int)$orders_id . "' ORDER BY sort_order");

    $ga_total = '0';
    $ga_tax = '0';
    $ga_shipping = '0';
	$ga_coupon ='';
    
	while ($totals = tep_db_fetch_array($totals_query)) {

		if ($totals['class'] == 'ot_total') {
			$ga_total = number_format($totals['value'], 2, '.', '');
			$total_flag = 'true';
		}
		
		if ($totals['class'] == 'ot_tax') {
			$ga_tax = number_format($totals['value'], 2, '.', '');
			$tax_flag = 'true';
		} 

		if ($totals['class'] == 'ot_shipping') {
			$ga_shipping = number_format($totals['value'], 2, '.', '');
			$shipping_flag = 'true';
		} 
		
		if ($totals['class'] == 'ot_coupon') {
			$ga_coupon_discount = number_format($totals['value'], 2);
			$ga_coupon = str_ireplace(array('Discount Coupons (', ')'),'',$totals['title']);
		}

	}


	// # Get products info for GA "item lines"
	$item_string='';
    $items_query = tep_db_query("SELECT op.products_id, 
										op.products_model, 
										op.products_name, 
										op.final_price, 
										op.products_tax, 
										op.products_quantity,
										p.products_sku,
										m.manufacturers_name,
										cd.categories_name,
										opa.products_options_values as products_optn
								 FROM " . TABLE_ORDERS_PRODUCTS . " op 
								 LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_id = op.products_id
								 LEFT JOIN ". TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa ON opa.orders_id = op.products_id
								 LEFT JOIN ". TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
								 LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id = p.products_id
								 LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = p2c.categories_id
								 WHERE op.orders_id = '" . $orders_id . "' 
								 ORDER BY op.products_name
								 ");

	while ($items = tep_db_fetch_array($items_query)) {

		// # Prepare the Enhanced GA Ecommerce "addProduct" action string
		// # Note: Cannot be used in conjunction with ga('require', 'ecommerce', 'ecommerce.js')
	

		$item_string .= "ga('ec:addProduct', { 
							'id':'" . $items['products_id'] . "', 
							'name':'" . $items['products_name'] . "',	
							'category':'" . $items['categories_name'] . "', 
							'brand': '".$items['manufacturers_name']."',	
							'price':'" . number_format(tep_add_tax($items['final_price'], $items['products_tax']), 2) . "',
							'quantity':'" . $items['products_quantity'] . "'
						";


		if(!empty($items['products_optn'])) {
			$item_string .= ", 'variant': '".$items['products_optn']."'";
		}


		$item_string .= "});". "\n";

	}

	$item_string = preg_replace('/\s+/', ' ', $item_string);

	// # Prepare the Enhanced GA Ecommerce "purchase" action string
	// # Note: Cannot be used in conjunction with Basic Ecommerce tracking

	 // # Transaction details are provided in an actionFieldObject.
	$purchase_string = "ga('ec:setAction', 'purchase', {
							  'id': '" . $orders_id . "', 
							  'affiliation': '" . $ga_affiliation . "', 
							  'revenue': '" . $ga_total . "', 
							  'tax': '" . $ga_tax . "',
							  'shipping': '" . $ga_shipping . "'";
	if(!empty($ga_coupon)) {
		$purchase_string .= ", 'coupon': '".$ga_coupon."'";
	}

	$purchase_string .= "});";

	$purchase_string = preg_replace('/\s+/', ' ', $purchase_string);

	// # END Enhanced GA Ecommerce "purchase" action string


// # also double check detect of function onOptionSelect(). 
// # If not loaded, we need the rest of the Google Analytics code, 
// # e.g. the UID and i.s.o.g.r.a.m Google function
echo "<script> 

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '". GOOGLE_ANALYTICS_UID ."');
ga('require', 'ec'); 

ga('set', '&cu', '". $currency ."'); 

" . $item_string . $purchase_string . " 

ga('send', 'pageview');

ga('ec:setAction', 'checkout_option', {'step': '2', 'option': '".$payment_method."'});

ga('send', 'event', 'Checkout', 'Payment Type');

</script>";
?>