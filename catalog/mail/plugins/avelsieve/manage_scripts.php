<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4
 *
 * Copyright (c) 2002 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * manage_scripts.php:  listing of Sieve scripts in the server
 *
 * @version $Id$
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2005 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

define('SM_PATH','../../');
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'include/load_prefs.php');
include_once(SM_PATH . 'functions/page_header.php');
include_once(SM_PATH . 'functions/imap.php');

include_once(SM_PATH . 'plugins/avelsieve/config/config.php');
include_once(SM_PATH . 'plugins/avelsieve/include/support.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_main.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/spamrule.inc.php');

sqsession_is_active();

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

avelsieve_initialize($sieve);

isset($popup) ? $popup = '?popup=1' : $popup = '';

sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
if(!isset($delimiter)) {
    $delimiter = sqimap_get_delimiter($imapConnection);
}

sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);

avelsieve_login($sieve);
$scripts = avelsieve_listscripts($sieve);
$sieve->sieve_logout();

/* -------------------- Presentation Logic ------------------- */

displayPageHeader($color, 'None');

$ht = new avelsieve_html();
		
echo $ht->table_header(_("Mail Filtering Scripts")).
	$ht->all_sections_start();

print_r($scripts);

echo $ht->all_sections_end() .
    $ht->table_footer();

echo '</body></html>';
?>
