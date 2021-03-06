<?php
class blk_shopping_cart_popup extends IXblock {
  function render($body) {
?>

<div style="position:relative;overflow:visible;width:100%;height:1px; z-index:101;" id="shopping_cart_box" onMouseover="cartBoxObj.showBox(0,'shopping_cart_list_popup')" onMouseout="cartBoxObj.hideBox(null,'shopping_cart_list_popup');">
<?php
  $cont=new blk_shopping_cart_popup_content();
  $cont->setContext($this->context,$this->args);
  $cont->render(NULL);
?>
</div>

<script type="text/javascript">

window.cartBoxObj={
  pos:0,
  interval:50,
  step:0.2,
  setBox:function(pos) {
    var blk=this.blk;
    if (!blk) return;
    var pblk=blk.parentNode.parentNode;
    var vf=1||(pblk.offsetY>=pblk.offsetX);
    pblk.style.height=blk.offsetHeight+'px';
    pblk.style.width=blk.offsetWidth+'px';
    blk.style.top=(vf?Math.floor(blk.offsetHeight*(pos-1)):0)+'px';
    blk.style.left=(vf?0:Math.floor(blk.offsetWidth*(pos-1)))+'px';
    pblk.style.visibility=(pos>0)?'visible':'hidden';
    this.pos=pos;
  },
  moveTo:function(pos,callbk) {
    var obj=this;
    var step=(pos-this.pos)/Math.floor((pos-this.pos)*10+0.5);
    if (this.boxInt) clearInterval(this.boxInt);
    this.boxInt=window.setInterval(function() {
      if (Math.abs(obj.pos-pos)<=obj.step) {
	obj.setBox(pos);
	clearInterval(obj.boxInt);
	if (callbk) callbk();
      } else obj.setBox(pos>obj.pos?obj.pos+obj.step:obj.pos-obj.step);
    },this.interval);
  },
  showBox:function(time,bx,chk) {
    if (!bx) bx='shopping_cart_popup';
    if (this.blk && this.blk.id!=bx) return chk?false:this.hideBox(function() { this.showBox(time,bx); }.bind(this));
    this.blk=$(bx);
    for (var e=$(bx).parentNode.firstChild;e;e=e.nextSibling) { if (e.id) e.style.display=(e.id==bx?'':'none');}
    this.moveTo(1);
    if (time) {
      var obj=this;
      this.boxOffTm=window.setTimeout(function() { obj.hideBox() },time*1000);
    }
  },
  hideBox:function(callbk,bx) {
    if (bx && this.blk && this.blk.id!=bx) return false;
    if (this.boxOffTm) window.clearTimeout(this.boxOffTm);
    this.boxOffTm=null;
    if (!this.blk) return callbk?callbk():false;
    this.moveTo(0,function() { this.blk=null; if (callbk) callbk(); }.bind(this));
  }
};

function addToCart(frm) {

	var blk = $('shopping_cart_box');

	try { 
		blk.getElementsByTagName('a')[0].focus() 
	} catch(e) { 
		alert(e); 
	};

  var e;
  var post=[];

	if (frm.tagName) {
    	for (var i=0;e=frm.elements[i];i++) {
			if(!(e.type=='checkbox' || e.type=='radio') || e.checked) {
				post.push(escape(e.name)+'='+escape(e.value));
			}
		}
	} else {

		for (var k in frm) {
			post.push(escape(k)+'='+escape(frm[k]));
		}

	}
	
	var fn=function() {
		//window.cartBoxObj=null;
    	new ajax('<?php echo DIR_WS_CATALOG?>shopping_cart_popup.php?action=add_product',{method:'post', postBody:post.join('&'), update:blk});
	}
	if(window.cartBoxObj) cartBoxObj.hideBox(fn);
}
</script>
<?php 
  }
}

class blk_shopping_cart_popup_content extends IXblock {

  function jsObjectName() {
    return 'cartPopupBox_'.$this->makeID();
  }

