<?php

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_SPECIALS);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_SPECIALS));

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
</head>
<body>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table>
  <tr>
    <td><table>
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td valign="top">

<?php

	$specials_query_raw = "SELECT p.products_id, 
								  pd.products_name, 
								  pd.products_info, 
								  pd.products_info_alt, 
								  p.products_price, 
								  p.products_tax_class_id, 
								  p.products_image, 
								  s.specials_new_products_price 
						   FROM " . TABLE_PRODUCTS . " p 
						   LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.products_id AND pd.language_id = '" . (int)$languages_id . "')
						   LEFT JOIN " . TABLE_SPECIALS . " s ON s.products_id = p.products_id
						   WHERE p.products_price > 0
						   AND p.products_status = '1'
						   AND s.status = '1' 
						   ORDER BY s.specials_date_added DESC";

  $specials_split = new splitPageResults($specials_query_raw, MAX_DISPLAY_SPECIAL_PRODUCTS);

  if (($specials_split->number_of_rows > 0) && ((PREV_NEXT_BAR_LOCATION == '1') || (PREV_NEXT_BAR_LOCATION == '3'))) {

?><div class="prodListing_topCountdiv"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="prodListing_topPageCount" valign="top"><?php echo $specials_split->display_count(TEXT_DISPLAY_NUMBER_OF_SPECIALS); ?></td>
            <td class="prodListing_topPage" valign="top"><?php echo TEXT_RESULT_PAGE . ' ' . $specials_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?></td>
          </tr>
        </table></div></td>
      </tr>

<?php
  }
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
<?php
    $row = 0;
    $specials_query = tep_db_query($specials_split->sql_query);


	if(tep_db_num_rows($specials_query) > 0) { 
	    while ($specials = tep_db_fetch_array($specials_query)) {
			$row++;
      
			$display_price = '<span class="specialPrice_was"><s>' . $currencies->display_price($specials['products_price'], tep_get_tax_rate($specials['products_tax_class_id'])) . '</s></span><br>';              
    	    $display_price .= '<span class="specialPrice_now">Now:&nbsp;</span> <span class="specialPrice_price">' . $currencies->display_price($specials['specials_new_products_price'], tep_get_tax_rate($specials['products_tax_class_id'])) . '</span>';

			$qview_desc=tep_get_products_qview_desc($specials['products_id']);

			echo '<td>
					<div class="prodListing_masterDiv" style="position:relative; z-index:1">
						<table width="100%" cellpadding="0" cellspacing="0">
							<tr>
								<td valign="top">	
									<div class="prodListing_mainbg" style="position:relative; z-index:1;"></div>
	
									<div class="prodListing_img" style="position:absolute; z-index:2;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $specials['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $specials['products_image'], $specials['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT,'class="prod_image"') . '</a></div>

									<div class="prodListing_title" style="position:absolute; z-index:3;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $specials['products_id']) . '" class="prodListing_title">' . $specials['products_name'] . '</a></div>' . ((is_bs_icon($specials['products_id'])) ? '<div class="prodListing_bestseller_icon" style="position:absolute; z-index:21;"></div>' : '') . ' ' . ((is_free_shipping($specials['products_id'])) ? '<div class="prodListing_free_shipping_icon" style="position:absolute; z-index:22;"></div>' : '') . '
'.(!isset($qview_desc)?'':
'<div class="prodListing_qview_icon" style="position:absolute; z-index:20;"><img src="/layout/img/quickview.gif" border="0" alt="" onMouseover="'.htmlspecialchars('ShowQView('.tep_js_quote($specials['products_name']).",".tep_js_quote($specials['products_info_alt'] != '' ? $specials['products_info_alt'] : $specials['products_info']).",".tep_js_quote($qview_desc).",".tep_js_quote($display_price).",".tep_js_quote(tep_image(DIR_WS_IMAGES . $specials['products_image'], $specials['products_name'], FEATURED_POPUP_IMAGE_WIDTH, FEATURED_POPUP_IMAGE_HEIGHT)).")").'" onMouseout="hideddrivetip()"></div>').'

									<div class="prodListing_desc" style="position:absolute; overflow:hidden; z-index:4;">' . ($specials['products_info_alt'] != '' ? $specials['products_info_alt'] : $specials['products_info']) . '</div>
			
									<div class="prodListing_more" style="position:absolute; z-index:6;"><b><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $specials['products_id']) . '" class="morelink">More info</a></b></div>
									<div class="prodListing_buynow" style="position:absolute; z-index:6; padding:2px 0 0 0;"><a href="javascript:addToCart({quantity:1,products_id:'. $specials['products_id'] . '});">'. IMAGE_BUTTON_BUY_NOW .'</a></div>
									<div class="prodListing_price" style="position:absolute; z-index:6;">' . $display_price . '</div>

									<div class="prodListing_seperatorVert" style="position:absolute; z-index:5;"><b></b></div>
									<div class="prodListing_seperatorHorz" style="position:absolute; z-index:5;"><b></b></div>
									<div class="prodListing_priceseperator" style="position:absolute; z-index:6;"><b></b></div>
								</td>
							</tr>
						</table>
					</div>
				</td>'."\n";

			if ((($row /  PRODUCT_LISTING_COLS) == floor($row / PRODUCT_LISTING_COLS))) {

				echo '</tr></table>
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
			            <tr></tr>
            			<tr>';
			}
		} // # end while

	} else { // # no results found

		echo '<tr><td>There are no current specials. Check back soon or sign-up for our mailing list to keep you up-to-date on all of our current specials.</td></tr>';
	}
?>
            </tr>
          </table></td>
      </tr>
<?php
  if (($specials_split->number_of_rows > 0) && ((PREV_NEXT_BAR_LOCATION == '2') || (PREV_NEXT_BAR_LOCATION == '3'))) {
?>
      <tr>
        <td><br><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="smallText"><?php echo $specials_split->display_count(TEXT_DISPLAY_NUMBER_OF_SPECIALS); ?></td>
            <td align="right" class="smallText"><?php echo TEXT_RESULT_PAGE . ' ' . $specials_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))); ?></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
    </table></td>

    <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
