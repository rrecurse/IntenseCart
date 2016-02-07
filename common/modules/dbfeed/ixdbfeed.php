<?php
class ixdbfeed extends IXmodule {
	var $category_path_cache, $feed, $filename, $products, $category_separator, $cols_separator;

  function ixdbfeed () {
		$this->category_path_cache = array();
		$this->feed = array();
		$this->products = array();
		$this->feed_products = array();
		$this->category_separator = "->";
		$this->cols_separator = ",";
  }

  function getName() {
    return "IXfeed";
  }

  function productUpdated($pid) {
  	unlink ($this->filename);
  }
  
  function storeFeed() {
    if (file_exists($this->filename)) unlink($this->filename); //return false;
    $this->products = $this->_get_products_ids();
    if (empty($this->products)) return false;
	$this->loadProducts();
	$this->buildFeed();
	$this->saveFeed();
  }

	function loadProducts() {
		$this->products = $this->_get_products_ids();
    	$product_feed_result = tep_db_query ("
		SELECT p.products_id,
			   p.products_upc,
			   p.products_sku,
			   p.products_model,
			   p.products_image,
			   p.products_image_xl_1,
			   p.products_image_xl_2,
			   p.products_image_xl_3,
			   p.products_image_xl_4,
			   p.products_weight,
			   p.products_width,
			   p.products_height,
			   p.products_length,
			   pd.products_name,
			   pd.products_description,
			   pd.products_info,
			   pd.products_head_keywords_tag,
			   m.manufacturers_name,
			   pg.customers_group_price AS products_price,
			   p.products_free_shipping,
			   IF(p.products_status = 1 AND (pg.customers_group_price > 0 OR p.products_price > 0), 'y', 'n') as stock,
			   p2c.categories_id,
			   dbfpe.extra_value as shipping_rate,
			   count(DISTINCT subp.products_id) as total_sub
		FROM ".TABLE_PRODUCTS." p
        LEFT JOIN ".TABLE_PRODUCTS." subp ON (p.products_id = subp.master_products_id)
        LEFT JOIN ".TABLE_PRODUCTS_DESCRIPTION." pd ON (p.master_products_id = pd.products_id and pd.language_id = {$GLOBALS['languages_id']})
        LEFT JOIN ".TABLE_MANUFACTURERS." m ON (p.manufacturers_id = m.manufacturers_id)
        LEFT JOIN ".TABLE_PRODUCTS_TO_CATEGORIES." p2c ON (p.products_id = p2c.products_id)
        LEFT JOIN ".TABLE_DBFEED_PRODUCTS_EXTRA." dbfpe ON (p.products_id = dbfpe.products_id AND  dbfpe.dbfeed_class = '".get_class($this)."' AND dbfpe.extra_field = 'shipping_cost')
		LEFT JOIN ". TABLE_PRODUCTS_GROUPS ." pg ON (pg.products_id = p.products_id AND pg.customers_group_id = 0)
        WHERE p.products_status = 1
		AND (p.products_price > 0 OR pg.customers_group_price > 0)
		AND p.master_products_id IN(".implode(",", $this->products).")
            ".$add_where."
		GROUP BY p.products_id;
    ");
	while ($row = tep_db_fetch_array($product_feed_result)) {

		// # purge inactive rows from dbfeed_products_extra at Generate feed.
		// # for some reason table aliases were breaking the delete query (although they worked with a SELECT test)
		// # so used whole table names.
			tep_db_query("DELETE FROM dbfeed_products_extra
						  WHERE NOT EXISTS (
								SELECT 1
								FROM dbfeed_products
								WHERE dbfeed_products.products_id = dbfeed_products_extra.products_id
								)
					      AND dbfeed_products_extra.dbfeed_class = '".get_class($this)."'
						 ");
		

		if ($row['total_sub'] > 1) continue;
		if (!$row['shipping_cost']) $row['shipping_cost'] = $this->getConf('shipping');
		$this->feed_products[] = $row;
	}
  }

	function pushFeed() {
		$this->storeFeed();
		$file_size = filesize($this->filename);
		$fp = fopen($this->filename, 'ro');

		$ch = curl_init();

 		curl_setopt ($ch, CURLOPT_URL, $this->getConf('ftp_host') . $this->getConf('ftp_path').'/'.$fp);
 		curl_setopt ($ch, CURLOPT_USERPWD, $this->getConf('ftp_user') . ':' . $this->getConf('ftp_pass'));
 		curl_setopt ($ch, CURLOPT_INFILE, $fp);
 		curl_setopt ($ch, CURLOPT_INFILESIZE, $file_size);
 		curl_setopt ($ch, CURLOPT_HEADER, 0);
 		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 0);
 		curl_setopt ($ch, CURLOPT_UPLOAD, 1);

 		$result = curl_exec ($ch);

		if(curl_exec($ch) === false) {
    		echo '<div class="upload_error">Curl error: ' . curl_error($ch).'</div>';
		} else {
	    	echo '<div class="upload_success">Operation completed without any errors</div>';
		}

		curl_close ($ch);
		fclose($fp);
	}


  function buildFeed() {
  }

	function actionList() {
		return Array('generate'=>'Generate', 'push' => 'Push');
	}

	function actionPerform($ac) {
		$ac = strtolower($ac);
		if($ac == 'generate') return $this->storeFeed();
		if($ac == 'push') return $this->pushFeed();
	}
  
 	function isReady() {
		return true;
	}
  
	function listConf() {
		return array('merchant'=>array('title'=>'Merchant ID','desc'=>'','default'=>''),
					 'shipping'=>array('title'=>'Shipping Cost','desc'=>'Default shipping cost','default'=>'0'),
				     'ftp_host'=>array('title'=>'FTP Host','desc'=>'FTP Host for feed upload','default'=>''),
					 'ftp_path'=>array('title'=>'FTP Path','desc'=>'','default'=>''),
					 'ftp_user'=>array('title'=>'FTP User','desc'=>'','default'=>''),
					 'ftp_pass'=>array('title'=>'FTP Password','desc'=>'','default'=>''),
    				);
	}

	function _get_products_ids() {
		$products_result = tep_db_query("
		SELECT dbfp.products_id 
		FROM " . TABLE_DBFEED_PRODUCTS . " dbfp 
		WHERE dbfeed_class = '" . get_class($this) . "'
		");

		$products_ids = array();
		while ($row = tep_db_fetch_array($products_result)) {
			$products_ids[] = $row['products_id'];
		}
		return $products_ids;
	}

	function _get_attribute_values_list($products_id) {
	
		$lang = (isset($GLOBALS['languages_id'])) ? $GLOBALS['languages_id'] : '1';

    	$res = tep_db_query("SELECT po.products_options_name, pov.products_options_values_name
							 FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
							 INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON (pa.options_id = po.products_options_id AND po.language_id = {$lang})
							 INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov ON (pa.options_values_id = pov.products_options_values_id AND pov.language_id = {$lang})
							 WHERE pa.products_id = {$products_id}
							 AND pa.options_values_price > 0
							");

		$values = array();
		while ($row = tep_db_fetch_array($res)) {
			$values[$row['products_options_name']][] = $row['products_options_values_name'];
		}

		reset($values);

		while (list($id, $value) = each($values)) {
			$result[$id] = implode(",", array_unique($value));
		}
		return $result;
	}

	
	function _get_text($text, $length = 0) {
     
		if(is_array($text)) {
			$text = array_map('strip_tags', array_keys($text));
		} else {
			$text = strip_tags($text);
		}

		$text = preg_replace(array("/\n/is", "/\r/is", "/\"/is"), array("", "", "\"\""),$text);
	    $text = str_replace(array("›"), array(">"), $text);
    	$text = preg_replace('/[\\x80-\\xFF]/', ' ', $text);
     
    	if(!is_array($text) && ((strlen($text) > $length) && ($length > 0))) {
	    	$text = substr($text, 0, $length);
	    } elseif(is_array($text)) {
    		$text = array_map(function ($x) use ($length) {
    			if(is_array($x)){
    				return call_user_func_array('_get_text', array($x, $length));
    			}
     
    			if(strlen($x) > $length && $length > 0) {
    				return substr($x, 0, $length);
    			}
    		}, $text); // # END $text = array_map(function.....
     
    		$text = implode($text);
    	}
     
    	return '"' .$text. '"';
	}

	function _getCategoryPath($c_id, $previous_category_path = '') {

		if (isset($this->categories_path_cache[$c_id])) {
			return $this->categories_path_cache[$c_id];
		}

		$cr = tep_db_query("
		SELECT c.parent_id, cd.categories_name 
		FROM ".TABLE_CATEGORIES." c
		LEFT JOIN ".TABLE_CATEGORIES_DESCRIPTION." cd ON (c.categories_id = cd.categories_id and cd.language_id = {$GLOBALS['languages_id']})
		WHERE c.categories_id = '".$c_id."'
		");
	
		$c_row = tep_db_fetch_array($cr);

		$previous_category_path = ($previous_category_path) ? " {$this->category_separator} " . $previous_category_path : '';

		if($c_row['parent_id'] != 0) {
			$category_path = $this->_getCategoryPath($c_row['parent_id'], $c_row['categories_name'] . $previous_category_path);
		} else {
			$category_path = $c_row['categories_name'] . $previous_category_path;	
		}

		$this->categories_path_cache[$c_id] = $category_path;
		return $category_path;
	}


	function saveFeed() { 
	// # function contents found in module /usr/share/IXcore/common/modules/dbfeed/
	}


	function adminProductEdit($pid,$xflds) {
		$xflds['shipping_cost'] =  (!isset($xflds['shipping_cost'])) ? $xflds['shipping_cost'] : $this->getConf('shipping');
		echo 'Shipping Cost: '. tep_draw_input_field('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']);
	}
}
?>
