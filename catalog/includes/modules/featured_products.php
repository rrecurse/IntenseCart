<script language="javascript" src="/js/qview.js"></script>
<?php
  $info_box_contents = array();
  //$info_box_contents[] = array('text' => 'Featured Products');
 
$buynow_text  .= 'add to cart';
$moreinfo_text  .= 'More info';

  new contentBoxHeading($info_box_contents);

  $featured_products_category_id = $current_category_id+0;

  $featured_cats=Array($featured_products_category_id=>$featured_products_category_id);
  $subcats=tep_get_subcats_info($featured_products_category_id);
  foreach ($subcats AS $subcat) $featured_cats[$subcat['id']]=$subcat['id'];

  $cat_name_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . $featured_products_category_id . "' limit 1");
  $cat_name_fetch = tep_db_fetch_array($cat_name_query);
  $cat_name = $cat_name_fetch['categories_name'];
  $info_box_contents = array();
  
  $order='';  
  if (FEATURED_PRODUCTS_SORT=='true') {
    $order="f.sort_order";
  } else {
    list($usec, $sec) = explode(' ', microtime());
    srand( (float) $sec + ((float) $usec * 100000) );
    $mtm= rand();
    $order="rand($mtm) DESC";
  }

//$order.=' '.join(',',$featured_cats);

//  if ( (!isset($featured_products_category_id)) || ($featured_products_category_id == '0') ) {
    $info_box_contents[] = array('align' => 'left', 'text' => '<a class="headerNavigation" href="' . tep_href_link(FILENAME_FEATURED_PRODUCTS) . '">' . TABLE_HEADING_FEATURED_PRODUCTS . '</a>');

    $featured_products_query = tep_db_query("SELECT p.products_id, p.products_image, p.products_tax_class_id, s.status as specstat, s.specials_new_products_price, p.products_price, pd.products_name, pd.products_info, pd.products_info_alt, pd.products_description from " . TABLE_PRODUCTS . " p left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id left join " . TABLE_FEATURED . " f on p.products_id = f.products_id LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id=pd.products_id AND pd.language_id='$languages_id' LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON p.products_id=p2c.products_id WHERE p.products_status = '1' AND p.products_price > 0 AND f.status = '1' AND p2c.categories_id IN ('".join("','",$featured_cats)."') GROUP BY p.products_id ORDER BY $order limit " . MAX_DISPLAY_FEATURED_PRODUCTS);
