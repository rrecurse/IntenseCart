<?php
require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';

class dbfeed_shopzillacom extends IXdbfeed {
	var $category_path_cache, $feed, $filename, $products;

  function dbfeed_shopzillacom () {
        parent::ixdbfeed();
        $this->category_separator = ">";
        $this->cols_separator = "\t";
	$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'shopzilla.txt';
  }

  function getName() {
    return "Shopzilla";
  }


  function _get_text2($text = '', $length = 0) {
		$text = (is_array($text)) ? array_shift($text) : $text;
        $text = strip_tags($text);
    $text = preg_replace(
        array("/\n/is", "/\r/is"),
        array("", ""),
        $text
    );
    $text=str_replace(array("<9b>"),array(">"),$text);
    $text=preg_replace('/[\\x80-\\xFF]/',' ',$text);
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
		$product_shipping = '';
		if ($product_row["shipping_rate"] > 0) {
			$product_shipping .= (float)$product_row["shipping_rate"];
		}
		if ($product_row["products_free_shipping"] == 1) {
			$product_shipping .= (float)0;
		}
		
        $options = $this->_get_attribute_values_list($product_row["products_id"]);
        $options_string = '';
        if (!empty($options)) $options_string = implode(',', $options);

        if (!empty($options_string)) $options_string = " - " . $options_string;

$keywords = IXdb::read("SELECT products_head_keywords_tag FROM products_description WHERE products_id='".$product_row["products_id"]."'",NULL,'products_head_keywords_tag');

	if(!is_array($keywords)){ 
		$kw = explode(',', $keywords); 
	} else { 
		$kw = array(); 
	}

	$product_feed_row = array(

			"Unique ID"				=> 	$this->_get_text2($product_row["products_id"]),
			"Title"					=> 	$this->_get_text2($product_row["products_name"] . $options_string),
			"Description"			=> 	$this->_get_text2($product_row["products_info"], 1024),
			"Category"				=>	$this->_get_text2(IXdb::read("SELECT extra_value FROM dbfeed_products_extra WHERE products_id='".$product_row["products_id"]."' AND dbfeed_class='".get_class($this)."' AND extra_field='category'",NULL,'extra_value')),
            "Product URL"			=> 	$this->_get_text2('http://'.$_SERVER['HTTP_HOST'].IXdb::read("SELECT url_new FROM url_rewrite_map WHERE item_id='p".$product_row["products_id"]."' ORDER BY url_new DESC LIMIT 1",NULL,'url_new')),
			"Image URL"				=> 	$this->_get_text2(HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"])),
			"Condition"				=> 	$this->_get_text2("New"),
			"Availability"			=> 	$this->_get_text2( ($product_row["stock"] == 'y') ? "In Stock" : "Out of Stock" ),
			"Current Price"			=> 	$this->_get_text2(number_format($product_row["products_price"], 2)),

			"Brand"					=> 	$this->_get_text2($product_row["manufacturers_name"]),
			"GTIN"					=>	$this->_get_text2((strlen($product_row["products_upc"] < 13) ? '0'.$product_row["products_upc"] : $product_row["products_upc"])),
			"MPN"					=>	$this->_get_text2($product_row["products_model"]),

			"Shipping Cost"			=> 	$this->_get_text2($product_shipping),
			"Ship Weight"		=> 	$this->_get_text2($product_row["products_weight"]),
			//"Bid"					=> 	$this->_get_text2(IXdb::read("SELECT extra_value FROM dbfeed_products_extra WHERE products_id='".$product_row["products_id"]."' AND dbfeed_class='".get_class($this)."' AND extra_field='bid'",NULL,'extra_value')),
			"Bid"					=> 	$this->_get_text2(''),
			"Promotional Text"		=>  $this->_get_text2(($product_row["products_free_shipping"] == 1 ? 'Free Shipping!' : ''))
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

	// # Close handle
	curl_close($ch);
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
	};
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
	'merchant'=>Array('title'=>'ShopZilla Merchant ID','desc'=>'','default'=>''),
	'shipping'=>Array('title'=>'Default Shipping Cost','desc'=>'Default shipping cost','default'=>'0'),
		//'bid'=>Array('title'=>'Default Bid','desc'=>'Default Category bid','default'=>'0')
    );
  }
  function adminProductEdit($pid,$xflds) {
    $cat_lkup=IXdb::read("SELECT DISTINCT extra_value FROM dbfeed_products_extra WHERE dbfeed_class='".get_class($this)."' AND extra_field='category'",Array(NULL),Array('id'=>'extra_value','text'=>'extra_value'));
	 if (!isset($xflds['shipping_cost'])) $xflds['shipping_cost'] = $this->getConf('shipping');
?>
<table border="0" cellspacing="2" cellpadding="0">
<tr><td>Shipping Cost:</td><td><?php echo tep_draw_input_field('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']);?></td></tr>
<!--tr><td>Category BID:</td><td><?php // echo tep_draw_input_field('dbfeed_extra['.get_class($this).'][bid]',$xflds['bid']);?>
</td></tr-->
<tr><td valign="top">ShopZilla Category ID:</td><td><table border="0" cellspacing="0" cellpadding="0"><tr><td>
<?php 
echo tep_draw_input_field('dbfeed_extra['.get_class($this).'][category]',$xflds['category'],' id="shopzilla_cat_fld"');
?>
</td>
<td><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b style=&quot;white-space:nowrap;&quot;>Shopzilla Category</b></font><br><br>The categorization field should be filled out based on the Shopzilla taxonomy (category structure), with each level of taxonomy separated by a \'>\' (e.g. Tools & Home Improvemen > Electrical).')" onMouseout="hideddrivetip()"> </div></td>
<td>[<a href="http://merchant.shopzilla.com/oa/general/taxonomy.xpml" target="_blank">ShopZilla</a>]</td>
</tr>
</table>

<?php
	if($cat_lkup){
 
		echo '<br>' .tep_draw_pull_down_menu('shopzillacom_cat_pickup',array_merge(Array(Array('id'=>'','text'=>'---Pick a Category---')),$cat_lkup),'','onChange="if (this.value) {$(\'shopzilla_cat_fld\').value=this.value; this.options[0].selected=true; }"');
	}
?>

</td></tr>
</table>
<?
  }

}
?>