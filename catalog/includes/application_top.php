<?php
	date_default_timezone_set('UCT');

	// # santize mod_security

	if(function_exists('tep_clean_get__recursive')) {
	
		// # Recursively clean $HTTP_GET_VARS and $_GET
		// # There is no legitimate reason for these to contain anything but ..
		// # A-Z a-z 0-9 -(hyphen).(dot)_(underscore) {} space

		$HTTP_GET_VARS = tep_clean_get__recursive($HTTP_GET_VARS);
		$_GET = tep_clean_get__recursive($_GET);
		$_REQUEST = $_GET + $_POST; // # $_REQUEST now holds the cleaned $_GET and std $_POST. $_COOKIE has been removed.
	}


	// # start the timer for the page parse time log
	define('PAGE_PARSE_START_TIME', microtime());

	// # set the level of error reporting
	 error_reporting(E_ALL & ~E_NOTICE);
	
	foreach ($_GET as $key => $value) {
		if (strpos($key, 'amp;') !== false) {
			$HTTP_GET_VARS[str_replace('amp;', '', $key)] = $value;
			unset($HTTP_GET_VARS[$key]);
		}
	}

	foreach ($HTTP_GET_VARS as $key => $value) {
		if (strpos($key, 'amp;') !== false) {
			$HTTP_GET_VARS[str_replace('amp;', '', $key)] = $value;
			unset($HTTP_GET_VARS[$key]);
		}
	}

	// # Allow disabling of ALL STS capture and display routines by setting this variable or passing ?no_sts=1 as a parameter
	// # moved this code after error_reporting, otherwise gives alarm on some systems
	if (!isset($no_sts)) $no_sts=0;

	if($HTTP_GET_VARS["no_sts"] > 0) {
		$no_sts=1;
	}

	// # check if register_globals is enabled.
	// # since this is a temporary measure this message is hardcoded. The requirement will be removed before 2.2 is finalized.
	if (function_exists('ini_get')) {
		ini_get('register_globals') or exit('FATAL ERROR: register_globals is disabled in php.ini, please enable it!');
	}

	$REQUEST_URI_PATH=preg_replace('|\?.*|','',$REQUEST_URI);

	if($REQUEST_URI_PATH != $PHP_SELF && DIR_CGI_CATALOG.$REQUEST_URI_PATH != $PHP_SELF && (strpos($REQUEST_URI, '.asp') !== false || strpos($REQUEST_URI, '.html') !== false)) {
		include('includes/redirects.php');
	
		foreach ($redirect_array as $r) {
			if( $REQUEST_URI == $r[0] ) {
				header("HTTP/1.0 301 Moved Permanently");
				header('Location: ' . $r[1]);
			}
		}
	}

 
	// # include server parameters
	require('../common/configure.php');
	require('includes/configure.php');

	//require('../common/service/errorhandler.php');
  
  
	putenv('ixcore_db_password=');
	unset($_SERVER['ixcore_db_password']);


	if (file_exists(DIR_FS_CATALOG_LOCAL.'domain_redirect.php')) {
		include_once(DIR_FS_CATALOG_LOCAL.'domain_redirect.php');
	}

	if(!isset($domain_redirect)) $domain_redirect = array();

	if($_SERVER['HTTP_HOST']!=SITE_DOMAIN && $_SERVER['HTTP_HOST']!=SITE_SHARED_DOMAIN) {
		$redir='';

		if(isset($domain_redirect[$_SERVER['HTTP_HOST']])) $redir=$domain_redirect[$_SERVER['HTTP_HOST']];

		if ($redir!='') {

			if (!preg_match('|^\w+:/|',$redir)) {
				$redir = preg_replace('|^\/*|','http://'.SITE_DOMAIN.'/',$redir);
			}

			header("HTTP/1.0 301 Redirect");
			header("Location: $redir");
			exit();
		}
	}

	// # define the project version
	//define('PROJECT_VERSION', 'IntenseCart');

	// # set the type of request (secure or not)
	//$request_type = (getenv('HTTPS') == 'on') ? 'SSL' : 'NONSSL';
	$request_type = ($SERVER_PORT == '443') ? 'SSL' : 'NONSSL';

	// # set php_self in the local scope
	if (!isset($PHP_SELF)) $PHP_SELF = $HTTP_SERVER_VARS['PHP_SELF'];

	if($request_type == 'NONSSL') {
		define('DIR_WS_CATALOG', DIR_WS_HTTP_CATALOG);
	} else {
		define('DIR_WS_CATALOG', DIR_WS_HTTPS_CATALOG);
	}

	// # include the list of project filenames
	require(DIR_WS_INCLUDES . 'filenames.php');

	// # include the list of project database tables
	require(DIR_WS_INCLUDES . 'database_tables.php');

	// # customization for the design layout
	define('BOX_WIDTH', 125); // how wide the boxes should be in pixels (default: 125)

	// # include the database functions
	require(DIR_WS_FUNCTIONS . 'database.php');

	// # make a connection to the database... now
	tep_db_connect() or die('Unable to connect to database server!');

	require(DIR_FS_COMMON . 'functions/general.php');
	define('DIR_FS_MODULES',DIR_FS_CATALOG_MODULES);
	require(DIR_FS_COMMON . 'functions/module.php');
	require(DIR_FS_COMMON . 'functions/block.php');
	require(DIR_FS_COMMON . 'modules/product/IXproduct.php');

	// # set application wide parameters
	tep_read_config();

	require(DIR_FS_COMMON.'database_updates.php');

	// # if gzip_compression is enabled, start to buffer the output
	if( (GZIP_COMPRESSION == 'true') && ($ext_zlib_loaded = extension_loaded('zlib')) && (PHP_VERSION >= '4') ) {
		if (($ini_zlib_output_compression = (int)ini_get('zlib.output_compression')) < 1) {
			if (PHP_VERSION >= '4.0.4') {
				ob_start('ob_gzhandler');
			} else {
				include(DIR_WS_FUNCTIONS . 'gzip_compression.php');
				ob_start();
    			ob_implicit_flush();
			}

		} else {
			 ini_set('zlib.output_compression_level', GZIP_LEVEL);
		}
	}

