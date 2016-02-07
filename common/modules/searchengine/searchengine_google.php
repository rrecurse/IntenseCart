<?php
 require_once '/usr/share/IXcore/common/modules/searchengine/ixsearchengine.php';
 class searchengine_google extends IXsearchengine {
	function searchengine_google() {
		parent::IXsearchengine();
		$this->url = "http://www.google.com/search";
	}
  	function getName() {
    	return "Google.com";
  	}
	function reset_params($keyword, $page = 1) {
		$this->params = array(
			'hl'		=> 'en',
			'filter'	=> '0',
			'btnG'		=> 'Google Search',
			'ie'		=> 'ISO-8859-1',
			'q'			=> $keyword,
			'start'		=> ($page - 1)*10
		);
	}
    function get_results_count($html, $max) {
		$result = 0;
		preg_match("/of about (.*?) for/is", $html, $matches);
		if (!empty($matches[1])) {
			$result = strip_tags($matches[1]);
			$result = str_replace("&nbsp;", "", $result);
			if ($max < (int)$result) $result = $max;
		};
        return (int)$result;
    }
    function parse_results($html) {
		$result = array();
		preg_match_all("/<h3 class=r><a href=\"([^\"]+)\" class=l/is", $html, $matches);
		if (!empty($matches[1])) {
			$result = $matches[1];
		};
		return $result;
    }
 }
?>
