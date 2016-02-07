<?php
/*
  $Id: PriceFormatter.php,v 1.6 2003/06/25 08:29:26 petri Exp $

  PriceFormatter.php - module to support quantity pricing

*/

class PriceFormatter {
  var $hiPrice;
  var $lowPrice;
  var $quantity;
  var $hasQuantityPrice;

  function PriceFormatter($prices=NULL) {
    $this->productsID = -1;

    $this->hasQuantityPrice=false;
    $this->hasSpecialPrice=false;

    $this->hiPrice=-1;
    $this->lowPrice=-1;

    for ($i=1; $i<=8; $i++){
      $this->quantity[$i] = -1;
      $this->prices[$i] = -1;
    }
    $this->thePrice = -1;
    $this->specialPrice = -1;
    $this->qtyBlocks = 1;

    if($prices)
      $this->parse($prices);
  }

  function encode() {
	$str = $this->productsID . ":"
	       . (($this->hasQuantityPrice == true) ? "1" : "0") . ":"
	       . (($this->hasSpecialPrice == true) ? "1" : "0") . ":"
	       . $this->quantity[1] . ":"
	       . $this->quantity[2] . ":"
	       . $this->quantity[3] . ":"
	       . $this->quantity[4] . ":"
		   . $this->quantity[5] . ":"
		   . $this->quantity[6] . ":"
		   . $this->quantity[7] . ":"
	       . $this->quantity[8] . ":"
	       . $this->price[1] . ":"
	       . $this->price[2] . ":"
	       . $this->price[3] . ":"
	       . $this->price[4] . ":"
		   . $this->price[5] . ":"
		   . $this->price[6] . ":"
		   . $this->price[7] . ":"
	       . $this->price[8] . ":"
	       . $this->thePrice . ":"
	       . $this->specialPrice . ":"
	       . $this->qtyBlocks . ":"
	       . $this->taxClass;
	return $str;
  }

  function decode($str) {
	list($this->productsID,
	     $this->hasQuantityPrice,
	     $this->hasSpecialPrice,
	     $this->quantity[1],
	     $this->quantity[2],
	     $this->quantity[3],
	     $this->quantity[4],
	     $this->quantity[5],
	     $this->quantity[6],
	     $this->quantity[7],
	     $this->quantity[8],
	     $this->price[1],
	     $this->price[2],
	     $this->price[3],
	     $this->price[4],
	     $this->price[5],
	     $this->price[6],
	     $this->price[7],
	     $this->price[8],
	     $this->thePrice,
	     $this->specialPrice,
	     $this->qtyBlocks,
	     $this->taxClass) = explode(":", $str);

	$this->hasQuantityPrice = (($this->hasQuantityPrice == 1) ? true : false);
	$this->hasSpecialPrice = (($this->hasSpecialPrice == 1) ? true : false);
  }

