<?php
/* $Id: categories.php,v 1.146 2006/09/11 14:40:27 hpdl Exp $

adapted for Separate Pricing Per Customer v4.1.1 2005/03/20

  IntenseCart, E-Commerce and Internet Marketing Solutions
  http://www.intensecart.com

  Copyrights (c) 2006 IntenseCart Inc.

  Redistribution without explicit written consent is forbidden under US and International Copyright Laws.

*/

  require('includes/application_top.php');

  // include the breadcrumb class and start the breadcrumb trail
  require(DIR_WS_CLASSES . 'breadcrumb.php');
  $breadcrumb = new breadcrumb;


  $pClass='product_giftcert';

  if (!isset($cPath_array) || sizeof($cPath_array)!=1) {
    $cat_row=tep_db_fetch_array(tep_db_query("select * from ".TABLE_CATEGORIES." where parent_id=0 AND products_class='$pClass'"));
    if (!$cat_row) {
      tep_db_query("INSERT INTO ".TABLE_CATEGORIES." (parent_id,date_added,categories_status,products_class) VALUES (0,NOW(),1,'$pClass')");
      $cat_row=Array('categories_id'=>tep_db_insert_id());
      tep_db_query("INSERT INTO ".TABLE_CATEGORIES_DESCRIPTION." (categories_id,categories_name,language_id) VALUES ('".$cat_row['categories_id']."','Gift Certificates','$languages_id')");
    }
    tep_redirect(HTTP_SERVER.DIR_WS_ADMIN.'gift_certs.php?cPath='.$cat_row['categories_id']);
  }


  // add category names to the breadcrumb trail
  if (isset($cPath_array)) {
    for ($i=0, $n=sizeof($cPath_array); $i<$n; $i++) {
      $categories_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . (int)$cPath_array[$i] . "' and language_id = '" . (int)$languages_id . "'");
      if (tep_db_num_rows($categories_query) > 0) {
        $categories = tep_db_fetch_array($categories_query);
        $breadcrumb->add($categories['categories_name'], tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . implode('_', array_slice($cPath_array, 0, ($i+1)))));
      } else {
        break;
      }
    }
  }

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

// By MegaJim
  require(DIR_WS_CLASSES . 'url_rewrite.php');
  $url_rewrite = new url_rewrite();

  $ModelFieldsList=Array('quantity');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');
  
  $cus_groups=tep_get_customer_groups();

  if (tep_not_null($action)) {

    if ($action=='new_product_preview' && (isset($HTTP_POST_VARS['express_update']) || isset($HTTP_POST_VARS['express_update_x']))) {
      $action = (isset($HTTP_GET_VARS['pID'])) ? 'update_product' : 'insert_product';
			$express_update = true;
	  }


function get_upload_file($fld) {
  global $UploadCache;
  if (!isset($UploadCache)) $UploadCache=Array();
  if (!isset($UploadCache[$fld])) {
    $model_image_obj = new upload($fld);
    $model_image_obj->set_destination(DIR_FS_CATALOG_IMAGES);
    $UploadCache[$fld] = ($model_image_obj->parse() && $model_image_obj->save())?$model_image_obj->filename:'';
  }
//echo 'get_upload_file('.$fld.")=".$UploadCache[$fld]."\n";
  return $UploadCache[$fld];
}



// Upload Product Images
   if (isset($HTTP_POST_VARS['upload_products_image']) || isset($_FILES['upload_products_image'])) {
     if (($HTTP_POST_VARS['unlink_image'] == 'yes') or ($HTTP_POST_VARS['delete_image'] == 'yes')) {
        $products_image = '';
        $products_image_name = '';
        } elseif (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') {
        $products_image = new upload('upload_products_image');
        $products_image->set_destination(DIR_FS_CATALOG_IMAGES);
        if ($products_image->parse() && $products_image->save()) {
          $products_image_name = $products_image->filename;
        } else {
          $products_image_name = (isset($HTTP_POST_VARS['products_previous_image']) ? $HTTP_POST_VARS['products_previous_image'] : '');
        }
        } else {
          if (isset($HTTP_POST_VARS['upload_products_image']) && tep_not_null($HTTP_POST_VARS['upload_products_image']) && ($HTTP_POST_VARS['upload_products_image'] != 'none')) {
            $products_image_name = $HTTP_POST_VARS['upload_products_image'];
          } else {
            $products_image_name = (isset($HTTP_POST_VARS['products_previous_image']) ? $HTTP_POST_VARS['products_previous_image'] : '');
          }
        }
   }
   $product_image_xl=Array();
   $products_image_xl_name=Array();
   for ($i=1;$i<=4;$i++) {
     if (isset($HTTP_POST_VARS['upload_products_image_xl_'.$i]) || isset($_FILES['upload_products_image_xl_'.$i])) {
//       echo "Upload $i: ".$HTTP_POST_VARS['upload_products_image_xl_'.$i]."\n";
       if (($HTTP_POST_VARS['unlink_image_xl_'.$i] == 'yes') or ($HTTP_POST_VARS['delete_image_xl_'.$i] == 'yes')) {
        $products_image_xl[$i] = '';
        $products_image_xl_name[$i] = '';
        } elseif (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') {
        $products_image_xl[$i] = new upload('upload_products_image_xl_'.$i);
        $products_image_xl[$i]->set_destination(DIR_FS_CATALOG_IMAGES);
        if ($products_image_xl[$i]->parse() && $products_image_xl[$i]->save()) {
          $products_image_xl_name[$i] = $products_image_xl[$i]->filename;
        } else {
          $products_image_xl_name[$i] = (isset($HTTP_POST_VARS['products_previous_image_xl_'.$i]) ? $HTTP_POST_VARS['products_previous_image_xl_'.$i] : '');
        }
        } else {
          if (isset($HTTP_POST_VARS['upload_products_image_xl_'.$i]) && tep_not_null($HTTP_POST_VARS['upload_products_image_xl_'.$i]) && ($HTTP_POST_VARS['upload_products_image_xl_'.$i] != 'none')) {
            $products_image_xl_name[$i] = $HTTP_POST_VARS['upload_products_image_xl_'.$i];
          } else {
            $products_image_xl_name[$i] = (isset($HTTP_POST_VARS['products_previous_image_xl_'.$i]) ? $HTTP_POST_VARS['products_previous_image_xl_'.$i] : '');
          }
        }
      }
    }


    switch ($action) {
      case 'setflag':
        if ( ($HTTP_GET_VARS['flag'] == '0') || ($HTTP_GET_VARS['flag'] == '1') ) {
          if (isset($HTTP_GET_VARS['pID'])) {
            tep_set_product_status($HTTP_GET_VARS['pID'], $HTTP_GET_VARS['flag']);
          } else if (isset($HTTP_GET_VARS['cID'])) {
            tep_set_category_status($HTTP_GET_VARS['cID'], $HTTP_GET_VARS['flag']);
	  }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }
        }

//        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $HTTP_GET_VARS['cPath'] . '&pID=' . $HTTP_GET_VARS['pID']));
        tep_redirect(tep_href_link('gift_certs.php', tep_get_all_get_params(Array('action','flag'))));
        break;
      case 'insert_category':
      case 'update_category':
        if (isset($HTTP_POST_VARS['categories_id'])) $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
        $sort_order = tep_db_prepare_input($HTTP_POST_VARS['sort_order']);

        $sql_data_array = array('sort_order' => $sort_order);

        if ($action == 'insert_category') {
          $insert_sql_data = array('parent_id' => $current_category_id,
				   'products_class'=>($pClass?$pClass:'product_default'),
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
          //HTC BOC
          $categories_htc_title_array = $HTTP_POST_VARS['categories_htc_title_tag'];
          $categories_htc_desc_array = $HTTP_POST_VARS['categories_htc_desc_tag'];
          $categories_htc_keywords_array = $HTTP_POST_VARS['categories_htc_keywords_tag'];
          $categories_htc_description_array = $HTTP_POST_VARS['categories_htc_description'];
          //HTC EOC

          $language_id = $languages[$i]['id'];

          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]));
          //HTC BOC
          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]),
           'categories_htc_title_tag' => tep_db_prepare_input($categories_htc_title_array[$language_id]),
           'categories_htc_desc_tag' => tep_db_prepare_input($categories_htc_desc_array[$language_id]),
           'categories_htc_keywords_tag' => tep_db_prepare_input($categories_htc_keywords_array[$language_id]),
           'categories_htc_description' => tep_db_prepare_input($categories_htc_description_array[$language_id]));
          //HTC EOC 

          if ($action == 'insert_category') {
            $insert_sql_data = array('categories_id' => $categories_id,
                                     'language_id' => $languages[$i]['id']);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
          } elseif ($action == 'update_category') {

// By MegaJim
    	    $url_rewrite->purge_item(sprintf('c%d',$categories_id),1);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
          }
        }

        if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {
          tep_db_query("update " . TABLE_CATEGORIES . " set categories_image = '" . tep_db_input($categories_image->filename) . "' where categories_id = '" . (int)$categories_id . "'");
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories_id));
        break;
      case 'delete_category_confirm':
        if (isset($HTTP_POST_VARS['categories_id'])) {
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);

