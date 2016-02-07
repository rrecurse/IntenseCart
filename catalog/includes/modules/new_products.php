<?php
/*
  $Id: new_products.php,v 1.34 2003/06/09 22:49:58 hpdl Exp $

  
  

  

  
*/
?>
<!-- new_products //-->
<?php
  $info_box_contents = array();
  $info_box_contents[] = array('text' => sprintf(TABLE_HEADING_NEW_PRODUCTS, strftime('%B')));

  new contentBoxHeading($info_box_contents);

  if ( (!isset($new_products_category_id)) || ($new_products_category_id == '0') ) {
    $new_products_query = tep_db_query("select distinct p.products_id, pd.products_info, p.products_image, p.products_tax_class_id, if(s.status, s.specials_new_products_price, p.products_price) as products_price from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on (p.products_id = pd.products_id) left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where products_status = '1' and pd.language_id = '" . $languages_id . "' order by p.products_date_added desc limit " . MAX_DISPLAY_NEW_PRODUCTS);
  } else {
  
    $new_products_query = tep_db_query("select distinct p.products_id, pd.products_info, p.products_image, p.products_tax_class_id, if(s.status, s.specials_new_products_price, p.products_price) as products_price from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on (p.products_id = pd.products_id) left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c where p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and c.parent_id = '" . (int)$new_products_category_id . "' and p.products_status = '1' and pd.language_id = '" . $languages_id . "' order by p.products_date_added desc limit " . MAX_DISPLAY_NEW_PRODUCTS);
  }

  $row = 0;
  $col = 0;
  $info_box_contents = array();
  while ($new_products = tep_db_fetch_array($new_products_query)) {
    $new_products['products_name'] = tep_get_products_name($new_products['products_id']);
    $new_products['products_info'] = tep_get_products_info($new_products['products_id']);
    
    $display_price = '';
    if ($new_products['specstat']) {
      $display_price = '<span>Was: <s>' . $currencies->display_price($new_products['products_price'], tep_get_tax_rate($new_products['products_tax_class_id'])) . '</s></span><br>';
      $display_price .= 'Now only: <span>' . $currencies->display_price($new_products['specials_new_products_price'], tep_get_tax_rate($new_products['products_tax_class_id'])) . '</span>';
    } else {
      $display_price = $currencies->display_price($new_products['products_price'], tep_get_tax_rate($new_products['products_tax_class_id']));
    }
    
    $buttons = '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $new_products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $new_products['products_image'], $new_products['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<b></a><br><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $new_products['products_id']) . '">' . $new_products['products_name'] . '</b></a><br>' . $new_products['products_info'] . '<br>' . "\r\n";
    $buttons .= '<table border=0 width="100%" cellspacing=0 cellpadding=0>' . "\r\n";
    $buttons .= '<tr>' . "\r\n";
    $buttons .= '<td colspan=3 style="font-size: 11px; font-family: Arial; color: #CA1F00; font-weight:bold; padding:6px;">' . $listing['products_info'] . '</td>' . "\r\n";
    $buttons .= '</tr>' . "\r\n";
    $buttons .= '<tr>' . "\r\n";   
    $buttons .= '<td rowspan="2" style="width:15px;"> </td>' . "\r\n"; 
    $buttons .= '<td style="width:85px; height:5px;"></td>' . "\r\n";    
    $buttons .= '<td rowspan="2" align="right" valign="top" style="width:90px; height:45px; padding-right:9px;padding-bottom:9px;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $new_products['products_id']) . '">' . tep_image_button('moreinfo.gif', 'Click here for more info on ' . $new_products['products_name']) .  '</a> <a href="' . tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action')) . 'action=buy_now&products_id=' . $new_products['products_id']) . '">' . tep_image_button('addtocart.gif', IMAGE_BUTTON_BUY_NOW) . '</a></td>' . "\r\n";
    $buttons .= '</tr>' . "\r\n";
    $buttons .= '<tr>' . "\r\n";   
    $buttons .= '<td valign="top" style="background:url(../images/tag-sm.gif); background-repeat: no-repeat; height: 30px; padding-left:20px; padding-top:6px;"><span style="font-size:15px; color: #FFFFFF; font-weight: bold;">' . $display_price . '</span></td>' . "\r\n";
    $buttons .= '</tr>' . "\r\n";
    $buttons .= '</table>' . "\r\n";

    $info_box_contents[$row][$col] = array('align' => 'center',
                                           'params' => 'class="smallText" width="33%" valign="top"',
                                           'text' => $buttons);
                                           //'text' => '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $new_products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $new_products['products_image'], $new_products['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<b></a><br><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $new_products['products_id']) . '">' . $new_products['products_name'] . '</b></a><br>' . $new_products['products_info'] . '<br>' . $currencies->display_price($new_products['products_price'], tep_get_tax_rate($new_products['products_tax_class_id'])));
                                           
                                           

    $col ++;
    if ($col > 2) {
      $col = 0;
      $row ++;
    }
  }

  new contentBox($info_box_contents);
?>
<!-- new_products_eof //-->
