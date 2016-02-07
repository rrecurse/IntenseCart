<?php

require_once '/usr/share/IXcore/common/modules/dbfeed/_abstract_dbfeed_amazon.php';

class dbfeed_amazon_us extends _dbfeed_amazon {
	public function getName () {
		return "Amazon Marketplace (US)";
	}

	/**
	 * Activates on $_POST['perform'] == 'push'
	 * @todo Can this be used to push inventory to Amazon?
	 * @see _dbfeed_amazon::pushFeed()
	 */
	public function pushFeed () {
		return false;
	}

}
