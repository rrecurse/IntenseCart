<?php

	global $ModuleConfigCache;
	$ModuleConfigCache=Array();

	function tep_list_modules($sec) {

		if(preg_match('/\W/',$sec)) return NULL;
		$lst = array();
		$d = opendir(DIR_FS_COMMON.'modules/'.$sec.'/');
		
		while($f = readdir($d)) {
			if (preg_match('/^('.$sec.'_.*)\.php$/',$f,$p)) {
			    $m = tep_module($p[1],$sec);
		    	if(isset($m)) $lst[$p[1]] = $m;
  			}
		}
		closedir($d);
		return $lst;
	}

	function tep_module($mod,$sec=NULL) {
		if (preg_match('/\W/',$mod.(isset($sec)?$sec:''))) return NULL;
		include_once(DIR_FS_COMMON.'modules/'.(isset($sec) ? $sec.'/' : '').$mod.'.php');
		if(class_exists($mod)) $m = new $mod;
		if(isset($m)) $m->mod_section=$sec;
		return $m;
	}

	function tep_read_module_conf($mod) {
		global $ModuleConfigCache;
		if(!isset($ModuleConfigCache[$mod])) {
			$ModuleConfigCache[$mod] = array();
		    $qry = tep_db_query("SELECT * FROM module_config WHERE conf_module='$mod'");
			while ($row=tep_db_fetch_array($qry)) {
				$ModuleConfigCache[$row['conf_module']][$row['conf_key']] = $row['conf_value'];
			}
		}
		return $ModuleConfigCache[$mod];
	}


class IXmodule {

	var $conf;

	function make($mod) { 
		return IXmodule::module($mod); 
	}
  
	function module($mod,$sec=NULL) {

		list($cls,$mkey)=explode(':',$mod);

		if(!class_exists($cls)) {

			if(!preg_match('/^\w+$/',$cls.$sec)) return NULL;

			if(!isset($sec)) {
        		if (preg_match('/^(\w+)_/',$cls,$clp)) $sec=$clp[1];
      		}

		@include_once(DIR_FS_COMMON.'modules/'.(isset($sec)?$sec.'/':'').$cls.'.php');

    	}

		if(class_exists($cls)) $m=new $cls;
		if(isset($m)) {
			$m->id=$GLOBALS['IXmoduleNextID']++;
			$m->mod_section=$sec;
			$m->key=$mkey;
		}
		
		return $m;
	}


	function getName() {
    	return 'Unknown Module';
  	}


	function getClass() {
		return get_class($this);
	}


	function getSortOrder() {
		return NULL;
	}


	function loadConf() {
    	global $ModuleConfigCache;
	    tep_read_module_conf(get_class($this));
    	$this->conf = &$ModuleConfigCache[get_class($this)];
		foreach($this->listConf() AS $k=>$lst) {
			if(!isset($this->conf[$k]) && isset($lst['default'])) $this->conf[$k] = $lst['default'];
		}
	}


	function loadExtra() {
	    global $ModuleExtraCache;
    	if(!isset($ModuleExtraCache[get_class($this)])) {
			$ModuleExtraCache[get_class($this)] = tep_db_read("SELECT * FROM module_extra WHERE module_class='".get_class($this)."'",array('module_extra_scope','module_extra_ref','module_extra_key'),'module_extra_value');
		} 

    	$this->extra_conf = &$ModuleExtraCache[get_class($this)];
	}

	function saveConf() {
		$mod = get_class($this);
	    $vals = array();
    	foreach ($this->getConf() AS $k=>$v) {
			$vals[] = "('$mod','".addslashes($k)."',".(isset($v) ? "'".addslashes($v)."'" : "NULL").",NOW())";
		}

	    tep_db_query("DELETE FROM module_config WHERE conf_module='$mod'");
	    if($vals) tep_db_query("INSERT INTO module_config (conf_module,conf_key,conf_value,date_modified) VALUES ".join(',',$vals));
	}

	
	function listConf() {
    	return Array();
	}


	function getConf($key=NULL) {
    	if(!isset($this->conf)) $this->loadConf();
	    if(!isset($key)) return $this->conf;
    	return isset($this->conf[$key])?$this->conf[$key]:NULL;
  	}

	function setConf($key,$val) {
    	if(!isset($this->conf)) $this->loadConf();
    	$this->conf[$key]=$val;
	}

	function getExtra($scope=NULL,$ref=NULL,$key=NULL) {
    	if(!isset($this->extra_conf)) $this->loadExtra();
    	$rf = &$this->extra_conf;
    	if(isset($rf) && isset($scope)) $rf=&$rf[$scope];
    if (isset($rf) && isset($ref)) $rf=&$rf[$ref];
    if (isset($rf) && isset($key)) $rf=&$rf[$key];
    return $rf;
  }