/*
  $page_404 = false;


  $req_url = parse_url($_SERVER['REQUEST_URI']);
  $split_url = explode('/', $req_url['path']!=''?$req_url['path']:'/');
  
  array_shift($split_url);
  
  if (sizeof($split_url) <= 1 || !is_numeric(str_replace('_', '', substr($split_url[0], 1))) || (substr($split_url[0], 0, 1) != 'C' && substr($split_url[0], 0, 1) != 'P')) {
    if (!file_exists(DIR_FS_CATALOG . substr($req_url['path'], 1))) {
      header("HTTP/1.0 404 Not Found");
      $page_404 = true;
    }
  }
*/

	// # set the HTTP GET parameters manually if search_engine_friendly_urls is enabled
	if (SEARCH_ENGINE_FRIENDLY_URLS == 'true') {
		if (strlen(getenv('PATH_INFO')) > 1) {
			$GET_array = array();
			$PHP_SELF = str_replace(getenv('PATH_INFO'), '', $PHP_SELF);
			$vars = explode('/', substr(getenv('PATH_INFO'), 1));

			for ($i=0, $n=sizeof($vars); $i<$n; $i++) {

				if (strpos($vars[$i], '[]')) {
					$GET_array[substr($vars[$i], 0, -2)][] = $vars[$i+1];
				} else {
					$HTTP_GET_VARS[$vars[$i]] = $vars[$i+1];
				}
			
				$i++;
			}

			if (sizeof($GET_array) > 0) {
				while (list($key, $value) = each($GET_array)) {
					$HTTP_GET_VARS[$key] = $value;
				}
			}
		}
	}
  

	// # define general functions used application-wide
	require(DIR_WS_FUNCTIONS . 'general.php');
	require(DIR_WS_FUNCTIONS . 'html_output.php');

	if (!empty($_POST['manufacturers_id'])) {
    
		$man_id = tep_db_prepare_input($_POST['manufacturers_id']);
		
			tep_redirect(tep_href_link(FILENAME_DEFAULT,'manufacturers_id=' . $man_id));
	}

	// # set the cookie domain
	$cookie_domain = (($request_type == 'NONSSL') ? HTTP_COOKIE_DOMAIN : HTTPS_COOKIE_DOMAIN);
	$cookie_path = (($request_type == 'NONSSL') ? HTTP_COOKIE_PATH : HTTPS_COOKIE_PATH);

	// # include cache functions if enabled
	if (USE_CACHE == 'true') include(DIR_WS_FUNCTIONS . 'cache.php');

	// # SEO URLs 
	//if (!$page_404) {
    
    require_once(DIR_WS_CLASSES . 'url_rewrite.php'); 
    $url_rewrite = new url_rewrite;
    $url_rewrite->request_url();
    //404 check
    if (isset($HTTP_GET_VARS['products_id'])) {
      if (!tep_product_exists($HTTP_GET_VARS['products_id'])) {
        $page_404 = true;
      }
    } elseif (isset($HTTP_GET_VARS['cPath'])) {
      $cPaths = explode("_", $HTTP_GET_VARS['cPath']);

      if (!tep_category_exists($cPaths[sizeof($cPaths)-1])) {
        $page_404 = true;
      }
    } elseif (isset($HTTP_GET_VARS['manufacturers_id'])) {
      //do nothing 
    } elseif (isset($HTTP_GET_VARS['info_id'])) {
      //do nothing 
    } elseif ($_SERVER['PHP_SELF'] == DIR_CGI_CATALOG.'/'.FILENAME_DEFAULT && $REQUEST_URI_PATH!='/' && $REQUEST_URI_PATH!='/'.FILENAME_DEFAULT) {
      $page_404 = true;
    }
  
  if ($_SERVER['SCRIPT_NAME'] == '404.php') {
    $page_404 = true;
  }

  if ($page_404) {
    header("HTTP/1.0 404 Not Found");
  }


  
