<?php 
ob_start();
include("includes/application_top.php");
 ?>
<html>
<head>
<title>Import products</title>	
<link rel="stylesheet" href="nusoap/soap.css">
<script type="text/javascript" src="nusoap/soap.js"></script>
</head>

<body>
	<?php include(DIR_WS_INCLUDES. 'header.php'); ?>

<?php

ini_set("display_errors", 1);
ini_set("memory_limit","64M");
include("nusoap/lib/nusoap.php");
include("nusoap/functions.php");

$table="visualproducts_selected";
exportMysqlToCsv($table,"csvdir/products.csv");

header("Location: ez_populate2.php?csvfile=products.csv");

?>

<div style="font-size: 12px;margin-top: 3px;">
<br>
<span style="background-color: #6296FC;color:#ffffff;margin-left: 5px;padding: 5px;font-size : 13px;"> Import was completed succesfully </span>
</div>
</body>
</html>
