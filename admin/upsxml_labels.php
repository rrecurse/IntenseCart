<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	global $total_weight,$free_shipping;

	chdir('../catalog/');
	require('includes/application_top.php');

	require(DIR_WS_CLASSES . 'order.php');
	$order = new order($_GET['oID']);

	$def = (!empty($_GET['shipping_method'])) ? $_GET['shipping_method'] : '';
	$shipMethodServiceCode = (!empty($_GET['serviceCode'])) ? $_GET['serviceCode'] : '';
	$total_weight = (!empty($_GET['weights']) && $_GET['weights'] > 0) ? (float)$_GET['weights'] : (float)SHIPPING_BOX_WEIGHT;
	$package_type = (!empty($_GET['package_type'])) ? $_GET['package_type'] : 'Customer Supplied';

	$warehouse = (!empty($_GET['warehouse'])) ? $_GET['warehouse'] : '1';

	// # grab warehouse details for shipment:
	$warehouse_query = tep_db_query("SELECT * FROM products_warehouse_profiles WHERE products_warehouse_id = '". (int)$warehouse."'");
	$warehouse = tep_db_fetch_array($warehouse_query);

	$shipFrom_country = $warehouse['products_warehouse_country'];

	// # double check warehouse shipping country is iso_2 code format, if not grab it from countries table.
	if(strlen($shipFrom_country) > 2) { 
		$shipFrom_country = tep_db_result(tep_db_query("SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " WHERE countries_name LIKE '".$shipFrom_country."'"),0);
	}

	// # convert delivery country name to iso_2 couuntry code
	$country_query = tep_db_query("SELECT countries_iso_code_2 AS country_code FROM " . TABLE_COUNTRIES . " WHERE countries_name LIKE '".$order->delivery['country']."' LIMIT 1");
	$country = tep_db_fetch_array($country_query);
	$shipTo_country = $country['country_code'];


	// # check if method passed is a UPS method - if not default to one.
	if(substr($def, 0, 7) != 'upsxml_') { 
	
		$def = 'non_UPS';
	}

	// # detect international
	$international = false;
	if($shipTo_country != $shipFrom_country) { 
		$international = true;
	}

	// # map amazon shipping methods to UPS naming convention
	$methodMap = array('Standard Ground', 'APO/FPO - Armed Forces', 'Expedited Delivery', 'non_UPS');

	if(MODULE_SHIPPING_UPSXML_SUREPOST == 'True' && $international === false) {

		$UPSmethodMap = array('upsxml_UPS SurePost', 'upsxml_UPS SurePost', 'upsxml_UPS 3 Day Select', 'upsxml_UPS SurePost');

	} else if(MODULE_SHIPPING_UPSXML_SUREPOST == 'True' && $international === true && $shipTo_country != 'PR') { 

		$UPSmethodMap = array("Worldwide Expedited", "Worldwide Expedited", "upsxml_UPS Saver", "upsxml_UPS Worldwide Expedited");

	} else if(MODULE_SHIPPING_UPSXML_SUREPOST == 'True' && $international === true && $shipTo_country == 'PR') { 

		$UPSmethodMap = array('upsxml_UPS SurePost', 'upsxml_UPS SurePost', 'upsxml_UPS 3 Day Select', 'upsxml_UPS SurePost');


	} else if(MODULE_SHIPPING_UPSXML_SUREPOST != 'True' && $international === false) { 

		$UPSmethodMap = array("upsxml_UPS Ground", "upsxml_UPS Ground", "upsxml_UPS 3 Day Select", "upsxml_UPS Ground");

	} 

	$def = str_replace($methodMap, $UPSmethodMap, $def);

	// # create the array with passed methods and any overrides from default method passed.
	// # usage: array(PACKAGE TYPE CODE, SHIPPING METHOD)
	$method = array($def, $total_weight, $package_type);

	// # Add service options
	$delivery_confirmation = (!empty($_GET['delivery_confirmation']) && $_GET['delivery_confirmation'] == 1) ? 1 : 0;
	$insurance_value = (!empty($_GET['insurance_value']) && $_GET['insurance_value'] > 0) ? number_format($_GET['insurance_value'],2) : '0';
	$UPS_notification = (!empty($_GET['UPS_notification']) && $_GET['UPS_notification'] == 1) ? 1 : 0;

	$print_type = (!empty($_GET['print_type'])) ? $_GET['print_type'] : 'gif';

	$options = array($delivery_confirmation, $insurance_value, $UPS_notification, $print_type);

	// # Add dimensional support
	$package_height = (!empty($_GET['package_height']) && $_GET['package_height'] > 0) ? $_GET['package_height'] : 0;
	$package_width = (!empty($_GET['package_width']) && $_GET['package_width']) ? $_GET['package_width'] : 0;
	$package_length = (!empty($_GET['package_length']) && $_GET['package_length']) ? $_GET['package_length'] : 0; // # also known as depth

	$dimensions = array($package_height,$package_width,$package_length);

	$use_negotiated_rates = true;

	require(DIR_WS_LANGUAGES . $language . '/checkout_shipping.php');

	require(DIR_WS_CLASSES . 'upsxml_labels.php');

	if(isset($_GET['genLabel'])) {
		// # create new class object
		// # usage: UPSLabelGen(ORDER ID, ARRAY WITH METHODS, SUPPLIER ID, PACKAGE OPTIONS, DIMENSIONS);
		$upsxml_requestLabel = new UPSLabelGen($_GET['oID'], $method, '1', $options, $dimensions);
		$upsxml_requestLabel->ConfirmRequest($_GET['oID']);
		exit();
	} else if(isset($_GET['printLabel'])) {
		$upsxml_printLabel = new UPSLabelGen($_GET['oID'], $method, '1', $options, $dimensions);
		$upsxml_printLabel->printLabel($oID, $_GET['tracking']);
		exit();
	} else if(isset($_GET['recoverLabel'])) {
		$upsxml_recoverLabel = new UPSLabelGen($_GET['oID'], $method, '1', $options, $dimensions);
		$upsxml_recoverLabel->labelRecovery($oID, $_GET['tracking']);
		exit();
	} else if(isset($_GET['voidLabel'])) {
		$upsxml_voidLabel = new UPSLabelGen($_GET['oID'], $method, '1', $options, $dimensions);
		$upsxml_voidLabel->voidLabel($oID, $_GET['tracking'],$_GET['tracking']);
		exit();
	}


  tep_session_unregister('shipping');
  $_SESSION['shipping_options'] = array();
  
  if (isset($_GET['zip'])) {
    $order->delivery['postcode'] = tep_db_prepare_input($_GET['zip']);
    $ship_postcode=$_GET['zip'];
  }

  if (isset($_GET['zone'])) {
    $ship_zone_id=$_GET['zone'];
    if (!tep_session_is_registered('ship_zone_id')) tep_session_register('ship_zone_id');
  }
  
  if (isset($_GET['cnty'])) {
    $country_query = tep_db_query("select countries_id, countries_name, countries_iso_code_2, countries_iso_code_3 from " . TABLE_COUNTRIES . " where countries_name = '" . $_GET['cnty'] . "'");
    if (tep_db_num_rows($country_query) > 0) {
      $country = tep_db_fetch_array($country_query);
      $order->delivery['country'] = array('id' => $country['countries_id'], 'title' => $country['countries_name'], 'iso_code_2' => $country['countries_iso_code_2'], 'iso_code_3' => $country['countries_iso_code_3']);
    }
  }

  if (isset($_GET['state'])) {
    $order->delivery['state'] = tep_db_prepare_input($_GET['state']);
  }

  if (isset($_GET['subtotal'])) {
    $order->info['subtotal'] = tep_db_prepare_input($_GET['subtotal']);
  }
  
  if (strlen($order->delivery['postcode']) >= 5 && is_numeric($order->delivery['country']['id'])) {

    $free_shipping=$total_weight=='';
  
    require(DIR_WS_CLASSES . 'shipping.php');
    
    $shipping_modules = new shipping();
    $quotes = $shipping_modules->quote('','upsxml', $options, $use_negotiated_rates);

      $pkg_list=Array();
      foreach ($shipping_modules->weight_list AS $w) {
        if ($w['weight']>0) {
			$pkg_list[] = ($w['qty'] == 1 ? $w['weight'] : $w['qty'].'&nbsp;x&nbsp;'.$w['weight']).'&nbsp;lbs';
		}
      }
//var_dump($shipping_modules->weight_list);
?>

<script type="text/javascript">
  shipData=new Array();
</script>

<table border="0" width="100%" cellspacing="0" cellpadding="0" style="padding: 10px 0" id="quoteTable_UPS">

<?php

	if($free_shipping == true) {

		$def = $quotes[0]['id'].'_'.$quotes[0]['methods'][0]['id'];
	$_SESSION['shipping_options'][$quotes[0]['id']] = array($quotes[0]['methods'][0]['id'] => array('name' => $quotes[0]['methods'][0]['title'], 'cost' => $free_shipping?0:$quotes[$i]['methods'][$j]['cost']));
	if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
	$shipping = array(id => $def, title => $quotes[0]['methods'][0]['title'], cost => 0);
?>
              <tr>
                <td>
<script type="text/javascript">
	shipData['<?php echo $def?>'] = { title: <?php echo tep_js_quote($quotes[0]['methods'][0]['title'])?>, value: '0' }; 
</script>
				</td>
                <td colspan="2" width="100%">

<table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" colspan="3"><b><?php echo FREE_SHIPPING_TITLE; ?></b>&nbsp;<?php echo $quotes[$i]['icon']; ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
                  <tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, 's', 0)">
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                    <td class="main" width="100%"><?php echo sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . tep_draw_hidden_field('shipping_null', 'free_free'); ?></td>
                    <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                  </tr>
                </table>

</td>
                <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
<?php

 } else { // # else not free shipping! (should never be free shipping whren using this quote tool in the admin!);

      $radio_buttons = 0;
      for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
?>
              <tr>
                <td colspan="3">
<script type="text/javascript">
jQuery.noConflict();
jQuery('#label_box').show();
//	jQuery('#package_type, #delivery_confirmation, #insurance_value, #UPS_notification, #package_height, #package_width, #package_length, input[name="print_type"]').val('').removeAttr('checked');


var typingTimer;                // # timer identifier
var doneTypingInterval = 1500;  // # time in ms, 5 second for example
var weight = parseFloat(jQuery('#weight_override').val()).toFixed(2);

jQuery('#weight_override').keyup(function(){
    clearTimeout(typingTimer);
    if (jQuery('#weight_override').val()) {
        typingTimer = setTimeout(doneTyping, doneTypingInterval);
    } 
});


jQuery('.up').click(function(){
    jQuery('#weight_override').val(parseFloat(jQuery('#weight_override').val())+0.50);
    clearTimeout(typingTimer);

	weight = parseFloat(jQuery('#weight_override').val()).toFixed(2);
	if(isNaN(weight) || weight < 0.01) weight = parseFloat(jQuery('#weight_override').val('0.01')).toFixed(2);
    if (jQuery('#weight_override').val()) {
        typingTimer = setTimeout(doneTyping, doneTypingInterval);
    }
});

jQuery('.down').click(function(){

    jQuery('#weight_override').val(parseFloat(jQuery('#weight_override').val())-0.50);
    clearTimeout(typingTimer);
	 weight= parseFloat(jQuery('#weight_override').val()).toFixed(2);
	if(isNaN(weight) || weight < 0.01) weight = parseFloat(jQuery('#weight_override').val('0.01')).toFixed(2);
    if (jQuery('#weight_override').val()) {
        typingTimer = setTimeout(doneTyping, doneTypingInterval);
    }
});


// # user is "finished typing," do something
function doneTyping () {
	jQuery('#weight_override').val(parseFloat(jQuery('#weight_override').val()).toFixed(2));
	if(isNaN(weight) || weight < 0.01) weight = parseFloat(jQuery('#weight_override').val('0.01')).toFixed(2);
	 weight = jQuery('#weight_override').val();
	reloadShipping(true,weight);


	var package_type = jQuery('#package_type').val();

    if(jQuery('input[name=delivery_confirmation]').is(':checked')) {
		var delivery_confirmation = 1;
    } else {
		var delivery_confirmation = 0;
    }
	var insurance_value = jQuery('#insurance_value').val();
	var package_height = jQuery('#package_height').val();
	var package_width = jQuery('#package_width').val();
	var package_length = jQuery('#package_length').val();

	reloadQuotes(package_type, delivery_confirmation, insurance_value, package_height, package_width, package_length);
}

jQuery(document).ready(function() {

  jQuery(document).keypress(function(e) {
    if((e.keyCode == 13)) {
      e.preventDefault();
      return false;
	  //reloadShipping(true,weight);
    }
  });

});

jQuery('#shipOptions').click(function(){
    jQuery('#shipOptionsRow').toggle( "slow", function() {
	// Animation complete.
	});
  });

jQuery("#package_type").change(function() {

    var val = jQuery(this).val();

    if(val != "Customer Supplied") {
        jQuery("#package_height, #package_width, #package_length").prop("disabled", true).removeAttr('selected').val("");		
    } else if (val == "Customer Supplied") {
        jQuery("#package_height, #package_width, #package_length").prop("disabled", false);
    }

	var package_type = val;

    if(jQuery('input[name=delivery_confirmation]').is(':checked')) {
		var delivery_confirmation = 1;
    } else {
		var delivery_confirmation = 0;
    }
	var insurance_value = jQuery('#insurance_value').val();
	var package_height = jQuery('#package_height').val();
	var package_width = jQuery('#package_width').val();
	var package_length = jQuery('#package_length').val();


	reloadQuotes(package_type, delivery_confirmation, insurance_value, package_height, package_width, package_length);
	setShipping(jQuery('input[name=shipping_null]:checked', '[name=status]').val());
});

jQuery('select[name=print_type]').change(function(){
	var val = jQuery(this).val();
	//jQuery("#print_type").val('+val+');

	    //if(val == "gif") {
			//jQuery('input[name=print_type]:first').removeAttr('checked');
			//jQuery('input[name=print_type]:last').attr('checked')
		//} else {
			//jQuery('input[name=print_type]:last').removeAttr('checked');
			//jQuery('input[name=print_type]:first').attr('checked')
		//}
	alert('You have changed the label format to '+val);
	setShipping(jQuery('input[name=shipping_null]:checked', '[name=status]').val());
    
  });

jQuery("#delivery_confirmation").change(function() {

	var package_type = jQuery('#package_type').val();

    if(jQuery(this).is(':checked')) {
		var delivery_confirmation = 1;
    } else {
		var delivery_confirmation = 0;
    }
	var insurance_value = jQuery('#insurance_value').val();
	var package_height = jQuery('#package_height').val();
	var package_width = jQuery('#package_width').val();
	var package_length = jQuery('#package_length').val();

	reloadQuotes(package_type, delivery_confirmation, insurance_value, package_height, package_width, package_length);

	setShipping(jQuery('input[name=shipping_null]:checked', '[name=status]').val());

});


jQuery("#UPS_notification").change(function() {

/*    if(jQuery(this).is(':checked')) {
		var UPS_notification = 1;
    } else {
		var UPS_notification = 0;
    }
*/

	var val = jQuery(this).val();
    if(val != "on") {    
		jQuery('input[name=UPS_notification]').val('');
    } else if (val == "on") {
		jQuery('input[name=UPS_notification]').val(1);
    }


	setShipping(jQuery('input[name=shipping_null]:checked', '[name=status]').val());
});


jQuery("#package_length, #package_width, #package_height").change(function() {
    var val = jQuery(this).val();
    if(val > 0) {    
		setShipping(jQuery('input[name=shipping_null]:checked', '[name=status]').val());	
    }
});

jQuery("#insurance_value").change(function() {
    var val = jQuery(this).val();
    if(val > 0) {    
		setShipping(jQuery('input[name=shipping_null]:checked', '[name=status]').val());	
    }
});

</script>


<table border="0" width="100%" cellspacing="0" cellpadding="5" style="background-color:#FFF; border: 1px dashed #d4d4d4; border-radius:5px; min-width:450px">
                  <tr>
                    <td> 
						<u style="font:normal 11px arial">Package Weight</u>: <b id="weightText"><?php echo join(', ',$pkg_list)?></b> &nbsp; <u id="shipOptions">Options</u>
					</td>
					<td colspan="2" align="right" nowrap>


			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
                	<td> Weight Override: <input type="input" size="2" id="weight_override" name="weight_override" value="<?php echo number_format((float)join(',',$pkg_list),2); ?>" min="0.0625" style="text-align:center; font:bold 11px arial; height:22px; width:35px"></td> <td><div class="up"></div><div class="down"></div>
					</td>
				</tr>
			</table>


		</td>
	</tr>
	<tr id="shipOptionsRow" style="display:none;">
		<td class="main" colspan="3">

			<table width="100%" cellpadding="5" cellspacing="0" border="0" style="border:1px dotted #999; background-color:#FFFFC4; border-radius:3px;">
				<tr>
				<td valign="top">Select Package Type:<br>
					<select id="package_type" name="package_type">
						<option value="Customer Supplied" selected>Customer Supplied</option>
						<option value="UPS Letter">UPS Letter</option>
						<option value="UPS Express Box">UPS Express Box</option>
						<option value="Tube">Tube</option>
						<option value="PAK">PAK</option>
					</select>
				</td>
				<td valign="top" nowrap>&nbsp; &nbsp; &nbsp; Dimensions (optional)<br>
					H x<input type="text" id="package_height" name="package_height" size="1" value=""> W x<input type="text" id="package_width" name="package_width" size="1" value=""> L x<input type="text" id="package_length" name="package_length" size="1" value=""> </td>
				<td align="right" style="padding-top:17px;" valign="top">Insurance Value <input type="text" id="insurance_value" name="insurance_value" value="0" size="2"></td>
				</tr>
				<tr>
					<td valign="top"><label><input type="checkbox" id="delivery_confirmation" name="delivery_confirmation" value="1"> Signature Required?</label></td>
					<td colspan="2"><div style="display:inline-block;"><label><input type="checkbox" id="UPS_notification" name="UPS_notification"> UPS Notification?</label></div>
					 <div style="display:inline-block; margin-left: 25px; position:relative; width:auto; right:0">
<script>
var selected = '<?php echo $print_type?>';
jQuery("select option").filter(function() {
    return jQuery(this).val() == selected; 
}).attr('selected', true);
</script>

Label Type:
<select id="print_type" name="print_type">
										<option value="gif">Image (GIF)</option>
										<option value="STARPL">STARPL</option>
										<option value="epl">EPL</option>
										<option value="spl">SPL</option>
										<option value="zpl">Zebra (ZPL)</option>
									</select>
						</div>
					</td>
				</tr>

			</table>	

		</td>
	</tr>
<?php
        if (isset($quotes[$i]['error'])) {
			echo '<tr>
                    <td class="main" colspan="3">'. $quotes[$i]['error'] .'</td>
                  </tr>';
	} else {
echo '<tr><td colspan="3"><div id="shipping_quotes">
			<table width="100%" cellpadding="5" cellspacing="0">';

		// # set the radio button to be checked if it is the method chosen
		for($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {

    		$shp = $quotes[$i]['id'].'_'.$quotes[$i]['methods'][$j]['id'];

		    if (!$def) $def = $shp;

       		$checked = ($shp == $def) ? true : false;

			echo '<tr class="moduleRow '.($ct++&1 ? 'tabEven' : 'tabOdd').'">';

			$val = tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0));

			echo '<td class="main" nowrap>' .$quotes[$i]['methods'][$j]['title'];

			echo "<script type=\"text/javascript\">
					shipData['". $shp."'] = { title: ". tep_js_quote($quotes[$i]["methods"][$j]["title"]).", value: '".sprintf("%.2f",$val)."' };
				  </script>
			</td>";
		
		if ( ($n > 1) || ($n2 > 1) ) {
			
			echo' <td class="main">'.$currencies->format($val),'</td> 
				  <td class="main" align="right">'. tep_draw_radio_field('shipping_null', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked, 'onClick="setShipping(this.value,shipData[this.value].title,shipData[this.value].value);"').'</td>';

		} else {

			echo '<td align="right" width="20" colspan="2">'. $currencies->format($val) . tep_draw_hidden_field('shipping_null', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']).'</td>';

		}

		echo '</tr>';

	
		if(!isset($_SESSION['shipping_options'][$quotes[$i]['id']])) { 
			$_SESSION['shipping_options'][$quotes[$i]['id']] = array();
		}

		$_SESSION['shipping_options'][$quotes[$i]['id']][$quotes[$i]['methods'][$j]['id']] = array('name' => $quotes[$i]['methods'][$j]['title'], 'cost' => $quotes[$i]['methods'][$j]['cost']);

	
		$radio_buttons++;
	}
}

		echo '</table></div></td></tr></table>
	<input type="image" src="images/addUPSLabel_button.png" onclick="addRow(this.form); return false; genUPSLabel(\''. $_GET['oID'].'\', jQuery(\'#shipping_method\').val()); return false" style="outline:none; margin-top:5px;">';
?>
</td>
</tr>
<?php
      }
    }
?>
</table>
<?
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
<script language="javascript">
  setShipping('<?php echo $def?>',shipData['<?php echo $def?>'].title,shipData['<?php echo $def?>'].value);
</script>
