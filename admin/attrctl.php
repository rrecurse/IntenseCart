<?
  include('includes/application_top.php');

  $optns=Array();
  $opvals=Array();
  $opvals_qry=tep_db_query("SELECT * FROM products_options_values_to_products_options v2o LEFT JOIN products_options_values v ON v.products_options_values_id=v2o.products_options_values_id AND v.language_id='$languages_id' LEFT JOIN products_options o ON o.products_options_id=v2o.products_options_id AND o.language_id='$languages_id'");
  while ($row=tep_db_fetch_array($opvals_qry)) {
    $optns[$row['products_options_id']]=$row['products_options_name'];
    if (!isset($opvals[$row['products_options_id']])) $opvals[$row['products_options_id']]=Array();
    $opvals[$row['products_options_id']][$row['products_options_values_id']]=$row['products_options_values_name'];
  }

?>

<script language="javascript" src="js/prototype.lite.js"></script>
<script language="javascript" src="js/expander-list.js"></script>

<div class="Accordion" id="modelAccordion" tabindex="0">
</div>


<div id="model_panel_prototype" class="AccordionPanel" style="display:none">
<div class="AccordionPanelTab">
<span field="attribute">Attribute</span> (<span field="count">0</span>)
</div>
<div class="AccordionPanelContent"><div></div></div>
</div>

<div id="model_entry_prototype" style="display:none">
<table><tr>
<td field="attributes"><table>
  <tr><td>Attribute</td><td><input type="text" name="attr_sort_order" size="3"></td></tr>
</table></td>
<td><table><tr>
<td><select name="model_price_sign[]"><option value="+">+</option><option value="-">-</option></select></td>
<td><input type="text" name="model_price[]" field="model_price" size="7"></td>
<td><input type="text" name="model_name[]" field="model_name"></td>
<td><input type="text" name="model_quantity[]" field="model_quantity" size="6"></td>
<td><input type="text" name="model_sku[]" field="model_sku"></td>
</tr>
<tr><td colspan="4"><input type="file" name="model_image[]"></td></tr>
</table>
</td></tr></table>
<input type="hidden" name="model_attrs[]" value="">
</div>


<table><tr>
<td id="new_model_attrs">
</td>
<td>

<table>
<tr>
<td>Price:</td><td><select name="model_price_sign" id="new_model_price_sign"><option value="+">+</option><option value="-">-</option></select>
<input type="text" name="new_model_price" id="new_model_price" size="7"></td>
<td>Stock:</td>
<td><input type="text" name="new_model_quantity" id="new_model_quantity" size="6"></td>
<td><input type="text" name="new_model_name" id="new_model_name"></td>
<td><input type="text" name="new_model_sku" id="new_model_sku"></td>
</tr>
<tr><td colspan="4"><input type="file" name="new_model_image"></td></tr>
</table>
</td>
<td>[<a href="javascript:void(0)" onClick="addNewModel('new')">Add</a>]</td>
</tr></table>




<script language="javascript">

var modelAccordion = new Spry.Widget.Accordion("modelAccordion");

<?
  $optnsJS=Array();
  $opvalsJS=Array();
  foreach ($optns AS $opid=>$op) {
    $optnsJS[]=$opid.':'.tep_js_quote($op);
    $opvalsJS2=Array();
    foreach ($opvals[$opid] AS $opvid=>$opv) $opvalsJS2[]=$opvid.':'.tep_js_quote($opv);
    $opvalsJS[]=$opid.':{'.join(',',$opvalsJS2).'}';
  }
?>

var optnNames={<?=join(',',$optnsJS)?>};
var attrValues={<?=join(',',$opvalsJS)?>};

var currOptions=<?=tep_js_quote_array(array_keys(tep_get_product_options($products_id)))?>;

