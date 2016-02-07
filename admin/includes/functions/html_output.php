<?php


	// # The HTML href link wrapper function
	
	function tep_href_link($page = '', $parameters = '', $connection = 'NONSSL') {
		if($page =='') {
			die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine the page link!<br><br>Function used:<br><br>tep_href_link(\'' . $page . '\', \'' . $parameters . '\', \'' . $connection . '\')</b>');
    	}
    if ($connection == 'NONSSL') {
      $link = HTTP_SERVER . DIR_WS_ADMIN;
    } elseif ($connection == 'SSL') {
      if (ENABLE_SSL == 'true') {
        $link = HTTPS_SERVER . DIR_WS_ADMIN;
      } else {
        $link = HTTP_SERVER . DIR_WS_ADMIN;
      }
    } else {
      die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine connection method on a link!<br><br>Known methods: NONSSL SSL<br><br>Function used:<br><br>tep_href_link(\'' . $page . '\', \'' . $parameters . '\', \'' . $connection . '\')</b>');
    }
    if ($parameters == '') {
      $link = $link . $page . '?' . SID;
    } else {
      $link = $link . $page . '?' . $parameters . '&' . SID;
    }

    while ( (substr($link, -1) == '&') || (substr($link, -1) == '?') ) $link = substr($link, 0, -1);

    return $link;
  }

  function tep_catalog_href_link($page = '', $parameters = '', $connection = 'NONSSL') {
    if ($connection == 'NONSSL') {
      $link = HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
    } elseif ($connection == 'SSL') {
      //if (ENABLE_SSL_CATALOG == 'true') {
        $link = HTTPS_CATALOG_SERVER . DIR_WS_CATALOG;
      //} else {
        $link = HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
      //}
    } else {
      die('</td></tr></table></td></tr></table><br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine connection method on a link!<br><br>Known methods: NONSSL SSL<br><br>Function used:<br><br>tep_href_link(\'' . $page . '\', \'' . $parameters . '\', \'' . $connection . '\')</b>');
    }
    if ($parameters == '') {
      $link .= $page;
    } else {
      $link .= $page . '?' . $parameters;
    }

    while ( (substr($link, -1) == '&') || (substr($link, -1) == '?') ) $link = substr($link, 0, -1);



    return $link;
  }



	// # Image Resizer
    if (USE_IMAGE_RESIZER=='Enable') {
     require(DIR_FS_FUNCTIONS.'image_resizer.php');
    }

	// # The HTML image wrapper function
	function tep_image_src($src, $width = '', $height = '') {
  		if(preg_match('|^/?'.DIR_WS_CATALOG_IMAGES.'(.+)$|',$src,$rargs)) return IXimage::src($rargs[1],$width,$height);
  
	// # Image Resizer
    if (USE_IMAGE_RESIZER=='Enable') {
    	if ((($width>0) || ($height>0)) && preg_match('|^/?'.DIR_WS_CATALOG_IMAGES.'(.*)$|',$src,$rargs)) {
     		$src=ImageResizer($rargs[1],$width,$height);
     	}
    }
    return $src;
  }

// # kicken's debugger

function dv(/*...*/){

	if (!isset($_COOKIE['ENABLE_DEBUG'])) return;
	ob_start();
	for ($i=0,$len=func_num_args(); $i<$len; $i++){
		$arg = func_get_arg($i);
		var_dump($arg);
		echo "\r\n";
	}
	$log = ob_get_clean();
	error_log($log);
}


  function tep_image($src, $alt = '', $width = '', $height = '', $params = '') {

    if (preg_match('|^/?'.DIR_WS_CATALOG_IMAGES.'(.+)$|',$src,$rargs)) return IXimage::tag($rargs[1],$alt,$width,$height,$parameters);

    $src=tep_image_src($src,$width,$height);
    $image = '<img src="' . $src . '" border="0" alt="' . $alt . '"';
    if ($alt) {
      $image .= ' title=" ' . $alt . ' "';
    }
    if ($width) {
      $image .= ' width="' . $width . '"';
    }
    if ($height) {
      $image .= ' height="' . $height . '"';
    }
    if ($params) {
      $image .= ' ' . $params;
    }
    $image .= '>';

    return $image;
  }

////
// The HTML form submit button wrapper function
// Outputs a button in the selected language
  function tep_image_submit($image, $alt = '', $parameters = '') {
    global $language;

    $image_submit = '<input type="image" src="' . tep_output_string(DIR_WS_LANGUAGES . $language . '/images/buttons/' . $image) . '" border="0" alt="' . tep_output_string($alt) . '"';

    if (tep_not_null($alt)) $image_submit .= ' title=" ' . tep_output_string($alt) . ' "';

    if (tep_not_null($parameters)) $image_submit .= ' ' . $parameters;

    $image_submit .= '>';

    return $image_submit;
  }

