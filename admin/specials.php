<?php

	require('includes/application_top.php');

	require(DIR_WS_CLASSES . 'currencies.php');
	$currencies = new currencies();

	$customers_groups_query = tep_db_query("select customers_group_name, customers_group_id from " . TABLE_CUSTOMERS_GROUPS . " order by customers_group_id ");
    
	while ($existing_groups =  tep_db_fetch_array($customers_groups_query)) {
		$input_groups[] = array("id"=>$existing_groups['customers_group_id'], "text"=> $existing_groups['customers_group_name']);
		$all_groups[$existing_groups['customers_group_id']]=$existing_groups['customers_group_name'];
	}

	$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

	if (tep_not_null($action)) {
    
		switch ($action) {
			case 'setflag':
    	    	tep_set_specials_status($HTTP_GET_VARS['id'], $HTTP_GET_VARS['flag']);
	        	tep_redirect(tep_href_link(FILENAME_SPECIALS, (isset($HTTP_GET_VARS['page']) ? 'page=' . $HTTP_GET_VARS['page'] . '&' : '') . 'sID=' . $HTTP_GET_VARS['id'], 'NONSSL'));
			break;
			
			case 'insert':
				$products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
				$products_price = tep_db_prepare_input($HTTP_POST_VARS['products_price']);
				$specials_price = tep_db_prepare_input($HTTP_POST_VARS['specials_price']);
				$day = tep_db_prepare_input($HTTP_POST_VARS['day']);
				$month = tep_db_prepare_input($HTTP_POST_VARS['month']);
				$year = tep_db_prepare_input($HTTP_POST_VARS['year']);
			
				$customers_group=tep_db_prepare_input($HTTP_POST_VARS['customers_group']);
				$price_query = tep_db_query("select customers_group_price from " . TABLE_PRODUCTS_GROUPS. " WHERE products_id = ".(int)$products_id . " AND customers_group_id  = ".(int)$customers_group);

				while ($gprices = tep_db_fetch_array($price_query)) {
					$products_price = $gprices['customers_group_price'];
				}


				if (substr($specials_price, -1) == '%') {
					$new_special_insert_query = tep_db_query("select products_id, products_price from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "' AND products_status = 1");
					$new_special_insert = tep_db_fetch_array($new_special_insert_query);

					$products_price = $new_special_insert['products_price'];
					$specials_price = ($products_price - (($specials_price / 100) * $products_price));
				}

				$expires_date = '';

				if (tep_not_null($day) && tep_not_null($month) && tep_not_null($year)) {
					$expires_date = $year;
					$expires_date .= (strlen($month) == 1) ? '0' . $month : $month;
					$expires_date .= (strlen($day) == 1) ? '0' . $day : $day;
				}

				tep_db_query("INSERT INTO " . TABLE_SPECIALS . " (products_id, specials_new_products_price, specials_date_added, expires_date, status, customers_group_id) values ('" . (int)$products_id . "', '" . tep_db_input($specials_price) . "', now(), '" . tep_db_input($expires_date) . "', '1', ".(int)$customers_group.")");


				tep_redirect(tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page']));
			break;

			case 'update':

				$specials_id = tep_db_prepare_input($HTTP_POST_VARS['specials_id']);
				$products_price = tep_db_prepare_input($HTTP_POST_VARS['products_price']);
				$specials_price = tep_db_prepare_input($HTTP_POST_VARS['specials_price']);
				$day = tep_db_prepare_input($HTTP_POST_VARS['day']);
				$month = tep_db_prepare_input($HTTP_POST_VARS['month']);
				$year = tep_db_prepare_input($HTTP_POST_VARS['year']);

		        if (substr($specials_price, -1) == '%') $specials_price = ($products_price - (($specials_price / 100) * $products_price));

        		$expires_date = '';

		        if (tep_not_null($day) && tep_not_null($month) && tep_not_null($year)) {
        			$expires_date = $year;
					$expires_date .= (strlen($month) == 1) ? '0' . $month : $month;
					$expires_date .= (strlen($day) == 1) ? '0' . $day : $day;
				}

				tep_db_query("UPDATE " . TABLE_SPECIALS . " SET specials_new_products_price = '" . tep_db_input($specials_price) . "', specials_last_modified = NOW(), expires_date = '" . tep_db_input($expires_date) . "' where specials_id = '" . (int)$specials_id . "'");

				tep_redirect(tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $specials_id));
			break;

			case 'deleteconfirm':
				$specials_id = tep_db_prepare_input($HTTP_GET_VARS['sID']);
				tep_db_query("delete from " . TABLE_SPECIALS . " where specials_id = '" . (int)$specials_id . "'");
				tep_redirect(tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page']));
			break;
			}
		}
?>
<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<?php
  if ( ($action == 'new') || ($action == 'edit') ) {
?>
<link rel="stylesheet" type="text/css" href="includes/javascript/calendar.css">
<script language="JavaScript" src="includes/javascript/calendarcode.js"></script>
<?php
  }
?>
</head>
<body style="margin:0; background:transparent;" onload="SetFocus();">
<div id="popupcalendar" class="text"></div>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
   
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr></table>
<?php

	if(($action == 'new') || ($action == 'edit') ) {

		$form_action = 'insert';

		if(($action == 'edit') && isset($HTTP_GET_VARS['sID'])) {

			$form_action = 'update';

			$product_query = tep_db_query("SELECT p.products_id, 
												  p.products_price, 
												  pd.products_name, 
												  s.specials_new_products_price, 
												  s.expires_date, 
												  s.customers_group_id 
										   FROM " . TABLE_PRODUCTS . " p
										   LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
										   LEFT JOIN " . TABLE_SPECIALS . " s ON s.products_id = p.products_id
										   WHERE  pd.language_id = '" . (int)$languages_id . "' 
										   AND s.specials_id = '" . (int)$HTTP_GET_VARS['sID'] . "'
										   AND p.products_status = '1' 
										   AND p.products_price > 0
										  ");

			$product = tep_db_fetch_array($product_query);
	
			$sInfo = new objectInfo($product);

		} else {
		
			$sInfo = new objectInfo(array());

			$specials_array = array();
    
			$specials_query = tep_db_query("SELECT p.products_status, 
												   p.products_id, 
												   s.customers_group_id,products_price 
											FROM " .  TABLE_PRODUCTS . " p
											LEFT JOIN " . TABLE_SPECIALS . " s ON s.products_id = p.products_id
											WHERE p.products_status = '1' 
											AND p.products_price > '0'
											GROUP BY p.products_id
										   ");


			while ($specials = tep_db_fetch_array($specials_query)) {

				$specials_array[] = (int)$specials['products_id'].":".(int)$specials['customers_group_id'];
		    }

			if(isset($HTTP_GET_VARS['sID']) && $sInfo->customers_group_id != '0') {

				$customer_group_price_query = tep_db_query("SELECT customers_group_price 
															FROM " . TABLE_PRODUCTS_GROUPS . " 
															WHERE products_id = '" . $sInfo->products_id . "' 
															AND customers_group_id =  '" . $sInfo->customers_group_id . "'
														   ");

				if ($customer_group_price = tep_db_fetch_array($customer_group_price_query)) {
					$sInfo->products_price = $customer_group_price['customers_group_price'];
				}
			}
		}
?>
		<form name="new_special" <?php echo 'action="' . tep_href_link(FILENAME_SPECIALS, tep_get_all_get_params(array('action', 'info', 'sID')) . 'action=' . $form_action, 'NONSSL') . '"'; ?> method="post">
<?php 
		if ($form_action == 'update') { 
			echo tep_draw_hidden_field('specials_id', $HTTP_GET_VARS['sID']); 
		}
?>
		<table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td>
					<br>
					<table border="0" cellspacing="0" cellpadding="2">
				<tr>
    	        	<td class="main"><?php echo TEXT_SPECIALS_PRODUCT; ?>&nbsp;</td>
	        	    <td class="main"><?php echo (isset($sInfo->products_name)) ? $sInfo->products_name . ' <small>(' . $currencies->format($sInfo->products_price) . ')</small>' : tep_draw_products_pull_down('products_id', 'style="font-size:10px; width:510px;"', $specials_array); echo tep_draw_hidden_field('products_price', (isset($sInfo->products_price) ? $sInfo->products_price : '')); ?></td>
				</tr>
    			<tr>
		            <td class="main"><?php echo TEXT_SPECIALS_GROUPS; ?>&nbsp;</td>
        		    <td class="main">
<?php 
	if (isset($sInfo->customers_group_id)) {
		
		for ($x=0; $x<count($input_groups); $x++) {
			if ($input_groups[$x]['id'] == $sInfo->customers_group_id) {
            	echo $input_groups[$x]['text'];
            }
		} // end for loop
		
	} else {

		echo tep_draw_pull_down_menu('customers_group', $input_groups, (isset($sInfo->customers_group_id)?$sInfo->customers_group_id:''));
	} 
?>
					</td>
				</tr>
				<tr>
					<td class="main"><?php echo TEXT_SPECIALS_SPECIAL_PRICE; ?>&nbsp;</td>
		            <td class="main"><?php echo tep_draw_input_field('specials_price', (isset($sInfo->specials_new_products_price) ? $sInfo->specials_new_products_price : '')); ?></td>
        		</tr>
				<tr>
		            <td class="main"><?php echo TEXT_SPECIALS_EXPIRES_DATE; ?>&nbsp;</td>
        		    <td class="main"><?php echo tep_draw_input_field('month', (isset($sInfo->expires_date) ? substr($sInfo->expires_date, 5, 2) : ''), 'size="2" maxlength="2" class="cal-TextBox"') . tep_draw_input_field('day', (isset($sInfo->expires_date) ? substr($sInfo->expires_date, 8, 2) : ''), 'size="2" maxlength="2" class="cal-TextBox"') . tep_draw_input_field('year', (isset($sInfo->expires_date) ? substr($sInfo->expires_date, 0, 4) : ''), 'size="4" maxlength="4" class="cal-TextBox"'); ?><a class="so-BtnLink" href="javascript:calClick();return false;" onmouseover="calSwapImg('BTN_date', 'img_Date_OVER',true);" onmouseout="calSwapImg('BTN_date', 'img_Date_UP',true);" onclick="calSwapImg('BTN_date', 'img_Date_DOWN');showCalendar('new_special','dteWhen','BTN_date');return false;"><?php echo tep_image(DIR_WS_IMAGES . 'cal_date_up.gif', 'Calendar', '22', '17', 'align="absmiddle" name="BTN_date"'); ?></a></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><br><?php echo TEXT_SPECIALS_PRICE_TIP; ?></td>
            <td class="main" align="right" valign="top"><br><?php echo (($form_action == 'insert') ? tep_image_submit('button_insert.gif', IMAGE_INSERT) : tep_image_submit('button_update.gif', IMAGE_UPDATE)). '&nbsp;&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . (isset($HTTP_GET_VARS['sID']) ? '&sID=' . $HTTP_GET_VARS['sID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
          </tr>
        </table></td>
      </tr></table>
</form>
<?php
  } else {
?>

	<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRODUCTS_PRICE; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
	$all_groups = array();
	$customers_groups_query = tep_db_query("select customers_group_name, customers_group_id from " . TABLE_CUSTOMERS_GROUPS . " order by customers_group_id ");
    while ($existing_groups =  tep_db_fetch_array($customers_groups_query)) {
      $all_groups[$existing_groups['customers_group_id']] = $existing_groups['customers_group_name'];
    }

   $specials_query_raw = "select p.products_id, pd.products_name, p.products_price, s.specials_id, s.customers_group_id, s.specials_new_products_price, s.specials_date_added, s.specials_last_modified, s.expires_date, s.date_status_change, s.status from " . TABLE_PRODUCTS . " p, " . TABLE_SPECIALS . " s, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = s.products_id order by pd.products_name";

   $customers_group_prices_query = tep_db_query("select s.products_id, s.customers_group_id, pg.customers_group_price from " . TABLE_SPECIALS . " s LEFT JOIN " . TABLE_PRODUCTS_GROUPS . " pg using (products_id, customers_group_id) ");

	while ($_customers_group_prices = tep_db_fetch_array($customers_group_prices_query)) {
		$customers_group_prices[] = $_customers_group_prices;
	}



    $specials_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $specials_query_raw, $specials_query_numrows);
    $specials_query = tep_db_query($specials_query_raw);
	while ($specials = tep_db_fetch_array($specials_query)) {

		if ((!isset($HTTP_GET_VARS['sID']) || (isset($HTTP_GET_VARS['sID']) && ($HTTP_GET_VARS['sID'] == $specials['specials_id']))) && !isset($sInfo)) {
        	$products_query = tep_db_query("select products_image from " . TABLE_PRODUCTS . " where products_id = '" . (int)$specials['products_id'] . "'");
	        $products = tep_db_fetch_array($products_query);
    	    $sInfo_array = array_merge($specials, $products);
        	$sInfo = new objectInfo($sInfo_array);
		}

		// # Remove killed products from specials array:
		if(!$sInfo->products_price > '0') { 
			tep_db_query("DELETE FROM " . TABLE_SPECIALS . " WHERE products_id = '". $sInfo->products_id. "'");
		}

	if(isset($sInfo) && is_object($sInfo) && ($specials['specials_id'] == $sInfo->specials_id)) {
        echo '                  <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $sInfo->specials_id . '&action=edit') . '\'">' . "\n";
      } else {
        echo '                  <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $specials['specials_id']) . '\'">' . "\n";
      }
?>
                <td  class="dataTableContent"><?php echo $specials['products_name']; ?></td>
                <!-- BOF Separate Pricing Per Customer -->
                <td  class="dataTableContent" align="right"><span class="oldPrice"><?php echo $currencies->format($specials['products_price']); ?></span> <span class="specialPrice"><?php echo $currencies->format($specials['specials_new_products_price'])." (".$all_groups[$specials['customers_group_id']].")"; ?></span></td>
<!-- EOF Separate Pricing per Customer -->
                <td  class="dataTableContent" align="right">
<?php
      if ($specials['status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_SPECIALS, 'action=setflag&flag=0&id=' . $specials['specials_id'], 'NONSSL') . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link(FILENAME_SPECIALS, 'action=setflag&flag=1&id=' . $specials['specials_id'], 'NONSSL') . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
                <td class="dataTableContent" align="right"><?php if (isset($sInfo) && is_object($sInfo) && ($specials['specials_id'] == $sInfo->specials_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $specials['specials_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
      </tr>
<?php
    }
?>
              <tr>
                <td colspan="4"><table border="0" width="100%" cellpadding="0"cellspacing="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $specials_split->display_count($specials_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_SPECIALS); ?></td>
                    <td class="smallText" align="right"><?php echo $specials_split->display_links($specials_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
<?php
  if (empty($action)) {
?>
                  <tr>
                    <td colspan="2" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&action=new') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>'; ?></td>
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

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_SPECIALS . '</b>');

      $contents = array('form' => tep_draw_form('specials', FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $sInfo->specials_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $sInfo->products_name . '</b>');
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . '&nbsp;<a href="' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $sInfo->specials_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (is_object($sInfo)) {
        $heading[] = array('text' => '<b>' . $sInfo->products_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $sInfo->specials_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_SPECIALS, 'page=' . $HTTP_GET_VARS['page'] . '&sID=' . $sInfo->specials_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_ADDED . ' ' . tep_date_short($sInfo->specials_date_added));
        $contents[] = array('text' => '' . TEXT_INFO_LAST_MODIFIED . ' ' . tep_date_short($sInfo->specials_last_modified));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_info_image($sInfo->products_image, $sInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT));
        $contents[] = array('text' => '<br>' . TEXT_INFO_ORIGINAL_PRICE . ' ' . $currencies->format($sInfo->products_price));
        $contents[] = array('text' => '' . TEXT_INFO_NEW_PRICE . ' ' . $currencies->format($sInfo->specials_new_products_price));
        $contents[] = array('text' => '' . TEXT_INFO_PERCENTAGE . ' ' . number_format(100 - (($sInfo->specials_new_products_price / $sInfo->products_price) * 100)) . '%');

        $contents[] = array('text' => '<br>' . TEXT_INFO_EXPIRES_DATE . ' <b>' . tep_date_short($sInfo->expires_date) . '</b>');
        $contents[] = array('text' => '' . TEXT_INFO_STATUS_CHANGE . ' ' . tep_date_short($sInfo->date_status_change));
      }
      break;
  }
  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
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
