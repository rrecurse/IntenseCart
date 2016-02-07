<?php

// ############################################
/*  Copyright (c) 2006 - 2016 IntenseCart eCommerce  */
// ############################################


	$def_page = $_SERVER['PHP_SELF'];

	require_once(DIR_WS_CLASSES . 'url_rewrite.php');
	$url_rewrite = new url_rewrite;

	// # Image Resizer
	if(USE_IMAGE_RESIZER=='Enable') {
		require(DIR_WS_FUNCTIONS.'image_resizer.php');
	}


	// # The HTML href link wrapper function
	function tep_href_link($page = 'index.php', $parameters = '', $connection = 'NONSSL', $add_session_id = true, $search_engine_safe = true) {

//if(!empty($parameters) && $_SERVER['REMOTE_ADDR'] == '104.162.19.65') error_log(print_r($parameters,1));

		global $request_type, $session_started, $SID, $url_rewrite;

		if (!tep_not_null($page)) {
			$page = $_SERVER['PHP_SELF'];
		}
    
		if (substr($page, 0, 1) == '/') {
			$page = substr($page, 1);
		}

		if($connection == 'NONSSL') {

			$link = HTTP_SERVER . DIR_WS_HTTP_CATALOG;

		} elseif ($connection == 'SSL') {
		
			if (ENABLE_SSL == true) {
				$link = HTTPS_SERVER . DIR_WS_HTTPS_CATALOG;
			} else {
				$link = HTTP_SERVER . DIR_WS_HTTP_CATALOG;
			}

		} else {
			die('<br><br><font color="#ff0000"><b>Error!</b></font><br><br><b>Unable to determine connection method on a link!<br><br>Known methods: NONSSL SSL</b><br><br>');
		}

		if (tep_not_null($parameters)) {
			$link .= $page . '?' . tep_output_string($parameters);
			$separator = '&amp;';
		} else {
			$link .= $page;
			$separator = '?';
		}

		while ( (substr($link, -5) == '&amp;') || (substr($link, -1) == '?') ) $link = substr($link, 0, -1);

		// # Add the session ID when moving from different HTTP and HTTPS servers, or when SID is defined
		if(($add_session_id == true) && ($session_started == true) && (SESSION_FORCE_COOKIE_USE == 'False') ) {

			if (tep_not_null($SID)) {
				$_sid = $SID;
			} elseif ((($request_type == 'NONSSL') && ($connection == 'SSL') && (ENABLE_SSL == true)) || (($request_type == 'SSL') && ($connection == 'NONSSL'))) {
				if (HTTP_COOKIE_DOMAIN != HTTPS_COOKIE_DOMAIN) {
					$_sid = tep_session_name() . '=' . tep_session_id();
				}
			}
		}

		// # These links need to be in the normal style to work. 
		// # if $test_link == $link, then one of the following words is NOT in the URL

		$included_urls = array('product_info.php', 
							   'information.php',
							   'index.php');

    
		$excluded_text = array('add_product',
							   'buy_now',
							   'notify',
							   'notify_remove',
							   'tell_a_friend',
							   'product_question',
							   'product_question_captcha',
							   'product_reviews.php', 
							   'product_reviews_info.php', 
							   'product_reviews_write.php', 
							   'featured_products.php',
							   'products_new.php',
							   'reviews.php',
							   'specials.php',
							   'load_file',
							   'select',
							   'sElEcT',
							   'union',
							   'information_schema',
							   'benchmark',
							   'gclid'
                        	  );


		$alter_link = false;

		if((SEARCH_ENGINE_FRIENDLY_URLS == 'true') && ($search_engine_safe == true)) {
			// # If this is one of the pages that is in the included_urls array
      
			if (str_replace($included_urls, '', $link) != $link) {
				if (str_replace($excluded_text, '', $link) == $link) {
					$alter_link = true;
				}
			}
		}
    
    
		if($alter_link === true) {
			while (strstr($link, '&&')) $link = str_replace('&&', '&', $link);

			$link = str_replace('?', '/', $link);
			$link = str_replace('&', '/', $link);
			$link = str_replace('=', '/', $link);
			$link = str_replace('*', '', $link);

			$link = tep_db_prepare_input($link);

			$link = $url_rewrite->transform_url($link);

    		$separator = '?';
		} else {
			$link = str_replace('&', '&amp;', $link);
		}

		if(isset($_sid)) $link .= $separator . $_sid;

		$link = str_replace('//index.php', '/index.php', $link);



		return $link;
	}
  

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


