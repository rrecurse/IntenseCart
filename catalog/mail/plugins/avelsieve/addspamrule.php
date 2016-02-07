<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Special form for SPAM mail filtering rules.
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: addspamrule.php,v 1.21 2006-06-30 12:56:10 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2002-2004 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 * @todo: Probably Import spamrule_filters from Filters plugin. It contains a
 * lot of spam rbls definitions.
 * @todo This file needs a lot of work to become like edit.php....
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

include_once(SM_PATH . 'plugins/avelsieve/config/config.php');
include_once(SM_PATH . 'plugins/avelsieve/include/support.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_rulestable.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_ruleedit.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_actions.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/process_user_input.inc.php');

sqsession_is_active();

sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('rules', $rules, SQ_SESSION);

sqgetGlobalVar('edit', $edit, SQ_GET & SQ_POST);

$backend_class_name = 'DO_Sieve_'.$avelsieve_backend;
$s = new $backend_class_name;
$s->init();

if(isset($_POST['cancel'])) {
	header("Location: ./table.php");
	exit;
}

if(isset($edit)) {
	$mode = 'edit';
	$rule = &$rules[$edit];
} else {
	$mode = 'addnewspam';
	$rule = array('type' => 10);
}

if(isset($edit)) {
	/* Have this handy: type of current rule */
	$type = $_SESSION['rules'][$edit]['type'];
	/* and the rule itself */
	$rule = $_SESSION['rules'][$edit];
	if($type != 10) {
		header("Location: edit.php?edit=$edit");
	}
	if(isset($_GET['dup'])) {
		$dup = true;
		$mode = 'duplicatespam';
	}
}

if(isset($_POST['spamrule_advanced'])) {
	$spamrule_advanced = true;
} elseif (isset($edit) && isset($rule['advanced'])) {
	$spamrule_advanced = true;
} else {
	$spamrule_advanced = false;
}

$spamrule = true;
global $spamrule;

/* Spam Rule variables */

/* If we need to get spamrule RBLs from LDAP, then do so now. */

if(isset($_SESSION['spamrule_rbls'])) {
	$spamrule_rbls = $_SESSION['spamrule_rbls'];
} elseif(isset($spamrule_tests_ldap) && $spamrule_tests_ldap == true &&
   !isset($_SESSION['spamrule_rbls'])) {
	include_once(SM_PATH . 'plugins/avelsieve/include/spamrule.inc.php');
	$spamrule_rbls = avelsieve_askldapforrbls();
	$_SESSION['spamrule_rbls'] = $spamrule_rbls;
}

if(isset($_POST['tests'])) {
	$tests = $_POST['tests'];
} elseif (isset($edit) && isset($rule['tests'])) {
	$tests = $rule['tests'];
} else {
	$tests = array_keys($spamrule_tests);
}

if(isset($_POST['score'])) {
	$score = $_POST['score'];
} elseif (isset($edit) && isset($rule['score'])) {
	$score = $rule['score'];
} else {
	$score = $spamrule_score_default;
}

/* Whitelist number of items to display */
if(isset($_POST['whitelistitems'])) {
	$whitelistitems = $_POST['whitelistitems'];
} elseif (isset($edit) && isset($rule['whitelist'])) {
	$whitelistitems = sizeof($rule['whitelist']) + 1;
} else {
	$whitelistitems = $startitems;
}
if(isset($_POST['whitelist_add'])) {
	$whitelistitems++;
}

/* The actual whitelist */
if(isset($_POST['whitelist_add']) || isset($_POST['apply'])) {
	$j=0;
	for($i=0; $i< $whitelistitems; $i++) {
		if(!empty($_POST['cond'][$i]['headermatch'])) {
			$whitelist[$j]['header'] = $_POST['cond'][$i]['header'];
			$whitelist[$j]['matchtype'] = $_POST['cond'][$i]['matchtype'];
			$whitelist[$j]['headermatch'] = $_POST['cond'][$i]['headermatch'];
			$j++;
		}
	}
} elseif (isset($edit) && isset($rule['whitelist'])) {
	$whitelist = $rule['whitelist'];
}