// include shopping cart class
  require(DIR_WS_CLASSES . 'shopping_cart.php');

  require(DIR_WS_CLASSES . 'wishlist.php');
  
// include navigation history class
  require(DIR_WS_CLASSES . 'navigation_history.php');

// some code to solve compatibility issues
  require(DIR_WS_FUNCTIONS . 'compatibility.php');

// check if sessions are supported, otherwise use the php3 compatible session class
  if (!function_exists('session_start')) {
    define('PHP_SESSION_NAME', 'IXsid');
    define('PHP_SESSION_PATH', $cookie_path);
    define('PHP_SESSION_DOMAIN', $cookie_domain);
    define('PHP_SESSION_SAVE_PATH', SESSION_WRITE_DIRECTORY);

    include(DIR_WS_CLASSES . 'sessions.php');
  }

// define how the session functions will be used
  require(DIR_WS_FUNCTIONS . 'sessions.php');

// set the session name and save path
  tep_session_name('IXsid');
  tep_session_save_path(SESSION_WRITE_DIRECTORY);

  $cookie_life = time()+2952000;

// set the session cookie parameters
   if (function_exists('session_set_cookie_params')) {
    session_set_cookie_params($cookie_life, $cookie_path, $cookie_domain);
  } elseif (function_exists('ini_set')) {
    ini_set('session.cookie_lifetime', $cookie_life);
    ini_set('session.cookie_path', $cookie_path);
    ini_set('session.cookie_domain', $cookie_domain);
  }

// set the session ID if it exists
   if (isset($HTTP_POST_VARS[tep_session_name()])) {
     tep_session_id($HTTP_POST_VARS[tep_session_name()]);
   } elseif ( ($request_type == 'SSL') && isset($HTTP_GET_VARS[tep_session_name()]) ) {
     tep_session_id($HTTP_GET_VARS[tep_session_name()]);
   }

// start the session
  $session_started = false;
  if (SESSION_FORCE_COOKIE_USE == 'True') {
    tep_setcookie('cookie_test', 'please_accept_for_session', time()+60*60*24*30, $cookie_path, $cookie_domain);

    if (isset($HTTP_COOKIE_VARS['cookie_test'])) {
      tep_session_start();
      $session_started = true;
    }
  } elseif (SESSION_BLOCK_SPIDERS == 'True') {
    $user_agent = strtolower(getenv('HTTP_USER_AGENT'));
    $spider_flag = false;

    if (tep_not_null($user_agent)) {
      $spiders = file(DIR_WS_INCLUDES . 'spiders.txt');

      for ($i=0, $n=sizeof($spiders); $i<$n; $i++) {
        if (tep_not_null($spiders[$i])) {
          if (is_integer(strpos($user_agent, trim($spiders[$i])))) {
            $spider_flag = true;
            break;
          }
        }
      }
    }

    if ($spider_flag == false) {
      tep_session_start();
      $session_started = true;
    }
  } else {
    tep_session_start();
    $session_started = true;
  }

// set SID once, even if empty
  $SID = (defined('SID') ? SID : '');

// verify the ssl_session_id if the feature is enabled
  if ( ($request_type == 'SSL') && (SESSION_CHECK_SSL_SESSION_ID == 'True') && (ENABLE_SSL == true) && ($session_started == true) ) {
    $ssl_session_id = getenv('SSL_SESSION_ID');
    if (!tep_session_is_registered('SSL_SESSION_ID')) {
      $SESSION_SSL_ID = $ssl_session_id;
      tep_session_register('SESSION_SSL_ID');
    }

    if ($SESSION_SSL_ID != $ssl_session_id) {
      tep_session_destroy();
      tep_redirect(tep_href_link(FILENAME_SSL_CHECK));
    }
  }

// verify the browser user agent if the feature is enabled
  if (SESSION_CHECK_USER_AGENT == 'True') {
    $http_user_agent = getenv('HTTP_USER_AGENT');
    if (!tep_session_is_registered('SESSION_USER_AGENT')) {
      $SESSION_USER_AGENT = $http_user_agent;
      tep_session_register('SESSION_USER_AGENT');
    }

    if ($SESSION_USER_AGENT != $http_user_agent) {
      tep_session_destroy();
      tep_redirect(tep_href_link(FILENAME_LOGIN));
    }
  }

// verify the IP address if the feature is enabled
  if (SESSION_CHECK_IP_ADDRESS == 'True') {
    $ip_address = tep_get_ip_address();
    if (!tep_session_is_registered('SESSION_IP_ADDRESS')) {
      $SESSION_IP_ADDRESS = $ip_address;
      tep_session_register('SESSION_IP_ADDRESS');
    }

    if ($SESSION_IP_ADDRESS != $ip_address) {
      tep_session_destroy();
      tep_redirect(tep_href_link(FILENAME_LOGIN));
    }
  }

