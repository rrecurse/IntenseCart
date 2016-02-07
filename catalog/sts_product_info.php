<?php
/*
$Id: sts_product_info.php,v 1.1 2005/07/25 18:19:27 stsdsea Exp stsdsea $

*/

// This program is designed to build template variables for the product_info.php page template
// This code was modified from product_info.php

$template['productid'] = $product_info['products_id'];
$template['productsid'] = $product_info['products_id']; // Just for consistency

// Start the "Add to Cart" form
$template['startform'] = tep_draw_form('cart_quantity', tep_href_link(FILENAME_PRODUCT_INFO, tep_get_all_get_params(array('action')) . 'action=add_product'));
// Add the hidden form variable for the Product_ID
$template['startform'] .= tep_draw_hidden_field('products_id', $product_info['products_id']);
$template['endform'] = "</form>";

// Get product information from products_id parameter
$product_info_query = tep_db_query("select p.products_id, pd.products_name, pd.products_description, pd.products_info, p.products_model, p.products_quantity, p.products_image, p.products_image_xl_1, p.products_image_xl_2, p.products_image_xl_3, p.products_image_xl_4, p.products_image_xl_5, p.products_image_xl_6, pd.products_url, p.products_price, p.products_tax_class_id, p.products_date_added, p.products_date_available, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status = '1' and p.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");
$product_info = tep_db_fetch_array($product_info_query);

$template['regularprice'] = $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
if ($new_price = tep_get_products_special_price($product_info['products_id'])) {
  $template['regularpricestrike'] = "<s>" . $template['regularprice'] . "</s>";
  $template['specialprice'] = $currencies->display_price($new_price, tep_get_tax_rate($product_info['products_tax_class_id']));
} else {
  $template['specialprice'] = '';
  $template['regularpricestrike'] = $template['regularprice'];
}

$template['productname'] = $product_info['products_name'];
$template['productmodel'] = (tep_not_null($product_info['products_model']) ? $product_info['products_model'] : '');


if (tep_not_null($product_info['products_image'])) {
  $template['imagesml'] = tep_image(DIR_WS_IMAGES . $product_info['products_image'], addslashes($product_info['products_name']), SMALL_IMAGE_WIDTH, '', 'hspace="5" vspace="5" border=0');
  $template['imagesml_popup'] = '<a href="javascript:popupWindow();">' . $template['imagesml'] . '</a>';
} else {
  $template['imagesml'] = tep_image(DIR_WS_IMAGES . 'dvd/no_picture.gif', addslashes($product_info['products_name']), '100', '80', 'hspace="5" vspace="5"');
  $template['imagesml_popup'] = $template['imagesml'];
}


if ($template['imagelrg'] == '') {
  if ($template['imagemed'] != '') {
    $template['imagelrg'] = $template['imagemed'];
    $template['imagelrg_popup'] = $template['imagemed_popup'];
  } elseif ($template['imagesml'] != '') {
    $template['imagelrg'] = $template['imagesml'];
    if ($template['imagesml_popup'] != $template['imagesml']) {
      $template['imagelrg_popup'] = $template['imagesml_popup'];
    } else {
      $template['imagelrg_popup'] = $template['imagelrg'];
    }
  }
}

if ($template['imagemed'] == '') {
  $template['imagemed'] = $template['imagesml'];
  if ($template['imagesml_popup'] != $template['imagesml']) {
    $template['imagemed_popup'] = $template['imagesml_popup'];
  } else {
    $template['imagemed_popup'] = $template['imagemed'];
  }
}


if ($template['imagesml_popup'] != '') {
  $template['sml_image_row'] = "<a href=\"javascript:void(0);\" onMouseOver=\"swapImage('mainimage','','/images/" . $product_info['products_image'] . "',1)\">" . tep_image('/images/' . $product_info['products_image'], 'Product Views', SMALL_IMAGE_WIDTH, '', 'style="border:1px solid #000000"') . '</a>';
  
  if (tep_not_null($product_info['products_image_xl_1'])) {
    $template['sml_image_row'] .= "<a href=\"javascript:void(0);\" onMouseOver=\"swapImage('mainimage','','/images/" . $product_info['products_image_xl_1'] . '\',1)">' . tep_image('/images/' . $product_info['products_image_xl_1'], 'Product Views', SMALL_IMAGE_WIDTH, '', 'style="border:1px solid #000000"') . '</a>';
  }
} else {
  $template['sml_image_row'] = '';
}
  


$template['productdesc'] = stripslashes($product_info['products_description']); 
$template['productshortdesc'] = stripslashes($product_info['products_info']); 