if(isset($_POST['action']))  {
	$action = $_POST['action'];
} elseif (isset($edit) && isset($rule['action'])) {
	$action = $rule['action'];
} else {
	$action = $spamrule_action_default;
}


if(isset($_POST['stop']))  {
	$stop = 1;
} elseif (isset($edit) && isset($rule['stop']) && !isset($_POST['apply'])) {
	$stop = $rule['stop'];
} else {
	$stop = 1;
}

if(isset($_POST['finished']) || isset($_POST['apply']) || isset($_POST['addnew'])) {
	/* get it together & save it */
	if($action == 'junk' && isset($_POST['junkprune_saveme'])) {
		/* Save previously unset (or zero) junkprune variable */
		setPref($data_dir, $username, 'junkprune', $_POST['junkprune_saveme']);
	}

	$newrule['type'] = 10;
	$newrule['tests'] = $tests;
	$newrule['score'] = $score;
	$newrule['action'] = $action;
	if(isset($whitelist)) {
		$newrule['whitelist'] = $whitelist;
	}
	if($spamrule_advanced) {
		$newrule['advanced'] = 1;
	}
	if(isset($stop) && $stop) {
		$newrule['stop'] = $stop;
	}

	if(isset($edit) && !isset($dup)) {
		$_SESSION['comm']['edited'] = $edit;
		$_SESSION['haschanged'] = true;
		$_SESSION['rules'][$edit] = $newrule;
	} else {
		$_SESSION['returnnewrule'] = $newrule;
		$_SESSION['comm']['edited'] = $edit;
		$_SESSION['comm']['new'] = true;
	}

	header('Location: table.php');
	exit;
}


/* ----------------- start printing --------------- */

$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
textdomain ('squirrelmail');

displayPageHeader($color, 'None');

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

require_once (SM_PATH . 'plugins/avelsieve/include/constants.inc.php');

$ht = new avelsieve_html_edit($s, $mode, $rule, false);
			
echo '<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
	$ht->table_header( _("Add SPAM Rule") ) .
	$ht->all_sections_start().
	$ht->section_start( _("Configure Anti-SPAM Protection") ).
	'<p>' . _("All incoming mail is checked for unsolicited commercial content (SPAM) and marked accordingly. This special rule allows you to configure what to do with such messages once they arrive to your Inbox.") . '</p>';

