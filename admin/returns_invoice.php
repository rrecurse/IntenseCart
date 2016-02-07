<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $oID = $HTTP_GET_VARS['oID'];
  $orders_query = tep_db_query("select returns_id from " . TABLE_RETURNS . " where returns_id = '" . $oID . "'");
  $order_result = tep_db_fetch_array($orders_query);
  $returns_id = $orders_result['returns_id'];
  include(DIR_WS_CLASSES . 'returns.php');
  $order = new order_return($oID);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">

<!-- body_text //-->
<table border="0" width="100%" cellspacing="0" cellpadding="2">
  <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td class="pageHeading"><?php echo nl2br(STORE_NAME_ADDRESS); ?></td>
            <td height="50"></td>
      </tr>
    </table></td>
  </tr>

      <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php

  $support_departments = array();
  $support_department_array = array();
  $support_department_query = tep_db_query("select * from " . TABLE_REFUND_METHOD . " ");
  while ($support_department = tep_db_fetch_array($support_department_query)) {
    $support_departments[] = array('id' => $support_department['refund_method_id'],
                               'text' => $support_department['refund_method_name']);
    $support_department_array[$support_department['refund_method_id']] = $support_department['refund_method_name'];
  }




  $restock_query = tep_db_query("SELECT configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'DEFAULT_RESTOCK_VALUE'");
  $restock = tep_db_fetch_array($restock_query);
