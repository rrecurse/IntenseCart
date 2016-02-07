<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * This file contains functions that spit out HTML, mostly intended for use by
 * addrule.php and edit.php.
 *
 * @version $Id: html_ruleedit.inc.php,v 1.26 2006-06-30 12:56:10 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004-2005 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'plugins/avelsieve/include/html_main.inc.php');

/**
 * HTML Output functions for rule editing / adding
 */
class avelsieve_html_edit extends avelsieve_html {
	/**
	 * @var boolean Enable spamrule building?
	 */
	var $spamrule_enable = false;

	/**
	 * @var mixed Is the window a pop-up window (called from elsewhere)?
	 */
	var $popup = false;

	/**
	 * @var string Mode of operation, for editing new rule. One of:
	 * 'wizard', 'addnew', 'edit', 'duplicate'
	 */
	var $mode;
	
	/**
	 * Constructor function. Takes as an optional argument a reference to a
	 * rule array which will be edited.
	 *
     * @param string $s Our Sieve Handler (Data Object). This is needed in
     *   order to have certain checks for capabilities of the specific backend.
	 * @param string $mode
	 * @param array $rule
	 * @param boolean $popup
	 * @param mixed $errmsg Array or string of error messages to display.
	 * @return void
	 */
	function avelsieve_html_edit(&$s, $mode = 'edit', $rule = array(), $popup = false, $errmsg = '') {
		$this->avelsieve_html();

		$this->rule = $rule;
		if(!isset($this->rule['type'])) {
			$this->rule['type'] = 0;
		}
		$this->mode = $mode;
		$this->popup = $popup;
		$this->errmsg = $errmsg;
        $this->s = $s;
		
		$this->active_types = $this->get_active_types();
	}
	
	/**
	 * @return array of types valid for the current capabilities.
	 */
	function get_active_types() {
		global $types;

		$active_types = array();
		foreach($types as $i=>$tp) {
			/* Skip disabled or not-supported */
			if(isset($tp['disabled'])) {
				continue;
			}
			if(array_key_exists('dependencies', $tp)) {
				foreach($tp['dependencies'] as $no=>$dep) {
					if(!$this->s->capability_exists($dep)) {
						continue 2;
					}
				}
			}
			$active_types[$tp['order']] = $i;
		}
		ksort($active_types);
		return $active_types;
	}

	/**
	 * Output rule type select widget.
	 *
	 * @param string
	 */
	function select_type($name, $selected) {
		global $types;

		/*
			$dummy = '<p align="center">' . _("Rule Type") . ': '.
			$dum = '<p>'._("What kind of rule would you like to add?"). '</p>';

		if($this->rule['type'] == 0 && $select == 'select') {
			$dum = '<option value="">'. _(" -- Please Select -- ") .'</option>';
		}
		*/
		/* FOR OLD TYPES */
		/*
		for($i=0; $i<sizeof($active_types); $i++) {
			$k = $active_types[$i];
			if($select == 'radio') {
				$out .= '<input type="radio" name="type" id="type_'.$k.'" value="'.$k.'" ';
				if($this->rule['type'] == $k) {
					$out .= 'selected="SELECTED"';
				}
				$out .= '/> '.
					'<label for="type_'.$k.'">'.$types[$k]['name'].'<br />'.
					'<blockquote>'.$types[$k]['description'].'</blockquote>'.
					'</label>';
			} elseif($select == 'select') {
				$out .= '<option value="'.$k.'" ';
				if($this->rule['type'] == $k) {
					$out .= 'selected="SELECTED"';
				}
				$out .= '>'. $types[$k]['name'] .'</option>';
			}
		}
		if($select == 'select') {
				$out .= '</select>';
		}
		if(!$this->js) {
			$out .= ' <input type="submit" name="changetype" value="'._("Change Type").'" />';
		}
		$out .= '<br/>';
		*/
		$out = '<input type="hidden" name="previous_'.$name.'" value="'.htmlspecialchars($selected).'" />'.
			'<select name="'.$name.'"';
		if($this->js) {
			$out .= ' onChange="addrule.submit();"';
		}
		$out .= '>';

		foreach($this->active_types as $no=>$type) {
			$out .= '<option value="'.$type.'"';
			if($selected == $type) {
				$out .= ' selected="SELECTED"';
			}
			$out .= '>'.$types[$type]['name'].'</option>';
		}
		$out .= '</select>';
		return $out;
	}

