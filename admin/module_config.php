<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


// # command line interface and arg check for cronjob

if(php_sapi_name() === 'cli' && (isset($_SERVER['argv']) && $_SERVER['argv'][3] == 'cron')) { 

if(!$_SERVER['DOCUMENT_ROOT'] && isset($_SERVER['argv'][1])) { 
$_SERVER['DOCUMENT_ROOT'] = $_SERVER['argv'][1];
$_SERVER['REQUEST_URI'] = '/admin/module_config.php?set=dbfeed&module=dbfeed_'.$_SERVER['argv'][2];
$_SERVER['SCRIPT_NAME'] = '/admin/core/module_config.php';
$_SERVER['PHP_SELF'] = '/admin/core/module_config.php';
$_SERVER['HTTPS'] = true;
$_GET['language'] = '1';
$_SERVER['REMOTE_USER'] = '.intensecart';
$_SERVER['SCRIPT_FILENAME'] = 'module_config.php';
}

// # Add the path to the IX common classes to PHP's include path.
define ('DIR_FS_CORE', '/usr/share/IXcore/');
set_include_path (get_include_path ().PATH_SEPARATOR.DIR_FS_CORE.'common/classes');


// Include the custom error handler.
//require_once (DIR_FS_CORE . "/common/service/errorhandler.php");

// # Grab the site directory from the cron job's first arg
define ('DIR_FS_SITE', $_SERVER['argv'][1]);

// # define a cookie filename for cURL below.
define ('FILE_COOKIE', DIR_FS_SITE."cache/cookie_dbfeedCron");

// # Include the configuration and table name constants.
require_once (DIR_FS_SITE.'conf/configure.php');
require_once (DIR_FS_CORE."admin/includes/database_tables.php");

if(!function_exists(getenv('ixcore_db_password'))) { 
//putenv("ixcore_db_password=eep9q3YF");
}

if(!defined('DB_SERVER_PASSWORD')) define ('DB_SERVER_PASSWORD', getenv('ixcore_db_password'));

$dbObject = new mysqli ('127.0.0.1', DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
if ($dbObject->errno) {
	trigger_error ("Could not connect to the database. Error: ".$dbObject->error, E_USER_ERROR);
	return 9;
}

$FeedName = 'dbfeed_'.$_SERVER['argv'][2];
$_GET['module'] = $FeedName;

// # Start a new session.
$dbObject->query('BEGIN');

// # Add new admin session to database, to avoid login problems.
$SessID = 'cron_'.substr (sha1 (mt_rand (1000, 99999)."a"), 0, 21);
$Query = "INSERT IGNORE INTO admin_sessions (admin_sessid, admin_user, ignore_addr, admin_addr, access_time, expire_minutes) ".
		"VALUES ('%s', '%s', 1, '127.0.0.1', NOW(), 1)";
$Query = sprintf ($Query, $dbObject->real_escape_string ($SessID), $dbObject->real_escape_string ($FeedName));
if (!$dbObject->query($Query)) {
	trigger_error ("Could not create new admin session.\nQuery: $Query\nError: ".$dbObject->error, E_USER_ERROR);
	return 9;
}
$Query = sprintf ("INSERT IGNORE INTO ".TABLE_ADMIN_PERMISSIONS." VALUES ('%s', 'ALL')", $dbObject->real_escape_string ($FeedName));
if (!$dbObject->query ($Query)) {
		$Error = $dbObject->error;
		$dbObject->query('ROLLBACK');
        trigger_error ("Could not add to admin group.\nQuery: $Query\nError: ".$Error, E_USER_ERROR);
        return 9;
}
$dbObject->query('COMMIT');

// # URL to generate feed form.
$URI = 'http://'.SITE_DOMAIN.'/admin/module_config.php?set=dbfeed&module='.$FeedName;

// Create cURL object.
$ch = curl_init($URI);

// # Configure cURL for retrieving all necessary cookies.
curl_setopt ($ch, CURLOPT_HEADER, true);
curl_setopt ($ch, CURLOPT_VERBOSE, false);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt ($ch, CURLOPT_COOKIESESSION, true);
curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
curl_setopt ($ch, CURLOPT_COOKIEFILE, FILE_COOKIE);
curl_setopt ($ch, CURLOPT_COOKIEJAR, FILE_COOKIE);
curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt ($ch, CURLOPT_COOKIE, "admin_sessid=$SessID; admin_user=$FeedName; IXAdminID=BloodyIXadminID");

// # get all cookies set properly.
curl_exec($ch);

// # Define the POST parameters that the import_orders.php file expects.
$thePost = array ("perform" => 'generate');

// # Set up the proper upload session.
curl_setopt ($ch, CURLOPT_POST, true);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $thePost);
curl_setopt($ch, CURLOPT_URL, $URI);

// # Send upload request.
$Res = curl_exec ($ch);

 if(curl_exec($ch) === false) {
    trigger_error('cURL error: ' . curl_error($ch));

} else {
    error_log('Operation completed successfully without any errors');
}


curl_close ($ch);



// # Delete admin session.
$Query = "DELETE FROM admin_sessions WHERE admin_sessid = ''";
$Query = sprintf ($Query, $dbObject->real_escape_string($SessID));
if (!$dbObject->query ($Query)) {
	trigger_error ("Could not delete admin session. Error: ".$dbObject->error, E_USER_WARNING);
}

$Query = sprintf ("DELETE FROM admin_sessions WHERE admin_user='%s'", $dbObject->real_escape_string($FeedName));

if (!$dbObject->Query ($Query)) {
        trigger_error ("Coult not remove from admin group.\nQuery: $Query\nError: ".$dbObject->error, E_USER_WARNING);
}
exit();
}

  require('includes/application_top.php');
  date_default_timezone_set('America/New_York');
  include(DIR_FS_LANGUAGES.$language.'/'.FILENAME_MODULES);


  $set = (isset($_GET['set'])) ? $_GET['set'] : '';

  $modset = tep_module($set);
  $modules = $modset->getAllModules();
  if (isset($_GET['module']) && isset($modules[$_GET['module']])) $curmod = $modules[$_GET['module']];

  if (!isset($curmod)) list($curmod) = array_values($modules);

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  $cferr = array();

  if (tep_not_null($action) && isset($curmod)) {
    switch ($action) {
      case 'enable':
	$modset->enableModule($curmod);
	break;
      case 'disable':
	$modset->disableModule($curmod);
	break;
      case 'save':

	$lcnf = $curmod->listConf();
	$cvals = array();
	foreach ($lcnf AS $key=>$cnf) {
		if(isset($_POST['conf_'.$key])) { 
			$cvals[$key] = $_POST['conf_'.$key];
		} else {
			$cvals[$key] = $cnf['default'];
		}
	}
	foreach ($cvals AS $key=>$val) {
	  $er=$curmod->validateConf($key,$val);
	  if (isset($er)) $cferr[$key]=$er;
	  switch ($lcnf[$key]['type']) {
	    case 'savefile':

	      if ($f=@fopen($curmod->getConf($key),'w')) {
	        fwrite($f,$val);
		fclose($f);
	      } else $cferr[$key]='Error saving file';
	      break;
	    default:
	      $curmod->setConf($key,$val);
		 }
	}
	if ($cferr) {
	  $action='edit';
	  break;
	}
	$curmod->saveConf();
	if (isset($_POST['enabled']) && $_POST['enabled']) $modset->enableModule($curmod,$_POST['sort_order']);
	else $modset->disableModule($curmod);
        tep_redirect(tep_href_link('module_config.php', 'set=' . $set . '&module=' . $_GET['module']));
        break;
    }
  }

  $mlst=$modset->listModules();

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?=$modset->getName()?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="js/css.css">
<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="js/prototype.lite.js"></script>
</head>
<body style="background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td width="58" style="padding:0 0 5px 5px"><img src="/admin/images/feeds-icon.gif" width="48" height="48" alt=""></td>
								<td class="pageHeading"><?=$modset->getName()?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top">
	    
