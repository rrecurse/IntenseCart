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

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// security token
$token = "0718d3e4-902c-40ae-a0ba-6f0a1bdf3796";

// csv headers
$headers = 
"categories_name,products_name,products_model,products_sku,products_price,products_quantity,products_info,products_description,products_head_keywords_tag,manufacturers_name,attr_color,attr_size,xsell\n";


// connecting to the soap server using the WSDL

$len=0;
$newlen = 0;
$data = array();
$time_start = microtime_float();
$page=1;
tep_db_query("TRUNCATE TABLE visualproducts");
do {

$client = new nusoap_client("http://testv2k.visual-2000.com/B2CWSStLawrence/SalesServices.svc?wsdl", true, false, false, false, false, 900, 900);
$result = $client->call('GetAllProduct',array('securityToken' => $token, 'page' => $page - 1));

$len = count($result['GetAllProductResult']['DataObjects']['WebErpProductData']);
$newlen += $len;

for($i=0;$i<$len;$i++) {
	
	$base = $result['GetAllProductResult']['DataObjects']['WebErpProductData'][$i];
	
	$names = explode(" ",$base['ProductTypeName']);
	$ProductModel = $names[0];
	$ProductName =  mysql_escape_string($base['ProductTypeName']);
	$IPCCode = $base['IPCCode'];
	$Price = $base['Price'];
	$desc1 = mysql_escape_string($base['Description_1']);
	$desc2 = mysql_escape_string($base['Description_2']);
	$brandlabel = mysql_escape_string($base['BrandLabel']);
	$warehouse = mysql_escape_string($base['WarehouseCode']);
	$sellingperiod = mysql_escape_string($base['SellingPeriod']);
	$color = mysql_escape_string($base['ColorDescriptionLanguage_1']);
	$size = mysql_escape_string($base['Size']);	

	$query = tep_db_query("SELECT count(*) AS total FROM visualproducts WHERE ipccode='$IPCCode'");
	$total = tep_db_fetch_array($query);
	
	if ($total['total'] == "0")
		tep_db_query("INSERT INTO visualproducts (id,productname,productmodel,ipccode,price,quantity,description1,description2,brandlabel,warehouse,sellingperiod,color,size) VALUES ('','$ProductName','$ProductModel','$IPCCode','$Price','','$desc1','$desc2','$brandlabel','$warehouse','$sellingperiod','$color','$size')");
	
	
}

$page++;
} while ($len >= 500);

$time_end = microtime_float();
$time = $time_end - $time_start;

// checking the rows that were selected
?>
</body>
total : <?php echo $newlen; ?>
seconds : <?php echo $time; ?>
</html>
