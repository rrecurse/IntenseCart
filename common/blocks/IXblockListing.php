<?php

// ############################################
/*  Copyright (c) 2006 - 2014 IntenseCart eCommerce  */
// ############################################


class IXblockListing extends IXblock {

	function jsObjectName() {
		return 'theListing_'.$this->makeID();
	}
  
	function setListingElement(&$row) {
    	$this->listing_row=&$row;
	}

	function getListingCount() {
    	$rs=0;
	    foreach($this->getListingTypesCount() AS $ct) {
			$rs += $ct;
		}
    	return $rs;
	}
  
	function getListingTypesCount() {
    	$ct = array();
	    foreach ($this->getListingRows('') AS $row) $ct[$row['item_type']]++;
    	return $ct;
	}
  
	function getListingRows($sort,$start=0,$count=NULL) {
    	return Array();
	}
  
	function getPageListingRows() {

    	if (!isset($this->pageListing)) {
			$this->pageListing = $this->getListingRows($this->sortMode,( $this->currPage ? ($this->currPage - 1) * $this->numPerPage : 0) );

		}

	$this->pageListingIdx = 0;
	return $this->pageListing;
	}
  
	function getSortModes() {
    	return array('' => array('title' => 'Default'),);
	}
  
  function getSortDir($mod,$dir) {
    switch ($mod) {
      case 'random': return '-'.rand();
      default: return (isset($dir) && $dir=='')?'-':'';
    }
  }

	// # New Responsive parsing of product divs
	function renderResponsive($lst,&$body,$cols=NULL,$max=NULL) {
    	$idx = 0;
		$cols = (!empty($cols) ? $cols : (int)$_GET['cols']);

		foreach ($lst AS $cell) {
//error_log(print_r($max,1));
			if ($max && $idx >= $max) break;

			if (!($cols ? ($idx%$cols) : $idx)) echo '';

			echo '<div id="'.$this->jsObjectName().'_'.$idx.'" class="col">';

			$this->setListingElement($cell);
			$this->renderBody($body);

			$idx++;

			echo '</div>';

			if ($cols && !($idx%$cols)) echo '';
		}

		if($idx && (!$cols || $idx%$cols)) {
			if($cols) for (;$idx%$cols;$idx++) echo '';
			echo '';
		}

	}

	// # Legacy - render the table
	function renderListing($lst,&$body,$cols=NULL,$max=NULL) {
    	$idx=0;

		if(isset($_GET['cols'])) {
			$cols = is_numeric($_GET['cols']) ? (int)$_GET['cols'] : 3;	
		}

		echo '<table border="0" cellspacing="0" cellpadding="0">';

		foreach ($lst AS $cell) {
	      if ($max && $idx>=$max) break;
    	  if (!($cols?($idx%$cols):$idx)) echo '<tr>';
	      echo '<td id="'.$this->jsObjectName().'_'.$idx.'">';
    	  $this->setListingElement($cell);
	      $this->renderBody($body);
    	  $idx++;
	      echo '</td>';
    	  if ($cols && !($idx%$cols)) echo '</tr>';
	    }

	    if ($idx && (!$cols || $idx%$cols)) {
    	  if ($cols) for (;$idx%$cols;$idx++) echo '<td>&nbsp;</td>';
	      echo '</tr>';
    	}

		echo '</table>';

	}
  
  function pageUrl($page=NULL,$sort=NULL) {
    $args = array();
	// # added query string to URL where needed.
	$url_query = parse_url(htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES), PHP_URL_QUERY);

    if (isset($page)) $args['page']=($page==1?NULL:$page);
    if (isset($sort)) $args['sort']=($this->args['sort']==$sort?NULL:$sort);
    return $this->root->pageUrl($args).(!empty($url_query) ? '?'.$url_query : '');
  }
  
  function initListing() {

	global $numPages;

    $this->numRecs = $this->getListingCount();

    $this->numPerPage = $this->args['items_per_page'];

	// # URL var pass override to increase from URL bar
    
	if(is_numeric($_GET['items_per_page'])) $this->numPerPage = (int)$_GET['items_per_page'];

    $this->currPage = $this->numPerPage ? $this->root->getPageArg('page',1) : NULL;

	$numRecs = (is_array($this->numRecs)) ? array_shift($this->numRecs) : $this->numRecs;

	$numPerPage = ($this->numPerPage > 0 ? $this->numPerPage : 99);

	$this->numPages = ($numRecs > 0 ? ceil($numRecs / $numPerPage) : 1);

	$GLOBALS['numPages'] = $this->numPages;

    $this->sortMode = $this->root->getPageArg('sort',$this->args['sort']);

    $mds=$this->getSortModes();

    if(!isset($mds[preg_replace('/-.*/','',$this->sortMode)])) list($this->sortMode)=array_keys($mds);
	
	}
  
  function render(&$body) {
    $this->initListing();
    $this->renderBody($body);
  }
  
  function preRenderSection($sec,&$body,$args) {
    $ptype='';
    if (isset($args['type'])) $ptype = explode(';',$args['type']);
    switch ($sec) {
      case 'content':
		(!empty($this->numRecs)) ? $this->numRecs : $this->numRecs = 0;
      case 'table':
        if ($ptype) {
	  $cts=$this->getListingTypesCount();
	  foreach ($ptype AS $t) if ($cts[$t]>0) return true;
	  return false;
	}
	  return $this->numRecs>0;

      case 'nocontent':
        if ($ptype) {
	  $cts=$this->getListingTypesCount();
	 foreach ($ptype AS $t) if ($cts[$t] > 0) return false;
	 return true;
        } 

		 return $this->numRecs <=0;

      case 'item':
        $idx=NULL;
		$shft=0;
		$finc=false;
        if (isset($args['index'])) {
			  if (preg_match('/^[+\\-]/',$args['index'])) $shft=$args['index']+0;
		  else $idx=$args['index'];
		}

        if (!isset($idx)) {
          $idx=$this->pageListingIdx+$shft;
		  $finc=true;
		}

		if (!isset($idx) || !isset($this->pageListing[$idx])) {
		  if ($finc) $this->pageListingIdx++;
		  return false;
		}

		if ($ptype && !in_array($this->pageListing[$idx]['item_type'],$ptype)) return false;
		if ($finc) $this->pageListingIdx++;
        $this->setListingElement($this->pageListing[$idx]);
        return true;
    	default: return true;
    }
  }

