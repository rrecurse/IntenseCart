<?php
/*
  IntenseCart, E-Commerce and Internet Marketing Solutions
  http://www.intensecart.com

  Copyrights (c) 2006 IntenseCart Inc.

  Redistribution without explicit written consent is forbidden under US and International Copyright Laws.

*/

  require('includes/application_top.php');
  
  include(DIR_WS_LANGUAGES.$language.'/categories.php');

  // include the breadcrumb class and start the breadcrumb trail
  require(DIR_WS_CLASSES . 'breadcrumb.php');
  $breadcrumb = new breadcrumb;

  $breadcrumb->add('Categories', tep_href_link(FILENAME_CATEGORIES));

  $pClass=NULL;
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

// By MegaJim
  require(DIR_WS_CLASSES . 'url_rewrite.php');
  $url_rewrite = new url_rewrite();

  $ModelFieldsList=Array('quantity');
  
  $DBFeedMods=tep_module('dbfeed');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : 'new_product');
  
  $cus_groups=tep_get_customer_groups();

  include(DIR_FS_MODULES.'tabedit.php');

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
	$product_ids=Array($products_id);
	$default_img=Array(NULL);
	$imgs_srt=Array();
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
	    foreach ($modelFields AS $mfld=>$dfld) if (isset($_POST[$mfld][$midx])) $sql_data_array[$dfld]=$_POST[$mfld][$midx]; else unset($sql_data_array[$dfld]);
	    $model_av=(isset($_POST['model_date_available'][$midx]) && !preg_match('/^\s*$/',$_POST['model_date_available'][$midx]))?$_POST['model_date_available'][$midx]:NULL;
	    $sql_data_array['products_date_available']=$model_av?date('Y-m-d',strtotime($model_av)):NULL;
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
	    if ($model_pid) {
	      $url_rewrite->purge_item(sprintf('p%d',$model_pid));

	      tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '$model_pid'");
	      unset($curr_models[$model_pid]);
	      $new_model_pid=NULL;
	    } else {
              tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
              $new_model_pid = $model_pid = tep_db_insert_id();
	    }
	    $product_ids[]=$model_pid;
	    if (isset($_POST['model_extra'])) foreach ($_POST['model_extra'] AS $xkey=>$xval) if (isset($xval[$midx])) tep_set_products_extra($model_pid,$xkey,$xval[$midx]);
	    $srt=Array();
	    foreach ($postattrs AS $aid=>$av) {
	      $optns_sort=$_POST['options_sort_order'][$aid];
	      $attrs_sort=isset($_POST['attrs_sort_order'][$aid.'_'.$av])?$_POST['attrs_sort_order'][$aid.'_'.$av]:'';
	      $attrs_sortq=$attrs_sort==''?'NULL':"'$attrs_sort'";
	      $srt[$aid]=$attrs_sort;
	      $attrs_img='';
	      if (isset($_POST['attr_image'][$aid.'_'.$av]) && preg_match('/^(\w+):(.*)/',$_POST['attr_image'][$aid.'_'.$av],$at_img_parse)) {
		if ($at_img_parse[1]=='file') $attrs_img=$at_img_parse[2];
		else if ($at_img_parse[1]=='upload') $attrs_img=get_upload_file('upload_attr_image_'.$at_img_parse[2]);
	      }
	      if ($new_model_pid) tep_db_query("INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES." (products_id,options_id,options_values_id,options_sort,options_values_sort,options_image) VALUES ('$new_model_pid','$aid','$av','$optns_sort',$attrs_sortq,'".addslashes($attrs_img)."')");
	      else tep_db_query("UPDATE ".TABLE_PRODUCTS_ATTRIBUTES." SET options_sort='$optns_sort',options_values_sort=$attrs_sortq,options_image='".addslashes($attrs_img)."' WHERE products_id='$model_pid' AND options_id='$aid'");
	    }
	    for ($i=0;isset($model_img[$i]);$i++) if ($model_img[$i] && (!isset($default_img[$i]) || $srt<$imgs_srt)) $default_img[$i]=$i?'':$model_img[$i];
	    $imgs_srt=$srt;
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


// XSell

if (isset($_POST['xsell'])) {
  tep_db_query("DELETE FROM products_images WHERE products_id IN (".join(',',$product_ids).") AND image_group='linked'");
  foreach ($_POST['xsell'] AS $ch=>$xsell) {
    tep_db_query("DELETE FROM products_xsell WHERE products_id IN (".join(',',$product_ids).") AND xsell_channel='".addslashes($ch)."'");
    foreach ($xsell AS $xidx=>$xsell_id) if ($xsell_id) {
      $pids=Array();
      if ($_POST['xsell_model'][$ch][$xidx]) {
        foreach (split(',',$_POST['xsell_model'][$ch][$xidx]) AS $mid) $pids[]=$mid;
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
//	print_r($_FILES);


$DBFeedMods->AdminProductSave($products_id);



          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];

            $sql_data_array = array('products_name' => tep_db_prepare_input($HTTP_POST_VARS['products_name'][$language_id]),
                                    'products_info' => tep_db_prepare_input($HTTP_POST_VARS['products_info'][$language_id]),
				    'products_info_alt' => tep_db_prepare_input($HTTP_POST_VARS['products_info_alt'][$language_id]),
                                    'products_description' => tep_db_prepare_input(collectTabEdit($HTTP_POST_VARS['products_description'][$language_id])),
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

          tep_redirect(tep_href_link('categories.php', 'pclass='.$pClass.'&cPath=' . $cPath . '&pID=' . $products_id));
        }
        break;
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


<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script language="JavaScript" src="js/popcalendar.js"></script>
</head>
<body style="background-color:transparent; margin:0">
<script type="text/javascript" src="js/tips.js"></script>
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

    $breadcrumb->add($pInfo->products_name, isset($_GET['pID'])?tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass."&cPath=$cPath&pID=".$_GET['pID']):'');

    if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
    switch ($pInfo->products_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
    }
?>

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
    <?php echo tep_draw_form('new_product', 'edit_product.php', 'pclass='.$pClass.'&cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=new_product_preview', 'post', 'enctype="multipart/form-data"'); ?>
    <table border="0" width="571" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" style="text-transform:uppercase; font-weight:bold">&nbsp; <a href="#"><?php echo sprintf(tep_output_generated_category_path($current_category_id)); ?> </a> &raquo; <?=$pInfo->products_name?> 
<!--( product ID: <?=//$pInfo->products_id?>)-->
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
            <td class="main"><?php echo TEXT_PRODUCTS_STATUS; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_status', '1', $in_status) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE . '&nbsp;' . tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr id="global_products_date_available">
            <td class="main"><?php echo TEXT_PRODUCTS_DATE_AVAILABLE; ?><br><small>(YYYY-MM-DD)</small></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?><script language="javascript">dateAvailable.writeControl(); dateAvailable.dateFormat="yyyy-MM-dd";</script></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MANUFACTURER; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id); ?></td>
          </tr>
		  <?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo 'Manufacturer&acute;s URL<br><small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small>'; ?></td>
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
            <td class="main"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? $products_name[$languages[$i]['id']] : tep_get_products_name($pInfo->products_id, $languages[$i]['id'])),' maxlength="40" size="40"'); ?></td>
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
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_model', $pInfo->products_model); ?></td>
          </tr>
          <tr id="global_products_sku">
            <td class="main">UPC Code:</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_sku', $pInfo->products_sku); ?></td>
          </tr>


		            
		  <tr>
		  <td colspan="2"><div class="tabber" onClick="contentChanged();">

     <div class="tabbertab">
	   <h2><span id="tab-left-active"></span><span>Descriptions</span><span id="tab-right-active"></span></h2>
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
                <td class="main"><? renderTabEdit('tab_products_description_'.$languages[$i]['id'],'products_description[' . $languages[$i]['id'] . ']', (isset($products_description[$languages[$i]['id']]) ? $products_description[$languages[$i]['id']] : tep_get_products_description($pInfo->products_id, $languages[$i]['id']))); ?></td>
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
     </div>


     <div class="tabbertab">
	  <h2>Images / Options </h2>
	  <table width="555" border="0" cellpadding="0" cellspacing="0"> 
	  <tr>
	<td colspan="2" style="width:571px;">

<?
  require( DIR_FS_MODULES.'attrctl.php');
  show_attrctl($pInfo->products_id,Array(0=>$pInfo->products_price),$productObject);
?>

</td>
</tr>
<tr><td>

<table width="100%" border="0" cellspacing="0" cellpadding="0" id="global_products_image">

  <tr>
           <td class="dataTableRow" valign="top"><span class="main"><?php echo TEXT_PRODUCTS_IMAGE_NOTE; ?></span></td>
           <?php if (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') { ?>
           <td class="dataTableRow" valign="top"><!--xxxxxxxxxxxxxxxxxxxxx--><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('upload_products_image') . '<br>'; ?></td>
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
              
	     <?
	      for($i=1;$i<=4;$i++) {
	       $pi=eval('return $pInfo->products_image_xl_'.$i.';');
	     ?>

              <tr>
                <td class="dataTableRow" valign="top"><span class="smallText"><?=$i?></span></td>
                <?php if (WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable'){ ?>
                <td class="dataTableRow" valign="top"><span class="smallText"><?php echo tep_draw_file_field('upload_products_image_xl_'.$i) . tep_draw_hidden_field('products_previous_image_xl_'.$i, $pi); ?></span></td>
                <?php } else { ?>
				<td class="dataTableRow" valign="top"><?php echo '<table border="0" cellspacing="0" cellpadding="0"><tr><td class="dataTableRow">' . tep_draw_textarea_field('upload_products_image_xl_'.$i, 'soft', '70', '2', $pi) . tep_draw_hidden_field('products_previous_image_xl_'.$i, $pi) . '</td></tr></table>'; } ?>
              </td>
			  </tr>
              <tr>
                <td class="dataTableRow" colspan="3" valign="top"><?php if (tep_not_null($pi)) { ?><span class="smallText"><?php echo tep_image(DIR_WS_CATALOG_IMAGES . $pi, $pi, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="left" hspace="0" vspace="5"') . '<br>'. tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="unlink_image_xl_'.$i.'" value="yes">' . TEXT_PRODUCTS_IMAGE_REMOVE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '5', '15') . '&nbsp;<input type="checkbox" name="delete_image_xl_'.$i.'" value="yes">' . TEXT_PRODUCTS_IMAGE_DELETE_SHORT . '<br>' . tep_draw_separator('pixel_trans.gif', '1', '42'); ?></span><?php } ?></td>
              </tr>

	     <?
	      }
	     ?>


<?php
  } else {
   echo
        tep_draw_hidden_field('products_previous_image_xl_1', $pInfo->products_image_xl_1) .
        tep_draw_hidden_field('products_previous_image_xl_2', $pInfo->products_image_xl_2) .
        tep_draw_hidden_field('products_previous_image_xl_3', $pInfo->products_image_xl_3) .
        tep_draw_hidden_field('products_previous_image_xl_4', $pInfo->products_image_xl_4) .
        tep_draw_hidden_field('products_previous_image_xl_5', $pInfo->products_image_xl_5) .
        tep_draw_hidden_field('products_previous_image_xl_6', $pInfo->products_image_xl_6);
     }
// EOF: MaxiDVD Added for Ulimited Images Pack!
?></table>
<script language="javascript">
  function setGlobalFieldsDisplay(f) {
    var d=f?'':'none';
    $('global_products_quantity').style.display=$('global_products_model').style.display=$('global_products_sku').style.display=$('global_products_image').style.display=$('global_products_date_available').style.display=d;
  }

  showAllModels();
</script>
</td></tr></table></td></tr></table>
     </div>
	 
    <div class="tabbertab">
	  <h2>Pricing</h2>
	 <table width="555" border="0" cellpadding="0" cellspacing="0">
	 <tr bgcolor="#ebebff">
            <td width="127" class="main"><?php echo TEXT_PRODUCTS_TAX_CLASS; ?></td>
            <td colspan="2" class="main"><?php echo tep_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()" style="font-family:verdana; font-size:8pt;"'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
            <td width="228" class="main"><?php echo tep_draw_input_field('products_price', $pInfo->products_price, 'onKeyUp="updateGross()" style="font-family:verdana; font-size:8pt; width:75px;"'); ?></td>
            <td width="200" rowspan="2" valign="top" class="main"><table width="200" border="0" cellspacing="0" cellpadding="5" style="border:solid 1px #666666">
              <tr>
                <td height="75" valign="top" bgcolor="#FFFFFF"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td style="color:#FF0000">&nbsp; Product
                        Cost:</td>
                    <td width="105"><?php echo tep_draw_input_field('products_cost', $pInfo->products_cost, 'style="font-family:verdana; font-size:8pt; width:75px;"'); ?></td>
                  </tr>
                  <tr>
                    <td colspan="2">&nbsp; Link Suppliers to Cost &nbsp;<input name="" type="checkbox" value=""></td>
                    </tr>
                  <tr>
                    <td colspan="2" style="padding-top:5px;">&nbsp; <textarea name="" rows="4" style="font-family:verdana; font-size:8pt; width:100px;">Supplier 1
Supplier 2
Supplier 3</textarea></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
            </tr>
          <tr bgcolor="#ebebff">
            <td valign="top" class="main" style="padding-top:5px;"><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
            <td valign="top" class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_gross', $pInfo->products_price, 'OnKeyUp="updateNet()" style="font-family:verdana; font-size:8pt; width:75px;"'); ?></td>
            </tr>

<script language="javascript">
<!--
updateGross();
//--></script>
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
<td style="padding-top:10px;" colspan="3">&nbsp; <b>Pricing Groups</b></td>
</tr>


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
 } // end if (!header), makes sure this is only shown once
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
        } // end while ($customers_group = tep_db_fetch_array($customers_group_query))
