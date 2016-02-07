<?
require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';
class dbfeed_shoppingcom extends IXdbfeed {
	var $category_path_cache, $feed, $filename, $products;

  function dbfeed_shoppingcom () {
        parent::ixdbfeed();
        $this->category_separator = "/";
        $this->cols_separator = ",";
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'shoppingcom.csv';
  }

  function getName() {
    return "Shopping.com";
  }

  function _get_text2($text = '', $length = 0) {
		$text = (is_array($text)) ? array_shift($text) : $text;
		$text = str_replace('›','>',$text);
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
        };
        if (!empty($options_string)) $options_string = " - " . $options_string;


$mylink_query = tep_db_query("SELECT url_new AS linkme FROM url_rewrite_map WHERE item_id ='p". $product_row["products_id"]."' LIMIT 1");
while ($mylink = tep_db_fetch_array($mylink_query)) {
        $newlink = $mylink['linkme'];
      }
$hostname = $_SERVER['SERVER_NAME'];
$hostname = str_replace('www.', '', $hostname);

$zip_query = tep_db_query("SELECT configuration_value AS zip FROM configuration WHERE configuration_key='MODULE_SHIPPING_UPSXML_RATES_POSTALCODE' LIMIT 1");
while ($myzip = tep_db_fetch_array($zip_query)) {
        $zip = $myzip['zip'];
      }
                $product_feed_row = array(
                        "MPN"                                   =>      $this->_get_text($product_row["products_model"]),
						"Unique Merchant SKU"					=>		$this->_get_text($product_row["products_id"]),
                        "Manufacturer Name"                     =>      $this->_get_text($product_row["manufacturers_name"]),
                        "UPC"                                   =>      $this->_get_text($product_row["products_upc"]),
                        "Product Name"                          =>      $this->_get_text($product_row["products_name"] . $options_string),
                        "Product Description"                   =>      $this->_get_text($product_row["products_info"], 1024),
                        "Product Price"                         =>      $this->_get_text(number_format($product_row["products_price"],2)),
                        "Product URL"                           =>      $this->_get_text("http://www.".$hostname.$newlink),
                        "Image URL"                             =>      $this->_get_text(HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"])),
                        "Shopping.com Categorization"           =>      $this->_get_text(IXdb::read("SELECT extra_value FROM dbfeed_products_extra WHERE products_id='".$product_row["products_id"]."' AND dbfeed_class='".get_class($this)."' AND extra_field='category'",NULL,'extra_value')),
                        "Stock Availability"                    =>      $this->_get_text(($product_row["stock"] == 'y' ? 'Y' : 'N')),
                        "Condition"                   			=>      $this->_get_text("New"),
                        "Ground Shipping"                       =>      $this->_get_text($product_shipping),
                        "Weight"                                =>      $this->_get_text($product_row["products_weight"]),
                        "Zip Code"                              =>      $this->_get_text($zip)
		);

		$this->feed[$product_row["products_id"]] = $product_feed_row;
	};
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
	};
	$fp = fopen($this->filename, "w+");
	fputs($fp, $dbfeed_text);
 	fclose($fp);
}


  function listConf() {
    return Array(
	'merchant'=>Array('title'=>'Merchant ID','desc'=>'','default'=>''),
	'shipping'=>Array('title'=>'Default Shipping Cost','desc'=>'Default shipping cost','default'=>'0'),
	'ftp_host'=>Array('title'=>'Shopping.com FTP Host','desc'=>'HTTP Host for feed upload','default'=>''),
	'ftp_path'=>Array('title'=>'Shopping.com FTP Path','desc'=>'','default'=>''),
	'ftp_user'=>Array('title'=>'Shopping.com FTP User','desc'=>'','default'=>''),
	'ftp_pass'=>Array('title'=>'Shopping.com FTP Password','desc'=>'','default'=>''),
    );
  }
  function adminProductEdit($pid,$xflds) {
    $cat_lkup=IXdb::read("SELECT DISTINCT extra_value FROM dbfeed_products_extra WHERE dbfeed_class='".get_class($this)."' AND extra_field='category'",Array(NULL),Array('id'=>'extra_value','text'=>'extra_value'));
	 if (!isset($xflds['shipping_cost'])) $xflds['shipping_cost'] = $this->getConf('shipping');
?>
<table border="0" cellspacing="2" cellpadding="0">
<tr><td>Shipping Cost:</td><td><?=tep_draw_input_field('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']);?></td></tr>
<tr><td valign="top">Shopping.com Category:</td><td><table border="0" cellspacing="0" cellpadding="0"><tr><td><?=tep_draw_input_field('dbfeed_extra['.get_class($this).'][category]',$xflds['category'],'id="shoppingcom_cat_fld"');?></td>
<td><div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b style=&quot;white-space:nowrap;&quot;>Shopping.com Category</b></font><br><br>The categorization field should be filled out based on Shopping.com\'s taxonomy (category structure), with each level of taxonomy separated by a \'>\' (e.g. Clothing > Accessories > Sunglasses). To determine where an item should be categorized, you visit the Shopping.com site and search for the product (or a similar product). When you find it on their site, look to the top left of the page. You\'ll see a \'breadcrumb\' or list of the categories that product falls into.')" onMouseout="hideddrivetip()"> </div></td>
<td>[<a href="https://docs.google.com/viewer?a=v&q=cache:gTglT1UgedMJ:https://merchant.shopping.com/sc/docs/sdc_taxonomy_new.pdf+&hl=en&gl=us&pid=bl&srcid=ADGEESitx0538Sk0w0ETnAO39NhEug1oVl7HYwDaZf0ATU-ihEa4EW-6Vkpu_OU4QOW6qWs9ZsG6axmyBXDCp33nrULH-by9Qzguml3Q5Ld3VbT50UPgkll8EDeiDedgqz4aUvOw-HWR&sig=AHIEtbTDNmzvUPjs6SFk4YOiS2ZWqOVsYw&pli=1" target="_blank">Shopping.com</a>]</td></tr></table>
<? if ($cat_lkup) { ?>
<br><?=tep_draw_pull_down_menu('shoppingcom_cat_pickup',array_merge(Array(Array('id'=>'','text'=>'---Pick a Category---')),$cat_lkup),'','onChange="if (this.value) {$(\'shoppingcom_cat_fld\').value=this.value; this.options[0].selected=true; }"')?>
<? } ?>
</td></tr>
</table>
<?
  }

}
?>