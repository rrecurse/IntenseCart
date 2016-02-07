<?php

  require('includes/application_top.php');

  function tep_set_featured_status($featured_id, $status) {
    global $messageStack;
    $st = tep_db_fetch_array(tep_db_query("SELECT p.products_id, p.products_status
										   FROM ".TABLE_FEATURED." f
										   LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id = f.products_id
										   WHERE  f.featured_id='" . $featured_id . "'
										  "));
    $p_status = $st["products_status"];


    if ($status == '1') {
      if ($p_status == 1) {
       return tep_db_query("update " . TABLE_FEATURED . " set status = '1', expires_date = NULL, date_status_change = NULL where featured_id = '" . $featured_id . "'");
      } else {
         $messageStack->add_session('Can\'t set featured item to active status because it\'s products is not active', 'error');
      }
    } elseif ($status == '0') {
      return tep_db_query("update " . TABLE_FEATURED . " set status = '0', date_status_change = now() where featured_id = '" . $featured_id . "'");
    } else {
      return -1;
    }
  }

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  switch ($_GET['action']) {
    case 'setflag':
      tep_set_featured_status($_GET['id'], $_GET['flag']);
      tep_redirect(tep_href_link(FILENAME_FEATURED, '', 'NONSSL'));
      break;
    case 'insert':
      $expires_date = tep_db_prepare_input($_POST['expires_date']);
      if (tep_not_null($expires_date)) {
      list($day, $month, $year) = explode('/', $expires_date);

            $expires_date = $year .
                            ((strlen($month) == 1) ? '0' . $month : $month) .
                            ((strlen($day) == 1) ? '0' . $day : $day);
      }
      tep_db_query("insert into " . TABLE_FEATURED . " (products_id, featured_date_added, expires_date, status, affiliate_id,sort_order) values ('" . $_POST['products_id'] . "', now(), '" . $expires_date . "', '1', " . (tep_session_is_registered('login_affiliate')?$login_id:'0') . ",'".$_POST['sort_order']."')");
      tep_redirect(tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page']));
      break;
    case 'update':
      $expires_date = tep_db_prepare_input($_POST['expires_date']);
      if (tep_not_null($expires_date)) {
      list($day, $month, $year) = explode('/', $expires_date);

            $expires_date = $year .
                            ((strlen($month) == 1) ? '0' . $month : $month) .
                            ((strlen($day) == 1) ? '0' . $day : $day);
      }
      tep_db_query("update " . TABLE_FEATURED . " set featured_last_modified = now(), expires_date = '" . $expires_date . "',sort_order = '" . $_POST["sort_order"] . "' where featured_id = '" . $_POST['featured_id'] . "'");
      tep_redirect(tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $featured_id));
      break;
    case 'deleteconfirm':
      $featured_id = tep_db_prepare_input($_GET['sID']);

      tep_db_query("delete from " . TABLE_FEATURED . " where featured_id = '" . tep_db_input($featured_id) . "'");

      tep_redirect(tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page']));
      break;
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title>Featured Products<?//php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
</head>
<body style="margin:0; background:transparent; height:600px">
<div id="spiffycalendar" class="text"></div>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
<td width="100%" valign="top" height="100%" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="0" height="100%" valign="top">
<?php
 
if($_GET['action'] == 'new' || $_GET['action'] == 'edit') {
 
	$form_action = 'insert';
    
	if($_GET['action'] == 'edit' && $_GET['sID']) {
          $form_action = 'update';

	      $product_query = tep_db_query("SELECT p.products_id,p.products_price, p.products_status, s.sort_order, pd.products_name, date_format(s.expires_date, '%d/%m/%Y') AS expires_date 
										 FROM " . TABLE_PRODUCTS . " p
										 LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id
										 LEFT JOIN " . TABLE_FEATURED . " s ON p.products_id = s.products_id
										 WHERE p.products_status = '1'
										 AND p.products_price > 0
										 AND pd.language_id = '" . $languages_id . "' 
										 AND s.featured_id = '".$_GET['sID']."' 
										".(tep_session_is_registered('login_affiliate') ? "AND s.affiliate_id = '" . $login_id."'" : '')." 
										 ORDER BY pd.products_name
										");
	      $product = tep_db_fetch_array($product_query);

    	  $sInfo = new objectInfo($product);

    } else {

		$sInfo = new objectInfo(array());

		// # create an array of featured products, which will be excluded from the pull down menu of products
		// # (when creating a new featured product)
		$exclude_array = array();
		$exclude_query = tep_db_query("SELECT p.products_id, p.products_status, p.products_price 
									  FROM " . TABLE_PRODUCTS . " p 
									  LEFT JOIN " . TABLE_FEATURED . " f ON f.products_id = p.products_id 
									  WHERE (p.products_status ='0' OR p.products_price < 1) 
									  OR f.products_id > 0
						  ".(tep_session_is_registered('login_affiliate') ? " AND (s.affiliate_id = '" . $login_id . "' OR s.affiliate_id = 0)":''));

      while ($exclude = tep_db_fetch_array($exclude_query)) {
        $exclude_array[] = $exclude['products_id'];
      }
    }
?>

<script type="text/javascript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script type="text/javascript">
  var dateExpires = new ctlSpiffyCalendarBox("dateExpires", "new_feature", "expires_date","btnDate1","<?php echo $sInfo->expires_date; ?>",scBTNMODE_CUSTOMBLUE);
</script>
      <tr>
        <td height"=25" background="/images/l_orange_bg.gif"><img src="/images/spacer.gif" width="1"  height=1 alt="" border="0"></td>
      </tr>
      <tr><td valign=top>
<?php echo tep_draw_form('new_feature', FILENAME_FEATURED .'?'. tep_get_all_get_params(array('action', 'info', 'sID')) . 'action=' . $form_action); ?>

<table border=0>
      <tr>

        <td valign=top><br><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_FEATURED_PRODUCT; ?>&nbsp;</td>
            <td class="main">
<?php 
		echo ($sInfo->products_name) ? $sInfo->products_name : tep_draw_products_pull_down('products_id', 'style="font-size:11px; width:99%"', $exclude_array);
		echo tep_draw_hidden_field('products_price', $sInfo->products_price);
?>
			</td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_FEATURED_EXPIRES_DATE; ?>&nbsp;</td>
            <td class="main"><script language="javascript">dateExpires.writeControl(); dateExpires.dateFormat="dd/MM/yyyy";</script></td>
          </tr>
          <tr>
            <td class="main">Sort Order:&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field("sort_order",(($sInfo->sort_order>0)?$sInfo->sort_order:'0'));?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" align="right" valign="top"><br><?php echo (($form_action == 'insert') ? tep_image_submit('button_insert.gif', IMAGE_INSERT) : tep_image_submit('button_update.gif', IMAGE_UPDATE)). '&nbsp;&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $_GET['sID']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?>
</td>
          </tr>
        </table>
<?php if ($form_action == 'update') echo tep_draw_hidden_field('featured_id', $_GET['sID']); ?>
</td>
</tr>
    <table>
      </form>
</td></tr>
<?php
  } else {
?>
      <tr>
        <td height="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0" height="100%">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
<!-- /*                <td class="dataTableHeadingContent" align="right">&nbsp;</td>
 <?php
if (!tep_session_is_registered('login_affiliate')){
?>
              <td class="dataTableHeadingContent" align="right"><?php echo TEXT_AFFILIATE;?></td>
<?php
}
?> /* -->
                <td class="dataTableHeadingContent" align="center" nowrap>Sort Order</td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_STATUS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
    $featured_query_raw = "select p.products_id, p.products_price, p.products_status, pd.products_name, s.sort_order, s.featured_id, s.featured_date_added, s.featured_last_modified, s.expires_date, s.date_status_change, s.status, s.affiliate_id from " . TABLE_PRODUCTS . " p, " . TABLE_FEATURED . " s, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and pd.language_id = '" . $languages_id . "' and p.products_id = s.products_id " . (tep_session_is_registered('login_affiliate')?" and s.affiliate_id = '" . $login_id . "'":'') . " order by s.sort_order, pd.products_name";
    $featured_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $featured_query_raw, $featured_query_numrows);
    $featured_query = tep_db_query($featured_query_raw);
    while ($featured = tep_db_fetch_array($featured_query)) {
      if ( ((!$_GET['sID']) || ($_GET['sID'] == $featured['featured_id'])) && (!$sInfo) ) {

        $products_query = tep_db_query("select products_image from " . TABLE_PRODUCTS . " where products_id = '" . $featured['products_id'] . "'");
        $products = tep_db_fetch_array($products_query);
        $sInfo_array = array_merge($featured, $products);
        $sInfo = new objectInfo($sInfo_array);
      }

      if ( (is_object($sInfo)) && ($featured['featured_id'] == $sInfo->featured_id) ) {
        echo '                  <tr class="dataTableRowSelected" onmouseover="this.style.cursor=\'hand\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $sInfo->featured_id . '&action=edit') . '\'">' . "\n";
      } else {
        echo '                  <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\';this.style.cursor=\'hand\'" onmouseout="this.className=\'dataTableRow\'" onclick="document.location.href=\'' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $featured['featured_id']) . '\'">' . "\n";
      }
?>
                <td  class="dataTableContent" colspan="2"><?php echo $featured['products_name']; ?></td>
<!-- /*                <td  class="dataTableContent" align="right">&nbsp;</td>
 <?php
if (!tep_session_is_registered('login_affiliate')){
?>
              <td class="dataTableContent" align="right"></td>
<?php
}
?> */ -->
              <td class="dataTableContent" align="center"><?php echo $featured['sort_order']; ?></td>
                <td  class="dataTableContent" align="right">
<?php
      if ($featured['status'] == '1') {
        echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_FEATURED, 'action=setflag&flag=0&id=' . $featured['featured_id'], 'NONSSL') . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
      } else {
        echo '<a href="' . tep_href_link(FILENAME_FEATURED, 'action=setflag&flag=1&id=' . $featured['featured_id'], 'NONSSL') . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
      }
?></td>
                <td class="dataTableContent" align="right"><?php if ( (is_object($sInfo)) && ($featured['featured_id'] == $sInfo->featured_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $featured['featured_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
      </tr>
<?php
    }
?>
              <tr>
                <td colspan="4"><table border="0" width="100%" cellpadding="0"cellspacing="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $featured_split->display_count($featured_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_FEATURED); ?></td>
                    <td class="smallText" align="right"><?php echo $featured_split->display_links($featured_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
<?php
  if (!$_GET['action']) {
?>
                  <tr>
                    <td colspan="2" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&action=new') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>'; ?></td>
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
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_FEATURED . '</b>');

      $contents = array('form' => tep_draw_form('featured', FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $sInfo->featured_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $sInfo->products_name . '</b>');
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . '&nbsp;<a href="' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $sInfo->featured_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (is_object($sInfo)) {
        $heading[] = array('text' => '<b>' . $sInfo->products_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $sInfo->featured_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_FEATURED, 'page=' . $_GET['page'] . '&sID=' . $sInfo->featured_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_ADDED . ' ' . tep_date_short($sInfo->featured_date_added));
        $contents[] = array('text' => '' . TEXT_INFO_LAST_MODIFIED . ' ' . tep_date_short($sInfo->featured_last_modified));
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_info_image($sInfo->products_image, $sInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT));

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
