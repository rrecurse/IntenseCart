<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * This page is the main interface to editing and adding new rules.
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: edit.php,v 1.33 2006-06-30 10:35:49 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2002-2004 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

if (file_exists('../../include/init.php')) {
    include_once('../../include/init.php');
} else if (file_exists('../../include/validate.php')) {
    define('SM_PATH','../../');
    include_once(SM_PATH . 'include/validate.php');
    include_once(SM_PATH . 'include/load_prefs.php');
    include_once(SM_PATH . 'functions/page_header.php');
    include_once(SM_PATH . 'functions/date.php');
}
    
include_once(SM_PATH . 'functions/imap.php');

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

include_once(SM_PATH . 'plugins/avelsieve/config/config.php');
require_once(SM_PATH . 'plugins/avelsieve/include/constants.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_rulestable.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_ruleedit.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_actions.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/process_user_input.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/support.inc.php');

sqsession_is_active();

$errmsg = array();

/* Session / Server vars */
sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('rules', $rules, SQ_SESSION);
/* Mode of operation */
sqgetGlobalVar('dup', $dup, SQ_GET & SQ_POST);
sqgetGlobalVar('addnew', $addnew, SQ_GET);
/* New folder Creation */
sqgetGlobalVar('newfoldername', $newfoldername, SQ_POST);
sqgetGlobalVar('newfolderparent', $newfolderparent, SQ_POST);
/* Essentials */
sqgetGlobalVar('popup', $popup, SQ_GET);
sqgetGlobalVar('edit', $edit, SQ_FORM);

sqgetGlobalVar('previoustype', $previoustype, SQ_POST);
sqgetGlobalVar('cond', $new_cond, SQ_POST);
sqgetGlobalVar('previous_cond', $previous_cond, SQ_POST);

sqgetGlobalVar('serialized_rule', $serialized_rule, SQ_GET);

sqgetGlobalVar('type', $type_get, SQ_GET);

isset($popup) ? $popup = '?popup=1' : $popup = '';

$backend_class_name = 'DO_Sieve_'.$avelsieve_backend;
$s = new $backend_class_name;
$s->init();

/* If this page is called before table.php is ever shown, then we have to make
 * the current filtering rules available in the Session. This will happen when
 * a user clicks either:
 * i) creation of a rule from the message commands (while viewing a message)
 * ii) creation of a rule from some search criteria.
 */
if (!isset($rules)) {
	$s->login();
	/* Actually get the script 'phpscript' (hardcoded ATM). */
    if($s->load('phpscript', $rules, $scriptinfo)) {
        $_SESSION['rules'] = $rules;
        $_SESSION['scriptinfo'] = $scriptinfo;
    }
    $s->logout();
}

/* Create new mailbox, if required by the user. */
if($newfoldername) {
	$created_mailbox_name = '';
	avelsieve_create_folder($newfoldername, $newfolderparent, $created_mailbox_name, $errmsg);
}

if(isset($edit)) {
	/* Editing an existing rule */
	$rule = &$rules[$edit];
} elseif(isset($serialized_rule)) {
	$type = $type_get;
    $rule = unserialize(urldecode($serialized_rule));
} elseif(!isset($edit) && isset($type_get)) {
	/* Adding a new rule through $_GET */
	$type = $type_get;
	$rule = process_input(SQ_GET, $errmsg, false);
} else {
	/* Adding a new rule from scratch */
	$rule = array();
}


/* FIXME - old style type */
/*
if(isset($previoustype) && (
	$previoustype == 0 ||
	(isset($type) && $previoustype != $type)
  )) {
		$changetype = true;
} else {
		$changetype = false;
}
*/
$changetype = false;
if(isset($previous_cond) && isset($new_cond)) {
	foreach($previous_cond as $n=>$t) {
		if(isset($new_cond[$n]['type']) && $t['type'] != $new_cond[$n]['type']) {
			$changetype = true;
		}
	}
}

/* Available Actions that occur if submitting the form in a number of ways */

