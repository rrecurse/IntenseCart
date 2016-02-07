<?php

  require('includes/application_top.php');

  if (!tep_session_is_registered('affiliate_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_AFFILIATE, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_AFFILIATE_SUMMARY);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_AFFILIATE_SUMMARY));

  $affiliate_banner_history_raw = "select sum(affiliate_banners_shown) as count from " . TABLE_AFFILIATE_BANNERS_HISTORY .  " where affiliate_banners_affiliate_id  = '" . $affiliate_id . "'";
  $affiliate_banner_history_query=tep_db_query($affiliate_banner_history_raw);
  $affiliate_banner_history = tep_db_fetch_array($affiliate_banner_history_query);
  $affiliate_impressions = $affiliate_banner_history['count'];
  if ($affiliate_impressions == 0) $affiliate_impressions="n/a";

  $affiliate_clickthroughs_raw = "select count(*) as count from " . TABLE_AFFILIATE_CLICKTHROUGHS . " where affiliate_id = '" . $affiliate_id . "'";
  $affiliate_clickthroughs_query = tep_db_query($affiliate_clickthroughs_raw);
  $affiliate_clickthroughs = tep_db_fetch_array($affiliate_clickthroughs_query);
  $affiliate_clickthroughs =$affiliate_clickthroughs['count'];

  $affiliate_sales_raw = "
			select count(*) as count, sum(affiliate_value) as total, sum(affiliate_payment) as payment from " . TABLE_AFFILIATE_SALES . " a
			left join " . TABLE_ORDERS . " o on (a.affiliate_orders_id=o.orders_id)
			where a.affiliate_id = '" . $affiliate_id . "' and o.orders_status >= " . AFFILIATE_PAYMENT_ORDER_MIN_STATUS . "
			";
  $affiliate_sales_query = tep_db_query($affiliate_sales_raw);
  $affiliate_sales = tep_db_fetch_array($affiliate_sales_query);

  $affiliate_transactions=$affiliate_sales['count'];
  if ($affiliate_clickthroughs > 0) {
	$affiliate_conversions = tep_round(($affiliate_transactions / $affiliate_clickthroughs)*100, 2) . "%";
  } else {
    $affiliate_conversions = "n/a";
  }
  $affiliate_amount = $affiliate_sales['total'];
  if ($affiliate_transactions>0) {
	$affiliate_average = tep_round($affiliate_amount / $affiliate_transactions, 2);
  } else {
	$affiliate_average = "n/a";
  }
  $affiliate_commission = $affiliate_sales['payment'];

  $affiliate_values = tep_db_query("select * from " . TABLE_AFFILIATE . " where affiliate_id = '" . $affiliate_id . "'");
  $affiliate = tep_db_fetch_array($affiliate_values);
  $affiliate_percent = 0;
  $affiliate_percent = $affiliate['affiliate_commission_percent'];
  if ($affiliate_percent < AFFILIATE_PERCENT) $affiliate_percent = AFFILIATE_PERCENT;
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">

<script src="includes/general.js" type="text/javascript"></script>

<script src="/admin/js/tips.js" type="text/javascript"></script>

</head>
<body>

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="100%" valign="top" colspan="2"><?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'affiliate_summary.png', HEADING_TITLE); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  if ($messageStack->size('account') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('account'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
}
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_GREETING . $affiliate['affiliate_firstname'] . ' ' . $affiliate['affiliate_lastname'] . '<br>' . TEXT_AFFILIATE_ID . $affiliate_id; ?></td>
          </tr>
          <tr>
            <td class="main"><a href="<?=tep_href_link(FILENAME_CREATE_ACCOUNT,'apply=vendor&ref='.$affiliate_id)?>">Create New Vendor</a></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="infoboxheading"><?php echo TEXT_SUMMARY_TITLE; ?></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><table width="100%" border="0" cellpadding="4" cellspacing="2">

                <tr>
                  <td width="35%" align="right" class="boxtext"> <?php echo TEXT_IMPRESSIONS; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_IMPRESSIONS; ?></b></font><br><?php echo TEXT_IMPRESSIONS_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span>
</td>
                  <td width="15%" class="boxtext"><?php echo $affiliate_impressions; ?></td>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_VISITS; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_VISITS; ?></b></font><br><?php echo TEXT_VISITS_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo $affiliate_clickthroughs; ?></td>
                </tr>
                <tr>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_TRANSACTIONS; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_TRANSACTIONS; ?></b></font><br><?php echo TEXT_TRANSACTIONS_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo $affiliate_transactions; ?></td>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_CONVERSION; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_CONVERSION; ?></b></font><br><?php echo TEXT_CONVERSION_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo $affiliate_conversions;?></td>
                </tr>
                <tr>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_AMOUNT; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_AMOUNT; ?></b></font><br><?php echo TEXT_AMOUNT_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo $currencies->format($affiliate_amount); ?></td>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_AVERAGE; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_AVERAGE; ?></b></font><br><?php echo TEXT_AVERAGE_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo $currencies->format($affiliate_average); ?></td>
                </tr>
                <tr>
                   <td align="right" class="boxtext"><?php echo TEXT_CLICKTHROUGH_RATE; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_CLICKTHROUGH_RATE; ?></b></font><br><?php echo TEXT_CLICKTHROUGH_RATE_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                   <td class="boxtext"><?php echo  $currencies->format(AFFILIATE_PAY_PER_CLICK); ?></td>
                   <td align="right" class="boxtext"><?php echo TEXT_PAYPERSALE_RATE; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_PAYPERSALE_RATE; ?></b></font><br><?php echo TEXT_PAY_PER_SALE_RATE_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                   <td class="boxtext"><?php echo  $currencies->format(AFFILIATE_PAYMENT); ?></td>
                </tr>
                <tr>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_COMMISSION_RATE; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_COMMISSION_RATE; ?></b></font><br><?php echo TEXT_COMMISSION_RATE_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo tep_round($affiliate_percent, 2). '%'; ?></td>
                  <td width="35%" align="right" class="boxtext"><?php echo TEXT_COMMISSION; ?><span style="cursor:help;" onmouseout="hideddrivetip()" onmouseover="ddrivetip('<font class=featuredpopName><b><?php echo TEXT_COMMISSION; ?></b></font><br><?php echo TEXT_COMMISSION_HELP?><br>')"><?php echo TEXT_SUMMARY_HELP; ?></span></td>
                  <td width="15%" class="boxtext"><?php echo $currencies->format($affiliate_commission); ?></td>
                </tr>
                <tr>
                  <td colspan="4"><?php echo tep_draw_separator(); ?></td>
                </tr>
                 <tr>
                  <td align="center" class="boxtext" colspan="4"><b><?php echo TEXT_SUMMARY; ?><b></td>
                </tr>
                <tr>
                  <td colspan="4"><?php echo tep_draw_separator(); ?></td>
                </tr>
      <tr>
        <td colspan="4"><img src="images/pixel_trans.gif" border="0" alt="" width="100%" height="10"></td>
      </tr>
      <tr>
        <td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_SUMMARY, '', 'SSL'). '">' . TEXT_AFFILIATE_SUMMARY . '</a>';?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="4"><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
                <td width="60"><img src="images/affiliate_account.png" border="0" alt=""></td>
                <td width="10"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main" width="50%"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_ACCOUNT, '', 'SSL'). '">' . TEXT_AFFILIATE_ACCOUNT . '</a>';?></td>
                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_NEWSLETTER, '', 'SSL'). '">' . TEXT_AFFILIATE_NEWSLETTER . '</a>';?></td>
                  </tr>
                  <tr>
                    <td class="main" width="50%"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_PASSWORD, '', 'SSL'). '">' . TEXT_AFFILIATE_PASSWORD . '</a>';?></td>
                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_NEWS, '', 'SSL'). '">' . TEXT_AFFILIATE_NEWS . '</a>';?></td>
                  </tr>
                </table></td>
               <td width="10" align="right"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
             </tr>
           </table></td>
         </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="4"><img src="images/pixel_trans.gif" border="0" alt="" width="100%" height="10"></td>
      </tr>
      <tr>
        <td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_BANNERS, '', 'SSL'). '">' . TEXT_AFFILIATE_BANNERS . '</a>';?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="4"><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
                <td width="60"><img src="images/embed.png" border="0" alt=""></td>
                <td width="10"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_BANNERS_CART, '', 'SSL'). '"><b>' . TEXT_AFFILIATE_BANNERS_CART . '</b></a> <img src="/images/new_icon.gif" alt="">';?></td>

                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_BANNERS_BUILD, '', 'SSL'). '">' . TEXT_AFFILIATE_BANNERS_BUILD . '</a>';?></td>

                  </tr>
                  <tr>
