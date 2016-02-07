<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

	include('includes/application_top.php');

	$channels = array ('ads'=>'Advertising Costs', 'sellercomm'=>'Marketplace Costs', 'sem'=>'SEM Costs');

	if(!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Ad Cost Editor');

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<link rel="stylesheet" href="js/css.css" type="text/css">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">

<script type="text/javascript" src="js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="js/tips.js"></script>
<script type="text/javascript" src="js/iframe.js"></script>
<script type="text/javascript" src="js/prototype.lite.js"></script>

<title><?php echo HEADING_TITLE ?></title>

<style>

.container {
	width:190px;
	display: inline-block;
	column-count: 2;
	column-gap: 10px;
	margin:0 5px;
}

.costTable {
	margin:15px 0;
	border-collapse:collapse;
	border: 1px solid #d4d4d4;
	width:180px;


}

.costTable th, .costTable td {
	border: 1px solid #d4d4d4
}

.costTable tr:first-child {
	background-color: #FFFFC4; 
}

.costTable tr:nth-child(even) {
	background-color: #FFF; 
}

.costTable tr:last-child {
	background-color: #E4F0D3;
}

</style>
</head>

<body style="margin:0; background:transparent;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>

<table width="100%">
	<tr>
		<td width="50"><img src="images/icons/adverticon.png" width="64" height="64"></td>
		<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>	
	</tr>
</table>

<?php

	if($_GET["timestamp"]) {

		$starting_date = date("Y-m", $_GET["timestamp"]).'-01';

		echo '<form action="'.$PHP_SELF.'" method="POST">

				<table width="100%" border="0">
					<tr>
						<th colspan="2">'.date("M Y", $_GET["timestamp"]).'</th>
					</tr>';

		foreach ($channels as $k => $v)	{

			$result = tep_db_query("SELECT acc_cost 
									FROM maintenance_cost 
									WHERE acc_channel = '". $k ."' 
									AND acc_month = '". $starting_date ."'
								   ");

			$row = tep_db_fetch_array($result);

			echo '<tr>
					<td>'.$v.'</td>
					<td><input type="text" name="'.$k.'" value="'.$row["acc_cost"].'"></td>
				 </tr>';
		}

		echo '<tr>
				<td><input type="hidden" name="update" value="1"><input type="hidden" name="timestamp" value="'.$_GET["timestamp"].'"></td>
				<td><input type="submit" value="Save!"></td>
			  </tr>
			</table>
		</form>';

	} else {

		if ($_POST["update"]) {
			$starting_date = date("Y-m", intval($_POST["timestamp"])).'-01';

			foreach ($channels as $k => $v)	{
				$result = tep_db_query("REPLACE INTO maintenance_cost 
										SET acc_cost = '". floatval($_POST[$k]) ."', 
										acc_channel = '". $k ."',
										acc_month = '". $starting_date ."'
									  ");
			}
		}

		$chan = array_keys($channels);
	
		foreach ($chan as $c) {
			$cost = 0;
		}

		$today = date('Y-m-d', time());

		list($today_year, $today_month, $today_day) = sscanf($today, "%d-%d-%d");

		$current_month_first_day_timestamp = mktime(0, 0, 0, $today_month, 1, $today_year);

		$result = tep_db_query("SELECT min(acc_month) FROM maintenance_cost");
		$starting_date = tep_db_fetch_array($result);

		if(is_null($starting_date["min(acc_month)"])) {

			list($start_year, $start_month, $start_day) = sscanf($today, "%d-%d-%d");

		} else {

			list($start_year, $start_month, $start_day) = sscanf($starting_date["min(acc_month)"], "%d-%d-%d");
		}

		$starting_date_timestamp = mktime(0, 0, 0, $start_month, 1, $start_year);

		$months = array();

		while ($starting_date_timestamp <= $current_month_first_day_timestamp) {
			$starting_date = sprintf("%04d-%02d-%02d", $start_year, $start_month, 1);

			$current_month["timestamp"] = $starting_date_timestamp;
			$current_month["sum"] = 0;


			foreach ($chan as $c) {

				$result = tep_db_query("SELECT acc_cost 
										FROM maintenance_cost 
										WHERE acc_channel = '". $c ."' 
										AND acc_month = '". $starting_date ."'
										");

				if(tep_db_num_rows($result) > 0) {

					$row = tep_db_fetch_array($result);

					if(!empty($row["acc_cost"])) {
						$cost = $row["acc_cost"];
					} else {
						$result = tep_db_query("UPDATE maintenance_cost 
												SET acc_channel = '". $c ."',
												acc_month = '". $starting_date ."',
												acc_cost = '". $cost."'
												WHERE cc_channel = '". $c ."',
												AND acc_month = '". $starting_date ."'	
												");
					}

				} else {

					$result = tep_db_query("REPLACE INTO maintenance_cost 
											SET acc_channel = '". $c ."', 
											acc_month = '". $starting_date ."', 
											acc_cost = '". $cost ."'
										   ");
				}

				$current_month[$c] = $cost;
				$current_month['sum'] += $cost;
			}

			array_unshift ($months, $current_month);

			if ($start_month < 12) {
				$start_month++;
			} else {
				$start_month = 1;
				$start_year++;
			}

			$starting_date_timestamp = mktime(0, 0, 0, $start_month, 1, $start_year);

		}

		foreach ($months as $m) {
			
			echo '<div class="container">
					<table cellpadding="5" cellspacing="0" class="costTable">
					<tr>
						<td style="font:bold 12px arial">' . date("F, Y", $m["timestamp"]) . '</td> 
						<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?timestamp='.$m["timestamp"].'">[Edit]</a></td>
					</tr>';

			foreach ($channels as $k => $v) {
				echo '<tr>
						<td>'. $v. ':</td> <td> $'. $m[$k].'</td>
					 </tr>';
			}

			echo '<tr>
					<td style="font: bold 12px arial; color:#000;">Total Spend:</td> <td style="font: bold 12px arial; color:#000;">$'. $m["sum"].'</td>
				  </tr>
				</table>
			</div>';
		}
	}
?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>