<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: constants.inc.php,v 1.17 2006-07-24 13:46:11 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

define('AVELSIEVE_BUGREPORT_EMAIL', 'avelsieve_bug_report@edunet.gr');

$conditions = array(
	"and" => _("AND (Every item must match)"),
	"or" => _("OR (Either item will match)")
);

$avelsieve_maintypes = array(
	1 => _("Rule"),
	10 => _("Anti-SPAM")
);

$types = array(
	'address' => array(
		'order' => 1,
		'name' => _("Address"),
		'description' => _("Perform an action depending on email addresses appearing in message headers.")
	),
	'header' => array(
		'order' => 0,
		'name' => _("Header"),
		'description' => _("Perform an action on messages matching a specified header (From, To etc.).")
	),
	'envelope' => array(
		'order' => 2,
		'name' => _("Envelope"),
		'description' => _("Perform an action on messages matching a specified envelope header (Envelope FROM, TO)."),
        'dependencies' => array('envelope')
	),
	'size' => array(
		'order' => 4,
		'name' => _("Size"),
		'description' => _("Perform an action on messages depending on their size.")
	),
	'body' => array(
		'order' => 3,
		'name' => _("Body"),
		'description' => _("Perform an action on messages depending on their content (body text)."),
		'dependencies' => array('body')
	),
	'all' => array(
		'order' => 5,
		'name' => _("All"),
		'description' => _("Perform an action on <strong>all</strong> incoming messages.")
	)
);

$actions = array(
	'keep', 'discard', 'reject', 'redirect', 'fileinto', 'vacation'
);
$additional_actions = array(
	'stop', 'notify', 'keepdeleted', 'disabled'
);


$matchtypes = array(
	"contains" => _("contains"),
	"does not contain" => _("does not contain"),
	"is" => _("is"),
	"is not" => _("is not"),
	"matches" => _("matches") . " " . _("wildcard"),
	"does not match" => _("does not match") . " " . _("wildcard")
);

$matchregex = array(
	'regex' => _("matches") . " " . _("regexp"),
	'not regex' => _("does not match") . " " . _("regexp")
);


$comparators = array(
	'gt' => '>  ' . _("is greater than"),
	'ge' => '=> ' . _("is greater or equal to"),
	'lt' => '<  ' . _("is lower than"),
	'le' => '<= ' . _("is lower or equal to"),
	'eq' => '=  ' . _("is equal to"),
	'ne' => '!= ' . _("is not equal to")
) ;
// gt" / "ge" / "lt""le" / "eq" / "ne"


$displaymodes = array(
	'verbose' => array( _("verbose"), _("Textual descriptions of the rules")),
	'terse' => array( _("terse"), _("More suitable for viewing the table of rules at once")),
	'tech' => array( _("tech"), _("Even more terse description for technical people")) ,
	'source' => array( _("source"), _("Display SIEVE source"))
);

if(AVELSIEVE_DEBUG == 1) {
	$displaymodes['debug'] = array('debug', 'Debugging mode (avelsieve variables)');
}

global $implemented_capabilities;
$implemented_capabilities = array('fileinto', 'envelope', 'reject', 'vacation', 'imapflags', 'relational', 'regex', 'notify', 'body');

global $cap_dependencies;  
$cap_dependencies['relational'] = array("comparator-i;ascii-numeric");

$prioritystrings = array(
	'low' => _("Low"),
	'normal' => _("Normal"),
	'high' => _("High")
);

/* Tools (Icons in table.php) */

$fmt = 'gif';

$avelsievetools = array(
	'rm' => array(
		'desc' => _("Delete"),
		'img' => "del.$fmt"
		),
	'edit' => array(
		'desc' => _("Edit"),
		'img' => "edit.$fmt"
		),
	'dup' => array(
		'desc' => _("Duplicate"),
		'img' => "dup.$fmt"
		),
	'mvup' => array(
		'desc' => _("Move Up"),
		'img' => "up.$fmt"
		),
	'mvtop' => array(
		'desc' => _("Move to Top"),
		'img' => "top.$fmt"
		),
	'mvdn' => array(
		'desc' => _("Move Down"),
		'img' => "down.$fmt"
		),
	'mvbottom' => array(
		'desc' => _("Move to Bottom"),
		'img' => "bottom.$fmt"
		)
);
	

if($spamrule_enable==true) {

if(in_array('junkfolder', $plugins)) {
	include SM_PATH . 'plugins/junkfolder/config.php';
	if(in_array('ldapuserdata', $plugins)) {
		$jd = getpref($data_dir, $username, 'junkprune');
		if(isset($jd) && $jd > 0) {
			$junkfolder_days = $jd;
		} else {
			$junkprune_saveme = true;
		}
	}
} else {
	$junkfolder_days = 7; /* Dummy default for E_ALL */
}


$spamrule_actions = array(
	'junk' => array(
		'short' => _("Junk Folder"),
		'desc' => sprintf( _("Store SPAM message in your Junk Folder. Messages older than %s days will be deleted automatically."), $junkfolder_days) . ' ' . _("Note that you can set the number of days in Folder Preferences.")
		),
	'trash' => array(
		'short' => _("Trash Folder"),
		'desc' => _("Store SPAM message in your Trash Folder. You will have to purge the folder yourself.")
		),

	'discard' => array(
		'short' => _("Discard"),
		'desc' => _("Discard SPAM message. You will get no indication that the message ever arrived.")
		)
);

}

/* Version Info for SIEVE scripts */

$avelsieve_version = array(
	'major' => 1,
	'minor' => 9,
	'release' => 7,
	'string' => "1.9.7"
);

$available_envelope = array('from', 'to');

if($avelsieve_enable_envelope_auth) {
	$available_envelope[] = 'auth';
}

/* Headers that typically include email addresses, for the :address check */
$available_address_headers = array(
	'From', 'To', 'Cc', 'Bcc', 'Reply-To', 'Sender', 'Resent-From', 'Resent-To'
);

?>