	/**
 	 * Listbox widget with available headers to choose from.
 	 *
 	 * @param string $selected_header Selected header
 	 * @param int $n option number
 	 */
	function header_listbox($selected_header, $n) {
		global $headers;

		$options = array('toorcc' => _("To: or Cc") );
		foreach($headers as $no=>$h){
			$options[$h] = $h;
		}

		$out = $this->generic_listbox('cond['.$n.'][header]', $options, $selected_header);
		return $out;
	}
	
	/**
 	 * Listbox widget with available address headers to choose from.
 	 *
 	 * @param string $selected_header Selected header
 	 * @param int $n option number
 	 */
	function address_listbox($selected_header, $n) {
		global $available_address_headers;
		$options = array('toorcc' => _("To: or Cc") );
		foreach($available_address_headers as $no=>$h){
			$options[$h] = $h;
		}
		$out = $this->generic_listbox('cond['.$n.'][address]', $options, $selected_header);
		return $out;
	}
	
	/**
 	 * Listbox widget with available envelope values to choose from.
 	 *
 	 * @param string $selected_envelope Selected header
 	 * @param int $n option number
 	 */
	function envelope_listbox($selected_envelope, $n) {
		global $available_envelope;
		foreach($available_envelope as $no=>$h){
			$options[$h] = $h;
		}

		$out = $this->generic_listbox('cond['.$n.'][envelope]', $options, $selected_envelope);
		return $out;
	}
	
	/**
	 * Matchtype listbox. Returns an HTML select listbox with available match
	 * types, such as 'contains', 'is' etc.
	 *
	 * @param string $selected_matchtype
	 * @param int $n
	 * @param string $varname
	 * @return string
	 */
	function matchtype_listbox($selected_matchtype, $n, $varname = 'matchtype') {
		global $matchtypes, $comparators, $matchregex;

		$options = $matchtypes;
		if($this->s->capability_exists('relational')) {
			$options = array_merge($options, $comparators);
		}
		if($this->s->capability_exists('regex')) {
			$options = array_merge($options, $matchregex);
		}
		
		$out = $this->generic_listbox('cond['.$n.']['.$varname.']', $options, $selected_matchtype);
		return $out;
	}
	
	/**
	 * The condition listbox shows the available conditions for a given match
	 * type. Usually 'and' and 'or'.
	 *
	 * @param string $selected_condition
	 * @return string
	 */
	function condition_listbox($selected_condition) {
		global $conditions;
		$out = _("The condition for the following rules is:").
			$this->generic_listbox('condition', $conditions, $selected_condition);
		return $out;
	}

	/**
	 * Output a whole line that represents a condition, that is the $n'th
	 * condition in the array $this->rule['cond'].
	 *
	 * @param int $n
	 * @return string
	 * @see condition_header()
	 * @see condition_address()
	 * @see condition_envelope()
	 * @see condition_size()
	 * @see condition_body()
	 * @see condition_all()
	 */
	function condition($n) {
		global $types;

		if(!isset($this->rule['cond'][$n]['type'])) {
			// $out = $this->select_type('cond['.$n.'][type]', '');
			$this->rule['cond'][$n]['type'] = 'header';
		}

		$out = $this->select_type('cond['.$n.'][type]', $this->rule['cond'][$n]['type']);

		if(isset($types[$this->rule['cond'][$n]['type']])) {
			$methodname = 'condition_' . $this->rule['cond'][$n]['type'];
			$out .= $this->$methodname($n);
		}
		return $out;
	}

