<!-- feeders //-->
          <tr>
            <td>
<?php

  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_FEEDERS,
                     'link'  => tep_href_link(FILENAME_FEEDERS, 'selected_box=feeders'));

  if ($selected_box == 'feeders') {
    $contents[] = array('text'  => '<a href="' . tep_href_link(FILENAME_BIZRATE) . '"target=_blank class="menuBoxContentLink">' . BOX_FEEDERS_BIZRATE . '</a><br>' .
    							   '<a href="' . tep_href_link(FILENAME_FROOGLE) . '"target=_blank class="menuBoxContentLink">' . BOX_FEEDERS_FROOGLE . '</a><br>' .
    							   '<a href="' . tep_href_link(FILENAME_YAHOO) . '"target=_blank class="menuBoxContentLink">' . BOX_FEEDERS_YAHOO . '</a>');
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- feeders_eof //-->