?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="pageHeading" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_RETURNS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td colspan="3"><?php echo tep_draw_separator(); ?></td>
          </tr>
          <tr>
            <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main" valign="top"><b><?php echo ENTRY_CUSTOMER; ?></b></td>
                <td class="main"><?php echo tep_address_format($order->customer['format_id'], $order->customer, 1, '&nbsp;', '<br>'); ?></td>
              </tr>
              <tr>
                <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
              </tr>
              <tr>
                <td class="main"><b><?php echo ENTRY_TELEPHONE; ?></b></td>
                <td class="main"><?php echo $order->customer['telephone']; ?></td>
              </tr>
              <tr>
                <td class="main"><b><?php echo ENTRY_EMAIL_ADDRESS; ?></b></td>
                <td class="main"><?php echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $order->customer['email_address'] . '</u></a>'; ?></td>
              </tr>
            </table></td>
            <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main" valign="top"><b><? echo TEXT_SHIPPING_ADRESS; ?></b></td>
                <td class="main"><?php echo tep_address_format($order->delivery['format_id'], $order->delivery, 1, '&nbsp;', '<br>'); ?></td>
              </tr>
            </table></td>
            <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main" valign="top"><b><? echo TEXT_BILLING_ADRESS; ?></b></td>
                <td class="main"><?php echo tep_address_format($order->billing['format_id'], $order->billing, 1, '&nbsp;', '<br>'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">
      </tr>
<?php
// BOF: WebMakers.com Added: Show Order Info
?>
<!-- add Order # // -->
<tr>
<td class="main"><b><?php echo TEXT_INVOICE_NO; ?></b></td>
<td class="main"><?php echo tep_db_input($oID); ?></td>
</tr>
<!-- add date/time // -->
<tr>
<td class="main"><b><?php echo TEXT_DATE_TIME; ?></b></td>
<td class="main"><?php echo tep_datetime_short($order->info['date_purchased']); ?></td>
</tr>
<tr>
<td class="main"><b><?php echo TEXT_IP_ADDRESS; ?></b></td>
<td class="main"><?php echo $order->info['rma_value']; ?></td>
</tr>

<?php
// EOF: WebMakers.com Added: Show Order Info
?>
<tr>
<td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>


<?php
 //   if ( (($order->info['cc_type']) || ($order->info['cc_owner']) || ($order->info['cc_number']) || ($order->info['cvvnumber'])) ) {
?>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
            <td class="main"><?php echo $order->info['cc_type']; ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
            <td class="main"><?php echo $order->info['cc_owner']; ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
            <td class="main"><?php echo $order->info['cc_number']; ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
            <td class="main"><?php echo $order->info['cc_expires']; ?></td>
          </tr>

<?php
// purchaseorder start
  //  } else if( (($order->info['account_name']) || ($order->info['account_number']) || ($order->info['po_number'])) ) {
?>

<?php
// purchaseorder end
//    }
?>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr class="dataTableHeadingRow">
            <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
            <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TAX; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></td>
            <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></td>
            <td class="dataTableHeadingContent" align="right"><? echo TEXT_DECUSIONS; ?></td>
            <td class="dataTableHeadingContent" align="right"><? echo TEXT_REFUND_AMOUNT; ?></td>

          </tr>
<?php
   // for ($i=0; $i<sizeof($order->products); $i++) {
       $refunds_payment_query = tep_db_query("SELECT * FROM " . TABLE_RETURN_PAYMENTS . " where returns_id = '" . $oID . "'");
       $refund = tep_db_fetch_array($refunds_payment_query);



      echo '          <tr class="dataTableRow">' . "\n" .
           '            <td class="dataTableContent" valign="top" align="right">' . $order->products['qty'] . '&nbsp;x</td>' . "\n" .
           '            <td class="dataTableContent" valign="top">' . $order->products['name'];



      echo '            </td>' . "\n" .
           '            <td class="dataTableContent" valign="top">' . $order->products['model'] . '</td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top">' . tep_display_tax_value($order->products['tax']) . '%</td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($order->products['final_price'] * $order->products['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format(tep_add_tax($order->products['final_price'], $order->products['tax']) * $order->products['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>' . "\n" .
            '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($refund['refund_payment_deductions']) . '</b></td>' . "\n" .
           '            <td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($refund['refund_payment_value']) . '</b></td>' . "\n";

       echo '          </tr>' . "\n";
   // }
?>
          <tr>
            <td align="right" colspan="8"><table border="0" cellspacing="0" cellpadding="2">
<?php
    for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
      echo '              <tr>' . "\n" .
           '                <td align="right" class="smallText">' . $order->totals[$i]['title'] . '</td>' . "\n" .
           '                <td align="right" class="smallText">' . $order->totals[$i]['text'] . '</td>' . "\n" .
           '              </tr>' . "\n";
    }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>

      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
      </tr>
      <tr><?php echo tep_draw_form('status', FILENAME_RETURNS, tep_get_all_get_params(array('action')) . 'action=update_order'); ?>

      </tr>
        <?
        $order_status_query = tep_db_query("SELECT returns_status_name FROM " . TABLE_RETURNS_STATUS . " where returns_status_id = '" . $order->info['orders_status'] . "'");
        $order_status  = tep_db_fetch_array($order_status_query);
?>

      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td><table border="0" cellspacing="0" cellpadding="2">
              <tr>
                 <td class="main" valign=top><b><? echo TEXT_RETURN_COMMENT; ?></b></td><td class=main><?php echo nl2br($order->info['comments']); ?></td>
               </tr>
               <tr>
               <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main" width=25%><b><? echo ENTRY_STATUS; ?></b></td><td width=65% class=main><?php echo $order_status['returns_status_name']; ?></td>
              </tr>
              <tr>
               <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td class="main" width=25% valign=top><b><? echo TEXT_CUSTOM_PREF_METHOD; ?></b></td><td width=65% class=main><? echo $order->info['department']; ?></td>
              </tr>
              <tr>
                <td class="main" width=25% valign=top><b><?php echo ENTRY_PAYMENT_METHOD; ?></b></td><td width=65% class=main><? echo $order->info['customer_method']; ?></td>
              </tr>
              <tr>
                <td class="main" width=25% valign=top><b><?php echo ENTRY_PAYMENT_REFERENCE; ?></b></td><td width=65% class=main><? echo $order->info['payment_reference']; ?></td>
              </tr>
              <?
               $price_new = $order->info['refund_amount'];

              ?>

              <tr>
                <td class="main" width=25%><b><? echo ENTRY_PAYMENT_AMOUNT; ?></b></td><td width=65% class=main><? echo $currencies->format($price_new ); ?></td>
              </tr>
              <tr>
                <td class="main" width=25%><b><? echo ENTRY_PAYMENT_DATE; ?></b></td><td width=65% class=main><? echo tep_date_short($order->info['refund_date']); ?></td>
              </tr>
              <?
              $restock_query = tep_db_query("SELECT configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'DEFAULT_RESTOCK_VALUE'");
              $restock = tep_db_fetch_array($restock_query);
              $tax = $restock['configuration_value'];
              $work_out_charge = ((tep_add_tax($order->info['refund_amount'],$order->products['tax']) / 100) * $tax);
              echo '<input type=hidden name=add_tax value=' . $order->products['tax'] . '>';
              ?>

              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>

            </table></td>



          </tr>
        </table></td>
      </form></tr>


<?php
 // }
  ?>



</table>
<!-- body_text_eof //-->

<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
