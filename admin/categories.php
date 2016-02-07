<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


  require('includes/application_top.php');

	// # Get the current date and time
	$now = date('Y-m-d H:i:s', time());

	if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

		// # Activate supplier warehouse module for product
		if(isset($_GET['multi-warehouse']) && $_GET['multi-warehouse'] == 'activate') { 
		
			if(!empty($_GET['pID'])) { 
	
				$convert_stock_query = tep_db_query("SELECT products_quantity FROM products WHERE products_id = '".$_GET['pID']."'");
	
				if(tep_db_num_rows($convert_stock_query) > 0) { 
	
					$convert_stock = tep_db_result($convert_stock_query,0);
				}
	
				$warehouse_check = tep_db_query("SELECT products_warehouse_id FROM " . TABLE_PRODUCTS_WAREHOUSE_INVENTORY . " WHERE products_id = '".$_GET['pID']."'");
	
				if(tep_db_num_rows($warehouse_check) < 1) { 
	
					$home_warehouse = tep_db_result(tep_db_query("SELECT products_warehouse_name FROM products_warehouse_profiles WHERE products_warehouse_id = '1'"),0);
	
					$convert_stock = tep_db_query("REPLACE INTO " . TABLE_PRODUCTS_WAREHOUSE_INVENTORY . "
												   SET products_quantity = '".$convert_stock."',
												   products_id = '".$_GET['pID']."',
												   products_warehouse_id = '1',
												   products_warehouse_name = '".$home_warehouse."'
												 ");
				}
	
				$return = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
				tep_redirect(tep_href_link(FILENAME_CATEGORIES, $return));
	
				exit();
			}
		}
	}

  // # include the breadcrumb class and start the breadcrumb trail
  require(DIR_WS_CLASSES . 'breadcrumb.php');
  $breadcrumb = new breadcrumb;

  $breadcrumb->add('Categories', tep_href_link(FILENAME_CATEGORIES));

  $pClass = NULL;

  if (isset($cPath_array) && sizeof($cPath_array)) {

    $cat_row=tep_db_fetch_array(tep_db_query("SELECT * FROM ".TABLE_CATEGORIES." WHERE categories_id='".addslashes($cPath_array[sizeof($cPath_array)-1])."'"));
    if (isset($cat_row['products_class'])) $pClass=$cat_row['products_class'];
  } else if (isset($_GET['pclass'])) $pClass=$_GET['pclass'];
  else if (isset($_POST['pclass'])) $pClass=$_POST['pclass'];
  if (!isset($pClass)) $pClass='product_default';

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

  require(DIR_WS_CLASSES . 'url_rewrite.php');
  $url_rewrite = new url_rewrite();

  $ModelFieldsList = array('quantity');
  
  $DBFeedMods = tep_module('dbfeed');

  $action = (isset($_GET['action']) ? $_GET['action'] : '');
  
  $cus_groups = tep_get_customer_groups();

  include(DIR_FS_MODULES.'tabedit.php');

  if (tep_not_null($action)) {

    if ($action=='new_product_preview' && (isset($_POST['express_update']) || isset($_POST['express_update_x']))) {
      $action = (isset($_GET['pID'])) ? 'update_product' : 'insert_product';
			$express_update = true;
	  }


	function get_upload_file($fld) {
		global $UploadCache;

		if(!isset($UploadCache)) $UploadCache = array();
		if(!isset($UploadCache[$fld])) {

			$model_image_obj = new upload($fld);
			$model_image_obj->set_destination(DIR_FS_CATALOG_IMAGES);
			$UploadCache[$fld] = ($model_image_obj->parse() && $model_image_obj->save())?$model_image_obj->filename:'';
		}

		//echo 'get_upload_file('.$fld.")=".$UploadCache[$fld]."\n";
		return $UploadCache[$fld];
	}


// # Upload Product Images
   if (isset($_POST['upload_products_image']) || isset($_FILES['upload_products_image'])) {
     if (($_POST['unlink_image'] == 'yes') or ($_POST['delete_image'] == 'yes')) {
        $products_image = '';
        $products_image_name = '';
        } elseif (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') {
        $products_image = new upload('upload_products_image');
        $products_image->set_destination(DIR_FS_CATALOG_IMAGES);
        if ($products_image->parse() && $products_image->save()) {
          $products_image_name = $products_image->filename;
        } else {
          $products_image_name = (isset($_POST['products_previous_image']) ? $_POST['products_previous_image'] : '');
        }
        } else {
          if (isset($_POST['upload_products_image']) && tep_not_null($_POST['upload_products_image']) && ($_POST['upload_products_image'] != 'none')) {
            $products_image_name = $_POST['upload_products_image'];
          } else {
            $products_image_name = (isset($_POST['products_previous_image']) ? $_POST['products_previous_image'] : '');
          }
        }
   }
   $product_image_xl=Array();
   $products_image_xl_name=Array();
   for ($i=1;$i<=4;$i++) {
     if (isset($_POST['upload_products_image_xl_'.$i]) || isset($_FILES['upload_products_image_xl_'.$i])) {
//       echo "Upload $i: ".$_POST['upload_products_image_xl_'.$i]."\n";
       if (($_POST['unlink_image_xl_'.$i] == 'yes') or ($_POST['delete_image_xl_'.$i] == 'yes')) {
        $products_image_xl[$i] = '';
        $products_image_xl_name[$i] = '';
        } elseif (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') {
        $products_image_xl[$i] = new upload('upload_products_image_xl_'.$i);
        $products_image_xl[$i]->set_destination(DIR_FS_CATALOG_IMAGES);
        if ($products_image_xl[$i]->parse() && $products_image_xl[$i]->save()) {
          $products_image_xl_name[$i] = $products_image_xl[$i]->filename;
        } else {
          $products_image_xl_name[$i] = (isset($_POST['products_previous_image_xl_'.$i]) ? $_POST['products_previous_image_xl_'.$i] : '');
        }
        } else {
          if (isset($_POST['upload_products_image_xl_'.$i]) && tep_not_null($_POST['upload_products_image_xl_'.$i]) && ($_POST['upload_products_image_xl_'.$i] != 'none')) {
            $products_image_xl_name[$i] = $_POST['upload_products_image_xl_'.$i];
          } else {
            $products_image_xl_name[$i] = (isset($_POST['products_previous_image_xl_'.$i]) ? $_POST['products_previous_image_xl_'.$i] : '');
          }
        }
      }
    }


    switch ($action) {
	  case 'status':
		$status = tep_db_prepare_input($_POST['status']);
		$thecat = tep_db_prepare_input($_POST['cPath']);
		tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $thecat . '&pclass=' . $_GET['pClass'].'&status='.$status));
	  break;

      case 'setflag':
        if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
          if (isset($_GET['pID'])) {
            tep_set_product_status($_GET['pID'], $_GET['flag']);
          } else if (isset($_GET['cID'])) {
            tep_set_category_status($_GET['cID'], $_GET['flag']);
	  }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }
        }

//        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $_GET['cPath'] . '&pID=' . $_GET['pID']));
        tep_redirect(tep_href_link(FILENAME_CATEGORIES, tep_get_all_get_params(Array('action','flag'))));

break;

case 'insert_category':
case 'update_category':

        $categories_id = (!empty($_POST['categories_id']) ? (int)tep_db_prepare_input($_POST['categories_id']) : (int)$_GET['cID']);

        $sort_order = tep_db_prepare_input($_POST['sort_order']);

        $sql_data_array = array('sort_order' => $sort_order);

        if ($action == 'insert_category') {

          $insert_sql_data = array('parent_id' => $current_category_id,
								   'products_class' => ($pClass ? $pClass : 'product_default'),
                                   'date_added' => 'now()',
								   'last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array);

          $categories_id = tep_db_insert_id();

        } elseif($action == 'update_category') {

          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "'");

        }

        $languages = tep_get_languages();

        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {

          $categories_name_array = $_POST['categories_name'];

          $categories_htc_title_array = tep_db_prepare_input($_POST['categories_htc_title_tag']);
          $categories_htc_desc_array = tep_db_prepare_input($_POST['categories_htc_desc_tag']);
          $categories_htc_keywords_array = tep_db_prepare_input($_POST['categories_htc_keywords_tag']);
          $categories_htc_description_array = tep_db_prepare_input($_POST['categories_htc_description']);

          $language_id = $languages[$i]['id'];

          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]));

          $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]),
           						  'categories_htc_title_tag' => tep_db_prepare_input($categories_htc_title_array[$language_id]),
           						  'categories_htc_desc_tag' => tep_db_prepare_input($categories_htc_desc_array[$language_id]),
           						  'categories_htc_keywords_tag' => tep_db_prepare_input($categories_htc_keywords_array[$language_id]),
           						  'categories_htc_description' => tep_db_prepare_input($categories_htc_description_array[$language_id])
								 );


          if ($action == 'insert_category') {

            $insert_sql_data = array('categories_id' => $categories_id,
                                     'language_id' => $languages[$i]['id']);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);

          } elseif ($action == 'update_category') {

    	    $url_rewrite->purge_item(sprintf('c%d',$categories_id),1);

            tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "' AND  language_id = '" . (int)$languages[$i]['id'] . "'");
          }

        } // # end for()

        if($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {

			// # don't update the image if filename is empty
			if(!empty($categories_image->filename)) { 
   				tep_db_query("UPDATE " . TABLE_CATEGORIES . " SET categories_image = '" . tep_db_input($categories_image->filename) . "' WHERE categories_id = '" . (int)$categories_id . "'");
	       	}
		}

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories_id));

break;

case 'delete_category_confirm':
        if (isset($_POST['categories_id'])) {
          $categories_id = tep_db_prepare_input($_POST['categories_id']);

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

            $check_query = tep_db_query("SELECT COUNT(*) AS total FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " WHERE products_id = '" . (int)$key . "' AND categories_id NOT IN(" . $category_ids . ")");
            $check = tep_db_fetch_array($check_query);

            if ($check['total'] < '1') {
              $products_delete[$key] = $key;
            }
          }

		// # removing categories can be a lengthy process
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

        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath));
break;

case 'delete_product_confirm':

	if (isset($_POST['products_id']) && isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
    	$product_id = tep_db_prepare_input($_POST['products_id']);
		$product_categories = $_POST['product_categories'];

		for ($i=0, $n=sizeof($product_categories); $i<$n; $i++) {
			tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "' and categories_id = '" . (int)$product_categories[$i] . "'");
			tep_db_query("delete from " . TABLE_PRODUCTS_GROUPS . " where products_id = '" . tep_db_input($product_id) . "' ");
		}

		$product_categories_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
		$product_categories = tep_db_fetch_array($product_categories_query);

		$url_rewrite->purge_item(sprintf('p%d',$product_id));

		if ($product_categories['total'] == '0')  tep_remove_product($product_id);
	}

	if (USE_CACHE == 'true') {
		tep_reset_cache_block('categories');
		tep_reset_cache_block('also_purchased');
	}

	tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath));
break;

case 'move_category_confirm':
	
	if (isset($_POST['categories_id']) && ($_POST['categories_id'] != $_POST['move_to_category_id'])) {
		$categories_id = tep_db_prepare_input($_POST['categories_id']);
		$new_parent_id = tep_db_prepare_input($_POST['move_to_category_id']);

		$path = explode('_', tep_get_generated_category_path_ids($new_parent_id));

		if (in_array($categories_id, $path)) {
			$messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');
			tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories_id));
		} else {
			tep_db_query("UPDATE " . TABLE_CATEGORIES . " 
						  SET parent_id = '" . (int)$new_parent_id . "',
						  last_modified = NOW() 
						  WHERE categories_id = '" . (int)$categories_id . "'
						");

			if (USE_CACHE == 'true') {
              tep_reset_cache_block('categories');
              tep_reset_cache_block('also_purchased');
            }

    	    $url_rewrite->purge_item(sprintf('c%d',$categories_id),1);

            tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $new_parent_id . '&cID=' . $categories_id));
		}
	}
break;

case 'move_product_confirm':
	$products_id = tep_db_prepare_input($_POST['products_id']);
	$new_parent_id = tep_db_prepare_input($_POST['move_to_category_id']);

	$duplicate_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$new_parent_id . "'");
	$duplicate_check = tep_db_fetch_array($duplicate_check_query);

	if ($duplicate_check['total'] < 1) tep_db_query("update " . TABLE_PRODUCTS_TO_CATEGORIES . " set categories_id = '" . (int)$new_parent_id . "' where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$current_category_id . "'");

	if (USE_CACHE == 'true') {
		tep_reset_cache_block('categories');
		tep_reset_cache_block('also_purchased');
	}

	$url_rewrite->purge_item(sprintf('p%d',$products_id));
	tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $new_parent_id . '&pID=' . $products_id));
break;

