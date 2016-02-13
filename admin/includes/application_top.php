<?php
// # Detect mobile device for barcode scanning
$useragent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) { 


$mobile = true;

}

  if (version_compare(phpversion(), "4.1.0", "<") === true) {
    $_GET &= $HTTP_GET_VARS;
    $_POST &= $HTTP_POST_VARS;
    $_SERVER &= $HTTP_SERVER_VARS;
    $_FILES &= $HTTP_POST_FILES;
    $_ENV &= $HTTP_ENV_VARS;
    if (isset($HTTP_COOKIE_VARS)) $_COOKIE &= $HTTP_COOKIE_VARS;
  }

  if (!ini_get("register_globals")) {
    extract($_GET, EXTR_SKIP);
    extract($_POST, EXTR_SKIP);
    extract($_COOKIE, EXTR_SKIP);
  } 

// Start the clock for the page parse time log
  define('PAGE_PARSE_START_TIME', microtime());

date_default_timezone_set('UCT');

// Set the level of error reporting
  error_reporting(E_ALL & ~E_NOTICE);

// Set the local configuration parameters - mainly for developers
  if (file_exists('includes/local/configure.php')) include('includes/local/configure.php');

// Include application configuration parameters
  require(dirname(__FILE__).'/configure.php');

 if (SITE_ENABLE_SSL === 1) {

    if (!isset($_SERVER['HTTPS'])) {

	    header('HTTP/1.0 302 Redirect');
    	header('Location: '.HTTP_SERVER.$_SERVER['REQUEST_URI']);
	    exit;
	}
  }

// Define the project version
  define('PROJECT_VERSION', 'IXcore');

// set php_self in the local scope
  $PHP_SELF = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);

// Used in the "Backup Manager" to compress backups
  define('LOCAL_EXE_GZIP', '/usr/bin/gzip');
  define('LOCAL_EXE_GUNZIP', '/usr/bin/gunzip');
  define('LOCAL_EXE_ZIP', '/usr/local/bin/zip');
  define('LOCAL_EXE_UNZIP', '/usr/local/bin/unzip');

// include the list of project filenames
  require(DIR_FS_INCLUDES . 'filenames.php');

// include the list of project database tables
  require(DIR_FS_INCLUDES . 'database_tables.php');

// customization for the design layout
  define('BOX_WIDTH', 125); // how wide the boxes should be in pixels (default: 125)

// Define how do we update currency exchange rates
// Possible values are 'oanda' 'xe' or ''
  define('CURRENCY_SERVER_PRIMARY', 'oanda');
  define('CURRENCY_SERVER_BACKUP', 'xe');

// include the database functions
  require(DIR_FS_FUNCTIONS . 'database.php');

// make a connection to the database... now
  tep_db_connect() or die('Unable to connect to database server!');

  require(DIR_FS_COMMON . 'functions/general.php');
  require(DIR_FS_COMMON . 'functions/block.php');
  require(DIR_FS_COMMON . 'functions/module.php');
  require(DIR_FS_COMMON . 'modules/product/IXproduct.php');

// set application wide parameters
  tep_read_config();
//  $configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
//  $configuration_query = tep_db_query('select c.configuration_key as cfgKey, IFNULL(l.configuration_value,c.configuration_value) as cfgValue from IXcore.' . TABLE_CONFIGURATION . ' c LEFT JOIN ' . TABLE_CONFIGURATION . ' l ON c.configuration_id=l.configuration_id');
//  while ($configuration = tep_db_fetch_array($configuration_query)) {
//    define($configuration['cfgKey'], $configuration['cfgValue']);
//  }
  require(DIR_FS_INCLUDES.'database_updates.php');

// define our general functions used application-wide
  require(DIR_FS_FUNCTIONS . 'general.php');
  require(DIR_FS_FUNCTIONS . 'html_output.php');

// initialize the logger class
  require(DIR_FS_CLASSES . 'logger.php');

// include shopping cart class
  require(DIR_FS_CLASSES . 'shopping_cart.php');

// some code to solve compatibility issues
  require(DIR_FS_FUNCTIONS . 'compatibility.php');

// check to see if php implemented session management functions - if not, include php3/php4 compatible session class
  if (!function_exists('session_start')) {
    define('PHP_SESSION_NAME', 'IXAdminID');
    define('PHP_SESSION_PATH', '/');
    define('PHP_SESSION_SAVE_PATH', SESSION_WRITE_DIRECTORY);

    include(DIR_FS_CLASSES . 'sessions.php');
  }

// define how the session functions will be used
  require(DIR_FS_FUNCTIONS . 'sessions.php');

// set the session name and save path
  tep_session_name('IXAdminID');
  tep_session_save_path(SESSION_WRITE_DIRECTORY);

// set the session cookie parameters
   if (function_exists('session_set_cookie_params')) {
    session_set_cookie_params(0, DIR_WS_ADMIN);
  } elseif (function_exists('ini_set')) {
    ini_set('session.cookie_lifetime', '0');
    ini_set('session.cookie_path', DIR_WS_ADMIN);
  }

// lets start our session
  if (!defined('NO_SESSION') || !NO_SESSION) tep_session_start();

  if (!ini_get("register_globals")) {
    if (version_compare(phpversion(), "4.1.0", "<") === true) {
      if (isset($HTTP_SESSION_VARS)) $_SESSION &= $HTTP_SESSION_VARS;
    }
    extract($_SESSION, EXTR_SKIP);
  }

