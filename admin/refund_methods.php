<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	$methID = tep_db_prepare_input($_GET['methID']);

	$default_method = tep_db_result(tep_db_query("SELECT configuration_value AS `default` FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'DEFAULT_REFUND_METHOD'"),0);

  switch ($_GET['action']) {
    case 'insert':
    case 'save':

      $method_id = (int)$_GET['methID'];

      $languages = tep_get_languages();

      for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {

        $refund_method_name_array = $_POST['refund_method_name'];
        $language_id = $languages[$i]['id'];

        $sql_data_array = array('refund_method_name' => tep_db_prepare_input($refund_method_name_array[$language_id]));

        if ($_GET['action'] == 'insert') {

          if (!empty($method_id)) {
            $next_id_query = tep_db_query("SELECT refund_method_id FROM " . TABLE_REFUND_METHOD . " ORDER BY refund_method_id DESC LIMIT 1");
            $next_id = tep_db_fetch_array($next_id_query);
            $method_id = $next_id['refund_method_id'] + 1;
          }

          $insert_sql_data = array('refund_method_id' => $method_id,
                                   'language_id' => $language_id);

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform(TABLE_REFUND_METHOD, $sql_data_array);

        } elseif ($_GET['action'] == 'save') {
          tep_db_perform(TABLE_REFUND_METHOD, $sql_data_array, 'update', "refund_method_id = '" . tep_db_input($method_id) . "' and language_id = '" . $language_id . "'");
        }

      }

      if ($_POST['default'] == 'on') {
        tep_db_query("UPDATE " . TABLE_CONFIGURATION . " 
					  SET configuration_value = '" . $method_id . "' 
					  WHERE configuration_key = 'DEFAULT_REFUND_METHOD'
					 ");
      }

    	tep_redirect(tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $method_id));

 	break;

	case 'deleteconfirm':

      if ($default_method == $methID) {
        tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = '' where configuration_key = 'DEFAULT_REFUND_METHOD'");
      }

      tep_db_query("delete from " . TABLE_REFUND_METHOD . " where refund_method_id = '" . tep_db_input($methID) . "'");

      tep_redirect(tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page']));

    break;

    case 'delete':

      $methID = (int)$_GET['methID'];
      $remove_status = true;

  }

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php');?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
<td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_ORDERS_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
	$refund_method_query_raw = "SELECT refund_method_id, refund_method_name FROM " . TABLE_REFUND_METHOD . " WHERE language_id = '" . $languages_id . "' ORDER BY refund_method_id";

	$refund_method_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $refund_method_query_raw, $refund_method_numrows);

	$refund_method_query = tep_db_query($refund_method_query_raw);

	while ($refund_method = tep_db_fetch_array($refund_method_query)) {

    	if (((!$_GET['methID']) || ($_GET['methID'] == $refund_method['refund_method_id'])) && (!$oInfo) && (substr($_GET['action'], 0, 3) != 'new')) {
			$oInfo = new objectInfo($refund_method);
	    }

		if((is_object($oInfo)) && ($refund_method['refund_method_id'] == $oInfo->refund_method_id) ) {

			echo '<tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id . '&action=edit') . '\'">' . "\n";

		} else {

			echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $refund_method['refund_method_id']) . '\'">' . "\n";

    	}

	    if ($default_method == $refund_method['refund_method_id']) {
			echo '<td class="dataTableContent"><b>' . $refund_method['refund_method_name'] . ' (' . TEXT_DEFAULT . ')</b></td>' . "\n";
    	} else {
			echo '<td class="dataTableContent">' . $refund_method['refund_method_name'] . '</td>' . "\n";
	    }
?>

		<td class="dataTableContent" align="right">
<?php 
		if ( (is_object($oInfo)) && ($refund_method['refund_method_id'] == $oInfo->refund_method_id) ) { 
			echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); 
		} else { 
			echo '<a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $refund_method['refund_method_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; 
		} 
?>
&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $refund_method_split->display_count($refund_method_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_TICKET_STATUS); ?></td>
                    <td class="smallText" align="right"><?php echo $refund_method_split->display_links($refund_method_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
<?php
  if (substr($_GET['action'], 0, 3) != 'new') {
?>
                  <tr>
                    <td colspan="2" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&action=new') . '">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
                  </tr>
<?php
  }
?>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();
  switch ($_GET['action']) {
    case 'new':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_ORDERS_STATUS . '</b>');

      $contents = array('form' => tep_draw_form('status', FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&action=insert'));
      $contents[] = array('text' => TEXT_INFO_INSERT_INTRO);

      $orders_status_inputs_string = '';
      $languages = tep_get_languages();
      for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
        $orders_status_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('refund_method_name[' . $languages[$i]['id'] . ']');
      }

      $contents[] = array('text' => '<br>' . TEXT_INFO_ORDERS_STATUS_NAME . $orders_status_inputs_string);
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('default') . ' ' . TEXT_SET_DEFAULT);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_insert.gif', IMAGE_INSERT) . ' <a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;

    case 'edit':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_ORDERS_STATUS . '</b>');

      $contents = array('form' => tep_draw_form('status', FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id  . '&action=save'));
      $contents[] = array('text' => TEXT_INFO_EDIT_INTRO);

      $orders_status_inputs_string = '';
      $languages = tep_get_languages();
      for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
        $orders_status_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('refund_method_name[' . $languages[$i]['id'] . ']', tep_get_refund_method_name($oInfo->refund_method_id, $languages[$i]['id']));
      }

		$contents[] = array('text' => '<br>' . TEXT_INFO_ORDERS_STATUS_NAME . $orders_status_inputs_string);

		if(DEFAULT_RETURNS_STATUS_ID != $oInfo->refund_method_id) {
			$contents[] = array('text' => '<br>' . tep_draw_checkbox_field('default') . ' ' . TEXT_SET_DEFAULT);
		}

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_update.gif', IMAGE_UPDATE) . ' <a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
	break;

    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_ORDERS_STATUS . '</b>');

      $contents = array('form' => tep_draw_form('status', FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id  . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $oInfo->refund_method_name . '</b>');
      if ($remove_status) $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;

    default:
      if (is_object($oInfo)) {
        $heading[] = array('text' => '<b>' . $oInfo->refund_method_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_REFUND_METHODS, 'page=' . $HTTP_GET_VARS['page'] . '&methID=' . $oInfo->refund_method_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');

        $orders_status_inputs_string = '';
        $languages = tep_get_languages();
        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
          $orders_status_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_get_refund_method_name($oInfo->refund_method_id, $languages[$i]['id']);
        }

        $contents[] = array('text' => $orders_status_inputs_string);
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
<!-- body_text_eof //-->
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
