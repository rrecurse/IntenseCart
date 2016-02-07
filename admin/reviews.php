<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	require('includes/application_top.php');

	$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

	if (tep_not_null($action)) {

		$reviews_id = tep_db_prepare_input($_GET['rID']);

		switch ($action) {

			case 'update':

				$reviews_rating = tep_db_prepare_input($_POST['reviews_rating']);
				$reviews_text = tep_db_prepare_input($_POST['reviews_text']);

				tep_db_query("UPDATE " . TABLE_REVIEWS . " SET reviews_rating = '" . tep_db_input($reviews_rating) . "', last_modified = NOW() WHERE reviews_id = '" . (int)$reviews_id . "'");

				tep_db_query("UPDATE " . TABLE_REVIEWS_DESCRIPTION . " SET reviews_text = '" . tep_db_input($reviews_text) . "' WHERE reviews_id = '" . (int)$reviews_id . "'");

				tep_redirect(tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $reviews_id));
				
			break;

			case 'deleteconfirm':

				$reviews_id = tep_db_prepare_input($_GET['rID']);

				tep_db_query("DELETE FROM " . TABLE_REVIEWS . " WHERE reviews_id = '" . (int)$reviews_id . "'");

				tep_db_query("DELETE FROM " . TABLE_REVIEWS_DESCRIPTION . " WHERE reviews_id = '" . (int)$reviews_id . "'");

				tep_redirect(tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page']));
				
			break;
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body style="margin:0; background:transparent;">

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
      </tr>
<?php
  if ($action == 'edit') {
    $rID = tep_db_prepare_input($_GET['rID']);

    $reviews_query = tep_db_query("SELECT r.reviews_id, 
										  r.products_id, 
										  r.customers_name AS customer_alias, 
										  r.date_added, 
										  r.last_modified, 
										  r.reviews_read, 
										  rd.reviews_text, 
										  r.reviews_rating,
										  p.products_image,
										  pd.products_name
									FROM " . TABLE_REVIEWS . " r
									LEFT JOIN " . TABLE_REVIEWS_DESCRIPTION . " rd ON rd.reviews_id = r.reviews_id
									LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = r.products_id
									LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = r.products_id AND pd.language_id = '" . (int)$languages_id . "')
									WHERE r.reviews_id = '" . (int)$rID . "'
									");
    $reviews = tep_db_fetch_array($reviews_query);

    $rInfo = new objectInfo($reviews);
?>
      <tr><?php echo tep_draw_form('review', FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $_GET['rID'] . '&action=preview'); ?>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="main" valign="top"><b><?php echo ENTRY_PRODUCT; ?></b> <?php echo $rInfo->products_name; ?><br><b><?php echo ENTRY_FROM; ?></b> <?php echo $rInfo->customer_alias; ?><br><br><b><?php echo ENTRY_DATE; ?></b> <?php echo tep_date_short($rInfo->date_added); ?></td>
            <td class="main" align="right" valign="top"><?php echo tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $rInfo->products_image, $rInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5"'); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table witdh="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="main" valign="top"><b><?php echo ENTRY_REVIEW; ?></b><br><br><?php echo tep_draw_textarea_field('reviews_text', 'soft', '60', '15', $rInfo->reviews_text); ?></td>
          </tr>
          <tr>
            <td class="smallText" align="right"><?php echo ENTRY_REVIEW_TEXT; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><?php echo ENTRY_RATING; ?></b>&nbsp;<?php echo TEXT_BAD; ?>&nbsp;<?php for ($i=1; $i<=5; $i++) echo tep_draw_radio_field('reviews_rating', $i, '', $rInfo->reviews_rating) . '&nbsp;'; echo TEXT_GOOD; ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td align="right" class="main"><?php echo tep_draw_hidden_field('reviews_id', $rInfo->reviews_id) . tep_draw_hidden_field('products_id', $rInfo->products_id) . tep_draw_hidden_field('customers_name', $rInfo->customer_alias) . tep_draw_hidden_field('products_name', $rInfo->products_name) . tep_draw_hidden_field('products_image', $rInfo->products_image) . tep_draw_hidden_field('date_added', $rInfo->date_added) . tep_image_submit('button_preview.gif', IMAGE_PREVIEW) . ' <a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $HTTP_GET_VARS['rID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </form></tr>
<?php
	} elseif ($action == 'preview') {

		if (tep_not_null($HTTP_POST_VARS)) {

			$rInfo = new objectInfo($HTTP_POST_VARS);

		} else {

			$rID = tep_db_prepare_input($_GET['rID']);

			$reviews_query = tep_db_query("SELECT r.reviews_id, 
												  r.products_id, 
												  r.customers_name AS customer_alias, 
												  r.date_added, 
												  r.last_modified, 
												  r.reviews_read, 
												  rd.reviews_text, 
												  r.reviews_rating,
												  p.products_image,
												  pd.products_name
											FROM " . TABLE_REVIEWS . " r
											LEFT JOIN " . TABLE_REVIEWS_DESCRIPTION . " rd ON rd.reviews_id = r.reviews_id
											LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = r.products_id
											LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = r.products_id AND pd.language_id = '" . (int)$languages_id . "')
											WHERE r.reviews_id = '" . (int)$rID . "'
										   ");

			$reviews = tep_db_fetch_array($reviews_query);

			$rInfo = new objectInfo($reviews);

		}
//var_dump($reviews);
?>
		<tr>
<?php 

		echo tep_draw_form('update', FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $_GET['rID'] . '&action=update', 'post', 'enctype="multipart/form-data"'); 
?>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="main" valign="top"><b><?php echo ENTRY_PRODUCT; ?></b> <?php echo $rInfo->products_name; ?><br><b><?php echo ENTRY_FROM; ?></b> <?php echo $rInfo->customer_alias; ?><br><br><b><?php echo ENTRY_DATE; ?></b> <?php echo tep_date_short($rInfo->date_added); ?></td>
            <td class="main" align="right" valign="top"><?php echo tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . $rInfo->products_image, $rInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'hspace="5" vspace="5"'); ?></td>
          </tr>
        </table>
      </tr>
      <tr>
        <td><table witdh="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top" class="main"><b><?php echo ENTRY_REVIEW; ?></b><br><br><?php echo nl2br(tep_db_output(tep_break_string($rInfo->reviews_text, 15))); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><?php echo ENTRY_RATING; ?></b>&nbsp;<?php echo tep_image(DIR_WS_CATALOG_IMAGES . 'stars_' . $rInfo->reviews_rating . '.gif', sprintf(TEXT_OF_5_STARS, $rInfo->reviews_rating)); ?>&nbsp;<small>[<?php echo sprintf(TEXT_OF_5_STARS, $rInfo->reviews_rating); ?>]</small></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<?php
    if (tep_not_null($HTTP_POST_VARS)) {
// # Re-Post all POST'ed variables
      reset($HTTP_POST_VARS);
      while(list($key, $value) = each($HTTP_POST_VARS)) echo tep_draw_hidden_field($key, $value);
?>
      <tr>
        <td align="right" class="smallText"><?php echo '<a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id . '&action=edit') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a> ' . tep_image_submit('button_update.gif', IMAGE_UPDATE) . ' <a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </form></tr>
<?php
    } else {
      if (isset($_GET['return'])) {
        $back_url = $_GET['return'];
        $back_url_params = '';
      } else {
        $back_url = FILENAME_REVIEWS;
        $back_url_params = 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id;
      }
?>
      <tr>
        <td align="right"><?php echo '<a href="' . tep_href_link($back_url, $back_url_params, 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>
<?php
    }
  } else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TABLE_HEADING_RATING; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_DATE_ADDED; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php

    $reviews_query_raw = "SELECT * FROM " . TABLE_REVIEWS . " ORDER BY date_added DESC";

    $reviews_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $reviews_query_raw, $reviews_query_numrows);

    $reviews_query = tep_db_query($reviews_query_raw);

    while ($reviews = tep_db_fetch_array($reviews_query)) {
//var_export($reviews);
		if ((!isset($_GET['rID']) || (isset($_GET['rID']) && ($_GET['rID'] == $reviews['reviews_id']))) && !isset($rInfo)) {

			$reviews_queri = tep_db_query("SELECT r.reviews_id, 
												  r.products_id, 
												  r.customers_name AS customer_alias,
												  c.customers_firstname,
												  c.customers_lastname,
												  r.date_added, 
												  r.last_modified, 
												  r.reviews_read, 
												  r.reviews_rating,
												  (AVG(r.reviews_rating) / 5 * 100) AS average_rating,
												  LENGTH(rd.reviews_text) AS reviews_text_size,
												  rd.reviews_text, 
												  p.products_image,
												  pd.products_name
											FROM " . TABLE_REVIEWS . " r
											LEFT JOIN " . TABLE_REVIEWS_DESCRIPTION . " rd ON rd.reviews_id = r.reviews_id
											LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = r.products_id
											LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = r.products_id AND pd.language_id = '" . (int)$languages_id . "')
											LEFT JOIN ". TABLE_CUSTOMERS ." c ON c.customers_id = r.customers_id
											WHERE r.products_id = '" . $reviews['products_id'] . "'
											");
	    	$review = tep_db_fetch_array($reviews_queri);
	
    	    $rInfo = new objectInfo($review);

		}

      if (isset($rInfo) && is_object($rInfo) && ($reviews['reviews_id'] == $rInfo->reviews_id) ) {
        echo '<tr id="defaultSelected" class="dataTableRowSelected" onclick="document.location.href=\'' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id . '&action=edit') . '\'" style="cursor:pointer;">' . "\n";
      } else {
        echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'" onclick="document.location.href=\'' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $reviews['reviews_id']) . '\'" style="cursor:pointer;">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $reviews['reviews_id'] . '&action=preview') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . tep_get_products_name($reviews['products_id']); ?></td>
                <td class="dataTableContent" align="right"><?php echo tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . 'stars_' . $reviews['reviews_rating'] . '.gif'); ?></td>
                <td class="dataTableContent" align="right"><?php echo tep_date_short($reviews['date_added']); ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($rInfo)) && ($reviews['reviews_id'] == $rInfo->reviews_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $reviews['reviews_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>
              <tr>
                <td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $reviews_split->display_count($reviews_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_REVIEWS); ?></td>
                    <td class="smallText" align="right"><?php echo $reviews_split->display_links($reviews_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
    $heading = array();
    $contents = array();

    switch ($action) {
      case 'delete':
        $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_REVIEW . '</b>');

        $contents = array('form' => tep_draw_form('reviews', FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id . '&action=deleteconfirm'));
        $contents[] = array('text' => TEXT_INFO_DELETE_REVIEW_INTRO);
        $contents[] = array('text' => '<br><b>' . $rInfo->products_name . '</b>');
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      default:
      if (isset($rInfo) && is_object($rInfo)) {
        $heading[] = array('text' => '<b>' . $rInfo->products_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_REVIEWS, 'page=' . $HTTP_GET_VARS['page'] . '&rID=' . $rInfo->reviews_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_ADDED . ' ' . tep_date_short($rInfo->date_added));
        if (tep_not_null($rInfo->last_modified)) $contents[] = array('text' => TEXT_INFO_LAST_MODIFIED . ' ' . tep_date_short($rInfo->last_modified));
        $contents[] = array('text' => '<br>' . tep_info_image($rInfo->products_image, $rInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT));
        $contents[] = array('text' => '<br>' . TEXT_INFO_REVIEW_AUTHOR . ' ' . $rInfo->customers_firstname. '  '. $rInfo->customers_lastname);
        $contents[] = array('text' => '<br>' . TEXT_INFO_REVIEW_ALIAS . ' ' . $rInfo->customer_alias);
        $contents[] = array('text' => TEXT_INFO_REVIEW_RATING . ' ' . tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG_IMAGES . 'stars_' . $rInfo->reviews_rating . '.gif'));
        $contents[] = array('text' => TEXT_INFO_REVIEW_READ . ' ' . $rInfo->reviews_read);
        $contents[] = array('text' => '<br>' . TEXT_INFO_REVIEW_SIZE . ' ' . $rInfo->reviews_text_size . ' bytes');
        $contents[] = array('text' => '<br>' . TEXT_INFO_PRODUCTS_AVERAGE_RATING . ' ' . number_format($rInfo->average_rating, 2) . '%');
        $contents[] = array('text' => '<br>' . ENTRY_REVIEW . ' ' . $rInfo->reviews_text);

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
