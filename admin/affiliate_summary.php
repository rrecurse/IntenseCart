<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  // delete clickthroughs
  if (AFFILIATE_DELETE_CLICKTHROUGHS != 'false' && is_numeric(AFFILIATE_DELETE_CLICKTHROUGHS)) {
    $time = mktime (1,1,1,date("m"),date("d") - AFFILIATE_DELETE_CLICKTHROUGHS, date("Y"));
    $time = date("Y-m-d", $time);
    tep_db_query("delete from " . TABLE_AFFILIATE_CLICKTHROUGHS . " where affiliate_clientdate < '". $time . "'");
  }
  // delete old records from affiliate_banner_history
  if (AFFILIATE_DELETE_AFFILIATE_BANNER_HISTORY != 'false' && is_numeric(AFFILIATE_DELETE_AFFILIATE_BANNER_HISTORY)) {
    $time = mktime (1,1,1,date("m"),date("d") - AFFILIATE_DELETE_AFFILIATE_BANNER_HISTORY, date("Y"));
    $time = date("Y-m-d", $time);
    tep_db_query("delete from " . TABLE_AFFILIATE_BANNERS_HISTORY . " where affiliate_banners_history_date < '". $time . "'");
  }


  $affiliate_banner_history_raw = "select sum(affiliate_banners_shown) as count from " . TABLE_AFFILIATE_BANNERS_HISTORY . "";
  $affiliate_banner_history_query = tep_db_query($affiliate_banner_history_raw);
  $affiliate_banner_history = tep_db_fetch_array($affiliate_banner_history_query);
  $affiliate_impressions = $affiliate_banner_history['count'];
  if ($affiliate_impressions == 0) $affiliate_impressions = "n/a";

  $affiliate_clickthroughs_raw = "select count(*) as count from " . TABLE_AFFILIATE_CLICKTHROUGHS . "";
  $affiliate_clickthroughs_query = tep_db_query($affiliate_clickthroughs_raw);
  $affiliate_clickthroughs = tep_db_fetch_array($affiliate_clickthroughs_query);
  $affiliate_clickthroughs = $affiliate_clickthroughs['count'];

  $affiliate_sales_raw = "
            select count(*) as count, sum(affiliate_value) as total, sum(affiliate_payment) as payment from " . TABLE_AFFILIATE_SALES . " a 
            left join " . TABLE_ORDERS . " o on (a.affiliate_orders_id = o.orders_id) 
            where o.orders_status >= " . AFFILIATE_PAYMENT_ORDER_MIN_STATUS . " 
            ";

  $affiliate_sales_query= tep_db_query($affiliate_sales_raw);
  $affiliate_sales= tep_db_fetch_array($affiliate_sales_query);

  $affiliate_transactions = $affiliate_sales['count'];
  if ($affiliate_clickthroughs > 0) {
	$affiliate_conversions = tep_round(($affiliate_transactions / $affiliate_clickthroughs)*100,2) . "%";
  } else {
    $affiliate_conversions = "n/a";
  }

  $affiliate_amount = $affiliate_sales['total'];
  if ($affiliate_transactions > 0) {
	$affiliate_average = tep_round($affiliate_amount / $affiliate_transactions, 2);
  } else {
    $affiliate_average = "n/a";
  }

  $affiliate_commission = $affiliate_sales['payment'];

  $affiliates_raw = "select count(*) as count from " . TABLE_AFFILIATE . "";
  $affiliates_raw_query = tep_db_query($affiliates_raw);
  $affiliates_raw = tep_db_fetch_array($affiliates_raw_query);
  $affiliate_number = $affiliates_raw['count'];
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo HEADING_TITLE; ?></title>

<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script src="includes/general.js"></script>

<script>
<!--
function popupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width=450,height=120,screenX=150,screenY=150,top=150,left=150')
}
//--></script>
</head>
<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>



