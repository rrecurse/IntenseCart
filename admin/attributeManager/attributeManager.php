<?php
/*
  $Id: attributeManager.php,v 1.0 21/02/06 Sam West$

  
  

  
  
  Copyright © 2006 Kangaroo Partners
  http://kangaroopartners.com
  osc@kangaroopartners.com
*/

// change the directory upone for application top includes
chdir('../');
//ini_set('include_path', dirname(dirname(__FILE__)) . (((substr(strtoupper(PHP_OS),0,3)) == "WIN") ? ";" : ":") . ini_get('include_path'));

// OSC application top needed for sessions, defines and functions
require_once('includes/application_top.php');

// db wrapper
if(class_exists("amDB"))
require_once('classes/amDB.class.php');

// session functions
if(function_exists("amSessionUnregister"))
require_once('includes/attributeManagerSessionFunctions.inc.php');

// config
require_once('classes/attributeManagerConfig.class.php');

// misc functions
require_once('includes/attributeManagerGeneralFunctions.inc.php');

// parent class
require_once('classes/attributeManager.class.php');

// instant class
require_once('classes/attributeManagerInstant.class.php');

// atomic class
require_once('classes/attributeManagerAtomic.class.php');

// security class
require_once('classes/stopDirectAccess.class.php');

// check that the file is allowed to be accessed
stopDirectAccess::checkAuthorisation(AM_SESSION_VALID_INCLUDE);


// get an instance of one of the attribute manager classes
$attributeManager =& amGetAttributeManagerInstance($_GET);

// do any actions that should be done
$globalVars = $attributeManager->executePageAction($_GET);


// set any global variables from the page action execution
if(0 !== count($globalVars) && is_array($globalVars)) 
	foreach($globalVars as $varName => $varValue)
		$$varName = $varValue;


// get the current products options and values
$allProductOptionsAndValues = $attributeManager->getAllProductOptionsAndValues();

// count the options
$numOptions = count($allProductOptionsAndValues);

// output a response header
header('Content-type: text/html; charset=ISO-8859-1');

//$attributeManager->debugOutput($allProductOptionsAndValues);
//$attributeManager->debugOutput($attributeManager);

// include any prompts


require_once('attributeManager/includes/attributeManagerPrompts.inc.php');

if(!isset($_GET['target']) || 'topBar' == $_GET['target'] ) {
	if(!isset($_GET['target'])) 
		echo '<div id="topBar">';
?>


<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td>
		<?php
		$languages = tep_get_languages();
		if(count($languages) > 1) {
			foreach ($languages as $amLanguage) {
			?>
			&nbsp;<input type="image" <?php echo ($attributeManager->getSelectedLanaguage() == $amLanguage['id']) ? 'style="padding:1px;border:1px solid black" onClick="return false" ' :'onclick="return amSetInterfaceLanguage(\''.$amLanguage['id'].'\'); contentChanged();" '?> src="<?php echo DIR_WS_CATALOG_LANGUAGES . $amLanguage['directory'] . '/images/' . $amLanguage['image']?>"  border="0" title="Changes" />
			<?php
			}
		}
		?>
		</td>
		<td align="right">
		
		<?php
		if(false !== AM_USE_TEMPLATES) {
			?>
			<div  style="padding:5px 3px 5px 0px">
				<?php echo tep_draw_pull_down_menu('template_drop',$attributeManager->buildAllTemplatesDropDown(),(0 == $selectedTemplate) ? '0' : $selectedTemplate,'id="template_drop" style="margin-bottom:3px"');	?>
				&nbsp;
				<input type="image" src="attributeManager/images/icon_load.png" onclick="return customTemplatePrompt('loadTemplate');" border="0" title="Loads the selected template" />
				&nbsp;
				<input type="image" src="attributeManager/images/icon_save.png" onclick="return customPrompt('saveTemplate');" border="0" title="Saves the current attributes as a new template" />
				&nbsp;
				<input type="image" src="attributeManager/images/icon_rename.png" onclick="return customTemplatePrompt('renameTemplate');" border="0" title="Renames the selected template" />
				&nbsp;
				<input type="image" src="attributeManager/images/icon_delete.png" onclick="return customTemplatePrompt('deleteTemplate');" border="0" title="Deletes the selected template" />
				&nbsp;
			</div>
			<?php
		}
		?>
		</td>
	</tr>
</table>
<?php
	if(!isset($_GET['target'])) 
		echo '</div>';
} // end target = topBar
	
if(!isset($_GET['target'])) 
	echo '<div id="attributeManagerAll">';
