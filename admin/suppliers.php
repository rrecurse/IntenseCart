<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('includes/application_top.php');

	$action = (isset($_GET['action']) ? $_GET['action'] : '');

	$theDate = date('Y-m-d H:i:s',time());
	
	if(tep_not_null($action)) {
    	switch ($action) {
      		case 'insert':
			case 'save':

			if(isset($_GET['hID'])) $suppliers_id = tep_db_prepare_input($_GET['hID']);
			$suppliers_group_name = tep_db_prepare_input($_POST['suppliers_group_name']);
			$suppliers_percentage = tep_db_prepare_input($_POST['suppliers_percentage']);
			$suppliers_name_login = tep_db_prepare_input($_POST['suppliers_name_login']);
			$suppliers_password = tep_db_prepare_input($_POST['suppliers_password']);
			$suppliers_email_address = tep_db_prepare_input($_POST['suppliers_email_address']);
			$suppliers_address1 = tep_db_prepare_input($_POST['suppliers_address1']);
			$suppliers_address2 = tep_db_prepare_input($_POST['suppliers_address2']);
			$suppliers_address3 = (!empty($_POST['suppliers_address3'])) ? tep_db_prepare_input($_POST['suppliers_address3']) : NULL;
			$suppliers_city = tep_db_prepare_input($_POST['suppliers_city']);
			$suppliers_state = tep_db_prepare_input($_POST['suppliers_state']);
			$suppliers_zip = tep_db_prepare_input($_POST['suppliers_zip']);
			$suppliers_country = tep_db_prepare_input($_POST['suppliers_country']);
			$suppliers_phone = tep_db_prepare_input($_POST['suppliers_phone']);
			$suppliers_url = tep_db_prepare_input($_POST['suppliers_url']);
			$suppliers_shipper_account = tep_db_prepare_input($_POST['suppliers_shipper_account']);
			$suppliers_taxid = tep_db_prepare_input($_POST['suppliers_taxid']);

			if($_POST['suppliers_default'] == 'on') { 
				tep_db_query("UPDATE suppliers SET suppliers_default='0'");
				$suppliers_default = ($_POST['suppliers_default'] == 'on') ? '1' : '0';
				
			}
		
			$sql_data_array = array('suppliers_group_name' => $suppliers_group_name,
                                'suppliers_group_id' => $suppliers_group_id,
								'suppliers_percentage' => $suppliers_percentage,
								'suppliers_email_address' => $suppliers_email_address,
								'suppliers_name' => $suppliers_name,
								'suppliers_address1' => $suppliers_address1,
								'suppliers_address2' => $suppliers_address2,
								'suppliers_address3' => $suppliers_address3,
								'suppliers_city' => $suppliers_city,
								'suppliers_state' => $suppliers_state,
								'suppliers_zip' => $suppliers_zip,
								'suppliers_country' => $suppliers_country,
								'suppliers_phone' => $suppliers_phone,
								'suppliers_url' => $suppliers_url,
								'suppliers_shipper_account' => $suppliers_shipper_account,
								'suppliers_default' => $suppliers_default,
								'suppliers_taxid' => $suppliers_taxid);

			$selected_catids = $_POST['categories'];

			if($action == 'insert') {
				$suppliers_password = md5($suppliers_password);
				$insert_sql_data = array('date_added' => $theDate);
				$insert_sql_data = array('suppliers_password' => $suppliers_password);

				$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

				tep_db_perform(TABLE_SUPPLIERS, $sql_data_array);
		
				$suppliers_id = tep_db_insert_id();
			  
		  // # add categories to suppliers
			if ($selected_catids){
				foreach( $selected_catids as $current_category_id){
					tep_db_query("insert into " . TABLE_CATEGORIES_TO_SUPPLIERS . "(categories_id, suppliers_id) values ('" . $current_category_id . "', '". $suppliers_id ."')");
				}
			}
		  
        } elseif ($action == 'save') {
//error_log(print_r($_POST['suppliers_default'],TRUE));
          $update_sql_data = array('last_modified' => 'now()');

		  if ($suppliers_password != ''){
		  	$suppliers_password = md5($suppliers_password);
		  	$update_sql_data = array('suppliers_password' => $suppliers_password);
		  }
          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_SUPPLIERS, $sql_data_array, 'update', "suppliers_id = '" . (int)$suppliers_id . "'");

			if ($selected_catids){
				foreach( $selected_catids as $current_category_id) {
					$categories_suppliers_query = tep_db_query("select * from " . TABLE_CATEGORIES_TO_SUPPLIERS . " where suppliers_id = '" . (int)$suppliers_id . "'");
					$diff = 'true'; // use to check each $current_category_id different all $categories_suppliers in database

					while ($categories_suppliers = tep_db_fetch_array($categories_suppliers_query) ){
						$all_diff = 'true';
						foreach ($selected_catids as $temp_category_id){
							if ($temp_category_id == $categories_suppliers['categories_id']){
								$all_diff = 'false';
								break;
							}
						}
						
						if ($all_diff == 'true')
							tep_db_query("delete from " . TABLE_CATEGORIES_TO_SUPPLIERS . " where suppliers_id = '" . (int)$suppliers_id . "'");
						
						if ( $categories_suppliers['categories_id'] == $current_category_id ){
							$diff = 'false';
							$all_diff = 'true';
//							break;
						} 
					}
					if ($diff == 'true'){
						tep_db_query("insert into " . TABLE_CATEGORIES_TO_SUPPLIERS . " values ('" . $current_category_id . "', '" . $suppliers_id . "')");
					}
				}
			}
		  
//		  $update_sql_categories_to_suppliers = array('categories_id' => $categories_id, 'suppliers_id' => $suppliers_id);		  
        }

        if ($suppliers_image = new upload('suppliers_image', DIR_FS_CATALOG_IMAGES)) {
          tep_db_query("update " . TABLE_SUPPLIERS . " set suppliers_image = '" . $suppliers_image->filename . "' where suppliers_id = '" . (int)$suppliers_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $suppliers_url_array = $_POST['suppliers_url'];
          $language_id = $languages[$i]['id'];

          $sql_data_array = array('suppliers_url' => tep_db_prepare_input($suppliers_url_array[$language_id]));

          if ($action == 'insert') {
            $insert_sql_data = array('suppliers_id' => $suppliers_id,
                                     'languages_id' => $language_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_SUPPLIERS_INFO, $sql_data_array);
          } elseif ($action == 'save') {
            tep_db_perform(TABLE_SUPPLIERS_INFO, $sql_data_array, 'update', "suppliers_id = '" . (int)$suppliers_id . "' and languages_id = '" . (int)$language_id . "'");
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('manufacturers');
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIERS, (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'hID=' . $suppliers_id));
        break;
      case 'deleteconfirm':
        $suppliers_id = tep_db_prepare_input($_GET['hID']);

        if (isset($_POST['delete_image']) && ($_POST['delete_image'] == 'on')) {
          $manufacturer_query = tep_db_query("select suppliers_image from " . TABLE_SUPPLIERS . " where suppliers_id = '" . (int)$suppliers_id . "'");
          $manufacturer = tep_db_fetch_array($manufacturer_query);

          $image_location = DIR_FS_DOCUMENT_ROOT . DIR_WS_CATALOG_IMAGES . $manufacturer['suppliers_image'];

          if (file_exists($image_location)) @unlink($image_location);
        }

        tep_db_query("delete from " . TABLE_SUPPLIERS . " where suppliers_id = '" . (int)$suppliers_id . "'");
        tep_db_query("delete from " . TABLE_SUPPLIERS_INFO . " where suppliers_id = '" . (int)$suppliers_id . "'");
		tep_db_query("delete from " . TABLE_CATEGORIES_TO_SUPPLIERS . " where suppliers_id = '" . (int)$suppliers_id . "'");

//        if (isset($_POST['delete_products']) && ($_POST['delete_products'] == 'on')) {
//          $products_query = tep_db_query("select products_id from " . TABLE_PRODUCTS . " where suppliers_id = '" . (int)$suppliers_id . "'");
//          while ($products = tep_db_fetch_array($products_query)) {
//            tep_remove_product($products['products_id']);
//          }
//        } else {
//          tep_db_query("update " . TABLE_PRODUCTS . " set suppliers_id = '' where suppliers_id = '" . (int)$suppliers_id . "'");
//        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('manufacturers');
        }

        tep_redirect(tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page']));
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
			<td class="dataTableHeadingContent" align="center" width="25"><?php echo TABLE_HEADING_SUPPLIERS_GROUP_ID; ?></td>
                <td class="dataTableHeadingContent" style="padding:0 10px"><?php echo TABLE_HEADING_SUPPLIERS; ?></td>

                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php

	$manufacturers_query_raw = "SELECT * from " . TABLE_SUPPLIERS . " 
								ORDER BY suppliers_group_name";

	$manufacturers_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $manufacturers_query_raw, $manufacturers_query_numrows);

	$manufacturers_query = tep_db_query($manufacturers_query_raw);

	while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {

		if ((!isset($_GET['hID']) || (isset($_GET['hID']) && ($_GET['hID'] == $manufacturers['suppliers_id']))) && !isset($mInfo) && (substr($action, 0, 3) != 'new')) {
			
			$manufacturer_products_query = tep_db_query("SELECT COUNT(*) AS products_count 
														 FROM suppliers_products_groups 
														 WHERE suppliers_group_id = '" . (int)$manufacturers['suppliers_id'] . "'
														");
			$manufacturer_products = tep_db_fetch_array($manufacturer_products_query);

			$mInfo_array = array_merge($manufacturers, $manufacturer_products);
			$mInfo = new objectInfo($mInfo_array);
		}

		if(isset($mInfo) && is_object($mInfo) && ($manufacturers['suppliers_id'] == $mInfo->suppliers_id)) {
			echo '<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $manufacturers['suppliers_id'] . '&action=edit') . '\'">' . "\n";
		} else {
			echo '<tr class="defaultSelected '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $manufacturers['suppliers_id']) . '\'">' . "\n";
		}
?>
                <td class="dataTableContent" align="center"><?php echo $manufacturers['suppliers_id']; ?></td>
        		<td class="dataTableContent" style="padding: 5px 10px"><?php echo $manufacturers['suppliers_group_name']; ?></td>
                <td class="dataTableContent" align="center" width="10"><?php if (isset($mInfo) && is_object($mInfo) && ($manufacturers['suppliers_id'] == $mInfo->suppliers_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $manufacturers['suppliers_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $manufacturers_split->display_count($manufacturers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS); ?></td>
                    <td class="smallText" align="right"><?php echo $manufacturers_split->display_links($manufacturers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
<?php
  if (empty($action)) {
?>
              <tr>
                <td align="right" colspan="2" class="smallText"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id . '&action=new') . '">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
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
      $heading[] = array('text' => '<b>' . TEXT_HEADING_NEW_SUPPLIERS . '</b>');

      $contents = array('form' => tep_draw_form('manufacturers', FILENAME_SUPPLIERS, 'action=insert', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_NEW_INTRO);

      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('suppliers_default') . ' ' .TEXT_SUPPLIERS_DEFAULT);
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_NAME . '<br>' . tep_draw_input_field('suppliers_group_name'));
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_GROUP_ID . '<br>' . tep_draw_input_field('suppliers_group_id'));
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_IMAGE . '<br>' . tep_draw_file_field('suppliers_image'));
// # Add Commission to Suppliers
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PERCENTAGE  . '<br>' . tep_draw_input_field('suppliers_percentage'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_NAME_LOGIN . '<br>' . tep_draw_input_field('suppliers_name_login'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PASSWORD . '<br>' . tep_draw_password_field('suppliers_password'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_CATEGORIES . '<br>' . tep_draw_mselect_menu('categories[]', $categories, $categories_selected));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_EMAIL . '<br>' . tep_draw_input_field('suppliers_email_address'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_SHIPPER_ACCOUNT . '<br>' . tep_draw_input_field('suppliers_shipper_account'));
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_URL . '<br>' . tep_draw_input_field('suppliers_url'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PHONE . '<br>' . tep_draw_input_field('suppliers_phone'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS1 . '<br>' . tep_draw_input_field('suppliers_address1'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS2 . '<br>' . tep_draw_input_field('suppliers_address2'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS3 . '<br>' . tep_draw_input_field('suppliers_address3'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_CITY . '<br>' . tep_draw_input_field('suppliers_city'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_STATE . '<br>' . tep_draw_input_field('suppliers_state'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ZIP . '<br>' . tep_draw_input_field('suppliers_zip'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_COUNTRY . '<br>' . tep_draw_input_field('suppliers_country'));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_TAXID . '<br>' . tep_draw_input_field('suppliers_taxid'));	 

      $manufacturer_inputs_string = '';
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $manufacturer_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('suppliers_url[' . $languages[$i]['id'] . ']');
      }
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $_GET['hID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'edit':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_EDIT_SUPPLIERS . '</b>');

      $contents = array('form' => tep_draw_form('manufacturers', FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id . '&action=save', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_EDIT_INTRO);
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('suppliers_default', '', $mInfo->suppliers_default) . ' ' .TEXT_SUPPLIERS_DEFAULT);
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_NAME . '<br>' . tep_draw_input_field('suppliers_group_name', $mInfo->suppliers_group_name));
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_GROUP_ID . '<br>' . tep_draw_input_field('suppliers_group_id', $mInfo->suppliers_group_id));
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_IMAGE . '<br>' . tep_draw_file_field('suppliers_image') . '<br>' . $mInfo->suppliers_image);
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PERCENTAGE . '<br>' . tep_draw_input_field('suppliers_percentage', $mInfo->suppliers_percentage));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_NAME_LOGIN . '<br>' . tep_draw_input_field('suppliers_name_login', $mInfo->suppliers_name));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PASSWORD . '(blank to not change) <br>' . tep_draw_password_field('suppliers_password'));
	  
	  if (isset($_GET['hID'])){
			$suppliers_id = tep_db_prepare_input($_GET['hID']);
			$categories_selected_query = tep_db_query("select categories_id, suppliers_id from " . TABLE_CATEGORIES_TO_SUPPLIERS . " where suppliers_id = '" . $suppliers_id . "' and categories_id >= 0");
		
			while ($categories_selected_result = tep_db_fetch_array($categories_selected_query)){
				$categories_selected[] = array('id' => $categories_selected_result['categories_id']);
			}
	  }

	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_CATEGORIES . '<br>' . tep_draw_mselect_menu('categories[]', $categories, $categories_selected));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_EMAIL . '<br>' . tep_draw_input_field('suppliers_email_address', $mInfo->suppliers_email_address));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_SHIPPER_ACCOUNT . '<br>' . tep_draw_input_field('suppliers_shipper_account', $mInfo->suppliers_shipper_account));
      $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_URL . '<br>' . tep_draw_input_field('suppliers_url',$mInfo->suppliers_url));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PHONE . '<br>' . tep_draw_input_field('suppliers_phone',$mInfo->suppliers_phone));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS1 . '<br>' . tep_draw_input_field('suppliers_address1',$mInfo->suppliers_address1));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS2 . '<br>' . tep_draw_input_field('suppliers_address2',$mInfo->suppliers_address2));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS3 . '<br>' . tep_draw_input_field('suppliers_address3', $mInfo->suppliers_address3));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_CITY . '<br>' . tep_draw_input_field('suppliers_city', $mInfo->suppliers_city));	 
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_STATE . '<br>' . tep_draw_input_field('suppliers_state', $mInfo->suppliers_state));	 
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ZIP . '<br>' . tep_draw_input_field('suppliers_zip', $mInfo->suppliers_zip));	 
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_COUNTRY . '<br>' . tep_draw_input_field('suppliers_country', $mInfo->suppliers_country));
	  $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_TAXID . '<br>' . tep_draw_input_field('suppliers_taxid', $mInfo->suppliers_taxid));	 


	  $manufacturer_inputs_string = '';
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $manufacturer_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('suppliers_url[' . $languages[$i]['id'] . ']', $mInfo->suppliers_url);
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_SUPPLIERS . '</b>');

      $contents = array('form' => tep_draw_form('manufacturers', FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $mInfo->suppliers_group_name . '</b>');
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_image', '', true) . ' ' . TEXT_DELETE_IMAGE);

      if ($mInfo->products_count > 0) {
        $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_products') . ' ' . TEXT_DELETE_PRODUCTS);
        $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $mInfo->products_count));
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (isset($mInfo) && is_object($mInfo)) {
        $heading[] = array('text' => '<b>' . $mInfo->suppliers_group_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_SUPPLIERS, 'page=' . $_GET['page'] . '&hID=' . $mInfo->suppliers_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');

        if($mInfo->date_added) $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . tep_date_short($mInfo->date_added));
        if ($mInfo->last_modified) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . tep_date_short($mInfo->last_modified));

        $contents[] = array('text' => '<br>' . tep_info_image($mInfo->suppliers_image, $mInfo->suppliers_group_name));
        $contents[] = array('text' => '<br>' . TEXT_PRODUCTS . ' ' . $mInfo->products_count);

		if($mInfo->suppliers_email_address) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_EMAIL . '<br>' . $mInfo->suppliers_email_address);
		if($mInfo->suppliers_phone) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_PHONE . '<br>' . $mInfo->suppliers_phone);
		if($mInfo->suppliers_address1) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS1 . '<br>' . $mInfo->suppliers_address1);
		if($mInfo->suppliers_address2) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS2 . '<br>' . $mInfo->suppliers_address2);
		if($mInfo->suppliers_address3) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ADDRESS3 . '<br>' . $mInfo->suppliers_address3);
		if($mInfo->suppliers_city) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_CITY . '<br>' . $mInfo->suppliers_city);
		if($mInfo->suppliers_state) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_STATE . '<br>' . $mInfo->suppliers_state);
		if($mInfo->suppliers_zip) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_ZIP . '<br>' . $mInfo->suppliers_zip);
		if($mInfo->suppliers_country) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_COUNTRY . '<br>' . $mInfo->suppliers_country);
		if($mInfo->suppliers_shipper_account) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_SHIPPER_ACCOUNT . '<br>' . $mInfo->suppliers_shipper_account);
		if($mInfo->suppliers_url) $contents[] = array('text' => '<br>' . TEXT_SUPPLIERS_URL . '<br>' . $mInfo->suppliers_url);

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
