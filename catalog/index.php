<?php

// ############################################
/*  Copyright (c) 2006 - 2016 IntenseCart eCommerce  */
// ############################################

 require_once('includes/application_top.php');

	$products_id = (int)$_GET['products_id'];

  if (!empty($products_id)){

    require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_INFO);

	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');


	$product_check_query = tep_db_query("SELECT COUNT(0) AS total
										 FROM products_groups pg 
										 LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = pg.products_id 
										 WHERE pg.products_id = '".$products_id."' 
										 AND pg.customers_group_id = '". $customer_group_id ."'
										 AND p.products_status = '1' 
										 AND pg.customers_group_price > 0
										 GROUP BY pg.products_id
										");

	$product_check = tep_db_fetch_array($product_check_query);

	tep_db_free_result($product_check_query);

	if($product_check['total'] < 1) { 
		tep_redirect(tep_href_link(FILENAME_DEFAULT, ''));
		exit();
	}
   
  } else {

// # SEF END

// # the following cPath references come from application_top.php
	$category_depth = 'top';
    
	if (isset($cPath) && tep_not_null($cPath)) {
		$category_parent_query = tep_db_query("SELECT COUNT(0) AS total FROM " . TABLE_CATEGORIES . " WHERE parent_id = '" . (int)$current_category_id . "'");

		$category_parent = tep_db_fetch_array($category_parent_query);

		if ($category_parent['total'] > 0) {

			$category_depth = 'nested'; // # navigate through the categories
			$real_page_name = FILENAME_DEFAULT;
			//$categories_products_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . (int)$current_category_id . "'");
			//$categories_products = tep_db_fetch_array($categories_products_query);
		} else {
			$category_depth = 'products'; // category has no products, but display the 'no products' message
			$real_page_name = FILENAME_PRODUCTS_ALL;
		}
	}

	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_DEFAULT);
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
</head>
<body> 

<?php require(DIR_WS_INCLUDES . 'header.php'); ?> 


<table border="0" width="100%" cellspacing="0" cellpadding="0"> 
  <tr> 
    <td valign="top"><table border="0" cellspacing="0" cellpadding="2"> 
       
        <?php require(DIR_WS_INCLUDES . 'column_left.php'); ?> 

      </table></td> 
    
