<?php

  require('includes/application_top.php');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  $banner_extension = tep_banner_image_extension();

  if (tep_not_null($action)) {
    switch ($action) {
      case 'setflag':
        if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
          tep_set_banner_status($_GET['bID'], $_GET['flag']);

          $messageStack->add_session(SUCCESS_BANNER_STATUS_UPDATED, 'success');
        } else {
          $messageStack->add_session(ERROR_UNKNOWN_STATUS_FLAG, 'error');
        }

        tep_redirect(tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $HTTP_GET_VARS['bID']));
        break;
      case 'insert':
      case 'update':
        if (isset($HTTP_POST_VARS['banners_id'])) $banners_id = tep_db_prepare_input($HTTP_POST_VARS['banners_id']);
        $banners_title = tep_db_prepare_input($HTTP_POST_VARS['banners_title']);
        $banners_url = tep_db_prepare_input($HTTP_POST_VARS['banners_url']);
	if (isset($HTTP_POST_VARS['banners_group_section'])) {
	  $banners_group_section=$HTTP_POST_VARS['banners_group_section'];
	  if (isset($HTTP_POST_VARS['banners_group_'.$banners_group_section.'_id'])) {
	    $banners_group_id=$HTTP_POST_VARS['banners_group_'.$banners_group_section.'_id'];

$cat_query = tep_db_query("SELECT parent_id FROM categories WHERE categories_id = '" . $banners_group_id . "'") or die(mysql_error());
$cat = tep_db_fetch_array($cat_query);

if ($cat['parent_id'] > '0' ) {
 if ($banners_group_id) $banners_group="$banners_group_section:".$cat['parent_id']."_$banners_group_id";
} else {
 if ($banners_group_id) $banners_group="$banners_group_section:$banners_group_id";
}
	  } else $banners_group=$HTTP_POST_VARS['banners_group_'.$banners_group_section];
	}

