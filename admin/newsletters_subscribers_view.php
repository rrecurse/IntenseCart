<?php

  require('includes/application_top.php');

  if ($HTTP_GET_VARS['action']) {
    switch ($HTTP_GET_VARS['action']) {

      case 'setflag':
        if ( ($HTTP_GET_VARS['flag'] == '0') || ($HTTP_GET_VARS['flag'] == '1') ) {
    		  if (isset($HTTP_GET_VARS['cID'])) {
            tep_set_newsletter_status($HTTP_GET_VARS['cID'], $HTTP_GET_VARS['flag']);
          }
       }

//        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->subscribers_id));
        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'cID=' . $HTTP_GET_VARS['cID']));



        break;
				
      case 'setflag1':
        if ( ($HTTP_GET_VARS['flag1'] == '0') || ($HTTP_GET_VARS['flag1'] == '1') ) {
    		  if ($HTTP_GET_VARS['cID']) {
            tep_set_blacklist_status($HTTP_GET_VARS['cID'], $HTTP_GET_VARS['flag1']);
          }
       }
//        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->subscribers_id));
        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'cID=' . $HTTP_GET_VARS['cID']));

        break;
				


case 'update':
      $subscribers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
      $subscribers_firstname = tep_db_prepare_input($HTTP_POST_VARS['subscribers_firstname']);
      $subscribers_lastname = tep_db_prepare_input($HTTP_POST_VARS['subscribers_lastname']);
      $subscribers_email_address = tep_db_prepare_input($HTTP_POST_VARS['subscribers_email_address']);
      $customers_newsletter = tep_db_prepare_input($HTTP_POST_VARS['customers_newsletter']);
      $subscribers_blacklist = tep_db_prepare_input($HTTP_POST_VARS['subscribers_blacklist']);
      $subscribers_gender = tep_db_prepare_input($HTTP_POST_VARS['subscribers_gender']);

      $sql_data_array = array('subscribers_firstname' => $subscribers_firstname,
                              'subscribers_lastname' => $subscribers_lastname,
                              'subscribers_email_address' => $subscribers_email_address,
                              'customers_newsletter' => $customers_newsletter,
                              'subscribers_blacklist' => $subscribers_blacklist);																																

        if (ACCOUNT_GENDER == 'true') $sql_data_array['subscribers_gender'] = $subscribers_gender;
