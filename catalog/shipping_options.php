<?php

	require('includes/application_top.php');
	global $total_weight, $free_shipping;
	
	$box_weight = (SHIPPING_BOX_WEIGHT > 0 ? SHIPPING_BOX_WEIGHT : 0);

	$total_weight = $cart->show_multi_weight_line() + $box_weight;

	$free_shipping = $cart->free_shipping;

	//if (isset($_GET['weight'])) $total_weight=$_GET['weight'];
	//if (isset($_GET['free'])) $free_shipping=$_GET['free']+0;

	$def0 = '';

	if (isset($_GET['d'])) {
    	$def0 = $HTTP_GET_VARS['d'];
	}

	require(DIR_WS_LANGUAGES . $language . '/checkout_shipping.php');

	require(DIR_WS_CLASSES . 'order.php');
	$order = new order($cart);

	tep_session_unregister('shipping');
	$_SESSION['shipping_options'] = array();
	$setShippingMethod = '';

	foreach ($order->delivery AS $k => $v) {
    	$order->delivery[$k] = '';
	}

if (isset($_GET['zip']) && strlen($_GET['zip']) >= 4) {
    $order->delivery['postcode'] = '';
    if ($_GET['zip'] != $order->delivery['postcode']) {
        $order->delivery['postcode'] = tep_db_prepare_input($_GET['zip']);
    }
    $ship_postcode = $_GET['zip'];
    if (!tep_session_is_registered('ship_postcode')) {
        tep_session_register('ship_postcode');
    }
} else {
    $order->delivery['postcode'] = '';
}
if (isset($_POST['street_address'])) {
    $order->delivery['street_address'] = $_POST['street_address'];
}
if (isset($_POST['city'])) {
    $order->delivery['city'] = $_POST['city'];
}
if (isset($_POST['state'])) {
    $order->delivery['state'] = preg_match('/^\d+$/', $_POST['state']) ? tep_db_read(
        "SELECT zone_name FROM " . TABLE_ZONES . " WHERE zone_id='" . $_POST['state'] . "'",
        null,
        'zone_name'
    ) : $_POST['state'];
}

if (isset($_GET['zone'])) {
    $ship_zone_id = $_GET['zone'];
    if (!tep_session_is_registered('ship_zone_id')) {
        tep_session_register('ship_zone_id');
    }
}

