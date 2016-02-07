<?php

	define('TITLE', 'Order CSV Exporter');
	define('FILENAME_EXPORTORDERS', 'exportorders.php');


require('includes/application_top.php'); 
//require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_EXPORTORDERS);

define('HEADING_TITLE', 'Export orders to CSV file');

define('INPUT_START', 'From Order #');
define('INPUT_END', 'To Order #');
define('INPUT_VALID', 'Export to CSV');
define('INPUT_DESC', 'Select the order numbers you want to export. Leave both fields empty if you want to export all orders or leave one of the fields empty to export all orders from X or all orders to X.');


	if(!empty($_POST)) { 

		$start = (!empty($_POST['start']) ? (int)$_POST['start'] : tep_db_result(tep_db_query("SELECT orders_id FROM orders ORDER BY orders_id ASC LIMIT 1"),0));
		$end = (!empty($_POST['end']) ? (int)$_POST['end'] : tep_db_result(tep_db_query("SELECT orders_id FROM orders ORDER BY orders_id DESC LIMIT 1"),0));

		if (empty($start) && empty($end)) {

			$where = " WHERE o.date_purchased >= '". date('Y-m-d')."'";

		// # if $start is empty we select all orders up to $end

		} else { 

			$rstart = min($start,$end);
			$rend = max($start,$end);

			$where = " WHERE o.orders_id BETWEEN ". (int)$rstart ." AND ". (int)$rend;
		}


	// # export orders from orders table - does not include products.
	if(isset($_POST['export_orders'])) { 

		$result = tep_db_query("SELECT o.orders_id AS `Order ID`, 
									   o.customers_id AS `Customer ID`, 
									   o.date_purchased AS `Purchase Date`, 
									   o.customers_name AS `Customer Full Name`,
									   c.customers_firstname AS `Customer First Name`,
									   c.customers_lastname AS `Customer Last Name`,
									   o.cc_owner AS `Name on Card`,
									   o.customers_company AS `Customer Company`, 
									   o.customers_email_address AS `Customer Email Address`,
									   o.billing_street_address AS `Billing Address`,
									   o.billing_suburb AS `Billing Address2`,
									   o.billing_city AS `Billing City`, 
									   o.billing_state AS `Billing State`, 
									   o.billing_postcode AS `Billing Postal Code`, 
									   o.billing_country AS `Billing Country`, 
									   o.customers_telephone AS `Customer Phone Number`, 
									   o.delivery_name AS `Delivery Name`, 
									   o.delivery_company AS `Delivery Company`, 
									   o.delivery_street_address AS `Delivery Address`, 
									   o.delivery_suburb AS `Delivery Address2`, 
									   o.delivery_city AS `Delivery City`,
									   o.delivery_state AS `Delivery State`, 
									   o.delivery_postcode AS `Delivery Postal Code`, 
									   o.delivery_country AS `Delivery Country`, 
									   o.cc_type AS `Credit Card Type`, 
									   o.cc_number AS `Last 4 of Card`, 
									   o.cc_expires AS `Card Exp. Date`,
									   o.comments AS `Order Comments`,
									   (SELECT value FROM orders_total WHERE class = 'ot_subtotal' AND orders_id = o.orders_id LIMIT 1) AS `Order Sub-total`,
									   (SELECT value FROM orders_total WHERE class = 'ot_tax' AND orders_id = o.orders_id LIMIT 1) AS `Order Tax`,
									   (SELECT value FROM orders_total WHERE class = 'ot_shipping' AND orders_id = o.orders_id LIMIT 1) AS `Order Shipping`,
									   (SELECT value FROM orders_total WHERE class = 'ot_coupon' AND orders_id = o.orders_id LIMIT 1) AS `Order Coupon`,
									   (SELECT value FROM orders_total WHERE class = 'ot_total' AND orders_id = o.orders_id LIMIT 1) AS `Order Total`
								FROM orders o
								LEFT JOIN customers c ON c.customers_id = o.customers_id
								" . $where . "
								AND o.orders_status != 0
								GROUP BY o.orders_id
								ORDER BY o.orders_id
								");
		
	} else if(isset($_POST['export_orders_products'])) {

	// # export orders products details of orders_products table

		$result = tep_db_query("SELECT op.orders_id AS `Order ID`,
									   op.products_model AS `Product Model`,
									   op.products_name AS `Product Name`,
									   op.final_price AS `Final Price`,
									   op.products_quantity AS `Product Quantity`,
									   op.free_shipping AS `Shipped Free`,
									   op.products_weight AS `Shipped Weight`
								FROM orders_products op
								LEFT JOIN orders o ON o.orders_id = op.orders_id
								" . $where . "
								AND o.orders_status != 0
								ORDER BY o.orders_id
								");
		
	}

		$num_fields = mysql_num_fields($result);

		$headers = array();

		for ($i = 0; $i < $num_fields; $i++) {
    		$headers[] = mysql_field_name($result , $i);
		}

		$fp = fopen('php://output', 'w');

		if ($fp && $result) {

	    	header('Content-Type: text/csv');

			if(isset($_POST['export_orders'])) { 
				header('Content-Disposition: attachment; filename="orders_' . ($start > 0 || $rstart > 0 ? ($rstart ? $rstart : $start).'_thru_'.($rend ? $rend : $rend) . '_' : '') .date('m-d-Y').'.csv"');
			} else if (isset($_POST['export_orders_products'])) {
				header('Content-Disposition: attachment; filename="order_products_' . ($start > 0 || $rstart > 0 ? ($rstart ? $rstart : $start).'_thru_'.($rend ? $rend : $rend) . '_' : '') .date('m-d-Y').'.csv"');

			}

    		header('Pragma: no-cache');
		    header('Expires: 0');

    		fputcsv($fp, $headers);

		    while ($row = mysql_fetch_row($result)) {
    		    fputcsv($fp, array_values($row));
	    	}
    	
			exit();
		}

		exit();

	}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body style="background-color:transparent; min-height:500px;">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td colspan="2" width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr>
          <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
                <td class="pageHeading" align="right"></td>
              </tr>
            </table></td>
        </tr>
        <tr>
          <td><table border="0" style="font-family:tahoma;font-size:11px;" width="100%" cellspacing="2" cellpadding="2">
              <tr>
                <td><form method="POST" action="<?php echo $PHP_SELF; ?>">
                    <table border="0" style="font-family:tahoma;font-size:11px;" cellpadding="3">
                      <tr>
                        <td><?php echo INPUT_START; ?></td>
                        <td><!-- input name="start" size="5" value="<?php echo $start; ?>"> -->
                          <?php
    	                    $orders_list_query = tep_db_query("SELECT orders_id, date_purchased FROM orders WHERE orders_status != 0 ORDER BY orders_id ASC");
   							$orders_list_array = array();
							$orders_list_array[] = array('id' => '', 'text' => '---');

   						    while ($orders_list = tep_db_fetch_array($orders_list_query)) {
   					        $orders_list_array[] = array('id' => $orders_list['orders_id'],
                                       'text' => $orders_list['orders_id']." - ".tep_date_short($orders_list['date_purchased']));
							}  

							echo '&nbsp;&nbsp;' . tep_draw_pull_down_menu('start', $orders_list_array, (isset($_GET['orders_id']) ? $_GET['orders_id'] : ''), 'size="1"') . '&nbsp;&nbsp;&nbsp;';

						?></td>
                      </tr>
                      <tr>
                        <td><?php echo INPUT_END; ?></td>
                        <td><!-- <input name="end" size="5" value="<?php echo $end; ?>"> -->
                          <?php 
						echo '&nbsp;&nbsp;' . tep_draw_pull_down_menu('end', $orders_list_array, (isset($_GET['orders_id']) ? $_GET['orders_id'] : ''), 'size="1"') . '&nbsp;&nbsp;&nbsp;';
						?></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td><input type="submit" name="export_orders" value="Export Orders" > &nbsp; <input type="submit" name="export_orders_products" value="Export Order Details"></td>
                      </tr>
                    </table>
                  </form></td>
              </tr>
              <tr>
                <td><?php echo INPUT_DESC; ?>
				</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
            </table></td>
        </tr>
      </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>