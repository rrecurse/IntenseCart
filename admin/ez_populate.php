<?php include('includes/application_top.php'); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?=CHARSET?>">
  <title>Bulk Catalog Updater</title>
<link rel="stylesheet" type="text/css" href="js/css.css">
</head>
<body style="margin:0; background:transparent;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td width="60" style="padding:5px; padding-right:1px;"><img src="images/product-icon.gif" width="48" height="48" border="0"></td>
    <td style="font:bold 17px arial; white-space:nowrap;">Bulk Catalog Updater</td>
</tr></table>
<?php
require(DIR_WS_INCLUDES . 'header.php');

set_time_limit(0);

if ($_POST["import"]) {
	if (!$_FILES["csv_file"]["error"]) {
		$products = file ($_FILES['csv_file']['tmp_name']);
		if (sizeof($products)==1) $products=explode("\n",$products[0]);
	} else {
		$products = explode ("\r\n", $_POST["csv_text"]);
	}

	$column_names = explode(',', array_shift($products));
	$column_names = array_map ("trim", $column_names);

	$attributes = preg_grep('|^attr_|i', $column_names);
	foreach ($attributes as $attribute) { // # prepare product_options
		$attribute_name = substr($attribute,5);
		$query = 'SELECT products_options_id FROM products_options WHERE language_id='.$languages_id.' AND products_options_name="'.$attribute_name.'"';
		$result = tep_db_query ($query);
		if (tep_db_num_rows($result)) {
			$row = tep_db_fetch_array($result);
			$product_options[$row["products_options_id"]] = $attribute_name;
		} else {
			$query ='SELECT max(products_options_id) FROM products_options WHERE language_id='.$languages_id;
			$result = tep_db_query ($query);
			$row = tep_db_fetch_array($result);
			$inserted_id = intval($row["max(products_options_id)"]);

			$query = 'INSERT INTO products_options SET
				products_options_id='.++$inserted_id.',
				language_id='.$languages_id.',
				products_options_name="'.$attribute_name.'"';
			tep_db_query($query);

			$product_options[$inserted_id] = $attribute_name;
		}
	}

	$product_options = array_map ("strtolower", $product_options);
	$product_options = array_flip ($product_options);

	$column_names = array_flip ($column_names);

	$query = 'SELECT manufacturers_id, manufacturers_name FROM manufacturers';
	$result = tep_db_query ($query);
	while ($row = tep_db_fetch_array($result)) {
		$manufacturers[$row["manufacturers_id"]] = strtolower($row["manufacturers_name"]);
	}
	$manufacturers = array_flip ($manufacturers);


	echo 'Importing please wait';
	foreach ($products as $current_product)	{
		$csv_row++;
		$current_product = trim ($current_product);

		$cells = explode(',', $current_product);
		$length = count ($cells);
		for ($j=0; $j<=$length; $j++) {
			if ((substr($cells[$j],0,1)=='"') && (substr($cells[$j],-1,1)!='"')) {
				$cells[$j+1] = $cells[$j].','.$cells[$j+1];
				unset ($cells[$j]);
			}
		}
		$j = 0;
		foreach ($cells as $cell)
		{
			if (preg_match('/^\s*"(.*)"\s*$/',$cell,$cell_p)) $cell=$cell_p[1];
			$new_cells[$j++] = $cell;
		}
		$cells = $new_cells;
		$cells = array_map ("trim", $cells);

  
		$cpath=preg_split('!\s*>>+\s*!',$cells[$column_names["categories_name"]]);
		$categories_id=0;
		foreach ($cpath AS $cat) if ($cat!='') {
		  $cat=substr($cat,0,32);
		  $cid=IXdb::read("SELECT c.categories_id FROM categories c,categories_description cd WHERE c.categories_id=cd.categories_id AND cd.categories_name='".addslashes($cat)."' AND c.parent_id='$categories_id' AND cd.language_id='$languages_id'",NULL,'categories_id');
		  if ($cid) $categories_id=$cid;
		  else {
		    $categories_id=IXdb::store('INSERT','categories',Array('parent_id'=>$categories_id));
		    IXdb::store('INSERT','categories_description',Array('categories_id'=>$categories_id,'categories_name'=>$cat,'language_id'=>$languages_id));
		  }
		}

/*
		$query = 'SELECT categories_id, categories_name FROM categories_description WHERE categories_name="'.$cells[$column_names["categories_name"]].'"';
		$result = tep_db_query ($query);
		if (tep_db_num_rows($result))
		{
			$row = tep_db_fetch_array($result);
			$categories_id = $row["categories_id"];
		}
		else
		{
			$query ='INSERT INTO categories SET categories_id=""';
			$result = tep_db_query ($query);
			$categories_id = tep_db_insert_id();
			$query = 'INSERT INTO categories_description SET categories_name="'.$cells[$column_names["categories_name"]].'", language_id='.$languages_id.', categories_id='.$categories_id;
			tep_db_query ($query);

		}
*/


		$product["products_sku"] = $cells[$column_names["products_sku"]];
		$product["products_model"] = $cells[$column_names["products_model"]];
		$product["products_price"] = preg_replace('/[^0-9\-\.]/','',$cells[$column_names["products_price"]]);
		$product["products_weight"] = preg_replace('/[^0-9\-\.]/','',$cells[$column_names["products_weight"]]);
		$product["products_quantity"] = $cells[$column_names["products_quantity"]];
		$product["manufacturers_id"] = intval($manufacturers[strtolower($cells[$column_names["manufacturers_name"]])]);// 0 if not set
		$product["products_name"] = $cells[$column_names["products_name"]];
		$product["products_info"] = $cells[$column_names["products_info"]];
		$product["products_description"] = $cells[$column_names["products_description"]];
		$product["products_url"] = $cells[$column_names["products_url"]];
		$product["products_image"] = $cells[$column_names["products_image"]];
		$product["products_head_keywords_tag"] = $cells[$column_names["products_head_keywords_tag"]];
		$product["categories_id"] = $categories_id;
		$product["products_status"]=1;

		$query = 'SELECT products_id FROM products_description WHERE language_id='.$languages_id.' AND products_name="'.mysql_real_escape_string($product["products_name"]).'"';

		$result = tep_db_query ($query);
		if (tep_db_num_rows($result))
		{
			$row = tep_db_fetch_array($result);
			$master_products_id = $row["products_id"];
			$new_master = 0;
			
			$lst=Array();
			foreach (Array('products_info','products_description','products_url','products_head_keywords_tag') AS $k) if ($product[$k]!='') $lst[]=$k.'="'.mysql_real_escape_string($product[$k]).'"';
			if ($lst) tep_db_query("UPDATE products_description SET ".join(',',$lst)." WHERE products_id='$master_products_id'");

			if ($product["categories_id"]) IXdb::query('INSERT IGNORE INTO products_to_categories SET products_id='.$master_products_id.',categories_id='.$product["categories_id"]);
		}
		else
		{
			// insert master
			$new_master = 1;
			$query = 'INSERT INTO products SET
			products_sku ="'.mysql_real_escape_string($product["products_sku"]).'",
			products_model ="'.mysql_real_escape_string($product["products_model"]).'",
			products_price ='.floatval($product["products_price"]).',
			products_weight ='.floatval($product["products_weight"]).',
			products_quantity ='.intval($cells[$column_names["products_quantity"]]).',
			products_image ='.floatval($product["products_image"]).',
			manufacturers_id ='.intval($manufacturers[$column_names["manufacturers_name"]].',
			products_status='.$product["products_status"]
			);
			$result = tep_db_query($query);

			$master_products_id = tep_db_insert_id();

			$query = 'UPDATE products SET master_products_id = products_id WHERE products_id='.$master_products_id;
			tep_db_query($query);

			$query = 'INSERT INTO products_description SET
			products_id='.$master_products_id.',
			language_id='.$languages_id.',
			products_name ="'.mysql_real_escape_string($product["products_name"]).'",
			products_info ="'.mysql_real_escape_string($product["products_info"]).'",
			products_description ="'.mysql_real_escape_string($product["products_description"]).'",
			products_url ="'.mysql_real_escape_string($product["products_url"]).'",
			products_head_keywords_tag ="'.mysql_real_escape_string($product["products_head_keywords_tag"]).'"';
			tep_db_query($query);


			$query = 'INSERT INTO products_to_categories SET
			products_id='.$master_products_id.',
			categories_id='.$product["categories_id"];
			tep_db_query($query);
			// end of inserting master
		}
		
		$has_attributes = 0;
		foreach ($attributes as $key => $attribute)
		{
			if ($cells[$key])
			{//has attribute
				$has_attributes = 1;
			}
		}

		if ($has_attributes)
		{
			if (!$new_master)
			{
				//checking if already in DB
				unset ($current_product_options);
				foreach ($attributes as $attribute)
				{//prepare current product_options
					$attribute_name = substr($attribute,5);
					$current_product_options[strtolower($attribute_name)] = trim(strtolower( $cells[$column_names[$attribute]]));
				}

				$new_attributes = 1;
				$query = 'SELECT products_id FROM products WHERE products_id!=master_products_id AND master_products_id='.$master_products_id;
				$master_result = tep_db_query ($query);
				while ($master_row = tep_db_fetch_array($master_result))
				{
					$query = 'SELECT options_id, options_values_id FROM products_attributes WHERE	products_id='.$master_row["products_id"];
					$result = tep_db_query ($query);
					unset ($my_options);
					while ($attr_row = tep_db_fetch_array($result))
					{
						$query = 'SELECT products_options_name FROM products_options WHERE language_id='.$languages_id.' AND products_options_id='.$attr_row["options_id"];
						$result1 = tep_db_query ($query);
						$ar1 = tep_db_fetch_array($result1);


						$query = 'SELECT products_options_values_name FROM products_options_values WHERE language_id='.$languages_id.' AND products_options_values_id='.$attr_row["options_values_id"];
						$result2 = tep_db_query ($query);
						$ar2 = tep_db_fetch_array($result2);

						$my_options[strtolower($ar1["products_options_name"])] = trim(strtolower($ar2["products_options_values_name"]));
					}

					if ($my_options==$current_product_options)
					{
						$new_attributes = 0;
						break;
					}

				}

			}


			if ($new_master || $new_attributes)
			{//insert new products with options
				$query = 'INSERT INTO products SET
					master_products_id ='.$master_products_id.',
					products_sku ="'.mysql_real_escape_string($product["products_sku"]).'",
					products_model ="'.mysql_real_escape_string($product["products_model"]).'",
					products_price ='.floatval($product["products_price"]).',
					products_weight ='.floatval($product["products_weight"]).',
					products_quantity ='.intval($product["products_quantity"]).',
					manufacturers_id ='.intval($product["manufacturers_id"]);
				$result = tep_db_query($query);

				$products_id = tep_db_insert_id();


				if (isset($column_names["xsell"])) foreach (split(',',$cells[$column_names["xsell"]]) AS $xsell) {
				  $xsell=addslashes(trim($xsell));
				  if ($xsell!='') {
				    $xrow=tep_db_fetch_array(tep_db_query("SELECT p.products_id,p.master_products_id,x.xsell_id FROM products p LEFT JOIN products_description pd ON p.master_products_id=pd.products_id LEFT JOIN products_xsell x ON (x.products_id='$master_products_id' OR x.products_id='$products_id') AND x.xsell_id=p.master_products_id AND x.xsell_channel='default' WHERE p.products_model='$xsell' OR pd.products_name='$xsell'"));
				    if ($xrow && !$xrow['xsell_id']) tep_db_query("INSERT INTO products_xsell (products_id,xsell_id,xsell_channel) VALUES ('$products_id','".$xrow['products_id']."','default')");
				  }
				}


				foreach ($attributes as $attribute)
				{//prepare product_options
					$attribute_name = substr($attribute,5);//Color
					$attribute_value = $cells[$column_names[$attribute]];//mycolor
					if (!$attribute_value) continue;

					$query = 'SELECT products_options_values_id FROM products_options_values WHERE language_id='.$languages_id.' AND products_options_values_name="'.$attribute_value.'"';
					$result = tep_db_query ($query);
					if (tep_db_num_rows($result))
					{
						$row = tep_db_fetch_array($result);
						$products_options_values_id = $row["products_options_values_id"];
					}
					else
					{
						$query ='SELECT max(products_options_values_id) FROM products_options_values WHERE language_id='.$languages_id;
						$result = tep_db_query ($query);
						$row = tep_db_fetch_array($result);
						$inserted_id = intval($row["max(products_options_values_id)"]);
						$products_options_values_id = ++$inserted_id;

						$query = 'INSERT INTO products_options_values SET
							products_options_values_id='.$products_options_values_id.',
							language_id='.$languages_id.',
							products_options_values_name="'.$attribute_value.'"';
						tep_db_query($query);
					}

					$query = 'SELECT * FROM products_options_values_to_products_options WHERE
						products_options_values_id='.$products_options_values_id.' AND
						products_options_id='.$product_options[strtolower($attribute_name)];
					$result = tep_db_query ($query);
					if (!tep_db_num_rows($result))
					{
						$query = 'INSERT INTO products_options_values_to_products_options SET
						products_options_values_id='.$products_options_values_id.',
						products_options_id='.$product_options[strtolower($attribute_name)];
						tep_db_query($query);
					}

					$query = 'INSERT INTO products_attributes SET
						products_id='.$products_id.',
						options_id='.$product_options[strtolower($attribute_name)].',
						options_values_id='.$products_options_values_id.',
						options_sort='.$column_names[$attribute].',
						options_values_sort='.$csv_row;
					tep_db_query($query);
				}
			}
		}

		echo '.';
		if ($z++ > 150)
		{
			echo '<br>';
			$z = 0;
		}
		flush();
		ob_flush();

	}

    echo '<br><b>Complete!</b>';
}

?>
<form method="post" action="" enctype="multipart/form-data">
<table border="0">
<tr><td valign="top" nowrap><b>Product list: </b></td><td><textarea name="csv_text" cols="50" rows="10"></textarea></td></tr>
<tr><td nowrap><b>Product list: </b</td><td><input name="csv_file" type="file"></td></tr>
<tr><td><input type="hidden" name="import" value="1"></td>
<td><input type="submit" value= "import products!"></td></tr>
</table>
</form>
<br>
<table width="100%"><tr><td style="padding:5px;">
<a href="ez_populate.csv.php?data=1"><b>Download Product &amp; Category Backup</b></a>
<br><br>
*Note - It is recommended you create a product with your maximum possibility of attributes. If a product does not have the same amount of attributes (Size / Color / Version etc ...) as another product, simply leaving the attribute blank for a product in which the attribute is not relevant is fine.
</td></tr></table>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
