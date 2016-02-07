<?php
/*
  $Id: supplier_s_categories_products.php.php,v 1.146 2003/07/11 14:40:27 hpdl Exp $

  
  

  Copyright (c) 2003 IntenseCart eCommerce

  
*/

  require('includes/application_top.php');
  require('includes/supplier_area_top.php');
	
  $suppliers_id = $HTTP_SESSION_VARS['login'];

  $IXAdminID = $_REQUEST['IXAdminID'];

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $categories_suppliers_query = tep_db_query("select c.categories_id, c.parent_id, cs.categories_id, cs.suppliers_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_TO_SUPPLIERS . " cs where c.categories_id = cs.categories_id and cs.suppliers_id = '" . (int)$suppliers_id . "'");


  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'setflag':
        if ( ($HTTP_GET_VARS['flag'] == '0') || ($HTTP_GET_VARS['flag'] == '1') ) {
          if (isset($HTTP_GET_VARS['pID'])) {
            tep_set_product_status($HTTP_GET_VARS['pID'], $HTTP_GET_VARS['flag']);
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('supplier_s_categories_products');
            tep_reset_cache_block('also_purchased');
          }
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $HTTP_GET_VARS['cPath'] . '&pID=' . $HTTP_GET_VARS['pID']));
        break;
      case 'insert_category':
      case 'update_category':
	  if($current_category_id != 0){
        if (isset($HTTP_POST_VARS['categories_id'])) $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
        $sort_order = tep_db_prepare_input($HTTP_POST_VARS['sort_order']);

        $sql_data_array = array('sort_order' => $sort_order);
		
        if ($action == 'insert_category') {
          $insert_sql_data = array('parent_id' => $current_category_id,
                                   'date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
		  
          tep_db_perform(TABLE_CATEGORIES, $sql_data_array);

          $categories_id = tep_db_insert_id();
        } elseif ($action == 'update_category') {
          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $categories_name_array = $HTTP_POST_VARS['categories_name'];

          $language_id = $languages[$i]['id'];

          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]));

          if ($action == 'insert_category') {
            $insert_sql_data = array('categories_id' => $categories_id,
                                     'language_id' => $languages[$i]['id']);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
          } elseif ($action == 'update_category') {
            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
          }
        }

        if (HTML_AREA_WYSIWYG_DISABLE == 'Disable') {
          if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {
            tep_db_query("update " . TABLE_CATEGORIES . " set categories_image = '" . tep_db_input($categories_image->filename) . "' where categories_id = '" . (int)$categories_id . "'");
          }
        } else {
          if (isset($HTTP_POST_VARS['categories_image']) && tep_not_null($HTTP_POST_VARS['categories_image']) && ($HTTP_POST_VARS['categories_image'] != 'none')) {
            tep_db_query("update " . TABLE_CATEGORIES . " set categories_image = '" . tep_db_input($HTTP_POST_VARS['categories_image']) . "' where categories_id = '" . (int)$categories_id . "'");
          }
        }

// RJW Begin Meta Tags Code
         $meta_title = tep_db_prepare_input($HTTP_POST_VARS['meta_title']);
         $meta_description = tep_db_prepare_input($HTTP_POST_VARS['meta_description']);
         $meta_keywords = tep_db_prepare_input($HTTP_POST_VARS['meta_keywords']);

         $sql_data_array = array('title' => $meta_title,
                                 'description' => $meta_description,
                                 'keywords' => $meta_keywords);

         $meta_query = tep_db_query ("select title, keywords, description from " . TABLE_META_TAGS . " where categories_id = '" . (int)$categories_id . "'");

         if (!tep_db_num_rows($meta_query)) {
            $insert_sql_data = array('categories_id' => $categories_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_META_TAGS, $sql_data_array);
         } else {
           tep_db_perform(TABLE_META_TAGS, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "'");
        }
// RJW End Meta Tags Code
		if (USE_CACHE == 'true') {
          tep_reset_cache_block('supplier_s_categories_products');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $categories_id));
        break;
		}
      case 'delete_category_confirm':
        if (isset($HTTP_POST_VARS['categories_id'])) {
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);

          $categories = tep_get_category_tree($categories_id, '', '0', '', true);
          $products = array();
          $products_delete = array();

          for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
            $product_ids_query = tep_db_query("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . (int)$categories[$i]['id'] . "'");

            while ($product_ids = tep_db_fetch_array($product_ids_query)) {
              $products[$product_ids['products_id']]['categories'][] = $categories[$i]['id'];
            }
          }

          reset($products);
          while (list($key, $value) = each($products)) {
            $category_ids = '';

            for ($i=0, $n=sizeof($value['categories']); $i<$n; $i++) {
              $category_ids .= "'" . (int)$value['categories'][$i] . "', ";
            }
            $category_ids = substr($category_ids, 0, -2);

            $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$key . "' and categories_id not in (" . $category_ids . ")");
            $check = tep_db_fetch_array($check_query);
            if ($check['total'] < '1') {
              $products_delete[$key] = $key;
            }
          }

// removing categories can be a lengthy process
          tep_set_time_limit(0);
          for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
            tep_remove_category($categories[$i]['id']);

// RJW Begin Meta Tags Code
         tep_db_query ("delete from " . TABLE_META_TAGS . " where categories_id = '" . $categories[$i]['id'] . "'");
// RJW End Meta Tags Code

          }

          reset($products_delete);
          while (list($key) = each($products_delete)) {
            tep_remove_product($key);
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('supplier_s_categories_products');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath));
        break;
	
      case 'delete_product_confirm':
        if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['product_categories']) && is_array($HTTP_POST_VARS['product_categories'])) {
          $product_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
          $product_categories = $HTTP_POST_VARS['product_categories'];

          for ($i=0, $n=sizeof($product_categories); $i<$n; $i++) {
		  $delimg_query = tep_db_query("select popup_images from " . TABLE_ADDITIONAL_IMAGES . " where products_id = '" . (int)$product_id . "'");
            while ($delimg = tep_db_fetch_array($delimg_query)){
                if (tep_not_null($delimg['popup_images']) && file_exists(DIR_FS_CATALOG_IMAGES.$delimg['popup_images']) )
                  if (!unlink (DIR_FS_CATALOG_IMAGES.$delimg['popup_images']))
                     $messageStack->add_session(ERROR_DEL_IMG_XTRA.$delimg['popup_images'], 'error');
                  else
                     $messageStack->add_session(SUCCESS_DEL_IMG_XTRA.$delimg['popup_images'], 'success');
            }
            tep_db_query("delete from " . TABLE_ADDITIONAL_IMAGES . " where products_id = '" . (int)$product_id . "'");
            tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "' and categories_id = '" . (int)$product_categories[$i] . "'");
          }

          $product_categories_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
          $product_categories = tep_db_fetch_array($product_categories_query);

          if ($product_categories['total'] == '0') {
            tep_remove_product($product_id);
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('supplier_s_categories_products');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath));
        break;
      case 'move_category_confirm':
        if (isset($HTTP_POST_VARS['categories_id']) && ($HTTP_POST_VARS['categories_id'] != $HTTP_POST_VARS['move_to_category_id'])) {
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
          $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);

          $path = explode('_', tep_get_generated_category_path_ids($new_parent_id));

          if (in_array($categories_id, $path)) {
            $messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');

            tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $categories_id));
          } else {
            tep_db_query("update " . TABLE_CATEGORIES . " set parent_id = '" . (int)$new_parent_id . "', last_modified = now() where categories_id = '" . (int)$categories_id . "'");

            if (USE_CACHE == 'true') {
              tep_reset_cache_block('supplier_s_categories_products');
              tep_reset_cache_block('also_purchased');
            }

            tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $new_parent_id . '&cID=' . $categories_id));
          }
        }

        break;

      case 'move_product_confirm':
        $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
        $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);

        $duplicate_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$new_parent_id . "'");
        $duplicate_check = tep_db_fetch_array($duplicate_check_query);
        if ($duplicate_check['total'] < 1) tep_db_query("update " . TABLE_PRODUCTS_TO_CATEGORIES . " set categories_id = '" . (int)$new_parent_id . "' where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$current_category_id . "'");

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('supplier_s_categories_products');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $new_parent_id . '&pID=' . $products_id));
        break;
///////////////////////////////////////////////////////////////////////////////////////
// BOF: WebMakers.com Added: Copy Attributes Existing Product to another Existing Product
      case 'create_copy_product_attributes':
  // $products_id_to= $copy_to_products_id;
  // $products_id_from = $pID;
        tep_copy_products_attributes($pID,$copy_to_products_id);
        break;
// EOF: WebMakers.com Added: Copy Attributes Existing Product to another Existing Product
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
// WebMakers.com Added: Copy Attributes Existing Product to All Existing Products in a Category
      case 'create_copy_product_attributes_categories':
  // $products_id_to= $categories_products_copying['products_id'];
  // $products_id_from = $make_copy_from_products_id;
  //  echo 'Copy from products_id# ' . $make_copy_from_products_id . ' Copy to all products in category: ' . $cID . '<br>';
        $categories_products_copying_query= tep_db_query("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id='" . $cID . "'");
        while ( $categories_products_copying=tep_db_fetch_array($categories_products_copying_query) ) {
          // process all products in category
          tep_copy_products_attributes($make_copy_from_products_id,$categories_products_copying['products_id']);
        }
        break;
