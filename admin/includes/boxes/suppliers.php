<?php
/*
  $Id: links.php,v 1.00 2003/10/02 Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- links //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_CATALOG_SUPPLIERS,
                     'link'  => tep_href_link(FILENAME_SUPPLIERS, 'selected_box=suppliers'));

    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_SUPPLIERS, '', 'NONSSL') . '" class="menuBoxContentSuppliers">' . BOX_SUPPLIERS . '</a>');


  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- links_eof //-->
