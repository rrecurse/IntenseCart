<?php
/*
  $Id: header_tags_controller.php,v 1.00 2003/10/02 Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- header_tags_controller //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_HEADER_TAGS_CONTROLLER,
                     'link'  => tep_href_link(FILENAME_HEADER_TAGS_CONTROLLER, 'selected_box=header tags'));

  if ($selected_box == 'header tags') {
    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_HEADER_TAGS_CONTROLLER, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_HEADER_TAGS_ADD_A_PAGE . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_HEADER_TAGS_ENGLISH, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_HEADER_TAGS_ENGLISH . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_HEADER_TAGS_FILL_TAGS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_HEADER_TAGS_FILL_TAGS . '</a>');
 
                                   
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- header_tags_controller_eof //-->
