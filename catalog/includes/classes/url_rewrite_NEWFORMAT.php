<?php
  /* By Daniel Kerr, Created 06/07/05,  */

  class url_rewrite {

    // Prepares URL characters
    function prepare_url($url) {
      // Convert special characters from European countries into the English alphabetic equivalent
      $url = strtr($url, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy');
      // Remove all none alphabetic and numeric charaters 
      $url = str_replace(' ', '-', ereg_replace('[^[:space:]a-zA-Z0-9_-]', '', $url));

      // Remove double '-'
      while (strstr($url, '--')) $url = str_replace('--', '-', $url);
      return $url;
    }

    // Select which pages to use SEO URLs on
    function pages($page) {
      $page_array = array(FILENAME_DEFAULT, FILENAME_PRODUCT_INFO);
      return in_array($page, $page_array);
    }

    // Transform the URLs   
    function transform_url($url) {
      global $languages_id;

      // Split the URL parts into an array
      $url_parts = parse_url($url);

      // Exit if the URL is not specified in the pages function
      if ((strpos($url, 'action')) || (!$this->pages(current($url_array = explode('/', trim(ltrim($url_parts['path'], DIR_WS_HTTP_CATALOG), '/'))))))
        return $url;

      // Remove the page name from the URL array
      array_shift ($url_array);

      // Empty the path for stores with there error reporting set to ALL
      $url_parts['path'] = rtrim(DIR_WS_HTTP_CATALOG, '/');
      
      if (strpos($url, '&') !== false) die(print_r($url_parts));
      // Start the transformation
      for ($i = 0; $i < sizeof($url_array); $i++) {
        switch ($url_array[$i]) {
          case 'cPath':
            $i++;

            $category_array = explode('_', $url_array[$i]);
            if ($pos = array_search('products_id', $url_array)) {
              $prodID = explode("{", $url_array[$pos+1], 2);
              $url_parts['path'] .= '/P' . $prodID[0];
            } else {
              $url_parts['path'] .= '/C' . $url_array[$i];
            }
            foreach ($category_array as $categories_id) {
              $category_query = tep_db_query("select distinct c.categories_id, cd.categories_name from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = '" . (int)$categories_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "'");
              $category_name = tep_db_fetch_array($category_query);
              $url_parts['path'] .= '/' . $this->prepare_url($category_name['categories_name']);
            }

            break;

          case 'products_id':
            $i++;

            //ADD CATEGORY NAME MODIFICATION - BB 2005-11-08
            //If cPath isn't in the URL, category names aren't added to the URL
            //This fixes that

            if (strpos($url, 'cPath') === false) {
              $prodID = explode("{", $url_array[$i], 2);
              $url_parts['path'] .= '/P' . $prodID[0];
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
            //END MODIFICATION - BB
            
            $product_query = tep_db_query("select distinct products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$url_array[$i] . "' and language_id = '" . (int)$languages_id . "'");
            $product_name = tep_db_fetch_array($product_query);
            $url_parts['path'] .= (substr($url_parts['path'], -1) == '/' ? '' : '/') . $this->prepare_url($product_name['products_name']);
            break;

          case 'manufacturers_id':
            $i++;

            $manufacturer_query = tep_db_query("select distinct manufacturers_name from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$url_array[$i] . "'");
            $manufacturer_name = tep_db_fetch_array($manufacturer_query);
            $url_parts['path'] .= '/' . $this->prepare_url($manufacturer_name['manufacturers_name']);
            break;

          default:
            $url_parts['path'] .= '/' . $url_array[$i];

            break;
        }
      } 

      // Return the converted URL
      return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '/';
    }

    // Changes the names of URL's into intergers
    function encrypt_url($url) {
      return sprintf('%u', crc32($this->prepare_url(strtolower($url))));
    }

    function request_url() {
      global $HTTP_GET_VARS;

      // Cache error messsage
      if (USE_CACHE <> true)
        exit ('Use cache needs to be set to \'True\' in the Admin and the cache directory needs to be writable to use SEO URL\'s');

      // Search Engine Friendly URLs error messsage
      if (SEARCH_ENGINE_FRIENDLY_URLS <> true)
        exit ('Search Engine Friendly URL\'s needs to be set to \'True\' in the Admin to use SEO URL\'s');

      // Force cookie error message
      if (SESSION_FORCE_COOKIE_USE <> true)
        exit ('Force cookie use needs to be set to \'True\' in the Admin and cookie domain needs to be setup in \'includes/configure.php\' to use SEO URL\'s');

      // Exit if not being called from the SEO pages or contains 'action'
      if ((!$this->pages(basename($_SERVER['PHP_SELF']))) || ($_SERVER['REQUEST_URI'] == '/') || (strpos($_SERVER['REQUEST_URI'], '.php')))
        return;

      // Put the request URL into an array
      $request_url_array = explode('/', trim(trim($_SERVER['REQUEST_URI'], '/'), $this->extention));

      // Get the cached URLs array
      $url_array = $this->cache_url();
      for ($i = 0; $i < sizeof($request_url_array); $i++) {
        switch ($request_url_array[$i]) {
          case 'sort':
            $i++;

            $HTTP_GET_VARS['sort'] .= $request_url_array[$i];

            break;

          case 'page':
            $i++;

            $HTTP_GET_VARS['page'] .= $request_url_array[$i];

            break;

          case 'language':
            $i++;

            $HTTP_GET_VARS['language'] .= $request_url_array[$i];

            break;

          default:
            if ($url_array[$this->encrypt_url($request_url_array[$i])]['key'] == 'categories_id') {
              if (!isset($HTTP_GET_VARS['cPath'])) {
                $HTTP_GET_VARS['cPath'] = $url_array[$this->encrypt_url($request_url_array[$i])]['value'];
              } else {
                $HTTP_GET_VARS['cPath'] .= '_' . $url_array[$this->encrypt_url($request_url_array[$i])]['value'];
              }
            } else {
              $HTTP_GET_VARS[$url_array[$this->encrypt_url($request_url_array[$i])]['key']] .= $url_array[$this->encrypt_url($request_url_array[$i])]['value'];
            }
            break;
        }
      }
    }

    // Caches the URLs into a file called url.cache that should be in the cache directory
    function cache_url() {
      global $refresh;

      // Read cache is part of the built in OSC cache fucntion
      if (($refresh == true) || !read_cache($url_array, 'url.cache')) {
        // Adds the categories to the URL array
        $categories_query = tep_db_query("select categories_id, categories_name from " . TABLE_CATEGORIES_DESCRIPTION);

        while ($categories = tep_db_fetch_array($categories_query)) {
          $url_array[$this->encrypt_url($categories['categories_name'])] = array('key' => 'categories_id', 'value' => $categories['categories_id']);
        }

        // Adds the manufacturers to the URL array
        $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS);

        while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
          $url_array[$this->encrypt_url($manufacturers['manufacturers_name'])] = array('key' => 'manufacturers_id', 'value' => $manufacturers['manufacturers_id']);
        }

        // Adds the products to the URL array
        $products_query = tep_db_query("select products_id, products_name from " . TABLE_PRODUCTS_DESCRIPTION);

        while ($products = tep_db_fetch_array($products_query)) {
          $url_array[$this->encrypt_url($products['products_name'])] = array('key' => 'products_id', 'value' => $products['products_id']);
        }

        // Write cache is part of the built in OSC cache fucntion
        write_cache($url_array, 'url.cache');
      }
      return $url_array;
    }
  }
?>
