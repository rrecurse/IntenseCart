<?php
/*
  $Id: search.php,v 1.22 2003/02/10 22:31:05 hpdl Exp $

  
  

  

  
*/
?>				<?php
				$info_box_contents = array();
				$info_box_contents[] = array('form' => tep_draw_form('quick_find', tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT, '', 'NONSSL', false), 'get'),
                               'align' => 'left',
                               'text' => '<div style="position:absolute; left:1px; top: -8px; font:bold 13px; white-space: nowrap; z-index:10px;">Product Search</div><div style="position:absolute; top:-9px; left:110px;">' . tep_draw_input_field('keywords', '&nbsp;search here', ' style="width:89px; height:16px; border:1px solid #818181; font: 8pt Tahoma;"') . '</div><div style="position:absolute; top:-8px; left:202px;">' . tep_hide_session_id() . tep_image_submit('go.gif', BOX_HEADING_SEARCH) . '</div><div style="position:absolute; top: -7px; left:235px; white-space:nowrap;"><a href="../advanced_search.php">Advanced Search</a></div>');

new infoBox($info_box_contents);

?>