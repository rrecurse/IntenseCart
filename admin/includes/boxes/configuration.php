<?php
/*
  $Id: configuration.php,v 1.17 2003/07/09 01:18:53 hpdl Exp $

  
  

  Copyright (c) 2002 IntenseCart eCommerce

  
*/
?>
<!-- configuration //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_CONFIGURATION,
                     'link'  => tep_href_link(FILENAME_CONFIGURATION, 'gID=1&selected_box=configuration'));

// By MegaJim
  $extra_cfg=Array();
  if (USE_EMAIL_NOW=='Enable') $extra_cfg[]=Array(regex=>'/E-Mail/i',text=>'<a href="'.tep_href_link('email_now.php','').'" class="menuBoxContentLink">Edit E-Mail Templates</a><br>');
  $extra_cfg[]=Array(regex=>'/Accounts/i',text=>'<a href="'.tep_href_link(FILENAME_ADMINS,'').'" class="menuBoxContentLink">Manage Admins</a><br>');

  if ($selected_box == 'configuration') {
    $cfg_groups = '';
    $configuration_groups_query = tep_db_query("select configuration_group_id as cgID, configuration_group_title as cgTitle from " . TABLE_CONFIGURATION_GROUP . " where visible = '1' order by sort_order");
    while ($configuration_groups = tep_db_fetch_array($configuration_groups_query)) {
      $cfg_groups .= '<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $configuration_groups['cgID'], 'NONSSL') . '" class="menuBoxContentLink">' . $configuration_groups['cgTitle'] . '</a><br>';
      foreach ($extra_cfg AS $ex) {
        if (!$ex['done'] && preg_match($ex['regex'],$configuration_groups['cgTitle'])) {
          $cfg_groups.=$ex['text'];
          $ex['done']=1;
        }
      }
    }

    $contents[] = array('text'  => $cfg_groups);
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- configuration_eof //-->
