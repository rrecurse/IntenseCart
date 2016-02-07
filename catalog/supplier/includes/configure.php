<?php
/*
  
  

  

  
*/

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
  define('HTTP_SERVER', 'http://dev.luxurychair.com'); // eg, http://localhost - should not be empty for productive servers
  define('HTTP_CATALOG_SERVER', 'http://dev.luxurychair.com');
  define('HTTPS_CATALOG_SERVER', 'http://dev.luxurychair.com');
  define('ENABLE_SSL_CATALOG', 'false'); // secure webserver for catalog module
  define('DIR_FS_DOCUMENT_ROOT', '/var/www/vhosts/luxurychair.com/subdomains/dev/httpdocs/'); // where the pages are located on the server
  define('DIR_WS_ADMIN', '/supplier/'); // absolute path required
  define('DIR_FS_ADMIN', '/var/www/vhosts/luxurychair.com/subdomains/dev/httpdocs/admin/'); // absolute pate required
  define('DIR_WS_CATALOG', '/'); // absolute path required
  define('DIR_FS_CATALOG', '/var/www/vhosts/luxurychair.com/subdomains/dev/httpdocs/'); // absolute path required
  define('DIR_WS_IMAGES', '../admin/images/');
  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_CATALOG_IMAGES', DIR_WS_CATALOG . 'images/');
  define('DIR_WS_CATALOG_IMAGES_CACHE', DIR_WS_CATALOG_IMAGES . 'cache/');
  define('DIR_WS_INCLUDES', '../admin/includes/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
  define('DIR_WS_CATALOG_LANGUAGES', DIR_WS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');
  define('DIR_FS_CATALOG_IMAGES_CACHE', DIR_FS_CATALOG_IMAGES . 'cache/');
  define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
  define('DIR_FS_CATALOG_CLASSES', DIR_FS_CATALOG . 'includes/classes/');
  define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');

// define our database connection
  define('DB_SERVER', 'localhost');
  define('DB_SERVER_USERNAME', 'dev_luxurychair');
  define('DB_SERVER_PASSWORD', 'Passw0rd');
  define('DB_DATABASE', 'dev-luxuryhchair');
  define('USE_PCONNECT', 'false'); // use persistent connections?
  define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'
  
  define('FILENAME_PRODUCT_INFO', 'product_info.php');
?>