// By MegaJim
	  $url_rewrite->purge_item(sprintf('c%d',$categories_id),1);


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
          }

          reset($products_delete);
          while (list($key) = each($products_delete)) {
            tep_remove_product($key);
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath));
        break;
      case 'delete_product_confirm':
        if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['product_categories']) && is_array($HTTP_POST_VARS['product_categories'])) {
          $product_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
          $product_categories = $HTTP_POST_VARS['product_categories'];

          for ($i=0, $n=sizeof($product_categories); $i<$n; $i++) {
            tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "' and categories_id = '" . (int)$product_categories[$i] . "'");
// BOF Separate Pricing per Customer
tep_db_query("delete from " . TABLE_PRODUCTS_GROUPS . " where products_id = '" . tep_db_input($product_id) . "' ");
// EOF Separate Pricing per Customer





          }

          $product_categories_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
          $product_categories = tep_db_fetch_array($product_categories_query);

// By MegaJim
	  $url_rewrite->purge_item(sprintf('p%d',$product_id));

          if ($product_categories['total'] == '0') {
            tep_remove_product($product_id);
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath));
        break;
      case 'move_category_confirm':
        if (isset($HTTP_POST_VARS['categories_id']) && ($HTTP_POST_VARS['categories_id'] != $HTTP_POST_VARS['move_to_category_id'])) {
          $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
          $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);

          $path = explode('_', tep_get_generated_category_path_ids($new_parent_id));

          if (in_array($categories_id, $path)) {
            $messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');

            tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories_id));
          } else {
            tep_db_query("update " . TABLE_CATEGORIES . " set parent_id = '" . (int)$new_parent_id . "', last_modified = now() where categories_id = '" . (int)$categories_id . "'");

            if (USE_CACHE == 'true') {
              tep_reset_cache_block('categories');
              tep_reset_cache_block('also_purchased');
            }

// By MegaJim
    	    $url_rewrite->purge_item(sprintf('c%d',$categories_id),1);

            tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $new_parent_id . '&cID=' . $categories_id));
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
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

// By MegaJim
    	$url_rewrite->purge_item(sprintf('p%d',$products_id));

        tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $new_parent_id . '&pID=' . $products_id));
        break;
      case 'insert_product':
      case 'update_product':
        if (isset($HTTP_POST_VARS['edit_x']) || isset($HTTP_POST_VARS['edit_y']) || !isset($HTTP_POST_VARS['products_status'])) {
          $action = 'new_product';
        } else {
// BOF MaxiDVD: Modified For Ultimate Images Pack!
            if ($HTTP_POST_VARS['delete_image'] == 'yes') {unlink(DIR_FS_CATALOG_IMAGES . $HTTP_POST_VARS['products_previous_image']);}
            //if ($HTTP_POST_VARS['delete_image_med'] == 'yes') {unlink(DIR_FS_CATALOG_IMAGES . $HTTP_POST_VARS['products_previous_image_med']);}
            //if ($HTTP_POST_VARS['delete_image_lrg'] == 'yes') {unlink(DIR_FS_CATALOG_IMAGES . $HTTP_POST_VARS['products_previous_image_lrg']);}
            //if ($HTTP_POST_VARS['delete_image_sm_1'] == 'yes') {unlink(DIR_FS_CATALOG_IMAGES . $HTTP_POST_VARS['products_previous_image_sm_1']);}
	    for ($i=1;$i<=4;$i++) if ($HTTP_POST_VARS['delete_image_xl_'.$i] == 'yes') {unlink(DIR_FS_CATALOG_IMAGES . $HTTP_POST_VARS['products_previous_image_xl_'.$i]);}
// EOF MaxiDVD: Modified For Ultimate Images Pack!
          if (isset($HTTP_GET_VARS['pID'])) $products_id = tep_db_prepare_input($HTTP_GET_VARS['pID']);
          $products_date_available = tep_db_prepare_input($HTTP_POST_VARS['products_date_available']);

          $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';

	  if ($pClass) $productObject=tep_module($pClass,'product');
	  if (!isset($productObject)) $productObject=tep_module('product_default','product');

          $sql_data_array = array('products_class' => $pClass,
				  'products_quantity' => tep_db_prepare_input($HTTP_POST_VARS['products_quantity']),
                                  'products_model' => tep_db_prepare_input($HTTP_POST_VARS['products_model']),
                                  'products_price' => tep_db_prepare_input($HTTP_POST_VARS['products_price']),
// START Qty Price Break [1242]
		                                    'products_qty_blocks' => (($i=tep_db_prepare_input($HTTP_POST_VARS['products_qty_blocks'])) < 1) ? 1 : $i,
// END Qty Price Break [1242]
                                  'products_date_available' => $products_date_available,
                                  'products_weight' => tep_db_prepare_input($HTTP_POST_VARS['products_weight']),
                                  'products_status' => tep_db_prepare_input($HTTP_POST_VARS['products_status']),
                                  'bs_icon' => tep_db_prepare_input($HTTP_POST_VARS['bs_icon']),
// By MegaJim - Free Shipping
                                  'products_free_shipping' => tep_db_prepare_input($HTTP_POST_VARS['products_free_shipping']),
                                  'products_separate_shipping' => tep_db_prepare_input($HTTP_POST_VARS['products_separate_shipping']),
                                  'products_show_qview' => tep_db_prepare_input($HTTP_POST_VARS['products_show_qview']),
                                  'products_sort_order' => tep_db_prepare_input($HTTP_POST_VARS['products_sort_order']),
                                  'products_tax_class_id' => tep_db_prepare_input($HTTP_POST_VARS['products_tax_class_id']),
                                  'manufacturers_id' => tep_db_prepare_input($HTTP_POST_VARS['manufacturers_id']));

// BOF MaxiDVD: Modified For Ultimate Images Pack!
       if (($HTTP_POST_VARS['unlink_image'] == 'yes') or ($HTTP_POST_VARS['delete_image'] == 'yes')) {
            $sql_data_array['products_image'] = '';
       } else {
         if (isset($products_image_name)) {
	    $sql_data_array['products_image'] = $products_image_name;
         } else if (isset($HTTP_POST_VARS['products_image']) && tep_not_null($HTTP_POST_VARS['products_image']) && ($HTTP_POST_VARS['products_image'] != 'none')) {
            $sql_data_array['products_image'] = tep_db_prepare_input($HTTP_POST_VARS['products_image']);
          }
       }
       for ($i=1;$i<=4;$i++) {
         if (($HTTP_POST_VARS['unlink_image_xl_'.$i] == 'yes') or ($HTTP_POST_VARS['delete_image_xl_'.$i] == 'yes')) {
            $sql_data_array['products_image_xl_'.$i] = '';
         } else {
	  if (isset($products_image_xl_name[$i])) {
	    $sql_data_array['products_image_xl_'.$i] = $products_image_xl_name[$i];
          } else if (isset($HTTP_POST_VARS['products_image_xl_'.$i]) && tep_not_null($HTTP_POST_VARS['products_image_xl_'.$i]) && ($HTTP_POST_VARS['products_image_xl_'.$i] != 'none')) {
            $sql_data_array['products_image_xl_'.$i] = tep_db_prepare_input($HTTP_POST_VARS['products_image_xl_'.$i]);
          }
         }
       }
// EOF MaxiDVD: Modified For Ultimate Images Pack!

          if ($action == 'insert_product') {
            $insert_sql_data = array('products_date_added' => 'now()');

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();

            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$current_category_id . "')");
            tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET master_products_id='$products_id' WHERE products_id='$products_id'");
          } elseif ($action == 'update_product') {
            $update_sql_data = array('products_last_modified' => 'now()'); $sql_data_array = array_merge($sql_data_array, $update_sql_data);

// By MegaJim
	    $url_rewrite->purge_item(sprintf('p%d',$products_id));

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");
          }

	if (isset($_POST['products_extra'])) foreach ($_POST['products_extra'] AS $xkey=>$xval) tep_set_products_extra($products_id,$xkey,$xval);

