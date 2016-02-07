<?php

require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';
require_once '/usr/share/IXcore/common/classes/crontab.php';

abstract class _dbfeed_amazon extends IXdbfeed {
	public $category_path_cache, $feed, $filename, $products;

	public function dbfeed_amazon () {
		self::__construct ();
	}

	public function __construct () {
		parent::ixdbfeed ();
		$this->category_separator = "/";
		$this->cols_separator = "\t";
		// FIXME: Is this needed?
		$this->filename = DIR_FS_SITE_CATALOG . 'pub/' . 'amazon.txt';
	}

//	abstract public function getName ();
//	abstract public function pushFeed ();

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

		// # Update - I added some $_GET vars to detect if the amazon CA or US modules are selected.
		// # If selected show the pop-up.
		// # && (isset($_GET['action']) && $_GET['action'] == 'enable')
		if(isset($_GET['module']) && ($_GET['module'] == 'dbfeed_amazon_us' || $_GET['module'] == 'dbfeed_amazon_ca')){ 
		echo '
		<script type="text/javascript">
			window.open ("'.DIR_WS_ADMIN.'amazon.php?feed='.$this->getClass().'", "'.$this->getClass ().'", "location=no,status=no,toolbar=no,menubar=no,scrollbars=yes,width=300,height=450");';
		echo "</script>\n";
		}
		return false;
	}

	/**
	 * Returns the name of the parent abstract class.
	 * @return string
	 */
	public function getParent () {
		return 'dbfeed_amazon';
	}

	public function saveConf () {
		parent::saveConf();

		// Get the polling times, paths and region for the crontab commands.
		$ordersPoll = $this->getConf('orders_poll');
		$inventoryPoll = $this->getConf('inventory_poll');
		list (, , $region) = explode ("_", $this->getClass ());
		$servicePath = DIR_FS_COMMON."service/";
		$root = DIR_FS_SITE;

		// Define the actual commands that's to be run via crontab.
		$orderCmd = "{$servicePath}amazon_orders.php $root $region";
		$inventoryCmd = "{$servicePath}amazon_inventory.php $root $region";

		// Remove old jobs
		Crontab::removeJob ($orderCmd);
		Crontab::removeJob ($inventoryCmd);

		// Add new jobs.
		if ($ordersPoll > 0) {
			Crontab::addJob ("*/$ordersPoll * * * * php -f {$orderCmd} >> {$root}logs/orders_{$region}.log");
		}
		if ($inventoryPoll > 0) {
			Crontab::addJob ("*/$inventoryPoll * * * * php -f {$inventoryCmd} >> {$root}logs/inventory_{$region}.log");
		}
	}

	/**
	 * Overloaded as this is not needed any more.
	 * @see ixdbfeed::loadProducts()
	 */
	public function loadProducts () {
		return false;
	}

	/**
	 * @FIXME Is this needed any more?
	 *
	 * @param string $text
	 * @param int $length
	 * @return string
	 */
	private function _get_text2 ($text, $length = 0) {
		$text = strip_tags ($text);
		$text = preg_replace (array ("/\n/is", "/\r/is"), array ("", ""), $text);
		$text = str_replace (array ("<9b>"), array (">"), $text);
		$text = preg_replace ('/[\\x80-\\xFF]/', ' ', $text);
		if ((strlen ($text) > $length) && ($length > 0)) {
			$text = substr ($text, 0, $length);
		}
		return $text;
	}

	/**
	 * Overloaded as this is not needed any more.
	 * @see ixdbfeed::buildFeed()
	 */
	public function buildFeed () {
		return false;
	}

	public function listConf () {
		return array (
			'merchant_id' => array ('title' => 'Merchant ID', 'desc' => 'Your Amazon AWS merchant ID', 'default' => ''),
			'secret_key' => array ('title' => 'Secret key', 'desc' => 'Your Amazon AWS secret key', 'default' => ''),
			'access_key' => array ('title' => 'Access key', 'desc' => 'Your Amazon AWS access key', 'default' => ''),
			'sellercentral_user' => array ('title' => 'Seller Central Username', 'desc' => 'Amazon Seller Central Username (for use with CuRL requests)', 'default' => ''),
			'sellercentral_pass' => array ('title' => 'Seller Central Password', 'desc' => 'Amazon Seller Central Password (for use with CuRL requests)', 'default' => ''),
			'marketplace_id' => array ('title' => 'Marketplace ID', 'desc' => 'Your Amazon AWS marketplace ID', 'default' => ''),
			'shipping' => array ('title' => 'Shipping Cost', 'desc' => 'Default shipping cost', 'default' => '0.00'),
			'amazon_surcharge' => array ('title' => 'Amazon Surcharge', 'desc' => 'Added per item cost for Amazon feed', 'default' => '0'),
			'orders_poll' => array ('title' => 'Orders update time', 'desc' => 'Time in minutes between each new orders polling. Warning: If set to less than 20 minutes polling might be throttled by Amazon.', 'default' => 20),
			'inventory_poll' => array ('title' => 'Inventory update time', 'desc' => 'Time in minutes between each product update push. Warning: If set to less than 20 minutes polling might be throttled by Amazon.', 'default' => 20),
		);
	}

	public function adminProductEdit ($pid, $xflds) {
//error_log(print_r($xflds,TRUE));

		$xflds['shipping_cost'] = (isset($xflds['shipping_cost'])) ? $xflds['shipping_cost'] : $this->getConf('shipping');
		$shippingCost = tep_draw_input_field ('dbfeed_extra['.get_class($this).'][shipping_cost]',$xflds['shipping_cost']);	

		$xflds['amazon_surcharge'] = (isset($xflds['amazon_surcharge'])) ? $xflds['amazon_surcharge'] : $this->getConf('amazon_surcharge');
		$amazonSurcharge = tep_draw_input_field ('dbfeed_extra[' . get_class($this) . '][amazon_surcharge]', $xflds['amazon_surcharge']);

		$xflds['sku'] = (isset($xflds['sku'])) ? $xflds['sku'] : '';
		$thesku = tep_draw_hidden_field ('dbfeed_extra['.get_class($this).'][sku]',$xflds['sku'],'');

		$xflds['asin'] = (isset($xflds['asin'])) ? $xflds['asin'] : '';
		$theASIN = tep_draw_input_field ('dbfeed_extra['.get_class($this).'][asin]',$xflds['asin'],'');


	echo <<<OutHTML
<table border="0" cellspacing="1" cellpadding="3">
	<tr>
		<td align="right">Amazon ASIN:</td>
		<td>{$theASIN}</td>
</tr>
	<tr>
		<td align="right">Shipping Cost:</td>
		<td>{$shippingCost}</td>
</tr>
	<tr>
		<td align="right">Amazon Surcharge:</td>
		<td>{$amazonSurcharge} {$thesku}</td>

OutHTML;

	echo <<<OutHTML
				
				</tr>
			</table>
OutHTML;
	
	}

}
