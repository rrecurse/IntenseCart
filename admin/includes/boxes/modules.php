<?php
/*
  $Id: modules.php,v 1.16 2003/07/09 01:18:53 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- modules //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_MODULES,
                     'link'  => tep_href_link(FILENAME_MODULES, 'set=modules&selected_box=modules'));

  if ($selected_box == 'modules') {
    $contents[] = array('text'  => '----------------------<br>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- modules_eof //-->