?>
<?php
if(!isset($_GET['target']) || 'currentAttributes' == $_GET['target']) {
	if(!isset($_GET['target'])) 
		echo '<div id="currentAttributes">';
?>
	<table width="100%" border="0" cellspacing="0" cellpadding="3">	
		<tr class="header">
			<td width="50" align="center" onclick="contentChanged();">
				<input type="image" src="attributeManager/images/icon_plus.gif" onclick="contentChanged(); return amShowHideAllOptionValues([<?php echo implode(',',array_keys($allProductOptionsAndValues));?>],true); " border="0" />
				&nbsp;
				<input type="image" src="attributeManager/images/icon_minus.gif" onclick="contentChanged(); return amShowHideAllOptionValues([<?php echo implode(',',array_keys($allProductOptionsAndValues));?>],false);" border="0" / onLoad=" contentChanged();">
			</td>
			<td>
				Name
			</td>
			<td>
				Track Stock?
			</td>
			<td align="right">
				<span style="margin-right:40px">Action</span>
			</td>
		</tr>
		
	<?php
	if(0 < $numOptions) {
		foreach($allProductOptionsAndValues as $optionId => $optionInfo){
			$numValues = count($optionInfo['values']);
			$txtTrackStock = ($optionInfo['track_stock'] == 1? 'Yes' : 'No');
	?>
			<tr class="option">
				<td align="center" onclick="contentChanged();">
				<input type="image" border="0" id="show_hide_<?php echo $optionId; ?>" src="attributeManager/images/icon_plus.gif" onclick="return amShowHideOptionsValues(<?php echo $optionId; ?>); contentChanged();" />
				
				</td>
				<td>
					<?php echo "{$optionInfo['name']} ($numValues)";?>
				</td>
				<td><?echo $txtTrackStock ?></td>
		
				<td align="right">
					<?php echo tep_draw_pull_down_menu("new_option_value_$optionId",$attributeManager->buildOptionValueDropDown($optionId),$selectedOptionValue,'style="margin:3px 0px 3px 0px;" id="new_option_value_'.$optionId.'"')?>
					<input type="image" src="attributeManager/images/icon_add.png" value="Add" border="0" onclick="return amAddOptionValueToProduct('<?php echo $optionId?>');" title="Adds the selected attribute on the left to the <?php echo $optionInfo['name']?> option " />
				
					<input type="image" title="Adds a new value to the <?php echo $optionInfo['name']?> option" border="0" src="attributeManager/images/icon_add_new.png" onclick="return customPrompt('amAddNewOptionValueToProduct','<?php echo addslashes("option_id:$optionId|option_name:{$optionInfo['name']}")?>');" />
									
<!--					<input type="image" src="attributeManager/images/icon_rename.png" onclick="return customTemplatePrompt('renameTemplate');" border="0" title="Renames the selected template" />-->
					
					<input type="image" border="0" onClick="return customPrompt('amRemoveOptionFromProduct','<?php echo addslashes("option_id:$optionId|option_name:{$optionInfo['name']}")?>');" src="attributeManager/images/icon_delete.png" title="Removes the option <?php echo $optionInfo['name']?> and the <?php echo $numValues?> option value(s) below it  from this product" />

			
					<?php
					if(AM_USE_SORT_ORDER) {
					?>	
					<input type="image" onclick="return customPrompt('moveOptionUp');" src="attributeManager/images/icon_up.png" title="Moves option up" />
					<input type="image" onclick="return customPrompt('moveOptionDown');" src="attributeManager/images/icon_down.png" title="Moves option down" />
					<?php
					}
					?>
				</td>
			</tr>
	<?php
			if(0 < $numValues){
				foreach($optionInfo['values'] as $optionValueId => $optionValueInfo) {
	?>
			<tr class="optionValue" id="trOptionsValues_<?php echo $optionId; ?>" style="display:none" >
				<td align="center">
					<img src="attributeManager/images/icon_arrow.gif" />
				</td>
				<td>
					<?php echo $optionValueInfo['name']?>
				</td>
				<td align="right">
					<span style="margin-right:41px;">
					<?php echo drawDropDownPrefix('id="prefix_'.$optionValueId.'" style="margin:3px 0px 3px 0px;" onChange="return amUpdate(\''.$optionId.'\',\''.$optionValueId.'\');"',$optionValueInfo['prefix']);?><?php echo tep_draw_input_field("price_$optionValueId",$optionValueInfo['price'],' style="margin:3px 0px 3px 0px;" id="price_'.$optionValueId.'" size="7" onChange="return amUpdate(\''.$optionId.'\',\''.$optionValueId.'\');"'); ?>
					</span>
<!--					<input type="image" src="attributeManager/images/icon_rename.png" onclick="return customTemplatePrompt('renameTemplate');" border="0" title="Renames the selected template" />-->
				
					<input type="image" border="0" onClick="return customPrompt('amRemoveOptionValueFromProduct','<?php echo addslashes("option_id:$optionId|option_value_id:$optionValueId|option_value_name:{$optionValueInfo['name']}")?>');" src="attributeManager/images/icon_delete.png" title="<?php echo "Removes {$optionValueInfo['name']} from {$optionInfo['name']}"?>, from this product" />
					<?php
					if(AM_USE_SORT_ORDER) {
					?>	
					<input type="image" onclick="return customPrompt('moveOptionValueUp','<?php echo "option_id:$optionId|option_value_id:$optionValueId"; ?>');" src="attributeManager/images/icon_up.png" title="Moves option value up" />
					<input type="image" onclick="return customPrompt('moveOptionValueDown','<?php echo "option_id:$optionId|option_value_id:$optionValueId"; ?>');" src="attributeManager/images/icon_down.png" title="Moves option value down" />
					<?php
					}
					?>
				</td>
			</tr>
	<?php
				}
			}
		}	
	}
	?>
	</table>
	<?php
	if(!isset($_GET['target'])) 
		echo '</div>';
} // end target = currentAttributes

