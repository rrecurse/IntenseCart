<?php
 require_once '/usr/share/IXcore/common/modules/searchengine/ixsearchengine.php';
class searchengine_yahoo extends IXsearchengine {

	function searchengine_yahoo() {
		parent::IXsearchengine();
		$this->url = "http://search.yahoo.com/search";
	}
  	function getName() {
    	return "Yahoo.com";
  	}
	function reset_params($keyword, $page = 1) {
		$this->params = array(
//			'fr'		=> 'yfp-t-301',
			'fr'		=> 'yfp-t-501',
			'cop'		=> 'mss',
//			'xargs'		=> 0,
			'ei'		=> 'ISO-8859-1',
			'p'			=> $keyword,
			'b'			=> ($page - 1)*10+1
		);
	}

  function get_results_count($html, $max) {
		return $max;
		$result = 0;
		preg_match("/of about (.*?) for/is", $html, $matches);
		if (!empty($matches[1])) {
			$result = strip_tags($matches[1]);
			$result = str_replace("&nbsp;", "", $result);
			if ($max < (int)$result) $result = $max;
		};
    return (int)$result;
  }

  function parse_results($html)
  {
      $result=array();
      preg_match_all("/<div class=\"res\">(.*?)<span class=url>(.*?)<\/span>/is", $html, $matches);
      if ($matches[2]) {
          $result=$matches[2];
      }
      for($i=0;$i<count($result);$i++) {
          $result[$i]='http://'.strip_tags($result[$i]);
      }
      return $result;
  }
  
  // obsolete
  function old_parse_results($html) {
		$result = array();
		// Old, fixed by Bogdan
		// preg_match_all("/<span class=yschurl>(.*?)<\/span>/is", $html, $matches);
		preg_match_all("/<a class=yschttl href=\"(.*?)\" >(.*?)<\/a>/is", $html, $matches);
/*
    // Old stuff, we don't need it with the new regexp
		if (!empty($matches[2])) {
			array_walk($matches[2], 'test_strip_tags');
			$result = $matches[2];
		};
 */
 		if (!empty($matches[1])) {
			$result = $matches[1];
		}
		return $result;
  }
}
/*
 // Old stuff
if (!function_exists('test_strip_tags')) {
function test_strip_tags(&$item) {
	$item = 'http://' . strip_tags($item);
}
}
*/
?>
