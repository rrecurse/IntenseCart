<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	if(isset($_GET['show']) && $_GET['show'] == 'all') {

		define('MAX_DISPLAY_SEARCH_RESULTS', 10000);
	}

	define ('HEADING_TITLE','Competitive Pricing');
	require('includes/application_top.php');

	$channel = (isset($_GET['channel']) ? preg_replace("/[^ \w]+/", "", $_GET['channel']) : 'dbfeed_amazon_us');

	if($channel == 'dbfeed_amazon_us') { 

		$tld = '.com';

	} elseif($channel == 'dbfeed_amazon_ca') { 

		$tld = '.ca';

	} 

	$base_url = "https://mws.amazonservices".$tld."/Products/2011-10-01";
	$host = "mws.amazonservices".tld;
	$uri = "/Products/2011-10-01";


 function amazon_xml($ASIN, $channel) {

	global $tld;
	$tld = $tld;

	$amazon_channel = $channel;

	date_default_timezone_set('GMT');
	$time = (date('I') == 1) ? time()+(STORE_TZ - 1)*3600: time()+STORE_TZ*3600;

	if($amazon_channel == 'dbfeed_amazon_us') { 

		$params = array(
			'AWSAccessKeyId' => "AKIAIVTXALQIYVBPKSUQ",
			'Action' => "GetLowestOfferListingsForASIN",
			'SellerId' => "A1O77D5UJY7IVU",
			'SignatureVersion' => "2",
			'Timestamp'=> date("Y-m-d\TH:i:s.\\0\\0\\0\\Z", strtotime("tomorrow")),
			'Version'=> "2011-10-01",
			'SignatureMethod' => "HmacSHA256",
			'MarketplaceId' => "ATVPDKIKX0DER",
			'ASINList.ASIN.1' => $ASIN
		);

	} elseif($amazon_channel == 'dbfeed_amazon_ca') { 

		$params = array(
			'AWSAccessKeyId' => "AKIAIBQFGBSL2LXV5YCA",
			'Action' => "GetLowestOfferListingsForASIN",
			'SellerId' => "A1XCDE6K72W6D6",
			'SignatureVersion' => "2",
			'Timestamp'=> date("Y-m-d\TH:i:s.\\0\\0\\0\\Z", strtotime("tomorrow")),
			'Version'=> "2011-10-01",
			'SignatureMethod' => "HmacSHA256",
			'MarketplaceId' => "A2EUQ1WTGCTBG2",
			'ASINList.ASIN.1' => $ASIN
		);

	} else { // # Default back to Amazon US 

		$params = array(
			'AWSAccessKeyId' => "AKIAIVTXALQIYVBPKSUQ",
			'Action' => "GetLowestOfferListingsForASIN",
			'SellerId' => "A1O77D5UJY7IVU",
			'SignatureVersion' => "2",
			'Timestamp'=> date("Y-m-d\TH:i:s.\\0\\0\\0\\Z", strtotime("tomorrow")),
			'Version'=> "2011-10-01",
			'SignatureMethod' => "HmacSHA256",
			'MarketplaceId' => "ATVPDKIKX0DER",
			'ASINList.ASIN.1' => $ASIN
		);

	}

	$url_parts = array();

	foreach(array_keys($params) as $key)
    	$url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params[$key]));
	sort($url_parts);

	// # Construct the string to sign
	$url_string = implode("&", $url_parts);

	$string_to_sign = "GET\nmws.amazonservices".$tld."\n/Products/2011-10-01\n" . $url_string;

	if($amazon_channel == 'dbfeed_amazon_us') { 

		// # Sign the request
		$signature = hash_hmac("sha256", $string_to_sign, 'DVtZjILPE8EkFwAOrWhw5IqCjlPr5Cm2G9vx8vbB', TRUE);

	} elseif($amazon_channel == 'dbfeed_amazon_ca') { 
	
		// # Sign the request
		$signature = hash_hmac("sha256", $string_to_sign, 'ztU+rczcQwfPzFMghfuiFlKb+5ImrsNLKmjn/6Do', TRUE);

	} else { 

		// # Sign the request
		$signature = hash_hmac("sha256", $string_to_sign, 'DVtZjILPE8EkFwAOrWhw5IqCjlPr5Cm2G9vx8vbB', TRUE);
		
	}


	// # Base64 encode the signature and make it URL safe
	$signature = urlencode(base64_encode($signature));

	$url = "https://mws.amazonservices".$tld."/Products/2011-10-01" . '?' . $url_string . "&Signature=" . $signature;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	//curl_setopt($ch, CURLOPT_RANGE, $a.'-'.$b);

	$response = curl_exec($ch);
	$response = preg_replace( "/\r|\n/", "", $response);

	$AmazonListings = simplexml_load_string($response);

	$ListingPrice = '';
	$LandedPrice = '';
	$itemCondition = '';

	$ListingPriceArray = array();
	$itemConditionArray = array();
	$LandedPriceArray = array();


	if(!empty($AmazonListings->GetLowestOfferListingsForASINResult->Product->LowestOfferListings->LowestOfferListing) && $AmazonListings->GetLowestOfferListingsForASINResult->Product->LowestOfferListings->LowestOfferListing->Qualifiers->ItemCondition != 'Used') { 
		foreach($AmazonListings->GetLowestOfferListingsForASINResult->Product->LowestOfferListings->LowestOfferListing as $child) {
	
			if ($child) {
				$ListingPrice .= $child->Price->ListingPrice->Amount;
				$LandedPrice .= $child->Price->LandedPrice->Amount;
				$itemCondition .= $child->Qualifiers->ItemCondition;

				$ListingPriceArray[] .= $child->Price->ListingPrice->Amount;
				$itemConditionArray[] .= $child->Qualifiers->ItemCondition;
				$LandedPriceArray[] .= $child->Price->LandedPrice->Amount;
			}
		}
	}


	if($ListingPriceArray && $itemConditionArray) { 
		$mergedResult = array_map(null, $ListingPriceArray, $itemConditionArray, $LandedPriceArray);
		return $mergedResult;
	} else { 
	 	echo '-';
	}
 }

  function _get_attribute_values_list($products_id) {

    $res = tep_db_query(
    "SELECT po.products_options_name, pov.products_options_values_name
        FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
        INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po
        ON (pa.options_id = po.products_options_id
        AND po.language_id = {$GLOBALS['languages_id']})
        INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
        ON (pa.options_values_id = pov.products_options_values_id
        AND pov.language_id = {$GLOBALS['languages_id']})
        WHERE pa.products_id = {$products_id} ");
    $values = array();
    while ($row = tep_db_fetch_array($res)) {
        $values[$row['products_options_name']][] = $row['products_options_values_name'];
    }
    reset($values);
    while (list($id, $value) = each($values)) {
        $result[$id] = implode(",", array_unique($value));
    }

	tep_db_free_result($res);
    return $result;
  }

if($_GET['action'] == "update" && is_array($_POST['products_price'])) {

    foreach($_POST['products_price'] as $products_id => $products_price) {

        $products_price = floatval($products_price);
   	    $products_id = intval($products_id);

		//# Update the prices!
		tep_db_query("UPDATE ".TABLE_PRODUCTS." 
					  SET products_price = '".$products_price."', 
					  products_last_modified = NOW(),
					  last_stock_change = NOW()
					  WHERE products_id = ".$products_id
					 );

		// # also update pricing group table.
		tep_db_query("UPDATE ".TABLE_PRODUCTS_GROUPS." 
					  SET customers_group_price = '" . $products_price."'
					  WHERE products_id = '".$products_id."'
					  AND customers_group_id = '0'
					 ");

		// # if price is set to zero, remove from feeds!
		if($products_price < 0.01) { 

			tep_db_query("DELETE FROM ".TABLE_DBFEED_PRODUCTS." 
						  WHERE products_id = '".$products_id."'
						 ");
			tep_db_query("OPTIMIZE TABLE ".TABLE_DBFEED_PRODUCTS);


			tep_db_query("DELETE FROM ".TABLE_DBFEED_PRODUCTS_EXTRA." 
						  WHERE products_id = '".$products_id."'
						 ");
			tep_db_query("OPTIMIZE TABLE ".TABLE_DBFEED_PRODUCTS_EXTRA);
		}
		
	}

} 

if($_GET['action'] == "update" && is_array($_POST['backorder'])) { 
	foreach ($_POST['backorder'] as $products_id => $dateAvailable) {
			$products_id = intval($products_id);
			$dateAvailable = (!empty($dateAvailable)) ? "'".date("Y-m-d H:s:i", strtotime($dateAvailable)). "'" : "NULL";

			if(date('Y-m-d 00:00:00', mktime()) > (str_replace("'","",$dateAvailable))) $dateAvailable = 'NULL';

			//# Update the backorder dates!
			tep_db_query("UPDATE ".TABLE_PRODUCTS." SET `last_stock_change` = NOW(), products_date_available = ".$dateAvailable." WHERE `products_id` = ".$products_id);

	}
}

if($_GET['action'] == "update" && is_array($_POST['cost_price'])) {

    foreach($_POST['cost_price'] as $products_id => $cost_price) {

        $cost_price = floatval($cost_price);
   	    $products_id = intval($products_id);

		//# Update the prices in the products table
		tep_db_query("UPDATE ".TABLE_PRODUCTS." 
					  SET products_price_myself = '".$cost_price."', 
					  products_last_modified = NOW() 
					  WHERE products_id = '".$products_id."'
					 ");

		//# Update the prices in the suppliers_products_groups for primary supplier only.
		tep_db_query("UPDATE suppliers_products_groups 
					  SET suppliers_group_price = '".$cost_price."'
					  WHERE products_id = '".$products_id."'
					  AND priority = 0
					 ");
	}
} 

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script src="includes/general.js"></script>

<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>

<style type="text/css">

.up {
    cursor: pointer;
    width: 11px;
    height: 11px;
    background: url("images/plusmin.gif") no-repeat scroll 0px 0px transparent;
}

.down {
    cursor: pointer;
    width: 11px;
    height: 11px;
    background: url("images/plusmin.gif") no-repeat scroll 0px -11px transparent;
}

</style>
</head>
<body style="background-color:transparent; margin:0;">

<script type="text/javascript">
$(document).ready( function() {

    var price='';
	var cost=''
	var gp='';
	var gpPercent='';
	var besticon='';
	var diff='';
	var surcharge='';
	var shipping_cost='';
	var fee='';
	var final='';
	var newVal ='';

    function change(incr,id,amt) {

		// # live price	
		price = $('#input_products_price_'+id);	

        if (price.val() == '' || price.val() <  0.00) {
        	newVal = 0.00;
        } else {
            newVal = parseFloat(amt).toFixed(2);
        }

        if (newVal > 0) {
            price.val(newVal);
        }

		cost = $('#div_cost_'+id).text();
		cost = parseFloat(cost.replace('$','')).toFixed(2);

		if($('#div_surcharge_'+id).length) { 
			surcharge = $('#div_surcharge_'+id).text();
			surcharge = parseFloat(surcharge.replace('Surcharge: + $','')).toFixed(2);
		} else {
			surcharge = parseFloat(0).toFixed(2);
		}

		if($('#div_shipping_cost_'+id).length) { 
			shipping_cost = $('#div_shipping_cost_'+id).text();
			if(shipping_cost.search("FREE") > 0) {
				shipping_cost = parseFloat(0).toFixed(2);
			} else {
				shipping_cost = parseFloat(shipping_cost.replace('Shipping: - $','')).toFixed(2);
			}
		} else {
			shipping_cost = parseFloat(0).toFixed(2);
		}

		// # gross profit
		gp = $('#div_gp_'+id).text();
		gp = parseFloat(gp.replace('$','')).toFixed(2);

		
		// # aggregate all charges into one price
		var newPrice = (+(newVal) + +(surcharge));

		var priceWsurcharge = (+(newVal) + +(surcharge));	

		// # amazon fee's
		fee = $('#div_fee_'+id).text();
		fee	= parseFloat(fee.replace('$','')).toFixed(2);

		newFee = parseFloat((1 + (12 / 100) * priceWsurcharge)).toFixed(2);

		if(fee != newFee) {
			$('#div_fee_'+id).text('$'+newFee);
		}

		var newGP = parseFloat(+(newPrice) - +(cost) - +(newFee)).toFixed(2);

		if(gp != newGP) {
			$('#div_gp_'+id).text('$'+newGP)
		}

		if(newGP < 0) {
			$('#div_gp_'+id).css({'color':'#FF0000'});
		} else {
			$('#div_gp_'+id).css({'color':'#000000'});
		}
		
		//if(newPrice != amt) {
			$('#div_totalPrice_'+id).text('Total: $'+(newPrice + +(shipping_cost)).toFixed(2));
		//}

		// # gross profit by percentage
		gpPercent = $('#div_gpPercent_'+id).text();
		gpPercent = parseFloat(gpPercent.replace('%','')).toFixed(2);

		final = parseFloat( (priceWsurcharge - newFee - +(cost)) ).toFixed(2);

		newGPpercent = Math.max( Math.ceil(parseFloat((final / priceWsurcharge) * 100) * 100) / 100, 0).toFixed(2);

		if(gpPercent != newGP) {
			$('#div_gpPercent_'+id).text(newGPpercent+'%')
		}

		if(newGPpercent < 0) {
			$('#div_gpPercent_'+id).css({'color':'#FF0000'});
		} else {
			$('#div_gpPercent_'+id).css({'color':'#000000'});
		}

		var amazonBestPrice = parseFloat($('#amazonBestPrice_'+id).val()).toFixed(2);
	
		// # the difference in percentage
		diff = $('#div_diff_'+id).text();
		diff = parseFloat(diff.replace('%','')).toFixed(2);

		var newDiff = parseFloat((+(amazonBestPrice) - (priceWsurcharge + +(shipping_cost)) ) / +(amazonBestPrice) * 100).toFixed(2);

		if((+(priceWsurcharge) + +(shipping_cost)) < +(amazonBestPrice)) {
			$('#div_diff_'+id).html('<font style="color:green">'+newDiff+'%</font>');
			$('#div_besticon_'+id).html('<img src="/admin/images/icons/success.gif">');
		} else if((+(priceWsurcharge) + +(shipping_cost)) > +(amazonBestPrice)) {
			$('#div_diff_'+id).html('<font style="color:red">'+newDiff+'%</font>');
			$('#div_besticon_'+id).html('<img src="/admin/images/icons/warning.gif">');
		} else {
			$('#div_diff_'+id).html('<font style="color:black">-</font>');
			$('#div_besticon_'+id).html('<img src="/admin/images/icons/success.gif">');
		}
    }

    $('.up').click( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("up_","");

		var incr = 1.00;

		var amt = parseFloat($('#input_products_price_'+theid).val()) + incr;

        change(incr, theid, amt);
    });

    $('.down').click( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("down_","");
		var incr = -1.00;

		var amt = parseFloat($('#input_products_price_'+theid).val()) + incr;

        change(incr,theid, amt);
    });

    $('.theCost').dblclick( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("theCost_","");

		if($('#div_cost_'+theid).is(":visible")){
			$('#cost_price_'+theid).prop('type', 'text');
			$('#cost_price_'+theid).addClass('price_input');
			$('#cost_price_'+theid).css({'width':'40px'});
			$('#cost_price_'+theid).focus();
			$('#div_cost_'+theid).hide();			
		} else {
			$('#cost_price_'+theid).prop('type', 'hidden');
			$('#div_cost_'+theid).html('$'+$('#cost_price_'+theid).val());
			$('#div_cost_'+theid).show();
		}
    });

	$('.cost_input').bind("change blur", function(e) {
		e.preventDefault(); 

		var theid = $(this).attr("id");
		theid = theid.replace("cost_price_","");	

		$('#div_cost_'+theid).html('$'+$('#cost_price_'+theid).val());

		var incr = 0;
		var amt = parseFloat($('#input_products_price_'+theid).val()) + incr;
        change(incr,theid, amt);


		if($('#div_cost_'+theid).is(":hidden")){
			$('#cost_price_'+theid).prop('type', 'hidden');
			$('#div_cost_'+theid).show();	
		}

    });

	$('.thePrice').change( function() {

		var theid = $(this).attr("id");
		theid = theid.replace("input_products_price_","");
		var incr = 0;

		var amt = parseFloat($(this).val()) + incr;

        change(incr,theid, amt);
    });

	$('#channel').bind('change', function () {

		var channel = $(this).val();
		var locate = location.pathname;
		locate = locate.replace('/admin/','');

		if(channel) {
			window.location = locate+'?channel='+channel; // redirect
		}

		return false;
	});	
});
</script>

<?php require(DIR_WS_INCLUDES . 'header.php'); 


	switch($sort) {

		case 'sortbynameDESC':
			$sortby = 'pd.products_name DESC, p.products_model';
		break;

		case 'sortbymodelASC':
			$sortby = 'p.products_model ASC, pd.products_name';
		break;

		case 'sortbymodelDESC':
			$sortby = 'p.products_model DESC, pd.products_name';
		break;

		case 'sortbycostASC':
			$sortby = 'p.products_price_myself ASC, pd.products_name ASC';
		break;

		case 'sortbycostDESC':
			$sortby = 'p.products_price_myself DESC, pd.products_name';
		break;

		case 'sortbyretailpriceASC':
			$sortby = 'p.products_price ASC, pd.products_name';
		break;

		case 'sortbyretailpriceDESC':
			$sortby = 'p.products_price DESC, pd.products_name';
		break;

		case 'sortbyfeeASC':
			$sortby = 'feePreview ASC, pd.products_name';
		break;

		case 'sortbyfeeDESC':
			$sortby = 'feePreview DESC, pd.products_name';
		break;

		case 'sortbygpASC':
			$sortby = 'gp ASC, pd.products_name';
		break;

		case 'sortbygpDESC':
			$sortby = 'gp DESC, pd.products_name';
		break;

		case 'sortbygppercentASC':
			$sortby = 'gpPercent ASC, pd.products_name';
		break;

		case 'sortbygppercentDESC':
			$sortby = 'gpPercent DESC, pd.products_name';
		break;

		case 'sortbydiffASC':
			$sortby = 'diff ASC, pd.products_name';
		break;

		case 'sortbydiffDESC':
			$sortby = 'diff DESC, pd.products_name';
		break;

    	default:
			$sortby = 'pd.products_name ASC, p.products_model';
	}


?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td>
				<table><tr><td width="53"><img src="/admin/images/icons/competitors.png" width="48" height="48"></td><td class="pageHeading"><?php echo HEADING_TITLE; ?></td></tr></table>

</td>
            <td class="pageHeading" align="right" style="padding: 0 10px 0 0;">

				<select id="channel">
					<option value="dbfeed_amazon_us"<?php echo ($_GET['channel'] == 'dbfeed_amazon_us' ? ' selected':'')?>>Amazon US</options>
					<option value="dbfeed_amazon_ca"<?php echo ($_GET['channel'] == 'dbfeed_amazon_ca' ? ' selected':'')?>>Amazon Canada</options>
				</select>

			</td>
      </tr>
	</table>
<table border="0" width="100%" cellspacing="0" cellpadding="0">

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top">

<form name="stockForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?action=update<?php echo (isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>">
<table border="0" width="100%" cellspacing="0" cellpadding="5">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == '' ? 'sortbynameDESC':'').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['cust_group']) ? '&cust_group='.$_GET['cust_group'] : '');?>" style="font:bold 11px arial; color:#fff">Product Info</a></td>
                <td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbymodelASC' ? 'sortbymodelDESC':'sortbymodelASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Model</a></td>
				<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbycostASC' ? 'sortbycostDESC':'sortbycostASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Cost</a></td>
				<td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbyretailpriceASC' ? 'sortbyretailpriceDESC':'sortbyretailpriceASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Our Price</a></td>
				<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbyfeeASC' ? 'sortbyfeeDESC':'sortbyfeeASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Fee Preview</a></td>
				<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbygpASC' ? 'sortbygpDESC':'sortbygpASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">GP</a></td>
				<td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbygppercentASC' ? 'sortbygppercentDESC':'sortbygppercentASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">GP%</a></td>
				
				<td class="dataTableHeadingContent" align="center" nowrap>Amazon Best<br><font style="normal 10px arial;">List | Shipped</font></td>
                <td class="dataTableHeadingContent" align="center">Best Price?</td>
                <td class="dataTableHeadingContent" align="center">Diff.</td>
              </tr>
