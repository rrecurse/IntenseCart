<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################

if (STORE_SESSIONS == 'mysql') {

	if (!$SESS_LIFE = get_cfg_var('session.gc_maxlifetime')) $SESS_LIFE = 1440;

    function _sess_open($save_path, $session_name) {
      return true;
    }

    function _sess_close() {
		return true;
    }

    function _sess_read($key) {

		if(!empty($key) && $key != '') { 
	      $value_query = tep_db_query("select value from " . TABLE_SESSIONS . " where sesskey = '" . tep_db_input($key) . "' and expiry > '" . time() . "'");
    	  $value = tep_db_fetch_array($value_query);
		}

		if (isset($value['value'])) {
			return $value['value'];
		}

		return false;
	}


	function _sess_write($key, $val) {
		global $SESS_LIFE;

		$expiry = time() + $SESS_LIFE;
		$value = $val;

		$qid = tep_db_query("SELECT COUNT(0) AS total FROM " . TABLE_SESSIONS . " WHERE sesskey = '" . tep_db_input($key) . "'");
		$total = tep_db_fetch_array($qid);

		if(!empty($val) || $val != '') { 
			if ($total['total'] > 0) {

				return tep_db_query("UPDATE " . TABLE_SESSIONS . " 
									 SET expiry = '" . tep_db_input($expiry) . "', 
									 value = '" . tep_db_input($value) . "',
									 ip_address = '".tep_db_input($_SERVER['REMOTE_ADDR'])."'
									 WHERE sesskey = '" . tep_db_input($key) . "'
									");
			} else {

				return tep_db_query("INSERT IGNORE INTO " . TABLE_SESSIONS . " 
									 SET sesskey = '".tep_db_input($key)."',
									 expiry = '".tep_db_input($expiry)."',
									 value = '".tep_db_input($value)."', 
									 ip_address = '".tep_db_input($_SERVER['REMOTE_ADDR'])."'
									");
			}
		}
    }


	function _sess_destroy($key) {
		return tep_db_query("delete from " . TABLE_SESSIONS . " where sesskey = '" . tep_db_input($key) . "'");
	}

	
	function _sess_gc($maxlifetime) {
		tep_db_query("delete from " . TABLE_SESSIONS . " where expiry < '" . time() . "'");
		return true;
    }

	
session_set_save_handler('_sess_open', '_sess_close', '_sess_read', '_sess_write', '_sess_destroy', '_sess_gc');

} // # END STORE_SESSIONS == 'mysql' check


	function tep_session_start() {
		return session_start();
	}

	function tep_session_register($variable) {

		global $session_started;
		if($session_started == true) {
			if(isset($GLOBALS[$variable])) {
				$_SESSION[$variable] =& $GLOBALS[$variable];
			} else {
				$_SESSION[$variable] = null;
			}
		}

		return false;
	}

	function tep_session_is_registered($variable) {
		return isset($_SESSION) && array_key_exists($variable, $_SESSION);
                
	}

	function tep_session_unregister($variable) {
		unset($_SESSION[$variable]);
	}

	function tep_session_id($sessid = '') {
		if ($sessid != '') {
			return session_id($sessid);
		} else {
			return session_id();
		}
	}

	function tep_session_name($name = '') {
		if ($name != '') {
			return session_name($name);
		} else {
			return session_name();
		}
	}

	function tep_session_close() {
		if (function_exists('session_close')) return session_close();
	}

	function tep_session_destroy() {
		return session_destroy();
	}

	function tep_session_save_path($path = '') {
		if ($path != '') {
			return session_save_path($path);
		} else {
			return session_save_path();
		}
	}
?>