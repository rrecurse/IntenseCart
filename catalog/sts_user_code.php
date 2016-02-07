<?php
/*
$Id: sts_user_code.php,v 1.1 2005/07/25 18:19:27 stsdsea Exp stsdsea $
*/

// PUT USER MODIFIED CODE IN HERE, SUCH AS NEW BOXES, ETC.

// The following code is a sample of how to add new boxes easily.
//  Just uncomment block below and tweak for your needs! 
//  Use as many blocks as you need and just change the block names.

  // $sts_block_name = 'newthingbox';
  // require(STS_START_CAPTURE);
  // require(DIR_WS_BOXES . 'new_thing_box.php');
  // require(STS_STOP_CAPTURE);
  // $template[$sts_block_name] = strip_unwanted_tags($sts_block[$sts_block_name], $sts_block_name);


    $sts_block_name = 'catmenu';
    require(STS_START_CAPTURE);
    echo "\n<!-- Start Category Menu -->\n";
    echo tep_draw_form('goto', FILENAME_DEFAULT, 'get', '');
    echo tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();" style="width: 135; font-size: 8pt; font-family:Verdana;"');
    echo "</form>\n";
    echo "<!-- End Category Menu -->\n";
    require(STS_STOP_CAPTURE);
    $template[$sts_block_name] = $sts_block[$sts_block_name];

function tep_get_category_tree($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
    global $languages_id;

    if (!is_array($category_tree_array)) $category_tree_array = array();
    if ( (sizeof($category_tree_array) < 1) && ($exclude != '0') ) $category_tree_array[] = array('id' => '0', 'text' => "Catalog");

    if ($include_itself) {
      $category_query = tep_db_query("select cd.categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " cd where cd.language_id = '" . (int)$languages_id . "' and cd.categories_id = '" . (int)$parent_id . "'");
      $category = tep_db_fetch_array($category_query);
      $category_tree_array[] = array('id' => $parent_id, 'text' => $category['categories_name']);
    }

    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and c.parent_id = '" . (int)$parent_id . "' order by c.sort_order, cd.categories_name");
    while ($categories = tep_db_fetch_array($categories_query)) {
      if ($exclude != $categories['categories_id']) $category_tree_array[] = array('id' => $categories['categories_id'], 'text' => $spacing . $categories['categories_name']);
      $category_tree_array = tep_get_category_tree($categories['categories_id'], $spacing . '&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array);
    }

    return $category_tree_array;
  }
	
// BG: DSEA: 07/29/05: Get Featured_4.php module for Home Page (index.php_0.html)
$sts_block_name = 'featured';
require(STS_START_CAPTURE);
include(DIR_WS_MODULES . "featured_4.php");
require(STS_STOP_CAPTURE);
$template[$sts_block_name] = strip_unwanted_tags($sts_block[$sts_block_name], $sts_block_name);


?>
