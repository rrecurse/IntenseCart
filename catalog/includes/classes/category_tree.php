<?

# Modified to support SEO URL's and multi-depth cats 3/11/05

 class IX_CategoryTree {
   var $root_category_id = 0,
       $max_level = 0,
       $data = array(),
       $root_start_string = '',
       $root_end_string = '',
       $parent_start_string = '<br>',
       $parent_end_string = '',
       $parent_group_start_string = '',
       $parent_group_end_string = '<br>',
       $child_start_string = '<br>',
       $child_end_string = '',
       $spacer_string = '',
       $spacer_multiplier = 1;

   function IX_CategoryTree($load_from_database = true) {
     global $languages_id;
         $categories_query = tep_db_query("select c.categories_id, cd.categories_name, c.parent_id from
		 " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id =
		 cd.categories_id and cd.language_id = '" . (int)$languages_id . "' AND c.categories_status=1 order by c.parent_id,
		 c.sort_order, cd.categories_name");
		 
         $this->data = array();
         while ($categories = tep_db_fetch_array($categories_query)) {
			// Ultimate SEO URLs compatibility - Chemo
            # initialize array container for parent_id 
			$p = array();
			tep_get_parent_categories($p, $categories['parent_id']);
			# For some reason it seems to return in reverse order so reverse the array 
			$p = array_reverse($p);
			# Implode the array to get the parent category path
			$cID = (implode('_', $p) ? implode('_', $p) . '_' . $categories['parent_id'] :
			$categories['parent_id']);
            # initialize array container for category_id 
			$c = array();
			tep_get_parent_categories($c, $categories['categories_id']);
			# For some reason it seems to return in reverse order so reverse the array 
			$c = array_reverse($c);
			# Implode the array to get the full category path
			$id = (implode('_', $c) ? implode('_', $c) . '_' . $categories['categories_id'] :
			$categories['categories_id']);

           $this->data[$cID][$id] = array('name' => $categories['categories_name'], 'count' => 0);
         } // eof While loop
    } //eof Function

   function buildBranch($parent_id, $level = 0) {
     $result = $this->parent_group_start_string;

     if (isset($this->data[$parent_id])) {
       foreach ($this->data[$parent_id] as $category_id => $category) {
         $category_link = $category_id;
         $result .= $this->child_start_string;
         if (isset($this->data[$category_id])) $result .= $this->parent_start_string;

         if ($level == 0) $result .= $this->root_start_string;
		 
         $result .= str_repeat($this->spacer_string, $this->spacer_multiplier * $level) . 
		 '<a href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $category_link) . '" class="sitemap_cat">';
         $result .= $category['name'];
         $result .= '</a>';

         if ($level == 0) $result .= $this->root_end_string;

         if (isset($this->data[$category_id])) $result .= $this->parent_end_string;

         $result .= $this->child_end_string;

         if (isset($this->data[$category_id]) && (($this->max_level == '0') ||
		 ($this->max_level > $level+1))) $result .= $this->buildBranch($category_id, $level+2);
       }
     }

     $result .= $this->parent_group_end_string;
     return $result;
   }

   function buildTree() {
     return $this->buildBranch($this->root_category_id);
   }
 }
?>