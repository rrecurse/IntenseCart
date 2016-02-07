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

  $heading[] = array('text'  => 'Supply Manager',
                     'link'  => tep_href_link(FILENAME_SUPPLY_REQUEST, 'selected_box=suppliers'));
//                     'link'  => tep_href_link(FILENAME_CUSTOMERS_GROUPS, 'selected_box=suppliers'));

  if ($selected_box == 'suppliers') {
    $contents[] = array('text' => '&#8226; <a href="' . tep_href_link(FILENAME_SUPPLIERS, '', 'NONSSL') . '" class="menuBoxContentSuppliers">' . BOX_SUPPLIERS . '</a><br>----------------------<br>' .
                                   '&#8226; <a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST, '', 'NONSSL') . '" class="menuBoxContentLink">Supply reqests</a><br>' .
                                    '&#8226; <a href="' . tep_href_link(FILENAME_CREATE_SUPPLY_REQUEST, '', 'NONSSL') . '" class="menuBoxContentLink">Create supply reqest</a><br>' .
 '&#8226; <a href="' . tep_href_link(FILENAME_SUPPLY_REQUEST_STATUS) . '" class="menuBoxContentLink">Supply request status</a><br>'.
// '&#8226; <a href="' . tep_href_link(FILENAME_SUPPLIERS, '', 'NONSSL') . '" class="menuBoxContentSuppliers">' . BOX_SUPPLIERS . '</a><br>'
''

 );
                                   
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- customers_eof //-->