<?php
    if ($curmod && isset($_POST['perform'])) {
      $rs=$curmod->actionPerform($_POST['perform']);
      echo is_array($rs)?join('<br>',$rs):$rs;
    }
?>
	    
	    <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_MODULES; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_SORT_ORDER; ?></td>
                <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_ACTION; ?></td>
              </tr>
<?php
 ksort($modules);
  foreach ($modules AS $key=>$mod) {
        if (isset($curmod) && $key==get_class($curmod)) {
          echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
        } else {
          echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link('module_config.php', 'set=' . $set . '&module=' . $key) . '\'">' . "\n";
        }
?>
                <td class="dataTableContent"><?php echo $mod->getName(); ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($mlst[$key])) echo $mlst[$key]['sort_order']?></td>
		<td class="dataTableContent" colspan="2">
<?php
    if (isset($mlst[$key]) && $mlst[$key]['mods_enabled']>0) {
      echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '&nbsp;&nbsp;<a href="'.tep_href_link('module_config.php', 'set='.$set.'&module='.$key.'&action=disable').'">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
    } else {
      echo ($mod->checkConf()?'<a href="'.tep_href_link('module_config.php', 'set='.$set.'&module='.$key.'&action=enable').'">' .tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>':tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', 'Not Configured', 10, 10)).'&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
    }      
?>
		</td>
                
              </tr>
<?php
  }

?>
            </table></td>
