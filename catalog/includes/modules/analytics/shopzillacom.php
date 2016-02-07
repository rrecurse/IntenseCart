<script type="text/javascript">
<!--
<?php

    $analytics_total='';
    $analytics_tax='';
    $analytics_shipping='';
	$units_ordered = '0';

	echo " var mid = '". SHOPZILLACOM_CONVERSION_CODE ."'; ";

	// # is this an existing customer?
	$existing_customer_query = tep_db_result(tep_db_query("SELECT COUNT(0) FROM ".TABLE_ORDERS." o WHERE o.customers_id = '" . (int)$customer_id . "' GROUP BY o.orders_id"),0);
	$existing_customer = ($existing_customer_query > 0) ? (int)1 : (int)0;

	echo " var cust_type = '". $existing_customer ."'; ";

	// # Grab order id
    $orders_query = tep_db_query("SELECT customers_city, customers_state, customers_country, orders_id 
								  FROM " . TABLE_ORDERS . " 
								  WHERE customers_id = '" . (int)$customer_id . "' 
								  ORDER BY date_purchased DESC LIMIT 1
								");

    $orders = tep_db_fetch_array($orders_query);

	$order_id = (int)$orders['orders_id'];

    $totals_query = tep_db_query("SELECT value, class FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '" . $order_id . "' ORDER BY sort_order");

   
     while ($totals = tep_db_fetch_array($totals_query)) {

        if ($totals['class'] == 'ot_total') {
            $analytics_total = number_format($totals['value'], 2);
            $total_flag = 'true';
        } else if ($totals['class'] == 'ot_tax') {
            $analytics_tax = number_format($totals['value'], 2);
            $tax_flag = 'true';
        } else if ($totals['class'] == 'ot_shipping') {
            $analytics_shipping = number_format($totals['value'], 2);
            $shipping_flag = 'true';
        }

     }

	echo " var order_value = '". $analytics_total ."'; ";
	echo " var order_id = '". $order_id ."'; ";

	// # Get products info for Analytics "Item lines"
	// # UTM:I|[order-id]|[sku/code]|[productname]|[price]|[quantity] 

    $items_query = tep_db_query("SELECT products_id, products_model, products_name, final_price, products_quantity 
								 FROM " . TABLE_ORDERS_PRODUCTS . " 
								 WHERE orders_id = '" . $order_id . "'
								 ORDER BY products_name
								");

    while ($items = tep_db_fetch_array($items_query)) {

		$units_ordered += $items['products_quantity'];

	} 

	echo " var units_ordered = '". $units_ordered ."'; ";

?>
   //-->
</script>
<script language="javascript" src="https://images.bizrate.com/api/roi_tracker.min.js?v=1"></script>