if (is_numeric($_GET['cnty']) && $_GET['cnty'] > 0) {
    $ship_country = $_GET['cnty'];
    if (!tep_session_is_registered('ship_country')) {
        tep_session_register('ship_country');
    }
    if ($_GET['cnty'] != $order->delivery['country']['id']) {
        $countryID = tep_db_prepare_input($_GET['cnty']);

        $country_query = tep_db_query("SELECT countries_name, countries_iso_code_2, countries_iso_code_3 
									   FROM " . TABLE_COUNTRIES . " 
									   WHERE countries_id = '" . (int)$countryID . "'
									  ");

        if (tep_db_num_rows($country_query) > 0) {

            $country = tep_db_fetch_array($country_query);

            $order->delivery['country'] = array(
                'id' => $countryID,
                'title' => $country['countries_name'],
                'iso_code_2' => $country['countries_iso_code_2'],
                'iso_code_3' => $country['countries_iso_code_3']
            );
        }
    }
}


if (strlen($order->delivery['postcode']) >= 4 && is_numeric($order->delivery['country']['id'])) {
    require(DIR_WS_CLASSES . 'shipping.php');

    $shipping_modules = new shipping;
    $quotes = $shipping_modules->quote();

    //$shipTitle ='UPS Ground, Est Delivery Date: August 31 2012';
    $shipTitle = $quotes[0]['methods'][0]['title'];
    $allowedShippingMethods = array('Ground', 'Best', 'Priority', 'Zipcode', 'Table', 'Flat', 'Per','SurePost');
    $validPromoMethods = preg_match("/" . implode("|", $allowedShippingMethods) . "/i", $shipTitle);

    $noFreeShipZones = array(
        21 //Hawaii
        , 2 //Alaska
        , 52 //Puerto Rico
    );

    $allow_free_shipping = 
        $validPromoMethods && (
			($order->delivery['country']['id'] != STORE_COUNTRY && FREE_SHIPPING_TO_ALL_COUNTRIES == "false")
            OR ($order->delivery['country']['id'] == STORE_COUNTRY && !in_array($ship_zone_id, $noFreeShipZones))
        );

	if($allow_free_shipping === false) { 
		$free_shipping = false;
	}



    $coupon_free_shipping = false;

    if(!empty($_SESSION['cc_id'])) {

        // # if coupon id is for free shipping ensure user selected UPS Ground shipping service
        // # Get the coupon info from the db.
        $coupon_info_query = tep_db_query("SELECT * FROM coupons WHERE coupon_id = '" . (int)$_SESSION['cc_id'] . "'");


        if(tep_db_num_rows($coupon_info_query) > 0) {
			
			$coupon_info = tep_db_fetch_array($coupon_info_query);

            $coupon_free_shipping = 
                $allow_free_shipping
                && $coupon_info['coupon_type'] == 'S' 
                && $coupon_info['coupon_active'] == 'Y' 
                && (float)$order->info['total'] >= (float)$coupon_info['coupon_minimum_order'];
        }
    }


    $pkg_list = array();

    foreach ($shipping_modules->weight_list AS $w) {
        if ($w['weight'] > 0) {
            $pkg_list[] = ($w['qty'] == 1 ? $w['weight'] : $w['qty'] . '&nbsp;x&nbsp;' . $w['weight']) . '&nbsp;lbs';
        }
    }

if(!empty($pkg_list)) { 
	echo 'Package(s) weight: <b>'. join(',' , $pkg_list).'</b><br>';
}
?>

    <table width="100%" border="0" cellpadding="5" cellspacing="0">
<?php

	if (sizeof($quotes) > 1 && sizeof($quotes[0]) > 1) {
?>
            <tr>
                <td colspan="3"><?php echo TEXT_CHOOSE_SHIPPING_METHOD; ?></td>
            </tr>
<?php
	} elseif (!$free_shipping) {
?>
            <tr>
                <td></td>
                <td class="main"></td>
                <td></td>
            </tr>
<?php

}
		if($free_shipping || $coupon_free_shipping){
            $setShippingMethod = $quotes[0]['id'] . '_' . $quotes[0]['methods'][0]['id'];
            $_SESSION['shipping_options'][$quotes[0]['id']] = array(
                $quotes[0]['methods'][0]['id'] => array(
                    'name' => $quotes[0]['methods'][0]['title'],
                    'cost' => ($free_shipping ? 0 : number_format($quotes[0]['methods'][0]['cost'], 2, '.', ''))
                )
            );

            if (!tep_session_is_registered('shipping')) {
                tep_session_register('shipping');
            }
            $shipping = array(
                'id' => $setShippingMethod,
				'title' => $quotes[0]['methods'][0]['title'], 
				'cost' => 0
            );
            
            if ($coupon_free_shipping){
                $description = sprintf(
                    FREE_SHIPPING_DESCRIPTION . ' by using promo code: &nbsp;<b>' . $coupon_info['coupon_code'] . '</b>&nbsp; - <a href="' . $parentpage . '?removecode=1" class="removecode">Remove code</a>'
                    , $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)
                );

            } else {

                $description = sprintf(
                    FREE_SHIPPING_DESCRIPTION
                    , $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)
                );
            }
            ?>
            <tr>
                <td colspan="3">
                    <table border="0" width="100%" cellspacing="0" cellpadding="5">
                        <tr>
                            <td><b><?php echo FREE_SHIPPING_TITLE; ?></b> <?php echo $quotes[$i]['icon']; ?></td>
                        </tr>
                        <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)"
                            onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, 's', 0)">
                            <td><?php echo $description ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