// internal
  function setListingItemType($type) {
    $prev=$this->listingItemTypes;
    $new=Array();
    if (isset($type)) foreach (explode(';',$type) AS $t) if ($t!='') $new[]=$t;
    if ($this->listingItemTypes) {
      if ($new) foreach ($this->listingItemTypes AS $idx=>$t) if (!in_array($t,$new)) delete($this->listingItemTypes[$idx]);
    } else $this->listingItemTypes=$new;
    return $prev;
  }

// internal
  function chkListingItemType(&$row) {
    if (!$this->listingItemTypes) return true;
    return in_array($row['item_type'],$this->listingItemTypes);
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'table':
        $this->renderListing($this->getListingRows($this->sortMode,($this->currPage?($this->currPage-1)*$this->numPerPage:0),($this->currPage?$this->numPerPage:NULL)),$body,$args['cols'],$args['max']);
//        $this->renderListing($this->getListingRows($this->sortMode,($this->currPage-1)*$this->numPerPage,$this->numPerPage),$body,$args['cols'],$args['max']);
	break;
      case 'responsive':
        $this->renderResponsive($this->getListingRows($this->sortMode,($this->currPage?($this->currPage-1)*$this->numPerPage:0),($this->currPage?$this->numPerPage:NULL)),$body,$args['cols'],$args['max']);
//error_log(print_r($this->numPerPage,1));

	break;

      case 'list': case 'listing':
        $max=max(0,$args['max']+0);
        $this->getPageListingRows();
	if (!$args['empty'] && $this->pageListingIdx>=count($this->pageListing)) {
	  break;
	}
	$ct=0;
	while ($max || $this->pageListingIdx<count($this->pageListing)) {
	  $idx=$this->pageListingIdx;
	  if ($max && $ct++>=$max) break;
	  $this->renderBody($body);
	  if ($idx==$this->pageListingIdx) {
	    $this->pageListingIdx++;
	    $ct--;
	  }
	}
	break;
      case 'item':
// index is adjusted in preRenderSection()
//        $idx=$this->pageListingIdx++;
//        $this->setListingElement($this->pageListing[$idx]);
        $this->renderBody($body);
        break;
      default: $this->renderBody($body);
    }
  }
}


class IXblockListingSQL extends IXblockListing {
  function getListingSQL() {
    return NULL;
  }
  
  function getListingCount() {

	// # scrub and replace select and GROUP BY sql functions for pagination
	// # replace everything between select and from with SELECT COUNT(0)....
    $sql = preg_replace('/select\s(.*?)\sfrom\s/i','SELECT COUNT(0) AS ct FROM ',$this->getListingSQL());
	// # continue to scrub for GROUP BY using case-insensitive preg_replace
    $sql = preg_replace('/group by\s(.*?)\s/i','',$sql);

    return tep_db_read($sql,NULL,'ct');
  }
  
  function getListingTypesCount() {
    return array('' => $this->getListingCount());
  }
  
  function getListingRows($sort=NULL,$start=0,$count=NULL) {

//error_log(print_r($count,1));

    $sql=$this->getListingSQL();
    $srtsql=$this->getSortSQL($sort);
    if ($srtsql) $sql.=' ORDER BY '.$srtsql;

	// # default start to record 0 if $start is passed as a negative value
	$start = ($start < 0 ? '0' : $start);

	// # only invoke LIMIT if count is more then 0
    if($count > 0) $sql.=' LIMIT '. $start . ', ' . $count;

    return tep_db_read($sql,array(NULL));
  }

  function getSortSQL($sort) {

    preg_match('/(.*?)(-(.*))?$/',$sort,$srtp);

    $mds=$this->getSortModes();

    $sql = sprintf($mds[$srtp[1]]['sql'],$srtp[3]);

    if ($srtp[2]=='-') $sql=preg_replace('/\s*,/',' DESC,',$sql).' DESC';

    return $sql;
  }
}

?>
