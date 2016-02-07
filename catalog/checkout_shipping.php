<?php

  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  require('includes/classes/http_client.php');
  
  //---PayPal WPP Modification START ---//
    if (tep_paypal_wpp_enabled()) {
      //$ec_enabled = true;
      $ec_enabled = false;
    } else {
      $ec_enabled = false;
    }
    $messageStack->reset();
    if ($ec_enabled) {
      if (tep_session_is_registered('payment_error')) {
        $checkout_login = true;
        $messageStack->add('payment', $payment_error);
        tep_session_unregister('payment_error');
      }
    }
    $ec_checkout = true;
    if (!tep_session_is_registered('paypal_ec_token') && !tep_session_is_registered('paypal_ec_payer_id') && !tep_session_is_registered('paypal_ec_payer_info')) { 
      $ec_checkout = false;
    }
  //---PayPal WPP Modification END ---//

  if (tep_session_is_registered('pns_errors') && sizeof($pns_errors) > 0) {
    foreach ($pns_errors as $error) {
      $messageStack->add('payment', $error);
    }
    tep_session_unregister('pns_errors');
  }
  

  
  // register a random ID in the session to check throughout the checkout procedure
  // against alterations in the shopping cart contents
  if (!tep_session_is_registered('cartID')) tep_session_register('cartID');
  $cartID = $cart->cartID;
  
  require(DIR_WS_CLASSES . 'payment.php');
  if (!$payment && $ec_enabled) {
    $payment = 'paypal_wpp';
  }
  $payment_modules = new payment($payment);

  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();
  $total_ship_count = $cart->count_ship_contents(); // Free shipping per product 1.0
  

  
  //the next 4 lines are for ccgv
  /* require(DIR_WS_CLASSES . 'order_total.php');
  
  $order_total_modules = new order_total;
  $order_total_modules->collect_posts();
  $order_total_modules->pre_confirmation_check(); 
  */

  // # if the customer is not logged on, redirect them to the login page
  
  if (!tep_session_is_registered('customer_id')) {
    //$navigation->set_snapshot();
    
    tep_redirect(tep_href_link(FILENAME_CREATE_ACCOUNT, 'co=1', 'SSL'));
  }
  
  // if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }
  
  if (!tep_session_is_registered('shipping')) tep_session_unregister('shipping');
  
  // if no shipping destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('sendto')) {
    tep_session_register('sendto');
    $sendto = $customer_default_address_id;
  } else {

    // # verify the selected shipping address
    $ship_address_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int)$customer_id . "' AND address_book_id = '" . (int)$sendto . "'");
    $ship_address = (tep_db_num_rows($ship_address_query) > 0 ? tep_db_result($ship_address_query, 0) : 0);
    
    if ($ship_address == 0) {
      $sendto = $customer_default_address_id;
      if (tep_session_is_registered('shipping')) tep_session_unregister('shipping');
    }
  }
  // # if no billing destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('billto')) {
    tep_session_register('billto');
    $billto = $customer_default_address_id;
  } else {
    // # verify the selected billing address
    $bill_address_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int)$customer_id . "' AND address_book_id = '" . (int)$billto . "'");
    $bill_address = (tep_db_num_rows($bill_address_query) > 0 ? tep_db_result($bill_address_query, 0) : 0);
  
    if ($bill_address == 0) {
      $billto = $customer_default_address_id;
      if (tep_session_is_registered('payment')) tep_session_unregister('payment');
    }
  }


	// # register a random ID in the session to check throughout the checkout procedure
	// # against alterations in the shopping cart contents