<?php
  $modinfo=(isset($curmod) && isset($mlst[get_class($curmod)]))?$mlst[get_class($curmod)]:Array();
  $heading = array();
  $contents = array();

  if (isset($curmod)) 

	switch ($action) {
    case 'edit':
		$dspljs = array();
	    $cfunc = 'fChange_'.get_class($curmod);
	    $idprf = 'conf_';
	
	    $frm='';

		foreach ($curmod->listConf() AS $key=>$cnf) {

			$fname = $idprf.$key;
			$fval = $curmod->getConf($key);
			$ftype = (isset($cnf['type'])) ? $cnf['type'] : 'text';

			if(!isset($fval)) $fval='';

			$ext = 'onChange="'.$cfunc.'();"';

			$frm .= '<div id="'.$idprf.$key.'" class="conf_field">';
			if(isset($cferr[$key])) { 
				$frm.='<p class="conf_error">'.$cferr[$key].'</p>';
			}

			switch ($ftype) {
			case 'select':
				$sel = array();
	
				foreach ($cnf['options'] AS $oi=>$ov) $sel[] = array('id'=>$oi,'text'=>$ov);
			
				$frm .= $cnf['title'].': '.tep_draw_pull_down_menu($fname,$sel,$fval,$ext);
			break;
	
			case 'checkbox':
				$frm .= tep_draw_checkbox_field($fname,1,$fval,'',$ext).'&nbsp;'.$cnf['title'];
			break;
	
			case 'radio':
				$frm .= $cnf['title'].':<br>';
		
				foreach ($cnf['values'] AS $v=>$txt) { 
					$frm.=tep_draw_radio_field($fname,$v,($fval==$v),'',$ext).'&nbsp;'.$txt.'<br>';
				}
			break;
	
			case 'savefile':
				if($fval) $f=@fopen($fval,'r');
				$frm .= $cnf['title'].':<br><textarea name="'.$fname.'">'.htmlspecialchars(fread($f,65535)).'</textarea>';
				fclose($f);
			break;

			default: 

				if(strpos($cnf['title'],'Password')) {
					$frm .= $cnf['title'].': '.tep_draw_password_field($fname,$fval);
				} else { 	
					$frm .= $cnf['title'].': '.tep_draw_input_field($fname,$fval,$ext);
				}
			}

		$frm .= "</div>\n";
		if(isset($cnf['js_visible'])) { 
			$dspljs[$key] = $cnf['js_visible'];
		}
	} // # END foreach


	$frm.="
		<script type=\"text/javascript\">
			function $cfunc() {
				var frm = document.forms['modules'];
				var val = function(f) {
				var e = frm.elements['$idprf'+f];
				return e.value;
			}";

		foreach ($dspljs AS $fld=>$expr) $frm.="    \$('$idprf$fld').style.display=($expr)?'':'none';\n";
    
		$frm.="
			if(window.contentChanged) window.contentChanged();
			}
			$cfunc();
		</script>";

      $heading[] = array('text' => '<b>' . $curmod->getName() . '</b>');

      $contents = array('form' => tep_draw_form('modules', 'module_config.php', 'set=' . $set . '&module=' . get_class($curmod) . '&action=save'));
      $contents[] = array('text' => '<input type="checkbox" name="enabled" value="1"'.(isset($modinfo['mods_enabled']) && $modinfo['mods_enabled']>0?' checked':'').'> Enable<br>Sort Order: '.tep_draw_input_field('sort_order',(isset($modinfo['sort_order'])?$modinfo['sort_order']:0),'size="3"'));
      $contents[] = array('text' => $frm);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_update.gif', IMAGE_UPDATE) . ' <a href="' . tep_href_link('module_config.php', 'set=' . $set . '&module=' . $_GET['module']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;

default:
        $heading[] = array('text' => '<b>' . $curmod->getName() . '</b>');
        $contents[] = array('align' => 'center', 
							'text' => '<a href="' . tep_href_link('module_config.php', 'set=' . $set . (isset($_GET['module']) ? '&module=' . $_GET['module'] : '') . '&action=edit') . '">'.tep_image_button('button_edit.gif', IMAGE_EDIT).'</a>');
		$cf = array();
		$clst = $curmod->listConf();


		foreach ($clst AS $k=>$cnf) {

			if(isset($cnf['values']) && isset($cnf['values'][$curmod->getConf($k)])) {			
				$cf[] = $cnf['title'].':<br><b>'.$cnf['values'][$curmod->getConf($k)].'</b><br>';

			} else { 			
				if(strpos($cnf['title'],'Password')) {
					$getItems = $curmod->getConf($k);
					$getItems = str_repeat('*', strlen($getItems));
					$cf[] = $cnf['title'].':<br><b>'.htmlspecialchars($getItems).'</b><br>';
				} else { 
					$cf[] = $cnf['title'].':<br><b>'.htmlspecialchars($curmod->getConf($k)).'</b><br>';
				}
			}


		}

		$actns = $curmod->actionList();



$filename = $curmod->filename;

	if ($actns) {
	if (file_exists($filename)) $cf[] = 'Last generated on:<br> ' . date ('M. dS, Y  - h:ia T.', filemtime($filename));
	  $cf[] .= '<hr>';
	  $f = array('<form method="post" action="module_config.php?set='.$_GET['set'].'&module='.get_class($curmod).'">');
	  foreach ($actns AS $ac=>$acname) $f[]='<button type="submit" name="perform" value="'.$ac.'">'.$acname.'</button>';
	  $f[]='</form>';
	  $cf[]=join('',$f);
	}

	$contents[]=array('text' => join("<br>\n",$cf));
break;
  }

  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '<td width="25%" valign="top">';

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '</td>' . "\n";
}
?>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); 
?>
