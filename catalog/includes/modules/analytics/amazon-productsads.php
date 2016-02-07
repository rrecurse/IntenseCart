<?php

	$merchant_id = AMAZON_PRODUCTADS_CONVERSION_CODE;

	$orders_id_query = tep_db_query("SELECT orders_id 
									 FROM " . TABLE_ORDERS . " 
									 WHERE customers_id = '" . $customer_id . "'
									 AND orders_source LIKE 'amazon%'
									 AND orders_source NOT LIKE 'dbfeed_amazon%'
									 AND orders_source NOT LIKE 'Amazon-FBA%'
									 ORDER BY date_purchased DESC 
									 LIMIT 1
									");
	if(tep_db_num_rows($orders_id_query) > 0) { 

		$orders_id = tep_db_result($orders_id_query,0);

    	$items_query = tep_db_query("SELECT p.products_sku,
											op.products_quantity,
											op.final_price, 
											o.currency
									FROM " . TABLE_ORDERS_PRODUCTS . " op
									LEFT JOIN " . TABLE_ORDERS . " o ON o.orders_id = op.orders_id
									LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = op.products_id
									WHERE op.orders_id = '".$orders_id."'  
									GROUP BY op.products_id 
									");
	}

	$i = 1;

	if(tep_db_num_rows($items_query) > 0) { 

		echo "<script type=\"text/javascript\">

			var _paPixel = _paPixel || [];

			_paPixel.push(['merchantId', '". $merchant_id."']);
			_paPixel.push(['type', 'purchase']);
			_paPixel.push(['uniqueEventId', (new Date()).getTime() + Math.random().toString(16).slice(2) ]);
		";

	    while ($items = tep_db_fetch_array($items_query)) {

			// # example format: _paPixel.push(['items', 'your-product-sku-1', 10, '39.99', 'USD']);
	
			echo "_paPixel.push(['items', '". $items['products_sku'] ."', '". $items['products_quantity'] ."','". $items['final_price'] ."', '". $items['currency'] ."']);";

			$i++;

		}

		echo "_paPixel.push(['submit']);

		(function() {
			var paPixel = document.createElement('script'); paPixel.type = 'text/javascript'; paPixel.async = true;
			paPixel.src = '//d10so77biaxg0k.cloudfront.net/pa_pixel.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(paPixel, s);
		})();

		</script>";
	}
?>