// Get the number of product attributes (the select list options)
$products_attributes_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$HTTP_GET_VARS['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "'");
$products_attributes = tep_db_fetch_array($products_attributes_query);
// If there are attributes (options), then...
if ($products_attributes['total'] > 0) {
  // Print the options header
  $template['optionheader'] = TEXT_PRODUCT_OPTIONS;

  // Select the list of attribute (option) names
  $products_options_name_query = tep_db_query("select distinct popt.products_options_id, popt.products_options_name from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$HTTP_GET_VARS['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "' order by popt.products_options_name");

  while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
    $products_options_array = array();
    $products_options_query = tep_db_query("select pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov where pa.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and pa.options_id = '" . (int)$products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int)$languages_id . "'");

    // For each option name, get the individual attribute (option) choices
    while ($products_options = tep_db_fetch_array($products_options_query)) {
      $products_options_array[] = array('id' => $products_options['products_options_values_id'], 'text' => $products_options['products_options_values_name']);

      // If the attribute (option) has a price modifier, include it
      if ($products_options['options_values_price'] != '0') {
        $products_options_array[sizeof($products_options_array)-1]['text'] .= ' (' . $products_options['price_prefix'] . $currencies->display_price($products_options['options_values_price'], tep_get_tax_rate($product_info['products_tax_class_id'])) .') ';
      }

    }
 
    // If we should select a default attribute (option), do it here
    if (isset($cart->contents[$HTTP_GET_VARS['products_id']]['attributes'][$products_options_name['products_options_id']])) {
      $selected_attribute = $cart->contents[$HTTP_GET_VARS['products_id']]['attributes'][$products_options_name['products_options_id']];
    } else {
      $selected_attribute = false;
    }

    $template['optionnames'] .= $products_options_name['products_options_name'] . ':<br>'; 
    $template['optionchoices'] .=  tep_draw_pull_down_menu('id[' . $products_options_name['products_options_id'] . ']', $products_options_array, $selected_attribute) . "<br>"; 
  }
} else {
  // No options, blank out the template variables for them
  $template['optionheader'] = '';
  $template['optionnames'] = '';
  $template['optionchoices'] = '';
}

// See if there are any reviews
$reviews_query = tep_db_query("select count(*) as count from " . TABLE_REVIEWS . " where products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "'");
$reviews = tep_db_fetch_array($reviews_query);
if ($reviews['count'] > 0) {
  $template['reviews'] = TEXT_CURRENT_REVIEWS . ' ' . $reviews['count']; 
} else {
  $template['reviews'] = '';
}

// See if there is a product URL
if (tep_not_null($product_info['products_url'])) {
  $template['moreinfolabel'] = 'Click here for more info on ' . $product_info['products_name'];
  $template['moreinfourl'] = tep_href_link(FILENAME_REDIRECT, 'action=url&goto=' . urlencode($product_info['products_url']), 'NONSSL', true, false); 
} else {
  $template['moreinfolabel'] = '';
  $template['moreinfourl'] = '';
}

$template['moreinfolabel'] = str_replace('%s', $template['moreinfourl'], $template['moreinfolabel']);

// See if product is not yet available
if ($product_info['products_date_available'] > date('Y-m-d H:i:s')) {
  $template['productdatelabel'] = TEXT_DATE_AVAILABLE;
  $template['productdate'] = tep_date_long($product_info['products_date_available']);
} else {
  $template['productdatelabel'] = TEXT_DATE_ADDED;
  $template['productdate'] = tep_date_long($product_info['products_date_added']); 
}

// Strip out %s values
$template['productdatelabel'] = str_replace('%s.', '', $template['productdatelabel']);

// See if any product reviews
$template['reviewsurl'] = tep_href_link(FILENAME_PRODUCT_REVIEWS, tep_get_all_get_params());
$template['reviewsbutton'] = tep_image_button('button_reviews.gif', IMAGE_BUTTON_REVIEWS);
$template['addtocartbutton'] = tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART);
$template['wishlistbutton'] = tep_image_submit('go.gif', 'Add to Wishlist', 'name="wishlist" value="wishlist"');
$template['quantityfield'] = tep_draw_input_field('quantity', '1', 'maxlength=2 size=2');


$sts_blocks = array(array('name' => 'xsell', 'include' => DIR_WS_MODULES . FILENAME_XSELL_PRODUCTS),
                    array('name' => 'xsellbuynow', 'include' => DIR_WS_MODULES . FILENAME_XSELL_PRODUCTS_BUYNOW),
                    array('name' => 'productimages', 'include' => DIR_WS_MODULES . 'product_images.php'),
                    array('name' => 'wishlistbox', 'include' => DIR_WS_BOXES . 'wishlist.php'),
                    array('name' => 'bookmarkme', 'include' => DIR_WS_BOXES . 'bookmark.php')
                    );
                    

foreach ($sts_blocks as $block) {
  $sts_block_name = $block['name'];
  require(STS_START_CAPTURE);
  include($block['include']);
  require(STS_STOP_CAPTURE);
  $template[$sts_block_name] = strip_unwanted_tags($sts_block[$sts_block_name], $sts_block_name);
}

?>
