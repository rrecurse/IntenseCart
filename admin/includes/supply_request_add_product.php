<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('application_top.php');

	require(DIR_FS_CLASSES.'currencies.php');
	$currencies = new currencies();
	require(DIR_FS_CATALOG_CLASSES.'PriceFormatter.php');

	$sID = $_GET['sID'];
	require(DIR_FS_CLASSES . 'supply_request.php');
	$supply_request = new supply_request($sID);

	$add_category_id = (isset($_GET['add_category_id'])) ? $_GET['add_category_id'] : '';
	$add_product_id = (isset($_GET['add_product_id'])) ? $_GET['add_product_id'] : 0;

//error_log(print_r('the product id - ' . $add_product_id,TRUE));

	$cat_tree = array();
	$cat_query = tep_db_query("SELECT c.categories_id,c.parent_id,cd.categories_name 
							   FROM ".TABLE_CATEGORIES." c, ".TABLE_CATEGORIES_DESCRIPTION." cd 
							   WHERE c.categories_id = cd.categories_id AND cd.language_id='$languages_id' 
							   ORDER BY c.sort_order
							  ");

	while($cat_data=tep_db_fetch_array($cat_query)) {

		if(!isset($cat_tree[$cat_data['parent_id']])) {
			$cat_tree[$cat_data['parent_id']] = array();
		}

		$cat_tree[$cat_data['parent_id']][] = array(id => $cat_data['categories_id'], text => $cat_data['categories_name']);
	}

function build_cat_pull_down($cat_tree,$cat,$pr) {

	$rs = array();

	foreach($cat_tree[$cat] AS $cat_info) {
    	$rs[] = array(id=>$cat_info['id'],text=>$pr.$cat_info['text']);
	    if(isset($cat_tree[$cat_info['id']])) { 
			$rs=array_merge($rs,build_cat_pull_down($cat_tree,$cat_info['id'],$pr.'. '));
		}
	}

  return $rs;

}


  $cat_pull_down = array_merge(array(array(id=>'',text=>'--Select Category--'), array(id=>'non_inv',text=>'[Non-Inventory]')), build_cat_pull_down($cat_tree,0,''));

?>

<table cellpadding="5" cellspacing="0" border="0">
	<tr>
		<td width="50" style="text-align:right; padding:0 5px; font:bold 12px arial;">Select Product:</td>
		<td align="left"><?php echo tep_draw_pull_down_menu('add_category_id', $cat_pull_down, $add_category_id, 'size="8" onChange="ReloadAddProduct(this.value);"')?>
  </td>

<?php

	$show_submit = 0;

if($add_category_id) { 

	$supplier = $supply_request->supplier['suppliers_id'];

    $products_query = tep_db_query("SELECT pd.products_id,
										   pd.products_name,
										   spg.suppliers_group_price as products_price
									FROM ". TABLE_PRODUCTS_TO_CATEGORIES ." p2c
									LEFT JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON  p2c.products_id = pd.products_id  AND pd.language_id = '".$languages_id."'
									LEFT JOIN ". TABLE_PRODUCTS ." p ON pd.products_id = p.products_id
									LEFT JOIN suppliers_products_groups spg ON spg.products_id = p.products_id
									WHERE p2c.categories_id='".$add_category_id."'
									AND suppliers_group_id = ".$supplier."
									AND p.products_status = 1
									AND p.products_price > 0
									ORDER BY pd.products_name
									");
	$products_pull_down = array(array(id=>'',text=>'--Select Product--'));

	while($products = tep_db_fetch_array($products_query)) {
		$products_pull_down[] = array(id=>$products['products_id'],text=>$products['products_name']);
    }
?>
<td style="min-width:295px">
  <?php echo tep_draw_pull_down_menu('add_product_select', $products_pull_down, $add_product_id, 'size="8" onChange="ReloadAddProduct(\''.$add_category_id.'\',this.value);" style="width:100%;"')?>
</td>
<?php
} else { 

echo '<td style="min-width:295px"><select style="width:100%;" size="8" disabled><option selected="" value=""><--Select Category First</option></select></td>';

}

	$attr_data_js = array();
		if($add_product_id) {
			$show_submit=1;
			$product_query = tep_db_query("SELECT p.*, spg.suppliers_group_price as products_price
										   FROM ". TABLE_PRODUCTS ." p
										   LEFT JOIN suppliers_products_groups spg ON spg.products_id = p.products_id
										   WHERE p.products_id='$add_product_id'
										  ");
			$product = tep_db_fetch_array($product_query);
		
	    $attr_boxes = array();
	    $attr_query = tep_db_query("SELECT pa.options_id,
											pa.options_values_id,
											po.products_options_name,
											pov.products_options_values_name,
											pa.options_values_price,pa.
											price_prefix 
									FROM ".TABLE_PRODUCTS_ATTRIBUTES." pa 
									LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." po ON pa.options_id=po.products_options_id AND po.language_id='$languages_id' 
									LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." pov ON pa.options_values_id=pov.products_options_values_id AND pov.language_id='$languages_id' 
									WHERE pa.products_id='$add_product_id' 
									ORDER BY pa.products_attributes_id
							   ");

	while($attr_data=tep_db_fetch_array($attr_query)) {

		if (!isset($attr_boxes[$attr_data['options_id']])) {
			$attr_boxes[$attr_data['options_id']] = array(text => $attr_data['products_options_name'],
														  sel => array(),
														  js_name => array(),
														  js_cost => array()
														  );
		}
    	
		$op_val_idx = sizeof($attr_boxes[$attr_data['options_id']]['sel']);
      	

		if($attr_data['options_values_price'] != 0) { 

			$attr_boxes[$attr_data['options_id']]['sel'][$op_val_idx] = array(id => $op_val_idx, text => $attr_data['products_options_values_name'].sprintf(' (%s%.2f)',$attr_data['price_prefix'],$attr_data['options_values_price']));
		
		} else { 

			$attr_boxes[$attr_data['options_id']]['sel'][$op_val_idx] = array(id => $op_val_idx, text => $attr_data['products_options_values_name']);
		}


	//	$attr_boxes[$attr_data['options_id']]['sel'][$op_val_idx] = array(id=>$op_val_idx,text=>$attr_data['products_options_values_name'].($attr_data['options_values_price']!=0?sprintf(' (%s%.2f)',$attr_data['price_prefix'],$attr_data['options_values_price']):''));

      $attr_boxes[$attr_data['options_id']]['js_name'][$op_val_idx]="'".addslashes($attr_data['products_options_values_name'])."'";
      $attr_boxes[$attr_data['options_id']]['js_cost'][$op_val_idx]="'".(($attr_data['options_values_price_prefix']=='-') ? 0-$attr_data['options_values_price'] : 0+$attr_data['options_values_price'])."'";
    }
    if (sizeof($attr_boxes)) {
		echo '<table>';

	foreach($attr_boxes AS $attr_op_id=>$attr_box) {
?>
		<tr><td><?php echo $attr_box['text']?></td>
			<td><?php echo tep_draw_pull_down_menu('add_product_attr_'.$attr_op_id,$attr_box['sel'],'',' id="add_product_attr_'.$attr_op_id.'"')?>
<?php
        $attr_data_js[]="{option:'".addslashes($attr_box['text'])."', value:array(".join(",",$attr_box['js_name']).")[jQuery('#add_product_attr_$attr_op_id').val()], cost:array(".join(",",$attr_box['js_cost']).")[jQuery('#add_product_attr_$attr_op_id').val()] }";
      }
	
	echo '</table><hr>';

    }
}

echo '<td width="130" style="text-align:center; white-space:nowrap">';
  if ($show_submit) {

	echo '<table cellspacing="0" cellpadding="0" align="center" border="0" width="100%">
				<tr><td align="center">';

    $img_wd=64;
    $img_ht=80;

    $modelsObj = tep_block('blk_product_models');

    $modelsObj->pid=$product['products_id'];
    $modelsObj->products_name=$product['products_name'];

    $modelsObj->render(NULL);

    $imgs = array();
    foreach ($modelsObj->getImages() AS $img) {

		$imgs[$img]=tep_image_src($img,$img_wd,$img_ht);
	}

    list($img0)=$imgs;
   
	echo tep_image($img0,$products['products_name'],$img_wd,$img_ht,'id="add_product_image"');

	echo '<div style="padding:10px 0 0 0">';

    $att=tep_block('blk_attr_select_pulldn');
    $att->setContext(Array('models'=>$modelsObj),Array());
    $att->render(Array());
    $addr=new IXaddress(Array('country'=>$_REQUEST['country'],'state'=>$_REQUEST['state']));


	echo tep_draw_hidden_field('add_product_id',$add_product_id,' id="add_product_id"');
	echo tep_draw_hidden_field('add_product_name',tep_get_products_name($add_product_id),' id="add_product_name"');
	echo tep_draw_hidden_field('add_product_model',$product['products_model'],' id="add_product_model"');
	echo tep_draw_hidden_field('add_product_cost',$product['products_price'],' id="add_product_cost"');
	echo tep_draw_hidden_field('add_product_comments', '', ' id="add_product_comments"');
	echo tep_draw_hidden_field('add_product_tax_class',$product['products_tax_class_id'],' id="add_product_tax_class"');
	echo tep_draw_hidden_field('add_product_tax',tep_get_tax_rate($product['products_tax_class_id'],$addr->getCountryID(),$addr->getZoneID()),' id="add_product_tax"');
	echo tep_draw_hidden_field('add_product_weight',$product['products_weight'],' id="add_product_weight"');
	echo tep_draw_hidden_field('add_product_attrs', '',' id="add_product_attrs"');
?>
<span style="font:normal 11px arial">Qty:</span> <?php echo tep_draw_input_field('add_product_quantity',1,' id="add_product_quantity" size="1" style="text-align:center"')?> 
<span><button id="add_product_button" name="new_product_add" onclick="AddToSupplyRequest({id:jQuery('#add_product_id').val(), name:jQuery('#add_product_name').val(), model:jQuery('#add_product_model').val(), cost:jQuery('#add_product_cost').val(), comments:jQuery('#add_product_comments').val(), tax:jQuery('#add_product_tax').val(), tax_class:jQuery('#add_product_tax_class').val(),weight:jQuery('#add_product_weight').val()},jQuery('#add_product_quantity').val(),getNewProdAttrs()); return false;">Add</button></span>
</div>
<script type="text/javascript">
  <?php echo $modelsObj->jsObjectName()?>.pidElement=jQuery("#add_product_id");
  <?php echo $modelsObj->jsObjectName()?>.attrsElement=jQuery("#add_product_attrs");
  <?php echo $modelsObj->jsObjectName()?>.showCartButton=function(flg) {

	if(flg) { 
		jQuery('#add_product_button').show();
		jQuery('#add_product_button').css({'visibility':'visible'});
	} else {
		jQuery('#add_product_button').hide();
		jQuery('#add_product_button').css({'visibility':'hidden'});
	}
  };
  window.newProdSelObj={
    images:<?php echo tep_js_quote($imgs)?>,
    imageSwap:function(imgs) {

		jQuery("#add_product_image").attr('src',this.images[imgs[0]]);

    }
  };
  <?php echo $modelsObj->jsObjectName()?>.imageSwapObj.push(newProdSelObj);
  <?php echo $modelsObj->jsObjectName()?>.selectAttr();
	window.getNewProdAttrs=function() {
    
		var rs=[];
		var att = jQuery("#add_product_attrs").val().split(';');

		for(var i=0;att[i]!=null;i++) {
			var ats;
			if(ats=att[i].match(/(\d+):(\d+)/)) { 
				rs.push({option:<?php echo $modelsObj->jsObjectName()?>.optns[ats[1]].name, 
						 value:<?php echo $modelsObj->jsObjectName()?>.optns[ats[1]].values[ats[2]].name});
			}
    	}
		
		return rs;
	}

</script>

</td></tr>
</table>
</td></tr>
</table>

<?php
  }
?>