// Update Models
	$default_img=Array(NULL);
	$sql_data_array['master_products_id']=$products_id;
	$modelFields=Array('model_quantity'=>'products_quantity','model_name'=>'products_model','model_sku'=>'products_sku');
	$curr_models=tep_get_product_models($products_id);
	$model_upload=Array();
	if (isset($_POST['model_attrs']) && is_array($_POST['model_attrs'])) foreach ($_POST['model_attrs'] AS $midx=>$attrs) {
	  if (preg_match_all('/(\w+):(\w+)/',$attrs,$attrparse)) {
	    $postattrs=Array();
	    foreach ($attrparse[1] AS $apidx=>$aid) $postattrs[$attrparse[1][$apidx]]=$attrparse[2][$apidx];
	    $model_pid=NULL;
	    foreach ($curr_models AS $mid=>$mattrs) {
	      if (sizeof($postattrs)!=sizeof($mattrs)) continue;
	      $model_pid=$mid;
	      foreach ($postattrs AS $aid=>$av) if (!isset($mattrs[$aid]) || $mattrs[$aid]!=$av) {
		$model_pid=NULL;
		break;
	      }
	      if ($model_pid) break;
	    }
	    foreach ($modelFields AS $mfld=>$dfld) if (isset($_POST[$mfld][$midx])) $sql_data_array[$dfld]=$_POST[$mfld][$midx];
	    $model_price=$_POST['products_price'];
	    if (isset($_POST['model_price'][$midx])) $model_price+=((isset($_POST['model_price_sign'][$midx]) && ($_POST['model_price_sign'][$midx]=='-')) ? -1 : 1)*$_POST['model_price'][$midx];
	    $sql_data_array['products_price']=$model_price;

	    $model_img=Array('','','','','');
	    if (isset($_POST['model_image_ptr'][$midx])) foreach (split('/',$_POST['model_image_ptr'][$midx]) AS $idx=>$imgptr) {
	      if (preg_match('/^(\w+):(.*)/',$imgptr,$img_ptr_parse)) {
		if ($img_ptr_parse[1]=='file') $model_img[$idx]=$img_ptr_parse[2];
		else if ($img_ptr_parse[1]=='upload') $model_img[$idx]=get_upload_file('upload_model_image_'.$img_ptr_parse[2]);
	      }
	    }
	    $sql_data_array['products_image']=$model_img[0];
	    for ($i=1;$i<=4;$i++) $sql_data_array['products_image_xl_'.$i]=$model_img[$i];
	    for ($i=0;isset($model_img[$i]);$i++) if ($model_img[$i] && !isset($default_img[$i])) $default_img[$i]=$i?'':$model_img[$i];
	    if ($model_pid) {
	      $url_rewrite->purge_item(sprintf('p%d',$model_pid));

	      tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '$model_pid'");
	      unset($curr_models[$model_pid]);
	      $new_model_pid=NULL;
	    } else {
              tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
              $new_model_pid = $model_pid = tep_db_insert_id();
	    }
	    if (isset($_POST['model_extra'])) foreach ($_POST['model_extra'] AS $xkey=>$xval) if (isset($xval[$midx])) tep_set_products_extra($model_pid,$xkey,$xval[$midx]);
	    foreach ($postattrs AS $aid=>$av) {
	      $optns_sort=$_POST['options_sort_order'][$aid];
	      $attrs_sort=isset($_POST['attrs_sort_order'][$aid.'_'.$av])?$_POST['attrs_sort_order'][$aid.'_'.$av]:'';
	      $attrs_sortq=$attrs_sort==''?'NULL':"'$attrs_sort'";
	      $attrs_img='';
	      if (isset($_POST['attr_image'][$aid.'_'.$av]) && preg_match('/^(\w+):(.*)/',$_POST['attr_image'][$aid.'_'.$av],$at_img_parse)) {
		if ($at_img_parse[1]=='file') $attrs_img=$at_img_parse[2];
		else if ($at_img_parse[1]=='upload') $attrs_img=get_upload_file('upload_attr_image_'.$at_img_parse[2]);
	      }
	      if ($new_model_pid) tep_db_query("INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES." (products_id,options_id,options_values_id,options_sort,options_values_sort,options_image) VALUES ('$new_model_pid','$aid','$av','$optns_sort',$attrs_sortq,'".addslashes($attrs_img)."')");
	      else tep_db_query("UPDATE ".TABLE_PRODUCTS_ATTRIBUTES." SET options_sort='$optns_sort',options_values_sort=$attrs_sortq,options_image='".addslashes($attrs_img)."' WHERE products_id='$model_pid' AND options_id='$aid'");
	    }
	  }
	}
	foreach ($curr_models AS $mid=>$mdata) {
	  $url_rewrite->purge_item(sprintf('p%d',$mid));
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_DESCRIPTION." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_TO_CATEGORIES." WHERE products_id='$mid'");
	}
	if ($default_img) {
	  $imgq=Array();
	  foreach ($default_img AS $idx=>$img) if (isset($img)) $imgq[$idx?'products_image_xl_'.$idx:'products_image']=$img;
	  if ($imgq) tep_db_perform(TABLE_PRODUCTS,$imgq,'update',"products_id='".(int)$products_id."'");
	}


// BOF Separate Pricing Per Customer
foreach ($cus_groups AS $cgrp=>$cgname)
{
    if ($cgrp && $HTTP_POST_VARS['sppcoption'][$cgrp] && $HTTP_POST_VARS['sppcprice'][$cgrp]) {
      tep_db_query("REPLACE into " . TABLE_PRODUCTS_GROUPS . " (products_id, customers_group_id, customers_group_price) values ('" . $products_id . "', '" . $cgrp . "', '" . $HTTP_POST_VARS['sppcprice'][$cgrp] . "')");
    } else
      tep_db_query("delete from " . TABLE_PRODUCTS_GROUPS . " where customers_group_id = '" . $cgrp . "' and products_id = '" . $products_id . "'");
    if (isset($_POST[discount_qty][$cgrp])) {
      tep_db_query("DELETE FROM products_discount WHERE products_id='" . $products_id . "' AND customers_group_id='" . $cgrp . "'");
      for ($i=0;isset($_POST[discount_qty][$cgrp][$i]);$i++) if ($_POST[discount_qty][$cgrp][$i]>0) tep_db_query("INSERT INTO products_discount (products_id,customers_group_id,discount_qty,discount_percent) VALUES ('$products_id','$cgrp','".addslashes($_POST[discount_qty][$cgrp][$i])."','".addslashes($_POST[discount_percent][$cgrp][$i])."')");
    }
}
  // EOF Separate Pricing Per Customer


// XSell

if (isset($_POST['xsell'])) {
  foreach ($_POST['xsell'] AS $ch=>$xsell) {
    tep_db_query("DELETE FROM products_xsell WHERE products_id='$products_id' AND xsell_channel='".addslashes($ch)."'");
    foreach ($xsell AS $xidx=>$xsell_id) if ($xsell_id) tep_db_query("REPLACE INTO products_xsell (products_id,xsell_channel,xsell_id,sort_order) VALUES ('$products_id','".addslashes($ch)."','".addslashes($xsell_id)."','".addslashes($xidx)."')");
  }
}





          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('products_name' => tep_db_prepare_input($HTTP_POST_VARS['products_name'][$language_id]),
                                    'products_info' => tep_db_prepare_input($HTTP_POST_VARS['products_info'][$language_id]),
				    'products_info_alt' => tep_db_prepare_input($HTTP_POST_VARS['products_info_alt'][$language_id]),
                                    'products_description' => tep_db_prepare_input($HTTP_POST_VARS['products_description'][$language_id]),
                                    'products_url' => tep_db_prepare_input($HTTP_POST_VARS['products_url'][$language_id]),
                                    'products_head_title_tag' => tep_db_prepare_input($HTTP_POST_VARS['products_head_title_tag'][$language_id]),
                                    'products_head_desc_tag' => tep_db_prepare_input($HTTP_POST_VARS['products_head_desc_tag'][$language_id]),
                                    'products_head_keywords_tag' => tep_db_prepare_input($HTTP_POST_VARS['products_head_keywords_tag'][$language_id]),
                                    'products_qview_desc' => tep_db_prepare_input($HTTP_POST_VARS['products_qview_desc'][$language_id]));   
           //HTC EOC
            if ($action == 'insert_product') {
              $insert_sql_data = array('products_id' => $products_id,
                                       'language_id' => $language_id);

              $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
            } elseif ($action == 'update_product') {
              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");
            }
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }

          tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products_id));
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

            $product_query = tep_db_query("select products_quantity, products_model, products_image, products_image_xl_1,  products_image_xl_2,  products_image_xl_3, products_image_xl_4, products_image_xl_5, products_image_xl_6, products_price, products_qty_blocks, products_date_available, products_weight, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");

            $product = tep_db_fetch_array($product_query);