function scanFields(fields,blk,tag) {
  var fld;
  for (var e=blk.firstChild;e;e=e.nextSibling) {
    if (e.getAttribute && (fld=e.getAttribute(tag)))
      fields[fld]=e;
    else scanFields(fields,e,tag);
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

function setInputs(sec,flds) {
  setInputsCol(sec.getElementsByTagName('input'),flds);
  setInputsCol(sec.getElementsByTagName('select'),flds);
}

function setInputsCol(inputs,flds) {
  for (var i=0;inputs[i];i++) {
    if (flds[inputs[i].name]!=undefined) {
      if (inputs[i].options) {
        for (var j=0;inputs[i].options[j];j++) inputs[i].options[j].selected=(inputs[i].options[j].value==flds[inputs[i].name]);
      } else inputs[i].value=flds[inputs[i].name];
    }
  }
}


function makeModelId(optns,attrs) {
  return attrs.join('_');
}

function addModel(optns,attrs,flds) {
  if (!attrs.length) return false;
  var mid=makeModelId(optns,attrs);
  var secid='model_entry_'+mid;
  if ($(secid)) return false;
  var panid='model_panel_'+(optns[0]==currOptions[0]?attrs[0]:'');
  var pan=$(panid);
  if (!pan) {
    pan=$('model_panel_prototype').cloneNode(true);
    pan.id=panid;
    pan.style.display='';
    $('modelAccordion').insertBefore(pan,null);
    modelAccordion.initPanel(pan);
  }
  pandivs=pan.getElementsByTagName('div');
  var sec=$('model_entry_prototype').cloneNode(true);
  sec.id=secid;
  for (var fld in flds) {
    setField(sec,fld,flds[fld]);
  }
  var attrlst=new Array();
  var attrtable=new Array();
  for (var i=0;optns[i]!=undefined;i++) {
    attrlst.push(optns[i]+':'+attrs[i]);
    attrtable.push('<tr><td>'+optnNames[optns[i]]+': '+attrValues[optns[i]][attrs[i]]+'</td><td><input type="text" name="attr_sort_order['+optns[i]+'_'+attrs[i]+']" size="2"></td></tr>');
  }
  flds['model_attrs[]']=attrlst.join(',');
  setInputs(sec,flds);
  setField(sec,'attributes','<table>'+attrtable.join('')+'</table>');
  var secs=pandivs[1].getElementsByTagName('div')[0];
  secs.insertBefore(sec,null);
  sec.style.display='';
  modelAccordion.adjustPanelHeight();
  setField(pandivs[0],'attribute',(optns[0]==currOptions[0]?attrValues[optns[0]][attrs[0]]:'[Other]'));
  var ct=0;
  for (var e=secs.firstChild;e;e=e.nextSibling) if (e.tagName=='DIV') ct++;
  setField(pandivs[0],'count',ct);
  return true;  
}

function makeSelectElement(selid,vals,val) {
  if (!vals) return 'n/a';
  var sel='<select id="'+selid+'" name="'+selid+'">';
  for (k in vals) sel+='<option value="'+k+'"'+(val==vals[k]?' selected':'')+'>'+vals[k]+'</option>';
  sel+='</select>';
  return sel;
}

function showOptionSelectors(boxid) {
  var newoptnsel=boxid+'_new_optn';
  var html='<table>';
  for (var i=0;currOptions[i]!=undefined;i++) html+='<tr><td>'+optnNames[currOptions[i]]+'</td><td>'+makeSelectElement(boxid+'_attr['+currOptions[i]+']',attrValues[currOptions[i]],0)+'</td></tr>';
  html+='<tr><td colspan="2">'+makeSelectElement(newoptnsel,optnNames)+' [<a href="javascript:void(0)" onClick="currOptions.push($(\''+newoptnsel+'\').value); showOptionSelectors(\''+boxid+'\')">Add</a>]</td></tr></table>';
  $(boxid).innerHTML=html;
}

var modelFieldList=new Array('model_name','model_price','model_price_sign','model_sku','model_quantity');

function addNewModel(prfx) {
  var attrs=new Array();
  var flds={};
  for (var i=0;currOptions[i]!=undefined;i++) attrs[i]=$(prfx+'_model_attrs_attr['+currOptions[i]+']').value;
  for (var i=0;modelFieldList[i]!=undefined;i++) flds[modelFieldList[i]+'[]']=$(prfx+'_'+modelFieldList[i]).value;
  addModel(currOptions,attrs,flds);
}



addModel(new Array(1,2),new Array(1,2),{'model_name[]':'test','model_price_sign[]':'-'});
addModel(new Array(1,2),new Array(1,3),{});
addModel(new Array(1,2),new Array(4,3),{});

showOptionSelectors('new_model_attrs');

</script>