if(!isset($_GET['target']) || 'newAttribute' == $_GET['target'] ) {
	
	if(!isset($_GET['target'])) 
		echo '<div id="newAttribute">';
	
	// check to see if the selected option isset if it isn't pick the first otion in the dropdown
	$optionDrop = $attributeManager->buildOptionDropDown();
	
	if(!is_numeric($selectedOption)) {
		foreach($optionDrop as $key => $value) {
			if(tep_not_null($value['id'])){
				$selectedOption = $value['id'];
				break;
			}
		}
	}

	$optionValueDrop = $attributeManager->buildOptionValueDropDown($selectedOption);
?>
	<table border="0"  cellpadding="3">
		<tr>
			<td align="right" valign="top">
				Option: <?php echo tep_draw_pull_down_menu('optionDropDown',$optionDrop,$selectedOption,'id="optionDropDown" onChange="return amUpdateNewOptionValue(this.value);"')?>
				<div class="optionValueAddDelete">
<!--					<input border="0"  type="image" src="attributeManager/images/icon_delete.png" onclick="return deleteOption();" title="Deletes the Option from the database" />-->
<!--					&nbsp;-->
<!--					<input type="image" src="attributeManager/images/icon_rename.png" onclick="return customTemplatePrompt('renameTemplate');" border="0" title="Renames the selected template" />-->
					&nbsp;
					<input border="0"  type="image" src="attributeManager/images/icon_add_new.png" onclick="return customPrompt('amAddOption');" title="Adds a new option to the list" />
					
				</div>
			</td>
			<td align="right" valign="top">
				Value: <?php echo tep_draw_pull_down_menu('optionValueDropDown',$optionValueDrop,(is_numeric($selectedOptionValue) ? $selectedOptionValue : ''),'id="optionValueDropDown"')?>
				<div class="optionValueAddDelete">
<!--					<input border="0"  type="image" src="attributeManager/images/icon_delete.png" onclick="return deleteOptionValue();" title="Deletes the option value from the database" />-->
<!--					&nbsp;-->
<!--					<input type="image" src="attributeManager/images/icon_rename.png" onclick="return customTemplatePrompt('renameTemplate');" border="0" title="Renames the selected template" />-->
					&nbsp;
					<input border="0" type="image" src="attributeManager/images/icon_add_new.png" onclick="return customPrompt('amAddOptionValue');" title="Adds a new option value to the list" />
					
				</div>
			</td>
			<td valign="top">
				Prefix: <?php echo drawDropDownPrefix('id="prefix_0"')?>
			</td>
			<td valign="top">
				Price: <?php echo tep_draw_input_field('newPrice','','size="4" id="newPrice"'); ?>
			</td>
			<td valign="top">
				<input type="image" src="attributeManager/images/icon_add.png" value="Add" onclick="return amAddAttributeToProduct();" title="Adds the attribute to the current product" border="0"  />
			</td>
		</tr>
	</table>
<?php
	if(!isset($_GET['target'])) 
		echo '</div>';
} // end target = newAttribute
if(!isset($_GET['target'])) 
	echo '</div>';
?>
