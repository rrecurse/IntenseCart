<?php
require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';

class dbfeed_amazon_productads extends IXdbfeed {

	var $category_path_cache, $feed, $filename, $products;

	function dbfeed_amazon_productads () {
		parent::ixdbfeed();
        $this->category_separator = ">";
        $this->cols_separator = "\t";
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'amazon_productads.txt';
	}


	function getName() {
    	return "Amazon Product Ads (PPC)";
	}


	function _get_text2($text = '', $length = 0) {
		$text = (is_array($text)) ? array_shift($text) : $text;
		$text = str_replace('›','>',$text);
        $text = strip_tags($text);
    	$text = preg_replace(
        array("/\n/is", "/\r/is"),
        array("", ""),
        $text);

	    $text = str_replace(array("<9b>"),array(">"),$text);
    	$text = preg_replace('/[\\x80-\\xFF]/',' ',$text);
        if ((strlen($text) > $length) && ($length > 0)) {
			$text = substr($text, 0, $length);
        }
    
		return $text;
	}

	
	function buildFeed() {

		if(file_exists($this->filename)) {
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
			if($product_row["shipping_rate"]) $product_shipping = $product_row["shipping_rate"];
			if($product_row["products_free_shipping"] == 1) $product_shipping = 0;
		
	        $options = $this->_get_attribute_values_list($product_row["products_id"]);
    	    $options_string = '';
        	if(!empty($options)) $options_string = implode(',', $options);

	        if(!empty($options_string)) $options_string = " - " . $options_string;

			$keywords = IXdb::read("SELECT products_head_keywords_tag 
									FROM products_description 
									WHERE products_id='".$product_row["products_id"]."'",NULL,'products_head_keywords_tag');

			if(!is_array($keywords)){ 
				$kw = explode(',', $keywords); 
			} else { 
				$kw = array(); 
			}


			$image1 = HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"]);
			$image2 = (!empty($product_row["products_image_xl_1"])) ? HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_1"]) : '';
			$image3 = (!empty($product_row["products_image_xl_2"])) ? HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_2"]) : '';
			$image4 = (!empty($product_row["products_image_xl_3"])) ? HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_3"]) : '';
			$image5 = (!empty($product_row["products_image_xl_4"])) ? HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_4"]) : '';

			$product_feed_row = array(
				"Category" => $this->_get_text2(IXdb::read("SELECT extra_value 
															FROM dbfeed_products_extra 
															WHERE products_id='".$product_row["products_id"]."' 
															AND dbfeed_class='".get_class($this)."' 
															AND extra_field='category'",NULL,'extra_value')),
				"Title" => $this->_get_text2($product_row["products_name"] . $options_string),
				"Link" => $this->_get_text2('http://'.$_SERVER['HTTP_HOST'].IXdb::read("SELECT url_new 
																						FROM url_rewrite_map 
																						WHERE item_id='p".$product_row["products_id"]."' 
																						ORDER BY url_new DESC LIMIT 1",NULL,'url_new')),
				"SKU" => $this->_get_text2($product_row["products_model"]),
				"Price" => $this->_get_text2(number_format($product_row["products_price"],2)),
				"Brand" => $this->_get_text2($product_row["manufacturers_name"]),	
				"Department" => $this->_get_text2($this->_getCategoryPath($product_row["categories_id"])),
				"UPC" => $this->_get_text2((isset($product_row["products_upc"])) ?  $product_row["products_upc"] : ""),
				"Image" => $this->_get_text2($image1),
				"Description" => $this->_get_text2($product_row["products_info"], 1024),		
				"Manufacturer" => $this->_get_text2($product_row["manufacturers_name"]),
				"Mfr part number" => $this->_get_text2($product_row["products_model"]),
				"Shipping Cost" => $this->_get_text2($product_shipping),
				"Age" => $this->_get_text2(''),
				"Band material" => $this->_get_text2(''),
				"Bullet point1" => $this->_get_text2(''),
				"Bullet point2" => $this->_get_text2(''),
				"Bullet point3" => $this->_get_text2(''),
				"Bullet point4" => $this->_get_text2(''),
				"Bullet point5" => $this->_get_text2(''),
				"Color" => $this->_get_text2(''),
				"Color and finish" => $this->_get_text2(''),
				"Computer CPU speed" => $this->_get_text2(''),
				"Cuisine" => $this->_get_text2(''),
				"Computer memory size" => $this->_get_text2(''),
				"Display size" => $this->_get_text2(''),
				"Digital Camera Resolution" => $this->_get_text2(''),
				"Display technology" => $this->_get_text2(''),
				"Flash drive size" => $this->_get_text2(''),
				"Flavor" => $this->_get_text2(''),
				"Gender" => $this->_get_text2(''),
				"Hard disk size" => $this->_get_text2(''),
				"Height" => $this->_get_text2(''),
				"Included RAM size" => $this->_get_text2(''),
				"Item package quantity" => $this->_get_text2(''),
				"Keywords1" => $this->_get_text2((!empty($kw[0])) ? $kw[0] : ""),
				"Keywords2" => $this->_get_text2((!empty($kw[1])) ? $kw[1] : ""),
				"Keywords3" => $this->_get_text2((!empty($kw[2])) ? $kw[2] : ""),
				"Keywords4" => $this->_get_text2((!empty($kw[3])) ? $kw[3] : ""),
				"Keywords5" => $this->_get_text2((!empty($kw[4])) ? $kw[4] : ""),
				"League and Team" => $this->_get_text2(''),
				"Length" => $this->_get_text2(''),
				"Material" => $this->_get_text2(''),
				"Maximum age" => $this->_get_text2(''),
				"Memory Card Type" => $this->_get_text2(''),
				"Metal type" => $this->_get_text2(''),
				"Minimum age" => $this->_get_text2(''),
				"Model Number" => $this->_get_text2($product_row["products_model"]),
				"Occasion" => $this->_get_text2(''),
				"Operating system" => $this->_get_text2(''),
				"Optical zoom" => $this->_get_text2(''),
				"Other image-url1" => $this->_get_text2($image2),
				"Other image-url2" => $this->_get_text2($image3),
				"Other image-url3" => $this->_get_text2($image4),
				"Other image-url4" => $this->_get_text2($image5),
				"Other image-url5" => $this->_get_text2(''),
				"Other image-url6" => $this->_get_text2(''),
				"Other image-url7" => $this->_get_text2(''),
				"Other image-url8" => $this->_get_text2(''),
				"Recommended Browse Node" => $this->_get_text2(''),
				"Ring size" => $this->_get_text2(''),
				"Scent" => $this->_get_text2(''),
				"Shipping Weight" => $this->_get_text2($product_row["products_weight"]),	
				"Size" => $this->_get_text2(''),
				"Size per pearl" => $this->_get_text2(''),
				"Specialty" => $this->_get_text2(''),
				"Theme HPC" => $this->_get_text2(''),
				"Total Diamond Weight" 		=> $this->_get_text2(''),
				"Watch movement" => $this->_get_text2(''),
				"Weight" => $this->_get_text2(''),
				"Width" => $this->_get_text2('')
				);

			$this->feed[$product_row["products_id"]] = $product_feed_row;
		}
	}


	function pushFeed() {

		if(file_exists($this->filename)) {
			chmod($this->filename, 0777) or die ("can't chmod file");
			fopen($this->filename, 'wo') or die ("can't open file");
			unlink($this->filename) or die ("can't delete the file!");
		}

		$this->storeFeed();
		$file_size = filesize($this->filename);
		$fp = fopen($this->filename, 'ro');	

		// # NEW - AMAZON REQUIRES SFTP Method - dependencies include installation of libssh2

		$theFile = $this->filename;
		$remoteName = 'amazon_productads.txt';

		// # scrub protocols from hostname
		$prtcls = array('ftp://', 'sftp://', 'ftps://', 'http://', 'https://');
		$host = str_replace($prtcls,'',$this->getConf('ftp_host'));

		// # scrub username in case we forgot to remove the host address
		$user = $this->getConf('ftp_user');
		if(strstr($user, '@', true)) {
			$user = explode('@',$user);
			$user = $user[0];
		} 

		$pass = $this->getConf('ftp_pass');
		$conn = ssh2_connect($host, 22);

		if(!$conn) { 
			trigger_error("Could not connect to: $host.");
		} else { 

			// # authenticate user check
			if(!ssh2_auth_password($conn, $user, $pass)) {
				trigger_error("Authentication Failed using username: $user and password: $pass");
			} else {

				// # file send check 
				if(!ssh2_scp_send($conn, $theFile, $remoteName, 0755)) {
					trigger_error("Could not upload $remoteName using file $fp.");
				} else {
					echo '<br><b style="color:green">Succesfully uploaded '.$theFile .' to '.$host . '!</b><br><br>';
				}

			} // # END auth check

		} // # END $conn check

		fclose($fp);
	}


	function saveFeed() {

		reset($this->feed);
		$dbfeed_text = "";
		while (list($id, $row) = each($this->feed)) {
			if (!isset($header_row)) {
				$header_row = array_keys($row);
				while (list(,$header_title) = each($header_row)) {
					$new_header_row[] = $this->_get_text2($header_title);
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


	function listConf() {
	    return array(
		'ftp_host' => array('title'=>'Amazon FTP Host','desc' => 'FTP Host for feed upload','default'=>''),
		'ftp_user' => array('title'=>'Amazon FTP User','desc' => '','default'=>''),
		'ftp_pass' => array('title'=>'Amazon FTP Password','desc' => '','default'=>''),
		'shipping' => array('title'=>'Default Shipping Cost','desc' => 'Default shipping cost','default' => '0.00'),
    	);
	}

	function adminProductEdit($pid,$xflds) {

		$cat_lkup = IXdb::read("SELECT DISTINCT extra_value 
								FROM dbfeed_products_extra 
								WHERE dbfeed_class='".get_class($this)."' 
								AND extra_field='category'",array(NULL),array('id'=>'extra_value','text'=>'extra_value'));

		$xflds['shipping_cost'] = (isset($xflds['shipping_cost'])) ? $xflds['shipping_cost'] : $this->getConf('shipping');
		
		echo '
		<table border="0" cellspacing="2" cellpadding="0">
			<tr>
				<td>Shipping Cost:</td>
				<td>'. tep_draw_input_field('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']).'</td>
			</tr>
			<tr>
				<td valign="top">Amazon Ads Category:</td>
				<td>
					<table border="0" cellspacing="0" cellpadding="0">
						<tr>
				<td>'.tep_draw_input_field('dbfeed_extra['.get_class($this).'][category]',$xflds['category'],'id="amazon_productads_cat_fld"').'</td>

							<td>';
?>
								<div class="helpicon" onMouseover="ddrivetip('<font class=featuredpopName><b style=&quot;white-space:nowrap;&quot;>Amazon Product Ads Category</b></font><br><br>The categorization field should be filled out based on the Amazon Browse Node taxonomy (category structure), with each level of taxonomy separated by a \'>\' (e.g. Tools & Home Improvement/Electrical).')" onMouseout="hideddrivetip()"> </div></td>
							<td>[<a href="http://www.amazonservices.com/content/product-ads-feedbuilder.htm" target="_blank">Feed Builder</a>]</td>
						</tr>
					</table>
<?php
		if($cat_lkup) {
			echo '<br>'. tep_draw_pull_down_menu('amazon_productads_cat_pickup',array_merge(array(array('id'=>'','text'=>'Pick Amazon Category')),$cat_lkup),'','onChange="if (this.value) {$(\'amazon_productads_cat_fld\').value=this.value; this.options[0].selected=true; }"');
		}
		
		echo '</td></tr></table>';

	}

}
?>