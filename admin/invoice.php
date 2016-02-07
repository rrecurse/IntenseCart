<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  require(DIR_WS_CLASSES . 'phpqrcode.php');   

  $oID = tep_db_prepare_input($_GET['oID']);

  $orders_date = tep_db_query("select date_purchased FROM ". TABLE_ORDERS ." WHERE orders_id = '". $oID."'");
  $orders_date = mysql_result($orders_date,0);

  $shipping_method = tep_db_query("select shipping_method from ". TABLE_ORDERS ." WHERE orders_id = '".$oID."'");
  $shipping_method = mysql_result($shipping_method,0);
  $shipping_method = str_replace('upsxml_','', $shipping_method);


  include(DIR_WS_CLASSES . 'order.php');
  $order = new order($oID);
  $order->getPayments();
  $payments=$order->getPayments();

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<style type="text/css">
	.tabOdd{background-color:#FFFFFF !important;}
/*	.tabEven{background-color:#F3F3F3 !important;}*/
@media print {

	body {  
		page-break-after:avoid !important;
		width:675px;
		height: 842px;
		margin: 0 auto;
		overflow:hidden !important;
	}

	.tabOdd{background-color:#F3F3F3;}
	.tabEven{background-color:none;}

}
</style>
</head>
<body>

<table border="0" width="675" style="width:675px; margin:5px auto" cellspacing="0" cellpadding="2" align="center">
  <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td style="padding:0 0 10px 0">
<?php if(!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') {
echo tep_image(DIR_WS_IMAGES . 'amazonSeller-logo.jpg',  TITLE, '', ''); 
echo '<br><span style="font:normal 11px arial">Thank you for buying from '.STORE_NAME.' on Amazon Marketplace</span>';
} else { 
echo tep_image(DIR_WS_IMAGES . 'invoice-logo.gif',  TITLE, '', ''); 
}
?></td>
        <td class="invoiceHeading" align="right"><?php echo nl2br(STORE_NAME_ADDRESS);?></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top" style="border-top:1px solid #333">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="35%" valign="top" style="padding:6px 0 0 0;"><table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo ENTRY_SOLD_TO; ?></b></td>
          </tr>
          <tr>
            <td class="main"><?php echo tep_address_format($order->customer['format_id'], $order->customer, 1, '', '<br>'); ?></td>
          </tr>
          <tr>
            <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo $order->customer['telephone']; ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $order->customer['email_address'] . '</u></a>'; ?></td>
          </tr>
        </table></td>
        <td width="30%" valign="top" style="padding:6px 0 0 0;"><table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo ENTRY_SHIP_TO; ?></b></td>
          </tr>
          <tr>
            <td class="main"><?php echo tep_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>'); 
			echo '<br><br>
			Ship via: <b>' .$shipping_method . '</b><br>';
echo (!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') ? 'Order: '. $oID : '';
?>
			</td>
          </tr>
        </table></td>
		<td width="35%" valign="top" style="padding:3px 0 0 0;">

<table width="100%" border="0" cellpadding="6" cellspacing="0" style="border:1px dashed #CCC; border-radius:5px">
          <tr>
            <td class="main"><b>ORDER INFO:</b></td>
          </tr>
          <tr>
            <td class="main" valign="top" style="padding:0">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>


<?php
$filename = DIR_FS_SITE.'public_html/admin/tmp/qrcode.png';

if(!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') { 
	echo '<td valign="top" style="padding:2px 0 0 6px">';
	$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$oID."' AND method = 'payment_amazonSeller'") or die(mysql_error());
	$theAmazonoID = tep_db_fetch_array($amazonOrder_query);

	echo '<td valign="top" style="padding:2px 0 0 0">';
	echo '<div style="padding:2px 0 10px 0;"><b>Order #:</b></div>';

} else { 

	echo '<td valign="top" style="padding:2px 0 0 6px">';
	echo '<div style="padding:15px 0 10px 0;"><b>Order #:</b>';
}

$amazonID = '<span style="font:bold 15px arial; color:#FF6600">'.$theAmazonoID['ref_id'].'</span>';
$orderID = '<span style="font:bold 18px arial">'.$oID.'</span>';

echo (!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') ? $amazonID : $orderID;

echo '</div>';
?>

</td>  

<?php if(!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') {
echo '<td valign="top" align="right" style="padding:0">';

QRcode::png($theAmazonoID['ref_id'], $filename, 'H', '2', 2);  
echo '<img src="'.str_replace(array(DIR_FS_SITE,'public_html'),'',$filename).'">';

} else { 

echo '<td valign="top" align="right" style="padding:0 5px 0 0">';

QRcode::png('Order#: '. $oID, $filename, 'H', '2', 2); 
echo '<img src="'.str_replace(array(DIR_FS_SITE,'public_html'),'',$filename).'">';
}
?>

</td>
</tr>
</table>
</td>
          </tr>
          <tr>
            <td class="main" style="padding-top:15px">Purchased: &nbsp;<?php echo date('M, jS Y - g:ia', strtotime($orders_date)) ;?></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
<tr>
<td style="padding:10px 0 0 0">
  <table border="0" width="100%" cellspacing="0" cellpadding="5">
      <tr class="dataTableHeadingRow">
		<td class="invoiceTableHeadingContent" width="25" align="center">Qty.</td>
        <td class="invoiceTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
        <td class="invoiceTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
        <td class="invoiceTableHeadingContent" align="center"><?php echo TABLE_HEADING_TAX; ?></td>
        <!--td class="invoiceTableHeadingContent" align="right"><?php //echo TABLE_HEADING_PRICE_EXCLUDING_TAX; ?></td-->
        <td class="invoiceTableHeadingContent" align="right">Each<?php //echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></td>
        <td class="invoiceTableHeadingContent" align="right">Subtotal<?php //echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></td>
        <td class="invoiceTableHeadingContent" align="right">Total<?php //echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></td>
      </tr>
<?php
    for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {

		echo '<tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'">
				<td class="dataTableContent" valign="top" align="center">' . $order->products[$i]['qty'] . '&nbsp;x</td>
				<td class="dataTableContent" valign="top">' . $order->products[$i]['name'];

		if (isset($order->products[$i]['attributes']) && (($k = sizeof($order->products[$i]['attributes'])) > 0)) {
			for ($j = 0; $j < $k; $j++) {
				echo '<br><nobr><small>&nbsp;<i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'];
				if ($order->products[$i]['attributes'][$j]['price'] != '0') echo ' (' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')';
				echo '</i></small></nobr>';
			}
		}

		echo '</td>
			<td class="dataTableContent" valign="top">' . $order->products[$i]['model'] . '</td>
			<td class="dataTableContent" align="right" valign="top">' . tep_display_tax_value($order->products[$i]['tax']) . '%</td>
			<!--td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format($order->products[$i]['final_price'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td-->
			<td class="dataTableContent" align="right" valign="top">' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), true, $order->info['currency'], $order->info['currency_value']) . '</td>
			<td class="dataTableContent" align="right" valign="top">' . $currencies->format($order->products[$i]['final_price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</td>
			<td class="dataTableContent" align="right" valign="top"><b>' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</b></td>
		</tr>';
	}
?>
      <tr>
        <td colspan="3">
			<a href="javascript:window.print()"><img class="dontprint" src="/admin/includes/languages/english/images/buttons/button_print_invoice.gif" alt="Click to Print This Page" border="0"></a>
		</td>
		<td align="right" colspan="4" style="padding: 20px 0 0 0">
			<table border="0" cellspacing="0" cellpadding="3" align="right">
<?php
	for ($i = 0, $n = sizeof($order->totals); $i < $n; $i++) {
		echo '<tr>
				<td align="right" class="dataTableContent" style="padding: 3px 10px 3px 0; font:bold 13px arial">' . $order->totals[$i]['title'] . '</td>
				<td align="right" class="dataTableContent" width="20" nowrap  style="padding: 0; font:bold 13px arial">' . $order->totals[$i]['text'] . '</td>
			</tr>';
	}
?>
       </table></td>
      </tr>
    </table></td>
  </tr>
</table>

<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>