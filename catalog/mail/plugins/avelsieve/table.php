<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4
 *
 * Copyright (c) 2002 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Based on Dan Ellis' test scripts that came with sieve-php.lib
 * <danellis@rushmore.com> <URL:http://sieve-php.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * table.php: main routine that shows a table of all the rules and allows
 * manipulation.
 *
 * @version $Id: table.php,v 1.32 2006-06-26 09:33:24 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
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
    
include_once(SM_PATH . 'functions/imap_general.php');

include_once(SM_PATH . 'plugins/avelsieve/config/config.php');
include_once(SM_PATH . 'plugins/avelsieve/include/support.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_rulestable.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/spamrule.inc.php');

sqsession_is_active();

sqgetGlobalVar('popup', $popup, SQ_GET);
sqgetGlobalVar('haschanged', $haschanged, SQ_SESSION);

$location = get_location();

sqgetGlobalVar('rules', $rules, SQ_SESSION);
sqgetGlobalVar('scriptinfo', $scriptinfo, SQ_SESSION);
sqgetGlobalVar('logout', $logout, SQ_POST);

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

$backend_class_name = 'DO_Sieve_'.$avelsieve_backend;
$s = new $backend_class_name;
$s->init();

isset($popup) ? $popup = '?popup=1' : $popup = '';

sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
if(!isset($delimiter)) {
	$delimiter = sqimap_get_delimiter($imapConnection);
}

sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
	
require_once (SM_PATH . 'plugins/avelsieve/include/constants.inc.php');

if (!isset($rules)) {
	/* Login. But if the rules are cached, don't even login to SIEVE
	 * Server. */ 
	$s->login();

	/* Actually get the script 'phpscript' (hardcoded ATM). */
    if($s->load('phpscript', $rules, $scriptinfo)) {
        $_SESSION['rules'] = $rules;
        $_SESSION['scriptinfo'] = $scriptinfo;
    }
}

// unset($sieve->response);
// TODO

/* On to the code that executes if avelsieve script exists or if a new rule has
 * been created. */

if ($logout) {
	/* Activate phpscript and log out. */
	$s->login();

	if ($newscript = makesieverule($rules)) {

		$s->save($newscript, 'phpscript');
		avelsieve_spam_highlight_update($rules);

		if(!($s->setactive('phpscript'))){
			/* Just to be safe. */
			$errormsg = _("Could not set active script on your IMAP server");
			$errormsg .= " " . $imapServerAddress.".<br />";
			$errormsg .= _("Please contact your administrator.");
			print_errormsg($errormsg);
			exit;
		}
		$s->logout();
	
	} else {
		/* upload a null thingie!!! :-) This works for now... some time
		 * it will get better. */
		$s->save('', 'phpscript'); 
		avelsieve_spam_highlight_update($rules);
		/* if(sizeof($rules) == "0") {
			$s->delete('phpscript');
		} */
	}
	session_unregister('rules');
	
	header("Location: $location/../../src/options.php\n\n");
	// header("Location: $location/../../src/options.php?optpage=avelsieve\n\n");
	exit;

} elseif (isset($_POST['addrule'])) {
	header("Location: $location/edit.php?addnew=1");
	exit;

} elseif (isset($_POST['addspamrule'])) {
	header("Location: $location/addspamrule.php");
	exit;
}

/* Routine for Delete / Delete selected / enable selected / disable selected /
 * edit / duplicate / moveup/down */

