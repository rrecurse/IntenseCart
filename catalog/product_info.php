<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_INFO);

	// # if no product id detected, redirect to root
	if(!empty($_GET['products_id'])) {
		$products_id = (int)$_GET['products_id'];
	} else {
		header('Location: http://' . $_SERVER['HTTP_HOST']);
		exit();
	}

	$product_check_query = tep_db_query("SELECT COUNT(*) AS total 
										 FROM " . TABLE_PRODUCTS . " p
										 LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
										 WHERE p.products_status = '1' 
										 AND p.products_id = '" . $products_id . "' 
										 AND pd.language_id = '" . (int)$languages_id . "'
										"); 

	$product_check = tep_db_fetch_array($product_check_query);


	if(!tep_session_is_registered('sppc_customer_group_id')) {
    	$customer_group_id = '0';
	} else {
		$customer_group_id = $sppc_customer_group_id;
	}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">

<script type="text/javascript" async src="/includes/tabs/webfxlayout.js"></script>
<script type="text/javascript" async src="/includes/tabs/tabpane.js"></script>

<link id="luna-tab-style-sheet" type="text/css" rel="stylesheet" href="/includes/tabs/tabpanewebfx.css" >
<link rel="stylesheet" type="text/css" href="stylesheet.css">


<script>

function popupWindow(pid,wWidth,wHeight) {
  var winl = (screen.width - wWidth) / 2;
  var wint = (screen.height - wHeight) / 2;
  window.open('/popup_image.php?pid='+pid,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width='+wWidth+',height='+wHeight+',screenX='+winl+',screenY='+wint+',top='+wint+',left='+winl+'')
}

</script>

</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>


<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><?php echo tep_draw_form('cart_quantity', tep_href_link(FILENAME_PRODUCT_INFO, tep_get_all_get_params(array('action')) . 'action=add_product')); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php
  if($product_check['total'] < 1) {
?>
      <tr>
        <td><?php new infoBox(array(array('text' => TEXT_PRODUCT_NOT_FOUND))); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  } else {


		// # future modification will be moving all of these products table for images into their own images table.
		$product_info_query = tep_db_query("SELECT p.products_id, 
												   pd.products_name, 
												   pd.products_description, 
												   p.products_model, 
												   p.products_quantity, 
												   p.products_image, 
												   p.products_image_xl_1, 
												   p.products_image_xl_2, 
												   p.products_image_xl_3, 
												   p.products_image_xl_4, 
												   p.products_image_xl_5, 
												   p.products_image_xl_6, 
												   pd.products_url, 
												   p.products_price, 
												   p.products_tax_class_id, 
												   p.products_date_added, 
												   p.products_date_available, 
												   p.manufacturers_id 
											 FROM " . TABLE_PRODUCTS . " p
											 LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
											 WHERE p.products_status = '1' 
											 AND p.products_id = '" . $products_id . "'  
											 AND pd.language_id = '" . (int)$languages_id . "'
											");

    $product_info = tep_db_fetch_array($product_info_query);

	
	// # Update impression/view count
	tep_db_query("UPDATE " . TABLE_PRODUCTS_DESCRIPTION . " 
				  SET products_viewed = (products_viewed + 1) 
				  WHERE products_id = '" . $products_id . "' 
				  AND language_id = '" . (int)$languages_id . "'
				");

    if($new_price = tep_get_products_special_price($product_info['products_id'])) {
	// # BOF Separate Price per Customer

		$scustomer_group_price_query = tep_db_query("SELECT customers_group_price 
													 FROM " . TABLE_PRODUCTS_GROUPS . " 
													 WHERE products_id = '" . $products_id. "' 
													 AND customers_group_id =  '" . $customer_group_id . "'
													");

		if($scustomer_group_price = tep_db_fetch_array($scustomer_group_price_query)) {
			$product_info['products_price']= $scustomer_group_price['customers_group_price'];
		}
		// # EOF Separate Price per Customer

		$products_price = '<s>' . $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id'])) . '</s> <span class="productSpecialPrice">' . $currencies->display_price($new_price, tep_get_tax_rate($product_info['products_tax_class_id'])) . '</span>';

	} else {

		// # BOF Separate Price per Customer
		$scustomer_group_price_query = tep_db_query("SELECT customers_group_price 
													 FROM " . TABLE_PRODUCTS_GROUPS . " 
													 WHERE products_id = '" . $products_id. "' 
													 AND customers_group_id =  '" . $customer_group_id . "'
													");

		if($scustomer_group_price = tep_db_fetch_array($scustomer_group_price_query)) {
			$product_info['products_price']= $scustomer_group_price['customers_group_price'];
		}
		// # EOF Separate Price per Customer
		$products_price = $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
	}

    if (tep_not_null($product_info['products_model'])) {
      $products_name = $product_info['products_name'] . '<br><span class="smallText">[' . $product_info['products_model'] . ']</span>';
    } else {
      $products_name = $product_info['products_name'];
    }
    
	// # DISPLAY PRODUCT WAS ADDED TO WISHLIST IF WISHLIST REDIRECT IS ENABLED 
	if(tep_session_is_registered('wishlist_id')) {
?>
      <tr>
        <td class="messageStackSuccess"><?php echo PRODUCT_ADDED_TO_WISHLIST; ?></td>
      </tr> 
      <?php tep_session_unregister('wishlist_id'); } ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" valign="top"><?php echo $products_name; ?></td>
            <td class="pageHeading" align="right" valign="top"><?php echo $products_price; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  if ($messageStack->size('friend') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('friend'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }
?>
      <tr>
        <td class="main">
<?php

	$product_description_string = $product_info['products_description'];
	$tab_array = preg_match_all ("|<newtab>(.*)</newtab>|Us", $product_description_string, $matches, PREG_SET_ORDER); // <new_tab>

	if ($tab_array){ ?>
		<div class="tab-pane" id="tabpane1">

		<script type="text/javascript">
			//tp = new WebFXTabPane(document.getElementById("tabpane1"));
			tp = new WebFXTabPane(document.getElementById("tabpane1"), false);
		</script>

<?php
		for ($i=0, $n=sizeof($matches); $i<$n; $i++) {

			$this_tab_name = preg_match_all ("|<tabname>(.*)</tabname>|Us", $matches[$i][1], $tabname, PREG_SET_ORDER);

				if ($this_tab_name){
					echo '<div class="tab-page" id="tabPage' . $i . '"> <h2 class="tab">' . $tabname[0][1] . '</h2>' .
						 '<script type="text/javascript">tp.addTabPage(document.getElementById("tabPage' . $i . '"));</script>';

			if (tep_not_null($product_info['products_image'])) {
?>
				<table border="0" cellspacing="0" cellpadding="2" width="100%">
					<tr>
						<td width="100%">
							<table border="0" cellspacing="0" cellpadding="2" align="right">
								<tr>
									<td align="center" class="smallText"> 
<script language="javascript">
<!--
document.write('<?php echo '<a href="javascript:popupWindow(\\\'' . tep_href_link(FILENAME_POPUP_IMAGE, 'pID=' . $product_info['products_id'] . '&image=0') . '\\\')">' . tep_image(DIR_WS_IMAGES . $new_image, addslashes($product_info['products_name']), $image_width, $image_height, 'hspace="5" vspace="5" alt=""') . '<br>' . tep_image_button('image_enlarge.gif', TEXT_CLICK_TO_ENLARGE) . '</a>'; ?>');
//-->
</script>
<noscript>
<?php echo '<a href="' . tep_href_link(DIR_WS_IMAGES . $product_info['products_image']) . '">' . tep_image(DIR_WS_IMAGES . $new_image . '&image=0', addslashes($product_info['products_name']), $image_width, $image_height, 'hspace="5" vspace="5" alt=""') . '<br>' . tep_image_button('image_enlarge.gif', TEXT_CLICK_TO_ENLARGE) . '</a>'; ?>
</noscript>


<?php echo '<a href="' . tep_href_link(DIR_WS_IMAGES . $product_info['products_image']) . '" target="_blank">' . tep_image(DIR_WS_IMAGES . $product_info['products_image'], 
$product_info['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5"') . '<br>' . TEXT_CLICK_TO_ENLARGE . '</a>'; ?>
</noscript>
</td>
</tr>
</table>
<?php
}
if (preg_match_all ("|<tabpage>(.*)</tabpage>|Us", $matches[$i][1], $tabpage, PREG_SET_ORDER)){
require($tabpage[0][1]);
}elseif (preg_match_all ("|<tabtext>(.*)</tabtext>|Us", $matches[$i][1], $tabtext, PREG_SET_ORDER)){
echo '<pre><div class="boxTextMain">' . $tabtext[0][1] . '</div></pre><br>';
}
echo '</tr></td>
</table></div>';
}
}
echo '</div>';
} else {
if(tep_not_null($product_info['products_image'])) {
?>
<table border="0" cellspacing="0" cellpadding="2" align="right">
<tr>
<td align="center" class="smallText">


<script type="text/javascript"><!--
      document.write('<?php echo '<a href="javascript:popupWindow(\\\'' . tep_href_link(FILENAME_POPUP_IMAGE, 'pID=' . $product_info['products_id'] . '&image=0') . '\\\')">' . tep_image(DIR_WS_IMAGES . $new_image, addslashes($product_info['products_name']), $image_width, $image_height, 'hspace="5" vspace="5" alt=""') . '<br>' . tep_image_button('image_enlarge.gif', TEXT_CLICK_TO_ENLARGE) . '</a>'; ?>');
//--></script>
<noscript>
      <?php echo '<a href="' . tep_href_link(DIR_WS_IMAGES . $product_info['products_image']) . '">' . tep_image(DIR_WS_IMAGES . $new_image . '&image=0', addslashes($product_info['products_name']), $image_width, $image_height, 'hspace="5" vspace="5" alt=""') . '<br>' . tep_image_button('image_enlarge.gif', TEXT_CLICK_TO_ENLARGE) . '</a>'; ?>
</noscript>


<h1>Product Info</h1>

</td>
</tr>
</table>
<?php
}
?>
<!-- End Tab Pane //-->
<p><?php echo stripslashes($product_info['products_description']); ?></p>
<?php
} 
    $products_attributes_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$_GET['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "'");
    $products_attributes = tep_db_fetch_array($products_attributes_query);
    if ($products_attributes['total'] > 0) {
?>
          <table border="0" cellspacing="0" cellpadding="2">
            <tr>
              <td class="main" colspan="2"><?php echo TEXT_PRODUCT_OPTIONS; ?></td>
            </tr>
<?php
      $products_options_name_query = tep_db_query("select distinct popt.products_options_id, popt.products_options_name from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$_GET['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "' order by popt.products_options_name");
      while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {
        $products_options_array = array();
        $products_options_query = tep_db_query("select pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov where pa.products_id = '" . (int)$_GET['products_id'] . "' and pa.options_id = '" . (int)$products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int)$languages_id . "'");
        while ($products_options = tep_db_fetch_array($products_options_query)) {
          $products_options_array[] = array('id' => $products_options['products_options_values_id'], 'text' => $products_options['products_options_values_name']);
          if ($products_options['options_values_price'] != '0') {
            $products_options_array[sizeof($products_options_array)-1]['text'] .= ' (' . $products_options['price_prefix'] . $currencies->display_price($products_options['options_values_price'], tep_get_tax_rate($product_info['products_tax_class_id'])) .') ';
          }
        }

        if (isset($cart->contents[$_GET['products_id']]['attributes'][$products_options_name['products_options_id']])) {
          $selected_attribute = $cart->contents[$_GET['products_id']]['attributes'][$products_options_name['products_options_id']];
        } else {
          $selected_attribute = false;
        }
?>
            <tr>
              <td class="main"><?php echo $products_options_name['products_options_name'] . ':'; ?></td>
              <td class="main"><?php echo tep_draw_pull_down_menu('id[' . $products_options_name['products_options_id'] . ']', $products_options_array, $selected_attribute); ?></td>
            </tr>
<?php
      }
?>
          </table>
<?php
    }
?>
        </td>
      </tr>
<?php
// BOF MaxiDVD: Modified For Ultimate Images Pack!
 if (ULTIMATE_ADDITIONAL_IMAGES == 'Enable') { include(DIR_WS_MODULES . 'additional_images.php'); }
// BOF MaxiDVD: Modified For Ultimate Images Pack!
; ?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
    $reviews_query = tep_db_query("select count(*) as count from " . TABLE_REVIEWS . " r, " . TABLE_REVIEWS_DESCRIPTION . " rd where r.products_id = '" . (int)$_GET['products_id'] . "' and r.reviews_id = rd.reviews_id and rd.languages_id = '" . (int)$languages_id . "'");
    $reviews = tep_db_fetch_array($reviews_query);
    if ($reviews['count'] > 0) {
?>
      <tr>
        <td class="main"><?php echo TEXT_CURRENT_REVIEWS . ' ' . $reviews['count']; ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
    }

    if (tep_not_null($product_info['products_url'])) {
?>
      <tr>
        <td class="main"><?php echo sprintf(TEXT_MORE_INFORMATION, tep_href_link(FILENAME_REDIRECT, 'action=url&goto=' . urlencode($product_info['products_url']), 'NONSSL', true, false)); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
    }

    if ($product_info['products_date_available'] > date('Y-m-d H:i:s')) {
?>
      <tr>
        <td align="center" class="smallText"><?php echo sprintf(TEXT_DATE_AVAILABLE, tep_date_long($product_info['products_date_available'])); ?></td>
      </tr>
<?php
    } else {
?>
      <tr>
        <td align="center" class="smallText"><?php echo sprintf(TEXT_DATE_ADDED, tep_date_long($product_info['products_date_added'])); ?></td>
      </tr>
<?php
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main"><?php echo '<a href="' . tep_href_link(FILENAME_PRODUCT_REVIEWS, tep_get_all_get_params()) . '">' . tep_image_button('button_reviews.gif', IMAGE_BUTTON_REVIEWS) . '</a>'; ?></td>
                <td align="center"><?php echo tep_image_submit('button_wishlist.gif', 'Add to Wishlist', 'name="wishlist" value="wishlist"'); ?></td>
                

//<!--<td class="main" align="right"><?php echo tep_draw_hidden_field('products_id', $product_info['products_id']) . tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART); ?></td>-->

<?php
	if ($product_info['products_price'] == CALL_FOR_PRICE_VALUE){
?>
		<td class="main" align="right"><a href="javascript:history.go(-1)"><?php echo tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE); ?></a></td>
<?php
	} else {
?>
<td class="main" align="right"><?php echo tep_draw_hidden_field('products_id', $product_info['products_id']) . tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART); ?></td>
<?php
}
?>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></form></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td>
<?php
/*
    if ((USE_CACHE == 'true') && empty($SID)) {
      echo tep_cache_also_purchased(3600);
      include(DIR_WS_MODULES . FILENAME_XSELL_PRODUCTS);
    } else {
*/
      include(DIR_WS_MODULES . FILENAME_XSELL_PRODUCTS);
      include(DIR_WS_MODULES . FILENAME_ALSO_PURCHASED_PRODUCTS);
  //  }
  }
?>
        </td>
      </tr>
    </table></form></td>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
