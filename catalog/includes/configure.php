<?php

  define('DIR_FS_DOCUMENT_ROOT',$_SERVER['DOCUMENT_ROOT'].'/');
  define('DIR_FS_SITE',preg_replace('|/[^/]*/$|','/',DIR_FS_DOCUMENT_ROOT));
  require_once(DIR_FS_SITE.'conf/configure.php');

  define('DIR_FS_CACHE',DIR_FS_SITE.'cache/');
  if (!defined('DIR_FS_CORE')) define('DIR_FS_CORE','/usr/share/IXcore/');

  define('DIR_FS_COMMON',DIR_FS_CORE.'common/');
  define('DIR_FS_SHARE',DIR_FS_SITE.'share/');

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
  define('HTTP_SERVER','http://'.SITE_DOMAIN);
  define('HTTPS_SERVER',(SITE_ENABLE_SSL?'https://':'http://').SITE_DOMAIN);
  define('ENABLE_SSL', true); // secure webserver for checkout procedure?
  define('HTTP_COOKIE_DOMAIN', SITE_DOMAIN);
  define('HTTPS_COOKIE_DOMAIN', SITE_DOMAIN);
  define('HTTP_COOKIE_PATH', '/');
  define('HTTPS_COOKIE_PATH', '/');
  define('DIR_WS_HTTP_CATALOG', '/');
  define('DIR_WS_HTTPS_CATALOG', '/');
  define('DIR_CGI_CATALOG', '/core');


// # added for cookieless static content - consider needing wildcard or seperate SSL cert to use subdomain securely.
if(defined('IMAGE_SUBDOMAIN') && IMAGE_SUBDOMAIN != '') {

	define('DIR_WS_CATALOG_IMAGES', (isset($_SERVER['HTTPS']) ? 'https:' : 'http:').str_replace(array('https:','http:'),'',IMAGE_SUBDOMAIN).'/');

} else { 

  define('DIR_WS_CATALOG_IMAGES', 'images/');

}


  define('DIR_WS_CATALOG_IMAGES_CACHE', DIR_WS_CATALOG_IMAGES.'cache/');
  define('DIR_WS_IMAGES',DIR_WS_CATALOG_IMAGES);
  define('DIR_WS_IMAGES_CACHE',DIR_WS_CATALOG_IMAGES_CACHE);
  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_INCLUDES', 'includes/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');

 /// API FOR MOBILE BROWSER DETECTION
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

$_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
if (strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini')>0) {
    $mobile_browser++;
}

if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'windows')>0) {
    $mobile_browser=0;
}

// Mobile Browser Detect
if ($mobile_browser > 0) {

if(is_dir(DIR_FS_SITE_CATALOG.DIR_WS_CATALOG_LAYOUT)){
  define('DIR_WS_CATALOG_LAYOUT' , 'layout_mobile/');
} else {
 define('DIR_WS_CATALOG_LAYOUT' , 'layout/');
}

} else {
  define('DIR_WS_CATALOG_LAYOUT' , 'layout/');
}

if(defined('CDN_CONTENT') && CDN_CONTENT != '') { 
	define('DIR_WS_CATALOG_LAYOUT_IMAGES', CDN_CONTENT .'/layout/img/');
} else {
	define('DIR_WS_CATALOG_LAYOUT_IMAGES', DIR_WS_CATALOG_LAYOUT.'img/');
}

if (defined('DIR_FS_CACHE')) {
} else {
  define('DIR_FS_CACHE', DIR_WS_INCLUDES . 'cache/');
}

  define('DIR_WS_DOWNLOAD_PUBLIC', 'pub/');


  define('DIR_FS_CATALOG',DIR_FS_CORE.'catalog/');
  define('DIR_FS_SITE_CATALOG', DIR_FS_DOCUMENT_ROOT); // absolute path required
  define('DIR_FS_INCLUDES',DIR_FS_CATALOG.'includes/');
  define('DIR_FS_DOWNLOAD', DIR_FS_SITE_CATALOG . 'download/');
  define('DIR_FS_DOWNLOAD_PUBLIC', DIR_FS_SITE_CATALOG . 'pub/');

	if (isset($_GET['fullsite']) && $_GET['fullsite'] == '1'){
		$fullsite = 1;
		define('DIR_FS_CATALOG_LAYOUT',DIR_FS_SITE_CATALOG.'layout/');
	} else {

		if ($mobile_browser > 0) {
			if (!isset($_GET['fullsite']) || isset($_GET['fullsite']) && !$_GET['fullsite'] == '1' || !$fullsite=1) {
				$fullsite = 0;
			}

			if(is_dir(DIR_FS_SITE_CATALOG.'layout_mobile/')) {
				define('DIR_FS_CATALOG_LAYOUT',DIR_FS_SITE_CATALOG.'layout_mobile/');
			} else {
				define('DIR_FS_CATALOG_LAYOUT',DIR_FS_SITE_CATALOG.'layout/');
			}
		} else {

			define('DIR_FS_CATALOG_LAYOUT',DIR_FS_SITE_CATALOG.'layout/');
		}
	}

  define('DIR_FS_CATALOG_LOCAL', DIR_FS_SITE_CATALOG.'local/');
  define('DIR_FS_CATALOG_IMAGES',DIR_FS_SITE_CATALOG.'images/');
  define('DIR_FS_CATALOG_IMAGES_CACHE',DIR_FS_CATALOG_IMAGES.'cache/');
  define('DIR_FS_CATALOG_CLASSES',DIR_FS_INCLUDES.'classes/');
  define('DIR_FS_CATALOG_MODULES',DIR_FS_INCLUDES.'modules/');

  define('DIR_FS_EDI',DIR_FS_CATALOG_LOCAL.'edi/');

// define our database connection
  define('USE_PCONNECT', 'false'); // use persistent connections?
  define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'

  define('CC_ENCRYPTION_KEY', 'R+32k85k:LkfadjHp($E*FDJjwj21jm1');

// STS: ADD: Define Simple Template System files
  define('STS_TEMPLATE_DIR', DIR_FS_CATALOG_LAYOUT);
  define('STS_START_CAPTURE', DIR_FS_INCLUDES . 'sts_start_capture.php');
  define('STS_STOP_CAPTURE', DIR_FS_INCLUDES . 'sts_stop_capture.php');
  define('STS_RESTART_CAPTURE', DIR_FS_INCLUDES . 'sts_restart_capture.php');
  define('STS_DEFAULT_TEMPLATE', STS_TEMPLATE_DIR . 'sts_template.html');
  define('STS_DISPLAY_OUTPUT', DIR_FS_INCLUDES . 'sts_display_output.php');
  define('STS_USER_CODE', DIR_FS_INCLUDES . 'sts_user_code.php');
  define('STS_PRODUCT_INFO', DIR_FS_INCLUDES . 'sts_product_info.php');
  define('STS_FUNCTIONS', DIR_FS_INCLUDES . 'sts_functions.php');
// STS: EOADD
//  define('STORE_DB_TRANSACTIONS','true');
?>