/*
  if (!tep_session_is_registered('cartID')) tep_session_register('cartID');
  $cartID = $cart->cartID;
*/
	// # if the order contains only virtual products, forward the customer to the billing page as
	// # a shipping address is not needed

	if ($order->content_type == 'virtual') {
	
		if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
	    $shipping = false;
    	$sendto = false;

  }
  
	tep_session_unregister('billing');
  
	if(empty($order->delivery['country']['id'])) {

		$shipping_address_query = tep_db_query("SELECT ab.*, 
													   c.countries_id, 
													   c.countries_name, 
													   c.countries_iso_code_2, 
													   c.countries_iso_code_3, 
													   c.address_format_id, 
													   z.zone_name
												FROM " . TABLE_ADDRESS_BOOK . " ab 
												LEFT JOIN " . TABLE_ZONES . " z ON z.zone_id = ab.entry_zone_id
												LEFT JOIN " . TABLE_COUNTRIES . " c ON c.countries_id = ab.entry_country_id 
												WHERE ab.customers_id = '" . (int)$customer_id . "' 
												AND ab.address_book_id = '" . (int)$sendto . "'
												");

	if(tep_db_num_rows($shipping_address_query) > 0) { 

    	$shipping_address = tep_db_fetch_array($shipping_address_query);

	    $order->delivery = array('firstname' => $shipping_address['entry_firstname'],
    	                        'lastname' => $shipping_address['entry_lastname'],
        	                    'company' => $shipping_address['entry_company'],
            	                'street_address' => $shipping_address['entry_street_address'],
                	            'suburb' => $shipping_address['entry_suburb'],
                    	        'city' => $shipping_address['entry_city'],
                        	    'postcode' => $shipping_address['entry_postcode'],
                            	'state' => ((tep_not_null($shipping_address['entry_state'])) ? $shipping_address['entry_state'] : $shipping_address['zone_name']),
	                            'zone_id' => $shipping_address['entry_zone_id'],
    	                        'country' => array('id' => $shipping_address['countries_id'], 'title' => $shipping_address['countries_name'], 'iso_code_2' => $shipping_address['countries_iso_code_2'], 'iso_code_3' => $shipping_address['countries_iso_code_3']),
        	                    'country_id' => $shipping_address['entry_country_id'],
            	                'format_id' => $shipping_address['address_format_id']);  
		}
	}

	require(DIR_WS_CLASSES . 'shipping.php');
	$shipping_modules = new shipping;

	// # get all available shipping quotes
	$quotes = $shipping_modules->quote();

	// # if no shipping method has been selected, automatically select the cheapest method.
	// # if the modules status was changed when none were available, to save on implementing
	// # a javascript force-selection method, also automatically select the cheapest shipping
	// # method if more than one module is now enabled

	if ( !tep_session_is_registered('shipping') || ( tep_session_is_registered('shipping') && ($shipping == false) && (tep_count_shipping_modules() > 1) ) ) $shipping = $shipping_modules->cheapest();

require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PAYMENT);
require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_SHIPPING);

$breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));

echo 'CC: '.$order->info['cc_number'];

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">

<?php echo $payment_modules->javascript_validation(); ?>
<script language="javascript"><!--
var selected;

function selectRowEffect2(object, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected2';
  selected = object;

// one button is not an array
  if (document.checkout_payment.shipping[0]) {
    document.checkout_payment.shipping[buttonSelect].checked=true;
  } else {
    document.checkout_payment.shipping.checked=true;
  }
}

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}
//--></script>
<script type="text/javascript"><!--
var selected;
<?php//rmh M-S_ccgv begin ?>
var submitter = null;
function submitFunction() {
   submitter = 1;
   }
<?php//rmh M-S_ccgv end ?>
function selectRowEffect(object, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected = object;

// one button is not an array
  if (document.checkout_payment.payment[0]) {
    document.checkout_payment.payment[buttonSelect].checked=true;
  } else {
    document.checkout_payment.payment.checked=true;
  }
}

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}
//--></script>
</head>
<body style="margin:0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">

<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>

    </table></td>

    <td width="100%" valign="top"><?php echo tep_draw_form('checkout_payment', tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, 'action=process', 'SSL'), 'post', 'onsubmit="return check_form();"') . tep_draw_hidden_field('action', 'process'); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php
  if (isset($HTTP_GET_VARS['payment_error']) && is_object(${$HTTP_GET_VARS['payment_error']}) && ($error = ${$HTTP_GET_VARS['payment_error']}->get_error())) {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo tep_output_string_protected($error['title']); ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
        <table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBoxNotice">
          <tr class="infoBoxNoticeContents">
            <td>
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="100%" valign="top"><?php echo tep_output_string_protected($error['error']); ?></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table>
       </td>
          </tr>
        </table>
<?php
  }
?>

      </td>
        <td>
        <table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
               </tr>
        </table></td>
      </tr>
<? //---PayPal WPP Modification START ---// ?>
<?php
  if ($messageStack->size('payment') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('payment'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }
?>
<? //---PayPal WPP Modification END ---// ?>
<?php

  if (!tep_session_is_registered('registered_now')) {

?>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td>
			<table border="0" width="50%" cellspacing="0" cellpadding="2">
              <tr>

               <td align="left" valign="top">
			   <table border="0" cellspacing="0" cellpadding="2">
                  <tr>
				  <td class="main"><b><?php echo TABLE_HEADING_SHIPPING_ADDRESS; ?></b></td>
				  </tr>
				  <tr>
                   <td class="main" valign="top"><?php echo tep_address_label($customer_id, $sendto, true, ' ', '<br>'); ?></td>
                   </tr><tr>
				  <td class="main"  valign="top"><?php echo  '<a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL') . '">' . tep_image_button('button_change_address.gif', IMAGE_BUTTON_CHANGE_ADDRESS) . '</a>'; ?></td>
				  </tr>
                </table>
				</td> </tr>
                </table>
				</td>
                <td>
			    <table border="0" width="50%" cellspacing="0" cellpadding="2">
              <tr>
               <td align="right"  valign="top">
			   <table border="0" cellspacing="0" cellpadding="2">
                  <tr>
				 <td class="main"><b><?php echo TABLE_HEADING_BILLING_ADDRESS; ?></b></td>
				 </tr>
				 <tr>
                  <td class="main" valign="top"><?php echo tep_address_label($customer_id, $billto, true, ' ', '<br>'); ?></td></tr>
					<tr>
				  <td class="main"  valign="top"><?php echo '<a href="' . tep_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL') . '">' . tep_image_button('button_change_address.gif', IMAGE_BUTTON_CHANGE_ADDRESS) . '</a>'; ?></td>
              </tr>

                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php } ?>