// EOF: WebMakers.com Added: Copy Attributes Existing Product to All Existing Products in a Category
///////////////////////////////////////////////////////////////////////////////////////
      case 'insert_product':
      case 'update_product':
        if (isset($HTTP_POST_VARS['edit_x']) || isset($HTTP_POST_VARS['edit_y'])) {
          $action = 'new_product';
        } else {
          if (isset($HTTP_GET_VARS['pID'])) $products_id = tep_db_prepare_input($HTTP_GET_VARS['pID']);
          $products_date_available = tep_db_prepare_input($HTTP_POST_VARS['products_date_available']);

          $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';

          $sql_data_array = array('products_quantity' => tep_db_prepare_input($HTTP_POST_VARS['products_quantity']),
                                  'products_model' => tep_db_prepare_input($HTTP_POST_VARS['products_model']),
                                  'products_price' => tep_db_prepare_input($HTTP_POST_VARS['products_price']),
                                  'products_date_available' => $products_date_available,
                                  'products_weight' => tep_db_prepare_input($HTTP_POST_VARS['products_weight']),
                                  'products_status' => tep_db_prepare_input($HTTP_POST_VARS['products_status']),
                                  'products_tax_class_id' => tep_db_prepare_input($HTTP_POST_VARS['products_tax_class_id'])/*,
                                  'manufacturers_id' => tep_db_prepare_input($HTTP_POST_VARS['manufacturers_id'])*/);

          if (isset($HTTP_POST_VARS['products_image']) && tep_not_null($HTTP_POST_VARS['products_image']) && ($HTTP_POST_VARS['products_image'] != 'none')) {
            $sql_data_array['products_image'] = tep_db_prepare_input($HTTP_POST_VARS['products_image']);
          }
		  if (isset($HTTP_POST_VARS['products_image_pop']) && tep_not_null($HTTP_POST_VARS['products_image_pop']) && ($HTTP_POST_VARS['products_image_pop'] != 'none')) {
            $sql_data_array['products_image_pop'] = tep_db_prepare_input($HTTP_POST_VARS['products_image_pop']);
          }

          if ($action == 'insert_product') {
            $insert_sql_data = array('products_date_added' => 'now()');
			$insert_sql_data = array('suppliers_id' => $suppliers_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();
            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . $products_id . "', '" . $HTTP_POST_VARS['categories_id'] . "')");

          } elseif ($action == 'update_product') {
            $update_sql_data = array('products_last_modified' => 'now()');

            $sql_data_array = array_merge($sql_data_array, $update_sql_data);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");
	 #delete categories saved in the tables
	    //tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '". $products_id . "'");	    
          }

	 # create loop here to insert rows for multiple categories
	 //$selected_catids = $HTTP_POST_VARS['categories_ids'];
	 //print_r($selected_catids);
	 //die("aklsdfsdljf");
	 
	 //if ($selected_catids)
	 //{
		//foreach ($selected_catids as $current_category_id)
	//	{
		//tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . $products_id . "', '" . $current_category_id . "')");
		//}
	// }

          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('products_name' => tep_db_prepare_input($HTTP_POST_VARS['products_name'][$language_id]),
                                    'products_info' => tep_db_prepare_input($HTTP_POST_VARS['products_info'][$language_id]),
									'products_description' => tep_db_prepare_input($HTTP_POST_VARS['products_description'][$language_id]),
                                    'products_url' => tep_db_prepare_input($HTTP_POST_VARS['products_url'][$language_id]),
                                    'products_head_title_tag' => tep_db_prepare_input($HTTP_POST_VARS['products_head_title_tag'][$language_id]),
                                    'products_head_desc_tag' => tep_db_prepare_input($HTTP_POST_VARS['products_head_desc_tag'][$language_id]),
                                    'products_head_keywords_tag' => tep_db_prepare_input($HTTP_POST_VARS['products_head_keywords_tag'][$language_id]));

            if ($action == 'insert_product') {
              $insert_sql_data = array('products_id' => $products_id,
                                       'language_id' => $language_id);				

              $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
            } elseif ($action == 'update_product') {
              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");
            }
          }
/////////////////////////////////////////////////////////////////////
// BOF: WebMakers.com Added: Update Product Attributes and Sort Order
// Update the changes to the attributes if any changes were made
          // Update Product Attributes
          $rows = 0;
          $options_query = tep_db_query("select products_options_id, products_options_name from " . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . $languages_id . "' order by products_options_name");
          while ($options = tep_db_fetch_array($options_query)) {
            $values_query = tep_db_query("select pov.products_options_values_id, pov.products_options_values_name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov, " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " p2p where pov.products_options_values_id = p2p.products_options_values_id and p2p.products_options_id = '" . $options['products_options_id'] . "' and pov.language_id = '" . $languages_id . "'");
            while ($values = tep_db_fetch_array($values_query)) {
              $rows ++;
// original              $attributes_query = tep_db_query("select products_attributes_id, options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . $products_id . "' and options_id = '" . $options['products_options_id'] . "' and options_values_id = '" . $values['products_options_values_id'] . "'");
              $attributes_query = tep_db_query("select products_attributes_id, options_values_price, price_prefix, products_options_sort_order, product_attributes_one_time, products_attributes_weight, products_attributes_weight_prefix, products_attributes_units, products_attributes_units_price from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . $products_id . "' and options_id = '" . $options['products_options_id'] . "' and options_values_id = '" . $values['products_options_values_id'] . "'");
              if (tep_db_num_rows($attributes_query) > 0) {
                $attributes = tep_db_fetch_array($attributes_query);
                if ($HTTP_POST_VARS['option'][$rows]) {
                  if ( ($HTTP_POST_VARS['prefix'][$rows] <> $attributes['price_prefix']) || ($HTTP_POST_VARS['price'][$rows] <> $attributes['options_values_price']) || ($HTTP_POST_VARS['products_options_sort_order'][$rows] <> $attributes['products_options_sort_order']) || ($HTTP_POST_VARS['product_attributes_one_time'][$rows] <> $attributes['product_attributes_one_time']) || ($HTTP_POST_VARS['products_attributes_weight'][$rows] <> $attributes['products_attributes_weight']) || ($HTTP_POST_VARS['products_attributes_weight_prefix'][$rows] <> $attributes['products_attributes_weight_prefix']) || ($HTTP_POST_VARS['products_attributes_units'][$rows] <> $attributes['products_attributes_units']) || ($HTTP_POST_VARS['products_attributes_units_price'][$rows] <> $attributes['products_attributes_units_price']) ) {
                    tep_db_query("update " . TABLE_PRODUCTS_ATTRIBUTES . " set options_values_price = '" . $HTTP_POST_VARS['price'][$rows] . "', price_prefix = '" . $HTTP_POST_VARS['prefix'][$rows] . "', products_options_sort_order = '" . $HTTP_POST_VARS['products_options_sort_order'][$rows] . "', product_attributes_one_time = '" . $HTTP_POST_VARS['product_attributes_one_time'][$rows] . "', products_attributes_weight = '" . $HTTP_POST_VARS['products_attributes_weight'][$rows] . "', products_attributes_weight_prefix = '" . $HTTP_POST_VARS['products_attributes_weight_prefix'][$rows] . "', products_attributes_units = '" . $HTTP_POST_VARS['products_attributes_units'][$rows] . "', products_attributes_units_price = '" . $HTTP_POST_VARS['products_attributes_units_price'][$rows] . "' where products_attributes_id = '" . $attributes['products_attributes_id'] . "'");
                  }
                } else {
                  tep_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_attributes_id = '" . $attributes['products_attributes_id'] . "'");
                }
              } elseif ($HTTP_POST_VARS['option'][$rows]) {
                tep_db_query("insert into " . TABLE_PRODUCTS_ATTRIBUTES . " values ('', '" . $products_id . "', '" . $options['products_options_id'] . "', '" . $values['products_options_values_id'] . "', '" . $HTTP_POST_VARS['price'][$rows] . "', '" . $HTTP_POST_VARS['prefix'][$rows] . "', '" . $HTTP_POST_VARS['products_options_sort_order'][$rows] . "', '" . $HTTP_POST_VARS['product_attributes_one_time'][$rows] . "', '" . $HTTP_POST_VARS['products_attributes_weight'][$rows] . "', '" . $HTTP_POST_VARS['products_attributes_weight_prefix'][$rows] . "', '" . $HTTP_POST_VARS['products_attributes_units'][$rows] . "', '" . $HTTP_POST_VARS['products_attributes_units_price'][$rows] . "' )");
              }
            }
          }
// EOF: WebMakers.com Added: Update Product Attributes and Sort Order
/////////////////////////////////////////////////////////////////////

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('supplier_s_categories_products');
            tep_reset_cache_block('also_purchased');
          }

          tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products_id));
        }
        break;
      case 'copy_to_confirm':
        if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['categories_id'])) {
          $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);

          if ($HTTP_POST_VARS['copy_as'] == 'link') {
            if ($categories_id != $current_category_id) {
              $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$categories_id . "'");
              $check = tep_db_fetch_array($check_query);
              if ($check['total'] < '1') {
                tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$categories_id . "')");
              }
            } else {
              $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
            }
          } elseif ($HTTP_POST_VARS['copy_as'] == 'duplicate') {
            $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "' and suppliers_id = '" . (int)$suppliers_id . "'");
            $product = tep_db_fetch_array($product_query);

            tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id, suppliers_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), '" . tep_db_input($product['products_date_available']) . "', '" . tep_db_input($product['products_weight']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "', '" . (int)$suppliers_id . "')");
            $dup_products_id = tep_db_insert_id();

            $description_query = tep_db_query("select language_id, products_name, products_info, products_description, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag, products_url from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$products_id . "'");
            while ($description = tep_db_fetch_array($description_query)) {
              tep_db_query("insert into " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id, products_name, products_info, products_description, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag, products_url, products_viewed) values ('" . (int)$dup_products_id . "', '" . (int)$description['language_id'] . "', '" . tep_db_input($description['products_name']) . "', '" . tep_db_input($description['products_info']) . "', '" . tep_db_input($description['products_description']) . "', '" . tep_db_input($description['products_head_title_tag']) . "', '" . tep_db_input($description['products_head_desc_tag']) . "', '" . tep_db_input($description['products_head_keywords_tag']) . "', '" . tep_db_input($description['products_url']) . "', '0')");
            }

            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$dup_products_id . "', '" . (int)$categories_id . "')");