// create the shopping cart & fix the cart if necesary
  if (tep_session_is_registered('cart') && is_object($cart)) {
    if (PHP_VERSION < 4) {
      $broken_cart = $cart;
      $cart = new shoppingCart;
      $cart->unserialize($broken_cart);
    }
  } else {
    tep_session_register('cart');
    $cart = new shoppingCart;
  }

// include currencies class and create an instance
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

// include the price formatter for the price breaks contribution
  require(DIR_WS_CLASSES . 'PriceFormatter.php');
  $pf = new PriceFormatter;

// include the mail classes
  require(DIR_WS_CLASSES . 'mime.php');
  require(DIR_WS_CLASSES . 'email.php');

// set the language
  if (!tep_session_is_registered('language') || isset($HTTP_GET_VARS['language'])) {
    if (!tep_session_is_registered('language')) {
      tep_session_register('language');
      tep_session_register('languages_id');
      tep_session_register('language_translate');
			tep_session_register('languages_code');
    }

    include(DIR_WS_CLASSES . 'language.php');
    $lng = new language();
    $lng->set_language('en');

    if (isset($HTTP_GET_VARS['language']) && tep_not_null($HTTP_GET_VARS['language'])) {
      $_SESSION['language_translate'] = $HTTP_GET_VARS['language'];
    }



/*
    if (isset($HTTP_GET_VARS['language']) && tep_not_null($HTTP_GET_VARS['language'])) {
      $lng->set_language($HTTP_GET_VARS['language']);
    } else {
      $lng->get_browser_language();
    }
*/
    $language = $lng->language['directory'];
    $languages_id = $lng->language['id'];
	  $languages_code = $lng->language['code']; // Added by Rigadin for PDF Generator [908]
  }


  if (file_exists(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/'.basename($_SERVER['PHP_SELF']))) @include(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/'.basename($_SERVER['PHP_SELF']));
  if (file_exists(DIR_FS_CATALOG_LAYOUT."languages/$language/$language.php")) @include(DIR_FS_CATALOG_LAYOUT."languages/$language/$language.php");




// include the language translations
  require(DIR_WS_LANGUAGES . $language . '.php');

// currency
  if (!tep_session_is_registered('currency') || isset($HTTP_GET_VARS['currency']) || ( (USE_DEFAULT_LANGUAGE_CURRENCY == 'true') && (LANGUAGE_CURRENCY != $currency) ) ) {
    if (!tep_session_is_registered('currency')) tep_session_register('currency');

    if (isset($HTTP_GET_VARS['currency'])) {
      if (!$currency = tep_currency_exists($HTTP_GET_VARS['currency'])) $currency = (USE_DEFAULT_LANGUAGE_CURRENCY == 'true') ? LANGUAGE_CURRENCY : DEFAULT_CURRENCY;
    } else {
      $currency = (USE_DEFAULT_LANGUAGE_CURRENCY == 'true') ? LANGUAGE_CURRENCY : DEFAULT_CURRENCY;
    }
  }

	// # navigation history
	if(tep_session_is_registered('navigation') && is_object($navigation)) {
		$broken_navigation = $navigation;
		$navigation = new navigationHistory;
		$navigation->unserialize($broken_navigation);
	} else {
		tep_session_register('navigation');
		$navigation = new navigationHistory;
	}
	
	$navigation->add_current_page();

//error_log(print_r('application top - ' . $navigation->path[0][page],1));

	// # wishlist data
	if(tep_session_is_registered('wishList')) {
		if(!is_object($wishList)) { 
			$wishList = new wishlist; 
		}
	} else {
		tep_session_register('wishList');
		$wishList = new wishlist;
	}

  // # Wishlist actions (must be before shopping cart actions) 
  if(isset($HTTP_POST_VARS['wishlist_x'])) {
    if(isset($HTTP_POST_VARS['products_id'])) {
      if(isset($HTTP_POST_VARS['id'])) {
        $attributes_id = $HTTP_POST_VARS['id'];
        tep_session_register('attributes_id');
      }
      $wishlist_id = $HTTP_POST_VARS['products_id'];
      if (is_array($wishlist_id)) $wishlist_id=$wishlist_id[0];
      tep_session_register('wishlist_id');
    }
    tep_redirect(tep_href_link(FILENAME_WISHLIST));
  } 
  
// include validation functions (right now only email address)
  require(DIR_WS_FUNCTIONS . 'validations.php');

  
// Shopping cart actions
  if (isset($HTTP_GET_VARS['action'])) {
// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled
    if ($session_started == false) {
      tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
    }

    if (DISPLAY_CART == 'true') {
      $goto =  FILENAME_SHOPPING_CART;
      $parameters = array('action', 'cPath', 'products_id', 'pid');
    } else {
      $goto = basename($PHP_SELF);
      if ($_GET['action'] == 'buy_now') {
        $parameters = array('action', 'pid', 'products_id');
      } else {
        $parameters = array('action', 'pid');
      }
    }
    switch ($_GET['action']) {
      case 'quickjump' :
        if (is_numeric(str_replace('_', '', substr(tep_db_prepare_input($_POST['dd3']), 1)))) {
          $sType = substr($HTTP_POST_VARS['dd3'], 0, 1);
          $sVal = substr($HTTP_POST_VARS['dd3'], 1);
          if ($sType == 'c') {
            tep_redirect(tep_href_link('index.php', 'cPath=' . $sVal));
          } else {
            tep_redirect(tep_href_link('index.php', 'products_id=' . $sVal));
          }
        } elseif (is_numeric($HTTP_POST_VARS['dd2'])) {
          tep_redirect(tep_href_link('index.php', 'cPath=' . $HTTP_POST_VARS['dd2']));
        } else {
          tep_redirect(tep_href_link('index.php', 'cPath=' . $HTTP_POST_VARS['dd1']));
        }
        break;
      // customer wants to update the product quantity in their shopping cart
      case 'update_product' :
    			      $qtylst=Array();
    			      $pids=Array();
    			      for ($i=0, $n=sizeof($_POST['products_id']); $i<$n; $i++) {
                                if (in_array($_POST['cart_id'][$i], (is_array($_POST['cart_delete']) ? tep_db_prepare_input($_POST['cart_delete']) : array()))) {
                                  $cart->remove($_POST['products_id'][$i],$_POST['cart_id'][$i]);
                                } else {
				  $qtylst[$_POST['cart_id'][$i]] += $_POST['cart_quantity'][$i];
				  $pids[$_POST['cart_id'][$i]] = tep_db_prepare_input($_POST['products_id'][$i]);
//                                  $attributes = ($_POST['id'][$_POST['products_id'][$i]]) ? $_POST['id'][$_POST['products_id'][$i]] : '';
//                                  $cart->update_quantity($_POST['products_id'][$i], $_POST['cart_quantity'][$i], $attributes, false);
                                }
                              }
			      foreach ($qtylst AS $cid=>$qty) $cart->update_quantity($pids[$cid], $qty, $cid);
//added for xsell_cart
if (isset($_POST['add_recommended'])) {
  foreach ($_POST['add_recommended'] as $value) {
    if (preg_match('/^[0-9]+$/i', $value)) {
      $cart->add_cart($value, 1, tep_db_prepare_input((int)$_POST['id']));
    }
  }
}
//added for xsell_cart
                              tep_redirect(tep_href_link($goto, tep_get_all_get_params($parameters)));
                              break;
      // customer adds a product from the products page
      case 'add_product' :    
				if (isset($_POST['products_id'])) {
					//$qty = preg_replace('/[^0-9]/i', '',$_POST['quantity']);
					$prod_list = is_array($_POST['products_id']) ?  $_POST['products_id'] : Array($_POST['products_id']);
					$prod_list = preg_replace('/[^0-9]/i', '',tep_db_prepare_input($prod_list));			
		
					$main_prod_id = isset($_POST['master_products_id']) ? $_POST['master_products_id'] : $_POST['products_id'][0];
					$main_prod_id = preg_replace('/[^0-9]/i', '',tep_db_prepare_input($main_prod_id));

					foreach ($prod_list AS $pidx=>$prod_ids) foreach (explode(',',$prod_ids) AS $prod_id) {
						if (is_array($_POST['quantity'])) $qty = (tep_db_prepare_input($_POST['quantity'][$pidx]) + 0);
						else {
				    		$qty = $_POST['quantity'] + 0;
				    
					if ($qty<1) $qty=1;
					}
					

					$master_pid = tep_db_read("SELECT master_products_id FROM products WHERE products_id='$prod_id'",NULL,'master_products_id');
					$attributes = array();
                        
					if (isset($_POST['attrs'][$prod_id])) {
						$attrlist = explode(';',$_POST['attrs'][$prod_id]);
						foreach ($attrlist as $attr) {
							list($oid, $oval)=explode(':',$attr);
				      		if (!$oid) continue;

							$attrln = tep_db_read("SELECT o.products_options_name,ov.products_options_values_name FROM products_options o LEFT JOIN products_options_values ov ON (ov.products_options_values_id='$oval' AND ov.language_id='$languages_id') WHERE o.products_options_id='$oid' AND o.language_id='$languages_id'",NULL,NULL);
//                                      if (is_numeric($oid) && $oid==(int)$oid && is_numeric($oval) && $oval==(int)$oval)
//                                        $attributes[$oid]=$oval;
				      if ($attrln) $attributes[$attrln['products_options_name']]=$attrln['products_options_values_name'];
                                    }
                                  } else {
				    $prod_id=tep_db_read("SELECT p.products_id FROM products p LEFT JOIN products_attributes pa ON (p.products_id=pa.products_id) WHERE p.products_id='$prod_id' OR p.master_products_id='$prod_id' ORDER BY p.products_id=p.master_products_id,pa.options_sort,pa.options_values_sort LIMIT 1",NULL,'products_id');
				    $attributes=tep_db_read("SELECT o.products_options_name,ov.products_options_values_name FROM products_attributes pa LEFT JOIN products_options o ON (pa.options_id=o.products_options_id AND o.language_id='$languages_id') LEFT JOIN products_options_values ov ON (pa.options_values_id=ov.products_options_values_id AND ov.language_id='$languages_id') WHERE pa.products_id='$prod_id' ORDER BY pa.options_sort",'products_options_name','products_options_values_name');
				  }
					if (isset($_POST['order_field']) && isset($_POST['order_field'][$master_pid])) { 
						$attributes = array_merge($attributes,$_POST['order_field'][$master_pid]);
					}
					
					if ($qty > 0) $cart->add_cart($prod_id, $qty, $attributes);
				}
                              }
//                              tep_redirect(tep_href_link($goto, tep_get_all_get_params($parameters)));
                              break;

      case 'buy_now_form' :    if (isset($_POST['products_id']) && is_numeric($_POST['products_id'])) {
                               $cart->add_cart(tep_db_prepare_input((int)$_POST['products_id']), tep_db_prepare_input((int)$_POST['cart_quantity']), tep_db_prepare_input((int)$_POST['id']));
 // replace quantities         $cart->get_quantity($_POST['products_id'])-($cart->get_quantity($_POST['products_id']))+($_POST['cart_quantity']), $_POST['id']);
                               }
                              tep_redirect(tep_href_link($goto, tep_get_all_get_params($parameters)));
                              break;

      // customer adds multiple products from the products_listing page
    case 'add_multiple' :    
                              while ( list( $key, $val ) = each( $HTTP_POST_VARS ) ) 
                                 { 
                                 if (substr($key,0,11) == "Qty_ProdId_" || substr($key,0,11) == "Qty_NPrdId_") 
                                 { 
                                 $prodId = substr($key, 11); 
                                 $qty = $val; 
                                 if ($qty <= 0 ) continue; 
                                if(isset($_POST["id_$prodId"]) && is_array($_POST["id_$prodId"])) {
                                   // We have attributes
                                   $cart->add_cart($prodId, $qty, $_POST["id_$prodId"]);
                                 } else {
                                   // No attributes
                                   $cart->add_cart($prodId, $qty);
                                 }
                                } 
                              } 
//                              tep_redirect(tep_href_link($goto, tep_get_all_get_params($parameters)));
                              break;
      // performed by the 'buy now' button in product listings and review page
      case 'buy_now' :        if (isset($_GET['products_id'])) {
                                if (tep_has_product_attributes($_GET['products_id'])) {
                                  tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $_GET['products_id']));
                                } else {
                                  $cart->add_cart($_GET['products_id'], 1);
                                }
                              }
                              tep_redirect(tep_href_link($goto, tep_get_all_get_params($parameters)));
                              break;

      case 'product_question' :

			$product_question_success = '';
            $email_address = tep_db_prepare_input($_POST['email']);
            $enquiry = tep_db_prepare_input($_POST['enquiry']);

   			$code_query = tep_db_query("SELECT code FROM visual_verify_code WHERE ixsid = '" . tep_session_id($_GET[tep_session_name()]) . "'");
			$code = (tep_db_num_rows($code_query) > 0 ? tep_db_result($code_query,0) : '');

			$user_entered_code = tep_db_prepare_input($_POST['visual_verify_code']);

                              if (tep_validate_email($email_address) && strcasecmp($user_entered_code, $code) == 0) {
                                tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, 'Enquiry from ' . STORE_NAME . ' regarding ' . $_POST['product_name'], $enquiry, 'Store Visitor', $email_address);
                                $product_question_success = 'yes';
                              } else {
								$product_question_success = 'no';
							//	echo '<script>alert(\'Incorrect Captcha Code - please try again\');</script>';	
							  }
                              break;


      case 'notify' :         if (tep_session_is_registered('customer_id')) {
                                if (isset($HTTP_GET_VARS['products_id'])) {
                                  $notify = $HTTP_GET_VARS['products_id'];
                                } elseif (isset($HTTP_GET_VARS['notify'])) {
                                  $notify = $HTTP_GET_VARS['notify'];
                                } elseif (isset($HTTP_POST_VARS['notify'])) {
                                  $notify = $HTTP_POST_VARS['notify'];
                                } else {
                                  tep_redirect(tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action', 'notify'))));
                                }
                                if (!is_array($notify)) $notify = array($notify);

                                for ($i=0, $n=sizeof($notify); $i<$n; $i++) {

                                  $check_notices_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_PRODUCTS_NOTIFICATIONS . " WHERE products_id = '" . $notify[$i] . "' AND customers_id = '" . $customer_id . "'");
                                  $check_notices = (tep_db_num_rows($check_notices_query) > 0 ? tep_db_result($check_notices_query,0) : 0);

                                  if ($check_notices_query == 0) {
                                    tep_db_query("INSERT INTO " . TABLE_PRODUCTS_NOTIFICATIONS . " (products_id, customers_id, date_added) VALUES ('" . $notify[$i] . "', '" . $customer_id . "', NOW())");
                                  }

                                }

                                tep_redirect(tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action', 'notify'))));

                              } else {
                                $navigation->set_snapshot();
                                tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
                              }
                              break;

      case 'notify_remove' :  
							
							if (tep_session_is_registered('customer_id') && isset($HTTP_GET_VARS['products_id'])) {

                                $check_query = tep_db_query("SELECT COUNT(0) FROM " . TABLE_PRODUCTS_NOTIFICATIONS . " WHERE products_id = '" . $HTTP_GET_VARS['products_id'] . "' AND customers_id = '" . $customer_id . "'");
                                $check = (tep_db_num_rows($check_query) > 0 ? tep_db_result($check_query,0) : 0);

                                if ($check > 0) {
                                  tep_db_query("DELETE FROM " . TABLE_PRODUCTS_NOTIFICATIONS . " WHERE products_id = '" . $HTTP_GET_VARS['products_id'] . "' AND customers_id = '" . $customer_id . "'");
                                }

                                tep_redirect(tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('action'))));

                            } else {
                                $navigation->set_snapshot();
                                tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
                            }
                            break;

      case 'cust_order' :     if (tep_session_is_registered('customer_id') && isset($HTTP_GET_VARS['pid'])) {
                                if (tep_has_product_attributes($HTTP_GET_VARS['pid'])) {
                                  tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $HTTP_GET_VARS['pid']));
                                } else {
                                  $cart->add_cart((int)$_GET['pid'], 1);
                                }
                              }
                              tep_redirect(tep_href_link($goto, tep_get_all_get_params($parameters)));
                              break;
    }
  }