	/** 
	 * Output all conditions
	 * @return string
	 */
	function all_conditions() {
		global $maxitems, $startitems, $comparators;
		
		if(isset($this->rule['condition'])) {
			$condition = $this->rule['condition'];
		} else {
			$condition = 'and';
		}

		if(isset($_POST['items'])) {
			$items = $_POST['items'];

		} elseif(isset($this->rule['cond'])) {
			$items = sizeof($this->rule['cond']);
		} else {
			global $items;
			if(!isset($items)) {
				$items = $startitems;
			}
		}
		if(isset($_POST['append'])) {
			$items++;
		} elseif(isset($_POST['less'])) {
			$items--;
		}

		$out = '';
		if($items > 1) {
			$out .= $this->condition_listbox($condition);
		}

		$out .= '<ul>';
		for ( $n=0; $n< $items; $n++) {
			$out .= '<li>'. $this->condition($n) . '</li>';
		}
		$out .= '</ul><br />';

		$out .= '<input type="hidden" name="items" value="'.$items.'" />';
		$out .= '<input type="hidden" name="type" value="1" />';
		
		if($items > 1) {
			$out .= '<input name="less" value="'. _("Less...") .'" type="submit" />';
		}
		if($items < $maxitems) {
			$out .= '<input name="append" value="'. _("More..."). '" type="submit" />';
		}
		return $out;
		
	}
	
	/**
	 * Output HTML code for header match rule.
	 *
	 * @param int $n Number of current condition (index of 'cond' array)
	 * @return string
	 */
	function condition_header($n) {

		if(isset($this->rule['cond'][$n]['header'])) {
			$header = $this->rule['cond'][$n]['header'];
		} else {
			$header = '';
		}
		if(isset($this->rule['cond'][$n]['matchtype'])) {
			$matchtype = $this->rule['cond'][$n]['matchtype'];
		} else {
			$matchtype = '';
		}
		if(isset($this->rule['cond'][$n]['headermatch'])) { 
			$headermatch = $this->rule['cond'][$n]['headermatch'];
		} else {
			$headermatch = '';
		}
		
		$out = $this->header_listbox($header, $n) .
			$this->matchtype_listbox($matchtype, $n) .
			'<input type="text" name="cond['.$n.'][headermatch]" size="24" maxlength="255" value="'.
			htmlspecialchars($headermatch).'" />';
		
		return $out;
	}
	
	/**
	 * Output HTML code for address match rule.
	 *
	 * @param int $n Number of current condition (index of 'cond' array)
	 * @return string
	 */
	function condition_address($n) {
		if(isset($this->rule['cond'][$n]['address'])) {
			$address = $this->rule['cond'][$n]['address'];
		} else {
			$address = '';
		}
		if(isset($this->rule['cond'][$n]['matchtype'])) {
			$matchtype = $this->rule['cond'][$n]['matchtype'];
		} else {
			$matchtype = '';
		}
		if(isset($this->rule['cond'][$n]['addressmatch'])) { 
			$addressmatch = $this->rule['cond'][$n]['addressmatch'];
		} else {
			$addressmatch = '';
		}
		$out = $this->address_listbox($address, $n) .
			$this->matchtype_listbox($matchtype, $n) .
			'<input type="text" name="cond['.$n.'][addressmatch]" size="24" maxlength="255" value="'.
			htmlspecialchars($addressmatch).'" />';
		return $out;
	}
	
	/**
	 * Output HTML code for envelope match rule.
	 *
	 * @param int $n Number of current condition (index of 'cond' array)
	 * @return string
	 */
	function condition_envelope($n) {
		if(isset($this->rule['cond'][$n]['envelope'])) {
			$envelope = $this->rule['cond'][$n]['envelope'];
		} else {
			$envelope = '';
		}
		if(isset($this->rule['cond'][$n]['matchtype'])) {
			$matchtype = $this->rule['cond'][$n]['matchtype'];
		} else {
			$matchtype = '';
		}
		if(isset($this->rule['cond'][$n]['envelopematch'])) { 
			$envelopematch = $this->rule['cond'][$n]['envelopematch'];
		} else {
			$envelopematch = '';
		}
		$out = $this->envelope_listbox($envelope, $n) .
			$this->matchtype_listbox($matchtype, $n) .
			'<input type="text" name="cond['.$n.'][envelopematch]" size="24" maxlength="255" value="'.
			htmlspecialchars($envelopematch).'" />';
		return $out;
	}
		