<?php
        } else {
            $radio_buttons = 0;
            for ($i = 0, $n = sizeof($quotes); $i < $n; $i++) {
                ?>
                <tr>
                    <td colspan="3">
                        <table border="0" width="100%" cellspacing="0" cellpadding="5"
                               style="border:1px dotted #999999">
                            <tr>

                                <td colspan="4" style="background-color:#e3e3e3">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                           style="border-bottom:1px dotted #999999">
                                        <tr>
                                            <td style="padding:10px 0 10px 10px;">
                                                <b><?php echo $quotes[$i]['module']; ?></b></td>
                                            <td align="right">
                                            <?php if (isset($quotes[$i]['icon']) && tep_not_null($quotes[$i]['icon'])) {
                                                    echo $quotes[$i]['icon'];
                                            } ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <?php
                            if (isset($quotes[$i]['error'])) {
                                ?>
                                <tr>
                                    <td class="main" colspan="3"><?php echo $quotes[$i]['error']; ?></td>
                                </tr>
                            <?php
                            } else {
                                for ($j = 0, $n2 = sizeof($quotes[$i]['methods']); $j < $n2; $j++) {
									// # set the radio button to be checked if it is the method chosen
                                    $shp = $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'];
                                    if (!isset($shipping['id'])) {
                                        $checked = true;
                                        if (!tep_session_is_registered('shipping')) {
                                            tep_session_register('shipping');
                                        }
                                        $shipping = array();
                                        $shipping['id'] = $shp;
                                        $shipping['title'] = $quotes[$i]['methods'][$j]['title'];
                                        $shipping['cost'] = $quotes[$i]['methods'][$j]['cost'];
                                    } else {
                                        $checked = ($shp == $shipping['id']);
                                    }

                                    if (!$setShippingMethod || $checked) {
                                        $setShippingMethod = $shp;
                                    }

                                    ?>
                                    <tr class="moduleRow">
                                    <td class="main" style="padding:0 0 0 15px"><?php echo $quotes[$i]['methods'][$j]['title']; ?></td>
                                    <?php
                                    if (($n > 1) || ($n2 > 1)) {
                                        ?>
                                        <td class="main"><?php echo $currencies->format(
                                                tep_add_tax(
                                                    $quotes[$i]['methods'][$j]['cost'],
                                                    (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0)
                                                )
                                            ); ?></td>
                                        <td align="right"><?php echo tep_draw_radio_field(
                                                'shipping_null',
                                                $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'],
                                                $checked,
                                                'onClick="setShipping(this.value);"'
                                            ); ?></td>

                                    <?php
                                    } else {

                                        ?>
                                        <td class="main" align="right" colspan="3"><?php echo $currencies->format(
                                                    tep_add_tax($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax'])
                                                ) . tep_draw_hidden_field(
                                                    'shipping_null',
                                                    $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']
                                                ); ?></td>

                                    <?php
                                    }
                                    ?>

                                    </tr>
                                    <?php
                                    if (!isset($_SESSION['shipping_options'][$quotes[$i]['id']])) {
                                        $_SESSION['shipping_options'][$quotes[$i]['id']] = array();
                                    }
                                    $_SESSION['shipping_options'][$quotes[$i]['id']][$quotes[$i]['methods'][$j]['id']] = array(
                                        'name' => $quotes[$i]['methods'][$j]['title'],
                                        'cost' => number_format($quotes[$i]['methods'][$j]['cost'], 2, '.', '')
                                    );
                                    $radio_buttons++;
                                }
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            <?php
            }
        }
        ?>
    </table>
    <script type="text/javascript">
        setShipping('<?php echo $setShippingMethod?>');
    </script>
<?php
} else {
    ?>
    <table border="0" width="100%" height="80" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" valign="middle" class="main" style="font-weight: bold">Please select your shipping country and zip code above to view shipping rates.</td>
        </tr>
    </table>
<?php
}
?>