// BOF: WebMakers.com Added: Attributes Copy on non-linked
            $products_id_from=tep_db_input($products_id);
            $products_id_to= $dup_products_id;
            $products_id = $dup_products_id;
if ( $HTTP_POST_VARS['copy_attributes']=='copy_attributes_yes' and $HTTP_POST_VARS['copy_as'] == 'duplicate' ) {

// WebMakers.com Added: Copy attributes to duplicate product
  // $products_id_to= $copy_to_products_id;
  // $products_id_from = $pID;
            $copy_attributes_delete_first='1';
            $copy_attributes_duplicates_skipped='1';
            $copy_attributes_duplicates_overwrite='0';

            if (DOWNLOAD_ENABLED == 'true') {
              $copy_attributes_include_downloads='1';
              $copy_attributes_include_filename='1';
            } else {
              $copy_attributes_include_downloads='0';
              $copy_attributes_include_filename='0';
            }
            tep_copy_products_attributes($products_id_from,$products_id_to);

// EOF: WebMakers.com Added: Attributes Copy on non-linked
}
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('supplier_s_categories_products');
            tep_reset_cache_block('also_purchased');
          }
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $categories_id . '&pID=' . $products_id));
        break;
      case 'new_product_preview':
        //print_r ($HTTP_POST_FILES['categories_ids']);
//          die("09r8340958");
      	if (HTML_AREA_WYSIWYG_DISABLE == 'Disable') {
          // copy image only if modified
          $products_image = new upload('products_image');
          $products_image->set_destination(DIR_FS_CATALOG_IMAGES);
          if ($products_image->parse() && $products_image->save()) {
            $products_image_name = $products_image->filename;
          } else {
            $products_image_name = (isset($HTTP_POST_VARS['products_previous_image']) ? $HTTP_POST_VARS['products_previous_image'] : '');
          }
        } else {
          if (isset($HTTP_POST_VARS['products_image']) && tep_not_null($HTTP_POST_VARS['products_image']) && ($HTTP_POST_VARS['products_image'] != 'none')) {
            $products_image_name = $HTTP_POST_VARS['products_image'];
          } else {
            $products_image_name = (isset($HTTP_POST_VARS['products_previous_image']) ? $HTTP_POST_VARS['products_previous_image'] : '');
          }
        }
		$products_image_pop = new upload('products_image_pop');
        $products_image_pop->set_destination(DIR_FS_CATALOG_IMAGES);
		if ($products_image_pop->parse() && $products_image_pop->save()) {
          $products_image_pop_name = $products_image_pop->filename;
        } else {
          $products_image_pop_name = (isset($HTTP_POST_VARS['products_previous_image_pop']) ? $HTTP_POST_VARS['products_previous_image_pop'] : '');
        }
        break;
		case 'add_images':
        $products_id = $HTTP_GET_VARS['pID'];
        $add_images_error = true;
        if ($popup_images = new upload('popup_images', DIR_FS_CATALOG_IMAGES)) {
          $add_images_error = false;
          $sql_data_array = array('products_id' => tep_db_prepare_input($products_id),
                                  'images_description' => tep_db_prepare_input($HTTP_POST_VARS['images_description']),
                                  'popup_images' => tep_db_prepare_input($popup_images->filename));
          $sql_data_array = array_merge($sql_data_array, $add_data_array);
        }
        if ($add_images_error == false) {
          tep_db_perform(TABLE_ADDITIONAL_IMAGES, $sql_data_array);
        } else {
          $messageStack->add_session(ERROR_ADDITIONAL_IMAGE_IS_EMPTY, 'error');
        }
        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products_id));
        break;

      case 'del_images':
        $products_id = tep_db_prepare_input($HTTP_GET_VARS['pID']);
        if ( ($HTTP_GET_VARS['pID']) && (is_array($HTTP_POST_VARS['additional_images_id'])) ) {                       
          $additional_images_id[] = tep_db_prepare_input($HTTP_POST_VARS['additional_images_id']);
          for ($i=0; $i<sizeof($additional_images_id); $i++) {
//SECTION DELETE POPUP IMAGES
            $delimg_query = tep_db_query("select popup_images from " . TABLE_ADDITIONAL_IMAGES . " where additional_images_id = '" . tep_db_input($additional_images_id[$i]) . "'");
            $delimg = tep_db_fetch_array($delimg_query);
            if (tep_not_null($delimg['popup_images']) && file_exists(DIR_FS_CATALOG_IMAGES.$delimg['popup_images']) )
                if (!unlink (DIR_FS_CATALOG_IMAGES.$delimg['popup_images']))
                   $messageStack->add_session(ERROR_DEL_IMG_XTRA.$delimg['popup_images'], 'error');
                else
                   $messageStack->add_session(SUCCESS_DEL_IMG_XTRA.$delimg['popup_images'], 'success');
//END OF SECTION DELETE POPUP IMAGES
            tep_db_query("delete from " . TABLE_ADDITIONAL_IMAGES . " where additional_images_id = '" . tep_db_input($additional_images_id[$i]) . "'");
          }
        }
        tep_redirect(tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products_id));
        break;
    }
  }

// check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG_IMAGES)) {
    if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else {
    $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
  }
?>
<?php
// WebMakers.com Added: Display Order
  switch (true) {
    case (CATEGORIES_SORT_ORDER=="products_name"):
      $order_it_by = "pd.products_name";
      break;
    case (CATEGORIES_SORT_ORDER=="products_name-desc"):
      $order_it_by = "pd.products_name DESC";
      break;
    case (CATEGORIES_SORT_ORDER=="model"):
      $order_it_by = "p.products_model";
      break;
    case (CATEGORIES_SORT_ORDER=="model-desc"):
      $order_it_by = "p.products_model DESC";
      break;
    default:
      $order_it_by = "pd.products_name";
      break;
    }
?>

<?php
$go_back_to=$REQUEST_URI;
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>

        <script language="Javascript1.2"><!-- // load htmlarea
// MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 - 2.2 MS2 Products Description HTML - Head
        _editor_url = "<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_ADMIN; ?>htmlarea/";  // URL to htmlarea files
          var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);
           if (navigator.userAgent.indexOf('Mac')        >= 0) { win_ie_ver = 0; }
            if (navigator.userAgent.indexOf('Windows CE') >= 0) { win_ie_ver = 0; }
             if (navigator.userAgent.indexOf('Opera')      >= 0) { win_ie_ver = 0; }
         <?php if (HTML_AREA_WYSIWYG_BASIC_PD == 'Basic'){ ?>  if (win_ie_ver >= 5.5) {
         document.write('<scr' + 'ipt src="' +_editor_url+ 'editor_basic.js"');
         document.write(' language="Javascript1.2"></scr' + 'ipt>');
            } else { document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); }
         <?php } else{ ?> if (win_ie_ver >= 5.5) {
         document.write('<scr' + 'ipt src="' +_editor_url+ 'editor_advanced.js"');
         document.write(' language="Javascript1.2"></scr' + 'ipt>');
            } else { document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); }
         <?php }?>
// --></script>
  
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<?php
// WebMakers.com Added: Java Scripts
include(DIR_WS_INCLUDES . 'javascript/' . 'webmakers_added_js.php')
?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
<div id="spiffycalendar" class="text"></div>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header_supplier.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
  <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'supplier_column_left.php'); ?>
<!-- left_navigation_eof //-->
	</table></td>
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo 'Supplier\'s Categories/Products'; ?></td>
           <td class="smallText" align="right">
		   <?php 
			echo tep_draw_form('search', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, '', 'get');
	echo tep_draw_hidden_field('IXAdminID', $IXAdminID);
    echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search');
    echo '</form>';
		   ?>
		   </td>
          </tr>
        </table></td>
		</tr>
		<tr>
<!-- body_text //-->
    <td width="100%" valign="top">
