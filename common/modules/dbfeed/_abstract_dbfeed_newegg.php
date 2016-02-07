<?php

require_once '/usr/share/IXcore/common/modules/dbfeed/ixdbfeed.php';
require_once '/usr/share/IXcore/common/classes/crontab.php';

abstract class _dbfeed_newegg extends IXdbfeed {
	public $category_path_cache, $feed, $filename, $products;

	public function dbfeed_newegg () {
		self::__construct ();
	}

	public function __construct () {
		parent::ixdbfeed ();
		$this->category_separator = "/";
		$this->cols_separator = "\t";
		// # FIXME: Is this needed?
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

	/**
	 * Returns the name of the parent abstract class.
	 * @return string
	 */
	public function getParent () {
		return 'dbfeed_newegg';
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
		$orderCmd = "{$servicePath}newegg_orders.php $root $region";
		$inventoryCmd = "{$servicePath}newegg_inventory.php $root $region";

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
	//public function buildFeed () {
	//	return false;
	//}

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
