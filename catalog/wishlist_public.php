<?php

  require('includes/application_top.php');
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_WISHLIST);
define('MAX_DISPLAY_WISHLIST_PRODUCTS', '50');
if(!isset($_GET['public_id'])) {
  	tep_redirect(tep_href_link(FILENAME_DEFAULT));
}

  $public_id = $_GET['public_id'];

$wish_box=(isset($_GET['wish_box']) && $_GET['wish_box']!='')?$_GET['wish_box']:NULL;

/*******************************************************************
****************** QUERY CUSTOMER INFO FROM ID *********************
*******************************************************************/

 	$customer_query = tep_db_query("select customers_firstname from " . TABLE_CUSTOMERS . " where customers_id = '" . $public_id . "'");
	$customer = tep_db_fetch_array($customer_query);

/*******************************************************************
****************** ADD PRODUCT TO SHOPPING CART ********************
*******************************************************************/

   if (isset($HTTP_POST_VARS['add_wishprod'])) {
	if(isset($HTTP_POST_VARS['add_prod_x'])) {
		foreach ($HTTP_POST_VARS['add_wishprod'] as $value) {
			$product_id = tep_get_prid($value);
			$cart->add_cart($product_id, $cart->get_quantity(tep_get_uprid($product_id, $HTTP_POST_VARS['id'][$value]))+1, $HTTP_POST_VARS['id'][$value]); 
		}
	}
  }


 $breadcrumb->add(NAVBAR_TITLE_WISHLIST, tep_href_link(FILENAME_WISHLIST, '', 'SSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top">
	  <table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

      </table>
	</td>
    <td width="100%" valign="top">
	  <table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        
            <td class="pageHeading" style="padding:5px 0 10px 0 "><?php echo $customer['customers_firstname'] .  HEADING_TITLE2; ?></td>

      </tr>

<?php
  if ($messageStack->size('wishlist') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('wishlist'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }

/*******************************************************************
****** QUERY THE DATABASE FOR THE CUSTOMERS WISHLIST PRODUCTS ******
*******************************************************************/

  $wishlist_query_raw = "select * from " . TABLE_WISHLIST . " where customers_id = '" . $public_id . "'";
  $wishlist_split = new splitPageResults($wishlist_query_raw, MAX_DISPLAY_WISHLIST_PRODUCTS);
  $wishlist_query = tep_db_query($wishlist_split->sql_query);

?>

<?php

  if (tep_db_num_rows($wishlist_query)) {

	if ($wishlist_split > 0 && (PREV_NEXT_BAR_LOCATION == '1' || PREV_NEXT_BAR_LOCATION == '3')) {
?>
      <tr>
        <td>
		<table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td><?php echo $wishlist_split->display_count(TEXT_DISPLAY_NUMBER_OF_WISHLIST); ?></td>
            <td align="right"><?php echo TEXT_RESULT_PAGE . ' ' . $wishlist_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?></td>
          </tr>
        </table>
		</td>
      </tr>

<?php
  }
?>
      <tr>
        <td style="padding:10px 0 10px 0"><?php echo tep_draw_form('wishlist_form', tep_href_link(FILENAME_WISHLIST_PUBLIC, 'public_id=' .$public_id)); ?>

				<table border="0" width="100%" cellspacing="0" cellpadding="3" class="productListing">
				  <tr>
						<td class="productListing-heading1"><?php echo BOX_TEXT_IMAGE; ?></td>
						<td class="productListing-heading2"><?php echo BOX_TEXT_PRODUCT; ?></td>
						<td class="productListing-heading3"><?php echo BOX_TEXT_PRICE; ?></td>
						<td class="productListing-heading4" style="width:25px; text-align:center"><?php echo BOX_TEXT_ADD; ?></td>
				  </tr>
<?php 

/*******************************************************************
***** LOOP THROUGH EACH PRODUCT ID TO DISPLAY IN THE WISHLIST ******
*******************************************************************/
	$i = 0;
    while ($wishlist = tep_db_fetch_array($wishlist_query)) {
	$wishlist_id = tep_get_prid($wishlist['products_id']);

    $products_query = tep_db_query("select pd.products_id, pd.products_name, pd.products_description, p.products_image, p.products_price, p.products_status, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . TABLE_SPECIALS . " s on pd.products_id = s.products_id where pd.products_id = '" . $wishlist_id . "' and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' order by products_name");
	$products = tep_db_fetch_array($products_query);

      if (($i/2) == floor($i/2)) {
        $class = "productListing-even";
      } else {
        $class = "productListing-odd";
      }

?>
				  <tr class="<?php echo $class; ?>">
					<td valign="top" class="productListing-data1" align="left" style="padding:2px"><a href="<?php echo tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $wishlist['products_id'], 'NONSSL'); ?>"><?php echo tep_image(DIR_WS_IMAGES . $products['products_image'], $products['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT); ?></a></td>
				<td valign="top" class="productListing-data2" align="left"><div style="color:#676767; font-size:11px; padding:5px 5px 0 10px;"><a href="<?php echo tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $wishlist['products_id'], 'NONSSL'); ?>"><b><?php echo $products['products_name']; ?></b></a>
<?php

/*******************************************************************
******** THIS IS THE WISHLIST CODE FOR PRODUCT ATTRIBUTES  *********
*******************************************************************/

                  $attributes_addon_price = 0;

                  // Now get and populate product attributes
                    $wishlist_products_attributes_query = tep_db_query("select products_options_id as po, products_options_value_id as pov from " . TABLE_WISHLIST_ATTRIBUTES . " where customers_id='" . $public_id . "' and products_id = '" . $wishlist['products_id'] . "'");
                    while ($wishlist_products_attributes = tep_db_fetch_array($wishlist_products_attributes_query)) {
                      // We now populate $id[] hidden form field with product attributes
                      echo tep_draw_hidden_field('id['.$wishlist['products_id'].']['.$wishlist_products_attributes['po'].']', $wishlist_products_attributes['pov']);
                      // And Output the appropriate attribute name
                      $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $wishlist_id . "'
                                       and pa.options_id = '" . $wishlist_products_attributes['po'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $wishlist_products_attributes['pov'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'");
                       $attributes_values = tep_db_fetch_array($attributes);
                       if ($attributes_values['price_prefix'] == '+')
                         { $attributes_addon_price += $attributes_values['options_values_price']; }
                       else if ($attributes_values['price_prefix'] == '-')
                         { $attributes_addon_price -= $attributes_values['options_values_price']; }
                       echo '<br /><small><i> ' . $attributes_values['products_options_name'] . ': ' . $attributes_values['products_options_values_name'] . '</i></small>';
                    } // end while attributes for product

                    if (tep_not_null($products['specials_new_products_price'])) {
                       $products_price = '<s>' . $currencies->display_price($products['products_price']+$attributes_addon_price, tep_get_tax_rate($products['products_tax_class_id'])) . '</s> <span class="productSpecialPrice">' . $currencies->display_price($products['specials_new_products_price']+$attributes_addon_price, tep_get_tax_rate($products['products_tax_class_id'])) . '</span>';
                    } else {
                       $products_price = $currencies->display_price($products['products_price']+$attributes_addon_price, tep_get_tax_rate($products['products_tax_class_id']));
                    }

/*******************************************************************
******* CHECK TO SEE IF PRODUCT HAS BEEN ADDED TO THEIR CART *******
*******************************************************************/

		if($cart->in_cart($wishlist[products_id])) {
				echo '<br><div class="wishlist_itemincart">' . TEXT_ITEM_IN_CART . '</div>';
		}

/*******************************************************************
********** CHECK TO SEE IF PRODUCT IS NO LONGER AVAILABLE **********
*******************************************************************/

 		if($products['products_status'] == 0) {
   				echo '<br><div class="wishlist_itemincart"><b>' . TEXT_ITEM_NOT_AVAILABLE . '</b></div>';
  		}

		$i++;
?>
				</div>	</td>
					<td valign="top" class="productListing-data4" style="text-align:center; width:65px; padding:10px 5px 0 5px;"><?php echo $products_price; ?></td>
					<td valign="top" class="productListing-data4" style="width:25px; padding:10px 5px 0 5px;">
<?php 

/*******************************************************************
* PREVENT THE ITEM FROM BEING ADDED TO CART IF NO LONGER AVAILABLE *
*******************************************************************/

			if($products['products_status'] != 0) {
		//	echo tep_draw_checkbox_field('add_wishprod[]',$wishlist_id,($wishlist_id==$wish_added));
echo '<a href="javascript:addToCart({quantity:1,products_id:' . $products['products_id'] . '});"><img src="/layout/img/buttons/english/button_in_cart.gif" alt="Add To Cart" width="72" height="25" border="0"></a>';	
			}
?>					</td>
				  </tr>

<?php
    }
?>
				</table></form>
		</td>
	  </tr>


<?php
  if ($wishlist_split > 0 && (PREV_NEXT_BAR_LOCATION == '2' || PREV_NEXT_BAR_LOCATION == '3')) {
?>
      <tr>
        <td style="padding:15px 0 0 0">
		<table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td><?php echo $wishlist_split->display_count(TEXT_DISPLAY_NUMBER_OF_WISHLIST); ?></td>
            <td align="right"><?php echo TEXT_RESULT_PAGE . ' ' . $wishlist_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?></td>
          </tr>
        </table>
		</td>
      </tr>

<?php
	}
?>
</table>

<?
} else { // Nothing in the customers wishlist

?>
  <tr>
	<td>
	<table border="0" width="100%" cellspacing="0" cellpadding="2">
	  <tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		  <tr>
			<td class="main"><?php echo BOX_TEXT_NO_ITEMS;?></td>
		  </tr>
		</table>
		</td>
	  </tr>
	</table>
	</td>
  </tr>
</table>

<?php
	}
?>

	</td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>