<?php
/*
  $Id: attributeManagerPrompts.inc.php,v 1.0 21/02/06 Sam West$

  
  

  
  
  Copyright © 2006 Kangaroo Partners
  http://kangaroopartners.com
  osc@kangaroopartners.com
*/

function yesNoButtons($section) {
return <<<yesnobuttons
<div style="width:100px;margin:auto;">
	<table  border="0" cellpadding="5">
		<tr>
			<td><input type="submit" value="Yes" onClick="return $section();" /></td>
			<td align="right"><input type="submit" value="No" onClick="removeCustomPrompt();" /></td>
		</tr>
	</table>
</div>
yesnobuttons;
}

function updateCancelButtons($section) {
return <<<updatecancelbuttons
<div style="text-align:right;margin-top:20px">
	<input type="submit" value="Update" onClick="return $section();" />&nbsp;
	<input type="submit" value="cancel" onClick="removeCustomPrompt();" />
</div>
updatecancelbuttons;
}

function languageTextFields() {
$return = '
<table>';
	$languages = tep_get_languages();
	foreach ($languages as $amLanguage) {
$return .='
	<tr>
		<td align="right">'. tep_image(DIR_WS_CATALOG_LANGUAGES . $amLanguage['directory'] . '/images/' . $amLanguage['image'], $amLanguage['name']).'</td>
		<td>'.tep_draw_input_field('text_field_'.$amLanguage['id'],'','id="'.$amLanguage['id'].'"').'</td>
	</tr>';
	}
$return .= '
</table>';
return $return;
}

function okButton() {
	return '<input type="submit" align="center" value="OK" onClick="removeCustomPrompt();" />';
}

class amPopups {
	var $header = '';
	var $contents = '';
	
	function setHeader($string) {
		$this->header .= $string;
	}
	
	function addToContents($string) {
		$this->contents .= $string;
	}
	
	function output() {
		return '
		<div id="popupHeading">'.stripcslashes($this->header).'</div>
		<div id="popupContainer">'.$this->contents.'</div>';
	}
}

// check that it is a prompt section
if(isset($_GET[AM_ACTION_GET_VARIABLE]) && $_GET[AM_ACTION_GET_VARIABLE] == 'prompt') {
	
	// de encode the extra gets string
	if(isset($_GET['gets'])) {
		$arrExtraValues = array();
		$valuePairs = array();
		
		if(strpos($_GET['gets'],'|')) 
			$valuePairs = explode('|',$_GET['gets']);
		else 
			$valuePairs[] = $_GET['gets'];
		
		foreach($valuePairs as $pair)
			if(strpos($pair,':')) {
				list($extraKey, $extraValue) = explode(':',$pair);	
				$arrExtraValues[$extraKey] = $extraValue;
			}
	}
	
	switch($_GET['section']) {
		case 'amAddOption':
		
		// Create drop down values for QTPro track stock option
		  $track_stock_arr = array(array('id' => '0', 'text' => 'No'),
				                         array('id' => '1', 'text' => 'Yes'));
		
			$amPopup = new amPopups();
			$amPopup->setHeader('Please enter a new Option Name');
			$amPopup->addToContents(languageTextFields());
			$amPopup->addToContents("<br />Track stock:&nbsp".tep_draw_pull_down_menu('track_stock',$track_stock_arr,'1','id="track_stock"'));		// QTPro track stock
			$amPopup->addToContents(updateCancelButtons($_GET['section']));
			echo $amPopup->output();
			break;
		case 'amAddOptionValue':
			$amPopup = new amPopups();
			$amPopup->setHeader('Please enter a new Option Value Name');
			$amPopup->addToContents(languageTextFields());
			$amPopup->addToContents(updateCancelButtons($_GET['section']));
			echo $amPopup->output();
			break;
		case 'amAddNewOptionValueToProduct':
			$amPopup = new amPopups();
			$amPopup->setHeader("Please enter a new Option Value Name to add to {$arrExtraValues['option_name']}");
			$amPopup->addToContents(languageTextFields());
			$amPopup->addToContents(updateCancelButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('option_id',$arrExtraValues['option_id'],'id="option_id"'));
			echo $amPopup->output();
			break;
		case 'amRemoveOptionFromProduct': 
			$amPopup = new amPopups();
			$amPopup->setHeader("Are you sure you want to remove {$arrExtraValues['option_name']} and all the values below it from this product?");
			$amPopup->addToContents(yesNoButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('option_id',$arrExtraValues['option_id'],'id="option_id"'));
			echo $amPopup->output();
			break;
		case 'amRemoveOptionValueFromProduct':
			$amPopup = new amPopups();
			$amPopup->setHeader("Are you sure you want to remove {$arrExtraValues['option_value_name']} from this product?");
			$amPopup->addToContents(yesNoButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('option_id',$arrExtraValues['option_id'],'id="option_id"'));
			$amPopup->addToContents(tep_draw_hidden_field('option_value_id',$arrExtraValues['option_value_id'],'id="option_value_id"'));
			echo $amPopup->output();
			break;
		case 'loadTemplate':
			$amPopup = new amPopups();
			$amPopup->setHeader("Are you sure you want to load the {$arrExtraValues['template_name']} Template? <br />This will overwrite this products current options and cannot be undone.");
			$amPopup->addToContents(yesNoButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('template_id',$arrExtraValues['template_id'],'id="template_id"'));
			echo $amPopup->output();
			break;
		case 'saveTemplate':
			$amPopup = new amPopups();
			$amPopup->setHeader("Please enter a new name for the new Template. Or...");
			$amPopup->addToContents("New Name:&nbsp".tep_draw_input_field('template_name','','id="template_name" onchange="((this.value != \'\') ? document.getElementById(\'existing_template\').selectedIndex = 0 : \'\')"'));
			$templatesDrop = $attributeManager->buildAllTemplatesDropDown();
			$amPopup->setHeader(" ...<br /> ... Choose and existing one to overwrite");
			$amPopup->addToContents("<br /><br />Existing:&nbsp".tep_draw_pull_down_menu('existing_template',$templatesDrop,'0','id="existing_template" onChange="document.getElementById(\'template_name\').value=\'\';"'));		
			$amPopup->addToContents(updateCancelButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('template_id',$arrExtraValues['template_id'],'id="template_id"'));
			echo $amPopup->output();
			break;
		case 'renameTemplate':
			$amPopup = new amPopups();
			$amPopup->setHeader("Please enter the new name for the {$arrExtraValues['template_name']} Template");
			$amPopup->addToContents("New Name:&nbsp".tep_draw_input_field('template_new_name','','id="template_new_name"'));
			$amPopup->addToContents(updateCancelButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('template_id',$arrExtraValues['template_id'],'id="template_id"'));
			echo $amPopup->output();
			break;
		case 'deleteTemplate':
			$amPopup = new amPopups();
			$amPopup->setHeader("Are you sure you want to delete the {$arrExtraValues['template_name']} Template?<br>This cannot be undone!");
			$amPopup->addToContents(yesNoButtons($_GET['section']));
			$amPopup->addToContents(tep_draw_hidden_field('template_id',$arrExtraValues['template_id'],'id="template_id"'));
			echo $amPopup->output();
			break;
	}
}

?>