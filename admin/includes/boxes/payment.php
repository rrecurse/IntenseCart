<?php
/*
  $Id: payment.php,v 1.21 2003/07/09 01:18:53 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- tools //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_PAYMENT,
                     'link'  => tep_href_link(FILENAME_PAYMENT, 'set=payment&selected_box=payment'));

  if ($selected_box == 'payment') {
    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_PAYMENT, 'set=payment') . '" class="menuBoxContentLink">Gift Certificates</a><br>' .
'<a href="' . tep_href_link(FILENAME_PAYMENT, 'set=payment') . '" class="menuBoxContentLink">Promotional Credits</a><br>----------------------<br>' .
'<a href="' . tep_href_link(FILENAME_PAYMENT, 'set=payment') . '" class="menuBoxContentLink">' . BOX_MODULES_PAYMENT . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_ORDERS_STATUS) . '" class="menuBoxContentLink">Order Status Config</a><br>' .
'<a href="' . tep_href_link(FILENAME_MODULES, 'set=ordertotal', 'NONSSL') . '" class="menuBoxContentLink">Checkout Control</a>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- tools_eof //-->