// By MegaJim - free shipping added
            tep_db_query("insert into " . TABLE_PRODUCTS . " (bs_icon, products_quantity, products_model, products_image, products_image_xl_1, products_image_xl_2, products_image_xl_3, products_image_xl_4, products_image_xl_5, products_image_xl_6, products_price, products_qty_blocks,  products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id, products_free_shipping, products_separate_shipping, products_show_qview, products_sort_order) values ('" . (int)$product['bs_icon'] . "', '" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_image_xl_1']) . "', '" . tep_db_input($product['products_image_xl_2']) . "', '" . tep_db_input($product['products_image_xl_3']) . "', '" . tep_db_input($product['products_image_xl_4']) . "', '" . tep_db_input($product['products_image_xl_5']) . "', '" . tep_db_input($product['products_image_xl_6']) . "', '" . tep_db_input($product['products_price']) . "', '" . tep_db_input($product['products_qty_blocks']) . "',  now(), '" . tep_db_input($product['products_date_available']) . "', '" . tep_db_input($product['products_weight']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "', '".(int)$product['products_free_shipping']. "', '".(int)$product['products_separate_shipping']."', '".(int)$product['products_show_qview']."', '".(int)$product['products_sort_order']."')");

            $dup_products_id = tep_db_insert_id();

            //Query modified for short descriptions
            $description_query = tep_db_query("select language_id, products_name, products_info, products_info_alt, products_description, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag, products_url, products_qview_desc from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$products_id . "'");
            while ($description = tep_db_fetch_array($description_query)) {
              //Query modified for short descriptions
              tep_db_query("insert into " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id, products_name, products_info, products_info_alt, products_description, products_url, products_viewed, products_qview_desc) values ('" . (int)$dup_products_id . "', '" . (int)$description['language_id'] . "', '" . tep_db_input($description['products_name']) . "', '" . tep_db_input($description['products_info']) . "', '" . tep_db_input($description['products_info_alt']) . "', '" . tep_db_input($description['products_description']) . "', '" . tep_db_input($description['products_url']) . "', '0', '" . tep_db_input($description['products_qview_desc']) . "')");
            }
    tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$dup_products_id . "', '" . (int)$categories_id . "')");
  // BOF Separate Pricing Per Customer 26042006 by Infobroker

  // What the shit was here??
  // Fixed by MegaJim