?>
                    <tr> 
                      <td colspan="3" style="padding:10px"><hr size="1"></td>
                    </tr-->
					 <tr>
           <td class="main">Minimum Order Qnty:</td>
		              <td class="main"><?php echo tep_draw_input_field('products_qty_blocks', $pInfo->products_qty_blocks, 'style="font-size:8pt; width:75px"') . '';?></td>
		              <td>
					  
					  <SCRIPT LANGUAGE="JavaScript">
<!--
function showHide(elementid){
if (document.getElementById(elementid).style.display == 'none'){
document.getElementById(elementid).style.display = '';
} else {
document.getElementById(elementid).style.display = 'none';
}
} 
//-->
</SCRIPT>

<A HREF="javascript:showHide('div1')">Calculator Expand/Collapse</A>
<div style="position:relative;">
<div style="position:absolute;">
<div id="div1" style="display:none; border: solid 1px #FFFFFF;">
					  
					  <table border="0" width="165" cellspacing="0" cellpadding="0" bgcolor="#6295FD"
style="border-color:black" onClick="previouskey=event.srcElement.innerText">
  <tr>
    <td width="100%" bgcolor="#FFFFFF" id="result" style="border:solid 1px #666666; font:bold 13px tahoma; color:black; text-align:right; height:19px; padding:5px;">0</td>
  </tr>
  <tr>
    <td width="100%" valign="middle" align="center"><table border="0" width="100%" cellspacing="0" cellpadding="0" style="font:bold 11px tahoma; color:white">
      <tr>
        <td width="80%" align="center" valign="top" style="padding-top:4px">
		<table border="1" width="100%" cellspacing="0" cellpadding="0" style="cursor:hand; font:bold 11px tahoma; color:white"  onMouseover="if (event.srcElement.tagName=='TD')event.srcElement.style.color='yellow'" onMouseout="event.srcElement.style.color='white'" onselectStart="return false" onClick="calculate()" height="82">
          <tr>
            <td width="25%" align="center" height="17">7</td>
            <td width="25%" align="center" height="17">8</td>
            <td width="25%" align="center" height="17">9</td>
            <td width="25%" align="center" height="17">/</td>
          </tr>
          <tr>
            <td width="25%" align="center" height="19">4</td>
            <td width="25%" align="center" height="19">5</td>
            <td width="25%" align="center" height="19">6</td>
            <td width="25%" align="center" height="19">*</td>
          </tr>
          <tr>
            <td width="25%" align="center" height="19">1</td>
            <td width="25%" align="center" height="19">2</td>
            <td width="25%" align="center" height="19">3</td>
            <td width="25%" align="center" height="19">-</td>
          </tr>
          <tr>
            <td width="25%" align="center" height="19">0</td>
            <td width="25%" align="center" height="19"
            onClick="pn();previouskey=1;event.cancelBubble=true">+/-</td>
            <td width="25%" align="center" height="19">.</td>
            <td width="25%" align="center" height="19">+</td>
          </tr>
        </table>
        </td>
        <td width="20%" style="padding-top:4px;" valign="top"><div align="left"><table border="1" width="100%" cellspacing="0"
        cellpadding="0">
          <tr>
            <td width="100%" style="cursor:hand;font:bold 11px tahoma ;color:white; text-align:center" onClick="result.innerText=0;results=''">C</td>
          </tr>
        </table>
        </div><div align="left"><table border="1" width="100%" cellspacing="0" cellpadding="0" height="65">
          <tr>
            <td width="100%" style="cursor:hand; font:bold 13 tahoma; color:white; text-align:center" onMouseover="event.srcElement.style.color='yellow'" onMouseout="event.srcElement.style.color='white'" onClick="calculateresult()">=</td>
          </tr>
        </table>
        </div></td>
      </tr>
    </table>
    </td>
  </tr>
