<?php

	require('includes/application_top.php');

	$country = (!empty($_GET['country']) ? preg_replace('/[^0-9]/i', '', $_GET['country']) : STORE_COUNTRY);
	$postal = preg_replace('/[^A-Za-z0-9 ]/i', '', $_GET['postal']);
	$zones_array = array();

	if(empty($country)) {
		error_log('No country defined. Error produced by IP ' . $_SERVER['REMOTE_ADDR']);
		die('Error');
	}

	$zones_query = NULL;

	if (!empty($postal)) {

		$zones_query = tep_db_query("SELECT z.zone_id, z.zone_name
									 FROM zones_to_postal zp 
									 LEFT JOIN zones z ON (z.zone_id = zp.zone_id AND z.zone_country_id = zp.zone_country_id)
									 WHERE zp.zone_country_id = '" . $country . "' 
									 AND '".$postal."' BETWEEN zp.postal_min AND zp.postal_max
									 ORDER BY z.zone_name
									");

	}

	if(tep_db_num_rows($zones_query) > 0) { 

		while ($zones_values = tep_db_fetch_array($zones_query)) {

			if(empty($def) || $def0 == $zones_values['zone_id']) {
				$def = $zones_values['zone_id'];
			}

			$zones_array[] = array('id' => $zones_values['zone_id'], 'text' => $zones_values['zone_name']);
		}

	} else {

		$zones_by_country_query = tep_db_query("SELECT zone_id, zone_name FROM zones WHERE zone_country_id = '" . $country . "' ORDER BY zone_name");

		while ($zones_by_country = tep_db_fetch_array($zones_by_country_query)) {

			if(empty($def) || $def0 == $zones_by_country['zone_id']) {
				$def = $zones_by_country['zone_id'];
			}

			$zones_array[] = array('id' => $zones_by_country['zone_id'], 'text' => $zones_by_country['zone_name']);
		}

	}

	if(empty($_GET['d']) || $_GET['d'] == '$ship_state') {

		if($_GET['d'] != '$ship_state') {
			error_log('No state defined at all in $_GET[d] = ' . $_GET['d']);
		}

		if(!empty($GLOBALS['ship_zone_id'])) { 
			$def0 = $GLOBALS['ship_zone_id'];
		} else {
			$def0 = '41'; // # default to New Jersey if everything else fails
		}


	} else {

		$def0 = $_GET['d'];
	}



	if(!$def) $def = $def0;

	if ($_GET['sec'] == 'bill') {
    	$js1 = 'setState(\'bill\', this.value);';
	    $js2 = $js1;

	} else {
	    $js2 = 'setState(\'ship\', this.value);';
    	$js1 = $js2;
	}

	if(sizeof($zones_array) > 0) {

		//# Kill the session? Maybe due to shipping bug to international
		//tep_session_unregister('cc_id');

    	if(sizeof($zones_array) > 1) {
			echo tep_draw_pull_down_menu(($_GET['sec'] == 'bill' ? 'bill' : 'ship') . '_state_null', $zones_array, $def, 'onChange="' . $js1 . '"');
		} else {
			echo '<b>'.$zones_array[0]['text'].'</b>';
		}

	} else {
	    echo tep_draw_input_field(($_GET['sec'] == 'bill' ? 'bill' : 'ship') . '_state_null', $_GET['d'], 'style="width:150px" onChange="' . $js2 . '" maxlength=32');

	    if ($country == '222') {
    	  echo '<br><center>For residents of the United Kingdom, please enter your country/province in the state field.</center>';
	    }
	}
?>
<eval code="setState('<?php echo $_GET['sec']?>','<?php echo $def?>')">