// $customers_group_query = tep_db_query("select customers_group_id, customers_group_name from " . TABLE_CUSTOMERS_GROUPS . " where customers_group_id != '0' order by customers_group_id");
//while ($customers_group = tep_db_fetch_array($customers_group_query)) // Gets all of the customers groups
//  {
//    $attributes_query = tep_db_query("select customers_group_id, customers_group_price from " . TABLE_PRODUCTS_GROUPS . " where ((products_id = '" . $products_id . "') && (customers_group_id = " . $customers_group['customers_group_id'] . ")) order by customers_group_id");
//  $attributes = tep_db_fetch_array($attributes_query);
//            tep_db_query("insert into " . TABLE_PRODUCTS_GROUPS . " (customers_group_id, customers_group_price, products_id) values ('" . $attributes['customers_group_id'] . "', '" . tep_db_input($attributes['customers_group_price']) . "', '" . (int)$dup_products_id . "')");
//}

    $attributes_query = tep_db_query("select customers_group_id, customers_group_price from " . TABLE_PRODUCTS_GROUPS . " where products_id = '" . $products_id . "' order by customers_group_id");
    while ($attributes = tep_db_fetch_array($attributes_query)) {
            tep_db_query("insert into " . TABLE_PRODUCTS_GROUPS . " (customers_group_id, customers_group_price, products_id) values ('" . $attributes['customers_group_id'] . "', '" . tep_db_input($attributes['customers_group_price']) . "', '" . (int)$dup_products_id . "')");
    }


  // EOF Separate Pricing Per Customer  26042006 by Infobroker




            $products_id = $dup_products_id;
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }
        }

        tep_redirect(tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $categories_id . '&pID=' . $products_id));
        break;
// BOF MaxiDVD: Modified For Ultimate Images Pack!
      case 'new_product_preview':

        break;
// EOF MaxiDVD: Modified For Ultimate Images Pack!
    }
  }

// check if the catalog image directory exists
  if (is_dir(DIR_FS_CATALOG_IMAGES)) {
    if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
  } else {
    $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
  }


  if ($pClass) $productObject=tep_module($pClass,'product');
  if (!isset($productObject)) $productObject=tep_module('product_default','product');

?>
<? 
define ('TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS', 'There are no Gift Certificates created yet. Please click "Create New Gift Certificate" to start.');
define ('EMPTY_CATEGORY','No Gift Certs Created');
define ('TEXT_IMAGE_NONEXISTENT','No Image Specified');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<META HTTP-EQUIV="MSThemeCompatible" CONTENT="no">
<title>Category &amp; Product Editor</title>

<link rel="STYLESHEET" href="js/css.css" type="text/css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script language="javascript" src="includes/general.js"></script>
<?php //require_once( 'attributeManager/includes/attributeManagerHeader.inc.php' )?>
<script language="javascript"><!--
function popupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width=100,height=100,screenX=150,screenY=150,top=150,left=150')
}
//--></script>


<script type="text/javascript">
function contentChanged() {
  top.resizeIframe('myframe');
}
</script>

<?php if ((HTML_AREA_WYSIWYG_DISABLE == 'Enable') or (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Enable')) { ?>
<script language="Javascript1.2"><!-- // load htmlarea
//MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.8 <head>
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
<?php }?>
<link rel="stylesheet" href="js/tabber.css" TYPE="text/css" MEDIA="screen">
<script type="text/javascript">
document.write('<style type="text/css">.tabber{display:none;}<\/style>');
</script>

<script language="javascript">
<?
  $modelFieldsJSDefs=Array();
  foreach ($ModelFieldsList AS $fld) $modelFieldsJSDefs[]="$fld:new Array()";
?>

var modelFields={<?=join(',',$modelFieldsJSDefs)?>};

function makeModelKey(attrs) {
  var keys=new Array();
  for (var k in attrs) keys.push(k);
  keys=keys.sort();
  var ak=new Array();
  for (var i=0;i<keys.length;i++) ak[i]=keys[i]+':'+attrs[keys[i]];
  return ak;  
}

function getModelValue(fld,attrs) {
  var fv=modelFields[fld];
  var ak=makeModelKey(attrs);
  var r=fv[ak.join(',')];
  if (r!=undefined) return r;
  for (var i=1;;i++) {
    r=digModelValue(fv,ak,i);
    if (r!=undefined) return r;
  }
}

function digModelValue(fv,ak,dp) {
  var akd=ak.splice(0,ak.length-1);
  dp--;
  var r;
  for (var i=ak.length-1;i>=0;i--) {
    r=(dp<=0)?fv[ak.join(',')]:digModelValue(fv,akd,dp);
    if (r!=undefined) return r;
    if (i>0) akd[i-1]=ak[i];
  }
  return akd.length?undefined:'';
}

function setModelValue(fld,attrs,val) {
  modelFields[fld][makeModelKey(attrs).join(',')]=val;
}

</script>



</head>
<body STYLE="background-color:transparent; margin:0" on--Load="goOnLoad();">
<!--script type="text/javascript" src="js/tabber.js"></script-->

<div id="spiffycalendar" class="text"></div>
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="571" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" valign="top" colspan="2">
<?php
  if ($action == 'new_product') {
    $parameters = array('products_name' => '',
                        'products_info' => '',
			'products_info_alt' => '',
                       'products_description' => '',
                       'products_url' => '',
                       'products_id' => '',
                       'products_quantity' => '',
                       'products_model' => '',
                       'products_image' => '',
		       'products_qview_desc' => '',
	                       'products_price' => '',
	                       'products_qty_blocks' => '',
                       'products_image_xl_1' => '',
                       'products_image_xl_2' => '',
                       'products_image_xl_3' => '',
                       'products_image_xl_4' => '',
                       'products_image_xl_5' => '',
	                   'products_image_xl_6' => '',
                       'products_price' => '',
                       'products_weight' => '',
                       'products_date_added' => '',
                       'products_last_modified' => '',
                       'products_date_available' => '',
                       'bs_icon' => '',
                       'products_free_shipping' => '0',
                       'products_separate_shipping' => '0',
                       'products_show_qview' => '1',
		       'products_sort_order' => 100,
                       'products_status' => '',
                       'products_tax_class_id' => '',
                       'manufacturers_id' => '');

    $pInfo = new objectInfo($parameters);

   //HTC BOC
    if (isset($HTTP_GET_VARS['pID']) && empty($HTTP_POST_VARS)) {
// BOF MaxiDVD: Modified For Ultimate Images Pack!
      $product_query = tep_db_query("select p.bs_icon, pd.products_name, pd.products_description, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_head_keywords_tag, pd.products_url, pd.products_qview_desc, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_image_xl_1, p.products_image_xl_2, p.products_image_xl_3, p.products_image_xl_4, p.products_image_xl_5, p.products_image_xl_6, p.products_price, p.products_qty_blocks, p.products_weight, p.products_date_added, p.products_free_shipping, p.products_separate_shipping, p.products_show_qview, p.products_sort_order, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");
// EOF MaxiDVD: Modified For Ultimate Images Pack!
      $product = tep_db_fetch_array($product_query);
   //HTC EOC 

      $pInfo->objectInfo($product);
    } elseif (tep_not_null($HTTP_POST_VARS)) {
      $pInfo->objectInfo($HTTP_POST_VARS);
      $products_name = $HTTP_POST_VARS['products_name'];
      $products_info = $HTTP_POST_VARS['products_info'];
      $products_info_alt = $HTTP_POST_VARS['products_info_alt'];
      $products_description = $HTTP_POST_VARS['products_description'];
      $products_url = $HTTP_POST_VARS['products_url'];
      $products_qview_desc = $HTTP_POST_VARS['products_qview_desc'];
    }

    $manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
    $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
    while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                     'text' => $manufacturers['manufacturers_name']);
    }
        $free_shipping_array = array(array('id' => '0', 'text' => 'No'), array('id' => '1', 'text' => 'Yes'));

    $tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
    $tax_class_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
    while ($tax_class = tep_db_fetch_array($tax_class_query)) {
      $tax_class_array[] = array('id' => $tax_class['tax_class_id'],
                                 'text' => $tax_class['tax_class_title']);
    }

    $languages = tep_get_languages();

    $breadcrumb->add($pInfo->products_name, isset($_GET['pID'])?tep_href_link('gift_certs.php', 'pclass='.$pClass."&cPath=$cPath&pID=".$_GET['pID']):'');

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
    <?=$breadcrumb->trail(2)?>
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
    <?php echo tep_draw_form('new_product', 'gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=new_product_preview', 'post', 'enctype="multipart/form-data"'); ?>
    <table border="0" width="571" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" style="text-transform:uppercase; font-weight:bold">&nbsp; <a href="#"><?php echo sprintf(tep_output_generated_category_path($current_category_id)); ?> </a> &raquo; <?=$pInfo->products_name ;?> 