// # The HTML image wrapper function
function tep_image_src($src, $width='', $height='') {
 	if(preg_match('|^/?'.DIR_WS_IMAGES.'(.+)$|',$src,$rargs)) {
		return IXimage::src($rargs[1],$width,$height);
	}

	// # Obsolete code below
    if ( (empty($src) || ($src == DIR_WS_IMAGES)) && (IMAGE_REQUIRED == 'false') ) {
      return false;
    }

	// # Image Resizer
    if (USE_IMAGE_RESIZER=='Enable') {

		if (($width>0 || $height>0) && preg_match('|^/?'.DIR_WS_IMAGES.'(.+)$|',$src,$rargs)) {
			$src = ImageResizer($rargs[1], $width, $height);
		}
	}

    if($src == DIR_WS_IMAGES) {
		$src = DIR_WS_IMAGES . 'no_image.gif';
	}

	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		return $src;
	} else {
		return str_replace('/http','http',DIR_WS_HTTP_CATALOG.$src);
	}

  }


////
// The HTML image wrapper function
  function tep_image($src, $alt = '', $width = '', $height = '', $parameters = '') {

    if (preg_match('|^/?'.DIR_WS_IMAGES.'(.+)$|',$src,$rargs)) return IXimage::tag($rargs[1],$alt,$width,$height,$parameters);

// Obsolete code below

    $src = tep_image_src($src, $width, $height);
    if (!$src) return false;

// alt is added to the img tag even if it is null to prevent browsers from outputting
// the image filename as default
    
    $image = '<img src="' . tep_output_string($src) . '" border="0" alt="' . tep_output_string($alt) . '"';

    if (tep_not_null($alt)) {
      $image .= ' title=" ' . tep_output_string($alt) . ' "';
    }

    if ( (CONFIG_CALCULATE_IMAGE_SIZE == 'true') && (empty($width) || empty($height)) ) {
      if ($image_size = @getimagesize($src)) {
        if (empty($width) && tep_not_null($height)) {
          $ratio = $height / $image_size[1];
          $width = $image_size[0] * $ratio;
        } elseif (tep_not_null($width) && empty($height)) {
          $ratio = $width / $image_size[0];
          $height = $image_size[1] * $ratio;
        } elseif (empty($width) && empty($height)) {
          $width = $image_size[0];
          $height = $image_size[1];
        }
      } elseif (IMAGE_REQUIRED == 'false') {
        return false;
      }
    }

    if (tep_not_null($width) && tep_not_null($height)) {
      $image .= ' width="' . tep_output_string($width) . '" height="' . tep_output_string($height) . '"';
    }

    if (tep_not_null($parameters)) $image .= ' ' . $parameters;

    $image .= '>';

    return $image;
  }