<?php
  if ($action == 'new_product') {
    $parameters = array('products_name' => '',
                       'products_info' => '',
					   'products_description' => '',
                       'products_url' => '',
                       'products_id' => '',
                       'products_quantity' => '',
                       'products_model' => '',
                       'products_image' => '',
                       'products_price' => '',
                       'products_weight' => '',
                       'products_date_added' => '',
                       'products_last_modified' => '',
                       'products_date_available' => '',
                       'products_status' => '',
                           'products_tax_class_id' => '',
                       //'manufacturers_id' => '', 
					   'suppliers_id' => '');

    $pInfo = new objectInfo($parameters);

    if (isset ($HTTP_GET_VARS['pID']) && (!$HTTP_POST_VARS) ) {
      $product_query = tep_db_query("select pd.products_name, pd.products_description, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_head_keywords_tag, pd.products_url, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id, p.suppliers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.suppliers_id = '" . (int)$suppliers_id . "'");
      $product = tep_db_fetch_array($product_query);

      $pInfo->objectInfo($product);
    } elseif (tep_not_null($HTTP_POST_VARS)) {
      $pInfo->objectInfo($HTTP_POST_VARS);
      $products_name = $HTTP_POST_VARS['products_name'];
	  $products_info = $HTTP_POST_VARS['products_info'];
      $products_description = $HTTP_POST_VARS['products_description'];
      $products_url = $HTTP_POST_VARS['products_url'];
    }

//  echo $current_category_id;    
    /*$manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
    $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
    while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                     'text' => $manufacturers['manufacturers_name']);
    }*/
	# get selected categories
    /*$categories_query_selected = tep_db_query("select categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . $HTTP_GET_VARS['pID'] . "'");
    $categories_array_selected = array(array('id' => ''));
    while ($categories = tep_db_fetch_array($categories_query_selected)) {
      $categories_array_selected[] = array('id' => $categories['categories_id']);
    }*/
    //echo $current_category_id;

    $categories_array = array(array('id' => $current_category_id, 'text' => $current_category_id)); 
    $categories_array_selected = array();
    $categories_array_selected[] = array('id' => $current_category_id);
    #Categories list displays only for one languge (Deafault is English)
    $language_id = 1;
    //$categories_array = tep_get_category_tree();

    $tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
    $tax_class_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
    while ($tax_class = tep_db_fetch_array($tax_class_query)) {
      $tax_class_array[] = array('id' => $tax_class['tax_class_id'],
                                 'text' => $tax_class['tax_class_title']);
    }

    $languages = tep_get_languages();

    if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
    switch ($pInfo->products_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }
?>
<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script language="javascript"><!--
  var dateAvailable = new ctlSpiffyCalendarBox("dateAvailable", "new_product", "products_date_available","btnDate1","<?php echo $pInfo->products_date_available; ?>",scBTNMODE_CUSTOMBLUE);
//--></script>
<script language="javascript"><!--
var tax_rates = new Array();
<?php
    for ($i=0, $n=sizeof($tax_class_array); $i<$n; $i++) {
      if ($tax_class_array[$i]['id'] > 0) {
        echo 'tax_rates["' . $tax_class_array[$i]['id'] . '"] = ' . tep_get_tax_rate_value($tax_class_array[$i]['id']) . ';' . "\n";
      }
    }
?>

function doRound(x, places) {
  return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
}

function getTaxRate() {
  var selected_value = document.forms["new_product"].products_tax_class_id.selectedIndex;
  var parameterVal = document.forms["new_product"].products_tax_class_id[selected_value].value;

  if ( (parameterVal > 0) && (tax_rates[parameterVal] > 0) ) {
    return tax_rates[parameterVal];
  } else {
    return 0;
  }
}

function updateGross() {
  var taxRate = getTaxRate();
  var grossValue = document.forms["new_product"].products_price.value;

  if (taxRate > 0) {
    grossValue = grossValue * ((taxRate / 100) + 1);
  }

  document.forms["new_product"].products_price_gross.value = doRound(grossValue, 4);
}

function updateNet() {
  var taxRate = getTaxRate();
  var netValue = document.forms["new_product"].products_price_gross.value;

  if (taxRate > 0) {
    netValue = netValue / ((taxRate / 100) + 1);
  }

  document.forms["new_product"].products_price.value = doRound(netValue, 4);
}
//--></script>
    <?php echo tep_draw_form('new_product', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=new_product_preview', 'post', 'enctype="multipart/form-data"'); ?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo sprintf(TEXT_NEW_PRODUCT, tep_output_generated_category_path($current_category_id)); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_STATUS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_status', '1', $in_status) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE . '&nbsp;' . tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_DATE_AVAILABLE; ?><br><small>(YYYY-MM-DD)</small></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?><script language="javascript">dateAvailable.writeControl(); dateAvailable.dateFormat="yyyy-MM-dd";</script></td>
          </tr>
          <!--<tr>
            <td colspan="2"><?php //echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>-->
          <!--<tr>
            <td class="main"><?php //echo TEXT_PRODUCTS_MANUFACTURER; ?></td>
            <td class="main"><?php //echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id); ?></td>
          </tr>-->
		  <tr>
            <td class="main"><?php //echo TEXT_CATEGORIES; ?></td>
            
            <td class="main"><?php echo tep_draw_hidden_field('categories_id', $current_category_id)/*tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_mselect_menu('categories_ids[]', $categories_array, $categories_array_selected, 'size=1')*/; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo TEXT_PRODUCTS_NAME; ?></td>
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? $products_name[$languages[$i]['id']] : tep_get_products_name($pInfo->products_id, $languages[$i]['id']))); ?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_TAX_CLASS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()"'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price', $pInfo->products_price, 'onKeyUp="updateGross()"'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_gross', $pInfo->products_price, 'OnKeyUp="updateNet()"'); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<script language="javascript"><!--
updateGross();
//--></script>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_INFO; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_info[' . $languages[$i]['id'] . ']', 'soft', '70', '2', (isset($products_info[$languages[$i]['id']]) ? $products_info[$languages[$i]['id']] : tep_get_products_info($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
?>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_DESCRIPTION; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '70', '15', (isset($products_description[$languages[$i]['id']]) ? $products_description[$languages[$i]['id']] : tep_get_products_description($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<tr>
            <td colspan="2" class="main"><hr><?php echo TEXT_PRODUCT_METTA_INFO; ?></td>
          </tr>
<?php
    }
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
         <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>          
          <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_PAGE_TITLE; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_head_title_tag[' . $languages[$i]['id'] . ']', 'soft', '70', '5', (isset($products_head_title_tag[$languages[$i]['id']]) ? $products_head_title_tag[$languages[$i]['id']] : tep_get_products_head_title_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>          
           <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_HEADER_DESCRIPTION; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', 'soft', '70', '5', (isset($products_head_desc_tag[$languages[$i]['id']]) ? $products_head_desc_tag[$languages[$i]['id']] : tep_get_products_head_desc_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>          
           <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_KEYWORDS; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', 'soft', '70', '5', (isset($products_head_keywords_tag[$languages[$i]['id']]) ? $products_head_keywords_tag[$languages[$i]['id']] : tep_get_products_head_keywords_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td colspan="2" class="main"><hr></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_quantity', $pInfo->products_quantity); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MODEL; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_model', $pInfo->products_model); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_IMAGE; ?></td>
        <?php if (HTML_AREA_WYSIWYG_DISABLE == 'Disable') { ?>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('products_image') . '<br>' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . $pInfo->products_image . tep_draw_hidden_field('products_previous_image', $pInfo->products_image); ?></td>
        <?php }else{ ?>
            <td class="main"><?php echo '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="main">' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp; </td><td class="main">' . tep_draw_textarea_field('products_image', 'soft', '70', '2', $pInfo->products_image) . tep_draw_hidden_field('products_previous_image', $pInfo->products_image) . '</td></tr></table>'; ?></td>
        <?php } ?>
          </tr>
		  <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_IMAGE_POP; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('products_image_pop') . '<br>' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . $pInfo->products_image_pop . tep_draw_hidden_field('products_previous_image_pop', $pInfo->products_image_pop); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo TEXT_PRODUCTS_URL . '<br><small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small>'; ?></td>
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_url[' . $languages[$i]['id'] . ']', (isset($products_url[$languages[$i]['id']]) ? $products_url[$languages[$i]['id']] : tep_get_products_url($pInfo->products_id, $languages[$i]['id']))); ?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_weight', $pInfo->products_weight); ?></td>
          </tr>
        </table></td>
      </tr>
<?php
/////////////////////////////////////////////////////////////////////
// BOF: WebMakers.com Added: Draw Attribute Tables
?>
      <tr>
        <td><table border="3" cellspacing="5" cellpadding="2" align="center" bgcolor="000000">
