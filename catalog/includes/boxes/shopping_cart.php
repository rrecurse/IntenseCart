<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################
	
	$cart_item_count = $cart->count_contents();

	if($cart_item_count == 1) {
		$itemdesc = 'Item';
	} else {
		$itemdesc = 'Items';
	}
?>

<div class="cartbox_carticon" onclick="location.href='/shopping_cart.php'"></div>

<div class="cartbox_title"><?php echo (defined('SHOPCART_CONTENTS')) ? SHOPCART_CONTENTS : 'Cart Contents';?></div>
<div class="cartbox_content">
<?php echo (defined('SHOPCART_CONTAINS')) ? SHOPCART_CONTAINS : 'Your cart contains <a href="'.tep_href_link(FILENAME_SHOPPING_CART, $_SERVER['QUERY_STRING']).'" class="cartbox_content_link" rel="nofollow">'. $cart_item_count . ' ' . $itemdesc.'</a>';?>
</div>

<div class="cartbox_subtotal">
<?php
	if(defined('SHOPCART_SUBTOTAL')) { 
		echo SHOPCART_SUBTOTAL;
	} else {

		// # strip any added html tags to the currency class (like structured data spans and meta tags).
		$showTotal = strip_tags(trim($currencies->format($cart->show_total())));

		echo '<a href="'. tep_href_link(FILENAME_SHOPPING_CART, $_SERVER['QUERY_STRING']).'" class="cartbox_subtotal_link" rel="nofollow">Sub-total: '.$showTotal.'</a>';
	}
?>
</div>

<div class="cartbox_viewcart">
<?php echo (defined('SHOPCART_VIEW')) ? SHOPCART_VIEW : '<a href="'. tep_href_link(FILENAME_SHOPPING_CART, $_SERVER['QUERY_STRING']).'" class="cartbox_viewcart_link" rel="nofollow">view shopping cart</a>';?>
</div>

