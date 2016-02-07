<?php

class blk_product_models extends IXblock {

  function jsObjectName() {
    return 'productModels_'.$this->makeID();
  }

  function render($body) {
    $this->loadModels();
    $selattr=isset($this->models[$this->pid])?$this->models[$this->pid]['attr']:Array();
?>

<script type="text/javascript">

window.<?php echo $this->jsObjectName()?>={
  id:<?php echo tep_js_quote($this->makeID())?>,
  pid:<?php echo tep_js_quote($this->pid)?>,
  optns:<?php echo tep_js_quote($this->optns)?>,
  models:<?php echo tep_js_quote_array($this->models)?>,
  currAttr:<?php echo tep_js_quote($selattr)?>,
  qty:1,
  attrSelObj:[],
  imageSwapObj:[],
  prodSwapObj:[],
  addAttrSelector:function(obj) {
    this.attrSelObj.push(obj);
    var avl=this.getAvailList(this.currAttr);
    obj.selectionChanged(this.currAttr,avl);
  },
  addImageSwap:function(obj) {
    this.imageSwapObj.push(obj);
  },
  addProductSwap:function(obj) {
    this.prodSwapObj.push(obj);
  },
  getAvailList:function(sll) {
    var rs={};
    var sl={};
    var unsl={};
    for (var op in this.optns) {
      rs[op]={};
      if (sll[op]==null) unsl[op]=true; else sl[op]=sll[op];
    }
    var m;
    for (var i=0;m=this.models[i];i++) {
      var mis=null;
      var ovf=false;
      for (var op in sl) if (m.attr[op]!=sl[op]) {
	if (mis==null) mis=op; else { ovf=true; break; }
      }
      if (!ovf) for (var op in this.optns) if (mis==null || mis==op) {
	if (!rs[op][m.attr[op]]) rs[op][m.attr[op]]=[];
	rs[op][m.attr[op]].push(m);
      }
      if (mis==null) {
	if (!rs[undefined]) rs[undefined]=[];
	rs[undefined].push(m);
      }
    }
    return rs;
  },
  getOffStock:function(lst) {
    var off={};
    for (var op in this.optns) if (lst[op]) {
      off[op]={};
<?php
	if (STOCK_CHECK == 'true') { ?>
      for (var attr in lst[op]) {
        var stk=0;
        for (var i=0;m=lst[op][attr][i];i++) stk+=Number(m.qty);
	if (stk<this.qty) off[op][attr]=this.qty-stk;
      }
<?php } ?>
    }
    return off;
  },
  previewAttr:function(optn,attr) {
    var sl={};
    for (var op in this.optns) if (op==optn && attr!=null) sl[op]=attr; else sl[op]=this.currAttr[op];
    var slm={};
    slm[optn]=attr;
    var avl=this.getAvailList(sl);
    var avm=attr==null?null:this.getAvailList(slm);
    var off=this.getOffStock(avl);
    for (var i=0;this.attrSelObj[i];i++) this.attrSelObj[i].selectionChanged(this.currAttr,avl,avm,off);
  },
  selectAttr:function(optn,attr) {
//    if (this.getAvailableAttrs(optn)[attr]) this.currAttr[optn]=attr;
    if (this.optns[optn]) {
      if (this.optns[optn].values[attr]) this.currAttr[optn]=attr; else this.currAttr[optn]=undefined;
    }
    var avl=this.getAvailList(this.currAttr);
    var off=this.getOffStock(avl);
    for (var i=0;this.attrSelObj[i];i++) this.attrSelObj[i].selectionChanged(this.currAttr,avl,null,off);
    this.modelSwap(avl);
  },
  modelSwap:function(avl) {
    var mdls=avl[undefined];
    var fsl=mdls && true;
    if (!fsl) {
      var optns=[];
      for (var op in this.optns) optns.unshift(op);
      for (var i=0;optns[i]!=null;i++) {
	for (var attr in avl[optns[i]]) { mdls=avl[optns[i]][attr]; break; }
	if (mdls) break;
      }
    }
    var errblk=$('models_attr_error_'+this.id);
    var stockblk=$('models_stock_warning_'+this.id);
    var availblk=$('models_date_avail_warning_'+this.id);
    if (errblk) errblk.style.display=fsl?'none':'';
    if (stockblk) stockblk.style.display='none';
    if (availblk) availblk.style.display='none';
    if (!mdls) return;
    for (var i=0;this.imageSwapObj[i];i++) this.imageSwapObj[i].imageSwap(mdls[0].image);
    for (var i=0;this.prodSwapObj[i];i++) this.prodSwapObj[i].productSwap(mdls[0].mid);
    this.itemPrice={min:mdls[0].price.price,max:mdls[0].price.price,quantity:mdls[0].price.quantity};
    this.displayPrice();
    if (this.showCartButton) this.showCartButton(false);
    if (fsl && mdls.length==1) {
<?php if (STOCK_CHECK == 'true') { ?>
      if (stockblk && this.qty>Number(mdls[0].qty)) {
        if ($('models_stock_qty_'+this.id)) $('models_stock_qty_'+this.id).innerHTML=mdls[0].qty;
        if ($('models_stock_msg_'+this.id)) $('models_stock_msg_'+this.id).innerHTML=mdls[0].stockmsg?mdls[0].stockmsg:'';
	stockblk.style.display='';
<?php if (STOCK_ALLOW_CHECKOUT != 'true') { ?>
	this.pidElement.value='';
	return;
<?php } ?>
      }
<?php } ?>
      if (mdls[0].date_avail && availblk) {
        $('models_date_avail_msg_'+this.id).innerHTML=mdls[0].date_avail;
	availblk.style.display='';
      }
      if (this.showCartButton) this.showCartButton(true);
      this.pidElement.value=mdls[0].mid;
      this.attrsElement.name='attrs['+mdls[0].mid+']';
      var attrs=[];
      for (var op in mdls[0].attr) attrs.push(op+':'+mdls[0].attr[op]);
      this.attrsElement.value=attrs.join(';');
      return;
    } 
    this.pidElement.value='';
  },
  displayPrice:function() {
    if (this.master) return this.master.displayPrice();
//    if (!$('final_price')) return false;
    this.qty=Number($('order_quantity')?$('order_quantity').value:1);
    var qcur=0;
    if (this.itemPrice.quantity) for (var q in this.itemPrice.quantity) if (Number(q)<=this.qty && Number(q)>qcur) qcur=Number(q);
    pmin=qcur?this.itemPrice.quantity[qcur]:this.itemPrice.min;
    if ($('final_price')) $('final_price').innerHTML=(pmin!=this.models[0].price.price?'<span class="priceChanged">'+pmin+'</span>':pmin);
    if ($('add_product_price')) $('add_product_price').value=pmin.replace(/[^\d\.]/g,'');
  },
  buyNow:function(frm) {
    if (this.pidElement.value) {
      if (frm.wishListClicked) { frm.wishListClicked=false; return true; }
      if (window.addToCart) {
        window.addToCart(frm);
	return false;
      }
      if (window.opener && window.opener.addToCart) {
        window.opener.addToCart(frm);
	return false;
      }
      return true;
    }
    return false;
  }
};
</script>
<?php
  }

