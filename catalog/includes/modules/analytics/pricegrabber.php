<?php

	$pgID = PRICEGRABBER_CONVERSION_CODE;

	$orders_id = tep_db_result(tep_db_query("SELECT orders_id 
											   FROM " . TABLE_ORDERS . " 
											   WHERE customers_id = '" . $customer_id . "' 
											   ORDER BY date_purchased DESC LIMIT 1
											   "),0);

    $items_query = tep_db_query("SELECT op.products_id, 
										op.products_model, 
										op.products_name, 
										m.manufacturers_name,
										op.final_price, 
										op.products_quantity 
								FROM orders_products op
								LEFT JOIN " . TABLE_PRODUCTS ." p ON p.products_id = op.products_id
								LEFT JOIN manufacturers m ON m.manufacturers_id = p.manufacturers_id
								WHERE op.orders_id = '".$orders_id."'  
								GROUP BY op.products_id 
								ORDER BY op.products_name
								");

	$i = 1;

	if(tep_db_num_rows($items_query) > 0) { 

		echo '<img src="https://www.pricegrabber.com/conversion.php?retid='.$pgID;

	    while ($items = tep_db_fetch_array($items_query)) {

			// # example format: <img src="https://www.pricegrabber.com/conversion.php?retid=23308&item1=a|b|c|d|e|f&item2=a|b|c|d|e|f&item3=a|b|c|d|e|f">
	
			echo '&item'.$i.'='.$items['manufacturers_name'].'|'. $items['products_model'].'|'.sprintf('%.2f',$items['final_price']).'|'.$items['products_name'].'|'.$items['products_model'].'|'.$items['products_quantity'].'">';

		$i++;

		}
	}
?>