<?php
    $rows = 0;
    $options_query = tep_db_query("select products_options_id, products_options_name from " . TABLE_PRODUCTS_OPTIONS . " where language_id = '" . $languages_id . "' order by products_options_name");
    while ($options = tep_db_fetch_array($options_query)) {
      $values_query = tep_db_query("select pov.products_options_values_id, pov.products_options_values_name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov, " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " p2p where pov.products_options_values_id = p2p.products_options_values_id and p2p.products_options_id = '" . $options['products_options_id'] . "' and pov.language_id = '" . $languages_id . "'");
      $header = false;
      while ($values = tep_db_fetch_array($values_query)) {
        $rows ++;
        if (!$header) {
          $header = true;
?>
          <tr valign="top">
<td><table border="2" cellpadding="2" cellspacing="2" bgcolor="FFFFFF">
              <tr class="dataTableHeadingRow">
              <td colspan="4" class="attributeBoxContent" align="center">Active Attributes</td>
              <td class="attributeBoxContent" width="20">&nbsp;</td>
              <td colspan="5" class="attributeBoxContent" align="center"><font color="FF0000">Coming Soon ...</font></td>
              </tr>
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" width="250" align="left"><?php echo $options['products_options_name']; ?></td>
                <td class="dataTableHeadingContent" width="50" align="center"><?php echo 'Prefix'; ?></td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'Price'; ?></td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'Sort Order'; ?></td>
                <td class="attributeBoxContent" width="20">&nbsp;</td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'One Time Charge'; ?></td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'Weight Prefix'; ?></td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'Weight'; ?></td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'Units'; ?></td>
                <td class="dataTableHeadingContent" width="70" align="center"><?php echo 'Units Price'; ?></td>
              </tr>
<?php
        }
        $attributes = array();
        if (sizeof($HTTP_POST_VARS) > 0) {
          if ($HTTP_POST_VARS['option'][$rows]) {
            $attributes = array(
                                'products_attributes_id' => $HTTP_POST_VARS['option'][$rows],
                                'options_values_price' => $HTTP_POST_VARS['price'][$rows],
                                'price_prefix' => $HTTP_POST_VARS['prefix'][$rows],
                                'products_options_sort_order' => $HTTP_POST_VARS['products_options_sort_order'][$rows],
                                'product_attributes_one_time' => $HTTP_POST_VARS['product_attributes_one_time'][$rows],
                                'products_attributes_weight' => $HTTP_POST_VARS['products_attributes_weight'][$rows],
                                'products_attributes_weight_prefix' => $HTTP_POST_VARS['products_attributes_weight_prefix'][$rows],
                                'products_attributes_units' => $HTTP_POST_VARS['products_attributes_units'][$rows],
                                'products_attributes_units_price' => $HTTP_POST_VARS['products_attributes_units_price'][$rows],
                                );
          }
        } else {
          $attributes_query = tep_db_query("select products_attributes_id, options_values_price, price_prefix, products_options_sort_order, product_attributes_one_time, products_attributes_weight, products_attributes_weight_prefix, products_attributes_units, products_attributes_units_price from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . $pInfo->products_id . "' and options_id = '" . $options['products_options_id'] . "' and options_values_id = '" . $values['products_options_values_id'] . "'");
          if (tep_db_num_rows($attributes_query) > 0) {
            $attributes = tep_db_fetch_array($attributes_query);
          }
        }
?>
              <tr class="dataTableRow">
                <td class="dataTableContent"><?php echo tep_draw_checkbox_field('option[' . $rows . ']', $attributes['products_attributes_id'], $attributes['products_attributes_id']) . '&nbsp;' . $values['products_options_values_name']; ?>&nbsp;</td>
                <td class="dataTableContent" width="50" align="center"><?php echo tep_draw_input_field('prefix[' . $rows . ']', $attributes['price_prefix'], 'size="2"'); ?></td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('price[' . $rows . ']', $attributes['options_values_price'], 'size="7"'); ?></td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('products_options_sort_order[' . $rows . ']', $attributes['products_options_sort_order'], 'size="7"'); ?></td>
                <td class="attributeBoxContent" width="20">&nbsp;</td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('product_attributes_one_time[' . $rows . ']', $attributes['product_attributes_one_time'], 'size="2"'); ?></td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('products_attributes_weight_prefix[' . $rows . ']', $attributes['products_attributes_weight_prefix'], 'size="2"'); ?></td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('products_attributes_weight[' . $rows . ']', $attributes['products_attributes_weight'], 'size="7"'); ?></td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('products_attributes_units[' . $rows . ']', $attributes['products_attributes_units'], 'size="7"'); ?></td>
                <td class="dataTableContent" width="70" align="center"><?php echo tep_draw_input_field('products_attributes_units_price[' . $rows . ']', $attributes['products_attributes_units_price'], 'size="7"'); ?></td>
              </tr>
<?php
      }
      if ($header) {
?>
            </table></td>
<?php
      }
    }
?>
          </tr>
        </table></td>
      </tr>
<?php
// EOF: WebMakers.com Added: Draw Attribute Tables
/////////////////////////////////////////////////////////////////////
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" align="right"><?php echo tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . tep_image_submit('button_preview.gif', IMAGE_PREVIEW) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr>
    </table></form>
    
<?php
//MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 - 2.2 MS2 Products Description HTML - </form>
   if (HTML_AREA_WYSIWYG_DISABLE == 'Disable') {} else { ?>
            <script language="JavaScript1.2" defer>
             var config = new Object();  // create new config object
             config.width = "<?php echo HTML_AREA_WYSIWYG_WIDTH; ?>px";
             config.height = "<?php echo HTML_AREA_WYSIWYG_HEIGHT; ?>px";
             config.bodyStyle = 'background-color: <?php echo HTML_AREA_WYSIWYG_BG_COLOUR; ?>; font-family: "<?php echo HTML_AREA_WYSIWYG_FONT_TYPE; ?>"; color: <?php echo HTML_AREA_WYSIWYG_FONT_COLOUR; ?>; font-size: <?php echo HTML_AREA_WYSIWYG_FONT_SIZE; ?>pt;';
             config.debug = <?php echo HTML_AREA_WYSIWYG_DEBUG; ?>;
          <?php for ($i = 0, $n = sizeof($languages); $i < $n; $i++) { ?>
             editor_generate('products_description[<?php echo $languages[$i]['id']; ?>]',config);
          <?php } ?>
             config.height = "35px";
             config.bodyStyle = 'background-color: white; font-family: Arial; color: black; font-size: 12px;';
             config.toolbar = [ ['InsertImageURL'] ];
             config.OscImageRoot = '<?= trim(HTTP_SERVER . DIR_WS_CATALOG_IMAGES) ?>';
             editor_generate('products_image',config);
            </script>
<?php } ?>

<?php
  } elseif ($action == 'new_product_preview') {
    if (tep_not_null($HTTP_POST_VARS)) {
      $pInfo = new objectInfo($HTTP_POST_VARS);
      $products_name = $HTTP_POST_VARS['products_name'];
	  $products_info = $HTTP_POST_VARS['products_info'];
      $products_description = $HTTP_POST_VARS['products_description'];
      $products_head_title_tag = $HTTP_POST_VARS['products_head_title_tag'];
      $products_head_desc_tag = $HTTP_POST_VARS['products_head_desc_tag'];
      $products_head_keywords_tag = $HTTP_POST_VARS['products_head_keywords_tag'];
      $products_url = $HTTP_POST_VARS['products_url'];
    } else {
      $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_info, pd.products_description, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_head_keywords_tag, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id, p.suppliers_id  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.suppliers_id = '" . (int)$suppliers_id . "'");
      $product = tep_db_fetch_array($product_query);

      $pInfo = new objectInfo($product);
      $products_image_name = $pInfo->products_image;
	  $products_image_name_pop = $pInfo->products_image_pop;
    }

    $form_action = (isset($HTTP_GET_VARS['pID'])) ? 'update_product' : 'insert_product';

    echo tep_draw_form($form_action, FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=' . $form_action, 'post', 'enctype="multipart/form-data"');

    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
      if (isset($HTTP_GET_VARS['read']) && ($HTTP_GET_VARS['read'] == 'only')) {
        $pInfo->products_name = tep_get_products_name($pInfo->products_id, $languages[$i]['id']);
		$pInfo->products_info = tep_get_products_info($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_description = tep_get_products_description($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_head_title_tag = tep_db_prepare_input($products_head_title_tag[$languages[$i]['id']]);
        $pInfo->products_head_desc_tag = tep_db_prepare_input($products_head_desc_tag[$languages[$i]['id']]);
        $pInfo->products_head_keywords_tag = tep_db_prepare_input($products_head_keywords_tag[$languages[$i]['id']]);
        $pInfo->products_url = tep_get_products_url($pInfo->products_id, $languages[$i]['id']);
      } else {
        $pInfo->products_name = tep_db_prepare_input($products_name[$languages[$i]['id']]);
		$pInfo->products_info = tep_db_prepare_input($products_info[$languages[$i]['id']]);
        $pInfo->products_description = tep_db_prepare_input($products_description[$languages[$i]['id']]);
        $pInfo->products_head_title_tag = tep_db_prepare_input($products_head_title_tag[$languages[$i]['id']]);
        $pInfo->products_head_desc_tag = tep_db_prepare_input($products_head_desc_tag[$languages[$i]['id']]);
        $pInfo->products_head_keywords_tag = tep_db_prepare_input($products_head_keywords_tag[$languages[$i]['id']]);
        $pInfo->products_url = tep_db_prepare_input($products_url[$languages[$i]['id']]);
      }
	  $selected_catids = $HTTP_POST_VARS['categories_ids'];
	if ($selected_catids){
		//create the sql statement
			$product_categories_query= "SELECT categories_id as id, categories_name as text FROM categories_description WHERE ("; 
			$selected_catids_size = count($selected_catids);
			foreach ($selected_catids as $current_category_id)
			{
			$product_categories_query .= "categories_id=".$current_category_id;
			$selected_catids_size--;
			if ($selected_catids_size)
				$product_categories_query .= " or ";
			}
			$product_categories_query .= " ) and language_id=".$languages_id;
		// execute the sql statement
			$product_categories_query_result = tep_db_query($product_categories_query);
	
		$categories_array = array(array('id' => '', 'text' => TEXT_NONE));
		$count=0;
		while ($product_categories = tep_db_fetch_array($product_categories_query_result)){
			$categories_array[$count]['id'] = $product_categories["id"];
			$categories_array[$count]['text'] = $product_categories["text"];
			$count++;
		}
		$selected_catids_size = count($selected_catids);
	}  
	?>
	<table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td class="pageHeading">
	<?php if ($selected_catids) { 
		print(TEXT_CATEGORIES."<br>");
		print(tep_draw_mselect_menu('categories_ids[]', $categories_array, $categories_array, 'size='.$selected_catids_size));
	} ?>
		</td>
	</tr>
	</table>
?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . $pInfo->products_name; ?></td>
            <td class="pageHeading" align="right"><?php echo $currencies->format($pInfo->products_price); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $products_image_name, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"') . $pInfo->products_description; ?></td>
      </tr>
	  <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>

          <tr>
            <td class="main"><b><u><?php echo sprintf(TEXT_PRODUCTS_INFO); ?></u></b></td>
            <td class="main" align="right"></td>
          </tr>
        

      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo $pInfo->products_info; ?></td>
      </tr>
<tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<?php
      if ($pInfo->products_url) {
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo sprintf(TEXT_PRODUCT_MORE_INFORMATION, $pInfo->products_url); ?></td>
      </tr>
<?php
      }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<?php
      if ($pInfo->products_date_available > date('Y-m-d')) {
?>
      <tr>
        <td align="center" class="smallText"><?php echo sprintf(TEXT_PRODUCT_DATE_AVAILABLE, tep_date_long($pInfo->products_date_available)); ?></td>
      </tr>
<?php
      } else {
?>
      <tr>
        <td align="center" class="smallText"><?php echo sprintf(TEXT_PRODUCT_DATE_ADDED, tep_date_long($pInfo->products_date_added)); ?></td>
      </tr>
<?php
      }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<?php
    }

    if (isset($HTTP_GET_VARS['read']) && ($HTTP_GET_VARS['read'] == 'only')) {
      if (isset($HTTP_GET_VARS['origin'])) {
        $pos_params = strpos($HTTP_GET_VARS['origin'], '?', 0);
        if ($pos_params != false) {
          $back_url = substr($HTTP_GET_VARS['origin'], 0, $pos_params);
          $back_url_params = substr($HTTP_GET_VARS['origin'], $pos_params + 1);
        } else {
          $back_url = $HTTP_GET_VARS['origin'];
          $back_url_params = '';
        }
      } else {
        $back_url = FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS;
        $back_url_params = 'cPath=' . $cPath . '&pID=' . $pInfo->products_id;
      }
?>
      <tr>
        <td align="right"><?php echo '<a href="' . tep_href_link($back_url, $back_url_params, 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>
<?php
    } else {
?>
      <tr>
        <td align="right" class="smallText">
<?php
/////////////////////////////////////////////////////////////////////
// BOF: WebMakers.com Added: Original Code No longer used
// Code has been left in to show how additional products_descriptions could be added
if (false) {
/* Re-Post all POST'ed variables */
      reset($HTTP_POST_VARS);
      while (list($key, $value) = each($HTTP_POST_VARS)) {
        if (!is_array($HTTP_POST_VARS[$key])) {
          echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
        }
      }
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        echo tep_draw_hidden_field('products_name[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_name[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_info[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_info[$languages[$i]['id']])));
		echo tep_draw_hidden_field('products_description[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_description[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_title_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_title_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_desc_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_keywords_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_url[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_url[$languages[$i]['id']])));
      }
} // false
// EOF: WebMakers.com Added: Original Code
/////////////////////////////////////////////////////////////////////
?>

<?php
/////////////////////////////////////////////////////////////////////
// BOF: WebMakers.com Added: Modified to include Attributes Code
/* Re-Post all POST'ed variables */
      reset($HTTP_POST_VARS);
      while (list($key, $value) = each($HTTP_POST_VARS)) {
        if (is_array($value)) {
          while (list($k, $v) = each($value)) {
            echo tep_draw_hidden_field($key . '[' . $k . ']', htmlspecialchars(stripslashes($v)));
          }
        } else {
          echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
        }
      }
// EOF: WebMakers.com Added: Modified to include Attributes Code
/////////////////////////////////////////////////////////////////////
      echo tep_draw_hidden_field('products_image', stripslashes($products_image_name));
	  echo tep_draw_hidden_field('products_image_pop', stripslashes($products_image_pop_name));

      echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"') . '&nbsp;&nbsp;';

      if (isset($HTTP_GET_VARS['pID'])) {
        echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
      } else {
        echo tep_image_submit('button_insert.gif', IMAGE_INSERT);
      }
      echo '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
?></td>
      </tr>
    </table></form>
<?php
    }
  } else {
?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <!--<tr>
            <td class="pageHeading"><?php //echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td class="smallText" align="right">
<?php
    echo tep_draw_form('search', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, '', 'get');
	echo tep_draw_hidden_field('IXAdminID', $IXAdminID);
    echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search');
    echo '</form>';
?>
                </td>
              </tr>
              <tr>
                <td class="smallText" align="right">
<?php
/*    echo tep_draw_form('goto', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, '', 'get');
	$categories_of_suppliers_query = tep_db_query("select categories_id, suppliers_id from " . TABLE_CATEGORIES_TO_SUPPLIERS . " where suppliers_id = '" . (int)$suppliers_id . "'");
	$array = array('id' => '', 'text' => '');
	while ($categories_of_suppliers = tep_db_fetch_array($categories_of_suppliers_query)){
		$array = array_merge($array, tep_get_category_tree($categories_of_suppliers['categories_id']));
	}
    echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPath', $array, $current_category_id, 'onChange="this.form.submit();"');
	echo '</form>';
*/
?>
                </td>
              </tr>
            </table></td>
          </tr>-->
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
			  <!--BOF - Added code for Admin Sort by products model---->
<td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
<!--EOF - Added code for Admin Sort by products model---->
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $categories_count = 0;
    $rows = 0;
    if (isset($HTTP_GET_VARS['search'])) {
      $search = tep_db_prepare_input($HTTP_GET_VARS['search']);

      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd , " . TABLE_CATEGORIES_TO_SUPPLIERS . " tcs where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and cd.categories_name like '%" . tep_db_input($search) . "%' and tcs.categories_id = c.categories_id and tcs.suppliers_id = '" . (int)$suppliers_id . "' order by c.sort_order, cd.categories_name");
    } else {
//      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, tcs.suppliers_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES_TO_SUPPLIERS ." tcs where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and tcs.suppliers_id = '" . $suppliers_id . "' and c.categories_id = tcs.categories_id" . " order by c.sort_order, cd.categories_name");
//	  $categories_suppliers_query = tep_db_query("select c.categories_id, c.parent_id, cs.categories_id, cs.suppliers_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_TO_SUPPLIERS . " cs where c.categories_id = cs.categories_id and cs.suppliers_id = '" . (int)$suppliers_id . "'");
//	  $categories_suppliers = tep_db_fetch_array($categories_suppliers_query);
//	  print_r($categories_suppliers);	  
//	  if ($categories_suppliers['parent_id'] == 0){
	      if ($current_category_id == 0)
		  	$categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, cd.categories_id, c.sort_order, c.date_added, c.last_modified, cts.categories_id, cts.suppliers_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES_TO_SUPPLIERS . " cts where c.categories_id = cd.categories_id and c.categories_id = cts.categories_id and cts.suppliers_id = '" . (int)$suppliers_id . "' order by c.sort_order, cd.categories_name");
		  else
		    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd " ." where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");	  
//		  $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, tcs.suppliers_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd, " . TABLE_CATEGORIES_TO_SUPPLIERS ." tcs where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and tcs.suppliers_id = '" . $suppliers_id . "' and c.categories_id = tcs.categories_id" . " order by c.sort_order, cd.categories_name");		  
//	  }  		
//    }
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_count++;
      $rows++;
	   
// Get parent_id for subcategories if search
      if (isset($HTTP_GET_VARS['search'])) $cPath= $categories['parent_id'];

      if ((!isset($HTTP_GET_VARS['cID']) && !isset($HTTP_GET_VARS['pID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
        $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
        $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

        $cInfo_array = array_merge($categories, $category_childs, $category_products);
        $cInfo = new objectInfo($cInfo_array);
      }

      if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, tep_get_path($categories['categories_id'])) . '\'">' . "\n";
      } else {
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
      }
?>
                <!--BOF - Added code for Admin Sort by products model---->
<td class="dataTableContent" width="5%" nowrap></td>
<!--EOF - Added code for Admin Sort by products model---->
				<td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>&nbsp;<b>' . $categories['categories_name'] . '</b>'; ?></td>
                <td class="dataTableContent" align="center">&nbsp;</td>
                <td class="dataTableContent" align="right">&nbsp;&nbsp;<?php if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
	}

    $products_count = 0;
    if (isset($HTTP_GET_VARS['search'])) {
      $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_model, p2c.categories_id, p.suppliers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and pd.products_name like '%" . tep_db_input($search) . "%' and p.suppliers_id = '" . (int)$suppliers_id . "' order by p.products_model");
    } else {
      $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.products_model, p.suppliers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "' and p.suppliers_id = '" . (int)$suppliers_id ."' order by p.Products_model");
    }
    while ($products = tep_db_fetch_array($products_query)) {
      $products_count++;
      $rows++;

// Get categories_id for product if search
      if (isset($HTTP_GET_VARS['search'])) $cPath = $products['categories_id'];

      if ( (!isset($HTTP_GET_VARS['pID']) && !isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['pID']) && ($HTTP_GET_VARS['pID'] == $products['products_id']))) && !isset($pInfo) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
// find out the rating average from customer reviews
        $reviews_query = tep_db_query("select (avg(reviews_rating) / 5 * 100) as average_rating from " . TABLE_REVIEWS . " where products_id = '" . (int)$products['products_id'] . "'");
        $reviews = tep_db_fetch_array($reviews_query);
        $pInfo_array = array_merge($products, $reviews);
        $pInfo = new objectInfo($pInfo_array);
      }

      if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id) ) {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview&read=only') . '\'">' . "\n";
      } else {
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products['products_id']) . '\'">' . "\n";
      }