//        $new_banners_group = tep_db_prepare_input($HTTP_POST_VARS['new_banners_group']);
//        $banners_group = (empty($new_banners_group)) ? tep_db_prepare_input($HTTP_POST_VARS['banners_group']) : $new_banners_group;
        $banners_html_text = tep_db_prepare_input($HTTP_POST_VARS['banners_html_text']);
        $banners_image_local = tep_db_prepare_input($HTTP_POST_VARS['banners_image_local']);
        $banners_image_target = tep_db_prepare_input($HTTP_POST_VARS['banners_image_target']);
        $db_image_location = '';
        $expires_date = tep_db_prepare_input($HTTP_POST_VARS['expires_date']);
        $expires_impressions = tep_db_prepare_input($HTTP_POST_VARS['expires_impressions']);
        $date_scheduled = tep_db_prepare_input($HTTP_POST_VARS['date_scheduled']);

        $banner_error = false;
        if (empty($banners_title)) {
          $messageStack->add(ERROR_BANNER_TITLE_REQUIRED, 'error');
          $banner_error = true;
        }

        if (empty($banners_group)) {
          $messageStack->add(ERROR_BANNER_GROUP_REQUIRED, 'error');
          $banner_error = true;
        }

        if (empty($banners_html_text)) {
			if (empty($banners_image_local)) {

				$banners_image = new upload('banners_image', DIR_FS_CATALOG_IMAGES, '777', array('.jpg', '.gif', '.png', '.swf'));

    	        $banners_image->set_destination(DIR_FS_CATALOG_IMAGES . $banners_image_target);
	
   	    	    if($banners_image->parse() == false || $banners_image->save() == false) {
					$banner_error = true;
				}
			}
        }

        if ($banner_error == false) {
          $db_image_location = (tep_not_null($banners_image_local)) ? $banners_image_local : $banners_image_target . $banners_image->filename;
          $sql_data_array = array('banners_title' => $banners_title,
                                  'banners_url' => $banners_url,
                                  'banners_image' => $db_image_location,
                                  'banners_group' => $banners_group,
                                  'banners_html_text' => $banners_html_text,
				  				  'banners_new_window' => (isset($banners_new_window) ? $banners_new_window : 0)
								  );

          if ($action == 'insert') {
            $insert_sql_data = array('date_added' => 'now()',
                                     'status' => '1');

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_BANNERS, $sql_data_array);

            $banners_id = tep_db_insert_id();

            $messageStack->add_session(SUCCESS_BANNER_INSERTED, 'success');
          } elseif ($action == 'update') {
            tep_db_perform(TABLE_BANNERS, $sql_data_array, 'update', "banners_id = '" . (int)$banners_id . "'");

            $messageStack->add_session(SUCCESS_BANNER_UPDATED, 'success');
          }

          if (tep_not_null($expires_date)) {
            list($day, $month, $year) = explode('/', $expires_date);

            $expires_date = $year .
                            ((strlen($month) == 1) ? '0' . $month : $month) .
                            ((strlen($day) == 1) ? '0' . $day : $day);

            tep_db_query("update " . TABLE_BANNERS . " set expires_date = '" . tep_db_input($expires_date) . "', expires_impressions = null where banners_id = '" . (int)$banners_id . "'");

          } elseif (tep_not_null($expires_impressions)) {

            tep_db_query("update " . TABLE_BANNERS . " set expires_impressions = '" . tep_db_input($expires_impressions) . "', expires_date = null where banners_id = '" . (int)$banners_id . "'");

          }

          if (tep_not_null($date_scheduled)) {
            list($day, $month, $year) = explode('/', $date_scheduled);

            $date_scheduled = $year .
                              ((strlen($month) == 1) ? '0' . $month : $month) .
                              ((strlen($day) == 1) ? '0' . $day : $day);

            tep_db_query("update " . TABLE_BANNERS . " set status = '0', date_scheduled = '" . tep_db_input($date_scheduled) . "' where banners_id = '" . (int)$banners_id . "'");
          }

          tep_redirect(tep_href_link(FILENAME_BANNER_MANAGER, (isset($HTTP_GET_VARS['page']) ? 'page=' . $HTTP_GET_VARS['page'] . '&' : '') . 'bID=' . $banners_id));
        } else {
          $action = 'new';
        }
        break;
      case 'deleteconfirm':
        $banners_id = tep_db_prepare_input($HTTP_GET_VARS['bID']);

        if (isset($HTTP_POST_VARS['delete_image']) && ($HTTP_POST_VARS['delete_image'] == 'on')) {
          $banner_query = tep_db_query("select banners_image from " . TABLE_BANNERS . " where banners_id = '" . (int)$banners_id . "'");
          $banner = tep_db_fetch_array($banner_query);

          if (is_file(DIR_FS_CATALOG_IMAGES . $banner['banners_image'])) {
            if (is_writeable(DIR_FS_CATALOG_IMAGES . $banner['banners_image'])) {
              unlink(DIR_FS_CATALOG_IMAGES . $banner['banners_image']);
            } else {
              $messageStack->add_session(ERROR_IMAGE_IS_NOT_WRITEABLE, 'error');
            }
          } else {
            $messageStack->add_session(ERROR_IMAGE_DOES_NOT_EXIST, 'error');
          }
        }

        tep_db_query("delete from " . TABLE_BANNERS . " where banners_id = '" . (int)$banners_id . "'");
        tep_db_query("delete from " . TABLE_BANNERS_HISTORY . " where banners_id = '" . (int)$banners_id . "'");

        if (function_exists('imagecreate') && tep_not_null($banner_extensio)) {
          if (is_file(DIR_FS_IMAGES . 'graphs/banner_infobox-' . $banners_id . '.' . $banner_extension)) {
            if (is_writeable(DIR_FS_IMAGES . 'graphs/banner_infobox-' . $banners_id . '.' . $banner_extension)) {
              unlink(DIR_FS_IMAGES . 'graphs/banner_infobox-' . $banners_id . '.' . $banner_extension);
            }
          }

          if (is_file(DIR_FS_IMAGES . 'graphs/banner_yearly-' . $banners_id . '.' . $banner_extension)) {
            if (is_writeable(DIR_FS_IMAGES . 'graphs/banner_yearly-' . $banners_id . '.' . $banner_extension)) {
              unlink(DIR_FS_IMAGES . 'graphs/banner_yearly-' . $banners_id . '.' . $banner_extension);
            }
          }

          if (is_file(DIR_FS_IMAGES . 'graphs/banner_monthly-' . $banners_id . '.' . $banner_extension)) {
            if (is_writeable(DIR_FS_IMAGES . 'graphs/banner_monthly-' . $banners_id . '.' . $banner_extension)) {
              unlink(DIR_FS_IMAGES . 'graphs/banner_monthly-' . $banners_id . '.' . $banner_extension);
            }
          }

          if (is_file(DIR_FS_IMAGES . 'graphs/banner_daily-' . $banners_id . '.' . $banner_extension)) {
            if (is_writeable(DIR_FS_IMAGES . 'graphs/banner_daily-' . $banners_id . '.' . $banner_extension)) {
              unlink(DIR_FS_IMAGES . 'graphs/banner_daily-' . $banners_id . '.' . $banner_extension);
            }
          }
        }

        $messageStack->add_session(SUCCESS_BANNER_REMOVED, 'success');

        tep_redirect(tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page']));
        break;
    }
  }

