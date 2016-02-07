<?
require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';
class dbfeed_froogle extends IXdbfeed {
	var $category_path_cache, $feed, $filename, $products;

  function dbfeed_yahoocom () {
		parent::ixdbfeed();
        $this->category_separator = "->";
        $this->cols_separator = "\t";
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'froogle.csv';
  }

  function getName() {
    return "Froogle";
  }

  function buildFeed() {
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
		$product_colors = '';
		if (!empty($options['Color'])) {
			$product_colors = $options['Color'];
			unset($options['Color']);
		};
		$product_sizes = '';
		if (!empty($options['Size'])) {
			$product_sizes = $options['Size'];
			unset($options['Size']);
		};
		$options_string = '';
		if (!empty($options)) {
			$options_string = implode(',', $options);
		};
		if (!empty($options_string)) $options_string = " - " . $options_string;
		$product_feed_row = array(
			"product_url"			=> 	$this->_get_text(tep_href_link('index.php', 'products_id=' . $product_row["products_id"])),
			"name"					=> 	$this->_get_text($product_row["products_name"]) . $options_string,
			"description"			=> 	$this->_get_text($product_row["products_description"], 1024),
			"price"					=> 	$this->_get_text(number_format($product_row["products_price"], 2, '.', '')),
			"image-url"				=> 	$this->_get_text(HTTP_CATALOG_SERVER . tep_image_src(DIR_WS_CATALOG_IMAGES . $product_row["products_image"])),
			"category"				=> 	$this->_get_text($this->_getCategoryPath($product_row["categories_id"])),
			"offer_id"				=> 	$this->_get_text($product_row["products_id"]),
			"instock"				=> 	$this->_get_text($product_row["stock"]),
			"shipping"				=> 	$this->_get_text($product_shipping),
			"brand"					=> 	$this->_get_text($product_row["manufacturers_name"]),
			"upc"					=> 	$this->_get_text($product_row["products_upc"]),
			"color"					=> 	$this->_get_text($product_colors),
			"size"					=> 	$this->_get_text($product_sizes),
			"product_id"			=> 	$this->_get_text($product_row["products_id"])
		);
		$this->feed[$product_row["products_id"]] = $product_feed_row;
	};
  }
}
?>