	function loadModels() {
		if(isset($this->models)) return;
		global $languages_id,$sppc_customer_group_id;

		$mdls = array();
		$sort = array();
    	$this->optns = array();

		$qry = tep_db_query("SELECT p.* 
							 FROM ".TABLE_PRODUCTS." m
							 LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id = m.master_products_id
							 WHERE m.products_id = '".addslashes($this->pid)."'
							");
		$master = tep_db_fetch_array($qry);

		$this->master_pid=$master['products_id'];
    	$dimgs = array(DIR_WS_CATALOG_IMAGES.$master['products_image']);
		$this->imglist = array();

		for($i=1;$i<=4;$i++) {
			if($master['products_image_xl_'.$i]) {
				$dimgs[$i] = DIR_WS_CATALOG_IMAGES.$master['products_image_xl_'.$i];
			}
		}

		foreach ($dimgs AS $img) $this->imglist[$img] = $img;

		$qry = tep_db_query("SELECT pa.*,
									p.products_image,
									p.products_price,
									p.products_model,
									p.products_tax_class_id,
									p.products_quantity,
									o.products_options_name,
									ov.products_options_values_name,
									p.products_image_xl_1,
									p.products_image_xl_2,
									p.products_image_xl_3,
									p.products_image_xl_4,
									p.products_date_available,
									m.manufacturers_name
							FROM ".TABLE_PRODUCTS." p
							LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON pa.products_id = p.products_id 
							LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." o ON o.products_options_id = pa.options_id
								AND o.language_id = '$languages_id' 
							LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." ov ON ov.products_options_values_id = pa.options_values_id
								AND ov.language_id = '$languages_id' 
							LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
							WHERE p.master_products_id = '".$master['products_id']."' 
							AND p.products_id != p.master_products_id
							ORDER BY pa.options_sort,pa.options_values_sort
							");

		while ($row=tep_db_fetch_array($qry)) {
			
			$mid = $row['products_id'];
			$optn = $row['options_id'];
			$attr = $row['options_values_id'];
			if(!isset($this->optns[$optn])) $this->optns[$optn]=Array('name'=>$row['products_options_name'],'values'=>array());

			if(!isset($this->optns[$optn]['values'][$attr])) {
				$this->optns[$optn]['values'][$attr] = array('name'=>$row['products_options_values_name'],'image'=>$row['options_image']);
			}
			
			if (!isset($sort[$mid])) {
				$sort[$mid] = array();
			}

			$sort[$mid][$optn] = $row['options_values_sort'];
			
			if(!isset($mdls[$mid])) {
				$pf = new PriceFormatter();
				$pf->loadProduct($mid,$sppc_customer_group_id);
				$imgs = array($row['products_image']?DIR_WS_CATALOG_IMAGES.$row['products_image']:$dimgs[0]);

				for($i=1;$i<=4;$i++) {
					$imgs[$i]=$row['products_image_xl_'.$i]?DIR_WS_CATALOG_IMAGES.$row['products_image_xl_'.$i]:$dimgs[$i];
				}
			
				foreach ($imgs AS $img) {
					if($img) 	$this->imglist[$img]=$img;
				}
				
				$mdls[$mid] = array('mid' => $mid,
									'attr' => array($optn=>$attr),
									'image' => $imgs,
									'price' => $pf->getPriceArray(),
									'model' => $row['products_model'],
									'qty' => $row['products_quantity'],
									'date_avail' => $this->_dateAvail($row['products_date_available']));
			} else { 
				$mdls[$mid]['attr'][$optn] = $attr;
			}
		}
		
		tep_db_free_result($qry);

		if($mdls) {
			asort($sort);
			$this->models = array();

			foreach($sort AS $mid=>$srt) { 
				$this->models[$mid]=$mdls[$mid];
			}

		} else {

			$pf = new PriceFormatter();
			$pf->loadProduct($master['products_id'],$sppc_customer_group_id);
      		$this->models = array($master['products_id']=>array('mid' => $master['products_id'],
																'attr' => array(),
																'image' => $dimgs,
																'price' => $pf->getPriceArray(),
																'model' => $master['products_model'],
																'qty' => $master['products_quantity'],
																'date_avail' => $this->_dateAvail($master['products_date_available'])
																));
		}


		$stkdep = array();

		if($_SESSION['cart'] && is_object($_SESSION['cart'])) {
//error_log(print_r($_SESSION['cart'],TRUE));
		//$_SESSION['cart']->checkStockDep($stkdep);
		}

		foreach ($this->models AS $mid=>$mdl) {
			$prods[$mid]=IXproduct::load($mid);
			if(isset($prods[$mid])) {
				$prods[$mid]->initStock(); 
			} else {
				unset($prods[$mid]);
			}

			if(isset($imglst[$mid]['linked'])) {
				foreach ($imglst[$mid]['linked'] AS $lid=>$imgs) {
					foreach ($imgs AS $img) if ($img) {
					$this->models[$mid]['image']['linked'][$lid][]=$this->imglist[$img]=DIR_WS_CATALOG_IMAGES.$img;
					}
				}
			}
		}

		foreach ($prods AS $mid=>$prod) $this->models[$mid]['qty']=$prods[$mid]->getStockDep($stkdep);
	}
  

function _dateAvail($d) {
    if (!d) return NULL;
    $t=strtotime($d);
    return $t>time()+86400?date('M d Y',$t):NULL;
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

  function exportContext() {
    $ctxt=$this->context;
    $ctxt['models']=&$this;
    return $ctxt;
  }

  function getNumSlots() {
    return 4;
  }

}


class blk_product_models_fields extends IXblock {
  function render($body) {
    $obj=$this->context['models'];
?>
<input type="hidden" name="products_id[]" id="products_id_<?php echo $obj->makeID()?>" value="">
<input type="hidden" name="attrs[<?php echo $obj->pid?>]" id="products_attrs_<?php echo $obj->makeID()?>" value="">
<script type="text/javascript">
  <?php echo $obj->jsObjectName()?>.pidElement=$('products_id_<?php echo $obj->makeID()?>');
  <?php echo $obj->jsObjectName()?>.attrsElement=$('products_attrs_<?php echo $obj->makeID()?>');
  <?php echo $obj->jsObjectName()?>.selectAttr();
</script>
<div id="models_attr_error_<?php echo $obj->makeID()?>" style="display:none; color:red;">
<?php if (defined('PROD_INFO_MODEL_ERROR_TEXT')) echo PROD_INFO_MODEL_ERROR_TEXT; else { ?>
This combination of attributes is unavailable<br>
Please choose different attributes.
<?php } ?></div>
<div id="models_stock_warning_<?php echo $obj->makeID()?>" style="display:none; color:#BF7FFF;">
<?php if (defined('PROD_INFO_OUTOFSTOCK_TEXT')) printf(PROD_INFO_OUTOFSTOCK_TEXT,'<span id="models_stock_qty_'.$obj->makeID().'">0</span>','<span id="models_stock_msg_'.$obj->makeID().'"></span>'); else { ?>
Insufficient stock level for this model<br>
Currently available: <span id="models_stock_qty_<?php echo $obj->makeID()?>">0</span><br>
<span id="models_stock_msg_<?php echo $obj->makeID()?>"></span><?php } ?>
</div>
<div id="models_date_avail_warning_<?php echo $obj->makeID()?>" class="models_date_avail_warning" style="display:none;">
<?php if (defined('PROD_INFO_DATE_AVAIL_TEXT')) printf(PROD_INFO_DATE_AVAIL_TEXT,'<span id="models_date_avail_msg_'.$obj->makeID().'"></span>'); else { ?>
This item is not available until <span id="models_date_avail_msg_<?php echo $obj->makeID()?>"></span>.<br>
You may Pre-Order this item now. You will not be billed until the item ships.
<?php } ?></div>

<?php
  }
}


class blk_product_models_cart_button extends IXblock {
  function render($body) {
    $obj=$this->context['models'];
    echo tep_image_submit('button_in_cart.gif', IMAGE_BUTTON_IN_CART,'id="models_cart_button_'.$obj->makeID().'" onClick="return '.$obj->jsObjectName().'.buyNow(this.form);"');
?>
<script type="text/javascript">
  <?php echo $obj->jsObjectName()?>.showCartButton=function(flg) {
    $('models_cart_button_<?php echo $obj->makeID()?>').className=flg?'':'buttonDisabled';
  }
  window.onload=<?php echo $obj->jsObjectName()?>.selectAttr.bind(<?php echo $obj->jsObjectName()?>);
</script>
<?php
  }
}

?>