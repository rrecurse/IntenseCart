<table width="100%" cellpadding="0" cellspacing="0">
<?
  $category_array = tep_main_categories();
  $isFirst = true;
  foreach ($category_array as $c) {
    if ($isFirst) {
      $isFirst = false;
    } else {
      if ($c['pID'] == '0') {
        echo '<tr><td class="shopbycat_linespace" colspan="2"></td></tr>';
      }
    }
    echo '<tr> 
            <td valign="top"><font class="shopbycat_raquo">&raquo;</font></td>
            <td><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'cPath=' . $c['id']) . '" class="shopbycat_txt">' . $c['text'] . '</a></td>
          </tr>';
  }

  function tep_main_categories($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
    global $languages_id;
    global $template_config;
    if (!is_array($category_tree_array)) $category_tree_array = array();

    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and c.parent_id = '" . (int)$parent_id . "' and c.categories_status=1 AND c.products_class='product_default' order by c.sort_order, cd.categories_name");
    while ($categories = tep_db_fetch_array($categories_query)) {
      
if ($exclude != $categories['categories_id']) $category_tree_array[] = array('id' => $categories['categories_id'], 'pID' => $parent_id, 'text' => $spacing . $categories['categories_name']);
      $category_tree_array = tep_main_categories ($categories['categories_id'], $spacing . '', $exclude . '', $category_tree_array); 
    }
    return $category_tree_array;

  }

?></table>
