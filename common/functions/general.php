<?php

	global $cusGroupCache;


	function tep_read_config() {
		if(@include_once(FILENAME_CONFIG_CACHE)) return true;
		$tmpcache = FILENAME_CONFIG_CACHE.'-'.posix_getpid();

		$cache = fopen($tmpcache,'w');
		if($cache) fwrite($cache,"<?\n// Configuration cache - do not change manually\n\n");
		$configuration_query = tep_db_query("SELECT ix.configuration_key as cfgKey, 
											IFNULL(c.configuration_value, ix.configuration_value) AS cfgValue 
											FROM IXcore.configuration ix 
											LEFT JOIN ".TABLE_CONFIGURATION." c ON ix.configuration_key = c.configuration_key 
											WHERE service_type IS NULL 
											".(SITE_SERVICE_TYPE ? "OR service_type = '".SITE_SERVICE_TYPE."'" : ""));
		while ($configuration = tep_db_fetch_array($configuration_query)) {
			define($configuration['cfgKey'], $configuration['cfgValue']);
			if($cache) fwrite($cache,"  define('".$configuration['cfgKey']."',\"".addslashes($configuration['cfgValue'])."\");\n");
  		}
		
		tep_db_free_result($configuration_query);

		if($cache) {
			fwrite($cache,"\n?>");
			if(fclose($cache)) return rename($tmpcache,FILENAME_CONFIG_CACHE);
		}

		return false;
	}


	function tep_get_customer_groups() {
		global $cusGroupCache;
		if(!isset($cusGroupCache)) {
			$cusGroupCache = array(0=>'[Retail]');
			$customers_group_query = tep_db_query("SELECT customers_group_id, customers_group_name 
												   FROM " . TABLE_CUSTOMERS_GROUPS . " 
												   WHERE customers_group_id != '0' 
												   ORDER BY customers_group_id
												  ");
			while($customers_group = tep_db_fetch_array($customers_group_query)) {
				$cusGroupCache[$customers_group['customers_group_id']] = $customers_group['customers_group_name'];
			}

			tep_db_free_result($customers_group_query);
		}


		return $cusGroupCache;

	}

	function tep_get_category_info($cat=0,$class='product_default') {

		global $categories_data_cache,$languages_id;
		$cat+=0;
		
		if(!isset($categories_data_cache)) {

			$categories_data_cache = array();
			$cat_query = tep_db_query("SELECT c.categories_id, c.parent_id, c.categories_status, cd.categories_name 
									   FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd 
									   WHERE c.categories_id = cd.categories_id 
									   AND cd.language_id = '" . (int)$languages_id . "'".($class?" AND c.products_class='$class'":'')." 
									   ORDER BY c.sort_order, cd.categories_name
									  ");
			while ($cat_row=tep_db_fetch_array($cat_query)) {
	
		        if(!isset($categories_data_cache[$cat_row['categories_id']])) { 
					$categories_data_cache[$cat_row['categories_id']] = array(); 
				} 
	
    		    if(!isset($categories_data_cache[$cat_row['parent_id']])) { 
					$categories_data_cache[$cat_row['parent_id']] = array();
				}
	
				$catdata = &$categories_data_cache[$cat_row['categories_id']];
				$catparent = &$categories_data_cache[$cat_row['parent_id']];
				$catdata['id'] = $cat_row['categories_id'];
				$catdata['name'] = $cat_row['categories_name'];
				$catdata['status'] = $cat_row['categories_status'];
				$catdata['parent'] = &$catparent;
				if(!isset($catparent['tree_all'])) $catparent['tree_all'] = array();
				$catparent['tree_all'][] = &$catdata;
	
				if ($catdata['status']) {
					if(!isset($catparent['tree'])) $catparent['tree'] = array();
					$catparent['tree'][] = &$catdata;
				}
			}

			tep_db_free_result($cat_query);
    	}

		return $categories_data_cache[$cat];
	}

$ProductsCacheExtra = array();

	function tep_get_product_extra($pid,$field=NULL) {
	
		global $ProductsCacheExtra;

		if(!isset($ProductsCacheExtra[$pid])) {
			$ProductsCacheExtra[$pid] = array();
			$qry = tep_db_query("SELECT * FROM products_extra WHERE products_id='".addslashes($pid)."'");

			while ($row = tep_db_fetch_array($qry)) {
				$ProductsCacheExtra[$pid][$row['products_extra_key']] = $row['products_extra_value'];
			}

			tep_db_free_result($qry);
		}

		return isset($field)?$ProductsCacheExtra[$pid][$field]:$ProductsCacheExtra[$pid];
	}
  
	function tep_db_read($sql,$key=NULL,$val=NULL) {
		return IXdb::read($sql,$key,$val);
	}
  

$GLOBALS['IXdbLink'] = &$GLOBALS['db_link'];

class IXdb {
	static function init($host,$user,$pass,$db) {

		$lnk = mysql_connect($host,$user,$pass);
		if($lnk) mysql_select_db($db,$lnk);
		return $GLOBALS['IXdbLink'] = $lnk;
	}


	static function query($sql,$safe=false) {

		if(isset($GLOBALS['dbQueryDump'])) $GLOBALS['dbQueryDump'][] = $query;
		
		$rs = mysql_query($sql,$GLOBALS['IXdbLink']);
	
	    if(!$safe && !$rs) IXdb::error($sql);

		if($rs === true) {
			$rs = mysql_affected_rows($GLOBALS['IXdbLink']);
			if(!$rs) $rs = '0e0';
		}

		return $rs;
	}

	static function fetch($res) {
		if($res) return mysql_fetch_assoc($res);
	}

	static function read($sql,$key=NULL,$val=NULL) {

		$qry = (is_string($sql)) ? IXdb::query($sql) : $sql;
		$rs = array();

		while($row = IXdb::fetch($qry)) {
			if(!isset($val)) {
				$v = $row;
			} elseif(is_array($val)) {
				$v = array();
				foreach ($val AS $k=>$f) $v[$k] = $row[$f];
			} else {
				$v = $row[$val];
			}
			
			if(!isset($key)) {
				$rs = $v;
			} elseif(is_array($key)) {
				$lst = sizeof($key)-1;
				$p = &$rs;
				for($i=0; $i<$lst; $i++) {
					$k = (isset($key[$i])) ? $row[$key[$i]] : sizeof($p);
					if(!isset($p[$k])) $p[$k] = array();
					$p = &$p[$k];
				}

			$p[(isset($key[$lst]) ? $row[$key[$lst]] : sizeof($p))] = $v;

			} else { 
				$rs[$row[$key]] = $v;
			}
		} // # END while
	
		if($qry) mysql_free_result($qry);

		return $rs;
	}


	static function query_count($sql,&$ct) {

		$qry = IXdb::query(preg_replace('|^SELECT\s+|i','SELECT SQL_CALC_FOUND_ROWS ',$sql));
		$ct = IXdb::read("SELECT FOUND_ROWS() AS ct",NULL,'ct');
    return $qry;
  }
 static function error($sql) {
    die(mysql_error() . ' - <font color="#000000"><b>' . mysql_errno($GLOBALS['IXdbLink']) . ' - ' . mysql_error($GLOBALS['IXdbLink']) . '<br><br>' . $sql . '<br><br></b></font>');
  }
 static function store($fn,$table,$data,$cond=NULL) {
    $set=Array();
    foreach ($data AS $f=>$v) {
      if (is_array($v)) {
        if (isset($v['sql'])) $set[]="$f=".$v['sql'];
      } else $set[] = $f."=".(isset($v)?"'".addslashes($v)."'":'NULL');
    }
    switch (strtolower($fn)) {
      case 'insert':
      case 'replace':
        IXdb::query("$fn INTO $table SET ".join(',',$set));
        return IXdb::insert_id();
      case 'update':
        if (!isset($cond)) break;
        return IXdb::query("UPDATE $table SET ".join(',',$set)." WHERE $cond");
      default: break;
    }
    return NULL;
  }
  function num_rows($rs) {
    if (is_string($rs)) $rs=IXdb::query($rs,true);
    return mysql_num_rows($rs);
  }
  function insert_id() {
    return mysql_insert_id($GLOBALS['IXdbLink']);
  }
  function quote($s) {
    if (is_array($s)) {
      $vals=Array();
      foreach ($s AS $v) $vals[]=IXdb::quote($v);
      return '('.join($vals).')';
    } else if (!isset($s)) return 'NULL';
    else if (is_numeric($s)) return $s;
    else return "'".mysql_real_escape_string($s)."'";
  }
}
  
  function tep_db_query_count($sql,&$ct) {
    $qry=tep_db_query(preg_replace('|^SELECT\s+|i','SELECT SQL_CALC_FOUND_ROWS ',$sql));
    $ct=tep_db_read("SELECT FOUND_ROWS() AS ct",NULL,'ct');
    return $qry;
  }
  
  function tep_display_field($name,$val,$optns) {
    $type=isset($optns['type'])?$optns['type']:'text';
    
  }
  
  
	class IXimage {

		function src($img, $wd=0, $ht=0) {
			if(!$wd && !$ht) {
				return IX_URI_IMAGES.$img;
			}

			$src = sprintf("%dx%d/%s",$wd,$ht,$img);

	// # ChristianF: Trying to get rid of some of the warnings here.
	//if (is_file (IX_PATH_IMAGECACHE)) {
		$st = @lstat(IX_PATH_IMAGECACHE.$src);
	//} else {
		//$st = false;
	//}

	//if (is_file (IX_PATH_IMAGES.$img)) {
		$st0 = @lstat(IX_PATH_IMAGES.$img);
	//} else {
		//$st0 = false;
	//}

	if($st0 && (!$st || $st0[9]>$st[9] || $st0[10]>$st[10])) {
		@unlink(IX_PATH_IMAGECACHE.$src);
		IXfile::makePath(IX_PATH_IMAGECACHE,$src);
		//fclose(fopen(IX_PATH_IMAGECACHE.$src,'w'));
		ImageResizer($img, $wd, $ht);
	} else if (!$st0) @unlink(IX_PATH_IMAGECACHE.$src);

    return IX_URI_IMAGECACHE.$src;
  }

  function srcFrag($img,$wd,$ht,$x1,$y1,$x2,$y2) {
  }


  function tag($img,$alt=NULL,$wd=0,$ht=0,$extra=NULL) {
    $src=IXimage::src($img,$wd,$ht);
    if (!isset($alt)) $alt = $img;
    $tag='<img src="'.htmlspecialchars($src).'" alt="'.htmlspecialchars($alt).'" border="0"';
    if ($wd) $tag.=sprintf(' width="%d"',$wd);
    if ($ht) $tag.=sprintf(' height="%d"',$ht);
    if ($extra) $tag.=' '.$extra;
    $tag.='>';
    return $tag;
  }
  function info($img) {
  }
}


class IXaddress {

  function IXaddress($addr, $orders_id='') {

	if(!empty($orders_id)) {
		$order = new order($orders_id);
	}

    if(!empty($addr['name'])) {

      preg_match('/^\s*((.*?)\s+)?(\S*)\s*$/',$addr['name'],$np);

      $this->first = $np[2];
      $this->last = $np[3];

    } else {

      $this->first = $addr['first_name'];
      $this->last = $addr['last_name'];
    }

	//if($addr['country'] == 'United States' && strlen($addr['postcode']) > 5) { 

	 //$this->postcode = substr($addr['postcode'], 0, 5);

	//} else { 

	  $this->postcode = $addr['postcode'];

	//}

    $this->address = $addr['street_address'];
    $this->address2 = $addr['suburb'];
    $this->city = $addr['city'];

    $this->zone = NULL;
    $this->zone_txt = $addr['state'];
    $this->country = NULL;
    $this->country_txt = $addr['country'];
    $this->phone = (isset($addr['telephone']) ? $addr['telephone'] : $order->customer['telephone']);
    $this->fax = $addr['fax'];
    $this->email = (isset($addr['email_address']) ? $addr['email_address'] : $order->customer['email_address']);
    $this->company = (isset($addr['company']) ? $addr['company'] : NULL);
  }

  function load($aid) {

    $row = IXdb::read("SELECT * FROM address_book WHERE address_book_id = '$aid'");

    if ($row) {

		//if($row['entry_country_id'] == '223' && strlen($row['entry_postcode']) > 5) { 

		//  $entry_postcode = substr($row['entry_postcode'], 0, 5);

		//} else { 
			
			$entry_postcode = $row['entry_postcode'];
			
		//}

      $addr = new IXaddress(array('first_name'=>$row['entry_firstname'],
								  'last_name'=>$row['entry_lastname'],
								  'company'=>$row['entry_company'],
								  'address'=>$row['entry_street_address'],
								  'address2'=>$row['entry_suburb'],
								  'city'=>$row['entry_city'],
								  'state'=>($row['entry_zone_id']?$row['entry_zone_id']:$row['entry_state']),
								  'postcode'=>$entry_postcode,
								  'country'=>$row['entry_country_id']
     							)
							);

      $addr->addrid = $aid;
      return $addr;
    }
    return NULL;
  }


  function save() {
    $adata = array(
      'entry_firstname'=>$this->getFirstName(),
      'entry_lastname'=>$this->getLastName(),
      'entry_company'=>$this->getCompany(),
      'entry_street_address'=>$this->getAddress(),
      'entry_suburb'=>$this->getAddress2(),
      'entry_city'=>$this->getCity(),
      'entry_postcode'=>$this->getPostCode(),
      'entry_state'=>$this->getZoneName(),
      'entry_zone_id'=>$this->getZoneID(),
      'entry_country_id'=>$this->getCountryID(),
    );

    if ($this->addrid) {
      IXdb::store('update','address_book',$adata,"address_book_id='{$this->addrid}'");
    } else {
      $this->addrid=IXdb::store('insert','address_book',$adata);
    }

    IXdb::query("DELETE FROM address_geo_coords WHERE address_book_id='{$this->addrid}'");
    return $this->addrid;
  }

  function populateFrom($addr) {
    if (get_class($addr)!=get_class($this)) return NULL;
    foreach (Array('first','last','address','address2','city','postcode','phone','fax','email') AS $fld) if (!$this->$fld) $this->$fld = $addr->$fld;
    if (!$this->zone_txt && (!$this->zone || !$this->zone['name'])) {
      $this->zone_txt = $addr->zone_txt;
      $this->zone = $addr->zone;
    }
    if (!$this->country_txt && (!$this->country || !$this->country['name'])) {
      $this->country_txt = $addr->country_txt;
      $this->country = $addr->country;
    }
  }

  function loadCountryInfo() {
    if (isset($this->country)) return;
    $c=addslashes($this->country_txt);
    $this->country=IXdb::read("SELECT * FROM IXcore.countries WHERE ".(is_numeric($c)?"countries_id='$c'":"countries_iso_code_2='$c' OR countries_name='$c'"),NULL,Array('id'=>'countries_id','code'=>'countries_iso_code_2','name'=>'countries_name','format'=>'address_format_id'));
    if (!$this->country) $this->country=Array('name'=>'','code'=>'');
  }

  function loadZoneInfo() {

    if (isset($this->zone)) return;

    $cid = $this->getCountryID();

    $z = addslashes($this->zone_txt);

    $this->zone = IXdb::read("SELECT * FROM IXcore.zones WHERE zone_country_id='$cid' AND (".(is_numeric($z)?"zone_id='$z'":"zone_code='$z' OR zone_name='$z'").")",NULL,Array('id'=>'zone_id','code'=>'zone_code','name'=>'zone_name'));

    if (!$this->zone) {
		$this->zone = array('name' => $this->zone_txt, 
							'code' => $this->zone_txt
							);
	}
  }

  function getFullName() {
    return $this->first.($this->first && $this->last ? ' ':'').$this->last;
  }

  function getFirstName() {
    return $this->first;
  }

  function getLastName() {
    return $this->last;
  }

  function getCompany($force=false) {
    return ($force || !empty($this->company) ? $this->company : '');
  }

  function getAddress() {
    return $this->address.'';
  }

  function getAddress2() {
    return $this->address2;
  }

  function getCity() {
    return $this->city.'';
  }

  function getPostCode() {
    return $this->postcode.'';
  }

  function getCountryName() {
    $this->loadCountryInfo();
    return $this->country['name'];
  }

  function getCountryCode() {
    $this->loadCountryInfo();
    return $this->country['code'];
  }

  function getCountryID() {
    $this->loadCountryInfo();
    return $this->country['id'];
  }

  function getZoneName() {
    $this->loadZoneInfo();
    return $this->zone['name'];
  }

  function getZoneCode() {
    $this->loadZoneInfo();
    return $this->zone['code'];
  }

  function getZoneID() {
    $this->loadZoneInfo();
    return $this->zone['id'];
  }

  function getPhone() {
    return $this->phone;
  }

  function getFax() {
    return $this->fax;
  }

  function getEmail() {
    return $this->email;
  }

  function matchGeoZone($geoid) {
    if (!$geoid) return true;
    $cid = $this->getCountryID();
    $zid = $this->getZoneID();
    return IXdb::num_rows(IXdb::query("SELECT * FROM zones_to_geo_zones WHERE geo_zone_id='$geoid' AND zone_country_id='$cid' AND (zone_id IS NULL OR zone_id=0 OR zone_id='$zid')"));
  }

  function listGeoZones() {
    return IXdb::read("SELECT * FROM geo_zones ORDER BY geo_zone_id",'geo_zone_id','geo_zone_name');
  }

}

class IXfile {

  function makePath($base,$path,$m=0777) {

//error_log(print_r('base - '. $base,1));
//error_log(print_r('path - '. $path,1));
//error_log(print_r('get_current_user - '. get_current_user(),1));

    if ($path=='') return false;

    $dp='';

    if(preg_match('|^(.+)/|', $path, $pp)) {
		$dp = $pp[1];
	}

    if (is_dir($base.$dp) || @mkdir($base.$dp,$m)) return true;

    return IXfile::makePath($base,$dp,$m);
  }
}


?>
