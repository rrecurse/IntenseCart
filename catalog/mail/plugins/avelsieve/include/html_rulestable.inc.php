<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * HTML Functions
 *
 * @version $Id: html_rulestable.inc.php,v 1.15 2006-06-26 09:33:24 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'plugins/avelsieve/include/html_main.inc.php');

/**
 * HTML Output functions for rule editing / adding
 */
class avelsieve_html_rules extends avelsieve_html {
	/**
	 * @param array SIEVE Rules that are to be printed.
	 */
	var $rules = array();
	
	/**
	 * @param string Display mode: 'verbose','terse','tech','source' or 'debug'
	 */
	var $mode = 'terse';

	/**
	 * Constructor function, that initializes some variables from possible
	 * template engines.
	 * @return void
	 */
	function avelsieve_html_rules(&$rules, $mode = 'terse') {
		$this->avelsieve_html();
		$this->rules = $rules;
		$this->mode = $mode;
	}

	/**
	 * Create new rules blurb, when none exists.
	 */
	function rules_create_new() {
		return ' <p>'.
			_("Here you can add or delete filtering rules for your email account. These filters will always apply to your incoming mail, wherever you check your email.").
			'</p>' .
			'<p>' . _("You don't have any rules yet. Feel free to add any with the button &quot;Add a New Rule&quot;. When you are done, please select &quot;Save Changes&quot; to get back to the main options screen.") . "</p>";
	
	}
	
	/**
	 * Introductory text
	 */
	function rules_blurb() {
		global $color, $conservative, $displaymodes, $scriptinfo;
		
		$out = " <p>"._("Here you can add or delete filtering rules for your email account. These filters will always apply to your incoming mail, wherever you check your email.")."</p> ";
		
		if($conservative) {
			$out .= "<p>"._("When you are done with editing, <strong>remember to select &quot;Save Changes&quot;</strong> to activate your changes!")."</p>";
		}
	
		$out .= $this->rules_confirmation_text();
	
		if(isset($scriptinfo['created'])) {
			$out .= $this->scriptinfo($scriptinfo);
		}
		
		global $inconsistent_folders;
		if(!empty($inconsistent_folders)) {
			$out .= '<p style="color:'.$color[2].'">' . _("Warning: In your rules, you have defined an action that refers to a folder that does not exist or a folder where you do not have permission to append to.") . '</p>';
		}
		
		$out .= "<p>"._("The following table summarizes your current mail filtering rules.")."</p>";
		
		/* NEW*/
		$out .= '
		<table cellpadding="3" cellspacing="2" border="0" align="center" width="97%" frame="box">
		<tr bgcolor="'.$color[0].'">
		<td style="white-space:nowrap" valign="middle">';
		
		$out .= _("No") . '</td><td></td>'.
			'<td>'. _("Description of Rule").
			' <small>(' . _("Display as:");
		
		
		foreach($displaymodes as $id=>$info) {
			if($this->mode == $id) {
				$out .= ' <strong><span title="'.$info[1].'">'.$info[0].'</span></strong>';
			} else {
				$out .= ' <a href="'.$_SERVER['SCRIPT_NAME'].'?mode='.$id.'" title="'.$info[1].'">'.$info[0].'</a>';
			}
		}
		$out .= ')</small>';
		
		$out .= ' </td><td valign="middle">'._("Options")."</td></tr>\n";
		return $out;
	}
		
	/**
	 * Returns the 'communication' aka 'comm' string from the previous screen,
	 * for instance edit.php or addspamrule.php.
	 * @return string
	 */
	function rules_confirmation_text() {
		global $color;
		$out = '';
		if(isset($_SESSION['comm'])) {
			$out .= '<p><font color="'.$color[2].'" align="center">';
		
			if(isset($_SESSION['comm']['new'])) {
				$out .= _("Successfully added new rule.");
		
			} elseif (isset($_SESSION['comm']['edited'])) {
				$out .= _("Successfully updated rule #");
				$out .= $_SESSION['comm']['edited']+1;
		
			} elseif (isset($_SESSION['comm']['deleted'])) {
				if(is_array($_SESSION['comm']['deleted'])) {
					$out .= _("Successfully deleted rules #");
					for ($i=0; $i<sizeof($_SESSION['comm']['deleted']); $i++ ) {
						$out .= $_SESSION['comm']['deleted'][$i] +1;
						if($i != (sizeof($_SESSION['comm']['deleted']) -1) ) {
							$out .= ", ";
						}
					}
				} else {
					$out .= _("Successfully deleted rule #");
					$out .= $_SESSION['comm']['deleted']+1;
				}
			}
		
			$out .= '</font></p>';
			session_unregister('comm');
		}
		return $out;
	}
	
	
	function rules_table_footer() {
		return '</table>';
	}
	
