<?php

  class url_rewrite {
    // # Prepares URL characters

	function __construct() {

	}

   function prepare_url($url) {

      // # Convert special characters from European countries into the English alphabetic equivalent

      $url = strtr($url, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy');

      // # Remove all non alphanumeric and numeric characters except forward slahes (can be used for pipe widths)
      $url = str_replace(' ', '-', preg_replace('/[^[:space:]a-zA-Z0-9\/_-]/', '', $url));

	  // # Remove forward slahes and replace with hyphen (I doubled up with escaped slashes and without)
      while (strstr($url, '\/')) {
		$url = str_replace('\/', '-', $url);
	  }
      while (strstr($url, '/')) {
		$url = str_replace('/', '-', $url);
	  }

	   // # Remove double hyphens and replace with single hypen
      while (strstr($url, '--')) {
		$url = str_replace('--', '-', $url);
	  }

      return $url;
    }

    // # Select which pages to use SEO URLs on
    function pages($page) {

      $page_array = array(FILENAME_DEFAULT, FILENAME_PRODUCT_INFO, FILENAME_INFORMATION);
      return in_array($page, $page_array);

    }

    // # Transform the URLs   
	function transform_url($url) {

      global $languages_id;
      // Split the URL parts into an array
      $url_parts = parse_url($url);
      
      
      // # Exit if the URL is not specified in the pages function
      if (strpos($url, 'action') !== false || (!$this->pages(current($url_array = explode('/', trim(ltrim($url_parts['path'], DIR_WS_HTTP_CATALOG), '/'))))))
        return $url;
        
      if (tep_not_null($url_parts['path'])) {

        $url_query = tep_db_query("SELECT url_new FROM url_rewrite WHERE url_original = '" . tep_db_prepare_input($url_parts['path']) . "'");

        if (tep_db_num_rows($url_query) > 0) {

          $url_result = tep_db_fetch_array($url_query);
		  // # - no trailing '/'
          return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_result['url_new'];
        }

      }

      $url_path = $url_parts['path'];

      // # Shift the page name on the URL array
      $page_name = array_shift ($url_array);

      // # Empty the path for stores with there error reporting set to ALL
      $url_parts['path'] = rtrim(DIR_WS_HTTP_CATALOG, '/');
      

		// # Start the transformation

      $weight = 0;
		$item_id = NULL;
		$use_cPath = 0;

		for ($i = 0; $i < sizeof($url_array); $i++) {

			switch ($url_array[$i]) {

				case 'cPath':

					$i++;
					if($url_array[$i]=='') break;
					$use_cPath=1;

					if (strpos($url_array[$i], '_') !== false) {

						$category_array = explode('_', $url_array[$i]);
						$weight = sizeof($category_array);

						if (!$item_id) $item_id = sprintf('c%d',$category_array[$weight-1]);

						foreach ($category_array as $categories_id) {

							$category_query = tep_db_query("SELECT c.categories_id, cd.categories_name 
												FROM " . TABLE_CATEGORIES . " c
												LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "')
												WHERE c.categories_id = '" . (int)$categories_id . "'
												");

							$category_name = tep_db_fetch_array($category_query);

							$url_parts['path'] .= '/' . $this->prepare_url($category_name['categories_name']);
						}

					} else {

						$weight = 1;

						if(!$item_id) $item_id=sprintf('c%d',$url_array[$i]);
						
						$category_query = tep_db_query("SELECT c.categories_id, c.parent_id, cd.categories_name FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = '" . (int)$url_array[$i] . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "'");

						$category = tep_db_fetch_array($category_query);
						$parent_id = $category['parent_id'];
						$url_cat = array($category['categories_name']);
						$max_depth = 10;
						$cur_depth = 0;

						while ($parent_id > 0) {

							$category_query = tep_db_query("SELECT c.categories_id, c.parent_id, cd.categories_name FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = '" . (int)$parent_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "'");

							$category = tep_db_fetch_array($category_query);

							$parent_id = $category['parent_id'];
							$url_cat[] = $category['categories_name'];
							
							$cur_depth++;
							if ($cur_depth >= $max_depth) break;
						}

						$url_cat = array_reverse($url_cat);
						foreach ($url_cat as $val) {
							$url_parts['path'] .= '/' . $this->prepare_url($val);
						}
					}

				break;

				case 'products_id':

					$i++;

					$item_id = sprintf('p%d',$url_array[$i]);
					if (!$use_cPath) {
              $prodID = explode("{", $url_array[$i], 2);
              //$url_parts['path'] .= '/P' . $prodID[0];
              $cat_array = array();
              $cat_query = tep_db_query("SELECT cd.categories_name as name, c.parent_id FROM categories c LEFT JOIN categories_description cd ON (c.categories_id = cd.categories_id) LEFT JOIN products_to_categories p2c ON (p2c.categories_id = c.categories_id) WHERE p2c.products_id = '" . (int)$url_array[$i] . "' AND p2c.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "'");
              $cat_val = tep_db_fetch_array($cat_query);
              
              $cat_array[] = $cat_val['name'];
              
              //If this category has a parent, get the name
              if (is_numeric($cat_val['parent_id']) && $cat_val['parent_id'] != '0') {
                $parent_id = $cat_val['parent_id'];
                while ($parent_id != '0') {
                  $cat_query = tep_db_query("SELECT cd.categories_name as name, c.parent_id FROM categories c LEFT JOIN categories_description cd ON (c.categories_id = cd.categories_id) WHERE cd.categories_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$languages_id . "'");
                  $cat_val = tep_db_fetch_array($cat_query);
                  
                  $cat_array[] = $cat_val['name'];
                  $parent_id = $cat_val['parent_id'];
                }
              }
              
              //Reverse array order
              $cat_array = array_reverse($cat_array);
              
              for ($x = 0; $x <= sizeof($cat_array); $x++) {
                $url_parts['path'] .= '/' . $this->prepare_url($cat_array[$x]);
              }
            }
            
            $product_query = tep_db_query("select distinct products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$url_array[$i] . "' and language_id = '" . (int)$languages_id . "'");
            $product_name = tep_db_fetch_array($product_query);
            $url_parts['path'] .= (substr($url_parts['path'], -1) == '/' ? '' : '/') . $this->prepare_url($product_name['products_name']);
            break;
          case 'info_id':
            $i++;
	    $item_id=sprintf('i%d',$url_array[$i]);
            $info_query = tep_db_query("SELECT info_title FROM " . TABLE_INFORMATION . " WHERE information_id = '" . $url_array[$i] . "'");
            $info_result = tep_db_fetch_array($info_query);
            $url_parts['path'] = str_replace(FILENAME_INFORMATION, '', $url_parts['path']);
            $url_parts['path'] .= '/' . $this->prepare_url($info_result['info_title']);

            break;

          case 'manufacturers_id':
            $i++;
	    $item_id=sprintf('m%d',$url_array[$i]);
            $manufacturer_query = tep_db_query("select distinct manufacturers_name from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$url_array[$i] . "'");
            $manufacturer_name = tep_db_fetch_array($manufacturer_query);
            $url_parts['path'] .= '/' . $this->prepare_url($manufacturer_name['manufacturers_name']);
            break;

          default:
            $url_parts['path'] .= '/' . $url_array[$i];
            break;
        }
      } 
      

		// # Record the URL
		if (strlen($url_parts['path']) > 2) {
	
			// # - add .html
			$url_parts['path'] .= '.html';
	
			while (1) {
			
				$url_query = tep_db_query("SELECT item_id FROM url_rewrite_map WHERE url_new = '" . tep_db_input($url_parts['path']) . "'");

				$item_row = tep_db_fetch_array($url_query);

				if (!$item_row) {

					tep_db_query("INSERT INTO url_rewrite_map (url_new,item_id) VALUES ('" . tep_db_input($url_parts['path']) . "',".($item_id==NULL?"NULL":"'".tep_db_input($item_id)."'").")");
					break;
				}

				if ($item_row['item_id']==$item_id) break;

				$url_parts['path']=preg_replace('/\.html$/','-.html',$url_parts['path']);
			}
        
			$url_query = tep_db_query("INSERT INTO url_rewrite (url_new,url_original,weight) VALUES ('" . tep_db_input($url_parts['path']) . "', '" . tep_db_prepare_input($url_path) . "','$weight')");
		}

		// # Return the converted URL

		// # no trailing '/'
		return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
	}

	function request_url() {
    
		global $HTTP_GET_VARS;

		// # Search Engine Friendly URLs error messsage

		if (SEARCH_ENGINE_FRIENDLY_URLS <> true) exit('Search Engine Friendly URL\'s needs to be set to \'True\' in the Admin to use SEO URL\'s');
		
		// # Force cookie error message
		if (SESSION_FORCE_COOKIE_USE <> true) exit ('Force cookie use needs to be set to \'True\' in the Admin and cookie domain needs to be setup in \'includes/configure.php\' to use SEO URL\'s');

		// # Exit if not being called from the SEO pages or contains 'action'
		if ((!$this->pages(basename($_SERVER['PHP_SELF']))) || ($_SERVER['REQUEST_URI'] == '/') || (strpos($_SERVER['REQUEST_URI'], '.php'))) return;

		$eURL = parse_url($_SERVER['REQUEST_URI']);

		$url_path = (substr($eURL['path'], -1) == '/' ? substr($eURL['path'], 0, -1) : $eURL['path']);

		// # adding weight

		$url_query = tep_db_query("SELECT url_original FROM url_rewrite WHERE url_new = '" . tep_db_prepare_input($url_path) . "' ORDER BY weight DESC LIMIT 1");

		if (tep_db_num_rows($url_query) > 0) {
			$url_result = tep_db_fetch_array($url_query);
			$url_path_orig = $url_result['url_original'];
		} else {
        	return;
		}

      // # Put the request URL into an array

      $request_url_array = explode('/', trim(trim($url_path_orig, '/'), $this->extention));

      // # Get the cached URLs array
      for ($i = 0; $i < sizeof($request_url_array); $i++) {
        switch ($request_url_array[$i]) {
          case 'sort':
          case 'page':
          case 'language':
          case 'cPath':
          case 'products_id':
          case 'info_id':
            $i++;
            $HTTP_GET_VARS[$request_url_array[($i-1)]] .= $request_url_array[$i];
            break;
        }
      }
    }
    
	function purge_item($item_id, $recurse=0) {
		
		$mp_query = tep_db_query("SELECT * FROM url_rewrite_map WHERE item_id = '". $item_id ."'");


		while ($mp_row = tep_db_fetch_array($mp_query)) {

			tep_db_query("DELETE FROM url_rewrite WHERE url_new = '". $mp_row['url_new'] ."'");

			if ($recurse) {

				$path_like = preg_replace('/\..*/','%', $mp_row['url_new']);

				tep_db_query("DELETE FROM url_rewrite WHERE url_new LIKE '".tep_db_input($path_like)."'");
				tep_db_query("DELETE FROM url_rewrite_map WHERE url_new LIKE '".tep_db_input($path_like)."'");
			}
		}

		tep_db_query("DELETE FROM url_rewrite_map WHERE item_id = '". $item_id ."'");
	}



	function purge_all() {
		tep_db_query("DELETE FROM url_rewrite");
		tep_db_query("DELETE FROM url_rewrite_map");
	}
    
}
?>