////
// Draw a 1 pixel black line
  function tep_black_line() {
    return tep_image(DIR_WS_IMAGES . 'pixel_black.gif', '', '100%', '1');
  }

////
// Output a separator either through whitespace, or with an image
  function tep_draw_separator($image = 'pixel_black.gif', $width = '100%', $height = '1') {
    return tep_image(DIR_WS_IMAGES . $image, '', $width, $height);
  }

////
// Output a function button in the selected language
  function tep_image_button($image, $alt = '', $params = '') {
    global $language;

    return tep_image(DIR_WS_LANGUAGES . $language . '/images/buttons/' . $image, $alt, '', '', $params);
  }


// # javascript to dynamically update the states/provinces list when the country is changed
// # TABLES: zones
  function tep_js_zone_list($country, $form, $field) {
    $countries_query = tep_db_query("select distinct zone_country_id from " . TABLE_ZONES . " order by zone_country_id");
    $num_country = 1;
    $output_string = '';
    while ($countries = tep_db_fetch_array($countries_query)) {
      if ($num_country == 1) {
        $output_string .= '  if (' . $country . ' == "' . $countries['zone_country_id'] . '") {' . "\n";
      } else {
        $output_string .= '  } else if (' . $country . ' == "' . $countries['zone_country_id'] . '") {' . "\n";
      }

      $states_query = tep_db_query("select zone_name, zone_id from " . TABLE_ZONES . " where zone_country_id = '" . $countries['zone_country_id'] . "' order by zone_name");

      $num_state = 1;
      while ($states = tep_db_fetch_array($states_query)) {
        if ($num_state == '1') $output_string .= '    ' . $form . '.' . $field . '.options[0] = new Option("' . PLEASE_SELECT . '", "");' . "\n";
        $output_string .= '    ' . $form . '.' . $field . '.options[' . $num_state . '] = new Option("' . $states['zone_name'] . '", "' . $states['zone_id'] . '");' . "\n";
        $num_state++;
      }
      $num_country++;
    }
    $output_string .= '  } else {' . "\n" .
                      '    ' . $form . '.' . $field . '.options[0] = new Option("' . TYPE_BELOW . '", "");' . "\n" .
                      '  }' . "\n";

    return $output_string;
  }


// # Output a form
  function tep_draw_form($name, $action, $parameters = '', $method = 'post', $params = '') {
    $form = '<form name="' . tep_output_string($name) . '" action="';
    if (tep_not_null($parameters)) {
      $form .= tep_href_link($action, $parameters);
    } else {
      $form .= tep_href_link($action);
    }
    $form .= '" method="' . tep_output_string($method) . '"';
    if (tep_not_null($params)) {
      $form .= ' ' . $params;
    }
    $form .= '>';

    return $form;
  }


// # Output a form input field
  function tep_draw_input_field($name, $value = '', $parameters = '', $required = false, $type = 'text', $reinsert_value = true) {
    $field = '<input type="' . tep_output_string($type) . '" name="' . tep_output_string($name) . '"';

    if (isset($GLOBALS[$name]) && ($reinsert_value == true) && is_string($GLOBALS[$name])) {
      $field .= ' value="' . tep_output_string(stripslashes($GLOBALS[$name])) . '"';
    } elseif (tep_not_null($value)) {
      $field .= ' value="' . tep_output_string($value) . '"';
    }

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;
  }


// # Output a form password field
  function tep_draw_password_field($name, $value = '', $required = false,$optns=NULL) {
    $field = tep_draw_input_field($name, $value, isset($optns)?$optns:'maxlength="40"', $required, 'password', false);

    return $field;
  }


// # Output a form filefield
  function tep_draw_file_field($name, $required = false) {
    $field = tep_draw_input_field($name, '', '', $required, 'file');

    return $field;
  }


// # Output a selection field - alias function for tep_draw_checkbox_field() and tep_draw_radio_field()
  function tep_draw_selection_field($name, $type, $value = '', $checked = false, $compare = '', $extra='') {
    $selection = '<input type="' . tep_output_string($type) . '" name="' . tep_output_string($name) . '"';

    if (tep_not_null($value)) $selection .= ' value="' . tep_output_string($value) . '"';

    if ( ($checked == true) || (isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) && ($GLOBALS[$name] == 'on')) || (isset($value) && isset($GLOBALS[$name]) && (stripslashes($GLOBALS[$name]) == $value)) || (tep_not_null($value) && tep_not_null($compare) && ($value == $compare)) ) {
      $selection .= ' CHECKED';
    }

    if ($extra!='') $selection .= " $extra";
    $selection .= '>';

    return $selection;
  }


// # Output a form checkbox field
  function tep_draw_checkbox_field($name, $value = '', $checked = false, $compare = '', $extra='') {
    return tep_draw_selection_field($name, 'checkbox', $value, $checked, $compare, $extra);
  }