// # rmh referral start
// # set the referral id
  if (!tep_session_is_registered('referral_id') || isset($HTTP_GET_VARS['ref'])) {
    if (!tep_session_is_registered('referral_id') && !tep_session_is_registered('customer_id')) {
      tep_session_register('referral_id');
    }

    if (isset($HTTP_GET_VARS['ref']) && tep_not_null($HTTP_GET_VARS['ref'])) {
      $referral_id = $HTTP_GET_VARS['ref'];
    } else {
      $referral_id = '';
    }
  }
// # rmh referral end

// # include the who's online functions
  require(DIR_WS_FUNCTIONS . 'whos_online.php');
  tep_update_whos_online();

// # include the password crypto functions
  require(DIR_WS_FUNCTIONS . 'password_funcs.php');


// # split-page-results
  require(DIR_WS_CLASSES . 'split_page_results.php');

// # infobox
  require(DIR_WS_CLASSES . 'boxes.php');

// # auto activate and expire banners
  require(DIR_WS_FUNCTIONS . 'banner.php');
  tep_activate_banners();
  tep_expire_banners();

// # auto expire special products
  require(DIR_WS_FUNCTIONS . 'specials.php');
  tep_expire_specials();

// # calculate category path
  if (isset($HTTP_GET_VARS['cPath'])) {
    $cPath = $HTTP_GET_VARS['cPath'];
  } elseif (isset($HTTP_GET_VARS['products_id']) && !isset($HTTP_GET_VARS['manufacturers_id'])) {
    $cPath = tep_get_product_path($HTTP_GET_VARS['products_id']);
  } else {
    $cPath = '';
  }

  if (tep_not_null($cPath)) {
    $cPath_array = tep_parse_category_path($cPath);
    $cPath = implode('_', $cPath_array);
    $current_category_id = $cPath_array[(sizeof($cPath_array)-1)];
  } else {
    $current_category_id = 0;
  }