	/**
	 * Submit Buttons for adding new rules
	 */
	function button_addnewrule() {
		global $spamrule_enable;
		$out = '<input name="addrule" value="' . _("Add a New Rule") . '" type="submit" />';
		if($spamrule_enable == true) {
			$out .= '<br/><input name="addspamrule" value="' . _("Add SPAM Rule") . '" type="submit" />';
		}
		$out .= concat_hook_function('avelsieve_rulestable_buttons', NULL);
		return $out;
	}
	
	/**
	 * Submit button for deleting selected rules
	 * @return string
	 */
	function button_deleteselected() {
		return '<input type="submit" name="deleteselected" value="' . _("Delete") . '" />';
	}

	/**
	 * Submit button for enabling selected rules
	 * @return string
	 */
	function button_enableselected() {
		return '<input type="submit" name="enableselected" value="' . _("Enable") . '" />';
	}

	/**
	 * Submit button for disabling selected rules
	 * @return string
	 */
	function button_disableselected() {
		return '<input type="submit" name="disableselected" value="' . _("Disable") . '" />';
	}
	
	
	function rules_footer() {
		global $conservative;
		$out = '';
		if($conservative) {
            $out = '<div style="text-align: center;"><p>'.
			    _("When you are done, please click the button below to return to your webmail.").
                '</p><input name="logout" value="'._("Save Changes").'" type="submit" /></div>';
		}
		return $out;
	}
	
	/**
	 * Output link for corresponding rule function (such as edit, delete, move).
	 *
	 * @param string $name
	 * @param int $i
	 * @param string $url Which page to link to
	 * @param string $xtra Extra stuff to be passed to URL
	 */
	function toolicon ($name, $i, $url = "table.php", $xtra = "", $attribs=array()) {
		global $useimages, $imagetheme, $location, $avelsievetools;
	
		$desc = $avelsievetools[$name]['desc'];
		$img = $avelsievetools[$name]['img'];

		$out = '';
	
		if(empty($xtra)) {
			$out .= ' <a href="'.$url.'?rule='.$i.'&amp;'.$name.'='.$i.'"';
		} else {
			$out .= ' <a href="'.$url.'?rule='.$i.'&amp;'.$name.'='.$i.'&amp;'.$xtra.'"';
		}
	
		if(sizeof($attribs) > 0) {
			foreach($attribs as $key=>$val) {
				$out .= ' '.$key.'="'.$val.'"';
			}
		}
		$out .= '>';
	
		if($useimages) {
			$out .= '<img title="'.$desc.'" src="'.$location.'/images/'.$imagetheme.
			'/'.$img.'" alt="'.$desc.'" border="0" />';
		} else {
			$out .= " | ". $desc;
		}
		$out .= '</a>';
		return $out;
	}
	
	/**
	 * Output script information (last modification date etc.)
	 * @param array $scriptinfo
	 * @return string
	 */
	function scriptinfo($scriptinfo) {
		if(function_exists('getLongDateString')) {
			bindtextdomain('squirrelmail', SM_PATH . 'locale');
			textdomain('squirrelmail');
			$cr = getLongDateString($scriptinfo['created']);
			$mo = getLongDateString($scriptinfo['modified']);
			bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
			textdomain ('avelsieve');
			
			$out = '<p><em>'. _("Last modified:").'</em> <strong>'.$mo.'</strong></p>';
		
			/*
			$out = '<p><em>'._("Created:").'</em> '.$cr.'.<br /><em>'.
			_("Last modified:").'</em> <strong>'.$mo.'</strong></p>';
			*/
		
		} else {
			/* Pretty useless information to be displayed every time */
			$dummy = _("Created:");
			/*
			$out = '<p><em>'._("Created:").'</em> '.
			date("Y-m-d H:i:s",$scriptinfo['created']).'. <em>'.
			*/
			$out = _("Last modified:").'</em> <strong>'.
			date("Y-m-d H:i:s",$scriptinfo['modified']).'</strong></p>';
		}
	
		if(AVELSIEVE_DEBUG == 1) {
			global $avelsieve_version;
			$out .= '<p>Versioning Information:</p>' .
				'<ul><li>Script Created using Version: '.$scriptinfo['version']['string'].'</li>'.
				'<li>Installed Avelsieve Version: '.$avelsieve_version['string'] .'</li></ul>';
		}
		return $out;
	}