</table>


<script language="JavaScript1.2">

var results=''
var previouskey=''
var re=/(\/|\*|\+|-)/
var re2=/(\/|\*|\+|-){2}$/
var re3=/.+(\/|\*|\+|-).+/
var re4=/\d|\./
var re5=/^[^\/\*\+].+\d$/
var re6=/\./

function calculate(){
if (event.srcElement.tagName=="TD"){
if (event.srcElement.innerText.match(re4)&&previouskey=="=")
results=''
if (result.innerText.match(re3)&&event.srcElement.innerText.match(re)){
if (!results.match(re5)){
result.innerText="Error!"
return
}
results=eval(results)
if (results.toString().length>=12&&results.toString().match(re6))
results=results.toString().substring(0,12)
result.innerText=results
}

results+=event.srcElement.innerText
if (results.match(re2))
results=results.substring(0,results.length-2)+results.charAt(results.length-1)

result.innerText=results
}
}

function calculateresult(){
if (!results.match(re5)){
result.innerText="Error!"
return
}
results=eval(results)
if (results.toString().length>=12&&results.toString().match(re6))
results=results.toString().substring(0,12)
result.innerText=results
}



function pn(){
if (result.innerText.charAt(0)!='-')
result.innerText=results='-'+result.innerText
else if (result.innerText.charAt(0)=='-')
result.innerText=results=result.innerText*(-1)
}

