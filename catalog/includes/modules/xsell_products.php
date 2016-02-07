<?php

@include(DIR_FS_CATALOG_LAYOUT. "languages/$language/" . FILENAME_XSELL_PRODUCTS);
require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_XSELL_PRODUCTS);

// BOF Separate Pricing Per Customer
 if(!tep_session_is_registered('sppc_customer_group_id')) {
 $customer_group_id = '0';
 } else {
  $customer_group_id = $sppc_customer_group_id;
 }

if ($_GET['products_id']) {
if ($customer_group_id != '0') {
$xsell_query = tep_db_query("select distinct p.products_id, p.products_image, pd.products_info, pd.products_info_alt, pd.products_name, p.products_tax_class_id, IF(pg.customers_group_price IS NOT NULL, pg.customers_group_price, p.products_price) as products_price from " . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_GROUPS . " pg using(products_id), " . TABLE_PRODUCTS_DESCRIPTION . " pd where xp.products_id = '" . $_GET['products_id'] . "' and xp.xsell_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_status = '1' and p.products_price > 0 and pg.customers_group_id = '".$customer_group_id."' order by sort_order asc limit " . MAX_DISPLAY_ALSO_PURCHASED);
} else {

$xsell_query = tep_db_query("select distinct p.products_id, p.products_image, pd.products_info, pd.products_info_alt, pd.products_name, p.products_tax_class_id, products_price from " . TABLE_PRODUCTS_XSELL . " xp, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where xp.products_id = '" . $HTTP_GET_VARS['products_id'] . "' and xp.xsell_id = p.products_id and p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_status = '1'  and p.products_price > 0 order by sort_order asc limit " . MAX_DISPLAY_ALSO_PURCHASED);
}
// EOF Separate Pricing Per Customer
$num_products_xsell = tep_db_num_rows($xsell_query);
if ($num_products_xsell >= 1) {
?>

<?php
     $info_box_contents = array();
     $info_box_contents[] = array('align' => 'left', 'text' => '<div class="xSellprodListing_masterTitle">' . XSELL_PRODUCTS_MAINTITLE . '</div>');
     new contentBoxHeading($info_box_contents);

     $row = 0;
     $col = 0;
     $info_box_contents = array();
     while ($xsell = tep_db_fetch_array($xsell_query)) {
       $xsell['specials_new_products_price'] = tep_get_products_special_price($xsell['products_id']);

if ($xsell['specials_new_products_price']) {
     $display_price =  '<s>' . $currencies->display_price($xsell['products_price'], tep_get_tax_rate($xsell['products_tax_class_id'])) . '</s><br>';
     $display_price .= '<span class="productSpecialPrice">' . $currencies->display_price($xsell['specials_new_products_price'], tep_get_tax_rate($xsell['products_tax_class_id'])) . '</span>';
   } else {
     $display_price =  $currencies->display_price($xsell['products_price'], tep_get_tax_rate($xsell['products_tax_class_id']));
   }
   
      $qview_desc=tep_get_products_qview_desc($xsell['products_id']);
      $buttons = '<br><div class="xSellprodListing_masterDiv" style="position:relative; z-index:1">
<div class="xSellprodListing_mainbg" style="position:absolute; z-index:1;"><div class="xSellprodListing_img" style="position:absolute; z-index:2;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], XSELL_IMAGE_WIDTH, XSELL_IMAGE_HEIGHT) . '</a></div>
<div class="xSellprodListing_title" style="position:absolute; z-index:3;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '" class="xSellprodListing_title">' . $xsell['products_name'] . '</a></div>
' . ((is_bs_icon($xsell['products_id'])) ? '
<div class="xSellprodListing_bestseller_icon" style="position:absolute; z-index:21;">'.tep_image(DIR_WS_IMAGES.'items/bestseller.gif','','','','class="transpng"').'</div>
' : '') . ' ' . ((is_free_shipping($xsell['products_id'])) ? '
<div class="xSellprodListing_free_shipping_icon" style="position:absolute; z-index:22;"></div>
' : '') . ''.(!isset($qview_desc)?'':'
<div class="xSellprodListing_qview_icon" style="position:absolute; z-index:20;"><img src="/layout/img/quickview.gif" border="0" alt="" onMouseover="'.htmlspecialchars('ShowQView('.tep_js_quote($xsell['products_name']).",".tep_js_quote($xsell['products_info_alt'] != '' ? $xsell['products_info_alt'] : $xsell['products_info']).",".tep_js_quote($qview_desc).",".tep_js_quote($display_price).",".tep_js_quote(tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], FEATURED_POPUP_IMAGE_WIDTH, FEATURED_POPUP_IMAGE_HEIGHT)).")").'" onMouseout="hideddrivetip()"></div>
').'
<div class="xSellprodListing_desc" style="position:absolute; z-index:4;">' . ($xsell['products_info_alt'] != '' ? $xsell['products_info_alt'] : $xsell['products_info']) . '</div>
<div class="xSellprodListing_more" style="position:absolute; z-index:6;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '" class="morelink">More Info</a></div>
<div class="xSellprodListing_price" style="position:absolute; z-index:6;">' . $display_price . '</div>

<div class="xSellprodListing_seperatorVert" style="position:absolute; z-index:5;"><b></b></div>
<div class="xSellprodListing_seperatorHorz" style="position:absolute; z-index:5;"><b></b></div>
<div class="xSellprodListing_priceseperator" style="position:absolute; z-index:6;"><b></b></div>
</div>
</div>';


       $info_box_contents[$row][$col] = array('align' => 'center',
                                              'params' => 'class="smallText" width="33%" valign="top"',
                                              'text' => $buttons);
                                              //'text' => '<div class="xSellprodListing_img" style="position:absolute; z-index:2;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], XSELL_IMAGE_WIDTH, XSELL_IMAGE_HEIGHT) . '</a></div><div class="xSellprodListing_title" style="position:absolute; z-index:8;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '" class="xSellprodListing_title">' . $xsell['products_name'] .'</a></div><div class="xSellprodListing_price" style="position:absolute; z-index:9;">' . $xsell_price. '</div><div class="xSellprodListing_AddtoCart" style="position:absolute; z-index:10;"><a href="' . tep_href_link(basename($PHP_SELF), 'action=buy_now&products_id=' . $xsell['products_id'], 'NONSSL') . '">' . tep_image_button('button_buy_now.gif', TEXT_BUY . $xsell['products_name'] . TEXT_NOW) .'</a></div><div class="xSellprodListing_more" style="position:absolute; z-index:6;" onclick="location.href=\'' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '\'">More Info</div>');
                                              
       $col ++;
       if ($col >= XSELL_DISPLAY_COLS) {
         $col = 0;
         $row ++;
       }
     }
     new contentBox($info_box_contents);
?>





<?php
   }
 }
?>