	function setExtra($scope,$ref,$key=NULL,$val=NULL) {
    
		if(!isset($this->extra_conf)) $this->loadExtra();
		if(!isset($this->extra_conf[$scope])) $this->extra_conf[$scope] = array();
		if(!isset($this->extra_conf[$scope][$ref])) $this->extra_conf[$scope][$ref] = array();
		if(isset($key)) {

			$this->extra_conf[$scope][$ref][$key] = $val;

			if(isset($val)) { 
				tep_db_query("REPLACE INTO module_extra (module_class,module_extra_scope,module_extra_ref,module_extra_key,module_extra_value) 
														VALUES ('".get_class($this)."','$scope','$ref','$key','".addslashes($val)."')");
			} else { 
				tep_db_query("DELETE FROM module_extra 
							  WHERE module_class='".get_class($this)."' 
							  AND module_extra_scope='$scope' 
							  AND module_extra_ref='$ref' 
							  AND module_extra_key='$key'
							");
			}

		} else {
	
			tep_db_query("DELETE FROM module_extra 
						  WHERE module_class='".get_class($this)."' 
						  AND module_extra_scope='$scope' 
						  AND module_extra_ref='$ref'
						");

			$this->extra_conf[$scope][$ref]=$val;

			if ($val) {
				$ins = array();
				foreach ($val AS $k=>$v) $ins[]="('".get_class($this)."','$scope','$ref','$k','".addslashes($v)."')";
				tep_db_query("REPLACE INTO module_extra (module_class,module_extra_scope,module_extra_ref,module_extra_key,module_extra_value) 
														VALUES ".join(',',$ins));
			}
		}
	}

	function validateConf($key,$var) {
		return NULL;
	}


	function checkConf() {
		if($this->isReady()) return true;
		foreach($this->getConf() AS $k=>$v) {
			if($this->validateConf($k,$v)) return false;
		}
		return true;
	}


	function isReady() {
    	return true;
	}


	function actionList() {
		return Array();
	}


	function isEqual(&$m) {
	    return isset($m) && is_subclass_of($m,'IXmodule') && $m->id==$this->id;
  	}

}


class IXmoduleSet {

	var $key = NULL;
	var $mods = NULL;
  
	function getName() {
		return 'Unknown Module Set';
	}


	function listModules($ac=false,$force=false) {
		if($force || (!$ac && !isset($this->mods)) || !isset($this->activemods)) {
			if(!$ac) $this->mods = array();
			$this->activemods = array();
			$key="";
			$qry = tep_db_query("SELECT * FROM module_sets 
								 WHERE mods_id='".get_class($this)."' 
								 AND mods_key='".addslashes($key)."'".($ac?" AND mods_enabled>0":"")." 
								 ORDER BY sort_order
								");

			while ($row=tep_db_fetch_array($qry)) {
				if(!$ac) $this->mods[$row['mods_module']] = $row;
				if($row['mods_enabled']>0) $this->activemods[$row['mods_module']] = $row;
			}
		}

		return ($ac) ? $this->activemods : $this->mods;
  }

	
	function getModules() {
		
		$lst = array();

		foreach ($this->listModules(true) AS $k=>$minfo){
			$lst[$k]=tep_module($k,$minfo['mods_section']);
		}
		
		return $lst;
	}


	function getFirstModule() {
		foreach ($this->listModules(true) AS $k=>$minfo) return tep_module($k,$minfo['mods_section']);
		return NULL;
	}

	function getAllModules() {
		return tep_list_modules(get_class($this));
	}

	function enableModule(&$mod,$srt=NULL) {
		if(!$mod->checkConf()) return false;
		if(!isset($srt)) {
			$qry = tep_db_query("SELECT sort_order 
								 FROM module_sets 
								 WHERE mods_id='".get_class($this)."' 
								 AND mods_key='".addslashes($this->key)."' 
								 AND mods_module='".get_class($mod)."'
								");
		$row = tep_db_fetch_array($qry);
		$srt = $row['sort_order'];
		}

		$srt+=0;
		tep_db_query("REPLACE INTO module_sets (mods_id,mods_key,mods_module,mods_section,sort_order,mods_enabled) VALUES ('".get_class($this)."','".addslashes($this->key)."','".get_class($mod)."','".addslashes($mod->mod_section)."','$srt',1)");

		$this->listModules(false,true);
	}


	function disableModule(&$mod, $wipe=false) {

		if($wipe) { 
			tep_db_query("DELETE FROM module_sets 
						  WHERE mods_id='".get_class($this)."' 
						  AND mods_key='".addslashes($this->key)."' 
						  AND mods_module='".get_class($mod)."'
						 ");
		} else {
			tep_db_query("UPDATE module_sets 
						  SET mods_enabled = 0 
						  WHERE mods_id='".get_class($this)."' 
						  AND mods_key='".addslashes($this->key)."' 
						  AND mods_module='".get_class($mod)."'
						");
		}

		$this->listModules(false,true);
	}

}
?>