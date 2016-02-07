<?php

    
  //Start an array of items being suggested.
  $xsell_contents_array = array();

  //Start to build the HTML that will display the xsell box.
  $xsell_box_contents = '';
  
  $xsl_draw_table=0;
  $xsl_draw_tr=0;
  $xsl_cols=0;
  $xsl_items=0;

  //Go through each item in the cart, and look for xsell products.
  foreach ($products AS $product_id_in_cart) {
    if (strpos($product_id_in_cart['id'], '{') !== false) {
      $products_id = substr($product_id_in_cart['id'], 0, strpos($product_id_in_cart['id'], '{'));
    } else {
      $products_id = $product_id_in_cart['id'];
    }
    //Main XSELL Query
$xsell_query_raw = "SELECT p.products_id, pd.products_name, pd.products_info, pd.products_info_alt, p.products_image, p.products_price, p.products_tax_class_id, xp.xsell_id, m.manufacturers_name, m.manufacturers_id from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m ON p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS ." mp where p.products_status = '1' AND p.products_id = xp.xsell_id AND (xp.products_id = mp.master_products_id OR xp.products_id = mp.products_id) AND mp.products_id = '" . $products_id . "' AND p.master_products_id = pd.products_id AND pd.language_id = '" . $languages_id . "' GROUP BY p.products_id ORDER BY xp.sort_order,pd.products_name";
$xsell_query = tep_db_query($xsell_query_raw);

    //Cycle through each suggested product and add to box, if there are none
    //go to the next product in the cart.
    while ($xsell = tep_db_fetch_array($xsell_query)) {

      //If the xsell item is already being suggested, we don't need
      //to suggest it again.  Keep track of xsell items I've already dealt
      //with.
      if (!in_array($xsell['products_id'], $xsell_contents_array)) {

        //Add this xsell product to the list of xsell products dealt with. 
        array_push($xsell_contents_array, $xsell['products_id']);  

        //If a suggested product is already in the cart, we don't need to
        //suggest it again. 
//        if (!$cart->in_cart($xsell['products_id'])) {  
	$xsl_in_cart=0;
	// By MegaJim - Strip attributes!
	foreach ($products AS $crt) {
	 if ($xsl_in_cart=($xsell['products_id']==preg_replace('/\{.*/','',$crt['id']))) break;
	}
        if (!$xsl_in_cart) {  
	  if ($xsl_items++>=XSELL_DISPLAY_MAX) break;
	  if (!$xsl_draw_table) {

$buynow_text  .= IMAGE_BUTTON_BUY_NOW;
$moreinfo_text  .= 'More info';

?>
<?php if (defined('CART_XSELL_TITLE')) echo '<div class="cart_xsell_title">'.CART_XSELL_TITLE.' </div>';?>
	    <input type="hidden" name="add_recommended[]" value="">
	    <table width="100%"><tr><td>
	      
	      <table width="100%" cellpadding="0" cellspacing="0" class="xsell_cart_table">
<?php
	    $xsl_draw_table=1;
	  }
	  if (!$xsl_draw_tr) {
	    echo "<tr>\n";
	    $xsl_draw_tr=1;
	  }
          if ($xsell_price = tep_get_products_special_price($xsell['products_id'])) {
            $display_price = '<s>' . $currencies->display_price($xsell['products_price'], tep_get_tax_rate($xsell['products_tax_class_id'])) . '</s> <span class="productSpecialPrice">' . $currencies->display_price($xsell_price, tep_get_tax_rate($xsell['products_tax_class_id'])) . '</span>';
          } else {
            $display_price = $currencies->display_price($xsell['products_price'], tep_get_tax_rate($xsell['products_tax_class_id']));
          }
          $qview_desc=tep_get_products_qview_desc($xsell['products_id']);

echo '<td>
  <div class="xSellprodListing_masterDiv" style="position:relative; z-index:1"><table width="100%" cellpadding="0" cellspacing="0"><tr><td>
<div class="xSellprodListing_mainbg" style="position:absolute; z-index:1;"></div>
<div class="xSellprodListing_img" style="position:absolute; z-index:2;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], XSELL_IMAGE_WIDTH, XSELL_IMAGE_HEIGHT) . '</a></div>
<div class="xSellprodListing_title" style="position:absolute; z-index:3;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '"><b>' . $xsell['products_name'] . '</b></a></div>
' . ((is_bs_icon($xsell['products_id'])) ? '<div class="xSellprodListing_bestseller_icon" style="position:absolute; z-index:21;">'.tep_image(DIR_WS_IMAGES.'items/bestseller.gif','','','','class="transpng"').'</div>' : '') . ' ' . ((is_free_shipping($xsell['products_id'])) ? '<div class="xSellprodListing_free_shipping_icon" style="position:absolute; z-index:22;"></div>' : '') . '
'.(!isset($qview_desc)?'':
'<!--div class="xSellprodListing_qview_icon" style="position:absolute; z-index:20;"><img src="/layout/img/quickview.gif" border="0" alt="" onMouseover="'.htmlspecialchars('ShowQView('.tep_js_quote($xsell['products_name']).",".tep_js_quote($xsell['products_info_alt'] != '' ? $xsell['products_info_alt'] : $xsell['products_info']).",".tep_js_quote($qview_desc).",".tep_js_quote($display_price).",".tep_js_quote(tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], FEATURED_POPUP_IMAGE_WIDTH, FEATURED_POPUP_IMAGE_HEIGHT)).")").'" onMouseout="hideddrivetip()"></div-->').'
<div class="xSellprodListing_desc" style="position:absolute; z-index:4;">' . ($xsell['products_info_alt'] != '' ? $xsell['products_info_alt'] : $xsell['products_info']) . '</div>
			
<div class="xSellprodListing_AddtoCart" style="position:absolute; z-index:6;" onclick="document.cart_quantity.elements[\'add_recommended[]\'].value=\''.$xsell['products_id'].'\'; document.cart_quantity.submit();"></div>

<div class="xSellprodListing_price" style="position:absolute; z-index:6;">' . $display_price . '</div>
<div class="xSellprodListing_more" style="position:absolute; z-index:6;"><b><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '" class="morelink">' . $moreinfo_text . '</a></b></div>
<div class="xSellprodListing_seperatorVert" style="position:absolute; z-index:5;"><b></b></div>
<div class="xSellprodListing_seperatorHorz" style="position:absolute; z-index:5;"><b></b></div>
<div class="xSellprodListing_priceseperator" style="position:absolute; z-index:6;"><b></b></div>
</td></tr></table>
</div>
</td>';
	if (++$xsl_cols>=XSELL_DISPLAY_COLS) {
	    if ($xsl_draw_tr) echo "</tr>\n";
	    $xsl_draw_tr=0;
	    $xsl_cols=0;
	}
	
      }  //END OF IF ALREADY IN CART
    }  // END OF IF ALREADY SUGGESTED
  }  //END OF WHILE QUERY STILL HAS ROWS
  if ($xsl_items>=XSELL_DISPLAY_MAX) break;
}  //END OF FOREACH PRODUCT IN CART LOOP

if ($xsl_draw_tr) echo "</tr>\n";
if ($xsl_draw_table) echo "</table></td></tr></table>\n";


?>
