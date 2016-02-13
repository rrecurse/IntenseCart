<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	//if ($_GET['showdebug'] == '1') print_r($HTTP_GET_VARS);
	//$template['debug'] .= ''; // Define as blank if not already defined

	// # //////////////////////////////////////////
	// # SELECT HOW TO DISPLAY THE OUTPUT
	// # //////////////////////////////////////////

	$display_template_output = 1;
	$display_normal_output = 0;
	$display_debugging_output = 0;

	unset($template);

	// # Override if we need to show a pop-up window
	//echo "Location: ".$_SERVER['PHP_SELF']."<br>cPath: ".$cPath."<br>Products ID: ".$HTTP_GET_VARS['products_id'];

	if($page_404) {

		$scriptname = '404.php';
		$scriptbasename = '404.php';

	} else {

		if(isset($HTTP_GET_VARS['products_id'])) {
			$scriptname = FILENAME_PRODUCT_INFO;
		} else {
			$scriptname = $_SERVER['REQUEST_URI'];
			$scriptname = getenv('SCRIPT_NAME');
		}

		if(strpos($scriptname, '/') !== false) {
			$scriptbasename = substr($scriptname, strrpos($scriptname, '/') + 1);
		} else {
			$scriptbasename = $scriptname;
		}


	}
	
	// # If script name contains "popup" then turn off templates and display the normal output
	// # This is required to prevent display of standard page elements (header, footer, etc) from the template and allow javascript code to run properly

	if(stripos($scriptname, "popup") !== false || stripos($scriptname, "info_shopping_cart") !== false || stripos($scriptname, "ec_process") !== false){

		$display_normal_output = 1;
		$display_template_output = 0;
	}

	// # //////////////////////////////////////////
	// # Allow the ability to turn on/off settings from the URL
	// # Set values to 0 or 1 as needed
	// # ///////////////////////////////////////////

	// # Allow Template output control from the URL

	if($HTTP_GET_VARS['sts_template'] != "") {
		$display_template_output = $HTTP_GET_VARS['sts_template'];
	}
 
	// # Allow Normal output control from the URL
	if($HTTP_GET_VARS['sts_normal'] != "") {
		$display_normal_output = $HTTP_GET_VARS['sts_normal'];
	}

	// # Allow Debugging control from the URL
	if($HTTP_GET_VARS['sts_debug'] != "") {
		$display_debugging_output = $HTTP_GET_VARS['sts_debug'];
	}

	/////////////////////////////////////////////
	////// # if product_info.php load data
	/////////////////////////////////////////////

	if($scriptbasename == 'product_info.php') {
		require(STS_PRODUCT_INFO);
	} elseif($scriptbasename == '404.php') {
		require(DIR_WS_INCLUDES . 'sts_404.php');
	}

	/////////////////////////////////////////////
	///// # Determine which template file to use
	/////////////////////////////////////////////

	$sts_template_array = array(STS_DEFAULT_TEMPLATE, STS_TEMPLATE_DIR . $scriptbasename . ".html");

	// # Are we in the index.php script?  If so, what is our Category Path (cPath)?
	if($scriptbasename == "index.php") {

		// # If no cPath defined, default to 0 (the home page)
		if ($cPath == "") {
			$sts_cpath = 0; 
		} elseif ($cPath=='0') {
			$sts_cpath='root';
		} else {
			$sts_cpath = $cPath;
		}

		// # If we are doing a search by manufacturer, use the "index.php_mfr.html" template
		if(isset($HTTP_GET_VARS['manufacturers_id'])) {
			$sts_cpath = "mfr";
		}

		// # Split cPath into parts and check for them individually
		$cpath_parts = (explode("_", $sts_cpath));	

		if($cpath_parts[0] != 'info') foreach ($cpath_parts as $a) {
			array_push($sts_template_array, STS_TEMPLATE_DIR . "index.php_$a.html");
			$listing = 'listing_category';
		}

		if(sizeof($cpath_parts) >= 2) {
			array_push($sts_template_array, STS_TEMPLATE_DIR . "index.php_".preg_replace('/_\d+$/','',$sts_cpath)."_0.html");

		}

		// # Look for category-path-specific template file like "index.php_1_17.html"
		array_push($sts_template_array, STS_TEMPLATE_DIR . "index.php_$sts_cpath.html");
		$listing = 'listing_category';

		// # Info pages
		if(isset($HTTP_GET_VARS['info_id'])) {
		    array_push($sts_template_array, STS_TEMPLATE_DIR . "index.php_info.html");
    		array_push($sts_template_array, STS_TEMPLATE_DIR . "index.php_info_".$HTTP_GET_VARS['info_id'].".html");
		}

		// # Check for manufacturer-specific templates 
		if(isset($HTTP_GET_VARS['manufacturers_id'])) {
			array_push($sts_template_array, STS_TEMPLATE_DIR . "index.php_mfr_" . $HTTP_GET_VARS['manufacturers_id'] . ".html");
			$listing = 'listing_manufacturer';
		}

	}

	if($scriptbasename == 'advanced_search_result.php' && $HTTP_GET_VARS['template'] == 'cartanywhere') {
		array_push($sts_template_array, STS_TEMPLATE_DIR . "advanced_search_result.php_cartanywhere.html");
		$listing = 'listing_affiliate_cartanywhere';

	} elseif($scriptbasename == 'shopping_cart.php' && $HTTP_GET_VARS['template'] == 'cartanywhere') {
		array_push($sts_template_array, STS_TEMPLATE_DIR . "shopping_cart.php_cartanywhere.html");

	} elseif($scriptbasename != "product_info.php" && $HTTP_GET_VARS['template'] == 'cartanywhere') {
		array_push($sts_template_array, STS_TEMPLATE_DIR . "cart_anywhere.php.html");
	}

	// # Check for Product_Info templates by Product ID and cPath
	if($scriptbasename == "product_info.php" && $HTTP_GET_VARS['template'] == 'cartanywhere') {
		array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_cartanywhere.html");

	} elseif($scriptbasename == "product_info.php" && $HTTP_GET_VARS['template'] != 'cartanywhere') {

		$cpath_parts = (explode("_", $cPath));

		foreach ($cpath_parts as $a) {
    	    array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_c$a.html");
		}

        //array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_c$cPath.html");
        //array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_{$template['masterproductid']}.html");
        
		array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_{$HTTP_GET_VARS['products_id']}.html");

        if($HTTP_GET_VARS['popup'] || $HTTP_GET_VARS['template']=='popup') { 
			array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_popup.html");
		}

        if(isset($sppc_customer_group_id) && $sppc_customer_group_id > 2) {
			array_push($sts_template_array, STS_TEMPLATE_DIR . "product_info.php_sppc.html");
		}
	
		$listing = 'product_xsell';
}


