<?php
/*
  $Id: gv_admin.php,v 1.2.2.1 2003/04/18 21:13:51 wilt Exp $

  
  

  Copyright (c) 2002 - 2003 IntenseCart eCommerce

  Gift Voucher System v1.0
  Copyright (c) 2001,2002 Ian C Wilson
  http://www.phesis.org

  
*/
?>
<!-- gv_admin //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_GV_ADMIN,
                     'link'  => tep_href_link(FILENAME_COUPON_ADMIN, 'selected_box=gv_admin'));

  if ($selected_box == 'gv_admin') {
    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_COUPON_ADMIN) . '" class="menuBoxContentLink">' . BOX_COUPON_ADMIN . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_GV_QUEUE) . '" class="menuBoxContentLink">' . BOX_GV_ADMIN_QUEUE . '</a><br>' .
                                   '<a href="' . tep_href_link(FILENAME_GV_MAIL) . '" class="menuBoxContentLink">' . BOX_GV_ADMIN_MAIL . '</a><br>' . 
                                   '<a href="' . tep_href_link(FILENAME_GV_SENT) . '" class="menuBoxContentLink">' . BOX_GV_ADMIN_SENT . '</a>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- gv_admin_eof //-->