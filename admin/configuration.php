<?php

  require('includes/application_top.php');

	function tep_cfg_upload_file($fid) {
		return '<input type="file" name="configuration_upload['.$fid.']">';
	}

	function tep_cfg_edit_data($key,$val) {

		$config_query = tep_db_query("SELECT IFNULL(ld.configuration_data,cd.configuration_data) AS cdata 
									  FROM IXcore.".TABLE_CONFIGURATION_DATA." cd 
									  LEFT JOIN ".TABLE_CONFIGURATION_DATA." ld ON ld.configuration_key = '".addslashes($key)."' 
									  WHERE cd.configuration_key='".addslashes($key)."'
									 ");
		$config = tep_db_fetch_array($config_query);
		
		return '<input type="hidden" name="configuration_value" value="'.htmlspecialchars($val).'"><textarea name="configuration_data" rows="16" wrap="off">'.htmlspecialchars($config['cdata']).'</textarea>';
	}

	function cfg_update_upload($cfg) {
		$k = $cfg['configuration_key'];

		if(isset($_FILES['configuration_upload']) && $_FILES['configuration_upload']['error'][$k]==0) {
			if($fr = fopen($_FILES['configuration_upload']['tmp_name'][$k],'r')) {

				if($fw=fopen(DIR_FS_DOCUMENT_ROOT.$cfg['configuration_value'],'w')) {
					while (!feof($fr)) fwrite($fw,fread($fr,65536));
					fclose($fw);
				}

				fclose($fr);
			}
		}
	}

	$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

	if (tep_not_null($action)) {
		switch ($action) {

			case 'save':
				$configuration_value = tep_db_prepare_input($_POST['configuration_value']);
				$cID = tep_db_prepare_input($_GET['cID']);

				$core_conf_query = tep_db_query("SELECT * FROM IXcore." . TABLE_CONFIGURATION . " WHERE configuration_id = '" . (int)$cID . "'");
				
				if($core_conf = tep_db_fetch_array($core_conf_query)) {
					if($core_conf['update_call']) $core_conf['update_call']($core_conf);
				else {
					if (isset($_POST['configuration_data'])) {

						tep_db_query("REPLACE INTO " . TABLE_CONFIGURATION_DATA . "
									  SET configuration_key = '" . tep_db_input($core_conf['configuration_key']) . "',
									  configuration_data =  '" . addslashes($_POST['configuration_data']) . "'
									  ");
					}
	
					if (CORE_PERMISSION) {

						tep_db_query("update " . TABLE_CORE_CONFIGURATION . " set configuration_value = '" . tep_db_input($configuration_value) . "', last_modified = now() where configuration_id = '" . (int)$cID . "'");
	
						tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key = '" . $core_conf['configuration_key'] . "'");
				    } else tep_db_query("replace into " . TABLE_CONFIGURATION . " (configuration_id,configuration_key,configuration_value,last_modified) values ('" . (int)$cID . "','" . tep_db_input($core_conf['configuration_key']) . "','" . tep_db_input($configuration_value) . "', now())");
					}

					unlink(FILENAME_CONFIG_CACHE);

				  // # Added 4/5/2015 - config in admin was deleting FILENAME_CONFIG_CACHE and not recreating according to tep_read_config().
				  // # Adding tep_read_config() function seems to have cleared up the issue.
				  tep_read_config();

					tep_redirect(tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cID));
				}
				
			break;
		}
	}

	$gID = (isset($HTTP_GET_VARS['gID'])) ? $HTTP_GET_VARS['gID'] : 1;

	$cfg_group_query = tep_db_query("select configuration_group_title from " . TABLE_CONFIGURATION_GROUP . " where configuration_group_id = '" . (int)$gID . "'");
	$cfg_group = tep_db_fetch_array($cfg_group_query);
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo HEADING_TITLE; ?></title>

	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

	<script src="js/jquery-2.0.3.min.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script type="text/javascript" src="js/prototype.js"></script>
</head>
<body style="margin:0; background:transparent;" onLoad="SetFocus();">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

	
<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo $cfg_group['configuration_group_title']; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CONFIGURATION_TITLE; ?></td>
                <td class="dataTableHeadingContent"><div  style="white-space:normal; width:200px; overflow-x:hidden;"><?php echo TABLE_HEADING_CONFIGURATION_VALUE; ?></div></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
	
	$configuration_query = tep_db_query("SELECT ixc.configuration_id, 
												ixc.configuration_title, 
												IFNULL(c.configuration_value, ixc.configuration_value) AS configuration_value, 
												ixc.use_function, 
												c.last_modified, 
												ixc.service_type 
										FROM IXcore." . TABLE_CONFIGURATION . " ixc 
										LEFT JOIN " . TABLE_CONFIGURATION . " c ON ixc.configuration_id = c.configuration_id 
										WHERE ixc.configuration_group_id = '" . (int)$gID . "'".(CORE_PERMISSION?'':" 
										AND (service_type IS NULL OR service_type = '".SITE_SERVICE_TYPE."')")." 
										ORDER BY ixc.configuration_title ASC
									   ");

	while ($configuration = tep_db_fetch_array($configuration_query)) {
 
		if (tep_not_null($configuration['use_function'])) {
		
			$use_function = $configuration['use_function'];

			if (preg_match('/->/', $use_function)) {
				$class_method = explode('->', $use_function);

				if (!is_object(${$class_method[0]})) {
					include(DIR_WS_CLASSES . $class_method[0] . '.php');
					${$class_method[0]} = new $class_method[0]();
				}

				$cfgValue = tep_call_function($class_method[1], $configuration['configuration_value'], ${$class_method[0]});
			} else {
				$cfgValue = tep_call_function($use_function, $configuration['configuration_value']);
			}
		} else {
			$cfgValue = $configuration['configuration_value'];
		}

		if ((!isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $configuration['configuration_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {

			$cfg_extra_query = tep_db_query("SELECT configuration_key, 
													configuration_description, 
													date_added, 
													use_function, 
													set_function 
											FROM IXcore." . TABLE_CONFIGURATION . " 
											WHERE configuration_id = '" . (int)$configuration['configuration_id'] . "'
											");

			$cfg_extra = tep_db_fetch_array($cfg_extra_query);

			$cInfo_array = array_merge($configuration, $cfg_extra);
			$cInfo = new objectInfo($cInfo_array);
		}

		if ( (isset($cInfo) && is_object($cInfo)) && ($configuration['configuration_id'] == $cInfo->configuration_id) ) {
			
			echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id . '&action=edit') . '\'">' . "\n";
	
		} else {

			echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id']) . '\'">' . "\n";
		}
?>
					<td class="dataTableContent"><?php if (CORE_PERMISSION && $configuration['service_type']) echo '<b>'.$configuration['service_type'].':</b> ' ; echo $configuration['configuration_title']; ?></td>
					<td class="dataTableContent" >
						<div  style="white-space:normal; width:200px; overflow-x:hidden;"><?php echo (strlen($cfgValue) > 40 ? htmlspecialchars(substr($cfgValue,0,38)).'<acronym title="'.htmlspecialchars($cfgValue).'">...</acronym>' : htmlspecialchars($cfgValue) );?>
						</div>
					</td>
					<td class="dataTableContent" align="right">
<?php 
	if((isset($cInfo) && is_object($cInfo)) && ($configuration['configuration_id'] == $cInfo->configuration_id) ) { 
		echo tep_image(DIR_WS_IMAGES . 'icon_arrow-right-blue.png', ''); 
	} else { 
		echo '<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.png', IMAGE_ICON_INFO) . '</a>'; 
	} 
?>
						&nbsp;
					</td>
				</tr>
<?php
  }
?>
			</table>
		</td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'edit':
      $heading[] = array('text' => '<b>' . $cInfo->configuration_title . '</b>');

      if ($cInfo->set_function) {
        eval('$value_field = ' . $cInfo->set_function . '"' . htmlspecialchars($cInfo->configuration_value) . '");');
      } else {
        $value_field = tep_draw_input_field('configuration_value', $cInfo->configuration_value);
      }

      $contents = array('form' => tep_draw_form('configuration', FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id . '&action=save','post',' enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_INFO_EDIT_INTRO);
      $contents[] = array('text' => '<br><b>' . $cInfo->configuration_title . '</b><br>' . $cInfo->configuration_description . '<br>' . $value_field);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_update.gif', IMAGE_UPDATE) . '&nbsp;<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (isset($cInfo) && is_object($cInfo)) {
        $heading[] = array('text' => '<b>' . $cInfo->configuration_title . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a>');
        $contents[] = array('text' => '<br>' . $cInfo->configuration_description);
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_ADDED . ' ' . tep_date_short($cInfo->date_added));
        if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => TEXT_INFO_LAST_MODIFIED . ' ' . tep_date_short($cInfo->last_modified));
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
    </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
