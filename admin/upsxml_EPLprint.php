<?php 
// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################
header("Content-Type:text/plain");

	// # check if EPL data is sent. Echo if it is.
	if(isset($_GET['epldata']) && !empty($_GET['epldata'])) { 

		echo $_GET['epldata']; 

	}

?>