<td class="main" width="50%"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_BANNERS_BANNERS, '', 'SSL'). '">' . TEXT_AFFILIATE_BANNERS_BANNERS . '</a>';?></td>
                    <td class="main" width="50%"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_BANNERS_PRODUCT, '', 'SSL'). '">' . TEXT_AFFILIATE_BANNERS_PRODUCT . '</a>';?></td>

                  </tr>
                  </table></td>
               <td width="10" align="right"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
             </tr>
           </table></td>
         </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="4"><img src="images/pixel_trans.gif" border="0" alt="" width="100%" height="10"></td>
      </tr>
      <tr>
        <td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_REPORTS, '', 'SSL'). '">' . TEXT_AFFILIATE_REPORTS . '</a>';?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="4"><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
                <td width="60"><img src="images/graph.png" border="0" alt=""></td>
                <td width="10"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_CLICKS, '', 'SSL'). '">' . TEXT_AFFILIATE_CLICKRATE . '</a>';?></td>
                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_PAYMENT, '', 'SSL'). '">' . TEXT_AFFILIATE_PAYMENT . '</a>';?></td>
                  </tr>
                  <tr>
                    <td class="main"><img src="images/arrow_green.gif" border="0" alt="">&nbsp; <?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_SALES, '', 'SSL'). '">' . TEXT_AFFILIATE_SALES . '</a>';?></td>
                    <td class="main" width="50%">&nbsp;</td>
                  </tr>
                  </table></td>
               <td width="10" align="right"><img src="images/pixel_trans.gif" border="0" alt="" width="10" height="1"></td>
             </tr>
           </table></td>
         </tr>
        </table></td>
      </tr>

            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