////
// The HTML form submit button wrapper function
// Outputs a button in the selected language

  function tep_image_submit($image, $alt = '', $parameters = '') {

    global $language;


	if (defined('CDN_CONTENT')) {

		//$image_submit = '<input type="image" src="' . tep_output_string(DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/'. $language  . '/' . $image) . '" style="border:none;" alt="' . tep_output_string($alt) . '"';
	
	    if(CDN_CONTENT) {
		    $image_submit = '<input type="image" src="' . tep_output_string(DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/'. $language  . '/' . $image) . '" style="border:none;" alt="' . tep_output_string($alt) . '"';
    	} else {
		    $image_submit = '<input type="image" src="' . tep_output_string('/'.DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/'. $language  . '/' . $image) . '" style="border:none;" alt="' . tep_output_string($alt) . '"';
	    }
	} else {
		    $image_submit = '<input type="image" src="' . tep_output_string('/'.DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/'. $language  . '/' . $image) . '" style="border:none;" alt="' . tep_output_string($alt) . '"';
	}


    if (tep_not_null($alt)) $image_submit .= ' title=" ' . tep_output_string($alt) . ' "';

    if (tep_not_null($parameters)) $image_submit .= ' ' . $parameters;

    $image_submit .= '>';

    return $image_submit;
  }

////
// Output a function button in the selected language
  function tep_image_button($image, $alt = '', $parameters = '') {
    global $language;

    return tep_image(DIR_WS_CATALOG_LAYOUT_IMAGES . 'buttons/' . $language . '/' . $image, $alt, '', '', $parameters);
  }

  function tep_get_image_info($img) {
    $info=@getimagesize(DIR_FS_SITE_CATALOG.$img);
    if ($info) return Array('width'=>$info[0],'height'=>$info[1],'mime'=>$info['mime']);
  }


// By MegaJim - Copied from admin
  function tep_catalog_href_link($page = '', $parameters = '', $connection = 'NONSSL') {
    if ($connection == 'NONSSL') {
      $link = HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
    } elseif ($connection == 'SSL') {
      if (ENABLE_SSL_CATALOG == 'true') {
        $link = HTTPS_CATALOG_SERVER . DIR_WS_CATALOG;
      } else {
        $link = HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
      }
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


////
// Output a separator either through whitespace, or with an image
  function tep_draw_separator($image = 'pixel_black.gif', $width = '100%', $height = '1') {
    return tep_image(DIR_WS_IMAGES . $image, '', $width, $height);
  }

////
// Output a form
  function tep_draw_form($name, $action, $method = 'post', $parameters = '') {
    $form = '<form name="' . tep_output_string($name) . '" action="' . tep_output_string($action) . '" method="' . tep_output_string($method) . '"';

    if (tep_not_null($parameters)) $form .= ' ' . $parameters;

    $form .= '>';

    return $form;
  }

////
// Output a form input field
  function tep_draw_input_field($name, $value = '', $parameters = '', $type = 'text', $reinsert_value = true) {
    $field = '<input type="' . tep_output_string($type) . '" name="' . tep_output_string($name) . '"';

    if ( (isset($GLOBALS[$name])) && ($reinsert_value == true) ) {
      $field .= ' value="' . tep_output_string(stripslashes($GLOBALS[$name])) . '"';
    } elseif (tep_not_null($value)) {
      $field .= ' value="' . tep_output_string($value) . '"';
    }

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    return $field;
  }

////
// Output a form password field
  function tep_draw_password_field($name, $value = '', $parameters = 'maxlength="40"') {
    return tep_draw_input_field($name, $value, $parameters, 'password', false);
  }

////
// Output a selection field - alias function for tep_draw_checkbox_field() and tep_draw_radio_field()
  function tep_draw_selection_field($name, $type, $value = '', $checked = false, $parameters = '') {
    $selection = '<input type="' . tep_output_string($type) . '" name="' . tep_output_string($name) . '"';

    if (tep_not_null($value)) $selection .= ' value="' . tep_output_string($value) . '"';

    if ( ($checked == true) || ( isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) && ( ($GLOBALS[$name] == 'on') || (isset($value) && (stripslashes($GLOBALS[$name]) == $value)) ) ) ) {
      $selection .= ' CHECKED';
    }

    if (tep_not_null($parameters)) $selection .= ' ' . $parameters;

    $selection .= '>';

    return $selection;
  }

////
// Output a form checkbox field
  function tep_draw_checkbox_field($name, $value = '', $checked = false, $parameters = '') {
    return tep_draw_selection_field($name, 'checkbox', $value, $checked, $parameters);
  }

////
// Output a form radio field
  function tep_draw_radio_field($name, $value = '', $checked = false, $parameters = '') {
    return tep_draw_selection_field($name, 'radio', $value, $checked, $parameters);
  }

////
// Output a form textarea field
  function tep_draw_textarea_field($name, $wrap, $width, $height, $text = '', $parameters = '', $reinsert_value = true) {
//    $field = '<textarea name="' . tep_output_string($name) . '" wrap="' . tep_output_string($wrap) . '" cols="' . tep_output_string($width) . '" rows="' . tep_output_string($height) . '"';
    $field = '<textarea name="' . tep_output_string($name) . '" cols="' . tep_output_string($width) . '" rows="' . tep_output_string($height) . '"';

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

////
// Output a form hidden field
  function tep_draw_hidden_field($name, $value = '', $parameters = '') {
    $field = '<input type="hidden" name="' . tep_output_string($name) . '"';

    if (tep_not_null($value)) {

      $field .= ' value="' . tep_output_string($value) . '"';

    } elseif (isset($GLOBALS[$name])) {

// added array_shift() check since stripslashes() only accepts strings
// and there is a random warning when adding to cart from quickview pop.

      $field .= ' value="'. tep_output_string(stripslashes( (is_array($GLOBALS[$name]) ? array_shift($GLOBALS[$name]) : $GLOBALS[$name]) )) .'"';
   
	}

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    return $field;
  }

////
// Hide form elements
  function tep_hide_session_id() {
    global $session_started, $SID;

    if (($session_started == true) && tep_not_null($SID)) {
      return tep_draw_hidden_field(tep_session_name(), tep_session_id());
    }
  }

////
// Output a form pull down menu
  function tep_draw_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false) {
    $field = '<select name="' . tep_output_string($name) . '"';

    if (tep_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    if (empty($default) && isset($GLOBALS[$name])) $default = stripslashes($GLOBALS[$name]);

    for ($i=0, $n=sizeof($values); $i<$n; $i++) {
      $field .= '<option value="' . tep_output_string($values[$i]['id']) . '"';
      if ($default == $values[$i]['id']) {
        $field .= ' SELECTED';
      }

      $field .= '>' . tep_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>';
    }
    $field .= '</select>';

    if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;
  }


  // # Creates a pull-down list of countries
  function tep_get_country_list($name, $selected = '', $parameters = '') {

    $countries_array = array(array('id' => '', 'text' => PULL_DOWN_DEFAULT));
    $countries = tep_get_countries();

    for ($i=0, $n=sizeof($countries); $i<$n; $i++) {
      $countries_array[] = array('id' => $countries[$i]['countries_id'], 'text' => $countries[$i]['countries_name']);
    }

    return tep_draw_pull_down_menu($name, $countries_array, $selected, $parameters);
  }

  // # rmh referral
  // # Creates a pull-down list of sources
  function tep_get_source_list($name, $show_other = false, $selected = '', $parameters = '') {

    $sources_array = array(array('id' => '', 'text' => PULL_DOWN_DEFAULT));
    $sources = tep_get_sources();

    for ($i=0, $n=sizeof($sources); $i<$n; $i++) {
      $sources_array[] = array('id' => $sources[$i]['sources_id'], 'text' => $sources[$i]['sources_name']);
    }

    if ($show_other == 'true') {
      $sources_array[] = array('id' => '9999', 'text' => PULL_DOWN_OTHER);
    }

    return tep_draw_pull_down_menu($name, $sources_array, $selected, $parameters);
  }
  
  // # Prepare quoted javascript string
  function tep_js_quote($s) {
    if (!isset($s)) return 'null';
    if (is_array($s)) {
      $j=Array();
      foreach ($s AS $k=>$v) $j[]=tep_js_quote($k).':'.tep_js_quote($v);
      return '{'.join(',',$j).'}';
    }
	//else if (is_int($s) || is_float($s)) return $s;
    return "'".preg_replace('|\r?\n|','\n',preg_replace("|\'|","\\'",preg_replace("|\\\\|","\\\\",$s)))."'";
  }


  function tep_js_quote_array($s) {
    if (!is_array($s)) return 'new Array()';
    $j=Array();
    foreach ($s AS $v) $j[]=tep_js_quote($v);
    return 'new Array('.join(',',$j).')';
  }
  
?>
