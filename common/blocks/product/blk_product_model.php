<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class blk_product_model extends IXblock {

  function jsObjectName() {
    return 'productModels_'.$this->makeID();
  }

  function renderOnce() {
?>

<?php
  }
  function render(&$body) {

    if (isset($this->context['product'])) { 
		$this->pid = $this->context['product']->getProductField('products_id');
		$this->pname = $this->context['product']->getProductField('products_name');
		$this->pmodel = $this->context['product']->getProductField('products_model');
		$this->pmid= $this->context['product']->getProductField('manufacturers_id');
		$this->pbrand = $this->context['product']->getProductField('manufacturers_name');
	}
		$this->listing = $this->context['root']->prod['products_model'];

	// # grab the category name for tracking

		$cat_name_query = tep_db_query("SELECT cd.categories_name
										FROM " . TABLE_CATEGORIES_DESCRIPTION . " cd
										LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES ." p2c ON p2c.categories_id = cd.categories_id
										LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = p2c.products_id
										WHERE p.products_id = ". $this->pid);

		if(tep_db_num_rows($cat_name_query) > 0) {

			$this->pcat = tep_db_result($cat_name_query,0);

		} else {

			$this->pcat = '';
		}

	//	tep_db_free_result($cat_name_query);

	if(empty($this->pbrand)) { 
		$manuf_name_query =	tep_db_query("SELECT  m.manufacturers_name
										  FROM " . TABLE_MANUFACTURERS . " m
										  WHERE m.manufacturers_id = ". 	$this->pmid);

		if(tep_db_num_rows($manuf_name_query) > 0) {
			$this->pbrand = tep_db_result($manuf_name_query,0);
		} else {
			$this->pbrand = '';
		}

		//tep_db_free_result($manuf_name_query);

	}

    $this->master = $this->args['detach'] ? NULL : $this->context['models'];
    $this->formFlg = !$this->args['noform'] && !$this->context['models'];
    $this->loadModels();
    list($first)=array_values($this->models);
    $selattr=isset($this->models[$this->pid])?$this->models[$this->pid]['attr']:($this->args['select_first']&&$first?$first['attr']:Array());

    if ($this->formFlg) echo '<form method="post" action="/shopping_cart.php?action=add_product" onsubmit="return '.$this->jsObjectName().'.buyNow(this,'.$this->jsObjectName().');" onclick="GAImpressionClick('.$this->jsObjectName().')">';
    $this->contentDivs=Array();
    $this->jsFields=Array();
    $this->jsFieldIdx=0;

?>
<script type="text/javascript">

window.<?php echo $this->jsObjectName()?>=new productModelObj({
  id:<?php echo tep_js_quote($this->makeID())?>,
  pid:<?php echo tep_js_quote($this->pid)?>,
<?php if (isset($this->master)) { ?>
  master:<?php echo $this->master->jsObjectName()?>,
<?php } ?>
  optns:<?php echo tep_js_quote($this->optns)?>,
  models:<?php echo tep_js_quote_array($this->models)?>,
<?php if (!empty($selattr)) { ?>
  currAttr:<?php echo tep_js_quote($selattr)?>,
<?php } ?>
  bkOrder:<?php echo (STOCK_ALLOW_CHECKOUT == 'true')?'true':'false'?>,
  pname: <?php echo tep_js_quote($this->pname);?>,
  pmodel: <?php echo tep_js_quote($this->pmodel);?>,
  pcat: <?php echo tep_js_quote($this->pcat);?>,
  pbrand: <?php echo tep_js_quote($this->pbrand);?>,
  listing: <?php echo tep_js_quote($this->listing);?>
});
	// # send the hit to GA function(s) located in display output
	// # currently sends hit for product_info details page - it should NOT.

	GAImpressionData(<?php echo $this->jsObjectName()?>);
	GAproductView(<?php echo $this->jsObjectName()?>);

//console.log('<?php //echo basename(__FILE__);?>');
</script>
<?php
//error_log(print_r(basename(__FILE__),1));
    $this->renderBody($body);

    if ($this->master || $this->formFlg) {
      if (!$this->pidElement) { ?>
<input type="hidden" name="products_id[]" id="<?php echo ($this->pidElement='products_id_'.$this->makeID())?>" value="">
<?php    }
      if (!$this->attrsElement) { ?>
<input type="hidden" name="attrs[<?php echo $this->pid?>]" id="<?php echo ($this->attrsElement='products_attrs_'.$this->makeID())?>" value="">
<?php    }
    }
?>
<script type="text/javascript">
  <?php echo $this->jsObjectName()?>.fields={<?php $lst=array(); foreach ($this->jsFields AS $f=>$fv) $lst[]=$f.':['.join(',',$fv).']'; echo join(',',$lst); ?>};
  <?php echo $this->jsObjectName()?>.pidElement=<?php if(isset($this->pidElement)) { ?>$('<?php echo $this->pidElement?>')<?php } else { ?>{name:'products_id[<?php echo $this->pid?>]'}<?php } ?>;
  <?php echo $this->jsObjectName()?>.attrsElement=<?php if (isset($this->attrsElement)) { ?>$('<?php echo $this->attrsElement?>')<?php } else { ?>{}<?php } ?>;
  <?php echo $this->jsObjectName()?>.contentDivs=[<?php $cds=Array(); foreach ($this->contentDivs AS $c) $cds[]="$('$c')"; echo join(',',$cds); ?>];
<?php if (!isset($this->master)) { ?>
  <?php echo $this->jsObjectName()?>.selectAttr();
<?php } ?>
</script>
<?php
    if ($this->formFlg) echo '</form>';
  }

  function HTMLParamsSection($sec,$htargs) {
    switch ($sec) {
      case 'content':
        $this->contentDivs[]=($id=$this->jsObjectName().'_cont_'.sizeof($this->contentDivs));
        $htargs['id']="id=\"$id\"";
	break;
      case 'msg_select':
      case 'msg_stock':
      case 'msg_stockQty':
      case 'msg_stockInfo':
      case 'msg_unavailable':
      case 'msg_backorder':
      case 'msg_backorderDate':
        $htargs['id']='id="'.$this->jsMakeField($sec).'"';
	break;
	//case 'cart_button':
    }
    return $htargs;
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'oldprice': return $this->hasOldPrice;
      case 'nooldprice': return !$this->hasOldPrice;
      default: return true;
    }
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'null_radio': $null=1;
      case 'select_radio': $radio=1;
      case 'select_checkbox':
?>
<input type="<?php echo $radio?'radio':'checkbox'?>" name="products_id[]" <?php if (!$null) { ?>id="<?php echo ($this->pidElement='products_id_'.$this->makeID())?>" <?php } ?>value="" onClick="<?php echo $this->jsObjectName()?>.selectAttr();">
<?php
	$this->renderBody($body);
	break;
      default: $this->renderBody($body);
    }
  }

  function loadModels() {
    if (isset($this->models)) return;
    global $languages_id,$sppc_customer_group_id;

    $mdls = array();
    $sort = array();
    $this->optns = array();
    $xsell = $this->args['xprice'] ? $this->context['xsell'] : NULL;
    $this->xsell = ($this->args['xprice'] && $this->context['xsell']) ? $this->context['xsell']->getXSellRef($this->pid) : NULL;

    $qry = tep_db_query("SELECT spg.*, 
								p.*,
								m.manufacturers_name
						 FROM ".TABLE_PRODUCTS." p 
						 LEFT JOIN suppliers_products_groups spg ON p.products_id = spg.products_id
						 LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
						 WHERE p.products_id='".(int)$this->pid."' OR p.master_products_id='".(int)$this->pid."'
						");

    $master = tep_db_fetch_array($qry);

    $this->master_pid=$master['products_id'];
	$this->products_msrp = $master['products_msrp'];
    $this->manufacturers_name = $master['manufacturers_name'];
    $dimgs = Array(DIR_WS_CATALOG_IMAGES.$master['products_image']);

    $this->imglist = Array();
    for ($i=1;$i<=4;$i++) if($master['products_image_xl_'.$i]) $dimgs[$i]=DIR_WS_CATALOG_IMAGES.$master['products_image_xl_'.$i];

    foreach ($dimgs AS $img) $this->imglist[$img] = $img;

    $qry = tep_db_query("SELECT pa.*,
								p.products_image,
								p.products_price,
								p.products_model,
								p.products_tax_class_id,
								p.products_quantity,
								o.products_options_name,
								ov.products_options_values_name,
								p.products_date_available,
								p.products_image_xl_1,
								p.products_image_xl_2,
								p.products_image_xl_3,
								p.products_image_xl_4,
								p.products_date_available,
								m.manufacturers_name
						FROM ".TABLE_PRODUCTS." p
						LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON pa.products_id = p.products_id 
						LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." o ON o.products_options_id = pa.options_id
							AND o.language_id='$languages_id' 
						LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." ov ON pa.options_values_id = ov.products_options_values_id 
							AND ov.language_id = ".$languages_id."
						LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
						WHERE p.master_products_id='".$master['products_id']."'
						AND p.products_id != p.master_products_id 
						ORDER BY pa.options_sort,pa.options_values_sort
						");

	while ($row = tep_db_fetch_array($qry)) {
		$mid = $row['products_id'];
		$optn = $row['options_id'];
		$attr = $row['options_values_id'];
		if(!isset($this->optns[$optn])) {
			$this->optns[$optn] = array('name' => $row['products_options_name'],'values' => array());
		}

		if(!isset($this->optns[$optn]['values'][$attr])) { 
			$this->optns[$optn]['values'][$attr]=Array('name'=>$row['products_options_values_name'],'image'=>$row['options_image']);
		}
		
		if(!isset($sort[$mid])) {
			$sort[$mid] = array();
		}

		$sort[$mid][$optn] = $row['options_values_sort'];

		if(!isset($mdls[$mid])) {
			$imgs = array($row['products_image'] ? DIR_WS_CATALOG_IMAGES.$row['products_image'] : $dimgs[0]);

			for($i=1;$i<=4;$i++) {
				$imgs[$i] = $row['products_image_xl_'.$i] ? DIR_WS_CATALOG_IMAGES.$row['products_image_xl_'.$i] : $dimgs[$i];
			}

			foreach($imgs AS $img) {
				if ($img) {
					$this->imglist[$img] = $img;
				}
			}
        	
			$mdls[$mid] = array('mid' => $mid,
								'attr' => array($optn=>$attr),
								'image' => array('default'=>$imgs),
								'price' => $this->_priceArray($mid),
								'model' => $row['products_model'],
								'qty' => $row['products_quantity'],
								'date_avail' => $this->_dateAvail($row['products_date_available']), 
								'products_msrp' => $this->products_msrp, 
								'manufacturers_name' => $this->manufacturers_name
								);
		} else $mdls[$mid]['attr'][$optn]=$attr;
    }

//	tep_db_free_result($qry);

    if ($mdls) {
      asort($sort);
      $this->models=Array();
      foreach ($sort AS $mid=>$srt) $this->models[$mid]=$mdls[$mid];
    } else {
      	$this->models = array($master['products_id'] => array('mid' => $master['products_id'], 'attr' => array(), 'image' => array('default' => $dimgs),'price' => $this->_priceArray($master['products_id']),'model'=>$master['products_model'],'qty'=>$master['products_quantity'],'date_avail'=>$this->_dateAvail($master['products_date_available']), 'products_msrp'=>$this->products_msrp, 'manufacturers_name'=>$this->manufacturers_name));
    }

    $imglst = tep_db_read("SELECT pi.*, p.products_id AS products_id FROM products_images pi,products p WHERE (pi.products_id=p.products_id OR pi.products_id=p.master_products_id) AND p.products_id IN (".join(',',array_keys($this->models)).") ORDER BY pi.sort_order", array('products_id','image_group','ref_id',NULL),'image_file');

    $stkdep = array();
    if ($GLOBALS['cart']) $GLOBALS['cart']->checkStockDep($stkdep);
    foreach ($this->models AS $mid=>$mdl) {
      $prods[$mid]=IXproduct::load($mid);
      if (isset($prods[$mid])) $prods[$mid]->initStock(); else unset($prods[$mid]);
      if (isset($imglst[$mid]['linked'])) {
        foreach ($imglst[$mid]['linked'] AS $lid=>$imgs) foreach ($imgs AS $img) if ($img) {
          $this->models[$mid]['image']['linked'][$lid][]=$this->imglist[$img]=DIR_WS_CATALOG_IMAGES.$img;
	}
      }
    }
    foreach ($prods AS $mid=>$prod) $this->models[$mid]['qty']=$prods[$mid]->getStockDep($stkdep);
  }
  
  function _priceArray($pid) {
    global $languages_id,$sppc_customer_group_id;
    $pf=new PriceFormatter();
    $pf->loadProduct($pid,$languages_id,$sppc_customer_group_id);
    $rs=$pf->getPriceArray();
    if ($this->xsell) foreach ($this->xsell AS $xpid) {
      $pf->loadProduct($pid,$languages_id,$sppc_customer_group_id,$xpid);
      $rs['xsell'][$xpid]=$pf->getPriceArray();
    }
    if ($pf->hasSpecialPrice) {
      $this->hasOldPrice=1;
      $rs['old']=$pf->displayPrice(NULL,true);
    }
    return $rs;
  }

  function _dateAvail($d) {
    if (!$d) return NULL;
    $t=strtotime($d);
    return ($t > (time()+86400)) ? date('M d Y',$t) : NULL;
  }

  function getAttrInfo($optn=NULL) {
    $this->loadModels();
    if (!isset($optn)) return $this->optns;
    return isset($this->optns[$optn])?$this->optns[$optn]['values']:NULL;
  }

  function getImages() {
    $this->loadModels();
    return $this->imglist;
  }

  function getNumSlots() {
    return 4;
  }
  
  function getVar($var,$args) {
	switch ($var) {

      case 'cart_button':	


				// # Detect current pricing group
				$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

				// # multi-warehousing - update tables for multi-warehousing.
				if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

					// # detect if product has an entry in the products_warehouse_inventory table.
					// # if not, then use default master quantity level.
					$product_check = tep_db_query("SELECT pwi.products_warehouse_id AS warehouse_id, 
														   pwi.products_quantity, 
														   p.products_id, 
														   p.products_quantity AS master_quantity,
														   p.products_status, 
														   pg.customers_group_price AS products_price, 
														   p.products_date_available
													FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." pwi 
													LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE ." pw ON pw.products_warehouse_id = pwi.products_warehouse_id
													LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = pwi.products_id
													LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
													WHERE pwi.products_id = '" . (int)$this->pid. "'
													AND (pwi.products_warehouse_id = 1 OR pw.products_warehouse_name = 'Home')
											   ");

						if(tep_db_num_rows($product_check) < 1) {
		
							$product_check = tep_db_query("SELECT p.products_id, p.products_status, pg.customers_group_price AS products_price, p.products_date_available, p.products_quantity 
											 			   FROM " . TABLE_PRODUCTS . " p
														   LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
														   WHERE p.products_id = '" . (int)$this->pid. "'
														   ");
						}


					} else { // # multi-warehousing is off


							$product_check = tep_db_query("SELECT p.products_id, p.products_status, pg.customers_group_price AS products_price, p.products_date_available, p.products_quantity 
											 			   FROM " . TABLE_PRODUCTS . " p
														   LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
														   WHERE p.products_id = '" . (int)$this->pid. "'
														   ");
			
					} // # END multi-warehousing


		if(tep_db_num_rows($product_check) > 0) { 
		
			$prod = tep_db_fetch_array($product_check);

			//tep_db_free_result($product_check);

			if (STOCK_CHECK == 'true' && STOCK_ALLOW_CHECKOUT != 'true') {

				// # also check /usr/share/IXcore/catalog/js/blocks/blk_product_model.js for cartButtonDisabled class logic

				if( ($prod['products_quantity'] < 1 && !$prod['products_date_available'] > 0) || ($prod['products_price'] == 0) || ($prod['products_status'] == 0) ) {

					return 'Product unavailable';

				} elseif( $prod['products_quantity'] < 1 && $prod['products_date_available'] > 0 ) {

					return tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART, 'id="'.$this->jsMakeField('cart_button').'" onclick="return '.$this->jsObjectName().'.buyNow('.($this->formFlg?'this.form':$this->jsObjectName().'.buildForm()').','.$this->jsObjectName().')"');

				} else {

					return tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART,'id="'.$this->jsMakeField('cart_button').'" class="buttonDisabled" onclick="return '.$this->jsObjectName().'.buyNow('.($this->formFlg?'this.form':$this->jsObjectName().'.buildForm()').','.$this->jsObjectName().')"');

				}

			
			} else { // # else not checking the stock check enforcement flag STOCK_CHECK.

			if( ($prod['products_price'] < 0.01) || ($prod['products_status'] == 0) ) {
					return 'Product unavailable';	
				} else {
					return tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART,'id="'.$this->jsMakeField('cart_button').'" onclick="return '.$this->jsObjectName().'.buyNow('.($this->formFlg?'this.form':$this->jsObjectName().'.buildForm()').','.$this->jsObjectName().')"');
				}	

			}
	
		} else { // # else no db results from product_id

			return 'Product not found.';

		}

case '':

case 'wishlist_button':

		return tep_image_submit('wishlist.gif', 'Remember this item for later', 'id="'.$this->jsMakeField('wishlist_button').'" name="wishlist" value="wishlist" onClick="return this.form.wishListClicked=true;"');

case 'products_model':

	if (isset($this->context['root']->prod['products_model'])) { 
		return	' <span class="products_modelTitle">Model: </span> <span class="products_model" id="'.$this->jsMakeField('model').'" itemprop="mpn" content="'.$this->context['root']->prod['products_model'].'"></span>'; 
	} else { 
		return ''; 
	}

case 'quantity_field':
		$size = (isset($args['size']) ? $args['size'] : 3);
		$disabled='';

		// # Detect current pricing group
		$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');
	
		// # multi-warehousing - update tables for multi-warehousing.
		if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

			// # detect if product has an entry in the products_warehouse_inventory table.
			// # if not, then use default master quantity level.
			$product_check = tep_db_query("SELECT  pwi.products_quantity,
												   pg.customers_group_price AS products_price
											FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." pwi 
											LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE ." pw ON pw.products_warehouse_id = pwi.products_warehouse_id
											LEFT JOIN products_groups pg ON (pg.products_id = pwi.products_id AND pg.customers_group_id = '". $customer_group_id ."')
											WHERE pwi.products_id = '" . (int)$this->pid. "'
											AND (pwi.products_warehouse_id = 1 OR pw.products_warehouse_name = 'Home')
											GROUP BY pg.products_id
									   ");

				if(tep_db_num_rows($product_check) < 1) {

					$product_check = tep_db_query("SELECT pg.customers_group_price AS products_price, 
														  p.products_quantity 
												   FROM products_groups pg 
												   LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = pg.products_id 
												   WHERE pg.products_id = '". (int)$this->pid ."' 
												   AND pg.customers_group_id = '". $customer_group_id ."' 
												   GROUP BY pg.products_id
												   ");
				}


			} else { // # multi-warehousing is off


				// # if pricing group is greater then Pending or Retail, reference the pricing_groups table for special pricing to show links
	
				$product_check = tep_db_query("SELECT pg.customers_group_price AS products_price, 
													  p.products_quantity 
											   FROM products_groups pg 
											   LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = pg.products_id 
											   WHERE pg.products_id = '". (int)$this->pid ."' 
											   AND pg.customers_group_id = '". $customer_group_id ."' 
											   GROUP BY pg.products_id
											   ");
			
			} // # END multi-warehousing


	
		$prod = tep_db_fetch_array($product_check);

	//	tep_db_free_result($product_check);

		// # condition will need to be updated for warehousing module and checking appropriaye warehouse level.
		if (STOCK_CHECK == 'true' && STOCK_ALLOW_CHECKOUT != 'true') {
			if($prod['products_quantity'] < 1) { 
				$disabled = ' disabled';
			}
		}

			return '<input type="text" name="quantity" value="1" size="'.$size.'" id="'.$this->jsMakeField('quantity').'" onchange="'.$this->jsObjectName().'.setQuantity(this.value);"'.$disabled.'>';

case 'products_price':

	// # Detect current pricing group
	$customer_group_id = (!empty($_SESSION['sppc_customer_group_id']) ? $_SESSION['sppc_customer_group_id'] : '0');

	// # if pricing group is greater then Pending or Retail, reference the pricing_groups table for special pricing to show links
	$product_check = tep_db_query("SELECT customers_group_price AS products_price FROM products_groups WHERE products_id = '". (int)$this->pid ."' AND customers_group_id = '". $customer_group_id ."' GROUP BY products_id");


	$prod = tep_db_fetch_array($product_check);

//	tep_db_free_result($product_check);

		if($prod['products_price'] > 0) {
			
			return '<meta itemprop="priceCurrency" content="USD" /><span id="'.$this->jsMakeField('price').'" itemprop="price">'.number_format($prod['products_price'],2).'</span><meta itemprop="itemCondition" itemtype="http://schema.org/OfferItemCondition" content="http://schema.org/NewCondition"/><meta itemprop="availability" itemtype="http://schema.org/ItemAvailability" content="http://schema.org/InStock"/>';
		} else {
			return '<span itemprop="price">-</span>';
		}

	case 'products_oldprice':
		return '<script>document.getElementById(\'specialFlag\').style.display=\'block\';</script><span id="'.$this->jsMakeField('oldprice').'"></span><meta itemprop="availability" itemtype="http://schema.org/ItemAvailability" content="http://schema.org/Discontinued"/>';

	default: 
		return $this->context['root']->getVar($var,$args);
	}
}
  
  function exportContext() {
    $ctxt=$this->context;
    $ctxt['models']=&$this;
    $ctxt['imageset']=&$this;
    return $ctxt;
  }
  
  function requireContext() {
    return Array('product');
  }
  
  function jsMakeField($fld) {
    if (!isset($this->jsFields[$fld])) $this->jsFields[$fld]=Array();
    $id=$this->jsObjectName().'_fld_'.(++$this->jsFieldIdx);
    $this->jsFields[$fld][]="$('$id')";
    return $id;
  }
}
?>