<?php

  require('includes/application_top.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, 'return=' . FILENAME_ACCOUNT, 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_WISHLIST);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script type="text/javascript">
<!--
function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}
//-->
</script>
</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table width="100%" border="0" align="center" cellpadding="3" cellspacing="3">
  <tr>
    <td valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td valign="top">


<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td><? if (!tep_session_is_registered('$customer_id')) {
echo 'Welcome back ' . $customer_first_name . ' &nbsp; <a href="' . tep_href_link('logoff.php') . '"><i>(Not ' . $customer_first_name . '?)</i></a>';
}
?></td>
<td align="right" nowrap><a href="/logoff.php" class="logofflink">Log Off</a></td></tr>
<?php
  if ($messageStack->size('account') > 0) {
?>
      <tr>
        <td colspan="2"><?php echo $messageStack->output('account'); ?></td>
      </tr>
<?php
  }

  if (tep_count_customer_orders() > 0) {
?>
      <tr>
        <td valign="top" colspan="2">
<br>


<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td class="main" width="50%"><?php echo '<b>' . OVERVIEW_PREVIOUS_ORDERS . '</b>'?></b></td>
<td class="main" width="50%" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL') . '"><u>' . OVERVIEW_SHOW_ALL_ORDERS . '</u></a>'; ?></td>
</tr></table>


</td>
      </tr>
      <tr>
        <td colspan="2">
<br>
<table border="0" width="100%" cellspacing="0" cellpadding="2" style="border:1px dashed #cccccc; padding:8px 10px 5px 10px">

<td><u>Date</u></td> <td><u>Order ID</u></td> <td><u>Name on Order</u></td> <td><u>Status</u></td> <td align="right"><u>Amount</u></td> <td></td>
<?php
    $orders_query = tep_db_query("select o.orders_id, o.date_purchased, o.delivery_name, o.delivery_country, o.billing_name, o.billing_country, ot.text as order_total, s.orders_status_name from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)$customer_id . "' and o.orders_id = ot.orders_id and ot.class = 'ot_total' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' order by orders_id desc limit 3");
    while ($orders = tep_db_fetch_array($orders_query)) {
      if (tep_not_null($orders['delivery_name'])) {
        $order_name = $orders['delivery_name'];
        $order_country = $orders['delivery_country'];
      } else {
        $order_name = $orders['billing_name'];
        $order_country = $orders['billing_country'];
      }
?>
                  <tr class="moduleRow" onMouseOver="rowOverEffect(this)" onMouseOut="rowOutEffect(this)" onClick="document.location.href='<?php echo tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders['orders_id'], 'SSL'); ?>'">
                    <td class="main"><?php echo tep_date_short($orders['date_purchased']); ?></td>
                    <td class="main"><?php echo '#' . $orders['orders_id']; ?></td>
                    <td class="main"><?php echo tep_output_string_protected($order_name)?></td>
                    <td class="main"><?php echo $orders['orders_status_name']; ?></td>
                    <td class="main" align="right"><?php echo $orders['order_total']; ?></td>
                    <td class="main" align="right" valign="top"><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders['orders_id'], 'SSL') . '">' . tep_image_button('small_view.gif', SMALL_IMAGE_BUTTON_VIEW) . '</a>'; ?></td>
                  </tr>
<?php
    }
?>
</table>


</td>
      </tr>
<?php
  }
?>
      <tr>
        <td style="padding:15px 0 0 0" class="main"><b><?php echo MY_ACCOUNT_TITLE; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td width="60"><?php echo tep_image(DIR_WS_IMAGES . 'account_personal.gif'); ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL') . '">' . MY_ACCOUNT_INFORMATION . '</a>'; ?></td>
                  </tr>
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL') . '">' . MY_ACCOUNT_ADDRESS_BOOK . '</a>'; ?></td>
                  </tr>
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_ACCOUNT_PASSWORD, '', 'SSL') . '">' . MY_ACCOUNT_PASSWORD . '</a>'; ?></td>
                  </tr>

                </table></td>
                <td width="10" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td style="padding:10px 0 0 0"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo MY_ORDERS_TITLE; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td valign="top"><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td width="60"><?php echo tep_image(DIR_WS_IMAGES . 'account_orders.gif'); ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL') . '">' . MY_ORDERS_VIEW . '</a>'; ?></td>
                  </tr>
<tr>
                    <td class="main"></td>
                  </tr>
<tr>
                    <td class="main"></td>
                  </tr>

                </table></td>
                <td width="10" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
 <tr>
        <td style="padding:10px 0 0 0"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo MY_WISHLIST_TITLE ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td width="60" valign="top" style="padding:20px 0 0 0"><?php echo tep_image(DIR_WS_IMAGES . 'account_wishlist.png'); ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_WISHLIST, '', 'SSL') . '">' . MY_WISHLIST_TITLE . '</a>'; ?></td>
                  </tr>
                  <tr>
                    <td class="main" style="padding:10px 0 0 7px;"><b><? echo MY_WISHLIST_PUBLIC_TITLE ;?></b>

<br><br><?php echo ' <a href="' . tep_href_link(FILENAME_WISHLIST_PUBLIC, '', 'SSL') . '?public_id=' . $customer_id .'&amp;wish_box=">' . tep_href_link(FILENAME_WISHLIST_PUBLIC, '', 'SSL') . '?public_id=' . $customer_id . '</a>'; ?><br><br>

<script type="text/javascript">
var addthis_config =
{
ui_cobrand : "<? echo STORE_NAME ?>"
}
var addthis_share =
{

}
</script>


<div class="addthis_toolbox addthis_default_style" addthis:url="<?php echo '' . tep_href_link(FILENAME_WISHLIST_PUBLIC, '', 'SSL') . '?public_id=' . $customer_id . ''; ?>" addthis:title="<?php echo ''. $customer_first_name .'\'s ' . NAVBAR_TITLE_WISHLIST .''?>">

<a href="http://www.addthis.com/bookmark.php" class="addthis_button_compact" >Share</a>
<span class="addthis_separator">|</span>
<a class="addthis_button_facebook"></a>
<a class="addthis_button_myspace"></a>
<a class="addthis_button_twitter"></a>
</div>
<script type="text/javascript" src="//s7.addthis.com/js/250/addthis_widget.js"></script>
</td>
                  </tr>
                </table></td>
                <td width="10" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td></tr>

      <tr>
        <td style="padding:10px 0 0 0"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo EMAIL_NOTIFICATIONS_TITLE; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td width="60"><?php echo tep_image(DIR_WS_IMAGES . 'account_notifications.gif'); ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_ACCOUNT_NEWSLETTERS, '', 'SSL') . '">' . EMAIL_NOTIFICATIONS_NEWSLETTERS . '</a>'; ?></td>
                  </tr>
                  <tr>
                    <td class="main"><?php echo tep_image(DIR_WS_IMAGES . 'arrow_green.gif') . ' <a href="' . tep_href_link(FILENAME_ACCOUNT_NOTIFICATIONS, '', 'SSL') . '">' . EMAIL_NOTIFICATIONS_PRODUCTS . '</a>'; ?></td>
                  </tr>
                </table></td>
                <td width="10" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td></tr>

    </table></td>

    <td valign="top"><table border="0" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table></td>
  </tr>
</table>

<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
