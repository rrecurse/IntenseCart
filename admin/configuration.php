<?php

  require('includes/application_top.php');

  function tep_cfg_upload_file($fid) {
    return '<input type="file" name="configuration_upload['.$fid.']">';
  }

  function tep_cfg_edit_data($key,$val) {
    $row=tep_db_fetch_array(tep_db_query("SELECT IFNULL(ld.configuration_data,cd.configuration_data) AS cdata FROM IXcore.".TABLE_CONFIGURATION_DATA." cd LEFT JOIN ".TABLE_CONFIGURATION_DATA." ld ON ld.configuration_key='".addslashes($key)."' WHERE cd.configuration_key='".addslashes($key)."'"));
    return '<input type="hidden" name="configuration_value" value="'.htmlspecialchars($val).'"><textarea name="configuration_data" rows="16" wrap="off">'.htmlspecialchars($row['cdata']).'</textarea>';
  }

  function cfg_update_upload($cfg) {
    $k=$cfg['configuration_key'];
    if (isset($_FILES['configuration_upload']) && $_FILES['configuration_upload']['error'][$k]==0) {
      if ($fr=fopen($_FILES['configuration_upload']['tmp_name'][$k],'r')) {
        if ($fw=fopen(DIR_FS_DOCUMENT_ROOT.$cfg['configuration_value'],'w')) {
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

//        tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = '" . tep_db_input($configuration_value) . "', last_modified = now() where configuration_id = '" . (int)$cID . "'");
        $core_conf_query=tep_db_query("select * from IXcore." . TABLE_CONFIGURATION . " where configuration_id = '" . (int)$cID . "'");
	if ($core_conf=tep_db_fetch_array($core_conf_query)) {
	  if ($core_conf['update_call']) $core_conf['update_call']($core_conf);
	  else {
	    if (isset($_POST['configuration_data'])) tep_db_query("replace into " . TABLE_CONFIGURATION_DATA . " (configuration_key,configuration_data) values ('" . tep_db_input($core_conf['configuration_key']) . "','" . addslashes($_POST['configuration_data']) . "')");

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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript" src="/admin/js/prototype.js"></script>
	<script type="text/javascript" src="/admin/js/effects.js"></script>
	<script type="text/javascript" src="/admin/js/accordion.js"></script>
	<script type="text/javascript">
			
		//
		//  In my case I want to load them onload, this is how you do it!
		// 
		Event.observe(window, 'load', loadAccordions, false);
	
		//
		//	Set up all accordions
		//
		function loadAccordions() {
						
			var bottomAccordion = new accordion('vertical_container');
			
			
			// Open first one
			bottomAccordion.activate($$('#vertical_container .accordion_toggle')[0]);
		}
		
	</script>
	<style type="text/css" >
		
		/*
			Vertical Accordions
		*/
		
		.accordion_toggle {
			display: block;
			height: 30px;
			width: 530px;
			background: url(images/accordion_toggle.jpg) no-repeat top right #a9d06a;
			padding: 0 10px 0 10px;
			line-height: 30px;
			color: #ffffff;
			font-weight: normal;
			text-decoration: none;
			outline: none;
			font-size: 12px;
			color: #000000;
			border-bottom: 1px solid #cde99f;
			cursor: pointer;
			margin: 0 0 0 0;
		}
		
		.accordion_toggle_active {
			background: url(images/accordion_toggle_active.jpg) no-repeat top right #e0542f;
			color: #ffffff;
			border-bottom: 1px solid #f68263;
		}
		
		.accordion_content {
			background-color: #ffffff;
			color: #444444;
			overflow: hidden;
		}
			
			.accordion_content h2 {
				margin: 15px 0 5px 10px;
				color: #0099FF;
			}
			
			.accordion_content p {
				line-height: 150%;
				padding: 5px 10px 15px 10px;
			}
			
		.vertical_accordion_toggle {
			display: block;
			height: 30px;
			width: 530px;
			background: url(images/accordion_toggle.jpg) no-repeat top right #a9d06a;
			padding: 0 10px 0 10px;
			line-height: 30px;
			color: #ffffff;
			font-weight: normal;
			text-decoration: none;
			outline: none;
			font-size: 12px;
			color: #000000;
			border-bottom: 1px solid #cde99f;
			cursor: pointer;
			margin: 0 0 0 0;
		}

		.vertical_accordion_toggle_active {
			background: url(images/accordion_toggle_active.jpg) no-repeat top right #e0542f;
			color: #ffffff;
			border-bottom: 1px solid #f68263;
		}

		.vertical_accordion_content {
			background-color: #ffffff;
			color: #444444;
			overflow: hidden;
		}

			.vertical_accordion_content h2 {
				margin: 15px 0 5px 10px;
				color: #0099FF;
			}

			.vertical_accordion_content p {
				line-height: 150%;
				padding: 5px 10px 15px 10px;
			}					
					    
    #vertical_nested_container {
      margin: 20px auto 20px auto;
      width: 550px;
    }

	</style>

</head>
<body style="margin:0; background:transparent;" onLoad="SetFocus();">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<!-- <div id="container">
<div id="vertical_container" >

   	<h1 class="accordion_toggle">Changelog</h1>
		<div class="accordion_content">
		xxxxxxxxxxxxxxxxxxx
		</div>
			<h1 class="accordion_toggle">Changelog</h1>
		<div class="accordion_content">
		xxxxxxxxxxxxxxxxxxx
		</div>
</div>
</div> -->
	
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
   $configuration_query = tep_db_query("select c.configuration_id, c.configuration_title, IFNULL(l.configuration_value,c.configuration_value) AS configuration_value, c.use_function, l.last_modified, c.service_type from IXcore." . TABLE_CONFIGURATION . " c LEFT JOIN " . TABLE_CONFIGURATION . " l ON c.configuration_id=l.configuration_id where c.configuration_group_id = '" . (int)$gID . "'".(CORE_PERMISSION?'':" AND (service_type IS NULL OR service_type='".SITE_SERVICE_TYPE."')")." order by c.configuration_title ASC");

/* $configuration_query = tep_db_query("select c.configuration_id, c.configuration_title, IFNULL(l.configuration_value,c.configuration_value) AS configuration_value, c.use_function, l.last_modified, c.service_type from IXcore." . TABLE_CONFIGURATION . " c LEFT JOIN " . TABLE_CONFIGURATION . " l ON c.configuration_id=l.configuration_id where c.configuration_group_id = '" . (int)$gID . "'".(CORE_PERMISSION?'':" AND (service_type IS NULL OR service_type='".SITE_SERVICE_TYPE."')")." order by c.sort_order"); */ 

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
      $cfg_extra_query = tep_db_query("select configuration_key, configuration_description, date_added, use_function, set_function from IXcore." . TABLE_CONFIGURATION . " where configuration_id = '" . (int)$configuration['configuration_id'] . "'");
      $cfg_extra = tep_db_fetch_array($cfg_extra_query);

      $cInfo_array = array_merge($configuration, $cfg_extra);
      $cInfo = new objectInfo($cInfo_array);
    }

    if ( (isset($cInfo) && is_object($cInfo)) && ($configuration['configuration_id'] == $cInfo->configuration_id) ) {
      echo '                  <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $cInfo->configuration_id . '&action=edit') . '\'">' . "\n";
    } else {
      echo '                  <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id']) . '\'">' . "\n";
    }
?>
                <td class="dataTableContent"><?php if (CORE_PERMISSION && $configuration['service_type']) echo '<b>'.$configuration['service_type'].':</b> ' ; echo $configuration['configuration_title']; ?></td>
                <td class="dataTableContent" ><div  style="white-space:normal; width:200px; overflow-x:hidden;"><?=strlen($cfgValue)>40?htmlspecialchars(substr($cfgValue,0,38)).'<acronym title="'.htmlspecialchars($cfgValue).'">...</acronym>':htmlspecialchars($cfgValue); ?></div></td>
                <td class="dataTableContent" align="right"><?php if ( (isset($cInfo) && is_object($cInfo)) && ($configuration['configuration_id'] == $cInfo->configuration_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CONFIGURATION, 'gID=' . $HTTP_GET_VARS['gID'] . '&cID=' . $configuration['configuration_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
            </table></td>
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
