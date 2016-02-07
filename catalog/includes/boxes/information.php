          <tr>
            <td>
<?php
  $info_box_contents = array();
  $info_box_contents[] = array('text' => BOX_HEADING_INFORMATION);

  new infoBoxHeading($info_box_contents, false, false);

  $informationString = "";
  $sql=mysql_query('SELECT information_id, languages_id, info_title FROM ' . TABLE_INFORMATION .' WHERE visible=\'1\' and languages_id ='.$languages_id.' ORDER BY v_order')
    or die(mysql_error());
  while($row=mysql_fetch_array($sql)):
        $filename_information = tep_href_link(FILENAME_INFORMATION, 'info_id=' . $row['information_id']);
        $informationString .= '<a href="' . $filename_information . '">' . tep_image(DIR_WS_ICONS . 'information_box_arrow.gif', $row['info_title'], '', '', '')  . $row['info_title'] . '</a><br>';
  endwhile;

  $info_box_contents = array();
  $info_box_contents[] = array('text' => $informationString .
                                          '<a href="' . tep_href_link(FILENAME_SHIPPING) . '">' . tep_image(DIR_WS_ICONS . 'information_box_arrow.gif', BOX_INFORMATION_SHIPPING, '', '', 'align=absbottom')  . BOX_INFORMATION_SHIPPING . '</a><br>' .
                                          '<a href="' . tep_href_link(FILENAME_PRIVACY) . '">' . tep_image(DIR_WS_ICONS . 'information_box_arrow.gif', BOX_INFORMATION_PRIVACY, '', '', 'align=absbottom')  . BOX_INFORMATION_PRIVACY . '</a><br>' .
                                          '<a href="' . tep_href_link(FILENAME_CONDITIONS) . '">' . tep_image(DIR_WS_ICONS . 'information_box_arrow.gif', BOX_INFORMATION_CONDITIONS, '', '', 'align=absbottom')  . BOX_INFORMATION_CONDITIONS . '</a><br>' .
                                          '<a href="' . tep_href_link(FILENAME_RETURNS_TRACK) . '">' . BOX_INFORMATION_RETURNS . '</a><br>' .
                                          '<a href="' . tep_href_link(FILENAME_CONTACT_US) . '">' . tep_image(DIR_WS_ICONS . 'information_box_arrow.gif', BOX_INFORMATION_CONTACT, '', '', 'align=absbottom')  . BOX_INFORMATION_CONTACT . '</a>');

  new infoBox($info_box_contents);
?>
            </td>
          </tr>