// # Output a form radio field
  function tep_draw_radio_field($name, $value = '', $checked = false, $compare = '', $extra='') {
    return tep_draw_selection_field($name, 'radio', $value, $checked, $compare, $extra);
  }


// # Output a form textarea field
  function tep_draw_textarea_field($name, $wrap, $width=NULL, $height=NULL, $text = '', $parameters = '', $reinsert_value = true) {
    $field = '<textarea name="' . tep_output_string($name) . '" wrap="' . tep_output_string($wrap) . '" cols="' . tep_output_string($width) . '" rows="' . tep_output_string($height) . '"';

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    if ( (isset($GLOBALS[$name])) && ($reinsert_value == true) ) {
      $field .= stripslashes($GLOBALS[$name]);
    } elseif (tep_not_null($text)) {
      $field .= $text;
    }

    $field .= '</textarea>';

    return $field;
  }

  function tep_draw_textbox_field($name, $size, $numchar, $value = '', $params = '', $reinsert_value = true) {
    $field = '<input type="text" name="' . $name . '" size="' . $size . '" maxlength="' . $numchar . '" value="';
    if ($params) $field .= '' . $params;
    $field .= '';
    if ( ($GLOBALS[$name]) && ($reinsert_value) ) {
      $field .= $GLOBALS[$name];
  } elseif ($value != '') {
      $field .= trim($value);
    } else {
      $field .= trim($GLOBALS[$name]);
    }
    $field .= '">';

    return $field;
  }
  

// # Output a form hidden field
  function tep_draw_hidden_field($name, $value = '', $parameters = '') {
    $field = '<input type="hidden" name="' . tep_output_string($name) . '"';

    if (tep_not_null($value)) {
      $field .= ' value="' . tep_output_string($value) . '"';
    } elseif (isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) {
      $field .= ' value="' . tep_output_string(stripslashes($GLOBALS[$name])) . '"';
    }

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    return $field;
  }


// # Output a form pull down menu
function tep_draw_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false) {

    $field = '<select name="' . tep_output_string($name) . '"';

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    if (empty($default) && isset($GLOBALS[$name])) $default = stripslashes($GLOBALS[$name]);

   	for ($i=0, $n=sizeof($values); $i<$n; $i++) {

	      $field .= '<option value="' . tep_output_string($values[$i]['id']) . '"';

			if(!empty($default)) {
				if(is_array($default)) { 
					foreach($default as $d) { 
						$default = $d[id];
	    				if ($default == $values[$i]['id']) {
    	   					$field .= ' SELECTED';
	    				}
					}
				} else {
			   		if ($default == $values[$i]['id']) {
    	   				$field .= ' SELECTED';
	    			}
				}
			}

   		  $field .= '>' . tep_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>';
    }

    $field .= '</select>';

    if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;
  }


  function tep_draw_mselect_menu($name, $values, $selected_vals, $params = '', $required = false) {
    
    $sel=Array();
    foreach ($selected_vals AS $v) {
      if (is_array($v)) $sel[$v['id']]=1;
      else $sel[$v]=1;
    }

    $field = '<select name="' . $name . '"';

    if ($params) $field .= ' ' . $params;

    $field .= ' multiple>';

    for ($i=0; $i<sizeof($values); $i++) {

            if (isset($values[$i]['id']))

            {

            $field .= '<option value="' . $values[$i]['id'] . '"';

            if ( ((strlen($values[$i]['id']) > 0) && ($GLOBALS[$name] == $values[$i]['id'])) ) {

              $field .= ' SELECTED';

            } else if (isset($sel[$values[$i]['id']]))

              $field .= ' SELECTED';

            }

      $field .= '>' . $values[$i]['text'] . '</option>';

    }

    $field .= '</select>';

 

    if ($required) $field .= TEXT_FIELD_REQUIRED;

 

    return $field;

  }

// # Prepare quoted javascript string

  function tep_js_quote($s) {
    if (!isset($s)) return 'null';
    if (is_array($s)) {
      $j=Array();
      foreach ($s AS $k=>$v) $j[]=tep_js_quote($k).':'.tep_js_quote($v);
      return '{'.join(',',$j).'}';
    }
    return "'".preg_replace('|\r?\n|','\n',preg_replace("|'|","\\'",preg_replace('|\\\\|','\\\\\\\\',$s)))."'";
  }
  function tep_js_quote_array($s) {
    if (!is_array($s)) return 'new Array()';
    $j=Array();
    foreach ($s AS $v) $j[]=tep_js_quote($v);
    return 'new Array('.join(',',$j).')';
  }

  function tep_fmt_number($num,$pre=0) {
    $num=sprintf(($pre>0?"%.${pre}f":"%d"),$num);
    while (preg_match('/\d\d\d\d/',$num)) $num=preg_replace('/(.*)(\d\d\d)/','$1,$2',$num);
    return $num;
  }

?>