?>
                <!--BOF - Added code for Admin Sort by products model---->
<td class="dataTableContent" width="5%" nowrap><?php echo '&nbsp;' . $products['products_model']; ?>&nbsp;</td>
<!--EOF - Added code for Admin Sort by products model---->
				<td class="dataTableContent"><?php echo '&nbsp;' . $products['products_name']; ?></td>
                <td class="dataTableContent" align="center">
<?php
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=setflag&flag=0&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=setflag&flag=1&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
                <td class="dataTableContent" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product') . '">' . tep_image(DIR_WS_ICONS . 'edit.gif', ICON_PREVIEW) . '</a>'; ?>&nbsp;<?php echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview&read=only') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>'; ?>&nbsp;<?php if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }

    $cPath_back = '';
    if (sizeof($cPath_array) > 0) {
      for ($i=0, $n=sizeof($cPath_array)-1; $i<$n; $i++) {
        if (empty($cPath_back)) {
          $cPath_back .= $cPath_array[$i];
        } else {
          $cPath_back .= '_' . $cPath_array[$i];
        }
      }
    }

    $cPath_back = (tep_not_null($cPath_back)) ? 'cPath=' . $cPath_back . '&' : '';
?>
              <tr>
                <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText"><?php echo TEXT_CATEGORIES . '&nbsp;' . $categories_count . '<br>' . TEXT_PRODUCTS . '&nbsp;' . $products_count; ?></td>
                    <td align="right" class="smallText"><?php if (sizeof($cPath_array) > 0) echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, $cPath_back . 'cID=' . $current_category_id) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>&nbsp;'; if (!isset($HTTP_GET_VARS['search'])) if($current_category_id != 0) echo '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&action=new_category') . '">' . tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY) . '</a>&nbsp;<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&action=new_product') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>'; ?>&nbsp;</td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
    $heading = array();
    $contents = array();
    switch ($action) {
      case 'new_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('newcategory', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=insert_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']');
        }

        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_NAME . $category_inputs_string);
        if (HTML_AREA_WYSIWYG_DISABLE == 'Disable') {
          $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
        }else{
          $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_textarea_field('categories_image', 'soft', '30', '1', $cInfo->categories_image));
        }
        $contents[] = array('text' => '<br>' . TEXT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', '', 'size="2"'));

// RJW Begin Meta Tags Code
        $contents[] = array('text' => '<br>' . TEXT_META_TITLE . '<br>' . tep_draw_input_field('meta_title'));
        $contents[] = array('text' => '<br>' . TEXT_META_DESCRIPTION . '<br>' . tep_draw_input_field('meta_description'));
        $contents[] = array('text' => '<br>' . TEXT_META_KEYWORDS . '<br>' . tep_draw_input_field('meta_keywords'));
// RJW End Meta Tags Code

		$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'edit_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=update_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_EDIT_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id']));
        }