//        if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($subscribers_dob);

        tep_db_perform(TABLE_SUBSCRIBERS, $sql_data_array, 'update', "subscribers_id = '" . tep_db_input($subscribers_id) . "'");

        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $subscribers_id));
        break;
      case 'deleteconfirm':
        $subscribers_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);

        tep_db_query("delete from " . TABLE_SUBSCRIBERS . " where subscribers_id = '" . tep_db_input($subscribers_id) . "'");

        tep_redirect(tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')))); 
        break;
    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Subscriber Manager</title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<?php
  if ($HTTP_GET_VARS['action'] == 'edit') {
?>
<script type="text/javascript"><!--

function check_form() {
  var error = 0;
  var error_message = "<?php echo JS_ERROR; ?>";

  var subscribers_firstname = document.customers.subscribers_firstname.value;
  var subscribers_lastname = document.customers.subscribers_lastname.value;
 <?php if (ACCOUNT_DOB == 'true') echo 'var customers_dob = document.customers.customers_dob.value;' . "\n"; ?>
  var subscribers_email_address = document.customers.subscribers_email_address.value;  
  
<?php if (ACCOUNT_GENDER == 'true') { ?>
  if (document.customers.subscribers_gender[0].checked || document.customers.subscribers_gender[1].checked) {
  } else {
    error_message = error_message + "<?php echo JS_GENDER; ?>";
    error = 1;
  }
<?php } ?>

  if (subscribers_firstname = "" || subscribers_firstname.length < <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_FIRST_NAME; ?>";
    error = 1;
  }

  if (subscribers_lastname = "" || subscribers_lastname.length < <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_LAST_NAME; ?>";
    error = 1;
  }

<?php if (ACCOUNT_DOB == 'true') { ?>
  if (customers_dob = "" || customers_dob.length < <?php echo ENTRY_DOB_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_DOB; ?>";
    error = 1;
  }
<?php } ?>

  if (subscribers_email_address = "" || subscribers_email_address.length < <?php echo ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>) {
    error_message = error_message + "<?php echo JS_EMAIL_ADDRESS; ?>";
    error = 1;
  }

  if (error == 1) {
    alert(error_message);
    return false;
  } else {
    return true;
  }
}
//--></script>
<?php
  }
?>
</head>
<body style="margin:0; background-color:transparent;" onload="SetFocus();">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if ($HTTP_GET_VARS['action'] == 'edit') {
    $subscribers_query = tep_db_query("select c.subscribers_gender, c.subscribers_firstname, c.subscribers_lastname, c.subscribers_email_address, c.customers_newsletter, c.subscribers_blacklist from " . TABLE_SUBSCRIBERS . " c where c.subscribers_id = '" . $HTTP_GET_VARS['cID'] . "'");
    $subscribers = tep_db_fetch_array($subscribers_query);
    $cInfo = new objectInfo($subscribers);

    $newsletter_array = array(array('id' => '1', 'text' => ENTRY_NEWSLETTER_YES),
                              array('id' => '0', 'text' => ENTRY_NEWSLETTER_NO));
															
    $blacklist_array = array(array('id' => '1', 'text' => ENTRY_BLACKLIST_YES),
                              array('id' => '0', 'text' => ENTRY_BLACKLIST_NO));
															
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr><?php echo tep_draw_form('customers', FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('action')) . 'action=update', 'post', 'onSubmit="return check_form();"') . tep_draw_hidden_field('default_address_id', $cInfo->subscribers_default_address_id); ?>
        <td class="formAreaTitle"><?php echo CATEGORY_PERSONAL; ?></td>
      </tr>
      <tr>
        <td class="formArea"><table border="0" cellspacing="2" cellpadding="2">
<?php
    if (ACCOUNT_GENDER == 'true') {
?>
          <tr>
            <td class="main"><?php echo ENTRY_GENDER; ?></td>
            <td class="main"><?php echo tep_draw_radio_field('subscribers_gender', 'm', false, $cInfo->subscribers_gender) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('subscribers_gender', 'f', false, $cInfo->subscribers_gender) . '&nbsp;&nbsp;' . FEMALE; ?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td class="main"><?php echo ENTRY_FIRST_NAME; ?></td>
            <td class="main"><?php echo tep_draw_input_field('subscribers_firstname', $cInfo->subscribers_firstname, 'maxlength="32"', true); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_LAST_NAME; ?></td>
            <td class="main"><?php echo tep_draw_input_field('subscribers_lastname', $cInfo->subscribers_lastname, 'maxlength="32"', true); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
            <td class="main"><?php echo tep_draw_input_field('subscribers_email_address', $cInfo->subscribers_email_address, 'size=40 maxlength="96"', true); ?></td>
          </tr>
        </table></td>
      </tr>

      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
         
     
      <tr>
        <td class="formAreaTitle"><?php echo CATEGORY_OPTIONS; ?></td>
      </tr>
      <tr>
        <td class="formArea">
				<table border="0" cellspacing="2" cellpadding="2">
          <tr>
            <td class="main"><?php echo ENTRY_NEWSLETTER; ?></td>
            <td class="main"><?php echo tep_draw_pull_down_menu('customers_newsletter', $newsletter_array, $cInfo->customers_newsletter); ?></td>
          </tr>
					          <tr>
            <td class="main"><?php echo ENTRY_BLACKLIST; ?></td>
            <td class="main"><?php echo tep_draw_pull_down_menu('subscribers_blacklist', $blacklist_array, $cInfo->subscribers_blacklist); ?></td>
          </tr>
					
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td align="right" class="main"><?php echo tep_image_submit('button_update.gif', IMAGE_UPDATE) . ' <a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('action'))) .'">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr></form>
<?php
  } else {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr><?php echo tep_draw_form('search', FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, '', 'get'); ?>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="smallText" align="right"><?php echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search'); ?></td>
          </form></tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
          <?php
          switch ($listing) {
              case "id-asc":
              $order = "c.subscribers_id";
              break;
              case "firstname":
              $order = "c.subscribers_firstname";
              break;
              case "firstname-desc":
              $order = "c.subscribers_firstname DESC";
              break;
              case "lastname":
              $order = "c.subscribers_lastname, c.subscribers_firstname";
              break;
              case "lastname-desc":
              $order = "c.subscribers_lastname DESC, c.subscribers_firstname";
              break;
              case "email":              
              $order = "c.subscribers_email_address";
              break;
              case "stat":
              $order = "c.customers_newsletter";
              break;
              case "stat-desc":
              $order = "c.customers_newsletter DESC";
              break;
              case "black":
              $order = "c.subscribers_blacklist";
              break;
              case "black-desc":
              $order = "c.subscribers_blacklist DESC";
              break;
              default:
              $order = "c.subscribers_id DESC";
          }
          ?>
             <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">

                <td class="dataTableHeadingContent">
	                <table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td rowspan="2" align="center" class="dataTableHeadingContent" style="padding-right:5px;"><?php echo TABLE_HEADING_LASTNAME; ?></td>
                    <td valign="bottom" style="padding-bottom:2px;"><a href="<?php echo "$PHP_SELF?listing=lastname"; ?>"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
                  </tr>
                  <tr>
                    <td valign="top" style="padding-bottom:1px;"><a href="<?php echo "$PHP_SELF?listing=lastname-desc"; ?>"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
                  </tr>
                </table>
                </td>

                <td class="dataTableHeadingContent">

<table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td rowspan="2" align="center" class="dataTableHeadingContent" style="padding-right:5px;"><?php echo TABLE_HEADING_FIRSTNAME; ?></td>
                    <td valign="bottom" style="padding-bottom:2px;"><a href="<?php echo "$PHP_SELF?listing=firstname"; ?>"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
                  </tr>
                  <tr>
                    <td valign="top" style="padding-bottom:1px;"><a href="<?php echo "$PHP_SELF?listing=firstname-desc"; ?>"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
                  </tr>
                </table>


	             
                </td>

		<td class="dataTableHeadingContent" align="center">                      
	                &nbsp;<?php echo TABLE_HEADING_EMAIL; ?>&nbsp;
                </td>

		<td class="dataTableHeadingContent" align="center">

<table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td rowspan="2" align="center" class="dataTableHeadingContent" style="padding-right:5px;"><?php echo TABLE_HEADING_ACCOUNT_STATUS; ?></td>
                    <td valign="bottom" style="padding-bottom:2px;"><a href="<?php echo "$PHP_SELF?listing=stat"; ?>"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
                  </tr>
                  <tr>
                    <td valign="top" style="padding-bottom:1px;"><a href="<?php echo "$PHP_SELF?listing=stat-desc"; ?>"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
                  </tr>
                </table>

                </td>

		<td class="dataTableHeadingContent" align="center">

<table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td rowspan="2" align="center" class="dataTableHeadingContent" style="padding-right:5px;"><?php echo TABLE_HEADING_ACCOUNT_BLACKLIST; ?></td>
                    <td valign="bottom" style="padding-bottom:2px;"><a href="<?php echo "$PHP_SELF?listing=black"; ?>"><img src="images/ic_up.gif" alt="Sort Descending" width="6" height="4" border="0"></a></td>
                  </tr>
                  <tr>
                    <td valign="top" style="padding-bottom:1px;"><a href="<?php echo "$PHP_SELF?listing=black-desc"; ?>"><img src="images/ic_down.gif" alt="Sort Ascending" width="6" height="4" border="0"></a></td>
                  </tr>
                </table>

                </td>
								
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $search = '';
    if ( ($HTTP_GET_VARS['search']) && (tep_not_null($HTTP_GET_VARS['search'])) ) {
      $keywords = tep_db_input(tep_db_prepare_input($HTTP_GET_VARS['search']));
      $search = "where c.subscribers_lastname like '%" . $keywords . "%' or c.subscribers_firstname like '%" . $keywords . "%' or c.subscribers_email_address like '%" . $keywords . "'";
    }
    $subscribers_query_raw = "select c.subscribers_id, c.subscribers_lastname, c.subscribers_firstname, c.subscribers_email_address, c.customers_newsletter, c.subscribers_blacklist, c.subscribers_email_address  from " . TABLE_SUBSCRIBERS . " c order by $order";
    $subscribers_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $subscribers_query_raw, $subscribers_query_numrows);
    $subscribers_query = tep_db_query($subscribers_query_raw);
    while ($subscribers = tep_db_fetch_array($subscribers_query)) {
//      $info_query = tep_db_query("select customers_info_date_account_created as date_account_created, customers_info_date_account_last_modified as date_account_last_modified, customers_info_date_of_last_logon as date_last_logon, customers_info_number_of_logons as number_of_logons from " . TABLE_SUBSCRIBERS_INFO . " where customers_info_id = '" . $subscribers['subscribers_id'] . "'");
//      $info = tep_db_fetch_array($info_query);

      if (((!$HTTP_GET_VARS['cID']) || (@$HTTP_GET_VARS['cID'] == $subscribers['subscribers_id'])) && (!$cInfo)) 
      {
        $customer_info = $country;

        $cInfo_array = array_merge($subscribers, (array) $customer_info);
        $cInfo = new objectInfo($cInfo_array);
      }
      if ( isset($cInfo) && is_object($cInfo) && ($subscribers['subscribers_id'] == $cInfo->subscribers_id) ) {
        echo '          <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'cPath=' . $cPath . '&cID=' . $subscribers['subscribers_id'] . '&action=edit')                         . '\'">' . "\n";
      } else {
        echo '        <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'cPath=' . $cPath . '&cID=' . $subscribers['subscribers_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent"><?php echo $subscribers['subscribers_lastname']; ?></td>
                <td class="dataTableContent"><?php echo $subscribers['subscribers_firstname']; ?></td>
                <td class="dataTableContent"><?php echo $subscribers['subscribers_email_address']; ?></td>
                <td class="dataTableContent" align="center">
<?php




      if ($subscribers['customers_newsletter'] == '1') {
tep_db_query("UPDATE customers SET customers_newsletter = '1' WHERE customers_email_address = '".$subscribers['subscribers_email_address']."'");
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'action=setflag&flag=0&cID=' . $subscribers['subscribers_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';


// #################### problème au niveau cpath, valeur nom prise en compte


      } else {
tep_db_query("UPDATE customers SET customers_newsletter = '0' WHERE customers_email_address = '".$subscribers['subscribers_email_address']."'");
        echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'action=setflag&flag=1&cID=  ' . $subscribers['subscribers_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
// #################### problème au niveau cpath, valeur nom prise en compte



?>
	</td>
           <td class="dataTableContent" align="center">
	<?php
      if ($subscribers['subscribers_blacklist'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'action=setflag1&flag1=0&cID=' . $subscribers['subscribers_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, 'action=setflag1&flag1=1&cID=' . $subscribers['subscribers_id'] . '&cPath=' . $cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?>
</td>								
                <td class="dataTableContent" align="right"><?php if ( isset($cInfo) && is_object($cInfo) && ($subscribers['subscribers_id'] == $cInfo->subscribers_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID')) . 'cID=' . $subscribers['subscribers_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    }
?>
              <tr>
                <td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $subscribers_split->display_count($subscribers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_CUSTOMERS); ?></td>
                    <td class="smallText" align="right"><?php echo $subscribers_split->display_links($subscribers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page', 'info', 'x', 'y', 'cID'))); ?></td>
                  </tr>
<?php
    if (tep_not_null($HTTP_GET_VARS['search'])) {
?>
                  <tr>
                    <td align="right" colspan="2"><?php echo '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET) . '</a>'; ?></td>
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
  switch ($HTTP_GET_VARS['action']) {
    case 'confirm':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CUSTOMER . '</b>');

      $contents = array('form' => tep_draw_form('customers', FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->subscribers_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO . '<br><br><b>' . $cInfo->subscribers_firstname . ' ' . $cInfo->subscribers_lastname . '</b>');
      if ($cInfo->number_of_reviews > 0) $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_reviews', 'on', true) . ' ' . sprintf(TEXT_DELETE_REVIEWS, $cInfo->number_of_reviews));
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->subscribers_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (is_object($cInfo)) {
        $heading[] = array('text' => '<b>' . $cInfo->subscribers_firstname . ' ' . $cInfo->subscribers_lastname . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->subscribers_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_NEWSLETTERS_SUBSCRIBERS_VIEW, tep_get_all_get_params(array('cID', 'action')) . 'cID=' . $cInfo->subscribers_id . '&action=confirm') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_MAILS, 'selected_box=tools&customer=' . $cInfo->subscribers_email_address) . '">' . tep_image_button('button_email.gif', IMAGE_EMAIL) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_DATE_ACCOUNT_CREATED . ' ' . tep_date_short($cInfo->date_account_created));
        $contents[] = array('text' => '<br>' . TEXT_DATE_ACCOUNT_LAST_MODIFIED . ' ' . tep_date_short($cInfo->date_account_last_modified));
      }
      break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top" style="padding:15px 5px 0 6px">' . "\n";

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