<!--( product ID: < ? = //$pInfo->products_id?>)-->
</td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>


 <!-- /* <tr>
           <td class="dataTableRow" valign="top"><span class="main"><?php echo TEXT_PRODUCTS_IMAGE_NOTE; ?></span></td>
           <?php if (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') { ?>
           <td class="dataTableRow" valign="top"><table width="100%" id="global_products_imageXXXXX"><tr><td>xxxxxxxxxxxxxxxxxxxxx<?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('upload_products_image') . '</td></tr></table><br>'; ?></td>
           <?php } else { ?>
           <td class="dataTableRow" valign="top">
<?php echo '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="dataTableRow">' . tep_draw_textarea_field('upload_products_image', 'soft', '55', '2', $pInfo->products_image) . tep_draw_hidden_field('products_previous_image', $pInfo->products_image) . '</td></tr></table>';
           } if (($HTTP_GET_VARS['pID']) && ($pInfo->products_image) != '') { ?>
</td></tr>
              <tr>
                 <td class="dataTableRow" colspan="3" valign="top"><?php if (tep_not_null($pInfo->products_image)) { ?><span class="smallText"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $pInfo->products_image, $pInfo->products_image, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="left" hspace="0" vspace="5"') . tep_draw_hidden_field('products_previous_image', $pInfo->products_image) . '<br>'. tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="unlink_image" value="yes">' . TEXT_PRODUCTS_IMAGE_REMOVE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="delete_image" value="yes">' . TEXT_PRODUCTS_IMAGE_DELETE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '1', '42'); ?></span><?php } ?></td>
              </tr>
           <?php } ?>
        <?php echo ' </tr>';?>
*/-->

      <tr>
        <td>

<table width="571" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td class="main">Status:</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_status', '1', $in_status) . '&nbsp;Available&nbsp;' . tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;Not&nbsp;Available' ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
                   <tr>
            <td class="main">Delivery</td>
            <td class="main"><?php  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' .tep_draw_pull_down_menu('products_free_shipping', Array(Array('id'=>1,'text'=>'Email Only'),Array('id'=>0,'text'=>'Mail the Card')), $pInfo->products_free_shipping); ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main">Item Name</td>
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? $products_name[$languages[$i]['id']] : tep_get_products_name($pInfo->products_id, $languages[$i]['id'])),' maxlength="28"'); ?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2" class="main"><hr></td>
          </tr>

	  <input type="hidden" name="products_quantity" value="999999">
		            
	  <tr>
	  <td colspan="2">

	 <table width="555" border="0" cellpadding="0" cellspacing="0" style="padding-top:10px;">
             					
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main" valign="top"><?php //if ($i == 0) echo TEXT_PRODUCTS_INFO; ?>
			<?php if ($i == 0) echo "Short Description:"; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_info[' . $languages[$i]['id'] . ']', 'soft', '55', '2', (isset($products_info[$languages[$i]['id']]) ? $products_info[$languages[$i]['id']] : tep_get_products_info($pInfo->products_id, $languages[$i]['id']))); ?></td>
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
            <td class="main" valign="top"><?php if ($i == 0) echo "Featured Short Description:"; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_info_alt[' . $languages[$i]['id'] . ']', 'soft', '55', '2', (isset($products_info_alt[$languages[$i]['id']]) ? $products_info_alt[$languages[$i]['id']] : tep_get_products_info_alt($pInfo->products_id, $languages[$i]['id']))); ?></td>
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
            <td class="main" valign="top"><?php //if ($i == 0) echo TEXT_PRODUCTS_DESCRIPTION; ?>
			<?php if ($i == 0) echo "Long Description:"; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '55', '15', (isset($products_description[$languages[$i]['id']]) ? $products_description[$languages[$i]['id']] : tep_get_products_description($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
	  
<?php
    }
?> 

<tr bgcolor="#ebebff">
            <td class="main">Show Quick View Popup</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_show_qview', $free_shipping_array, $pInfo->products_show_qview); ?></td>
          </tr>
<?
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
         <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo 'Quick Preview Description'; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_qview_desc[' . $languages[$i]['id'] . ']', 'soft', '55', '5', (isset($products_qview_desc[$languages[$i]['id']]) ? $products_qview_desc[$languages[$i]['id']] : $pInfo->products_qview_desc)); ?></td>
              </tr>
            </table></td>
          </tr>
	  
<?php
    }
?>   
       </table>


<?
  require( DIR_FS_MODULES.'attrctl.php');
  show_attrctl($pInfo->products_id,Array(0=>$pInfo->products_price),$productObject);
?>

<script language="javascript">
  function setGlobalFieldsDisplay(f) {
  }
  showAllModels();
</script>

	 
<? include(DIR_FS_MODULES.'xsellctl.php'); ?>


</td></tr>

      <tr>
        <td class="main" align="right" colspan="2" style="padding:10px;"><?php echo tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . tep_image_submit('button_update.gif', IMAGE_UPDATE,' name="express_update"') . '&nbsp;&nbsp;<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr>