<? //---PayPal WPP Modification START ---//-- ?>
<?php if (!$ec_enabled || isset($_GET['ec_cancel']) || (!tep_session_is_registered('paypal_ec_payer_id') && !tep_session_is_registered('paypal_ec_payer_info'))) { ?>
<? //---PayPal WPP Modification END ---//-- ?>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TABLE_HEADING_PAYMENT_METHOD; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  $selection = $payment_modules->selection();

  if (sizeof($selection) > 1) {

  
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" valign="top"><?php echo TEXT_SELECT_PAYMENT_METHOD; ?></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
  }
?>
<?php
  $radio_buttons = 0;
  for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
    if ( ($selection[$i]['id'] == $payment) || ($n == 1) ) {
      echo '                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')" style="background-color:#EFEFEF">' . "\n";
    } else {
      echo '                  <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')" style="background-color:#EFEFEF">' . "\n";
    }
?>
                    <td width="10"> </td>
                    <td class="main" align="right" style="width:10px">
<?php
    if (sizeof($selection) > 1) {
      echo '<input type="radio" name="payment" value="' . $selection[$i]['id'] . '"' . ($radio_buttons == 0 ? ' CHECKED' : '') . '>';
      //echo tep_draw_radio_field('payment', $selection[$i]['id'], ($radio_buttons == 0 ? 'true' : 'false'));
    } else {
      echo tep_draw_hidden_field('payment', $selection[$i]['id']);
    }
?>
                    </td>
                    <td class="main" colspan="3"><b><?php echo $selection[$i]['module']; ?></b></td>
                    <td width="10"> </td>
                  </tr>
<?php
    if (isset($selection[$i]['error'])) {
?>
                  <tr>
                    <td width="10"> </td>
                    <td class="main" colspan="4"><?php echo $selection[$i]['error']; ?></td>
                    <td width="10"> </td>
                  </tr>
<?php
    } elseif (isset($selection[$i]['fields']) && is_array($selection[$i]['fields'])) {
?>
                  <tr>
                    <td width="10"> </td>
                    <td colspan="4"><table border="0" cellspacing="0" cellpadding="2">
<?php
      for ($j=0, $n2=sizeof($selection[$i]['fields']); $j<$n2; $j++) {
?>
                      <tr>
                        <td width="10"> </td>
                        <td class="main"><?php echo $selection[$i]['fields'][$j]['title']; ?></td>
                        <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                        <td class="main"><?php echo $selection[$i]['fields'][$j]['field']; ?></td>
                        <td width="10"> </td>
                      </tr>
<?php
      }
?>
                    </table></td>
                    <td width="10"> </td>
                  </tr>
<?php
    }
?>
                </table></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
              <tr><td style="height:20px"></td></tr>
<?php
    $radio_buttons++;
  }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
<? //---PayPal WPP Modification START ---//-- ?>
<?php } ?>
<? //---PayPal WPP Modification END ---//-- ?>
       <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<? if (MODULE_PAYMENT_STORED_CC_STATUS == 'True') { ?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>

            <td class="main"><b>Purchase Options</b></td>
          </tr>
        </table></td>
      </tr>
       <tr>
        <td style="padding-left:10px"><input type="checkbox" name="storecard" value="yes" CHECKED> Please store my financial information to make my next visit even easier.</td>
       </tr>
       <tr>
        <td><?php //echo tep_draw_separator('pixel_trans.gif', '100%', 20'); ?></td>
      </tr>
<?php
  } else {
    tep_draw_hidden_field('storecard', 'no');
  }
  if (tep_count_shipping_modules() > 0) {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TABLE_HEADING_SHIPPING_METHOD; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
    if (sizeof($quotes) > 1 && sizeof($quotes[0]) > 1) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="50%" valign="top"><?php echo TEXT_CHOOSE_SHIPPING_METHOD; ?></td>
                <td class="main" width="50%" valign="top" align="right"><?php echo '<b>' . TITLE_PLEASE_SELECT . '</b><br>' . tep_image(DIR_WS_IMAGES . 'arrow_east_south.gif'); ?></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
    } elseif ($free_shipping == false) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="100%" colspan="2"></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
    }

 

    if ($free_shipping == true) {

?>
              <tr>
           		<td width="10"></td>
                <td colspan="2" width="100%">
					<table border="0" width="100%" cellspacing="0" cellpadding="2">
	                  <tr>
    	                <td width="10"></td>
        	            <td class="main" colspan="3"><b><?php echo FREE_SHIPPING_TITLE; ?></b>&nbsp;<?php echo $quotes[$i]['icon']; ?></td>
            	        <td width="10"></td>
                	  </tr>
	                  <tr id="defaultSelected" class="moduleRowSelected">
    	                <td width="10"></td>
        	            <td class="main" width="100%">
<?php 

	// # strip any added html tags to the currency class (like structured data spans and meta tags).
	$freeShipThreshold = $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER);
	$freeShipThreshold = strip_tags(trim($freeShipThreshold));

	echo sprintf(FREE_SHIPPING_DESCRIPTION, $freeShipThreshold) . tep_draw_hidden_field('shipping', 'free_free'); 

