<?php
 require_once '/usr/share/IXcore/common/modules/searchengine/ixsearchengine.php';
 class searchengine_msn extends IXsearchengine {
	function searchengine_msn() {
		parent::IXsearchengine();
		$this->url = "http://search.msn.com/results.aspx";
	}
  	function getName() {
    	return "MSN.com";
  	}
	function reset_params($keyword, $page = 1) {
		$this->params = array(
			'q'			=> $keyword,
			'first'		=> ($page - 1)*10+1,
			'FORM'		=> 'PERE',
		);
	}
    function get_results_count($html, $max) {
		$result = 0;
		preg_match("/of (.*?) results/is", $html, $matches);
		if (!empty($matches[1])) {
			$result = strip_tags($matches[1]);
			$result = str_replace("&nbsp;", "", $result);
			$result = str_replace(",", "", $result);
			if ($max < (int)$result) $result = $max;
		};
        return (int)$result;
    }
    function parse_results($html) {
		$result = array();
		preg_match_all("/<li><h3><a href=\"([^\"]*)\"/is", $html, $matches);
		if (!empty($matches[1])) {
			$result = $matches[1];
		};
		return $result;
    }
 }
?>
