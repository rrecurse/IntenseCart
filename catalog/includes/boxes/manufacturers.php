<?php
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	$manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");

	if($number_of_rows = tep_db_num_rows($manufacturers_query)) {
  
		$info_box_contents = array();
		//$info_box_contents[] = array('text' => BOX_HEADING_MANUFACTURERS);

		new infoBoxHeading($info_box_contents, false, false);

		if($number_of_rows <= MAX_DISPLAY_MANUFACTURERS_IN_A_LIST) {
			// # Display a list
			$manufacturers_list = '';

			while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
				if(strlen($manufacturers['manufacturers_name']) > MAX_DISPLAY_MANUFACTURER_NAME_LEN) {
					$manufacturers_name = substr($manufacturers['manufacturers_name'], 0, MAX_DISPLAY_MANUFACTURER_NAME_LEN) . '...';
				} else {
					$manufacturers_name = $manufacturers['manufacturers_name'];
				}

				if(isset($HTTP_GET_VARS['manufacturers_id']) && ($HTTP_GET_VARS['manufacturers_id'] == $manufacturers['manufacturers_id'])) {
					$manufacturers_name = '<b>' . $manufacturers_name .'</b>';
				}

				$manufacturers_list .= '<a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . $manufacturers['manufacturers_id']) . '">' . $manufacturers_name . '</a><br>';

			}
	
			$manufacturers_list = substr($manufacturers_list, 0, -4);
	
			$info_box_contents = array();
			$info_box_contents[] = array('text' => $manufacturers_list);

		} else {
	
			// # Display a drop-down
			$manufacturers_array = array();
	
			if(MAX_MANUFACTURERS_LIST < 2) {
				$manufacturers_array[] = array('id' => '', 'text' => PULL_DOWN_DEFAULT);
			}

			while($manufacturers = tep_db_fetch_array($manufacturers_query)) {

				if(strlen($manufacturers['manufacturers_name']) > MAX_DISPLAY_MANUFACTURER_NAME_LEN) { 
					$manufacturers_name = substr($manufacturers['manufacturers_name'], 0, MAX_DISPLAY_MANUFACTURER_NAME_LEN) . '..';
				} else {
					$manufacturers_name = $manufacturers['manufacturers_name'];
				}
	
    	    	$manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'], 'text' => $manufacturers_name);
			}

			$info_box_contents = array();
			$info_box_contents[] = array('form' => tep_draw_form('manufacturers', tep_href_link(FILENAME_DEFAULT, '', 'NONSSL', false), 'post'),
										 'text' => tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, (isset($HTTP_GET_VARS['manufacturers_id']) ? $HTTP_GET_VARS['manufacturers_id'] : ''), 'onChange="this.form.submit();" size="' . MAX_MANUFACTURERS_LIST . '" style="width:120px; height:17px; border: 1px solid #818181; font: 8pt Tahoma;"') . tep_hide_session_id());

		}

		new infoBox($info_box_contents);
	}
?>