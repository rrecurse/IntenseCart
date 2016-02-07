<?php

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  require(DIR_WS_CLASSES . 'phpqrcode.php');   


  $oID = (int)tep_db_prepare_input($_GET['oID']);
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
	.tabEven{background-color:#F3F3F3 !important;}
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
echo '<br><span style="font:normal 11px arial">Thank you for buying from '.STORE_NAME.' on the Amazon Marketplace</span>';
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
		
			$promocode_query = tep_db_query("SELECT title FROM orders_total WHERE orders_id = '".$oID."' AND class = 'ot_coupon'");

			if(tep_db_num_rows($promocode_query) > 0) { 
				$promocode = tep_db_result($promocode_query,0);

				$promocode = str_replace(array('Discount Coupons (',')', 'Discount Coupons:',':'), '', $promocode);
					if(!empty($promocode)) {
						echo '<br>Promo Code: &nbsp;<b>'. $promocode.'</b>';
					}
			}
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
	$amazonOrder_query = tep_db_query("SELECT ref_id FROM " . TABLE_PAYMENTS . " WHERE orders_id = '".$oID."' AND method = 'payment_amazonSeller'");
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
      <tr class="invoiceTableHeadingRow">
		<td class="invoiceTableHeadingContent" align="center">Image</td>
		<td class="invoiceTableHeadingContent" align="center">Qty.</td>
        <td class="invoiceTableHeadingContent" align="center"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
        <td class="invoiceTableHeadingContent" colspan="2" style="padding:0 10px;"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
      </tr>
<?php
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
  $the_image_query = tep_db_query("SELECT products_image FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . $order->products[$i]['id'] . "'");
  $the_image = tep_db_fetch_array($the_image_query);

      echo '      <tr class="dataTableRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'">' . "\n" .
		   '		<td class="invoiceTableContent" style="padding:5px; width:75px; padding: 5px 10px 5px 5px">'. tep_image(DIR_WS_CATALOG_IMAGES . $the_image['products_image'], SMALL_IMAGE_WIDTH * .65, SMALL_IMAGE_HEIGHT * .65).'</td>' . "\n" .
           '        <td class="invoiceTableContent" width="11%" align="center" style="padding:0 10px;"><b>' . $order->products[$i]['qty'] . '&nbsp;x </b></td>' . "\n" .
           '        <td class="invoiceTableContent" colspan="2" width="11%" nowrap style="padding:0 10px;" align="center">' . $order->products[$i]['model'] . '</td>' . "\n" .
           '        <td class="invoiceTableContent" width="100%" style="padding:0 0 0 10px;" width="60%">' . $order->products[$i]['name'];

      if (isset($order->products[$i]['attributes']) && (sizeof($order->products[$i]['attributes']) > 0)) {
        for ($j=0, $k=sizeof($order->products[$i]['attributes']); $j<$k; $j++) {
          echo '<br><nobr><small>&nbsp;<i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'];
          echo '</i></small></nobr>';
        }
      }

      echo '        </td>' . "\n" .
           '      </tr>' . "\n";
    }
?>
</table>
</td>
</tr>
  <!--tr>
    <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
  </tr>
  <tr>
    <td><table border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td class="main"><b><?php echo ENTRY_PAYMENT_METHOD; ?></b></td>
        <td class="main"><?php $paymod=IXmodule::module($order->info['payment_method']); echo isset($paymod)?$paymod->getName():$order->info['payment_method'] ?></td>
      </tr>
</table></td>
  </tr-->
<?php if(!empty($payments) && get_class($payments[0]) == 'payment_amazonSeller') { ?>
<tr>
<td style="padding:10px 5px; font:normal 11px arial; text-align:justify">
<b> Returning your item:</b><br><br>
Go to "Your Account" on Amazon.com, click "Your Orders" and then click the "seller profile" link for this order to get information about the return and refund policies that apply.<br><br>
Visit <a href="http://www.amazon.com/returns" target="_blank" style="text-decoration:underline; color:blue">http://www.amazon.com/returns</a> to print a return shipping label. Please have your order ID ready.
<br><br>
<b>Thanks for buying on Amazon Marketplace.</b>
To provide feedback for the seller please visit <a href="www.amazon.com/feedback" style="text-decoration:underline; color:blue">www.amazon.com/feedback</a>. To contact the seller, please visit Amazon.com and click on "Your Account" at the top of any page. In Your Account, go to the "Orders" section and click on the link "Leave seller feedback". Select the order or click on the "View Order" button. Click on the "seller profile" under the appropriate product. On the lower right side of the page under "Seller Help", click on "Contact this seller". 
</td>
</tr>
<?php } ?>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