// # Active template is now in $sts_template_file
if ($page_404) {
  $sts_template_file = STS_TEMPLATE_DIR . '404.php.html';
} else {
  foreach ($sts_template_array as $checkfile) {
    if ($display_debugging_output) {
  	print "Checking for Template: $checkfile -> " . (file_exists($checkfile) ? "Found":"Not Found") . "<br>\n";
    }

    if (file_exists($checkfile)) {
  	$sts_template_file = $checkfile;
    }
  }
}

if ($display_debugging_output) {
  print "Active Template is [$sts_template_file]<br>\n";
}

// # Open Template file and read into a variable
if (!file_exists($sts_template_file)) {
  echo "Template file doesn't exist: [$sts_template_file]";
}

// # Used to read and check for $url_ and $urlcat_ variables
$sts_read_template_file = 1;  
// # Used for including and executing inline PHP and displaying HTML
$sts_include_template_file = 1;
// # Old method of reading in html file
if ($sts_read_template_file) {
	if (!$fh = fopen($sts_template_file, 'r')) {
		echo 'Can\'t open Template file: '. $sts_template_file;
error_log(print_r('Cant open Template file:' .$sts_template_file . ' by IP - ' . $_SERVER['REMOTE_ADDR'], 1), 1, 'support@zwaveproducts.com');
	}

	$sts_template_file_contents = fread($fh, filesize($sts_template_file));

	if(filesize($sts_template_file) == 0) { 
error_log(print_r('Cant open Template file:' .$sts_template_file . ' by IP - ' . $_SERVER['REMOTE_ADDR'], 1), 1, 'support@zwaveproducts.com');
	}

	fclose($fh);
}
// # See if there are any $url_ or $urlcat_ variables in the template file, if so, flag to read them
if (strpos(stripslashes($sts_template_file_contents), "\$url_") or strpos(stripslashes($sts_template_file_contents), "\$urlcat_") ) {
	$sts_need_url_tags = 1;
} else {
	$sts_need_url_tags = 0;
}
	

// # new method of including template file as executable code and capturing the output
if ($sts_include_template_file) {
	require(STS_START_CAPTURE);

	// We could just eval($sts_template_file_contents) instead, but we would then lose the optimization
	// benefits that a PHP accellerator would offer in caching the compiled code, so we require() the file instead
	require($sts_template_file);
	$sts_block_name = 'template_html';
	require(STS_STOP_CAPTURE);
	$template_html = $sts_block['template_html'];
}


/////////////////////////////////////////////
////// Run any user code needed
/////////////////////////////////////////////
require(STS_USER_CODE);

/////////////////////////////////////////////
////// Set up template variables
/////////////////////////////////////////////

/////////////////////////////////////////////
////// Capture <title> and <meta> tags
/////////////////////////////////////////////

// # STS: ADD: Support for Header Tag Controller
  // # Capture the output
  require(STS_START_CAPTURE);


  if(file_exists(DIR_WS_INCLUDES . 'header_tags.php') ) {
    require_once(DIR_WS_FUNCTIONS . 'clean_html_comments.php');
    require_once(DIR_WS_FUNCTIONS . 'header_tags.php');
    require_once(DIR_WS_INCLUDES . 'header_tags.php');
  } else {
    echo "<title>" . TITLE . "</title>";
  }
  // EOF: Changed: Header Tag Controller v1.0

  $sts_block_name = 'headertags';
  require(STS_STOP_CAPTURE);

// STS: EOADD: Support for WebMakers.com's Header Tag Controller contribution

