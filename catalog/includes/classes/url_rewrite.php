<?php

  class url_rewrite {
    
    var $cache = array();
  
    // # Prepares URL characters
    function prepare_url($url) {

      // # Convert special characters from European countries into the English alphabetic equivalent

      $url = strtr($url, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy');

      // # Remove all non alphanumeric and numeric characters except forward slahes, periods and underscores
      $url = str_replace(' ', '-', preg_replace('/[^[:space:]a-zA-Z0-9\/_.-]/', '', $url));

	  // # Remove forward slahes and replace with hyphen 
      while (strstr($url, '/')) {
		$url = str_replace('/', '-', $url);

        // # remove leading hyphen
        $url = ltrim($url, '-');
	  }

      while (strstr($url, '--')) {
		//$url = str_replace('--', '-', $url);
		$url = preg_replace('/-{2,}/','-',$url);
	  }

	  if(empty($url) || $url == '.html') return false;

      return $url;
    }

    // # Select which pages to use SEO URLs on
    function pages($page) {
      $page_array = array(FILENAME_DEFAULT, FILENAME_PRODUCT_INFO, FILENAME_INFORMATION);
      return in_array($page, $page_array);
    }

    // # Transform the URLs   
	function transform_url($url) {

		global $languages_id, $numPages;
		// # Split the URL parts into an array

		$url_parts = parse_url($url);

		// # Exit if the URL is not specified in the pages function
		if (strpos($url, 'action') !== false || (!$this->pages(current($url_array = explode('/', trim(ltrim($url_parts['path'], DIR_WS_HTTP_CATALOG), '/')))))) return $url;
        
		if (tep_not_null($url_parts['path'])) {

			if (isset($this->cache[$url_parts['path']])) return $url_parts['scheme'] . '://' . $url_parts['host'] . $this->cache[$url_parts['path']];

			$url_query = tep_db_query("SELECT url_new FROM url_rewrite WHERE url_original = '" . tep_db_prepare_input($url_parts['path']) . "'");

			if (tep_db_num_rows($url_query) > 0) {
				$url_result = tep_db_fetch_array($url_query);
				tep_db_free_result($url_query);

				// # no trailing '/'
				return $url_parts['scheme'] . '://' . $url_parts['host'] . ($this->cache[$url_parts['path']] = $url_result['url_new']);
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
					if(!$url_array[$i]) break;

				    $use_cPath = 1;
	
					//$category_array = (strpos($url_array[$i], '_') !== false ? explode('_', $url_array[$i]) : $url_array[$i]);

					if (strpos($url_array[$i], '_') !== false) {

						$category_array = explode('_', $url_array[$i]);
	
						$weight = sizeof($category_array);

						if (!$item_id) $item_id=sprintf('c%d',$category_array[$weight-1]);
		

						foreach ($category_array as $categories_id) {

							if ($categories_id) {

								$category_name_query = tep_db_query("SELECT c.categories_id, c.parent_id, cd.categories_name 
																	 FROM " . TABLE_CATEGORIES . " c
																	 LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "')
																	 WHERE c.categories_id = '" . (int)$categories_id . "' 
																	");
	
								$category = tep_db_fetch_array($category_name_query);

								$category_name = $category['categories_name'];

							} else { 
								$category_name = '';
							}

							 $url_parts['path'] .= '/' . (!empty($category_name) ? $this->prepare_url($category_name) :'');

						}

					} else {

						$weight=1;

						if (!$item_id) $item_id=sprintf('c%d',$url_array[$i]);

						$category_query = tep_db_query("SELECT c.categories_id, 
															   c.parent_id, 
															   cd.categories_name 
														FROM " . TABLE_CATEGORIES . " c
														LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "')
														WHERE c.categories_id = '" . (int)$url_array[$i] . "'
														");
	
						$category = tep_db_fetch_array($category_query);
						tep_db_free_result($category_query);

						$parent_id = $category['parent_id'];

						$url_cat = array($category['categories_name']);

						$max_depth = 10;
	
						$cur_depth = 0;

						while ($parent_id > 0) {

							$cat_query = tep_db_query("SELECT c.categories_id, 
															  c.parent_id, 
														  cd.categories_name 
													   FROM " . TABLE_CATEGORIES . " c
													   LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "')
													   WHERE c.categories_id = '" . (int)$parent_id . "'
													 ");

							$category = tep_db_fetch_array($cat_query);
							tep_db_free_result($cat_query);
	
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

					$prod_query = tep_db_query("SELECT p.products_id,
													   p.master_products_id,
													   p.products_model,
													   pd.products_name 
												FROM ". TABLE_PRODUCTS ." p 
												LEFT JOIN ". TABLE_PRODUCTS_DESCRIPTION ." pd ON (pd.products_id = p.master_products_id AND pd.language_id = '" . (int)$languages_id . "')
												WHERE p.products_id = '" . (int)$url_array[$i] . "'
												");

					$prod_row = tep_db_fetch_array($prod_query);
					tep_db_free_result($prod_query);

					$item_id = sprintf('p%d',$url_array[$i]);

					if (!$use_cPath) {
				
						$prodID = explode("{", $url_array[$i], 2);
						//$url_parts['path'] .= '/P' . $prodID[0];
              
						$cat_array = array();
						$cat_query = tep_db_query("SELECT cd.categories_name as name, c.parent_id 
												   FROM " . TABLE_CATEGORIES . " c
												   LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (cd.categories_id = c.categories_id AND cd.language_id = '" . (int)$languages_id . "')
												   LEFT JOIN products_to_categories p2c ON p2c.categories_id = c.categories_id
												   WHERE p2c.products_id = '" . (int)$prod_row['master_products_id'] . "' 
												  ");

						$cat_val = tep_db_fetch_array($cat_query);

						$cat_array[] = $cat_val['name'];
		
						// # If this category has a parent, get the name
						if(is_numeric($cat_val['parent_id']) && $cat_val['parent_id'] != '0') {

            				$parent_id = $cat_val['parent_id'];
								
							while ($parent_id != '0') {

								$cat_query = tep_db_query("SELECT cd.categories_name AS name, 
																  c.parent_id 
														   FROM " . TABLE_CATEGORIES . " c 
														   LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (c.categories_id = cd.categories_id AND cd.language_id = '" . (int)$languages_id . "') 
														   WHERE cd.categories_id = '" . $parent_id . "'
														  ");

								$cat_val = tep_db_fetch_array($cat_query);
	
								$cat_array[] = $cat_val['name'];
								$parent_id = $cat_val['parent_id'];
							}
						}
							
						tep_db_free_result($cat_query);

						// # Reverse array order
						$cat_array = array_reverse($cat_array);
              
						for ($x = 0; $x <= sizeof($cat_array); $x++) {
							$url_parts['path'] .= '/' . $this->prepare_url($cat_array[$x]);
						}
					}
            
					$url_parts['path'] .= (substr($url_parts['path'], -1) == '/' ? '' : '/') . $this->prepare_url($prod_row['products_name']);
	
			    	if($prod_row['products_id'] != $prod_row['master_products_id']) {

						$url_parts['path'] .= '/'.$this->prepare_url($prod_row['products_model']);

					}

			break;

          case 'info_id':
            $i++;
		    $item_id=sprintf('i%d',$url_array[$i]);
            $info_query = tep_db_query("SELECT info_title FROM ".TABLE_INFORMATION." WHERE information_id = '" . $url_array[$i] . "'");
            $info_result = tep_db_fetch_array($info_query);
            $url_parts['path'] = str_replace(FILENAME_INFORMATION, '', $url_parts['path']);
            $url_parts['path'] .= '/' . $this->prepare_url($info_result['info_title']);
            break;

          case 'manufacturers_id':
            $i++;
		    $item_id = sprintf('m%d',$url_array[$i]);
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
		// # Added logic to stop recording bad URLs and/or DOS attempts.

		$record_url = true;
		$bad_url = array("/R/","/ad/","/ref/","utm","/utm_source/","/source/","/subscribers/", "/ap/","Barix","bcsi_scan","cachebuster","cols", "DealName","fb_locale","email"."fullsite","ixsid","amazon","template","pagespeed","---", "busted", "/sa/", "/X/","/5d", "/6d","/6a");

		$hacks = array("BENCHMARK","database","information_schema","sElEcT","cOnCaT","lImIt");
		

		if (strlen($url_parts['path']) > 0) {

			$url_parts['path'] = preg_replace('|^/*|','/',$url_parts['path']).'.html';

			// # Loop through bad_url array and match against url_parts
			foreach ($bad_url as $burl) {
				if (stripos($url_parts['path'], $burl) !== false) {	
					$record_url = false;
					//error_log($burl . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' - ' .$_SERVER['REMOTE_ADDR']);
				}
			}
			// # END bad_url check.

			// # Loop through hacks array and match against url_parts
			foreach ($hacks as $hack) {
				if (stripos($url_parts['path'], $hack) !== false) {	
					$record_url = false;
					error_log('HACK ATTEMPT! - ' . $hack . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ' - ' .$_SERVER['REMOTE_ADDR']);
				}
			}
			// # END hack check.


			while (1) {

				$item_query = tep_db_query("SELECT item_id FROM ". TABLE_URL_REWRITE_MAP ." WHERE url_new = '" . tep_db_prepare_input($url_parts['path']) . "'");
				$item_row = tep_db_fetch_array($item_query);
			
        		if (tep_db_num_rows($item_query) == 0 && $record_url) {

					if(!empty($item_id)) { 

						tep_db_query("INSERT IGNORE INTO ". TABLE_URL_REWRITE_MAP ." 
										SET url_new = '" . tep_db_prepare_input($url_parts['path']) . "',
										item_id = '". tep_db_prepare_input($item_id) ."',
										date_entered = NOW()
										");
					}

					break;

				}

				tep_db_free_result($item_query);

				if($item_row['item_id'] == $item_id) break;
//error_log(print_r($url_parts['path'],1));
				$url_parts['path'] = preg_replace('/\.html$/','-.html',$url_parts['path']);
				
				$url_parts['path'] = preg_replace('/-{2,}/','-',$url_parts['path']);

				// # scrub the 0_ from the cPath to avoid saving bad url_original
				$url_parts['path'] = (strpos($url_parts['path'], '/cPath/0_') !== false ? str_replace('/0_','/',$url_parts['path']) : $url_parts['path']);

    		} // # end while


			// # new sanity check for url recording


			// # detect valid catagory
            if (strpos($url_path, '/cPath/') !== false) { 

				$catagory_id = substr($url_path, strpos($url_path, '/cPath/') + 7);			
	
				$category_array = (strpos($catagory_id, '_') !== false ? explode('_', $catagory_id) : $catagory_id);

				if(is_array($category_array)) { 

					$parent_id = (int)$category_array[0];
					$catagory_id = (int)$category_array[1];

				} else {

					$parent_id = '0';
					$catagory_id = (int)$catagory_id;
				}


				$cat_check_query = tep_db_query("SELECT c.parent_id FROM ".TABLE_CATEGORIES." c WHERE c.parent_id = '".$parent_id."' AND c.categories_id = '".$catagory_id."'");

				if(tep_db_num_rows($cat_check_query) == 0 || (strpos($url_path, '/product_info.php/cPath/') !== false) || (strpos($url_path, '/cPath/0_') !== false)) { 

					$record_url = false;

				} 


			}	// # END detect valid catagory

			// # detect valid product
            if (strpos($url_path, '/products_id/') !== false) { 

				$products_id = substr($url_path, strpos($url_path, '/products_id/') + 13);

				$prod_check_query = tep_db_query("SELECT p.products_id FROM ".TABLE_PRODUCTS ." p WHERE p.products_id = '".(int)$products_id."'");

				if(tep_db_num_rows($prod_check_query) == 0 || (strpos($url_path, '/cPath/') !== false) || (strpos($url_path, '/index.php/products_id/') !== false)) { 

					$record_url = false;

				}

/* // # commented out - doesnt seem to work as expected - return to this later.
				// # products clean up

				if($products_id > 0) {
	
					tep_db_query("DELETE u.* 
								  FROM ". TABLE_URL_REWRITE ." u
								  LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_id = '".(int)$products_id."'
								  WHERE u.url_original LIKE '%/products_id/".(int)$products_id."' 
								  AND p.products_status = 0
								");
			
					tep_db_query("DELETE um.* 
								  FROM ". TABLE_URL_REWRITE_MAP ." um 
								  LEFT JOIN ". TABLE_PRODUCTS ." p ON p.products_id = '".(int)$products_id."'
								  AND p.products_status = 0
								 ");
				}
//if($_SERVER['REMOTE_ADDR'] == '104.162.19.65') error_log(print_r($GLOBALS['HTTP_GET_VARS']['page'] . ' - ' . $numPages,1));
*/
			}	// # END detect valid product


			// # detect valid manufacturer
            if (strpos($url_path, '/mfr_id/') !== false) { 

				$manufacturers_id = substr($url_path, strpos($url_path, '/mfr_id/') + 8);	

				$manuf_check_query = tep_db_query("SELECT m.manufacturers_id FROM ". TABLE_MANUFACTURERS ." m WHERE m.manufacturers_id = '".(int)$manufacturers_id."'");

				if(tep_db_num_rows($manuf_check_query) == 0) { 

					$record_url = false;

				}

			}

            if (strpos($url_path, '/manufacturers_id/') !== false) { 

				$manufacturers_id = substr($url_path, strpos($url_path, '/manufacturers_id/') + 18);	

				$manuf_check_query = tep_db_query("SELECT m.manufacturers_id FROM ". TABLE_MANUFACTURERS ." m WHERE m.manufacturers_id = '".(int)$manufacturers_id."'");

				if(tep_db_num_rows($manuf_check_query) == 0) { 

					$record_url = false;

				}

			}	// # END detect valid manufacturer


			// # detect valid information page

            if (strpos($url_path, '/info_id/') !== false) { 

				$information_id = substr($url_path, strpos($url_path, '/info_id/') + 9);	

				$info_check_query = tep_db_query("SELECT i.information_id FROM ". TABLE_INFORMATION ." i WHERE i.information_id = '".(int)$information_id."'");

				if(tep_db_num_rows($info_check_query) == 0) { 

					$record_url = false;

				}

			}	// # END detect valid information page


			// # detect proper page count
            if (strpos($url_path, '/page/') !== false) { 

				$thepage = (int)str_replace('.html', '',substr($url_path, strpos($url_path, '/page/') + 6));

				if((int)$GLOBALS['HTTP_GET_VARS']['page'] > (int)$numPages) { 

					$record_url = false;

				} else if((int)$numPages == 1 && strpos($url_path, '/page/0/') !== false) {

					$record_url = false;

				} else if($thepage > (int)$numPages) { 

					$record_url = false;
				}
			}

			// # END sanity check for url recording

			if ($record_url) {

				$url_query = tep_db_query("INSERT IGNORE INTO ". TABLE_URL_REWRITE ." 
									SET url_new = '" . tep_db_prepare_input($url_parts['path']) . "',
									url_original = '" . tep_db_prepare_input($url_path) . "',
									weight = '".$weight."',
									date_entered = NOW()
									");
			}
		}

		// # Return the converted URL
		// # no trailing '/'
		return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
	}


	function request_url() {
		global $HTTP_GET_VARS;

		// # Cache error messsage
		//if (USE_CACHE <> true)
        //exit ('Use cache needs to be set to \'True\' in the Admin and the cache directory needs to be writable to use SEO URL\'s');

		// # Search Engine Friendly URLs error messsage
		if (SEARCH_ENGINE_FRIENDLY_URLS <> true) exit ('Search Engine Friendly URL\'s needs to be set to \'True\' in the Admin to use SEO URL\'s');

		// # Force cookie error message
		if (SESSION_FORCE_COOKIE_USE <> true) exit ('Force cookie use needs to be set to \'True\' in the Admin and cookie domain needs to be setup in \'includes/configure.php\' to use SEO URL\'s');

		// # Exit if not being called from the SEO pages or contains 'action'
		if ((!$this->pages(basename($_SERVER['PHP_SELF']))) || ($_SERVER['REQUEST_URI'] == '/') || (strpos($_SERVER['REQUEST_URI'], '.php'))) return;

		$eURL = parse_url($_SERVER['REQUEST_URI']);

		$url_path = (substr($eURL['path'], -1) == '/' ? substr($eURL['path'], 0, -1) : $eURL['path']);
		
		// # adding weight
		
		$url_query = tep_db_query("SELECT url_original FROM ". TABLE_URL_REWRITE ." WHERE url_new = '" . tep_db_prepare_input($url_path) . "' ORDER BY weight DESC LIMIT 1");

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
				case 'mfr_id':
				case 'manufacturers_id':
					$i++;
					$HTTP_GET_VARS[$request_url_array[($i-1)]] .= $request_url_array[$i];
				break;
			}
		}
	}
    
    
	function purge_item($item_id,$recurse=0) {

		$mp_query = tep_db_query("SELECT * FROM ". TABLE_URL_REWRITE_MAP ." WHERE item_id='$item_id'");

		while ($mp_row=tep_db_fetch_array($mp_query)) {
    
		    tep_db_query("DELETE FROM ". TABLE_URL_REWRITE ." WHERE url_new='".tep_db_input($mp_row['url_new'])."'");

			if ($recurse) {
				$path_like = preg_replace('/\..*/','%',$mp_row['url_new']);

				tep_db_query("DELETE FROM ". TABLE_URL_REWRITE ." WHERE url_new LIKE '".tep_db_input($path_like)."'");
				tep_db_query("DELETE FROM ". TABLE_URL_REWRITE_MAP ." WHERE url_new LIKE '".tep_db_input($path_like)."'");
			}
		}

		tep_db_query("DELETE FROM ". TABLE_URL_REWRITE_MAP ." WHERE item_id='$item_id'");
	}

	function purge_all() {
      tep_db_query("DELETE FROM ". TABLE_URL_REWRITE);
      tep_db_query("DELETE FROM ". TABLE_URL_REWRITE_MAP);
    }
    
  }
?>
