<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * Functions that have to do with SpamRule Functionality
 *
 * @version $Id: spamrule.inc.php,v 1.3 2006-01-13 16:25:28 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004-2005 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Ask LDAP Server's Sendmail configuration for configured RBLs.
 *
 * @global array $ldapserver Squirrelmail's ldap server configuration
 * @return RBLs structure, or false if no such configuration present. Structure
 * looks like this:
 * Array(
 *   [0] => Array
 *       (
 *           [host] => relays.ordb.org
 *           [name] => Open Relay DataBase
 *           [serverweight] => 50
 *       )
 */
function avelsieve_askldapforrbls() {
	global $ldap_server;

	foreach($ldap_server as $ldapno=>$info) {
		if(isset($info['mtarblspamfilter'])) {
			$mtarblspamfilter = $info['mtarblspamfilter'];
			$ls = $ldapno;
			break;
		}
	}
		
	if(!isset($mtarblspamfilter)) {
		return false;
	}

	if(!($ldap = ldap_connect($ldap_server[$ls]['host']))) {
		print "Could not connect to LDAP!";
		return false;
	}

	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3); 

	if(isset($ldap_server[$ls]['binddn'])) {
		$bind_result = ldap_bind($ldap, $ldap_server[$ls]['binddn'], $ldap_server[$ls]['bindpw']);
		if (!$bind_result) {
			print "Error while binding to LDAP server";
			return false;
		}
	}
		
	if (!($search_result = ldap_search($ldap, $ldap_server[$ls]['mtarblspambase'],
		$ldap_server[$ls]['mtarblspamfilter']))) {
		print "Failed to search for SPAM RBLs.";
		return false;
	}

	$info = ldap_get_entries($ldap, $search_result);

	$spamrbls = array();
	
	for($j=0; $j<$info['count']; $j++) {

	if(isset($info[$j]['sendmailmtaclassname']) &&
	   $info[$j]['sendmailmtaclassname'][0] == 'SpamRBLs') {

		unset ($spamrule_tests);
		$spamrule_tests = array();

		$spamrule_temp = array();
		for($i=0; $i<$info[$j]['sendmailmtaclassvalue']['count']; $i++) {
			$spamrule_temp[] =  $info[$j]['sendmailmtaclassvalue'][$i];
		}
		$spamrule_temp = str_replace('<', '', $spamrule_temp);
		$spamrule_temp = str_replace('>', '', $spamrule_temp);
		
		$temp=array();
		for($i=0; $i<sizeof($spamrule_temp); $i++) {
			$temp[$i] = explode('@', $spamrule_temp[$i]);
		}
		$temp2 = array();
		for($i=0; $i<sizeof($temp); $i++) {
			$temp2[$i] = explode(':', $temp[$i][0]);
		}
		for($i=0; $i<sizeof($temp); $i++) {
			$spamrbls[$i]['host'] = $temp[$i][1];
			$spamrbls[$i]['name'] = $temp2[$i][0];
			$spamrbls[$i]['test'] = str_replace(' ', '.', $temp2[$i][0]);
			$spamrbls[$i]['serverweight'] = $temp2[$i][1];
		}
		
		/* TODO: Replace explode() with one smart regexp */
	}
	}

	for($j=0; $j<$info['count']; $j++) {
		if(isset($info[$j]['sendmailmtaclassname']) &&
		   $info[$j]['sendmailmtaclassname'][0] == 'SpamForged') {
	
			$no = sizeof($spamrbls);
			$spamrbls[$no]['name'] = _("Test for Forged Header");
			$spamrbls[$no]['test'] = 'FORGED';
			$spamrbls[$no]['serverweight'] = $info[$j]['sendmailmtaclassvalue'][0];
		}
	}
	
	return($spamrbls);
}

/**
 * Create or update an entry in the squirrelmail highlight list, when there is
 * a spam rule.
 *
 * Note: this function requires a patch in functions/imap_messages.php, which at
 * the moment is not releasable. Perhaps two new plugin hooks should be in
 * place in order to support that.
 *
 * @param $rules array
 * @return void
 */
function avelsieve_spam_highlight_update(&$rules) {
	global $data_dir, $username, $color, $avelsieve_spam_highlight_enable;
	if(!isset($avelsieve_spam_highlight_enable) ||
	  (isset($avelsieve_spam_highlight_enable) && !$avelsieve_spam_highlight_enable)) {
		return;
	}

	/* TODO: Probably move these arguments to configuration file */
	$avelsieve_hili_name = 'SPAM';
	$avelsieve_hili_color = $color[3];
	$avelsieve_hili_value = ';';
	$avelsieve_hili_match_type = 'x-spam-tests';

	$hili=getPref($data_dir, $username, 'hililist', '');
	$hilight = unserialize($hili);

	$hilight_exists = false;
	foreach($hilight as $h) {
		if($h['name'] == 'SPAM') {
			$hilight_exists = true;
		}
	}
	
	$rule_exists = false;
	for($i=0; $i<sizeof($rules); $i++) {
		if($rules[$i]['type'] == '10') {
			$rule_exists = true;
		}
	}
		
	if($rule_exists) {
		if(!$hilight_exists) {
			$hilight[] = array(
				'name' => $avelsieve_hili_name,
				'color' => $avelsieve_hili_color,
				'value' => $avelsieve_hili_value,
				'match_type' => $avelsieve_hili_match_type
			);
			setPref($data_dir, $username, 'hililist', serialize($hilight));
		}
	} else {
		if($hilight_exists) {
			/* Here we could remove the highlight rule, but I guess it won't
			 * hurt leaving it in there. */
		}
	}
}
 
?>