<?php
	$stklevel = STOCK_REORDER_LEVEL;

	$products_ids_query = "
		SELECT
            p.products_id,
            p.products_quantity,
			pd.products_name,
            count(subp.products_id) as total_sub
        FROM
            ".TABLE_PRODUCTS." p
        LEFT JOIN
            ".TABLE_PRODUCTS." subp ON (p.products_id = subp.master_products_id 
				and (subp.master_products_id <> subp.products_id and subp.master_products_id <> 0))
		LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id
        WHERE
            p.products_status = 1
		AND p.products_price > 0
        GROUP BY p.products_id
		ORDER BY p.products_quantity, pd.products_name ASC";

	$result = tep_db_query($products_ids_query);

	while ($row = tep_db_fetch_array($result)) {
		if ($row['total_sub'] > 0) continue;
		$products_ids[] = $row['products_id'];

	}

	tep_db_free_result($result);

	$products_ids_string = 0;
	if (is_array($products_ids) && count($products_ids)) {
		$products_ids_string = implode(',', $products_ids);
//var_dump($products_ids_string);
	}


  if ($_GET['page'] > 1) $rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS;

 $products_query_raw = "SELECT p.products_id, 
							   p.products_status, 
							   p.products_free_shipping, 
							   p.products_price_myself, 
							   p.products_price, 
							   p.master_products_id, 
							   p.products_model, 
							   p.products_quantity, 
							   p.products_weight,
							   pd.products_name,

							   IF((SELECT dbe.extra_value
								FROM dbfeed_products_extra dbe 
								WHERE dbe.dbfeed_class='". $amazon_channel ."' 
								AND dbe.extra_field='shipping_cost' 
								AND dbe.products_id = p.master_products_id) > 0, 

									(SELECT dbe.extra_value
									FROM dbfeed_products_extra dbe 
									WHERE dbe.dbfeed_class='". $amazon_channel ."' 
									AND dbe.extra_field='shipping_cost' 
									AND dbe.products_id = p.master_products_id), 
									
									IF(p.products_free_shipping = 0, 
										(6.99 + (0.50 * (CEIL(p.products_weight * 16) / 16))), 
										0.00)	

								) AS shipping_cost, 

								(SELECT COALESCE(dxe.extra_value, '0.00') 
								FROM dbfeed_products_extra dxe 
								WHERE (dxe.extra_field='amazon_surcharge' AND dbfeed_class = '". $channel ."')
								AND dxe.products_id = p.master_products_id) AS amazon_surcharge, 

								(SELECT SUM(1.00 + (12 / 100) * SUM(p.products_price + amazon_surcharge)) 
								FROM products p2 
								WHERE p2.master_products_id = p.master_products_id) AS feePreview, 

								(SELECT SUM(p.products_price + amazon_surcharge + shipping_cost) 
								FROM products p3 WHERE p3.products_id = p.master_products_id) AS combinedSurcharge_Price, 	

								(SELECT SUM(p.products_price + amazon_surcharge) - (SUM(1.00 + (12 / 100) * SUM(p.products_price + amazon_surcharge))) - p.products_price_myself
								FROM products p5 
								WHERE p5.products_id = p.master_products_id) AS gp, 

								(SELECT ((SUM(p.products_price + amazon_surcharge) - (SUM(1.00 + (12 / 100) * SUM(p.products_price + amazon_surcharge ))) - p.products_price_myself) / ((SUM(p.products_price + amazon_surcharge)))) * 100
								FROM products p5 
								WHERE p5.products_id = p.master_products_id) AS gpPercent, 

							   dpe.extra_value AS asin,
							   dpe.dbfeed_class AS channel

						FROM " . TABLE_PRODUCTS . " p
						LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
						LEFT JOIN dbfeed_products_extra dpe ON (dpe.products_id = p.products_id AND dpe.extra_field ='asin' AND dpe.dbfeed_class = '".$channel."')
						WHERE pd.language_id = '" . $languages_id. "' 
						AND p.products_id IN($products_ids_string) 
						AND p.products_status = 1 
						AND p.products_price > 0 
						GROUP BY pd.products_id 
						ORDER BY ". $sortby;
 
	$products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_query_raw, $products_query_numrows);

	$products_query = tep_db_query($products_query_raw);

	while ($products = tep_db_fetch_array($products_query)) {

		if ($products['master_products_id'] == 0) $products['master_products_id'] = $products['products_id'];

		$attributes = _get_attribute_values_list($products['products_id']);
		if (is_array($attributes)) {
			$attributes_string = array();
			while (list($id, $value) = each($attributes)) {
				$attributes_string[]= "$id:" . ((is_array($value))?implode(',', $value):$value);
			}
			if (!empty($attributes_string)) {
				$products['products_name'] .= " (".join('; ',$attributes_string).")";
			}
		}

    	if (strlen($rows) < 2) {
	      $rows = '0' . $rows;
    	}

?>
		<tr class="dataTableRow <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>">
			<td class="dataTableContent" valign="top" style="padding:10px 5px; font:normal 10px arial;">
<?php 

	$product_url = tep_db_result(tep_db_query("SELECT url_new FROM url_rewrite_map WHERE item_id = 'p".$products['master_products_id']."'"),0, "url_new");

	echo '<a href="'. $product_url .'" style="font:normal 11px arial" target="_blank">' . $products['products_name'] . '</a>';

	echo '<br><br> ASIN: '. (!empty($products['asin']) ? '<a href="https://www.amazon'.$tld.'/dp/'.$products['asin'].'" target="_blank">'. $products['asin'].'</a>' : '<b style="color:red">No Active Offers</b>');

	echo '<br><br> <b>Current Stock: <b style="font:bold 11px arial;">'. max($products['products_quantity'],0).'</b></b>';
?>
</td>

<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 4px; font:normal 10px arial;"><?php echo $products['products_model'];?></td>

<td class="dataTableContent theCost" valign="top" style="width:50px; text-align:center; padding:5px 5px 0 5px;" id="theCost_<?php echo $products['products_id']?>">
<div id="div_cost_<?php echo $products['products_id']?>" style="padding-top:5px;">
$<?php echo number_format($products['products_price_myself'],2);?>
</div>
<?php echo tep_draw_hidden_field('cost_price['.$products['products_id'].']', number_format($products['products_price_myself'],2), 'id="cost_price_'.$products['products_id'].'" class="cost_input" style="font:normal 11px arial; width:50px; height:20px; text-align:center; padding:0 !important;"');?>
</td>

<td class="dataTableContent" valign="top" style="color:#FF0000; font:normal 10px arial; text-align:center; padding: 5px 3px 10px 3px" nowrap>
<table align="center" style="height:25px; padding-bottom:10px;" cellpadding="0" cellspacing="0" border="0">
<tr><td>
<?php echo '<input name="products_price['.$products['products_id'].']" value="'.number_format($products['products_price'],2).'" style="font:normal 11px arial; width:50px; height:20px; text-align:center; padding:0 !important;" id="input_products_price_'.$products['products_id'].'" class="thePrice">';?>
</td>
<td>
<div class="up" id="up_<?php echo $products['products_id']?>"></div>
<div class="down" id="down_<?php echo $products['products_id']?>"></div>
</td>
</tr>
</table>
<?php 
if($products['amazon_surcharge'] > 0) { 
	echo '<div id="div_surcharge_'.$products['products_id'].'" style="color:green">Surcharge: + $' . $products['amazon_surcharge'].'</div>';
} 
echo '<div id="div_shipping_cost_'.$products['products_id'].'">Shipping: ';

if(($products['products_free_shipping'] == 1) && ($products['shipping_cost'] < 0.01)){
	echo 'FREE';
} elseif($products['products_free_shipping'] == 0 && $products['shipping_cost'] == 0) { 
	echo 'FREE';
} elseif($products['products_free_shipping'] == 1 && $products['shipping_cost'] > 0) { 
	echo '- $' . number_format($products['shipping_cost'],2);
} elseif($products['products_free_shipping'] == 0 && $products['shipping_cost'] > 0) { 
	echo '- $' . number_format($products['shipping_cost'],2);
}

echo '</div>';
echo '<div id="div_onsiteFreeShip_'.$products['products_id'].'"></div>';
echo '<div id="div_totalPrice_'.$products['products_id'].'" style="padding-top:5px; color:#000">Total: $'.number_format($products['combinedSurcharge_Price'],2).'</div>';
?>
</td>
<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px">
	<div id="div_fee_<?php echo $products['products_id'];?>">
<?php 
	echo '$'.number_format($products['feePreview'],2);
?>
</div>
</td>
<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px">
<div id="div_gp_<?php echo $products['products_id']?>">
	<?php echo '$'.number_format($products['gp'], 2); ?>
</div>
</td>
<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px">
<div id="div_gpPercent_<?php echo $products['products_id']?>">
<?php echo number_format($products['gpPercent'], 2). '%';?>
</div>
</td>

<td class="dataTableContent" align="center" style="color: #000;" valign="top" nowrap>
<?php 
	$theListings = amazon_xml($products['asin'], $channel);

	if($theListings || !is_null($theListings)) {
	    
    	echo '<table style="font:normal 11px arial;" cellpadding="3">';

			$itemListPrice = '';
			$itemCondition = '';
			$itemLandedPrice = '';
	
		asort($theListings);
	
		// # limit to 5 results
		//foreach(array_slice($theListings, 0, 5) as $key => $value) {

		foreach($theListings as $key => $value) {

			if($value[1] != 'Used') {

	  		  	echo '<tr><td valign="top">$'.$value[0] .'</td><td> | </td> <td valign="top"> $'.$value[2].' </td><!--td valign="top"> '. $value[1] . '</td--></tr>';
		

			$itemListPrice .= $value[0];
			$itemCondition .= $value[1];
			$itemLandedPrice .= $value[2];
			}
		}
		
        
	    echo '</table>';

		$itemListPrice = (float)substr($itemListPrice, 0, strpos($itemListPrice, '.')+3);
		$itemLandedPrice = (float)substr($itemLandedPrice, 0, strpos($itemLandedPrice, '.')+3);

	}
?>
</td>
<td class="dataTableContent" align="center">

<div id="div_besticon_<?php echo $products['products_id']?>">
<?php
	if(is_null($itemLandedPrice) || ($itemLandedPrice >= $products['combinedSurcharge_Price'])) { 
		echo '<img src="/admin/images/icons/success.gif">';
	} else {
		echo '<img src="/admin/images/icons/warning.gif">';
	}
?>
</div>
</td>
<td class="dataTableContent" align="center" width="65">
<div id="div_diff_<?php echo $products['products_id']?>">

<?php 
	
	if(($itemLandedPrice > 0) && $itemLandedPrice > $products['combinedSurcharge_Price']) {
		echo '<font style="color:#009900;">' .number_format((($itemLandedPrice - $products['combinedSurcharge_Price']) / $itemLandedPrice) * 100, 2). '%</font>';
	} elseif(($itemLandedPrice > 0) && $itemLandedPrice < $products['combinedSurcharge_Price']) { 
		echo '<font style="color:#FF0000;">' .number_format((($itemLandedPrice - $products['combinedSurcharge_Price']) / $itemLandedPrice) * 100, 2). '%</font>';
	} else {
		echo ' - ';
	}	
?>
</div>
<input type="hidden" value="<?php echo $itemLandedPrice ?>" id="amazonBestPrice_<?php echo $products['products_id']?>">
</td>
</tr>
<?php
	$rows++;
  }

	tep_db_free_result($products_query);
?>
<tr>
<td colspan="9"></td> <td align="center"><input type="submit" name="action" value="Update All"></td>
</tr>
            </table>

</form>

</td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="5" style="margin:10px 0 0 0">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?> &nbsp;|&nbsp; 

<?php
if(isset($_GET['show']) && $_GET['show'] == 'all'){
echo '<a href="'.$PHP_SELF.'"><b>Back to paging</b></a>';
} else { 
echo '<a href="'.$PHP_SELF.'?show=all"><b>Show All</b></a>';
}
?>

</td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
