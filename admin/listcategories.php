<?php

require('includes/application_top.php');


?>
<html>
<head>
<title>Valid Categories/Products List</title>
<style type="text/css">
<!--
h4 {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: x-small; text-align: center}
p {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: xx-small}
th {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: xx-small}
td {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: xx-small}
-->
</style>
<head>
<body>
<table width="550" border="1" cellspacing="1" bordercolor="gray">
<tr>
<td colspan="4">
<h4>Valid Categories List</h4>
</td>
</tr>
<?php
	$coupon_get = tep_db_query("SELECT restrict_to_categories 
								FROM " . TABLE_COUPONS . "
								WHERE coupon_id='". $_GET['cid']."'
								");


	if(tep_db_num_rows($coupon_get) > 0) { 

		$get_result = tep_db_fetch_array($coupon_get);

		echo "<tr><th>Category ID</th><th>Category Name</th></tr>";

		$cat_ids = preg_split('/[,]/', $get_result['restrict_to_categories']);

		for ($i = 0; $i < count($cat_ids); $i++) {
	
			$result = mysql_query("SELECT c.*, cd.* 
								   FROM categories c
								   LEFT JOIN categories_description cd ON cd.categories_id = c.categories_id
								   WHERE cd.language_id = '" . $languages_id . "'
								   AND c.categories_id='" . $cat_ids[$i] . "'
								  ");

			if ($row = mysql_fetch_array($result)) {
				echo '<tr>
						<td>'.$row['categories_id'].'</td>
						<td>'.$row['categories_name'].'</td>
					  </tr>';
			} 
		}

	} else {

		echo '<tr>
				<th colspan="2">Please ensure the ?cid= in your URL it set to active coupon code</th>
			  </tr>';
	}

	echo "</table>\n";
?>
<br>
<table width="550" border="0" cellspacing="1">
<tr>
<td align=middle><input type="button" value="Close Window" onClick="window.close()"></td>
</tr></table>
</body>
</html>
