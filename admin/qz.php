<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	require('includes/application_top.php');

	$label_query = tep_db_query("SELECT label_digest, tracking_number FROM ".TABLE_ORDERS_SHIPPED." WHERE orders_id = '".$_GET['oID']."' AND (ship_type ='Full' OR ship_type = 'Partial')");

	if(tep_db_num_rows($label_query) > 0) {

		$printlabel=0;
		while($label = tep_db_fetch_array($label_query)) { 
			    $theLabel = $label['label_digest'];
		$printlabel++;	
		}
}
//echo $printlabel . '<br><br>' . $theLabel;

echo '<img src="data:image/gif;base64,'. $theLabel. '" width="100" height="60">';

if($_GET['process'] == '1') { 
echo json_encode(array(
'barcode' => $theLabel
));
}
?>
<html>
	<head>
<!--[if lt IE 9]><script type="text/javascript" src="js/jquery-1.10.2.min.js"></script><![endif]-->
<!--[if IE 9]><!--><script type="text/javascript" src="js/jquery-2.0.3.min.js"></script><!--<![endif]-->

   <script type="text/javascript" src="js/html2canvas.js"></script>
   <script type="text/javascript" src="js/jquery.plugin.html2canvas.js"></script>
		<script src="js/directPrint.js" type="text/javascript"></script>
	</head>
	<body>
		<div id='tmp'></div>
		<input type='button' id='print' value='Print Barcode' onClick='doClick();' />
		
	<script>
	function doClick () {
		$.post('qz.php?process=1', function (response) {
			if(response.barcode) {
				$('#tmp').text(response.barcode);
				directPrint.setPrinterName('Canon MX450 series Printer'); //set printer name
				directPrint.init('#tmp');
				directPrint.print();
				alert('Printing. ' + response.barcode);
			} else {
				alert(response.message || 'Error has been detected.. please try again...');
			}
		}, 'json').error(function() {
				alert("Error has been detected.. please try again...");
			});
	}	
</script>
<div id="tmp"></div>
</body>
</html>