<?php

//require_once '/usr/share/IXcore/common/modules/dbfeed/_abstract_dbfeed_newegg.php';

//class dbfeed_newegg_us extends _dbfeed_newegg {

require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';

class dbfeed_newegg_us extends IXdbfeed {
	var $category_path_cache, $feed, $filename, $products;

  function __construct () {
        parent::ixdbfeed();
        $this->category_separator = "/";
        $this->cols_separator = "\t";
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'newegg.txt';
  }

	/**
	 * Overloaded to allow for first-time install procedure.
	 * @see IXmodule::checkConf()
	 */
	public function checkConf () {
		// If installation has been completed, allow activation.
		if (is_file (DIR_FS_SITE.'conf/'.$this->getClass ().'.conf')) {
			return true;
		}

		// Show pop-up window to install process.
		// TODO: See if there is a way to show this only when trying to enable the mod.

		// # Update - I added some $_GET vars to detect if the newegg CA or US modules are selected.
		// # If selected show the pop-up.
		// # && (isset($_GET['action']) && $_GET['action'] == 'enable')
		if(isset($_GET['module']) && ($_GET['module'] == 'dbfeed_newegg_us' || $_GET['module'] == 'dbfeed_newegg_ca')){ 
		echo '
		<script type="text/javascript">
			window.open ("'.DIR_WS_ADMIN.'newegg.php?feed='.$this->getClass().'", "'.$this->getClass ().'", "location=no,status=no,toolbar=no,menubar=no,scrollbars=yes,width=300,height=450");';
		echo "</script>\n";
		}
		return false;
	}