	/**
	 * Size match
	 *
	 * @param int $n Number of current condition (index of 'cond' array)
	 * @return string
	 */
	function condition_size($n) {
		if(isset($this->rule['cond'][$n]['sizerel'])) {
			$sizerel = $this->rule['cond'][$n]['sizerel'];
		} else {
			$sizerel = 'bigger';
		}
		if(isset($this->rule['cond'][$n]['sizeamount'])) {
			$sizeamount = $this->rule['cond'][$n]['sizeamount'];
		} else {
			$sizeamount = '';
		}
		if(isset($this->rule['cond'][$n]['sizeunit'])) {
			$sizeunit = $this->rule['cond'][$n]['sizeunit'];
		} else {
			$sizeunit = 'kb';
		}

		// $out = '<p>'._("This rule will trigger if message is").
		$out = '<select name="cond['.$n.'][sizerel]"><option value="bigger" name="sizerel"';
		if($sizerel == "bigger") $out .= ' selected="SELECTED"';
		$out .= '>'. _("bigger") . '</option>'.
			'<option value="smaller" name="sizerel"';
		if($sizerel == 'smaller') $out .= ' selected="SELECTED"';
		$out .= '>'. _("smaller") . '</option>'.
			'</select>' .
			_("than") . 
			'<input type="text" name="cond['.$n.'][sizeamount]" size="10" maxlength="10" value="'.$sizeamount.'" /> '.
			'<select name="cond['.$n.'][sizeunit]">'.
			'<option value="kb" name="sizeunit';
		if($sizeunit == 'kb') $out .= ' selected="SELECTED"';
		$out .= '">' . _("KB (kilobytes)") . '</option>'.
			'<option value="mb" name="sizeunit"';
		if($sizeunit == "mb") $out .= ' selected="SELECTED"';
		$out .= '">'. _("MB (megabytes)") . '</option>'.
			'</select>';
		return $out;
	}
		
	/**
	 * Output HTML code for body match rule.
	 *
	 * @param int $n Number of current condition (index of 'cond' array)
	 * @return string
	 */
	function condition_body($n) {
		if(isset($this->rule['cond'][$n]['matchtype'])) {
			$matchtype = $this->rule['cond'][$n]['matchtype'];
		} else {
			$matchtype = '';
		}
		if(isset($this->rule['cond'][$n]['bodymatch'])) { 
			$bodymatch = $this->rule['cond'][$n]['bodymatch'];
		} else {
			$bodymatch = '';
		}
		$out = $this->matchtype_listbox($matchtype, $n) .
			'<input type="text" name="cond['.$n.'][bodymatch]" size="24" maxlength="255" value="'.
			htmlspecialchars($bodymatch).'" />';
		return $out;
	}
		
	/**
	 * All messages 
	 * @return string
	 * @obsolete
	 */
	function condition_all() {
		$out = _("All Messages");
		$dum = _("The following action will be applied to <strong>all</strong> incoming messages that do not match any of the previous rules.");
		return $out;
	}
	
	/**
	 * Output available actions in a radio-button style.
	 * @return string
	 */
	function rule_3_action() {
		/* Preferences from config.php */
		global $useimages, $translate_return_msgs;
		/* Data taken from addrule.php */
		global $boxes, $emailaddresses;
		/* Other */
		global $actions;
		$out = '<p>'. _("Choose what to do when this rule triggers, from one of the following:"). '</p>';
		
		foreach($actions as $action) {
			$classname = 'avelsieve_action_'.$action;
			if(class_exists($classname)) {
				$$classname = new $classname($this->s, $this->rule, 'html');
				if($$classname->is_action_valid()) {
					$out .= $$classname->action_html();
				}
			}
		}
		return $out;
	}
	
