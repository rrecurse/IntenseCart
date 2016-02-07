<?php

$date = date("Y-m-d");
$file = "csvdir/products-" . $date . ".csv";

$data = file($file);
	
header('Content-Disposition: attachment; filename="' . $file . '"');
header("Content-Type: text/csv");
  
foreach($data as $line)
	echo $line;
	
?>
