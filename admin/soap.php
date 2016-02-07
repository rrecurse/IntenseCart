<?php include("includes/application_top.php"); ?>
<html>
<head>
<title>Export products into a CSV file</title>	
<link rel="stylesheet" href="nusoap/soap.css">
<script type="text/javascript" src="nusoap/soap.js"></script>	
</head>

<body onLoad="Loaded()">
	<?php include(DIR_WS_INCLUDES. 'header.php'); ?>
<br>
<span id="loader" style="background-color: #6296FC;color:#ffffff;margin-left: 5px;padding: 5px;font-size : 11px;">Please wait while the document loads..</span>


<?php

flush();
ini_set("display_errors", 1);
ini_set("memory_limit","64M");
include("nusoap/lib/nusoap.php");
include("nusoap/functions.php");

// security token
$token = "0718d3e4-902c-40ae-a0ba-6f0a1bdf3796";

// csv headers
$headers = 
"categories_name,products_name,products_model,products_sku,products_price,products_quantity,products_info,products_description,products_head_keywords_tag,manufacturers_name,attr_color,attr_size,xsell\n";

// date and file

$date = date("Y-m-d");
$file = "csvdir/products-" . $date . ".csv";

// delete or download

if (isset($_GET['delete'])) {
	unlink("$file");
}
elseif (isset($_GET['download'])) {
	
	header("Location: downloadcsv.php");

}


if (!isset($_GET['page'])) {
	$page = 1;
	$_POST['products'] = 0;

	$f = fopen($file,"w");
	fwrite($f,$headers);
	fclose($f);
}
else {
	$f = fopen($file,"a");
	$page = $_GET['page'];
}	

// connecting to the soap server using the WSDL

$client = $client = new nusoap_client("http://testv2k.visual-2000.com/B2CWSStLawrence/SalesServices.svc?wsdl", true, false, false, false, false, 900, 900);
$result = $client->call('GetAllProduct',array('securityToken' => $token, 'page' => $page - 1));

$len = count($result['GetAllProductResult']['DataObjects']['WebErpProductData']);

// Check for a fault
if ($client->fault) {
	echo '<h2>Fault</h2><pre>';
	print_r($result);
	echo '</pre>';
} else {
	// Check for errors
	$err = $client->getError();
	if ($err) {
		// Display the error
		echo '<h2>Error</h2><pre>' . $err . '</pre>';
	} 
}

// checking the rows that were selected

$write = '';

if (isset($_POST['submit'])) {
	
	$count = 0;	
	for ($i=0;$i<$len;$i++) { 
	
		$x = 'val' . $i;
		
		if (isset($_POST[$x])) {
			
			$count++;
			$ProductSku = 'sku';
			$ProductQuantity = ' ';
			$ProductInfo = 'info';
			$ProductKeywords = ' ';
			$Manufacturers = 'manufacturer';
			$Attr_Size = 'size';
			$xsell = '';

			$names = explode(" ",$base['ProductTypeName']);
			$ProductModel = $names[0];
			$ProductName = $names[1];
			$Size = $_POST['size' . $i];
			$Color = $_POST['color' . $i];

			$color = explode("-",$base['ColorDescriptionLanguage_1']);			
			$base = $result['GetAllProductResult']['DataObjects']['WebErpProductData'][$i];
			$write .= '"' . $_POST['category' . $i] . '", ' . $ProductName . ', ' . $ProductModel . ', ' . $base['IPCCode'] . ', ' . 
$base['Price'] 
. ', ' . $ProductQuantity . ', ' . $base['Description_2'] . ', ' . $base['Description_1'] . ', ' .  $ProductKeywords . ', ' . $base['BrandLabel'] . ', ' . 
$Color . ', ' . $Size . ', ' . $xsell . " \r\n";
		
		}
		
	}
		
	fwrite($f,$write);
	fclose($f);

	@copy($file,$file . '.bak');		
	$_POST['products'] = $_POST['products'] + $count; // counting the number of products selected	
	
}