  function parse($prices,$discounts=NULL,$xsell=NULL) {
    $this->productsID = $prices['products_id'];
    $this->hasQuantityPrice=$discounts&&true;
    $this->hasSpecialPrice=false;

    $this->discounts=Array();
    if ($discounts) foreach ($discounts AS $dsc) $this->discounts[$dsc['discount_qty']]=$dsc['discount_percent'];

    $this->thePrice = isset($prices['customers_group_price']) ? number_format((float)$prices['customers_group_price'],2) : number_format((float)$prices['products_price'],2);
    $this->specialPrice=$prices['specials_new_products_price'];
    
    if ($xsell) {
      $this->crossPrice = number_format( (float) max(0,$this->thePrice * (100+$xsell['price_percent']) / 100 + $xsell['price_diff']),2);
      if (!$this->specialPrice || $this->crossPrice < $this->specialPrice) {
        $this->specialPrice = $this->crossPrice;
		$this->crossItem = $xsell['products_id'];
      }
    }
    
    $this->hasSpecialPrice = isset($this->specialPrice) && $this->specialPrice < $this->thePrice-.005;

	// # Change support special prices
	// # If any price level has a price greater than the special price lower it to the special price
/*
	if ($this->hasSpecialPrice == true) {
		for($i=1; $i<=8; $i++) {
			if ($this->price[$i] > $this->specialPrice)
				$this->price[$i] = $this->specialPrice;
		}
	}
	//end changes to support special prices
*/

    $this->qtyBlocks=$prices['products_qty_blocks'];

    $this->taxClass=$prices['products_tax_class_id'];

    $this->hiPrice = $this->thePrice;
    $this->lowPrice = $this->thePrice*(1-($this->discounts?max($this->discounts)/100:0));
  }

function loadProduct($product_id, $language_id=1, $customer_group_id=NULL,$xsell=NULL) {

	global $sppc_customer_group_id;

    if(!isset($customer_group_id)) {
		$customer_group_id = isset($sppc_customer_group_id) ? $sppc_customer_group_id+0 : 0;
	}

	if($language_id < 1) {
		$language_id = 1;
	}

    $product_info_query = tep_db_query("SELECT pd.products_name, 
				   						p.*, 
									    pg.customers_group_price, 
										pg.customers_group_id,
										IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price,
										IF(s.status, s.specials_new_products_price, p.products_price) as final_price,
										m.manufacturers_name,
										cd.categories_name,
										pwi.products_warehouse_id as warehouse_id
										FROM ". TABLE_PRODUCTS ." p
										LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION ." pd ON (pd.products_id = p.master_products_id AND pd.language_id = '". (int)$language_id ."')
										LEFT JOIN ". TABLE_MANUFACTURERS ." m ON m.manufacturers_id = p.manufacturers_id
										LEFT JOIN ". TABLE_SPECIALS ." s ON  s.products_id = p.master_products_id
										LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON (pg.products_id = p.products_id AND pg.customers_group_id = '". $customer_group_id ."')
										LEFT JOIN ". TABLE_PRODUCTS_TO_CATEGORIES . " p2c ON p2c.products_id = p.master_products_id
										LEFT JOIN ". TABLE_CATEGORIES_DESCRIPTION ." cd ON cd.categories_id = p2c.categories_id
										LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY . " pwi ON pwi.products_id = p.products_id
										WHERE p.products_status = '1'
										AND p.products_id = '". (int)$product_id ."'
										");
	
		$product_info = tep_db_fetch_array($product_info_query);

		$dsc_rows = array();

		$dsc_query = tep_db_query("SELECT * FROM products_discount 
								   WHERE products_id = '".$product_info['master_products_id']."' 
								   AND customers_group_id = '".$product_info['customers_group_id']."'
								   ORDER BY discount_qty
								  ");

		while($dsc_row = tep_db_fetch_array($dsc_query)) {
			$dsc_rows[]=$dsc_row;
		}

		$xsell_row = NULL;
		if ($xsell) {
			$xsell_row = tep_db_read("SELECT x.* 
									  FROM products_xsell x
									  LEFT JOIN products mp ON (mp.products_id = x.products_id OR mp.master_products_id = x.products_id)
									  LEFT JOIN products p ON (p.products_id = x.xsell_id OR p.master_products_id = x.xsell_id)
									  WHERE mp.products_id = '".$xsell."' 
									  AND p.products_id = '".$product_id."'
									  ORDER BY price_percent, price_diff LIMIT 1
									");

		}
    $this->parse($product_info,$dsc_rows,$xsell_row);

    return $product_info;
  }

  function computePrice($qty=1) {

	$qty = $this->adjustQty($qty);
	$price = $this->thePrice;

	for ($q=$qty;$q>0;$q--) {
		if(isset($this->discounts[$q])) {
			return $price * (1 - $this->discounts[$q] / 100);
		}
	}

	if($this->hasSpecialPrice) {
		$price = min($price,$this->specialPrice);
	}

	return $price;
  }

  function getPriceArray() {
    $pr=Array('price'=>$this->displayPrice($this->thePrice),'quantity'=>Array());
    foreach ($this->discounts AS $qty=>$dsc) {
      $pr['quantity'][$qty]=$this->displayPrice($this->thePrice*(1-$dsc/100));
    }
    return $pr;
  }

  function displayPrice($p=NULL,$nospecial=false) {
    global $currencies;
    if (!isset($p)) $p=$this->thePrice;
    if (!$nospecial && $this->hasSpecialPrice && $this->specialPrice<$p) $p=$this->specialPrice;

	// # strip any added html tags to the currency class (like structured data spans and meta tags).
	$display_price = strip_tags(trim($currencies->display_price($p, tep_get_tax_rate($this->taxClass))));

    return $display_price;
  }

  function adjustQty($qty) {
	// Force QTY_BLOCKS granularity
	$qb = $this->getQtyBlocks();
	if ($qty < 1)
		$qty = 1;

	if ($qb >= 1)
	{
		if ($qty < $qb)
			$qty = $qb;

		if (($qty % $qb) != 0)
			$qty += ($qb - ($qty % $qb));
	}
	return $qty;
  }

  function getQtyBlocks() {
    return $this->qtyBlocks;
  }

  function getPrice() {
    return $this->thePrice;
  }

  function getLowPrice() {
    return $this->lowPrice;
  }

  function getHiPrice() {
    return $this->hiPrice;
  }

  function hasSpecialPrice() {
    return $this->hasSpecialPrice;
  }

  function hasQuantityPrice() {
    return $this->hasQuantityPrice;
  }

  function getPriceString($style='productPriceInBox') {
    global $currencies;

	// # strip any added html tags to the currency class (like structured data spans and meta tags).
	$thePrice = strip_tags(trim($currencies->display_price($this->thePrice,tep_get_tax_rate($this->taxClass))));

    if ($this->hasSpecialPrice == true) {

		// # strip any added html tags to the currency class (like structured data spans and meta tags).
		$specialPrice = strip_tags(trim($currencies->display_price($this->specialPrice,tep_get_tax_rate($this->taxClass))));

    	$lc_text = '<table align="top" border="1" cellspacing="0" cellpadding="0">';
        $lc_text .= '<tr><td align="center" class=' . $style. ' colspan="2">';

	    $lc_text .= '&nbsp;<s>'.$thePrice. '</s>&nbsp;&nbsp;<span class="productSpecialPrice">' . $specialPrice . '</span>&nbsp; </td></tr>';

    } else {

		$lc_text = '<table align="top" border="1" cellspacing="0" cellpadding="0">';
		$lc_text .= '<tr><td align="center" class=' . $style. ' colspan="2">'. $thePrice . '</td></tr>';
    }
      // If you want to change the format of the price/quantity table
      // displayed on the product information page, here is where you do it.

	if($this->hasQuantityPrice == true) {

		foreach ($this->discounts AS $qty=>$dsc) {
			$lc_text .= '<tr><td class='.$style.'>'. $qty .'+ </td><td class='.$style.'>'. $this->displayPrice($this->thePrice*(1-$dsc/100))	.'</td></tr>';
		}

		$lc_text .= '</table>';

   	} else {

		$lc_text = '&nbsp;' . $thePrice . '&nbsp;';
	}

    return $lc_text;
  }

		
  function getPriceBreaks($style='productPriceInBox') {

	global $currencies;

	$prc = $this->getPriceArray();

      // # If you want to change the format of the price/quantity table
      // # displayed on the product information page, here is where you do it.

    $lc_text='';

	if($prc['quantity']) {

		$lc_text = '<div class="productPriceInBox_title">&nbsp;Quantity Discounts:</div>
						<table class="'.$style.'">';

			foreach ($prc['quantity'] AS $qty=>$p) {

				$lc_text .= '<tr><td>Quantity:&nbsp;<b>'.$qty.'pcs.</b> &nbsp;</td>
								 <td>Per item:&nbsp; <font style="font:bold 12px arial; color:#B65B34">'.$p.'</font></td>
							</tr>';
			}

		$lc_text .= '</table>';
	}
		return $lc_text;
	}


function getPriceStringShort() {
    global $currencies;

	// # strip any added html tags to the currency class (like structured data spans and meta tags).
	$thePrice = strip_tags(trim($currencies->display_price($this->thePrice, tep_get_tax_rate($this->taxClass))));

    if ($this->hasSpecialPrice == true) {

		// # strip any added html tags to the currency class (like structured data spans and meta tags).
		$specialPrice = strip_tags(trim($currencies->display_price($this->specialPrice, tep_get_tax_rate($this->taxClass))));

      $comm = (isset($this->crossItem)) ? 'Purchased with '.htmlspecialchars(tep_get_products_name($this->crossItem)) : 'Special Price';
      $lc_text = '&nbsp;<s title="'.$comm.'">'. $thePrice . '</s> <span class="productSpecialPrice" title="'.$comm.'">'.$specialPrice.'</span>&nbsp;';

    } else {

		if($this->hasQuantityPrice == true) {

			// # strip any added html tags to the currency class (like structured data spans and meta tags).
			$lowPrice = strip_tags(trim($currencies->display_price($this->lowPrice, tep_get_tax_rate($this->taxClass))));
			$hiPrice = strip_tags(trim($currencies->display_price($this->hiPrice, tep_get_tax_rate($this->taxClass))));
		
			$lc_text = '&nbsp;' . $lowPrice . ' - ' . $hiPrice . '&nbsp;';
		
		} else {
		
			$lc_text = '&nbsp;' . $thePrice . '&nbsp;';
		}
	}

	return $lc_text;
  }
  
  function loadprice($product_id)  {
    return $this->loadProduct($product_id);
/*
// Returns only price related variables, lighter DB query than the LoadProduct method
    global $sppc_customer_group_id;
    $customer_group_id=isset($sppc_customer_group_id)?$sppc_customer_group_id+0:0;

$sql = "SELECT p.products_id, p.products_price, pg.customers_group_price,
		p.products_qty_blocks, p.products_tax_class_id, 
		IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, 
		IF(s.status, s.specials_new_products_price, p.products_price) as final_price 
		FROM " . TABLE_PRODUCTS . " p left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id
		LEFT JOIN " . TABLE_PRODUCTS_GROUPS . " pg ON p.products_id=pg.products_id AND pg.customers_group_id='$customer_group_id'
		WHERE p.products_id ='".(int)$product_id."'";



    $product_price_query = tep_db_query($sql);
    $product_price = tep_db_fetch_array($product_price_query);
    $dsc_rows=Array();
    $dsc_query=tep_db_query("SELECT * FROM products_discount WHERE products_id='$product_is' AND customers_group_id='$customer_group_id'");
    while ($dsc_row=tep_db_fetch_array($dsc_query)) $dsc_rows[]=$dsc_row;
    $this->parse($product_price,$dsc_rows);

    return $product_price;
*/
  }
  
}

?>
