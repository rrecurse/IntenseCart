<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


require_once(DIR_WS_FUNCTIONS.'banner.php');


if(!defined('BANNER_GROUP')) {

	if(isset($HTTP_GET_VARS['products_id'])) { 

		define('BANNER_GROUP','products:'.(int)$HTTP_GET_VARS['products_id']);

	} elseif(isset($HTTP_GET_VARS['manufacturers_id'])) { 

		define('BANNER_GROUP','manufacturers:'.(int)$HTTP_GET_VARS['manufacturers_id']);

	} elseif(isset($HTTP_GET_VARS['info_id'])) { 

		define('BANNER_GROUP','info:'.(int)$HTTP_GET_VARS['info_id']);

	} elseif(isset($HTTP_GET_VARS['cPath'])) { 

		$cPath = $HTTP_GET_VARS['cPath'];
		$cPath = preg_replace('/[^0-9_]/i','',$cPath);

		define('BANNER_GROUP',join(',',array_reverse(explode(',','categories:'.str_replace('',',categories:',$cPath)))));

	} else { 

	 define('BANNER_GROUP','main');

	}
}

foreach (explode(',',BANNER_GROUP) AS $banner_g) {
	$banner_section=tep_display_banner('dynamic',$banner_g);

	if(!preg_match('/TEP ERROR/',$banner_section)) {
		echo $banner_section;
		break;
	}
}

?>
