<?php
/*
  $Id: configuration.php,v 1.17 2003/07/09 01:18:53 hpdl Exp $

  
  

  

  
*/
?>
<!-- configuration //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_SUPPLIER,
                     'link'  => tep_href_link(FILENAME_SUPPLIER, 'gID=1&selected_box=supplier'));

  if ($selected_box == 'supplier') {
  /*  $cfg_groups = '';
    $configuration_groups_query = tep_db_query("select configuration_group_id as cgID, configuration_group_title as cgTitle from " . TABLE_CONFIGURATION_GROUP . " where visible = '1' order by sort_order");
    while ($configuration_groups = tep_db_fetch_array($configuration_groups_query)) {
      $cfg_groups .= '<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $configuration_groups['cgID'], 'NONSSL') . '" class="menuBoxContentLink">' . $configuration_groups['cgTitle'] . '</a><br>';
    }*/

    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_SUPPLIER_S_CATEGORIES_PRODUCTS, 'gID=1&selected_box=supplier') . '" class="menuBoxContentLink">' . BOX_HEADING_SUPPLIER_S_PRODUCTS . '</a><br><br>' .
	'<a href="' . tep_href_link(FILENAME_SUPPLIER_STATISTIC, 'gID=1&selected_box=supplier') . '" class="menuBoxContentLink">' . BOX_HEADING_SUPPLIER_STATISTIC . '</a>'
						);
  }
  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- configuration_eof //-->
