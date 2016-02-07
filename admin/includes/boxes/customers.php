<?php
/*
  $Id: customers.php,v 1.16 2003/07/09 01:18:53 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- customers //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_CUSTOMERS,
                     'link'  => tep_href_link(FILENAME_ORDERS, 'selected_box=customers'));

  if ($selected_box == 'customers') {
    $contents[] = array('text'  => '&#8226; <a href="' . tep_href_link(FILENAME_CUSTOMERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_CUSTOMERS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_ORDERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_ORDERS . '</a><br>----------------------<br>' .
 
                              
                                   '&#8226; <a href="' . tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_MANUAL_ORDER_CREATE_ACCOUNT . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_CREATE_ORDER, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_MANUAL_ORDER_CREATE_ORDER . '</a><br>----------------------<br>' .

                                   '&#8226; <a href="' . tep_href_link(FILENAME_CUSTOMERS, 'vendors=1', 'NONSSL') . '" class="menuBoxContentLink">' . Vendors . '</a><br>----------------------<br>' .
                                 
                                   '&#8226; <a href="' . tep_href_link(FILENAME_RETURNS) . '" class="menuBoxContentLink">' . BOX_RETURNS_MAIN . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_RETURNS_REASONS) . '" class="menuBoxContentLink">' . BOX_RETURNS_REASONS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_REFUND_METHODS) . '" class="menuBoxContentLink">' . BOX_HEADING_REFUNDS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_RETURNS_STATUS) . '" class="menuBoxContentLink">' . BOX_RETURNS_STATUS . '</a><br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_RETURNS_TEXT) . '" class="menuBoxContentLink">' . BOX_RETURNS_TEXT . '</a><br>----------------------<br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_BATCH_PRINT) . '" class="menuBoxContentLink">' . BOX_TOOLS_BATCH_CENTER . '</a><br>' . 

 '&#8226; <a href="' . tep_href_link(FILENAME_ORDERLIST, '', 'NONSSL') . '" class="menuBoxContentLink">Weekly Batch</a><br>----------------------<br>' .

 '&#8226; <a href="' . tep_href_link('customer_export.php', '', 'NONSSL') . '" class="menuBoxContentLink">Export Emails</a><br>----------------------<br>' .

 '&#8226; <a href="' . tep_href_link(FILENAME_QBI, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CATALOG_QBI . '</a><br>----------------------<br>' .
 '&#8226; <a href="' . tep_href_link(FILENAME_CUSTOMERS_GROUPS) . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_GROUPS . '</a><br>');
                                   
                                   


  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- customers_eof //-->