  function render($body) {
    global $currencies;
    $cart_item_count = $this->context['cart']->count_contents();
    if($cart_item_count == 1) {
      $itemdesc = 'Item';
    } else {
      $itemdesc = 'Items';
    }

    @include_once(DIR_FS_CATALOG_LAYOUT.'languages/english/shopping_cart.php');
    
    $pf=new PriceFormatter;

	// # strip any added html tags to the currency class (like structured data spans and meta tags).
	$showTotal = strip_tags(trim($currencies->format($this->context['cart']->show_total())));


?>
<div class="cartbox_carticon" onclick="location.href='/shopping_cart.php'"></div>
<div class="cartbox_title"><? if (defined('HEADER_TITLE_CART_CONTENTS')) echo HEADER_TITLE_CART_CONTENTS; else { ?>
Cart Contents<? } ?></div>
<div class="cartbox_content"><? if (defined('SHOPCART_CONTAINS')) {
echo SHOPCART_CONTAINS . ' &nbsp;<a href="'.tep_href_link(FILENAME_SHOPPING_CART).'" class="cartbox_content_link" rel="nofollow">'.$cart_item_count.' '.$itemdesc.'</a>';
} else { ?>
Your cart contains &nbsp;<a href="<?php echo tep_href_link(FILENAME_SHOPPING_CART);?>" class="cartbox_content_link" rel="nofollow"><?php echo $cart_item_count . ' ' . $itemdesc;?></a><? } ?></div>

<div class="cartbox_subtotal">
	<?php if(defined('SHOPCART_SUBTOTAL')) { 
		printf(SHOPCART_SUBTOTAL,$currencies->format($this->context['cart']->show_total())); 
	} else { 
	?>
		<a href="<?php echo tep_href_link(FILENAME_SHOPPING_CART);?>" class="cartbox_subtotal_link" rel="nofollow">Sub-total: <?php echo $showTotal;?></a>
	<?php } ?>
</div>

<div class="cartbox_viewcart">

<?php if (defined('SHOPCART_VIEW')) echo '<a href="/shopping_cart.php" class="shopping_cart_contents_viewcart" rel="nofollow">' . SHOPCART_VIEW . '</a>'; else { ?>
<a href="/shopping_cart.php" class="shopping_cart_contents_viewcart" rel="nofollow">view cart</a>
<? } ?>

</div>


<div class="cartbox_popup" style="position:absolute;overflow:hidden;z-index:10;visibility:hidden;top:16px">
<div style="position:relative;overflow:visible;top:0px;left:0px">
<div class="shopping_cart_list_popup" style="position:absolute;z-index:10;" id="shopping_cart_list_popup">
<?php
    $products = $this->context['cart']->get_products();
    if ($products) {
?>
<table border="0" cellspacing="0" cellpadding="5" bgcolor="#FFFFFF" class="shopping_cart_contents">
<tr>
<td colspan="2" class="shopping_cart_contents_name"><u>Product</u></td>
<td class="shopping_cart_contents_qnty"><u>Qnty.</u></td>
<td class="shopping_cart_contents_price"><u>Price</u></td>
</tr>
<?php
      foreach ($products AS $product) {
        $pf->loadprice($product['id']);
?>

<tr style="padding-top:3px;">
  <td valign="top" colspan="2" class="shopping_cart_contents_name"><?php echo htmlspecialchars($product['name'])?></td>
 <td valign="top" class="shopping_cart_contents_qnty"><?php echo $product['quantity']?> </td>
  <td valign="top" class="shopping_cart_contents_price"> <?php echo $pf->displayPrice($pf->getPrice())?></td>
</tr>
<?php
      }
?>
<tr>
	<td align="right" colspan="4" class="shopping_cart_contents_viewcart">
<?php 
	if (defined('SHOPCART_VIEW')) {
		echo '<a href="/shopping_cart.php" class="shopping_cart_contents_viewcart" rel="nofollow">' . SHOPCART_VIEW . '</a>'; 
	} else { 
		echo '<a href="/shopping_cart.php" class="shopping_cart_contents_viewcart" rel="nofollow">view cart</a>';
	} 
?>
</td></tr></table>

<?php } else {  ?>

     <div class="shopping_cart_contents_empty">
<?php echo (defined('SHOPCART_EMPTY')) ? SHOPCART_EMPTY : 'Your shopping cart is empty';?>
</div>
<?php } ?>

</div>

<div class="shopping_cart_popup" style="position:absolute;left:0px;top:0px;z-index:10;" id="shopping_cart_popup" onMouseover="event.cancelBubble=true; return false;" onMouseout="event.cancelBubble=true; return false;">
<?php
    $product = $this->context['cart']->get_last_product();
 if($product) {
      $pf->loadprice($product['id']);
?>
<div class="shopping_cart_popup_added" style="position:relative; z-index:10;">
<div class="shopping_cart_popup_added-title" style="position:relative; z-index:10;">

<?php 
	if(defined('SHOPCART_ADDED')) {
		echo SHOPCART_ADDED; 
	} else { 
		echo 'Item(s) Added To' . (defined('HEADER_TITLE_CART_CONTENTS') ? HEADER_TITLE_CART_CONTENTS :  'Your Cart'); 
	} 
?>
</div>
<div class="shopping_cart_popup_added-img" style="position:relative; z-index:10;">
<?php echo tep_image(DIR_WS_IMAGES . $product['image'], $product['name'], AUTOSUGGEST_THUMB_WIDTH, AUTOSUGGEST_THUMB_HEIGHT)?>
</div>
  <div class="shopping_cart_popup_added-qty" style="position:absolute; z-index:10;"><?php echo $product['quantity']?> </div>
  <div class="shopping_cart_popup_added-price" style="position:absolute; z-index:10;"> <?php echo $pf->displayPrice($pf->getPrice())?></div>
  <div class="shopping_cart_popup_added-viewcart" style="position:absolute; z-index:10;">
<?php 
	if(defined('SHOPCART_VIEW')) {
		echo '<a href="/shopping_cart.php" class="shopping_cart_contents_viewcart" rel="nofollow">' . SHOPCART_VIEW . '</a>'; 
	} else { 
		echo '<a href="/shopping_cart.php" class="shopping_cart_contents_viewcart" rel="nofollow">view cart</a>';
	} 
?>
</div>
</div>
<?php
    } // # END if($products)
?>
</div>
</div>
</div>
<?php
  }
}
?>