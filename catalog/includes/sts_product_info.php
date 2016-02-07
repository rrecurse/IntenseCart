<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


if(file_exists(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/product_info.php')){ 
	@include(DIR_FS_CATALOG_LAYOUT.'languages/'.$language.'/product_info.php');
}

// # This program is designed to build template variables for the product_info.php page template
// # This code was modified from product_info.php

$template['productid'] = $product_info['products_id'];
$template['productsid'] = $product_info['products_id'];


	// # Get product information from products_id parameter
	$product_info_query = tep_db_query("SELECT p.products_id, 
												pd.products_name, 
												pd.products_description, 
												pd.products_info, 
												p.products_model, 
												p.products_quantity, 
												p.products_image, 
												p.products_image_xl_1, 
												p.products_image_xl_2, 
												p.products_image_xl_3,  
												p.products_image_xl_4,  
												p.products_image_xl_5, 
												p.products_image_xl_6, 
												pd.products_url,  
												p.products_price,  
												p.products_tax_class_id,  
												p.products_date_added, 
												p.products_date_available,  
												p.manufacturers_id,  
												p.master_products_id,
												m.manufacturers_name
										FROM " . TABLE_PRODUCTS . " p 
										LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.master_products_id
										LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
										WHERE p.products_status = '1' 
										AND p.products_id = '" . (int)$_GET['products_id'] . "'
										AND pd.language_id = '" . (int)$languages_id . "'
										");

	$product_info = tep_db_fetch_array($product_info_query);

	$template['masterproductid'] = $product_info['master_products_id'];

	// # Separate Price per Customer
	if(!tep_session_is_registered('sppc_customer_group_id')) { 
		$customer_group_id = '0';
	} else {
		$customer_group_id = $sppc_customer_group_id;
	}
   // # END Separate Price per Customer


	// # Set last product viewed (LPV) session (for navigation)
	$lpv = ($_SERVER["HTTPS"] == "on" ? 'https://' : 'http://').$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

	if(!tep_session_is_registered('lpv')) {
		tep_session_register('lpv');
	}

// # END set LPV


	// # START Qty Price Break
	// # if switching global var $HTTP_GET_VARS[] for $_GET[] - 
	// # you will break the brice break display on product info.

	// # Load price breaks into $pf (price formatter)
	$pf->loadPrice((int)$HTTP_GET_VARS['products_id'], (int)$languages_id); 

	$template['lowprice'] = ($pf->lowPrice == 0 ? '' : $currencies->display_price($pf->lowPrice, tep_get_tax_rate($product_info['products_tax_class_id'])));

	$template['highprice'] = ($pf->hiPrice == 0 ? '' : $currencies->display_price($pf->hiPrice, tep_get_tax_rate($product_info['products_tax_class_id'])));

	// # Returns an array with the different price breaks. 
	// # Empty if no price break or special price exists.
	$template['pricebreaks'] = $pf->getPriceBreaks();

	// # END Qty Price Break

	$template['regularprice'] = $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));

	if($new_price = tep_get_products_special_price($product_info['products_id'])) {

		// # Separate Price per Customer

		$scustomer_group_price_query = tep_db_query("SELECT customers_group_price 
													FROM " . TABLE_PRODUCTS_GROUPS . "
													WHERE products_id = '" . (int)$_GET['products_id']. "' 
													AND customers_group_id =  '" . $customer_group_id . "'
													");
		
		if($scustomer_group_price = tep_db_fetch_array($scustomer_group_price_query)) {
			$new_price = $scustomer_group_price['customers_group_price'];
		}

		// # END Separate Price per Customer

	
		$template['regularpricestrike'] = "<s>" . $template['regularprice'] . "</s>";
		$template['specialprice'] = $currencies->display_price($new_price, tep_get_tax_rate($product_info['products_tax_class_id']));

	} else {

		// # Separate Price per Customer
   	    $scustomer_group_price_query = tep_db_query("SELECT customers_group_price 
													FROM " . TABLE_PRODUCTS_GROUPS . " 
													WHERE products_id = '" . (int)$_GET['products_id']. "' 
													AND customers_group_id =  '" . $customer_group_id . "'
													");
		if($scustomer_group_price = tep_db_fetch_array($scustomer_group_price_query)) {
			$template['regularprice']=  $currencies->display_price( $scustomer_group_price['customers_group_price'] , tep_get_tax_rate($product_info['products_tax_class_id']));
		}

		// # END Separate Price per Customer

		$template['specialprice'] = '';
		$template['regularpricestrike'] = $template['regularprice'];
	}

	$template['productname'] = $product_info['products_name'];
	$template['productmodel'] =  $product_info['products_model'];

	$image_width = MEDIUM_IMAGE_WIDTH;
	$image_height = MEDIUM_IMAGE_HEIGHT;

	// # No more image sizes!

	if (tep_not_null($product_info['products_image'])) {

		$template['imagesml'] = tep_image(DIR_WS_IMAGES . $product_info['products_image'], addslashes($product_info['products_name']), $image_width, $image_height, 'hspace="5" vspace="5" border=0 id="mainimage" alt=""');

		$template['imagesml_popup'] = '<a href="javascript:popupWindow(\'' . $product_info['products_id'] . '\');">' . $template['imagesml'] . '</a>';
  
	} else {

		$template['imagesml'] = tep_image(DIR_WS_IMAGES . 'no_image.gif', addslashes($product_info['products_name']), '100', '80', 'hspace="5" vspace="5" alt=""');

		$template['imagesml_popup'] = $template['imagesml'];
	}

	$template['imagemed']=&$template['imagesml'];
	$template['imagelrg']=&$template['imagesml'];


	// # See modules/product_images.php for image resizer

	$altImages = array();

	if($product_info['products_image_xl_1'] != '') {
		for($i=1;$i<=4;$i++) {
			if(!tep_not_null($product_info['products_image_xl_'.$i])) continue;
				$image_url = preg_replace('|.*?src="(.*?)".*|','$1',tep_image(($altImages[]=DIR_WS_IMAGES.$product_info['products_image_xl_'.$i]),'',$image_width,$image_height));

			$template['sml_image_row'] .= '&nbsp;<a href="javascript:void(0);" onMouseOver="swapImage(\'mainimage\',\'\',\''.$image_url.'\',1)">'. tep_image('images/' . $product_info['products_image_xl_'.$i], 'Product Views', ULT_THUMB_IMAGE_WIDTH, ULT_THUMB_IMAGE_HEIGHT, 'style="border:1px solid #000000"').' </a>';
			}
		} else {
		  $template['sml_image_row'] = '';
	}


if ($template['sml_image_row'] == '') {
  $bigpic = $product_info['products_image'];
} else {
  $bigpic = $product_info['products_image_xl_1'];
}


$product_description_string = $product_info['products_description'];
$tab_array = preg_match_all ("|<newtab>(.*)</newtab>|Us", $product_description_string, $matches, PREG_SET_ORDER); // <new_tab>

if ($tab_array){

 $desc .= '<div class="tab-pane" id="tabpane1">

  <script type="text/javascript">
  tp = new WebFXTabPane(document.getElementById("tabpane1"), false);
  </script>';

 for ($i=0, $n=sizeof($matches); $i<$n; $i++) {

  $this_tab_name = preg_match_all ("|<tabname>(.*)</tabname>|Us", $matches[$i][1], $tabname, PREG_SET_ORDER);

  if ($this_tab_name){

   $desc.='<div class="tab-page" id="tabPage' . $i . '">
    <h2 class="tab">' . $tabname[0][1] . '</h2>
    <script type="text/javascript">tp.addTabPage(document.getElementById("tabPage' . $i . '"));</script>
    <table border="0" cellspacing="0" cellpadding="2" align="left">
    <tr>
    <td width="100%">';

   if (preg_match_all ("|<tabpage>(.*)</tabpage>|Us", $matches[$i][1], $tabpage, PREG_SET_ORDER)){
    require($tabpage[0][1]);
   } elseif (preg_match_all ("|<tabtext>(.*)</tabtext>|Us", $matches[$i][1], $tabtext, PREG_SET_ORDER)){
    $desc.='<div class="boxTextMain">' . $tabtext[0][1] . '</div><br>';
   }
   $desc.='</td></tr></table></div>';
  }
 }
 $desc.='</div>';
 $template['productdesc']=$desc;
}else{
 $template['productdesc'] = stripslashes($product_info['products_description']); 
}

$template['productshortdesc'] = stripslashes($product_info['products_info']); 

// # Get the number of product attributes (the select list options)
$products_attributes_query = tep_db_query("SELECT COUNT(*) AS total 
										   FROM " . TABLE_PRODUCTS_OPTIONS . " po
										   LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON pa.options_id = po.products_options_id
										   WHERE pa.products_id='" . (int)$_GET['products_id'] . "' 
										   AND po.language_id = '" . (int)$languages_id . "'
										  ");
$products_attributes = tep_db_fetch_array($products_attributes_query);

// # If there are attributes (options), then...
if($products_attributes['total'] > 0) {
  // # Print the options header
  $template['optionheader'] = TEXT_PRODUCT_OPTIONS;

  // # Select the list of attribute (option) names
  $products_options_name_query = tep_db_query("select distinct popt.products_options_id, popt.products_options_name from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$_GET['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "' order by popt.products_options_name");


      $products_id=(preg_match("/^\d{1,10}(\{\d{1,10}\}\d{1,10})*$/",$_GET['products_id']) ? (int)$_GET['products_id'] : (int)$_GET['products_id']); 
      require(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN . '.php');
      $class = 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN;
      $pad = new $class($products_id);
      $template['optionchoices'] =  $pad->draw();
						$template['optionnames'] = '';
		
} else {
  // No options, blank out the template variables for them
  $template['optionheader'] = '';
  $template['optionnames'] = '';
  $template['optionchoices'] = '';
}


	$products_id =  preg_replace('/[^0-9]/i', '', tep_db_prepare_input($_GET['products_id']));


	$modelsObj = tep_block('blk_product_models');
	$modelsObj->pid = $products_id;
	$modelsObj->products_name = $product_info['products_name'];


// # See if there is a product URL
if (tep_not_null($product_info['products_url'])) {
  $template['moreinfolabel'] = 'Click here for more info on ' . $product_info['products_name'];
  $template['moreinfourl'] = tep_href_link(FILENAME_REDIRECT, 'action=url&goto=' . urlencode($product_info['products_url']), 'NONSSL', true, false); 
} else {
  $template['moreinfolabel'] = '';
  $template['moreinfourl'] = '';
}

$template['moreinfolabel'] = str_replace('%s', $template['moreinfourl'], $template['moreinfolabel']);

// # See if product is not yet available
if ($product_info['products_date_available'] > date('Y-m-d H:i:s')) {
  $template['productdatelabel'] = TEXT_DATE_AVAILABLE;
  $template['productdate'] = tep_date_long($product_info['products_date_available']);
} else {
  $template['productdatelabel'] = TEXT_DATE_ADDED;
  $template['productdate'] = tep_date_long($product_info['products_date_added']); 
}

// # Strip out %s values
$template['productdatelabel'] = str_replace('%s.', '', $template['productdatelabel']);

$template['addtocartbutton'] = tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART);

$template['quantityfield'] = tep_draw_input_field('quantity', '1', 'id="order_quantity" maxlength=2 size=2 onChange="'.$modelsObj->jsObjectName().'.displayPrice(); '.$modelsObj->jsObjectName().'.selectAttr();"');


$sts_blocks = array(array('name' => 'xsell', 'include' => DIR_WS_MODULES . FILENAME_XSELL_PRODUCTS),
                    array('name' => 'reviews', 'include' => DIR_WS_MODULES . 'product_reviews.php'),
                    array('name' => 'productimages', 'include' => DIR_WS_MODULES . 'product_images.php'),              
                    array('name' => 'productquestion', 'include' => DIR_WS_BOXES . 'product_question.php')
                    );
                    

foreach ($sts_blocks as $block) {
  $sts_block_name = $block['name'];
  require(STS_START_CAPTURE);
  include($block['include']);
  require(STS_STOP_CAPTURE);
  $template[$sts_block_name] = strip_unwanted_tags($sts_block[$sts_block_name], $sts_block_name);
}

// # temporary
class imageFk { 
	function getImages() {
		global $products_id,$altImages;
		$imgs = array();

		$qry = tep_db_query("SELECT products_image,
									products_image_xl_1,
									products_image_xl_2,
									products_image_xl_3,
									products_image_xl_4 
							FROM ".TABLE_PRODUCTS." 
							WHERE master_products_id = '".$products_id."'
							");

		while ($row=tep_db_fetch_array($qry)) {
			$imgs[$row['products_image']]=DIR_WS_IMAGES.$row['products_image'];
			for($i=1;$i<=4;$i++) { 
				$imgs[$row['products_image_xl_'.$i]]=DIR_WS_IMAGES.$row['products_image_xl_'.$i];
			}
		}

		return $imgs;
	}

	function imageSwapFunc() {
		return 'imageSwap';
	}
	
	function getNumSlots() {
		return 4;
		global $altImages;
		return 1+sizeof($altImages);
	}
} // # END class imageFK

	$imgobj = new imageFk();

	$imgs = array();

	foreach($imgobj->getImages() AS $img) {
		$imgs[$img]=tep_image_src($img,$image_width,$image_height);
	}

	$template['imagesml'] .= '

<script type="text/javascript">
	
	var prodImages='.tep_js_quote($imgs).';

	function '.$imgobj->imageSwapFunc().'(imgs) {
		if(prodImages[imgs[0]]) { 
			$("mainimage").src=prodImages[imgs[0]];
		}
	}
</script>';


	function tmpl_tag_product_models($tag) {
		global $modelsObj;
?>
<script type="text/javascript">
	function checkFormBuyNow(frm) {
		return <?=$modelsObj->jsObjectName()?>.buyNow(frm);
	}
</script>

<?php
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $modelsObj->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}

function tmpl_tag_product_models_fields($tag) {
  global $modelsObj;
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $obj=tep_block('blk_product_models_fields');
  $obj->setContext(Array('models'=>$modelsObj),Array());
  $obj->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}

function tmpl_tag_product_models_cart_button($tag) {
  global $modelsObj;
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $obj=tep_block('blk_product_models_cart_button');
  $obj->setContext(Array('models'=>$modelsObj),Array());
  $obj->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}


function tmpl_tag_image_scaler($tag) {
  global $modelsObj;
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $blk=tep_block('blk_image_scaler');
  $large=preg_match('/large/',$tag);
  $blk->setContext(Array('imageset'=>$modelsObj,'product'=>$modelsObj),Array('width'=>$large?LARGE_IMAGE_WIDTH:MEDIUM_IMAGE_WIDTH,'height'=>$large?LARGE_IMAGE_HEIGHT:MEDIUM_IMAGE_HEIGHT));
  $blk->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}

function tmpl_tag_attr_select_img($tag) {
  global $modelsObj;
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $blk=tep_block('blk_attr_select_img');
  $blk->setContext(Array('models'=>$modelsObj),Array('width'=>48,'height'=>32,'cols'=>0,'optn'=>preg_replace('/^.*_/','',$tag)));
  $blk->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}

function tmpl_tag_attr_select_pulldn($tag) {
  global $modelsObj;
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $blk=tep_block('blk_attr_select_pulldn');
  $blk->setContext(Array('models'=>$modelsObj),Array('optns'=>(preg_match('/_(\d+)$/',$tag,$tagp)?Array($tagp[1]=>$tagp[1]):NULL)));
  $blk->render(Array());
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}

function tmpl_tag_xsell_models($tag) {
  global $modelsObj;
  $sts_block_name = $tag;
  require(STS_START_CAPTURE);
  $blk=tep_block('blk_xsell');
  $tagp=NULL;
  preg_match('/xsell_models_(\d+)(_(\d+))?/',$tag,$tagp);
  $blk->setContext(Array('productset'=>$modelsObj),Array('cols'=>($tagp?$tagp[1]:XSELL_DISPLAY_COLS),'max'=>($tagp&&$tagp[3]?$tagp[3]:MAX_DISPLAY_ALSO_PURCHASED)));
  $blk->render(Array(Array('tag'=>'div','class'=>'blk_product')));
  require(STS_STOP_CAPTURE);
  return $sts_block[$tag];
}

?>