<?php 
// # SEF BEGIN
if(!empty($products_id)){
?> 
    <td width="100%" valign="top"><?php echo tep_draw_form('cart_quantity', tep_href_link(FILENAME_PRODUCT_INFO, tep_get_all_get_params(array('action')) . 'action=add_product')); ?> 
      <table border="0" width="100%" cellspacing="0" cellpadding="0"> 
        <?php
  if ($product_check['total'] < 1) {
?> 
        <tr> 
          <td><?php new infoBox(array(array('text' => TEXT_PRODUCT_NOT_FOUND))); ?></td> 
        </tr> 
        <tr> 
          <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td> 
        </tr> 
        <tr> 
          <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox"> 
              <tr class="infoBoxContents"> 
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2"> 
                    <tr> 
                      <td width="10"></td> 
                      <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td> 
                      <td width="10"></td> 
                    </tr> 
                  </table></td> 
              </tr> 
            </table></td> 
        </tr> 
<?php

} else {

	$product_info_query = tep_db_query("SELECT p.products_id, 
											   pd.products_name, 
											   pd.products_description, 
											   p.products_model, 
											   p.products_quantity, 
											   p.products_image, 
											   pd.products_url, 
											   p.products_price, 
											   p.products_tax_class_id, 
											   p.products_date_added, 
											   p.products_date_available, 
											   p.manufacturers_id 
										FROM " . TABLE_PRODUCTS . " p
										LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
										WHERE p.products_status = '1'
										AND p.products_price > 0
										AND p.products_id = '".$products_id."'
										AND pd.language_id = '".(int)$languages_id."'
									   ");
    $product_info = tep_db_fetch_array($product_info_query);

    //tep_db_query("UPDATE " . TABLE_PRODUCTS_DESCRIPTION . " SET products_viewed = products_viewed+1 WHERE products_id = '" . $products_id . "' and language_id = '" . (int)$languages_id . "'");

    if ($new_price = tep_get_products_special_price($product_info['products_id'])) {
      $products_price = '<s>' . $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id'])) . '</s> <span class="productSpecialPrice">' . $currencies->display_price($new_price, tep_get_tax_rate($product_info['products_tax_class_id'])) . '</span>';
    } else {
      $products_price = $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
    }

    if (tep_not_null($product_info['products_model'])) {
      $products_name = $product_info['products_name'] . '<br><span class="smallText">[' . $product_info['products_model'] . ']</span>';
    } else {
      $products_name = $product_info['products_name'];
    }
?> 
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
        <tr> 
          <td class="main"> <?php
    if (tep_not_null($product_info['products_image'])) {
?> 
            <table border="0" cellspacing="0" cellpadding="2" align="right"> 
              <tr> 
                <td align="center" class="smallText"> 
<script type="text/javascript">
<!--
document.write('<?php echo '<a href="javascript:popupWindow(\\\'' . tep_href_link(FILENAME_POPUP_IMAGE, 'pID=' . $product_info['products_id']) . '\\\')">' . tep_image(DIR_WS_IMAGES . $product_info['products_image'], addslashes($product_info['products_name']), SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5" alt=""') . '<br>' . TEXT_CLICK_TO_ENLARGE . '</a>'; ?>');
//-->
</script> 
<noscript> 
<?php echo '<a href="' . tep_href_link(DIR_WS_IMAGES . $product_info['products_image']) . '" target="_blank">' . tep_image(DIR_WS_IMAGES . $product_info['products_image'], $product_info['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5" alt=""') . '<br>' . TEXT_CLICK_TO_ENLARGE . '</a>'; ?> 
</noscript> </td> 
              </tr> 
            </table> 
            <?php
    }
?> 
            <p><?php echo stripslashes($product_info['products_description']); ?></p> 
<?php
	
	$products_attributes_query = tep_db_query("SELECT COUNT(0) AS total 
											   FROM " . TABLE_PRODUCTS_OPTIONS . " popt
											   LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ON patrib.options_id = popt.products_options_id
											   WHERE patrib.products_id='".$products_id."' 
											   AND popt.language_id = '".(int)$languages_id."'
											  ");
    $products_attributes = tep_db_fetch_array($products_attributes_query);
    if ($products_attributes['total'] > 0) {
?> 
            <table border="0" cellspacing="0" cellpadding="2"> 
              <tr> 
                <td class="main" colspan="2"><?php echo TEXT_PRODUCT_OPTIONS; ?></td> 
              </tr> 

<?php
		
	$products_options_name_query = tep_db_query("SELECT DISTINCT popt.products_options_id, popt.products_options_name 
												 FROM " . TABLE_PRODUCTS_OPTIONS . " popt
												 LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." patrib ON patrib.options_id = popt.products_options_id
												 WHERE patrib.products_id = '".$products_id."'
												 AND popt.language_id = '".(int)$languages_id."'
												 ORDER BY popt.products_options_name
												");
	while ($products_options_name = tep_db_fetch_array($products_options_name_query)) {

		$products_options_array = array();

        $products_options_query = tep_db_query("select pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov where pa.products_id = '" . $products_id . "' and pa.options_id = '" . (int)$products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int)$languages_id . "'");
        while ($products_options = tep_db_fetch_array($products_options_query)) {
          $products_options_array[] = array('id' => $products_options['products_options_values_id'], 'text' => $products_options['products_options_values_name']);
          if ($products_options['options_values_price'] != '0') {
            $products_options_array[sizeof($products_options_array)-1]['text'] .= ' (' . $products_options['price_prefix'] . $currencies->display_price($products_options['options_values_price'], tep_get_tax_rate($product_info['products_tax_class_id'])) .') ';
          }
        }

        if (isset($cart->contents[$products_id]['attributes'][$products_options_name['products_options_id']])) {
          $selected_attribute = $cart->contents[$products_id]['attributes'][$products_options_name['products_options_id']];
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
?> </td> 
        </tr> 
        <tr> 
          <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td> 
        </tr> 
        <?php
    $reviews_query = tep_db_query("select count(*) as count from " . TABLE_REVIEWS . " where products_id = '" . $products_id . "'");
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

    if ($product_info['products_date_available'] > date('Y-m-d H:i:s', time())) {
	
		echo '<tr>
				<td align="center" class="smallText">'.sprintf(TEXT_DATE_AVAILABLE, tep_date_long($product_info['products_date_available'])) .'</td>
			 </tr>';

    } else {
	
		echo '<tr>
				<td align="center" class="smallText">'.sprintf(TEXT_DATE_ADDED, tep_date_long($product_info['products_date_added'])).'</td>
			</tr>';
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
                      <td class="main" align="right"><?php echo tep_draw_hidden_field('products_id', $product_info['products_id']) . tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART); ?></td> 
                      <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td> 
                    </tr> 
                  </table></td> 
              </tr> 
            </table></td> 
        </tr> 
        <tr> 
          <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td> 
        </tr> 
        <tr> 
          <td> <?php
 /*   if ((USE_CACHE == 'true') && empty($SID)) {
      echo tep_cache_also_purchased(3600);
    } else {
*/
      include(DIR_WS_MODULES . FILENAME_ALSO_PURCHASED_PRODUCTS);
  //}
  }
?> </td> 
        </tr> 
      </table> 
      </form></td> 
<?php

} elseif(isset($HTTP_GET_VARS['info_id'])) {

	if(!isset($HTTP_GET_VARS['info_id']) || !tep_not_null($HTTP_GET_VARS['info_id'])) {
		$title = 'Sorry. Page Not Found.';
		$breadcrumb->add($INFO_TITLE, tep_href_link(FILENAME_INFORMATION, 'info_id=' . $HTTP_GET_VARS['info_id'], 'NONSSL'));
	} else {
		$info_id = $HTTP_GET_VARS['info_id'];
		$information_query = tep_db_query("SELECT info_title, description 
										   FROM " . TABLE_INFORMATION . " 
										   WHERE visible='1' 
										   AND information_id='" . $info_id . "'
										  ");
		$information = tep_db_fetch_array($information_query);
        $title = stripslashes($information['info_title']);
        $description = stripslashes($information['description']);

		// # Added as noticed by infopages module
		if(!preg_match("/([\<])([^\>]{1,})*([\>])/i", $description)) {
        $description = str_replace("\r\n", "<br>\r\n", $description);
	}

	$desc_new = '';
	preg_match_all('/(.*?)(\{\{(.*?)\}\}|$)/s', $description, $info_match);

  for ($i=0;isset($info_match[0][$i]);$i++) {
    $desc_new .= $info_match[1][$i];
    $inc_f = $info_match[3][$i];
    if($inc_f != '' && !preg_match('/\.\./',$inc_f)) {
      $inc_fpath = DIR_FS_CATALOG."layout/$inc_f";
      $inc_fd = @fopen($inc_fpath,'r');
      if ($inc_fd) {
        $desc_new .= fread($inc_fd,65536);
	fclose($inc_fd);
	continue;
      } else $desc_new.="{{$inc_fpath}}";
    } else $desc_new.=$info_match[2][$i];
  }
  $description = $desc_new;

        $breadcrumb->add($title, tep_href_link(FILENAME_INFORMATION, 'info_id=' . (int)$HTTP_GET_VARS['info_id'], 'NONSSL'));
        }
?>
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><?php echo $articles_menu; ?></td>
      </tr>
      <tr>
        <td class="category_desc"><?php echo $description; ?></td>
</tr>
        </table>

</td>
<?php

} else {

	// # SEF END
	$cPath_array = explode('_',$cPath);
	$current_category_id = (int)$cPath_array[sizeof($cPath_array)-1];
	//$current_category_id = $cPath;

	if($category_depth == 'nested') {

		$category_query = tep_db_query("SELECT cd.categories_name, c.categories_image 
										FROM " . TABLE_CATEGORIES . " c
										LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = '".$current_category_id."'
										WHERE c.categories_id = '" . $current_category_id . "'
										AND cd.language_id = '" . (int)$languages_id . "'
									  ");

		$category = tep_db_fetch_array($category_query);
?> 

<td width="100%" valign="top">
	<table border="0" width="100%" cellspacing="0" cellpadding="0"> 
        <tr> 
          <td><table border="0" width="100%" cellspacing="0" cellpadding="0"> 
              <tr> 
                <td><table width="173" border="0" cellpadding="0" cellspacing="0"> 
                    <tr> 
<?php
	
	if (isset($cPath) && strpos('_', $cPath)) {

		// # check to see if there are deeper categories within the current category
		$category_links = array_reverse(explode('_',$cPath));

		for($i=0, $n=sizeof($category_links); $i<$n; $i++) {
			$categories_query = tep_db_query("SELECT COUNT(0) AS total 
											  FROM " . TABLE_CATEGORIES . " c
											  LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = c.categories_id
											  WHERE c.parent_id = '" . (int)$category_links[$i] . "'
											  AND cd.language_id = '" . (int)$languages_id . "'
											");

			$categories = tep_db_fetch_array($categories_query);

			if($categories['total'] > 0) {
	
				$categories_query = tep_db_query("SELECT c.categories_id, 
														 cd.categories_name, 
														 c.categories_image, 
														 c.parent_id, 
														 cd.categories_htc_description 
												  FROM " . TABLE_CATEGORIES . " c
												  LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = c.categories_id
												  WHERE c.parent_id = '" . (int)$category_links[$i] . "'
												  AND cd.language_id = '" . (int)$languages_id . "'
												  ORDER BY sort_order, cd.categories_name
												 ");

				break; // # we've found the deepest category the customer is in
			}
		}

	} else {

		$categories_query = tep_db_query("SELECT c.categories_id, 
														 cd.categories_name, 
														 c.categories_image, 
														 c.parent_id, 
														 cd.categories_htc_description 
												  FROM " . TABLE_CATEGORIES . " c
												  LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = c.categories_id
												  WHERE c.parent_id = '" . (int)$current_category_id . "' 
												  AND cd.language_id = '" . (int)$languages_id . "'
												  ORDER BY sort_order, cd.categories_name
												 ");
			}
		
	$number_of_categories = tep_db_num_rows($categories_query);
	
			$rows = 0;
			while ($categories = tep_db_fetch_array($categories_query)) {
				$rows++;
				$cPath_new = tep_get_path($categories['categories_id']);
				$width = (int)(100 / MAX_DISPLAY_CATEGORIES_PER_ROW) . '%';
				echo '                <td align="center"  width="' . $width . '" valign="top">
<table class="cat-main_table" cellspacing="0" cellpadding="0">
  <tr>
   <td style="width:173px; height:14px;"></td>
  </tr>
  <tr>
   <td><table border="0" cellpadding="0" cellspacing="0" width="173">
	  <tr>
	   <td valign="top"><table border="0" cellpadding="0" cellspacing="0" width="172">
		  <tr>
		   <td align="center" valign="top" style="width:172px; height:28px;"><a href="' . tep_href_link(FILENAME_DEFAULT, $cPath_new) . '" style="font:bold 13px;">' . $categories['categories_name'] . '</a></td>
		  </tr>
		  <tr>
		   <td align="center" valign="top"><a href="' . tep_href_link(FILENAME_DEFAULT, $cPath_new) . '">' . tep_image(DIR_WS_IMAGES . $categories['categories_image'], $categories['categories_name'], SUBCATEGORY_IMAGE_WIDTH, SUBCATEGORY_IMAGE_HEIGHT) . '</a></td>
		  </tr>
		  <tr>
		   <td style="width:172px; height:32px;" class="listCategoryDesc">'.$categories['categories_htc_description'].'</td>
		  </tr>
		  <tr>
		   <td align="center" valign="top" style="width:172px; height:20px;"><a href="' . tep_href_link(FILENAME_DEFAULT, $cPath_new) . '" style="font:bold 11px;">More ' . $categories['categories_name'] . '</a></td>
		  </tr>
		</table></td>
	   <td style="background:url(layout/img-lc/cat_c.jpg) no-repeat; width:1px; height:228px;"></td>
	  </tr>
	</table></td>
  </tr>
  <tr>
   <td style="background-color:#DADADA; width:173px; height:1px;"></td>
  </tr>
</table></td>' . "\n";
				if ((($rows / MAX_DISPLAY_CATEGORIES_PER_ROW) == floor($rows / MAX_DISPLAY_CATEGORIES_PER_ROW)) && ($rows != $number_of_categories)) {
					echo '              </tr>' . "\n";
					echo '              <tr>' . "\n";
				}
			}
	
	// # needed for the new products module shown below
			$new_products_category_id = $current_category_id;
	?> </tr> 
                </table></td> 
              </tr> 
              <tr> 
                <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td> 
              </tr> 
              <tr> 
                <td><?php //include(DIR_WS_MODULES . 'featured_products.php'); ?></td> 
              </tr> 
            </table>
          </td> 
        </tr> 
      </table></td> 
    <?php
		} elseif ($category_depth == 'products' || isset($_GET['manufacturers_id'])) {
	// # create column list
			$define_list = array('PRODUCT_LIST_MODEL' => PRODUCT_LIST_MODEL,
													 'PRODUCT_LIST_NAME' => PRODUCT_LIST_NAME,
													 'PRODUCT_LIST_MANUFACTURER' => PRODUCT_LIST_MANUFACTURER,
													 'PRODUCT_LIST_PRICE' => PRODUCT_LIST_PRICE,
													 'PRODUCT_LIST_QUANTITY' => PRODUCT_LIST_QUANTITY,
													 'PRODUCT_LIST_WEIGHT' => PRODUCT_LIST_WEIGHT,
													 'PRODUCT_LIST_IMAGE' => PRODUCT_LIST_IMAGE,
													 'PRODUCT_LIST_BUY_NOW' => PRODUCT_LIST_BUY_NOW);
	
			asort($define_list);
	
			$column_list = array();
			reset($define_list);
			while (list($key, $value) = each($define_list)) {
				if ($value > 0) $column_list[] = $key;
			}

			$column_list[]='PRODUCT_LIST_SORT_ORDER';
	

   if(!tep_session_is_registered('sppc_customer_group_id')) { 
     $customer_group_id = '0';
     } else {
      $customer_group_id = $sppc_customer_group_id;
   }
   // # this will build the table with specials prices for the retail group or update it if needed
   // # this function should have been added to includes/functions/database.php

   if ($customer_group_id == '0') {
	   tep_db_check_age_specials_retail_table(); 
   }

   $status_product_prices_table = false;
   $status_need_to_get_prices = false;

   // # find out if sorting by price has been requested
   if ( (isset($_GET['sort'])) && (preg_match('/[1-8][ad]/', $_GET['sort'])) && (substr($_GET['sort'], 0, 1) <= sizeof($column_list)) && $customer_group_id != '0' ){
    $_sort_col = substr($_GET['sort'], 0 , 1);
    if ($column_list[$_sort_col-1] == 'PRODUCT_LIST_PRICE') {
      $status_need_to_get_prices = true;
      }
   }
   
   if ($status_need_to_get_prices == true && $customer_group_id != '0') { 
   $product_prices_table = 'products_group_prices_'.$customer_group_id;
   // the table with product prices for a particular customer group is re-built only a number of times per hour
   // (setting in /includes/database_tables.php called MAXIMUM_DELAY_UPDATE_PG_PRICES_TABLE, in minutes)
   // to trigger the update the next function is called (new function that should have been
   // added to includes/functions/database.php)
   tep_db_check_age_products_group_prices_cg_table($customer_group_id);
   $status_product_prices_table = true;   

   } // end if ($status_need_to_get_prices == true && $customer_group_id != '0')
// EOF Separate Pricing Per Customer




			$select_column_list = 'pd.products_info, pd.products_info_alt, ';
	
			for ($i=0, $n=sizeof($column_list); $i<$n; $i++) {
				switch ($column_list[$i]) {
					case 'PRODUCT_LIST_MODEL':
						$select_column_list .= 'p.products_model, ';
						break;
					case 'PRODUCT_LIST_NAME':
						$select_column_list .= 'pd.products_name, ';
						break;
					case 'PRODUCT_LIST_MANUFACTURER':
						$select_column_list .= 'm.manufacturers_name, ';
						break;
					case 'PRODUCT_LIST_QUANTITY':
						$select_column_list .= 'p.products_quantity, ';
						break;
					case 'PRODUCT_LIST_IMAGE':
						$select_column_list .= 'p.products_image, ';
						break;
					case 'PRODUCT_LIST_WEIGHT':
						$select_column_list .= 'p.products_weight, ';
						break;
				}
			}
	


	// # show the products of a specified manufacturer
	if (isset($_GET['manufacturers_id'])) {

		if (isset($_GET['filter_id']) && tep_not_null($_GET['filter_id'])) {
		// # We are asked to show only a specific category

			if ($status_product_prices_table == true) {

				$listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, tmp_pp.products_price, p.products_tax_class_id, IF(tmp_pp.status, tmp_pp.specials_new_products_price, NULL) as specials_new_products_price, IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . $product_prices_table . " as tmp_pp using(products_id), " . TABLE_MANUFACTURERS . " m, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . (int)$_GET['filter_id'] . "'";		

			} else { // # either retail or no need to get correct special prices

				$listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join specials s on p.products_id = s.products_id where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . (int)$_GET['filter_id'] . "'";
			} // # end else { // either retail...

		} else { // # We show them all

	        if ($status_product_prices_table == true) {

				$listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, tmp_pp.products_price, p.products_tax_class_id, IF(tmp_pp.status, tmp_pp.specials_new_products_price, NULL) as specials_new_products_price, IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) as final_price from " . TABLE_PRODUCTS . " p left join " . $product_prices_table . " as tmp_pp using(products_id), " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m where p.products_status = '1' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "'";

			} else { // # either retail or no need to get correct special prices

				$listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from (" . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m) left join specials s on p.products_id = s.products_id where p.products_status = '1' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "'";
			} // # end else { // either retail...
		}
	
	} else {
		// # show the products in a given category
		if (isset($_GET['filter_id']) && tep_not_null($_GET['filter_id'])) {
		
			// # We are asked to show only specific catgeory;  

			if ($status_product_prices_table == true) {

				$listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, tmp_pp.products_price, p.products_tax_class_id, IF(tmp_pp.status, tmp_pp.specials_new_products_price, NULL) as specials_new_products_price, IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) as final_price from " . TABLE_PRODUCTS . " p left join " . $product_prices_table . " as tmp_pp using(products_id), " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$_GET['filter_id'] . "' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . (int)$current_category_id . "'";	
		
			} else { // either retail or no need to get correct special prices

				$listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_MANUFACTURERS . " m, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c left join specials s using(products_id) where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$_GET['filter_id'] . "' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . (int)$current_category_id . "'";
			} // # end else { // either retail...

		} else { // # We show them all

        	if ($status_product_prices_table == true) {

		        $listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, tmp_pp.products_price, p.products_tax_class_id, IF(tmp_pp.status, tmp_pp.specials_new_products_price, NULL) as specials_new_products_price, IF(tmp_pp.status, tmp_pp.specials_new_products_price, tmp_pp.products_price) as final_price from " . TABLE_PRODUCTS_DESCRIPTION . " pd left join " . $product_prices_table . " as tmp_pp using(products_id) left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS . " p" . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_status = '1' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . (int)$current_category_id . "'";
        
			} else { // either retail or no need to get correct special prices

		        $listing_sql = "select " . $select_column_list . " p.products_id, p.manufacturers_id, p.products_price, p.products_tax_class_id, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id left join specials s on p.products_id = s.products_id, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_status = '1' and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id = '" . (int)$current_category_id . "'";

			} // # end else { // either retail...
		}
	}


	list($sort_default_key,$sort_default_dir) = explode(':',PRODUCT_SORT_DEFAULT);


	if ( (!isset($_GET['sort'])) || (!preg_match('/[1-8][ad]/', $_GET['sort'])) || (substr($_GET['sort'], 0, 1) > sizeof($column_list)) ) {

		for ($i=0, $n=sizeof($column_list); $i<$n; $i++) {
			if ($column_list[$i] == $sort_default_key) {
				$sort_col = $i+1;
				break;
			}
		}

		$sort_order=$sort_default_dir;

	} else {
	
		$sort_col = substr($_GET['sort'], 0 , 1);
		$sort_order = substr($_GET['sort'], 1);
	}
			
			
	$sort_list = array(array(key => $column_list[$sort_col-1], dir => $sort_order));

	foreach (explode(',',PRODUCT_SORT_SECONDARY) AS $sort_order_line) {
		list($sort_order_key,$sort_order_dir) = explode(':',$sort_order_line);
		if ($sort_order_key!='') $sort_list[]=Array(key=>$sort_order_key,dir=>$sort_order_dir);
	}

	$listing_sql .= ' order by ';
			

	foreach ($sort_list AS $sort_list_idx=>$sort_entry) {
		if ($sort_list_idx) $listing_sql .= ', ';
		$sort_dir_token=($sort_entry['dir'] == 'd' ? 'DESC' : 'ASC');
		
		switch ($sort_entry['key']) {
				case 'PRODUCT_LIST_MODEL':
					$listing_sql .= "p.products_model $sort_dir_token";
					break;
				case 'PRODUCT_LIST_NAME':
					$listing_sql .= "pd.products_name $sort_dir_token";
					break;
				case 'PRODUCT_LIST_MANUFACTURER':
					$listing_sql .= "m.manufacturers_name  $sort_dir_token";
					break;
				case 'PRODUCT_LIST_QUANTITY':
					$listing_sql .= "p.products_quantity $sort_dir_token";
					break;
				case 'PRODUCT_LIST_IMAGE':
					$listing_sql .= "pd.products_name $sort_dir_token";
					break;
				case 'PRODUCT_LIST_WEIGHT':
					$listing_sql .= "p.products_weight $sort_dir_token";
					break;
				case 'PRODUCT_LIST_PRICE':
					$listing_sql .= "final_price $sort_dir_token";
					break;
               
				default:  $listing_sql.="p.products_sort_order $sort_dir_token";
			}
		}
?>
    <td>
<?php
		
			// # optional Product List Filter
			if (PRODUCT_LIST_FILTER > 0) {

				if (isset($_GET['manufacturers_id'])) {

					$filterlist_sql = "select distinct c.categories_id as id, cd.categories_name as name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where p.products_status = '1' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and p2c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and p.manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "' order by cd.categories_name";

				} else {

					$filterlist_sql= "select distinct m.manufacturers_id as id, m.manufacturers_name as name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_MANUFACTURERS . " m where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "' order by m.manufacturers_name";
				}

				//$filterlist_query = tep_db_query($filterlist_sql);

				//if (tep_db_num_rows($filterlist_query) > 1) {


				//}
			}
	
			// # Get the right image for the top-right
			$image = DIR_WS_IMAGES . 'table_background_list.gif';
			if (isset($_GET['manufacturers_id'])) {
				$image = tep_db_query("select manufacturers_image from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$_GET['manufacturers_id'] . "'");
				$image = tep_db_fetch_array($image);
				$image = $image['manufacturers_image'];
			} elseif ($current_category_id) {
				$image = tep_db_query("select categories_image from " . TABLE_CATEGORIES . " where categories_id = '" . (int)$current_category_id . "'");
				$image = tep_db_fetch_array($image);
				$image = $image['categories_image'];
			}

	include(DIR_WS_MODULES . FILENAME_PRODUCT_LISTING); 
?>
	</td> 
<?php } else { 	?> 

    <td width="100%" valign="top"></td> 
   </tr> 
</table></td> 
<?php
	}

}

?> 
    
    <td valign="top"><table border="0" cellspacing="0" cellpadding="2"> 
        
        <?php require(DIR_WS_INCLUDES . 'column_right.php'); ?> 
       
      </table></td> 
  </tr> 
</table> 

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?> 

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
