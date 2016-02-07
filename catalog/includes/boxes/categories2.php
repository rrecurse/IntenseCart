<?php
/*
  $Id: categories.php,v 1.25 2003/07/09 01:13:58 hpdl Exp $
  Based on prior works of
  
  
  
    New categories box featuring manufacturers
    Written in 06.2007 by Peter Brandt
    www.imagetag.de

  
*/

  function tep_show_category($counter) {
    global $tree, $categories_string, $cPath_array, $navPath, $offset;
    $filter_id=$_GET['filter_id'];
    for ($i=0; $i<$tree[$counter]['level']-1; $i++) {
      $categories_string .= "&nbsp;&nbsp;";
    }

    $categories_string .= '<a href="';

    if ($tree[$counter]['parent'] == 0) {
      $cPath_new = 'cPath=' . $counter;
    } else {
      $cPath_new = 'cPath=' . $tree[$counter]['path'];
    }

    $categories_string .= tep_href_link(FILENAME_DEFAULT, $cPath_new) . '">';

    if (isset($cPath_array) && in_array($counter, $cPath_array) || isset($filter_id) && $counter == $filter_id+$offset){
      $categories_string .= '<b>';
    }

// display category name
    $categories_string .= $tree[$counter]['name'];

    if (isset($cPath_array) && in_array($counter, $cPath_array) || isset($filter_id) && $counter == $filter_id+$offset) {
      $categories_string .= '</b>';
    }

    if (tep_has_category_subcategories($counter)) {
      $categories_string .= '-&gt;';
    }

    $categories_string .= '</a>';

    if (SHOW_COUNTS == 'true') {
      $products_in_category = tep_count_products_in_category($counter);
      if ($products_in_category > 0) {
        $categories_string .= '&nbsp;(' . $products_in_category . ')';
      }
    }

    $categories_string .= '<br>';

    if ($tree[$counter]['next_id'] != false) {
      tep_show_category($tree[$counter]['next_id']);
    }
  }
?>
<!-- categories //-->
          <tr>
            <td>
<?php
  $info_box_contents = array();
  $info_box_contents[] = array('text' => BOX_HEADING_CATEGORIES);
  new infoBoxHeading($info_box_contents, true, false);

  $id_query=tep_db_query("SELECT categories_id as id FROM " . TABLE_CATEGORIES . " ORDER BY id DESC LIMIT 1"); # Used to determine the last used categories_id.
  $id=tep_db_fetch_array($id_query);                                                                           # The offset is needed to prevent double id entries
  $offset=$id['id'];                                                                                           # in the output array (tree).
  $navPath=array();
  $navPath[]=0;
  $categories_string = '';
  $tree = array();
  $level=0;
  $new_path = '';

  if (isset($cPath_array)){
    $navPath=array_merge($navPath,$cPath_array);
  }

  while (list($key, $value) = each($navPath)) {
    unset($parent_id);
    unset($first_id);
    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.parent_id = " . (int)$value . " and c.categories_id = cd.categories_id and cd.language_id='" . (int)$languages_id ."' order by sort_order, c.parent_id, cd.categories_name");
    if (!tep_db_num_rows($categories_query)) {
      $categories_query = tep_db_query("select distinct (m.manufacturers_id + " . (int)$offset . ") as categories_id, m.manufacturers_name as categories_name, p2c.categories_id as parent_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_MANUFACTURERS . " m where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$value . "' order by m.manufacturers_name");
    $new_path .= $value."&filter_id=";
    }
    else {
      if ($value!=0) {$new_path .= $value."_";}
      }
    if (tep_db_num_rows($categories_query)) {
      while ($categories = tep_db_fetch_array($categories_query))  {
       if ($categories['categories_id']>$offset) {$output=$categories['categories_id']-$offset;}  #The offset set in the query is removed to set the correct filter_id
	 else {$output=$categories['categories_id'];}
	 $tree[$categories['categories_id']] = array('name' => $categories['categories_name'],
                                                    'parent' => $categories['parent_id'],
                                                    'level' => $key+1,
                                                    'path' => $new_path . $output,
                                                    'next_id' => false);

      if (!isset($first_element)) {
        $first_element = $categories['categories_id'];
        }

      if (isset($parent_id)) {
        $tree[$parent_id]['next_id'] = $categories['categories_id'];
        }

      $parent_id = $categories['categories_id'];

      if (!isset($first_id)) {
        $first_id = $categories['categories_id'];
        }

      $last_id = $categories['categories_id'];

	}
    $tree[$last_id]['next_id'] = $tree[$value]['next_id'];
    $tree[$value]['next_id'] = $first_id;

    } else {
       break;
      }
    }
  tep_show_category($first_element);
  $info_box_contents = array();
  $info_box_contents[] = array('text' => $categories_string);

  new infoBox($info_box_contents);
?>
            </td>
          </tr>
<!-- categories_eof //-->