?>
					</td>
                    <td width="10"></td>
                  </tr>
                </table></td>
                <td width="10"></td>
              </tr>
<?php
    } else {
      $radio_buttons = 0;
      for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
?>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10"></td>
                    <td class="main" colspan="3"><b><?php echo $quotes[$i]['module']; ?></b>&nbsp;<?php if (isset($quotes[$i]['icon']) && tep_not_null($quotes[$i]['icon'])) { echo $quotes[$i]['icon']; } ?></td>
                    <td width="10"></td>
                  </tr>
<?php
        if (isset($quotes[$i]['error'])) {
?>
                  <tr>
                    <td width="10"></td>
                    <td class="main" colspan="3"><?php echo $quotes[$i]['error']; ?></td>
                    <td width="10"> </td>
                  </tr>
<?php
        } else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
// set the radio button to be checked if it is the method chosen
            $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $shipping['id']) ? true : false);

            if ( ($checked == true) || ($n == 1 && $n2 == 1) ) {
              echo '                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect2(this, ' . $radio_buttons . ')">' . "\n";
            } else {
              echo '                  <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect2(this, ' . $radio_buttons . ')">' . "\n";
            }
?>
                    <td width="10"> </td>
                    <td class="main" width="75%"><?php echo $quotes[$i]['methods'][$j]['title']; ?></td>
<?php
            if ( ($n > 1) || ($n2 > 1) ) {

				// # strip any added html tags to the currency class (like structured data spans and meta tags).
				$theAmount = $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0)));
				$theAmount = strip_tags(trim($theAmount));
?>
				<td class="main"><?php echo $theAmount ?></td>
				<td class="main" align="right"><?php echo tep_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked); ?></td>
<?php
            } else {

				// # strip any added html tags to the currency class (like structured data spans and meta tags).
				$theAmount = $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax']));
				$theAmount = strip_tags(trim($theAmount));
?>
                    <td class="main" align="right" colspan="2"><?php echo  $theAmount . tep_draw_hidden_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']); ?></td>
<?php
            }
?>
                    <td width="10"> </td>
                  </tr>
<?php
            $radio_buttons++;
          }
        }
?>
                </table></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
      }
    }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }
?>
<?php
 // echo $order_total_modules->credit_selection();//rmh M-S_ccgv
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr><tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TABLE_HEADING_COMMENTS; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td><?php echo tep_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"> </td>
                <td class="main"><b><?php echo TITLE_CONTINUE_CHECKOUT_PROCEDURE . '</b><br>' . TEXT_CONTINUE_CHECKOUT_PROCEDURE; ?></td>
                <td class="main" align="right"><?php echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?></td>
                <td width="10"> </td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td width="25%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td width="50%" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'checkout_bullet.gif'); ?></td>
                <td width="50%"><?php echo tep_draw_separator('pixel_silver.gif', '100%', '1'); ?></td>
              </tr>
            </table></td>
            <td width="25%"><?php echo tep_draw_separator('pixel_silver.gif', '100%', '1'); ?></td>
            <td width="25%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td width="50%"><?php echo tep_draw_separator('pixel_silver.gif', '100%', '1'); ?></td>
                <td width="50%"><?php echo tep_draw_separator('pixel_silver.gif', '1', '5'); ?></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td align="center" width="25%" class="checkoutBarCurrent">Shipping & Payment Methods</td>
            <td align="center" width="25%" class="checkoutBarTo"><?php echo CHECKOUT_BAR_CONFIRMATION; ?></td>
            <td align="center" width="25%" class="checkoutBarTo"><?php echo CHECKOUT_BAR_FINISHED; ?></td>
          </tr>
        </table></td>
      </tr>
    </table></form></td>
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
