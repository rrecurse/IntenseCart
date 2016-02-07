<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	// # product listing with attributes
	$list_box_contents = array();
	$list_box_contents[] = array('params' => 'class="prodListing-heading"');
	$cur_row = sizeof($list_box_contents) - 1;

	for($col=0, $n=sizeof($column_list); $col<$n; $col++) {
		switch ($column_list[$col]) {
			case 'PRODUCT_LIST_MULTIPLE':
			$add_multiple = "1";
			echo '<form name="buy_now_" method="post" action="' . tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action')) . 'action=add_multiple', 'NONSSL') . '">';
		break;
		}
	}
	// # END  product listing with attributes
 
	$buynow_text .= IMAGE_BUTTON_BUY_NOW;
	$moreinfo_text .= 'More info';

	if (!defined('PRODUCT_SORT_COLUMNS')) define('PRODUCT_SORT_COLUMNS','PRODUCT_LIST_NAME:a:Name,PRODUCT_LIST_PRICE:d:Price');


	// # Enhanced Search 
	if (isset($pw_mispell)){ 

	echo '<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr><td valign="top">'. $pw_string.'</td></tr>
		  </table>';
	}
	// # END added search enhancements mod

	$listing_split = new splitPageResults($listing_sql, MAX_DISPLAY_PRODUCTS, 'p.products_id');
	// # END - Enhanced Search