<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" style="padding:5px;">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td width="53"><img src="images/handshake-icon.gif" width="48" height="48"></td>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td style="padding:5px 0 0 0">
			  <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" style="padding:5px"><?php echo TEXT_SUMMARY_TITLE; ?></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><table width="100%" border="0" cellpadding="5" cellspacing="0" class="dataTableContent">
                <tr>
                  <td width="20%" align="right" class="dataTableContent"><?php echo TEXT_AFFILIATES; ?></td>
                  <td width="29%" align="left" class="dataTableContent"><?php echo $affiliate_number; ?></td>
                  <td width="25%" align="right" class="dataTableContent"></td>
                  <td width="26%" class="dataTableContent"></td>
                </tr>
                <tr>
                  <td align="right" nowrap class="dataTableContent"><?php echo TEXT_IMPRESSIONS; ?></td>
                  <td align="left" class="dataTableContent"><?php echo $affiliate_impressions; ?></td>
                  <td align="right" class="dataTableContent"><?php echo TEXT_VISITS; ?></td>
                  <td align="left" class="dataTableContent"><?php echo $affiliate_clickthroughs; ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap class="dataTableContent"><?php echo TEXT_TRANSACTIONS; ?></td>
                  <td align="left" class="dataTableContent"><?php echo $affiliate_transactions; ?></td>
                  <td align="right" class="dataTableContent"><?php echo TEXT_CONVERSION; ?></td>
                  <td align="left" class="dataTableContent"><?php echo $affiliate_conversions;?></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><?php echo TEXT_AMOUNT; ?></td>
                  <td align="left" class="dataTableContent"><?php echo $currencies->display_price($affiliate_amount, ''); ?></td>
                  <td align="right" class="dataTableContent"><?php echo TEXT_AVERAGE; ?></td>
                  <td align="left" class="dataTableContent"><?php echo $currencies->display_price($affiliate_average, ''); ?></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent"><?php echo TEXT_COMMISSION_RATE; ?></td>
                  <td align="left" class="dataTableContent"><?php echo tep_round(AFFILIATE_PERCENT, 2) . ' %'; ?></td>
                  <td align="right" nowrap class="dataTableContent"><b><?php echo TEXT_COMMISSION; ?></b></td>
                  <td align="left" class="dataTableContent"><b><?php echo $currencies->display_price($affiliate_commission, ''); ?></b></td>
                </tr>
                <tr>
                  <td align="right" class="dataTableContent" colspan="4" style="padding:15px 0 15px 0; border-top:solid 1px #e5e5e5"><?php echo '<a href="' . tep_href_link(FILENAME_AFFILIATE_BANNERS, '') . '">' . tep_image_button('button_affiliate_banners.gif', IMAGE_BANNERS) . '</a> <a href="' . tep_href_link(FILENAME_AFFILIATE_CLICKS, '') . '">' . tep_image_button('button_affiliate_clickthroughs.gif', IMAGE_CLICKTHROUGHS) . '</a> <a href="' . tep_href_link(FILENAME_AFFILIATE_SALES, '') . '">' . tep_image_button('button_affiliate_sales.gif', IMAGE_SALES) . '</a>'; ?></td>
                </tr>
            </table>

              <table width="100%" cellpadding="5" cellspacing="0" style="border:solid 1px #E5E5E5">
                <tr>
                  <td width="33%"> 
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
                		<tr>
            	    	  <td width="53"><a href="affiliate_affiliates.php"><img src="images/users-icon.gif" alt="" width="48" height="48" border="0"></a></td> 
						  <td><a href="affiliate_affiliates.php"><b>Affiliate Manager</b></a></td>
						</tr>
					</table>
				</td>
                <td width="34%" style="border-left:solid 1px #e5e5e5;"> <table width="100%" border="0" cellspacing="0" cellpadding="5">
                <tr>
                  <td width="53">
				  <a href="customers_groups.php?page=1&cID=2&action=edit"><img src="images/commish-icon.gif" alt="" width="48" height="48" border="0"></a>
				  </td>
				  <td>
				  <a href="affiliate_payment.php"><b>Reconcile Payments</b></a> <br>
				  <a href="customers_groups.php?page=1&cID=2&action=edit"><b><small>Comm. Groups</small></b></a>
				  </td>
                </tr></table>
                  </td>
                  <td width="33%" style="border-left:solid 1px #e5e5e5;"> <table width="100%" border="0" cellspacing="0" cellpadding="5">
                <tr>
                  <td width="53"><a href="affiliate_clicks.php"><img src="images/handshake-icon.gif" alt="" width="48" height="48" border="0"></a></td><td>  <a href="affiliate_clicks.php"><b>Referral Log</b></a>
                   </td></tr></table></td>
                </tr>
                <tr>
                  <td style="border-top:solid 1px #e5e5e5;"> <table width="100%" border="0" cellspacing="0" cellpadding="5">
                <tr>
                  <td width="53"><a href="affiliate_banners.php"><img src="images/affbanners-icon.gif" alt="" width="48" height="48" border="0"></a></td><td>
				  <a href="affiliate_banners.php"><b>Affiliate Banners</b></a>
                     </td></tr></table></td>
                  <td style="border-left:solid 1px #e5e5e5; border-top:solid 1px #e5e5e5;"> <table width="100%" border="0" cellspacing="0" cellpadding="5">
                <tr>
                  <td width="53"><a href="affiliate_news.php"><img src="images/affnews-icon.gif" alt="" width="48" height="48" border="0"></a></td><td>
                      <a href="affiliate_news.php"><b>Announcements</b></a></td>
                </tr></table></td>
                  <td style="border-left:solid 1px #e5e5e5; border-top:solid 1px #e5e5e5;"> <table width="100%" border="0" cellspacing="0" cellpadding="5">
                <tr>
                  <td width="53"><a href="affiliate_contact.php"><img src="images/affnewsAdd-icon.gif" alt="" width="48" height="48" border="0"></a></td><td>
                      <a href="affiliate_contact.php"><b>Email
                        Affiliates</b></a></td></tr></table></td>
                </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php');?>