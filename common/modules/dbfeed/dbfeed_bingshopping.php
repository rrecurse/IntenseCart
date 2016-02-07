<?php 

require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';

class dbfeed_bingshopping extends IXdbfeed {
	var $category_path_cache, $feed, $filename, $products;

  function dbfeed_bingshopping () {
        parent::ixdbfeed();
        $this->category_separator = "/";
        $this->cols_separator = "\t";
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'bingshopping.txt';
  }

  function getName() {
    return "Bing Product Search";
  }


  function _get_text2($text, $length = 0) {
        $text = strip_tags($text);
	    $text = preg_replace( array("/\n/is", "/\r/is"), array("", ""),$text);
		$text = str_replace(array("<9b>"),array(">"),$text);
		$text = preg_replace('/[\\x80-\\xFF]/',' ',$text);

        if ((strlen($text) > $length) && ($length > 0)) {
                $text = substr($text, 0, $length);
        };
        return $text ;
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

		$product_shipping = 0;
		if ($product_row["shipping_rate"]) {
			$product_shipping = $product_row["shipping_rate"];
		};
		if ($product_row["products_free_shipping"] == 1) {
			$product_shipping = 0;
		};
        $options = $this->_get_attribute_values_list($product_row["products_id"]);
        $options_string = '';
        if (!empty($options)) {
            $options_string = implode(',', $options);
        }

        if (!empty($options_string)) $options_string = " - " . $options_string;

		$product_feed_row = array(
			
			"id"					=>	$this->_get_text2($product_row["products_id"]),
			"title"					=>	$this->_get_text2($product_row["products_name"] . $options_string),
			"brand"					=>	$this->_get_text2($product_row["manufacturers_name"]),
			"link"					=>	$this->_get_text2('http://'.$_SERVER['HTTP_HOST'].IXdb::read("SELECT url_new FROM url_rewrite_map WHERE item_id='p".$product_row["products_id"]."' ORDER BY url_new DESC LIMIT 1",NULL,'url_new')),
			"price"					=>	$this->_get_text2($product_row["products_price"]),
			"description"			=>	$this->_get_text2($product_row["products_info"], 1024),
			"image_link"			=>	$this->_get_text2(HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"])),
			"mpn"					=>	$this->_get_text2($product_row["products_model"]),
			"upc"					=>	$this->_get_text2($product_row["products_upc"]),
			"sku"					=>	$this->_get_text2($product_row["products_id"]),
			"gtin"					=>	$this->_get_text2((strlen($product_row["products_upc"] < 13) ? '0'.$product_row["products_upc"] : $product_row["products_upc"])),
			"availability"			=>	$this->_get_text2(($product_row["stock"] == 'y' ? 'In stock' : 'Out of Stock')),
			"condition"				=>	$this->_get_text2("New"),
			"product_type"			=>	$this->_get_text2($this->_getCategoryPath($product_row["categories_id"])),
			"b_category"			=>	$this->_get_text2(IXdb::read("SELECT extra_value FROM dbfeed_products_extra WHERE products_id='".$product_row["products_id"]."' AND dbfeed_class='".get_class($this)."' AND extra_field='category'",NULL,'extra_value')),
			"sale_price"			=>	$this->_get_text2(''),
			"sale_price_effective_date"	=>	$this->_get_text2(''),
			"bingads_grouping"		=>	$this->_get_text2(''),
			"bingads_label"			=>	$this->_get_text2(''),
			"bingads_redirect"		=>	$this->_get_text2(''),
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
			};
			$dbfeed_text .= implode($this->cols_separator, $new_header_row) . "\r\n";
		};
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

  function listConf() {
    return Array(
//	'merchant'=>Array('title'=>'Merchant ID','desc'=>'','default'=>''),
	'shipping'=>Array('title'=>'Default Shipping Cost','desc'=>'Default shipping cost','default'=>'0'),
	'ftp_host'=>Array('title'=>'Bing FTP Host','desc'=>'HTTP Host for feed upload','default'=>''),
//	'ftp_path'=>Array('title'=>'Bing FTP Path','desc'=>'','default'=>''),
	'ftp_user'=>Array('title'=>'Bing FTP User','desc'=>'','default'=>''),
	'ftp_pass'=>Array('title'=>'Bing FTP Password','desc'=>'','default'=>''),
    );
  }
  function adminProductEdit($pid,$xflds) {
    $cat_lkup = IXdb::read("SELECT DISTINCT extra_value FROM dbfeed_products_extra WHERE dbfeed_class='".get_class($this)."' AND extra_field='category'", array(NULL), array('id'=>'extra_value','text'=>'extra_value'));
	 if (!isset($xflds['shipping_cost'])) $xflds['shipping_cost'] = $this->getConf('shipping');
?>
<table border="0" cellspacing="2" cellpadding="0">
	<tr>
		<td>Shipping Cost:</td>
		<td><?php echo tep_draw_input_field('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']);?></td>
	</tr>
	<tr>
		<td valign="top">Bing Shopping Category:</td>
		<td>
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td><?php echo tep_draw_input_field('dbfeed_extra['.get_class($this).'][category]',$xflds['category'],'id="bingshopping_cat_fld"');?></td>
					<td><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b style=&quot;white-space:nowrap;&quot;>Bing Shopping Category</b></font><br><br>The categorization field should be filled out based on Bing\'s taxonomy (category structure), with each level of taxonomy separated by a \'>\' (e.g. Clothing > Accessories > Sunglasses). To determine where an item should be categorized, you visit the Bing Shopping catagory tool and search for the product (or a similar product). When you find it on their site, look to the top left of the page. You\'ll see a \'breadcrumb\' or list of the categories that product falls into.')" onMouseout="hideddrivetip()"> </div></td>
					<td>[<a href="http://www.bing.com" target="_blank">Bing</a>]</td>
				</tr>
			</table>
<?php
	if ($cat_lkup) {
		echo '<br>' . tep_draw_pull_down_menu('bingshopping_cat_pickup',array_merge(array(array('id'=>'','text'=>'---Pick a Category---')),$cat_lkup),'','onChange="if (this.value) {$(\'bingshopping_cat_fld\').value=this.value; this.options[0].selected=true; }"');
	}
?>

</td></tr>
</table>
<?
  }

}
?>
