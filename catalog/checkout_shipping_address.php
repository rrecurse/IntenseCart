<?php

	require('includes/application_top.php');

	// # if the customer is not logged on, redirect them to the login page
	if (!tep_session_is_registered('customer_id')) {
    	$navigation->set_snapshot();
	    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
	}

	// # if there is nothing in the customers cart, redirect them to the shopping cart page

	if ($cart->count_contents() < 1) {
    	tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
	}

	// # needs to be included earlier to set the success message in the messageStack
	require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_SHIPPING_ADDRESS);

	require(DIR_WS_CLASSES . 'order.php');

	$order = new order;

	// # if the order contains only virtual products, forward the customer to the billing page as
	// # a shipping address is not needed
	if ($order->content_type == 'virtual') {
    
		if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
		$shipping = false;
		if (!tep_session_is_registered('sendto')) tep_session_register('sendto');
		$sendto = false;
		tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
	}

	$error = false;
	$process = false;
	
	if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'submit')) {

		// # process a new shipping address
    
		if (tep_not_null($HTTP_POST_VARS['firstname']) && tep_not_null($HTTP_POST_VARS['lastname']) && tep_not_null($HTTP_POST_VARS['street_address'])) {

			$process = true;

			if (ACCOUNT_GENDER == 'true') $gender = tep_db_prepare_input($HTTP_POST_VARS['gender']);
			if (ACCOUNT_COMPANY == 'true') $company = tep_db_prepare_input($HTTP_POST_VARS['company']);

			$firstname = tep_db_prepare_input($HTTP_POST_VARS['firstname']);
			$lastname = tep_db_prepare_input($HTTP_POST_VARS['lastname']);
			$street_address = tep_db_prepare_input($HTTP_POST_VARS['street_address']);
			if (ACCOUNT_SUBURB == 'true') $suburb = tep_db_prepare_input($HTTP_POST_VARS['suburb']);
			$postcode = tep_db_prepare_input($HTTP_POST_VARS['postcode']);
			$city = tep_db_prepare_input($HTTP_POST_VARS['city']);
			$country = tep_db_prepare_input($HTTP_POST_VARS['country']);

			if (ACCOUNT_STATE == 'true') {
				if (isset($HTTP_POST_VARS['zone_id'])) {
					$zone_id = tep_db_prepare_input($HTTP_POST_VARS['zone_id']);
				} else {
					$zone_id = false;
				}
			
				$state = tep_db_prepare_input($HTTP_POST_VARS['state']);
			}

			if (ACCOUNT_GENDER == 'true') {
				if ( ($gender != 'm') && ($gender != 'f') ) {
					$error = true;
					$messageStack->add('checkout_address', ENTRY_GENDER_ERROR);
				}
			}

			if(strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
				$error = true;
				$messageStack->add('checkout_address', ENTRY_FIRST_NAME_ERROR);
			}

			if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
				$error = true;
				$messageStack->add('checkout_address', ENTRY_LAST_NAME_ERROR);
			}

			if(strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
    	    	$error = true;
				$messageStack->add('checkout_address', ENTRY_STREET_ADDRESS_ERROR);
			}

			if(strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
    	    	$error = true;
				$messageStack->add('checkout_address', ENTRY_POST_CODE_ERROR);
			}

			if(strlen($city) < ENTRY_CITY_MIN_LENGTH) {
	        	$error = true;
				$messageStack->add('checkout_address', ENTRY_CITY_ERROR);
			}


			if (ACCOUNT_STATE == 'true') {
		
				if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
	
    	        	$error = true;
		            $messageStack->add('checkout_address', ENTRY_STATE_ERROR);		
				} else {

			    	$zone_check_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$country . "'");
				    $zone_check = (tep_db_num_rows($zone_check_query) > 0 ? tep_db_result($zone_check_query, 0) : 0);

    				$entry_state_has_zones = ($zone_check > 0 ? true : false);

	    			if ($entry_state_has_zones == true) {

						$zone_query = tep_db_query("SELECT DISTINCT zone_id FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$country . "' AND (zone_name LIKE '" . tep_db_input($state) . "%' OR zone_code LIKE '%" . tep_db_input($state) . "%')");

						$zone_id = (tep_db_num_rows($zone_query) > 0 ? tep_db_result($zone_query, 0) : 0);
	
						if ($zone_id == 0) {
    			    	    $error = true;
							$messageStack->add('checkout_address', ENTRY_STATE_ERROR_SELECT);
						}
					}
				}
			}
	 
			if ( (is_numeric($country) == false) || ($country < 1) ) {
		        $error = true;
    		    $messageStack->add('checkout_address', ENTRY_COUNTRY_ERROR);
			}

			if ($error == false) {
	
				$sql_data_array = array('customers_id' => $customer_id,
    	    	                        'entry_firstname' => $firstname,
        	    	                    'entry_lastname' => $lastname,
            	    	                'entry_street_address' => $street_address,
                	    	            'entry_postcode' => $postcode,
                    	    	        'entry_city' => $city,
                        	    	    'entry_country_id' => $country);

				if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
				if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $company;
				if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = $suburb;
		
				if (ACCOUNT_STATE == 'true') {

					if ($zone_id > 0) {
    		    
						$sql_data_array['entry_zone_id'] = $zone_id;
	    	    		$sql_data_array['entry_state'] = '';
	
			        } else {

		    	        $sql_data_array['entry_zone_id'] = '0';
		        	    $sql_data_array['entry_state'] = $state;
					}
				}

				// # fast easy checkout start

				if (!tep_session_is_registered('sendto')) tep_session_register('sendto');
			
				tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
		
				$sendto = tep_db_insert_id();

				if (!tep_session_is_registered('registered_now')) {  
		
					tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));  
		
				} else {
	
					tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
				}
			}
	
		// # process the selected shipping destination
    	} elseif (isset($HTTP_POST_VARS['address'])) {
    	
			$reset_shipping = false;

			if (tep_session_is_registered('sendto')) {
        
				if ($sendto != $HTTP_POST_VARS['address']) {

					if (tep_session_is_registered('shipping')) {
						$reset_shipping = true;
					}
				}

			} else {
				tep_session_register('sendto');
			}

			$sendto = $HTTP_POST_VARS['address'];

		   	$ship_address_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (int)$country . "'");
			$ship_address = (tep_db_num_rows($ship_address_query) > 0 ? tep_db_result($ship_address_query, 0) : 0);

			if ($ship_address > 0) {
	
				if (!tep_session_is_registered('registered_now')) {  
					tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
				} else {
					tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
				}

			} else {
				tep_session_unregister('sendto');
			}

		} else {
	
			if (!tep_session_is_registered('sendto')) tep_session_register('sendto');
			$sendto = $customer_default_address_id;
	
			if (!tep_session_is_registered('registered_now')) {  
				tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));  
			} else {
				tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
			}
		}
	}

	// # fast easy checkout end

	// # if no shipping destination address was selected, use their own address as default
	if (!tep_session_is_registered('sendto')) {
    	$sendto = $customer_default_address_id;
	}

	$breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
	$breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'));

	$addresses_count = tep_count_customer_address_book_entries();
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script language="javascript"><!--
var selected;

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
  if (document.checkout_address.address[0]) {
    document.checkout_address.address[buttonSelect].checked=true;
  } else {
    document.checkout_address.address.checked=true;
  }
}

