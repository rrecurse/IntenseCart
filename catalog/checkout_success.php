<?php


  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'order.php');
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_SUCCESS);

	// # if the customer session is not registered, redirect them to the shopping cart page
	if (!tep_session_is_registered('customer_id')) {
		tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
	}


	if(isset($_GET['action']) && ($_GET['action'] == 'update')) {

    $notify_string = 'action=notify&';

    $notify = $_POST['notify'];
    
if (!is_array($notify)) $notify = array($notify);
    for ($i=0, $n=sizeof($notify); $i<$n; $i++) {
      $notify_string .= 'notify[]=' . $notify[$i] . '&';
    }
    if (strlen($notify_string) > 0) $notify_string = substr($notify_string, 0, -1);

//    tep_redirect(tep_href_link(FILENAME_DEFAULT, $notify_string));
    tep_redirect(DIR_WS_HTTP_CATALOG.FILENAME_DEFAULT."?$notify_string");
  }
  

	$breadcrumb->add(NAVBAR_TITLE_1);
	$breadcrumb->add(NAVBAR_TITLE_2);

	$orders_query = tep_db_query("SELECT orders_id FROM " . TABLE_ORDERS . " WHERE customers_id = '" . (int)$customer_id . "' ORDER BY date_purchased DESC LIMIT 1");

	if(!isset($orders_id)) { 
		$orders_id = (tep_db_num_rows($orders_query) > 0 ? tep_db_result($orders_query,0) : '');
	}

	$order = new order($orders_id);

	$global_query = tep_db_query("SELECT global_product_notifications FROM " . TABLE_CUSTOMERS_INFO . " WHERE customers_info_id = '" . (int)$customer_id . "'");
	$global = tep_db_fetch_array($global_query);

	if($global['global_product_notifications'] != '1') {

		$products_array = array();

		$products_query = tep_db_query("SELECT op.products_id, 
											   op.products_name 
										FROM " . TABLE_ORDERS_PRODUCTS . " op 
										WHERE op.orders_id = '" . (int)$orders_id . "' 
										ORDER BY op.products_name
										");

		while ($products = tep_db_fetch_array($products_query)) {
			$products_array[] = array('id' => $products['products_id'], 'text' => $products['products_name']);
		}

		$total_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orders_id . "' AND class='ot_total'");
		$order_total=tep_db_fetch_array($total_query);

		$tax_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orders_id . "' AND class='ot_tax'");
		$order_tax = tep_db_fetch_array($tax_query);

		$shipping_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orders_id . "' AND class='ot_shipping'");
		$order_shipping = tep_db_fetch_array($shipping_query);
	}
?>
<!doctype HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
</head>
<body style="margin:0;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top">
    <table width="100%"><tr><td><?php echo tep_draw_form('order', tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'action=update', 'SSL')); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="4" cellpadding="2">
          <tr>
            <td valign="top"></td>
            <td valign="top"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?><div align="center" class="pageHeading"><?php echo HEADING_TITLE; ?></div>
<br><div><?php echo join('<br>',$order->getPurchaseInfo())?>
</div><br><?php echo TEXT_SUCCESS; ?><br><br>
<?php
  if ($global['global_product_notifications'] != '1') {
    echo TEXT_NOTIFY_PRODUCTS . '<br><p class="productsNotifications">';

    $products_displayed = array();
    for ($i=0, $n=sizeof($products_array); $i<$n; $i++) {
      if (!in_array($products_array[$i]['id'], $products_displayed)) {
        echo tep_draw_checkbox_field('notify[]', $products_array[$i]['id']) . ' ' . $products_array[$i]['text'] . '<br>';
        $products_displayed[] = $products_array[$i]['id'];
      }
    }

    echo '</p>';
  } else {
    echo TEXT_SEE_ORDERS . '<br><br>' . TEXT_CONTACT_STORE_OWNER;
  }
?>
            <h3><?php echo TEXT_THANKS_FOR_SHOPPING; ?></h3></td>
          </tr>
        </table></td>
      </tr>
<?php require('add_checkout_success.php'); //CCGV
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td align="center"><?php echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?></td>
      </tr>

<?php if (DOWNLOAD_ENABLED == 'true') include(DIR_WS_MODULES . 'downloads.php'); ?>