if(isset($_POST['cancel'])) {
	/* Cancel Editing */
	header("Location: table.php$popup");
	exit;

} elseif(isset($_POST['apply']) && !$changetype) {
	/* Apply change in existing rule */
	$editedrule = process_input(SQ_POST, $errmsg, true);
	if(empty($errmsg)) {
		$_SESSION['rules'][$edit] = $editedrule;
		$_SESSION['comm']['edited'] = $edit;
		$_SESSION['haschanged'] = true;
		header("Location: table.php$popup");
	} else {
		$rule = $editedrule;
	}

} elseif(isset($_POST['addnew']) && !$changetype) {
	/* Add new rule */
 	$newrule = process_input(SQ_POST, $errmsg, true);
	if(empty($errmsg)) {
		if(isset($dup)) {
			// insert moving rule in place
			array_splice($_SESSION['rules'], $edit+1, 0, array($newrule));
			// Reindex
			$_SESSION['rules'] = array_values($_SESSION['rules']);
		} else {
			$_SESSION['rules'][] = $newrule;
		}
		/* Communication: */
		$_SESSION['comm']['edited'] = $edit;
		$_SESSION['comm']['new'] = true;
		$_SESSION['haschanged'] = true;
		header("Location: table.php$popup");
		exit;
	} else {
        $rule = $newrule;
    }
} elseif($changetype || isset($_POST['append']) || isset($_POST['less'])) {
	/* still in editing; apply any changes. */
	$rule = process_input(SQ_POST, $errmsg, false);
}



/* Grab the list of my IMAP folders. This is only needed for the GUI, and is
 * done as the last step. */
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
if(!isset($delimiter)) {
	$delimiter = sqimap_get_delimiter($imapConnection);
}
// $folder_prefix = "INBOX";
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0); 
if($SQM_INTERNAL_VERSION[0] == 1 && $SQM_INTERNAL_VERSION[1] == 5) {
    /* In Squirrelmail 1.5.x, use sqimap_mailbox_list() with
     * $show_only_subscribed_folders flag off. Thanks to Simon Matter */
    global $show_only_subscribed_folders;
    $old_show_only_subscribed_folders = $show_only_subscribed_folders;
    $show_only_subscribed_folders = false;
    $boxes = sqimap_mailbox_list($imapConnection,true);
    /* Restore correct folder cache */
    $show_only_subscribed_folders = $old_show_only_subscribed_folders;
    $dummy = sqimap_mailbox_list($imapConnection,true);

} else {
    /* In Squirrelmail 1.4.x, use sqimap_mailbox_list_all() */
    $boxes = sqimap_mailbox_list_all($imapConnection);
}
sqimap_logout($imapConnection); 

/* Mode of operation */
if(isset($dup)) {
	$mode = 'duplicate';
} elseif(isset($addnew)) {
	$mode = 'addnew';
} else {
	$mode = 'edit';
}

/* -------------- Presentation Logic ------------- */

$js = '
<script language="JavaScript" type="text/javascript">
function checkOther(id){
	for(var i=0;i<document.addrule.length;i++){
		if(document.addrule.elements[i].value == id){
			document.addrule.elements[i].checked = true;
		}
	}
}
function el(id) {
  if (document.getElementById) {
    return document.getElementById(id);
  }
  return false;
}

function ShowDiv(divname) {
  if(el(divname)) {
    el(divname).style.display = "";
  }
  return false;
}
function HideDiv(divname) {
  if(el(divname)) {
    el(divname).style.display = "none";
  }
}
function ToggleShowDiv(divname) {
  if(el(divname)) {
    if(el(divname).style.display == "none") {
      el(divname).style.display = "";
	} else {
      el(divname).style.display = "none";
	}
  }	
}
</script>
';

$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
textdomain ('squirrelmail');
if($popup) {
	displayHtmlHeader('', $js);
} else {
	displayPageHeader($color, 'None');
	echo $js;
}

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

$ht = new avelsieve_html_edit($s, $mode, $rule, $popup, $errmsg);

if(isset($edit)) {
	echo $ht->edit_rule($edit);
} else {
	echo $ht->edit_rule();
}
	
?>
</body></html>