/*  } else {
    $info_box_contents[] = array('align' => 'left', 'text' => sprintf(TABLE_HEADING_FEATURED_PRODUCTS_CATEGORY, $cat_name));
    $subcategories_array = array();
    tep_get_subcategories($subcategories_array, $featured_products_category_id);
    $featured_products_category_id_list = tep_array_values_to_string($subcategories_array);
    if ($featured_products_category_id_list == '') {
      $featured_products_category_id_list .= $featured_products_category_id;
    } else {
      $featured_products_category_id_list .= ',' . $featured_products_category_id;
    }
    $featured_products_query = tep_db_query("select distinct p.products_id, p.products_image, p.products_tax_class_id, s.status as specstat, s.specials_new_products_price, p.products_price, pd.products_name, pd.products_info, pd.products_info_alt, pd.products_description from " . TABLE_PRODUCTS . " p left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c left join " . TABLE_FEATURED . " f on p.products_id = f.products_id LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id=pd.products_id AND pd.language_id='$languages_id' where p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and c.parent_id in (" . $featured_products_category_id_list . ") and p.products_status = '1'  AND p.products_price > 0 AND f.status = '1' order by $order limit " . MAX_DISPLAY_FEATURED_PRODUCTS);
  }
*/
  $row = 0;
  $col = 0;
  $info_box_contents = array();
  $featured_ids=Array();
  while ($featured_products = tep_db_fetch_array($featured_products_query)) {
    if ($featured_ids[$featured_products['products_id']]) continue;
    $featured_ids[$featured_products['products_id']]=1;
//    $featured_products['products_name'] = tep_get_products_name($featured_products['products_id']);
//    $featured_products['products_info'] = tep_get_products_info($featured_products['products_id']);
//    $featured_products['products_info_alt'] = tep_get_products_info_alt($featured_products['products_id']);
    $display_price = '';
    if ($featured_products['specstat']) {
      $display_price = '<span class="specialPrice_was"><s>' . $currencies->display_price($featured_products['products_price'], tep_get_tax_rate($featured_products['products_tax_class_id'])) . '</s></span><br>';
      $display_price .= '<span class="specialPrice_now">Now:&nbsp;</span> <span class="specialPrice_price">' . $currencies->display_price($featured_products['specials_new_products_price'], tep_get_tax_rate($featured_products['products_tax_class_id'])) . '</span>';
    } else {
      $display_price = $currencies->display_price($featured_products['products_price'], tep_get_tax_rate($featured_products['products_tax_class_id']));
    }
    
    $attr_query=tep_db_query("SELECT pa.options_id,po.products_options_name,pov.products_options_values_name FROM ".TABLE_PRODUCTS_ATTRIBUTES." pa LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON pa.options_id=po.products_options_id AND po.language_id='$languages_id' LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pa.options_values_id=pov.products_options_values_id AND pov.language_id='$languages_id' WHERE pa.products_id='".$featured_products['products_id']."' ORDER BY pa.options_id,pa.options_values_id");
    $op_id=0;
    $ul_open=0;
    $attrs="<div id=\"attrsbox\" width=100%>";
    while ($attr_row=tep_db_fetch_array($attr_query)) {
      if ($attr_row['options_id']!=$op_id) {
        $op_id=$attr_row['options_id'];
	if ($ul_open) $attrs.="</ul>";
	$ul_open=0;
      }
      if (!$ul_open) $attrs.='<slide target=attrsbox over=1 delay=1000>'.tep_output_string($attr_row['products_options_name'])."</slide><ul>";
      $ul_open=1;
      $attrs.='<slide target=attrsbox delay=100><li>'.tep_output_string($attr_row['products_options_values_name'])."</li></slide>";
    }
    if ($ul_open) $attrs.="</ul>";
//    $attrs.="</ul>";

    $qview_desc=tep_get_products_qview_desc($featured_products['products_id']);

    $buttons  = '<div class="featured_masterDiv" style="position:relative; overflow:hidden; z-index:1">
<div class="featured_mainbg" style="position:absolute; z-index:1;"></div>
<div class="featured_img" style="position:absolute; z-index:2;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $featured_products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $featured_products['products_image'], $featured_products['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a></div>
<div class="featured_title" style="position:absolute; z-index:3;"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $featured_products['products_id']) . '" class="featured_title">' . $featured_products['products_name'] . '</a></div>
' . ((is_bs_icon($featured_products['products_id'])) ? '<div class="featured_bestseller_icon" style="position:absolute; z-index:21;">'.tep_image(DIR_WS_IMAGES.'items/bestseller.gif','','','','class="transpng"').'</div>' : '') . ' ' . ((is_free_shipping($featured_products['products_id'])) ? '<div class="featured_free_shipping_icon" style="position:absolute; z-index:22;"></div>' : '') . '
'.(!isset($qview_desc)?'':
'<div class="featured_qview_icon" style="position:absolute; z-index:20;"><img src="/layout/img/quickview.gif" border="0" alt="" onMouseover="'.htmlspecialchars('ShowQView('.tep_js_quote($featured_products['products_name']).",".tep_js_quote($featured_products['products_info_alt'] != '' ? $featured_products['products_info_alt'] : $featured_products['products_info']).",".tep_js_quote($qview_desc).",".tep_js_quote($display_price).",".tep_js_quote(tep_image(DIR_WS_IMAGES . $featured_products['products_image'], $featured_products['products_name'], FEATURED_POPUP_IMAGE_WIDTH, FEATURED_POPUP_IMAGE_HEIGHT)).")").'" onMouseout="hideddrivetip()"></div>').'
<div class="featured_desc" style="position:absolute; z-index:4;">' . ($featured_products['products_info_alt'] != '' ? $featured_products['products_info_alt'] : $featured_products['products_info']) . '<br>' . ($listing['products_info_alt'] != '' ? $listing['products_info_alt'] : $listing['products_info']) . '</div>
<div class="featured_buynow" style="position:absolute; z-index:6;"><a href="' . tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action')) . 'action=buy_now&products_id=' . $featured_products['products_id']) . '">' . $buynow_text . '</a></div>
<div class="featured_more" style="position:absolute; z-index:6;"><b><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $featured_products['products_id']) . '" class="morelink"> ' . $moreinfo_text . '</a></b></div>

<div class="featured_price" style="position:absolute; z-index:6;">' . $display_price . '</div>

<div class="featured_seperatorVert" style="position:absolute; z-index:5;"></div>
<div class="featured_seperatorHorz" style="position:absolute; z-index:5;"><b></b></div>
<div class="featured_priceseperator" style="position:absolute; z-index:6;"><b></b></div>

</div>
' . "\r\n";
    $info_box_contents[$row][$col] = array('align' => 'center',
                                           'params' => 'class="smallText" width="33%" valign="top"',
                                           'text' => $buttons);
                                           //'text' => '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $featured_products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . $featured_products['products_image'], $featured_products['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<b></a><br><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $featured_products['products_id']) . '">' . $featured_products['products_name'] . '</b></a><br>' . $featured_products['products_info'] . '<br>' . $currencies->display_price($featured_products['products_price'], tep_get_tax_rate($featured_products['products_tax_class_id'])));
                                           
                                           

    $col ++;
    if ($col >= FEATURED_PRODUCTS_COLUMNS) {
      $col = 0;
      $row ++;
    }
  }

  new contentBox($info_box_contents);
?>
<!-- new_products_eof //-->
