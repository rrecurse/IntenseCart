<?php

//echo tep_draw_pull_down_menu('cPath', tep_get_category_list(), $current_category_id, 'onChange="this.form.submit();" style="width: 135px; font-size: 8pt; font-family:Verdana;"');
$cats=tep_get_category_list();
?>
<div id="menu">
<?php
$lastitem = '';
if (isset($cats)){
foreach ($cats as $cat) {
  echo $cat . "\r\n";
  $lastitem = $cat;
}
}

if (substr($lastitem, -5) != '</li>') {
  echo '<li></li>';
}
?>
  </ul>
</li>
</ul>
</div>
<?php

$category_tree_array = array();
$close_me = false;
$depth = 0;
$cat_id_url = '';
function tep_get_category_list($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
    global $languages_id;
    global $category_tree_array;
    global $close_me;
    global $depth;
    global $cat_id_url;
    
    $cat_info=tep_get_category_info($parent_id);
    
    if (is_array($cat_info['tree'])) foreach ($cat_info['tree'] AS $categories) {
      if ($exclude != $categories['id']) {
        if (is_array($categories['tree'])) {
          $children = true;
        } else {
          $children = false;
        }

        if ($parent_id == '0') {
          $cat_id_url = $categories['id'];
          $disp_cat_id = $cat_id_url;
          $depth = 0;
          if (sizeof($category_tree_array) > 0) {
            $start_tag = '</ul></li><li>';
          } else {
            $start_tag = '<ul><li>';
          }
          $end_tag = '<ul>';
          if (!$children) $end_tag .= '<li></li>';
        } else {
          $start_tag = '<li class="catmenu-sub">';
          if (!$children) {
            $disp_cat_id = $cat_id_url .= '_' . $categories['id'];
            $end_tag = '</li>';
          } else {
            if (strpos($cat_id_url, $categories['id']) === false) {
              $cat_id_url .= '_' . $categories['id'];
            }
            $disp_cat_id = $cat_id_url;
            $end_tag = '<ul>';
            $close_me = $categories['id'];
            $depth++;
          }
        }
        $category_tree_array[] = $start_tag . '<a href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $categories['id'], 'NONSSL') . '">' . $categories['name'] . '</a>' . $end_tag;
      }

      $category_tree_array = tep_get_category_list($categories['id'], $spacing . '&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array);
    }
    if ($close_me == $parent_id) {
      for ($x=0; $x<$depth; $x++) {
        $category_tree_array[] = '</ul></li>';
      }
      $depth = 0;
      $close_me = false;
    }
    return $category_tree_array;
  }
  

?>