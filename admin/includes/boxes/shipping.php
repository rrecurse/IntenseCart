<?php
/*
  $Id: shipping.php,v 1.21 2003/07/09 01:18:53 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- tools //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_SHIPPING,
                     'link'  => tep_href_link(FILENAME_SHIPPING, 'set=shipping&selected_box=shipping'));

  if ($selected_box == 'shipping') {
    $contents[] = array('text'  => '&nbsp;<font style="color:#333333;">Carriers</font><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=upsxml&action=edit', 'NONSSL') . '" class="menuBoxContentLink">UPS (XML)</a><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=fedex&action=edit', 'NONSSL') . '" class="menuBoxContentLink">FedEx</a><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=usps&action=edit', 'NONSSL') . '" class="menuBoxContentLink">US Postal Service</a><br>----------------------<br>' .
'&nbsp;<font style="color:#333333;">Shipping Methods</font><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=zones&action=edit', 'NONSSL') . '" class="menuBoxContentLink">Zone Rates</a><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=zipship&action=edit', 'NONSSL') . '" class="menuBoxContentLink">Zipcode Rates</a><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=flat&action=edit', 'NONSSL') . '" class="menuBoxContentLink">Flat Rate</a><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=item&action=edit', 'NONSSL') . '" class="menuBoxContentLink">Per Item</a><br>' .
'&#8226; <a href="' . tep_href_link(FILENAME_SHIPPING, 'set=shipping&module=table&action=edit', 'NONSSL') . '" class="menuBoxContentLink">Table Rate</a><br>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- tools_eof //-->