<?php
	// # PayPal WPP START
	if (tep_paypal_wpp_enabled()) {
    	if ($paypal_ec_temp) {
        	tep_session_unregister('customer_id');
        tep_session_unregister('customer_default_address_id');
	        tep_session_unregister('customer_first_name');
    	    tep_session_unregister('customer_country_id');
        	tep_session_unregister('customer_zone_id');
	        tep_session_unregister('comments');
    	    //$cart->reset();
	        tep_db_query("delete from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "'");
    	    tep_db_query("delete from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
        	tep_db_query("delete from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . (int)$customer_id . "'");
	        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "'");
    	    tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "'");
        	tep_db_query("delete from " . TABLE_WHOS_ONLINE . " where customer_id = '" . (int)$customer_id . "'");
	    }
  
    	tep_session_unregister('paypal_ec_temp');
	    tep_session_unregister('paypal_ec_token');
    	tep_session_unregister('paypal_ec_payer_id');
	    tep_session_unregister('paypal_ec_payer_info');
	}
// # END PayPal WPP


echo '</table></form>';

// # Google Universal Analytics Ecommerce tracking
if(GOOGLE_ANALYTICS_CONVERSION_ENABLE == 'true') include(DIR_WS_MODULES . 'analytics/analytics.php');

echo '<div style="display:none;">'; 


// # Google ADWORDS Code for Purchase Conversion Page
if (GOOGLE_CONVERSION_ENABLE=='true') { ?>

<script type="text/javascript">

/* <![CDATA[ */
var google_conversion_id = <?php echo GOOGLE_CONVERSION_CODE ?>;
var google_conversion_language = "en_US";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "p--DCLripQIQkJqu6QM";
var google_conversion_value = "<?php echo ($order_total['value'] > 0 ? number_format($order_total['value'],2) : '0');?>";
var google_conversion_currency = "USD";
var google_remarketing_only = false;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<img height=1 width=1 border=0 src="//www.googleadservices.com/pagead/conversion/1234567890/?value=10.0&label=Purchase&script=0">
</noscript>

<?php 
} // # END Google ADWORDS Code for Purchase Conversion

if(BING_CONVERSION_ENABLE=='true') { 

?>
	<script>
		(function(w,d,t,r,u){var f,n,i;w[u]=w[u]||[],f=function(){var o={ti:"<?php echo BING_CONVERSION_CODE ?>"};o.q=w[u],w[u]=new UET(o),w[u].push("pageLoad")},n=d.createElement(t),n.src=r,n.async=1,n.onload=n.onreadystatechange=function(){var s=this.readyState;s&&s!=="loaded"&&s!=="complete"||(f(),n.onload=n.onreadystatechange=null)},i=d.getElementsByTagName(t)[0],i.parentNode.insertBefore(n,i)})(window,document,"script","//bat.bing.com/bat.js","uetq");
	</script>

	<script>
		var uetq = uetq || [];
		uetq.push({ 'gv': '<?php $order_total['value']+0 ?>' }); // # The Goal Value 
	</script>

	<noscript>
		<img src="//bat.bing.com/action/0?ti=<?php echo BING_CONVERSION_CODE ?>&Ver=2&gv=<?php $order_total['value']+0 ?>" height="0" width="0" style="display:none; visibility: hidden;" />
	</noscript>

<?php 

} 	// # END Bind Ads Code for Conversion Tracking
 

if(PRICEGRABBER_CONVERSION_ENABLE =='true') include(DIR_WS_MODULES . 'analytics/pricegrabber.php');

if(SHOPPINGCOM_CONVERSION_ENABLE =='true') include(DIR_WS_MODULES . 'analytics/shoppingcom.php');

if(SHOPZILLACOM_CONVERSION_ENABLE =='true') include(DIR_WS_MODULES . 'analytics/shopzillacom.php');

if(NEXTAG_CONVERSION_ENABLE =='true') include(DIR_WS_MODULES . 'analytics/nextag.php'); 

if(AMAZON_PRODUCTADS_CONVERSION_ENABLE =='true') include(DIR_WS_MODULES . 'analytics/amazon-productsads.php'); 


?>

</div>


</td></tr></table>

</td>


    <td><table>

<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>

    </table>
    </td>
  </tr>
</table>
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
