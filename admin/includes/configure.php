<?php


  define('DIR_FS_DOCUMENT_ROOT',$_SERVER['DOCUMENT_ROOT'].'/');
  define('DIR_FS_SITE',preg_replace('|/[^/]*/$|','/',DIR_FS_DOCUMENT_ROOT));
  require_once(DIR_FS_SITE.'conf/configure.php');

  define('DIR_FS_CACHE',DIR_FS_SITE.'cache/');
  if (!defined('DIR_FS_CORE')) define('DIR_FS_CORE','/usr/share/IXcore/');

  define('DIR_FS_COMMON',DIR_FS_CORE.'common/');
  define('DIR_FS_SHARE',DIR_FS_SITE.'share/');
  require_once(DIR_FS_COMMON.'configure.php');

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
  define('HTTP_CATALOG_SERVER', 'http://'.SITE_DOMAIN);
  define('HTTPS_CATALOG_SERVER', (SITE_ENABLE_SSL?'https://':'http://').SITE_DOMAIN);
  define('HTTP_SERVER', $_SERVER['HTTP_HOST']==SITE_SHARED_DOMAIN?'https://'.SITE_SHARED_DOMAIN:HTTPS_CATALOG_SERVER);
  define('ENABLE_SSL_CATALOG', 'false'); // secure webserver for catalog module
  define('DIR_WS_ADMIN', '/admin/'); // absolute path required
  define('DIR_FS_ADMIN', DIR_FS_CORE.'admin/'); // absolute pate required
  define('DIR_FS_SITE_ADMIN', DIR_FS_DOCUMENT_ROOT.'admin/');
  define('DIR_WS_CATALOG', '/'); // absolute path required
  define('DIR_FS_CATALOG', DIR_FS_CORE.'catalog/'); // absolute path required
  define('DIR_FS_SITE_CATALOG',DIR_FS_DOCUMENT_ROOT);
  define('DIR_WS_IMAGES', 'images/');
  define('DIR_FS_IMAGES', DIR_FS_SITE_ADMIN.'images/');
  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_CATALOG_IMAGES', DIR_WS_CATALOG . 'images/');
  define('DIR_WS_CATALOG_IMAGES_CACHE', DIR_WS_CATALOG_IMAGES . 'cache/');
  define('DIR_WS_INCLUDES', 'includes/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
  define('DIR_FS_INCLUDES', DIR_FS_ADMIN . 'includes/');
  define('DIR_FS_BOXES', DIR_FS_INCLUDES . 'boxes/');
  define('DIR_FS_FUNCTIONS', DIR_FS_INCLUDES . 'functions/');
  define('DIR_FS_CLASSES', DIR_FS_INCLUDES . 'classes/');
  define('DIR_FS_MODULES', DIR_FS_INCLUDES . 'modules/');
  define('DIR_FS_LANGUAGES', DIR_FS_INCLUDES . 'languages/');
  define('DIR_WS_CATALOG_INCLUDES', DIR_WS_CATALOG . 'includes/');
  define('DIR_FS_CATALOG_INCLUDES', DIR_FS_CATALOG . 'includes/');
  define('DIR_WS_CATALOG_LANGUAGES', DIR_WS_CATALOG_INCLUDES . 'languages/');
  define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG_INCLUDES . 'languages/');
  define('DIR_FS_CATALOG_IMAGES', DIR_FS_SITE_CATALOG . 'images/');
  define('DIR_FS_CATALOG_IMAGES_CACHE', DIR_FS_CATALOG_IMAGES . 'cache/');
  define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG_INCLUDES . 'modules/');
  define('DIR_FS_CATALOG_CLASSES', DIR_FS_CATALOG_INCLUDES . 'classes/');
  define('DIR_FS_CATALOG_LAYOUT', DIR_FS_SITE_CATALOG . 'layout/');
  define('DIR_FS_CATALOG_LOCAL', DIR_FS_SITE_CATALOG. 'local/');
  define('DIR_FS_BACKUP', DIR_FS_SITE_ADMIN . 'backups/');
  define('DIR_FS_QBI_OUTPUT', DIR_FS_SITE_ADMIN.'qbi_output/');

// define our database connection

  define('USE_PCONNECT', 'false'); // use persistent connections?
  define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'
  define('CORE_PERMISSION',DB_SERVER_USERNAME=='IXprototype');
?>
