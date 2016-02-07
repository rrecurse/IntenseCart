<?php
/*
  $Id: shop_by_price.php,v 2.2 2005/12/10  $
  
// locate the price of the products based in the value of each currency.

// It only must return one. The value of $currency must be only.*/
$sel_currency = array();
$sel_currency_query = tep_db_query("select symbol_left, symbol_right, value from " . TABLE_CURRENCIES . " where code = '" . $currency . "' limit 1");
$sel_currency = tep_db_fetch_array($sel_currency_query);

$price_ranges = Array( 	"from&nbsp;$%d&nbsp; - &nbsp;$%d",
			"$%d&nbsp; - &nbsp;&amp; up" );

//$price_range = Array( 	"from &nbsp;$0&nbsp;-" . $sel_currency['symbol_left'] . "200" . $sel_currency['symbol_right'],
//						"from &nbsp;" . $sel_currency['symbol_left'] . "201" . $sel_currency['symbol_right'] . "&nbsp;to &nbsp;" . $sel_currency['symbol_left'] . "300" . $sel_currency['symbol_right'],
//						"from &nbsp;" . $sel_currency['symbol_left'] . "301" . $sel_currency['symbol_right'] . "&nbsp; to &nbsp;" . $sel_currency['symbol_left'] . "500" . $sel_currency['symbol_right'],
//                        "from &nbsp;" . $sel_currency['symbol_left'] . "501" . $sel_currency['symbol_right'] . "&nbsp; to &nbsp;" . $sel_currency['symbol_left'] . "1000" . $sel_currency['symbol_right'],
//						"above" . $sel_currency['symbol_left'] . "1000" . $sel_currency['symbol_right'] );

//$price_ranges_sql = Array( 	"p.products_price < " . 200 / $sel_currency['value'],
//							"(p.products_price <= " . 300 / $sel_currency['value'] . " and p.products_price >= " . 201 / $sel_currency['value'] . ")",
//                            "(p.products_price <= " . 500 / $sel_currency['value'] . " and p.products_price >= " . 301 / $sel_currency['value'] . ")",
//                           "(p.products_price <= " . 1000 / $sel_currency['value'] . " and p.products_price >= " . 501 / $sel_currency['value'] . ")",
//                            "p.products_price > " . 1000 / $sel_currency['value']);

define('NAVBAR_TITLE', 'Shop by Price');
define('HEADING_TITLE', 'Shop by Price - ' . $price_ranges[$range]);
define('BOX_HEADING_SHOP_BY_PRICE', 'Shop By Price');
define('TABLE_HEADING_BUY_NOW', 'Buy Now!');
define('TABLE_HEADING_IMAGE', '');
define('TABLE_HEADING_MANUFACTURER', 'Manufacturer');
define('TABLE_HEADING_MODEL', 'Model');
define('TABLE_HEADING_PRICE', 'Price');
define('TABLE_HEADING_PRODUCTS', 'Product Name');
define('TABLE_HEADING_QUANTITY', 'Quantity');
define('TABLE_HEADING_WEIGHT', 'Weight');
define('TEXT_NO_PRODUCTS', '<p align="center"><br>Sorry, no products found ' . $price_range[$range] . '.<br>Please try another price range ...<br></p>');
?>