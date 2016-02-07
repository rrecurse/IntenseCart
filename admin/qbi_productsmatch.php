<?php

	require('includes/application_top.php');

	require(DIR_WS_LANGUAGES . $language . '/qbi_general.php');
	require(DIR_WS_INCLUDES . 'qbi_version.php');
	require(DIR_WS_INCLUDES . 'qbi_definitions.php');
	require(DIR_WS_INCLUDES . 'qbi_page_top.php');
	require(DIR_WS_INCLUDES . 'qbi_menu_tabs.php');

	if (isset($stage) AND $stage=="produpdate") {
		prod_update($product_menu);
		echo MATCH_SUCCESS;
	}

	echo '<form action="'. $_SERVER[PHP_SELF] .'" method="post" name="qbi_products" id="qbi_products">
			<input name="stage" id="stage" type="hidden" value="produpdate">
			<input name="search_page" id="search_page" type="hidden" value="'. $search_page .'">

			<table class="lists" width="100%" cellpadding="5" cellspacing="0">';
			

	$count = tep_db_result(tep_db_query("SELECT COUNT(products_id) FROM ".TABLE_PRODUCTS." WHERE products_status = '1' AND products_price > 0"),0);

	if($count > 0) { 

		$page = new page_class($count, QBI_PROD_ROWS, 10); 

		$limit = $page->get_limit();

		$result = tep_db_query("SELECT p.products_id,
									   p.products_model, 
									   pd.products_name,
									   ov.products_options_values_name
								FROM ". TABLE_PRODUCTS ." p
								LEFT JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON (pd.products_id = p.products_id AND pd.language_id='".$languages_id."') 
								LEFT JOIN ". TABLE_PRODUCTS_ATTRIBUTES ." pa ON pa.products_id = p.products_id
								LEFT JOIN  ". TABLE_PRODUCTS_OPTIONS_VALUES ." ov ON (ov.products_options_values_id = pa.options_values_id AND ov.language_id='".$languages_id."')
								WHERE p.products_status = '1' AND p.products_price > 0
								GROUP BY p.products_model
								ORDER BY pd.products_name ASC
								 ".$limit."
								");

		$hstring = $page->make_head_string(PRODMATCH_TITLE); 
		
		// # add the other variables to pass to next page in a similar fashion 
		$pstring = $page->make_page_string(); 

		echo '<tr><th colspan="3" class="counter">'.$hstring.'</th></tr>
				<tr><td colspan="3">&nbsp;</td></tr>
				<tr><th class="colhead" class="dataTableHeadingRow">'.MATCH_OSC.'</th>
					<th></th>
					<th class="colhead">'.MATCH_QB.'</th>
			</tr>'; 

		while ($row = tep_db_fetch_array($result)) {

				echo '<tr class="'.($ct++&1 ? 'tabEven' : 'tabOdd').'">
						<td class="dataTableContent">'.substr($row['products_model'],0,24).'</td>
						<td class="dataTableContent">'.substr($row['products_name'],0,256) . (!empty($row['products_options_values_name']) ? ' ('. $row['products_options_values_name'] .')' : '').'</td>';
			
			item_menu($row['products_id'],0);
		}

		echo "<tr><td colspan=\"3\">&nbsp;</td></tr>\r\n";
		echo "<tr><td colspan=\"3\" class='pagelist'>$pstring</td></tr>\r\n";
	}
?>

		<tr>
			<td colspan="3">
				<input name="submit" type="submit" id="submit" value="<?php echo MATCH_BUTTON ?>" />
			</td>
		</tr>
</table>
</form>
<?php require(DIR_WS_INCLUDES . 'qbi_page_bot.php'); ?>