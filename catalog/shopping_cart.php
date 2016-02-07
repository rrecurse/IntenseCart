<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require("includes/application_top.php");

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_SHOPPING_CART);

	$breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_SHOPPING_CART));

	$parentpage = $_SERVER["SCRIPT_NAME"];

	global $customer_city;

	if(isset($_GET['removecode']) && $_GET['removecode'] == 1){
		tep_session_unregister('cc_id');
		$cc_id = '';
		header("Location: https://" . $_SERVER['HTTP_HOST'] . "/shopping_cart.php");
		exit();
	}

	require(DIR_WS_INCLUDES . 'header.php');
	require(DIR_WS_INCLUDES . 'column_left.php'); 
?>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="shopping_cart_parentTable">
<tr>
<td valign="top">
	
<?php 
echo tep_draw_form('cart_quantity', tep_href_link(FILENAME_SHOPPING_CART, 'action=update_product', 'SSL'), 'POST', 'id="cart_form"');

	if ($cart->count_contents() > 0) {
?>

 <table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr><td>

  <tr>
    <td>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="productListing">
  <tr>
    <td><?php
    $info_box_contents = array();
    $info_box_contents[0][] = array('align' => 'left',
                                    'params' => 'class="productListing-heading1"',
                                    'text' => TABLE_HEADING_REMOVE);

    $info_box_contents[0][] = array('params' => 'class="productListing-heading2"',
                                    'text' => TABLE_HEADING_PRODUCTS);

    $info_box_contents[0][] = array('align' => 'center',
                                    'params' => 'class="productListing-heading3"',
                                    'text' => TABLE_HEADING_QUANTITY);

    $info_box_contents[0][] = array('align' => 'right',
                                    'params' => 'class="productListing-heading4"',
                                    'text' => TABLE_HEADING_TOTAL);

    $any_out_of_stock = 0;
    if (STOCK_CHECK == 'true') $cart->checkStock();
    $products = $cart->get_products();
/*    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
// Push all attributes information in an array

      if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        while (list($option, $value) = each($products[$i]['attributes'])) {
          echo tep_draw_hidden_field('id[' . $products[$i]['id'] . '][' . $option . ']', $value);
          $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $products[$i]['id'] . "'
                                       and pa.options_id = '" . $option . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $value . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'");
          $attributes_values = tep_db_fetch_array($attributes);

          $products[$i][$option]['products_options_name'] = $attributes_values['products_options_name'];
          $products[$i][$option]['options_values_id'] = $value;
          $products[$i][$option]['products_options_values_name'] = $attributes_values['products_options_values_name'];
          $products[$i][$option]['options_values_price'] = $attributes_values['options_values_price'];
          $products[$i][$option]['price_prefix'] = $attributes_values['price_prefix'];
        }
      }

    }
*/
	for ($i=0, $n=sizeof($products); $i<$n; $i++) {

		if (($i/2) == floor($i/2)) {
			$info_box_contents[] = array('params' => 'class="productListing-even"');
		} else {
			$info_box_contents[] = array('params' => 'class="productListing-odd"');
		}

		$cur_row = sizeof($info_box_contents) - 1;

		// # grab the category name for tracking

		$cat_name_query =	tep_db_query("SELECT cd.categories_name
										  FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd
										  LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES ." p2c ON p2c.categories_id = cd.categories_id
										  LEFT JOIN " . TABLE_PRODUCTS . " p ON p.master_products_id = p2c.products_id
										  WHERE p.products_id = ". $products[$i]['id']);

		if(tep_db_num_rows($cat_name_query) > 0) {
			$pcat = tep_db_result($cat_name_query,0);
		} else {
			$pcat = '';
		}

		$manuf_name_query =	tep_db_query("SELECT  m.manufacturers_name
										  FROM " . TABLE_MANUFACTURERS . " m
										  LEFT JOIN " . TABLE_PRODUCTS . " p ON p.manufacturers_id = m.manufacturers_id
										  WHERE p.products_id = ". $products[$i]['id']);

		if(tep_db_num_rows($manuf_name_query) > 0) {
			$pbrand = tep_db_result($manuf_name_query,0);
		} else {
			$pbrand = '';
		}

		$info_box_contents[$cur_row][] = array('align' => 'center',
        									   'params' => 'class="productListing-data1" style="padding-top:5px;" valign="top"',
											   'text' => tep_draw_checkbox_field('cart_delete[]', $products[$i]['cart_id'], '', 'id="remove_all_'.$products[$i]['id'].'"') . '<br><input type="submit" class="updatelink" value="remove">');

		$products_name = '

<script>
	jQuery("#cart_form" ).submit(function(e) {

		if(product_'. $products[$i]['id'].') { 
			GACartAdjust(product_'. $products[$i]['id'].');
		}
	});
</script>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="productListing-data2">
							<tr>
								<td width="'. SMALL_IMAGE_WIDTH .'" style="padding:5px" align="center"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $products[$i]['id']) . '" class="cart_prodImg">' . tep_image(DIR_WS_IMAGES . $products[$i]['image'], $products[$i]['name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a></td>
								<td valign="top" style="padding:5px;"><div style="color:#676767; font-size:11px; padding:5px 5px 0 10px;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $products[$i]['id']) . '" class="cart_prodTitle"><b>' . $products[$i]['name'] . '</b></a>';




	if (STOCK_CHECK == 'true') {
	
		$stock_check = tep_check_stock($products[$i]['id'], $products[$i]['quantity']);
        
		if (tep_not_null($stock_check)) {
          $any_out_of_stock = 1;
          $products_name .= $stock_check;
        }
	}

	if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {

		reset($products[$i]['attributes']);

		while (list($option, $value) = each($products[$i]['attributes'])) {
			
			$products_name .= '<br><small>&#8226;&nbsp; ' . $option . ' ' . $value . '</small>';
        }

	} 

		$products_name .= ' </div></td></tr></table>';
      
		$info_box_contents[$cur_row][] = array('text' => $products_name);

		$info_box_contents[$cur_row][] = array('align' => 'center',
        									   'params' => 'class="productListing-data3" valign="top" style="padding-top:5px;"',
 											   'text' => tep_draw_input_field('cart_quantity[]', $products[$i]['quantity'], 'size="2" style="text-align:center" maxlength="4" id="cart_quantity_'.$products[$i]['id'].'"') . tep_draw_hidden_field('products_id[]', $products[$i]['id']) . tep_draw_hidden_field('cart_id[]', $products[$i]['cart_id']) . '<br><input type="submit" class="updatelink" value="update">');

		$info_box_contents[$cur_row][] = array('align' => 'right',
											   'params' => 'class="productListing-data4" valign="top"',
											   'text' => $currencies->display_price($products[$i]['final_price'], tep_get_tax_rate($products[$i]['tax_class_id']), $products[$i]['quantity']));
                                             //'text' => '' . $products[$i]['price_obj']->getPriceStringShort() . '');

										//	'text' => $currencies->display_price($products[$i]['final_price'],$products[$i]['quantity']));
    }

    new productListingBox($info_box_contents);
?>
</td>

  </tr>
  <tr>
<td align="right" style="padding-right:14px; background-color:#EFEFEF; border:1px solid #FFFFFF; border-top:none;">
<div style="padding:10px; color:#363636; font:bold 12px arial;"> <?php echo SUB_TITLE_SUB_TOTAL; ?> &nbsp;<span class="subtotal"><?php echo $currencies->format($cart->show_total()); ?></span></div></td>
      </tr>
</table>


<?php
	if($any_out_of_stock == 1) {
		if(STOCK_ALLOW_CHECKOUT == 'true') {
			 echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center"><tr><td class="stockWarning" align="center"><br>'. OUT_OF_STOCK_CAN_CHECKOUT.'</td></tr></table>';
		} else {
			echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center"><tr><td class="stockWarning" align="center"><br>'. OUT_OF_STOCK_CANT_CHECKOUT,'</td></tr></table>';
		}
    }
?>

</td></tr>


<?php

	if(SHOPPING_CART_CHECKOUT_PREVIEW=='Enable') {

		include(DIR_WS_LANGUAGES.$language.'/'.FILENAME_CHECKOUT_SHIPPING);
		$customer = array();

		if(tep_session_is_registered('customer_id')) {
			
			$customer_query = tep_db_query("SELECT c.*, ab.* 
											FROM " . TABLE_CUSTOMERS . " c 
											LEFT JOIN " . TABLE_ADDRESS_BOOK . " ab ON (c.customers_default_address_id = ab.address_book_id) 
											WHERE c.customers_id = '" . (int)$_SESSION['customer_id'] . "'
											");

			$customer = tep_db_fetch_array($customer_query);
		}

		$total_weight = $cart->show_weight();
		$multi_weight = $cart->show_multi_weight_line();
		$total_count = $cart->count_contents();
		$free_shipping = $cart->free_shipping;
  
		if(!tep_session_is_registered('ship_country')) { 

			$ship_country = (isset($customer['entry_country_id']) ? $customer['entry_country_id'] : STORE_COUNTRY);
			tep_session_register('ship_country');

		} else {

			if(!empty($_SESSION['ship_country'])) { 

					if(is_numeric($_SESSION['ship_country'])) { 
						$ship_country = (int)$_SESSION['ship_country'];
					} else {
						$ship_country = tep_db_result(tep_db_query("SELECT countries_id FROM ". TABLE_COUNTRIES ." WHERE countries_name = '". $_SESSION['ship_country'] ."'"),0);
					}
			
			} else { 

				$ship_country = STORE_COUNTRY;
			}
		}

		if(!tep_session_is_registered('ship_zone_id')) $ship_zone_id = (isset($customer['entry_zone_id']) ? $customer['entry_zone_id'] : 1);
		
		if (!tep_session_is_registered('ship_postcode')) $ship_postcode = (isset($customer['entry_postcode']) ? $customer['entry_postcode'] : '');

		if (!tep_session_is_registered('customer_city')) $customer_city = (!empty($customer['entry_city']) ? $customer['entry_city'] : '');

		if (!tep_session_is_registered('customer_telephone')) $customer_telephone = (!empty($customer['telephone']) ? $customer['telephone'] : '');

?>

<tr>
	<td valign="top">
<script type="text/javascript">
<!--

function disableEnterKey(e){
	if (e.keyCode == 13) {
		var tb = document.getElementById("shipping_postcode");
		reloadState('ship');
		return false;
	}
}

function reloadShipping() {
	reloadOT('');
	if ($('shipping_postcode').value=='') {
		$('shipping_options').innerHTML= '<p>Please select your state and postal code<\/p>';
	} else {
		$('shipping_options').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Loading shipping costs, please wait...<br><img src="images\/loading_bar.gif" alt=""><\/td><\/tr><\/table>';

		new ajax ('<?php echo (!empty($_SERVER['HTTPS'])?'https':'http') . '://'.$_SERVER['HTTP_HOST'];?>/shipping_options.php?zip='+document.checkout.shipping_postcode.value+'&cnty='+document.checkout.ship_country.value+'&zone='+document.checkout.ship_state.value+'&weight=<?php echo $multi_weight?>&free=<?php echo $free_shipping?>', {method: 'post', postBody: 'state='+escape(document.checkout.ship_state.value), update: $('shipping_options')});
	}

}

function applyCoupon(code) {

	if(code) { 
		$('coupon_code').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle">Verifying and applying coupon, please wait...<br><img src="images\/loading_bar.gif" alt=""><\/td><\/tr><\/table>';

		new ajax ('<?php echo !empty($_SERVER['HTTPS'])?'https':'http';?><?php echo '://'.$_SERVER['HTTP_HOST'];?>/checkout_ot.php', {postBody: 'gv_redeem_code='+code, update: $('coupon_code')});

	setTimeout("reloadShipping(document.checkout.shipping.value)", 500);

	onOptionSelect('1', code, 'applyCoupon');

	}
}

function reloadOT(v) {
	if(v==null) v = document.checkout.shipping.value;
	if(v=='') {
		$('order_total').innerHTML= document.checkout.ship_postcode.value!=''?'<P>Calculating order costs<\/P>':'';
		return;
	}

	$('order_total').innerHTML = '<table border="0" height="65"><tr><td align="right" valign="middle"><img src="images\/loading.gif"><\/td><\/tr><\/table>';

	new ajax ('<?php echo !empty($_SERVER['HTTPS'])?'https':'http';?><?php echo '://'.$_SERVER['HTTP_HOST'];?>/order_total.php?s='+v+'&z='+document.checkout.ship_state.value+'&c='+document.checkout.ship_country.value, {method: 'get', update: $('order_total')});

}

var reloadStateBusy=0;

function reloadState(sec) {
	if (reloadStateBusy) return;
	reloadStateBusy=1;
	window.setTimeout('reloadStateBusy=0',500);
	var section_country = document.checkout[sec+'_country'].value;
	var section_state = document.checkout[sec+'_state'].value;
	var section_postcode = document.checkout[sec+'_postcode'].value;
	
	if (section_postcode=='') {
	    $(sec+'_state_div').innerHTML = 'Please enter postcode';
    	reloadShipping();
		return;
	}

	$(sec+'_state_div').innerHTML = '<img src="images\/loading_bar.gif" alt="">';

	new ajax('<?php echo !empty($_SERVER['HTTPS'])?'https':'http';?><?php echo '://'.$_SERVER['HTTP_HOST'];?>/state_dropdown.php?country='+section_country+'&sec='+sec+'&postal='+section_postcode+'&d='+section_state, {method: 'get', update: $(sec+'_state_div')});


	// # address validation - get the city name
	var zip = document.checkout.ship_postcode.value;
	var ship_country = document.checkout.ship_country.value;

	var url = '<?php echo HTTPS_SERVER;?>/address_validation.php';

	if(ship_country == 223) { 
		jQuery.get(url, 'address_validate=CityStateLookupRequest&zip='+zip, 'html')
			.done(function(html){
				jQuery('input[name="ship_city"]').val(html);
			})
			.fail(function(){
				jQuery('input[name="ship_city"]').val();
				//alert('Having trouble validating your postal or zip code');
			});
	// # canada post - get city name
	} else if (ship_country == 38) { 
		jQuery.get(url, 'address_validate=reversePostalcode&zip='+zip+'&ship_country='+ship_country, 'html')
			.done(function(html){
				jQuery('input[name="ship_city"]').val(html);
			})
			.fail(function(){
				jQuery('input[name="ship_city"]').val();
				console.log('Having trouble validating your postal or zip code');
			});		
	}

}

function setState(sec,v) {
	document.checkout.elements[sec+"_state"].value = v;
	//document.checkout.elements[sec+"_postcode"].select();
	reloadShipping();
}

function setShipping(v) {
	document.checkout.shipping.value = v;
	reloadOT(v);

	 // # send event to onOptionSelect function to track shipping option addition
	onOptionSelect('1', v, 'shipping');
}

function reloadPostal(sec) {
	//document.checkout[sec+'_postcode'].value = '';
	reloadState(sec);
}

document.checkout = document.forms['checkout'] = document.cart_quantity;
//-->

</script>

<?php

	if (USE_COUPONS == 'Enable') {
	
		$promo = '';

		if(isset($_SESSION['cc_id'])) {

			$cc_id = $_SESSION["cc_id"];

		    // # Get the coupon info from the db.
			$coupon_info_query = tep_db_query("SELECT * FROM coupons WHERE coupon_id = '".$cc_id."'");

			if(tep_db_num_rows($coupon_info_query) > 0) {
				$coupon_info = tep_db_fetch_array($coupon_info_query);
			}

			$promo = $coupon_info['coupon_code'];
		}
?>
              
			  <table class="coupon_tableMain">
			  <tr>
                <td style="padding:10px 7px 7px 7px; border:1px solid #FFFFFF; color:#000000"><b>&bull;  Discount Code</b></td>
              </tr>
              <tr>
                <td style="padding-top:10px; border:1px solid #FFFFFF; border-top:0;">
				<table border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td valign="top" style="padding:10px;"><p>Do you have a special
                        promotion code? If so, please enter it below and then
                        press the Redeem button. (You may skip this step). <br>
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td align="center" valign="top" class="coupon_code_cart">
<div id="coupon_code" style="white-space:nowrap;"><table cellpadding="5" cellspacing="0" border="0"><tr><td valign="top">Coupon Code:</td><td valign="top"><?php echo tep_draw_input_field('gv_redeem_code', $promo, 'id="gv_redeem_code"');?></td><td valign="top" style="padding:0 0 0 5px"><?php echo '<input type="image" name="submit_redeem" onClick="applyCoupon($(\'gv_redeem_code\').value)" src="' . DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/' . $language . '/button_redeem.gif" alt="' . IMAGE_REDEEM_VOUCHER . '" title="' . IMAGE_REDEEM_VOUCHER . '">'; ?></td></tr></table>
</div>
                    <noscript>Please enable javascript to checkout.</noscript></td>
                  </tr>
                </table>
				</td>
			    </tr>
            </table>
<?php
 }
?>

</td>
</tr>
<tr>
<td>

<table class="shipGen_tableMain">
<tr>
 <td style="padding:7px; padding-top:10px; border: 1px solid #FFFFFF; color:#000000"><b>&bull;  Shipping Calculator</b></td>
</tr>
<tr><td style="padding:7px; border: 1px solid #FFFFFF; border-top:0;">


<table cellpadding="0" cellspacing="0" align="center">
<tr>
 <td class="shipGen_tableMain_TD"><?php echo ENTRY_COUNTRY?></td>
 <td class="shipGen_tableMain_TD" colspan="2"><?php echo ENTRY_POST_CODE?></td>
 <td class="shipGen_tableMain_TD"><?php echo ENTRY_STATE?></td>
</tr>
<tr>
 <td class="shipGen_tableMain_TD" valign="top"><?php echo tep_get_country_list('ship_country',$ship_country, 'id="shipping_country"')?></td>
 <td class="shipGen_tableMain_TD" valign="top"><?php echo tep_draw_input_field('ship_postcode',$ship_postcode, 'style="width:100px" size="10" maxlength="10" id="shipping_postcode" onChange="reloadState(\'ship\')" onKeyPress="return disableEnterKey(event)"')?></td>
<td class="shipGen_tableMain_TD" valign="top">&nbsp;<input type="image" class="shopping_cart_go_button" onClick="reloadState('ship'); return false" src="<?php echo DIR_WS_IMAGES. 'go.gif'?>"></td>
 <td class="shipGen_tableMain_TD" valign="top" align="center">
<div id="ship_state_div" style="display:block; position:relative; z-index:1"></div>

<input type="hidden" name="ship_state" value="<?php echo $ship_zone_id?>">
<input type="hidden" name="ship_city" value="<?php echo $customer_city?>">
</td>
</tr>
</table>


</td></tr>
</table>

<table class="shipGen_tableMain">
<tr>
 <td style="padding:7px; padding-top:10px; border: 1px solid #FFFFFF; color:#000000"><b>&bull; <?php echo TABLE_HEADING_SHIPPING_METHOD; ?></b></td>
</tr>
<tr>
 <td colspan="3" valign="top" style="padding:5px; border: 1px solid #FFFFFF; border-top:0;">
  <div id="shipping_options"><noscript>Please enable javascript to checkout.</noscript></div>
  <input type="hidden" name="shipping" value="">
 </td>
</tr>
</table>



<table class="OrderSummary_tableMain">
<tr>
 <td style="padding:10px 7px 7px 7px; padding-top:10px; border: 1px solid #FFFFFF; color:#000000"><b>&bull; Order Total</b></td>
</tr>
<tr>
<td style="border:1px solid #FFFFFF; border-top:none; padding:10px;" align="right">
<div id="order_total"></div>
</td></tr>
</table>

<table class="Checkout_tableMain" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
 <td style="padding:10px 7px 7px 7px; font-weight: bold; border: 1px solid #FFFFFF; color:#000000" align="center">
 <?php
    $back = sizeof($navigation->path)-2;

	if(tep_session_is_registered('lpv')) {
		$backpage = $lpv;
	} elseif (!empty($navigation->path[$back])) { 
		$backpage = 'javascript:history.back('.$back.')';
	}
		echo '<a href="'.$backpage.'">'. tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE_SHOPPING) . '</a>';


 ?>
              &nbsp; &nbsp;


<?php

	$payset = tep_module('checkout');
	$buttns = array();
	$chkbuttn = 0;

	foreach($payset->getModulesCustomer($customer_id) AS $cls=>$mod) {
		$buttn = $mod->getExternalCheckoutButton();
		if($buttn) $buttns[]=$buttn; else $chkbuttn=1;
	}

	$prvbuttn = 0;

	if($chkbuttn) {

		if($any_out_of_stock == 0) {

			echo '<a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . '"">' . tep_image_button('button_checkout.gif', IMAGE_BUTTON_CHECKOUT) . '</a>';

			$prvbuttn = 1;
		}
	}

	foreach ($buttns AS $buttn) {
		echo '<span class="ext_checkout_'.$cls.'">'.($prvbuttn++?' <br> or <br> ':'').$buttn.'</span>';
	}

	if($customer_group_id > 1 && $any_out_of_stock > 0) {

		if (STOCK_ALLOW_VENDOR_CHECKOUT == 'true') {

			echo '<a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . '">' . tep_image_button('button_checkout.gif', IMAGE_BUTTON_CHECKOUT) . '</a>';

		} else {
			echo OUT_OF_STOCK_CANT_CHECKOUT_VENDORS;
		}

	} 
?>
</td>
 </tr></table>

<script type="text/javascript">
<!--
 reloadState('ship');
//-->
</script>
 
</td></tr>

<?php
	 } else { 
?>
 <tr><td style="padding:10px; font-weight: bold; border: 1px solid #FFFFFF; color:#000000">
<?php
    $back = sizeof($navigation->path)-2;
    if (isset($navigation->path[$back])) {

	if(tep_session_is_registered('lpv')) {
		echo '<a href="'.$lpv.'">';
	} else {
		echo '<a href="javascript:history.back(-2)">';
	}

	 echo tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE_SHOPPING);?></a>

 <?php 
} 
?>

&nbsp; &nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . '" onclick="window.location.href=(\''.tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL').'\'); return false">' . tep_image_button('button_checkout.gif', IMAGE_BUTTON_CHECKOUT) . '</a>'; ?>
 </td></tr>
<?php
 }
?>


<!-- tr>
<td>

<div align="center"> <?php //include(DIR_WS_MODULES . 'xsell_cart.php'); ?></div>
</td>
</tr -->
</table>
<?php
  } else {
?> <table width="100%" align="center">
      <tr>
        <td align="center"><?php new infoBox(array(array('text' => TEXT_CART_EMPTY))); ?></td>
      </tr>
      <tr>
        <td align="center" style="padding:10px 0 0 0">
<?php
	if(tep_session_is_registered('lpv')) {
		echo '<a href="'.$lpv.'">';
	} else {
		echo '<a href="javascript:history.back(-2)">';
	}

	echo tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE_SHOPPING);?></a></td>
      </tr>
</table>
<?php
  }
?>
 </form>
 </td>
</tr>
</table>
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