	/**
	 *
	 */
	function rules_confirmation() {
		global $color;
		$out = $this->table_header( _("Current Mail Filtering Rules") ).
			$this->all_sections_start().
			$this->rules_confirmation_text().
			$this->all_sections_end().
			' <br/><input type="button" name="Close" onClick="window.close(); return false;" value="'._("Close").'" />';
			$this->table_footer();
		return $out;
	}

	/**
	 * Main function to output a whole table of SIEVE rules.
	 * @return string
	 */
	function rules_table() {
		global $color;
		
        $out = '<form name="rulestable" method="POST" action="table.php">';

		if(empty($this->rules)) {
			$out .= $this->table_header(_("No Filtering Rules Defined Yet")).
				$this->all_sections_start().
		        '<tr><td bgcolor="'.$color[4].'" align="center">'.
				$this->rules_create_new().
				$this->button_addnewrule().
				$this->rules_footer().
                '</td></tr>'.
				$this->all_sections_end() .
				$this->table_footer().
                '</form>';
            return $out;
		}

		$out .= // $this->all_sections_start().
            $this->table_header( _("Current Mail Filtering Rules") ).
		    // '<tr><td bgcolor="'.$color[4].'" align="center">'.
			$this->rules_blurb();
            // '</td></tr>';

		$toggle = false;
		for ($i=0; $i<sizeof($this->rules); $i++) {
			$out .="\n<tr";
			if ($toggle) {
				$out .=' bgcolor="'.$color[12].'"';
			}
			$out .= "><td>".($i+1)."</td><td>".
				'<input type="checkbox" name="selectedrules[]" value="'.$i.'" /></td><td>';
			$out .= makesinglerule($this->rules[$i], $this->mode);
			$out .= '</td><td style="white-space: nowrap"><p>';
		
			/* $out .='</td><td><input type="checkbox" name="rm'.$i.'" value="1" /></td></tr>'; */
			
			/* Edit */
			if($this->rules[$i]['type'] == 10) {
				$out .= $this->toolicon("edit", $i, "addspamrule.php", "");
			} elseif($this->rules[$i]['type'] < 10) {
				$out .= $this->toolicon("edit", $i, "edit.php", "");
			} else {
				$args = do_hook_function('avelsieve_edit_link');
				$out .= $this->toolicon("edit", $i, $args[0], $args[1]);
				unset($args);
			}
			
			/* Duplicate */
			if($this->rules[$i]['type'] == 10) {
				$out .= $this->toolicon("dup", $i, "addspamrule.php", "edit=$i&amp;dup=1");
			} elseif($this->rules[$i]['type'] < 10) {
				$out .= $this->toolicon("dup", $i, "edit.php", "edit=$i&amp;dup=1");
			} else {
				$args = do_hook_function('avelsieve_edit_link'); 
				$out .= $this->toolicon('dup', $i, $args[0], $args[1]);
				unset($args);
			}
		
			/* Delete */
			$out .= $this->toolicon("rm", $i, "table.php", "",
				array('onclick'=>'return confirm(\''._("Really delete this rule?").'\')'));
		
			/* Move up / Move to Top */
			if ($i != 0) {
				if($i != 1) {
					$out .=$this->toolicon("mvtop", $i, "table.php", "");
				}
				$out .=$this->toolicon("mvup", $i, "table.php", "");
			}
		
			/* Move down / to bottom */
			if ($i != sizeof($this->rules)-1 ) {
				$out .=$this->toolicon("mvdn", $i, "table.php", "");
				if ($i != sizeof($this->rules)-2 ) {
					$out .=$this->toolicon("mvbottom", $i, "table.php", "");
				}
			}
		
			$out .= "</p></td></tr>\n";
		
			if(!$toggle) {
				$toggle = true;
			} elseif($toggle) {
				$toggle = false;
			}
		}
		
		$out .='<tr><td colspan="4">'.
			'<table width="100%" border="0"><tr><td align="left">'.
			_("Action for Selected Rules:") . '<br/>' .
			$this->button_enableselected(). 
			$this->button_disableselected(). '<br/>' .
			$this->button_deleteselected(). '<br/>' .
			'</td><td align="right">'.
			$this->button_addnewrule().
			'</td></tr></table>'. 
			'</td></tr>'.
			$this->rules_footer().
			$this->all_sections_end() .
			$this->table_footer().
            '</form>';

		return $out;
	}
}
	
?>
