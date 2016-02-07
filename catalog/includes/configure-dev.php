<?php
/*
  
  

  

  
*/

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
  define('HTTP_SERVER', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/'); // eg, http://localhost - should not be empty for productive servers
  define('HTTPS_SERVER', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/'); // eg, https://localhost - should not be empty for productive servers
  define('ENABLE_SSL', true); // secure webserver for checkout procedure?
  define('HTTP_COOKIE_DOMAIN', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/');
  define('HTTPS_COOKIE_DOMAIN', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/');
  define('HTTP_COOKIE_PATH', '/');
  define('HTTPS_COOKIE_PATH', '/');
  define('DIR_WS_HTTP_CATALOG', '/');
  define('DIR_WS_HTTPS_CATALOG', '/');
  define('DIR_WS_IMAGES', 'images/');
  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_INCLUDES', 'includes/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
  define('DIR_FS_CACHE', DIR_WS_INCLUDES . 'cache/');

  define('DIR_WS_DOWNLOAD_PUBLIC', 'pub/');
  define('DIR_FS_CATALOG', '/var/www/vhosts/bodyinacinch.com/httpdocs/');
  define('DIR_FS_DOWNLOAD', DIR_FS_CATALOG . 'download/');
  define('DIR_FS_DOWNLOAD_PUBLIC', DIR_FS_CATALOG . 'pub/');

// define our database connection
  define('DB_SERVER', 'localhost');
  define('DB_SERVER_USERNAME', 'bodyin');
  define('DB_SERVER_PASSWORD', 'sashaurq1');
  define('DB_DATABASE', 'bodyin');
  define('USE_PCONNECT', 'false'); // use persistent connections?
  define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'

  define('CC_ENCRYPTION_KEY', 'R+32k85k:LkfadjHp($E*FDJjwj21jm1');

// STS: ADD: Define Simple Template System files
  define('STS_TEMPLATE_DIR', 'layout/');
  define('STS_START_CAPTURE', DIR_WS_INCLUDES . 'sts_start_capture.php');
  define('STS_STOP_CAPTURE', DIR_WS_INCLUDES . 'sts_stop_capture.php');
  define('STS_RESTART_CAPTURE', DIR_WS_INCLUDES . 'sts_restart_capture.php');
  define('STS_DEFAULT_TEMPLATE', STS_TEMPLATE_DIR . 'sts_template.html');
  define('STS_DISPLAY_OUTPUT', DIR_WS_INCLUDES . 'sts_display_output.php');
  define('STS_USER_CODE', DIR_WS_INCLUDES . 'sts_user_code.php');
  define('STS_PRODUCT_INFO', DIR_WS_INCLUDES . 'sts_product_info.php');
  define('STS_FUNCTIONS', DIR_WS_INCLUDES . 'sts_functions.php');
// STS: EOADD
?>