/////////////////////////////////////////////
////// Set up template variables
/////////////////////////////////////////////

	$template['sid'] =  tep_session_name() . '=' . tep_session_id();

	//$template['sysmsgs'] = $messageStack->output('header');

	// # Strip out <title> variable
	//$template['title'] = str_between($sts_block['headertags'], "<title>", "</title>");

	// # Load up the <head> content that we need to link up everything correctly.  Append to anything that may have been set in sts_user_code.php
	$template['headcontent'] .= $sts_block['headertags'];

	$template['headcontent'] .= '<meta http-equiv="Content-Type" content="text/html; charset=' . CHARSET  . '">' . "\n";

	// # Set Page cache time
	$template['headcontent'] .= '<meta name="Distribution" content="Global">' . "\n";
	$template['headcontent'] .= '<meta http-equiv="Content-Language" content="EN">' . "\n";
	$template['headcontent'] .= '<meta name="robots" content="index, follow">' . "\n";
	$template['headcontent'] .= '<meta name="googlebot" content="index, follow">' . "\n";
	$template['headcontent'] .= '<meta http-equiv="Cache-Control" content="public">' . "\n";
	$template['headcontent'] .= '<meta http-equiv="expires" content="'.date('D, d M Y H:i:s T', strtotime('+2 weeks')).'">' . "\n";
	$template['headcontent'] .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">' . "\n";


	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	// # Detect Safari browser and add Touch Icons
    if(preg_match('/Safari/i',$user_agent)) {

		if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
			$template['headcontent'] .= '<link rel="shortcut icon" href="'. CDN_CONTENT .'/layout/favicon.ico">' . "\n";
			$template['headcontent'] .= '<link rel="apple-touch-icon-precomposed" sizes="152x152" href="'. CDN_CONTENT .'/layout/apple-touch-icon-152x152.png"><link rel="apple-touch-icon-precomposed" sizes="144x144" href="'. CDN_CONTENT .'/layout/apple-touch-icon-144x144.png"><link rel="apple-touch-icon-precomposed" sizes="120x120" href="'. CDN_CONTENT .'/layout/apple-touch-icon-120x120.png"><link rel="apple-touch-icon-precomposed" sizes="76x76" href="'. CDN_CONTENT .'/layout/apple-touch-icon-76x76.png">' . "\n";
		} else {
			$template['headcontent'] .= '<link rel="shortcut icon" href="/layout/favicon.ico">' . "\n";
			$template['headcontent'] .= '<link rel="apple-touch-icon" sizes="152x152" href="/layout/apple-touch-icon-152x152.png"><link rel="apple-touch-icon" sizes="144x144" href="/layout/apple-touch-icon-144x144.png"><link rel="apple-touch-icon" sizes="120x120" href="/layout/apple-touch-icon-120x120.png"><link rel="apple-touch-icon" sizes="76x76" href="/layout/apple-touch-icon-76x76.png">' . "\n";
		}

    }

	// # logic to display alternative layout directory named /layout_mobile/
	// # this is for seperation of mobile/tablet templates and desktop templates
	// # while this can be accomplished in a single well thought out stylesheet, 
	// # sometimes it's better for a complete seperation of the two

	if (!isset($_GET['fullsite'])) {
    	$fullsite = 0;

		// # Mobile Browser Detect 
		if ($mobile_browser > 0) {

			$mobilecss = DIR_FS_SITE_CATALOG.'/layout_mobile/css/mobile.css';

			if(file_exists($mobilecss))  { 

				if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
					$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="'. CDN_CONTENT. '/layout_mobile/css/mobile.css">' . "\n";
				} else {
					$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="/layout_mobile/css/mobile.css">' . "\n";
				}

			} else {

				if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
					$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="'. CDN_CONTENT .'/layout/css/css.css">' . "\n";
				} else {
					$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="/layout/css/css.css">' . "\n";
				}


			}

		} else { 

			if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
				$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="'. CDN_CONTENT .'/layout/css/css.css">' . "\n";
			} else {
				$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="/layout/css/css.css">' . "\n";
			}
		}

	} else {
	
		$fullsite = 1;

		if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
			$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="'. CDN_CONTENT .'/layout/css/css.css">' . "\n";
		} else {
			$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="/layout/css/css.css">' . "\n";
		}
	}

	// # END of layout_mobile logic


	 // # Google mod_pagespeed should flatten these stylesheets if Mod_Pagespeed=on

	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="'. CDN_CONTENT .'/layout/css/cartbox.css">' . "\n";
	} else {
		$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="/layout/css/cartbox.css">' . "\n";
	}


	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="'. CDN_CONTENT .'/layout/css/autosuggestbox.css">' . "\n";
	} else {
		$template['headcontent'] .= '<link rel="stylesheet" type="text/css" media="screen" href="/layout/css/autosuggestbox.css">' . "\n";
	}


	$template['headcontent'] .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>' . "\n";


	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<script type="text/javascript" src="'. CDN_CONTENT .'/js/prototype.lite.js"></script>' . "\n";
	} else {
		$template['headcontent'] .= '<script src="/js/prototype.lite.js"></script>' . "\n";
	}


	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<script type="text/javascript" src="'. CDN_CONTENT .'/js/javascript.js"></script>' . "\n";
	} else {
		$template['headcontent'] .= '<script type="text/javascript" async src="/js/javascript.js"></script>' . "\n";
	}


	$template['headcontent'] .= get_javascript($sts_block['applicationtop2header'],'get_javascript(applicationtop2header)');


	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<script src="'. CDN_CONTENT .'/js/blocks/blk_product_model.js"></script>' . "\n";
	} else {
		$template["headcontent"] .= '<script type="text/javascript" src="/js/blocks/blk_product_model.js"></script>';
	}


	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<script src="'. CDN_CONTENT .'/js/blocks/blk_box_ajax_popup.js"></script>' . "\n";
	} else {
		$template["headcontent"] .= '<script type="text/javascript" src="/js/blocks/blk_box_ajax_popup.js"></script>';
	}


	//if(isset($GLOBALS['qview_desc'])) {
		// # if using, remove from /usr/share/IXcore/common/blocks/box/blk_box_ajax_popup.php
		//$template['headcontent'] .= '<script type="text/javascript" src="/js/blocks/blk_box_ajax_popup.js"></script>' . "\n";
	//}


	if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
		$template['headcontent'] .= '<script type="text/javascript" src="'. CDN_CONTENT .'/js/blocks/blk_image_telescope.js"></script>' . "\n";
	} else {
		$template["headcontent"] .= '<script type="text/javascript" async src="/js/blocks/blk_image_telescope.js"></script>';
	}

	$template["headcontent"] .= "