	public function getName () {
		return "Newegg Marketplace (US)";
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

		$products_id = $product_row["products_id"];

        $options = $this->_get_attribute_values_list($products_id);

        $options_string = '';

        if (!empty($options)) {
            $options_string = implode(',', $options);
        }

        if (!empty($options_string)) $options_string = " - " . $options_string;

		$products_msrp_query = tep_db_query("SELECT products_msrp FROM suppliers_products_groups WHERE products_id = '". $products_id."' AND (priority = 0 OR priority = 1) LIMIT 1");
		$products_msrp = (tep_db_num_rows($products_msrp_query) > 0 ? number_format(tep_db_result($products_msrp_query,0),2) : '');

		// # multi-warehousing - update tables for multi-warehousing.
		if(MULTI_WAREHOUSE_ACTIVE == 'true') { 

			$warehouse_qty_query = tep_db_query("SELECT pwi.products_quantity
												FROM ". TABLE_PRODUCTS_WAREHOUSE_INVENTORY ." pwi 
												LEFT JOIN ". TABLE_PRODUCTS_WAREHOUSE ." pw ON pw.products_warehouse_id = pwi.products_warehouse_id
												WHERE pwi.products_id = '". $products_id ."'
												AND pw.products_warehouse_default = 1
											   ");

			if(tep_db_num_rows($warehouse_qty_query) > 0) {

				$products_quantity = (tep_db_num_rows($warehouse_qty_query) > 0 ? tep_db_result($warehouse_qty_query,0) : '0');

			} else {

				$products_quantity_query = tep_db_query("SELECT products_quantity FROM ". TABLE_PRODUCTS ." WHERE products_id = '". $products_id ."'");
				$products_quantity = (tep_db_num_rows($products_quantity_query) > 0 ? tep_db_result($products_quantity_query,0) : 0);

			}
			
		} else { // # multi-warehousing not active


				$products_quantity_query = tep_db_query("SELECT products_quantity FROM ". TABLE_PRODUCTS ." WHERE products_id = '". $products_id ."'");
				$products_quantity = (tep_db_num_rows($products_quantity_query) > 0 ? tep_db_result($products_quantity_query,0) : 0);
		}
		


			$packs = (empty($packs) || $packs < 1 ? 1 : $packs);

            if(stripos($product_row["products_name"], 'pack') !== false) {

				$packs = (int) substr($product_row["products_name"], strripos($product_row["products_name"], 'pack') -3, 2);
              
              if(!is_int($packs) || $packs == 0) {
                
                if(stripos($product_row["products_name"], 'multi') !== false) {
 					$packs =  substr($product_row["products_name"], strripos($product_row["products_name"], 'multi') -5, 2);
            	}
			}
              	
		}

		$condition = (stripos($product_row["products_name"], 'Open Box') !== false ? 'Refurbished' : 'New');

		// # manufacturer name patch for mismatching manufacturer names on newegg:

		$oldmanuf = array('Baldwin Z-Wave Locks', 'Cooper Aspire RF', 'GE Z-Wave', 'Kwikset Z-Wave Locks', 'Leviton Vizia RF', 'Linear / 2GIG', 'Intermatic HomeSettings','Nexia / Schlage', 'Vera', 'Yale Z-Wave Locks');
		$newmanuf = array('Baldwin','Cooper Wiring Devices', 'GE', 'Kwikset', 'Leviton Manufacturing Co.', 'Linear', 'Intermatic','Schlage', 'Vera Control', 'Yale2You');

		$manufacturers_name = str_replace($oldmanuf, $newmanuf, $product_row["manufacturers_name"]);

		$products_model = (strlen($product_row["products_model"]) > 20 ? str_replace(' ','',$product_row["products_model"]) : $product_row["products_model"]);
		$products_model = (strlen($products_model) > 20 ? substr($products_model,0,20) : $products_model);

		$products_length = ((float)$product_row["products_length"] > 0 ? $product_row["products_length"] : '');
		$products_width = ((float)$product_row["products_width"] > 0 ? $product_row["products_width"] : '');
		$products_height = ((float)$product_row["products_height"] > 0 ? $product_row["products_height"] : '');
/*
		// # update existing product
		$product_feed_row = array(
			
			"Seller Part #"					=>	$this->_get_text2($product_row["products_sku"]),
			"Manufacturer"					=> 	$this->_get_text2($manufacturers_name),
			"Manufacturer Part # / ISBN"	=>	$this->_get_text2($products_model),
			"UPC"							=>	$this->_get_text2($product_row["products_upc"]),
			"Newegg Item#"					=>	$this->_get_text2(IXdb::read("SELECT extra_value FROM dbfeed_products_extra WHERE products_id='".$products_id."' AND dbfeed_class='".get_class($this)."' AND extra_field='itemid'",NULL,'extra_value')),
			"Currency"						=>	$this->_get_text2('USD'),
			"MSRP"							=>	$this->_get_text2(number_format($products_msrp,2)),
			"MAP"							=>	$this->_get_text2(number_format($product_row["products_price"],2)),
			"Checkout MAP"					=>	$this->_get_text2('False'),
			"Selling Price"					=>	$this->_get_text2(number_format($product_row["products_price"],2)),
			"Shipping"						=>	($product_row["products_free_shipping"] == 1 ? 'Free' : 'Default'),
			"Inventory"						=>	$this->_get_text2($products_quantity),
			"Packs Or Sets"					=>	$this->_get_text2($packs),
			"Item Condition"				=>	$this->_get_text2($condition),
			"Activation Mark"				=>	$this->_get_text2(($product_row["stock"] == 'y' ? 'True' : 'False')),

		);
*/


		// # create product

		// # Action = "Create Item", "Update Item", "Update/Append Image", and "Replace Image"
		// # If the action field is left blank, if the item does not exist in our system, the action by default will be "Create Item". 
		// # if the item does exist in our system, the action by default will be “Update Item Price and Inventory”.

		$action = '';


		// # image array
		$image1 = (!empty($product_row["products_image"]) ? tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"]) : '');
		$image2 = (!empty($product_row["products_image_xl_1"]) ? tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_1"]) : '');
		$image3 = (!empty($product_row["products_image_xl_2"]) ? tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_2"]) : '');
		$image4 = (!empty($product_row["products_image_xl_3"]) ? tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image_xl_3"]) : '');

		$images = (!empty($image1) ? $image1 .',' : '') . (!empty($image2) ? $image2 .',' : '') . (!empty($image3) ? $image3 .',' : '') . (!empty($image4) ? $image4 : '');

		$product_feed_row = array(
			
			"Seller Part #"					=>	$this->_get_text2($product_row["products_sku"]),
			"Manufacturer"					=> 	$this->_get_text2($manufacturers_name),
			"Manufacturer Part # / ISBN"	=>	$this->_get_text2($products_model),
			"UPC"							=>	$this->_get_text2($product_row["products_upc"]),
			"Related Seller Part#"			=>	$this->_get_text2(''),
			"Website Short Title"			=>	$this->_get_text2($product_row["products_name"]),
			"Bullet Description"			=>	$this->_get_text2(''),
			"Product Description"			=>	$this->_get_text2($product_row["products_info"], 4000),
			"Item Length"					=>	$this->_get_text2($products_length),
			"Item Width"					=>	$this->_get_text2($products_width),
			"Item Height"					=>	$this->_get_text2($products_height),
			"Item Weight"					=>	$this->_get_text2($product_row["products_weight"]),
			"Packs Or Sets"					=>	$this->_get_text2('1'),
			"Item Condition"				=>	$this->_get_text2($condition),
			"Item Package"					=>	$this->_get_text2('Retail'),
			"Shipping Restriction"			=>	$this->_get_text2(''),
			"Currency"						=>	$this->_get_text2('USD'),
			"MSRP"							=>	$this->_get_text2($products_msrp),
			"MAP"							=>	$this->_get_text2(number_format($product_row["products_price"],2)),
			"Checkout MAP"					=>	$this->_get_text2('False'),
			"Selling Price"					=>	$this->_get_text2(number_format($product_row["products_price"],2)),
			"Shipping"						=>	($product_row["products_free_shipping"] == 1 ? 'Free' : 'Default'),
			"Inventory"						=>	$this->_get_text2($products_quantity),
			"Activation Mark"				=>	$this->_get_text2(($product_row["stock"] == 'y' ? 'True' : 'False')),
			"Action"						=>	$this->_get_text2($action),
			"Item Images"					=>	$this->_get_text2($images, 2000),
			"Prop 65"						=>	$this->_get_text2(''),
			"Country Of Origin"				=>	$this->_get_text2(''),
			"Prop 65 - Motherboard"			=>	$this->_get_text2(''),
			"Age 18+ Verification"			=>	$this->_get_text2(''),
			"Choking Hazard 1"				=>	$this->_get_text2(''),
			"Choking Hazard 2"				=>	$this->_get_text2(''),
			"Choking Hazard 3"				=>	$this->_get_text2(''),
			"Choking Hazard 4"				=>	$this->_get_text2(''),
			"CommonPackageContents"			=>	$this->_get_text2(''),
			"ManufacturerWarrantyParts"		=>	$this->_get_text2(''),
			"ManufacturerWarrantyLabor"		=>	$this->_get_text2(''),
			"SURHABrand"					=>	$this->_get_text2(''),
			"SURHAModel"					=>	$this->_get_text2(''),
			"SURHASpecifications"			=>	$this->_get_text2(''),
			"SURHAFeatures"					=>	$this->_get_text2(''),
			"SURHADimensions"				=>	$this->_get_text2(''),
			"SURHAWeight"					=>	$this->_get_text2(''),
			"SURHAType"						=>	$this->_get_text2(''),
			"SportsGlobalSportsTeam"		=>	$this->_get_text2(''),
			"SURHAColor"					=>	$this->_get_text2(''),
			"GlobalApp-Enabled"				=>	$this->_get_text2(''),
			"GroupType"						=>	$this->_get_text2(''),
			"SportsGlobalSportsLeague"		=>	$this->_get_text2(''),
			"GlobalElectricalOutletPlugType"=>	$this->_get_text2(''),

		);

		$this->feed[$products_id] = $product_feed_row;
	}

/*
// # Add missing products to database that we recently added to newegg.

$models = array('PA-100-3PK','PD-100-3PK','WS-100-3PK','WD-100-3PK','DRAG-AEON-2KIT4','PD-100','WD-100','DRAG-AEON-2KIT2','DRAG-AEON-2KIT3','DRAG-AEON-2KIT','WA-100','WS-100','PA-100','VERA-DRAG-AEON-3KIT');

$newegg_ids = array('9SIA8EJ37U9905','9SIA8EJ37U9908','9SIA8EJ37U9907','9SIA8EJ37U9906','9SIA8EJ37U9909','9SIA8EJ37U9897','9SIA8EJ37U9900','9SIA8EJ37U9902','9SIA8EJ37U9901','9SIA8EJ37U9904','9SIA8EJ37U9903','9SIA8EJ37U9898','9SIA8EJ37U9899','9SIA8EJ36X5256');


$neweggArray = array_combine($models, $newegg_ids);

foreach($neweggArray as $model => $newegg_id) { 

	$model_query = tep_db_query("SELECT products_id from products WHERE products_model = '".$model."'");

	if(tep_db_num_rows($model_query) > 0) { 

		$products_id = tep_db_result($model_query,0);

		tep_db_query("INSERT IGNORE INTO dbfeed_products_extra (dbfeed_class, products_id, extra_field, extra_value)
		VALUES ('dbfeed_newegg_us', '".$products_id."', 'itemid', '". $newegg_id."')");

		tep_db_query("INSERT IGNORE INTO dbfeed_products_extra (dbfeed_class, products_id, extra_field, extra_value)
		VALUES ('dbfeed_newegg_us', '".$products_id."', 'sku', '". $model."')");

		tep_db_query("INSERT IGNORE INTO dbfeed_products_extra (dbfeed_class, products_id, extra_field, extra_value)
		VALUES ('dbfeed_newegg_us', '".$products_id."', 'newegg_surcharge', '0')");

		tep_db_query("INSERT IGNORE INTO dbfeed_products_extra (dbfeed_class, products_id, extra_field, extra_value)
		VALUES ('dbfeed_newegg_us', '".$products_id."', 'shipping_cost', '0.00')");

	}

}
*/


  }


	/**
	 * Activates on $_POST['perform'] == 'push'
	 * @todo Can this be used to push inventory to Newegg?
	 * @see _dbfeed_newegg::pushFeed()
	 */
	public function pushFeed () {
		return false;

	}

	function saveFeed() {

		reset($this->feed);

		$dbfeed_text = "";
		// # update products
		//$dbfeed_text .= "Version=2.0"."\n";

		// # create / update products - do not remove weird spacing - these are tabs to match the content column count.
		$dbfeed_text .= "Version=1.01	SubCategoryID=3479		TemplateDate=".date('Y-m-d')."																																														
";
		
		while (list($id, $row) = each($this->feed)) {

			if (!isset($header_row)) {
				$header_row = array_keys($row);
				while (list(,$header_title) = each($header_row)) {
					$new_header_row[] = $this->_get_text2($header_title);
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


	public function actionList() {
		//return null;
		return array('generate'=>'Generate');
	}


	public function listConf () {
		return array (
			'seller_id' => array ('title' => 'Newegg Seller ID', 'desc' => 'Newegg Seller ID', 'default' => ''),
			'auth_key' => array ('title' => 'API Authorization key', 'desc' => 'Your Newegg API Authorization key', 'default' => ''),
			'secret_key' => array ('title' => 'API Secret key', 'desc' => 'Newegg API Secret key', 'default' => ''),
			'newegg_ftp_host' => array ('title' => 'Newegg FTP Host', 'desc' => 'Newegg Seller FTP Host', 'default' => ''),
			'newegg_ftp_user' => array ('title' => 'Newegg FTP Username', 'desc' => 'Newegg Seller FTP username', 'default' => ''),
			'newegg_ftp_pass' => array ('title' => 'Newegg FTP Password', 'desc' => 'Newegg Seller FTP password', 'default' => ''),
			'newegg_ftp_format' => array ('title' => 'Newegg FTP file format', 'desc' => 'Newegg Seller FTP file format - Possible values: csv, xls, xml', 'default' => ''),
			'shipping' => array ('title' => 'Default Shipping Cost', 'desc' => 'Default shipping cost', 'default' => '0.00'),
			'newegg_surcharge' => array ('title' => 'Newegg Default Surcharge', 'desc' => 'Added per item cost for Newegg feed', 'default' => '0'),
			'orders_poll' => array ('title' => 'Orders update time', 'desc' => 'Time in minutes between each new orders polling. Warning: If set to less than 20 minutes polling might be throttled by Newegg.', 'default' => 20),
			'inventory_poll' => array ('title' => 'Inventory update time', 'desc' => 'Time in minutes between each product update push. Warning: If set to less than 20 minutes polling might be throttled by Newegg.', 'default' => 20),
		);
	}

	public function adminProductEdit ($pid, $xflds) {

		$xflds['shipping_cost'] = (isset($xflds['shipping_cost'])) ? $xflds['shipping_cost'] : $this->getConf('shipping');
		$shippingCost = tep_draw_input_field ('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']);	

		$xflds['newegg_surcharge'] = (isset($xflds['newegg_surcharge'])) ? $xflds['newegg_surcharge'] : $this->getConf('newegg_surcharge');
		$neweggSurcharge = tep_draw_input_field ('dbfeed_extra[' . get_class($this) . '][newegg_surcharge]', $xflds['newegg_surcharge']);

		$xflds['sku'] = (isset($xflds['sku'])) ? $xflds['sku'] : '';
		$thesku = tep_draw_hidden_field ('dbfeed_extra['.get_class($this).'][sku]',$xflds['sku'],'');

		$xflds['itemid'] = (isset($xflds['itemid'])) ? $xflds['itemid'] : '';
		$theItemID = tep_draw_input_field ('dbfeed_extra['.get_class($this).'][itemid]',$xflds['itemid'],'');


	echo <<<OutHTML
<table border="0" cellspacing="1" cellpadding="3">
	<tr>
		<td align="right">Newegg Item #:</td>
		<td>{$theItemID}</td>
</tr>
	<tr>
		<td align="right">Shipping Cost:</td>
		<td>{$shippingCost}</td>
</tr>
	<tr>
		<td align="right">Newegg Surcharge:</td>
		<td>{$neweggSurcharge} {$thesku}</td>

OutHTML;

	echo <<<OutHTML
				
				</tr>
			</table>
OutHTML;
	
	}


}
