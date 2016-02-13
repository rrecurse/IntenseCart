<?php

  include(DIR_FS_CATALOG_LOCAL."header_tags_$language.php");

$tags_array = array();
// # Define specific settings per page:
switch (true) {
	case (strstr($_SERVER['PHP_SELF'],FILENAME_ALLPRODS) or strstr($PHP_SELF,FILENAME_ALLPRODS) ):

		$the_category_query = tep_db_query("SELECT cd.categories_name 
											FROM " . TABLE_CATEGORIES . " c
											LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = '" . $current_category_id . "' AND cd.language_id = '" . $languages_id . "'
											WHERE c.categories_id = '" . $current_category_id . "' 
											");
		$the_category = tep_db_fetch_array($the_category_query);

		$the_manufacturers_query= tep_db_query("SELECT manufacturers_name 
												FROM " . TABLE_MANUFACTURERS . " 
												WHERE manufacturers_id = '" . $_GET['manufacturers_id'] . "'
											   ");

		$the_manufacturers = tep_db_fetch_array($the_manufacturers_query);

		if (HTDA_ALLPRODS_ON == '1') {
			$tags_array['desc'] = HEAD_DESC_TAG_ALLPRODS . ' ' . HEAD_DESC_TAG_ALL;
		} else {
			$tags_array['desc'] = HEAD_DESC_TAG_ALLPRODS;
		}

    if (HTKA_ALLPRODS_ON=='1') {
      $tags_array['keywords']= HEAD_KEY_TAG_ALL . ' ' . HEAD_KEY_TAG_ALLPRODS;
    } else {
      $tags_array['keywords']= HEAD_KEY_TAG_ALLPRODS;
    }

    if (HTTA_ALLPRODS_ON=='1') {
      $tags_array['title']= HEAD_TITLE_TAG_ALLPRODS . ' ' . HEAD_TITLE_TAG_ALL . " " . $the_category['categories_name'] . $the_manufacturers['manufacturers_name'];
    } else {
      $tags_array['title']= HEAD_TITLE_TAG_ALLPRODS;
    }
    break;

// # info pages
  case isset($_GET['info_id']):
   $info_sql=tep_db_query("SELECT page_title,htc_description,htc_keywords,info_title FROM ".TABLE_INFORMATION." WHERE information_id='".addslashes($_GET['info_id'])."'");
   $info_row=tep_db_fetch_array($info_sql);
   $tags_array['title']=($info_row && $info_row['page_title'])?$info_row['page_title']:HEAD_TITLE_TAG_ALL;
   $tags_array['desc']=($info_row && $info_row['htc_description'])?$info_row['htc_description']:HEAD_DESC_TAG_ALL;
   $tags_array['keywords']=($info_row && $info_row['htc_keywords'])?$info_row['htc_keywords']:HEAD_KEY_TAG_ALL;
   break;


// # products_all.PHP
case (strstr($_SERVER['PHP_SELF'],FILENAME_PRODUCTS_ALL) or strstr($PHP_SELF,FILENAME_PRODUCTS_ALL) ):

    $the_category_query = tep_db_query("select cd.categories_name from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = '" . $current_category_id . "' and cd.categories_id = '" . $current_category_id . "' and cd.language_id = '" . $languages_id . "'");
    $the_category = tep_db_fetch_array($the_category_query);

    $the_manufacturers_query= tep_db_query("select manufacturers_name from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . $_GET['manufacturers_id'] . "'");
    $the_manufacturers = tep_db_fetch_array($the_manufacturers_query);

    if (HTDA_PRODUCTS_ALL_ON=='1') {
      $tags_array['desc']= HEAD_DESC_TAG_PRODUCTS_ALL . ' ' . HEAD_DESC_TAG_ALL;
    } else {
      $tags_array['desc']= HEAD_DESC_TAG_PRODUCTS_ALL;
    }

    if (HTKA_PRODUCTS_ALL_ON=='1') {
      $tags_array['keywords']= HEAD_KEY_TAG_ALL . ' ' . HEAD_KEY_TAG_PRODUCTS_ALL;
    } else {
      $tags_array['keywords']= HEAD_KEY_TAG_PRODUCTS_ALL;
    }

    if (HTTA_ALLPRODS_ON=='1') {
      $tags_array['title']= HEAD_TITLE_TAG_PRODUCTS_ALL . ' ' . HEAD_TITLE_TAG_ALL . " " . $the_category['categories_name'] . $the_manufacturers['manufacturers_name'];
    } else {
      $tags_array['title']= HEAD_TITLE_TAG_PRODUCTS_ALL;
    }
    break;

// # INDEX.PHP
  case ((strstr($_SERVER['PHP_SELF'],FILENAME_DEFAULT) or strstr($PHP_SELF,FILENAME_DEFAULT) ) && (!isset($_GET['products_id'])) ):

    $the_category_query = tep_db_query("select categories_name, categories_htc_title_tag, categories_htc_desc_tag, categories_htc_keywords_tag from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '" . (int)$current_category_id . "' and language_id = '" . (int)$languages_id . "'");
    $the_category = tep_db_fetch_array($the_category_query);

    $the_manufacturers_query= tep_db_query("SELECT manufacturers_name from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$HTTP_GET_VARS['manufacturers_id'] . "'");
    $the_manufacturers = tep_db_fetch_array($the_manufacturers_query);

    	//error_log(print_r('manufacturers_name - ' . $the_manufacturers['manufacturers_name'],1));

 
    $showCatTags = false;
    if ($category_depth == 'nested' || ($category_depth == 'products' || isset($_GET['manufacturers_id']))) 
      $showCatTags = true;
    
    if (HTDA_DEFAULT_ON=='1') {
      if ($showCatTags == true) {
         if (HTTA_CAT_DEFAULT_ON=='1') {
           $tags_array['desc']= $the_category['categories_htc_desc_tag'] . ' ' . HEAD_DESC_TAG_DEFAULT . ' ' . HEAD_DESC_TAG_ALL;
         } else {
           $tags_array['desc']= $the_category['categories_htc_desc_tag'] . ' ' . HEAD_DESC_TAG_ALL;
         }
      } else {
        $tags_array['desc']= HEAD_DESC_TAG_DEFAULT . ' ' . HEAD_DESC_TAG_ALL;
      }
    } else {
      if ($showCatTags == true) {
         if (HTTA_CAT_DEFAULT_ON=='1') {
           $tags_array['desc']= $the_category['categories_htc_desc_tag'] . ' ' . HEAD_DESC_TAG_DEFAULT;
         } else {
           $tags_array['desc']= (tep_not_null($the_category['categories_htc_desc_tag']) ? $the_category['categories_htc_desc_tag'] : HEAD_DESC_TAG_ALL);
         }
      } else {
        $tags_array['desc']= HEAD_DESC_TAG_DEFAULT;
      }  
    }

    if (HTKA_DEFAULT_ON=='1') {
      if ($showCatTags == true) {
          if (HTTA_CAT_DEFAULT_ON=='1') {
            $tags_array['keywords']= $the_category['categories_htc_keywords_tag'] . ' ' . HEAD_KEY_TAG_ALL . ' ' . HEAD_KEY_TAG_DEFAULT;
          } else {  
            $tags_array['keywords']= $the_category['categories_htc_keywords_tag'] .  ' ' . HEAD_KEY_TAG_DEFAULT;
          }
      } else {
        $tags_array['keywords']= HEAD_KEY_TAG_ALL . ' ' . HEAD_KEY_TAG_DEFAULT;
      }  
    } else {
      if ($showCatTags == true) {
         if (HTTA_CAT_DEFAULT_ON=='1') {
           $tags_array['keywords']= $the_category['categories_htc_keywords_tag'] . ' ' . HEAD_KEY_TAG_DEFAULT;
         } else {
           $tags_array['keywords']= (tep_not_null($the_category['categories_htc_keywords_tag']) ? $the_category['categories_htc_keywords_tag'] : HEAD_KEY_TAG_ALL); // Was _DEFAULT
         }  
      } else {
         $tags_array['keywords']= HEAD_KEY_TAG_DEFAULT;
      }   
    }

    if (HTTA_DEFAULT_ON=='1') {
      if ($showCatTags == true) {
        if (HTTA_CAT_DEFAULT_ON=='1') {
          $tags_array['title']= $the_category['categories_htc_title_tag'] .' '.  HEAD_TITLE_TAG_DEFAULT . " " .  $the_manufacturers['manufacturers_name'] . ' - ' . HEAD_TITLE_TAG_ALL;
        } else {
          $tags_array['title']= (tep_not_null($the_category['categories_htc_title_tag']) ? $the_category['categories_htc_title_tag'] : $the_category['categories_name']) .' '.  $the_manufacturers['manufacturers_name'] . ' - ' . HEAD_TITLE_TAG_ALL;
        }
      } else {
        $tags_array['title']= HEAD_TITLE_TAG_DEFAULT . " " . $the_category['categories_name'] . $the_manufacturers['manufacturers_name'] . ' - ' . HEAD_TITLE_TAG_ALL;
      }
    } else {
      if ($showCatTags == true) {
        if (HTTA_CAT_DEFAULT_ON=='1') {

			$tags_array['title'] = (tep_not_null($the_category['categories_htc_title_tag']) ? $the_category['categories_htc_title_tag'] : $the_category['categories_name']) . ' ' . HEAD_TITLE_TAG_DEFAULT;

		} elseif((HTTA_CAT_DEFAULT_ON !='1') && tep_not_null($the_manufacturers['manufacturers_name'])){

			$tags_array['title'] = $the_manufacturers['manufacturers_name'] . ' - ' . HEAD_TITLE_TAG_DEFAULT;

        } else {

			$tags_array['title']= (tep_not_null($the_category['categories_htc_title_tag']) ? $the_category['categories_htc_title_tag'] : HEAD_TITLE_TAG_ALL);

        } 

      } else {

        $tags_array['title']= HEAD_TITLE_TAG_DEFAULT;
      }  
    }

    break;

// PRODUCT_INFO.PHP
  case ($real_page_name == FILENAME_PRODUCT_INFO || strstr($_SERVER['PHP_SELF'],FILENAME_PRODUCT_INFO) || (strstr($_SERVER['PHP_SELF'],FILENAME_DEFAULT) && isset($_GET['products_id'])));
    $tag_query = tep_db_query("SELECT products_name, products_head_title_tag, products_head_desc_tag, products_head_keywords_tag FROM " . TABLE_PRODUCTS_DESCRIPTION . " WHERE products_id = '".(int)$_GET['products_id'] . "' AND language_id = '".$languages_id . "'");
    if (tep_db_num_rows($tag_query) > 0) {
      $tag = tep_db_fetch_array($tag_query);
      /*
      $tags_array['desc'] = $tag['products_head_title_tag'];
      $tags_array['keywords'] = $tag['products_head_desc_tag'];
      $tags_array['title'] = $tag['products_head_keywords_tag'];
      */
      $tags_array = tep_header_tag_page(HTTA_PRODUCT_INFO_ON, (tep_not_null($tag['products_head_title_tag']) ? $tag['products_head_title_tag'] : $tag['products_name']), HTDA_PRODUCT_INFO_ON, HEAD_DESC_TAG_PRODUCT_INFO, HTKA_PRODUCT_INFO_ON, HEAD_KEY_TAG_PRODUCT_INFO );
      $tags_array['keywords'] = $tag['products_head_keywords_tag'];
      $tags_array['desc'] = $tag['products_head_desc_tag'];
    } else {
      $tags_array = tep_header_tag_page(HTTA_PRODUCT_INFO_ON, HEAD_TITLE_TAG_PRODUCT_INFO, 
                                        HTDA_PRODUCT_INFO_ON, HEAD_DESC_TAG_PRODUCT_INFO, 
                                        HTKA_PRODUCT_INFO_ON, HEAD_KEY_TAG_PRODUCT_INFO );
    }
   break;

// ALL OTHER PAGES NOT DEFINED ABOVE
  default:
    $tags_array['desc'] = HEAD_DESC_TAG_ALL;
    $tags_array['keywords'] = HEAD_KEY_TAG_ALL;
    $tags_array['title'] = HEAD_TITLE_TAG_ALL;
    break;
  }

echo '<title>' . $tags_array['title'] . '</title>' . "\n";
echo '<meta name="Description" Content="' .$tags_array['desc'] . '">' . "\n";
echo '<meta name="Abstract" Content="' .$tags_array['desc'] . '">' . "\n";
echo '<meta name="Keywords" Content="' . $tags_array['keywords'] . '">' . "\n";
?>
