<table width="100%" align="center" cellpadding="5" cellspacing="0" border="0" style="border:1px dashed #CCC">


<?php

	$xsellChannels=tep_db_read("SELECT * FROM xsell_channels",'xsell_channel','xsell_title');
	$xsellPullDown=Array();
	$mdlpulldn=Array(Array('id'=>'','text'=>'All Models'));

	$product_query=tep_db_query("SELECT p.products_id,ov.products_options_values_name FROM ".TABLE_PRODUCTS." p LEFT JOIN ".TABLE_PRODUCTS_ATTRIBUTES." pa ON p.products_id=pa.products_id LEFT JOIN ".TABLE_PRODUCTS_OPTIONS_VALUES." ov ON ov.products_options_values_id=pa.options_values_id AND ov.language_id='$languages_id' WHERE master_products_id='".$pInfo->products_id."' AND p.master_products_id!=p.products_id ORDER BY pa.options_sort");

	$attrs = array();

	while ($row = tep_db_fetch_array($product_query)) {
		if(!isset($attrs[$row['products_id']])) $attrs[$row['products_id']] = array();
		$attrs[$row['products_id']][]=$row['products_options_values_name'];
	}

	foreach ($attrs AS $mid=>$att) {
		$mdlpulldn[]=Array('id'=>$mid,'text'=>join(',',$att));
	}

  foreach ($xsellChannels AS $ch=>$chname) {
    $xsellPullDown[]=Array('id'=>$ch,'text'=>$chname);
?>
<tr>
<td colspan="9" style="background-color:#6295FD; color:#FFF">
Channel - <b><?php echo $chname?></b>
</td></tr>

<tr style="background-color:#D4EAB7;">
<td align="center"><u>ID</u></td> 
<td style="width:42px"><u>Image</u></td> 
<td align="left"><u>Name</u></td> 
<td><u>Model</u></td> 
<td><u>Discount</u></td> 
<td align="center"><u>Qty.</u></td> 
<td><u>Different Image</u></td> 
<td><u>Priority</u></td>
<td><u>Remove</u></td> 
</tr>

<tr><td colspan="9">
<table width="100%" id="xsell_ctrl_<?php echo $ch?>" cellspacing="5" cellpadding="0" border="0" style="border:1px dashed #ccc; background-color:#FFFFC4">
<tr style="display:none">
<td width="30"><input type="hidden" name="xsell[<?php echo $ch?>][]" value=""><span></span></td>
<td width="47"><img src=""></td>
<td width="175"><span></span></td>
<td width="121"><?php echo tep_draw_pull_down_menu('xsell_model['.$ch.'][]',$mdlpulldn,'')?></td>
<td align="center" width="77"><?php echo tep_draw_input_field('xsell_price_diff['.$ch.'][]','','size="4"')?></td>
<td width="40"><?php echo tep_draw_input_field('xsell_price_limit['.$ch.'][]','','size="1"')?></td>
<td width="195"><input type="hidden" name="xsell_currimage[<?php echo $ch?>][]" value=""><input type="file" name="xsell_image[<?php echo $ch?>][]" size="10"></td>
<td width="46"><a href="javascript:void(0)" onClick="xSellMoveUp(this); return false;"><img src="images/arrow_up.png"></a><a href="javascript:void(0)" onClick="xSellMoveDown(this); return false;"><img src="images/arrow_down.png"></a></td>
<td align="center" width="36"><a href="javascript:void(0)" onClick="xSellDel(this); return false;"><img src="images/icon_delete.png"></a></td>
</tr>
</table>
</td></tr>

<?php
	$xsell_qry = tep_db_query("SELECT mp.products_id AS ref_pid,
								IFNULL(pi.image_file,p.products_image) AS products_image,
								pi.image_file AS xsell_image,
								p.products_id,
								pd.products_name,
								px.price_percent,
								px.price_diff,
								px.price_limit 
							  FROM (products mp,products_xsell px) 
							  LEFT JOIN ".TABLE_PRODUCTS." p ON p.products_id = px.xsell_id 
							  LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON pd.language_id = '".(int)$languages_id."' AND pd.products_id = p.master_products_id 
							  LEFT JOIN products_images pi ON (pi.products_id=mp.products_id AND pi.image_group='linked' AND pi.ref_id=p.products_id) 
							  WHERE px.products_id = mp.products_id 
							  AND mp.master_products_id = '".$pInfo->products_id."' 
							  AND xsell_channel = '$ch' 
							  ORDER BY px.sort_order
							 ");

	while ($xsell_row=tep_db_fetch_array($xsell_qry)) {
?>
<tr>
<td><input type="hidden" name="xsell[<?php echo $ch?>][]" value="<?php echo $xsell_row['products_id']?>"><?php echo $xsell_row['products_id']?></td>
<td><?php echo tep_image(DIR_WS_CATALOG_IMAGES.$xsell_row['products_image'],'',32,40)?></td>
<td><?php echo htmlspecialchars($xsell_row['products_name'])?></td>
<td><?php echo tep_draw_pull_down_menu('xsell_model['.$ch.'][]',$mdlpulldn,$xsell_row['ref_pid']==$pInfo->products_id?'':$xsell_row['ref_pid'])?></td>
<td><?php echo tep_draw_input_field('xsell_price_diff['.$ch.'][]',-$xsell_row['price_diff'],'size="4"')?></td>
<td><?php echo tep_draw_input_field('xsell_price_limit['.$ch.'][]',$xsell_row['price_limit'],'size="1"')?></td>
<td><input type="hidden" name="xsell_currimage[<?php echo $ch?>][]" value="<?php echo $xsell_row['xsell_image']?>"><input type="file" name="xsell_image[<?php echo $ch?>][]" size="10"> <!-- img src="/admin/includes/languages/english/images/buttons/button_upload.gif" --></td>
<td align="center"><a href="javascript:void(0)" onClick="xSellMoveUp(this); return false;"><img src="images/arrow_up.png" alt="" border="0"></a><a href="javascript:void(0)" onClick="xSellMoveDown(this); return false;"><img src="images/arrow_down.png" alt="" border="0"></a></td>
<td align="center"><a href="javascript:void(0)" onClick="xSellDel(this); return false;"><img src="images/icon_delete.png" alt="" border="0"></a></td>
</tr>

	<?php } ?>

<?php } ?>

<script type="text/javascript">
function findParentTag(obj,tag) {
  while (obj && obj.tagName!=tag) obj=obj.parentNode;
  return obj;
}

function xSellAdd(pid,pdesc,ch,img) {
  blk=$('xsell_ctrl_'+ch);
  if (!blk) return;
  var flds=blk.getElementsByTagName('input');
//  for (var i=0;flds[i];i++) if (flds[i].name.match(/^xsell/) && flds[i].value==pid) return true;
  var tr0=blk.getElementsByTagName('tr')[0];
  tr=tr0.cloneNode(true);
  tr.getElementsByTagName('input')[0].value=pid;
  var spans=tr.getElementsByTagName('span');
  spans[0].innerHTML=pid;
  spans[1].innerHTML=pdesc;
  if (img) tr.getElementsByTagName('img')[0].src=img;
  tr0.parentNode.insertBefore(tr,null);
  tr.style.display='';
  contentChanged();
}

function xSellDel(obj) {
  if (!window.confirm('Remove the XSell product?')) return;
  var tr=findParentTag(obj,'TR');
  if (tr) tr.parentNode.removeChild(tr);
  contentChanged();
}

function xSellMoveUp(obj) {
  var tr=findParentTag(obj,'TR');
  if (!tr) return;
  var prv=tr.parentNode.firstChild;
  while (prv && prv.nextSibling!=tr) prv=prv.nextSibling;
  if (prv) tr.parentNode.insertBefore(tr,prv);
  contentChanged();
}

function xSellMoveDown(obj) {
  var tr=findParentTag(obj,'TR');
  if (tr) tr.parentNode.insertBefore(tr,(tr.nextSibling?tr.nextSibling.nextSibling:null));
  contentChanged();
}

function xsellLoadProducts(catid,pid) {
  if (catid) {
    $('xsell_prod_select').innerHTML='loading...';
    new ajax('includes/modules/prod_list.php?cat='+catid+(pid?'&models='+pid:''),{update:'xsell_prod_select'});
  } else $('xsell_prod_select').innerHTML='';
}

function prodSelected(pid,pdesc,img,catid,ms) {
  if (!ms) xsellLoadProducts(catid,pid);
  else if (pid) xSellAdd(pid,pdesc,$('xsellchannel').value,img);
}
</script>
<table><tr><td style="padding:20px 5 5px 0"><img src="/admin/images/add.png"></td><td style="font:bold 13px arial; padding:20px 0 0 0">Add Cross-sell Product</td></tr></table>

<table width="100%" cellpadding="5" style="background-color:#B8DB95; border:1px dashed #333">
<tr><td>
<?php
	$catPullDown = array(array('id'=>'','text'=>'-Category-'), array('id'=>'0','text'=>'[Root]'));
	tep_build_cat_pull_down($catPullDown,'&raquo; ');
?>
Channel: <?php echo tep_draw_pull_down_menu('xsellchannel',$xsellPullDown,'','id="xsellchannel"')?> Product: 
<?php echo tep_draw_pull_down_menu('xsellcat',$catPullDown,'','onChange="xsellLoadProducts(this.value)"')?>
<span id="xsell_prod_select"></span>
</td></tr>
</table>