function check_form_optional(form_name) {
  var form = form_name;

  var firstname = form.elements['firstname'].value;
  var lastname = form.elements['lastname'].value;
  var street_address = form.elements['street_address'].value;

  if (firstname == '' && lastname == '' && street_address == '') {
    return true;
  } else {
    return check_form(form_name);
  }
}
//--></script>
<?php require(DIR_WS_INCLUDES . 'form_check.js.php'); ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><?php echo tep_draw_form('checkout_address', tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL'), 'post', 'onSubmit="return check_form_optional(checkout_address);"'); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_delivery.gif', HEADING_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  if ($messageStack->size('checkout_address') > 0) {
?>
      <tr>
        <td><?php echo $messageStack->output('checkout_address'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }

  if ($process == false) {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TABLE_HEADING_SHIPPING_ADDRESS; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="50%" valign="top"><?php echo TEXT_SELECTED_SHIPPING_DESTINATION; ?></td>
                <td align="right" width="50%" valign="top"><table border="0" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main" align="center" valign="top"><?php echo '<b>' . TITLE_SHIPPING_ADDRESS . '</b><br>' . tep_image(DIR_WS_IMAGES . 'arrow_south_east.gif'); ?></td>
                    <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" valign="top"><?php echo tep_address_label($customer_id, $sendto, true, ' ', '<br>'); ?></td>
                    <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
    if ($addresses_count > 1) {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TABLE_HEADING_ADDRESS_BOOK_ENTRIES; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="50%" valign="top"><?php echo TEXT_SELECT_OTHER_SHIPPING_DESTINATION; ?></td>
                <td class="main" width="50%" valign="top" align="right"><?php echo '<b>' . TITLE_PLEASE_SELECT . '</b><br>' . tep_image(DIR_WS_IMAGES . 'arrow_east_south.gif'); ?></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php
		$radio_buttons = 0;

		$addresses_query = tep_db_query("SELECT address_book_id, 
												entry_firstname AS firstname, 
												entry_lastname AS lastname, 
												entry_company AS company, 
												entry_street_address AS street_address, 
												entry_suburb AS suburb, 
												entry_city AS city, 
												entry_postcode AS postcode, 
												entry_state AS state, 
												entry_zone_id AS zone_id, 
												entry_country_id AS country_id 
										  FROM " . TABLE_ADDRESS_BOOK . " 
										  WHERE customers_id = '" . (int)$customer_id . "'
										 ");

		if(tep_db_num_rows($addresses_query) > 0) {
			while ($addresses = tep_db_fetch_array($addresses_query)) {

		        $format_id = tep_get_address_format_id($addresses['country_id']);

				echo '<tr>
						<td>'. tep_draw_separator('pixel_trans.gif', '10', '1') .'</td>
						<td colspan="2">
							<table border="0" width="100%" cellspacing="0" cellpadding="2">';

				if ($addresses['address_book_id'] == $sendto) {
	
					echo '		<tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">';

				} else {

					echo '		<tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, ' . $radio_buttons . ')">';
				}
?>
									<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
									<td class="main" colspan="2"><b><?php echo tep_output_string_protected($addresses['firstname'] . ' ' . $addresses['lastname']); ?></b></td>
									<td class="main" align="right"><?php echo tep_draw_radio_field('address', $addresses['address_book_id'], ($addresses['address_book_id'] == $sendto)); ?></td>
									<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
								</tr>
								<tr>
					            	<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                					<td colspan="3">
										<table border="0" cellspacing="0" cellpadding="2">
											<tr>
								            	<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
            									<td class="main"><?php echo tep_address_format($format_id, $addresses, true, ' ', ', '); ?></td>
								            	<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
					            			</tr>
										</table>
									</td>
								<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
							</tr>
						</table>
					</td>
					<td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
				</tr>
<?php
			$radio_buttons++;
		} // # end while
	} // # end tep_db_num_rows
	
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
  }

  if ($addresses_count < MAX_ADDRESS_BOOK_ENTRIES) {
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TABLE_HEADING_NEW_SHIPPING_ADDRESS; ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main" width="100%" valign="top"><?php echo TEXT_CREATE_NEW_SHIPPING_ADDRESS; ?></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
              <tr>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td><?php require(DIR_WS_MODULES . 'checkout_new_address.php'); ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
                </table></td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td class="main"><?php echo '<b>' . TITLE_CONTINUE_CHECKOUT_PROCEDURE . '</b><br>' . TEXT_CONTINUE_CHECKOUT_PROCEDURE; ?></td>
                <td class="main" align="right"><?php echo tep_draw_hidden_field('action', 'submit') . tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?php
  if ($process == true) {
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><?php echo '<a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL') . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?></td>
      </tr>
<?php
  }
?>
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
            <td width="25%"><?php echo tep_draw_separator('pixel_silver.gif', '100%', '1'); ?></td>
            <td width="25%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td width="50%"><?php echo tep_draw_separator('pixel_silver.gif', '100%', '1'); ?></td>
                <td width="50%"><?php echo tep_draw_separator('pixel_silver.gif', '1', '5'); ?></td>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td align="center" width="25%" class="checkoutBarCurrent"><?php echo CHECKOUT_BAR_DELIVERY; ?></td>
            <td align="center" width="25%" class="checkoutBarTo"><?php echo CHECKOUT_BAR_PAYMENT; ?></td>
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