// set the language
  if (!tep_session_is_registered('language') || isset($_GET['language']) || empty($language)) {
    if (!tep_session_is_registered('language')) {
      tep_session_register('language');
      tep_session_register('languages_id');
    }
    include(DIR_FS_CLASSES . 'language.php');
    $lng = new language();

    if (isset($_GET['language']) && tep_not_null($_GET['language'])) {
      $lng->set_language($HTTP_GET_VARS['language']);
    } else {
      $lng->get_browser_language();
    }

    $language = $lng->language['directory'];
    $languages_id = $lng->language['id'];
  }

// include the language translations
  require(DIR_FS_LANGUAGES . $language . '.php');
  $current_page = preg_replace('/\.\w+$/','.php',basename($PHP_SELF));
  if (file_exists(DIR_FS_LANGUAGES . $language . '/' . $current_page)) {
    include(DIR_FS_LANGUAGES . $language . '/' . $current_page);
  }

// define our localization functions
  require(DIR_FS_FUNCTIONS . 'localization.php');

// Include validation functions (right now only email address)
  require(DIR_FS_FUNCTIONS . 'validations.php');

// setup our boxes
  require(DIR_FS_CLASSES . 'table_block.php');
  require(DIR_FS_CLASSES . 'box.php');

	// # initialize the message stack for output messages

	if(!tep_session_is_registered('messageToStack')) {
		tep_session_register('messageToStack');
	}

	require(DIR_FS_CLASSES . 'message_stack.php');
	$messageStack = new messageStack();

// split-page-results
  require(DIR_FS_CLASSES . 'split_page_results.php');

// entry/item info classes
  require(DIR_FS_CLASSES . 'object_info.php');

// email classes
  require(DIR_FS_CLASSES . 'mime.php');
  require(DIR_FS_CLASSES . 'email.php');

// file uploading class
  require(DIR_FS_CLASSES . 'upload.php');

// orderlist
define('FILENAME_ORDERLIST', 'orderlist.php'); 

// calculate category path
	$cPath = (isset($_GET['cPath'])) ? $_GET['cPath'] : '';

  if (tep_not_null($cPath)) {
    $cPath_array = tep_parse_category_path($cPath);
    $cPath = implode('_', $cPath_array);
    $current_category_id = $cPath_array[(sizeof($cPath_array)-1)];
  } else {
    $current_category_id = 0;
  }

// default open navigation box
  if (!tep_session_is_registered('selected_box')) {
    tep_session_register('selected_box');
    $selected_box = 'configuration';
  }

  if(isset($_GET['selected_box'])) $selected_box = $_GET['selected_box'];
/*
// the following cache blocks are used in the Tools->Cache section
// ('language' in the filename is automatically replaced by available languages)
  $cache_blocks = array(array('title' => TEXT_CACHE_CATEGORIES, 'code' => 'categories', 'file' => 'categories_box-language.cache', 'multiple' => true),
                        array('title' => TEXT_CACHE_MANUFACTURERS, 'code' => 'manufacturers', 'file' => 'manufacturers_box-language.cache', 'multiple' => true),
                        array('title' => TEXT_CACHE_ALSO_PURCHASED, 'code' => 'also_purchased', 'file' => 'also_purchased-language.cache', 'multiple' => true)
                       );
*/
// check if a default currency is set
  if (!defined('DEFAULT_CURRENCY')) $messageStack->add(ERROR_NO_DEFAULT_CURRENCY_DEFINED, 'error');

// check if a default language is set
  if (!defined('DEFAULT_LANGUAGE')) $messageStack->add(ERROR_NO_DEFAULT_LANGUAGE_DEFINED, 'error');

  if (function_exists('ini_get') && ((bool)ini_get('file_uploads') == false) ) {
    $messageStack->add(WARNING_FILE_UPLOADS_DISABLED, 'warning');
  }
  
  require(DIR_FS_FUNCTIONS . 'admins.php');

  if (!GetAdminUser()) {
    include(DIR_FS_INCLUDES.'login.php');	
    exit();
  }

if(!CheckAdminPermission(GetAdminUser(),(defined('ADMIN_PERMISSION')?preg_split('/,/',ADMIN_PERMISSION):GetAdminFilePermissions(preg_replace('|.*/|','',$_SERVER['SCRIPT_FILENAME']))))) {
    echo "Access denied for ".GetAdminUser()."\n";
	error_log('Access denied for '.GetAdminUser() .' at '.$_SERVER['SCRIPT_FILENAME']);
    exit();
  }
  if (defined('SITE_EXPIRE') && SITE_EXPIRE>0 && time()>SITE_EXPIRE) {
   if (CheckAdminPermission(GetAdminUser(),'SUPER')) {
      $messageStack->add('Account "'.DB_DATABASE.'" has expired', 'warning');
   } else {
     header('Location: http://www.intensecart.com/expired.html');
      exit;
    }
  }
  
  
require(DIR_FS_INCLUDES . 'add_ccgvdc_application_top.php');  // CCGV

// Include OSC-AFFILIATE
  require(DIR_FS_INCLUDES.'affiliate_application_top.php');

?>
