<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('includes/application_top.php');

	global $language;

	include(DIR_WS_LANGUAGES . $language . '/' . 'warehouse_manager.php');

	$action = (isset($_GET['action']) ? $_GET['action'] : '');

	$theDate = date('Y-m-d H:i:s',time());
	
	if(tep_not_null($action)) {
    	switch ($action) {
      		case 'insert':
			case 'save':

			$warehouse_id = (isset($_GET['wID']) ? tep_db_prepare_input($_GET['wID']) : '');
			$warehouse_name = tep_db_prepare_input($_POST['warehouse_name']);
			$warehouse_email_address = tep_db_prepare_input($_POST['warehouse_email_address']);
			$warehouse_address1 = tep_db_prepare_input($_POST['warehouse_address1']);
			$warehouse_address2 = tep_db_prepare_input($_POST['warehouse_address2']);
			$warehouse_address3 = (!empty($_POST['warehouse_address3'])) ? tep_db_prepare_input($_POST['warehouse_address3']) : NULL;
			$warehouse_city = tep_db_prepare_input($_POST['warehouse_city']);
			$warehouse_state = tep_db_prepare_input($_POST['warehouse_state']);
			$warehouse_zip = tep_db_prepare_input($_POST['warehouse_zip']);
			$warehouse_country = tep_db_prepare_input($_POST['warehouse_country']);
			$warehouse_phone = tep_db_prepare_input($_POST['warehouse_phone']);
			$warehouse_url = tep_db_prepare_input($_POST['warehouse_url']);
			$warehouse_shipper_account = tep_db_prepare_input($_POST['warehouse_shipper_account']);
			$warehouse_taxid = tep_db_prepare_input($_POST['warehouse_taxid']);

			if($_POST['warehouse_default'] == 'on') { 
				tep_db_query("UPDATE ". TABLE_PRODUCTS_WAREHOUSE ." SET products_warehouse_default = '0'");
				$warehouse_default = ($_POST['warehouse_default'] == 'on') ? '1' : '0';
				
			}
		
			$sql_data_array = array('products_warehouse_default' => $warehouse_default,
                        	        'products_warehouse_id' => $warehouse_id,
									'products_warehouse_name' => $warehouse_name,
									'products_warehouse_address1' => $warehouse_address1,
									'products_warehouse_address2' => $warehouse_address2,
									'products_warehouse_address3' => $warehouse_address3,
									'products_warehouse_city' => $warehouse_city,
									'products_warehouse_state' => $warehouse_state,
									'products_warehouse_zip' => $warehouse_zip,
									'products_warehouse_country' => $warehouse_country,
									'products_warehouse_phone' => $warehouse_phone,
									'products_warehouse_url' => $warehouse_url,
									'products_warehouse_shipper_account' => $warehouse_shipper_account,
									'products_warehouse_email_address' => $warehouse_email_address,
									'products_warehouse_taxid' => $warehouse_taxid
									);


			if($action == 'insert') {

				$insert_sql_data = array('date_added' => $theDate);

				$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

				tep_db_perform(TABLE_PRODUCTS_WAREHOUSE, $sql_data_array);
		
				$warehouse_id = tep_db_insert_id();
			  
			} elseif ($action == 'save') {

				$update_sql_data = array('last_modified' => 'now()');

				$sql_data_array = array_merge($sql_data_array, $update_sql_data);

				tep_db_perform(TABLE_PRODUCTS_WAREHOUSE, $sql_data_array, 'update', "products_warehouse_id = '" . (int)$warehouse_id . "'");
		  
			}

        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $warehouse_url_array = $_POST['warehouse_url'];

          $sql_data_array = array('warehouse_url' => tep_db_prepare_input($warehouse_url_array[$language_id]));

          if ($action == 'insert') {
            $insert_sql_data = array('warehouse_id' => $warehouse_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_PRODUCTS_WAREHOUSE, $sql_data_array);
          } elseif ($action == 'save') {
            tep_db_perform(TABLE_PRODUCTS_WAREHOUSE, $sql_data_array, 'update', "products_warehouse_id = '" . (int)$warehouse_id . "'");
          }
        }


        tep_redirect(tep_href_link(FILENAME_WAREHOUSE_MANAGER, (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'wID=' . $warehouse_id));
        break;

		case 'deleteconfirm':

    	    $warehouse_id = tep_db_prepare_input($_GET['wID']);

	        tep_db_query("DELETE FROM " . TABLE_PRODUCTS_WAREHOUSE . " WHERE products_warehouse_id = '" . (int)$warehouse_id . "' AND products_warehouse_default != '1'");

        	tep_redirect(tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'].'&wID=' . $warehouse_id));

		break;
    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>

<style type="text/css">
body {min-height:625px}

.tabEven {
  background-color:#F0F5FB !important;
}

.tabOdd {
  background-color:#EBF1F5 !important;
}
</style>
</head>
<body style="margin:0; background:transparent;" onload="SetFocus();">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="3">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
			<td class="dataTableHeadingContent" align="center" width="25"><?php echo TABLE_HEADING_WAREHOUSE_ID; ?></td>
                <td class="dataTableHeadingContent" style="padding:0 10px"><?php echo TABLE_HEADING_WAREHOUSE; ?></td>

                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php

	$warehouse_query_raw = "SELECT * FROM " . TABLE_PRODUCTS_WAREHOUSE . " ORDER BY products_warehouse_id";

	$warehouse_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $warehouse_query_raw, $warehouse_query_numrows);

	$warehouse_query = tep_db_query($warehouse_query_raw);

	while ($warehouse = tep_db_fetch_array($warehouse_query)) {

		if ((!isset($_GET['wID']) || (isset($_GET['wID']) && ($_GET['wID'] == $warehouse['products_warehouse_id']))) && !isset($mInfo) && (substr($action, 0, 3) != 'new')) {
			
			$warehouse_products_query = tep_db_query("SELECT COUNT(0) AS products_count 
													  FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." 
													  WHERE products_warehouse_id = '" . (int)$warehouse['products_warehouse_id'] . "'
													 ");
			$warehouse_products = tep_db_fetch_array($warehouse_products_query);

			$mInfo_array = array_merge($warehouse, $warehouse_products);
			$mInfo = new objectInfo($mInfo_array);
		}

		if(isset($mInfo) && is_object($mInfo) && ($warehouse['products_warehouse_id'] == $mInfo->products_warehouse_id)) {
			echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $warehouse['products_warehouse_id'] . '&action=edit') . '\'">' . "\n";
		} else {
			echo '<tr class="defaultSelected '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $warehouse['products_warehouse_id']) . '\'">' . "\n";
		}
?>
                <td class="dataTableContent" align="center"><?php echo $warehouse['products_warehouse_id']; ?></td>
        		<td class="dataTableContent" style="padding: 5px 10px"><?php echo $warehouse['products_warehouse_name']; ?></td>
                <td class="dataTableContent" align="center" width="10"><?php if (isset($mInfo) && is_object($mInfo) && ($warehouse['products_warehouse_id'] == $mInfo->products_warehouse_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $warehouse['products_warehouse_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $warehouse_split->display_count($warehouse_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_WAREHOUSES); ?></td>
                    <td class="smallText" align="right"><?php echo $warehouse_split->display_links($warehouse_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
<?php
  if (empty($action)) {
?>
              <tr>
                <td align="right" colspan="2" class="smallText"><?php echo '<a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id . '&action=new') . '">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
              </tr>
<?php
  }
?>
            </table></td>
<?php
  $heading = array();
  $contents = array();

//add list of catagories

	$categories_selected = array('id' => '');

	$categories = array(array('id' => '', 'text' => TEXT_NONE));
	$categories = tep_get_category_tree();

//	$listcategories = array('text' => '<br>' . 'Categories:' . '<br>' . tep_draw_mselect_menu('categories[]', $categories, $categories_selected));

  switch ($action) {
    case 'new':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_NEW_warehouse . '</b>');

      $contents = array('form' => tep_draw_form('warehouse', FILENAME_WAREHOUSE_MANAGER, 'action=insert', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_NEW_INTRO);

      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('warehouse_default') . ' ' .TEXT_WAREHOUSE_DEFAULT);
      $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_NAME . '<br>' . tep_draw_input_field('warehouse_name'));
      $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ID . '<br>' . tep_draw_input_field('warehouse_id'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_EMAIL . '<br>' . tep_draw_input_field('warehouse_email_address'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_SHIPPER_ACCOUNT . '<br>' . tep_draw_input_field('warehouse_shipper_account'));
      $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_URL . '<br>' . tep_draw_input_field('warehouse_url'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_PHONE . '<br>' . tep_draw_input_field('warehouse_phone'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS1 . '<br>' . tep_draw_input_field('warehouse_address1'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS2 . '<br>' . tep_draw_input_field('warehouse_address2'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS3 . '<br>' . tep_draw_input_field('warehouse_address3'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_CITY . '<br>' . tep_draw_input_field('warehouse_city'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_STATE . '<br>' . tep_draw_input_field('warehouse_state'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ZIP . '<br>' . tep_draw_input_field('warehouse_zip'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_COUNTRY . '<br>' . tep_draw_input_field('warehouse_country'));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_TAXID . '<br>' . tep_draw_input_field('warehouse_taxid'));	 

      $warehouse_inputs_string = '';
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $warehouse_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('warehouse_url[' . $languages[$i]['id'] . ']');
      }
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $_GET['wID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;


    case 'edit':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_EDIT_WAREHOUSE . '</b>');

      $contents = array('form' => tep_draw_form('warehouse', FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id . '&action=save', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_EDIT_INTRO);
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('warehouse_default', '', $mInfo->products_warehouse_default) . ' ' .TEXT_WAREHOUSE_DEFAULT);
      $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_NAME . '<br>' . tep_draw_input_field('warehouse_name', $mInfo->products_warehouse_name));
    //  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ID . '<br>' . tep_draw_input_field('warehouse_id', $mInfo->products_warehouse_id));

	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_EMAIL . '<br>' . tep_draw_input_field('warehouse_email_address', $mInfo->products_warehouse_email_address));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_SHIPPER_ACCOUNT . '<br>' . tep_draw_input_field('warehouse_shipper_account', $mInfo->products_warehouse_shipper_account));
      $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_URL . '<br>' . tep_draw_input_field('warehouse_url',$mInfo->products_warehouse_url));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_PHONE . '<br>' . tep_draw_input_field('warehouse_phone',$mInfo->products_warehouse_phone));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS1 . '<br>' . tep_draw_input_field('warehouse_address1',$mInfo->products_warehouse_address1));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS2 . '<br>' . tep_draw_input_field('warehouse_address2',$mInfo->products_warehouse_address2));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS3 . '<br>' . tep_draw_input_field('warehouse_address3', $mInfo->products_warehouse_address3));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_CITY . '<br>' . tep_draw_input_field('warehouse_city', $mInfo->products_warehouse_city));	 
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_STATE . '<br>' . tep_draw_input_field('warehouse_state', $mInfo->products_warehouse_state));	 
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ZIP . '<br>' . tep_draw_input_field('warehouse_zip', $mInfo->products_warehouse_zip));	 
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_COUNTRY . '<br>' . tep_draw_input_field('warehouse_country', $mInfo->products_warehouse_country));
	  $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_TAXID . '<br>' . tep_draw_input_field('warehouse_taxid', $mInfo->products_warehouse_taxid));	 


	  $warehouse_inputs_string = '';
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $warehouse_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('warehouse_url[' . $languages[$i]['id'] . ']', $mInfo->products_warehouse_url);
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;


    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_warehouse . '</b>');

      $contents = array('form' => tep_draw_form('warehouse', FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $mInfo->products_warehouse_name . '</b>');
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_image', '', true) . ' ' . TEXT_DELETE_IMAGE);

      if ($mInfo->products_count > 0) {
        $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_products') . ' ' . TEXT_DELETE_PRODUCTS);
        $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $mInfo->products_count));
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;


    default:
      if (isset($mInfo) && is_object($mInfo)) {
        $heading[] = array('text' => '<b>' . $mInfo->products_warehouse_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_WAREHOUSE_MANAGER, 'page=' . $_GET['page'] . '&wID=' . $mInfo->products_warehouse_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');

        if($mInfo->date_added) $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($mInfo->date_added));
        if ($mInfo->last_modified) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($mInfo->last_modified));

        $contents[] = array('text' => '<br>' . TEXT_PRODUCTS . ' ' . $mInfo->products_count);

		if($mInfo->products_warehouse_email_address) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_EMAIL . '<br>' . $mInfo->products_warehouse_email_address);
		if($mInfo->products_warehouse_phone) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_PHONE . '<br>' . $mInfo->products_warehouse_phone);
		if($mInfo->products_warehouse_address1) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS1 . '<br>' . $mInfo->products_warehouse_address1);
		if($mInfo->products_warehouse_address2) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS2 . '<br>' . $mInfo->products_warehouse_address2);
		if($mInfo->products_warehouse_address3) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ADDRESS3 . '<br>' . $mInfo->products_warehouse_address3);
		if($mInfo->products_warehouse_city) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_CITY . '<br>' . $mInfo->products_warehouse_city);
		if($mInfo->products_warehouse_state) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_STATE . '<br>' . $mInfo->products_warehouse_state);
		if($mInfo->products_warehouse_zip) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_ZIP . '<br>' . $mInfo->products_warehouse_zip);
		if($mInfo->products_warehouse_country) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_COUNTRY . '<br>' . $mInfo->products_warehouse_country);
		if($mInfo->products_warehouse_shipper_account) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_SHIPPER_ACCOUNT . '<br>' . $mInfo->products_warehouse_shipper_account);
		if($mInfo->products_warehouse_url) $contents[] = array('text' => '<br>' . TEXT_WAREHOUSE_URL . '<br>' . $mInfo->products_warehouse_url);

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
