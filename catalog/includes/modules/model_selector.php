<script language="javascript">
var productOptnNames={};
var productAttrValues={};
var productModels={};
var productOptions={};

function curr2num(cur) {
  return Number(cur.replace(/[^0-9\.]/g,''));
}

function selectModel(pid,attrs,val) {
  var mdls=new Array();
  var nextattr=new Array();
  var nvs={};
  var nx=productOptions[pid][attrs.length];
  var md;
  var boxid='<?=modelAttrBoxId("'+pid+'","'+attrs.length+'")?>';
  if (!$(boxid) && attrs.length>0) selectModel(pid,attrs.slice(0,attrs.length-1),attrs[attrs.length-1]);
  var box=$(boxid);
  if (!box) return false;
  for (var i=0;md=productModels[pid][i];i++) {
    for (var j=0;j<attrs.length;j++) if (md.attr[productOptions[pid][j]]!=attrs[j]) {
      md=undefined;
      break;
    }
    if (md) {
      mdls.push(md);
      if (nx!=undefined) {
        var nv=md.attr[nx];
        if (!nvs[nv]) { nextattr.push(nv); nvs[nv]=[]; }
	nvs[nv].push(md);
      }
    }
  }
  var img=mdls[0]?mdls[0].image:null;
  if (nx!=undefined) {
    var ht='';
    if (nextattr.length>0) {
      ht+=productOptnNames[pid][nx]+': <select onChange="selectModel(\''+pid+'\',['+(attrs.length?'\''+attrs.join('\',\'')+'\',':'')+'this.value])">';
      if (nextattr.length>1) ht+='<option value=""'+(val==null?' selected':'')+'>-- Please Select --</option>';
      for (var i=0;nextattr[i]!=undefined;i++) ht+='<option value="'+nextattr[i]+'"'+(val==nextattr[i]?' selected':'')+'>'+productAttrValues[pid][nx][nextattr[i]]+'</option>';
      ht+='</select><div id="<?=modelAttrBoxId("'+pid+'","'+(attrs.length+1)+'")?>"></div>';
    }
    box.innerHTML=ht;
    if (nextattr.length==1) selectModel(pid,attrs.concat(nextattr));
    else if (val==null) {
      var pmin=null;
      var pmax=null;
      for (var midx=0;mdls[midx];midx++) {
	if (pmin==null || curr2num(mdls[midx].price.price)<curr2num(pmin.price)) pmin=mdls[midx].price;
	if (pmax==null || curr2num(mdls[midx].price.price)>curr2num(pmax.price)) pmax=mdls[midx].price;
      }
      finishSelectModel(pid,img,null,null,pmin,pmax);
    }
  } else finishSelectModel(pid,img,(mdls[0]?mdls[0].mid:null),makeAttrStr(pid,attrs),mdls[0].price,mdls[0].price);
}

function makeAttrStr(pid,attrs) {
  var st=new Array();
  for (var i=0;attrs[i]!=undefined;i++) st.push(productOptions[pid][i]+':'+attrs[i]);
  return st.join(';');
}

</script>

<?

function modelAttrBoxId($pid,$idx) {
  return 'model_attr_box_'.$pid.'_'.$idx;
}

function modelSelector($pid,$dimgs=NULL) {
  global $languages_id,$currencies,$sppc_customer_group_id;
  $mdls=Array();
  $opts=Array();
  $optvals=Array();
  $qry=tep_db_query("SELECT m.master_products_id,pa.*,p.products_image,p.products_price,p.products_model,p.products_tax_class_id,o.products_options_name,ov.products_options_values_name,p.products_image_xl_1,p.products_image_xl_2,p.products_image_xl_3,p.products_image_xl_4 FROM ".TABLE_PRODUCTS." m, ".TABLE_PRODUCTS." p, ".TABLE_PRODUCTS_ATTRIBUTES." pa LEFT JOIN ".TABLE_PRODUCTS_OPTIONS." o ON pa.options_id=o.products_options_id AND o.language_id='$languages_id' LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." ov ON pa.options_values_id=ov.products_options_values_id AND ov.language_id='$languages_id' WHERE m.products_id='$pid' AND p.master_products_id=m.master_products_id AND pa.products_id=p.products_id AND p.products_id!=p.master_products_id ORDER BY pa.options_sort,pa.options_values_sort");
  while ($row=tep_db_fetch_array($qry)) {
    $mid=$row['products_id'];
    $optn=$row['options_id'];
    $attr=$row['options_values_id'];
    $ct=0;
    foreach ($mdls AS $midx=>$md) if ($md['mid']==$mid) {
      $ct++;
      if (isset($md['attr'][$optn])) {
        if ($md['attr'][$optn]==$attr) continue;
        $md['attr'][$optn]==$attr;
	$mdls[]=$md;
      } else $mdls[$midx]['attr'][$optn]=$attr;
    }
    if (!$ct) {
      $pf=new PriceFormatter();
      $pf->loadProduct($mid,$sppc_customer_group_id);
      $imgs=Array($row['products_image']?DIR_WS_IMAGES.$row['products_image']:$dimgs[0]);
      for ($i=1;$i<=4;$i++) $imgs[$i]=$row['products_image_xl_'.$i]?DIR_WS_IMAGES.$row['products_image_xl_'.$i]:$dimgs[$i];
      $mdls[]=Array('mid'=>$mid,'attr'=>Array($optn=>$attr),'image'=>$imgs,'price'=>$pf->getPriceArray(),'model'=>$row['products_model']);
    }
    if (!isset($opts[$optn])) $opts[$optn]=$row['products_options_name'];
    if (!isset($optvals[$optn])) $optvals[$optn]=Array();
    if (!isset($optvals[$optn][$attr])) $optvals[$optn][$attr]=$row['products_options_values_name'];
  }
  if ($mdls) {
    $this_mdl=Array();
    foreach ($mdls AS $midx=>$md) {
      foreach ($opts AS $optn=>$optname) if (!isset($md['attr'][$optn])) {
	$mdls[$midx]['attr'][$optn]='0';
        if (!isset($optvals[$optn]['0'])) $optvals[$optn]['0']='Unspecified';
      }
      if (!$this_mdl && $md['mid']==$pid) foreach ($opts AS $optn=>$optname) $this_mdl[$optn]=$mdls[$midx]['attr'][$optn];
    }
?>
<div id="<?=modelAttrBoxId($pid,0)?>"></div>
<script language="javascript">
  productModels['<?=$pid?>']=<?=tep_js_quote_array($mdls)?>;
  productOptions['<?=$pid?>']=<?=tep_js_quote_array(array_keys($opts))?>;
  productOptnNames['<?=$pid?>']=<?=tep_js_quote($opts)?>;
  productAttrValues['<?=$pid?>']=<?=tep_js_quote($optvals)?>;
  selectModel('<?=$pid?>',<?=tep_js_quote_array($this_mdl)?>);
</script>
<?
  } else {
?>
<script language="javascript">
  finishSelectModel('<?=$pid?>',null,'<?=$pid?>','');
</script>
<?
  }
}

?>