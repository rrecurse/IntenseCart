<?php
/*
  $Id: newsletters & Subscribers.php,v 4.0 2002/03/29 13:04:25 dgw_ Exp $
  
  
  Copyright (c) 2002 IntenseCart eCommerce
  
*/
  require('includes/application_top.php');
  if ($HTTP_GET_VARS['action']) {
    switch ($HTTP_GET_VARS['action']) {
      case 'lock':
      case 'unlock':
        $newsletter_id = tep_db_prepare_input($HTTP_GET_VARS['nID']);
        $status = (($HTTP_GET_VARS['action'] == 'lock') ? '1' : '0');
        tep_db_query("update " . TABLE_SUBSCRIBERS_UPDATE . " set locked = '" . $status . "' where newsletters_id = '" . tep_db_input($newsletter_id) . "'");
        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']));
        break;
      case 'insert':
      case 'update':
        $newsletter_id = tep_db_prepare_input($HTTP_POST_VARS['newsletter_id']);
        $newsletter_module = tep_db_prepare_input($HTTP_POST_VARS['module']);
        $title = tep_db_prepare_input($HTTP_POST_VARS['title']);
        $content = tep_db_prepare_input($HTTP_POST_VARS['content']);
        $newsletter_error = false;
        if (empty($title)) {
          $messageStack->add(ERROR_NEWSLETTER_TITLE, 'error');
          $newsletter_error = true;
        }
        if (empty($module)) {
          $messageStack->add(ERROR_NEWSLETTER_MODULE, 'error');
          $newsletter_error = true;
        }
        if (!$newsletter_error) {
          $sql_data_array = array('title' => $title,
                                  'content' => $content,
                                  'module' => $newsletter_module);

          if ($HTTP_GET_VARS['action'] == 'insert') {
            $sql_data_array['date_added'] = 'now()';
            $sql_data_array['status'] = '0';
            $sql_data_array['locked'] = '0';

            tep_db_perform(TABLE_SUBSCRIBERS_UPDATE, $sql_data_array);
            $newsletter_id = tep_db_insert_id();
          } elseif ($HTTP_GET_VARS['action'] == 'update') {
            tep_db_perform(TABLE_SUBSCRIBERS_UPDATE, $sql_data_array, 'update', 'newsletters_id = \'' . tep_db_input($newsletter_id) . '\'');
          }

          tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $newsletter_id));

        } else {

          $HTTP_GET_VARS['action'] = 'new';

        }
        break;

      case 'deleteconfirm':
        $newsletter_id = tep_db_prepare_input($HTTP_GET_VARS['nID']);
        tep_db_query("delete from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($newsletter_id) . "'");
        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page']));
        break;

      case 'delete':
      case 'new': if (!$HTTP_GET_VARS['nID']) break;
      case 'send':
      case 'confirm_send':
        $newsletter_id = tep_db_prepare_input($HTTP_GET_VARS['nID']);
        $check_query = tep_db_query("select locked from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($newsletter_id) . "'");
        $check = tep_db_fetch_array($check_query);

        if ($check['locked'] < 1) {
          switch ($HTTP_GET_VARS['action']) {
            case 'delete': $error = ERROR_REMOVE_UNLOCKED_NEWSLETTER; break;
            case 'new': $error = ERROR_EDIT_UNLOCKED_NEWSLETTER; break;
            case 'send': $error = ERROR_SEND_UNLOCKED_NEWSLETTER; break;
            case 'confirm_send': $error = ERROR_SEND_UNLOCKED_NEWSLETTER; break;
          }

          $messageStack->add_session($error, 'error');
          tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']));
        }
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

</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<div id="spiffycalendar" class="text"></div>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td valign="top" colspan="2" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
<?php
  if ($HTTP_GET_VARS['action'] == 'new') {
    $form_action = 'insert';
    if ($HTTP_GET_VARS['nID']) {
      $nID = tep_db_prepare_input($HTTP_GET_VARS['nID']);
      $form_action = 'update';
      $newsletter_query = tep_db_query("select title, content, module from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($nID) . "'");
      $newsletter = tep_db_fetch_array($newsletter_query);
      $nInfo = new objectInfo($newsletter);
    } elseif ($HTTP_POST_VARS) {
      $nInfo = new objectInfo($HTTP_POST_VARS);
    } else {
      $nInfo = new objectInfo(array());
    }
    $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
    $directory_array = array();
    if ($dir = dir(DIR_WS_MODULES . 'newsletters/')) {
      while ($file = $dir->read()) {
        if (!is_dir(DIR_WS_MODULES . 'newsletters/' . $file)) {
          if (substr($file, strrpos($file, '.')) == $file_extension) {
            $directory_array[] = $file;
          }
        }
      }
      sort($directory_array);
      $dir->close();
    }
    for ($i=0; $i<sizeof($directory_array); $i++) {
      $modules_array[] = array('id' => substr($directory_array[$i], 0, strrpos($directory_array[$i], '.')), 'text' => substr($directory_array[$i], 0, strrpos($directory_array[$i], '.')));
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr><?php echo tep_draw_form('newsletter', FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&action=' . $form_action); if ($form_action == 'update') echo tep_draw_hidden_field('newsletter_id', $nID); ?>
        <td><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_NEWSLETTER_MODULE; ?></td>
            <td class="main"><?php echo tep_draw_pull_down_menu('module', $modules_array, $nInfo->module); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_NEWSLETTER_TITLE; ?></td>
            <td class="main"><?php echo tep_draw_input_field('title', $nInfo->title, '', true); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_NEWSLETTER_CONTENT; ?></td>
            <td class="main"><?php echo tep_draw_textarea_field('content', 'soft', '100%', '20', $nInfo->content); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr><td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td></tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" align="right"><?php echo (($form_action == 'insert') ? tep_image_submit('button_save.gif', IMAGE_SAVE) : tep_image_submit('button_update.gif', IMAGE_UPDATE)). '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
          </tr>
        </table></td>
      </form></tr>
<?php

  } elseif ($HTTP_GET_VARS['action'] == 'preview') {
    $nID = tep_db_prepare_input($HTTP_GET_VARS['nID']);
    $newsletter_query = tep_db_query("select title, content, module from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($nID) . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);
    $nInfo = new objectInfo($newsletter);
?>

      <tr>
        <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>
      <tr>
        <td><tt><?php echo nl2br($nInfo->content); ?></tt></td>
      </tr>
      <tr>
        <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>

<?php

  } elseif ($HTTP_GET_VARS['action'] == 'send') {
    $nID = tep_db_prepare_input($HTTP_GET_VARS['nID']);
    $newsletter_query = tep_db_query("select title, content, module from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($nID) . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);

 $select_str = "select p.customers_id, p.customers_email_address, p.customers_firstname, p.customers_lastname, p.customers_gender, p.customers_newsletter ";
 $from_str .= "from " . TABLE_CUSTOMERS . " p, " . TABLE_ADDRESS_BOOK . " a  ";
// $where .= "where p.customers_id=a.customers_id and p.customers_newsletter='1' ";
 $where .= "where p.customers_id=a.customers_id ";

 $order_by .= "order by customers_id "; 
 $listing_sql = $select_str . $from_str . $where . $order_by ;

 $listing = tep_db_query($listing_sql);
  $number_of_products = '0';
  if (tep_db_num_rows($listing)) {
    while ($listing_values = tep_db_fetch_array($listing)) {
      $number_of_products++;
$listing_values['customers_firstname'] = ucfirst(strtolower($listing_values['customers_firstname'])) ;
//$listing_values['customers_email_address'] = strtolower($listing_values['customers_email_address']) ;
$listing_values['customers_lastname'] = ucfirst(strtolower(addslashes($listing_values['customers_lastname']))) ;
  $product_info = tep_db_query("select subscribers_email_address from " . TABLE_SUBSCRIBERS . " where subscribers_email_address = '" . $listing_values['customers_email_address'] . "' ");
     if (!tep_db_num_rows($product_info)) {
        tep_db_query("insert into " . TABLE_SUBSCRIBERS . " (subscribers_id,customers_id, subscribers_email_address, subscribers_firstname, subscribers_lastname,  customers_newsletter, subscribers_blacklist, language, subscribers_gender, subscribers_email_type, source_import, entry_date, date_account_created) values ('', '" . $listing_values['customers_id'] . "', '" . $listing_values['customers_email_address'] . "', '" . $listing_values['customers_firstname'] . "', '" . $listing_values['customers_lastname'] . "', '" . $listing_values['customers_newsletter'] . "' , '" . $listing_values['customers_blacklist'] . "', 'English', '" . $listing_values['customers_gender'] . "', 'Text',  'CUSTOMERS',   now(), now() )");
      } else {
        tep_db_query("update " . TABLE_SUBSCRIBERS . " set customers_newsletter = '" . $listing_values['customers_newsletter'] . "',  subscribers_blacklist = '" . $listing_values['customers_blacklist'] . "',  date_account_last_modified = now() where subscribers_email_address = '" . $listing_values['customers_email_address'] . "'");
	}
	}
}
	
?>
      <tr>
        <td><?php echo TEXT_TABLE_UPDATED; ?></td>
      </tr>

<?php

  } elseif ($HTTP_GET_VARS['action'] == 'confirm') {
    $nID = tep_db_prepare_input($HTTP_GET_VARS['nID']);
    $newsletter_query = tep_db_query("select title, content, module from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($nID) . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);
    $nInfo = new objectInfo($newsletter);
    include(DIR_WS_LANGUAGES . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    include(DIR_WS_MODULES . 'newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    $module_name = $nInfo->module;
    $module = new $module_name($nInfo->title, $nInfo->content);
?>
      <tr>
        <td><?php echo $module->confirm(); ?></td>
      </tr>

<?php

  } elseif ($HTTP_GET_VARS['action'] == 'confirm_send') {
    $nID = tep_db_prepare_input($HTTP_GET_VARS['nID']);
    $newsletter_query = tep_db_query("select newsletters_id, title, content, module from " . TABLE_SUBSCRIBERS_UPDATE . " where newsletters_id = '" . tep_db_input($nID) . "'");
    $newsletter = tep_db_fetch_array($newsletter_query);
    $nInfo = new objectInfo($newsletter);
    include(DIR_WS_LANGUAGES . $language . '/modules/newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    include(DIR_WS_MODULES . 'newsletters/' . $nInfo->module . substr($PHP_SELF, strrpos($PHP_SELF, '.')));
    $module_name = $nInfo->module;
    $module = new $module_name($nInfo->title, $nInfo->content);
?>

      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">
       <tr>
            <td class="main" valign="middle"><?php echo tep_image(DIR_WS_IMAGES . 'ani_send_email.gif', IMAGE_ANI_SEND_EMAIL); ?></td>
            <td class="main" valign="middle"><b><?php echo TEXT_PLEASE_WAIT; ?></b></td>
          </tr>
        </table></td>
      </tr>
<?php
  tep_set_time_limit(0);
  flush();
  $module->send($nInfo->newsletters_id);
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><font color="#ff0000"><b><?php echo TEXT_FINISHED_SENDING_EMAILS; ?></b></font></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><?php echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>
<?php
  } else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top">
 	     <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NEWSLETTERS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_SENT; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $newsletters_query_raw = "select newsletters_id, title, action, date_sent, status, locked from " . TABLE_SUBSCRIBERS_UPDATE . " order by set_order";
		
		
    $newsletters_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $newsletters_query_raw, $newsletters_query_numrows);
    $newsletters_query = tep_db_query($newsletters_query_raw);
    while ($newsletters = tep_db_fetch_array($newsletters_query)) {
      if (((!$HTTP_GET_VARS['nID']) || (@$HTTP_GET_VARS['nID'] == $newsletters['newsletters_id'])) && (!$nInfo) && (substr($HTTP_GET_VARS['action'], 0, 3) != 'new')) {
        $nInfo = new objectInfo($newsletters);
      }

     if ( (is_object($nInfo)) && ($newsletters['newsletters_id'] == $nInfo->newsletters_id) ) {
        echo '                  <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $nInfo->newsletters_id . '&action=preview') . '\'">' . "\n";
      } else {
        echo '                  <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $newsletters['newsletters_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $newsletters['newsletters_id'] . '&action=preview') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $newsletters['title']; ?></td>
                <td class="dataTableContent" align="center"><?php if ($newsletters['status'] == '1') { echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK); } else { echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS); } ?></td>
                <td class="dataTableContent" align="center"><?php if ($newsletters['locked'] > 0) { echo tep_image(DIR_WS_ICONS . 'locked.gif', ICON_LOCKED); } else { echo tep_image(DIR_WS_ICONS . 'unlocked.gif', ICON_UNLOCKED); } ?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($nInfo)) && ($newsletters['newsletters_id'] == $nInfo->newsletters_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $newsletters['newsletters_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>
              <tr>
                <td colspan="6"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $newsletters_split->display_count($newsletters_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_NEWSLETTERS); ?></td>
                    <td class="smallText" align="right"><?php echo $newsletters_split->display_links($newsletters_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>

<?php
  $heading = array();
  $contents = array();
  switch ($HTTP_GET_VARS['action']) {
    case 'delete':
      $heading[] = array('text' => '<b>' . $nInfo->title . '</b>');
      $contents = array('form' => tep_draw_form('newsletters', FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $nInfo->newsletters_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $nInfo->title . '</b>');
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $HTTP_GET_VARS['nID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;

    default:

      if (is_object($nInfo)) {

        $heading[] = array('text' => '<b>' . $nInfo->title . '</b>');



        if ($nInfo->locked > 0) {

          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $nInfo->newsletters_id . '&action=send') . '">' . tep_image_button('button_update.gif', IMAGE_SEND) . '</a>');

        } else {

          $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $nInfo->newsletters_id . '&action=preview') . '">' . tep_image_button('button_preview.gif', IMAGE_PREVIEW) . '</a> <a href="' . tep_href_link(FILENAME_NEWSLETTERS_UPDATE, 'page=' . $HTTP_GET_VARS['page'] . '&nID=' . $nInfo->newsletters_id . '&action=lock') . '">' . tep_image_button('button_lock.gif', IMAGE_LOCK) . '</a>');

        }

        $contents[] = array('text' => '<br>' . TEXT_NEWSLETTER_DATE_ADDED . ' ' . tep_date_short($nInfo->date_added));

        if ($nInfo->status == '1') $contents[] = array('text' => TEXT_NEWSLETTER_DATE_SENT . ' ' . tep_date_short($nInfo->date_sent));

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