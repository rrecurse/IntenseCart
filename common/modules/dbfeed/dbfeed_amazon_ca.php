<?php

require_once '/usr/share/IXcore/common/modules/dbfeed/_abstract_dbfeed_amazon.php';

class dbfeed_amazon_ca extends _dbfeed_amazon {
	public function getName () {
		return "Amazon Marketplace (Canada)";
	}

	public function pushFeed () {
		return false;
	}

	public function actionList() {
		return null;
	}

}
