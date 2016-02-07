function productModelObj(flds){for(var f in flds)this[f]=flds[f];this.linked=[];this.qty=1;this.attrSelObj=[];this.imageSwapObj=[];this.prodSwapObj=[];if(this.master)
this.master.linked.push(this);}productModelObj.prototype.addAttrSelector=function(obj){this.attrSelObj.push(obj);var avl=this.getAvailList(this.currAttr);obj.selectionChanged(
this.currAttr,avl);};productModelObj.prototype.addImageSwap=function(obj){this.imageSwapObj.push(obj);};productModelObj.prototype.addProductSwap=function(obj){this.
prodSwapObj.push(obj);};productModelObj.prototype.getAvailList=function(sll){var rs={};var sl={};var unsl={};for(var op in this.optns){rs[op]={};if(sll[op]==null)
unsl[op]=true;else sl[op]=sll[op];}var m;for(var i=0;m=this.models[i];i++){var mis=null;var ovf=false;for(var op in sl)if(m.attr[op]!=sl[op]){if(mis==null)mis=op;
else{ovf=true;break;}}if(!ovf)for(var op in this.optns)if(mis==null||mis==op){if(!rs[op][m.attr[op]])rs[op][m.attr[op]]=[];rs[op][m.attr[op]].push(m);}if(mis==null){
if(!rs[undefined])rs[undefined]=[];rs[undefined].push(m);}}return rs;};productModelObj.prototype.getOffStock=function(lst){var off={};for(var op in this.optns)if(
lst[op]){off[op]={};for(var attr in lst[op]){var stk=0;for(var i=0;m=lst[op][attr][i];i++)stk+=Number(m.qty);if(stk<this.qty)off[op][attr]=this.qty-stk;}}return off;};
productModelObj.prototype.previewAttr=function(optn,attr){var sl={};for(var op in this.optns)if(op==optn&&attr!=null)sl[op]=attr;else sl[op]=this.currAttr[op];var 
slm={};slm[optn]=attr;var avl=this.getAvailList(sl);var avm=attr==null?null:this.getAvailList(slm);var off=this.getOffStock(avl);for(var i=0;this.attrSelObj[i];i++)
this.attrSelObj[i].selectionChanged(this.currAttr,avl,avm,off);};productModelObj.prototype.selectAttr=function(optn,attr){if(this.optns[optn]){if(this.optns[optn].
values[attr])this.currAttr[optn]=attr;else this.currAttr[optn]=undefined;}return this.master?this.master.swapAttr():this.swapAttr();};productModelObj.prototype.swapAttr=
function(mpid){var ac=!((this.pidElement.type=='checkbox'||this.pidElement.type=='radio')&&!this.pidElement.checked);if(this.contentDivs)for(var i=0;this.contentDivs[
i];i++)this.contentDivs[i].style.display=ac?'':'none';if(!ac){this.modelSwap({});return null;}var lids=[];var mid;var avl=this.getAvailList(this.currAttr);if(this.
linked){var currid=(avl[undefined]&&avl[undefined][0])?avl[undefined][0].mid:null;for(var i=0;this.linked[i];i++)if(mid=this.linked[i].swapAttr(currid))lids.push(
mid);}this.linked_ids=lids;var off=this.getOffStock(avl);for(var i=0;this.attrSelObj[i];i++)this.attrSelObj[i].selectionChanged(this.currAttr,avl,null,off);return 
this.modelSwap(avl,mpid);};productModelObj.prototype.setQuantity=function(qty){this.qty=isNaN(qty)?1:Number(qty);if(this.maxqty&&this.qty>this.maxqty)this.qty=this.
maxqty;if(this.minqty&&this.qty<this.minqty)this.qty=this.minqty;if(this.fields.quantity)for(var i=0;this.fields.quantity[i];i++)this.fields.quantity[i].value=this.
qty;this.selectAttr();};productModelObj.prototype.modelSwap=function(avl,mpid){var mdls=avl[undefined];var fsl=mdls&&true;if(!fsl){var optns=[];for(var op in this.
optns)optns.unshift(op);for(var i=0;optns[i]!=null;i++){for(var attr in avl[optns[i]]){mdls=avl[optns[i]][attr];break;}if(mdls)break;}}this.pidElement.value='';var 
errblk=$('models_attr_error_'+this.id);var stockblk=$('models_stock_warning_'+this.id);var availblk=$('models_date_avail_warning_'+this.id);if(errblk)errblk.style.
display=fsl?'none':'';if(stockblk)stockblk.style.display='none';if(availblk)availblk.style.display='none';if(!mdls){this.currPrice=0;return null;}var imgs=mdls[0].
image['default'];if(this.linked_ids&&mdls[0].image.linked)for(var i=0;this.linked_ids[i];i++)if(mdls[0].image.linked[this.linked_ids[i]])imgs=mdls[0].image.linked[
this.linked_ids[i]];for(var i=0;this.imageSwapObj[i];i++)this.imageSwapObj[i].imageSwap(imgs);for(var i=0;this.prodSwapObj[i];i++)this.prodSwapObj[i].productSwap(
mdls[0].mid);var prc=(mpid&&mdls[0].price.xsell&&mdls[0].price.xsell[mpid])?mdls[0].price.xsell[mpid]:mdls[0].price;this.itemPrice={min:prc.price,max:prc.price,quantity:
prc.quantity};this.currPrice=this.displayPrice();this.setField('model',mdls.length==1?mdls[0]['model']:'');if(this.showCartButton)this.showCartButton(false);if(fsl&&
mdls.length==1){if(stockblk&&this.qty>Number(mdls[0].qty)){if($('models_stock_qty_'+this.id))$('models_stock_qty_'+this.id).innerHTML=mdls[0].qty;if($('models_stock_msg_'+
this.id))$('models_stock_msg_'+this.id).innerHTML=mdls[0].stockmsg?mdls[0].stockmsg:'';stockblk.style.display='';<?if(STOCK_ALLOW_CHECKOUT!='true'){?>return mdls[
0].mid;<?}?>}if(mdls[0].date_avail){$('models_date_avail_msg_'+this.id).innerHTML=mdls[0].date_avail;availblk.style.display='';}if(this.showCartButton)this.showCartButton(
true);this.pidElement.value=mdls[0].mid;this.attrsElement.name='attrs['+mdls[0].mid+']';var attrs=[];for(var op in mdls[0].attr)attrs.push(op+':'+mdls[0].attr[op]);
this.attrsElement.value=attrs.join(';');}return mdls[0].mid;};productModelObj.prototype.displayPrice=function(){var qcur=0;if(this.itemPrice.quantity)for(var q in 
this.itemPrice.quantity)if(Number(q)<=this.qty&&Number(q)>qcur)qcur=Number(q);pmin=qcur?this.itemPrice.quantity[qcur]:this.itemPrice.min;pval=Number(pmin.replace(/[^\
d\.]/,''));if(this.linked&&this.linked.length>0){for(var i=0;this.linked[i];i++)pval+=this.linked[i].currPrice;pmin=pmin.replace(/\d+(\.\d*)?/,pval.toFixed(2));}this.
setField('price',(pmin!=this.models[0].price.price?'<span class="priceChanged">'+pmin+'</span>':pmin));return pval;};productModelObj.prototype.setField=function(fld,
val){var e;if(this.fields[fld])for(var i=0;e=this.fields[fld][i];i++){if(e.tagName=='INPUT')e.value=val;else e.innerHTML=val;}};productModelObj.prototype.buyNow=function(
frm){if(this.pidElement.value){if(frm.wishListClicked){frm.wishListClicked=false;return true;}if(window.addToCart){window.addToCart(frm);return false;}return true;}
if(this.msgSelect)this.msgSelect.style.display='';return false;};