if(!$spamrule_advanced) {
	echo '<p>'. sprintf( _("Select %s to add the predefined rule, or select the advanced SPAM filter to customize the rule."), '<strong>' . _("Add Spam Rule") . '</strong>' ) . '</p>'.
		'<p style="text-align:center"> <input type="submit" name="spamrule_advanced" value="'. _("Advanced Spam Filter...") .'" /></p>';

} else {

	/*
	include_once(SM_PATH . 'plugins/filters/filters.php');
	$spamfilters = load_spam_filters();
	*/

    echo '<input type="hidden" name="spamrule_advanced" value="1" />'.
        '<ul><li><strong>'. _("Target Score") . '</strong>';
	
	/* If using sendmail LDAP configuration, get the sum of maximum score */
	if(isset($spamrule_rbls)) {
		$spamrule_score_max = 0;
		foreach($spamrule_rbls as $no=>$info) {
			if(isset($info['serverweight'])) {
				$spamrule_score_max += $info['serverweight'];
			}
		}
	}

	echo '<br/>'. sprintf( _("Messages with SPAM-Score higher than the target value, the maximum value being %s, will be considered SPAM.") , $spamrule_score_max ) .
        '<br/>'. _("Target Score") . ': <input name="score" id="score" value="'.$score.'" size="4" /><br/><br/>'.

	    '</li><li><strong>'. _("SPAM Lists to check against") .'</strong><br/>';
	
	/**
	 * Print RBLs that are available in this system.
	 * 1) Check for RBLs in LDAP Sendmail configuration
	 * 2) Use RBLs supplied in config.php
	 */
	 
	if(isset($spamrule_rbls)) {
		/* from LDAP */
		foreach($spamrule_rbls as $no=>$info) {
			echo '<input type="checkbox" name="tests[]" value="'.$info['test'].'" id="spamrule_test_'.$no.'" ';
			if(in_array($info['test'], $tests)) {
				echo 'checked="CHECKED" ';
			}
			echo '/> '.
			    '<label for="spamrule_test_'.$no.'">'.$info['name'].' ('.$info['serverweight'].')</label><br />';
		}
			
	} elseif(isset($spamrule_tests)) {
		/* from config.php */
		foreach($spamrule_tests as $st=>$txt) {
			echo '<input type="checkbox" name="tests[]" value="'.$st.'" id="spamrule_test_'.$st.'" ';
			if(in_array($st, $tests)) {
				echo 'checked="CHECKED" ';
			}
			echo '/> '.
			    '<label for="spamrule_test_'.$st.'">'.$txt.'</label><br />';
		}
	/*
	} elseif(isset($spamrule_filters)) {
	foreach($spamrule_filters as $st=>$fi) {
		echo '<input type="checkbox" name="tests[]" value="'.$st.'" id="spamrule_test_'.$st.'" ';
		if(in_array($st, $tests)) {
			echo 'checked="CHECKED" ';
		}
		echo '/> '.
		    '<label for="spamrule_test_'.$st.'">'$fi.['name'].'</label><br />';
	}
	*/
	}
	echo '<br/><br/></li>';

	/**
	 * Whitelist
	 * Emails with these header-criteria will never end up in Junk / Trash
	 * / discard.
	 */
	
	echo '<li><strong>' . _("Whitelist") . '</strong>'.
		'<br/>'. _("Messages that match any of these header rules will never end up in Junk Folders or regarded as SPAM.") .
        '<br/><br/>';
        '<input type="hidden" name="whitelistitems" value="'.$whitelistitems.'" />';

	for($i=0; $i<$whitelistitems; $i++) {
		echo $ht->header_listbox(
			isset($whitelist[$i]['header']) ? $whitelist[$i]['header'] : 'From' , $i
		);
		echo $ht->matchtype_listbox(
			isset($whitelist[$i]['matchtype']) ?  $whitelist[$i]['matchtype'] : '' , $i, 'matchtype'
		);
		echo '<input name="cond['.$i.'][headermatch]" value="'.
			( isset($whitelist[$i]['headermatch']) ? $whitelist[$i]['headermatch'] : '' ) .
			'" size="18" />'.
			'<br/>';
	}
	echo '<br/><input type="submit" name="whitelist_add" value="'._("More...").'"/><br/><br/></li>';

	/**
	 * Action
	 */
	echo '<li><strong>'. _("Action") . '</strong><br/>';
	
	$trash_folder = getPref($data_dir, $username, 'trash_folder');
	foreach($spamrule_actions as $ac=>$in) {
	
		if($ac == 'junk' && (!in_array('junkfolder', $plugins))) {
			continue;
		}
		if($ac == 'trash' && ($trash_folder == '' || $trash_folder == 'none')) {
			continue;
		}
	
		echo '<input type="radio" name="action" id="action_'.$ac.'" value="'.$ac.'" '; 
		if($action == $ac) {
			echo 'checked="CHECKED" ';
		}
		echo '/> ';
	
		echo ' <label for="action_'.$ac.'"><strong>'.$in['short'].'</strong> - '.$in['desc'].'</label><br/>';
	}


	echo '</li></ul>';

}

if(isset($junkprune_saveme)) {
	echo '<input type="hidden" name="junkprune_saveme" value="'.$junkfolder_days.'" />';
}
	
/* STOP */
	
echo '<br /><input type="checkbox" name="stop" id="stop" value="1" ';
if(isset($stop)) {
    echo 'checked="CHECKED" ';
}
echo '/><label for="stop">';
if ($useimages) {
    echo '<img src="images/stop.gif" width="35" height="33" border="0" alt="'. _("STOP") . '" align="middle" /> ';
} else {
    echo "<strong>"._("STOP").":</strong> ";
}
echo _("If this rule matches, do not check any rules after it."). '</label>'.
    $ht->section_end().
    $ht->submit_buttons(). '</div>'.
    $ht->all_sections_end().
    $ht->table_footer() . '</form>';

?>
</body></html>
