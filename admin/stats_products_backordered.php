<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


	// # override for application_top definitions
	if(isset($_GET['show']) && $_GET['show'] == 'all') define('MAX_DISPLAY_SEARCH_RESULTS', '20000');
	define ('HEADING_TITLE','Inventory Level Control');

	require('includes/application_top.php');

	global $mobile;

if(!isset($_SESSION['origin']) || $_SESSION['origin'] != FILENAME_STATS_PRODUCTS_BACKORDERED) { 

	session_start();
	$_SESSION['origin'] = strtok(FILENAME_STATS_PRODUCTS_BACKORDERED, '?');
} 

function _get_attribute_values_list($products_id) {

	$res = tep_db_query("SELECT po.products_options_name, pov.products_options_values_name
						 FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
						 INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON (pa.options_id = po.products_options_id 
							AND po.language_id = {$GLOBALS['languages_id']})
						 INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON (pa.options_values_id = pov.products_options_values_id 
							AND pov.language_id = {$GLOBALS['languages_id']})
						 WHERE pa.products_id = {$products_id}
						");
    $values = array();
    while ($row = tep_db_fetch_array($res)) {
        $values[$row['products_options_name']][] = $row['products_options_values_name'];
    }

	tep_db_free_result($res);

    reset($values);
    while (list($id, $value) = each($values)) {
        $result[$id] = implode(",", array_unique($value));
    }
    return $result;
 }

if($_GET['action'] == "update" && is_array($_POST['quantity'])) {

    foreach ($_POST['quantity'] as $products_id => $products_quantity) {
	        $products_quantity = intval($products_quantity);
    	    $products_id = intval($products_id);

		//# Update the stock quantities!
		tep_db_query("UPDATE ".TABLE_PRODUCTS." 
					  SET `products_quantity` = " . $products_quantity.", 
					  `last_stock_change` = NOW()
					  WHERE `products_id` = ".$products_id);
	}

} 


	// # multi-warehousing - update tables for multi-warehousing.
	if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

		if($_GET['action'] == "update" && is_array($_POST['warehouse'])) {

		    foreach ($_POST['warehouse'] as $warehouse_id => $warehouse) {

    	    	$warehouse_id = intval($warehouse_id);

			    foreach ($warehouse as $products_id => $products_quantity) {

		    	    $products_quantity = intval($products_quantity);
	
					$products_id = intval($products_id);


					// # now update the products_warehouse_inventory table.

						tep_db_query("UPDATE ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." 
									  SET products_quantity = ". $products_quantity ." 
									  WHERE products_warehouse_id = '". $warehouse_id ."' 
									  AND products_id = '".$products_id."'
									");	
				}
			}

		}
	

	}



if($_GET['action'] == "update" && is_array($_POST['backorder'])) { 
	foreach ($_POST['backorder'] as $products_id => $dateAvailable) {
			$products_id = intval($products_id);
			$dateAvailable = (!empty($dateAvailable)) ? "'".date("Y-m-d H:s:i", strtotime($dateAvailable)). "'" : "NULL";

			if(date('Y-m-d 00:00:00', mktime()) > (str_replace("'","",$dateAvailable))) $dateAvailable = 'NULL';

			//# Update the backorder dates!
			tep_db_query("UPDATE ".TABLE_PRODUCTS." 
						  SET `last_stock_change` = NOW(), 
						  products_date_available = ".$dateAvailable." 
						  WHERE `products_id` = ".$products_id
						);

	}
}

if($_GET['action'] == "update" && is_array($_POST['notes'])) { 
	foreach ($_POST['notes'] as $products_id => $notes) {
			$products_id = intval($products_id);
			$notes = (!empty($notes)) ? "'".$notes. "'" : "NULL";

			//# Update the backorder dates!
			tep_db_query("UPDATE ".TABLE_PRODUCTS." 
						  SET last_stock_change = NOW(), 
						  purchase_handler_data = $notes 
						  WHERE products_id = ".$products_id
						);

	}
}


if($_GET['action'] == "updateFromScan" && !empty($_POST['casepack_qty']) || ($_GET['action'] == "updateFromScan" && is_array($_POST['casepack_qty']) ) ) { 
	foreach ($_POST['casepack_qty'] as $products_id => $casepack_qty) {
			$products_id = intval($products_id);
			$casepack_qty = intval($casepack_qty);

	//# Update the stock quantities!
		tep_db_query("UPDATE ".TABLE_PRODUCTS." SET products_quantity = products_quantity + ".$casepack_qty.", `last_stock_change` = NOW() WHERE `products_id` = ".$products_id);   	

	}
}


if(isset($_GET['export']) && $_GET['export'] == 'csv') {

	$sortby = $_GET['sortby'];

	$products_ids_string = $_GET['product_ids'];


	$export_products_query = tep_db_query("SELECT pd.products_name AS `Product Name`,
												  p.products_model AS `Model`,
												  spg.suppliers_sku AS `Supplier SKU`,
												  spg.suppliers_group_price AS `Supplier Cost`,
												  p.products_quantity AS `Master Inventory`,
												  p.products_upc AS `UPC-A`,
												  pg.customers_group_price AS `Retail Price`,
												  DATE_FORMAT(p.products_date_available, '%m/%d/%Y') as `Backorder Date`,
												   (SELECT SUM(op.products_quantity)
													FROM ". TABLE_ORDERS ." o
													LEFT JOIN ". TABLE_ORDERS_PRODUCTS ." op ON op.orders_id = o.orders_id
													WHERE o.date_purchased BETWEEN (NOW() - INTERVAL 180 DAY) AND (NOW() - INTERVAL 1 HOUR)
													AND (o.orders_status = 1 OR o.orders_status = 2)
													AND op.products_id = p.products_id) AS `Products Backordered`,
												  p.purchase_handler_data AS `Notes`
										  FROM " . TABLE_PRODUCTS . " p
										  LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
										  LEFT JOIN suppliers_products_groups spg ON (spg.products_id = p.master_products_id AND spg.priority = 0)
										  LEFT JOIN products_groups pg ON (pg.products_id = p.products_id AND pg.customers_group_id = 0)
										  WHERE pd.language_id = '" . $languages_id. "' 
										  AND p.products_id IN(".$products_ids_string.")
										  GROUP BY p.master_products_id 
										  ORDER BY `Backorder Date`, pd.products_name ASC, p.products_model");

	$filename = SITE_DOMAIN.'_current-inventory_'.date('m-d-Y', time()).'.csv';
	$filename = str_replace('www.','',$filename);

	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename=' . $filename);

	$count = mysql_num_fields($export_products_query);
	
	for ($i = 0; $i < $count; $i++) {
    	$header[] = mysql_field_name($export_products_query, $i);
	}
	
	print implode(',', $header) . "\r\n";


	//array_merge($export_products_query, array('Backorders' => '') );

	while ($row = tep_db_fetch_array($export_products_query)) {	

    	foreach ($row as $value) {
        	$values[] = '"' . str_replace('"', '""', html_entity_decode(strip_tags($value))) . '"';
	    }
    	
		print implode(',', $values) . "\r\n";
	    unset($values);
	}

	exit();
} else { 

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo HEADING_TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script language="javascript" src="js/prototype.lite.js"></script>

<style type="text/css">

//#calendar{left:calc(100% - 325px) !important; width:250px !important;}

@media screen and (min-width: 1091px) {

	* {
		font-size:100% !important;
	}

	.tableinfo_right-btm {
		height:35px !important;
	}

	.dataTableHeadingRow { 
		height:35px !important;	
	}

}

@media screen and (max-width: 1090px) {


}

.black_overlay{
	display: none;
	position: absolute;
	top: 0%;
	left: 0%;
	width: 100%;
	height: 100%;
	background-color: #333;
	z-index:1001;
	-moz-opacity: 0.7;
	opacity:.70;
	filter: alpha(opacity=70);
}

<?php if($mobile !== true){ 
echo '
	.white_content {
	display: none;
	position: fixed;
	top: 5%;
	left: 10%;
	width: 75%;
	height: 150px;
	padding: 10px 15px 15px 15px;
	border: 10px solid #053389;
	background-color: white;
	z-index:1002;
	overflow: hidden;
	-moz-border-radius: 10px;
	border-radius: 10px;
	-webkit-border-radius: 10px;
	}

	#searchbarcode  {
	width:200px;
	height:40px;
	font:bold 15px Tahoma;
	}';

} else {

echo '
	.white_content {
	display: none;
	position:absolute;
	top:5px;
	left: 25%;
	right: 25%;
	margin:0 auto;
	width: 50%;
	height: 290px;
	padding: 10px 15px 15px 15px;
	border: 5px solid #053389;
	background-color: white;
	z-index:1002;
	overflow: hidden;
	-moz-border-radius:5px;
	border-radius:5px;
	-webkit-border-radius:5px;
	}

	#searchbarcode  {
	width:150px;
	height:40px;
	border:solid 2px #333;
	font:bold 15px Tahoma;
	}';

} ?>


.closeme {
	text-align:right;
	height:25px;
	width:25px;
	float:right;
}


@media screen {

	body {
		margin:0 auto;
	}
}

@page { size:8.5in 11in; margin: 0.5cm 0.75cm }

@media print {
	body {
		page-break-after:avoid !important;
		width:99%;
		height: 99%;
		margin: 0 auto;
	}

	.col_details { 

	}

	.col_model { 

	}

	.col_qty input { 
		background-color:transparent;
		border:0;
	}

	.col_backorder input { 
		background-color:transparent;
		border:0;
	}

	.col_request { 
		display:none;
		width:1px;
		overflow:hidden;
	}

	.hide_print { 
		display:none;
		overflow:hidden;
	}

	.tabOdd { 
		background-color:transparent !important;
	}
}
	</style>
<?php if($mobile === true) { 

echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">';

}
?>

<script type="text/javascript">

function is_touch_device(target) {

	if (("ontouchstart" in document.documentElement) || ('ontouchstart' in window) || ('onmsgesturechange' in window)) { 
		//alert('It\'s a touch screen device.');
		var evt = target.ownerDocument.createEvent('TouchEvent');
		evt.initUIEvent('touchstart', true, true);
		target.dispatchEvent(evt);

	} else {
		// Others devices.
	} 

}


<!--
function barcodeLookup(thebarcode) {
	var thebarcode = $('searchbarcode').value;
	var mytabIndex = document.activeElement.tabIndex;

		if(thebarcode) {
			$('barcodeResults').innerHTML = '<table border="0" width="100%" height="100"><tr><td align="center" valign="middle" class="dataTableContent">Looking up Barcode, please wait...<br><img src="images\/loading_bar.gif" alt=""><\/td><\/tr><\/table>';
			new ajax ('/admin/stats_products_backordered.php?barcodelookup=1', {postBody: 'searchbarcode='+thebarcode, update: $('barcodeResults')});
	
			$('searchbarcode').value = "";
    		return;
			}
		}

function scanPop() {

//is_touch_device();

	document.getElementById('light').style.display='block'; 
	document.getElementById('fade').style.display='block'; 

	var displayed = document.getElementById('light').style.display;

	if(displayed === 'block') { 
<?php if($mobile === true) { 
echo "
	alert('--> Prepare to Scan Barcodes <--');
	top.scrollTo(425,230);

"; } // # END mobile detection
?>	
		setTimeout("is_touch_device(document.getElementById('scanIcon'))",500);
if (document.activeElement.title != 'searchbarcode') document.getElementById('searchbarcode').focus();
		document.getElementById('searchbarcode').focus();

	} // # END block detection for barcode popup


} // # END scanPop() function

function disableEnterKey(e){
      //if(e.keyCode == 13) return false;
	  if(e.keyCode == 86 || e.keyCode == 13 || e.keyCode == 37 || e.keyCode == 38 || e.keyCode == 39 || e.keyCode == 40) barcodeLookup(document.getElementById('searchbarcode').value);
 
}

function captureForm() {
barcodeLookup(document.getElementById('searchbarcode').value);
return false;
alert('captureForm() works');
}

//-->


<!--
function viewport(){
    var e = window;
    var a = 'inner';
    if (!('innerWidth' in window)){
        a = 'client';
        e = document.documentElement || document.body;
    }
    return { width : e[ a+'Width' ] , height : e[ a+'Height' ] }
alert(e);
}
window.onload = viewport();
//-->
</script>

<script type="text/javascript" src="js/popcalendar.js"></script>
</head>
<body style="background-color:transparent;">
<?php require(DIR_WS_INCLUDES . 'header.php'); 

	if($_GET['barcodelookup'] == '1' && !empty($_POST['searchbarcode'])) { 
		$searchbarcode = tep_db_prepare_input($_POST['searchbarcode']);

		$barcode_query = tep_db_query("SELECT p.products_id, 
											  p.products_model, 
											  p.products_sku,
											  p.products_upc,
											  pd.products_name,
											  p.products_quantity,
											  p.products_image,
											  p.products_price,
											  p.products_last_modified, 
											  s.products_id,
											  s.casepack_sku,
											  s.casepack_qty 
										FROM " . TABLE_PRODUCTS . " p 
										LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
										LEFT JOIN ".TABLE_SUPPLIERS_PRODUCTS_GROUPS." s ON s.products_id = p.products_id
										WHERE pd.language_id = '" . (int)$languages_id . "' 
										AND (s.casepack_sku LIKE '" . $searchbarcode . "' OR p.products_model LIKE '" . $searchbarcode . "' OR p.products_upc LIKE '" . $searchbarcode . "' OR p.products_sku LIKE '" . $searchbarcode . "') 
										GROUP BY p.products_id 
										ORDER BY pd.products_name
									   ");

//var_dump($_POST['searchbarcode']);
//error_log(print_r($searchbarcode, TRUE));

	if(mysql_num_rows($barcode_query) > 0) { 
		echo '<form name="barcodeForm" method="POST" action="'. $_SERVER['PHP_SELF'].'?action=updateFromScan">
				<table border="0" width="100%" cellspacing="0" cellpadding="5" align="center">
	              <tr class="dataTableHeadingRow">
    	            <td class="dataTableHeadingContent">'.TABLE_HEADING_PRODUCTS.'</td>
    	            <td class="dataTableHeadingContent" align="center">Model:</td>
    	            <td class="dataTableHeadingContent" align="center">SKU:</td>
					<td class="dataTableHeadingContent" align="center" nowrap>In-Stock:</td>
					<td class="dataTableHeadingContent" align="center" nowrap>Add Stock:</td>
        	      </tr>';

	while ($scanresults = tep_db_fetch_array($barcode_query)) {

		echo ' <tr class="dataTableRow">
                <td class="dataTableContent" valign="top" style="padding:10px 10px;">' . $scanresults['products_name'] . '</td>
<td class="dataTableContent" valign="top" style="color:#000; text-align:center; padding:10px 5px" nowrap><b>'. $scanresults['products_model'].'</b></td>
<td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px;" nowrap>' .$scanresults['products_sku'] .' <b></b></td>
<td class="dataTableContent" align="center">'.$scanresults['products_quantity'].'</td>
<td class="dataTableContent" valign="top" style="color:#FF0000; text-align:center;" width="60"><table><tr><td> <input type="button" id="minus" value="-" onClick="casepack_qty_'.$scanresults['products_id'].'.value = (casepack_qty_'.$scanresults['products_id'].'.value-1)"></td><td><input name="casepack_qty['.$scanresults['products_id'].']" value="'.$scanresults['casepack_qty'].'" style="font:bold 12px arial; color:red; width:35px; height:25px; text-align:center;" id="casepack_qty_'.$scanresults['products_id'].'"></td><td>    <input type="button" value="+" 
    onClick="casepack_qty_'.$scanresults['products_id'].'.value = (+casepack_qty_'.$scanresults['products_id'].'.value+1)"></td></tr></table>
</td>
</tr>';	
	}

	tep_db_free_result($barcode_query);

echo '</table>
<input type="submit" value="add stock">
</form>';
} else {
echo '<br><b style="font:bold 12px arial">No Results found, please scan a different barcode</b>';
}
exit();
} elseif($_GET['barcodelookup'] == '1' && empty($_POST['searchbarcode'])) {
echo 'No Results found, please scan a different barcode';
//exit();
} else {


	switch($_GET['sort']) {

		case 'sortbynameASC':
			$sortby = 'pd.products_name ASC, p.products_quantity ASC, p.products_model';
		break;

		case 'sortbynameDESC':
			$sortby = 'pd.products_name DESC, p.products_quantity ASC, p.products_model';
		break;

		case 'sortbymodelASC':
			$sortby = 'p.products_model ASC, pd.products_name DESC, p.products_quantity ASC';
		break;

		case 'sortbymodelDESC':
			$sortby = 'p.products_model DESC, pd.products_name DESC, p.products_quantity ASC';
		break;

		case 'sortbyskuSC':
			$sortby = 'p.products_sku ASC, pd.products_name';
		break;

		case 'sortbyskuDESC':
			$sortby = 'p.products_sku DESC, pd.products_name';
		break;

		case 'sortbydateASC':
			$sortby = 'p.products_date_available ASC, p.products_quantity ASC, pd.products_name ASC, p.products_model';
		break;

		case 'sortbydateDESC':
			$sortby = 'p.products_date_available DESC, p.products_quantity ASC, pd.products_name ASC, p.products_model';
		break;

		case 'sortbybackorderASC':
			$sortby = 'backorders ASC, pd.products_name';
		break;

		case 'sortbybackorderDESC':
			$sortby = 'backorders DESC, pd.products_name';
		break;

		case 'sortbyqtyDESC':
			$sortby = 'p.products_quantity DESC, pd.products_name ASC, p.products_model';
		break;

    	default:
			$sortby = 'backorders DESC, pd.products_name ASC, p.products_model';
	}

?>
<script>

jQuery(document).ready(function() {

	jQuery(window).resize(function() {
	
		top.resizeIframe('myframe');
	});

});

</script>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top">
		<table border="0" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td width="55"><img src="/admin/images/icons/supply-icon.png" width="48" height="48"></td>
				<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
				<td class="pageHeading hide_print" align="right">
					<table width="200" cellpadding="0" cellspacing="5" border="0" style="background-color:#FFFFFF; border:solid 1px #D9E4EC; margin:0 0 5px 0">
						<tr>
							<td><img src="images/scanbarcode.png" width="48" height="48" alt="" border="0" style="cursor:pointer;" onclick="scanPop()"></td><td nowrap><span style="font:bold 15px arial">Scan Barcodes</span><br>
<span style="font:normal 11px arial">(Barcode Scanner Required)</span></td>
						</tr>
					</table>
					</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table class="loop_rows" border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
            				<td valign="top">

<form name="stockForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?action=update<?php echo (isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '').(isset($_GET['sort']) ? '&sort='.$_GET['sort'].'' : '');?>">
<table border="0" width="100%" cellspacing="0" cellpadding="5">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbynameASC' ? 'sortbynameDESC':'sortbynameASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff"><?php echo TABLE_HEADING_PRODUCTS; ?></a></td>
                <td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbymodelASC' ? 'sortbymodelDESC':'sortbymodelASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Model</a></td>
                <!--td class="dataTableHeadingContent" align="center"><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbyskuDESC' ? 'sortbyskuASC':'sortbyskuDESC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">SKU</a></td-->

				<td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbyqtyDESC' ? 'sortbyqtyASC':'sortbyqtyDESC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Stock</a></td>

				<td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbydateASC' ? 'sortbydateDESC':'sortbydateASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">In-stock Date</a></td>
				<td class="dataTableHeadingContent" align="center" nowrap><a href="<?php echo $_SERVER['PHP_SELF'] .'?sort='.($_GET['sort'] == 'sortbybackorderASC' ? 'sortbybackorderDESC':'sortbybackorderASC').(isset($_GET['show']) && $_GET['show'] == 'all' ? '&amp;show=all' : '');?>" style="font:bold 11px arial; color:#fff">Backordered</a></td>
				<td class="dataTableHeadingContent hide_print" align="center">Request Inventory</td>
              </tr>
<?php
	$stklevel = STOCK_REORDER_LEVEL;

	$result = tep_db_query("SELECT p.products_id,
								   p.products_quantity,
								   p.products_price,
								   pd.products_name,
								   pg.customers_group_price
							FROM ". TABLE_PRODUCTS ." p
							LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON p.products_id = pd.products_id
							LEFT JOIN " . TABLE_PRODUCTS_GROUPS ." pg ON pg.products_id = p.products_id
					        WHERE p.products_status = 1
							AND (p.products_price > 0 OR pg.customers_group_price > 0)
					        GROUP BY p.products_id
							");

	while ($row = tep_db_fetch_array($result)) {
		//if ($row['total_sub'] > 0) continue;

		$products_ids[] = $row['products_id'];

	}

	tep_db_free_result($result);

	$products_ids_string = 0;
	if (is_array($products_ids) && count($products_ids)) {
		$products_ids_string = implode(',', $products_ids);
	}


	if(is_int($_GET['page']) && $_GET['page'] > 1) {
		$rows = ($_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS - MAX_DISPLAY_SEARCH_RESULTS);
	}

 $products_query_raw = "SELECT p.products_id, 
							   p.products_status,
							   p.products_sku,
							   p.master_products_id,
							   p.products_model,
							   p.products_quantity, 
							   UNIX_TIMESTAMP(p.products_date_available) as dateAvail, 
							   pd.products_name,
							   spg.suppliers_sku,
							   (SELECT SUM(op.products_quantity)
								FROM ". TABLE_ORDERS ." o
								LEFT JOIN ". TABLE_ORDERS_PRODUCTS ." op ON op.orders_id = o.orders_id
								WHERE o.date_purchased BETWEEN (NOW() - INTERVAL 180 DAY) AND (NOW() - INTERVAL 1 HOUR)
								AND (o.orders_status = 1 OR o.orders_status = 2)
								AND op.products_id = p.products_id) AS backorders,
								p.purchase_handler_data AS notes
						FROM " . TABLE_PRODUCTS . " p
						LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
						LEFT JOIN suppliers_products_groups spg ON (spg.products_id = p.master_products_id AND spg.priority = 0)
						WHERE pd.language_id = '" . $languages_id. "' 
						AND	p.products_id IN ($products_ids_string)
						GROUP BY p.master_products_id 
						ORDER BY ". $sortby;

	$products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_query_raw, $products_query_numrows);

	$products_query = tep_db_query($products_query_raw);

	while ($products = tep_db_fetch_array($products_query)) {

    	$rows++;

		if ($products['master_products_id'] == 0) {
			$products['master_products_id'] = $products['products_id'];
		}

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

		if(strlen($rows) < 2) {
			$rows = '0' . $rows;
		}

		$backorderDate = (!is_null($products['dateAvail']) && $products['dateAvail'] > 0) ? date('m/d/Y', $products['dateAvail']) : NULL;


?>
	<tr class="dataTableRow <?php echo ($ct++&1 ? 'tabEven' : 'tabOdd');?>">
                <td class="dataTableContent col_details" valign="top" style="padding:10px 10px;">
<?php echo $products['products_name'];
//'<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=new_product_preview&read=only&pID=' . $products['master_products_id'] . '&origin=' . FILENAME_STATS_PRODUCTS_BACKORDERED . '?page=' . $_GET['page'], 'SSL') . '" style="font:normal 11px arial">' . $products['products_name'] . '</a>';
echo '<br><br> SKU: ' . $products['products_sku'];

if($products['suppliers_sku']) { 
	echo '<br><br> Supplier\'s SKU: ' . $products['suppliers_sku'];
}
?>
</td>
<td class="dataTableContent col_model" valign="top" style="color:#000; text-align:center; padding:10px 5px" nowrap><b> <?php echo $products['products_model'] ?> </b></td>
<!--td class="dataTableContent" valign="top" style="text-align:center; padding:10px 5px;" nowrap><?php //echo $products['products_sku'] ?></td-->
<td class="dataTableContent col_qty" valign="top" style="text-align:center; padding:5px 10px 10px 10px">
<table width="100%" cellpadding="5" cellspacing="0" style="border:solid 1px #e4e4e4">
<?php 
	echo '<tr style="background-color:#FFF"><td align="right"><b>Master</b></td><td><input name="quantity['.$products['products_id'].']" value="'.$products['products_quantity'].'" style="font:bold 12px arial; color:black; width:35px; height:20px; text-align:center; "></td></tr>';

		// # multi-warehousing - update tables for multi-warehousing.
		if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

			// # detect if product has an entry in the products_warehouse_inventory table.
			// # if not, then use default master quantity level.
			$warehouse_id_query = tep_db_query("SELECT pwi.products_warehouse_id,
													   pwi.products_quantity,
													   pw.products_warehouse_name AS warehouse_name
												FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." pwi 
												LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE ." pw ON pw.products_warehouse_id = pwi.products_warehouse_id
												WHERE pwi.products_id = '". $products['products_id'] ."'
												ORDER BY pwi.products_warehouse_id ASC
											   ");
			$c = true;

			$warehouse_allocated = 0;

			if(tep_db_num_rows($warehouse_id_query) > 0) { 

				while ($warehouse = tep_db_fetch_array($warehouse_id_query)) {
	
					$warehouse_allocated += $warehouse['products_quantity'];
	
					$warehouse_id = $warehouse['warehouse_id'];
	
					if($warehouse['warehouse_name'] == 'Amazon-FBA_US') {
	
						$warehouse_name = 'FBA US';
	
					} else if($warehouse['warehouse_name'] == 'Amazon-FBA_CA') { 
	
						$warehouse_name = 'FBA CA';
	
					} else if ($warehouse['warehouse_name'] == 'Newegg-SBN_US') { 
	
						$warehouse_name = 'SBN US';

					} else if ($warehouse['warehouse_name'] == 'Newegg-SBN_CA') { 
	
						$warehouse_name = 'SBN CA';

					} else { 
	
						$warehouse_name = $warehouse['warehouse_name'];
					}
	
					echo '<tr'.(($c = !$c)? ' style="background-color:#FFF"':' style="background-color:#EFEFEF"').'><td align="right">'.$warehouse_name.' </td><td><input name="warehouse['.$warehouse['products_warehouse_id'].']['.$products['products_id'].']" value="'. $warehouse['products_quantity'] .'" style="font:bold 11px arial; color:red; width:35px; height:16px; text-align:center;"></div></td></tr>';

				}

				if(($products['products_quantity'] - $warehouse_allocated) > 0) { 

					echo '<tr><td colspan="2"><div id="allocation" style="color:red">Unallocated: <b>'. ($products['products_quantity'] - $warehouse_allocated) .'</b></div></td></tr>';

				} else if($warehouse_allocated > $products['products_quantity']) { 

					echo '<tr><td colspan="2"><div id="allocation" style="color:orange">Over-Allocated: <b>'. ($warehouse_allocated - $products['products_quantity']) .'</b></div></td></tr>';

				}

			}

		}

?>

</table>
</td>

<td class="dataTableContent col_backorder" align="center" valign="top" width="70" style="position: relative; padding:10px 5px">

<?php echo '<input name="backorder['.$products['products_id'].']" value="'.$backorderDate.'" style="font:normal 11px arial; color:black; width:60px; height:25px; text-align:center;" id="backorder_'.$products['products_id'].'" onclick="self.popUpCalendar(this,this,\'mm/dd/yyyy\',document);">'?>

</td>

<td class="dataTableContent" style="text-align:center; padding:15px 10px 5px 10px" width="60" valign="top">
<?php 
	if($products['backorders'] > 0) { 
	
		echo '<a href="orders.php?pID='.$products['products_id'].'&date_from='.date('m/d/Y',strtotime("today - 180 days")).'&date_to='.date('m/d/Y').'&status=1,2" style="font:bold 11px arial; color:#FF0000;">'. $products['backorders'].'</a>';

	} else { 
		echo '0';
	}
?>
</td>
<td class="dataTableContent col_request" align="center" style="padding:10px 5px;">

<?php 
	
	$supply_request_query = tep_db_query("SELECT sr.date_requested, srp.*, srh.* 
										  FROM ". TABLE_SUPPLY_REQUEST_PRODUCTS ." srp
										  LEFT JOIN supply_request_status_history srh ON srh.supply_request_id = srp.supply_request_id
										  LEFT JOIN supply_request sr ON sr.supply_request_id = srp.supply_request_id
										  WHERE srp.products_id = " . $products['products_id'] . "
										  ORDER BY supply_request_status_history_id DESC
										  LIMIT 1
										");

	if(tep_db_num_rows($supply_request_query) > 0 ) { 

		$supply_request = tep_db_fetch_array($supply_request_query);
		echo 'P/O: ' . $supply_request['supply_request_id'];
		echo '<br>Created: ' . date('m/d/Y',strtotime($supply_request['date_requested']));

		if($supply_request['products_comments']) { 
			echo '<div style="margin:5px 0 0 0; background-color:#FFFFDD; padding: 5px; border:1px dotted #e4e4e4"><u style="line-height:20px">Product Notes:</u><br>' . $supply_request['products_comments'].'</div>';
		}

		if($supply_request['comments']) { 
			echo '<div style="margin:5px 0 0 0; background-color:#FFF; padding: 5px; border:1px dotted #e4e4e4"><u style="line-height:20px">P/O Comments:</u><br>' . $supply_request['comments'].'</div>';
		}
		echo '<br><a href="edit_supply_request.php'.($supply_request['supply_request_id'] ? '?sID='.$supply_request['supply_request_id'].'&action=edit' : '').'"> Edit</a>';

		tep_db_free_result($supply_request_query);

	} else {

		echo '<div id="prodNotes" style="width:99%; text-align:left;">
				Product Notes: <br> <textarea name="notes['.$products['products_id'].']" style="font:bold 11px arial; color:#000; width:99%; height:30px; padding-left:2px; ">'.$products['notes'].'</textarea>
			<br><br></div>
	
			<a href="create_supply_request.php'.($products['products_id'] ? '?pID='.$products['products_id'] : '').'">+ Supply Request</a>';
	}
?>
			</td>
              </tr>
<?php 
} // # END while loop


	tep_db_free_result($products_query);
?>
			<tr>
				<td colspan="5">
				
				</td> 
				<td class="hide_print" align="right" style="padding-right:10px;"><input type="submit" name="action" value="Update All"></td>
			</tr>
		</table>

</form>
	<button class="hide_print" onclick="window.print(); return false;" style="font:normal 11px arial;">Print</button> 
	<button class="hide_print" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']?>?export=csv&sortby=<?php echo $sortby?>&product_ids=<?php echo $products_ids_string?>'; return false;" name="export" style="font:normal 11px arial;" >Export</button>

<div id="light" class="white_content" style="display:none;">
<div class="closeme"><a href="#" onclick="$('light').style.display='none'; document.getElementById('fade').style.display='none'; $('barcodeResults').innerHTML = '';"><img src="/admin/images/lightbox_close_button.png" border="0" alt="close"></a></div>
<div>

<form name="barcodes" method="POST" action="<?php echo $_SERVER['PHP_SELF']?>?barcodelookup=1" onSubmit="return captureForm()">
<table><tr>
<td valign="top"><img src="images/scanbarcode.png" width="48" height="48" alt="" border="0" id="scanIcon" onclick="document.getElementById('searchbarcode').focus();"> &nbsp; </td>
<td><input title="searchbarcode" type="text" name="searchbarcode" id="searchbarcode" value="" autofocus="autofocus" onKeyPress="return disableEnterKey(event)" onpaste="barcodeLookup($('searchbarcode').value);" onblur="barcodeLookup($('searchbarcode').value);" tabindex="1"></td>
<td><button tabindex="2" onclick="return false;" style="height:40px; width:55px; font:bold 15px arial; background-color:#FFFFFF">GO</button></td></tr></table>
</form>
<div id="barcodeResults"></div>
</div>
</div>


</td>
          </tr>
          <tr>
            <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="5" style="margin:10px 0 0 0">
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?> <span class="hide_print">&nbsp;|&nbsp; 

<?php
if(isset($_GET['show']) && $_GET['show'] == 'all'){
echo '<a href="'.$PHP_SELF.'"><b>Back to paging</b></a>';
} else { 
echo '<a href="'.$PHP_SELF.'?show=all"><b>Show All</b></a>';
}
?> &nbsp; | &nbsp; <a href="configuration.php?gID=9">Modify Low Stock Level Threshold</a>
</span>

</td>
                <td class="smallText hide_print" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>
<div id="fade" class="black_overlay"></div>
<?php } ?>
</body>
</html>
<?php } // # END $_GET['export'] for csv export
?>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