</table></form>

 <?php
  } elseif ($action == 'new_product_preview') {
    if (tep_not_null($HTTP_POST_VARS)) {
      $pInfo = new objectInfo($HTTP_POST_VARS);
      $products_name = $HTTP_POST_VARS['products_name'];
      $products_info = $HTTP_POST_VARS['products_info'];
      $products_info_alt = $HTTP_POST_VARS['products_info_alt'];
      $products_description = $HTTP_POST_VARS['products_description'];
      $products_head_title_tag = $HTTP_POST_VARS['products_head_title_tag'];
      $products_head_desc_tag = $HTTP_POST_VARS['products_head_desc_tag'];
      $products_head_keywords_tag = $HTTP_POST_VARS['products_head_keywords_tag'];
      $products_url = $HTTP_POST_VARS['products_url'];
      $products_qview_desc = $HTTP_POST_VARS['products_qview_desc'];
    } else {
// BOF MaxiDVD: Modified For Ultimate Images Pack! & Short Descriptions
      $product_query = tep_db_query("select * from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'");
// EOF MaxiDVD: Modified For Ultimate Images Pack!
      $product = tep_db_fetch_array($product_query);
 // HTC EOC

      $pInfo = new objectInfo($product);
      $products_image_name = $pInfo->products_image;
      $products_image_xl_name = Array(1=>$pInfo->products_image_xl_1,2=>$pInfo->products_image_xl_2,3=>$pInfo->products_image_xl_3,4=>$pInfo->products_image_xl_4);
    }

    $form_action = (isset($HTTP_GET_VARS['pID'])) ? 'update_product' : 'insert_product';

    $breadcrumb->add($pInfo->products_name, isset($_GET['pID'])?tep_href_link('gift_certs.php', 'pclass='.$pClass."&cPath=$cPath&pID=".$_GET['pID']):'');

    echo tep_draw_form($form_action, 'gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=' . $form_action, 'post', 'enctype="multipart/form-data"');

    // HTC BOC         
    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
      if (isset($HTTP_GET_VARS['read']) && ($HTTP_GET_VARS['read'] == 'only')) {
        $pInfo->products_name = tep_get_products_name($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_info = tep_get_products_info($pInfo->products_id, $languages[$i]['id']);
	$pInfo->products_info_alt = tep_get_products_info_alt($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_description = tep_get_products_description($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_head_title_tag = tep_db_prepare_input($products_head_title_tag[$languages[$i]['id']]);
        $pInfo->products_head_desc_tag = tep_db_prepare_input($products_head_desc_tag[$languages[$i]['id']]);
        $pInfo->products_head_keywords_tag = tep_db_prepare_input($products_head_keywords_tag[$languages[$i]['id']]);
        $pInfo->products_url = tep_get_products_url($pInfo->products_id, $languages[$i]['id']);
      } else {
        $pInfo->products_name = tep_db_prepare_input($products_name[$languages[$i]['id']]);
        $pInfo->products_info = tep_db_prepare_input($products_info[$languages[$i]['id']]);
	$pInfo->products_info_alt = tep_db_prepare_input($products_info_alt[$languages[$i]['id']]);
        $pInfo->products_description = tep_db_prepare_input($products_description[$languages[$i]['id']]);
        $pInfo->products_head_title_tag = tep_db_prepare_input($products_head_title_tag[$languages[$i]['id']]);
        $pInfo->products_head_desc_tag = tep_db_prepare_input($products_head_desc_tag[$languages[$i]['id']]);
        $pInfo->products_head_keywords_tag = tep_db_prepare_input($products_head_keywords_tag[$languages[$i]['id']]);
        $pInfo->products_url = tep_db_prepare_input($products_url[$languages[$i]['id']]);
      }
    // HTC EOC
?>
    <?=$breadcrumb->trail(2)?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . $pInfo->products_name; ?></td>
            <td class="pageHeading" align="right"><?php echo $currencies->format($pInfo->products_price); ?></td>
          </tr>
        </table></td>
      </tr>
<!-- // BOF MaxiDVD: Modified For Ultimate Images Pack! // -->
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main">
<?php if ($products_image_name) { 
	echo tep_image(DIR_WS_CATALOG_IMAGES . $products_image_name, $products_image_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"');
	}

	echo $pInfo->products_description . '<br><br><center>'; 

	for($i=1;$i<=4;$i++) {
		if ($products_image_xl_name[$i]) { ?>
	<script>
<!--
      document.write('<?php echo '<a href="javascript:popupWindow(\\\'' . tep_href_link(FILENAME_POPUP_IMAGE, 'image=' . $products_image_xl_name[$i]) . '\\\')">' . tep_image(DIR_WS_CATALOG_IMAGES . $products_image_xl_name[$i], $products_image_xl_name[$i], ULT_THUMB_IMAGE_WIDTH, ULT_THUMB_IMAGE_HEIGHT, 'align="center" hspace="5" vspace="5"') . '</a>'; ?>');
//-->
</script>
<?php
	}
}
?>        </td>
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
        <td class="main"><?php echo $pInfo->products_info_alt; ?></td>
      </tr>
<tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<!-- // EOF MaxiDVD: Modified For Ultimate Images Pack! // -->
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
        $back_url = 'gift_certs.php';
        $back_url_params = 'cPath=' . $cPath . '&pID=' . $pInfo->products_id;
      }
?>

      <tr>
        <td align="right"><?php echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=new_product') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link($back_url, $back_url_params, 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>

<?php
    } else {
?>
      <tr>
        <td align="right" class="smallText">
<?php
/* Re-Post all POST'ed variables */
      reset($HTTP_POST_VARS);
      while (list($key, $value) = each($HTTP_POST_VARS)) {//        if (!is_array($HTTP_POST_VARS[$key])) {
	// BOF Separate Pricing per Customer
if (is_array($value)) {
  while (list($k, $v) = each($value)) {
    echo tep_draw_hidden_field($key . '[' . $k . ']', htmlspecialchars(stripslashes($v)))."\n";
  }
} else {
	// EOF Separate Pricing per Customer
          echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)))."\n";
        }
      }
      // HTC BOC
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        echo tep_draw_hidden_field('products_name[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_name[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_info[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_info[$languages[$i]['id']])));
	echo tep_draw_hidden_field('products_info_alt[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_info_alt[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_description[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_description[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_title_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_title_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_desc_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_head_keywords_tag[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_url[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_url[$languages[$i]['id']])));
        echo tep_draw_hidden_field('products_qview_desc[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_qview_desc[$languages[$i]['id']])));
      }
      // HTC EOC  
      echo tep_draw_hidden_field('products_image', stripslashes($products_image_name));
// BOF MaxiDVD: Added For Ultimate Images Pack!
      for ($i=1;$i<=4;$i++) {
        echo tep_draw_hidden_field('products_image_xl_'.$i, stripslashes($products_image_xl_name[$i]));
      }
// EOF MaxiDVD: Added For Ultimate Images Pack!
      echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"') . '&nbsp;&nbsp;';

      if (isset($HTTP_GET_VARS['pID'])) {
        echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
      } else {
        echo tep_image_submit('button_insert.gif', IMAGE_INSERT);
      }
      echo '&nbsp;&nbsp;<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
?></td>
      </tr>
    </table></form>
<?php
    }
  } else {
?>
    <?=$breadcrumb->trail(2)?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <!--td class="pageHeading"><?php echo HEADING_TITLE; ?></td-->
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td class="smallText" align="right">
                </td>
              </tr>
              <tr>
                <td class="smallText" align="right">
                </td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
<td class="dataTableHeadingContent" align="center" style="width:60px;">Image</td>
<td class="dataTableHeadingContent" align="center">Preview</td>
                <td class="dataTableHeadingContent">Gift Items</td>
                <td class="dataTableHeadingContent" align="center">on / off</td>
                <td class="dataTableHeadingContent" align="right"><?php //echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $categories_count = 0;
    $rows = 0;
    if (isset($HTTP_GET_VARS['search'])) {
      $search = tep_db_prepare_input($HTTP_GET_VARS['search']);

      // HTC BOC 
      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag, cd.categories_htc_description, c.categories_status from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id".($pClass?" AND c.products_class='".addslashes($pClass)."'":"")." and cd.language_id = '" . (int)$languages_id . "' and cd.categories_name like '%" . tep_db_input($search) . "%' order by c.sort_order, cd.categories_name");
    } else {
      $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, cd.categories_htc_title_tag, cd.categories_htc_desc_tag, cd.categories_htc_keywords_tag, cd.categories_htc_description, c.categories_status from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = '" . (int)$current_category_id . "' ".($pClass?" AND c.products_class='".addslashes($pClass)."'":"")."and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name");
    // HTC EOC
    }
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
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&'.tep_get_path($categories['categories_id'])) . '\'">' . "\n";
      } else {
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent" align="center" style="width:60px;"><?php echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&'.tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>'; ?></td>
<td class="dataTableContent" align="center"><?php echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&'.tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', 'View Category') . '</a>'; ?></td>
<td class="dataTableContent" style="width:330px;"><?php echo '<b>' . $categories['categories_name'] . '</b>'; ?>
</td>
                <td class="dataTableContent" align="center" style="width:115px;">
<!-- CHANGE THIS TO CATEGORY DISABLE / ENABLE -->
<?php
    if ($categories['categories_status']){
      echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '&nbsp;&nbsp;<a href="'.tep_href_link('gift_certs.php', 'pclass='.$pClass.'&action=setflag&flag=0&cPath=' . $cPath . '&cID=' . $categories['categories_id']).'">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
    } else {
      echo '<a href="'.tep_href_link('gift_certs.php', 'pclass='.$pClass.'&action=setflag&flag=1&cPath=' . $cPath . '&cID=' . $categories['categories_id']).'">' .tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
    }      
?>
<!-- END - CHANGE THIS TO CATEGORY DISABLE / ENABLE --></td>
<td class="dataTableContent" align="center" style="width:64px;"><?php if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }

    $products_count = 0;
    if (isset($HTTP_GET_VARS['search'])) {
      $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p2c.categories_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and pd.products_name like '%" . tep_db_input($search) . "%' order by pd.products_name");
    } else {
      $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_qty_blocks, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "' order by pd.products_name");
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
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product') . '\'">' . "\n";

      } else {
        echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products['products_id']) . '\'">' . "\n";
      }
