<?php
// # Sort Order
class blk_listing_sort extends IXblock {

  function preRender(&$body) {

    $mds = $this->listing->getSortModes();

 	if ($this->args['mode']) {

		$this->sortModes = array();

		foreach(preg_split('/;/',$this->args['mode']) AS $m) {
			if (isset($mds[$m])) {
				$this->sortModes[$m] = $mds[$m];
			}
		}

    } else {
		$this->sortModes = $mds;
	}

    preg_match('/^(.*?)(-.*)?$/',$this->listing->sortMode,$srtp);

    list(,$this->sortMode,$this->sortDir) = $srtp;

    $this->sortDir.='';

    return !!$this->sortModes;

  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'sort_up': return $this->lstDir == '';
      case 'sort_down': return $this->lstDir == '-';
      case 'current': return $this->lstSort == $this->sortMode;
      case 'noncurrent': return $this->lstSort != $this->sortMode;
      case 'separator': return $this->sepFlag;
      default: return true;
    }
  }

  function renderSection($sec,&$body,$args) {

    switch ($sec) {

      case 'sort':

        $sidx=0;

        foreach($this->sortModes AS $this->lstSort => $srt) {

		  $this->lstDir = $this->listing->getSortDir($this->lstSort,($this->lstSort == $this->sortMode ? $this->sortDir : NULL));
		  $this->sepFlag=++$sidx<sizeof($this->sortModes);
		  $this->renderBody($body);

		}

		return;
		
		default: break;
    }

    $this->renderBody($body);
  }
  
	function getVar($var,$args) {
		switch ($var) {
			case 'sort_name': return $this->sortModes[$this->lstSort]['title'];
			case 'sort_href': return $this->listing->pageUrl($this->listing->currPage,$this->lstSort.$this->lstDir);
    	}

    return NULL;
  }

  function requireContext() {
    return Array('root','listing');
  }
}
?>
