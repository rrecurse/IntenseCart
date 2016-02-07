<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

  require('includes/application_top.php');



	$clean_query = tep_db_query("SELECT ot.*, o.payment_method 
								 FROM orders_total ot
								 LEFT JOIN orders o ON o.orders_id = ot.orders_id
								 LEFT JOIN orders_products op ON op.orders_id = ot.orders_id
								 WHERE o.payment_method = 'payment_amazonSeller'
								 AND op.products_returned = '0'
								AND title = 'Shipping Refund'
								 ORDER BY ot.orders_id DESC
								");


//	while($clean = tep_db_fetch_array($clean_query)) { 
	
		//echo $clean['orders_id'] . ' - ' . $clean['title'] . ' - ' . $clean['text'] . ' <br>';

//		$addRefund = abs($clean['value']);
		

		//tep_db_query("UPDATE orders_total SET `value` = `value` + $addRefund, text = CONCAT('$',FORMAT(`value`,2)) WHERE orders_id = '".$clean['orders_id']."' AND title = 'Total:'");


		// # set sort orders
		//tep_db_query("UPDATE orders_total SET sort_order = '2' WHERE orders_id = '".$clean['orders_id']."' AND title = 'Discount Coupons:'");
		//tep_db_query("UPDATE orders_total SET sort_order = '3' WHERE orders_id = '".$clean['orders_id']."' AND title = 'Tax:'");
		//tep_db_query("UPDATE orders_total SET sort_order = '4' WHERE orders_id = '".$clean['orders_id']."' AND title = 'Total:'");


		//tep_db_query("DELETE FROM orders_total WHERE orders_id = '".$clean['orders_id']."' AND title = 'Shipping Refund'");
//	}

	