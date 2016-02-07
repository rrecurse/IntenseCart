<?php 

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	define('MODEL_THUMB_WIDTH',40);
	define('MODEL_THUMB_HEIGHT',40);

	$optns = array();
	$opvals = array();

	$opvals_qry = tep_db_query("SELECT * FROM products_options_values_to_products_options v2o 
								LEFT JOIN products_options_values v ON v.products_options_values_id = v2o.products_options_values_id 
									AND v.language_id='".$languages_id."' 
								LEFT JOIN products_options o ON o.products_options_id = v2o.products_options_id 
									AND o.language_id='".$languages_id."'
							   ");
	while ($row = tep_db_fetch_array($opvals_qry)) {
		$optns[$row['products_options_id']] = $row['products_options_name'];

		if(!isset($opvals[$row['products_options_id']])) {
			$opvals[$row['products_options_id']] = array();
		}

		$opvals[$row['products_options_id']][$row['products_options_values_id']] = $row['products_options_values_name'];
	}


	function show_attrctl($products_id,$base_prices,$pobj) {

		$opts = tep_get_product_options($products_id);

		$attrSort = array();
		$numimgs = 5;
		$savedAttrImages = array('none:'=>'<img src="images/add-icon.jpg" alt="" title="Add attribute swatch image">');

		$addattrimg = array();

		foreach($opts as $optn => $attrs) {
			$attr_sort = 1;
			$attrSort[$optn] = array();

			foreach ($attrs as $attr => $attrdata) {

				$attrSort[$optn][$attr] = $attr_sort++;

				$imgptr = ($attrdata['options_image'] ? 'file:'.$attrdata['options_image'] : 'none:');	

				if($attrdata['options_image'] && !isset($savedAttrImages[$imgptr])) {
					$savedAttrImages[$imgptr] = tep_image(DIR_WS_CATALOG_IMAGES.$attrdata['options_image'], $attrdata['options_image'],24,16);
				}

				$addattrimg[]="setAttrImage('$optn','$attr',".tep_js_quote($imgptr).");";

			}
		}

		$modelFieldList = array();

		foreach(array('name','price','price_sign','sku','quantity', 'upc', 'childProducts_id') AS $fld) {
			if(!in_array($fld,$pobj->disableModelFields())) $modelFieldList[]='model_'.$fld;
		}
		
		foreach($pobj->getModelFields() AS $xfld=>$fdata) {
			$modelFieldList[]='model_extra['.$xfld.']';
		}
?>
<div id="modelManagerBase">

<div id="addOptionBox" style="display:none">
<table>
<tr><td>Create new option:</td><td><input type="text" name="new_option_name" id="new_option_name"></td></tr>
<tr><td colspan="2" align="center">
  <button type="button" onClick="returnPromptBox(this,$('new_option_name').value)">Create</button>
  <button type="button" onClick="returnPromptBox(this,null)">Cancel</button>
</td></tr>
</table>
</div>

<div id="addOptionValueBox" style="display:none">
<table>
<tr><td>Create new value for option <span field="optn_name"></span>:</td><td><input type="text" name="new_option_value_name" id="new_option_value_name"></td></tr>
<tr><td colspan="2" align="center">
  <button type="button" onClick="returnPromptBox(this,$('new_option_value_name').value)">Create</button>
  <button type="button" onClick="returnPromptBox(this,null)">Cancel</button>
</td></tr>
</table>
</div>

<div id="alertBox" style="display:none">
<table>
<tr><td align="left"><pre field="message"></pre></td></tr>
<tr><td align="center"><button type="button" onClick="returnPromptBox(this,0)">Ok</button></td></tr>
</table>
</div>

<div id="confirmBox" style="display:none">
<table>
<tr><td align="left"><pre field="message"></pre></td></tr>
<tr><td align="center"><button type="button" onClick="returnPromptBox(this,1)">Ok</button> <button type="button" onClick="returnPromptBox(this,0)">Cancel</button></td></tr>
</table>
</div>

<div id="selectModelImage" style="display:none">
<div id="selectModelImageExisting">
</div>
<div style="display:none"><input type="file" id="upload_model_image_new" name="upload_model_image_new"></div>
<table>
<tr id="upload_model_image_block_0">
<td><input type="radio" name="select_model_image" value="upload:0"></td>
<td>&nbsp;</td>
<td><input type="file" id="upload_model_image_0" name="upload_model_image_0"></td>
</tr>
</table>
  <div id="selectModelImageDiffWarning" style="width:300px">
    <b>Warning:</b> The models in this group have different images in this slot,
    the individual model images will be overriden if applied!
  </div>
  <button type="button" onClick="returnPromptBox(this,this.form.select_model_image)">Select</button>
  <button type="button" onClick="returnPromptBox(this,null)">Cancel</button>
</div>


<div id="selectAttrImage" style="display:none; z-index:20;">
<table border="0" style="background:#C0C0C0;">
<tr><td onClick="selectAttrImageDone('none:');">[none]</td><td field="img_icons">&nbsp;</td></tr>
<td colspan="2"><input type="file" id="upload_attr_image_new" name="upload_attr_image_new" style="display:none;"><button onClick="selectAttrImageDone(); return false;">&raquo;</button></td>
</table>
</div>

<div id="modelPricingBox" style="display:none;">

Group:&nbsp;<select id="model_switch_pricing_group" onChange="for (var i=0;this.options[i];i++) { var b=$('modelPricingBoxGrp_'+this.options[i].value); if (b) b.style.display=this.options[i].selected?'':'none'; }; updatePromptBox($('modelPricingBox'));">

	<?php foreach (tep_get_customer_groups() AS $cgrp=>$cgname) { ?>
		<option value="<?php echo $cgrp?>"><?php echo $cgname?></option><?php } ?>
</select>

<div id="modelPricingBoxGrp" style="display:none">
<input type="checkbox" name="mgrp_allow" value="1" onClick="this.parentNode.getElementsByTagName('table')[0].style.display=this.checked?'':'none'; updatePromptBox($('modelPricingBox'));"> Allow separate pricing for this model
<table border="0">
<tr><td>Unit Price:</td><td><select name="mgrp_price_sign"><option value="+">+</option><option value="-">-</option></select><input name="mgrp_price_value" type="text"></td></tr>
<tr><td colspan="2">
 Separate Discount Brackets for this model
<table border="0">
  <tr><th>Quantity</th><th>Discount&nbsp;%</th></tr>
<?php for ($idx=1;$idx<=10;$idx++) { ?>
  <tr><td><input type="text" name="mgrp_dq[<?php echo $idx?>]" size="5"></td><td><input type="text" name="mgrp_dv[<?php echo $idx?>]" size="5"></td></tr>
<?php } ?>
</table>
</td></tr></table>
</div>
  <button type="button" onClick="returnPromptBox(this,this.form.select_model_image)">Select</button>
  <button type="button" onClick="returnPromptBox(this,null)">Cancel</button>
</div>



<div id="model_panel_prototype" class="AccordionPanel" style="display:none">
<div class="AccordionPanelTab" style="height:48px !important">
<table cellpadding="1" cellspacing="0" border="0" width="100%" height="45"><tr><td style="padding:3px 0 0 7px; width:21px;" valign="middle"><h4 style="width:11px; height:11px; padding:0"></h4></td><td valign="middle" align="left" style="white-space:nowrap; padding:4px 0 0 1px; font:bold 12px arial"><span field="attribute" style="white-space:nowrap;">Attribute</span> (<span field="count">0</span>)</td>

<?php for ($i=0;$i<$numimgs;$i++) { ?>

	<td style="width:<?php echo MODEL_THUMB_WIDTH ?>px; height:<?php echo MODEL_THUMB_HEIGHT?>px; padding:3px 5px 0 0; cursor:pointer; text-align:center; vertical-align:middle;" field="model_image_<?php echo $i ?>" onClick="selectGroupImage(this,<?php echo $i ?>); event.cancelBubble=true;"><?php echo tep_image(DIR_WS_IMAGES.'no_image.gif','',MODEL_THUMB_WIDTH,MODEL_THUMB_HEIGHT)?></td>

<?php } ?>

</tr></table>
</div>
<div class="AccordionPanelContent"><div></div></div>
</div>

<div id="model_entry_prototype" style="display:none;">
	<table width="95%" style="border-bottom:1px dashed #999999">
		<tr>
			<td align="center" width="50"><img src="images/expand.png" border="0" alt="" vspace="5" >
				<div style="z-index:100; width:42px; height:42px; padding:0; overflow:hidden; position:relative;">
					<div style="z-index:20; position:absolute; top:-3px; left:-3px; margin:0;" onMouseOver="this.parentNode.style.overflow='visible';" onMouseOut="this.parentNode.style.overflow='hidden';">

						<table cellspacing="0" cellpadding="0" border="0" style="border:dashed 1px #999999; background-color:#CCC;">
							<tr>
<?php 

	for ($i=0;$i<$numimgs;$i++) { ?>

								<td>
									<div style="width:<?php echo MODEL_HEIGHT_WIDTH?>px;height:<?php echo MODEL_THUMB_HEIGHT?>px;padding:3px 10px 5px 3px;margin:0;cursor:pointer; text-align:center; vertical-align:middle;" field="model_image_<?php echo $i?>" onClick="selectModelImage(this,<?php echo $i?>)"><?php echo tep_image(DIR_WS_IMAGES.'no_image.gif','',MODEL_THUMB_WIDTH,MODEL_THUMB_HEIGHT)?></div>
								</td>
<?php } ?>
							</tr>
							<tr>
<?php 
	
	for ($i=0;$i<$numimgs;$i++) { ?>
								<td align="center"><?php echo $i>0?'alt'.$i:'main'?></td>
<?php } ?>
							</tr>
					</table>
				</div>
			</div>
		</td>

		<td valign="top">
			<div style="overflow:hidden">
					<div field="attributes" style="position:relative; top:1px; left:0px;" onMouseOver="this.parentNode.style.overflow='visible';" onMouseOut="this.parentNode.style.overflow='hidden';">
					</div>
			</div>
		</td>
		<td>

			<table style="padding:0 0 0 10px;">
				<tr>
					<td align="right" style="padding:0 7px 0 0">Price:</td> 
					<td>
<?php if(!in_array('price_sign',$pobj->disableModelFields())) { ?>
						<select name="model_price_sign[]">
							<option value="+">+</option>
							<option value="-">-</option>
						</select>
					<?php } ?>
						<input type="text" name="model_price[]" field="model_price" size="7">
						<input type="hidden" name="model_pricing_list[]" value="">
						<a href="javascript:void(0);" onclick="showPricingBox(this); return false;"><img src="images/add-icon.jpg" alt="" title="Edit model price breaks and pricing Groups" width="14" height="14" hspace="10" border="0"></a></td>
<?php 

	if(!in_array('quantity',$pobj->disableModelFields())) { ?>
		<td align="right" style="padding:0 7px 0 10px">Avail. Stock:</td><td><input type="text" name="model_quantity[]" field="model_quantity" size="6"></td>
<?php 
	} else {
		echo'<td>&nbsp;</td><td>&nbsp;</td>';
	} 
?>
</tr>
<tr>
<td align="right" style="padding:0 7px 0 0">Model:</td> <td><input type="text" name="model_name[]" field="model_name"></td>
<td align="right" style="padding:0 7px 0 0">SKU:</td> <td><input type="text" name="model_sku[]" field="model_sku"></td>
</tr>
<tr>
<td align="right" style="padding:0 7px 0 0">Available on:</td> <td><input type="text" name="model_date_available[]" field="model_date_available" size="10" onClick="popUpCalendar(this,this,'mm/dd/yyyy',document);"></td>

<td align="right" style="padding:0 7px 0 0">UPC:</td> <td><input type="text" name="model_upc[]" field="model_upc"></td>
</tr>
<tr>
<td colspan="3">&nbsp;</td>

<td style="padding:5px 7px 5px 0;">

<div id="theFeed" onclick="setFeeds(this)"; return false;"  style="line-height:15px; cursor:pointer;">
	<img src="images/add-icon.jpg" alt="" title="Edit Marketplace Feeds" width="14" height="14" border="0"> &nbsp; Edit / Add Feeds
</div>

</td>

</tr>

<?php foreach ($pobj->getModelFields() AS $xfld=>$fdata) { ?>
		<tr>
			<td colspan="2"><?php echo $fdata['title']?>:</td>
			<td colspan="2"><input type="text" name="model_extra[<?php echo $xfld?>][]" field="model_x_<?php echo $xfld?>" size="6"></td>
		</tr>
<?php } ?>
</table>
</td>
<td style="padding:0 10px 0 0" id="test"><a href="javascript:void(0)" onClick="window.theEvent=event; delThisModel(this);"><img src="images/delete.png" alt="" title="Delete this attribute combination" id="deleteicon"></a></td>
</tr></table>
<input type="hidden" name="model_attrs[]" value="">
<input type="hidden" name="model_image_ptr[]" value="">
<input type="hidden" name="model_childProducts_id[]" value="">


</div>

<?php 

$last_bar = (isset($prod) ? $prod : null);

?>

<div id="modelSetFeeds_<?php echo $prod?>" class="modelSetFeeds" style="display:none; width:80%; padding: 10px;">

<?php
	// # Marketplace feeds
	// # check to make sure attributes exist by comparing products_id to master_producs_id

	$attributeFeed_query = tep_db_query("SELECT master_products_id, products_id 
										 FROM products 
										 WHERE master_products_id = '".$products_id."' 
										 AND products_id != master_products_id
										");
	
	// # if attributes id's found, generate the Feed selectors

	if(tep_db_num_rows($attributeFeed_query) > 0) {
		$DBFeedMods = tep_module('dbfeed');
		$DBFeedMods->adminProductEdit($theProducts_id);
	} 

//var_dump($theProducts_id);
?>

  <button type="button" onclick="returnPromptBox(this,null)">Cancel</button>
</div>
<div id="modelManager"> 

<div class="modelAccordion" id="modelAccordion" tabindex="0"></div>

<div style="padding:15px 0 10px 2px;"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="23"><img src="images/add.png" title="Add attribute to model" width="22" height="23" alt=""></td><td style="padding:0 0 0 6px; font:bold 13px arial; text-align:left"> Add Models:</td><td align="right" style="padding:0 10px 0 0">
<div onmouseover="ShowContent('tips1'); return true;" onmouseout="HideContent('tips1'); return true;" class="helpicon" style="width:16px; height:16px;"><a href="javascript:ShowContent('tips1')"></a></div>

<div id="tips1" style="display:none; position:absolute; border: 1px dashed #333; background-color: white; padding: 5px; text-align:left; width:300px;">
<font class="featuredpopName"><b style="white-space:nowrap;">Attributes &amp; Models</b></font><br><br><b>#1 Add Attributes</b> - Add Attributes from the dashed box to the left. Example: Color, Size, Width, In-seam. After Adding Attributes, you must add Attribute Values.<br><br><b>#2 Attribute Values</b> - Example: Small (or SM), Large, 34B, 34C.<br><br><b>#3 Price</b> - The difference of price for this particular model. Uses price from your Prices Tab above. Default is no difference.<br><br><b>Model</b> - Arbitrary model number of product model.<br><br><b>Image</b> - Model Image. Hint: Assign different color photos for your different color attributes.<br><br><b>Avail. Stock</b> - Your available to sell stock. <u>Note:</u> If you\'ve activated an ERP module, this value will be dynamically fed from your ERP system.<br><br> <b>SKU</b> -  7 Digits typically. <br><br> <b>UPC</b> - 12 Digit Universal Identifer: <u>Note:</u> If you\'ve activated an ERP module, this value will be required to link your models to your ERP system.
</div>

</td></tr></table></div>
<table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#D4EAB7; border:dashed 1px #CCC;">
  <tr>
<td id="new_model_attrs">
</td>
<td valign="top" style="padding:10px 0 0 0" valign="top">

<table width="100%">
<tr>
<td valign="top">
<table cellpadding="5" cellspacing="0" border="0" style="margin: 0 0 0 35px;">
<tr>
<td align="right">Price:</td>
<td><?php if (!in_array('price_sign',$pobj->disableModelFields())) { ?>
<select name="new_model_price_sign" id="new_model_price_sign"><option value="+">+</option>
<option value="-">-</option></select>
<?php } ?>
<input type="text" name="new_model_price" id="new_model_price" size="7">
</td>
</tr>
<table>

<table style="margin: 0 0 0 35px">
<tr>
<?php 
	if (!in_array('quantity',$pobj->disableModelFields())) {
		echo '<td align="right" style="padding-right:5px; width:30px;" nowrap>Qty:</td>';
	?>
<td><input type="text" name="new_model_quantity" id="new_model_quantity" size="6"></td>
<?php 
} else { 
echo '<td colspan="2">&nbsp;</td>';
} 
?>
</td></tr>
<tr>
<td valign="bottom" colspan="2" style="padding: 5px 0 0 0">Select Attribute Image:</td>
</tr>
</table>
</td>
<td>
<table>
<tr>
<td align="right" style="padding:5px 7px 5px 0">Model:</td><td><input type="text" name="new_model_name" id="new_model_name"></td>
</tr>
<td align="right" style="padding:0 7px 5px 0;">SKU:</td><td><input type="text" name="new_model_sku" id="new_model_sku"></td>
</tr>
<tr>
<td align="right" style="padding:0 7px 5px 0;">UPC:</td><td><input type="text" name="new_model_upc" id="new_model_upc"></td>
</tr>
<?php foreach ($pobj->getModelFields() AS $xfld=>$fdata) { ?>
	<tr>
		<td colspan="2"><?php echo $fdata['title']?>:</td>
		<td><input type="text" name="new_model_extra[<?php echo $xfld?>]" id="new_model_extra[<?php echo $xfld?>]" field="model_x_<?php echo $xfld?>" size="6"></td>
	</tr>
<?php } ?>
</table>
</td></tr>
</table>
<table style="margin: 0 0 5px 40px" cellpadding="0" cellspacing="0">
<tr>
<td valign="top">
<input type="file" id="new_upload_model_image" name="new_upload_model_image" style="font:normal 11px verdana;"></td>
</tr>
</table>

</td>
<td style="padding:0 10px 0 10px;"><a href="javascript:void(0)" onClick="addNewModel('new')"><img src="images/add.png" alt="" border="0" title="Add model to product"></a></td>
</tr></table>

</div>

<div id="models_populate" style="display:none;">
<table><tr>
<td><?php echo tep_draw_pull_down_menu('models_populate_cat',tep_get_category_tree(),'','onChange="loadPopulateProducts(this.value)"')?></td>
<td id="models_populate_prod_box"></td>
</tr></table>
<script type="text/javascript">
function loadPopulateProducts(cat) {
  var blk=$('models_populate_prod_box');
  blk.innerHTML='';
  new ajax('attrctl_populate.php?cat='+cat,{update:blk});
}
</script>
</div>

</div>


<script type="text/javascript">
var modelAccordion = new Spry.Widget.Accordion("modelAccordion");
var currOptions=<?php echo tep_js_quote_array(array_keys($opts))?>;

var primaryOptions=[true];
var attrSort=<?php echo tep_js_quote($attrSort)?>;
var modelFieldList=<?php echo tep_js_quote_array($modelFieldList)?>;
//alert(currOptions.toSource());
<?php

    $modattrs = tep_get_product_models($products_id);
    $images = array();
	//if($default_image) $images[]=$default_image;

    $savedImages = array('default:' => '[none]', 'multiple:' => '[****]');

    $addmdl = array();

	foreach ($modattrs AS $mid => $attrs) {

		$modl = tep_get_product_model_fields($products_id,$mid);

		$model_price = ($modl['products_price'] - $base_prices[0]);
		$img_ptr = array();

		foreach ($modl['products_image_list'] AS $idx=>$pimg) {
			if($pimg && !isset($images[$pimg])) {
				$images[$pimg] = $pimg;
			}
			
			$img_ptr[$idx] = $pimg ? 'file:'.$pimg : 'default:';

			if($img_ptr[$idx] && !isset($savedImages[$img_ptr[$idx]])) { 
				$savedImages[$img_ptr[$idx]]=tep_image(DIR_WS_CATALOG_IMAGES.$pimg,$pimg,MODEL_THUMB_WIDTH,MODEL_THUMB_HEIGHT);
			}
		}

		$grp_price = array(0 => $modl['products_price']);

		$discnts = array();

		$grp_qry = tep_db_query("SELECT * FROM " .TABLE_PRODUCTS_GROUPS. " WHERE products_id = '$mid'");

		while($row = tep_db_fetch_array($grp_qry)) {
			$grp_price[$row['customers_group_id']] = $row['customers_group_price'];
		}

		$dsc_qry = tep_db_query("SELECT * FROM products_discount WHERE products_id='$mid'");

		while ($row = tep_db_fetch_array($dsc_qry)) {
			if(!isset($discnts[$row['customers_group_id']])) {
				$discnts[$row['customers_group_id']]=Array();
			}
			
			$discnts[$row['customers_group_id']][$row['discount_qty']] = $row['discount_qty'].':'.$row['discount_percent'];
		}

		$pr_grps = array();

		foreach($grp_price AS $cgrp => $prc) {
			$pr_grps[] = $cgrp.':'.($prc-$base_prices[$cgrp]).(isset($discnts[$cgrp])?','.join(',',$discnts[$cgrp]):'');
		}

		$pr_list = join(';',$pr_grps);

		$date_av_t = preg_match('/^[1-9]/', $modl['products_date_available']) ? strtotime($modl['products_date_available']) : 0;

		$date_av = ($date_av_t > 0) ? date('m/d/Y',$date_av_t) : '';

		$modflds = array('model_name[]' => $modl['products_model'],
						 'model_sku[]' => $modl['products_sku'],
						 'model_upc[]' => $modl['products_upc'],
						 'model_asin[]' => $modl['products_asin'],
						 'model_ship[]' => $modl['products_ship'],
						 'model_date_available[]' => $date_av,
						 'model_quantity[]' => $modl['products_quantity'],
						 'model_price_sign[]' => ($model_price<0?'-':'+'),
						 'model_price[]' => sprintf("%.2f",abs($model_price)),
						 'model_pricing_list[]' => $pr_list,
						 'model_image_ptr[]' => join('/',$img_ptr),
						 'model_childProducts_id[]' => $mid
					  	);

		foreach($pobj->getModelFields() AS $xfld => $fdata) {
			$modflds['model_extra['.$xfld.'][]'] = tep_get_products_extra($mid,$xfld);
		}

		$addmdl[] = "addModel(".tep_js_quote_array(array_keys($attrs)).",".tep_js_quote_array(array_values($attrs)).",".tep_js_quote($modflds).");";
	}
?>
  var savedImages=<?php echo tep_js_quote($savedImages)?>;
  var savedAttrImages=<?php echo tep_js_quote($savedAttrImages)?>;

function showAllModels() {
  showOptionSelectors('new_model_attrs');

<?php echo join("\n",$addattrimg);?>

<?php echo join("\n",$addmdl);?>
}

</script>

<div style="display:none">
<table id="listModelImages">
<tr>
<td><input type="radio" name="select_model_image" value="default:"></td>
<td colspan="2">None</td>
</tr>
<?php foreach ($images AS $img) { ?>
<tr>
<td><input type="radio" name="select_model_image" value="file:<?php echo htmlspecialchars($img)?>"></td>
<td><?php echo tep_image(DIR_WS_CATALOG_IMAGES.$img,$img,40,40)?></td>
<td><?php echo htmlspecialchars($img)?></td>
</tr>
<?php } ?>
</table>
</div>

<script type="text/javascript">
  $('selectModelImageExisting').insertBefore($('listModelImages'),null);
</script>

<?php 
  }
?>


<script type="text/javascript">

var optnNames=<?php echo tep_js_quote($optns)?>;
var attrValues=<?php echo tep_js_quote($opvals)?>;
var attrPickImages={};

var attrFeedUrl='/admin/attrctl.xml.php?';

var modelSections={};

function boxAlert(msg) { 
	return createPromptBox($('alertBox'),$('modelManager'),{message: msg}); 
}

function boxConfirm(msg,func) { 
	return createPromptBox($('confirmBox'),$('modelManager'),{message: msg},function(rs) { if (rs) func(); }); 
}

function boxPrompt(msg) { 
	return window.prompt(msg); 
}

function scanFields(fields,blk,tag) {
  var fld;
  for (var e=blk.firstChild;e;e=e.nextSibling) {
    if (e.getAttribute && (fld=e.getAttribute(tag)))
      fields[fld]=e;
    scanFields(fields,e,tag);
  }
}

function setField(blk,name,val,df) {
  if (val==null) val=df;
  if (!blk) return;
  if (!blk.dataFieldList) scanFields(blk.dataFieldList={},blk,'field');
  if (blk.dataFieldList[name]) blk.dataFieldList[name].innerHTML=val;
}

function getField(blk,name) {
  if (!blk.dataFieldList) scanFields(blk.dataFieldList={},blk,'field');
  if (blk.dataFieldList[name]) return blk.dataFieldList[name].innerHTML;
  return null;
}

function setAttrSortOrder(optn,attr,val) {
  var flds={};
  flds['attrs_sort_order['+optn+'_'+attr+']']=val;
  return setInputs($('modelAccordion'),flds);
}

function setInputs(sec,flds) {
  setInputsCol(sec.getElementsByTagName('input'),flds);
  setInputsCol(sec.getElementsByTagName('select'),flds);
}

function setInputsCol(inputs,flds) {
  for (var i=0;inputs[i];i++) {
    if (flds[inputs[i].name]!=undefined) {
      if (inputs[i].type=='radio' || inputs[i].type=='checkbox') {
        inputs[i].checked=(inputs[i].value==flds[inputs[i].name]);
      } else if (inputs[i].options) {
        for (var j=0;inputs[i].options[j];j++) inputs[i].options[j].selected=(inputs[i].options[j].value==flds[inputs[i].name]);
      } else inputs[i].value=flds[inputs[i].name];
    }
  }

}

function getInputValue(sec,fld) {
  var inps=sec.getElementsByTagName('input');
  for (var i=0;inps[i];i++) if (inps[i].name==fld && ((inps[i].type!='radio' && inps[i].type!='checkbox') || inps[i].checked!=false)) return inps[i].value;
  inps=sec.getElementsByTagName('select');
  for (var i=0;inps[i];i++) if (inps[i].name==fld) return inps[i].value;
  return null;
}


function makeModelId(optns,attrs) {
  return attrs.join('_');
}

function addModel(optns,attrs,flds) {

	if(!attrs.length) return false;

	var mid = makeModelId(optns,attrs);

	var secid = 'model_entry_'+mid;

	if($(secid)) return false;

	var sec = $('model_entry_prototype').cloneNode(true);

	sec.id = secid;

	for (var fld in flds) {
		setField(sec,fld,flds[fld]);
	}

	var attrlst = new Array();

	var attrtable = new Array('<tr style="background:#E1E1E1; height:18px;"><td colspan="2" style="width:100px; padding:5px;">Attributes</td><td style="padding:5px;">Sort<\/td><td style="padding:5px;" align="center">Image<\/td><\/tr>');


	for(var i=0;optns[i]!=undefined;i++) {

		attrlst.push(optns[i]+':'+attrs[i]);

		if(!attrSort[optns[i]]) attrSort[optns[i]]={};

		var sr = attrSort[optns[i]][attrs[i]];

		if(sr==undefined) {

			sr = 1;

			for(var ii in attrSort[optns[i]]) {
				if(Number(attrSort[optns[i]][ii]) >= sr) {
					sr = Number(attrSort[optns[i]][ii])+1;
				}
			}

			attrSort[optns[i]][attrs[i]] = sr;
		}

		var aimgfld = 'attr_image_ptr_'+optns[i]+'_'+attrs[i];

		attrtable.push('<tr><td style="padding:5px; text-align:left" colspan="2">'+optnNames[optns[i]]+':&nbsp; '+attrValues[optns[i]][attrs[i]]+'<\/td><td style="padding:5px;"><input type="text" name="attrs_sort_order['+optns[i]+'_'+attrs[i]+']" value="'+sr+'" style="width:16px; font-size:8px;" onChange="setAttrSortOrder(\''+optns[i]+'\',\''+attrs[i]+'\',this.value)"><\/td><td style="padding:5px;"><div style="cursor:pointer;" onClick="selectAttrImage(\''+optns[i]+'\',\''+attrs[i]+'\',this)" field="attr_image_'+optns[i]+'_'+attrs[i]+'" align="center">'+makeAttrImage($(aimgfld)?$(aimgfld).value:'none:')+'<\/div><\/td><\/tr>');

	}

	flds['model_attrs[]'] = attrlst.join(',');

	model_childProducts_id = flds['model_childProducts_id[]'];

	setInputs(sec,flds);

	setField(sec,'attributes','<table width="100%" border="0" cellspacing="1" cellpadding="0">'+attrtable.join('')+'<\/table>');

		jQuery("#theFeed").replaceWith('<div id="theFeed_'+model_childProducts_id+'" onclick="setFeeds(this);"  style="line-height:15px; cursor:pointer;"><img src="images/add-icon.jpg" alt="" title="Edit Marketplace Feeds" width="14" height="14" border="0"> &nbsp; Edit / Add Feeds</div>');

//alert(model_childProducts_id);

	sec.dataFieldList = null;

	var imgs = flds['model_image_ptr[]'].split('/');

	for(var i=0;imgs[i]!=null;i++) setModelImage(sec,i,imgs[i]);

	modelSections[secid] = {};

	for(var i=0;optns[i]!=undefined;i++) modelSections[secid][optns[i]]=attrs[i];

	attachModelSection(sec,modelSections[secid]);

	return true;  
}


function attachModelSection(sec,att) {

	var prevp=sec.parentNode;
	if (prevp && prevp.tagName!='DIV') prevp=null;

	var panid='model_panel';
	var pantitle='Models';
	for (var i=0;currOptions[i]!=null;i++) if (primaryOptions[i]) {
    	panid+='_'+att[currOptions[i]];
	    pantitle+='&nbsp;&raquo;&nbsp;'+attrValues[currOptions[i]][att[currOptions[i]]];
	}

	var pan = $(panid);

	if(!pan) {

		pan = $('model_panel_prototype').cloneNode(true);
		pan.id = panid;
		pan.style.display='';

		$('modelAccordion').insertBefore(pan,null);
		modelAccordion.initPanel(pan);
	}

	var pandivs = pan.getElementsByTagName('div');
	var secs = pandivs[1].getElementsByTagName('div')[0];
	secs.insertBefore(sec,null);
	sec.style.display='';
	modelAccordion.adjustPanelHeight();

	if(window.contentChanged) window.contentChanged();

	setField(pandivs[0],'attribute',pantitle);
	updateModelCount(secs,pandivs[0]);
	if (prevp) updateModelCount(prevp);
}

function delModelSection(secid) {
  var sec=$(secid);
  if (!sec) return;
  var p=sec.parentNode;
  p.removeChild(sec);
  modelAccordion.adjustPanelHeight();
  updateModelCount(p);
  delete(modelSections[secid]);
}

function updateModelCount(secs,hd) {
  var ct=0;
  var mdls=[];
  if (!hd) hd=secs.parentNode.parentNode.getElementsByTagName('div')[0];
  for (var e=secs.firstChild;e;e=e.nextSibling) if (e.tagName=='DIV') mdls[ct++]=e;
  setField(hd,'count',ct);
  if (secs.grpImageTm) clearTimeout(secs.grpImageTm);
  secs.grpImageTm=setTimeout(function() {
    var imgs=[];
    for (var i=0;mdls[i];i++) {
      var imf=getInputValue(mdls[i],'model_image_ptr[]');
      if (imf) {
	var imd=imf.split('/');
	for (var j=0;imd[j]!=null;j++) if (imgs[j]==undefined) imgs[j]=imd[j]; else if (imgs[j]!=imd[j]) imgs[j]='multiple:';
      }
    }
    for (var i=0;imgs[i]!=null;i++) setModelImage(hd,i,imgs[i]);
  },500);
  if (ct==0) {
    $('modelAccordion').removeChild(hd.parentNode);
    for (var e=$('modelAccordion').firstChild;;e=e.nextSibling) {
      if (!e) {
        setGlobalFieldsDisplay(true);
        break;
      }
      if (e.tagName=='DIV') break;
    }
  } else setGlobalFieldsDisplay(false);
}

function reloadAllModels() {
  for (var mid in modelSections) attachModelSection($(mid),modelSections[mid]);
}

function delThisModel(rf) {
  while (rf) {
    if (rf.tagName=='DIV' && rf.id) {
      boxConfirm('Delete the model?',function() { delModelSection(rf.id); });
      return;
    }
    rf=rf.parentNode;
  }
}

function makeSelectElement(selid,vals,val) {
  if (!vals) return 'n/a';
  var sel='<select style="width:130px" id="'+selid+'" name="'+selid+'"><option value=""'+(val==null?' selected':'')+'>-Select Option-<\/option>';
  for (k in vals) sel+='<option value="'+k+'"'+(val==k?' selected':'')+'>'+vals[k]+'<\/option>';
  sel+='<\/select>';
  return sel;
}

function showOptionSelectors(boxid,vals) {
  var newoptnsel=boxid+'_new_optn';
  var html='<table width="100%" cellpadding="5" cellspacing="0" style="padding:10px 0 10px 10px">';
  for (var i=0;currOptions[i]!=undefined;i++) {
    var selid=boxid+'_attr['+currOptions[i]+']';
    var selval=(vals && vals[currOptions[i]]!=undefined)?vals[currOptions[i]]:($(selid)?$(selid).value:'');
    html+='<tr><td style="padding:4px;"><a href="javascript:void(0)" onClick="return delOptionSelector(\''+currOptions[i]+'\');"><img src="images\/delete.png" title="Remove Values from Attribute combo"></a></td>'
	+'<td>'+optnNames[currOptions[i]]+'</td>'
	+'<td>'+makeSelectElement(selid,attrValues[currOptions[i]],selval)+'<input type="hidden" name="options_sort_order['+currOptions[i]+']" value="'+i+'"><\/td>'
	+'<td><input type="checkbox" name="options_primary['+currOptions[i]+']" value="1"'+(primaryOptions[i]?' checked':'')+' onClick="primaryOptions['+i+']=this.checked; reloadAllModels();"><\/td>'
	+'<td><a href="javascript:void(0)" onClick="return addOptionValue(\''+currOptions[i]+'\');"><img src="images\/icon_add_new.png"><\/a><a href="javascript:void(0)" onClick="return delOptionValue(\''+currOptions[i]+'\',$(\''+boxid+'_attr['+currOptions[i]+']\').value);"><img src="images/icon_delete.png" title="Delete Value from Attribute Array" alt=""><\/a></td></tr>';
  }
  html+='<table width="100%" height="35" cellspacing="0" cellpadding="0" style="margin:0 0 10px 9px; background-color:#B8DB95; border:dashed 1px #999"><tr><td style="padding:0 7px 0 4px"><a href="javascript:void(0)" onClick="return addOptionSelector($(\''+newoptnsel+'\').value);"><img src="images\/go-up.png" title="Add attribute to model" alt=""><\/a><\/td><td style="padding:0 3px 0 0">Name:<\/td><td colspan="2">'+makeSelectElement(newoptnsel,optnNames)+'<\/td><td style="padding:0 5px 0 21px"><a href="javascript:void(0)" onClick="return addOption();"><img src="images\/icon_add_new.png" title="Add Attribute to Array" alt=""></a><a href="javascript:void(0)" onClick="return delOption($(\''+newoptnsel+'\').value);"><img src="images\/icon_delete.png" title="Delete Attribute from Array" alt=""><\/a><\/td><\/tr><\/table>';

	//$(boxid).innerHTML=html;
	jQuery("#"+boxid).html(html);

}

function addOptionSelector(optn) {
  if (optn=='') return false;
  for (var i=0;currOptions[i]!=undefined;i++) if (currOptions[i]==optn) return false;
  currOptions.push(optn);
  showOptionSelectors('new_model_attrs');
  return false;
}

function delOptionSelector(optn,onsuccess) {
  var todel=new Array();
  var fst=currOptions[0]==optn;
  for (var secid in modelSections) if (fst || modelSections[secid][optn]!=undefined) todel.push(secid);
  var fn2=function() {
    for (var j=0;todel[j];j++) delModelSection(todel[j]);
    for (var j=0;currOptions[j];j++) if (currOptions[j]==optn) currOptions.splice(j--,1);
    showOptionSelectors('new_model_attrs');
    if (onsuccess) onsuccess();
  };
  if (todel.length>0) boxConfirm('This will delete '+todel.length+' models. Do you want to proceed?',fn2);
  else fn2();
}

function addNewModel(prfx) {
  var attrs=new Array();
  var flds={};
  for (var i=0;currOptions[i]!=undefined;i++) {
    attrs[i]=$(prfx+'_model_attrs_attr['+currOptions[i]+']').value;
    if (attrs[i]=='') return boxAlert('Please select values for all attributes');
  }
  for (var i=0;modelFieldList[i]!=undefined;i++) flds[modelFieldList[i]+'[]']=$(prfx+'_'+modelFieldList[i]).value;
  var up=$(prfx+'_upload_model_image');
  var img;
  if (up.value!='') img='upload:'+attachModelImageUpload(up);
  else {
    for (var secid in modelSections) if (modelSections[secid][currOptions[0]]==attrs[0]) {
      if (img=getInputValue($(secid),'model_image_ptr[]')) break;
    }
  }
  if (!img) for (var im in savedImages) if (img=im) break;
  if (!img) for (var secid in modelSections) if (img=getInputValue($(secid),'model_image_ptr[]')) break;
  flds['model_image_ptr[]']=img;
  flds['model_pricing_list[]']='0:';
  if (addModel(currOptions,attrs,flds)) {
    for (var i=0;currOptions[i]!=undefined;i++) $(prfx+'_model_attrs_attr['+currOptions[i]+']').options[0].selected=true;
  } else boxAlert('This model already exists');
}

function addOptionValue(optn) {
  createPromptBox($('addOptionValueBox'),$('modelManager'),{optn_name:optnNames[optn]},function(val) {
    if (val!=null && val.match(/\S/)) callAttrFeed('add_attr='+optn+':'+escape(val));
  });
}

function delOptionValue(optn,attr) {
  if (attr=='') return false;
  var todel=new Array();
  for (var secid in modelSections) if (modelSections[secid] && modelSections[secid][optn]==attr) todel.push(secid);
  boxConfirm('Delete value "'+attrValues[optn][attr]+'" from option "'+optnNames[optn]+'"?',
    function() { todel.length>0?boxConfirm('This operation will delete '+todel.length+' models of the current product',function() {delOptionValueGo(optn,attr,todel);}):delOptionValueGo(optn,attr)}
  );
}
function delOptionValueGo(optn,attr,todel) {
  if (todel) for (var j=0;todel[j];j++) delModelSection(todel[j]);
  callAttrFeed('del_attr='+optn+':'+attr);
}

function addOption() {
  createPromptBox($('addOptionBox'),$('modelManager'),{},function(val) {
    if (val!=null && val.match(/\S/)) callAttrFeed('add_optn='+escape(val));
  });
}

function delOption(optn) {
  if (optn=='') return false;
  boxConfirm('Delete option "'+optnNames[optn]+'"?',function() { delOptionSelector(optn,function() {delOptionGo(optn);}); });
}

function delOptionGo(optn) {
  callAttrFeed('del_optn='+optn);
}


function callAttrFeed(rq) {
  new ajax(attrFeedUrl,{ postBody:rq, onComplete: parseAttrFeed });
}

function getXmlValue(xml,key) {
  var el=xml.getElementsByTagName(key)[0];
  if (el) el=el.firstChild;
  return el?el.nodeValue:null;
}

function makeAttrUsedWarning(xml) {
  var prods=new Array();
  var prd=xml.getElementsByTagName('product');
  for (var i=0;prd[i];i++) prods.push(getXmlValue(prd[i],'id')+' '+getXmlValue(prd[i],'name')+' ('+getXmlValue(prd[i],'count')+')');
  if (prods.length>0) return 'The attribute is used by the following products:\n'+prods.join('\n');
  return '';
}

function parseAttrFeed(req) {
  if (!req.responseXML) return;
  var rsp=req.responseXML.getElementsByTagName('attrctl')[0];
  if (!rsp) return;
  var setval={};
  var imgr=rsp.getElementsByTagName('attribute_images');
  if (imgr[0]) {
    var optn=getXmlValue(imgr[0],'optn_id');
    var attr=getXmlValue(imgr[0],'attr_id');
    if (!attrPickImages[optn]) attrPickImages[optn]={};
    if (!attrPickImages[optn][attr]) attrPickImages[optn][attr]={};
    var imgs=imgr[0].getElementsByTagName('image');
    for (var i=0;imgs[i];i++) {
      var im='file:'+getXmlValue(imgs[i],'name');
      var itag=getXmlValue(imgs[i],'tag');
      attrPickImages[optn][attr][im]=itag;
      if (!savedAttrImages[im]) savedAttrImages[im]=itag;
    }
    fillAttrImageIcons(optn,attr);
    return;
  }
  var attrsc=rsp.getElementsByTagName('attribute');
  for (var i=0;attrsc[i];i++) {
    var rs=getXmlValue(attrsc[i],'result');
    var optn=getXmlValue(attrsc[i],'optn_id');
    var attr=getXmlValue(attrsc[i],'attr_id');
    var attrn=getXmlValue(attrsc[i],'attr_name');
    if (rs=='added') {
      attrValues[optn][attr]=attrn;
      setval[optn]=attr;
    } else if (rs=='deleted') delete (attrValues[optn][attr]);
    else boxAlert('Error: attribute "'+attrn+'"\n'+makeAttrUsedWarning(attrsc[i]));
  }
  var optnsc=rsp.getElementsByTagName('option');
  for (var i=0;optnsc[i];i++) {
    var rs=getXmlValue(optnsc[i],'result');
    var optn=getXmlValue(optnsc[i],'optn_id');
    var optname=getXmlValue(optnsc[i],'optn_name');
    if (rs=='added') {
      optnNames[optn]=optname;
      if (!attrValues[optn]) attrValues[optn]={};
      addOptionSelector(optn);
    } else if (rs=='deleted') {
      delete (attrValues[optn]);
      delete (optnNames[optn]);
    } else boxAlert('Error: attributes for option "'+optnNames[optn]+'"\n'+makeAttrUsedWarning(optnsc[i]));
  }
  showOptionSelectors('new_model_attrs',setval);
}

function attachModelImageUpload(elmnt) {
  var up,idx;
  for (idx=0;up=$('upload_model_image_'+idx);idx++) if (up.value==elmnt.value) return idx;
  var lastblk=$('upload_model_image_block_'+(idx-1));
  var newblk=lastblk.cloneNode(true);
  newblk.id='upload_model_image_block_'+idx;
  var inps=newblk.getElementsByTagName('input');
  for (var i=0;inps[i];i++) {
    if (inps[i].type=='file') {
      var ffld=inps[i];
      var elcpy=elmnt.cloneNode(true);
      elmnt.parentNode.insertBefore(elcpy,elmnt);
      elmnt.name=elmnt.id='upload_model_image_'+idx;
      ffld.parentNode.insertBefore(elmnt,ffld);
      ffld.parentNode.removeChild(ffld);
    }
    if (inps[i].name=='select_model_image') {
      inps[i].value='upload:'+idx;
      inps[i].checked=false;
    }
  }
  lastblk.parentNode.insertBefore(newblk,lastblk.nextSibling);
  return idx;
}

function selectModelImage(obj,idx) {
  var div=obj;
  while (div && (div.tagName!='DIV' || !div.id)) div=div.parentNode;
  if (div) return selectImage([div],idx);
}

function selectGroupImage(obj,idx) {
  var div=obj;
  while (div && (div.tagName!='DIV')) div=div.parentNode;
  while ((div=div.nextSibling) && (div.tagName!='DIV'));
  var divs=[];
  for (var d=div.getElementsByTagName('div')[0].firstChild;d;d=d.nextSibling) if (d.tagName=='DIV') divs.push(d);
  return selectImage(divs,idx);
}

function selectImage(divs,idx) {
  var imgflds=[];
  for (var i=0;divs[i];i++) {
    var inps=divs[i].getElementsByTagName('input');
    for (var j=0;inps[j];j++) if (inps[j].name=='model_image_ptr[]') { imgflds[i]=inps[j]; break; }
  }
  var grpimg=null;
  var gdiff=false;
  for (var i=0;imgflds[i];i++) {
    var im=imgflds[i].value.split('/')[idx];
    if (grpimg==null) grpimg=im; else if (grpimg!=im) gdiff=true;
  }
  setInputs($('selectModelImage'),{select_model_image:grpimg});
  $('selectModelImageDiffWarning').style.display=gdiff?'':'none';
  attachModelImageUpload($('upload_model_image_new'));
  createPromptBox($('selectModelImage'),$('modelManager'),{},function(radio) {
    if (radio) {
      for (var i=0;radio[i];i++) if (radio[i].checked) {
	for (var j=0;imgflds[j];j++) {
  	  var imgs=imgflds[j].value.split('/');
	  while (imgs.length<idx) imgs.push('default:');
	  imgs.splice(idx,1,radio[i].value);
	  imgflds[j].value=imgs.join('/');
          setModelImage(divs[j],idx,radio[i].value);
        }
      }
      updateModelCount(divs[0].parentNode);
    }
  });
}

function selectAttrImage(optn,attr,obj) {
  var pos=findPos(obj);
  var blk=$('selectAttrImage');
  var upid='upload_attr_image_'+optn+'_'+attr;
  if (!$(upid)) {
    var upl=$('upload_attr_image_new').cloneNode(true);
    upl.id=upl.name=upid;
    $('upload_attr_image_new').parentNode.insertBefore(upl,$('upload_attr_image_new'));
  }
  var upl=$(upid);
  for (var obj=upl.parentNode.firstChild;obj;obj=obj.nextSibling) if (obj.tagName=='INPUT' && obj!=upl) obj.style.display='none';
  upl.style.display='';
  blk.style.position='absolute';
  blk.style.left=pos.x+'px';
  blk.style.top=(pos.y+16)+'px';
  blk.currOptn=optn;
  blk.currAttr=attr;
  if (!fillAttrImageIcons(optn,attr)) {
    setField($('selectAttrImage'),'img_icons','loading...');
    callAttrFeed('get_attr_imgs='+escape(optn)+':'+escape(attr));
  }
  blk.style.display='';
}

function fillAttrImageIcons(optn,attr) {
  if (!attrPickImages[optn] || !attrPickImages[optn][attr]) return false;
  icontbl=[];
  for (var im in attrPickImages[optn][attr]) icontbl.push('<td onClick="selectAttrImageDone(\''+im.replace(/['\\]/,'\\$1')+'\');">'+attrPickImages[optn][attr][im]+'<\/td>');
  setField($('selectAttrImage'),'img_icons','<table border="0"><tr>'+icontbl.join('')+'<\/tr><\/table>');
  return true;
}

function selectAttrImageDone(im) {
  if (im==null) im='upload:'+$('selectAttrImage').currOptn+'_'+$('selectAttrImage').currAttr;
  $('selectAttrImage').style.display='none';
  setAttrImage($('selectAttrImage').currOptn,$('selectAttrImage').currAttr,im);
}

function setAttrImage(optn,attr,im) {
  var fid='attr_image_ptr_'+optn+'_'+attr;
  if (!$(fid)) {
    var f=document.createElement('input');
    f.type='hidden';
    f.id=fid;
    f.name='attr_image['+optn+'_'+attr+']';
    $('selectAttrImage').insertBefore(f,null);
  }
  $(fid).value=im;
  for (var m in modelSections) if (modelSections[m] && modelSections[m][optn]==attr) setField($(m),'attr_image_'+optn+'_'+attr,makeAttrImage(im));
}

function makeAttrImage(im) {
  if (savedAttrImages[im]) return savedAttrImages[im];
  else if (im.match(/^upload:/)) return '[up]';
  else return '[+]';
}

function showPricingBox(obj) {
  while (obj && obj.tagName!='DIV') obj=obj.parentNode;
  var pbox=$('modelPricingBox');

  var grplst=<?php echo tep_js_quote(tep_get_customer_groups())?>;
  var prl=String(getInputValue(obj,'model_pricing_list[]')).split(';');
  var prg={};
  for (var i=0;prl[i]!=undefined;i++) {
    var prlg=prl[i].split(',');
    var gp;
    if (prlg[0] && (gp=prlg[0].split(':'))) {
      var cgrp=gp[0];
      prg[cgrp]={price:gp[1],dics:[]};
      for (var j=1;prlg[j]!=undefined;j++) {
        var gp=prlg[j].split(':');
	if (gp[0] && gp[1]) prg[cgrp].dics.push({q:gp[0],v:gp[1]});
      }
    }
  }
  if (!prg[0]) prg[0]={price:0,disc:[]};
  prg[0].price=getInputValue(obj,'model_price[]')*(getInputValue(obj,'model_price_sign[]')=='-'?-1:1);
  for (var cgrp in grplst) {
    var gbox=$('modelPricingBoxGrp_'+cgrp);
    if (!gbox) {
      gbox=$('modelPricingBoxGrp').cloneNode(true);
      gbox.id='modelPricingBoxGrp_'+cgrp;
      gbox.style.display=cgrp!=0?'none':'';
      $('modelPricingBoxGrp').parentNode.insertBefore(gbox,null);
    }
    var p=prg[cgrp];
    if (!p) p={price:0,dics:[]};
    gbox.getElementsByTagName('table')[0].style.display=prg[cgrp]?'':'none';
    var pflds={mgrp_allow:(prg[cgrp]?1:0),mgrp_price_sign:(p.price<0?'-':'+'),mgrp_price_value:Math.abs(p.price)};
    for (var i=1;i<=10;i++) {
      var gp=p.dics[i-1];
      if (!gp) gp={q:'',v:''};
      pflds['mgrp_dq['+i+']']=gp.q;
      pflds['mgrp_dv['+i+']']=gp.v;
    }
//    var slop=$('model_switch_pricing_group').getElementsByTagName('option');
//    for (var i=0;slop[i];i++) slop[i].style.display=grplst[slop[i].value]!=undefined?'':'none';
//    slop[0].selected=true;
    setInputs(gbox,pflds);
  }
  createPromptBox(pbox,$('modelManager'),{},function(flg) {
    if (!flg) return;
    var prl=new Array();
    for (var cgrp in grplst) {
      var gbox=$('modelPricingBoxGrp_'+cgrp);
      if (cgrp && !getInputValue(gbox,'mgrp_allow')) continue;
      var price=getInputValue(gbox,'mgrp_price_value')*(getInputValue(gbox,'mgrp_price_sign')=='-'?-1:1);
      if (cgrp==0) setInputs(obj,{'model_price[]':Math.abs(price),'model_price_sign[]':(price<0?'-':'+')});
      var ql=[cgrp+':'+price];
      for (var i=1;;i++) {
        var q=getInputValue(gbox,'mgrp_dq['+i+']');
	if (q==null) break;
        var v=getInputValue(gbox,'mgrp_dv['+i+']');
	if (q && v && !isNaN(q) && !isNaN(v)) ql.push(q+':'+v);
      }
      prl.push(ql.join(','));
    }
    setInputs(obj,{'model_pricing_list[]':prl.join(';')});
  });
}

function setModelImage(obj,idx,imgp) {
  setField(obj,'model_image_'+idx,savedImages[imgp]?savedImages[imgp]:'[upload]');
}

function setFeeds(pid) {

	if(pid) { 
		pid = pid.getAttribute('id');
		pid = pid.replace('theFeed_', '');
		pid = parseInt((pid * 1));
		//alert(pid);
	}
	createPromptBox($('modelSetFeeds_'+pid),$('modelManager'),{},function(flg) {
	});
}


function returnPromptBox(box,val) {
  while (box) {
    if (box.fnReturnPromptBox) break;
    box=box.parentNode;
  }
  if (!box) return false;
  box.fnReturnPromptBox(val);

  contentChanged();

  return false;
}

function createPromptBox(box,blk,flds,func) {
	if (box.fnReturnPromptBox) return false;
	if (!blk) blk=box.parentNode;
	var blkpos=findPos(blk);
	var blkwd=blk.scrollWidth;
	var blkht=blk.scrollHeight;
	var idprfx=box.id+'_';

		
 	// cover the attribute manager with a semi tranparent div
 	newBit = blk.appendChild(document.createElement("div"));
 	newBit.id = idprfx+"blackout";
 	newBit.className = "blackout";
 	newBit.style.height = blkht;
 	newBit.style.width = blkwd;
 	newBit.style.left = blkpos.x;
 	newBit.style.top = blkpos.y;
	newBit.style.position='absolute';
 	
 	// hide select boxes (for IE)
	showHideSelectBoxes(blk,'hidden'); 
	
	// create a popup shaddow
	popupShadow = blk.appendChild(document.createElement("div"));
	popupShadow.id = idprfx+"popupShadow";
	popupShadow.className = "popupShadow";
	
	// create the contents div
	box.className = "popupContents";
	
	for (var fld in flds) setField(box,fld,flds[fld]);
	box.style.position = "absolute";
	box.style.visibility = "hidden";
	box.style.display = "block";
	
	// work out the center postion for the box
	var leftPos = (((blkwd - box.scrollWidth) / 2) + blkpos.x);
	var scrOfX = 0, scrOfY = 0;
	var topPos = (((blkht - box.scrollHeight) / 2) + blkpos.y);

if (window.theEvent) {
topPos = theEvent.clientY+this.offsetfromcursorY;
}
this.offsetfromcursorY=-30;

    	
	// position the box
	box.style.left = leftPos;
	box.style.top = topPos;
	box.style.visibility = "visible";
	
	// size the shadow
	popupShadow.style.width = box.scrollWidth;
	popupShadow.style.height = box.scrollHeight;
	popupShadow.style.opacity = ".50";
	
	// position the shadow
	popupShadow.style.left = leftPos+2;
	popupShadow.style.top = topPos+2;

	// if the form has any inputs focus on the first one
	inputs = box.getElementsByTagName("input");

	for (var ii=0;inputs[ii];ii++) if (inputs[ii].type=='button') { 
		alert(inputs[ii].type); inputs[ii].focus(); break; 
	}

	box.fnReturnPromptBox = function(val) { 
		removePromptBox(this,blk); if (func) func(val); 
	}
	
	return false;
}

function updatePromptBox(box) {
  var sh=$(box.id+'_'+'popupShadow');
  if (!sh) return;
  sh.style.width=box.offsetWidth+'px';
  sh.style.height=box.offsetHeight+'px';
}

function removePromptBox(box,blk) {
	box.fnReturnPromptBox=undefined;
	var idprfx=box.id+'_';
	box.style.display='none';
	blk.removeChild($(idprfx+"popupShadow"));
	blk.removeChild($(idprfx+"blackout"));
	showHideSelectBoxes(blk,'visible');	
}

function findPos(obj) {
	var pos={x:0,y:0};
	if (obj.offsetParent){
		while (obj.offsetParent) {
			pos.x += obj.offsetLeft;
			pos.y += obj.offsetTop;
			obj = obj.offsetParent;
		}
	}
	else {
		if (obj.x) pos.x += obj.x;
		if (obj.y) pos.y += obj.y;
	}
	return pos;
}

function showHideSelectBoxes(blk,vis) {
	var selects = blk.getElementsByTagName("select");
	for(var i = 0; i < selects.length; i++) 
		selects[i].style.visibility = vis;
	return false;
}

</script>
