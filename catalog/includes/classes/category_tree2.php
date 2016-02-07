<?php

 class catTree {
   var $root_category_id = 0,
       $max_level = 0,
       $data = array(),
       $root_start_string = '',
       $root_end_string = '',
       $parent_start_string = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:5px 0;">',
       $parent_end_string = '</table>',
       $parent_group_start_string = '',
       $parent_group_end_string = '',
       $child_start_string = '<tr><td> ',
       $child_end_string = '</td></tr>',
       $spacer_string = '<br>',
       $spacer_multiplier = 1;

	function __construct($load_from_database = true) {

		global $languages_id;

		$categories_query = tep_db_query("SELECT c.categories_id, 
												 cd.categories_name, 
												 c.parent_id 
										  FROM " . TABLE_CATEGORIES . " c
										  LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON  cd.categories_id = c.categories_id
										  WHERE cd.language_id = '" . (int)$languages_id . "' 
										  AND categories_status = '1' 
										  AND categories_name NOT LIKE '%Gift Certificates%' 
										  ORDER BY c.parent_id, c.sort_order, cd.categories_name
										");
		$this->data = array();

		while ($categories = tep_db_fetch_array($categories_query)) {
			// # initialize array container
			$c = array();
			// # Get the category path, $c is passed by reference
			tep_get_parent_categories($c, $categories['categories_id']);
			// # reverse the array
			$c = array_reverse($c);
			// # Implode the array to get the full category path
			$id = (implode('_', $c) ? implode('_', $c) . '_' . $categories['categories_id'] : $categories['categories_id']);
			$this->data[$categories['parent_id']][$id] = array('name' => $categories['categories_name'], 'count' => 0);
		}

		tep_db_free_result($categories_query);
	}

	function buildBranch($parent_id, $level = 0) {

		$result = $this->parent_group_start_string;

		if (isset($this->data[$parent_id])) {
			
			foreach ($this->data[$parent_id] as $category_id => $category) {
	
				$category_link = $category_id;
if(!is_null($category_id)) { 
				$result .= $this->child_start_string;

				if (isset($this->data[$category_id])) {
					$result .= $this->parent_start_string;
				}

				if ($level == 0) {

					$result .=  $this->root_start_string;

					$result .= str_repeat($this->spacer_string, $this->spacer_multiplier * $level) . '<h4 style="padding-top:10px;"><a href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $category_link) . '" class="sitemap_subcat" style="font:bold 16px arial;">' .$category['name'] . '</a></h4>';
		 		} else { 

					$result .= str_repeat($this->spacer_string, $this->spacer_multiplier * $level) . ' <a href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $category_link) . '" class="sitemap_subcat">' .$category['name'] . '</a><br>';
				}


				$result .= $this->buildProducts($category_id);
         
				if ($level == 0) {
					$result .= $this->root_end_string;
				}

				if (isset($this->data[$category_id])) {
					$result .= $this->parent_end_string;
				}

				$result .= $this->child_end_string;
}
				if (isset($this->data[$category_id]) && (($this->max_level == '0') || ($this->max_level > $level+1))) {
					$result .= $this->buildBranch($category_id, $level+1);
				}
			}
		}

		$result .= $this->parent_group_end_string;

		return $result;
	}

	function buildProducts($category_id) {
     	global $languages_id;

		if (strpos($category_id,"_") !== false) {

			$categori_id = explode("_",$category_id); 
			$categori_id = $categori_id[sizeof($categori_id)-1];

		} else {

			$categori_id=$category_id;
		}

		$products_query = tep_db_query("SELECT p.products_id, 
											   p2c.categories_id, 
											   pd.products_name 
										FROM " . TABLE_PRODUCTS ." p
										LEFT JOIN products_description pd ON pd.products_id = p.products_id
										LEFT JOIN products_to_categories p2c ON p2c.products_id = p.products_id
										WHERE p.products_status = '1' 
										AND p.products_price > 0
										AND p2c.categories_id = '".$categori_id."'
										AND pd.language_id = '".$languages_id."'
										ORDER BY pd.products_name
									  ");

		$result='';
		$result .= $this->parent_group_start_string;

		while ($products = tep_db_fetch_array($products_query)) {

			$result .= $this->child_start_string;
			$result.='&bull; &nbsp;<a href="' .tep_href_link(FILENAME_PRODUCT_INFO, ($category_id ? 'cPath=' . $category_id . '&' : '') . 'products_id=' . $products['products_id']) . '" class="sitemap_prods">' . $products['products_name'] . '</a> &nbsp;';
			$result .= $this->child_end_string;
		}

		tep_db_free_result($products_query);

		$result .= $this->parent_group_end_string;
		return $result;
	}


	function buildTree() {
		return $this->buildBranch($this->root_category_id);
	}
}
?>