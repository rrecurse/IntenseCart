
<script language="javascript">

function ReloadExpressCart(ac,pid,qty,attr) {
  var url='<?=HTTPS_SERVER?>/express_cart.php';
  if (pid) {
    url+='?pID='+escape(pid);
    url+='&attr='+escape(attr);
    url+='&'+(ac?ac:'add')+'_qty='+qty;
  }
  new ajax(url, {method: 'get', update: $('express_cart')});
}

function ReloadAddProduct(cat,pid) {
  var url='<?=HTTPS_SERVER?>/express_cart_add.php';
  if (cat) {
    url+='?add_category_id='+cat;
    if (pid) url+='&add_product_id='+pid;
  }
  new ajax(url, {method: 'get', update: $('express_cart_add')});
}

var ProductQty;
var ProductAttr;

function AddProductReset() {
 ProductQty=1;
 ProductAttr=Array();
}

function AddProductSetAttr(attr,val) {
 ProductAttr[attr]=val;
}

function AddProductSetQty(qty) {
 ProductQty=qty;
}

function AddProduct(pid) {
 var attrl=Array();
 for (var k in ProductAttr) if (ProductAttr[k]>0) attrl[attrl.length]=k+':'+ProductAttr[k];
 ReloadExpressCart('add',pid,ProductQty,attrl.join(','));
 ReloadAddProduct();
}

</script>

<div id="express_cart">Loading...</div>
<div id="express_cart_add">Loading...</div>

<script language="javascript">
  ReloadExpressCart();
  ReloadAddProduct();
</script>

<?
?>