if (($listing_split->number_of_rows > 0) && ( (PREV_NEXT_BAR_LOCATION == '1') || (PREV_NEXT_BAR_LOCATION == '3'))) {
?>
<div class="prodListing_topCountdiv"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td class="prodListing_topPageCount" valign="top"><?php echo $listing_split->display_count(TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
    <td class="prodListing_topPage" valign="top"><?php if ($listing_split->current_page_number) echo TEXT_RESULT_PAGE . ' ' . $listing_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?> <span style="prodListing_topPageCount_showall">&nbsp;<a href="<?php echo tep_href_link(basename($PHP_SELF),tep_get_all_get_params(array('page', 'info', 'x', 'y')).($listing_split->current_page_number?'page=all':''))?>" class="prodListing_topPageCount_showall"><?=$listing_split->current_page_number?'View All':'Show Pages'?></a></span></td>
  </tr></table></div>

<div id="browsecontrol-prod_listingID" class="browsecontrol-prod_listingClass">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td class="blackTextBoldNormal">Sort by: 
<?php 
	// # Sort
// Some vars are initialized in index.php

//      $sort_id=preg_replace('/\D/','',$HTTP_GET_VARS["sort"])+0;
      $sort_sep=0;
      foreach(preg_split('/,/',PRODUCT_SORT_COLUMNS) AS $sort_col_dir) {
        list($sort_col_key,$sort_col_dir,$sort_col_name)=preg_split('/:/',$sort_col_dir);
        $sort_col_num=1;
        foreach($column_list AS $sort_col_lkey) {
          if ($sort_col_key==$sort_col_lkey) {
            if ($sort_sep) echo ' | ';
            $sort_sep=1;
            if ($sort_col_num==$sort_col) {
            	$sort_col_dir=preg_match('/^d/',$sort_order)?'a':'d';
            }
      ?>
       &nbsp;<a href="<?php echo tep_href_link(basename($PHP_SELF),tep_get_all_get_params(array('info', 'x', 'y','sort')).(($sort_col_key==$sort_default_key && $sort_col_dir==$sort_default_dir)?'':'sort='.$sort_col_num.$sort_col_dir));?>" class="blackText"><?=$sort_col_name?></a> 
      <?
            break;
          }
          $sort_col_num++;
        }
      }

      ?>
 &nbsp;| &nbsp;<a href="<?php echo tep_href_link(basename($PHP_SELF),tep_get_all_get_params(array('page', 'info', 'x', 'y')).($listing_split->current_page_number?'page=all':''))?>" class="blackText"><?=$listing_split->current_page_number?'Show All':'Show Pages'?></a> 
     </td>
     </tr>

</table>
</div>
<div class="prodListing_group" id="prodListing_group">
<?php
}

$list_box_contents = array();
 
  if ($listing_split->number_of_rows > 0) {
    $row = 0;
    $column = 0;
    $listing_query = tep_db_query($listing_split->sql_query);
    $product_contents = array();
    while ($listing = tep_db_fetch_array($listing_query)) {
      $listing['specstat']=$listing['specials_new_products_price'];
      if ($listing['specstat']) {
        $display_price = '<span class="specialPrice_was"><s>' . $currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</s></span><br>';              
        $display_price .= '<span class="specialPrice_now">Now:&nbsp;</span><span class="specialPrice_price">' . $currencies->display_price($listing['specials_new_products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</span>';
      } else {
        $display_price = $currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id']));
      }

      $qview_desc=tep_get_products_qview_desc($listing['products_id']);
      $lc_text = '<table width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td valign="top">
							<div class="prodListing_masterDiv" style="position:relative; z-index:1">
								<div class="prodListing_mainbg" style="position:absolute; z-index:1;"></div>
								<div class="prodListing_img" style="position:absolute; z-index:2;">
									<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $listing['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $listing['products_image'], $listing['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT,'class="prod_image"') . '</a>
								</div>
								<div class="prodListing_title" style="position:absolute; z-index:3;">
									<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $listing['products_id']) . '" class="prodListing_title">' . $listing['products_name'] . '</a>
								</div>
'. (is_bs_icon($listing['products_id']) ? '<div class="prodListing_bestseller_icon" style="position:absolute; z-index:21;"></div>' : '') . ' ' . (is_free_shipping($listing['products_id']) ? '<div class="prodListing_free_shipping_icon" style="position:absolute; z-index:22;"></div>' : '') . '
'.(!isset($qview_desc) ? '' : '<div class="prodListing_qview_icon" style="position:absolute; z-index:20;"><img src="/layout/img/quickview.gif" border="0" alt="" onMouseover="'.htmlspecialchars('ShowQView('.tep_js_quote($listing['products_name']).",".tep_js_quote($listing['products_info_alt'] != '' ? $listing['products_info_alt'] : $listing['products_info']).",".tep_js_quote($qview_desc).",".tep_js_quote($display_price).",".tep_js_quote(tep_image(DIR_WS_IMAGES . $listing['products_image'], $listing['products_name'], FEATURED_POPUP_IMAGE_WIDTH, FEATURED_POPUP_IMAGE_HEIGHT)).")").'" onMouseout="hideddrivetip()"></div>').'
<div class="prodListing_desc" style="position:absolute; overflow:hidden; z-index:4;">' . ($listing['products_info_alt'] != '' ? $listing['products_info_alt'] : $listing['products_info']) . '</div><div class="prodListing_buynow" style="position:absolute; z-index:6;"><a href="' . tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action')) . 'action=buy_now&products_id=' . $listing['products_id']) . '"if (!window.addToCart) return true; onClick=" addToCart({quantity:1,products_id:'. $listing['products_id'] . '}); return false;">' . $buynow_text . '</a></div><div class="prodListing_more" style="position:absolute; z-index:6;"><b><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $listing['products_id']) . '" class="morelink">' . $moreinfo_text . '</a></b></div>
<div class="prodListing_price" style="position:absolute; z-index:6;">' . $display_price . '</div>

<div class="prodListing_seperatorVert" style="position:absolute; z-index:5;"><b></b></div>
<div class="prodListing_seperatorHorz" style="position:absolute; z-index:5;"><b></b></div>
<div class="prodListing_priceseperator" style="position:absolute; z-index:6;"><b></b></div>
</div></td></tr></table>';

      $list_box_contents[$row][$column] = array('align' => 'center',
                                                'valign' => 'center',
                                                'params' => 'class="prodListing-data"',
                                                'text'  => $lc_text);
                                                
      $column++;
      if ($column >= PRODUCT_LISTING_COLS) {
        $row++;
        $column = 0;
      }
    }

    new productListingBox($list_box_contents);
  } else {
    $list_box_contents = array();

    $list_box_contents[0] = array('params' => 'class="prodListing-odd"');
    $list_box_contents[0][] = array('params' => 'class="prodListing-data"',
                                   'text' => TEXT_NO_PRODUCTS);

    new productListingBox($list_box_contents);
  }

if ( ($listing_split->number_of_rows > 0) && ((PREV_NEXT_BAR_LOCATION == '2') || (PREV_NEXT_BAR_LOCATION == '3')) ) {
?>
</div>
<div class="prodListing_bottomCountdiv"><table style="width:100%;" cellspacing="0" cellpadding="0">
    <tr>
      <td class="prodListing_bottomPageCount"><?php echo $listing_split->display_count(TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
      <td class="prodListing_bottomPage"><? if ($listing_split->current_page_number) echo TEXT_RESULT_PAGE . ' ' . $listing_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?></td>
    </tr>



    <?php if ($add_multiple == "1"){
?>
    <tr>
      <td align="left"><!--a href="<//?php echo tep_href_link(FILENAME_CHECKOUT_PAYMENT, '/includes/modules/', 'SSL'); ?>"><//?php echo tep_image_button('button_checkout.gif', IMAGE_BUTTON_CHECKOUT); ?></a-->
      </td>
      <td align="right"><?php echo tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART); ?></td>
    </tr>
    <?php } ?>
</table></div>
<?php
}
?>
