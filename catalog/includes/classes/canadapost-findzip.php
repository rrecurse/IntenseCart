<?php

/** THIS IS A SCRAPER! NOT A TRUE API INTEGRATION!!!! **/

class CanadaPost {
	
	protected $connection_handle;
	protected $urls = array(
		'base' => 'http://www.canadapost.ca',
		'reverse_postalcode' => 'https://www.canadapost.ca/cpo/mc/personal/postalcode/fpc.jsf', //http://www.canadapost.ca/cpotools/apps/fpc/personal/findAnAddress
	);
	public $api_key;
	public $site_url;
	public $error = null;
	
	
	/**
	 * Class Constructor function
	 *
	 * @access	public
	 * @return	void
	*/
	function __construct() {
		$this->connect();
	}
	
	
	/**
	 * Initializes a new cURL session/handle
	 *
	 * @access	protected
	 * @return	boolean
	*/
	protected function connect() {
	   
		// # If there is no connection
		if ( ! is_resource($this->connection_handle)) {
			// # Try to create one
			if ( ! $this->connection_handle = curl_init()) {
				trigger_error('Could not start new CURL instance');
				$this->error = true;
				return false;
			}
		}

		$cookie = tempnam(DIR_FS_SITE.'public_html/tmp', 'CURLCOOKIE');

		curl_setopt_array($this->connection_handle, array(
			CURLOPT_HEADER => false,
			CURLOPT_POST => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1; rv:37.0) Gecko/20100101 Firefox/37.0',
			CURLOPT_COOKIEJAR => $cookie,
		));
		
		return true;
		
	}
	
	
	/**
	 * Close the current cURL session/handle
	 *
	 * @access	protected
	 * @return	boolean
	*/
	protected function disconnect()	{
		if (is_resource($this->connection_handle)) {
			curl_close($this->connection_handle);
		}
	}
	
	
	/**
	 * Send a request through the current cURL session
	 *
	 * @access	protected
	 * @param 	array                      The data to be POSTed
	 * @param 	string                     The URL to send it to
	 * @return	boolean|string
	*/
	protected function send_data($data=null, $url)	{
		// Set the url to send data to
		curl_setopt($this->connection_handle, CURLOPT_URL, $url);

		if ($data === false) {
			curl_setopt($this->connection_handle, CURLOPT_HTTPGET, true);
		} else {
			curl_setopt($this->connection_handle, CURLOPT_POST, true);
			curl_setopt($this->connection_handle, CURLOPT_POSTFIELDS, http_build_query($data));
		}
		
		// Send data and grab the result
		$response = curl_exec($this->connection_handle);
		if ($response === false) {
			trigger_error(curl_error($this->connection_handle));
			$this->error = true;
			return false;
		}
		
		return $response;
	}
	
	
	/** THIS IS A SCRAPER! NOT A TRUE API INTEGRATION!!!!
	 * Get an address from a postal code
	 *
	 * @access	public
	 * @param	string        Postal Code
	 * @return	boolean
	*/
	public function reverse_postalcode($postalcode)	{
		// # Get the CSFR values from the form
		$data = array();
		$response = $this->send_data(false, $this->urls['reverse_postalcode']);
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->validateOnParse = true;
		@$dom->loadHTML($response);

		$form = $dom->getElementById('addressComplete');

		if(strlen($form->getElementsByTagName('input')) > 0) { 
			foreach ($form->getElementsByTagName('input') as $input) {
				$data[$input->getAttribute('name')] = $input->getAttribute('value');
			}
		} else {
			error_log('Canada Post scraper did not find form element - canadaPost page structure change!');
			exit();
		}

		$data['postalCode'] = $postalcode;

		// # Part 2 (the actual request)
		$results = array();
		$response = $this->send_data($data, $this->urls['base'].$form->getAttribute('action'));
		@$dom->loadHTML($response);
		$table = $dom->getElementById('listPostalCodeResult:fpcResultsTable:tbody_element');

		if (is_null($table)) return $results;

		$rows = $table->getElementsByTagName('tr');
		$columns = array('building', 'number', 'delivery_mode', 'street', 'suite', 'city', 'province', 'postalcode');

		foreach ($rows as $row) {
			$cells = $row->getElementsByTagName('td');
			$result = array();
			foreach ($columns as $i => $col) {
				$cell = $cells->item($i);
				if ($col == 'number') {
					$children = $cell->childNodes;
					$number = explode('-', $children->item(0)->textContent);
					$result['number_start'] = $number[0];
					$result['number_end'] = $number[1];
					$result['odd_even'] = strtolower($children->item(2)->textContent);
				} else {
					$result[$col] = trim($cell->textContent);
				}
			}

			$results[] = $result;
		}

		//error_log(print_r($results[0]['city'],1));

		return $results;
	}

	function __destruct() {
		$this->disconnect();
	}
	
}

