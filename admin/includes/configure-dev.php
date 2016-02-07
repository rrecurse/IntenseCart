<?php
/*
  
  

  Copyright (c) 2003 IntenseCart eCommerce

  
*/

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
  define('HTTP_SERVER', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/'); // eg, http://localhost - should not be empty for productive servers
  define('HTTP_CATALOG_SERVER', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/');
  define('HTTPS_CATALOG_SERVER', 'https://68.178.204.111:8443/sitepreview/http/bodyinacinch.com/');
  define('ENABLE_SSL_CATALOG', 'true'); // secure webserver for catalog module
  define('DIR_FS_DOCUMENT_ROOT', '/var/www/vhosts/bodyinacinch.com/httpdocs/'); // where the pages are located on the server
  define('DIR_WS_ADMIN', '/admin/'); // absolute path required
  define('DIR_FS_ADMIN', '/var/www/vhosts/bodyinacinch.com/httpdocs/admin/'); // absolute pate required
  define('DIR_WS_CATALOG', '/'); // absolute path required
  define('DIR_FS_CATALOG', '/var/www/vhosts/bodyinacinch.com/httpdocs/'); // absolute path required
  define('DIR_WS_IMAGES', 'images/');
  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_CATALOG_IMAGES', DIR_WS_CATALOG . 'images/');
  define('DIR_WS_INCLUDES', 'includes/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
  define('DIR_WS_CATALOG_LANGUAGES', DIR_WS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');
  define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
  define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');

// define our database connection
  define('DB_SERVER', 'localhost');
  define('DB_SERVER_USERNAME', 'bodyin');
  define('DB_SERVER_PASSWORD', 'sashaurq1');
  define('DB_DATABASE', 'bodyin');
  define('USE_PCONNECT', 'false'); // use persisstent connections?
  define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'
?>