?>
<td class="dataTableContent" center="center" style="width:50px; padding:5px;"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $products['products_image'], $products['products_name'], 40, 50); ?></td>
<td class="dataTableContent" style="width:70px;" align="center"><?php echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products['products_id'] . '&action=new_product') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>';?></td>
                <td class="dataTableContent" style="width:390px;"><?php echo $products['products_name']; ?></td>
                <td class="dataTableContent" align="center" style="width:55px;">
<?php
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&action=setflag&flag=0&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&action=setflag&flag=1&pID=' . $products['products_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
                <td class="dataTableContent" align="right"><?php if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp; </td>
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
                    <td class="smallText"><?php echo 'Gift Items:&nbsp;' . $products_count; ?></td>
                    <td align="right" class="smallText">
<?php if (sizeof($cPath_array) > 0) echo '<a href="javascript:history.go( -1 );">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>&nbsp;'; 
if (!isset($HTTP_GET_VARS['search'])) echo ($products_count==0?'<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&action=new_category') . '">' . tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY) . '</a>&nbsp;':'').($categories_count==0?'<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&action=new_product') . '">' . tep_image_button('button_new_gift-cert.gif', IMAGE_NEW_PRODUCT) . '</a>':'')?>&nbsp;</td>
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

        $contents = array('form' => tep_draw_form('newcategory', 'gift_certs.php', 'pclass='.$pClass.'&action=insert_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']');
          // HTC BOC
          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']');
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']');
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']');
          $category_htc_description_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_textarea_field('categories_htc_description[' . $languages[$i]['id'] . ']', 'hard', 30, 5, '');
          // HTC EOC
        }

        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
        $contents[] = array('text' => '<br>' . TEXT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', '', 'size="2"'));
        // HTC BOC
        $contents[] = array('text' => '<br>' . 'Header Tags Category Title' . $category_htc_title_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Description' . $category_htc_desc_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Keywords' . $category_htc_keywords_string);
        $contents[] = array('text' => '<br>' . 'Categories Page Description' . $category_htc_description_string);
        // HTC EOC
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'edit_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', 'gift_certs.php', 'pclass='.$pClass.'&action=update_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_EDIT_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id']));
          // HTC BOC
          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_title($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_desc($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_keywords($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_description_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_textarea_field('categories_htc_description[' . $languages[$i]['id'] . ']', 'hard', 30, 5, tep_get_category_htc_description($cInfo->categories_id, $languages[$i]['id']));
          // HTC EOC
        }

        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<br>' . tep_image(DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name) . '<br>' . DIR_WS_CATALOG_IMAGES . '<br><b>' . $cInfo->categories_image . '</b>');
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
        $contents[] = array('text' => '<br>' . 'Header Tags Category Title' . $category_htc_title_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Description' . $category_htc_desc_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Keywords' . $category_htc_keywords_string);
        $contents[] = array('text' => '<br>' . 'Categories Page Description' . $category_htc_description_string);
        // HTC EOC
        $contents[] = array('text' => '<br>' . TEXT_EDIT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'delete_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', 'gift_certs.php', 'pclass='.$pClass.'&action=delete_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
        $contents[] = array('text' => '<br><b>' . $cInfo->categories_name . '</b>');
        if ($cInfo->childs_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count));
        if ($cInfo->products_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $cInfo->products_count));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'move_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', 'gift_certs.php', 'pclass='.$pClass.'&action=move_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name));
        $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $cInfo->categories_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'delete_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', 'gift_certs.php', 'pclass='.$pClass.'&action=delete_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
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
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'move_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', 'gift_certs.php', 'pclass='.$pClass.'&action=move_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name));
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
        $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'copy_to':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_COPY_TO . '</b>');

        $contents = array('form' => tep_draw_form('copy_to', 'gift_certs.php', 'pclass='.$pClass.'&action=copy_to_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES . '<br>' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('text' => '<br>' . TEXT_HOW_TO_COPY . '<br>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '<br>' . tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE);
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', IMAGE_COPY) . ' <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      default:
        if ($rows > 0) {
          if (isset($cInfo) && is_object($cInfo)) { // category info box contents
            $heading[] = array('text' => '<b>' . $cInfo->categories_name . '</b>');

            $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=edit_category') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=delete_category') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=move_category') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>');
            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added));
            if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified));
            $contents[] = array('text' => '<br>' . tep_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT) . '<br>' . $cInfo->categories_image);
            $contents[] = array('text' => '<br>' . TEXT_SUBCATEGORIES . ' ' . $cInfo->childs_count . '<br>' . TEXT_PRODUCTS . ' ' . $cInfo->products_count);
          } elseif (isset($pInfo) && is_object($pInfo)) { // product info box contents
            $heading[] = array('text' => '<b>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</b>');
// BOF MaxiDVD: Added Catalog Preview Button.
//++++ QT Pro: Added Stock Button
            $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=new_product') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link('gift_certs.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=delete_product') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE));
            $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link('orders.php', 'pID=' . $pInfo->products_id) . '">View Ordered</a>');
// EOF MaxiDVD: Added Catalog Preview Button.
            $contents[] = array('text' => '<br>Date Added: ' . tep_date_short($pInfo->products_date_added));
            if (tep_not_null($pInfo->products_last_modified)) $contents[] = array('text' => 'Last Modified: ' . tep_date_short($pInfo->products_last_modified));
            $contents[] = array('text' => '<br>' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '<br>' . $pInfo->products_image);
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
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
