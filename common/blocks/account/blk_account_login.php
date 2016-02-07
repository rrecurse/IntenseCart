<?php

class blk_account_login extends IXblock {

	function preRenderSection($sec,&$body,$args) {
    
		switch ($sec) {
			case 'login':
			if(isset($_SESSION['customer_id']) && $_SESSION['customer_id']) {

				$g = $_SESSION['sppc_customer_group_id'];
				if($this->_chkList($args['exclude'],$g) || !$this->_chkList($args['include'],$g,true)) {
					if(!isset($this->nologin)) $this->nologin = true;
					return false;
				} else {
					if(isset($this->nologin)) $this->nologin=false;
					return true;
				}

			} else {
				return false;
			}

			case 'nologin':

			if($this->nologin || !isset($_SESSION['customer_id']) || !$_SESSION['customer_id']) {
				return true;
			}

			$g = $_SESSION['sppc_customer_group_id'];

			if($this->_chkList($args['include'],$g)) {
				return true;
			}

			//return false;

			default: 
				return false;
			}
	}

	function _chkList($lst,$val,$e=false) {
		if ($lst=='') return $e;

		foreach (explode(';',$lst) AS $v) {
			if(preg_match('/(\d+)-(\d+)/',$v,$vp) && $val>=$vp[1] && $val<=$vp[2]) return true;
			if(is_numeric($v) && $v==$val) return true;
		}
		return false;
	}
}
?>