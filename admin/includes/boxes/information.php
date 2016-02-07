<?php
/*
  $Id: information.php,v 1.6 2003/02/10 22:31:00 hpdl Exp $

  
  

  Copyright (c) 2003 IntenseCart eCommerce

  
*/
?>
<!-- information //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_INFORMATION,
                     'link'  => tep_href_link(FILENAME_INFORMATION_MANAGER, 'selected_box=information'));

  if ($selected_box == 'information' || $menu_dhtml == true) {
    $contents[] = array('text'  =>
                                   '<a href="' . tep_href_link(FILENAME_INFORMATION_MANAGER) . '" class="menuBoxContentLink">' . BOX_INFORMATION_MANAGER . '</a><br>'<a href="' . tep_href_link(FILENAME_FILE_MANAGER, '', 'NONSSL') . '" class="menuBoxContentLink">Page Builder</a><br>');

  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- information_eof //-->