	/**
	 * Output available *additional* actions (notify, stop etc.) in a
	 * checkbox-button style.
	 *
	 * @return string
	 */
	function rule_3_additional_actions() {
		/* Preferences from config.php */
		global $useimages, $translate_return_msgs;
		/* Data taken from addrule.php */
		global $boxes, $emailaddresses;
		/* Other */
		global $additional_actions;

		$out = '';
		
		foreach($additional_actions as $action) {
			$classname = 'avelsieve_action_'.$action;
			if(class_exists($classname)) {
				$$classname = new $classname($this->s, $this->rule, 'html');
				if($$classname != null) {
					$out .= $$classname->action_html();
				}
			}
		}
		return $out;
	}
	/**
	 * Submit buttons for edit form -- not applicable for wizard
	 * @return string
	 */
	function submit_buttons() {
		$out = '<tr><td><div style="text-align: center">';
		switch ($this->mode) {
			case 'addnew':
				$out .= '<input type="submit" name="addnew" value="'._("Add New Rule").'" />';
				break;
			case 'addnewspam':
				$out .= '<input type="submit" name="addnew" value="'._("Add SPAM Rule").'" />';
				break;
			case 'duplicate':
				$out .= '<input type="hidden" name="dup" value="1" />';
				$out .= '<input type="submit" name="addnew" value="'._("Add New Rule").'" />';
				break;
			case 'duplicatespam':
				$out .= '<input type="hidden" name="dup" value="1" />';
				$out .= '<input type="submit" name="addnew" value="'._("Add SPAM Rule").'" />';
				break;
			case 'edit':
				$out .= '<input type="submit" name="apply" value="'._("Apply Changes").'" />';
				break;
		}
		if($this->popup) {
			$out .= ' <input type="submit" name="cancel" onClick="window.close(); return false;" value="'._("Cancel").'" />';
		} else {
			$out .= ' <input type="submit" name="cancel" value="'._("Cancel").'" />';
		}
		return $out;
	}

	/**
	 * Main function that outputs a form for editing a whole rule.
	 *
	 * @param int $edit Number of rule that editing is based on.
	 */
	function edit_rule($edit = false) {
		global $PHP_SELF, $color;

        if($this->mode == 'edit') {
            /* 'edit' */
            $out = '<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
                '<input type="hidden" name="edit" value="'.$edit.'" />'.
                $this->table_header( _("Editing Mail Filtering Rule") . ' #'. ($edit+1) ).
                $this->all_sections_start();
        } else {
            /* 'duplicate' or 'addnew' */
            $out = '<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
                $this->table_header( _("Create New Mail Filtering Rule") ).
	    		$this->all_sections_start();
		}
		/* ---------- Error (or other) Message, if it exists -------- */
		if(!empty($this->errmsg)) {
			$out .= $this->section_start( _("Error Encountered:") ).
				'<div style="text-align:center; color:'.$color[2].';">';

			if(is_array($this->errmsg)) {
				$out .= '<ul>';
				foreach($this->errmsg as $msg) {
					$out .= '<li>'.$msg.'</li>';
				}
				$out .= '</ul>';
			} else {
				$out .= '<p>'.$this->errmsg .'</p>';
			}
			$out .= '<p>'. _("You must correct the above errors before continuing."). '</p>';
			
			$out .= '</div>' . 	$this->section_end();
		}
		
		/* --------------------- 'if' ----------------------- */
		$out .= $this->section_start( _("Condition") );

		switch ($this->rule['type']) { 
			case 0:
			case 1: 
			default:
				// New-style generic conditions
				$out .= $this->all_conditions();
				break;
			case 2:			/* header */
			case 3: 		/* size */
			case 4: 		/* All messages */
				/* Obsolete */
				/* Something went wrong. Probably re-migrate. */
				/* FIXME */
				print "DEBUG: Something went wrong. Probably re-migrate.";
				break;
				
		}
		$out .= $this->section_end();

		/* --------------------- 'then' ----------------------- */
		
		$out .= $this->section_start( _("Action") );
		
		if(isset($rule['folder'])) {
			$selectedmailbox = $rule['folder'];
		}
		
		$out .= $this->rule_3_action().
			$this->section_end();

		$out .= $this->section_start( _("Additional Actions") );
		$out .= $this->rule_3_additional_actions().
			$this->section_end();


		/* --------------------- buttons ----------------------- */

		$out .= $this->submit_buttons().
			'</div></td></tr>'.
			$this->all_sections_end() .
			$this->table_footer().
            '</form>';

		return $out;
	}
}
?>
