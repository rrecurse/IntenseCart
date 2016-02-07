<?php

class blk_product extends IXblock {

//  function jsObjectName() {
//    return 'XSell_'.$this->makeID();
//  }

  function render($body) {
    global $currencies;
    $xsell=&$this->context['datarow'];
    $xsell['specials_new_products_price'] = tep_get_products_special_price($xsell['products_id']);
//    echo '--'.$xsell['products_id'].' '.$xsell['specials_new_products_price'].'--';

    if ($xsell['specials_new_products_price']) {
      $display_price =  '<s>' . $currencies->display_price($xsell['products_price'], tep_get_tax_rate($xsell['products_tax_class_id'])) . '</s><br>';
      $display_price .= '<span class="productSpecialPrice">' . $currencies->display_price($xsell['specials_new_products_price'], tep_get_tax_rate($xsell['products_tax_class_id'])) . '</span>';
    } else {
      $display_price =  $currencies->display_price($xsell['products_price'], tep_get_tax_rate($xsell['products_tax_class_id']));
    }
    $qview_desc=tep_get_products_qview_desc($xsell['products_id']);
?><table cellpadding="0" cellspacing="0" border="0" class="xSellprodListing_table"><tr><td valign="top">
<div class="xSellprodListing_masterDiv" style="position:relative; overflow:hidden; z-index:1">
<div class="xSellprodListing_mainbg" style="position:absolute; z-index:1;"></div>
<div class="xSellprodListing_img" style="position:absolute; z-index:2;"><a href="<?=tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], XSELL_IMAGE_WIDTH, XSELL_IMAGE_HEIGHT)?></a></div>
<div class="xSellprodListing_title" style="position:absolute; z-index:3;"><a href="<?=tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id'])?>" class="xSellprodListing_title"><?=$xsell['products_name']?></a></div>
<?php if (is_bs_icon($xsell['products_id'])) { ?>
<div class="xSellprodListing_bestseller_icon" style="position:absolute; z-index:21;"><?=tep_image(DIR_WS_IMAGES.'items/bestseller.gif','','','','class="transpng"')?></div>
<?php }
if (is_free_shipping($xsell['products_id'])) { ?>
<div class="xSellprodListing_free_shipping_icon" style="position:absolute; z-index:22;"></div>
<?php }
if (isset($qview_desc)) { ?>
<div class="xSellprodListing_qview_icon" style="position:absolute; z-index:20;"><img src="/layout/img/quickview.gif" border="0" alt="" onMouseover="<?=htmlspecialchars('ShowQView('.tep_js_quote($xsell['products_name']).",".tep_js_quote($xsell['products_info_alt'] != '' ? $xsell['products_info_alt'] : $xsell['products_info']).",".tep_js_quote($qview_desc).",".tep_js_quote($display_price).",".tep_js_quote(tep_image(DIR_WS_IMAGES . $xsell['products_image'], $xsell['products_name'], FEATURED_POPUP_IMAGE_WIDTH, FEATURED_POPUP_IMAGE_HEIGHT)).")")?>" onMouseout="hideddrivetip()"></div>
<?php } ?>
<div class="xSellprodListing_desc" style="position:absolute; z-index:4;"><?=($xsell['products_info_alt'] != '' ? $xsell['products_info_alt'] : $xsell['products_info'])?></div>
			
<div class="xSellprodListing_more" style="position:absolute; z-index:6;"><a href="<?=tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $xsell['products_id'])?>" class="morelink">More Info</a></div>

<div class="xSellprodListing_price" style="position:absolute; z-index:6;"><?=$display_price?></div>

<div class="xSellprodListing_seperatorVert" style="position:absolute; z-index:5;"><b></b></div>
<div class="xSellprodListing_seperatorHorz" style="position:absolute; z-index:5;"><b></b></div>
<div class="xSellprodListing_priceseperator" style="position:absolute; z-index:6;"><b></b></div>
<div class="xSellprodListing_AddtoCart" style="position:absolute; z-index:9;" onclick="addToCart({quantity:1,products_id:'<?=$xsell['products_id']?>'});"><b></b></div>
</div>

<?php
  }
  
  function requireContext() {
    return Array();
  }
  
  function getNumSlots() {
    return 4;
  }
}
?>