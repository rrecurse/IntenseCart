<?php
require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';

class dbfeed_googlebase extends IXdbfeed {
	var $category_path_cache, $feed, $filename, $products;

  function dbfeed_googlebase () {
        parent::ixdbfeed();
        $this->category_separator = "/";
        $this->cols_separator = "\t";
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'googlebase.txt';
  }

  function getName() {
    return "Google Shopping Network";
  }


  function _get_text2($text, $length = 0) {
		$text = (is_array($text)) ? array_shift($text) : $text;
		$text = str_replace('›','>',$text);
        $text = strip_tags($text);
	    $text = preg_replace(array("/\n/is", "/\r/is"), array("", ""),  $text);

		$text = str_replace(array("<9b>"),array(">"),$text);

		$text = preg_replace('/[\\x80-\\xFF]/',' ',$text);

        if ((strlen($text) > $length) && ($length > 0)) {
                $text = substr($text, 0, $length);
        }

        return $text;
  }

	function buildFeed() {
		if (file_exists($this->filename)) {
			chmod($this->filename, 0777) or die ("can't chmod file");
			fopen($this->filename, 'wo') or die ("can't open file");
		} else {
			fopen($this->filename, 'wo') or die ("can't open file");
			chmod($this->filename, 0777) or die ("can't chmod the file2");
		}

		$this->feed = array();
    	reset($this->feed_products);

	    while (list(,$product_row) = each($this->feed_products)) {

			$product_shipping = '';

			if ($product_row["shipping_rate"] > 0) {
	
				$product_shipping .= $this->_get_text2('"US":::"'.$product_row['shipping_rate'].'"');
	
			} else if ($product_row["products_free_shipping"] == 1) {
	
				$product_shipping .= $this->_get_text2('"US":::"0"');
			}



    	    $options = $this->_get_attribute_values_list($product_row["products_id"]);
        	$options_string = '';

	        if (!empty($options)) {
    	        $options_string = implode(',', $options);
        	}

	        if (!empty($options_string)) $options_string = " - " . $options_string;


			$product_feed_row = array(
			"id"						=>	$this->_get_text2($product_row["products_id"]),
			"title"						=>	$this->_get_text2($product_row["products_name"] . $options_string),
			"description"				=>	$this->_get_text2($product_row["products_info"], 1024),			
			"google_product_category"	=>	$this->_get_text2(IXdb::read("SELECT extra_value FROM dbfeed_products_extra WHERE products_id='".$product_row["products_id"]."' AND dbfeed_class='".get_class($this)."' AND extra_field='category'",NULL,'extra_value')),
			"product_type"				=>	$this->_get_text2($this->_getCategoryPath($product_row["categories_id"])),
			"link"						=>	$this->_get_text2('http://'.$_SERVER['HTTP_HOST'].IXdb::read("SELECT url_new FROM url_rewrite_map WHERE item_id='p".$product_row["products_id"]."' ORDER BY url_new DESC LIMIT 1",NULL,'url_new')),
			"image link"				=>	$this->_get_text2(HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"])),
			"condition"                 =>	$this->_get_text2("new"),
			"availability"				=>	$this->_get_text2(($product_row["stock"] == 'y' ? 'in stock' : 'out of stock')),
			"price"                     =>	$this->_get_text2($product_row["products_price"]),
			"brand"						=>	$this->_get_text2($product_row["manufacturers_name"]),
			"mpn"						=>	$this->_get_text2($product_row["products_model"]),
			"gtin"						=>	$this->_get_text2((strlen($product_row["products_upc"] < 13) ? '0'.$product_row["products_upc"] : $product_row["products_upc"])),
			"tax"						=>	$this->_get_text2('"US"::"0":'),
			"shipping"					=>	$product_shipping,
			"shipping_weight"			=>	$this->_get_text2($product_row["products_weight"]),
			"online_only"				=>	$this->_get_text2("y"),
			);

			$this->feed[$product_row["products_id"]] = $product_feed_row;
		}
	}


	function pushFeed() {

		if (file_exists($this->filename)) {

			chmod($this->filename, 0777) or die ("can't chmod file");
			fopen($this->filename, 'wo') or die ("can't open file");
			unlink($this->filename) or die ("can't delete the file!");
		}

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
		curl_setopt ($ch, CURLOPT_FTP_USE_EPSV, 1);
		curl_setopt ($ch, CURLOPT_PORT, '21');

 		$result = curl_exec ($ch);

		if(curl_exec($ch) === false) {
		    echo '<div class="upload_error">Curl error: ' . curl_error($ch).'</div>';
		} else {
		    echo '<div class="upload_success">Operation completed without any errors</div>';
		}

		curl_close ($ch);
		fclose($fp);
	}


	function saveFeed() {
		reset($this->feed);
		$dbfeed_text = "";
		while (list($id, $row) = each($this->feed)) {
			if (!isset($header_row)) {
				$header_row = array_keys($row);
				while (list(,$header_title) = each($header_row)) {
					$new_header_row[] = $this->_get_text($header_title);
				}
	
				$dbfeed_text .= implode($this->cols_separator, $new_header_row) . "\r\n";
			}

			$dbfeed_text .= implode($this->cols_separator, $row) . "\r\n";
		}

		$fp = fopen($this->filename, "w+");
		if(!fputs($fp, $dbfeed_text)) { 
			echo 'Problem saving! Check file permissions';
		} else {
			echo '<b style="color:green">Successfully Generated:</b><span style="color:#333;"> '.$this->filename.'</span><br><br>';
		}
 		fclose($fp);
	}

	function actionList() {
		return Array('generate'=>'Generate');
	}


	function listConf() {
    	return array('shipping' => array('title'=>'Default Shipping Cost','desc'=>'Default shipping cost','default'=>'0'),
					 'ftp_host' => array('title'=>'Google FTP Host','desc'=>'HTTP Host for feed upload','default'=>''),
					 'ftp_user' => array('title'=>'Google FTP User','desc'=>'','default'=>''),
					 'ftp_pass' => array('title'=>'Google FTP Password','desc'=>'','default'=>''),
					);
	}


	function adminProductEdit($pid,$xflds) {
    
		$cat_lkup=IXdb::read("SELECT DISTINCT extra_value FROM dbfeed_products_extra WHERE dbfeed_class='".get_class($this)."' AND extra_field='category'", array(NULL),Array('id'=>'extra_value','text'=>'extra_value'));
		if(!isset($xflds['shipping_cost'])) $xflds['shipping_cost'] = $this->getConf('shipping');
?>
		<table border="0" cellspacing="2" cellpadding="0">
			<tr>
				<td>Shipping Cost:</td>
				<td><?php echo tep_draw_input_field('dbfeed_extra['. get_class($this) .'][shipping_cost]', $xflds['shipping_cost']);?></td>
			</tr>
			<tr>
				<td valign="top">Google Shopping Category:</td>
				<td>
					<table border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td><?php echo tep_draw_input_field('dbfeed_extra['.get_class($this).'][category]',$xflds['category'],'id="googlebase_cat_fld"');?></td>
							<td><div class="helpicon" onmouseover="ddrivetip('<font class=featuredpopName><b style=\'white-space:nowrap\'>Google Shopping Category</b></font><br><br>The categorization field should be filled out based on Google Shopping\'s Taxonomy (category structure), with each level of taxonomy separated by a \'>\' (e.g. Clothing > Accessories > Sunglasses). To determine where an item should be categorized, you visit the Google Shopping catagory tool and search for the product (or a similar product). When you find it on their site, look to the top left of the page. You\'ll see a \'breadcrumb\' or list of the categories that product falls into.')" onMouseout="hideddrivetip()"> </div></td>
							<td>[<a href="http://support.google.com/merchants/bin/answer.py?hl=en&answer=1705911" target="_blank">Google</a>]</td>
						</tr>
					</table>

<?php if($cat_lkup) { ?>

	<br>
	<?php echo tep_draw_pull_down_menu('googlebase_cat_pickup',array_merge(array(array('id'=>'','text'=>'---Pick a Category---')),$cat_lkup),'','onChange="if (this.value) {$(\'googlebase_cat_fld\').value=this.value; this.options[0].selected=true; }"'); ?>
<?php } ?>
</td></tr></table>
<?php
  }

}
?>