// RJW Begin Meta Tags Code
$meta_query = tep_db_query ("select title, keywords, description from " . TABLE_META_TAGS . " where categories_id = '" . $cInfo->categories_id . "'");
$meta = tep_db_fetch_array($meta_query);
// RJW End Meta Tags Code


        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<br>' . tep_image(DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name) . '<br>' . DIR_WS_CATALOG_IMAGES . '<br><b>' . $cInfo->categories_image . '</b>');
        if (HTML_AREA_WYSIWYG_DISABLE == 'Disable') {
          $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
        }else{
          $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_textarea_field('categories_image', 'soft', '30', '1', $cInfo->categories_image));
        }
        $contents[] = array('text' => '<br>' . TEXT_EDIT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));

// RJW Begin Meta Tags Code
        $contents[] = array('text' => '<br>' . TEXT_META_TITLE . '<br>' . tep_draw_input_field('meta_title', $meta ['title']));
        $contents[] = array('text' => '<br>' . TEXT_META_DESCRIPTION . '<br>' . tep_draw_input_field('meta_description', $meta ['description']));
        $contents[] = array('text' => '<br>' . TEXT_META_KEYWORDS . '<br>' . tep_draw_input_field('meta_keywords', $meta ['keywords']));
 // RJW End Meta Tags Code

		$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'delete_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=delete_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
        $contents[] = array('text' => '<br><b>' . $cInfo->categories_name . '</b>');
        if ($cInfo->childs_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count));
        if ($cInfo->products_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $cInfo->products_count));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'move_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=move_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name));
        $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $cInfo->categories_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;

      case 'delete_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=delete_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => TEXT_DELETE_PRODUCT_INTRO);
        $contents[] = array('text' => '<br><b>' . $pInfo->products_name . '</b>');

        $product_categories_string = '';
        $product_categories = tep_generate_category_path($pInfo->products_id, 'product');
        for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
          $category_path = '';
          for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
            $category_path .= $product_categories[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
          }
          $category_path = substr($category_path, 0, -16);
          $product_categories_string .= tep_draw_checkbox_field('product_categories[]', $product_categories[$i][sizeof($product_categories[$i])-1]['id'], true) . '&nbsp;' . $category_path . '<br>';
        }
        $product_categories_string = substr($product_categories_string, 0, -4);

        $contents[] = array('text' => '<br>' . $product_categories_string);
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'move_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_PRODUCT . '</b>');

