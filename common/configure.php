<?php

umask(0002);

if (isset($_GET['IXdebug'])) {
	$_COOKIE['IXdebug']=$_GET['IXdebug'];
	set_cookie('IXdebug',$_GET['IXdebug'] ? 1 : NULL);
}

if (!defined('DIR_FS_CORE')) define('DIR_FS_CORE','/usr/share/IXcore/');
if (!defined('SITE_RESELLER')) define('SITE_RESELLER','intensecart');

define('DIR_FS_RESELLER','/home/resellers/'.SITE_RESELLER.'/includes/');

// New Definitions
if (!defined('IX_PATH_CORE')) define('IX_PATH_CORE','/usr/share/IXcore/');
if (!defined('IX_RESELLER')) define('IX_RESELLER','intensecart');

define('IX_PATH_RESELLER','/home/resellers/'.IX_RESELLER.'/includes/');

define('IX_PATH_SITE',$_SERVER['DOCUMENT_ROOT'].'/');
define('IX_PATH_CLIENT',preg_replace('|/[^/]*/$|','/',IX_PATH_SITE));

require_once(IX_PATH_CLIENT.'conf/configure.php');
require_once(IX_PATH_CLIENT.'cache/config_cache.php');

define('IX_URI_SITE','/');
define('IX_PATH_CACHE',IX_PATH_CLIENT.'cache/');
define('IX_PATH_COMMON',IX_PATH_CORE.'common/');
define('IX_PATH_SHARE',IX_PATH_CLIENT.'share/');

// # added for cookieless static content
// # consider needing wildcard or seperate SSL cert to use subdomain securely.

if(defined('IMAGE_SUBDOMAIN') && IMAGE_SUBDOMAIN != '') {

	define('IX_URI_IMAGES', (isset($_SERVER['HTTPS']) ? 'https:' : 'http:').str_replace(array('https:','http:'),'',IMAGE_SUBDOMAIN).'/');

} else { 

	define('IX_URI_IMAGES',IX_URI_SITE.'images/');
}

define('IX_PATH_IMAGES',IX_PATH_SITE.'images/');
define('IX_URI_IMAGECACHE',IX_URI_IMAGES.'cache/');
define('IX_PATH_IMAGECACHE',IX_PATH_IMAGES.'cache/');
define('IX_URI_LAYOUT' , IX_URI_SITE.'layout/');
define('IX_PATH_LAYOUT' , IX_PATH_SITE.'layout/');
define('IX_URI_ICONS', IX_URI_LAYOUT . 'icons/');
define('IX_PATH_ICONS', IX_PATH_LAYOUT . 'icons/');

define('IX_PATH_CLASSES',IX_PATH_COMMON.'classes/');
define('IX_PATH_MODULES',IX_PATH_COMMON.'modules/');

?>