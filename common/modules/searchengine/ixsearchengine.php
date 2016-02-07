<?
 class IXsearchengine extends IXmodule {
	var $url, $params, $method, $results_count;
	function IXsearchengine() {
		$this->url = '';
		$this->params = array();
		$this->method = 'GET';
		$this->results_count = 0;
	}
    function getSearchResults($keyword, $max) {
        $this->reset_params($keyword);
        $result_html = $this->get_page($this->url, $this->method, $this->params);
        $this->results_count = $this->get_results_count($result_html, $max);
        if (!$this->results_count)
          return array();
        $counter = 0;
        $page = 1;
        $iterations=0;
        while ($counter < $this->results_count) {
            $iterations++;
            if ($iterations>10) {
              return array();
            }
            if ($counter != 0) {
                $this->reset_params($keyword, $page);
                $result_html = $this->get_page($this->url, $this->method, $this->params);
            };
            $results = $this->parse_results($result_html);
            $temp_results_count = count($results);
            if (!$temp_results_count) {
                // If we can't extract any more results, we'd get stuck in an
                // infinite loop below, so we return what we were able to gather
                // so far. We should warn someone however that we have incomplete
                // results -- this happens when search engines change their
                // way of displaying their results.
                return $result_array;
            }
            if ($temp_results_count + $counter <= $this->results_count) {
                $counter += $temp_results_count;
                $result_array = array_merge($result_array, $results);
                if ($counter != $this->results_count) {
                    $page++;
                };
            } else {
                $i = 0;
                while ($counter < $this->results_count) {
                    $result_array[] = $results[$i++];
                    $counter++;
                };
            };
        };
        return $result_array;
    }
	function getSearchStats($keyword_array, $timestamp, $max) {
		$current_date = date("Y-m-d");
		$needed_date = date("Y-m-d", $timestamp);
		if ($needed_date > $current_date) {
			return false;
		};
		$result_array = array();
		reset ($keyword_array);
		while (list(,$keyword) = each($keyword_array)) {
			$results = $this->get_cache($keyword, $timestamp, $max);
			if (isset($results)) {
				$result_array[$keyword] = $results;
			} elseif ($needed_date == $current_date) {
				$results = $this->getSearchResults($keyword, $max);
//				if (!empty($results)) {
					$this->save_cache($keyword, $timestamp, $max, $results);
					$result_array[$keyword] = $results;
//				} else {
//					$result_array[$keyword] = array();
//				};
			} else {
				$result_array[$keyword] = array();
			};
		};
		return $result_array;
	}
	function reset_params($keyword, $page) {
		return false;
	}
	function get_results_count($html, $max) {
		return false;
	}
	function parse_results($html) {
		return false;
	}
	function get_page($url, $method = 'GET', $params = array()) {
		if (!in_array($method, array('GET', 'POST'))) {
			return false;
		}
 		// Init curl library
 		$ch = curl_init();
 		// Not include HTTP HEADER in result
 		curl_setopt ($ch, CURLOPT_HEADER, 1);
 		// Do not print result, only return it
 		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
 		// Follow location header if get redirected
 		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		if (!empty($params)) {
	    	while (list($id, $value) = each($params)) {
        		$params_pairs[] = rawurlencode($id) . "=" . rawurlencode($value);
    		};
    		$params_string = implode('&', $params_pairs);
		};
		if ($method == 'POST') {
			curl_setopt ($ch, CURLOPT_POST, 1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $params_string);
		} else {
			$url .= '?' . $params_string;
		};
 		// Set URL
 		curl_setopt ($ch, CURLOPT_URL, $url);
 		curl_setopt ($ch, CURLOPT_HTTPHEADER,array("Accept-Language" => "en-us") );
		$result = curl_exec ($ch) or curl_error($ch);
		curl_close ($ch);
		return $result;
	}
	function get_cache($keyword, $timestamp, $max) {
		$cache_result = tep_db_query("
			SELECT * FROM searchengine_cache 
			WHERE 
				searchengine_class = '".$this->db_prepare(get_class($this))."'
			and
				request_date <= FROM_UNIXTIME($timestamp, '%Y-%m-%d')
			and
				keyword = '".$this->db_prepare($keyword)."'
			and
				(max_results >= $max or real_results < max_results) 
			ORDER BY request_date DESC LIMIT 1
		");
		if ($row = tep_db_fetch_array($cache_result)) {
			return unserialize($row['result']);
		} else {
			return NULL;
		};
	}
	function save_cache($keyword, $timestamp, $max, $result) {
		tep_db_query("DELETE FROM searchengine_cache WHERE 
			searchengine_class = '".$this->db_prepare(get_class($this))."'
		and
			request_date = FROM_UNIXTIME($timestamp)
		and 
			keyword = '".$this->db_prepare($keyword)."'
		and
			real_results < ".count($result).";
		");
		tep_db_query("
			INSERT INTO searchengine_cache SET
				searchengine_class = '".$this->db_prepare(get_class($this))."',
				request_date = FROM_UNIXTIME($timestamp),
				keyword = '".$this->db_prepare($keyword)."',
				max_results = $max,
				real_results = ".count($result).",
				result = '".$this->db_prepare(serialize($result))."';
		");
	}

	function db_prepare($text) {
		return str_replace("'", "\'", $text);
	}

  function actionList() {
    return Array('test'=>'Test');
  }

  function actionPerform($ac) {
    if ($ac=='test') print_r( $this->getSearchStats(array('test', 'another test'), time(), 25));
  }
  function isReady() {
	return true;
  }


 }
?>
