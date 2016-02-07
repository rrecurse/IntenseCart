<?
  if (isset($HTTP_GET_VARS['manufacturers_id'])) {
    $filterlist_sql = "select distinct c.categories_id as id, cd.categories_name as name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where p.products_status = '1' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id and p2c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and p.manufacturers_id = '" . (int)$HTTP_GET_VARS['manufacturers_id'] . "' order by cd.categories_name";
  } else {
    $filterlist_sql= "select distinct m.manufacturers_id as id, m.manufacturers_name as name from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_MANUFACTURERS . " m where p.products_status = '1' and p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$current_category_id . "' order by m.manufacturers_name";
  }
  $filterlist_query = tep_db_query($filterlist_sql);
  if (tep_db_num_rows($filterlist_query) > 1) {                                        echo '            <table border="0"><tr><td align="center" class="main">' . tep_draw_form('filter', FILENAME_DEFAULT, 'get') . TEXT_SHOW . '&nbsp;';
    if (isset($HTTP_GET_VARS['manufacturers_id'])) {
      echo tep_draw_hidden_field('manufacturers_id', $HTTP_GET_VARS['manufacturers_id']);
      $options = array(array('id' => '', 'text' => TEXT_ALL_CATEGORIES));
    } else {
      echo tep_draw_hidden_field('cPath', $cPath);
      $options = array(array('id' => '', 'text' => TEXT_ALL_MANUFACTURERS));
    }
    echo tep_draw_hidden_field('sort', $HTTP_GET_VARS['sort']);
    while ($filterlist = tep_db_fetch_array($filterlist_query)) {
      $options[] = array('id' => $filterlist['id'], 'text' => $filterlist['name']);
    }                                        
    echo tep_draw_pull_down_menu('filter_id', $options, (isset($HTTP_GET_VARS['filter_id']) ? $HTTP_GET_VARS['filter_id'] : ''), 'onchange="this.form.submit()"');
    echo '</form></td></tr></table>' . "\n";
  }
?>
