<?php

	include('includes/application_top.php');

	require(DIR_WS_CLASSES . 'shipping.php');

	require(DIR_WS_CLASSES . 'order.php');
	$order = new order($cart); 

	require(DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;

	global $customer_country_id, $customer_zone_id, $shipping_cost;

	$zone = preg_replace('/[^0-9]/i', '', $_GET['z']);

	$customer_country_id = preg_replace('/[^0-9]/i', '', $_GET['c']);

	if ($zone != '') {

		$customer_zone_id = $zone;

		if (!tep_session_is_registered('customer_zone_id')) {
			tep_session_register('customer_zone_id');
		}

	}

	if(!tep_session_is_registered('shipping')) {
		tep_session_register('shipping');
	}

	if( isset($_GET['s']) && strpos($_GET['s'], '_') ) {

		list($module, $method) = explode('_', $_GET['s']);

		if ($module == 'free--') {
			
			$shipping = array('id' => $_GET['s'], 'title' => 'Free', 'cost' => 0);

		} else {

			$shipping_modules = new shipping($_GET['s']);  

			$shipping = array('id' => $_GET['s'],
               				  'title' => $shipping_options[$module][$method]['name'],
							  'cost' => $shipping_options[$module][$method]['cost']
							 );

			$shipping_cost = $shipping['cost'];

			echo "<script>document.getElementById('confirm_button').style.display = 'block';</script>";

			//foreach($order->products as $products) { 
			//	error_log($products['free_shipping']);
			//}

			if(empty($shipping['cost']) && $customer_country_id != '223') { 

				error_log('order_total.php - ' . print_r($shipping,1));

				$shipping = array('id' => '',
        	       				  'title' => '',
								  'cost' => $shipping_cost
								 );


				echo "<script>document.getElementById('confirm_button').style.display = 'none';</script>";
				echo '<b>Please correct your shipping details above.</b>';
				return false;

				//echo '<script>reloadShipping();</script>';

			}
		}


	    echo '<table border="0" class="orderTotalTable"><tr><td>';

		$order->delivery['country_id'] = $customer_country_id;

		$order->info['zone_id'] = $customer_zone_id;
		$order->info['state'] = tep_get_zone_name($customer_country_id, $customer_zone_id, '');

		if (MODULE_ORDER_TOTAL_INSTALLED) {
    		$order_total_modules->process();
	    	echo $order_total_modules->output();
		}

    	echo '</table>';

	} else {
		echo 'Error';
	}
?>