</script>

</div></div></div>
</td>
		              </tr>
		            <tr>
<?
  $cusGroupSelect=Array();
  $discounts=Array();
  $grp_price=Array();
  foreach ($cus_groups AS $cgrp=>$cgname) {
    $discounts[$cgrp]=Array();
    $cusGroupSelect[]=Array(id=>'pricing_group_'.$cgrp,'text'=>$cgname);
  }
  $dsc_query=tep_db_query("SELECT * FROM products_discount WHERE products_id='".$pInfo->products_id."' ORDER BY customers_group_id,discount_qty");
  while ($dsc_row=tep_db_fetch_array($dsc_query)) $discounts[$dsc_row['customers_group_id']][$dsc_row['discount_qty']]=$dsc_row['discount_percent'];
  $pgrp_query=tep_db_query("SELECT * FROM ". TABLE_PRODUCTS_GROUPS. " WHERE products_id='".$pInfo->products_id."'");
  while ($pgrp_row=tep_db_fetch_array($pgrp_query)) $grp_price[$pgrp_row['customers_group_id']]=$pgrp_row['customers_group_price'];
?>
<script language="javascript">
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
				<?=tep_draw_pull_down_menu('switch_pricing_group',$cusGroupSelect,'','id="switch_pricing_group" onChange="showPricingGroups(this);"')?>
