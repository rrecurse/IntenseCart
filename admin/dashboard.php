<?php

require('includes/application_top.php');

define('ADMIN_PERMISSION','ALL');
define('DIR_FS_DASH_BOX',DIR_FS_MODULES.'dash_box/');
define('DIR_FS_DASH_BOX_JS',DIR_FS_ADMIN.'js/dash_box/');

function render_td($table_cols, $table_rows, $obj) {
		if ($table_cols == 1) $width=283;
		if ($table_cols == 2) $width=571;

		if ($obj=="none") {
				print "<td rowspan=".$table_rows." colspan=".$table_cols." >&nbsp;</td>";
			} else {
				print "<td rowspan=".$table_rows." colspan=".$table_cols." >";
				$obj->render();
				print "</td>";
			}
	}

	$result = tep_db_query("SELECT dash_table, sort_order, dash_permission 
						  	FROM admin_dash 
						  	WHERE dash_name = '". $_GET['dash'] ."'
						  	ORDER BY sort_order
						   ");
	$i = 0;
?>
<!DOCTYPE html>
	<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
		<link rel="stylesheet" type="text/css" href="js/css.css">
		<script type="text/javascript" src="js/tips.js"></script>
		<script type="text/javascript" src="js/iframe.js"></script>
		<script type="text/javascript" src="js/prototype.lite.js"></script>
		<script type="text/javascript" src="js/xmlfeed.js"></script>
		<!--script type="text/javascript" src="js/popcalendar.js"></script-->
		<!--script type="text/javascript" src="https://www.google.com/jsapi"></script-->

<title>Dashboards</title>

<style type="text/css">
	html, body {min-height:1000px; overflow-y:hidden}

@media screen and (min-width: 1091px) {

	* {
		font-size:100% !important;
	}

	.tableinfo_right-btm {
		height:35px !important;
	}

	.dashbox_bluetop {	
		height:35px !important; 
		background-color:#6295FD; 
		font:bold 12px arial; 
		color:#FFFFFF;
	}

	#chartHeight { 
		height:350px !important; 

	}

}

@media screen and (max-width: 1090px) {

	.dashbox_bluetop {
		height:22px; 
		background-color:#6295FD; 
		font:bold 12px arial; 
		color:#FFFFFF;
	}

	#chartHeight { 
		height:268px;

	}

}

</style>
</head>

<body>
<?php include(DIR_WS_INCLUDES.'header.php');?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="padding:0 0 0 4px">

<?php
error_log(print_r($result[0],1));
	while ($row = tep_db_fetch_array($result)) {
			if (!AdminPermission($row['dash_permission'])) continue;
			$table[$i]=$row['dash_table'];
			$table_file[$i]=DIR_FS_DASH_BOX.$table[$i].".php";
			$sort[$i]=$row['sort_order'];
			if (@is_file($table_file[$i]))
				{
					include($table_file[$i]);
					$str[$i] = $table[$i];
					$obj[$i] = new $str[$i]();
					$t_cols[$i]=$obj[$i]->table_cols;
					$t_rows[$i]=$obj[$i]->table_rows;
					$i++;
				}
		}
	$j=0;
	while ($j<$i) {
			$cols=0;
			print '<tr valign="top">';
			render_td($t_cols[$j], $t_rows[$j], $obj[$j]);
			$count=1;
			if ($cols+$t_cols[$j]==1)
				{
				
				if ($j+1<$i)
					if ($t_cols[$j+1]=="1" && $t_rows[$j+1]=="1")
						{
							render_td($t_cols[$j+1], $t_rows[$j+1], $obj[$j+1]);
							print "</tr>";
							$count=2;
							if ($t_rows[$j]>1)
								{	
						
									$k=2;
									$pre=1;
							  		while ($k<=$t_rows[$j])
							  		{
										if ($t_cols[$j+$k]==1 && $t_rows[$j+$k]==1 && $pre==1 && $j+$k<=$i)
											{
												print '<tr valign="top">';
												render_td($t_cols[$j+$k], $t_rows[$j+$k], $obj[$j+$k]);
												print "</tr>";
												$count=$k+1;
											}
										else
											{
												print '<tr valign="top">';
												render_td($t_cols[$j+$k], $t_rows[$j+$k], "none");
												print "</tr>";
												$pre=0;
											}
										$k++;
									}
								}
							  
						}
					else 
					{
						render_td($t_cols[$j], 1, "none");
					print "</tr>";
					}
				
				else
				{
					render_td($t_cols[$j], 1, "none");
					print "</tr>";
					break;
				}
			}	
			else
				{
					print "</tr>";
					
				}
			$j=$j+$count;
		}
	
	print "</table></body></html>";
?>