?>
	
<?php if ($len != "0") { ?>	
	
<div style="font-size: 10px;">
<form method="POST" action="soap.php?page=<?=$page+1?>">
<?php if (isset($_GET['page'])) { ?>There are <strong> <?=$_POST['products']?> </strong> products added until now &nbsp;&nbsp;&nbsp;  <? } ?><br><br><input type="submit" 
name="submit" value="Proceed">&nbsp;&nbsp;&nbsp;<input type="button" onClick="checkAll(this);" value="Check All">&nbsp;&nbsp;&nbsp;<input type="button" 
onClick="uncheckAll(this);" value="Uncheck All">
<input type="hidden" name="products" value="<?=$_POST['products']?>">
<br>
<br>
<?php if (isset($_GET['page']) && $_POST['products'] != "0") { ?> <a href="downloadcsv.php" target="_blank">Download CSV file</a> <br>  <? } ?>
<?php if (isset($_GET['page'])) { ?> <a href="soap.php?delete">Delete CSV file</a><br><br>   <? } ?>
<table>
	<tr class="yellow">

<th>&nbsp;</th>
<th>Product </th>
<th>Category</th>
<th>Color</th>
<th>Size</th>
<th>Product Code</th>
<th>Selling Period</th>
<th>Warehouse</th>
	</tr>

<?php 

for ($i=0;$i<$len;$i++) {

	$base = $result['GetAllProductResult']['DataObjects']['WebErpProductData'][$i];
?>	

<tr <? if ($i%2 == 0) echo 'class="strip"'; ?> >

<td><input type="checkbox" name="val<?=$i?>"></td>

<?php 

                        $names = explode(" ",$base['ProductTypeName']);
                        $ProductModel =	$names[0];
                        $ProductName = $names[1];
                        $Size =	$names[2];

?>

<td><? echo $names[0] . ' ' . $names[1]?></td>
<td>
<select name="category<?=$i?>">

<?php 

  $category_array = tep_main_categories();

  foreach ($category_array as $c) {
	
      if ($c['pID'] == '0') 
	  	echo '<option value="' . $c['text'] . '">' . $c['text'] . '</option>';
	  else {
		$value = searchid($c['pID']) . ' >> ' . $c['text'];
		echo '<option value="' . $value . '">&nbsp;&nbsp;' . $c['text'] . '</option>';
 	}
}
	

?>

</select>
</td>
<td>
<select name="color<?=$i?>">

<?php

$colorquery = tep_db_query("select pv.products_options_values_name as c from products_options_values as pv, products_options_values_to_products_options as pvp where pvp.products_options_id = '1' AND pvp.products_options_values_to_products_options_id = pv.products_options_values_id");


while ($colors = tep_db_fetch_array($colorquery)) {

?>

<option value="<?=$colors['c']?>"><?=$colors['c']?></option>

<?php } ?>
</select>
</td>
<td>
<select name="size<?=$i?>">
<?php 

	$sizequery = tep_db_query("select pv.products_options_values_name as c from products_options_values as pv, products_options_values_to_products_options as pvp where pvp.products_options_id = '2' AND pvp.products_options_values_to_products_options_id = pv.products_options_values_id");

	while ($sizes = tep_db_fetch_array($sizequery)) {

	?>
	
	<option value="<?=$sizes['c']?>"><?=$sizes['c']?></option>
	
	<?php } ?>

</select>
</td>
<td><?=$base['ProductCode']?></td>
<td><?=$base['SellingPeriod']?></td>
<td><?=$base['WarehouseCode']?></td>	
</tr>

<?

}

?>

</table>
<br>
</form>
<?php } else { ?>

<br>
<span style="font-size: 12px;">No more products to browse  <a href="downloadcsv.php" target="_blank">Download CSV file</a></span> 
<br>
	
<?php } ?>
</div>
</body>

</html>