<?  foreach ($cus_groups AS $cgrp=>$cgname) { ?>
				<div id="pricing_group_<?=$cgrp?>" style="display:none">
<?	if ($cgrp) { ?>
				<?=tep_draw_checkbox_field('sppcoption[' . $cgrp . ']', 1, isset($grp_price[$cgrp])  ,'','onChange="$(\'pricing_group_details_'.$cgrp.'\').style.display=this.checked?\'\':\'none\'"');?> Separate pricing for this group
				<div id="pricing_group_details_<?=$cgrp?>" style="display:<?=isset($grp_price[$cgrp])?'':'none'?>">
				Price for this Customers Group: <?=tep_draw_input_field('sppcprice[' . $cgrp . ']', $grp_price[$cgrp]);?>
<?	} ?>
				<table border="0" cellspacing="0" cellpadding="0">
			        <tr><th>Quantity:</th><th>Discount&nbsp;%</th></tr>
<?	$discounts[$cgrp]['']='';
	foreach ($discounts[$cgrp] AS $qty=>$dsc) { ?>
			        <tr><td><?=tep_draw_input_field('discount_qty['.$cgrp.'][]', $qty, 'size="7" onChange="adjustDiscountTable(this);"')?></td><td><?=tep_draw_input_field('discount_percent['.$cgrp.'][]', $dsc, 'size="7"')?></td></tr>
<?	} ?>
				</table>
<?	if ($cgrp) { ?>
				</div>
<?	} ?>
				</div>
<?  } ?>
<script language="javascript">showPricingGroups($('switch_pricing_group'));</script>
			      </td>
		            </tr>


        </table>
     </div>
	 
	 <div class="tabbertab">
	  <h2>Shipping / Payment</h2>
	  <table width="555" border="0" cellpadding="0" cellspacing="0">
                   <tr>
            <td class="main">Free Shipping</td>
            <td class="main"><?php  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' .tep_draw_pull_down_menu('products_free_shipping', $free_shipping_array, $pInfo->products_free_shipping); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main">Ship in a Separate Package</td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_separate_shipping', $free_shipping_array, $pInfo->products_separate_shipping); ?></td>
          </tr>
					<tr>
            <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT; ?></td>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_weight', $pInfo->products_weight); ?></td>
          </tr>
