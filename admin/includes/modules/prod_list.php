<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	// # for admin side Xsell / Cross selling list.
	// # requested from /admin/includes/modules/xsellctl.php

	// # generates the select list with models after product selection.

	require('../application_top.php');

	$catid = isset($HTTP_GET_VARS['cat']) ? $HTTP_GET_VARS['cat']+0 : 0;
	$pid = isset($_GET['models']) ? $_GET['models']+0 : 0;
	$pulldn = array(array('id'=>'','text'=>'----Product----'));
	$imgs = array();

	$product_query = tep_db_query("SELECT pd.products_name, p.products_image, p.products_id, pg.customers_group_price
								   FROM ".TABLE_PRODUCTS_TO_CATEGORIES." p2c 
								   LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p2c.products_id = pd.products_id AND pd.language_id='$languages_id' 
								   LEFT JOIN ".TABLE_PRODUCTS." p ON p2c.products_id = p.products_id
								   LEFT JOIN " . TABLE_PRODUCTS_GROUPS ." pg ON pg.products_id = p.products_id
								   WHERE p2c.categories_id='$catid' 
								   AND p.products_status = 1
								   AND (p.products_price > 0 OR pg.customers_group_price > 0)
								   GROUP BY p.products_id
								   ORDER BY pd.products_name ASC
								  ");

	while ($row = tep_db_fetch_array($product_query)) {

		$pulldn[] = array('id' => $row['products_id'], 'text' => $row['products_name']);

		$imgs[$row['products_id']] = tep_image_src(DIR_WS_CATALOG_IMAGES.$row['products_image'],32,40);
		if ($row['products_id']==$pid) $pname=$row['products_name'];
	}
?>
&nbsp;
<script type="text/javascript">
	window.prod_list_imgs = <?php echo tep_js_quote($imgs);?>;
</script>

<?php echo tep_draw_pull_down_menu('prod_list',$pulldn,$_GET['models'],'onChange="if (this.value) prodSelected(this.value,this.options[this.selectedIndex].innerHTML,prod_list_imgs[this.value],'.tep_js_quote($catid).');"');?>

<?php

	if ($pid) {

    	$pulldn = array(array('id'=>'','text'=>'---Model---'));
	    $mdlimgs = array();

    	$product_query = tep_db_query("SELECT p.products_id,
											  p.products_image,
											  p.products_model,
											  ov.products_options_values_name 
										FROM ".TABLE_PRODUCTS." p 
										LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON p.products_id = pa.products_id 
										LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." ov ON ov.products_options_values_id = pa.options_values_id AND ov.language_id = ".(int)$languages_id." 
										WHERE master_products_id = ".$pid."
										ORDER BY pa.options_sort
									   ");
    $mdls = array();

	while ($row=tep_db_fetch_array($product_query)) {

		if(!isset($mdls[$row['products_id']])) {
			$mdls[$row['products_id']] = array('model'=>$row['products_model'],'attrs' => array());
		}

		if($row['products_id']!=$pid) {
			$mdls[$row['products_id']]['attrs'][]=$row['products_options_values_name'];
		}

		$mdlimgs[$row['products_id']] = tep_image_src(DIR_WS_CATALOG_IMAGES.$row['products_image'],32,40);
    }

	asort($mdls);
    foreach ($mdls AS $pid=>$mdl) {
		$pulldn[]=Array('id'=>$pid,'text'=>$mdl['attrs']?join('/',$mdl['attrs']):'[default]');
	}
?>

<script language="javascript">
	window.prod_list_model_imgs=<?=tep_js_quote($mdlimgs)?>;
</script>

<?php echo tep_draw_pull_down_menu('prod_list_model',$pulldn,'','onChange="if (this.value) prodSelected(this.value,'.htmlspecialchars(tep_js_quote($pname)).'+(this.value=='.htmlspecialchars(tep_js_quote($pid)).'?\'\':\' - \'+this.options[this.selectedIndex].innerHTML),prod_list_model_imgs[this.value],'.htmlspecialchars(tep_js_quote($catid)).','.htmlspecialchars(tep_js_quote($pid)).'); this.options[0].selected=true;"')?>

<?php } ?>
