<?php set_time_limit(0); ?>
<?php include("includes/application_top.php"); ?>
<html>
<head>
<title>Visual 2000 Product Extractor</title>	
<link rel="stylesheet" href="nusoap/soap.css">
<script type="text/javascript" src="nusoap/soap.js"></script>
</head>

<body onLoad="Loaded()">
	<?php include(DIR_WS_INCLUDES. 'header.php'); ?>
<br>
<span id="loader" style="background-color: #6296FC;color:#ffffff;margin-left: 5px;padding: 5px;font-size : 11px;">Please wait while the document loads..</span>
<br>
<br>

<?php

flush();
ini_set("display_errors", 1);
ini_set("memory_limit","64M");
include("nusoap/lib/nusoap.php");
include("nusoap/functions.php");

// getting categories


$array = tep_main_categories();

function getid($id) {

$k=0;
global $array;
foreach($array as $a) {

	if ($a['id'] == $id)
		return $k;

	$k++;
}

}

function getparent($i,$id,$first = 'false') {
		
	if ($first == 'true')
		$value = '';
		
	global $array;
	
	if ($array[$i]['pID'] == 0) {
		return $array[$i]['text'] . ' >> ';
	}
	else { 

		$newid = getid($array[$i]['pID']);
								
		
		if ($first != 'true')
				$value .= $array[$i]['text'] . ' >> ';
				
		return getparent($newid,$array[$newid]['id'],'false') . $value;
		
	}
}


$i=0;
foreach ($array as $a) {


	if ($a['pID'] == "0") {


	$cat_values .= "<option value=\"$a[text]\">$a[text]</option>";

}
	else {
		$val = getparent($i,$a['id'],'true');
		
		$val = $val . $a['text'];
		$total = explode(">>",$val);
	
	
		if (count($total) == "2")
			$spaces = '&nbsp;&nbsp;';
		else
			$spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';

		$cat_values .= "<option value=\"$val\">$spaces $a[text]</option>";
		
	}
	
	$i++;
}


function showcat($i) {
	
	global $cat_values;
	$categories = '<select name="category' . $i .'">';
	$categories .= $cat_values;
	$categories .= '</select>';
	
	return $categories;
}

$colorquery = tep_db_query("select products_options_values.products_options_values_name AS c FROM products_options_values,products_options_values_to_products_options WHERE products_options_values_to_products_options.products_options_values_id=products_options_values.products_options_values_id AND products_options_values_to_products_options.products_options_id='1'");


while ($colors = tep_db_fetch_array($colorquery)) {
$cat_color_values .= '<option value="' . $colors['c'] . '">' . $colors['c'] . '</option>';
}

$sizequery = tep_db_query("select products_options_values.products_options_values_name AS c FROM products_options_values,products_options_values_to_products_options WHERE products_options_values_to_products_options.products_options_values_id=products_options_values.products_options_values_id AND products_options_values_to_products_options.products_options_id='2'");

while ($sizes = tep_db_fetch_array($sizequery)) {

$size_values .= '<option value="' . $sizes['c'] .'">' . $sizes['c'] . '</option>';

}

function show_color($i) {
	
	global $cat_color_values;
	
	$colors .='<select name="color' . $i . '">';	
	$colors .= $cat_color_values;
	$colors .='</select>';
	
	
	return $colors;
}

function show_size($i) {
	
	global $size_values;
	
	$sz .='<select name="size' . $i . '">';	
	$sz .= $size_values;
	$sz .='</select>';
	
	return $sz;
}

$q = tep_db_query("SELECT * FROM visualproducts");

$rows = 0;
while ($query = tep_db_fetch_array($q))
	$rows++;

if (!isset($_GET['page'])) {
	$_POST['products'] = 0;
	tep_db_query("TRUNCATE TABLE visualproducts_selected");
}
else
	$pagenum = $_GET['page'];

$page_rows = 100;

//This tells us the page number of our last page
$last = ceil($rows/$page_rows);

if ($pagenum < 1)
{
$pagenum = 1;
}
elseif ($pagenum > $last)
{
$pagenum = $last;
}

//This sets the range to display in our query
$max = 'limit ' .($pagenum - 1) * $page_rows .',' .$page_rows;

// build the array of products

$q = mysql_query("SELECT * FROM visualproducts $max");
$base = array();
while ($array = mysql_fetch_array($q)) 
	$base[] = $array;

$len = count($base);

// when next page was submitted 

