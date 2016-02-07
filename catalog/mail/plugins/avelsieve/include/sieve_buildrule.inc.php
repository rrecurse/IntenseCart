<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve_buildrule.inc.php,v 1.25 2006-06-06 10:51:17 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Script Variables Schema
 * NB: Might be Incomplete.
 *
 * The following table tries to describe the variables schema that is used by
 * avelsieve.
 *
 * VARIABLES
 * ---------
 * AVELSIEVE_CREATED
 * AVELSIEVE_MODIFIED
 * AVELSIEVE_COMMENT
 * AVELSIEVE_VERSION
 * 
 * Condition
 *
 * 1    cond   // New-style mixed rule, might be anything inside here. The
 *                value is an indexed array with each one describing a condition
 *                
 * 1              example: cond => array( [0] => array(
 *                           'type' => 'address'|'envelope'|'header'|'size'|'body'|'all'
 *                       // For 'header':
 *                           'header' => ...
 *                           'matchtype' => ...
 *                           'headermatch' => ...
 *                       // For 'address':
 *                           'address' => ...
 *                           'matchtype' => ...
 *                           'addressmatch' => ...
 *                       // For 'envelope':
 *                           'matchtype' => ...
 *                           'matchtype' => ...
 *                           'envelopematch' => ...
 *                       // For 'size':
 *                           'sizerel' => ...
 *                           'sizeamount' => ...
 *                           'sizeunit' => ...
 *                       // For 'body':
 *                           'matchtype' => ...
 *                           'bodymatch' => ...
 *                       // For 'all': nothing!
 *      
 * 1											// Not implemented yet.
 * 2	header[$n]									// Header Match
 * 2	matchtype[$n]		'is' | 'contains' | 'matches' | 'lt' | 'regex' | ...
 * 2	headermatch[$n]		string
 * 2	condition		undefined | 'or' | 'and'
 * 3	sizerel			'bigger' | 'smaller'        // Size match
 * 3	sizeamount		int
 * 3	sizeunit		'kb' | 'mb'
 * 4                                                // Always
 * 10	score			int							// Spam Rule
 * 10	tests			array
 * 10	action			'trash' | 'junk' | 'discard'
 * 
 * Action
 *
 * action		1 | 2 | 3 | 4 | 5 | 6
 *
 * 1) // Keep
 *
 * 2) // Discard
 *
 * 3) // Reject w/ excuse
 *
 * excuse		string		valid only for: action==3
 *
 * 4) // Redirect
 *
 * redirectemail	string (email)	valid only for: action==4
 * keep			string (email)	valid only for: action==4 (?TBC)
 *
 * 5) // Fileinto
 *
 * folder				valid only for: action==5
 *
 * 6) // Vacation
 *
 * vac_days	int
 * vac_addresses	string
 * vac_message	string		valid only for: action==6
 *
 * 
 * -) // All
 *
 * keepdeleted	boolean
 * stop		boolean
 * notify	array
 *		'method' => string
 *		'id' => string
 *		'options' => array( [0]=> foo, [1] => bar )
 *		'priority' => low|normal|high
 *		'message' => string
 *
 *
 */ 

/**
 * Build a snippet which is used for header, address, envelope rules as well as
 * spam rule whitelists.  Takes arguments in natural English language order:
 * 'header From contains foo', or 'envelope to contains bar'.
 *
 * @param string $name Can be 'header', address', 'envelope', 'body' or empty,
 * 	leaving the caller of this function to fill in the approriate value.
 * @param string $header Header, Address or Envelope-part name.
 * @param string $matchtype Human readable, as defined in avelsieve constants.
 *     E.g. 'contains', 'is' etc.
 * @param string $headermatch The desired value.
 * @param string $mode 'verbose', 'terse' , 'tech' or 'rule'
 *   verbose = return a (verbose) textual description of the rule.
 *   terse = return a very terse description
 *   tech = similar to terse, only for people with a more technical background
 *   	(read: geeks)
 *   rule = return a string with the appropriate SIEVE code.
 *
 * @return string 
 */