<script>
function loadfile(filename, type){

	// # if javascript 
	if (type == \"js\"){
		var fileref = document.createElement(\"script\");
		fileref.setAttribute(\"type\",\"text/javascript\");
		fileref.setAttribute(\"src\", filename);

	// # if CSS
	} else if (type==\"css\"){ 
		var fileref=document.createElement(\"link\")
		fileref.setAttribute(\"rel\", \"stylesheet\")
		fileref.setAttribute(\"type\", \"text/css\")
		fileref.setAttribute(\"href\", filename)
	}

	if(typeof fileref!=\"undefined\") { 
		document.getElementsByTagName(\"head\")[0].appendChild(fileref)
	}
}
</script>
";


//# Google Analytics Tracking Logic
if (GOOGLE_ANALYTICS_UID) {
	$getHostname = str_replace('www.', '', $_SERVER['HTTP_HOST']);

	$template_file = str_replace(STS_TEMPLATE_DIR, '', $sts_template_file);

	if(strpos($template_file, 'index,php_') !== false) { 
		$listing = 'listing_category';
	} 

	if($template_file == 'index.php_0.html') { 
		$listing = 'listing_featured';
	} elseif($template_file == 'index,php.html') { 
		$listing = 'listing_category';
	} elseif($template_file == 'index.php_mfr.html') {
		$listing = 'listing_manufacturer';
	} elseif($template_file == 'advanced_search_result.php.html') {
		$listing = 'listing_search_result';
	} elseif($template_file == 'shopping_cart.php.html') {
		$listing = 'xsell_cart';
	}

	$template["headcontent"] .= "
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', '".GOOGLE_ANALYTICS_UID ."', '". $getHostname ."');
	ga('require', 'displayfeatures');
	ga('send', 'pageview');
	ga('require', 'ec');";


	if(tep_session_is_registered('customer_id')) {

		$template["headcontent"] .= "
			ga('set', 'userId', '".$_SESSION["customer_id"]."');
		";
	}

	$template["headcontent"] .= "

	function GAImpressionData(product) { 
	// # Google Universal Analytics 
	// # Enhanced eCommerce - Product Impression

		/*
		// # important! do not count impression for product details pages! 
		// # product details page impressions are tracked separately as 'ec:setAction', 'detail'
		// # product.listing member is defined only for root product model inside blk_product_model
		// # product.listing will return null on all listings other then product detail page
		*/

		if(product.pmodel != product.listing){

			// # drill into the currently selected model, if any.
			var variant = '';
			for(key in product.currAttr) {
				variant = product.optns[key]['values'][product.currAttr[key]]['name'];
			}

				// # adjust product.id by splitting in half to get actual position in grid

				var position = Math.ceil((parseInt(product.id) / 2));

				ga('ec:addImpression', {
		    		'id': product.pid,
		    		'name': product.pname,
   					'category': product.pcat,
    				'brand': product.pbrand,
    				'variant': (variant ? variant : undefined),
 					'list': '". $listing."',
 					'position': position
				});

			ga('send', 'pageview');
		} 

	}	// # END Google UA Enhanced eCommerce - Product Impression


	function GAImpressionClick(product) {
		// # Google Universal Analytics 
		// # Enhanced eCommerce - Impression counter for products

		if(product.pmodel != product.listing){
	
			// # drill into the currently selected model, if any.
			var variant = '';
			for(key in product.currAttr) {
				variant = product.optns[key]['values'][product.currAttr[key]]['name'];
			}

			var position = Math.ceil((parseInt(product.id) / 2));

			ga('ec:addProduct', {
		    	'id': product.pid,
		    	'name': product.pname,
   				'category': product.pcat,
	    		'brand': product.pbrand,
    			'variant': (variant ? variant : undefined),
 				'position': position
 			});
		
			ga('ec:setAction', 'click', { list: '".$listing."' });

			// # Send click with an event, then send user to product page.
  			ga('send', 'event', 'UX', 'click', 'Product Click');

		}
	}


	function GAproductView(product) { 
		// # Google Universal Analytics 
		// # Enhanced eCommerce - Product Detail page view

		// # only count product detail view if listing matches pmodel

		if(product.pmodel == product.listing){

			// # drill into the currently selected model, if any.
			var variant = ''; 
			for(key in product.currAttr) {
				variant = product.optns[key]['values'][product.currAttr[key]]['name'];
			}

			ga('ec:addProduct', {
		    	'id': product.pid,
		    	'name': product.pname,
   				'category': product.pcat,
    			'brand': product.pbrand,
    			'variant': (variant ? variant : undefined)
			});

			ga('ec:setAction', 'detail');
			ga('send', 'pageview');

		}
	}


	function GAaddToCart(product) {

		// # Google Universal Analytics 
		// # Enhanced eCommerce - Add to Cart / Conversion funnel

		// # drill into the currently selected model, if any.
		var variant = '';
		for(key in product.currAttr) {
			variant = product.optns[key]['values'][product.currAttr[key]]['name'];
		}
	

			ga('ec:addProduct', {
	    		'id': product.pid,
	    		'name': product.pname,
				'category': product.pcat,
   				'brand': product.pbrand,
   				'variant': (variant ? variant : undefined),
				'price': product.currPrice,
				'quantity': product.qty
			});
		
	
		ga('ec:setAction', 'add');
		ga('send', 'event', 'UX', 'click', 'add to cart');

		// # END Google UA Enhanced eCommerce - Add to Cart / Conversion funnel
	//console.log(product.pid+', '+product.pname+', '+product.pcat+', '+product.pbrand+', '+variant+', '+product.price+', '+product.qty);
	}

function GACartAdjust(product) {

	// # Google Universal Analytics 
	// # Enhanced Ecommerce - Cart Adjustment setAction

	var action ='';
	var qty ='0';
	var old_qty = product.qty;
	var new_qty = document.getElementById('cart_quantity_'+product.pid).value;

	// # if the new qty equals zero, 
	// # send full quantity to be removed

	if(new_qty == 0) {
		action ='remove';
		qty = old_qty;
	}

	if(document.getElementById('remove_all_'+product.pid).checked) {
		action ='remove'
		qty = old_qty;
	}

	// # if new quanity is LESS then original
	// # send the removed item total to GA, not new total in cart!.

	if(new_qty < old_qty) { 

		action ='remove'
		qty = (old_qty - new_qty);

	} else if(new_qty > old_qty) {

	// # ELSE if new quanity is MORE then original
	// # send the difference in item total to GA, not new total in cart!.

		action = 'add';
		qty = (new_qty - old_qty);
	}

	if(action == 'add' || action == 'remove') { 

		  ga('ec:addProduct', {
    		'id': product.pid,
	    	'name': product.pname,
	    	'category': product.pcat,
		    'brand': product.pbrand,
    		'variant': (product.currAttr ? product.currAttr : undefined),
		    'price': product.price,
    		'quantity': qty
		  });
	

		ga('ec:setAction', action);

		// # Also send event.

		if(action == 'remove') {
			ga('send', 'event', 'Adjust Cart', 'click', 'Remove from cart');	
		} else if (action == 'add') {
			ga('send', 'event', 'Adjust Cart', 'click', 'Add Item quantities');
		}

	}

}

function GAcheckout(cart, step) {
	// # Google Universal Analytics 
	// # Enhanced Ecommerce - Checkout Funnel steps

	var step = step;
	// # loop through the master cart object and grab line items
	for(var i = 0; i < cart.length; i++) {

		var product = cart[i];

			ga('ec:addProduct', {
				'id': product.pid,
		    	'name': product.pname,
		    	'category': product.pcat,
			    'brand': product.pbrand,
		 		'variant': (product.currAttr ? product.currAttr : undefined),
			    'price': product.price,
    			'quantity': product.qty	
			});
	}
		ga('ec:setAction','checkout', {'step':step});
		ga('send', 'pageview');

}


function onOptionSelect(step, option, mode) {
	// # Google Universal Analytics 
	// # Enhanced Ecommerce - Checkout Options
	// # usage: 
	// # step - step in checkout funnel as defined in GA
	// # option - the current option value
	// # mode - shipping or paymentType or applyCoupon

	if(option) { 	

		if(mode =='shipping') { 
			option = option.replace(/^.*_/g, '');
		}
	
		ga('ec:setAction', 'checkout_option', {
			'step': step,
			'option': option
		});

		if(mode =='shipping') { 
			ga('send', 'event', 'Checkout', 'Shipping Option');
		} else if(mode == 'paymentType') {
			ga('send', 'event', 'Checkout', 'Payment Type');
		} else if(mode == 'applyCoupon') {
			ga('send', 'event', 'Checkout', 'Coupon');
		}
	}
}


function onGAPromo(promoid, name, creative, position, mode) {
	// # Google Universal Analytics 
	// # Enhanced Ecommerce - Promotional Events
	// # usage: 
	// # name - name of promotion
	// # creative - name of the creative or image
	// # position - position in the template
	// # mode - impression or click

	if(!mode) {
		var mode='impression';
	}

	if(promoid) { 

		// # Promo details provided in a promoFieldObject.
		ga('ec:addPromo', {             
 			'id': promoid,
			'name': name,
			'creative': creative,
			'position': position
		});

		if(mode == 'click') { 
			ga('ec:setAction', 'promo_click');
			ga('send', 'event', 'Internal Promotions', mode, name);
		}

	}
}";

	$prodObject = $cart->get_products();

	if(!empty($prodObject)) {
		foreach ($prodObject as $products) {

			$pid = $products['products_id'];
			$pname = $products['products_name'];
			$pcat = $products['categories_name'];
			$pbrand = $products['manufacturers_name'];
			$variant = $products['attributes'];
			$price = number_format($products['products_price'],2);
			$qty = $products['quantity'];
		
			if(!empty($variant)) {
				foreach($variant as $key => $val) { 
					$template["headcontent"] .= "
						window.product_".$pid." = {
							pid: '". $pid."',
							pname: '".$pname."',
							pcat: '". $pcat."',
							pbrand: '".$pbrand."',
							currAttr: '".$val."',
							price: '".$price."',
							qty: '".$qty."'
						};
					";	
				}

			} else { 

				$template["headcontent"] .= "
					window.product_".$pid." = {
						pid: '". $pid."',
						pname: '".$pname."',
						pcat: '". $pcat."',
						pbrand: '".$pbrand."',
						price: '".$price."',
						qty: '".$qty."'
					};
				";	
			}
			
		}

		$template["headcontent"] .= "
		// # loop through all product_ objects and add to one object for cart submission


			jQuery(function() {
				jQuery.concat || jQuery.extend({
					concat:function(b,c){
					var a=[];
					for(x in arguments) a = a.concat(arguments[x]);
           			return a;
       			}
   			});
   
				var thearr=[];
				for (var p in window) {
					if(p.match(/^product_.*/)) {
						thearr.push(window[p]);
					}
				}

				window.GAcart = jQuery.concat(thearr);

			";

			if($template_file == 'shopping_cart.php.html') { 
				$template["headcontent"] .= "GAcheckout(window.GAcart, '1');	";
			} elseif($template_file == 'checkout.php.html') { 
				$template["headcontent"] .= "GAcheckout(window.GAcart, '2');	";
			} 
			
		$template["headcontent"] .= "
		});
		";

	}


	$template["headcontent"] .= "</script>" . "\n";

} 
	// # END - Google Analytics Tracking Logic


  $template['headcontent'] .= '</head><body>';

  // Note: These values lifted from the stock /catalog/includes/header.php script's HTML
  // catalogurl: url to catalog's home page
  // catalog: link to catalog's home page
  $template['cataloglogo'] = '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image(DIR_WS_IMAGES . 'logo.gif', 'intenseCart') . '</a>';
  $template['urlcataloglogo'] = tep_href_link(FILENAME_DEFAULT);

  $template['myaccountlogo'] = '<a href="' . tep_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">' . tep_image(DIR_WS_IMAGES . 'header_account.gif', HEADER_TITLE_MY_ACCOUNT) . '</a>';
  $template['urlmyaccountlogo'] = tep_href_link(FILENAME_ACCOUNT, '', 'SSL');

  $template['cartlogo'] = '<a href="' . tep_href_link(FILENAME_SHOPPING_CART) . '">' . tep_image(DIR_WS_IMAGES . 'header_cart.gif', HEADER_TITLE_CART_CONTENTS) . '</a>';
  $template['urlcartlogo'] = tep_href_link(FILENAME_SHOPPING_CART);

  $template['checkoutlogo'] = '<a href="' . tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . '">' . tep_image(DIR_WS_IMAGES . 'header_checkout.gif', HEADER_TITLE_CHECKOUT) . '</a>';
  $template['urlcheckoutlogo'] = tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL');

  $template['breadcrumbs'] = $breadcrumb->trail('&nbsp; <font class="breadcrumb_raquo">&raquo;</font>&nbsp; ');

  if (tep_session_is_registered('customer_id')) {

    $template['myaccount'] = '<a href=' . tep_href_link(FILENAME_ACCOUNT, '', 'SSL') . ' class="headerNavigation">' . HEADER_TITLE_MY_ACCOUNT . '</a>';
    $template['urlmyaccount'] = tep_href_link(FILENAME_ACCOUNT, '', 'SSL');
    $template['logoff'] = '<a href=' . tep_href_link(FILENAME_LOGOFF, '', 'SSL')  . ' class="headerNavigation">' . HEADER_TITLE_LOGOFF . '</a>';
    $template['urllogoff'] = tep_href_link(FILENAME_LOGOFF, '', 'SSL');
    $template['myaccountlogoff'] = $template['myaccount'] . " | " . $template['logoff'];
  } else {
    $template['myaccount'] = '<a href=' . tep_href_link(FILENAME_ACCOUNT, '', 'SSL') . ' class="headerNavigation">' . HEADER_TITLE_MY_ACCOUNT . '</a>';
    $template['urlmyaccount'] = tep_href_link(FILENAME_ACCOUNT, '', 'SSL');
    $template['logoff'] = '';
    $template['urllogoff'] = '';
    $template['myaccountlogoff'] = $template['myaccount'];
  }

  $template['cartcontents']    = '<a href=' . tep_href_link(FILENAME_SHOPPING_CART) . ' class="headerNavigation">' . HEADER_TITLE_CART_CONTENTS . '</a>';
  $template['urlcartcontents'] = '<a href=' . tep_href_link(FILENAME_SHOPPING_CART) . ' class="headerNavigation">' . HEADER_TITLE_CART_CONTENTS . '</a>';

  $template['checkout'] = '<a href=' . tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') . ' class="headerNavigation">' . HEADER_TITLE_CHECKOUT . '</a>';
  $template['urlcheckout'] = tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL');

/////////////////////////////////////////////
////// Create custom boxes
/////////////////////////////////////////////
  $template['categorybox'] = strip_unwanted_tags($sts_block['categorybox'], 'categorybox');
  $template['manufacturerbox'] = strip_unwanted_tags($sts_block['manufacturerbox'], 'manufacturerbox');
  $template['whatsnewbox'] = strip_unwanted_tags($sts_block['whatsnewbox'], 'whatsnewbox');
  $template['searchbox'] = strip_unwanted_tags($sts_block['searchbox'], 'searchbox');
  $template['informationbox'] = strip_unwanted_tags($sts_block['informationbox'], 'informationbox');
  $template['cartbox'] = strip_unwanted_tags($sts_block['cartbox'], 'cartbox');
  $template['maninfobox'] = strip_unwanted_tags($sts_block['maninfobox'], 'maninfobox');
  $template['orderhistorybox'] = strip_unwanted_tags($sts_block['orderhistorybox'], 'orderhistorybox');
  $template['bestsellersbox'] = strip_unwanted_tags($sts_block['bestsellersbox'], 'bestsellersbox');
  $template['specialfriendbox'] = strip_unwanted_tags($sts_block['specialfriendbox'], 'specialfriendbox');
  $template['reviewsbox'] = strip_unwanted_tags($sts_block['reviewsbox'], 'reviewsbox');
  $template['languagebox'] = strip_unwanted_tags($sts_block['languagebox'], 'languagebox');
  $template['currenciesbox'] = strip_unwanted_tags($sts_block['currenciesbox'], 'currenciesbox');
  if ($page_404) {
    require(STS_START_CAPTURE);
    require(DIR_FS_CATALOG . '404.php');
    $sts_block_name = 'content';
    require(STS_STOP_CAPTURE);
    
    $template['content'] = $sts_block['content'];
  } else { 
    $template['content'] = strip_content_tags($sts_block['columnleft2columnright'], 'content');
  }
  // Prepend any error/warning messages to $content
  if ($messageStack->size('header') > 0) {
    $template['content'] = $messageStack->output('header') . $template['content'];
  }
  $template['date'] = strftime(DATE_FORMAT_LONG);
  $template['numrequests'] = $counter_now . ' ' . FOOTER_TEXT_REQUESTS_SINCE . ' ' . $counter_startdate_formatted;
  $template['counter'] = $sts_block['counter'];
  $template['footer'] = $sts_block['footer'];
  $template['banner'] = $sts_block['banner'];

  
/////////////////////////////////////////////
////// Get Categories
/////////////////////////////////////////////

if ($sts_need_url_tags) {
	print "<!-- STS: Reading $url_ and $urlcat_ tags, recommend not using them -->";
	$get_categories_description_query = tep_db_query("SELECT categories_id, categories_name FROM " . TABLE_CATEGORIES_DESCRIPTION);
	// Loop through each category (in each language) and create template variables for each name and path
	while ($categories_description = tep_db_fetch_array($get_categories_description_query)) {
	      $cPath_new = tep_get_path($categories_description['categories_id']);
	      $path = substr($cPath_new, 6); // Strip off the "cPath=" from string

//fix for sts $urlcat vars messing up when using SEF urls
	  if (strlen($path) >= 4) {
	  	if (substr($path, 4,1) == '_') {
			$path = substr($path, 5);
			$cPath_new = 'cPath=' . $path; 
		}
		else if (substr($path, 5,1) == '_') {
			$path = substr($path, 6);
			$cPath_new = 'cPath=' . $path;
		}
	   }
//end SEF url fix
        
	      $catname = $categories_description['categories_name'];
	      $catname = str_replace(" ", "_", $catname); // Replace Spaces in Category Name with Underscores
	
	      $template["cat_" . $catname] = tep_href_link(FILENAME_DEFAULT, $cPath_new);
	      $template["urlcat_" . $catname] = tep_href_link(FILENAME_DEFAULT, $cPath_new);
	      $template["cat_" . $path] = tep_href_link(FILENAME_DEFAULT, $cPath_new);
	      $template["urlcat_" . $path] = tep_href_link(FILENAME_DEFAULT, $cPath_new);

	}
}

/////////////////////////////////////////////
////// Display Template HTML
/////////////////////////////////////////////

  // Sort array by string length, so that longer strings are replaced first
  uksort($template, "sortbykeylength");



  $template['searchsuggestbox'] = $sts_block['searchsuggestbox'];



  // Manually replace the <!--$headcontent--> if present
    $template_html = str_replace(array('<!--$headcontent-->', '$headcontent'), $template['headcontent'], $template_html);


class blk_sts extends IXblock {

	function blk_sts() {

		global $HTTP_GET_VARS, $languages_id, $current_category_id;

		if( isset($HTTP_GET_VARS['products_id'])) {

			$this->prod_query = tep_db_query("SELECT pd.*, p.*, m.manufacturers_name
											  FROM ".TABLE_PRODUCTS." p 
											  LEFT JOIN products_description pd ON (pd.products_id=p.master_products_id AND pd.language_id='$languages_id') 
											  LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
											  WHERE p.products_id='".$HTTP_GET_VARS['products_id']."'
											  ");

			$this->prod = tep_db_fetch_array($this->prod_query);

		}

    	if (isset($current_category_id)) {
			$this->cid=$current_category_id;
		}

    	if(isset($HTTP_GET_VARS['manufacturers_id'])) { 
			$this->mid=$HTTP_GET_VARS['manufacturers_id'];
		} elseif (isset($HTTP_GET_VARS['mfr_id'])) {
			$this->mid=$HTTP_GET_VARS['mfr_id'];
		}
	}

	function getVar($var,$args) {
		switch ($var) {
			case 'odd_even': return ($this->odd_even[$args['key']]=!$this->odd_even[$args['key']])?'odd':'even';
			case 'sysmsgs': return $GLOBALS['messageStack']->output(isset($args['scope'])?$args['scope']:'header');
			case 'sts_block': return $GLOBALS['sts_block'][$args['name']];
			case 'POST': return $_POST[$args['field']].'';
			case 'GET': return $_GET[$args['field']].'';
		}

		return tmpl_tag($var);
	}


	function getProductField($fld) {
		return ($this->prod ? $this->prod[$fld] : NULL);
		tep_db_free_result($this->prod_query);
	}


	function exportContext() {
		global $HTTP_GET_VARS;
		$ctxt = array();
		$ctxt['root']=&$this;

		if(isset($HTTP_GET_VARS['products_id'])) {
			$this->product_obj=$this->block('blk_product_main');
			$this->product_obj->setContext(Array(),Array());
			$this->product_obj->setData($HTTP_GET_VARS['products_id']);
			$ctxt['product']=&$this->product_obj;
		}

		if(isset($this->cid)) $ctxt['category']=&$this;
		if(isset($this->mid)) $ctxt['manufacturer']=&$this;
		return $ctxt;
	}

	function getPageArg($arg,$val=NULL) {
		return isset($GLOBALS['HTTP_GET_VARS'][$arg])?$GLOBALS['HTTP_GET_VARS'][$arg]:$val;
	}

	function pageUrl($args=NULL) {
		$argl='';
		if(isset($args['page']) && $args['page']!=1) {
			$argl.='&page='.$args['page'];
		}

		if(isset($args['sort'])) {
			$argl.='&sort='.$args['sort'];
		}

    	if(isset($args['mfr_id'])) {
    		if ($args['mfr_id']) {
				$argl.='&mfr_id='.$args['mfr_id'];
			}
	    } elseif(isset($GLOBALS['HTTP_GET_VARS']['mfr_id'])) {
			$argl.='&mfr_id='.$GLOBALS['HTTP_GET_VARS']['mfr_id'];
		}
    		return tep_href_link('index.php','cPath='.$GLOBALS['cPath'].$argl);
		}
	}


function tmpl_expand($html) {
  return preg_replace('/\$(\w+)/e','tmpl_tag("\1")',$html);
}

function tmpl_tag($tag) {
  global $template;
  if (isset($template[$tag])) return tmpl_expand($template[$tag]);
  for ($ftag=$tag;$ftag;$ftag=preg_replace('/_?[^_]+$/','',$ftag)) {
    if (function_exists($fn="tmpl_tag_$ftag")) {
      $rs=$fn($tag);
      if (isset($rs)) return $rs;
    }
  }
  return '$'.$tag;
}


    include_once(DIR_FS_COMMON.'functions/template.php');

	//$tp = tep_parse_template($template_html);
    $tp = IXblock::parse($template_html);

    
	//print_r($tp);
	// exit;

    $STSblk=new blk_sts();
    $STSblk->render($tp);

/*
	//$template_html = tmpl_expand($template_html);

	// # Automatically replace all the other template variables
	//  foreach ($template as $key=>$value) {
	//    $template_html = str_replace('$' . $key, $value, $template_html);
	//  }

  if (isset($_GET['show_shit'])) {
    echo join('<br>',array_keys($template));
    echo '<br><hr>';
  }


  if ($display_template_output == 1) {
    if (tep_not_null($_SESSION['language_translate']) && $_SESSION['language_translate'] != 'en') {
      echo translate($template_html, $_SESSION['language_translate']);
    } else {
      echo $template_html;
    }
  }

*/
  $sts_block['header'] = str_replace('<!-- header //-->', '', $sts_block['header']);

/////////////////////////////////////////////
////// Display HTML
/////////////////////////////////////////////
 if ($display_normal_output == 1) {
  echo $sts_block['applicationtop2header'];
  echo $sts_block['header'];


  echo $sts_block['header2columnleft'];

  // print column_left stuff
  echo $sts_block['categorybox'];
  echo $sts_block['manufacturerbox'];
  echo $sts_block['whatsnewbox'];
  echo $sts_block['searchbox'];
  echo $sts_block['informationbox'];

  echo $sts_block['columnleft2columnright'];

  // print column_right stuff
  echo $sts_block['cartbox'];
  echo $sts_block['maninfobox'];
  echo $sts_block['orderhistorybox'];
  echo $sts_block['bestsellersbox'];
  echo $sts_block['specialfriendbox'];
  echo $sts_block['reviewsbox'];
  echo $sts_block['languagebox'];
  echo $sts_block['currenciesbox'];
  echo $sts_block['columnright2footer'];

  // print footer
  echo $sts_block['content'];
  echo $sts_block['counter'];
  echo $sts_block['footer'];
  echo $sts_block['banner'];
 }
/////////////////////////////////////////////
////// End Display HTML
/////////////////////////////////////////////

	if ($display_debugging_output == 1) {
		// # Print Debugging Info
		print "\n<pre><hr>\n";
		print "STS_TEMPLATE=[" . $sts_template_file . "]<hr>\n";

		// # Replace $variable names in $sts_block_html_* with variables from the $template array
		foreach ($sts_block as $key=>$value) {
			print "<b>\$sts_block['$key']</b><hr>" . $value . "<hr>\n";
		}

		foreach ($template as $key=>$value) {
			print "<b>\$template['$key']</b><hr>" . $value . "<hr>\n";
		}

	}

 if ($display_normal_output == 1) {
  echo $sts_block['footer2applicationbottom'];
 }

  function translate($buffer, $lng) {
   $filename = 'translate/translateme' . date("U") . '.htm';
   $handle = fopen(DIR_FS_SITE_CATALOG.$filename, 'w');
   fwrite($handle, $buffer);
   fclose($handle);

   $ch = curl_init();

   curl_setopt($ch, CURLOPT_URL, 'http://babelfish.altavista.com/babelfish/trurl_pagecontent?lp=en_' . $lng . '&url=' . urlencode('http://' . $_SERVER['SERVER_NAME'] . '/' . $filename));
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8) Gecko/20051111 Firefox/1.5");
   curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
   $content = curl_exec ($ch);

   curl_close ($ch);

   unlink(DIR_FS_SITE_CATALOG.$filename);

   $content = str_replace('http://babelfish.altavista.com/babelfish/trurl_pagecontent?lp=en_' . $lng . '&trurl=http%3a%2f%2f', 'http://', $content);
   $content = str_replace('%2f', '/', $content);
   $content = str_replace('%3f', '?', $content);
   $content = str_replace('%3d', '=', $content);
   $content = str_replace('http://babelfish.altavista.com/babelfish/trurl_pagecontent', '"' . $_SERVER['REQUEST_URI'] . '"', $content);
   $content = substr($content, 0, strpos($content, '</html>') + 7);
   return utf8_decode($content);
  }

// # STRIP_UNWANTED_TAGS() - Remove leading and trailing <tr><td> from strings
function strip_unwanted_tags($tmpstr, $commentlabel) {

//    return $tmpstr;
//    return "<table>$tmpstr</table>";


  // Now lets remove the <tr><td> that the require puts in front of the tableBox
  $tablestart = strpos($tmpstr, "<table");

  // If empty, return nothing
  if ($tablestart < 1) {
  	//return  "\n<!-- start $commentlabel //-->\n$tmpstr\n<!-- end $commentlabel //-->\n";
    return $tmpstr;
  }

  $tmpstr = substr($tmpstr, $tablestart); // strip off stuff before <table>

  // Now lets remove the </td></tr> at the end of the tableBox output
  // strrpos only works for chars, not strings, so we'll cheat and reverse the string and then use strpos
  $tmpstr = strrev($tmpstr);

  $tableend = strpos($tmpstr, strrev("</table>"), 1);
  $tmpstr = substr($tmpstr, $tableend);  // strip off stuff after </table>

  // Now let's un-reverse it
  $tmpstr = strrev($tmpstr);

  // print "<hr>After cleaning tmpstr:" . strlen($tmpstr) . ": FULL=[".  htmlspecialchars($tmpstr) . "]<hr>\n";
  return $tmpstr; //"\n<!-- start $commentlabel //-->\n$tmpstr\n<!-- end $commentlabel //-->\n";
}


// STRIP_CONTENT_TAGS() - Remove text before "body_text" and after "body_text_eof"
function strip_content_tags($tmpstr, $commentlabel) {
  // Now lets remove the <tr><td> that the require puts in front of the tableBox
  $tablestart = strpos($tmpstr, "<table");
  $formstart = strpos($tmpstr, "<form");

  // If there is a <form> tag before the <table> tag, keep it
  if ($formstart !== false and $formstart < $tablestart) {
     $tablestart = $formstart;
     $formfirst = true;
  }

  // If empty, return nothing
  if ($tablestart < 1) {
        return $tmpstr; // "\n<!-- start $commentlabel //-->\n$tmpstr\n<!-- end $commentlabel //-->\n";
  }
  
  $tmpstr = substr($tmpstr, $tablestart); // strip off stuff before <table>

  // Now lets remove the </td></tr> at the end of the tableBox output
  // strrpos only works for chars, not strings, so we'll cheat and reverse the string and then use strpos
  $tmpstr = strrev($tmpstr);

  if ($formfirst == true) {
    $tableend = strpos($tmpstr, strrev("</form>"), 1);
  } else {
    $tableend = strpos($tmpstr, strrev("</table>"), 1);
  } 

  $tmpstr = substr($tmpstr, $tableend);  // strip off stuff after <!-- body_text_eof //-->

  // Now let's un-reverse it
  $tmpstr = strrev($tmpstr);

  // print "<hr>After cleaning tmpstr:" . strlen($tmpstr) . ": FULL=[".  htmlspecialchars($tmpstr) . "]<hr>\n";
  return $tmpstr; // "\n<!-- start $commentlabel //-->\n$tmpstr\n<!-- end $commentlabel //-->\n";
}


function get_javascript($tmpstr, $commentlabel) {
  // Now lets remove the <tr><td> that the require puts in front of the tableBox
  $tablestart = strpos($tmpstr, "<script");

  // If empty, return nothing
  if ($tablestart === false) {
	return "\n\n";
  }

  $tmpstr = substr($tmpstr, $tablestart); // strip off stuff before <table>

  // Now lets remove the </td></tr> at the end of the tableBox output
  // strrpos only works for chars, not strings, so we'll cheat and reverse the string and then use strpos
  $tmpstr = strrev($tmpstr);

  $tableend = strpos($tmpstr, strrev("</script>"), 1);
  $tmpstr = substr($tmpstr, $tableend);  // strip off stuff after </table>

  // Now let's un-reverse it
  $tmpstr = strrev($tmpstr);

  // print "<hr>After cleaning tmpstr:" . strlen($tmpstr) . ": FULL=[".  htmlspecialchars($tmpstr) . "]<hr>\n";
  return $tmpstr;
 // "\n<!-- start $commentlabel //-->\n$tmpstr\n<!-- end $commentlabel //-->\n";
}

// Return the value between $startstr and $endstr in $tmpstr
function str_between($tmpstr, $startstr, $endstr) {
  $startpos = strpos($tmpstr, $startstr);

  // If empty, return nothing
  if ($startpos === false) {
        return  "";
  }

  $tmpstr = substr($tmpstr, $startpos + strlen($startstr)); // strip off stuff before $start

  // Now lets remove the </td></tr> at the end of the tableBox output
  // strrpos only works for chars, not strings, so we'll cheat and reverse the string and then use strpos
  $tmpstr = strrev($tmpstr);

  $endpos = strpos($tmpstr, strrev($endstr), 1);

  $tmpstr = substr($tmpstr, $endpos + strlen($endstr));  // strip off stuff after </table>

  // Now let's un-reverse it
  $tmpstr = strrev($tmpstr);

  return  $tmpstr;
}

function sortbykeylength($a,$b) {
  $alen = strlen($a);
  $blen = strlen($b);
  if ($alen == $blen) $r = 0;
  if ($alen < $blen) $r = 1;
  if ($alen > $blen) $r = -1;
  return $r;
}


?>