<tr>
<td class="main">Shipping Method</td><td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_ship_method', $pInfo->products_ship_method); ?></td>
</tr>
<tr>
<td class="main">Payment Method</td><td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_payment_method', $pInfo->products_payment_method); ?></td>
</tr>
       </table>
     </div>
	 
<? if ($productObject->productEditSectionAllowed('marketing')) { ?>
	 <div class="tabbertab">
	  <h2>Marketing</h2>
	  <table width="555" border="0" cellpadding="0" cellspacing="0">
	   <tr>
            <td class="main" bgcolor="#ebebff">BestSeller Icon</td>
            <td class="main" bgcolor="#ebebff"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('bs_icon', $free_shipping_array, $pInfo->bs_icon); ?></td>
          </tr>
	   <tr>
            <td colspan="2" class="main" style="padding-top:10px;"><?php echo TEXT_PRODUCT_METTA_INFO; ?></td>
          </tr>
           <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr> 
<?php          
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
                 
          <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_PAGE_TITLE; ?></td>
            <td><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" valign="top"><?php echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main"><?php echo tep_draw_textarea_field('products_head_title_tag[' . $languages[$i]['id'] . ']', 'soft', '55', '5', (isset($products_head_title_tag[$languages[$i]['id']]) ? $products_head_title_tag[$languages[$i]['id']] : tep_get_products_head_title_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
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
                <td class="main"><?php echo tep_draw_textarea_field('products_head_desc_tag[' . $languages[$i]['id'] . ']', 'soft', '55', '3', (isset($products_head_desc_tag[$languages[$i]['id']]) ? $products_head_desc_tag[$languages[$i]['id']] : tep_get_products_head_desc_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
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
                <td class="main"><?php echo tep_draw_textarea_field('products_head_keywords_tag[' . $languages[$i]['id'] . ']', 'soft', '55', '1', (isset($products_head_keywords_tag[$languages[$i]['id']]) ? $products_head_keywords_tag[$languages[$i]['id']] : tep_get_products_head_keywords_tag($pInfo->products_id, $languages[$i]['id']))); ?></td>
              </tr>
            </table></td>
          </tr>
<?php
    }
?>
<tr>
<td colspan="2" style="padding-top:10px; padding-bottom:10px;"><hr>
  <b>Pay-Per-Click Manager</b></td>
</tr>
                    <tr> 
                      <td colspan="2"><iframe src="product_ads.php?pID=<?=$pInfo->products_id?>" allowtransparency="true" style="margin:0;" frameborder="0" id="" width="555" class="contentiframe"></iframe></td>
                    </tr>
<tr>
            <td class="main" style="padding-top:15px; border-top:1px solid #333333;"><b>Cross-sell Products</b></td><td align="right"><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b style=&quot;white-space:nowrap;&quot;>Product Cross-Sell &amp; Linking</b></font><br><br><b>ID</b> - System ID of the model.<br><br><b>Image</b> - Model image.<br><br><b>Model</b> - Model of product to cross sell.<br><br><b>Discount</b> - The difference or discount issued if product is purchased in combination with the product your editing.<br><br><b>Qty.</b> - Maximum quantity the discount applies too.<br><br><b>Channels</b> - <br><u>Stock Links:</u> Add products to your stock link channel if your parent product is a combination of individually stocked products. If you want to sell a combo product, but display said combo as a separate product for purchase, this is where you\'ll connect the contents of the combination.<br><br><u>Product Info Group:</u> Add products to this channel to display as linked upgrades on your product info page.<br><br><u>Also Available:</u> This is a simple cross sell suggestion channel. You may create any number of standard cross sell suggestion channels.')" onMouseout="hideddrivetip()"> </div></td>
</tr>
<tr><td colspan="2">
<? include(DIR_FS_MODULES.'xsellctl.php'); ?>
</td>
</tr>
       </table>
     </div>
<? }	 
   if ($productObject->productEditSectionAllowed('auctions')) { ?>
	 <div class="tabbertab">
	  <h2 style="white-space: nowrap">Auctions / Feeds</h2>
	  <table width="555" border="0" cellpadding="0" cellspacing="0">
                    <tr> 
                      <td style="padding-top:10px; padding-bottom:10px;"><? $DBFeedMods->AdminProductEdit($pInfo->products_id)?></td>
                    </tr>
                    <tr> 
                      <td style="padding-top:10px; padding-bottom:10px;">Ebay Auction Feed not activated.</td>
                    </tr>
       </table>
     </div>
<? }
   $extra=Array();
   $extra_qry=tep_db_query("SELECT * FROM products_extra WHERE products_id='".$pInfo->products_id."'");
   while ($extra_row=tep_db_fetch_array($extra_qry)) $extra[$extra_row['products_extra_key']]=$extra_row['products_extra_value'];
   $pfields=$productObject->getProductFields();
   if ($pfields) {
?>
	 <div class="tabbertab">
	  <h2 style="white-space: nowrap"><?=$productObject->getName()?></h2>
	  <table width="555" border="0" cellpadding="0" cellspacing="0">
<?    foreach ($pfields AS $fld=>$fdata) {
	$fval=isset($extra[$fld])?$extra[$fld]:$fdata['default'];
?>
                    <tr> 
                      <td><?=$fdata['title']?>:</td><td><?=tep_draw_input_field('products_extra['.$fld.']',$fval)?></td>
                    </tr>
<?    } ?>
       </table>
     </div>

<? } ?>

</div></td>
		  </tr>
        </table></td>
      </tr>
      <tr>
        <td class="main" align="right" colspan="2" style="padding:10px;"><?php echo tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . tep_image_submit('button_update.gif', IMAGE_UPDATE,' name="express_update"') . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pclass='.$pClass.'&cPath=' . $cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr>
    </table></form>

<script type="text/javascript" src="js/tabber.js"></script>

<?php
  }
?>
    </td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