function build_rule_snippet($name, $header, $matchtype, $headermatch, $mode='rule') {
	$out = $text = $terse = $tech = '';
				
	switch($name) {
		case 'header':
			if($header == 'toorcc') {
				$text .= sprintf( _("the header %s"), '<strong>&quot;To&quot; / &quot;Cc&quot; </strong>');
				$terse .= sprintf( _("Header %s") , _("To or Cc"));
				$tech .= 'header To/Cc';
			} else {
				$text .= sprintf( _("the header %s"), ' <strong>&quot;'.htmlspecialchars($header).'&quot;</strong>');
				$terse .= sprintf( _("Header %s"), htmlspecialchars($header));
				$tech .= sprintf( 'header %s', htmlspecialchars($header));
			}
			// $escapeslashes = false;
			break;

		case 'envelope':
			$text .= sprintf( _("the envelope %s") , '<strong>&quot;'.htmlspecialchars($header).'&quot;</strong>');
			$terse .= sprintf( _("Envelope %s"), htmlspecialchars($header));
			$tech .= 'envelope '.htmlspecialchars($header).' ';
			break;

		case 'address':
			if($header == 'toorcc') {
				$text .= sprintf( _("the address %s") , '<strong>&quot;To&quot; / &quot;Cc&quot; </strong>');
				$terse .= sprintf( _("Address %s"), _("To or Cc"));
				$tech .= 'address To/Cc';
			} else {
				$text .= sprintf( _("the address %s") , '<strong>&quot;'.htmlspecialchars($header).'&quot;</strong>');
				$terse .= sprintf( _("Address %s"), htmlspecialchars($header));
				$tech .= 'address '.htmlspecialchars($header).' ';
			}
			break;
		
		case 'body':
			$text .= _("message body");
			$terse .= ("Body");
			$tech .= 'body';
			break;
	}
	$text .= ' ';
	$terse .= ' ';
	$tech .= ' ';

 	switch ($matchtype) {
 			case 'is':
 				$out .= sprintf('%s :is', $name);
				$text .= _("is");
				$terse .= _("is");
				$tech .= "=";
 				break 1;
 			case 'is not':
 				$out .= sprintf("not %s :is", $name);
				$text .= _("is not");
				$terse .= _("is not");
				$tech .= "!=";
 				break 1;
 			case "contains":
 				$out .= sprintf("%s :contains", $name);
				$text .= _("contains");
				$terse .= _("contains");
				$tech .= "=";
 				break 1;
 			case "does not contain":
 				$out .= sprintf("not %s :contains", $name);
				$text .= _("does not contain");
				$terse .= _("does not contain");
				$tech .= "!~=";
 				break 1;
 			case "matches":
 				$out .= sprintf("%s :matches", $name);
				$text .= _("matches");
				$terse .= _("matches");
				$tech .= "M=";
				$escapeslashes = true;
 				break 1;
 			case "does not match":
 				$out .= sprintf("not %s :matches", $name);
				$text .= _("does not match");
				$terse .= _("does not match");
				$tech .= '!M=';
				$escapeslashes = true;
 				break 1;
 			case "gt":
				$out .= sprintf('%s :value "gt" :comparator "i;ascii-numeric"', $name);
				$text .= _("is greater than");
				$terse .= '>';
				$tech .= '>';
 				break 1;
 			case "ge":
				$out .= sprintf('%s :value "ge" :comparator "i;ascii-numeric"', $name);
				$text .= _("is greater or equal to");
				$terse .= '>=';
				$tech .= ">=";
 				break 1;
 			case "lt":
				$out .= sprintf('%s :value "lt" :comparator "i;ascii-numeric"', $name);
				$text .= _("is lower than");
				$terse .= '<';
				$tech .= '<';
 				break 1;
 			case "le":
				$out .= sprintf('%s :value "le" :comparator "i;ascii-numeric"', $name);
				$text .= _("is lower or equal to");
				$terse .= '<=';
				$tech .= '<=';
 				break 1;
 			case "eq":
				$out .= sprintf('%s :value "eq" :comparator "i;ascii-numeric"', $name);
				$text .= _("is equal to");
				$terse .= '=';
				$tech .= '==';
 				break 1;
 			case "ne":
				$out .= sprintf('%s :value "ne" :comparator "i;ascii-numeric"', $name);
				$text .= _("is not equal to");
				$terse .= '!=';
				$tech .= '!=';
 				break 1;
 			case 'regex':
 				$out .= sprintf('%s :regex :comparator "i;ascii-casemap"', $name);
				$text .= _("matches the regural expression");
				$terse .= _("matches the regural expression");
				$tech .= 'R=';
				$escapeslashes = true;
 				break 1;
 			case 'not regex':
 				$out .= sprintf('not %s :regex :comparator "i;ascii-casemap"', $name);
				$text .= _("does not match the regural expression");
				$terse .= _("does not match the regural expression");
				$tech .= '!R=';
				$escapeslashes = true;
 				break 1;
 			case 'exists':
 				$out .= "exists";
				$text .= _("exists");
				$terse .= _("exists");
				$tech .= "E";
 				break 1;
 			case 'not exists':
 				$out .= "not exists";
				$text .= _("does not exist");
				$terse .= _("does not exist");
				$tech .= '!E';
 				break 1;
 			default:
 				break 1;
	}

	if($header == 'toorcc') {
		$out .= ' ["to", "cc"]';
	} elseif($header) {
		$out .= ' "' . $header . '"';
	}

	/* Escape slashes and double quotes */
	$out .= " \"". avelsieve_addslashes($headermatch) . "\"";
	$text .= " &quot;". htmlspecialchars($headermatch) . "&quot;";
	$terse .= ' '.htmlspecialchars($headermatch). ' ';
	$tech .= ' '.htmlspecialchars($headermatch). ' ';

	switch($mode) {
		case 'terse':
			return $terse;
		case 'text':
		case 'verbose':
			return $text;
		case 'tech':
			return $tech;
		default:
			return $out;
	}
}