case 'insert_product':
case 'update_product':

	if (isset($_POST['edit_x']) || isset($_POST['edit_y']) || !isset($_POST['products_status'])) {
          $action = 'new_product';
	} else {
		if ($_POST['delete_image'] == 'yes') {
				unlink(DIR_FS_CATALOG_IMAGES . $_POST['products_previous_image']);
			}

	    for ($i=1;$i<=4;$i++) if ($_POST['delete_image_xl_'.$i] == 'yes') {
			unlink(DIR_FS_CATALOG_IMAGES . $_POST['products_previous_image_xl_'.$i]);
		}

        if (isset($_GET['pID'])) $products_id = tep_db_prepare_input($_GET['pID']);
		

		if($pClass) $productObject = tep_module($pClass,'product');
		if(!isset($productObject)) { 
			$productObject = tep_module('product_default','product');	
		}

		$costPrice = tep_db_prepare_input($_POST['products_price_myself']);
		$suppliers = ($_POST['products_x_supplier'] == '0') ? '1' : tep_db_prepare_input($_POST['products_x_supplier']);
		$suppliers_id = ($_POST['suppliers_id'] == '0') ? '1' : tep_db_prepare_input($_POST['suppliers_id']);
		$products_price = tep_db_prepare_input($_POST['products_price']);
		$products_sku = tep_db_prepare_input($_POST['products_sku']);
		$products_msrp = tep_db_prepare_input($_POST['products_msrp']);
		$casepack_sku = tep_db_prepare_input($_POST['casepack_sku']);
		$casepack_qty = tep_db_prepare_input($_POST['casepack_qty']);
		$suppliers_sku = tep_db_prepare_input($_POST['suppliers_sku']);
		$reup_threshold = tep_db_prepare_input($_POST['reup_threshold']);
		$reup_quantity = tep_db_prepare_input($_POST['reup_quantity']);


		$suppliers_entry = tep_db_query("SELECT * FROM ".TABLE_SUPPLIERS_PRODUCTS_GROUPS." 
										 WHERE suppliers_group_id='".$suppliers_id."' 
										 AND priority ='0' 
										 AND products_id = '".(int)$products_id."'
										");

		$supply = tep_db_fetch_array($suppliers_entry);

		$priority = tep_db_prepare_input($_POST['priority']);

			if($priority == '0' && $supply['priority'] == '0') {

				tep_db_query("DELETE FROM ". TABLE_SUPPLIERS_PRODUCTS_GROUPS ."
							  WHERE priority = '0'
							  AND suppliers_group_id='".$suppliers_id."' 
							  AND products_id = '".(int)$products_id."'
							 ");
			}
	
          $sql_data_array = array('products_class' => $pClass,
								  'products_quantity' => tep_db_prepare_input($_POST['products_quantity']),
                                  'products_model' => tep_db_prepare_input($_POST['products_model']),
                                  'products_price' => $products_price,
                                  'products_price_myself' => $costPrice,
								  'products_last_modified' => $now,
								  'products_qty_blocks' => (($i=tep_db_prepare_input($_POST['products_qty_blocks'])) < 1) ? 1 : $i,
                                  'products_weight' => tep_db_prepare_input($_POST['products_weight']),
                                  'products_status' => tep_db_prepare_input($_POST['products_status']),
                                  'bs_icon' => tep_db_prepare_input($_POST['bs_icon']),
                                  'products_free_shipping' => tep_db_prepare_input($_POST['products_free_shipping']),
                                  'products_separate_shipping' => tep_db_prepare_input($_POST['products_separate_shipping']),
                                  'products_show_qview' => tep_db_prepare_input($_POST['products_show_qview']),
                                  'products_sort_order' => tep_db_prepare_input($_POST['products_sort_order']),
                                  'products_tax_class_id' => tep_db_prepare_input($_POST['products_tax_class_id']),
                                  'manufacturers_id' => tep_db_prepare_input($_POST['manufacturers_id']),
                                  'products_upc' => tep_db_prepare_input($_POST['products_upc']),
                                  'products_sku' => $products_sku,
                                  'products_make' => tep_db_prepare_input($_POST['products_make']),
                                  'products_width' => tep_db_prepare_input($_POST['products_width']),
                                  'products_height' => tep_db_prepare_input($_POST['products_height']),
                                  'products_length' => tep_db_prepare_input($_POST['products_length']),
								  'suppliers_id' => $suppliers,
                                  'products_harmonized_code' => (int)$_POST['products_harmonized_code'],
                                  'products_origin_country' => tep_db_prepare_input($_POST['products_origin_country']),
								  );

          $supplier_data_array = array('suppliers_group_id' => $suppliers,
                                  'suppliers_group_price' => $costPrice,
                                  'products_id' => (int)$products_id,
                                  'products_msrp' => $products_msrp,
								  'casepack_sku' => $casepack_sku,
								  'casepack_qty' => $casepack_qty,
								  'suppliers_sku' => $suppliers_sku,
								  'reup_threshold' => $reup_threshold,
								  'reup_quantity' => $reup_quantity,
								  'priority' => $priority);

		
	if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

		// # Insert the warehouse inventory levels if found.
		if(is_array($_POST['products_warehouse_id']) && is_array($_POST['products_warehouse_quantity'])) { 

			$inventory_array = array_combine($_POST['products_warehouse_id'], $_POST['products_warehouse_quantity']);

			foreach($inventory_array as $warehouse_id => $warehouse_products_quantity) { 

				$warehouse_inventory_array = array('products_quantity' => $warehouse_products_quantity);

				tep_db_perform(TABLE_PRODUCTS_WAREHOUSE_INVENTORY, $warehouse_inventory_array, 'update', "products_id = '".$_GET['pID']."' AND products_warehouse_id = '".$warehouse_id."'");
			}
		}

		if(!empty($_POST['addWarehouse'])) {
	
			$warehouse_id = (int)$_POST['addWarehouse'];

			$warehouse_name = tep_db_result(tep_db_query("SELECT products_warehouse_name FROM products_warehouse_profiles WHERE products_warehouse_id = '".$warehouse_id."'"),0);

			tep_db_query("INSERT IGNORE INTO ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY . " 
						  SET products_warehouse_id = '". $warehouse_id ."',
						  products_id = '". $_GET['pID'] ."',
						  products_quantity = '0',
						  products_warehouse_name = '". $warehouse_name ."'
						");
		}
	}


		if(empty($_POST['dateAvailable'])) { 

			$sql_data_array['products_date_available'] = 'null';

		} else {

			$now = date('Y-m-d H:i:s', time());	

			$postedDate = date('Y-m-d H:i:s', strtotime($_POST['dateAvailable']));

			if($postedDate > 0 && $postedDate > $now) { 

				$sql_data_array['products_date_available'] = $postedDate;
			}

		}
                                

		if (($_POST['unlink_image'] == 'yes') or ($_POST['delete_image'] == 'yes')) {
            $sql_data_array['products_image'] = '';
		} else {
			if (isset($products_image_name)) {
				$sql_data_array['products_image'] = $products_image_name;
			} elseif (isset($_POST['products_image']) && tep_not_null($_POST['products_image']) && ($_POST['products_image'] != 'none')) {
            	$sql_data_array['products_image'] = tep_db_prepare_input($_POST['products_image']);
          	}
		}

		for ($i=1;$i<=4;$i++) {
			if (($_POST['unlink_image_xl_'.$i] == 'yes') or ($_POST['delete_image_xl_'.$i] == 'yes')) {
				$sql_data_array['products_image_xl_'.$i] = '';
			} else {
	  			if (isset($products_image_xl_name[$i])) {
				    $sql_data_array['products_image_xl_'.$i] = $products_image_xl_name[$i];
          		} elseif (isset($_POST['products_image_xl_'.$i]) && tep_not_null($_POST['products_image_xl_'.$i]) && ($_POST['products_image_xl_'.$i] != 'none')) {
         		   $sql_data_array['products_image_xl_'.$i] = tep_db_prepare_input($_POST['products_image_xl_'.$i]);
          		}
         	}
		}




if ($action == 'insert_product') {
            $insert_sql_data = array('products_date_added' => $now);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();

            tep_db_query("INSERT INTO " . TABLE_PRODUCTS_TO_CATEGORIES . " 
						  SET products_id = '".(int)$products_id."', 
						  categories_id = '". (int)$current_category_id."'
						");

            tep_db_query("UPDATE " . TABLE_PRODUCTS . " 
						  SET master_products_id = '$products_id' 
						  WHERE products_id = '$products_id'
						 ");
	
			tep_db_query("INSERT INTO " . TABLE_SUPPLIERS_PRODUCTS_GROUPS . " 
						  SET suppliers_group_id = '".$suppliers."', 
						  suppliers_group_price = '". $costPrice."', 
						  products_id = '".(int)$products_id."', 
						  products_msrp = '".$products_msrp."',
						  casepack_sku = '".$casepack_sku."',
						  casepack_qty = '".$casepack_qty."',
						  suppliers_sku = '".$suppliers_sku."',
						  reup_threshold = '".$reup_threshold."',
						  reup_quantity = '".$reup_quantity."',
						  priority = '".$priority."'
						 ");

} elseif($action == 'update_product') {


		$update_sql_data = array('products_last_modified' => $now); 
		$sql_data_array = array_merge($sql_data_array, $update_sql_data);
	

		tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");

		if(mysql_num_rows($suppliers_entry) < 1) {

			tep_db_query("REPLACE INTO " . TABLE_SUPPLIERS_PRODUCTS_GROUPS . " 
						  SET suppliers_group_id = '".$suppliers."', 
						  suppliers_group_price = '".$costPrice."', 
						  products_id = '".(int)$products_id."', 
						  products_msrp = '".$products_msrp."',
						  casepack_sku = '".$casepack_sku."',
						  casepack_qty = '".$casepack_qty."',
						  suppliers_sku = '".$suppliers_sku."',
						  reup_threshold = '".$reup_threshold."',
						  reup_quantity = '".$reup_quantity."',
						  priority = '".$priority."'
						 ");
		} else { 

			tep_db_perform(TABLE_SUPPLIERS_PRODUCTS_GROUPS, $supplier_data_array, 'update', "products_id = '$products_id' AND suppliers_group_id = '".	$suppliers_id."'");
	
		}



} // # END $action == 'update_product'

	if (isset($_POST['products_extra'])) {
		foreach ($_POST['products_extra'] AS $xkey=>$xval) {
			tep_set_products_extra($products_id,$xkey,$xval);
		}
	}

	$prDiff = array();
	
	// # Update Models
	$product_ids = array($products_id);
	$default_img = array(NULL);
	$default_model = NULL;
	$imgs_srt = array();
	$sql_data_array['master_products_id'] = $products_id;

	$modelFields = array('model_quantity'=>'products_quantity',
						 'model_name'=>'products_model',
						 'model_sku'=>'products_sku',
						 'model_upc' => 'products_upc',
						 );
	$curr_models = tep_get_product_models($products_id);

	$model_upload = array();

	if(isset($_POST['model_attrs']) && is_array($_POST['model_attrs'])) foreach ($_POST['model_attrs'] AS $midx=>$attrs) {
	
		if (preg_match_all('/(\w+):(\w+)/',$attrs,$attrparse)) {

			$postattrs = array();

			foreach ($attrparse[1] AS $apidx=>$aid) {
				$postattrs[$attrparse[1][$apidx]] = $attrparse[2][$apidx];
			}

			$model_pid = NULL;

			foreach ($curr_models AS $mid => $mattrs) {

				if (sizeof($postattrs) != sizeof($mattrs)) continue;

				$model_pid = $mid;

				foreach ($postattrs AS $aid=>$av) {
					if(!isset($mattrs[$aid]) || $mattrs[$aid] != $av) {
						$model_pid = NULL;
						break;
					}
	
					if ($model_pid) {
						break;
					}
				}
			}


			    foreach ($modelFields AS $mfld => $dfld) { 
					if(isset($_POST[$mfld][$midx])) {
						$sql_data_array[$dfld] = $_POST[$mfld][$midx]; 
					} else {
						unset($sql_data_array[$dfld]);
					}
				}

				if(isset($_POST['model_date_available'][$midx]) && !preg_match('/^\s*$/',$_POST['model_date_available'][$midx])) { 
				    $model_av = $_POST['model_date_available'][$midx];
				} else { 
					$model_av = NULL;
				}

				if(isset($_POST['model_asin'][$midx])) { 
				    $model_asin = $_POST['model_asin'][$midx];
				} else { 
					$model_asin = NULL;
				}

			    $sql_data_array['products_date_available'] = $model_av ? date('Y-m-d',strtotime($model_av)) : NULL;
    			$model_price = $_POST['products_price'];
	
			    if(isset($_POST['model_price'][$midx])) { 
					if(isset($_POST['model_price_sign'][$midx]) && $_POST['model_price_sign'][$midx]=='-') {
						$model_price += (-1 * $_POST['model_price'][$midx]);
					} else {
						$model_price += (1 * $_POST['model_price'][$midx]);
					}
				}
	
			    $sql_data_array['products_price'] = $model_price;
	
    			$model_img = array('','','','','');

				if(isset($_POST['model_image_ptr'][$midx])) {
					foreach (explode('/',$_POST['model_image_ptr'][$midx]) AS $idx=>$imgptr) {
						if (preg_match('/^(\w+):(.*)/',$imgptr,$img_ptr_parse)) {
							if($img_ptr_parse[1]=='file') { 
								$model_img[$idx]=$img_ptr_parse[2];
							}
						} elseif($img_ptr_parse[1]=='upload') { 
							$model_img[$idx]=get_upload_file('upload_model_image_'.$img_ptr_parse[2]);
	    	  			}
					}
				}

				$sql_data_array['products_image'] = $model_img[0];

				for($i=1;$i<=4;$i++) {
					$sql_data_array['products_image_xl_'.$i]=$model_img[$i];
				}

				if($model_pid) {
		
					$url_rewrite->purge_item(sprintf('p%d',$model_pid));
				
					tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '$model_pid'");

					unset($curr_models[$model_pid]);

					$new_model_pid = NULL;
	
			    } else {

					tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
					$new_model_pid = $model_pid = tep_db_insert_id();
			    }

			    $product_ids[] = $model_pid;

			    if (!isset($default_model)) {
					$default_model = $sql_data_array['products_model'];
				}
	    
			    IXdb::query("DELETE FROM products_discount WHERE products_id='" . $model_pid . "'");

			    if($_POST['model_pricing_list'][$midx]) {
	
					foreach (explode(';',$_POST['model_pricing_list'][$midx]) AS $prlst) {
		
			        	$prq = explode(',',$prlst);
			
						if($prq[0]) {
							list($cgrp,$prq0)=explode(':',$prq[0]);
							$prDiff[$model_pid][$cgrp] = $prq0;
	
							for($qi=1;isset($prq[$qi]);$qi++) {
								$prqi = explode(':',$prq[$qi]);
								IXdb::query("INSERT INTO products_discount 
											 SET products_id ='".$model_pid."',
											 customers_group_id = '".$cgrp."',
											 discount_qty = '".addslashes($prqi[0])."',
											 discount_percent = '".addslashes($prqi[1])."'
											");
							}
						}
					}
				}

	    		$prDiff[$model_pid][0] = ($model_price - $_POST['products_price']);
	    
			    if(isset($_POST['model_extra'])) {
					foreach ($_POST['model_extra'] AS $xkey=>$xval) {
						if (isset($xval[$midx])) {
							tep_set_products_extra($model_pid,$xkey,$xval[$midx]);
						}
					}
				}

			    $srt = array();

				foreach($postattrs AS $aid=>$av) {

					$optns_sort = $_POST['options_sort_order'][$aid];
					$attrs_sort = isset($_POST['attrs_sort_order'][$aid.'_'.$av]) ? $_POST['attrs_sort_order'][$aid.'_'.$av] : '';
					$attrs_sortq = ($attrs_sort == '') ? 'NULL' : "'$attrs_sort'";
					$srt[$aid] = $attrs_sort;
					$attrs_img = '';

					if(isset($_POST['attr_image'][$aid.'_'.$av]) && preg_match('/^(\w+):(.*)/',$_POST['attr_image'][$aid.'_'.$av],$at_img_parse)) {
						if($at_img_parse[1] == 'file') {
							$attrs_img = $at_img_parse[2];
						} elseif($at_img_parse[1] == 'upload') { 
							$attrs_img = get_upload_file('upload_attr_image_'.$at_img_parse[2]);
						}
					}

					if($new_model_pid) {
						tep_db_query("INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES."
									  SET products_id = '". $new_model_pid ."',
									  options_id = '". $aid ."',
									  options_values_id = '". $av ."',
									  options_sort = '". $optns_sort ."',
									  options_values_sort = ". $attrs_sortq .",
									  options_image = '".addslashes($attrs_img)."'
									 ");
					} else { 
						tep_db_query("UPDATE ".TABLE_PRODUCTS_ATTRIBUTES." 
									  SET options_sort = '".$optns_sort."',
									  options_values_sort = $attrs_sortq,
									  options_image = '".addslashes($attrs_img)."' 
									  WHERE products_id = '".$model_pid."' 
									  AND options_id='".$aid."'
									 ");
					}
				}

	    for($i=0;isset($model_img[$i]);$i++) {
			if ($model_img[$i] && (!isset($default_img[$i]) || $srt<$imgs_srt)) $default_img[$i]=$i ?'' :$model_img[$i];
		}
	    $imgs_srt=$srt;
	  }
	}

	foreach ($curr_models AS $mid=>$mdata) {
	  $url_rewrite->purge_item(sprintf('p%d',$mid));
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_DESCRIPTION." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_STOCK." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_TO_CATEGORIES." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_DISCOUNTS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_IMAGES." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_NOTIFICATIONS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_GROUPS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_SPECIALS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_SPECIALS_RETAIL_PRICES." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_FEATURED." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_DBFEED_PRODUCTS." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_DBFEED_PRODUCTS_EXTRA." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_CUSTOMERS_BASKET." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_CUSTOMERS_BASKET_ATTRIBUTES." WHERE products_id='$mid'");
	  tep_db_query("DELETE FROM ".TABLE_PRODUCTS_XSELL." WHERE products_id='$mid'");
	  // # Delete from URL rewrite MAP table.
	  tep_db_query("DELETE FROM ".TABLE_URL_REWRITE." WHERE url_original = '/product_info.php/products_id/".$mid."'");
	  tep_db_query("DELETE FROM ".TABLE_URL_REWRITE_MAP." WHERE item_id = 'p".$mid."'");


	}

	if($default_img){
	  $imgq=Array();
	  foreach ($default_img AS $idx=>$img) if (isset($img)) $imgq[$idx?'products_image_xl_'.$idx:'products_image']=$img;
	  if (isset($default_model)) $imgq['products_model']=$default_model;
	  if ($imgq) tep_db_perform(TABLE_PRODUCTS,$imgq,'update',"products_id='".(int)$products_id."'");
	}


foreach ($cus_groups AS $cgrp=>$cgname){
    if ($cgrp && $_POST['sppcoption'][$cgrp] && $_POST['sppcprice'][$cgrp]) {
      $prc=$_POST['sppcprice'][$cgrp];
      $rate=$_POST['products_price']>0?$prc/$_POST['products_price']:1;
      foreach ($product_ids AS $pid) {
        $mprc=isset($prDiff[$pid][$cgrp])?$prc+$prDiff[$pid][$cgrp]:$prc+$rate*$prDiff[$pid][0];
        IXdb::query("REPLACE into products_groups (products_id, customers_group_id, customers_group_price) VALUES ('$pid','$cgrp','$mprc')");
      }
    } else
      tep_db_query("delete from " . TABLE_PRODUCTS_GROUPS . " where customers_group_id = '" . $cgrp . "' and products_id IN (".join(',',$product_ids).")");
    if (isset($_POST[discount_qty][$cgrp])) {
      tep_db_query("DELETE FROM products_discount WHERE products_id='" . $products_id . "' AND customers_group_id='" . $cgrp . "'");
      for ($i=0;isset($_POST[discount_qty][$cgrp][$i]);$i++) if ($_POST[discount_qty][$cgrp][$i]>0) tep_db_query("INSERT INTO products_discount (products_id,customers_group_id,discount_qty,discount_percent) VALUES ('$products_id','$cgrp','".addslashes($_POST[discount_qty][$cgrp][$i])."','".addslashes($_POST[discount_percent][$cgrp][$i])."')");
    }
}
  // # END Separate Pricing Per Customer


// # XSell - Cross sell

if (isset($_POST['xsell'])) {
  tep_db_query("DELETE FROM products_images WHERE products_id IN (".join(',',$product_ids).") AND image_group='linked'");
  foreach ($_POST['xsell'] AS $ch=>$xsell) {
    tep_db_query("DELETE FROM products_xsell WHERE products_id IN (".join(',',$product_ids).") AND xsell_channel='".addslashes($ch)."'");
    foreach ($xsell AS $xidx=>$xsell_id) if ($xsell_id) {
      $pids=Array();
      if ($_POST['xsell_model'][$ch][$xidx]) {
        foreach (explode(',',$_POST['xsell_model'][$ch][$xidx]) AS $mid) $pids[]=$mid;
	tep_db_query("REPLACE INTO products_xsell (products_id,xsell_channel,xsell_id,sort_order) VALUES ('$mid','".addslashes($ch)."','".addslashes($xsell_id)."','".addslashes($xidx)."')");
      } else $pids[]=$products_id;
      $img=$_POST['xsell_currimage'][$ch][$xidx];
      if (isset($_FILES['xsell_image']['name'][$ch][$xidx]) && $_FILES['xsell_image']['size'][$ch][$xidx] && !$_FILES['xsell_image']['error'][$ch][$xidx]) {
        $newimg=preg_replace('[^\w\-\.]','',preg_replace('|^.*[\\\/]|','',$_FILES['xsell_image']['name'][$ch][$xidx]));
	if (@rename($_FILES['xsell_image']['tmp_name'][$ch][$xidx],DIR_FS_CATALOG_IMAGES.$newimg)) $img=$newimg;
      }
      foreach ($pids AS $xpid) {
        tep_db_query("REPLACE INTO products_xsell (products_id,xsell_channel,xsell_id,sort_order,price_percent,price_diff,price_limit) VALUES ('$xpid','".addslashes($ch)."','".addslashes($xsell_id)."','".addslashes($xidx)."','".(-$_POST['xsell_price_percent'][$ch][$xidx])."','".(-$_POST['xsell_price_diff'][$ch][$xidx])."',".($_POST['xsell_price_limit'][$ch][$xidx]?"'".$_POST['xsell_price_limit'][$ch][$xidx]."'":"NULL").")");
        if ($img) tep_db_query("REPLACE INTO products_images (products_id,image_group,ref_id,image_file,sort_order) VALUES ('$xpid','linked','".addslashes($xsell_id)."','$img','1')");
      }
    }
  }
}

$DBFeedMods->adminProductSave($products_id, $products_sku);



          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('products_name' => tep_db_prepare_input($_POST['products_name'][$language_id]),
                                    'products_info' => tep_db_prepare_input($_POST['products_info'][$language_id]),
				   					'products_info_alt' => tep_db_prepare_input($_POST['products_info_alt'][$language_id]),
                                    'products_description' => tep_db_prepare_input(collectTabEdit($_POST['products_description'][$language_id])),
                                    'products_url' => tep_db_prepare_input($_POST['products_url'][$language_id]),
                                    'products_head_title_tag' => tep_db_prepare_input($_POST['products_head_title_tag'][$language_id]),
                                    'products_head_desc_tag' => tep_db_prepare_input($_POST['products_head_desc_tag'][$language_id]),
                                    'products_head_keywords_tag' => tep_db_prepare_input($_POST['products_head_keywords_tag'][$language_id]),
                                    'products_qview_desc' => tep_db_prepare_input($_POST['products_qview_desc'][$language_id]));   

            if ($action == 'insert_product') {
              $insert_sql_data = array('products_id' => $products_id,
                                       'language_id' => $language_id);

              $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);

	tep_db_query("INSERT IGNORE INTO " . TABLE_PRODUCTS_GROUPS . " (customers_group_id, customers_group_price, products_id) values ('0', '".$products_price."', '" . (int)$products_id . "')") or die( "products_groups insert error occured: " . mysql_error() );

            } elseif ($action == 'update_product') {

				// # only re-write the Prduct URL if we change product name
				$currentProdName = mysql_result(tep_db_query("SELECT products_name FROM ". TABLE_PRODUCTS_DESCRIPTION." WHERE products_id = '".$products_id."'"),0);

				$newProdName = $_POST['products_name'][$language_id];

				if($currentProdName !== $newProdName) {
					$url_rewrite->purge_item(sprintf('p%d',$products_id));
				}

				// # END Product URL rewrite class call.

              tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");

			tep_db_query("INSERT IGNORE INTO " . TABLE_PRODUCTS_GROUPS . " (customers_group_id, customers_group_price, products_id) values ('0', '".$products_price."', '" . (int)$products_id . "')") or die( "products_groups insert error occured: " . mysql_error() );

            }
          }

          if (USE_CACHE == 'true') {
            tep_reset_cache_block('categories');
            tep_reset_cache_block('also_purchased');
          }

          tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products_id. (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '')));
        }
        break;
	
	case 'copy_to_confirm':

		if(isset($_POST['products_id']) && isset($_POST['categories_id'])) {

			$products_id = (int)$_POST['products_id'];
			$categories_id = tep_db_prepare_input($_POST['categories_id']);

			if ($_POST['copy_as'] == 'link') {

				if ($categories_id != $current_category_id) {

					$check_query = tep_db_query("SELECT COUNT(0) AS total 
												  FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " 
												  WHERE products_id = '" . (int)$products_id . "' 
												  AND categories_id = '" . (int)$categories_id . "'
												");

					$check = tep_db_fetch_array($check_query);

					if ($check['total'] < '1') {

						tep_db_query("INSERT INTO " . TABLE_PRODUCTS_TO_CATEGORIES . " 	
									  SET products_id = '" . (int)$products_id . "', 
									  categories_id = '" . (int)$categories_id . "'
									");
					}

				} else {

					$messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
				}

			} elseif($_POST['copy_as'] == 'duplicate') {

				$i = 1;

				$data_query = tep_db_query("SELECT * FROM products WHERE master_products_id = '" . (int)$products_id . "'");

				while($product = tep_db_fetch_array($data_query)) {

					  $product_data_array = array('products_quantity' => $product['products_quantity'],
												  'products_class' => $product['products_class'],
												  'stock_class' => $product['stock_class'],
												  'products_model' => $product['products_model'],
												  'products_sku' => $product['products_sku'],
												  'products_upc' => $product['products_upc'],
												  'featured_product' => $product['Featured_product'],
												  'products_image' => $product['products_image'],
												  'products_image_xl_1' => $product['products_image_xl_1'],
												  'products_image_xl_2' => $product['products_image_xl_2'],
												  'products_image_xl_3' => $product['products_image_xl_3'],
												  'products_image_xl_4' => $product['products_image_xl_4'],
												  'products_image_xl_5' => $product['products_image_xl_5'],
												  'products_image_xl_6' => $product['products_image_xl_6'],
												  'products_image_xl_7' => $product['products_image_xl_7'],
												  'products_image_xl_8' => $product['products_image_xl_8'],
												  'products_price' => $product['products_price'],
												  'products_date_added' => date('Y-m-d H:i:s', time()),
												  'products_last_modified' => date('Y-m-d H:i:s', time()),
												  'last_stock_change' => date('Y-m-d H:i:s', time()),
												  'products_date_available' => $product['products_date_available'],
												  'products_featured_until' => $product['products_featured_until'],
												  'products_weight' => $product['products_weight'],
												  'products_status' => '0',
												  'products_featured' => $product['products_featured'],
												  'products_tax_class_id' => $product['products_tax_class_id'],				 
												  'manufacturers_id' => $product['manufacturers_id'],
												  'products_make' => $product['products_make'],
												  'products_ordered' => $product['products_ordered'],
												  'products_length' => $product['products_length'],
												  'products_width' => $product['products_width'],
												  'products_height' => $product['products_height'],
												  'products_ready_to_ship' => $product['products_ready_to_ship'],
												  'products_price_competitor' => $product['products_price_competitor'],
												  'products_qty_blocks' => $product['products_qty_blocks'],
												  'products_free_shipping' => $product['products_free_shipping'],
												  'products_show_qview' => $product['products_show_qview'],
												  'products_separate_shipping' => $product['products_separate_shipping'],
												  'product_special' => $product['product_special'],
												  'products_seo_page_name' => $product['products_seo_page_name'],
												  'bs_icon' => 0,
												  'qbi_imported' => $product['qbi_imported'],
												  'supplier_id' => $product['supplier_id'],
												  'suppliers_id' => $product['suppliers_id'],
												  'cost_price' => $product['cost_price'],
												  'products_price_myself' => $product['products_price_myself'],
												  'products_sort_order' => $product['products_sort_order'],
												  'purchase_handler' => $product['purchase_handler'],
												  'purchase_handler_data' => $product['purchase_handler_data'],
												  'products_template' => $product['products_template'],
												  'products_harmonized_code' => $product['products_harmonized_code'],
												  'products_origin_country' => $product['products_origin_country']
												);


            			tep_db_perform(TABLE_PRODUCTS, $product_data_array);
			            $new_id = tep_db_insert_id();

						if($i == 1) {
							tep_db_query("UPDATE ".TABLE_PRODUCTS." SET master_products_id = $new_id WHERE products_id = $new_id");
						}

						$attr_query = tep_db_query("SELECT * FROM products_attributes WHERE products_id = '" . $product['products_id'] . "'");
				
						while($attr = tep_db_fetch_array($attr_query)) {

							tep_db_query("INSERT INTO products_attributes 
										  SET products_id='" . $new_id . "',
										  options_id='" . $attr['options_id'] . "',
										  options_values_id='" . $attr['options_values_id'] . "',
										  options_values_price='" . $attr['options_values_price'] . "',
										  price_prefix='" . $attr['price_prefix'] . "'
										");
						}

						$xsell_query = tep_db_query("SELECT * FROM products_xsell WHERE products_id = '" . $product['products_id'] . "'");

						while($xsellarray = tep_db_fetch_array($xsell_query)) {

							tep_db_query("INSERT INTO products_xsell 
										  SET products_id = '" . $new_id . "', 
										  xsell_id = '" . $xsellarray['xsell_id'] . "',
										  sort_order = '" . $xsellarray['sort_order'] . "',
										  xsell_channel = '" . $xsellarray['xsell_channel'] . "',
										  price_percent = '" . $xsellarray['price_percent'] . "',
										  price_diff = '" . $xsellarray['price_diff'] . "',
										  price_limit = '" . $xsellarray['price_limit'] . "'
										");
						}
	  
						$i++;

					} // # END while($product ...

					$dup_products_id = tep_db_insert_id();


					// # Query modified for short descriptions

					$products_details_query = tep_db_query("SELECT * FROM products_description 
														    WHERE products_id = '" . (int)$products_id . "'
										  					");
           
					while ($prod_details = tep_db_fetch_array($products_details_query)) {
 
						tep_db_query("INSERT INTO " . TABLE_PRODUCTS_DESCRIPTION . " 
									  SET products_id = '" . $new_id . "', 
									  language_id = '" . (int)$prod_details['language_id'] . "',
									  products_name = 'Copy of " . tep_db_input($prod_details['products_name']) . "',
									  products_short = '" . tep_db_input($prod_details['products_short']) . "',
									  products_description = '" . tep_db_input($prod_details['products_description']) . "',
									  products_url =  '" . tep_db_input($prod_details['products_url']) . "',
									  products_viewed = '" . tep_db_input($prod_details['products_viewed']) . "', 
									  products_info = '" . tep_db_input($prod_details['products_info']) . "',
									  products_head_title_tag = '" . tep_db_input($prod_details['products_head_title_tag']) . "',
									  products_head_desc_tag = '" . tep_db_input($prod_details['products_head_desc_tag']) . "',
									  products_head_keywords_tag = '" . tep_db_input($prod_details['products_head_keywords_tag']) . "',
									  products_info_alt = '" . tep_db_input($prod_details['products_info_alt']) . "',
									  additional_features = '" . tep_db_input($prod_details['additional_features']) . "',
									  products_qview_desc = '" . tep_db_input($prod_details['products_qview_desc']) . "'
									");

					}
		
					tep_db_query("INSERT IGNORE INTO " . TABLE_PRODUCTS_TO_CATEGORIES . " 
								  SET products_id = '" . $new_id . "', 
								  categories_id = '" . (int)$categories_id . "'
								 ");
 

					$customergrp_query = tep_db_query("SELECT customers_group_id, customers_group_price 
													   FROM products_groups 
													   WHERE products_id = '" . (int)$products_id . "' 
													   ORDER BY customers_group_id
													  ");

					while($customergrp = tep_db_fetch_array($customergrp_query)) {

		    	        tep_db_query("INSERT INTO products_groups 
									  SET customers_group_id = '" . $customergrp['customers_group_id'] . "', 
									  customers_group_price = '" . tep_db_input($customergrp['customers_group_price']) . "', 
									  products_id = '".$new_id."'
									 ");
					}


					$products_id = $dup_products_id;
				}


				if (USE_CACHE == 'true') {
		            tep_reset_cache_block('categories');
            		tep_reset_cache_block('also_purchased');
				}
			}


			tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $categories_id . '&pID=' . $new_id));
	
			break;

		}
	}

	// # check if the catalog image directory exists
	if (is_dir(DIR_FS_CATALOG_IMAGES)) {
		if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
	} else {
    	$messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
	}

	if($pClass) {
		$productObject=tep_module($pClass,'product');
	}

	if(!isset($productObject)) {
		$productObject=tep_module('product_default','product');
	}
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<META HTTP-EQUIV="MSThemeCompatible" CONTENT="no">
<title>Category &amp; Product Editor</title>

<link rel="stylesheet" href="js/css.css" type="text/css">
<script type="text/javascript" src="js/prototype.lite.js"></script>
<script type="text/javascript" src="js/expander-list.js"></script>
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="js/tips.js"></script>

<?php //require_once( 'attributeManager/includes/attributeManagerHeader.inc.php' )?>

<script type="text/javascript">
function contentChanged() {
  top.resizeIframe('myframe');
}
</script>

<link rel="stylesheet" href="js/tabber.css" type="text/css">

<script type="text/javascript">
document.write('<style type="text/css">.tabber{display:none;}<\/style>');
</script>

<script type="text/javascript">
<?php
  $modelFieldsJSDefs=Array();
  foreach ($ModelFieldsList AS $fld) $modelFieldsJSDefs[]="$fld:new Array()";
?>

var modelFields={<?php echo join(',',$modelFieldsJSDefs)?>};

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

<?php
if(isset($_GET['action']) && $_GET['action'] == 'new_product') {
	echo '<script type="text/javascript" src="js/popcalendar.js"></script>';
}

if(isset($_GET['edittpl']) && $_GET['edittpl'] == 1) {
?>
<style type="text/css">

.black_overlay {
	display: none;
	position: absolute;
	top: 0%;
	left: 0%;
	width: 100%;
	height: 100%;
	background-color: #ccc;
	z-index:1001;
	-moz-opacity: 0.7;
	opacity:.70;
	filter: alpha(opacity=70);
}

.white_content {
	display: none;
	position: absolute;
	top: 10%;
	left: 10%;
	width: 75%;
	height: 60%;
	padding: 10px 15px 15px 15px;
	border: 10px solid #053389;
	background-color: white;
	z-index:1002;
	overflow: hidden;
	-moz-border-radius: 15px;
	border-radius: 15px;
	-webkit-border-radius: 15px;
}

.modelSetFeeds select {
	max-width:400px;
	width:auto;
}

</style>

</head>
<body style="background-color:transparent; margin:0" onload="document.getElementById('light').style.display='block'; document.getElementById('fade').style.display='block';">
<?php } else { ?>

<style>

.tabEven:hover,.tabOdd:hover  { 
	cursor:pointer;
	background-color:#FFFFC4 !important;
}

</style>
</head>
<body style="background-color:transparent; margin:0">
<?php } ?>
<script type="text/javascript" language="JavaScript">
<!-- 
var cX = 0; var cY = 0; var rX = 0; var rY = 0;
function UpdateCursorPosition(e){ cX = e.pageX; cY = e.pageY;}
function UpdateCursorPositionDocAll(e){ cX = event.clientX; cY = event.clientY;}
if(document.all) { document.onmousemove = UpdateCursorPositionDocAll; }
else { document.onmousemove = UpdateCursorPosition; }
function AssignPosition(d) {
if(self.pageYOffset) {
	rX = self.pageXOffset;
	rY = self.pageYOffset;
	}
else if(document.documentElement && document.documentElement.scrollTop) {
	rX = document.documentElement.scrollLeft;
	rY = document.documentElement.scrollTop;
	}
else if(document.body) {
	rX = document.body.scrollLeft;
	rY = document.body.scrollTop;
	}
if(document.all) {
	cX += rX; 
	cY += rY;
	}
d.style.left = (cX-300) + "px";
d.style.top = (cY+10) + "px";
}
function HideContent(d) {
if(d.length < 1) { return; }
document.getElementById(d).style.display = "none";
}
function ShowContent(d) {
if(d.length < 1) { return; }
var dd = document.getElementById(d);
AssignPosition(dd);
dd.style.display = "block";
}
function ReverseContentDisplay(d) {
if(d.length < 1) { return; }
var dd = document.getElementById(d);
AssignPosition(dd);
if(dd.style.display == "none") { dd.style.display = "block"; }
else { dd.style.display = "none"; }
}
//-->
</script>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
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
                       'products_date_added' => $now,
                       'products_last_modified' => $now,
                       'products_date_available' => $now,
                       'bs_icon' => '0',
                       'products_free_shipping' => '0',
                       'products_separate_shipping' => '0',
                       'products_show_qview' => '1',
		     		   'products_sort_order' => 100,
                       'products_status' => '1',
                       'products_tax_class_id' => '',
                       'manufacturers_id' => '');

    $pInfo = new objectInfo($parameters);

    if (isset($_GET['pID']) && empty($HTTP_POST_VARS)) {

      $product_query = tep_db_query("select pd.*,p.* from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$_GET['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");

      $product = tep_db_fetch_array($product_query);
      $pInfo->objectInfo($product);

    } elseif (tep_not_null($HTTP_POST_VARS)) {

      $pInfo->objectInfo($HTTP_POST_VARS);

      $products_name = $_POST['products_name'];
      $products_info = $_POST['products_info'];
      $products_info_alt = $_POST['products_info_alt'];
      $products_description = $_POST[''];
      $products_sku = $_POST['products_sku'];
      $products_url = $_POST['products_url'];
      $products_qview_desc = $_POST['products_qview_desc'];
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

    $breadcrumb->add($pInfo->products_name, isset($_GET['pID'])?tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass."&cPath=$cPath&pID=".$_GET['pID']):'');

    if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
    switch ($pInfo->products_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }
	
	if((!empty($pInfo->products_date_available) && $pInfo->products_date_available != '0000-00-00 00:00:00') || $pInfo->products_date_available > 0) {
		$dateAvailable = date('m/d/Y', strtotime($pInfo->products_date_available));
	} else {
		$dateAvailable = '';
	}

?>

    <?php echo $breadcrumb->trail(2)?>
<script type="text/javascript">
<!--
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
    <?php echo tep_draw_form('new_product', FILENAME_CATEGORIES, 'pclass='.$pClass.'&amp;cPath=' . $cPath . (isset($_GET['pID']) ? '&amp;pID=' . $_GET['pID'] : '') . '&amp;action=new_product_preview'. (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: ''), 'post', 'enctype="multipart/form-data"'); ?>
    <table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td style="padding:13px 0 5px 8px">
<div class="pageHeading" style="text-transform:uppercase; font:bold 12px arial;"><a href="categories.php?pclass=product_default">Top</a> &raquo; <a href="categories.php?pclass=product_default&amp;cPath=<?php echo $_GET['cPath'];?>"><?php echo '<b>' . $categories['categories_name'] . '</b>'; ?></a> &raquo; <?php echo $pInfo->products_name?> 
</div>

</td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td style="padding:0 0 0 10px">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td>
<div style="float:left;">
<table>
          <tr>
<td>
<tr>
            <td class="main"><?php echo TEXT_PRODUCTS_STATUS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_status', '1', $in_status) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE . ' &nbsp; ' . tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?></td>
          </tr>
<tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr id="global_products_date_available">
            <td class="main" style="padding:0 0 10px 0"><?php echo TEXT_PRODUCTS_DATE_AVAILABLE; ?><br><small>(MM/DD/YYYY)</small></td>
            <td class="main" style="padding:0 0 10px 25px">

<table border="0" cellpadding="0" cellspacing="0"><tr><td>

<input type="text" name="dateAvailable" style="font:bold 9px arial;" onClick="self.popUpCalendar(this,this,'mm/dd/yyyy',document);" value="<?php echo (($dateAvailable > 0 || $dateAvailable != '01/01/1970') ? $dateAvailable : '');?>" size="12" maxlength="11">
</td><td valign="top">
<img src="images/calander2.gif" width="16" height="16" hspace="3" border="0" onClick="self.popUpCalendar(document.new_product.dateAvailable,document.new_product.dateAvailable,'mm/dd/yyyy',document);" style="cursor:pointer" alt=""></td></tr></table>
</td>
          </tr>
<tr>
	    <?php include_once(DIR_FS_CATALOG_INCLUDES.'modules/models.php'); ?>
            <td class="main"><?php echo TEXT_PRODUCTS_MANUFACTURER; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id,'onChange="reloadModelMkField(this.value)"'); ?>
  <span id="model_mk_selector"><?php display_model_selection($pInfo->manufacturers_id,$pInfo->products_make); ?></span>
	    <script type="text/javascript">
  function reloadModelMkField(mid) {
    new ajax('/model_lookup.php?mid='+escape(mid)+'&sel=<?php echo urlencode($pInfo->products_make)?>',{update:$('model_mk_selector')});
  }
	    </script>
	    </td>
          </tr>
		  <?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo 'Manufacturer\'s URL<br><small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small>'; ?></td>
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_url[' . $languages[$i]['id'] . ']', (isset($products_url[$languages[$i]['id']]) ? $products_url[$languages[$i]['id']] : tep_get_products_url($pInfo->products_id, $languages[$i]['id']))); ?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main">Sort Order</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_sort_order', $pInfo->products_sort_order); ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo TEXT_PRODUCTS_NAME; ?></td>
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? $products_name[$languages[$i]['id']] : tep_get_products_name($pInfo->products_id, $languages[$i]['id'])),' maxlength="255" size="40" id="prodName"'); ?> &nbsp; <span id="maxchars">70</span>

<script src="js/jquery.tinylimiter.js"></script>
<script>
jQuery.noConflict();
jQuery(document).ready( function() {
	var elem = jQuery("#maxchars");
	jQuery("#prodName").limiter(70, elem);


	jQuery('input[name="products_price"], input[name="products_price_gross"]').keyup(function() {

		var amazonSurcharge = jQuery('input[name="dbfeed_extra[dbfeed_amazon_us][amazon_surcharge]"]').val();

		var price1 = jQuery('input[name="products_price"]').val();
		var price2 = jQuery('input[name="products_price_gross"]').val();
	

		if(price1 && price2 < 1) { 
			jQuery('input[name="dbfeed_extra[dbfeed_amazon_us][amazon_surcharge]"]').val('0');
		}
	//alert(amazonSurcharge);
	});

});
</script>

</td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2" class="main"><hr></td>
          </tr>
          <tr id="global_products_quantity">
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_quantity', $pInfo->products_quantity); ?></td>
          </tr>
          <tr id="global_products_model">
            <td class="main"><?php echo TEXT_PRODUCTS_MODEL; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_model', $pInfo->products_model, 'maxlength="255"'); ?></td>
          </tr>
          <tr id="global_products_sku">
            <td class="main">Product SKU:</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_sku', $pInfo->products_sku); ?></td>
          </tr>
<tr id="global_products_upc">
            <td class="main">Product UPC:</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_upc', $pInfo->products_upc); ?></td>
          </tr>
</table>
</div><div style="float:right; padding:0 25px 0 0; font:bold 9px arial;"><?php echo 'Products ID: ' .$pInfo->products_id . '<br><br>' .tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT);?></div>
</td></tr>
<tr>
		  <td colspan="2">

<!-- DO NOT CHANGE id="t" OF tabber DIV! - this id is used by /admin/js/tabber.js to auto generate tab ID's. These ID's are inlined in script for tab recalling on page refresh -->

<div class="tabber" onClick="contentChanged();" id="t">
     <div class="tabbertab">
	   <h2>Descriptions</h2>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding-top:10px;">
             					
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main" valign="top" nowrap><?php //if ($i == 0) echo TEXT_PRODUCTS_INFO; ?>
			<?php if ($i == 0) echo "Short Description:&nbsp;"; ?></td>
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
            <td class="main" valign="top" nowrap><?php if ($i == 0) echo "Featured Description:&nbsp;"; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_info_alt[' . $languages[$i]['id'] . ']', 'soft', '70', '2', (isset($products_info_alt[$languages[$i]['id']]) ? $products_info_alt[$languages[$i]['id']] : tep_get_products_info_alt($pInfo->products_id, $languages[$i]['id']))); ?></td>
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
            <td class="main" valign="top" nowrap style="padding:5px 0 0 0"><?php //if ($i == 0) echo TEXT_PRODUCTS_DESCRIPTION; ?>
			<?php if ($i == 0) echo "Long Description:"; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top" style="padding:5px 0 0 0"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main">
<?php renderTabEdit('tab_products_description_'.$languages[$i]['id'],'products_description[' . $languages[$i]['id'] . ']', (isset($products_description[$languages[$i]['id']]) ? $products_description[$languages[$i]['id']] : tep_get_products_description($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
	  
<?php
    }
?> 

<tr>
            <td class="main" style="padding:5px 0 5px 0; white-space:nowrap;">Show "Quick Look" Popup?</td>
            <td class="main" style="padding:5px 0 5px 2px;"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_show_qview', $free_shipping_array, $pInfo->products_show_qview); ?></td>
          </tr>
<?
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
         <tr>
            <td class="main" valign="top" nowrap><?php if ($i == 0) echo 'Quick Look Description:&nbsp;'; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_qview_desc[' . $languages[$i]['id'] . ']', 'soft', '70', '3', (isset($products_qview_desc[$languages[$i]['id']]) ? $products_qview_desc[$languages[$i]['id']] : $pInfo->products_qview_desc)); ?></td>
              </tr>
            </table></td>
          </tr>
	  
<?php
    }
?>   <tr>
            <td class="main" style="padding:5px 0 5px 0;">Show Best-seller Icon?</td>
            <td class="main" style="padding:5px 0 5px 30px;"> <?php echo tep_draw_pull_down_menu('bs_icon', $free_shipping_array, $pInfo->bs_icon); ?></td>
          </tr>
       </table>
     </div>



     <div class="tabbertab">
	  <h2>Images / Options </h2>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0"> 
	  <tr>
	<td colspan="2" style="padding:0 10px 0 0">

<?php
  require( DIR_FS_MODULES.'attrctl.php');

  	$prcs = IXdb::read("SELECT customers_group_id, customers_group_price 
						FROM " . TABLE_PRODUCTS_GROUPS . " 
						WHERE products_id = '" . $pInfo->products_id . "' 
						ORDER BY customers_group_id", 'customers_group_id', 'customers_group_price'
						);
	$prcs[0] = $pInfo->products_price;

	show_attrctl($pInfo->products_id,$prcs,$productObject);
?>

</td>
</tr>
<tr><td>
<br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="0" id="global_products_image">

  <tr>
           <td class="dataTableRow" valign="top"><span class="main"><?php echo TEXT_PRODUCTS_IMAGE_NOTE; ?></span></td>
          
                    <?php if (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') { ?>
           <td class="dataTableRow" valign="top"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('upload_products_image') . '<br>'; ?></td>
           <?php } else { ?>
           <td class="dataTableRow" valign="top">
<?php echo '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="dataTableRow">' . tep_draw_textarea_field('upload_products_image', 'soft', '55', '2', $pInfo->products_image) . tep_draw_hidden_field('products_previous_image', $pInfo->products_image) . '</td></tr></table>';
           } if (($_GET['pID']) && ($pInfo->products_image) != '') { ?>
</td></tr>
              <tr>
                 <td class="dataTableRow" colspan="3" valign="top"><?php if (tep_not_null($pInfo->products_image)) { ?><span class="smallText"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $pInfo->products_image, $pInfo->products_image, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="left" hspace="0" vspace="5"') . tep_draw_hidden_field('products_previous_image', $pInfo->products_image) . '<br>'. tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="unlink_image" value="yes">' . TEXT_PRODUCTS_IMAGE_REMOVE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="delete_image" value="yes">' . TEXT_PRODUCTS_IMAGE_DELETE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '1', '42'); ?></span><?php } ?></td>
              </tr>
           <?php } ?>

  <?php
      if (ULTIMATE_ADDITIONAL_IMAGES == 'Enable') {
   ?>
          <tr>
            <td colspan="3"><?php echo tep_draw_separator('pixel_trans.gif', '1', '20'); ?></td>
          </tr>
         <tr>
            <td class="main" colspan="3"><?php echo TEXT_PRODUCTS_IMAGE_ADDITIONAL . '<br><hr>';?></td>
  </tr>
          <tr>
            <td class="smalltext" colspan="3">
			
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
              <tr>
                <td class="smalltext" colspan="3" valign="top"><?php echo TEXT_PRODUCTS_IMAGE_XL_NOTICE; ?></td>
              </tr>
              
<?php
		for($i=1;$i<=4;$i++) {
	       $pi=eval('return $pInfo->products_image_xl_'.$i.';');
?>

              <tr>
                <td class="dataTableRow" valign="top"><span class="smallText"><?php echo $i?></span></td>

<?php 

		if (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable'){ ?>

			<td class="dataTableRow" valign="top"><span class="smallText">

				<?php echo tep_draw_file_field('upload_products_image_xl_'.$i) . tep_draw_hidden_field('products_previous_image_xl_'.$i, $pi); ?>
			
			</span></td>
<?php 
		} else { 
?>
			<td class="dataTableRow" valign="top">
<?php 

			echo '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="dataTableRow">' . tep_draw_textarea_field('upload_products_image_xl_'.$i, 'soft', '70', '2', $pi) . tep_draw_hidden_field('products_previous_image_xl_'.$i, $pi) . '</td></tr></table>'; 
		} 
?>
			</td>
		</tr>
              <tr>
                <td class="dataTableRow" colspan="3" valign="top"><?php if (tep_not_null($pi)) { ?><span class="smallText"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $pi, $pi, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="left" hspace="0" vspace="5"') . '<br>'. tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="unlink_image_xl_'.$i.'" value="yes">' . TEXT_PRODUCTS_IMAGE_REMOVE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="delete_image_xl_'.$i.'" value="yes">' . TEXT_PRODUCTS_IMAGE_DELETE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '1', '42'); ?></span><?php } ?></td>
              </tr>

<?php
	}

  } else {
	echo
        tep_draw_hidden_field('products_previous_image_xl_1', $pInfo->products_image_xl_1) .
        tep_draw_hidden_field('products_previous_image_xl_2', $pInfo->products_image_xl_2) .
        tep_draw_hidden_field('products_previous_image_xl_3', $pInfo->products_image_xl_3) .
        tep_draw_hidden_field('products_previous_image_xl_4', $pInfo->products_image_xl_4) .
        tep_draw_hidden_field('products_previous_image_xl_5', $pInfo->products_image_xl_5) .
        tep_draw_hidden_field('products_previous_image_xl_6', $pInfo->products_image_xl_6);
     }
?>
</table>
<script type="text/javascript">
	function setGlobalFieldsDisplay(f) {
	jQuery.noConflict();

		var d = (f) ? 'block' : 'none';
		
		jQuery('#global_products_quantity').css({'display':d});
        jQuery('#global_products_model').css({'display':d});
		jQuery('#global_products_sku').css({'display':d});
        jQuery('#global_products_upc').css({'display':d});
		jQuery('#global_products_image').css({'display':d});
        jQuery('#global_products_date_available').css({'display':d});
  }

  showAllModels();
</script>
</td></tr></table></td></tr></table>
     </div>
	 
    <div class="tabbertab" style="height:340px;" onClick="contentChanged();">
	  <h2> Pricing / Cost</h2>
	 <table width="100%" border="0" cellpadding="0" cellspacing="0">
	 <tr>
            <td width="127" class="main"><?php echo TEXT_PRODUCTS_TAX_CLASS; ?></td>
            <td colspan="2" class="main"><?php echo tep_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()" style="font-family:verdana; font-size:8pt;"'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
            <td width="228" class="main"><?php echo tep_draw_input_field('products_price', $pInfo->products_price, 'onKeyUp="updateGross()" style="font-family:verdana; font-size:8pt; width:75px;"'); ?></td>
            <td width="240" rowspan="3" valign="top" class="main">
				<table width="240" border="0" cellspacing="0" cellpadding="5" style="border:solid 1px #666666">
              <tr>
                <td style="padding:10px"valign="top" bgcolor="#FFFFFF">
<?php
	$suppliers_exists = tep_db_query("SELECT s.*, spg.*
									  FROM suppliers s 
									  JOIN suppliers_products_groups spg ON s.suppliers_id = spg.suppliers_group_id 
									  WHERE suppliers_id='".$pInfo->suppliers_id."' 
									  AND products_id = '".$pInfo->products_id."'
									");

	$suppliers = tep_db_fetch_array($suppliers_exists);

	$supplierExists = ($pInfo->suppliers_id =='0') ? '' : $suppliers['suppliers_id'];
?>

<table style="background-color:#FFFFCA; border:dashed 1px #e4e4e4" width="100%" cellpadding="5" cellspacing="0">
                  <tr>
                    <td style="color:#000000" align="right">Product Cost:&nbsp;</td>
                    <td width="75">
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td><?php echo tep_draw_input_field('products_price_myself', $pInfo->products_price_myself, 'style="font-family:verdana; font-size:8pt; width:70px;"'); ?></td>
								<td nowrap align="right"> &nbsp; MSRP: <?php echo tep_draw_input_field('products_msrp', number_format($suppliers['products_msrp'], 2, '.', ''), 'style="font-size:8pt; width:55px"'); ?></td></tr></table>
</td>
                  </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="5" style="margin-top:5px">
                  <tr>
                    <td align="right"> Supplier:&nbsp;</td>
                    <td>
<?php



		$suppliers_array = array_merge(array(array('id' => 0, 'text' => 'Make a selection')
											),
												IXdb::read("SELECT * FROM suppliers ORDER BY suppliers_group_name ASC", array(NULL),
															array('id' => 'suppliers_id',
																  'text' => 'suppliers_group_name'
																  )
												)
										); 
 
	echo tep_draw_pull_down_menu('products_x_supplier', $suppliers_array, $supplierExists, 'id="supplierSelect"');

	//echo tep_draw_hidden_field('suppliers_id', $supplierExists);
?>
</td>
				</tr>
				<tr>
					<td nowrap>Case-Pack Sku:</td>
					<td>
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td><?php echo tep_draw_input_field('casepack_sku', $suppliers['casepack_sku'], 'style="font-size:8pt; width:85px"'); ?></td>
								<td nowrap> &nbsp; Case Qty: <?php echo tep_draw_input_field('casepack_qty', $suppliers['casepack_qty'], 'style="font-size:8pt; width:25px"'); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td nowrap>Supplier Sku:</td>
					<td>
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td><?php echo tep_draw_input_field('suppliers_sku', $suppliers['suppliers_sku'], 'style="font-size:8pt; width:85px"'); ?></td>
								<td nowrap> &nbsp; Priority: <?php echo tep_draw_input_field('priority', $suppliers['priority'], 'style="font-size:8pt; width:25px"'); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td nowrap>Reorder Threshold:</td>
					<td>
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td><?php echo tep_draw_input_field('reup_threshold', $suppliers['reup_threshold'], 'style="font-size:8pt; width:30px"'); ?></td>
								<td nowrap> &nbsp; Reorder Qty: <?php echo tep_draw_input_field('reup_quantity', $suppliers['reup_quantity'], 'style="font-size:8pt; width:30px"'); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
				</td>
		</tr>
		<tr>
			<td valign="top" class="main" style="padding-top:5px;"><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
            <td valign="top" class="main"><?php echo tep_draw_input_field('products_price_gross', $pInfo->products_price, 'OnKeyUp="updateNet()" style="font-family:verdana; font-size:8pt; width:75px;"'); ?></td>
            </tr>
	 <tr>
           <td valign="top" class="main">Minimum Order Qnty:</td>
		              <td valign="top" class="main"><?php echo tep_draw_input_field('products_qty_blocks', $pInfo->products_qty_blocks, 'style="font-size:8pt; width:75px"') . '';?></td>
	</tr>

<script type="text/javascript">
<!--
updateGross();
//-->
</script>
<?php
    $customers_group_query = tep_db_query("select customers_group_id, customers_group_name from " . TABLE_CUSTOMERS_GROUPS . " where customers_group_id != '0' order by customers_group_id");
    $header = false;
    while ($customers_group = tep_db_fetch_array($customers_group_query)) {

     if (tep_db_num_rows($customers_group_query) > 0) {
       $attributes_query = tep_db_query("select customers_group_id, customers_group_price from " . TABLE_PRODUCTS_GROUPS . " where products_id = '" . $pInfo->products_id . "' and customers_group_id = '" . $customers_group['customers_group_id'] . "' order by customers_group_id");
     } else {
         $attributes = array('customers_group_id' => 'new');
     }
 if (!$header) { ?>
<tr>
<td style="padding-top:10px;" colspan="2">&nbsp; <b>Pricing Groups</b></td>
<td>


<!--tr>
<td style="padding:10px;" colspan="3"><hr size="1"></td>
</tr>
    <tr bgcolor="#ebebff">
    <td class="main" colspan="3" style="">Note that if a field is left empty, no price for that customer group will be inserted in the database.<br />
If a field is filled, but the checkbox is unchecked no price will be inserted either.<br />
If a price is already inserted in the database, but the checkbox unchecked it will be removed from the database.</td>
    </tr>
 <?php
 $header = true;
 } // # end if (!header), makes sure this is only shown once
 ?>
        <tr bgcolor="#ebebff">
       <td class="main"><?php // only change in version 4.1.1
             if (isset($pInfo->sppcoption)) {
	   echo tep_draw_checkbox_field('sppcoption[' . $customers_group['customers_group_id'] . ']', 'sppcoption[' . $customers_group['customers_group_id'] . ']', (isset($pInfo->sppcoption[ $customers_group['customers_group_id']])) ? 1: 0);
      } else {
      echo tep_draw_checkbox_field('sppcoption[' . $customers_group['customers_group_id'] . ']', 'sppcoption[' . $customers_group['customers_group_id'] . ']', true) . '&nbsp;' . $customers_group['customers_group_name'];
      }
?>
 &nbsp;</td>
       <td colspan="2" class="main"><?php
       if ($attributes = tep_db_fetch_array($attributes_query)) {
       echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('sppcprice[' . $customers_group['customers_group_id'] . ']', $attributes['customers_group_price']);
       }  else {
	       if (isset($pInfo->sppcprice[$customers_group['customers_group_id']])) { // when a preview was done and the back button used
		       $sppc_cg_price = $pInfo->sppcprice[$customers_group['customers_group_id']];
	       } else { // nothing in the db, nothing in the post variables
		       $sppc_cg_price = '';
	       }
	   echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('sppcprice[' . $customers_group['customers_group_id'] . ']', $sppc_cg_price );
	 }  ?></td>
    </tr>
<?php
        } // # END while $customers_group = tep_db_fetch_array($customers_group_query)
?>
                    <tr> 
                      <td colspan="3" style="padding:10px"><hr size="1"></td>
                    </tr-->
					  

</td>
		              </tr>
		            <tr>
<?php
  $cusGroupSelect = array();
  $discounts = array();
  $grp_price = array();
  foreach ($cus_groups AS $cgrp=>$cgname) {
    $discounts[$cgrp] = array();
    $cusGroupSelect[] = array(id=>'pricing_group_'.$cgrp,'text'=>$cgname);
  }

	$dsc_query = tep_db_query("SELECT * 
							   FROM products_discount 
							   WHERE products_id = '".$pInfo->products_id."' 
							   ORDER BY customers_group_id,discount_qty
							  ");

	while ($dsc_row=tep_db_fetch_array($dsc_query)) {
		$discounts[$dsc_row['customers_group_id']][$dsc_row['discount_qty']]=$dsc_row['discount_percent'];
	}

	$pgrp_query = tep_db_query("SELECT * FROM ". TABLE_PRODUCTS_GROUPS. " WHERE products_id='".$pInfo->products_id."'");

	while($pgrp_row=tep_db_fetch_array($pgrp_query)) {
		$grp_price[$pgrp_row['customers_group_id']]=$pgrp_row['customers_group_price'];
	}
?>
<script type="text/javascript">
	function adjustDiscountTable(blk) {
		while (blk && blk.tagName!='TABLE') blk=blk.parentNode;
		var trs=blk.getElementsByTagName('tr');
		if (trs[trs.length-1].getElementsByTagName('input')[0].value!='') {
			var tr=trs[trs.length-1].cloneNode(true);
			var inps=tr.getElementsByTagName('input');
			for (var i=0;inps[i];i++) inps[i].value='';
			trs[trs.length-1].parentNode.insertBefore(tr,null);
		} else for (var idx=trs.length-1;idx>0 && trs[idx-1].getElementsByTagName('input')[0].value=='';idx--) trs[idx].parentNode.removeChild(trs[idx]);
}

 	function showPricingGroups(sel) {
		var blk;
		for (var i=0;sel.options[i];i++) if (blk=$(sel.options[i].value)) blk.style.display=sel.options[i].selected?'':'none';
	}
</script>
		<td class="main"><?php echo TEXT_PRODUCTS_PRICE1; ?></td>
		<td colspan="2" align="left">
			<?php echo 	tep_draw_pull_down_menu('switch_pricing_group',$cusGroupSelect,'','id="switch_pricing_group" onChange="showPricingGroups(this);"')?>
<?php  foreach ($cus_groups AS $cgrp=>$cgname) { ?>
				<div id="pricing_group_<?php echo $cgrp?>" style="display:none">
<?	if ($cgrp) { ?>
			<?php echo 	tep_draw_checkbox_field('sppcoption[' . $cgrp . ']', 1, isset($grp_price[$cgrp])  ,'','onChange="$(\'pricing_group_details_'.$cgrp.'\').style.display=this.checked?\'\':\'none\'"');?> Separate pricing for this group
				<div id="pricing_group_details_<?php echo $cgrp?>" style="display:<?php echo isset($grp_price[$cgrp])?'':'none'?>">
				Price for this Customers Group: <?php echo tep_draw_input_field('sppcprice[' . $cgrp . ']', $grp_price[$cgrp]);?>
<?	} ?>
				<table border="0" cellspacing="0" cellpadding="0">
			        <tr><th>Quantity:</th><th>Discount&nbsp;%</th></tr>
<?	$discounts[$cgrp]['']='';
	foreach ($discounts[$cgrp] AS $qty=>$dsc) { ?>
			        <tr><td><?php echo tep_draw_input_field('discount_qty['.$cgrp.'][]', $qty, 'size="7" onChange="adjustDiscountTable(this);"')?></td><td><?php echo tep_draw_input_field('discount_percent['.$cgrp.'][]', $dsc, 'size="7"')?></td></tr>
<?	} ?>
				</table>
<?	if ($cgrp) { ?>
				</div>
<?	} ?>
				</div>
<?php  } ?>
<script type="text/javascript">showPricingGroups($('switch_pricing_group'));</script>
			      </td>
		            </tr>


        </table>
     </div>
	 
	 <div class="tabbertab" style="height:345px;" onClick="contentChanged();">
	  <h2>Inventory / Shipping</h2>
<table width="100%" border="0" cellpadding="5" cellspacing="0">
  <tr>
    <td class="main">Free Shipping</td>
    <td class="main"><?php  echo '&nbsp;' .tep_draw_pull_down_menu('products_free_shipping', $free_shipping_array, $pInfo->products_free_shipping); ?></td>

    <td rowspan="7" valign="top" class="main" style="padding-left:15px;">

<?php 

	if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

		echo '<table width="240" border="0" cellspacing="0" cellpadding="5" style="border:solid 1px #666666">
			<tr>
				<td style="padding:10px"valign="top" bgcolor="#FFFFFF">';
	

		$warehouse_query = tep_db_query("SELECT pwp.products_warehouse_name,
												pwi.products_warehouse_id,
												pwi.products_id,
												pwi.products_quantity
									     FROM products_warehouse_profiles pwp 
										 LEFT JOIN " . TABLE_PRODUCTS_WAREHOUSE_INVENTORY . " pwi ON pwp.products_warehouse_id = pwi.products_warehouse_id
										 WHERE pwi.products_id = '".$_GET['pID']."'
										 ");

		if(tep_db_num_rows($warehouse_query) > 0) { 

			echo '<table style="background-color:#FFFFCA; border:dashed 1px #e4e4e4" width="100%" cellpadding="5" cellspacing="0">
    	              <tr>
        	            <td style="color:#000000" align="right">Multi-warehouse:&nbsp;</td>
            	        <td width="75"><b style="color:green">Active</b></td>
                	  </tr>
					</table>

					<table width="100%" border="0" cellspacing="0" cellpadding="5" style="margin-top:5px">';

			$warehouse_allocated = 0;

			while($warehouse = tep_db_fetch_array($warehouse_query)) {
	
    			$warehouse_id = $warehouse['products_warehouse_id'];
	    		$warehouse_name = $warehouse['products_warehouse_name'];
	    		$warehouse_products_id = $warehouse['products_id'];
		    	$products_warehouse_quantity = $warehouse['products_quantity'];

				$warehouse_allocated += $warehouse['products_quantity'];

				echo '<tr>
						<td align="right" width="50%"><b>' . $warehouse_name . ':</b> </td>
    	            	<td>Qty: &nbsp; <input type="input" name="products_warehouse_quantity[]" value="'. $products_warehouse_quantity .'" style="font:normal 11px arial; width:65px">
						      <input type="hidden" name="products_warehouse_id[]" value="'. $warehouse_id .'"></td>
					 </tr>';
//error_log(print_r($pInfo->products_quantity,1));

			}
				if(($pInfo->products_quantity - $warehouse_allocated) > 0) { 

					echo '<tr><td colspan="2" align="center"><div id="allocation" style="color:red">Unallocated: <b>'. ($pInfo->products_quantity - $warehouse_allocated) .'</b></div></td></tr>';

				}
		
			$add_warehouse_query = tep_db_query("SELECT pwp.products_warehouse_name,
														pwp.products_warehouse_id
												FROM products_warehouse_profiles pwp
												WHERE pwp.products_warehouse_id NOT IN
												    (SELECT products_warehouse_id 
   													FROM " . TABLE_PRODUCTS_WAREHOUSE_INVENTORY . "
													WHERE products_id = '".(int)$_GET['pID']."')
												");

			if(tep_db_num_rows($add_warehouse_query) > 0) { 

				echo '<tr>
						<td colspan="2" align="center">Add Warehouse: &nbsp; 
							<select name="addWarehouse">';
	
					echo '<option value="" selected="selected">Select:</option>';

				while($addwarehouse = tep_db_fetch_array($add_warehouse_query)) {
		
	    			$warehouse_id = $addwarehouse['products_warehouse_id'];
		    		$warehouse_name = $addwarehouse['products_warehouse_name'];

					echo '<option value="'.$warehouse_id.'">'.$warehouse_name.'</option>';
				}
	
				echo '</select>
					</td>
				</tr>';

			}

		} else {
			echo '<table style="background-color:#FFFFCA; border:dashed 1px #e4e4e4" width="100%" cellpadding="5" cellspacing="0">
    	              <tr>
        	            <td style="color:#000000" align="right">Multi-warehouse:&nbsp;</td>
            	        <td width="75"><b style="color:red"><a href="'.FILENAME_CATEGORIES.'?pID='.(int)$_GET['pID'].'&amp;multi-warehouse=activate">Inactive</a></b></td>
                	  </tr>';	
		}
?>
				</table>
			</td>
		</tr>
	</table>
<?php } ?>
</td>
  </tr>
  <tr bgcolor="#ebebff">
    <td class="main">Ship in a Separate Package</td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_pull_down_menu('products_separate_shipping', $free_shipping_array, $pInfo->products_separate_shipping); ?></td>
  </tr>
  <tr>
    <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT; ?></td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_input_field('products_weight', $pInfo->products_weight); ?></td>
  </tr>
  <tr>
    <td class="main">Width:</td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_input_field('products_width', $pInfo->products_width); ?></td>
  </tr>
  <tr>
    <td class="main">Length:</td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_input_field('products_length', $pInfo->products_length); ?></td>
  </tr>
  <tr>
    <td class="main">Height:</td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_input_field('products_height', $pInfo->products_height); ?></td>
  </tr>
  <tr>
    <td class="main">Harmonized Code:</td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_input_field('products_harmonized_code', $pInfo->products_harmonized_code); ?></td>
  </tr>

  <tr>
    <td class="main">Country of Origin:</td>
    <td class="main"><?php echo '&nbsp;' . tep_draw_input_field('products_origin_country', $pInfo->products_origin_country); ?></td>
  </tr>
  
</table>

     </div>

<?php if ($productObject->productEditSectionAllowed('marketing')) { ?>
	 <div class="tabbertab">
	  <h2>META Tags</h2>
<table border="0" cellpadding="5" cellspacing="0" style="padding:0 0 5px 0">
	   <tr>
            <td colspan="2" class="main" style="padding:5px 0 10px 0;"><?php echo TEXT_PRODUCT_METTA_INFO; ?></td>
          </tr>
         
<?php          
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
                 
          <tr>
            <td valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_PAGE_TITLE; ?></td>
<td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td><?php echo tep_draw_textarea_field('products_head_title_tag[' . $languages[$i]['id'] . ']', 'soft', '60', '1', (isset($products_head_title_tag[$languages[$i]['id']]) ? $products_head_title_tag[$languages[$i]['id']] : tep_get_products_head_title_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
                  
           <tr>
            <td valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_HEADER_DESCRIPTION; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', 'soft', '60', '3', (isset($products_head_desc_tag[$languages[$i]['id']]) ? $products_head_desc_tag[$languages[$i]['id']] : tep_get_products_head_desc_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
        
           <tr>
            <td valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_KEYWORDS; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', 'soft', '60', '1', (isset($products_head_keywords_tag[$languages[$i]['id']]) ? $products_head_keywords_tag[$languages[$i]['id']] : tep_get_products_head_keywords_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
?>
</table>
<!--tr>
<td><hr>
  <b>Pay-Per-Click Manager</b></td>
</tr>
                    <tr> 
                      <td colspan="2"><iframe src="product_ads.php?pID=<?php echo $pInfo->products_id?>" allowtransparency="true" style="margin:0;" frameborder="0" id="" width="100%" class="contentiframe"></iframe></td>
                    </tr-->



</div>

<div class="tabbertab" onClick="contentChanged();">

	  <h2>Cross Selling</h2>
<table width="100%" cellpadding="0" cellspacing="0" border="0">

<tr>
            <td style="height:30px; padding:5px 0 0 0">

<table cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:0 7px 5px 2px;"><img src="/admin/images/xsell-icon.png" alt=""></td><td><b style="font:bold 13px arial">Cross-sell Products</b></td></tr></table>

</td><td align="right" style="padding:0 0 0 10px">







<div onmouseover="ShowContent('tips'); return true;" onmouseout="HideContent('tips'); return true;" class="helpicon" style="width:16px; height:16px;"><a href="javascript:ShowContent('tips')"></a></div>

<div id="tips" style="display:none; position:absolute; border: 1px dashed #333; background-color: white; padding: 5px; text-align:left; width:300px;">
<font class="featuredpopName"><b style="white-space:nowrap;">Product Cross-Sell &amp; Linking</b></font><br><br><b>ID</b> - System ID of the model.<br><br><b>Image</b> - Model image.<br><br><b>Model</b> - Model of product to cross sell.<br><br><b>Discount</b> - The difference or discount issued if product is purchased in combination with the product your editing.<br><br><b>Qty.</b> - Maximum quantity the discount applies too.<br><br><b>Channels</b> - <br><u>Stock Links:</u> Add products to your stock link channel if your parent product is a combination of individually stocked products. If you want to sell a combo product, but display said combo as a separate product for purchase, this is where you'll connect the contents of the combination.<br><br><u>Product Info Group:</u> Add products to this channel to display as linked upgrades on your product info page.<br><br><u>Also Available:</u> This is a simple cross sell suggestion channel. You may create any number of standard cross sell suggestion channels.
</div>
</td>
</tr>
</table>

<?php include(DIR_FS_MODULES.'xsellctl.php'); ?>

</div>


<?php }	 
   if ($productObject->productEditSectionAllowed('auctions')) { ?>
	 <div class="tabbertab" onClick=" contentChanged();">
	  <h2 style="white-space: nowrap">Marketplace Feeds</h2>

	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr> 
                      <td style="padding-top:10px; padding-bottom:10px;" class="productFeedTab">

<?php 

	$attributeFeed_query = tep_db_query("SELECT master_products_id, products_id 
										 FROM products 
										 WHERE master_products_id = '".$pInfo->products_id."' 
										 AND products_id != master_products_id
										");
	
	if(tep_db_num_rows($attributeFeed_query) < 1) {
		$DBFeedMods->adminProductEdit($pInfo->products_id);
	} else {
		echo 'Attributes found - please manage feed preferences from Attribute Control on your Options Tab';
	}
?>
</td>
                    </tr>
       </table>

     </div>

<?php }

	$extra = array();
	$extra_qry = tep_db_query("SELECT * FROM products_extra WHERE products_id='".$pInfo->products_id."'");
  
	while ($extra_row = tep_db_fetch_array($extra_qry)) {
		$extra[$extra_row['products_extra_key']]=$extra_row['products_extra_value'];
	}

	$pfields = $productObject->getProductFields();

	if($pfields) {
?>

	 <div class="tabbertab">
	  <h2 style="white-space: nowrap"><?php echo $productObject->getName()?></h2>
	  <table width="100%" border="0" cellpadding="0" cellspacing="0">
<?php 
	foreach ($pfields AS $fld=>$fdata) {
		$fval=isset($extra[$fld])?$extra[$fld]:$fdata['default'];
?>
                    <tr> 
                      <td><?php echo $fdata['title']?>:</td><td><?php echo tep_draw_input_field('products_extra['.$fld.']',$fval)?></td>
                    </tr>
<?php    } 
?>

       </table>

     </div>

<?php } ?>
</div>

</td>
		  </tr>
        </table></td>
      </tr>
      <tr>
        <td class="main" align="right" colspan="2" style="padding:10px 15px 5px 0;"><?php echo tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d H:i:s'))) . tep_image_submit('button_update.gif', IMAGE_UPDATE,' name="express_update"') . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '')) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr>
    </table></form>

<script type="text/javascript" src="js/tabber.js"></script>

<?php
} else {
?>
    <?php echo $breadcrumb->trail(2)?>
    <table border="0" width="100%" cellspacing="0" cellpadding=0">
      <tr>
        <td width="50%" style="padding:5px;">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="85%">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top" style="padding:5px; padding-right:1px;"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="60"><img src="images/product-icon.gif" width="48" height="48" border="0"></td>
    <td style="font:bold 17px arial; white-space:nowrap;">Inventory / Products</td>
  </tr>
</table>
</td>
<td align="right" style="padding:5px 2px 0 0; font: bold arial red">

<?php 
	if(!empty($_GET['search'])) { 
		echo' &nbsp <a href="'.FILENAME_CATEGORIES.'?search='.(!empty($_GET['status']) ? '&status='.$_GET['status'] : '').'">clear search</a>';
	}
?>
</td>
</tr>
</table>

</td>
<td align="right">
<table border="0" cellspacing="0" cellpadding="0" width="250">
  <tr>
    <td style="padding-left:10px;">

<?php
    echo tep_draw_form('search', FILENAME_CATEGORIES, 'status='.(!empty($_GET['status']) ? $_GET['status'] : '').(!empty($_GET['search']) ? '&search='.$_GET['search'] : ''), 'GET','style="margin:0"');
    echo 'Product Search: ' . tep_draw_input_field('search',$_GET['search'],'style="width:125px;"');
	echo '<input name="status" type="hidden" value="'.$_GET['status'].'">';
    echo '</form>';
?></td>
<td style="padding-left:10px;"> 
<?php
    echo tep_draw_form('goto', FILENAME_CATEGORIES.'?status='.$_GET['status'].(!empty($_GET['search']) ? '&search='.$_GET['search'] : ''), '', 'get','style="margin:0"');
    echo 'Jump To: ' . ' ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();" style="width:175px;"');
	echo '<input name="status" type="hidden" value="'.$_GET['status'].'">';
    echo '</form>';
?>
</td>
<td style="padding-left:10px;">
<?php

    echo tep_draw_form('prodStatus', FILENAME_CATEGORIES.'?status='.$_GET['status'].(!empty($_GET['search']) ? '&search='.$_GET['search'] : ''), '', 'get','style="margin:0"');
	echo '<input name="cPath" type="hidden" value="'.$_GET['cPath'].'">';
	echo '<input name="search" type="hidden" value="'.$_GET['search'].'">';
    echo '
Status: <select name="status" style="font:normal 12px arial; width:90px;" onchange="this.form.submit();">';

if(isset($_GET['status']) && $_GET['status'] == '1') {

$selected2 = ''; $selected1 = 'selected'; $selected0 = '';

} elseif(isset($_GET['status']) && $_GET['status'] == '0') {

$selected2 = ''; $selected1 = ''; $selected0 = 'selected';

} else {

$selected2 = 'selected'; $selected1 = ''; $selected0 = '';

}
?>
			<option <?php echo $selected2?> value="">Show All</option>
			<option <?php echo $selected1?> value="1">Active</option>
			<option <?php echo $selected0?> value="0">Disabled</option>

</select>
			</form>
</td>
  </tr>
</table>
</td>
  </tr>
</table>

</td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow" style="cursor:default">
<td class="dataTableHeadingContent" align="center" style="width:60px;"><?php echo TABLE_HEADING_IMAGE; ?></td>
<td class="dataTableHeadingContent" align="center" style="white-space:nowrap;">Edit</td>
                <td class="dataTableHeadingContent" style="padding:0 0 0 10px"><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="center">Visible?</td>
                <td class="dataTableHeadingContent" align="right"><?php //echo TABLE_HEADING_ACTION; ?>&nbsp; &nbsp; </td>
              </tr>
<?php
    $categories_count = 0;
    $rows = 0;


	if(isset($_GET['status']) && $_GET['status'] == 1) {

		$filterActiveCats = "AND c.categories_status = '1'";

	} elseif(isset($_GET['status']) && $_GET['status'] == '0') {

		$filterActiveCats = "AND c.categories_status = '0'";

	} else {

		$filterActiveCats = '';
	}


      $search = (!empty($_GET['search']) ? tep_db_prepare_input($_GET['search']) : '');

      $categories_query = tep_db_query("SELECT c.categories_id, 
											   cd.categories_name, 
											   c.categories_image, 
											   c.parent_id, 
											   c.sort_order, 
											   c.date_added, 
											   c.last_modified, 
											   cd.categories_htc_title_tag, 
											   cd.categories_htc_desc_tag, 
											   cd.categories_htc_keywords_tag, 
											   cd.categories_htc_description, 
											   c.categories_status 
										 FROM " . TABLE_CATEGORIES . " c
										 LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = c.categories_id
										 WHERE c.categories_id = cd.categories_id
										 ".($pClass ? " AND c.products_class = '".addslashes($pClass)."'" : "" )." 
										 AND cd.language_id = '" . (int)$languages_id . "' 
										 ".((!empty($search))? " AND cd.categories_name LIKE '%". $search ."%' " : " AND c.parent_id = '" . (int)$current_category_id . "'")."
										 ".$filterActiveCats." 
										 ORDER BY c.sort_order, cd.categories_name
										 ");


    while ($categories = tep_db_fetch_array($categories_query)) {
		$categories_count++;
		$rows++;

		// # Get parent_id for subcategories if search
		if (isset($_GET['search'])) {
			$cPath = $categories['parent_id'];
	}

		
		if ((!isset($_GET['cID']) && !isset($_GET['pID']) || (isset($_GET['cID']) && ($_GET['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
			$category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
			$category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));

			$cInfo_array = array_merge($categories, $category_childs, $category_products);
			$cInfo = new objectInfo($cInfo_array);
		}


		if(isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {

			echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&'.tep_get_path($categories['categories_id'])) . (!empty($_GET['status']) ? '&status='. $_GET['status']: '') . '\'">' . "\n";
		} else {
			echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories['categories_id']. (!empty($_GET['status']) ? '&status='. $_GET['status']: '')) . '\'">' . "\n";
		}
?>

		<td class="dataTableContent" align="center" style="width:60px;"><?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&'.tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>'; ?></td>

		<td class="dataTableContent" align="center" style="padding-left:4px; padding-right:4px; width:30px;"><?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&'.tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', 'View Category') . '</a>'; ?></td>

		<td class="dataTableContent" style="padding-left:5px; width:320px;"><?php echo '<b>' . $categories['categories_name'] . '</b>'; ?></td>

		<td class="dataTableContent" align="center" style="width:50px;">

<?php
    if ($categories['categories_status']){
      echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '&nbsp;&nbsp;<a href="'.tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=setflag&flag=0&cPath=' . $cPath . '&cID=' . $categories['categories_id']).'">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
    } else {
      echo '<a href="'.tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=setflag&flag=1&cPath=' . $cPath . '&cID=' . $categories['categories_id']).'">' .tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
    }      
?>
<!-- END - CHANGE THIS TO CATEGORY DISABLE / ENABLE --></td>
<td class="dataTableContent" align="center" style="width:64px;"><?php if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) { echo tep_image(DIR_WS_IMAGES . 'right-arrow.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $categories['categories_id']) . '">' . tep_image(DIR_WS_IMAGES . 'preview.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }


	if(isset($_GET['status']) && $_GET['status'] == 1) {

		$filterActive = " AND p.products_status = '1'";

	} elseif(isset($_GET['status']) && $_GET['status'] == '0') {

		$filterActive = " AND p.products_status = '0'";

	} else {

		$filterActive = " ";

	}

    $products_count = 0;


    if(!empty($_GET['search'])) {

		$search = (!empty($_GET['search']) ? tep_db_prepare_input($search) : '');

		$searchSQL = " 	AND (pd.products_name LIKE '%".$search."%' OR p.products_model LIKE '%".$search."%' OR spg.suppliers_sku LIKE '%".$search."%' OR p.products_sku LIKE '%".$search."%' OR p.products_upc LIKE '%".$search."%' OR m.manufacturers_name LIKE '%".$search."%' OR p.products_id ='".$search."') ";

    } else {

		$searchSQL = " 	AND p2c.categories_id = '" . (int)$current_category_id . "' ";
    }

	$products_query = tep_db_query("SELECT p.products_id, 
										   p.products_model, 
										   p.products_sku, 
										   p.products_upc, 
										   p.products_quantity, 
										   p.products_image, 
										   p.products_price, 
										   p.products_qty_blocks, 
										   p.products_date_added, 
										   p.products_last_modified, 
										   p.products_date_available, 
										   p.products_status, 
										   p.products_weight,
										   p.products_width,
										   p.products_height,
										   p.products_length,
										   p.products_harmonized_code,
										   p.products_origin_country,
										   pd.products_name, 
										   p2c.categories_id,
										   m.manufacturers_name,
										   c.parent_id
										FROM " . TABLE_PRODUCTS . " p
										LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
										LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id = p.products_id
										LEFT JOIN suppliers_products_groups spg ON spg.products_id = p.products_id
										LEFT JOIN ". TABLE_MANUFACTURERS ." m ON m.manufacturers_id = p.manufacturers_id
										LEFT JOIN " . TABLE_CATEGORIES . " c ON c.categories_id = p2c.categories_id
									  	WHERE pd.language_id = '" . (int)$languages_id . "' 
									  	". $searchSQL ."
										". $filterActive ." 
										GROUP BY p.products_id 
										ORDER BY pd.products_name
									   ");

	while ($products = tep_db_fetch_array($products_query)) {
		$products_count++;
		$rows++;

		$catPath = $products['parent_id'] . '_' . $products['categories_id'];

		// # Get categories_id for product if search

		if((!isset($_GET['pID']) && !isset($_GET['cID']) || (isset($_GET['pID']) && ($_GET['pID'] == $products['products_id']))) && !isset($pInfo) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
		
			// # find out the rating average from customer reviews
    	    $reviews_query = tep_db_query("SELECT (AVG(reviews_rating) / 5 * 100) AS average_rating 
										   FROM " . TABLE_REVIEWS . " 
										   WHERE products_id = '" . $products['products_id'] . "'
										  ");

        	$reviews = tep_db_fetch_array($reviews_query);
	        $pInfo_array = array_merge($products, $reviews);
    	    $pInfo = new objectInfo($pInfo_array);
		}

		if(isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id) ) {
	
			echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $catPath . '&pID=' . $products['products_id'] . '&action=new_product').(isset($_GET['status']) ? '&amp;status='.$_GET['status'] : '').(isset($_GET['search']) ? '&amp;search='.$_GET['search'] : '').'\'">' . "\n";

		} else {
	
			echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $catPath . '&pID=' . $products['products_id']) . (isset($_GET['status']) ? '&amp;status='.$_GET['status'] : '').(isset($_GET['search']) ? '&amp;search='.$_GET['search'] : '').'\'">' . "\n";
		}
?>

<td class="dataTableContent" align="center" style="width:60px; padding:5px;"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $products['products_image'], $products['products_name'], 50, 60); ?></td>
<td class="dataTableContent" style="width:50px;" align="center">

<?php echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&amp;cPath=' . $catPath . '&amp;pID=' . $products['products_id'] . '&amp;action=new_product') . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') .'">' . tep_image(DIR_WS_ICONS . 'edit.gif', 'Edit') . '</a>';?>

</td>
                <td class="dataTableContent" style="padding:0 10px"><?php echo $products['products_name']; ?></td>
                <td class="dataTableContent" align="center" style="width:55px;">
<?php
      if ($products['products_status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=setflag&flag=0&pID=' . $products['products_id'] . '&cPath=' . $catPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=setflag&flag=1&pID=' . $products['products_id'] . '&cPath=' . $catPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
                <td class="dataTableContent" align="center"><?php if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id)) { echo tep_image(DIR_WS_IMAGES . 'right-arrow.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $catPath . '&pID=' . $products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . 'preview.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp; </td>
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
                <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="5" style="margin-top:10px">
                  <tr>
                    <td class="smallText"><?php echo TEXT_CATEGORIES . '&nbsp;' . $categories_count . '<br>' . TEXT_PRODUCTS . '&nbsp;' . $products_count; ?></td>
                    <td align="right" class="smallText">
<?php 

	if (sizeof($cPath_array) > 0) {
		echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&'.$cPath_back . 'cID=' . $current_category_id) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>&nbsp;'; 
	}

	if (empty($_GET['search'])) {

		if($products_count == 0) { 
			echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&action=new_category') . '">' . tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY) . '</a>&nbsp;';
		}

		if($categories_count == 0) { 
			echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&action=new_product') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>';
		}

	}
?>
					</td>
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

        $contents = array('form' => tep_draw_form('newcategory', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=insert_category&cPath='. $cPath, 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

        $category_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']');
          // HTC BOC
          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']');
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']');
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']');
          $category_htc_description_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_textarea_field('categories_htc_description[' . $languages[$i]['id'] . ']', 'soft', 25, 5, '');
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
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

break;

case 'edit_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=update_category&cPath=' . $cPath, 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_EDIT_INTRO);

        $category_inputs_string = '';

        $languages = tep_get_languages();

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {

          $category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id']));

          $category_htc_title_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_title_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_title($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_desc_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_desc_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_desc($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_keywords_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_htc_keywords_tag[' . $languages[$i]['id'] . ']', tep_get_category_htc_keywords($cInfo->categories_id, $languages[$i]['id']));
          $category_htc_description_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_textarea_field('categories_htc_description[' . $languages[$i]['id'] . ']', 'soft', 25, 5, tep_get_category_htc_description($cInfo->categories_id, $languages[$i]['id']));

        }

        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_NAME . $category_inputs_string);
        $contents[] = array('text' => '<img src="/images/' .$cInfo->categories_image. '" alt="' .$cInfo->categories_name. '" width="200"><br>' . DIR_WS_CATALOG_IMAGES . '<b>' . $cInfo->categories_image . '</b>');
        $contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
        $contents[] = array('text' => '<br>' . 'Header Tags Category Title' . $category_htc_title_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Description' . $category_htc_desc_string);
        $contents[] = array('text' => '<br>' . 'Header Tags Category Keywords' . $category_htc_keywords_string);
        $contents[] = array('text' => '<br>' . 'Categories Page Description' . $category_htc_description_string);

        $contents[] = array('text' => '<br>' . TEXT_EDIT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));

if(isset($cPath) && $cPath > 0) {
	$contents[] = array('text' => '<br>Template Filename:<br>&nbsp;<b>index.php_'.$cPath.'_'.$cInfo->categories_id.'.html</b>');
} else {
	$contents[] = array('text' => '<br>Template Filename:<br>&nbsp;<b>index.php_'.$cInfo->categories_id.'.html</b>');
}

if(isset($_GET['buildtpl']) && $_GET['buildtpl'] == 1) {
	include ('buildtpl.php');
}

if(isset($cPath) && $cPath > 0) {
	$filename = '/home/'.LINUX_USER_NAME.'/public_html/layout/index.php_'.$cPath.'_'.$cInfo->categories_id.'.html';
} else {
	$filename = '/home/'.LINUX_USER_NAME.'/public_html/layout/index.php_'.$cInfo->categories_id.'.html';
}

if (file_exists($filename)) {

if(isset($_GET['edittpl']) && $_GET['edittpl'] == 1) {
	$contents[] = array('text' => '<br>Using custom template?: Yes - <a href="javascript:void(0);" onclick="document.getElementById(\'light\').style.display=\'block\';document.getElementById(\'fade\').style.display=\'block\';	;">edit template</a>');
} else {
	$contents[] = array('text' => '<br>Using custom template?: Yes - <a href="javascript:void(0);" onclick=" document.location.href=\'categories.php?edittpl=1&amp;pclass=product_default&amp;cPath='.$cPath.'&amp;cID='.$cInfo->categories_id.'&amp;action=edit_category\';">edit template</a>');
}
 

} else {
	$contents[] = array('text' => '<br>Using custom template?: NO - <a href="javascript:void(0);" onclick="document.location.href=\'categories.php?buildtpl=1&amp;pclass=product_default&amp;cPath='.$cPath.'&amp;cID='.$cInfo->categories_id.'&amp;action=edit_category\';">create template</a>');
}
	
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

break;

case 'delete_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=delete_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
        $contents[] = array('text' => '<br><b>' . $cInfo->categories_name . '</b>');
        if ($cInfo->childs_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count));
        if ($cInfo->products_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $cInfo->products_count));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

break;

case 'move_category':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_CATEGORY . '</b>');

        $contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=move_category_confirm&cPath=' . $cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name));
        $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $cInfo->categories_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

break;

case 'delete_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=delete_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
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
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

break;

case 'move_product':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_PRODUCT . '</b>');

        $contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=move_product_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name));
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
        $contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');

break;

case 'copy_to':

        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_COPY_TO . '</b>');

        $contents = array('form' => tep_draw_form('copy_to', FILENAME_CATEGORIES, 'pclass='.$pClass.'&action=copy_to_confirm&cPath=' . $cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
        $contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
        $contents[] = array('text' => '<br>' . TEXT_CATEGORIES . '<br>' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id));
        $contents[] = array('text' => '<br>' . TEXT_HOW_TO_COPY . '<br>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '<br>' . tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE);
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', IMAGE_COPY) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . (int)$_GET['pID']) . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'. tep_draw_hidden_field('products_id', (int)$_GET['pID']));

break;

default:
        if ($rows > 0) {
          if (isset($cInfo) && is_object($cInfo)) { // category info box contents
            $heading[] = array('text' => '<b>' . $cInfo->categories_name . '</b>');

            $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=edit_category') . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=delete_category') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&cID=' . $cInfo->categories_id . '&action=move_category') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>');
            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added));
            if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified));
            $contents[] = array('text' => '<br>' . tep_info_image($cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT) . '<br>' . $cInfo->categories_image);
            $contents[] = array('text' => '<br>' . TEXT_SUBCATEGORIES . ' ' . $cInfo->childs_count . '<br>' . TEXT_PRODUCTS . ' ' . $cInfo->products_count);

if(isset($cPath) && $cPath > 0) {
	$contents[] = array('text' => '<br>Template Filename:<br>&nbsp;<b>index.php_'.$cPath.'_'.$cInfo->categories_id.'.html</b>');
} else {
	$contents[] = array('text' => '<br>Template Filename:<br>&nbsp;<b>index.php_'.$cInfo->categories_id.'.html</b>');
}
          } elseif (isset($pInfo) && is_object($pInfo)) { // product info box contents
            $heading[] = array('text' => '<b>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</b>');

            $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=new_product') . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=delete_product') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=move_product') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $pInfo->products_id . '&action=copy_to') . (!empty($_GET['search']) ? '&search='.$_GET['search']: '') . (!empty($_GET['status']) ? '&status='.$_GET['status']: '') . '">' . tep_image_button('button_copy_to.gif', IMAGE_COPY_TO) . '</a>  <a target="_blank" href="' . HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'product_info.php?products_id=' . $pInfo->products_id .'">' . tep_image_button('button_preview.gif', IMAGE_PREVIEW) . '</a> <a href="' . tep_href_link("stock.php", 'product_id=' . $pInfo->products_id) . '">' . tep_image_button('button_stock.gif', "Stock") . '</a>');

            $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link('orders.php', 'pID=' . $pInfo->products_id) . '">View Ordered</a>');

            $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($pInfo->products_date_added));
            
			if(tep_not_null($pInfo->products_last_modified)){
				$contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($pInfo->products_last_modified));
			}
			
			$today = date('Y-m-d', time());
			$dateAvail = date('Y-m-d',$pInfo->products_date_available);
            if($today < $dateAvail) { 
				$contents[] = array('text' => TEXT_DATE_AVAILABLE . ' ' . tep_date_short($pInfo->products_date_available));
			}

            $contents[] = array('text' => '<br><div align="center">' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</div><br>' . $pInfo->products_image);
            $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_PRICE_INFO . ' ' . $currencies->format($pInfo->products_price) . '<br>' . TEXT_PRODUCTS_QUANTITY_INFO . ' ' . $pInfo->products_quantity);
            $contents[] = array('text' => '<br>' . TEXT_PRODUCTS_AVERAGE_RATING . ' ' . number_format($pInfo->average_rating, 2) . '%');
          }
        } else { // create category/product info
          $heading[] = array('text' => '<b>' . EMPTY_CATEGORY . '</b>');

          $contents[] = array('text' => TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS);
        }
break;
    }

if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
      echo '            <td width="185" valign="top" style="padding:0 6px 0 6px;"><div style="background-color:#FFF; border:dotted 1px #333333; padding:5px;">' . "\n";

      $box = new box;
      echo $box->infoBox($heading, $contents);

      echo '            </div></td>' . "\n";
    }
?>
          </tr>
        </table>
<?php
if(isset($_GET['edittpl']) && $_GET['edittpl'] == 1) {
include ('edittpl.php');
}
?>
</td>
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