if(isset($_GET['rule']) || isset($_POST['deleteselected']) ||
	isset($_POST['enableselected']) || isset($_POST['disableselected']) ) {

	if (isset($_GET['edit'])) {
		header("Location: $location/edit.php?edit=".$_POST['rule']."");
		exit;

	} elseif (isset($_GET['dup'])) {
		header("Location: $location/edit.php?edit=".$_POST['rule']."&dup=1");
		exit;

	} elseif (isset($_GET['rm']) || ( isset($_POST['deleteselected']) && isset($_POST['selectedrules'])) ) {
		if (isset($_POST['deleteselected'])) {
			$rules2 = $rules;
			foreach($_POST['selectedrules'] as $no=>$sel) {
				unset($rules2[$sel]);
			} 
			$rules = array_values($rules2);
			$_SESSION['comm']['deleted'] = $_POST['selectedrules'];

		} elseif(isset($_GET['rm'])) {
			$rules2 = $rules;
			unset($rules2[$_GET['rule']]);
			$rules = array_values($rules2);
			$_SESSION['comm']['deleted'] = $_GET['rule'];
		}

	    if (!$conservative) {
		    $s->login();
		    if(sizeof($rules) == 0) {
				$s->delete('phpscript');
			}  else {
		        $newscript = makesieverule($rules);
    		    $s->save($newscript, 'phpscript');

            }
	    	avelsieve_spam_highlight_update($rules);
		    sqsession_register($rules, 'rules');
		} 
        /* Since removing rules is a destructive function, we should redirect
         * to ourselves so as to eliminate the 'rm' GET parameter. (User could
         * do "Reload Frame" in browser) */
	    sqsession_register($rules, 'rules');
        session_write_close();
	    header("Location: $location/table.php\n\n");
        exit;
	
	} elseif(isset($_POST['enableselected']) || isset($_POST['disableselected'])) {
		foreach($_POST['selectedrules'] as $no=>$sel) {
			if(isset($_POST['enableselected'])) {
				/* Verify that it is enabled  by removing the disabled flag. */
				if(isset($rules[$sel]['disabled'])) {
					unset($rules[$sel]['disabled']);
				}
			} elseif(isset($_POST['disableselected'])) {
				/* Disable! */
				$rules[$sel]['disabled'] = 1;
			}
		} 

	} elseif (isset($_GET['mvup'])) {
		$rules = array_swapval($rules, $_GET['rule'], $_GET['rule']-1);

	} elseif (isset($_GET['mvdn'])) {
		$rules = array_swapval($rules, $_GET['rule'], $_GET['rule']+1);
	
	} elseif (isset($_GET['mvtop'])) {

		/* Rule to get to the top: */
		$ruletop = $rules[$_GET['rule']];

		unset($rules[$_GET['rule']]);
		array_unshift($rules, $ruletop);

	} elseif (isset($_GET['mvbottom'])) {
		
		/* Rule to get to the bottom: */
		$rulebot = $rules[$_GET['rule']];
		
		unset($rules[$_GET['rule']]);
		
		/* Reindex */
		$rules = array_values($rules);

		/* Now Append it */
		$rules[] = $rulebot;

	}

	sqsession_register($rules, 'rules');
	
	/* Register changes to timsieved if we are not conservative in our
	 * connections with him. */

	if ($conservative == false && $rules) {
		$newscript = makesieverule($rules);
		$s->login();
		$s->save($newscript, 'phpscript');
		avelsieve_spam_highlight_update($rules);
	}
}	

if (isset($_SESSION['returnnewrule'])) {
    /* There is a new rule to be added */
	$newrule = $_SESSION['returnnewrule'];
	session_unregister('returnnewrule');
	$rules[] = $newrule;
	$haschanged = true;
}

if( (!$conservative && isset($haschanged) ) ) {
    /* Commit changes */
	$s->login();
	$newscript = makesieverule($rules);
	$s->save($newscript, 'phpscript');
	avelsieve_spam_highlight_update($rules);
	if(isset($_SESSION['haschanged'])) {
		unset($_SESSION['haschanged']);
	}

}

if(isset($rules)) {
	$_SESSION['rules'] = $rules;
	$_SESSION['scriptinfo'] = $scriptinfo;
}

if(isset($sieve_loggedin)) {
	$sieve->sieve_logout();
}
	
/* This is the place to do a consistency check, after all changes have been
 * done. We also grab the list of all folders. */
	
// $folder_prefix = "INBOX";
sqgetGlobalVar('key', $key, SQ_COOKIE);
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0); 
$boxes = sqimap_mailbox_list_all($imapConnection);
sqimap_logout($imapConnection); 
$inconsistent_folders = avelsieve_folder_consistency_check($boxes, $rules);

/* -------------------- Presentation Logic ------------------- */

$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
textdomain ('squirrelmail');

if($popup) {
	displayHtmlHeader('', '');
} else {
	displayPageHeader($color, 'None');
}

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

/* Debugging Part - Developers might want to enable this */
/*
include_once(SM_PATH . 'plugins/avelsieve/include/dumpr.php');
echo 'SESSION:';
dumpr($_SESSION);
echo 'POST:';
dumpr($_POST);
echo 'Rules:';
dumpr($rules);
*/

if(AVELSIEVE_DEBUG == 1) {
	print "Debug: Using Backend: $avelsieve_backend.<br/>";
}


if(isset($_GET['mode'])) {
	if(array_key_exists($_GET['mode'], $displaymodes)) {
		$mode = $_GET['mode'];
	} else {
		$mode = $avelsieve_default_mode;
	}
	sqsession_register($mode, 'mode');
	setPref($data_dir, $username, 'avelsieve_display_mode', $mode);
} else {
	if( ($mode_tmp = getPref($data_dir, $username, 'avelsieve_display_mode', '')) != '') {
		if(array_key_exists($mode_tmp, $displaymodes)) {
			$mode = $mode_tmp;
		} else {
			$mode = $avelsieve_default_mode;
		}
	} else {
		$mode = $avelsieve_default_mode;
	}
}
	
$ht = new avelsieve_html_rules($rules, $mode);

if(!empty($inconsistent_folders)) {
}

if($popup) {
	echo $ht->rules_confirmation();
} else {
	echo $ht->rules_table();
}

?>
</body></html>
