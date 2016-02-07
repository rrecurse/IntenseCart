<?
// Page Navigation
class blk_listing_pagenav extends IXblock {

  function preRender(&$body) {
    return $this->listing->numPages>0;
  }

  function render(&$body) {
    $this->numPages=$this->listing->numPages;
    $this->currPage=$this->listing->currPage;
    $this->startPage=1;
    $this->endPage=$this->numPages;
    $this->navMax=max(0,$this->args['max']+0);
    $this->navPad=$this->args['pad']+0;
    $this->renderBody($body);
  }

  function preRenderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'current': return $this->currPage==$this->lstPage;
      case 'noncurrent': return $this->currPage!=$this->lstPage;
      case 'separator': return $this->sepFlag;
      case 'left': return $this->navMax && $this->numPages>$this->navMax && $this->currPage>$this->navMax-2*$this->navPad;
      case 'right': return $this->navMax && $this->numPages>$this->navMax && $this->currPage<=$this->numPages-$this->navMax+2*$this->navPad;
      case 'showall': return $this->numPages>1;
      default: return true;
    }
  }

  function renderSection($sec,&$body,$args) {
    switch ($sec) {
      case 'left':
        $this->startPage=1;
        $this->endPage=$this->navPad;
	break;
      case 'right':

        $this->startPage=$this->numPages-$this->navPad+1;
        $this->endPage=$this->numPages;
	break;

      case 'main':

        if (!$this->navMax || $this->currPage<=$this->navMax-2*$this->navPad){
	  $this->startPage=1;
	  $this->endPage=($this->navMax && $this->numPages>$this->navMax)?$this->navMax-$this->navPad:$this->numPages;
	} else if ($this->currPage>$this->numPages-$this->navMax+2*$this->navPad) {
	  $this->startPage=$this->numPages-$this->navMax+$this->navPad+1;
	  $this->endPage=$this->numPages;
	} else {
	  $this->startPage = ($this->startPage < 0 ? 1 : $this->currPage - floor($this->navMax/2)+$this->navPad);
	  $this->endPage=$this->startPage+$this->navMax-2*$this->navPad-1;
	}
	break;

	case 'page':

		$this->startPage = 1;
		for ($this->lstPage=$this->startPage; $this->lstPage <= $this->endPage;$this->lstPage++) {
			$this->sepFlag=$this->lstPage<$this->endPage;
			$this->renderBody($body);
		}

		return;

	case 'showall':
		$this->lstPage=0;
		break;
		default: break;
	}

	$this->renderBody($body);
}
  
  function getVar($var,$args) {
    switch ($var) {
      case 'page_number': return $this->lstPage;
      case 'page_href': return $this->listing->pageUrl($this->lstPage,$this->listing->sortMode);
      case 'showall_href': return $this->listing->pageUrl(0,$this->listing->sortMode);
    }
    return NULL;
  }

  function requireContext() {
    return Array('root','listing');
  }
}
?>