// include the breadcrumb class and start the breadcrumb trail
  require(DIR_WS_CLASSES . 'breadcrumb.php');
  $breadcrumb = new breadcrumb;

  //$breadcrumb->add(HEADER_TITLE_TOP, HTTP_SERVER);
  $breadcrumb->add(HEADER_TITLE_CATALOG, tep_href_link(FILENAME_DEFAULT));

	// # add category names or the manufacturer name to the breadcrumb trail
	if(isset($cPath_array)) {
    
		for ($i=0, $n=sizeof($cPath_array); $i<$n; $i++) {

			$categories_name_query = tep_db_query("SELECT categories_name FROM " . TABLE_CATEGORIES_DESCRIPTION . " WHERE categories_id = '" . (int)$cPath_array[$i] . "' AND language_id = '" . (int)$languages_id . "'");
			$categories_name = (tep_db_num_rows($categories_name_query) > 0 ? tep_db_result($categories_name_query,0) : '');
		
			if(!empty($categories_name)) { 
				$breadcrumb->add($categories_name, tep_href_link(FILENAME_DEFAULT, 'cPath=' . implode('_', array_slice($cPath_array, 0, ($i+1)))));
			} else {
				break;
			}
		}

	} elseif(isset($HTTP_GET_VARS['manufacturers_id'])) {
    
	    $manufacturers_query = tep_db_query("SELECT manufacturers_name FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = '" . (int)$HTTP_GET_VARS['manufacturers_id'] . "'");

		$manufacturers_name = (tep_db_num_rows($manufacturers_query) > 0 ? tep_db_result($manufacturers_query,0) : '');
		
		if(!empty($manufacturers_name)) { 
			$breadcrumb->add($manufacturers_name, tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . $HTTP_GET_VARS['manufacturers_id']));
		}

	}

	// # add the products model to the breadcrumb trail
	if (isset($HTTP_GET_VARS['products_id'])) {
    	
		$products_name_query = tep_db_query("SELECT products_name FROM " . TABLE_PRODUCTS_DESCRIPTION . " WHERE products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' AND language_id = '" . (int)$languages_id . "'");
		$products_name = (tep_db_num_rows($products_name_query) > 0 ? tep_db_result($products_name_query,0) : '');
		
		if(!empty($products_name)) { 
			$breadcrumb->add($products_name, tep_href_link(FILENAME_PRODUCT_INFO, 'cPath=' . $cPath . '&products_id=' . $HTTP_GET_VARS['products_id']));
		}

	}

	// # initialize the message stack for output messages
	require(DIR_WS_CLASSES . 'message_stack.php');
	$messageStack = new messageStack;
  
	if(isset($HTTP_GET_VARS['action']) && $HTTP_GET_VARS['action'] == 'tell_a_friend') {
		require_once(DIR_WS_MODULES . 'tell_a_friend.php');
	}

	// # set which precautions should be checked
	define('WARN_INSTALL_EXISTENCE', 'true');
	define('WARN_CONFIG_WRITEABLE', 'true');
	define('WARN_SESSION_DIRECTORY_NOT_WRITEABLE', 'true');
	define('WARN_SESSION_AUTO_START', 'true');
	define('WARN_DOWNLOAD_DIRECTORY_NOT_READABLE', 'true');

	require(DIR_WS_INCLUDES . 'add_ccgvdc_application_top.php');  // CCGV
	require(DIR_WS_FUNCTIONS . 'featured.php');
	
	tep_expire_featured();
  

	// # Capture text between application_top.php and header.php
	if (strpos($_SERVER['REQUEST_URI'], "ec_process") !== true) {
		require(STS_START_CAPTURE);
	}

	if (!$referer_url) {
		if ($HTTP_SERVER_VARS['HTTP_REFERER']) {
			$referer_url = $HTTP_SERVER_VARS['HTTP_REFERER'];
			tep_session_register('referer_url');
		}
	}
  
	$traffic_stats_ok = 1;

	// # user_ad_tracker modification

	if($ad) {
		$advertiser = $_GET["ad"];  
		tep_session_register('advertiser');
	} 
   
	// # IX tracking - didnt seem to so anything. seems to want to write to the traffic_items table - doesnt seem to work
	// # commented out for future removal.
	require_once(IX_PATH_CLASSES.'IXtracker.php');
	IXtracker::track();

	// # START SuperTracker
	require(DIR_WS_CLASSES . 'supertracker.php');
	 $tracker = new supertracker;
	 $traffic_stats_ok = $tracker->update();
	
	if($traffic_stats_ok > 0) {
		include_once(DIR_WS_INCLUDES . 'traffic_stats.php');
	}
// # END SuperTracker

// # Include AFFILIATE
  require(DIR_WS_INCLUDES . 'affiliate_application_top.php');

 define('FILENAME_BEST_SELLERS', 'best_sellers.php');

	_sess_gc(2952000);

 // # API FOR MOBILE BROWSER DETECTION
$mobile_browser = '0';
 
if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|mobile|blackberry)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
    $mobile_browser++;
}
 
if((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
    $mobile_browser++;
}    
 
$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
$mobile_agents = array(
    'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
    'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
    'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
    'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
    'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
    'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
    'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
    'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
    'wapr','webc','winw','winw','xda','xda-');
 
if(in_array($mobile_ua,$mobile_agents)) {
    $mobile_browser++;
}
 
if (strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini')>0) {
    $mobile_browser++;
}
 
if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'windows')>0) {
    $mobile_browser=0;
}

$navigation->set_snapshot($_SERVER["REQUEST_URI"]);
?>
