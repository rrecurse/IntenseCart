
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_REPORTS,
                     'link'  => tep_href_link(FILENAME_STATS_SALES_REPORT, 'selected_box=reports'));

  if ($selected_box == 'reports') {
    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_STATS_SALES_REPORT, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_SALES_REPORT . '</a><br>' .

                                   '<a href="stats_sales.php?selected_box=reports&by=date" class="menuBoxContentLink">' . BOX_REPORTS_SALES . '</a><br>' .
                                    
                                    '<a href="' . tep_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_PRODUCTS_PURCHASED . '</a><br>' .
                                    '<a href="' . tep_href_link(FILENAME_STATS_PRODUCTS_VIEWED, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_PRODUCTS_VIEWED . '</a><br>' .
				                   '<a href="' . tep_href_link(FILENAME_STATS_AD_RESULTS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_AD_RESULTS . '</a><br>' .
'<a href="' . tep_href_link(FILENAME_STATS_PRODUCTS_BACKORDERED, '', 'NONSSL') . '" class="menuBoxContentLink">Low Stock Report</a><br>' .  
                                   //'<a href="' . tep_href_link(FILENAME_STATS_LOW_STOCK_ATTRIB, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_STATS_LOW_STOCK_ATTRIB . '</a><br>' . 
                                  '<a href="' . tep_href_link(FILENAME_STATS_CUSTOMERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_ORDERS_TOTAL . '</a><br>----------------------<br>' .

                                   '<a href="' . tep_href_link('supertracker.php', '', 'NONSSL') . '" class="menuBoxContentLink">Traffic Statistics</a><br>' .                                 
'<a href="' . tep_href_link(FILENAME_STATS_AVERAGE, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_REPORTS_STATS_AVERAGE . '</a><br>' . 
'<font style="font-size:9px;">&#8226; <a href="' . tep_href_link(FILENAME_STATS_REFERRAL_SOURCES, '', 'NONSSL') . '" class="menuBoxContentLink">Refered by ...</a></font><br>----------------------<br>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- reports_eof //-->