if (isset($_POST['submit'])) {
	
	$count = 0;	
	for ($i=0;$i<$len;$i++) { 
	
		$x = 'val' . $i;
		
		if (isset($_POST[$x])) {
			
			$count++;
			$ps = $_POST['sku' . $i];
			
		//	echo 'sku : ' . $ps . '<br>';
			$query = mysql_query("SELECT * FROM visualproducts WHERE ipccode='$ps'");
			$data = mysql_fetch_array($query);
			
			$names = explode(" ",$data['productname']);

			$cat = $_POST['category' . $i];
			$ProductName = mysql_escape_string($name[0] . ' ' .$names[1]);
			$ProductModel = $names[0];
			$ProductSku = $ps;
			$ProductPrice = $data['price'];
			$ProductQuantity = ' ';
			$ProductInfo = mysql_escape_string($data['description2']);
			$ProductDescription = mysql_escape_string($data['description1']);
			$ProductKeywords = ' ';
		//	$Manufacturers = mysql_escape_string($data['brandlabel']);
			$Manufacturers = '';
			$Size = $_POST['size' . $i];
			$Color = $_POST['color' . $i];
			$xsell = ''; 			
			
			tep_db_query("INSERT INTO visualproducts_selected (id,categories_name,products_name,products_model,products_sku,products_price,products_quantity,products_info,products_description,products_head_keywords_tag,manufacturers_name,attr_color,attr_size,xsell) VALUES ('','$cat','$ProductName','$ProductModel','$ProductSku','$ProductPrice','$ProductQuantity','$ProductInfo','$ProductDescription','$ProductKeywords','$Manufacturers','$Color','$Size','$xsell')");
		}
		
	}
			
	$_POST['products'] = $_POST['products'] + $count; // counting the number of products selected	
	
}

//This does the same as above, only checking if we are on the last page, and then generating the Next and Last links
if ($pagenum == $last)
{
}
else {
$next = $pagenum+1;

?>		
<div style="font-size: 10px;">
	
<form method="POST" action="?page=<?=$next?>">

<input type="submit" name="submit" value="Next page"></input>&nbsp;&nbsp;&nbsp;<input type="button" onClick="checkAll(this);" value="Check All">&nbsp;&nbsp;&nbsp;<input type="button" onClick="uncheckAll(this);" value="Uncheck All"><br><br>


<span style="background-color: #6296FC;color:#ffffff;margin-left: 3px;margin-top: 3px;padding: 5px;font-size : 12px;"> Page <?=$pagenum?> of <?=$last?> </span> 


<?php if ($_GET['page'] != "") { ?>
<span style="background-color: #6296FC;color:#ffffff;margin-left: 4px;margin-top: 3px;padding: 5px;font-size : 12px;"> 
<strong><?=$_POST['products']?></strong> were exported
<?php } ?>
</span>&nbsp;


<?php if ($_GET['page'] != "") { ?>
<span style="background-color: #6296FC;color:#ffffff;margin-left: 4px;margin-top: 3px;padding: 5px;font-size : 12px;"> 
	<a href="completeimport.php" style="color: #fff;font-weight: bold;">Complete Import</a>
</span>
<?php } ?>


<?php } ?>
</p>    
<input type="hidden" name="products" value="<?=$_POST['products']?>">
<br>
<br>
<table>
	<tr class="yellow">

<th>&nbsp;</th>
<th>Product Name </th>
<th>Category</th>
<th>Color</th>
<th>Size</th>
<th>IPCCode</th>
<th>Selling Period</th>
	</tr>

<?php 

for($i=0;$i<$len;$i++)
{
	
	$names = explode(" ",$base[$i]['productname']);
?>

<?php

if ($i % 2 == 0)
	echo '<tr class="strip">';
else
	echo '<tr>';
?>

<td><input type="checkbox" name="val<?=$i?>"></td>

<?php 

?>

<td><?=$names[0]?><br><?=$names[1]?></td>
<td>
<?php echo showcat($i); ?>
</td>
<td>
<?=$base[$i]['color']?>
<br>
<?php echo show_color($i); ?>
</td>
<td>
<?=$base[$i]['size']?>
<br>
<?php echo show_size($i); ?>
</td>
<td>
<?=$base[$i]['ipccode']?>
<input type="hidden" name="sku<?=$i?>" value="<?=$base[$i]['ipccode']?>">
</td>
<td>
<?=$base[$i]['sellingperiod']?>
</td>	
</tr>

<?
} 
?>

</table>
<br>
</form>
</div>
</body>
</html>