// check if the graphs directory exists
  $dir_ok = false;
  if (function_exists('imagecreate') && tep_not_null($banner_extension)) {
    if (is_dir(DIR_FS_IMAGES . 'graphs')) {
      if (is_writeable(DIR_FS_IMAGES . 'graphs')) {
        $dir_ok = true;
      } else {
        $messageStack->add(ERROR_GRAPHS_DIRECTORY_NOT_WRITEABLE, 'error');
      }
    } else {
      $messageStack->add(ERROR_GRAPHS_DIRECTORY_DOES_NOT_EXIST, 'error');
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title><?php echo TITLE; ?></title>

<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="includes/javascript/prototype.lite.js"></script>
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>

<script language="javascript">


function popupImageWindow(url) {
	window.open(url,'popupImageWindow','toolbar=no,location=no,directories=no,status=no,titlebar=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=+w+,height=+h+,screenX=150,screenY=150,top=150,left=150');

}

</script>

</head>
<body style="margin:0; background:transparent;">
<div id="spiffycalendar" class="text"></div>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
   
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
<?php
  if ($action == 'new') {
    $form_action = 'insert';

    $parameters = array('expires_date' => '',
                        'date_scheduled' => '',
                        'banners_title' => '',
                        'banners_url' => '',
                        'banners_group' => '',
                        'banners_image' => '',
                        'banners_html_text' => '',
                        'expires_impressions' => '');

    $bInfo = new objectInfo($parameters);

    if (isset($HTTP_GET_VARS['bID'])) {
      $form_action = 'update';

      $bID = tep_db_prepare_input($HTTP_GET_VARS['bID']);

      $banner_query = tep_db_query("select banners_title, banners_url, banners_image, banners_group, banners_html_text, status, date_format(date_scheduled, '%d/%m/%Y') as date_scheduled, date_format(expires_date, '%d/%m/%Y') as expires_date, expires_impressions, date_status_change, banners_new_window from " . TABLE_BANNERS . " where banners_id = '" . (int)$bID . "'");
      $banner = tep_db_fetch_array($banner_query);

      $bInfo->objectInfo($banner);
    } elseif (tep_not_null($HTTP_POST_VARS)) {
      $bInfo->objectInfo($HTTP_POST_VARS);
    }

//    $groups_array = array();
//    $groups_query = tep_db_query("select distinct banners_group from " . TABLE_BANNERS . " order by banners_group");
//    while ($groups = tep_db_fetch_array($groups_query)) {
//      $groups_array[] = array('id' => $groups['banners_group'], 'text' => $groups['banners_group']);
//    }
    if (preg_match('/(\w*?):(.*)/',$banner['banners_group'],$banners_group_split)) {
      $banners_group_section=$banners_group_split[1];
      $banners_group_id=$banners_group_split[2];
    } else {
      $banners_group_section='other';
    }
    $categories_pull_down=tep_get_category_tree();
    $categories_pull_down[0]['text']='--select--';
    $products_pull_down=Array(Array(id=>'',text=>'--select--'));
    $products_query=tep_db_query("SELECT products_id,products_name FROM ".TABLE_PRODUCTS_DESCRIPTION." WHERE language_id='$languages_id' ORDER BY products_name");
    while ($products_row=tep_db_fetch_array($products_query)) $products_pull_down[]=Array(id=>$products_row['products_id'],text=>sprintf("%4d: %s",$products_row['products_id'],$products_row['products_name']));
    $manufacturers_pull_down=Array(Array(id=>'',text=>'--select--'));
    $manufacturers_query=tep_db_query("SELECT manufacturers_id,manufacturers_name FROM ".TABLE_MANUFACTURERS." ORDER BY manufacturers_name");
    while ($manufacturers_row=tep_db_fetch_array($manufacturers_query)) $manufacturers_pull_down[]=Array(id=>$manufacturers_row['manufacturers_id'],text=>sprintf("%4d: %s",$manufacturers_row['manufacturers_id'],$manufacturers_row['manufacturers_name']));
    $info_pull_down=Array(Array(id=>'',text=>'--select--'));
    $info_query=tep_db_query("SELECT information_id,info_title FROM ".TABLE_INFORMATION." ORDER BY info_title");
    while ($info_row=tep_db_fetch_array($info_query)) $info_pull_down[]=Array(id=>$info_row['information_id'],text=>sprintf("%4d: %s",$info_row['information_id'],$info_row['info_title']));
?>
<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script language="javascript">
  var dateExpires = new ctlSpiffyCalendarBox("dateExpires", "new_banner", "expires_date","btnDate1","<?php echo $bInfo->expires_date; ?>",scBTNMODE_CUSTOMBLUE);
  var dateScheduled = new ctlSpiffyCalendarBox("dateScheduled", "new_banner", "date_scheduled","btnDate2","<?php echo $bInfo->date_scheduled; ?>",scBTNMODE_CUSTOMBLUE);
</script>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr><?php echo tep_draw_form('new_banner', FILENAME_BANNER_MANAGER, (isset($HTTP_GET_VARS['page']) ? 'page=' . $HTTP_GET_VARS['page'] . '&' : '') . 'action=' . $form_action, 'post', 'enctype="multipart/form-data"'); if ($form_action == 'update') echo tep_draw_hidden_field('banners_id', $bID); ?>
        <td><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_BANNERS_TITLE; ?></td>
            <td class="main"><?php echo tep_draw_input_field('banners_title', $bInfo->banners_title, '', true); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_BANNERS_URL; ?></td>
            <td class="main"><?php echo tep_draw_input_field('banners_url', $bInfo->banners_url); ?></td>
          </tr>
	  <tr>
	    <td class="main">&nbsp;</td>
	    <td class="main"><?=tep_draw_checkbox_field('banners_new_window',1,$bInfo->banners_new_window)?> Open link in a new window</td>
	  </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_BANNERS_GROUP; ?></td>
            <td class="main"><table>
	    <tr>
	     <td><?=tep_draw_radio_field('banners_group_section','categories',0,$banner_groups_section,' id="group_section_categories"')?>Category Page:</td>
	     <td><?=tep_draw_pull_down_menu('banners_group_categories_id',$categories_pull_down,($banners_group_section=='categories'?$banners_group_id:''),' onChange="if (Number(this.value)) $(\'group_section_categories\').checked=true"')?>
	    </tr>
	    <tr>
	     <td><?=tep_draw_radio_field('banners_group_section','products',0,$banners_group_section,' id="group_section_products"')?>Product Page:</td>
	     <td><?=tep_draw_pull_down_menu('banners_group_products_id',$products_pull_down,($banners_group_section=='products'?$banners_group_id:''),' onChange="if (Number(this.value)) $(\'group_section_products\').checked=true"')?>
	    </tr>
	    <tr>
	     <td><?=tep_draw_radio_field('banners_group_section','manufacturers',0,$banners_group_section,' id="group_section_manufacturers"')?>Manufacturer Page:</td>
	     <td><?=tep_draw_pull_down_menu('banners_group_manufacturers_id',$manufacturers_pull_down,($banners_group_section=='manufacturers'?$banners_group_id:''),' onChange="if (Number(this.value)) $(\'group_section_manufacturers\').checked=true"')?>
	    </tr>
	    <tr>
	     <td><?=tep_draw_radio_field('banners_group_section','info',0,$banners_group_section,' id="group_section_info"')?>Info Page:</td>
	     <td><?=tep_draw_pull_down_menu('banners_group_info_id',$info_pull_down,($banners_group_section=='info'?$banners_group_id:''),' onChange="if (Number(this.value)) $(\'group_section_info\').checked=true"')?>
	    </tr>
	    <tr>
	     <td><?=tep_draw_radio_field('banners_group_section','other',0,$banners_group_section,' id="group_section_other"')?>Other:</td>
	     <td><?=tep_draw_input_field('banners_group_other',($banners_group_section=='other'?$banner['banners_group']:''),' onChange="if (this.value!=\'\') $(\'group_section_other\').checked=true"')?>
	    </tr>
	    
	    </table>
	    <!--?php echo tep_draw_pull_down_menu('banners_group', $groups_array, $bInfo->banners_group) . TEXT_BANNERS_NEW_GROUP . '<br>' . tep_draw_input_field('new_banners_group', '', '', ((sizeof($groups_array) > 0) ? false : true)); ?-->
	    </td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_BANNERS_IMAGE; ?></td>
            <td class="main"><?php echo tep_draw_file_field('banners_image') . ' ' . TEXT_BANNERS_IMAGE_LOCAL . '<br>' . DIR_FS_CATALOG_IMAGES . tep_draw_input_field('banners_image_local', (isset($bInfo->banners_image) ? $bInfo->banners_image : '')); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_BANNERS_IMAGE_TARGET; ?></td>
            <td class="main"><?php echo DIR_FS_CATALOG_IMAGES . tep_draw_input_field('banners_image_target'); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td valign="top" class="main"><?php echo TEXT_BANNERS_HTML_TEXT; ?></td>
            <td class="main"><?php echo tep_draw_textarea_field('banners_html_text', 'soft', '60', '5', $bInfo->banners_html_text); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_BANNERS_SCHEDULED_AT; ?><br><small>(dd/mm/yyyy)</small></td>
            <td valign="top" class="main"><script language="javascript">dateScheduled.writeControl(); dateScheduled.dateFormat="dd/MM/yyyy";</script></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td valign="top" class="main"><?php echo TEXT_BANNERS_EXPIRES_ON; ?><br><small>(dd/mm/yyyy)</small></td>
            <td class="main"><script language="javascript">dateExpires.writeControl(); dateExpires.dateFormat="dd/MM/yyyy";</script><?php echo TEXT_BANNERS_OR_AT . '<br>' . tep_draw_input_field('expires_impressions', $bInfo->expires_impressions, 'maxlength="7" size="7"') . ' ' . TEXT_BANNERS_IMPRESSIONS; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_BANNERS_BANNER_NOTE . '<br>' . TEXT_BANNERS_INSERT_NOTE . '<br>' . TEXT_BANNERS_EXPIRCY_NOTE . '<br>' . TEXT_BANNERS_SCHEDULE_NOTE; ?></td>
            <td class="main" align="right" valign="top" nowrap><?php echo (($form_action == 'insert') ? tep_image_submit('button_insert.gif', IMAGE_INSERT) : tep_image_submit('button_update.gif', IMAGE_UPDATE)). '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, (isset($HTTP_GET_VARS['page']) ? 'page=' . $HTTP_GET_VARS['page'] . '&' : '') . (isset($HTTP_GET_VARS['bID']) ? 'bID=' . $HTTP_GET_VARS['bID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
          </tr>
        </table></td>
      </form></tr>
<?php
  } else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_BANNERS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_GROUPS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATISTICS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php

	$banners_query_raw = "SELECT banners_id, banners_title, banners_image, banners_group, status, expires_date, expires_impressions, date_status_change, date_scheduled, date_added 
						  FROM " . TABLE_BANNERS . " 
						  ORDER BY banners_title, banners_group
						 ";

	$banners_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $banners_query_raw, $banners_query_numrows);

	$banners_query = tep_db_query($banners_query_raw);

	while ($banners = tep_db_fetch_array($banners_query)) {

		$info_query = tep_db_query("SELECT SUM(banners_shown) AS banners_shown, 
										   SUM(banners_clicked) AS banners_clicked
										   FROM " . TABLE_BANNERS_HISTORY . "
										   WHERE banners_id = '" . (int)$banners['banners_id'] . "'
									");

		$info = tep_db_fetch_array($info_query);

		if((!isset($HTTP_GET_VARS['bID']) || (isset($HTTP_GET_VARS['bID']) && ($HTTP_GET_VARS['bID'] == $banners['banners_id']))) && !isset($bInfo) && (substr($action, 0, 3) != 'new')) {

			$bInfo_array = array_merge($banners, $info);
			$bInfo = new objectInfo($bInfo_array);
		}

		$banners_shown = ($info['banners_shown'] != '') ? $info['banners_shown'] : '0';
		$banners_clicked = ($info['banners_clicked'] != '') ? $info['banners_clicked'] : '0';

		if(isset($bInfo) && is_object($bInfo) && ($banners['banners_id'] == $bInfo->banners_id)) {

        	echo '<tr id="defaultSelected" class="dataTableRowSelected" onclick="document.location.href=\'' . tep_href_link(FILENAME_BANNER_STATISTICS, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $bInfo->banners_id) . '\'">' . "\n";

      } else {

	        echo '<tr class="dataTableRow" onclick="document.location.href=\'' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $banners['banners_id']) . '\'">' . "\n";
      }
?>
            <td class="dataTableContent">
<?php echo '<a href="javascript:popupImageWindow(\'' . FILENAME_POPUP_IMAGE . '?banner=' . $banners['banners_id'] . '\')">' . tep_image(DIR_WS_IMAGES . 'icon_popup.gif', 'View Banner') . '</a>&nbsp;' . $banners['banners_title']; ?></td>
                <td class="dataTableContent" align="right"><?php echo $banners['banners_group']; ?></td>
                <td class="dataTableContent" align="right"><?php echo $banners_shown . ' / ' . $banners_clicked; ?></td>
                <td class="dataTableContent" align="right">
<?php
      if ($banners['status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', 'Active', 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $banners['banners_id'] . '&action=setflag&flag=0') . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', 'Set Inactive', 10, 10) . '</a>';
$mysqldate = date('m/d/Y h:i:s a', time());
$phpdate = strtotime( $mysqldate );
	      tep_db_query("update " . TABLE_BANNERS . " set date_status_change = FROM_UNIXTIME($phpdate) where banners_id = '" . $banners['banners_id'] . "'");

      } else {
        echo '<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $banners['banners_id'] . '&action=setflag&flag=1') . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', 'Set Active', 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', 'Inactive', 10, 10);
      }
?></td>
                <td class="dataTableContent" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_BANNER_STATISTICS, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $banners['banners_id']) . '">' . tep_image(DIR_WS_ICONS . 'statistics.gif', ICON_STATISTICS) . '</a>&nbsp;'; if (isset($bInfo) && is_object($bInfo) && ($banners['banners_id'] == $bInfo->banners_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $banners['banners_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>
              <tr>
                <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $banners_split->display_count($banners_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_BANNERS); ?></td>
                    <td class="smallText" align="right"><?php echo $banners_split->display_links($banners_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
                  <tr>
                    <td align="right" colspan="2"><?php echo '<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'action=new') . '">' . tep_image_button('button_new_banner.gif', IMAGE_NEW_BANNER) . '</a>'; ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();
  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . $bInfo->banners_title . '</b>');

      $contents = array('form' => tep_draw_form('banners', FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $bInfo->banners_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $bInfo->banners_title . '</b>');
      if ($bInfo->banners_image) $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_image', 'on', true) . ' ' . TEXT_INFO_DELETE_IMAGE);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . '&nbsp;<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $_GET['page'] . '&bID=' . $_GET['bID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (is_object($bInfo)) {
        $heading[] = array('text' => '<b>' . $bInfo->banners_title . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $bInfo->banners_id . '&action=new') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_BANNER_MANAGER, 'page=' . $HTTP_GET_VARS['page'] . '&bID=' . $bInfo->banners_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_BANNERS_DATE_ADDED . ' ' . tep_date_short($bInfo->date_added));

        if ( (function_exists('imagecreate')) && ($dir_ok) && ($banner_extension) ) {
          $banner_id = $bInfo->banners_id;
          $days = '3';
          include(DIR_WS_INCLUDES . 'graphs/banner_infobox.php');
          $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image(DIR_WS_IMAGES . 'graphs/banner_infobox-' . $banner_id . '.' . $banner_extension));
        } else {
          include(DIR_WS_FUNCTIONS . 'html_graphs.php');
          $contents[] = array('align' => 'center', 'text' => '<br>' . tep_banner_graph_infoBox($bInfo->banners_id, '3'));
        }

        $contents[] = array('text' => tep_image(DIR_WS_IMAGES . 'graph_hbar_blue.gif', 'Blue', '5', '5') . ' ' . TEXT_BANNERS_BANNER_VIEWS . '<br>' . tep_image(DIR_WS_IMAGES . 'graph_hbar_red.gif', 'Red', '5', '5') . ' ' . TEXT_BANNERS_BANNER_CLICKS);

        if ($bInfo->date_scheduled) $contents[] = array('text' => '<br>' . sprintf(TEXT_BANNERS_SCHEDULED_AT_DATE, tep_date_short($bInfo->date_scheduled)));

        if ($bInfo->expires_date) {
          $contents[] = array('text' => '<br>' . sprintf(TEXT_BANNERS_EXPIRES_AT_DATE, tep_date_short($bInfo->expires_date)));
        } elseif ($bInfo->expires_impressions) {
          $contents[] = array('text' => '<br>' . sprintf(TEXT_BANNERS_EXPIRES_AT_IMPRESSIONS, $bInfo->expires_impressions));
        }

        if ($bInfo->date_status_change) $contents[] = array('text' => '<br>' . sprintf(TEXT_BANNERS_STATUS_CHANGE, tep_date_short($bInfo->date_status_change)));
      }
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