/** 
 * Gets a $rule array and builds a part of a SIEVE script (aka a rule).
 *
 * @param $rule	A rule array.
 * @param $mode	What to return. Can be one of:
 *   verbose = return a (verbose) textual description of the rule.
 *   terse = return a very terse description
 *   tech = similar to terse, only for people with a more technical background
 *   	(read: geeks)
 *   rule = return a string with the appropriate SIEVE code. (Default)
 *   source = return a string with the appropriate SIEVE code in format for
 *   	display to the user.
 * @return string
 */
function makesinglerule($rule, $mode='rule') {
	if($mode == 'debug') {
		include_once(SM_PATH . 'plugins/avelsieve/include/dumpr.php');
		return dumpr($rule, true);
	}
	global $maxitems, $color, $inconsistent_folders;
	$out = $text = $terse = $tech = '';

	/* Step zero: serialize & encode the rule inside the SIEVE script. Also
	 * check if it is disabled. */
	
	$coded = urlencode(base64_encode(serialize($rule)));
	if($mode != 'source') {
		$out = "#START_SIEVE_RULE".$coded."END_SIEVE_RULE\n";
	}

	/* Check for a disabled rule. */
	if (isset($rule['disabled']) && $rule['disabled']==1) {
		if ($mode=='rule') {
			/* For disabled rules, we only need the sieve comment. */
			return $out;
		} else {
			$text .= _("This rule is currently <strong>DISABLED</strong>:").' <span style="font-size: 0.9em; color:'.$color[15].';">';
			$terse .= '<div align="center">' . _("DISABLED") . '</div>';
			$tech .= '<div align="center">' . _("DISABLED") . '</div>';
		}
	}
	
	$terse .= '<table width="100%" border="0" cellspacing="2" cellpadding="2"';
	$tech .= '<table width="100%" border="0" cellspacing="2" cellpadding="2"';
	if (isset($rule['disabled']) && $rule['disabled']==1) {
		$terse .= ' style="font-size: 0.5em; background-color: inherit; color:'.$color[15].';"';
		$tech .= ' style="font-size: 0.5em; background-color: inherit; color:'.$color[15].';"';
	}
	$terse .= '><tr><td align="left">';
	$tech .= '><tr><td align="left">';
	
	/* Step one: make the if clause */
	/* The actual 'if' will be added by makesieverule() */
	
	if($rule['type'] == '10') {
		/* SpamRule */
	
		global $spamrule_score_default, $spamrule_score_header,
		$spamrule_tests, $spamrule_tests_header, $spamrule_action_default;
		
		$spamrule_advanced = false;
	
		if(isset($rule['advanced'])) {
			$spamrule_advanced = true;
		}
	
		if(isset($rule['score'])) {
			$sc = $rule['score'];
		} else {
			$sc = $spamrule_score_default;
		}
		
		if(isset($rule['tests'])) {
			$te = $rule['tests'];
		} else {
			$te = array_keys($spamrule_tests);
		}
	
		if(isset($rule['action'])) {
			$ac = $rule['action'];
		} else {
			$ac = $spamrule_action_default;
		}
	
		/*
		if allof( anyof(header :contains "X-Spam-Rule" "Open.Relay.Database" ,
		        	header :contains "X-Spam-Rule" "Spamhaus.Block.List" 
				),
		  	header :value "gt" :comparator "i;ascii-numeric" "80" ) {
			
			fileinto "INBOX.Junk";
			discard;
		}
			
		// Whitelist scenario:
		if allof( anyof(header :contains "X-Spam-Rule" "Open.Relay.Database" ,
		        	header :contains "X-Spam-Rule" "Spamhaus.Block.List" 
				),
		  	header :value "gt" :comparator "i;ascii-numeric" "80" ,
		  	not anyof(header :contains "From" "Important Person",
		        	header :contains "From" "Foo Person"
		  	)
			) {
			
			fileinto "INBOX.Junk";
			discard;
		}
		*/
		
		$out .= 'allof( ';
		$text .= _("All messages considered as <strong>SPAM</strong> (unsolicited commercial messages)");
		$terse .= _("SPAM");
		$tech .= 'SPAM';
		
		if(sizeof($te) > 1) {
			$out .= ' anyof( ';
			for($i=0; $i<sizeof($te); $i++ ) {
				$out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[$i].'"';
				if($i < (sizeof($te) -1 ) ) {
					$out .= ",";
				}
			}
			$out .= " ),\n";
		} else {
			$out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[0].'", ';
		}
	
		$out .= "\n";
		$out .= ' header :value "ge" :comparator "i;ascii-numeric" "'.$spamrule_score_header.'" "'.$sc.'" ';
	
		if(isset($rule['whitelist']) && sizeof($rule['whitelist']) > 0) {
			/* Insert here header-match like rules, ORed of course. */
			$text .= ' (' . _("unless") . ' ';
			$terse .= '<br/>' . _("Whitelist:") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
			$tech .= ' !(WHITELIST:<br/>';
	
			$out .= " ,\n";
			$out .= ' not anyof( ';
			for($i=0; $i<sizeof($rule['whitelist']); $i++ ) {
				$out .= build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'rule');
				$text .= build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'verbose');
				$terse .= '<li>'. build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'terse') . '</li>';
				$tech .= build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'tech') . '<br/>';
				if($i<sizeof($rule['whitelist'])-1) {
					$out .= ', ';
					$text .= ' ' . _("or") . ' ';
				}
			}
			$text .= '), '; 
			$tech .= '), '; 
			$terse .= '</ul>'; 
			$out .= " )";
		}
		$out .= " )\n{\n";

		if($spamrule_advanced == true) {
			$text .= _("matching the Spam List(s):");
			$terse .= '<br/>' . _("Spam List(s):") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
			$tech .= '<br/>' . _("Spam List(s):") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
			for($i=0; $i<sizeof($te); $i++) {
				$text .= $spamrule_tests[$te[$i]].', ';
				$terse .= '<li>' . $spamrule_tests[$te[$i]].'</li>';
				$tech .= '<li>' . $spamrule_tests[$te[$i]].'</li>';
			}
			$text .= sprintf( _("and with score greater than %s") , $sc );
			$terse .= '</ul>' . sprintf( _("Score > %s") , $sc);
			$tech .= '</ul>' . sprintf( _("Score > %s") , $sc);
		}
	
		$text .= ', ' . _("will be") . ' ';
		$terse .= '</td><td align="right">';
		$tech .= '</td><td align="right">';
	
		if($ac == 'junk') {
			$out .= 'fileinto "INBOX.Junk";';
			$text .= _("stored in the Junk Folder.");
			$terse .= _("Junk");
			$tech .= 'JUNK';
	
		} elseif($ac == 'trash') {
			$text .= _("stored in the Trash Folder.");
	
			global $data_dir, $username;
			$trash_folder = getPref($data_dir, $username, 'trash_folder');
			/* Fallback in case it does not exist. Thanks to Eduardo
		 	* Mayoral. If not even Trash does not exist, it will end up in
		 	* INBOX... */
			if($trash_folder == '' || $trash_folder == 'none') {
				$trash_folder = "Trash";
			}
			$out .= 'fileinto "'.$trash_folder.'";';

			$terse .= _("Trash");
			$tech .= 'TRASH';
	
		} elseif($ac == 'discard') {
			$out .= 'discard;';
			$text .= _("discarded.");
			$terse .= _("Discard");
			$tech .= _("Discard");
		}
	
	} else {
		$text .= "<strong>"._("If")."</strong> ";
	} 
	
	if($rule['type'] == "1") {
		/* New-style 'cond' array for conditions of different types. */
		/* Condition ('and' / 'or') */
		if(sizeof($rule['cond']) > 1) {
			switch ($rule['condition']) {
				case "or":
					$out .= "anyof (";
					$text .= _("<em>any</em> of the following mail headers match: ");
					// $terse .= "ANY (";
					break;
				default: 
				case "and":
					$out .= "allof (";
					$text .= _("<em>all</em> of the following mail headers match: ");
					// $terse .= "ALL (";
					break;
			}
		} else {
			$lonely = true;
		}

		/* Indexed array $rule['cond'] contains a bunch of rule definitions */
		for($i=0;$i<sizeof($rule['cond']);$i++) {
			switch($rule['cond'][$i]['type']) {
			case 'address':
				$out .= build_rule_snippet('address', $rule['cond'][$i]['address'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['addressmatch'],'rule');
				$text .= build_rule_snippet('address', $rule['cond'][$i]['address'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['addressmatch'],'verbose');
				$terse .= build_rule_snippet('address', $rule['cond'][$i]['address'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['addressmatch'],'terse');
				$tech .= build_rule_snippet('address', $rule['cond'][$i]['address'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['addressmatch'],'tech');
				break;

			case 'envelope':
				$out .= build_rule_snippet('envelope', $rule['cond'][$i]['envelope'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['envelopematch'],'rule');
				$text .= build_rule_snippet('envelope', $rule['cond'][$i]['envelope'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['envelopematch'],'verbose');
				$terse .= build_rule_snippet('envelope', $rule['cond'][$i]['envelope'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['envelopematch'],'terse');
				$tech .= build_rule_snippet('envelope', $rule['cond'][$i]['envelope'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['envelopematch'],'tech');
				break;

			case 'header':
				$out .= build_rule_snippet('header', $rule['cond'][$i]['header'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['headermatch'],'rule');
				$text .= build_rule_snippet('header', $rule['cond'][$i]['header'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['headermatch'],'verbose');
				$terse .= build_rule_snippet('header', $rule['cond'][$i]['header'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['headermatch'],'terse');
				$tech .= build_rule_snippet('header', $rule['cond'][$i]['header'], $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['headermatch'],'tech');
		
				break;

			case 'size':
				$out .= 'size :';
				$text .= _("the size of the message is");
				$text .= "<em>";
				$terse .= _("Size");
				$tech .= _("Size");
				
				if($rule['cond'][$i]['sizerel'] == "bigger") {
					$out .= "over ";
					$terse .= " > ";
					$tech .= " > ";
					$text .= _(" bigger");
				} else {
					$out .= "under ";
					$terse .= " < ";
					$tech .= " < ";
					$text .= _(" smaller");
				}
				$text .= " "._("than")." ". htmlspecialchars($rule['cond'][$i]['sizeamount']) .
					" ". htmlspecialchars($rule['cond'][$i]['sizeunit']) . "</em>, ";
				$terse .= $rule['cond'][$i]['sizeamount'];
				$tech .= $rule['cond'][$i]['sizeamount'];
				$out .= $rule['cond'][$i]['sizeamount'];
				
				if($rule['cond'][$i]['sizeunit']=="kb") {
					$out .= "K\n";
					$terse .= "K\n";
					$tech .= "K\n";
				} elseif($rule['cond'][$i]['sizeunit']=="mb") {
					$out .= "M\n";
					$terse .= "M\n";
					$tech .= "M\n";
				}
				break;
 		
			case 'body':
				$out .= build_rule_snippet('body', '', $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['bodymatch'],'rule');
				$text .= build_rule_snippet('body', '', $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['bodymatch'],'verbose');
				$terse .= build_rule_snippet('body', '', $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['bodymatch'],'terse');
				$tech .= build_rule_snippet('body', '', $rule['cond'][$i]['matchtype'],
					$rule['cond'][$i]['bodymatch'],'tech');
				break;

			case 'all':
				$out .= 'true';
				$text .= _("For <strong>ALL</strong> incoming messages; ");
				$terse .= _("ALL");
				$tech .= '<strong>*</strong>';
				break;
			}
				
			if(isset($rule['cond'][$i+1])) {
				/* TODO :Probably add more extensive check for validity of
				 * the next array? In case it is empty? */

				$out .= ",\n";
				$text .= ", ";
	
				if ($rule['condition'] == 'or' ) {
					$terse .= ' ' . _("or") . '<br/>';
					$tech .= ' ' . _("or") . '<br/>';
				} elseif ($rule['condition'] == 'and' ) {
					$terse .= ' ' . _("and") . '<br/>';
					$tech .= ' ' . _("and") . '<br/>';
				}
			} elseif($i == 0  && !isset($rule['cond'][1]['headermatch']) ) {
				$out .= "\n";
				$text .= ", ";
			} else {
				$out .= ")\n";
				$text .= ", ";
			}
		}
	
	} elseif($rule['type'] == '4') {/* always */
		$out .= "true {\n";
	} elseif($rule['type'] != 10) {
		/* Other type, probably handled by another plugin. */
		do_hook_function('avelsieve_buildrule_condition', $args = array($rule, $out, $text, $terse, $tech));
	}
	
	/* step two: make the then clause */
	
	if( $rule['type'] != '4' && $rule['type']!=10 ) {
		$out .= "{\n";
		$terse .= '</td><td align="right">';
		$tech .= '</td><td align="right">';
		$text .= "<strong>";
		$text .= _("then");
		$text .= "</strong> ";
	}
	
	if(isset($rule['keep'])) {
		$out .= "keep;\n";
	}
	
	/* Fallback to default action */
	if(!isset($rule['action'])) {
		$rule['action'] = 1;
	}
	
	switch ($rule['action']) {
	case '1':	/* keep (default) */
	default:
		$out .= "keep;";
		$text .= _("<em>keep</em> it.");
		$terse .= _("Keep");
		$tech .= 'KEEP';
		break;
	
	case '2':	/* discard */
		$out .= "discard;";
		$text .= _("<em>discard</em> it.");
		$terse .= _("Discard");
		$tech .= 'DISCARD';
		break;
	
	case '3':	/* reject w/ excuse */
		$out .= "reject text:\n".$rule['excuse']."\r\n.\r\n;";
		$text .= _("<em>reject</em> it, sending this excuse back to the sender:")." \"".htmlspecialchars($rule['excuse'])."\".";
		$terse .= _("Reject");
		$tech .= "REJECT";
		break;
	
	case '4':	/* redirect to address */
		if(strstr(trim($rule['redirectemail']), ' ')) {
			$redirectemails = explode(' ', trim($rule['redirectemail']));
		}
		if(!isset($redirectemails)) {
			if(strstr(trim($rule['redirectemail']), ',')) {
				$redirectemails = explode(',', trim($rule['redirectemail']));
			}
		}
		if(isset($redirectemails)) {
			foreach($redirectemails as $redirectemail) {
				$out .= 'redirect "'.$redirectemail."\";\n";
				$terse .= _("Redirect to").' '.htmlspecialchars($redirectemail). '<br/>';
				$tech .= 'REDIRECT '.htmlspecialchars($redirectemail). '<br/>';
			}
			$text .= sprintf( _("<em>redirect</em> it to the email addresses: %s."), implode(', ',$redirectemails));
		} else {
			$out .= "redirect \"".$rule['redirectemail']."\";";
			$text .= _("<em>redirect</em> it to the email address")." ".htmlspecialchars($rule['redirectemail']).".";
			$terse .= _("Redirect to") . ' ' .htmlspecialchars($rule['redirectemail']);
			$tech .= 'REDIRECT' . ' ' .htmlspecialchars($rule['redirectemail']);
		}
		break;
	
	case '5':	/* fileinto folder */
		$out .= 'fileinto "'.$rule['folder'].'";';

		if(!empty($inconsistent_folders) && in_array($rule['folder'], $inconsistent_folders)) {
			$clr = '<span style="color:'.$color[2].'">';
			$text .= $clr;
			$terse .= $clr;
			$tech .= $clr;
		}
		$text .= sprintf( _("<em>file</em> it into the folder %s"),
			' <strong>' . htmlspecialchars(imap_utf7_decode_local($rule['folder'])) . '</strong>');
		$terse .= sprintf( _("File into %s"), htmlspecialchars(imap_utf7_decode_local($rule['folder'])));
		$tech .= "FILEINTO ".htmlspecialchars(imap_utf7_decode_local($rule['folder']));
		
		if(!empty($inconsistent_folders) && in_array($rule['folder'], $inconsistent_folders)) {
			$cls = '<em>' . _("(Warning: Folder not available)") . '</em></span>';
			$text .= ' '.$cls;
			$terse .= '<br/>'.$cls;
			$tech .= '<br/>'.$cls;
		}
		$text .= '. ';
		break;
	
	case '6':      /* vacation message */
 		$out .= 'vacation :days '.$rule['vac_days'];
		
		/* If vacation address does not exist, do not set the :addresses
	 	* argument. */
	
 		if(isset($rule['vac_addresses']) && trim($rule['vac_addresses'])!="") {
			$addresses = str_replace(",",'","',str_replace(" ","",$rule['vac_addresses']));
 			$out .= ' :addresses ["'.$addresses.'"]';
		}
	
		/* FIXME Replace single dot with dot-stuffed line. RFC 3028 2.4.2 */ 
  		$out .= " text:\n".$rule['vac_message']."\r\n.\r\n;";
 		$text .= _("reply with this vacation message: ") . htmlspecialchars($rule['vac_message']);
		$terse .= _("Vacation Message");
		$tech .= 'VACATION';
 		break;
	
	default:
		do_hook_function('avelsieve_buildrule_action', $args = array($rule, $out, $text, $terse, $tech));
		break;
	}
	
	if(isset($rule['keep'])) {
		$text .= ' ' . _("Also keep a local copy.");
		$terse .= '<br/>' . _("Keep");
		$tech .= '<br/>KEEP';
	}
	
	if (isset($rule['keepdeleted'])) {
		$text .= _(" Also keep a copy in INBOX, marked as deleted.");
		$out .= "\naddflag \"\\\\\\\\\\\\\\\\Deleted\";\nkeep;";
		$terse .= '<br />' . _("Keep Deleted");
		$tech .= '<br />KEEP DELETED';
	}
	
	/* Notify extension */
	
	if (array_key_exists("notify", $rule) && is_array($rule['notify']) && ($rule['notify']['method'] != '')) {
		global $notifystrings, $prioritystrings;
		$text .= _(" Also notify using the method")
			. " <em>" . htmlspecialchars($notifystrings[$rule['notify']['method']]) . "</em>, ".
			_("with")
			. " " . htmlspecialchars($prioritystrings[$rule['notify']['priority']]) . " " .
			_("priority and the message")
			. " <em>&quot;" . htmlspecialchars($rule['notify']['message']) . "&quot;</em>.";
			
		$out .= "\nnotify :method \"".$rule['notify']['method']."\" ";
		$out .= ":options \"".$rule['notify']['options']."\" ";
	
		if(isset($rule['notify']['id'])) {
			$out .= ":id \"".$rule['notify']['id']."\" ";
		}
		if(isset($rule['notify']['priority']) && array_key_exists($rule['notify']['priority'], $prioritystrings)) {
			$out .= ":".$rule['notify']['priority'] . " ";
		}
		$out .= ':message "'.$rule['notify']['message']."\";\n";
		/* FIXME - perhaps allow text: multiline form in notification string? */
		$terse .= '<br/>' . sprintf( _("Notify %s"), $rule['notify']['options']);
		$tech .= '<br/>' . sprintf('NOTIFY %s', $rule['notify']['options']);
	}
	
	
	/* Stop processing other rules */
	
	if (isset($rule['stop'])) {
		$text .= ' ' . _("Then <strong>STOP</strong> processing rules.");
		$out .= "\nstop;";
		$terse .= '<br/>' . _("Stop");
		$tech .= '<br/>STOP';
	}
	
	$out .= "\n}";
	$terse .= "</td></tr></table>";
	$tech .= "</td></tr></table>";
	
	if (isset($rule['disabled']) && $rule['disabled']==1) {
		$text .= '</span>';
	}
	
	switch($mode) {
		case 'terse':
			return $terse;
		case 'text':
		case 'verbose':
			return $text;
		case 'tech':
			return $tech;
		case 'source':
			return str_replace("\n", '<br/>', $out);
		default:
			return $out;
	}
}	
	
	
/**
 * Make a complete set of rules, that is, a SIEVE script.
 *
 * @param $rulearray An array of associative arrays, each one describing a
 * rule.
 * @return $string
 */
function makesieverule ($rulearray) {
    global $implemented_capabilities, $cap_dependencies, $sieve_capabilities,
        $avelsieve_version, $creation_date, $scriptinfo,
        $avelsieve_custom_sieve_implementation;

	if ( (sizeof($rulearray) == 0) || $rulearray[0] == "0" ) {
		return false;
	}

	/* Encoded avelsieve version information */
	$versionencoded = base64_encode(serialize($avelsieve_version));

    if($avelsieve_custom_sieve_implementation == 'exim') {
        $out = "# Sieve filter\n";
    } elseif($avelsieve_custom_sieve_implementation == 'mfl') {
        $out = "sieve {\n";
    } else {
        $out = '';
    }
	
	$out .= "# This script has been automatically generated by avelsieve\n".
	    "# (Sieve Mail Filters Plugin for Squirrelmail)\n".
	    "# Warning: If you edit this manually, then the changes will not \n".
	    "# be reflected in the users' front-end!\n";
    
	$out .= "#AVELSIEVE_VERSION" . $versionencoded . "\n";

	$modification_date = time();

	if(isset($scriptinfo['created'])) {
		$out .= "#AVELSIEVE_CREATED" . $scriptinfo['created'] . "\n";

	} else { /* New script */
		$creation_date = $modification_date;
		$out .= "#AVELSIEVE_CREATED" . $creation_date . "\n";

	}

	$out .= "#AVELSIEVE_MODIFIED" . $modification_date . "\n";
	// $out .= "#AVELSIEVE_COMMENT" . $script_comment . "\n"

	/* Require all capablities that avelsieve supports AND the server supports. */
	foreach($implemented_capabilities as $no=>$cap) {
		if(array_key_exists($cap, $sieve_capabilities)) {
			$torequire[] = $cap;
			if(array_key_exists($cap, $cap_dependencies)) {
				foreach($cap_dependencies[$cap] as $no2=>$dep) {
					$torequire[] = $dep;
				}
			}
		}
	}
		
 	$out .= 'require ["'. implode('","', $torequire) . "\"];\n";

	/* The actual rules */
	for ($i=0; $i<sizeof($rulearray); $i++) {
		if (!isset($rulearray[$i]['disabled']) || $rulearray[$i]['disabled'] != 1) {
			switch ($i) {
				case 0:		$out .= "if\n";		break;
				default:	$out .= "\nif\n";	break;
			}		
		} else {
			$out .= "\n";
		}
		$out .= makesinglerule($rulearray[$i],'rule');
	}

    /* It seems that if there are some rules and all of them are disabled, or
     * if there are no rules, then it fails to upload the script (i.e.
     * timsieved reports a parse error at the last line). By entering the
     * ...implicit keep explicitly, it seems to work: */
    $practically_no_rules = true;
    if(sizeof($rulearray) > 0) {
        for($i=0;$i<sizeof($rulearray); $i++) {
            if(isset($rulearray[$i]['disabled']) && $rulearray[$i]['disabled']) {
                // Rule disabled
            } else {
                $practically_no_rules = false;
                break; // No need to check anything further.
            }
        }
    }
	if($practically_no_rules) {
        $out .= "\nkeep;";
    }
    
    if($avelsieve_custom_sieve_implementation == 'mfl') {
        $out .= "}";
    }
	return DO_Sieve::encode_script($out);
}

?>
