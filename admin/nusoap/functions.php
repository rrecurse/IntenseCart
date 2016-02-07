<?php

 function tep_main_categories($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
    global $languages_id;
    global $template_config;
    if (!is_array($category_tree_array)) $category_tree_array = array();

    $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd 
where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and c.parent_id = '" . (int)$parent_id . "' and c.categories_status=1 AND 
c.products_class='product_default' order by c.sort_order, cd.categories_name");
    while ($categories = tep_db_fetch_array($categories_query)) {
      
if ($exclude != $categories['categories_id']) $category_tree_array[] = array('id' => $categories['categories_id'], 'pID' => $parent_id, 'text' => $spacing . 
$categories['categories_name']);
      $category_tree_array = tep_main_categories ($categories['categories_id'], $spacing . '', $exclude . '', $category_tree_array); 
    }
    return $category_tree_array;

  }

function searchid($id) {

	 $category_array = tep_main_categories(); 

	foreach($category_array as $c) {
		if ($c['id'] == $id)
			return $c['text'];
	}
}

function exportMysqlToCsv($table,$filename = 'export.csv')
{
    $csv_terminated = "\n";
    $csv_separator = ",";
    $csv_enclosed = '';
    $csv_escaped = "\\";
    $sql_query = "select categories_name,products_name,products_model,products_sku,products_price,products_quantity,products_info,products_description,products_head_keywords_tag,manufacturers_name,attr_color,attr_size,xsell from $table";
 
    // Gets the data from the database
    $result = mysql_query($sql_query);
    $fields_cnt = mysql_num_fields($result);
 
 
    $schema_insert = '';
 
    for ($i = 0; $i < $fields_cnt; $i++)
    {
        $l = $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed,
            stripslashes(mysql_field_name($result, $i))) . $csv_enclosed;
        $schema_insert .= $l;
        $schema_insert .= $csv_separator;
    } // end for
 
    $out = trim(substr($schema_insert, 0, -1));
    $out .= $csv_terminated;
 
    // Format the data
    while ($row = mysql_fetch_array($result))
    {
        $schema_insert = '';
        for ($j = 0; $j < $fields_cnt; $j++)
        {
            if ($row[$j] == '0' || $row[$j] != '')
            {
 
                if ($csv_enclosed == '')
                {
                    $schema_insert .= $row[$j];
                } else
                {
                    $schema_insert .= $csv_enclosed . 
					str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $row[$j]) . $csv_enclosed;
                }
            } else
            {
                $schema_insert .= '';
            }
 
            if ($j < $fields_cnt - 1)
            {
                $schema_insert .= $csv_separator;
            }
        } // end for
 
        $out .= $schema_insert;
        $out .= $csv_terminated;
    } // end while
 

	$f = fopen($filename,"w");
	fwrite($f,$out);
	fclose($f); 
}
 

?>