$contents = array('form' => tep_draw_form('products', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=move_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
      $contents[] = array('text' => sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
      $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'new_images':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_IMAGES . '</b>');  
                                                                                                                                                                                                                                                                                                      
      $contents = array('form' => tep_draw_form('new_images', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=add_images&cPath=' . $cPath . '&pID=' . $HTTP_GET_VARS['pID'], 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_NEW_IMAGES_INTRO);      
      $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_IMAGES_DESC . '<br>' . tep_draw_input_field('images_description'));
      $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_IMAGES_NEWPOP . $newpop_resol . '<br>' . tep_draw_file_field('popup_images'));
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $HTTP_GET_VARS['pID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');    
      break;                                                                                  
    case 'delete_images':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_IMAGES . '</b>');            
      $contents = array('form' => tep_draw_form('delete_images', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=del_images&cPath=' . $cPath . '&pID=' . $HTTP_GET_VARS['pID']));
      $contents[] = array('text' => TEXT_DEL_IMAGES_INTRO);      
                                                                                                                                                                                                                                                                                                      
      $images_product = tep_db_query("SELECT additional_images_id, images_description FROM " . TABLE_ADDITIONAL_IMAGES . " where products_id = '" . $HTTP_GET_VARS['pID'] . "'");
      if (!tep_db_num_rows($images_product)) {                                                                                                                                                                                                                                                        
        $contents[] = array('align' => 'center', 'text' => '<br><font color="red">No Additional Images!</font>');  
        $contents[] = array('align' => 'center', 'text' => '<br><a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $HTTP_GET_VARS['pID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');  
      } else {                                                                                                                                                                                                                                                                                        
        while ($new_images = tep_db_fetch_array($images_product)) {                                                                                                                                                                                                                                  
          $contents[] = array('text' => '&nbsp;' . tep_draw_checkbox_field('additional_images_id[]', $new_images['additional_images_id'], true) . $new_images['images_description']);    
        }                                                                                                                                                                                                                                                                                            
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $HTTP_GET_VARS['pID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');  
      }                                                                                                                                                                                                                                                                                              
      break;
    case 'copy_to':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_COPY_TO . '</b>');

      $contents = array('form' => tep_draw_form('copy_to', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=copy_to_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
      $contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
      $contents[] = array('text' => '<br>' . TEXT_CATEGORIES . '<br>' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id));
      $contents[] = array('text' => '<br>' . TEXT_HOW_TO_COPY . '<br>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '<br>' . tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE);
// BOF: Attributes copy
// WebMakers.com Added: Attributes Copy
      $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
      $contents[] = array('text' => '<br>' . TEXT_COPY_ATTRIBUTES_ONLY);
      $contents[] = array('text' => '<br>' . TEXT_COPY_ATTRIBUTES . '<br>' . tep_draw_radio_field('copy_attributes', 'copy_attributes_yes', true) . ' ' . TEXT_COPY_ATTRIBUTES_YES . '<br>' . tep_draw_radio_field('copy_attributes', 'copy_attributes_no') . ' ' . TEXT_COPY_ATTRIBUTES_NO);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', IMAGE_COPY) . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
     $contents[] = array('align' => 'center', 'text' => '<br>' . ATTRIBUTES_NAMES_HELPER . '<br>' . tep_draw_separator('pixel_trans.gif', '1', '10'));
// EOF: Attributes copy
      break;
// BOF: Attributes copy to existing product:
// WebMakers.com Added: Copy Attributes Existing Product to another Existing Product
    case 'copy_product_attributes':
      $copy_attributes_delete_first='1';
      $copy_attributes_duplicates_skipped='1';
      $copy_attributes_duplicates_overwrite='0';

      if (DOWNLOAD_ENABLED == 'true') {
        $copy_attributes_include_downloads='1';
        $copy_attributes_include_filename='1';
      } else {
        $copy_attributes_include_downloads='0';
        $copy_attributes_include_filename='0';
      }

      $heading[] = array('text' => '<b>' . 'Copy Attributes to another product' . '</b>');
      $contents = array('form' => tep_draw_form('products', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=create_copy_product_attributes&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . tep_draw_hidden_field('products_id', $pInfo->products_id) . tep_draw_hidden_field('products_name', $pInfo->products_name));
      $contents[] = array('text' => '<br>Copying Attributes from #' . $pInfo->products_id . '<br><b>' . $pInfo->products_name . '</b>');
      $contents[] = array('text' => 'Copying Attributes to #&nbsp;' . tep_draw_input_field('copy_to_products_id', $copy_to_products_id, 'size="3"'));
      $contents[] = array('text' => '<br>Delete ALL Attributes and Downloads before copying&nbsp;' . tep_draw_checkbox_field('copy_attributes_delete_first',$copy_attributes_delete_first, 'size="2"'));
      $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
      $contents[] = array('text' => '<br>' . 'Otherwise ...');
      $contents[] = array('text' => 'Duplicate Attributes should be skipped&nbsp;' . tep_draw_checkbox_field('copy_attributes_duplicates_skipped',$copy_attributes_duplicates_skipped, 'size="2"'));
      $contents[] = array('text' => '&nbsp;&nbsp;&nbsp;Duplicate Attributes should be overwritten&nbsp;' . tep_draw_checkbox_field('copy_attributes_duplicates_overwrite',$copy_attributes_duplicates_overwrite, 'size="2"'));
      if (DOWNLOAD_ENABLED == 'true') {
        $contents[] = array('text' => '<br>Copy Attributes with Downloads&nbsp;' . tep_draw_checkbox_field('copy_attributes_include_downloads',$copy_attributes_include_downloads, 'size="2"'));
        // Not used at this time - download name copies if download attribute is copied
        // $contents[] = array('text' => '&nbsp;&nbsp;&nbsp;Include Download Filenames&nbsp;' . tep_draw_checkbox_field('copy_attributes_include_filename',$copy_attributes_include_filename, 'size="2"'));
      }
      $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
      $contents[] = array('align' => 'center', 'text' => '<br>' . PRODUCT_NAMES_HELPER);
      if ($pID) {
        $contents[] = array('align' => 'center', 'text' => '<br>' . ATTRIBUTES_NAMES_HELPER);
      } else {
       $contents[] = array('align' => 'center', 'text' => '<br>Select a product for display');
      }
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', 'Copy Attribtues') . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
// WebMakers.com Added: Copy Attributes Existing Product to All Products in Category
    case 'copy_product_attributes_categories':
      $copy_attributes_delete_first='1';
      $copy_attributes_duplicates_skipped='1';
      $copy_attributes_duplicates_overwrite='0';

      if (DOWNLOAD_ENABLED == 'true') {
        $copy_attributes_include_downloads='1';
        $copy_attributes_include_filename='1';
      } else {
        $copy_attributes_include_downloads='0';
        $copy_attributes_include_filename='0';
      }
    $heading[] = array('text' => '<b>' . 'Copy Product Attributes to Category ...' . '</b>');
      $contents = array('form' => tep_draw_form('products', FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'action=create_copy_product_attributes_categories&cPath=' . $cPath . '&cID=' . $cID . '&make_copy_from_products_id=' . $copy_from_products_id));
      $contents[] = array('text' => 'Copy Product Attributes from Product ID#&nbsp;' . tep_draw_input_field('make_copy_from_products_id', $make_copy_from_products_id, 'size="3"'));
      $contents[] = array('text' => '<br>Copying to all products in Category ID#&nbsp;' . $cID . '<br>Category Name: <b>' . tep_get_category_name($cID, $languages_id) . '</b>');
      $contents[] = array('text' => '<br>Delete ALL Attributes and Downloads before copying&nbsp;' . tep_draw_checkbox_field('copy_attributes_delete_first',$copy_attributes_delete_first, 'size="2"'));
      $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
      $contents[] = array('text' => '<br>' . 'Otherwise ...');
      $contents[] = array('text' => 'Duplicate Attributes should be skipped&nbsp;' . tep_draw_checkbox_field('copy_attributes_duplicates_skipped',$copy_attributes_duplicates_skipped, 'size="2"'));
      $contents[] = array('text' => '&nbsp;&nbsp;&nbsp;Duplicate Attributes should be overwritten&nbsp;' . tep_draw_checkbox_field('copy_attributes_duplicates_overwrite',$copy_attributes_duplicates_overwrite, 'size="2"'));
      if (DOWNLOAD_ENABLED == 'true') {
        $contents[] = array('text' => '<br>Copy Attributes with Downloads&nbsp;' . tep_draw_checkbox_field('copy_attributes_include_downloads',$copy_attributes_include_downloads, 'size="2"'));
       // Not used at this time - download name copies if download attribute is copied
        // $contents[] = array('text' => '&nbsp;&nbsp;&nbsp;Include Download Filenames&nbsp;' . tep_draw_checkbox_field('copy_attributes_include_filename',$copy_attributes_include_filename, 'size="2"'));
      }
      $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
      $contents[] = array('align' => 'center', 'text' => '<br>' . PRODUCT_NAMES_HELPER);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', 'Copy Attribtues') . ' <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cID) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
// EOF: Attributes copy
    default:
      if ($rows > 0) {
        if (isset($cInfo) && is_object($cInfo)) { // category info box contents
          $heading[] = array('text' => '<b>' . $cInfo->categories_name . '</b>');

//          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=edit_category') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=delete_category') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=move_category') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>');
          $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added));
          if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified));
          $contents[] = array('text' => '<br>' . tep_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT) . '<br>' . $cInfo->categories_image);
          $contents[] = array('text' => '<br>' . TEXT_SUBCATEGORIES . ' ' . $cInfo->childs_count . '<br>' . TEXT_PRODUCTS . ' ' . $cInfo->products_count);
//BOF: Attributes copy to all existing products
          if ($cInfo->childs_count==0 and $cInfo->products_count >= 1) {
// WebMakers.com Added: Copy Attributes Existing Product to All Existing Products in Category
            $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
            if ($cID) {
              $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&cID=' . $cID . '&action=copy_product_attributes_categories') . '">' . 'Copy Product Attributes to <br>ALL products in Category: ' . tep_get_category_name($cID, $languages_id) . '<br>' . tep_image_button('button_copy_to.gif', 'Copy Attributes') . '</a>');
            } else {
              $contents[] = array('align' => 'center', 'text' => '<br>Select a Category to copy attributes to');
            }
          }
//EOF: Attributes copy to all existing products
        } elseif (isset($pInfo) && is_object($pInfo)) { // product info box contents
          $heading[] = array('text' => '<b>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</b>');

          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=new_product') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=delete_product') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=move_product') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=copy_to') . '">' . tep_image_button('button_copy_to.gif', IMAGE_COPY_TO) . '</a>');
          $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($pInfo->products_date_added));
          if (tep_not_null($pInfo->products_last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($pInfo->products_last_modified));
          if (date('Y-m-d') < $pInfo->products_date_available) $contents[] = array('text' => TEXT_DATE_AVAILABLE . ' ' . tep_date_short($pInfo->products_date_available));
          $contents[] = array('text' => '<br>' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<br>' . $pInfo->products_image);
          $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_PRICE_INFO . ' ' . $currencies->format($pInfo->products_price) . '<br>' . TEXT_PRODUCTS_QUANTITY_INFO . ' ' . $pInfo->products_quantity);
          $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_AVERAGE_RATING . ' ' . number_format($pInfo->average_rating, 2) . '%');
// BOF: Attributes copy existing to existing
// WebMakers.com Added: Copy Attributes Existing Product to another Existing Product
          $contents[] = array('text' => '<br>' . tep_image(DIR_WS_IMAGES . 'pixel_black.gif','','100%','3'));
          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=copy_product_attributes') . '">' . 'Products Attributes Copier:<br>' . tep_image_button('button_copy_to.gif', 'Copy Attributes') . '</a>');
          if ($pID) {
            $contents[] = array('align' => 'center', 'text' => '<br>' . ATTRIBUTES_NAMES_HELPER . '<br>' . tep_draw_separator('pixel_trans.gif', '1', '10'));
          } else {
            $contents[] = array('align' => 'center', 'text' => '<br>Select a product to display attributes');
          }
//EOF: Attributes copy existing to existing
  $contents[] = array('text' => '<br><b>' . TEXT_INFO_HEADING_NEW_IMAGES . '</b><hr>');
  
   $images_product = tep_db_query("SELECT additional_images_id, popup_images, images_description FROM " . TABLE_ADDITIONAL_IMAGES . " where products_id = '" . $pInfo->products_id . "'");
          if (!tep_db_num_rows($images_product)) {
            $contents[] = array('align' => 'center', 'text' => '<font color="red">No Additional Images!</font><hr>');
          } else {
            while ($new_images = tep_db_fetch_array($images_product)) {
             $contents[] = array('text' => '&nbsp;' . tep_image(DIR_WS_CATALOG_IMAGES  . $new_images['popup_images'], $new_images['images_description'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="absmiddle"') . '<br><br>&nbsp;<hr>');
            }
          }
          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=new_images') . '">' . tep_image_button('button_images_add.gif', IMAGE_ADDITIONAL_NEW) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=delete_images') . '">' . tep_image_button('button_images_del.gif', IMAGE_ADDITIONAL_DEL) . '</a>');
        }
      } else { // create category/product info
        $heading[] = array('text' => '<b>' . EMPTY_CATEGORY . '</b>');

        $contents[] = array('text' => TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS);
      }
      break;
    }

    if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
      echo '            <td width="25%" valign="top">' . "\n";

      $box = new box;
      echo $box->infoBox($heading, $contents);

      echo '            </td>' . "\n";

      // Add neccessary JS for WYSIWYG editor of category image
      if(($action=='edit_category') or ($action=='new_category'))    {
        if (HTML_AREA_WYSIWYG_DISABLE != 'Disable'){
          echo '
                  <script language="JavaScript1.2" defer>
                  var config = new Object();  // create new config object
                  config.width  = "250px";
                  config.height = "35px";
                  config.bodyStyle = "background-color: white; font-family: Arial; color: black; font-size: 12px;";
                  config.debug = ' . HTML_AREA_WYSIWYG_DEBUG . ';
                  config.toolbar = [ ["InsertImageURL"] ];
                  config.OscImageRoot = "' . trim(HTTP_SERVER . DIR_WS_CATALOG_IMAGES) . '";
                  editor_generate("categories_image",config);
                 </script>
               ';
        }        
      }

    }
?>
          </tr>
        </table></td>
      </tr>
    </table>
<?php
  }
?>
    </td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
